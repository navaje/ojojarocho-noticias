import argparse
import logging
import sys
from pathlib import Path

from . import feeds
from .card import fecha_local, make_card
from .classifier import classify, is_official_source
from .config import ROOT_DIR, load_settings
from .dedupe import Dedupe
from .social import SocialPoster
from .summarize import resumen
from .wp import WPClient

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s %(levelname)s %(name)s: %(message)s",
    handlers=[logging.StreamHandler(sys.stdout)],
)
log = logging.getLogger("noticias")


def setup_file_logging():
    log_dir = ROOT_DIR / "logs"
    log_dir.mkdir(parents=True, exist_ok=True)
    fh = logging.FileHandler(log_dir / "bot.log", encoding="utf-8")
    fh.setFormatter(logging.Formatter("%(asctime)s %(levelname)s %(name)s: %(message)s"))
    logging.getLogger().addHandler(fh)


def run(settings, dry_run=False, local_feeds=None, only_feed=None, limit=None):
    cfg = settings.config
    state = Dedupe(settings.state_db)
    wp = None
    social = SocialPoster(
        settings.fb_page_id, settings.fb_page_access_token, settings.ig_user_id, settings.social_hashtags
    )
    if not dry_run:
        if not (settings.wp_url and settings.wp_user and settings.wp_app_password):
            log.error("Faltan WP_URL, WP_USER o WP_APP_PASSWORD en .env")
            return 2
        wp = WPClient(settings.wp_url, settings.wp_user, settings.wp_app_password, settings.wp_category_name, settings.wp_category_slug)
    max_total = limit or cfg.get("max_posts_per_run", 10)
    per_feed = cfg.get("max_items_per_feed", 25)
    resumen_chars = cfg.get("resumen_max_chars", 280)
    published = pending = skipped = 0
    feeds_cfg = cfg.get("feeds", [])
    if local_feeds:
        feeds_cfg = [{"name": "local-fixture", "url": local_feeds, "enabled": True}]
    for feed in feeds_cfg:
        if not feed.get("enabled", True):
            continue
        if only_feed and feed["name"].lower() != only_feed.lower():
            continue
        try:
            if local_feeds:
                parsed = feeds.parse_local_file(local_feeds)
            else:
                parsed = feeds.fetch_feed(feed["url"])
        except Exception as exc:
            log.warning("Feed falló %s (%s): %s", feed["name"], feed["url"], exc)
            continue
        if not parsed["entries"]:
            log.warning("Feed sin entradas válidas: %s", feed["name"])
            continue
        for item in feeds.items(parsed, per_feed):
            if published + pending >= max_total:
                break
            cls = classify(item["title"], item["body"], cfg)
            if not cls["local"]:
                skipped += 1
                continue
            key = Dedupe.key(item["url"], item["title"])
            if state.seen(key):
                skipped += 1
                continue
            oficial = is_official_source(feed["name"], cfg)
            resumen_txt = resumen(item["body"], resumen_chars)
            titulo = f"{cls['emoji']} {item['title']}"
            if dry_run:
                log.info(
                    "[DRY-RUN] status=%s tipo=%s lugar=%s fuente=%s\n  título: %s\n  url: %s\n  resumen: %s",
                    cls["status"], cls["tipo"], cls["lugar"], feed["name"], titulo, item["url"], resumen_txt[:120],
                )
                continue
            try:
                media_id = None
                image_url = None
                card_path = make_card(
                    cls["tipo"], cls["label"], cls["emoji"], cls["lugar"], item["title"], feed["name"],
                    fecha_local(settings.tz), cfg.get("site_name", "ojojarocho.online"),
                )
                if card_path:
                    media_id, image_url = wp.upload_media(card_path, titulo)
                    card_path.unlink(missing_ok=True)
                content = wp.build_content(cls["lugar"], resumen_txt, feed["name"], item["url"], oficial, cls["emoji"])
                post = wp.create_post(
                    titulo, content, cls["status"], item["url"], feed["name"],
                    cls["lugar"], cls["tipo"], cls["label"], cls["emoji"], media_id=media_id,
                )
                state.mark(key, item["url"], item["title"])
                post_url = post.get("link", item["url"])
                if cls["status"] == "publish":
                    published += 1
                    log.info("PUBLICADO: %s (%s)", post_url, cls["lugar"])
                    if social.enabled:
                        social.crosspost(cls["emoji"], cls["label"], cls["lugar"], item["title"], post_url, feed["name"], image_url)
                else:
                    pending += 1
                    log.info("PENDIENTE (revisión manual): %s — %s", post.get("id"), item["title"])
            except Exception as exc:
                log.error("Error publicando '%s': %s", item["title"], exc)
    log.info("Resumen: publicadas=%d pendientes=%d omitidas=%d", published, pending, skipped)
    state.close()
    return 0


def main(argv=None):
    parser = argparse.ArgumentParser(description="Bot de noticias locales ojojarocho.online")
    parser.add_argument("--dry-run", action="store_true", help="No publica nada, solo muestra lo que haría")
    parser.add_argument("--local-feeds", help="Usa un archivo RSS local en vez de descargar feeds (pruebas)")
    parser.add_argument("--feed", help="Procesar solo el feed con este nombre exacto")
    parser.add_argument("--limit", type=int, help="Máximo de noticias nuevas por corrida")
    args = parser.parse_args(argv)
    setup_file_logging()
    settings = load_settings()
    logging.getLogger().setLevel(settings.log_level)
    return run(settings, dry_run=args.dry_run, local_feeds=args.local_feeds, only_feed=args.feed, limit=args.limit)


if __name__ == "__main__":
    sys.exit(main())

import logging
import time

import requests

log = logging.getLogger("noticias.social")
GRAPH = "https://graph.facebook.com/v21.0"


class SocialPoster:
    def __init__(self, fb_page_id="", fb_token="", ig_user_id="", hashtags=""):
        self.fb_page_id = fb_page_id
        self.fb_token = fb_token
        self.ig_user_id = ig_user_id
        self.hashtags = hashtags
        self.enabled = bool(fb_page_id and fb_token)

    def _fb_message(self, emoji, label, lugar, titulo, url, fuente):
        return (
            f"{emoji} {label} en {lugar}\n\n{titulo}\n\n"
            f"🔗 Leer en ojojarocho.online: {url}\n\n"
            f"Fuente: {fuente}\n{self.hashtags}"
        )

    def post_facebook(self, message, link):
        r = requests.post(
            f"{GRAPH}/{self.fb_page_id}/feed",
            data={"message": message, "link": link, "access_token": self.fb_token},
            timeout=30,
        )
        r.raise_for_status()
        return r.json().get("id")

    def post_instagram(self, image_url, caption):
        r = requests.post(
            f"{GRAPH}/{self.ig_user_id}/media",
            data={"image_url": image_url, "caption": caption, "access_token": self.fb_token},
            timeout=30,
        )
        r.raise_for_status()
        creation_id = r.json()["id"]
        for _ in range(10):
            chk = requests.get(
                f"{GRAPH}/{creation_id}",
                params={"fields": "status_code", "access_token": self.fb_token},
                timeout=30,
            ).json()
            if chk.get("status_code") == "FINISHED":
                break
            time.sleep(3)
        r2 = requests.post(
            f"{GRAPH}/{self.ig_user_id}/media_publish",
            data={"creation_id": creation_id, "access_token": self.fb_token},
            timeout=30,
        )
        r2.raise_for_status()
        return r2.json().get("id")

    def crosspost(self, emoji, label, lugar, titulo, post_url, fuente, image_url=None):
        if not self.enabled:
            log.info("Redes sociales deshabilitadas (faltan FB_PAGE_ID / FB_PAGE_ACCESS_TOKEN)")
            return
        message = self._fb_message(emoji, label, lugar, titulo, post_url, fuente)
        try:
            self.post_facebook(message, post_url)
            log.info("Publicado en Facebook: %s", post_url)
        except Exception as exc:
            log.error("Error publicando en Facebook: %s", exc)
        if self.ig_user_id and image_url:
            try:
                self.post_instagram(image_url, message)
                log.info("Publicado en Instagram: %s", post_url)
            except Exception as exc:
                log.error("Error publicando en Instagram: %s", exc)

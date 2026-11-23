import json
import os
from dataclasses import dataclass, field
from pathlib import Path

try:
    from dotenv import load_dotenv
except Exception:
    def load_dotenv(path):
        with open(path, encoding="utf-8") as f:
            for line in f:
                line = line.strip()
                if not line or line.startswith("#") or "=" not in line:
                    continue
                k, v = line.split("=", 1)
                os.environ.setdefault(k.strip(), v.strip().strip('"').strip("'"))

BASE_DIR = Path(__file__).resolve().parent
ROOT_DIR = BASE_DIR.parent
CONFIG_JSON = BASE_DIR / "config.json"


@dataclass
class Settings:
    wp_url: str = ""
    wp_user: str = ""
    wp_app_password: str = ""
    wp_category_name: str = "Noticias locales"
    wp_category_slug: str = "noticias-locales"
    fb_page_id: str = ""
    fb_page_access_token: str = ""
    ig_user_id: str = ""
    social_hashtags: str = "#Cardel #Veracruz #Xalapa #NoticiasLocales"
    dry_run: bool = False
    log_level: str = "INFO"
    state_db: str = str(ROOT_DIR / "data" / "state.db")
    tz: str = "America/Mexico_City"
    config: dict = field(default_factory=dict)


def load_settings(env_path=None, config_path=None):
    env_file = Path(env_path) if env_path else ROOT_DIR / ".env"
    if env_file.exists():
        load_dotenv(env_file)
    with open(config_path or CONFIG_JSON, encoding="utf-8") as f:
        cfg = json.load(f)
    s = Settings(config=cfg)
    s.wp_url = os.getenv("WP_URL", s.wp_url).rstrip("/")
    s.wp_user = os.getenv("WP_USER", s.wp_user)
    s.wp_app_password = os.getenv("WP_APP_PASSWORD", s.wp_app_password)
    s.wp_category_name = os.getenv("WP_CATEGORY_NAME", cfg.get("wp_category_name", s.wp_category_name))
    s.wp_category_slug = os.getenv("WP_CATEGORY_SLUG", cfg.get("wp_category_slug", s.wp_category_slug))
    s.fb_page_id = os.getenv("FB_PAGE_ID", s.fb_page_id)
    s.fb_page_access_token = os.getenv("FB_PAGE_ACCESS_TOKEN", s.fb_page_access_token)
    s.ig_user_id = os.getenv("IG_USER_ID", s.ig_user_id)
    s.social_hashtags = os.getenv("SOCIAL_HASHTAGS", s.social_hashtags)
    s.dry_run = os.getenv("DRY_RUN", "false").lower() in ("1", "true", "yes")
    s.log_level = os.getenv("LOG_LEVEL", s.log_level).upper()
    s.state_db = os.getenv("STATE_DB", s.state_db)
    s.tz = os.getenv("TZ", s.tz)
    return s

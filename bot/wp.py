import re
import unicodedata

import requests

_WS = re.compile(r"\s+")
_SLUG_STRIP = re.compile(r"[^a-z0-9]+")


def slugify(text, max_len=60):
    t = unicodedata.normalize("NFKD", text or "")
    t = "".join(c for c in t if not unicodedata.combining(c)).lower()
    t = _SLUG_STRIP.sub("-", t).strip("-")
    return _WS.sub("-", t)[:max_len].strip("-") or "noticia"


class WPClient:
    def __init__(self, base_url, user, app_password, category_name, category_slug, timeout=30):
        self.base = base_url.rstrip("/")
        self.api = f"{self.base}/wp-json/wp/v2"
        self.session = requests.Session()
        self.session.auth = (user, app_password)
        self.session.headers.update({"Accept": "application/json"})
        self.timeout = timeout
        self._category_id = None
        self.category_name = category_name
        self.category_slug = category_slug

    def _get(self, path, params=None):
        r = self.session.get(f"{self.api}{path}", params=params, timeout=self.timeout)
        r.raise_for_status()
        return r.json()

    def _post(self, path, json=None, data=None, headers=None):
        h = dict(headers or {})
        r = self.session.post(f"{self.api}{path}", json=json, data=data, headers=h, timeout=self.timeout)
        r.raise_for_status()
        return r.json()

    def ensure_category(self):
        if self._category_id:
            return self._category_id
        found = self._get("/categories", {"slug": self.category_slug, "per_page": 5})
        if found:
            self._category_id = found[0]["id"]
        else:
            created = self._post("/categories", json={"name": self.category_name, "slug": self.category_slug})
            self._category_id = created["id"]
        return self._category_id

    def ensure_tag(self, name):
        slug = slugify(name, 40)
        found = self._get("/tags", {"slug": slug, "per_page": 5})
        if found:
            return found[0]["id"]
        try:
            created = self._post("/tags", json={"name": name, "slug": slug})
            return created["id"]
        except requests.HTTPError:
            found = self._get("/tags", {"search": name, "per_page": 10})
            if found:
                return found[0]["id"]
            raise

    def upload_media(self, png_path, title):
        with open(png_path, "rb") as f:
            data = f.read()
        r = self.session.post(
            f"{self.api}/media",
            data=data,
            headers={
                "Content-Type": "image/png",
                "Content-Disposition": f'attachment; filename="{png_path.name}"',
            },
            timeout=self.timeout,
        )
        r.raise_for_status()
        j = r.json()
        return j["id"], j["source_url"]

    def build_content(self, lugar, resumen, fuente, url_fuente, oficial, emoji):
        oficial_html = '<p><strong>✅ Fuente oficial</strong></p>' if oficial else ""
        return (
            f"<p><strong>📍 {lugar}</strong></p>"
            f"<p>{resumen}</p>"
            f"{oficial_html}"
            f'<p>Fuente: <a href="{url_fuente}" target="_blank" rel="noopener nofollow">{fuente}</a></p>'
            f"<p><em>Noticia en desarrollo. Esta entrada se actualiza con la información verificada. {emoji}</em></p>"
        )

    def create_post(self, title, content, status, url_fuente, fuente, lugar, tipo, label, emoji, media_id=None):
        cat_id = self.ensure_category()
        tag_names = ["noticias locales", "veracruz"]
        if lugar:
            tag_names.append(lugar)
        if label:
            tag_names.append(label)
        tag_ids = [self.ensure_tag(n) for n in tag_names]
        slug = f"{slugify(title)}-{url_fuente.__hash__() & 0xffff:x}"
        payload = {
            "title": title,
            "content": content,
            "status": status,
            "slug": slug[:90],
            "categories": [cat_id],
            "tags": tag_ids,
            "meta": {
                "_oj_tipo": tipo,
                "_oj_label": label,
                "_oj_emoji": emoji,
                "_oj_lugar": lugar or "",
                "_oj_fuente": fuente,
                "_oj_url_fuente": url_fuente,
            },
        }
        if media_id:
            payload["featured_media"] = media_id
        return self._post("/posts", json=payload)

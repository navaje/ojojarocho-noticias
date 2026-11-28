import re
import unicodedata

_WS = re.compile(r"\s+")


def normalize(text):
    if not text:
        return ""
    text = unicodedata.normalize("NFKD", text)
    text = "".join(c for c in text if not unicodedata.combining(c))
    return _WS.sub(" ", text.lower()).strip()


def find_lugar(text_norm, geo_keywords):
    for geo in geo_keywords:
        if geo["key"] in text_norm:
            return geo["name"]
    return None


def classify(title, body, cfg):
    text_norm = normalize(f"{title} {body}")
    lugar = find_lugar(text_norm, cfg.get("geo_keywords", []))
    tipos = cfg.get("tipos", {})
    tipo = "general"
    for name in cfg.get("tipo_order", ["general"]):
        kws = tipos.get(name, {}).get("keywords", [])
        if any(normalize(k) in text_norm for k in kws):
            tipo = name
            break
    t = tipos.get(tipo, tipos.get("general", {}))
    auto = tipo in cfg.get("auto_publish_types", [])
    return {
        "local": lugar is not None,
        "lugar": lugar,
        "tipo": tipo,
        "label": t.get("label", tipo),
        "emoji": t.get("emoji", "📰"),
        "auto": auto,
        "status": "publish" if auto else "pending",
    }


def is_official_source(source_name, cfg):
    n = normalize(source_name or "")
    return any(normalize(k) in n for k in cfg.get("official_source_keywords", []))

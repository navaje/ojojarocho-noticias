import html
import re

_TAG = re.compile(r"<[^>]+>")
_WS = re.compile(r"\s+")


def strip_html(raw):
    if not raw:
        return ""
    text = _TAG.sub(" ", raw)
    text = html.unescape(text)
    return _WS.sub(" ", text).strip()


def resumen(text, max_chars=280):
    text = strip_html(text)
    if len(text) <= max_chars:
        return text
    cut = text[:max_chars]
    if " " in cut:
        cut = cut[: cut.rfind(" ")]
    return cut.rstrip(" ,;:") + "…"

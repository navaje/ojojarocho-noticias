import textwrap
from datetime import datetime
from pathlib import Path
from zoneinfo import ZoneInfo

CARD_DIR = Path(__file__).resolve().parent.parent / "cards"
W, H = 1200, 630

COLORS = {
    "seguridad": ("#7f1d1d", "#450a0a"),
    "vialidad": ("#92400e", "#451a03"),
    "clima": ("#1e3a8a", "#172554"),
    "general": ("#134e4a", "#042f2e"),
}

_FONT_CANDIDATES = [
    "/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf",
    "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf",
    "/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf",
    "/usr/share/fonts/TTF/DejaVuSans-Bold.ttf",
]


def _font(size, bold=True):
    from PIL import ImageFont

    name = _FONT_CANDIDATES[0] if bold else _FONT_CANDIDATES[1]
    for cand in ([name] if bold else _FONT_CANDIDATES[1:]):
        try:
            if Path(cand).exists():
                return ImageFont.truetype(cand, size)
        except Exception:
            continue
    try:
        return ImageFont.load_default(size)
    except TypeError:
        return ImageFont.load_default()


def _wrap(draw, text, font, max_w):
    words = text.split()
    lines, cur = [], ""
    for w in words:
        t = (cur + " " + w).strip()
        if draw.textlength(t, font=font) <= max_w:
            cur = t
        else:
            if cur:
                lines.append(cur)
            cur = w
    if cur:
        lines.append(cur)
    return lines


def make_card(tipo, label, emoji, lugar, titulo, fuente, fecha_str, site_name):
    try:
        from PIL import Image, ImageDraw
    except Exception:
        return None
    CARD_DIR.mkdir(parents=True, exist_ok=True)
    top, bottom = COLORS.get(tipo, COLORS["general"])
    img = Image.new("RGB", (W, H), bottom)
    draw = ImageDraw.Draw(img)
    for y in range(H):
        r = y / H
        c = tuple(int(int(top[i:i+2], 16) * (1 - r) + int(bottom[i:i+2], 16) * r) for i in (1, 3, 5))
        draw.line([(0, y), (W, y)], fill=c)
    draw.rectangle([0, 0, W, 86], fill="#0b1220")
    draw.text((40, 24), site_name, font=_font(34), fill="#f8fafc")
    badge = f"{emoji} {label.upper()}"
    draw.rounded_rectangle([40, 120, 40 + draw.textlength(badge, font=_font(40)) + 36, 190], radius=14, fill=top, outline="#f8fafc", width=2)
    draw.text((58, 132), badge, font=_font(40), fill="#ffffff")
    f_lugar = _font(64)
    draw.text((40, 220), (lugar or "Veracruz").upper(), font=f_lugar, fill="#fde68a")
    f_title = _font(34, bold=False)
    lines = _wrap(draw, titulo, f_title, W - 80)[:4]
    y = 310
    for ln in lines:
        draw.text((40, y), ln, font=f_title, fill="#e5e7eb")
        y += 46
    draw.rectangle([0, H - 70, W, H], fill="#0b1220")
    draw.text((40, H - 52), f"📅 {fecha_str}   ·   Fuente: {fuente} · noticia en desarrollo", font=_font(26, bold=False), fill="#94a3b8")
    out = CARD_DIR / f"card-{datetime.now().timestamp():.0f}.png"
    img.save(out, "PNG")
    return out


def fecha_local(tz_name):
    try:
        tz = ZoneInfo(tz_name)
    except Exception:
        tz = ZoneInfo("America/Mexico_City")
    return datetime.now(tz).strftime("%d %b %Y · %H:%M")

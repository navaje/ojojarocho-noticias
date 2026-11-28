import xml.etree.ElementTree as ET

import requests

UA = "Mozilla/5.0 (X11; Linux x86_64; rv:128.0) Gecko/20100101 Firefox/128.0 ojojarocho-noticias-bot/1.0"
ATOM = "{http://www.w3.org/2005/Atom}"
CONTENT = "{http://purl.org/rss/1.0/modules/content/}"


def fetch_feed(url, timeout=25):
    resp = requests.get(
        url,
        timeout=timeout,
        headers={"User-Agent": UA, "Accept": "application/rss+xml, application/atom+xml, application/xml, text/xml, */*"},
    )
    resp.raise_for_status()
    return parse_bytes(resp.content)


def parse_local_file(path):
    with open(path, "rb") as f:
        return parse_bytes(f.read())


def _text(el):
    if el is None or el.text is None:
        return ""
    return el.text.strip()


def _atom_link(entry):
    for link in entry.findall(f"{ATOM}link"):
        if link.get("href"):
            return link.get("href")
    return ""


def parse_bytes(data):
    root = ET.fromstring(data)
    entries = []
    if root.tag.endswith("}rss") or root.tag == "rss":
        channel = root.find("channel")
        if channel is None:
            return {"entries": []}
        for item in channel.findall("item"):
            body = _text(item.find(f"{CONTENT}encoded")) or _text(item.find("description"))
            entries.append(
                {
                    "title": _text(item.find("title")),
                    "url": _text(item.find("link")) or _text(item.find("guid")),
                    "body": body,
                    "published": _text(item.find("pubDate")) or _text(item.find("{http://purl.org/dc/elements/1.1/}date")),
                }
            )
    elif root.tag == f"{ATOM}feed":
        for entry in root.findall(f"{ATOM}entry"):
            body = _text(entry.find(f"{ATOM}content")) or _text(entry.find(f"{ATOM}summary"))
            entries.append(
                {
                    "title": _text(entry.find(f"{ATOM}title")),
                    "url": _atom_link(entry) or _text(entry.find(f"{ATOM}id")),
                    "body": body,
                    "published": _text(entry.find(f"{ATOM}published")) or _text(entry.find(f"{ATOM}updated")),
                }
            )
    else:
        raise ValueError("Formato de feed no soportado")
    return {"entries": [e for e in entries if e["title"] and e["url"]]}


def items(parsed, limit):
    return parsed["entries"][:limit]

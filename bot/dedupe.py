import hashlib
import sqlite3
from pathlib import Path

from .classifier import normalize


class Dedupe:
    def __init__(self, db_path):
        self.db_path = Path(db_path)
        self.db_path.parent.mkdir(parents=True, exist_ok=True)
        self.conn = sqlite3.connect(str(self.db_path))
        self.conn.execute(
            "CREATE TABLE IF NOT EXISTS items ("
            "hash TEXT PRIMARY KEY, url TEXT, title TEXT, first_seen TEXT DEFAULT CURRENT_TIMESTAMP)"
        )
        self.conn.commit()

    @staticmethod
    def key(url, title):
        base = normalize(url or "") or normalize(title or "")
        return hashlib.sha1(base.encode("utf-8")).hexdigest()

    def seen(self, key):
        cur = self.conn.execute("SELECT 1 FROM items WHERE hash = ?", (key,))
        return cur.fetchone() is not None

    def mark(self, key, url, title):
        self.conn.execute(
            "INSERT OR IGNORE INTO items (hash, url, title) VALUES (?, ?, ?)",
            (key, url or "", title or ""),
        )
        self.conn.commit()

    def close(self):
        self.conn.close()

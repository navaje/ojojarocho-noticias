import tempfile
import unittest
from pathlib import Path

from bot.dedupe import Dedupe


class TestDedupe(unittest.TestCase):
    def test_seen_y_mark(self):
        with tempfile.TemporaryDirectory() as d:
            db = Dedupe(Path(d) / "state.db")
            k = Dedupe.key("https://ejemplo.mx/nota-1", "Título")
            self.assertFalse(db.seen(k))
            db.mark(k, "https://ejemplo.mx/nota-1", "Título")
            self.assertTrue(db.seen(k))
            db.close()

    def test_key_igual_para_misma_url(self):
        self.assertEqual(Dedupe.key("https://a.mx/1", "x"), Dedupe.key("https://a.mx/1", "otro título"))


if __name__ == "__main__":
    unittest.main()

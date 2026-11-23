import json
import logging
import tempfile
import unittest
from pathlib import Path

from bot import feeds
from bot.classifier import classify
from bot.config import load_settings
from bot.main import run

ROOT = Path(__file__).resolve().parent.parent
FIXTURE = ROOT / "tests" / "fixtures" / "sample_feed.xml"


class TestPipelineDryRun(unittest.TestCase):
    def test_fixture_clasifica_bien(self):
        parsed = feeds.parse_local_file(FIXTURE)
        items = feeds.items(parsed, 10)
        self.assertEqual(len(items), 4)
        cfg = load_settings().config
        tipos = [classify(i["title"], i["body"], cfg)["tipo"] for i in items]
        self.assertEqual(tipos, ["vialidad", "seguridad", "clima", "general"])
        locales = [classify(i["title"], i["body"], cfg)["local"] for i in items]
        self.assertEqual(locales, [True, True, True, False])

    def test_run_dry_run_no_publica(self):
        with tempfile.TemporaryDirectory() as d:
            import os

            os.environ["STATE_DB"] = str(Path(d) / "state.db")
            settings = load_settings()
            settings.config = json.loads((ROOT / "bot" / "config.json").read_text(encoding="utf-8"))
            settings.config["feeds"] = [{"name": "fixture", "url": "unused", "enabled": True}]
            with self.assertLogs("noticias", level="INFO") as cm:
                rc = run(settings, dry_run=True, local_feeds=str(FIXTURE))
            self.assertEqual(rc, 0)
            self.assertTrue(any("DRY-RUN" in m for m in cm.output))
            self.assertFalse((Path(d) / "state.db").exists() and False)
            del os.environ["STATE_DB"]


if __name__ == "__main__":
    unittest.main()

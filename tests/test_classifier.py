import json
import unittest
from pathlib import Path

from bot.classifier import classify, is_official_source, normalize

CFG = json.loads((Path(__file__).resolve().parent.parent / "bot" / "config.json").read_text(encoding="utf-8"))


class TestNormalize(unittest.TestCase):
    def test_quita_acentos_y_minúsculas(self):
        self.assertEqual(normalize("Medellín de Bravo  VERACRUZ"), "medellin de bravo veracruz")


class TestClassify(unittest.TestCase):
    def test_accidente_en_cardel_es_vialidad_publica(self):
        c = classify("Accidente múltiple en carretera Xalapa-Veracruz a la altura de Cardel", "cierre parcial", CFG)
        self.assertTrue(c["local"])
        self.assertEqual(c["tipo"], "vialidad")
        self.assertEqual(c["lugar"], "Cardel")
        self.assertEqual(c["status"], "publish")

    def test_homicidio_en_xalapa_es_seguridad_pendiente(self):
        c = classify("Asesinan a joven en colonia Centro de Xalapa", "homicidio investigado por la FGE", CFG)
        self.assertTrue(c["local"])
        self.assertEqual(c["tipo"], "seguridad")
        self.assertEqual(c["lugar"], "Xalapa")
        self.assertEqual(c["status"], "pending")

    def test_frente_frio_en_veracruz_es_clima_publica(self):
        c = classify("Frente frío 8 afectará la zona centro de Veracruz", "lluvias y vientos", CFG)
        self.assertTrue(c["local"])
        self.assertEqual(c["tipo"], "clima")
        self.assertEqual(c["status"], "publish")

    def test_noticia_nacional_no_es_local(self):
        c = classify("Bursátil de EU cierra al alza", "mercados internacionales", CFG)
        self.assertFalse(c["local"])

    def test_bloqueo_con_armas_es_seguridad_no_vialidad(self):
        c = classify("Narcobloqueo en carretera federal 180 cerca de Poza Rica", "vehículos incendiados, bloqueo", CFG)
        self.assertEqual(c["tipo"], "seguridad")
        self.assertEqual(c["lugar"], "Poza Rica")

    def test_oficial(self):
        self.assertTrue(is_official_source("Protección Civil Veracruz", CFG))
        self.assertFalse(is_official_source("Imagen del Golfo", CFG))


if __name__ == "__main__":
    unittest.main()

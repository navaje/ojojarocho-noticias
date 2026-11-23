import unittest

from bot.summarize import resumen, strip_html


class TestSummarize(unittest.TestCase):
    def test_strip_html(self):
        self.assertEqual(strip_html("<p>Hola <strong>mundo</strong></p>"), "Hola mundo")

    def test_trunca_en_palabra(self):
        texto = "a " * 200
        r = resumen(texto, 50)
        self.assertTrue(r.endswith("…"))
        self.assertLessEqual(len(r), 52)

    def test_corto_no_se_trunca(self):
        self.assertEqual(resumen("Texto corto", 280), "Texto corto")


if __name__ == "__main__":
    unittest.main()

# ojojarocho-noticias

Sistema automático de **noticias locales** para [ojojarocho.online](https://ojojarocho.online).

Lee RSS de medios de Veracruz, filtra por proximidad a **Cardel, Xalapa, Veracruz y alrededores**, clasifica el tipo de noticia (alerta vial, clima, seguridad, general), evita duplicados y publica en WordPress (entradas de la categoría **Noticias locales** + página **Noticias de hoy**). Las noticias de vialidad y clima se publican solas; las de seguridad y otras quedan como **pendientes** para tu revisión. Las publicadas se cross-postean a tu página de **Facebook** e **Instagram**.

## Cómo funciona

```
RSS de medios de Veracruz ──► filtro geográfico (Cardel, Xalapa, Veracruz...)
      │                        filtro por tipo (vialidad / clima / seguridad / general)
      │                        anti-duplicados (sqlite)
      ▼
tarjeta PNG propia (Pillow) ──► WordPress REST API ──► entrada en "Noticias locales"
                                                          │  publish (vialidad/clima)
                                                          │  pending (seguridad/otros → tú apruebas)
                                                          ▼
                                              Facebook Page + Instagram (Graph API)
```

Corre como **cron en tu hosting** (cada 30 min recomendado).

## Estructura del repo

```
bot/                  código Python del bot (config.json: feeds, keywords, reglas)
tests/                pruebas unitarias + fixture RSS de ejemplo
wp-plugin/ojojarocho-noticias/   plugin de WordPress (shortcodes, schema, estilos)
docs/                 guías paso a paso de instalación y configuración
```

## Orden recomendado de instalación

1. [docs/01-INSTALACION_HOSTING.md](docs/01-INSTALACION_HOSTING.md) — subir el repo al hosting, entorno virtual, `.env`, prueba en seco y cron.
2. [docs/02-CONFIG_WORDPRESS.md](docs/02-CONFIG_WORDPRESS.md) — Application Password, instalar el plugin, crear la página "Noticias de hoy", menú y sección en la portada.
3. [docs/03-CONFIG_META_FACEBOOK_INSTAGRAM.md](docs/03-CONFIG_META_FACEBOOK_INSTAGRAM.md) — app de Meta, token de página y cuenta de Instagram para el cross-post.
4. [docs/04-REGLAS_Y_SEGURIDAD.md](docs/04-REGLAS_Y_SEGURIDAD.md) — qué se publica automático, cómo aprobar pendientes, política de nota roja, derechos de autor y seguridad de credenciales.

## Prueba rápida local (sin tocar WordPress)

```bash
python3 -m venv venv && source venv/bin/activate
pip install -r requirements.txt
python -m unittest discover -s tests -v          # pruebas unitarias
python -m bot --dry-run --local-feeds tests/fixtures/sample_feed.xml   # simula una corrida
```

## Reglas de publicación (editables en `bot/config.json`)

| Tipo | Ejemplos | Estado automático |
|---|---|---|
| 🚧 Alerta vial | accidentes, cierres, bloqueos, tráfico en carretera | `publish` |
| ⛈️ Clima | frentes fríos, lluvias, alertas de Protección Civil | `publish` |
| 🚨 Seguridad | homicidios, baleos, secuestros | `pending` (tú apruebas en WP) |
| 📰 General local | cualquier otra noticia con lugar de la zona | `pending` |

Solo se publican noticias donde aparece un lugar de la lista geográfica (Cardel primero). Nunca se copian textos completos de los medios: solo titular + resumen corto + enlace a la fuente original.

## Seguridad

- Las credenciales viven solo en `.env` en el servidor (nunca en git; `.env` está en `.gitignore`).
- El bot se instala **fuera de `public_html`**; si debe ir dentro, se protege con `.htaccess` (ver guía de hosting).
- Tokens de Meta: usar **System User** para que no expiren (ver guía de Meta).

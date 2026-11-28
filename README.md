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

## Instalación recomendada (sin terminal/SSH)

Todo dentro de WordPress como plugin (sirve en HostGator aunque el shell esté bloqueado):

1. Descarga el plugin listo: [ojojarocho-noticias-plugin.zip](https://github.com/navaje/ojojarocho-noticias/raw/vibe/noticias-locales-6676d1/ojojarocho-noticias-plugin.zip)
2. wp-admin → Plugins → Añadir nuevo → Subir plugin → instalar → **activar**
3. wp-admin → **Noticias bot** → "Buscar noticias y publicar ahora" para probar
4. Crea la página "Noticias de hoy" con `[noticias_hoy limite="12"]`, ponla en el menú, y `[noticias_recientes n="5"]` en la portada
5. (Opcional) Configura Facebook/Instagram en Noticias bot → Configuración

Guía detallada paso a paso: [docs/05-INSTALACION_PLUGIN_SIN_SSH.md](docs/05-INSTALACION_PLUGIN_SIN_SSH.md)

## Instalación alternativa (con SSH en el hosting)

Si tu hosting tiene shell habilitado, puedes correr el bot en Python con cron del sistema: ver [docs/01-INSTALACION_HOSTING.md](docs/01-INSTALACION_HOSTING.md). El código Python está en `bot/`.

## Prueba rápida local (solo si tienes Python)

```bash
pip install -r requirements.txt
python -m unittest discover -s tests -v
python -m bot --dry-run --local-feeds tests/fixtures/sample_feed.xml
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

# 04 — Reglas de publicación, nota roja y seguridad

## Qué se publica solo y qué espera tu aprobación

| Tipo | Ejemplos | Estado |
|---|---|---|
| 🚧 vialidad | accidentes, cierres de carretera, bloqueos, tráfico | **publica solo** |
| ⛈️ clima | frentes fríos, lluvias, alertas de PC | **publica solo** |
| 🚨 seguridad | homicidios, baleos, secuestros, bloqueos armados | **pendiente** |
| 📰 general | cualquier otra noticia local | **pendiente** |

Para aprobar una pendiente: wp-admin → **Entradas → Pendientes** → edita si quieres → **Publicar**. Al publicarla manualmente puedes cross-postearla a mano a Facebook/Instagram si lo deseas.

Cambiar las reglas: edita `bot/config.json` → `auto_publish_types` (lista de tipos que se publican solos). Reinicia el cron (no hace falta; el cron lee el archivo en cada corrida).

## Política de nota roja (recomendada)

- Las noticias de seguridad **siempre pasan por ti** antes de ver la luz.
- Al aprobarlas, evita datos sensibles de víctimas: nombres completos, domicilios, placas, rostros. Puedes editar el resumen antes de publicar.
- No uses imágenes explícitas; el bot genera tarjetas neutras con el texto del tipo de alerta.
- Si la noticia es grave y hay duda de veracidad, mejor no publicarla: una falsa alarma daña más tu reputación que no dar la noticia.

## Derechos de autor (por qué solo titular + resumen + enlace)

- El bot **nunca copia el texto completo** de la fuente: toma las primeras oraciones como resumen (máx. ~280 caracteres) y siempre enlaza al original con `rel="noopener nofollow"`.
- Las imágenes son **tarjetas propias** generadas con Pillow (sin fotos de terceros).
- Mantén el enlace "Fuente: …" visible; es la atribución correcta para agregación de noticias.

## Anti-duplicados y frecuencia

- Cada noticia se registra por hash de su URL en `data/state.db`; nunca se republica la misma URL.
- Cron cada 30 min; si un medio publica mucho, ajusta `max_items_per_feed` y `max_posts_per_run` en `bot/config.json`.

## Seguridad operativa

- `.env` con `chmod 600`, fuera de `public_html` (o con `.htaccess` `Require all denied`), y **nunca** en git (`.gitignore` ya lo excluye).
- Application Password de WP: si se filtra, revócala en Usuarios → Tu perfil → Application Passwords.
- Tokens de Meta: System User con token sin caducidad; revocar y regenerar si se filtran (Business → System users).
- El bot no abre puertos ni escucha nada; solo hace peticiones salientes a los feeds, a tu WP y a graph.facebook.com.
- Logs en `logs/` (rotación en guía 01). No se loguean credenciales.

## Monitoreo

- `tail -f ~/ojojarocho-noticias/logs/cron.log` para ver corridas.
- Si una corrida falla seguido en un feed (Cloudflare, 403), desactiva ese feed en `config.json` (`"enabled": false`) o sube el User-Agent en `bot/feeds.py`.
- Para forzar republicar una noticia en pruebas, borra su fila en `data/state.db` o usa `--dry-run` para ver qué haría.

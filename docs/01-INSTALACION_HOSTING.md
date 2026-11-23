# 01 — Instalación en el hosting (cron)

Requisitos del hosting: acceso SSH, **Python 3.9+**, `pip`, y cron habilitado. Todo se instala **fuera de `public_html`** (por ejemplo en `/home/tuusuario/ojojarocho-noticias`).

## 1. Subir el código al servidor

```bash
cd ~
git clone https://github.com/navaje/ojojarocho-noticias.git
cd ojojarocho-noticias
git checkout vibe/noticias-locales-6676d1   # o main cuando se fusione
```

## 2. Entorno virtual e dependencias

```bash
python3 -m venv venv
source venv/bin/activate
pip install -r requirements.txt
```

Si el hosting no tiene Pillow compilable, el bot sigue funcionando: publica sin tarjeta de imagen y el cross-post a Instagram usa el enlace (ver guía de Meta).

## 3. Crear y proteger el `.env`

```bash
cp .env.example .env
nano .env        # pega los valores (ver guías 02 y 03)
chmod 600 .env
```

Mínimo indispensable para la primera prueba: `WP_URL`, `WP_USER`, `WP_APP_PASSWORD`.

## 4. Probar en seco (no publica nada)

```bash
python -m bot --dry-run --local-feeds tests/fixtures/sample_feed.xml
```

Debe mostrar 3 noticias clasificadas (vialidad, seguridad, clima) y descartar la nacional.

Prueba real contra los feeds (sin publicar todavía):

```bash
python -m bot --dry-run --limit 5
```

## 5. Primera corrida real

```bash
python -m bot --limit 5
```

Revisa en WordPress → Entradas: las de vialidad/clima quedan **publicadas**, las de seguridad como **pendientes**.

## 6. Activar el cron (cada 30 minutos)

```bash
crontab -e
```

Añadir:

```
*/30 * * * * cd /home/TU_USUARIO/ojojarocho-noticias && /home/TU_USUARIO/ojojarocho-noticias/venv/bin/python -m bot >> logs/cron.log 2>&1
```

Reemplaza `TU_USUARIO`. Verifica que corre:

```bash
tail -20 ~/ojojarocho-noticias/logs/cron.log
tail -20 ~/ojojarocho-noticias/logs/bot.log
```

## 7. Si el proyecto queda dentro de public_html

Crear `ojojarocho-noticias/.htaccess` con:

```
Require all denied
```

y mover fuera de `public_html` lo sensible: `.env`, `data/`, `logs/` (pueden vivir en `~/` y apuntar con rutas absolutas en `.env`, p. ej. `STATE_DB=/home/TU_USUARIO/noticias-data/state.db`).

## 8. Verificar/ampliar la lista de feeds

Desde el servidor (donde no hay bloqueo anti-bot del sandbox):

```bash
for u in $(jq -r '.feeds[].url' bot/config.json); do echo -n "$u -> "; curl -s -o /dev/null -w "%{http_code} %{content_type}\n" --max-time 15 -A "Mozilla/5.0" "$u"; done
```

Los que regresen `404` o `text/html` desactívalos en `bot/config.json` (`"enabled": false`) o sustitúyelos. Para agregar un medio nuevo basta añadir:

```json
{"name": "Nombre del medio", "url": "https://medio.mx/rss", "enabled": true}
```

## 9. Actualizar el bot en el servidor

```bash
cd ~/ojojarocho-noticias && git pull && source venv/bin/activate && pip install -r requirements.txt
```

## 10. Rotación de logs (opcional)

En el cron, truncar cuando crezca:

```
0 3 * * * find /home/TU_USUARIO/ojojarocho-noticias/logs -name "*.log" -size +5M -exec truncate -s 0 {} \;
```

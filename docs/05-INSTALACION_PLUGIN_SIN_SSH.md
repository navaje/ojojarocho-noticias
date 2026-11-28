# 05 — Instalación 100% dentro de WordPress (SIN terminal/SSH) ⭐ recomendada para HostGator sin shell

Esta es la forma más simple: **todo vive dentro de WordPress como plugin**. No necesitas terminal, SSH, Python ni nada instalado en el servidor. El bot corre con el cron interno de WordPress (WP-Cron, cada 30 min).

## Paso 1 — Descargar el plugin listo

En tu navegador entra a esta URL y se descarga el ZIP solo:

```
https://github.com/navaje/ojojarocho-noticias/raw/vibe/noticias-locales-6676d1/ojojarocho-noticias-plugin.zip
```

(Si ya descargaste el repo completo, el ZIP también está dentro: `ojojarocho-noticias-plugin.zip`.)

## Paso 2 — Subirlo a WordPress

1. Entra a `https://ojojarocho.online/wp-admin`
2. **Plugins → Añadir nuevo → Subir plugin**
3. Elige el archivo `ojojarocho-noticias-plugin.zip` → **Instalar ahora** → **Activar plugin**
   - Si ya tenías una versión vieja instalada: en "Subir plugin" elige el nuevo ZIP y confirma **"Reemplazar el plugin actual"**, luego reactiva.

Al activarse, el plugin programa solo una tarea cada 30 minutos y crea la categoría **"Noticias locales"**.

## Paso 3 — Probar el bot a mano

1. En el menú izquierdo de wp-admin aparece **📰 Noticias bot** → entra.
2. Clic en **"Buscar noticias y publicar ahora"**.
3. Abajo verás el **log** de la corrida. Debe decir algo como `publicadas=2 pendientes=1 omitidas=30`.
4. Ve a **Entradas**: las de vialidad/clima quedan **publicadas**; las de seguridad quedan en **Pendientes** (tú las apruebas).

## Paso 4 — La página de noticias en tu sitio

1. **Páginas → Añadir nueva** → título `Noticias de hoy` → en el cuerpo pega: `[noticias_hoy limite="12"]` → **Publicar**.
2. **Apariencia → Menús** → agrega la página "Noticias de hoy" al menú principal → Guardar.
3. En tu página de inicio: botón **+ → bloque Shortcode** → pega `[noticias_recientes n="5"]` → Actualizar.
4. Visita `https://ojojarocho.online/noticias-de-hoy/` → ya deben verse las tarjetas de noticias.

## Paso 5 — Que corra solo cada 30 min (WP-Cron)

WordPress ya lo programó al activar el plugin. WP-Cron se dispara cuando alguien visita tu página; como tu sitio tiene visitas, correrá solo.

Si tu sitio pasa mucho tiempo sin visitas y quieres que sea puntual, agrega un cron externo (no necesita shell, solo una petición web):

- **Opción A — cron de cPanel:** cPanel → Cron Jobs → cada 30 min → comando:
  ```
  curl -s "https://ojojarocho.online/wp-cron.php?doing_wp_cron" >/dev/null 2>&1
  ```
  (En HostGator con shell deshabilitado a veces los cron no corren; si falla, usa la opción B.)
- **Opción B — servicio gratis externo:** entra a [cron-job.org](https://cron-job.org) (gratis) → crea un job cada 30 min con la URL `https://ojojarocho.online/wp-cron.php?doing_wp_cron` → listo.

## Paso 6 — Facebook e Instagram (cuando quieras)

1. En wp-admin → **Noticias bot** → sección Configuración → llena:
   - **Facebook Page ID** y **Facebook Page Access Token** (los sacas como indica `docs/03-CONFIG_META_FACEBOOK_INSTAGRAM.md`, con System User de Meta para que no caduque).
   - **Instagram User ID** (tu cuenta profesional vinculada a la página de FB).
   - Hashtags (ya viene con `#Cardel #Veracruz #Xalapa #NoticiasLocales`).
2. Guarda. Listo: cada noticia **publicada** (vialidad/clima) se cross-postea sola a Facebook e Instagram con su tarjeta. Las pendientes no se postean hasta que tú las apruebes.

## Paso 7 — Ver/editar las reglas

En **Noticias bot → Configuración** puedes:
- activar/desactivar **feeds** (medios) uno por uno;
- elegir qué **tipos** se publican solos (por defecto: Alerta vial y Clima; Seguridad y General quedan pendientes).

Para cambiar lugares o palabras clave, edita `wp-plugin/ojojarocho-noticias/includes/config.php` (o pídemelo y lo ajusto).

## Solución de problemas

| Síntoma | Causa | Solución |
|---|---|---|
| El botón "Ejecutar ahora" tarda mucho | Lee 14 feeds con timeout 25s | Normal la primera vez; luego solo lee lo nuevo |
| Log dice "Feed falló" en varios | Esos medios bloquean bots (Cloudflare) | Desactiva esos feeds en Configuración |
| No crea entradas | Ninguna noticia de la zona en ese momento | Normal; espera a que haya noticia local |
| Las noticias no llevan imagen | El servidor no tiene la librería GD | Se publica sin tarjeta; Facebook sí pone enlace |
| Quiero probar sin publicar | — | Desactiva todos los tipos en "se publican solos" → todo queda pendiente |
| Error fatal al activar | Conflicto con otro plugin | Desactiva el otro plugin temporalmente y repite |

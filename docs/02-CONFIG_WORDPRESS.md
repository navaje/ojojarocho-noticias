# 02 — Configuración de WordPress (ojojarocho.online)

## 1. Crear la Application Password para el bot

1. Entra a wp-admin como administrador.
2. **Usuarios → Tu perfil → Application Passwords**.
3. Nombre: `bot-noticias`. Clic en "Añadir nueva".
4. Copia la contraseña que aparece (formato `xxxx xxxx xxxx xxxx xxxx xxxx`). **Se muestra una sola vez.**
5. Pégala en `.env` del servidor como `WP_APP_PASSWORD`, y en `WP_USER` tu usuario de WordPress.

Si tu WP es anterior a 5.6, actualiza WordPress primero (la REST API con Application Passwords existe desde 5.6).

## 2. Permalinks y rendimiento

1. **Ajustes → Enlaces permanentes** → seleccionar "Nombre de la entrada" (`/%postname%/`).
2. **Ajustes → Medios**: WordPress 6.x genera WebP automáticamente; si tu tema no lo hace, las tarjetas PNG que sube el bot igual sirven para redes. Activa lazy-loading nativo (viene por defecto en imágenes desde WP 5.5).
3. En el tema (preferiblemente un **tema hijo**) asegúrate de que `wp_head()` y `wp_footer()` se impriman (el plugin inyecta estilos y schema en `wp_head`).

## 3. Instalar el plugin "Ojo Jarocho Noticias"

1. Sube la carpeta `wp-plugin/ojojarocho-noticias/` a `wp-content/plugins/` (vía SFTP o el administrador: Plugins → Añadir nuevo → Subir plugin → zip).
2. Actívalo: **Plugins → Ojo Jarocho Noticias → Activar**.

El plugin registra los campos `_oj_tipo`, `_oj_lugar`, `_oj_fuente`, etc. para que el bot pueda escribirlos vía REST, y provee dos shortcodes.

## 4. Crear la categoría (opcional)

El bot la crea solo si no existe (slug `noticias-locales`). Si prefieres crearla tú: **Entradas → Categorías → "Noticias locales"** con slug `noticias-locales`.

## 5. Crear la página "Noticias de hoy"

1. **Páginas → Añadir nueva**, título: `Noticias de hoy`.
2. En el contenido pega: `[noticias_hoy limite="12"]`
3. Publica. La URL sugerida: `ojojarocho.online/noticias-de-hoy/`.
4. **Apariencia → Menús**: añade la página al menú principal (ej. entre "Servicios" y "Contacto"), etiqueta: `Noticias`.

## 6. Sección de noticias recientes en la portada

En la página de inicio (o en un widget del sidebar/pie):

```
[noticias_recientes n="5"]
```

Muestra una lista compacta con insignia de tipo, título, lugar y tiempo relativo ("hace 2 horas"). En el editor de bloques escribe un shortcode bloque con ese texto.

## 7. Verificación

- Crea una entrada de prueba en la categoría "Noticias locales" con `_oj_lugar = Cardel` (o déjala que la cree el bot) y confirma en el fuente de la página (`Ctrl+U`) que aparece `<script type="application/ld+json">` con `NewsArticle`.
- Comprueba que la REST API responde: `https://ojojarocho.online/wp-json/wp/v2/posts?per_page=1` (debe devolver JSON).
- Prueba la página: `https://ojojarocho.online/noticias-de-hoy/`.

## 8. Rendimiento en celular (recomendado)

- Instala un plugin de caché (WP Super Cache o LiteSpeed Cache si tu hosting lo soporta) y excluye de caché la página `noticias-de-hoy` para que siempre muestre lo último.
- Las tarjetas del bot ya van optimizadas (1200×630 PNG); si quieres más velocidad, convierte a WebP en Ajustes → Medios o con el plugin de caché.
- Verifica en PageSpeed Insights (móvil) después de la primera semana de noticias.

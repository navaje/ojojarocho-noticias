# 03 — Cross-post a Facebook e Instagram (Meta Graph API)

El bot publica en tu **página de Facebook** y, si hay imagen pública (la tarjeta subida a WP), también en tu **Instagram profesional** vinculado a esa página.

## 1. Requisitos

- Tu página de Facebook de ojojarocho (donde ya publicas).
- Tu cuenta de Instagram en modo **Profesional** (Empresa o Creador) **vinculada a esa página de Facebook** (Configuración de la página → Instagram → Vincular cuenta).

## 2. Crear la app en Meta

1. Entra a [developers.facebook.com](https://developers.facebook.com) con la cuenta dueña de la página.
2. **Mis apps → Crear app** → tipo **Negocio** → nombre "ojojarocho noticias".
3. En el panel de la app, **Agregar producto**: no necesitas Facebook Login; usa directamente la Graph API.
4. Ve a **Configuración → Básico** y anota el **ID de la app** y el **secreto** (no se usa en el bot, guárdalo seguro).

## 3. Permisos necesarios

En **Roles → Roles** (o en la app: Herramientas → Graph API Explorer):

- `pages_show_list`
- `pages_read_engagement`
- `pages_manage_posts`
- `instagram_basic`
- `instagram_content_publish`

## 4. Obtener un token de página que NO expire (System User recomendado)

La forma robusta es un **System User** de tu Business:

1. Entra a [business.facebook.com](https://business.facebook.com) → **Configuración del negocio → Usuarios → Usuarios del sistema → Añadir** → nombre "bot-noticias", rol **Administrador**.
2. En el system user → **Generar token nuevo**: selecciona tu app, marca los permisos de arriba (`pages_manage_posts`, `pages_read_engagement`, `pages_show_list`, `instagram_basic`, `instagram_content_publish`) y marca **"No caduca"** si está disponible; si no, renuévalo cada 60 días.
3. Con ese token, lista tus páginas:

   ```
   GET https://graph.facebook.com/v21.0/me/accounts?access_token=TOKEN_DEL_SYSTEM_USER
   ```

   De la respuesta, toma el `id` y el `access_token` de **tu página de ojojarocho**. Ese `access_token` es el `FB_PAGE_ACCESS_TOKEN` (no caduca si es de system user).
4. Obtén el ID de Instagram: con el mismo page token:

   ```
   GET https://graph.facebook.com/v21.0/PAGE_ID?fields=instagram_business_account&access_token=PAGE_TOKEN
   ```

   El `instagram_business_account.id` es tu `IG_USER_ID`.

Alternativa rápida (token de 60 días): en el **Graph API Explorer** selecciona tu página y pide `pages_manage_posts` etc.; luego expande a token de larga duración con el botón del explorador. Para producción usa el System User.

## 5. Llenar el `.env` en el servidor

```
FB_PAGE_ID=123456789012345
FB_PAGE_ACCESS_TOKEN=EAAG... (token de página del system user)
IG_USER_ID=17841xxxxxxxxx
SOCIAL_HASHTAGS=#Cardel #Veracruz #Xalapa #NoticiasLocales
```

`chmod 600 .env` de nuevo.

## 6. Probar

```bash
python -m bot --limit 3
```

Las noticias publicadas (vialidad/clima) deben aparecer en tu página de FB y en Instagram en unos segundos. Las pendientes **no** se cross-postean hasta que las apruebes y publiques manualmente (puedes republicarlas a mano en redes si quieres).

## 7. Seguridad de tokens

- Nunca pongas tokens en el repo, en WP, ni en capturas de pantalla.
- Si un token se filtra, revócalo inmediatamente en Business → System users → tokens, y genera uno nuevo.
- El bot solo lee/escribe en tu propia página; no pide permisos de usuarios.

## Limitaciones importantes

- Instagram **no permite publicar solo con un enlace**: por eso el bot sube la tarjeta PNG a WordPress y usa su URL pública como imagen del post de IG. Si Pillow no está instalado en el hosting, el post de IG se omite (Facebook sí publica con enlace).
- Meta puede pedir **revisión de la app** para permisos de página en producción; con System User dentro de tu propio negocio normalmente no se requiere revisión para publicar en tu propia página.

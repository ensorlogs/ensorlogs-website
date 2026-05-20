# Ensorlogs

Este es el repo público de mi web: ensorlogs.com.

Aquí dejo solo lo necesario para publicar el sitio y el theme de WordPress. Las notas internas, borradores, scripts de trabajo y documentación operativa se quedan fuera del repo público.

Sitio: https://ensorlogs.com

## Calidad y CI

Antes de desplegar a producción, el workflow **Quality Gate** (`.github/workflows/quality-gate.yml`) ejecuta pruebas de seguridad, estándares HTML, enlaces y Lighthouse. Ver `tests/README.md` para ejecutarlas en local.

## Actualizar el tema en WordPress (ensorlogs.com)

1. Sube la versión en `wp-theme/ensorlogs/style.css` y `ENSORLOGS_THEME_VERSION` en `functions.php`.
2. Commit y tag: `git tag v1.10.1 && git push origin main && git push origin v1.10.1`
3. El workflow **Release Theme** publica el release en GitHub (versión visible, **sin** `ensorlogs.zip` descargable en la página del release).
4. En `wp-config.php` del sitio (obligatorio):

```php
define('ENSORLOGS_GITHUB_TOKEN', 'ghp_tu_personal_access_token');
```

5. En WordPress: **Apariencia → Ensorlogs · Seed → Buscar actualizaciones** → **Escritorio → Actualizaciones**.

El token solo lo usa tu servidor; el visitante no descarga el tema desde GitHub Releases.

**Repo privado (recomendado):** en GitHub → Settings → Change visibility → Private. El PAT sigue funcionando en WordPress y el código no es público.

**Primera vez con 1.10.0 sin token en el updater nuevo:** sube `wp-theme/ensorlogs.zip` manualmente una vez, o pega el token y actualiza a 1.10.1 desde Actualizaciones.

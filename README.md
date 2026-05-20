# Ensorlogs

Este es el repo público de mi web: ensorlogs.com.

Aquí dejo solo lo necesario para publicar el sitio y el theme de WordPress. Las notas internas, borradores, scripts de trabajo y documentación operativa se quedan fuera del repo público.

Sitio: https://ensorlogs.com

## Calidad y CI

Antes de desplegar a producción, el workflow **Quality Gate** (`.github/workflows/quality-gate.yml`) ejecuta pruebas de seguridad, estándares HTML, enlaces y Lighthouse. Ver `tests/README.md` para ejecutarlas en local.

## Actualizar el tema en WordPress (ensorlogs.com)

1. Sube la versión en `wp-theme/ensorlogs/style.css` y `ENSORLOGS_THEME_VERSION` en `functions.php`.
2. Crea y publica el tag: `git tag v1.10.0 && git push origin v1.10.0`
3. El workflow **Release Theme** genera `ensorlogs.zip` y lo adjunta al GitHub Release.
4. En el sitio: **Apariencia → Ensorlogs · Seed → Buscar actualizaciones** y luego **Escritorio → Actualizaciones** para instalar.

Si el repo es privado, define en `wp-config.php`: `define('ENSORLOGS_GITHUB_TOKEN', 'ghp_...');`

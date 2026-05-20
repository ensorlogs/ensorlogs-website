# Ensorlogs

Este es el repo público de mi web: ensorlogs.com.

Aquí dejo solo lo necesario para publicar el sitio y el theme de WordPress. Las notas internas, borradores, scripts de trabajo y documentación operativa se quedan fuera del repo público.

Sitio: https://ensorlogs.com

## Calidad y CI

Antes de desplegar a producción, el workflow **Quality Gate** (`.github/workflows/quality-gate.yml`) ejecuta pruebas de seguridad, estándares HTML, enlaces y Lighthouse. Ver `tests/README.md` para ejecutarlas en local.

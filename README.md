# Ensorlogs

Este es el repo público de mi web: ensorlogs.com.

Aquí dejo solo lo necesario para publicar el sitio y el theme de WordPress. Las notas internas, borradores, scripts de trabajo y documentación operativa se quedan fuera del repo público.

Sitio: https://ensorlogs.com

## Vista local (sitio estático ES / EN)

No abras los `.html` con doble clic (`file://`): el botón **EN** no navegará bien. Usa un servidor local:

```bash
./scripts/serve-static.sh
# o: python3 -m http.server 8080
```

- Español: http://127.0.0.1:8080/index.html  
- Inglés: http://127.0.0.1:8080/en/index.html  

Tras cambiar textos en español, regenera inglés:

```bash
python3 scripts/generate_en_static.py
```

## Calidad y CI

Antes de desplegar a producción, el workflow **Quality Gate** (`.github/workflows/quality-gate.yml`) ejecuta pruebas de seguridad, estándares HTML, enlaces y Lighthouse. Ver `tests/README.md` para ejecutarlas en local.

## Actualizar el tema en WordPress (ensorlogs.com)

1. Sube la versión en `wp-theme/ensorlogs/style.css` y `ENSORLOGS_THEME_VERSION` en `functions.php`.
2. Commit y tag: `git tag v1.10.1 && git push origin main && git push origin v1.10.1`
3. El workflow **Release Theme** publica el release en GitHub (versión visible, **sin** `ensorlogs.zip` descargable en la página del release).
4. En WordPress: **Apariencia → Ensorlogs · Seed → Buscar actualizaciones** → **Escritorio → Actualizaciones**.

Cada release en GitHub incluye `ensorlogs.zip` (generado por el workflow).

**Repo privado:** añade en `wp-config.php`:

```php
define('ENSORLOGS_GITHUB_TOKEN', 'ghp_tu_personal_access_token');
```

**Si no ves la actualización** (tema antiguo sin zip en el release): sube una vez `wp-theme/ensorlogs.zip` manualmente; las siguientes irán solas.

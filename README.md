# Ensorlogs — repo del sitio

Aquí está el HTML estático, los CSS (`style.min.css` compilado + `ensor-brand.css`), JS sueltos y el formulario PHP. Los posts largos viven en Markdown en `content/articulos/`; el script `scripts/render_blog_articles.py` vuelca a `articulos/*.html` usando la plantilla de `blogs-details.html`. Los proyectos siguen en `proyectos/`.

Si quieres ver cómo encaja todo: [`docs/ARQUITECTURA.md`](docs/ARQUITECTURA.md). Si prefieres un recorrido por archivos: [`docs/GUIA-ESTUDIANTES.md`](docs/GUIA-ESTUDIANTES.md).

## Verlo en local

```bash
cd /ruta/al/repo
python3 -m http.server 8080
```

Abre `http://localhost:8080/` en el navegador. Con `file://` suelen fallar rutas y fetch; mejor un servidor mínimo.

## Regenerar los logs del blog

Necesitas lo de `requirements.txt` (PyYAML, markdown, etc.):

```bash
python3 -m venv .venv
.venv/bin/pip install -r requirements.txt
.venv/bin/python scripts/render_blog_articles.py
```

Toca los `.md` de `content/articulos/`, no los HTML generados (se pisan al correr el script). Los campos del YAML están resumidos en `content/articulos/README.md`.

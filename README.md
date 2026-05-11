# Ensorlogs — sitio web

Código del sitio [ensorlogs.com](https://ensorlogs.com): páginas en HTML, estilos en `assets/css/` (Tailwind ya compilado en `style.min.css` + `ensor-brand.css`), JS sin framework, formulario con `contact-form.php`. Los posts largos se escriben en Markdown bajo `content/articulos/` y el script `scripts/render_blog_articles.py` genera `articulos/*.html`. Las fichas de proyectos siguen en `proyectos/`.

Más detalle de estructura y flujos: [`docs/ARQUITECTURA.md`](docs/ARQUITECTURA.md). Si quieres un recorrido tipo tutorial: [`docs/GUIA-ESTUDIANTES.md`](docs/GUIA-ESTUDIANTES.md).

## Ver en local

```bash
cd ruta/del/repo
python3 -m http.server 8080
```

Abre `http://127.0.0.1:8080/`. (Con `file://` algunas cosas se comportan raro; mejor servidor.)

## Regenerar artículos del blog

Necesitas `PyYAML` y `markdown` (ver `requirements.txt`). Ejemplo con venv:

```bash
python3 -m venv .venv
.venv/bin/pip install -r requirements.txt
.venv/bin/python scripts/render_blog_articles.py
```

Edita los `.md` en `content/articulos/` (no los HTML generados). Detalle de campos: `content/articulos/README.md`.

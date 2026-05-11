# Scripts de build — Ensorlogs

## `render_blog_articles.py`

Genera las páginas **`articulos/articulo-*.html`** a partir de:

- **`blogs-details.html`**: plantilla HTML compartida (cabecera, nav, footer).
- **Lista `ARTICLES`** dentro del propio script: metadatos, cuerpo HTML del post y tarjetas “relacionadas”.

### Uso

Desde la **raíz del repositorio** (no desde `scripts/`):

```bash
python3 scripts/render_blog_articles.py
```

### Qué estás aprendiendo si lees el código

- Cómo **partir un HTML grande** en trozos reutilizables con `str.index` y slices.
- Cómo **inyectar** metaetiquetas distintas por página sin un motor de plantillas.
- Límite del enfoque: el HTML del artículo sigue siendo **strings** en Python; a medio plazo conviene **Markdown** u otro formato de contenido.

Más contexto: [`../docs/ARQUITECTURA.md`](../docs/ARQUITECTURA.md) y [`../docs/GUIA-ESTUDIANTES.md`](../docs/GUIA-ESTUDIANTES.md).

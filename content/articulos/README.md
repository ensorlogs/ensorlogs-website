# Artículos (fuente Markdown)

Cada `.md` tiene **YAML al inicio** (entre `---`) y debajo el **cuerpo**. Puedes mezclar Markdown (`## Título`, listas, `**negrita**`) y HTML con las mismas clases Tailwind que ya usabas en el sitio.

Campos del front matter:

| Campo | Uso |
|-------|-----|
| `order` | Orden en el generador y para “artículos relacionados” (menor = antes). |
| `filename` | Nombre del HTML de salida (ej. `articulo-mi-slug.html`). |
| `primary_tema` | Slug del filtro del blog (`blog.html?tema=…`). |
| `pill` | Texto de la pastilla bajo el título. |
| `h1` | Título principal del artículo. |
| `date` | Texto libre (ej. “Enero 2026”). |
| `meta_title`, `meta_desc`, `meta_keywords` | SEO (`<title>` y meta). |
| `canonical_path` | Ruta en el sitio (ej. `articulos/articulo-….html`). |
| `hero_src`, `hero_alt` | Imagen hero. |
| `tags` | Lista de `{ slug, label }` para los chips de temas. |

Regenerar HTML:

```bash
python3 -m venv .venv          # solo la primera vez
.venv/bin/pip install -r requirements.txt
.venv/bin/python scripts/render_blog_articles.py
```

No edites a mano los `articulos/*.html` generados: se sobrescriben al correr el script.

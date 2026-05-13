# Logs (fuente Markdown)

Cada `.md` tiene **YAML al inicio** (entre `---`) y debajo el **cuerpo**. Puedes mezclar Markdown (`## Título`, listas, `**negrita**`) y HTML con las mismas clases Tailwind que ya usabas en el sitio.

Campos del front matter:

| Campo | Uso |
|-------|-----|
| `order` | Orden en el generador (menor = antes). Con **menos de 3** logs en la carpeta, el bloque “Otros logs” no se muestra. |
| `filename` | Nombre del HTML de salida (ej. `mi-slug.html`). Sin prefix `articulo-`. |
| `primary_tema` | Slug del stack principal (mismo que usa `blog.html?tema=…`). |
| `pill` | (Opcional) Texto de la pastilla; si se omite, se usa la etiqueta del stack. |
| `stacks` | Lista de slugs de **stacks** que aparecerán como badges con logo. Ej. `[wordpress, ia, servidores]`. |
| `h1` | Título principal del log. |
| `date` | Texto libre (ej. “Mayo 2026”). |
| `meta_title`, `meta_desc`, `meta_keywords` | SEO (`<title>` y meta). |
| `canonical_path` | Ruta en el sitio (ej. `articulos/wordpress-….html`). |
| `hero_src`, `hero_alt` | Imagen hero. |
| `tags` | (Compatibilidad) Lista de `{ slug, label }`; si no hay `stacks`, se derivan de aquí. |

Chrome del lector (auto):

- Barra de progreso de lectura arriba.
- Chip flotante con la sección actual.
- TOC sticky en desktop, sheet en mobile (botón inferior).
- Badges de stacks con logo en cabecera y pie del log.
- Filtro por audiencia y secciones tematizadas (panel con icono + color) si añades:

```html
<section class="ensor-aud-section" data-aud="context" markdown="1">
## Contexto
Texto markdown normal va aquí.
</section>
```

Valores de `data-aud` (orden recomendado): `context`, `data`, `student`, `teacher`, `professional`. Opcionales: `beginner`, `advanced`, `client`.

- Bloque «Prompt IA» dentro de cualquier sección (incluye botón copiar automático):

```html
<div class="ensor-ai-prompt" markdown="1">
**Prompt IA — describe qué hace**

<pre>Eres [rol].
Tu tarea es [objetivo].
Devuelve: [formato esperado].</pre>
</div>
```

- Resaltado: usa `<mark>palabra clave</mark>` para el subrayado amarillo.

Regenerar HTML:

```bash
python3 -m venv .venv          # solo la primera vez
.venv/bin/pip install -r requirements.txt
.venv/bin/python scripts/render_blog_articles.py
```

No edites a mano los `articulos/*.html` generados: se sobrescriben al correr el script.

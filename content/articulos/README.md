# Fuente Markdown de los logs

Cada archivo lleva **YAML entre `---`** y debajo el **cuerpo**. Puedes mezclar Markdown normal y HTML con las mismas clases que ya usas en el sitio.

### Front matter (lo que uso casi siempre)

| Campo | Para qué |
|-------|----------|
| `order` | Orden al generar (número bajo = antes). Si hay menos de 3 logs, el bloque “Otros logs” no sale. |
| `filename` | Nombre del HTML de salida, ej. `mi-slug.html` (sin prefijo `articulo-`). |
| `primary_tema` | Slug del stack principal (el de `blog.html?tema=…`). |
| `pill` | (Opcional) Texto de la pastilla; si no va, sale la etiqueta del stack. |
| `stacks` | Lista de slugs de stacks con badge, ej. `[wordpress, ia, servidores]`. |
| `h1` | Título del log. |
| `date` | Texto libre (“Mayo 2026”, etc.). |
| `meta_title`, `meta_desc`, `meta_keywords` | SEO. |
| `canonical_path` | Ruta publicada, ej. `articulos/….html`. |
| `hero_src`, `hero_alt` | Imagen del hero. |
| `tags` | Compatibilidad: `{ slug, label }`; si no hay `stacks`, se puede derivar de aquí. |

### Cosas que mete solo el lector (no hace falta tocarlas en el `.md` salvo que quieras)

- Barra de progreso arriba.
- Chip con el encabezado en el que vas.
- TOC en escritorio y hoja en móvil (se arma con los `h2`/`h3` del cuerpo).
- Bloques por audiencia si usas secciones así:

```html
<section class="ensor-aud-section" data-aud="context" markdown="1">
## Contexto
Texto…
</section>
```

Valores habituales de `data-aud`: `context`, `data`, `student`, `teacher`, `professional`; a veces `beginner`, `advanced`, `client`.

### Bloque copiable tipo “prompt” (opcional)

```html
<div class="ensor-ai-prompt" markdown="1">
**Prompt — qué hace**

<pre>Tu instrucción aquí, sin contraseñas ni URLs privadas.</pre>
</div>
```

Para el subrayado amarillo: `<mark>texto</mark>`.

### Generar de nuevo

```bash
python3 -m venv .venv
.venv/bin/pip install -r requirements.txt
.venv/bin/python scripts/render_blog_articles.py
```

No edites a mano `articulos/*.html`: el script los machaca.

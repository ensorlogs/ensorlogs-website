# Arquitectura (notas rápidas)

Cómo está repartido el repo y qué toca qué.

## Flujo general

1. Visitante entra a páginas HTML + assets en `assets/`.
2. El formulario de contacto hace `POST` a `contact-form.php` (validación básica y correo vía lo que tenga el hosting).
3. Cuando toco un log en Markdown, paso el script `scripts/render_blog_articles.py` y se regeneran los `articulos/*.html`.

## Carpetas

| Ruta | Qué hay |
|------|---------|
| Raíz | `index.html`, `blog.html`, `services.html`, etc., más `.htaccess` si despliegas en Apache. |
| `content/articulos/` | Markdown fuente de cada log. |
| `articulos/` | HTML generado (salida del script). |
| `proyectos/` | Fichas de proyecto. |
| `assets/css/` | `style.min.css` (plantilla + utilidades compiladas), `ensor-brand.css` (marca y piezas Ensor). |
| `assets/js/` | JS del front (tema, filtros, lector, etc.). |
| `assets/img/` | Imágenes. |
| `scripts/` | Generador de artículos. |
| `src/tailwind.css` | Solo si en algún momento regeneras `style.min.css` desde Tailwind. |

## Filtro `?tema=` en blog y proyectos

`blog-tema-filter.js` y `projects-tema-filter.js` leen el querystring, aplican clases para ocultar tarjetas y actualizan la URL con `history.replaceState` sin recargar. El estilo de ocultar está en `ensor-brand.css`.

## Contacto

`contact.html` manda a `contact-form.php`. Campos típicos: nombre, email, asunto, mensaje, captcha simple y honeypot. La respuesta es una página HTML, no JSON.

## Cosas que cargan desde fuera

Google Fonts (Bricolage Grotesque), a veces iconos de marcas por CDN, AOS/Swiper locales en `assets/`.

## `.htaccess`

Solo cuenta en **Apache** con `AllowOverride` permitido. Ahí suelen ir cabeceras de seguridad básicas, compresión/caché de estáticos y reglas de redirección si cambias rutas o dominio. En local con `python3 -m http.server` ese archivo no hace nada: es normal.

Si cambias de dominio, revisa condiciones de host en las reglas de rewrite.

## Añadir cosas sin romper lo demás

- Página nueva: copia cabecera/canonical de una existente y ajusta título y rutas de CSS/JS.
- Log nuevo: duplica un `.md` en `content/articulos/`, ajusta YAML, corre el script.
- Filtro nuevo: copia el JS del blog, cambia selectores y nombre del parámetro en la URL.

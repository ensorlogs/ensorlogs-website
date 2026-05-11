# Arquitectura

Carpetas, flujos (blog, contacto, generador de artículos) y qué depende de qué.

---

## Diagrama textual

```
[Visitante]
     │
     ▼
┌─────────────────────────────────────────────────────────┐
│  HTML estático (index, blog, projects, articulos/, proyectos/) │
│  + CSS (style.min + ensor-brand + fonts)               │
│  + JS (jQuery + AOS + script.js + filtros + tema)     │
└─────────────────────────────────────────────────────────┘
     │ POST contacto
     ▼
┌──────────────────┐     ┌─────────────────────────────┐
│ contact-form.php │────▶│ mail() / SMTP del hosting   │
└──────────────────┘     └─────────────────────────────┘

[Desarrollador] ──▶ python3 scripts/render_blog_articles.py ──▶ articulos/*.html
                      (lee blogs-details.html + content/articulos/*.md)
```

---

## Carpetas principales

| Ruta | Contenido |
|------|-----------|
| `/` | Páginas `.html` de sección (index, blog, projects…), `.htaccess`, `README.md`. |
| `content/articulos/` | Fuente Markdown (`.md`) de cada artículo. |
| `articulos/` | HTML generado (`articulo-*.html`) a partir de los `.md`. |
| `proyectos/` | Fichas de detalle de cada caso (`proyecto-*.html`). |
| `assets/css/` | Hojas de estilo; `ensor-brand.css` es la capa de marca. |
| `assets/js/` | Scripts del front; filtros y utilidades Ensor. |
| `assets/img/` | Imágenes y logos. |
| `scripts/` | Herramientas de build (`render_blog_articles.py`). |
| `docs/` | Documentación para humanos (esta carpeta). |
| `src/tailwind.css` | Fuente Tailwind si regeneras `style.min.css` en tu flujo. |

---

## Flujo: filtro del blog (`?tema=`)

1. `blog.html` carga `blog-tema-filter.js` (al final, con `defer`).
2. Al cargar, el script lee `window.location.search` con `URLSearchParams`.
3. Para cada `.blog-item[data-temas]`, divide el atributo en slugs y decide si mostrar u ocultar (clase `ensor-blog-item--hidden`, definida en `ensor-brand.css`).
4. Al hacer clic en una pastilla del `<nav class="ensor-blog-temas">`, se hace `preventDefault`, se actualiza la URL con `history.replaceState` y se vuelve a aplicar el filtro **sin recargar** la página.

El listado de **proyectos** repite el patrón con `projects-tema-filter.js` y `projects.html`.

---

## Flujo: formulario de contacto

1. `contact.html` envía `POST` multipart/urlencoded a `contact-form.php`.
2. Campos esperados: `clientName`, `clientEmail`, `contactSubject`, `contact__message`, `contact_captcha`, honeypot `website`.
3. PHP responde con **HTML completo** (página de resultado), no JSON — diseño simple para hosting compartido.

---

## Dependencias externas (runtime)

- **Google Fonts** (Bricolage Grotesque).
- **simple-icons** vía `cdn.jsdelivr.net` en algunas páginas (iconos de marcas).
- **AOS**: JS local + CSS copiado a `assets/css/aos-2.3.1.min.css` (antes unpkg).

---

## Apache (`.htaccess`)

Solo aplica en servidores **Apache** con `AllowOverride` adecuado (típico en SiteGround). Incluye:

- Cabeceras de seguridad (`X-Frame-Options`, `Referrer-Policy`, etc.).
- Compresión y caché de estáticos.
- Redirección HTTP→HTTPS condicionada al host `ensorlogs.com`.
- **301** de URLs antiguas en la raíz (`/articulo-….html`, `/proyecto-….html`) hacia `articulos/` y `proyectos/` (enlaces externos y buscadores conservan autoridad).

Si cambias de dominio, revisa las reglas `RewriteCond` del host.

---

## Extensiones futuras sin romper lo existente

- Nuevas páginas: copia el `<head>` de `index.html`, ajusta `<title>` y canonical, enlaza CSS/JS en el mismo orden.
- Nuevo filtro: copia `blog-tema-filter.js`, cambia selectores y nombre del parámetro URL, añade CSS de ocultación si usas otra clase.
- Nuevo artículo: copia un `.md` en `content/articulos/`, ajusta front matter y `order`, ejecuta el script.

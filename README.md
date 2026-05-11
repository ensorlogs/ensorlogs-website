# Ensorlogs Web (`ensorlogsweb`)

Sitio web **estático** de **Ensorlogs** (portfolio, blog, proyectos y contacto). Este repositorio está pensado para **leerlo en GitHub** y para **aprender**: el código mezcla HTML “clásico”, utilidades **Tailwind** compiladas en `style.min.css`, capa de marca **`ensor-brand.css`**, **JavaScript** sin framework en el front, un poco de **PHP** para el formulario y **Python** opcional para generar artículos.

---

## Qué aprenderás si exploras el repo

| Tema | Dónde verlo |
|------|-------------|
| Estructura de una web multipágina | `index.html`, `blog.html`, `projects.html` |
| Filtros sin recargar (URL + DOM) | `assets/js/blog-tema-filter.js`, `assets/js/projects-tema-filter.js` |
| Modo oscuro con `localStorage` + `class` en `<html>` | Cabecera de cada `.html` + `assets/js/theme-mode.js` |
| Formulario → servidor → respuesta HTML | `contact.html` + `contact-form.php` |
| Marca y tokens CSS (`:root`, variables) | `assets/css/ensor-brand.css` |
| Generar muchas páginas desde una plantilla | `scripts/render_blog_articles.py` + `blogs-details.html` |

Guía paso a paso para estudiantes: **[`docs/GUIA-ESTUDIANTES.md`](docs/GUIA-ESTUDIANTES.md)**.  
Mapa técnico un poco más denso: **[`docs/ARQUITECTURA.md`](docs/ARQUITECTURA.md)**.

---

## Requisitos en tu máquina

- **Navegador** (para abrir los `.html` directamente o con un servidor estático).
- **Git** (recomendado para historial y subir a GitHub).
- **Python 3** (solo si vas a **regenerar** artículos del blog con el script).
- **PHP** (solo si quieres probar el envío del formulario en local; en producción lo usa SiteGround).

No hace falta Node ni bundler para **ver** el sitio: los CSS ya están compilados en `assets/css/style.min.css`.

---

## Cómo ver el sitio en local

1. Clona o descarga el repo.
2. Opción A: abre `index.html` con doble clic (algunas rutas relativas y `file://` se comportan distinto; sirve para una vista rápida).
3. Opción B (recomendada): sirve la carpeta raíz con cualquier servidor estático, por ejemplo:
   ```bash
   cd /ruta/al/repo
   python3 -m http.server 8080
   ```
   Luego entra en `http://127.0.0.1:8080/`.

---

## Blog: generar las páginas de artículo (`articulo-*.html`)

Los artículos largos se generan desde **`blogs-details.html`** (plantilla) y datos en **`scripts/render_blog_articles.py`**.

```bash
cd /ruta/al/repo
python3 scripts/render_blog_articles.py
```

**Importante:** si editas a mano un `articulo-*.html` y luego vuelves a ejecutar el script, **se sobrescribirá** ese archivo. Para flujo “fuente única”, edita el script o evita regenerar hasta migrar a Markdown (ver `docs/ARQUITECTURA.md`).

---

## Despliegue (p. ej. SiteGround)

1. Sube por **FTP/SFTP** el contenido de la raíz del repo (o solo lo que cambió).
2. Asegúrate de que **`.htaccess`** llegue a la **raíz pública** del dominio (cabeceras de seguridad, caché, HTTPS en `ensorlogs.com`).
3. El formulario necesita **`contact-form.php`** y PHP con `mail()` o SMTP según tu plan.

---

## Convenciones de nombre (Ensor)

- Clases y hooks propios suelen llevar prefijo **`ensor-`** (ej. `ensor-home-tema-pill`, `ensor-nav-volver`).
- Filtros del blog/proyectos usan atributo **`data-temas="slug1 slug2"`** (slugs en minúsculas, separados por espacio). Lista de slugs en comentarios de `blog-tema-filter.js`.

---

## Licencia y plantilla base

El diseño parte de una **plantilla portfolio** (comentarios al inicio de algunos CSS/JS originales). La capa de marca, textos, blog, proyectos y scripts adicionales son **específicos de Ensorlogs**.

---

## Dudas o mejoras

Si usas GitHub: **Issues** o **Discussions** (si las activas) son buen sitio para apuntar mejoras. Si estudias con el repo, abre los `.js` y `docs/GUIA-ESTUDIANTES.md` en ese orden.

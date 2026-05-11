# Ensorlogs — sitio web

Código del sitio [ensorlogs.com](https://ensorlogs.com): páginas en HTML, estilos en `assets/css/` (Tailwind ya compilado en `style.min.css` + `ensor-brand.css`), JS sin framework, formulario con `contact-form.php`. Los posts largos salen de `scripts/render_blog_articles.py` hacia `articulos/`; las fichas de proyectos están en `proyectos/`.

Más detalle de estructura y flujos: [`docs/ARQUITECTURA.md`](docs/ARQUITECTURA.md). Si quieres un recorrido tipo tutorial: [`docs/GUIA-ESTUDIANTES.md`](docs/GUIA-ESTUDIANTES.md).

## Ver en local

```bash
cd ruta/del/repo
python3 -m http.server 8080
```

Abre `http://127.0.0.1:8080/`. (Con `file://` algunas cosas se comportan raro; mejor servidor.)

## Regenerar artículos del blog

Desde la raíz del repo:

```bash
python3 scripts/render_blog_articles.py
```

Eso vuelve a escribir los HTML en `articulos/`. Si tocas esos archivos a mano y luego corres el script, se pisan.

## Subir a hosting

Sube el contenido de la raíz (incluido `.htaccess` en la carpeta pública del dominio). El formulario necesita PHP. En `ensorlogs.com` el `.htaccess` fuerza HTTPS y redirige URLs viejas de artículos/proyectos en la raíz hacia `articulos/` y `proyectos/`.

La base visual partió de una plantilla tipo portfolio; el resto (marca, textos, blog, scripts) es propio del proyecto.

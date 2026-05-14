# Recorrido por el repo

Orden que a mí me sirvió para no perderme cuando volví a tocar el proyecto después de un tiempo.

## 1. Una página tipo `index.html`

- `<head>`: meta, OG, JSON-LD si aplica, orden de CSS.
- El script corto al inicio que lee `localStorage.theme` y pone `dark` en `<html>` antes de pintar (evita flash).
- Estructura: preloader opcional, `header`, `main`, `footer`, scripts al final con `defer`.

El interruptor de tema vive también en `theme-mode.js`.

## 2. CSS

Orden habitual: Font Awesome → `style.min.css` (plantilla compilada; no lo edito a mano) → `ensor-brand.css` (variables `--ensor-*`, botones, lector, filtros del blog, etc.).

## 3. JavaScript

La plantilla arrastra jQuery en `script.js`. Lo más acotado está en archivos sueltos: `nav-volver.js`, `theme-mode.js`, filtros de blog/proyectos, `ensor-reader.js` en los logs. Vale la pena leerlos en ese orden si quieres entender el patrón sin tragarte todo jQuery de golpe.

## 4. `contact-form.php`

POST, honeypot, validación de campos, cabeceras de correo sin basura inyectable, respuesta HTML. Cualquier cosa que salga del usuario conviene escaparla al imprimir.

## 5. `render_blog_articles.py`

Lee plantilla + YAML + Markdown y escupe HTML. Útil si quieres ver cómo se mezclan f-strings y trocear HTML grande por marcadores.

## 6. `.htaccess`

Directivas de Apache: caché, cabeceras, redirects. En tu máquina con servidor estático de Python no aplica. En producción, léelo antes de tocar reglas de dominio.

## 7. Qué haría después (lista corta)

Tests mínimos (por ejemplo que cada HTML generado tenga un `h1`). CSP estricta si algún día centralizamos scripts inline. Nada de esto es obligatorio para mover contenidos hoy.

Si haces un fork, documenta sobre todo **qué archivo tocarías para cambiar X** y qué romperías si lo borras.

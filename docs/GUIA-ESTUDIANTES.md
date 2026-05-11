# Guía para estudiantes — recorrido por el código Ensorlogs

Esta guía es un **itinerario de lectura**, no un curso completo de HTML/CSS/JS. Cada bloque tiene una **meta de aprendizaje** y archivos concretos.

---

## 1. Empezar por el “esqueleto” de una página

**Archivo:** `index.html` (sirve de referencia para casi todas las páginas).

**Qué mirar:**

1. `<head>`: metaetiquetas SEO, Open Graph, JSON-LD (`application/ld+json`), fuentes, CSS en orden.
2. Un `<script>` muy pequeño **inline** al inicio: lee `localStorage.theme` y pone la clase `dark` en `<html>` **antes** de pintar mucho contenido (evita un “flash” de modo claro).
3. `<body>`: preloader, `<header>`, `<main>`, `<footer>`.
4. Al final del `<body>`, scripts con **`defer`**: se descargan en paralelo al HTML y se ejecutan **en orden** cuando el documento está parseado.

**Pregunta para ti:** ¿Por qué el tema se decide en dos sitios (inline en `<head>` y `theme-mode.js`)? Pista: uno es mínimo y síncrono; el otro sincroniza botones con jQuery.

---

## 2. CSS: tres capas encima de otra

| Archivo | Rol |
|---------|-----|
| `assets/css/fontAwesome5Pro.css` | Iconos (Font Awesome). |
| `assets/css/style.min.css` | Plantilla + Tailwind **compilado** (no edites a mano; el fuente Tailwind está en `src/tailwind.css` si lo usas en tu flujo). |
| `assets/css/ensor-brand.css` | **Marca Ensorlogs**: variables `--ensor-*`, botones, preloader, filtros del blog, etc. |

**Ejercicio:** en `ensor-brand.css`, busca `:root` y cambia solo `--ensor-accent` en local; recarga y observa qué piezas heredan el color.

---

## 3. JavaScript sin framework (pero con jQuery legacy)

La plantilla original usa **jQuery** (`assets/js/script.js`). Es código imperativo: “cuando pasa X, haz Y”.

**Orden sugerido de lectura:**

1. `assets/js/nav-volver.js` — IIFE corta, `querySelectorAll`, lógica de URL.
2. `assets/js/theme-mode.js` — `localStorage`, clases en `<html>`, parámetros `?version=` en la URL.
3. `assets/js/blog-tema-filter.js` — `URLSearchParams`, `history.replaceState`, clases para ocultar nodos.
4. `assets/js/projects-tema-filter.js` — mismo patrón que el blog, otro contenedor y otra página base.
5. `assets/js/script.js` — preloader, menú móvil, AOS, Swiper, scroll.

**Idea clave:** `blog-tema-filter.js` y `projects-tema-filter.js` **no** comparten archivo a propósito: así puedes comparar “mismo patrón, distinto contexto” (principio DRY a nivel de aprendizaje vs. a nivel de librería).

---

## 4. PHP: un solo endpoint de formulario

**Archivos:** `contact.html` (vista) y `contact-form.php` (procesamiento).

**Flujo:**

1. El usuario envía `POST` a `contact-form.php`.
2. PHP valida honeypot, captcha simple, email, longitudes y patrones sospechosos.
3. Construye cabeceras de correo **sin saltos de línea** en campos que van a cabeceras (evita inyección de cabeceras).
4. Responde con **una página HTML** (mismo estilo visual) según éxito o error.

**Pregunta para ti:** ¿Por qué `htmlspecialchars()` al imprimir textos en la respuesta?

---

## 5. Python: plantilla + datos → HTML

**Archivos:** `scripts/render_blog_articles.py`, plantilla `blogs-details.html`.

**Ideas de programación que aparecen:**

- Leer un archivo grande como **texto** y cortar por “marcadores” (`index`, slicing).
- **f-strings** con HTML embebido (rápido de leer; en proyectos grandes a veces se prefiere un motor de plantillas).
- Una **lista de diccionarios** (`articles`) donde cada dict describe un artículo.

Tras ejecutar el script, abre un `articulos/articulo-*.html` generado y compáralo con `blogs-details.html`.

---

## 6. Seguridad y rendimiento en producción

Lee **`.htaccess`** con calma: son directivas de **Apache** (típico en SiteGround). Comenta en el propio archivo qué hace cada bloque.

En local, si usas `python3 -m http.server`, **`.htaccess` no se aplica** (no es Apache). Eso es normal.

---

## 7. Siguiente nivel (cuando domines lo anterior)

- Añadir **tests** (p. ej. validar que cada `articulos/articulo-*.html` tiene un `<h1>`).
- Mover contenidos del blog a **Markdown** + un generador (menos HTML repetido en Python).
- **CSP** (Content-Security-Policy) con nonces para scripts inline (requiere build o servidor que inyecte nonces).

---

¡Buen estudio! Si documentas tu propio fork, mantén el mismo espíritu: **qué hace el archivo**, **por qué existe**, **qué romperías si lo tocas**.

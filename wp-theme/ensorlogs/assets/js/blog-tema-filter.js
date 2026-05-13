/**
 * =============================================================================
 * Filtro del blog por tema — ``blog.html?tema=slug`` (SPA ligera)
 * =============================================================================
 *
 * Objetivo pedagógico
 * -------------------
 * Mostrar cómo se puede **filtrar contenido en el cliente** sin frameworks:
 * leer la query string, togglear clases CSS y actualizar la barra de URL con
 * ``history.replaceState`` (sin recargar la página al hacer clic en un filtro).
 *
 * Contrato con el HTML
 * ----------------------
 * - Contenedor de tarjetas: ``.blog-wrapper``
 * - Cada tarjeta: ``.blog-item`` con atributo ``data-temas="linux ia"`` (slugs en
 *   minúsculas separados por espacio).
 * - Nave de filtros: ``.ensor-blog-temas`` con enlaces ``href="blog.html?tema=..."``
 *   y un enlace “ver todos” con clase ``ensor-blog-tema--todos``.
 *
 * Slugs reconocidos (deben coincidir con los del HTML)
 * -----------------------------------------------------
 * linux, ia, database, wordpress, crm, marketing, python, google, servidores,
 * it, windows, mac (y a veces ``automatizacion`` en entradas antiguas).
 *
 * CSS: la clase ``.ensor-blog-item--hidden`` se define en ``ensor-brand.css``.
 */
(function () {
    /** Normaliza string: minúsculas y sin espacios extremos. */
    function norm(s) {
        return String(s || '')
            .toLowerCase()
            .trim();
    }

    function blogItems() {
        return document.querySelectorAll('.blog-wrapper .blog-item[data-temas]');
    }

    function blogWrapper() {
        return document.querySelector('.blog-wrapper');
    }

    function hasFilterableGrid() {
        return blogItems().length > 0;
    }

    function getTemaFromSearch(search) {
        var params = new URLSearchParams(search || '');
        return norm(params.get('tema'));
    }

    function getTemaFromUrl() {
        return getTemaFromSearch(window.location.search);
    }

    /** Marca la pastilla activa según ``tema`` (vacío = “ver todos”). */
    function markActivePills(tema) {
        var nav = document.querySelector('.ensor-blog-temas');
        if (!nav) {
            return;
        }
        nav.querySelectorAll('.ensor-blog-tema--active').forEach(function (el) {
            el.classList.remove('ensor-blog-tema--active');
        });
        if (!tema) {
            var todos = nav.querySelector('a.ensor-blog-tema--todos');
            if (todos) {
                todos.classList.add('ensor-blog-tema--active');
            }
            return;
        }
        nav.querySelectorAll('a[href*="tema="]').forEach(function (a) {
            try {
                var u = new URL(a.getAttribute('href'), window.location.href);
                if (norm(u.searchParams.get('tema')) === tema) {
                    a.classList.add('ensor-blog-tema--active');
                }
            } catch (e) {
                /* href inválido: ignorar */
            }
        });
    }

    /** Oculta tarjetas cuyo ``data-temas`` no contenga el slug seleccionado. */
    function applyFilter(tema) {
        var items = blogItems();
        if (!items.length) {
            return;
        }
        var visible = 0;
        items.forEach(function (el) {
            var list = norm(el.getAttribute('data-temas'))
                .split(/\s+/)
                .filter(Boolean);
            var show = !tema || list.indexOf(tema) !== -1;
            el.classList.toggle('ensor-blog-item--hidden', !show);
            if (show) {
                visible += 1;
            }
        });
        var wrap = blogWrapper();
        if (wrap && visible > 0 && tema) {
            window.requestAnimationFrame(function () {
                wrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        }
    }

    /** Sincroniza la URL visible sin recargar (útil para compartir enlace filtrado). */
    function syncUrl(tema) {
        var next = 'blog.html';
        if (tema) {
            next += '?tema=' + encodeURIComponent(tema);
        }
        try {
            history.replaceState({}, '', next);
        } catch (e) {
            /* entornos file:// u otros: ignorar */
        }
    }

    /** Intercepta clics solo en enlaces que apuntan a ``blog.html?...``. */
    function initNavClicks() {
        var nav = document.querySelector('.ensor-blog-temas');
        if (!nav || !hasFilterableGrid()) {
            return;
        }
        nav.addEventListener('click', function (e) {
            var a = e.target.closest('a');
            if (!a || !nav.contains(a)) {
                return;
            }
            var hrefAttr = a.getAttribute('href');
            if (!hrefAttr) {
                return;
            }
            var raw = hrefAttr.trim();
            if (!/^blog\.html(\?|$)/i.test(raw)) {
                return;
            }
            e.preventDefault();
            var u;
            try {
                u = new URL(raw, window.location.href);
            } catch (err) {
                return;
            }
            var temaClick = norm(u.searchParams.get('tema'));
            syncUrl(temaClick);
            markActivePills(temaClick);
            applyFilter(temaClick);
        });
    }

    var tema = getTemaFromUrl();
    markActivePills(tema);
    applyFilter(tema);
    initNavClicks();
})();

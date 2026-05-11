/**
 * Filtra la rejilla de proyectos por stack (?tema=slug, sin recargar al hacer clic).
 * Cada .project-item debe llevar data-temas="slug1 slug2" (mismos slugs que el blog).
 */
(function () {
    function norm(s) {
        return String(s || '')
            .toLowerCase()
            .trim();
    }

    function projectItems() {
        return document.querySelectorAll('.projects-wrapper .project-item[data-temas]');
    }

    function projectWrapper() {
        return document.querySelector('.projects-wrapper');
    }

    function hasFilterableGrid() {
        return projectItems().length > 0;
    }

    function getTemaFromUrl() {
        try {
            return norm(new URLSearchParams(window.location.search).get('tema'));
        } catch (e) {
            return '';
        }
    }

    function markActivePills(tema) {
        var nav = document.querySelector('.ensor-proyectos-temas');
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
                /* ignore */
            }
        });
    }

    function applyFilter(tema) {
        var items = projectItems();
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
        var wrap = projectWrapper();
        if (wrap && visible > 0 && tema) {
            window.requestAnimationFrame(function () {
                wrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        }
    }

    function syncUrl(tema) {
        var next = 'projects.html';
        if (tema) {
            next += '?tema=' + encodeURIComponent(tema);
        }
        try {
            history.replaceState({}, '', next);
        } catch (e) {
            /* ignore */
        }
    }

    function initNavClicks() {
        var nav = document.querySelector('.ensor-proyectos-temas');
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
            if (!/^projects\.html(\?|$)/i.test(raw)) {
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

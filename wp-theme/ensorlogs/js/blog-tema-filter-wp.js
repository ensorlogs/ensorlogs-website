/**
 * Filtro blog por tema — versión WordPress (ruta /blog/ configurable vía window.ENSOR_BLOG_PATH).
 */
(function () {
    function norm(s) {
        return String(s || '')
            .toLowerCase()
            .trim();
    }

    function blogBasePath() {
        var p = window.ENSOR_BLOG_PATH || '/blog/';
        if (p.charAt(0) !== '/') {
            p = '/' + p;
        }
        return p.replace(/\/*$/, '/');
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

    function pathMatchesBlog(u) {
        var want = blogBasePath();
        var path = (u.pathname || '').replace(/\/*$/, '/') || '/';
        return path === want || path.toLowerCase() === want.toLowerCase();
    }

    function isBlogNavHref(raw) {
        if (/^blog\.html(\?|$)/i.test(raw)) {
            return true;
        }
        try {
            var u = new URL(raw, window.location.href);
            if (!pathMatchesBlog(u)) {
                return false;
            }
            return true;
        } catch (e) {
            return false;
        }
    }

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
                /* ignore */
            }
        });
    }

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

    function syncUrl(tema) {
        var base = blogBasePath();
        var next = tema ? base + '?tema=' + encodeURIComponent(tema) : base;
        try {
            history.replaceState({}, '', next);
        } catch (e) {
            /* ignore */
        }
    }

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
            if (!isBlogNavHref(raw)) {
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

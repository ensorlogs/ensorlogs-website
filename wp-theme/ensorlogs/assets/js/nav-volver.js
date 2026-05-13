/**
 * =============================================================================
 * Navegación: ocultar “Volver” en la portada (Ensorlogs)
 * =============================================================================
 *
 * Problema de UX
 * ---------------
 * En ``index.html`` el enlace “Volver” no tiene sentido (ya estás en inicio).
 * En el resto de páginas sí: vuelves al home.
 *
 * Implementación
 * ---------------
 * Detectamos si la URL actual es la raíz o ``index.html`` (también con ``file://``
 * para quien abre el HTML en local). Si es portada, añadimos ``.hidden`` y
 * ``aria-hidden`` a todos los ``a.ensor-nav-volver``.
 *
 * CSS relacionado: ``ensor-brand.css`` fuerza ``display: none`` en ese enlace
 * cuando lleva ``.hidden`` (el menú móvil usa flex y anula utilidades Tailwind).
 *
 * Patrón de código: IIFE (función anónima ejecutada al cargar el script) para no
 * declarar variables globales.
 */
(function () {
    function normalizePath(p) {
        p = (p || '').replace(/\\/g, '/');
        if (!p || p === '/') {
            return '/';
        }
        return p.replace(/\/+$/, '') || '/';
    }

    function isHomePage() {
        var g = typeof window !== 'undefined' ? window.ENSORLOGS : null;
        if (g && typeof g.homePath === 'string' && g.homePath.length) {
            var path = normalizePath(window.location.pathname || '');
            var home = normalizePath(g.homePath);
            return path === home;
        }
        var href = window.location.href || '';
        var path = (window.location.pathname || '').replace(/\\/g, '/');
        var tail = (path.split('/').pop() || '').split('?')[0].split('#')[0];
        if (tail === '' || tail === 'index.html') {
            return true;
        }
        if (/^file:/i.test(href)) {
            var last = (href.split('/').pop() || '').split('?')[0].split('#')[0];
            if (last === '' || last === 'index.html') {
                return true;
            }
        }
        return false;
    }

    var onHome = isHomePage();
    document.querySelectorAll('a.ensor-nav-volver').forEach(function (a) {
        if (onHome) {
            a.classList.add('hidden');
            a.setAttribute('aria-hidden', 'true');
        } else {
            a.classList.remove('hidden');
            a.removeAttribute('aria-hidden');
        }
    });
})();

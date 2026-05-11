/**
 * Oculta el enlace "Volver" (a inicio) en la portada; visible en el resto de páginas.
 * Marca aria-hidden cuando está oculto.
 */
(function () {
    function isHomePage() {
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

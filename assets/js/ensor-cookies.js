/*!
 * Ensorlogs · Consentimiento de cookies (RGPD / LOPDGDD)
 *
 * Funcionamiento:
 * - Si no hay consentimiento, muestra el banner y desbloquea solo cookies
 *   técnicas.
 * - Cuando el usuario acepta categorías, libera los `<script type="text/plain"
 *   data-ensor-consent="analytics|marketing">` correspondientes inyectándolos
 *   como scripts ejecutables.
 * - El consentimiento se guarda en localStorage + cookie técnica
 *   `ensorlogs_consent` durante 12 meses.
 * - Se puede reabrir el panel desde cualquier enlace
 *   `<a href="#" data-ensor-cookies-open>...</a>` o
 *   `<button class="ensor-cookies-reopen">...</button>`.
 */
(function () {
    'use strict';
    var d = document;
    var STORAGE_KEY = 'ensorlogs_consent';
    var COOKIE_NAME = 'ensorlogs_consent';
    var COOKIE_DAYS = 365;
    var CATEGORIES = ['necessary', 'analytics', 'marketing'];

    function readConsent() {
        try {
            var raw = localStorage.getItem(STORAGE_KEY);
            if (raw) return JSON.parse(raw);
        } catch (e) {}
        var m = d.cookie.match(new RegExp('(?:^|; )' + COOKIE_NAME + '=([^;]*)'));
        if (m) {
            try { return JSON.parse(decodeURIComponent(m[1])); } catch (e) {}
        }
        return null;
    }
    function writeConsent(consent) {
        try { localStorage.setItem(STORAGE_KEY, JSON.stringify(consent)); } catch (e) {}
        var exp = new Date();
        exp.setDate(exp.getDate() + COOKIE_DAYS);
        d.cookie = COOKIE_NAME + '=' + encodeURIComponent(JSON.stringify(consent)) +
            '; expires=' + exp.toUTCString() +
            '; path=/; SameSite=Lax';
    }
    function clearConsent() {
        try { localStorage.removeItem(STORAGE_KEY); } catch (e) {}
        d.cookie = COOKIE_NAME + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
    }

    function activateConsentedScripts(consent) {
        var nodes = Array.prototype.slice.call(
            d.querySelectorAll('script[type="text/plain"][data-ensor-consent]')
        );
        nodes.forEach(function (s) {
            var need = s.getAttribute('data-ensor-consent');
            if (!consent[need]) return;
            var n = d.createElement('script');
            for (var i = 0; i < s.attributes.length; i++) {
                var a = s.attributes[i];
                if (a.name === 'type' || a.name === 'data-ensor-consent') continue;
                n.setAttribute(a.name, a.value);
            }
            n.type = s.getAttribute('data-ensor-type') || 'text/javascript';
            n.text = s.text || s.innerHTML;
            s.parentNode.insertBefore(n, s);
            s.parentNode.removeChild(s);
        });
        // Evento para que terceros sepan que pueden actuar
        try {
            d.dispatchEvent(new CustomEvent('ensorlogs:consent', { detail: consent }));
        } catch (e) {}
    }

    function ensureBanner(getLegalUrl, openPanelFn) {
        if (d.querySelector('.ensor-cookies')) return d.querySelector('.ensor-cookies');
        var legalUrl = getLegalUrl();
        var el = d.createElement('section');
        el.className = 'ensor-cookies';
        el.setAttribute('role', 'dialog');
        el.setAttribute('aria-label', 'Aviso de cookies');
        el.innerHTML =
            '<h2 class="ensor-cookies__title">Usamos cookies</h2>' +
            '<p class="ensor-cookies__text">Usamos cookies técnicas necesarias para que el sitio funcione y, si tú lo aceptas, cookies de medición y marketing para entender qué contenido funciona mejor. Puedes aceptar, rechazar o ajustar por categoría. Más información en nuestra ' +
                '<a href="' + legalUrl + '" class="ensor-cookies__link">política de cookies</a>.</p>' +
            '<div class="ensor-cookies__actions">' +
                '<button type="button" class="ensor-cookies__btn" data-action="reject">Rechazar todo</button>' +
                '<button type="button" class="ensor-cookies__btn" data-action="customize">Personalizar</button>' +
                '<button type="button" class="ensor-cookies__btn ensor-cookies__btn--primary" data-action="accept">Aceptar todo</button>' +
            '</div>';
        d.body.appendChild(el);

        el.addEventListener('click', function (e) {
            var t = e.target.closest('[data-action]');
            if (!t) return;
            var action = t.getAttribute('data-action');
            if (action === 'accept') {
                writeAndApply({ necessary: true, analytics: true, marketing: true });
                hide();
            } else if (action === 'reject') {
                writeAndApply({ necessary: true, analytics: false, marketing: false });
                hide();
            } else if (action === 'customize') {
                openPanelFn();
            }
        });
        function hide() { el.classList.remove('is-visible'); }
        return el;
    }

    function ensureModal() {
        if (d.querySelector('.ensor-cookies-modal')) return d.querySelector('.ensor-cookies-modal');
        var modal = d.createElement('div');
        modal.className = 'ensor-cookies-modal';
        modal.innerHTML =
            '<div class="ensor-cookies-modal__panel" role="dialog" aria-modal="true" aria-labelledby="ensor-ck-title">' +
                '<h2 id="ensor-ck-title" class="ensor-cookies-modal__title">Preferencias de cookies</h2>' +
                '<p class="ensor-cookies-modal__intro">Activa solo las categorías que quieras. Las cookies técnicas son imprescindibles para que el sitio funcione y por eso no se pueden desactivar.</p>' +

                '<div class="ensor-cookies-modal__row">' +
                    '<div class="ensor-cookies-modal__row-top">' +
                        '<span class="ensor-cookies-modal__row-title">Técnicas (necesarias)</span>' +
                        '<label class="ensor-cookies-switch">' +
                            '<input type="checkbox" checked disabled data-cat="necessary">' +
                            '<span class="ensor-cookies-switch__slider"></span>' +
                        '</label>' +
                    '</div>' +
                    '<p class="ensor-cookies-modal__row-text">Sesión, preferencias de tema y registro de consentimiento. Indispensables.</p>' +
                '</div>' +

                '<div class="ensor-cookies-modal__row">' +
                    '<div class="ensor-cookies-modal__row-top">' +
                        '<span class="ensor-cookies-modal__row-title">Analítica / medición</span>' +
                        '<label class="ensor-cookies-switch">' +
                            '<input type="checkbox" data-cat="analytics">' +
                            '<span class="ensor-cookies-switch__slider"></span>' +
                        '</label>' +
                    '</div>' +
                    '<p class="ensor-cookies-modal__row-text">Datos agregados para entender qué logs y proyectos funcionan mejor.</p>' +
                '</div>' +

                '<div class="ensor-cookies-modal__row">' +
                    '<div class="ensor-cookies-modal__row-top">' +
                        '<span class="ensor-cookies-modal__row-title">Marketing</span>' +
                        '<label class="ensor-cookies-switch">' +
                            '<input type="checkbox" data-cat="marketing">' +
                            '<span class="ensor-cookies-switch__slider"></span>' +
                        '</label>' +
                    '</div>' +
                    '<p class="ensor-cookies-modal__row-text">Recordar interacciones con CTAs y campañas. Hoy no se cargan; se reservaría su uso para colaboraciones puntuales.</p>' +
                '</div>' +

                '<div class="ensor-cookies-modal__actions">' +
                    '<button type="button" class="ensor-cookies__btn" data-action="cancel">Cancelar</button>' +
                    '<button type="button" class="ensor-cookies__btn ensor-cookies__btn--primary" data-action="save">Guardar preferencias</button>' +
                '</div>' +
            '</div>';
        d.body.appendChild(modal);

        modal.addEventListener('click', function (e) {
            if (e.target === modal) { modal.classList.remove('is-open'); return; }
            var t = e.target.closest('[data-action]');
            if (!t) return;
            if (t.getAttribute('data-action') === 'cancel') {
                modal.classList.remove('is-open');
            } else if (t.getAttribute('data-action') === 'save') {
                var c = { necessary: true };
                CATEGORIES.forEach(function (cat) {
                    if (cat === 'necessary') return;
                    var input = modal.querySelector('input[data-cat="' + cat + '"]');
                    c[cat] = !!(input && input.checked);
                });
                writeAndApply(c);
                modal.classList.remove('is-open');
                var b = d.querySelector('.ensor-cookies');
                if (b) b.classList.remove('is-visible');
            }
        });
        return modal;
    }

    function openModal(currentConsent) {
        var modal = ensureModal();
        CATEGORIES.forEach(function (cat) {
            if (cat === 'necessary') return;
            var input = modal.querySelector('input[data-cat="' + cat + '"]');
            if (input) input.checked = !!(currentConsent && currentConsent[cat]);
        });
        modal.classList.add('is-open');
    }

    function writeAndApply(consent) {
        writeConsent(consent);
        activateConsentedScripts(consent);
    }

    function getLegalUrl() {
        // Permite override por meta global: <meta name="ensor-cookies-url" content="/legal/cookies.html">
        var m = d.querySelector('meta[name="ensor-cookies-url"]');
        if (m && m.content) return m.content;
        // Fallback razonable: ruta legal/ a partir del depth actual.
        var path = location.pathname || '/';
        var depth = path.split('/').filter(Boolean).length;
        var prefix = '';
        if (depth > 1) { for (var i = 0; i < depth - 1; i++) prefix += '../'; }
        else if (depth === 1) { prefix = ''; }
        return prefix + 'legal/cookies.html';
    }

    function init() {
        var consent = readConsent();
        if (consent) {
            activateConsentedScripts(consent);
        } else {
            var banner = ensureBanner(getLegalUrl, function () {
                openModal(null);
            });
            banner.classList.add('is-visible');
        }

        // Botones reutilizables para reabrir preferencias
        d.addEventListener('click', function (e) {
            var t = e.target.closest('[data-ensor-cookies-open], .ensor-cookies-reopen');
            if (!t) return;
            e.preventDefault();
            openModal(readConsent() || {});
        });
    }

    // API pública mínima
    window.EnsorCookies = {
        get: readConsent,
        clear: function () { clearConsent(); location.reload(); },
        open: function () { openModal(readConsent() || {}); }
    };

    if (d.readyState !== 'loading') init();
    else d.addEventListener('DOMContentLoaded', init);
})();

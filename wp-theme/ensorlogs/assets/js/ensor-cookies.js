/*!
 * Ensorlogs · Consentimiento de cookies (RGPD / LOPDGDD)
 */
(function () {
    'use strict';
    var d = document;
    var STORAGE_KEY = 'ensorlogs_consent';
    var COOKIE_NAME = 'ensorlogs_consent';
    var COOKIE_DAYS = 365;
    var CATEGORIES = ['necessary', 'analytics', 'marketing'];

    var COPY = {
        es: {
            bannerAria: 'Aviso de cookies',
            bannerTitle: 'Usamos cookies',
            bannerText: 'Usamos cookies técnicas necesarias para que el sitio funcione y, si tú lo aceptas, cookies de medición y marketing para entender qué contenido funciona mejor. Puedes aceptar, rechazar o ajustar por categoría. Más información en nuestra ',
            cookiePolicy: 'política de cookies',
            rejectAll: 'Rechazar todo',
            customize: 'Personalizar',
            acceptAll: 'Aceptar todo',
            modalTitle: 'Preferencias de cookies',
            modalIntro: 'Activa solo las categorías que quieras. Las cookies técnicas son imprescindibles para que el sitio funcione y por eso no se pueden desactivar.',
            catNecessary: 'Técnicas (necesarias)',
            catNecessaryText: 'Sesión, preferencias de tema y registro de consentimiento. Indispensables.',
            catAnalytics: 'Analítica / medición',
            catAnalyticsText: 'Datos agregados para entender qué logs y proyectos funcionan mejor.',
            catMarketing: 'Marketing',
            catMarketingText: 'Recordar interacciones con CTAs y campañas. Hoy no se cargan; se reservaría su uso para colaboraciones puntuales.',
            cancel: 'Cancelar',
            save: 'Guardar preferencias'
        },
        en: {
            bannerAria: 'Cookie notice',
            bannerTitle: 'We use cookies',
            bannerText: 'We use essential technical cookies so the site works and, if you accept, measurement and marketing cookies to understand what content works best. You can accept, reject, or adjust by category. More information in our ',
            cookiePolicy: 'cookie policy',
            rejectAll: 'Reject all',
            customize: 'Customize',
            acceptAll: 'Accept all',
            modalTitle: 'Cookie preferences',
            modalIntro: 'Enable only the categories you want. Technical cookies are required for the site to work and cannot be disabled.',
            catNecessary: 'Technical (required)',
            catNecessaryText: 'Session, theme preferences, and consent record. Essential.',
            catAnalytics: 'Analytics / measurement',
            catAnalyticsText: 'Aggregated data to understand which logs and projects perform best.',
            catMarketing: 'Marketing',
            catMarketingText: 'Remember interactions with CTAs and campaigns. Not loaded today; reserved for occasional collaborations.',
            cancel: 'Cancel',
            save: 'Save preferences'
        }
    };

    function detectLang() {
        var path = (location && location.pathname) || '/';
        if (/\/en(\/|$)/.test(path)) {
            return 'en';
        }
        var meta = d.querySelector('meta[name="ensor-lang"]');
        if (meta && meta.content === 'en') {
            return 'en';
        }
        var htmlLang = d.documentElement && d.documentElement.getAttribute('lang');
        if (htmlLang && String(htmlLang).toLowerCase().indexOf('en') === 0) {
            return 'en';
        }
        return 'es';
    }

    function strings() {
        return COPY[detectLang()] || COPY.es;
    }

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
        try {
            d.dispatchEvent(new CustomEvent('ensorlogs:consent', { detail: consent }));
        } catch (e) {}
    }

    function ensureBanner(getLegalUrl, openPanelFn) {
        if (d.querySelector('.ensor-cookies')) return d.querySelector('.ensor-cookies');
        var t = strings();
        var legalUrl = getLegalUrl();
        var el = d.createElement('section');
        el.className = 'ensor-cookies';
        el.setAttribute('role', 'dialog');
        el.setAttribute('aria-label', t.bannerAria);
        el.innerHTML =
            '<h2 class="ensor-cookies__title">' + t.bannerTitle + '</h2>' +
            '<p class="ensor-cookies__text">' + t.bannerText +
                '<a href="' + legalUrl + '" class="ensor-cookies__link">' + t.cookiePolicy + '</a>.</p>' +
            '<div class="ensor-cookies__actions">' +
                '<button type="button" class="ensor-cookies__btn" data-action="reject">' + t.rejectAll + '</button>' +
                '<button type="button" class="ensor-cookies__btn" data-action="customize">' + t.customize + '</button>' +
                '<button type="button" class="ensor-cookies__btn ensor-cookies__btn--primary" data-action="accept">' + t.acceptAll + '</button>' +
            '</div>';
        d.body.appendChild(el);

        el.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-action]');
            if (!btn) return;
            var action = btn.getAttribute('data-action');
            if (action === 'accept') {
                writeAndApply({ necessary: true, analytics: true, marketing: true });
                el.classList.remove('is-visible');
            } else if (action === 'reject') {
                writeAndApply({ necessary: true, analytics: false, marketing: false });
                el.classList.remove('is-visible');
            } else if (action === 'customize') {
                openPanelFn();
            }
        });
        return el;
    }

    function ensureModal() {
        if (d.querySelector('.ensor-cookies-modal')) return d.querySelector('.ensor-cookies-modal');
        var t = strings();
        var modal = d.createElement('div');
        modal.className = 'ensor-cookies-modal';
        modal.innerHTML =
            '<div class="ensor-cookies-modal__panel" role="dialog" aria-modal="true" aria-labelledby="ensor-ck-title">' +
                '<h2 id="ensor-ck-title" class="ensor-cookies-modal__title">' + t.modalTitle + '</h2>' +
                '<p class="ensor-cookies-modal__intro">' + t.modalIntro + '</p>' +
                '<div class="ensor-cookies-modal__row">' +
                    '<div class="ensor-cookies-modal__row-top">' +
                        '<span class="ensor-cookies-modal__row-title">' + t.catNecessary + '</span>' +
                        '<label class="ensor-cookies-switch">' +
                            '<input type="checkbox" checked disabled data-cat="necessary">' +
                            '<span class="ensor-cookies-switch__slider"></span>' +
                        '</label>' +
                    '</div>' +
                    '<p class="ensor-cookies-modal__row-text">' + t.catNecessaryText + '</p>' +
                '</div>' +
                '<div class="ensor-cookies-modal__row">' +
                    '<div class="ensor-cookies-modal__row-top">' +
                        '<span class="ensor-cookies-modal__row-title">' + t.catAnalytics + '</span>' +
                        '<label class="ensor-cookies-switch">' +
                            '<input type="checkbox" data-cat="analytics">' +
                            '<span class="ensor-cookies-switch__slider"></span>' +
                        '</label>' +
                    '</div>' +
                    '<p class="ensor-cookies-modal__row-text">' + t.catAnalyticsText + '</p>' +
                '</div>' +
                '<div class="ensor-cookies-modal__row">' +
                    '<div class="ensor-cookies-modal__row-top">' +
                        '<span class="ensor-cookies-modal__row-title">' + t.catMarketing + '</span>' +
                        '<label class="ensor-cookies-switch">' +
                            '<input type="checkbox" data-cat="marketing">' +
                            '<span class="ensor-cookies-switch__slider"></span>' +
                        '</label>' +
                    '</div>' +
                    '<p class="ensor-cookies-modal__row-text">' + t.catMarketingText + '</p>' +
                '</div>' +
                '<div class="ensor-cookies-modal__actions">' +
                    '<button type="button" class="ensor-cookies__btn" data-action="cancel">' + t.cancel + '</button>' +
                    '<button type="button" class="ensor-cookies__btn ensor-cookies__btn--primary" data-action="save">' + t.save + '</button>' +
                '</div>' +
            '</div>';
        d.body.appendChild(modal);

        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                modal.classList.remove('is-open');
                return;
            }
            var btn = e.target.closest('[data-action]');
            if (!btn) return;
            if (btn.getAttribute('data-action') === 'cancel') {
                modal.classList.remove('is-open');
            } else if (btn.getAttribute('data-action') === 'save') {
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
        var m = d.querySelector('meta[name="ensor-cookies-url"]');
        if (m && m.content) return m.content;

        var path = (location && location.pathname) || '/';
        var parts = path.split('/').filter(Boolean);
        var onEn = parts[0] === 'en';
        if (onEn) {
            parts.shift();
        }
        var file = parts.length ? parts[parts.length - 1] : '';
        var depth = parts.length;
        if (file && /\.html$/i.test(file)) {
            depth = Math.max(0, parts.length - 1);
        }
        var prefix = depth > 0 ? '../'.repeat(depth) : '';
        if (onEn && depth === 0) {
            prefix = '';
        }
        return prefix + (onEn ? 'legal/cookies.html' : 'legal/cookies.html');
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

        d.addEventListener('click', function (e) {
            var t = e.target.closest('[data-ensor-cookies-open], .ensor-cookies-reopen');
            if (!t) return;
            e.preventDefault();
            openModal(readConsent() || {});
        });
    }

    window.EnsorCookies = {
        get: readConsent,
        clear: function () { clearConsent(); location.reload(); },
        open: function () { openModal(readConsent() || {}); }
    };

    if (d.readyState !== 'loading') init();
    else d.addEventListener('DOMContentLoaded', init);
})();

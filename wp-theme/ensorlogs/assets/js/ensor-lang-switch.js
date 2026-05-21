/*!
 * Ensorlogs · Language switch (ES / EN) — navega a la versión traducida del sitio.
 */
(function () {
    'use strict';
    var d = document;

    function readMeta(name) {
        var el = d.querySelector('meta[name="' + name + '"]');
        return el ? (el.getAttribute('content') || '').trim() : '';
    }

    function assetsPrefix() {
        var base = readMeta('ensor-assets-base');
        if (base) {
            return base.replace(/\/?$/, '/');
        }
        var parts = ((d.location && d.location.pathname) || '/').split('/').filter(Boolean);
        if (parts.length && /\.[a-z0-9]+$/i.test(parts[parts.length - 1])) {
            parts.pop();
        }
        return (parts.length ? '../'.repeat(parts.length) : '') + 'assets/';
    }

    function isEnPath(path) {
        return /\/en(\/|$)/.test(path) || /^en\//.test(path);
    }

    function detectLang() {
        var path = (d.location && d.location.pathname) || '/';
        if (isEnPath(path)) {
            return 'en';
        }
        var explicit = readMeta('ensor-lang');
        if (explicit === 'en' || explicit === 'es') {
            return explicit;
        }
        return 'es';
    }

    function resolveAltUrl(targetLang) {
        var alt = readMeta('ensor-lang-alt');
        if (alt) {
            try {
                return new URL(alt, d.location.href).href;
            } catch (e) {
                if (alt.indexOf('http') === 0) {
                    return alt;
                }
            }
        }

        var path = (d.location && d.location.pathname) || '/';
        var parts = path.split('/').filter(Boolean);
        var onEn = parts[0] === 'en';
        if (onEn) {
            parts.shift();
        }
        var file = parts.length ? parts[parts.length - 1] : '';
        var hasHtml = /\.html$/i.test(file);
        if (!file || !hasHtml) {
            file = 'index.html';
        }
        var relPath = parts.join('/');
        if (file && file !== 'index.html') {
            relPath = relPath ? relPath : file;
        } else if (file === 'index.html' && parts.length) {
            relPath = parts.join('/');
        } else {
            relPath = '';
        }

        function relUrl(target) {
            try {
                return new URL(target, d.location.href).href;
            } catch (e) {
                return target;
            }
        }

        if (targetLang === 'en') {
            if (onEn) {
                return d.location.href;
            }
            if (relPath) {
                return relUrl('en/' + relPath);
            }
            return relUrl('en/index.html');
        }

        if (!onEn) {
            return d.location.href;
        }
        var up = parts.length > 1 && parts[0] === 'en' ? '../'.repeat(parts.length - 1) : '../';
        if (relPath) {
            return relUrl(up + relPath);
        }
        return relUrl(up + 'index.html');
    }

    function ready(fn) {
        if (d.readyState !== 'loading') {
            fn();
        } else {
            d.addEventListener('DOMContentLoaded', fn);
        }
    }

    function makeFlagImg(code) {
        var img = d.createElement('img');
        var prefix = assetsPrefix();
        if (code === 'en') {
            img.src = prefix + 'img/flag-usa.svg';
            img.alt = 'English';
        } else {
            img.src = prefix + 'img/flag-venezuela.svg';
            img.alt = 'Español';
        }
        img.width = 16;
        img.height = 11;
        img.decoding = 'async';
        img.loading = 'lazy';
        img.className = code === 'en' ? 'ensor-lang-switch__flag ensor-flag-us' : 'ensor-lang-switch__flag ensor-flag-ve';
        return img;
    }

    function makeBtn(code, label, lang) {
        var btn = d.createElement('button');
        btn.type = 'button';
        btn.className = 'ensor-lang-switch__btn';
        btn.appendChild(makeFlagImg(code));
        var text = d.createElement('span');
        text.className = 'ensor-lang-switch__code';
        text.textContent = label;
        btn.appendChild(text);
        btn.setAttribute('lang', code);
        if (code === lang) {
            btn.classList.add('is-active');
            btn.disabled = true;
            btn.setAttribute('aria-current', 'true');
        } else {
            btn.setAttribute('aria-label', code === 'en' ? 'Switch to English' : 'Cambiar a español');
            btn.addEventListener('click', function () {
                var url = resolveAltUrl(code);
                if (url) {
                    try {
                        localStorage.setItem('ensor_lang', code);
                    } catch (e) {}
                    d.location.assign(url);
                }
            });
        }
        return btn;
    }

    function buildNav(extraClass, id) {
        var lang = detectLang();
        var nav = d.createElement('nav');
        nav.id = id;
        nav.className = 'ensor-lang-switch ' + extraClass;
        nav.setAttribute('aria-label', lang === 'en' ? 'Language' : 'Idioma');
        nav.appendChild(makeBtn('es', 'ES', lang));
        nav.appendChild(makeBtn('en', 'EN', lang));
        return nav;
    }

    function buildSwitcher() {
        if (d.getElementById('ensor-lang-switch')) {
            return;
        }

        var lang = detectLang();
        var floating = buildNav('ensor-lang-switch--floating', 'ensor-lang-switch');
        d.body.appendChild(floating);

        var themeToggle = d.querySelector('.mobile_menu .ensor-mobile-theme-toggle');
        if (themeToggle) {
            var mobile = buildNav('ensor-lang-switch--mobile', 'ensor-lang-switch-mobile');
            themeToggle.insertAdjacentElement('afterend', mobile);
        }

        if (d.documentElement) {
            d.documentElement.setAttribute('lang', lang === 'en' ? 'en' : 'es');
        }
    }

    ready(buildSwitcher);
})();

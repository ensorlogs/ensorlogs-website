/*!
 * Ensorlogs · Accesibilidad
 * - Toolbar flotante (tamaño de texto, espaciado, alto contraste).
 * - Preferencias persistentes en localStorage.
 * - Se autoinyecta en cualquier página que cargue este script.
 *
 * Las preferencias también respetan prefers-reduced-motion y
 * prefers-contrast: more para usuarios que no abren el panel.
 */
(function () {
    'use strict';
    var d = document;
    var STORAGE_KEY = 'ensor_a11y_prefs_v1';

    function loadPrefs() {
        try {
            var raw = localStorage.getItem(STORAGE_KEY);
            return raw ? JSON.parse(raw) : {};
        } catch (e) { return {}; }
    }
    function savePrefs(p) {
        try { localStorage.setItem(STORAGE_KEY, JSON.stringify(p)); } catch (e) {}
    }

    function applyPrefs(p) {
        var root = d.documentElement;
        root.classList.toggle('ensor-a11y-text-lg', p.text === 'lg');
        root.classList.toggle('ensor-a11y-text-xl', p.text === 'xl');
        root.classList.toggle('ensor-a11y-spacing', !!p.spacing);
        root.classList.toggle('ensor-a11y-contrast', !!p.contrast);
    }

    function ready(fn) {
        if (d.readyState !== 'loading') fn();
        else d.addEventListener('DOMContentLoaded', fn);
    }

    function ensureSkipLink() {
        if (d.querySelector('.ensor-skip-link')) return;
        var a = d.createElement('a');
        a.href = '#main-content';
        a.className = 'ensor-skip-link';
        a.textContent = 'Saltar al contenido principal';
        if (d.body.firstChild) d.body.insertBefore(a, d.body.firstChild);
        else d.body.appendChild(a);
        // Garantiza un destino #main-content
        if (!d.getElementById('main-content')) {
            var target = d.querySelector('main, .main-content, .ensor-reader, article');
            if (target) target.id = 'main-content';
        }
    }

    function buildToolbar(prefs) {
        var fab = d.createElement('button');
        fab.className = 'ensor-a11y-fab';
        fab.type = 'button';
        fab.setAttribute('aria-label', 'Abrir opciones de accesibilidad');
        fab.setAttribute('aria-expanded', 'false');
        fab.innerHTML =
            '<svg viewBox="0 0 24 24" aria-hidden="true">' +
            '<circle cx="12" cy="4" r="2"/>' +
            '<path d="M3 8h18v2l-6 1v3l2 7h-2l-2-6h-2l-2 6H7l2-7v-3L3 10z"/>' +
            '</svg>';

        var panel = d.createElement('section');
        panel.className = 'ensor-a11y-panel';
        panel.setAttribute('role', 'dialog');
        panel.setAttribute('aria-label', 'Opciones de accesibilidad');
        panel.innerHTML = [
            '<h2>Accesibilidad</h2>',

            '<div class="ensor-a11y-panel__row">',
                '<div>',
                    '<span class="ensor-a11y-panel__label">Tamaño del texto</span>',
                    '<span class="ensor-a11y-panel__hint">Aumenta el cuerpo de la lectura.</span>',
                '</div>',
                '<div class="ensor-a11y-group" role="group" aria-label="Tamaño del texto">',
                    '<button type="button" class="ensor-a11y-btn" data-text="md" aria-label="Tamaño normal">A</button>',
                    '<button type="button" class="ensor-a11y-btn" data-text="lg" aria-label="Tamaño grande">A+</button>',
                    '<button type="button" class="ensor-a11y-btn" data-text="xl" aria-label="Tamaño muy grande">A++</button>',
                '</div>',
            '</div>',

            '<div class="ensor-a11y-panel__row">',
                '<div>',
                    '<span class="ensor-a11y-panel__label">Espaciado de lectura</span>',
                    '<span class="ensor-a11y-panel__hint">Líneas y letras más holgadas.</span>',
                '</div>',
                '<button type="button" class="ensor-a11y-btn" data-toggle="spacing" aria-pressed="false">Activar</button>',
            '</div>',

            '<div class="ensor-a11y-panel__row">',
                '<div>',
                    '<span class="ensor-a11y-panel__label">Alto contraste</span>',
                    '<span class="ensor-a11y-panel__hint">Fondo negro, texto blanco y acento amarillo.</span>',
                '</div>',
                '<button type="button" class="ensor-a11y-btn" data-toggle="contrast" aria-pressed="false">Activar</button>',
            '</div>',

            '<div class="ensor-a11y-panel__row">',
                '<div>',
                    '<span class="ensor-a11y-panel__label">Restablecer</span>',
                    '<span class="ensor-a11y-panel__hint">Vuelve a la apariencia predeterminada.</span>',
                '</div>',
                '<button type="button" class="ensor-a11y-btn" data-action="reset">Restablecer</button>',
            '</div>'
        ].join('');

        d.body.appendChild(fab);
        d.body.appendChild(panel);

        function refreshUI() {
            var textBtns = panel.querySelectorAll('[data-text]');
            textBtns.forEach(function (b) {
                b.classList.toggle('is-active', (prefs.text || 'md') === b.getAttribute('data-text'));
            });
            var sp = panel.querySelector('[data-toggle="spacing"]');
            sp.classList.toggle('is-active', !!prefs.spacing);
            sp.textContent = prefs.spacing ? 'Activado' : 'Activar';
            sp.setAttribute('aria-pressed', prefs.spacing ? 'true' : 'false');
            var co = panel.querySelector('[data-toggle="contrast"]');
            co.classList.toggle('is-active', !!prefs.contrast);
            co.textContent = prefs.contrast ? 'Activado' : 'Activar';
            co.setAttribute('aria-pressed', prefs.contrast ? 'true' : 'false');
        }

        fab.addEventListener('click', function () {
            var open = !panel.classList.contains('is-open');
            panel.classList.toggle('is-open', open);
            fab.setAttribute('aria-expanded', open ? 'true' : 'false');
        });

        panel.addEventListener('click', function (e) {
            var t = e.target.closest('button');
            if (!t) return;
            if (t.hasAttribute('data-text')) {
                prefs.text = t.getAttribute('data-text');
            } else if (t.getAttribute('data-toggle') === 'spacing') {
                prefs.spacing = !prefs.spacing;
            } else if (t.getAttribute('data-toggle') === 'contrast') {
                prefs.contrast = !prefs.contrast;
            } else if (t.getAttribute('data-action') === 'reset') {
                prefs = {};
            }
            applyPrefs(prefs);
            savePrefs(prefs);
            refreshUI();
        });

        d.addEventListener('click', function (e) {
            if (panel.classList.contains('is-open') &&
                !panel.contains(e.target) && !fab.contains(e.target)) {
                panel.classList.remove('is-open');
                fab.setAttribute('aria-expanded', 'false');
            }
        });

        d.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && panel.classList.contains('is-open')) {
                panel.classList.remove('is-open');
                fab.setAttribute('aria-expanded', 'false');
                fab.focus();
            }
        });

        refreshUI();
    }

    ready(function () {
        var prefs = loadPrefs();
        // Respeta preferencia del sistema si nunca el usuario tocó nada.
        if (Object.keys(prefs).length === 0 && window.matchMedia) {
            if (matchMedia('(prefers-contrast: more)').matches) prefs.contrast = true;
        }
        applyPrefs(prefs);
        ensureSkipLink();
        buildToolbar(prefs);
    });
})();

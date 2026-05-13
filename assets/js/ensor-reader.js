/**
 * Ensorlogs · Reader UX
 *
 * Activa, en cada Log individual:
 *  - Barra de progreso de lectura (fixed top).
 *  - Chip flotante con el topic / sección actual.
 *  - TOC sticky (desktop) o sheet flotante (mobile) generado a partir de los h2/h3.
 *  - Filtro por audiencia (estudiante, profesional, profesor, datos, etc.)
 *    cuando hay secciones marcadas con `.ensor-aud-section[data-aud="..."]`.
 *
 * No depende de jQuery; jQuery puede seguir cargado para otros scripts.
 */
(function () {
    'use strict';

    var d = document;
    var body = d.body || d.documentElement;
    if (!body) return;

    var root = d.querySelector('.ensor-reader');
    if (!root) return;

    var contentEl = root.querySelector('.ensor-reader-body');
    if (!contentEl) return;

    /* ------------------------------------------------------------------
     * Progress bar
     * ------------------------------------------------------------------ */
    var progressFill = d.querySelector('.ensor-reader-progress__fill');
    var topicChip = d.querySelector('.ensor-reader-topic');
    var topicChipText = topicChip ? topicChip.querySelector('.ensor-reader-topic__text') : null;

    function clamp(n, min, max) {
        return Math.max(min, Math.min(max, n));
    }

    function updateProgress() {
        var rect = contentEl.getBoundingClientRect();
        var winH = window.innerHeight || d.documentElement.clientHeight;
        var top = rect.top;
        var height = rect.height;
        if (height <= 0) return;

        // 0 cuando el inicio del contenido aún no entró bajo la mitad superior;
        // 1 cuando el final del contenido ya pasó por la mitad inferior.
        var scrolled = clamp((-top + winH * 0.25) / (height - winH * 0.6), 0, 1);
        if (progressFill) {
            progressFill.style.width = (scrolled * 100).toFixed(2) + '%';
        }
    }

    /* ------------------------------------------------------------------
     * TOC build: si el HTML no trae <nav.ensor-reader-toc>, se genera.
     * Se cogen h2 y h3 dentro del cuerpo.
     * ------------------------------------------------------------------ */
    function slugify(text) {
        return String(text || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/(^-|-$)/g, '')
            .substring(0, 80);
    }

    function ensureHeadingIds() {
        var seen = {};
        var headings = contentEl.querySelectorAll('h2, h3');
        for (var i = 0; i < headings.length; i++) {
            var h = headings[i];
            if (!h.id) {
                var base = slugify(h.textContent) || ('seccion-' + (i + 1));
                var id = base;
                var n = 1;
                while (seen[id] || d.getElementById(id)) {
                    n += 1;
                    id = base + '-' + n;
                }
                seen[id] = true;
                h.id = id;
            }
            // anchor discreto al final del título
            if (!h.querySelector('.ensor-anchor')) {
                var a = d.createElement('a');
                a.href = '#' + h.id;
                a.className = 'ensor-anchor';
                a.setAttribute('aria-hidden', 'true');
                a.tabIndex = -1;
                a.textContent = '#';
                h.appendChild(a);
            }
        }
        return headings;
    }

    function buildTOC(headings) {
        var tocList = d.querySelectorAll('.ensor-reader-toc__list');
        if (!tocList.length || !headings.length) return [];

        var html = '';
        for (var i = 0; i < headings.length; i++) {
            var h = headings[i];
            var tag = h.tagName.toLowerCase();
            var cls = tag === 'h3' ? 'ensor-reader-toc__item ensor-reader-toc__item--h3' : 'ensor-reader-toc__item';
            html += '<li class="' + cls + '">' +
                '<a href="#' + h.id + '" class="ensor-reader-toc__link" data-target="' + h.id + '">' +
                escapeHtml(h.textContent.replace(/#$/, '').trim()) +
                '</a></li>';
        }
        for (var j = 0; j < tocList.length; j++) {
            tocList[j].innerHTML = html;
        }
        return Array.prototype.slice.call(d.querySelectorAll('.ensor-reader-toc__link'));
    }

    function escapeHtml(s) {
        return String(s || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    /* ------------------------------------------------------------------
     * Active heading: IntersectionObserver
     * ------------------------------------------------------------------ */
    function activateLink(id) {
        var links = d.querySelectorAll('.ensor-reader-toc__link');
        for (var i = 0; i < links.length; i++) {
            links[i].classList.toggle('is-current', links[i].dataset.target === id);
        }
        if (topicChip && topicChipText) {
            var match = contentEl.querySelector('#' + window.CSS.escape(id));
            var label = match ? match.textContent.replace(/#$/, '').trim() : '';
            topicChipText.textContent = label;
            topicChip.classList.toggle('is-visible', !!label);
        }
    }

    function watchHeadings(headings) {
        if (!('IntersectionObserver' in window) || !headings.length) return;
        var current = headings[0].id;
        activateLink(current);
        var obs = new IntersectionObserver(
            function (entries) {
                entries.forEach(function (e) {
                    if (e.isIntersecting) {
                        current = e.target.id;
                        activateLink(current);
                    }
                });
            },
            { rootMargin: '-30% 0px -55% 0px', threshold: 0.01 }
        );
        for (var i = 0; i < headings.length; i++) obs.observe(headings[i]);
    }

    /* ------------------------------------------------------------------
     * Audience filter
     * ------------------------------------------------------------------ */
    function audienceLabels() {
        // Etiquetas legibles para data-aud="..."
        return {
            context:      'Contexto',
            data:         'Datos',
            student:      'Como estudiante',
            teacher:      'Como profesor',
            professional: 'Como profesional',
            beginner:     'Para empezar',
            advanced:     'Avanzado',
            client:       'Para clientes'
        };
    }

    // Orden preferente de los chips del filtro. Lo que no esté aquí va al final.
    var AUDIENCE_ORDER = [
        'context', 'data', 'student', 'teacher', 'professional',
        'beginner', 'advanced', 'client'
    ];

    function initAudienceChips() {
        var bar = d.querySelector('.ensor-reader-aud');
        if (!bar) return;
        var sections = Array.prototype.slice.call(contentEl.querySelectorAll('.ensor-aud-section'));
        if (!sections.length) {
            bar.style.display = 'none';
            return;
        }

        // Set data-aud-label para cada sección (visible en CSS)
        var labelsMap = audienceLabels();
        var present = {};
        sections.forEach(function (sec) {
            var audAttr = (sec.getAttribute('data-aud') || '').trim().toLowerCase();
            if (!audAttr) return;
            // Permitir múltiples audiencias por sección, separadas por espacio o coma
            var auds = audAttr.split(/[\s,]+/).filter(Boolean);
            sec.setAttribute('data-aud', auds.join(' '));
            sec.setAttribute('data-aud-label', auds.map(function (a) { return labelsMap[a] || titleCase(a); }).join(' · '));
            auds.forEach(function (a) { present[a] = true; });
        });

        var keys = Object.keys(present).sort(function (a, b) {
            var ia = AUDIENCE_ORDER.indexOf(a);
            var ib = AUDIENCE_ORDER.indexOf(b);
            if (ia === -1) ia = 999;
            if (ib === -1) ib = 999;
            if (ia !== ib) return ia - ib;
            return a.localeCompare(b);
        });
        if (!keys.length) {
            bar.style.display = 'none';
            return;
        }

        bar.innerHTML = '<span class="ensor-reader-aud__label">Filtrar</span>' +
            '<button type="button" class="ensor-reader-aud__chip is-active" data-aud="*">Todo</button>' +
            keys.map(function (a) {
                return '<button type="button" class="ensor-reader-aud__chip" data-aud="' + escapeHtml(a) + '">' + escapeHtml(labelsMap[a] || titleCase(a)) + '</button>';
            }).join('');

        bar.addEventListener('click', function (e) {
            var btn = e.target.closest('.ensor-reader-aud__chip');
            if (!btn) return;
            var target = btn.dataset.aud;
            var chips = bar.querySelectorAll('.ensor-reader-aud__chip');
            chips.forEach(function (c) { c.classList.toggle('is-active', c === btn); });
            sections.forEach(function (sec) {
                if (target === '*') {
                    sec.hidden = false;
                    return;
                }
                var auds = (sec.getAttribute('data-aud') || '').split(' ');
                sec.hidden = auds.indexOf(target) === -1;
            });
            updateProgress();
        });
    }

    function titleCase(s) {
        return s.charAt(0).toUpperCase() + s.slice(1);
    }

    /* ------------------------------------------------------------------
     * Mobile TOC sheet
     * ------------------------------------------------------------------ */
    function initMobileSheet() {
        var trigger = d.querySelector('.ensor-reader-toc-toggle');
        var sheet = d.querySelector('.ensor-reader-toc-sheet');
        if (!trigger || !sheet) return;
        trigger.addEventListener('click', function () {
            sheet.classList.add('is-open');
        });
        sheet.addEventListener('click', function (e) {
            if (e.target === sheet || e.target.closest('.ensor-reader-toc__link')) {
                sheet.classList.remove('is-open');
            }
        });
    }

    /* ------------------------------------------------------------------
     * Prompt IA — botón copiar
     * Cualquier .ensor-ai-prompt[data-copy] o con un <pre>/<code> dentro
     * recibe un botón "Copiar". Si ya existe un .ensor-ai-prompt__copy
     * en el HTML, sólo le conectamos el handler.
     * ------------------------------------------------------------------ */
    function initAiPrompts() {
        var prompts = Array.prototype.slice.call(d.querySelectorAll('.ensor-ai-prompt'));
        prompts.forEach(function (box) {
            // Texto a copiar: prioriza data-copy; si no, primer <pre> o <code>.
            var explicit = box.getAttribute('data-copy');
            var source = box.querySelector('pre, code, .ensor-ai-prompt__code');
            if (!explicit && !source) return;

            var btn = box.querySelector('.ensor-ai-prompt__copy');
            if (!btn) {
                btn = d.createElement('button');
                btn.type = 'button';
                btn.className = 'ensor-ai-prompt__copy';
                btn.innerHTML =
                    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" ' +
                    'stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" ' +
                    'width="14" height="14" aria-hidden="true">' +
                    '<rect x="9" y="9" width="11" height="11" rx="2"/>' +
                    '<path d="M5 15V5a2 2 0 0 1 2-2h10"/></svg>' +
                    '<span>Copiar prompt</span>';
                box.appendChild(btn);
            }

            btn.addEventListener('click', function () {
                var text = explicit || (source ? source.innerText : '');
                if (!text) return;
                copyToClipboard(text).then(function (ok) {
                    if (!ok) return;
                    var label = btn.querySelector('span');
                    var original = label ? label.textContent : '';
                    btn.classList.add('is-ok');
                    if (label) label.textContent = '¡Copiado!';
                    setTimeout(function () {
                        btn.classList.remove('is-ok');
                        if (label) label.textContent = original || 'Copiar prompt';
                    }, 1600);
                });
            });
        });
    }

    function copyToClipboard(text) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text).then(
                function () { return true; },
                function () { return false; }
            );
        }
        // Fallback (http, navegadores viejos)
        return new Promise(function (resolve) {
            try {
                var ta = d.createElement('textarea');
                ta.value = text;
                ta.setAttribute('readonly', '');
                ta.style.position = 'fixed';
                ta.style.opacity = '0';
                d.body.appendChild(ta);
                ta.select();
                var ok = d.execCommand('copy');
                d.body.removeChild(ta);
                resolve(ok);
            } catch (err) { resolve(false); }
        });
    }

    /* ------------------------------------------------------------------
     * Init
     * ------------------------------------------------------------------ */
    function ready(fn) {
        if (d.readyState !== 'loading') fn();
        else d.addEventListener('DOMContentLoaded', fn);
    }

    ready(function () {
        var headings = ensureHeadingIds();
        buildTOC(headings);
        watchHeadings(headings);
        initAudienceChips();
        initMobileSheet();
        initAiPrompts();
        updateProgress();
        window.addEventListener('scroll', updateProgress, { passive: true });
        window.addEventListener('resize', updateProgress, { passive: true });
    });
})();

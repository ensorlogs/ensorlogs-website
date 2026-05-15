/**
 * Ensorlogs · Reader UX
 *
 * Activa, en cada Log individual:
 *  - Barra de progreso de lectura (fixed, debajo del header del sitio).
 *  - Chip flotante con el topic / sección actual (debajo de la barra);
 *    permanece oculto hasta que la cabecera del log (título y meta) queda
 *    por encima del chip, para no tapar el h1 al cargar.
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
     * Altura bajo el header del sitio: alinea barra + chip con el nav fijo
     * (incluye estado .is-sticky del theme).
     * ------------------------------------------------------------------ */
    var offsetRafQueued = false;

    function findSiteHeader() {
        var main = d.querySelector('main#main-content') || d.querySelector('main.app');
        if (!main) return null;
        var ch = main.children;
        for (var i = 0; i < ch.length; i++) {
            if (ch[i].tagName === 'HEADER') {
                return ch[i];
            }
        }
        return null;
    }

    function syncSiteHeaderOffset() {
        var siteHeader = findSiteHeader();
        if (!siteHeader) {
            d.documentElement.style.removeProperty('--ensor-site-header-offset');
            return;
        }
        var y = Math.ceil(siteHeader.getBoundingClientRect().bottom);
        if (y < 0) y = 0;
        d.documentElement.style.setProperty('--ensor-site-header-offset', y + 'px');
    }

    function scheduleSyncSiteHeaderOffset() {
        if (offsetRafQueued) return;
        offsetRafQueued = true;
        requestAnimationFrame(function () {
            offsetRafQueued = false;
            syncSiteHeaderOffset();
        });
    }

    /* ------------------------------------------------------------------
     * Progress bar
     * ------------------------------------------------------------------ */
    var progressFill = d.querySelector('.ensor-reader-progress__fill');
    var topicChip = d.querySelector('.ensor-reader-topic');
    var topicChipText = topicChip ? topicChip.querySelector('.ensor-reader-topic__text') : null;

    /** Borde superior del chip fijo (px en viewport), alineado con ensor-reader.css */
    function getReaderChipTopPx() {
        var siteHeader = findSiteHeader();
        var headerBottom = 0;
        if (siteHeader) {
            headerBottom = Math.ceil(siteHeader.getBoundingClientRect().bottom);
            if (headerBottom < 0) {
                headerBottom = 0;
            }
        } else {
            var st = getComputedStyle(d.documentElement);
            var raw = st.getPropertyValue('--ensor-site-header-offset').trim();
            headerBottom = parseFloat(raw);
            if (!headerBottom || isNaN(headerBottom)) {
                headerBottom = 104;
            }
        }
        var st2 = getComputedStyle(d.documentElement);
        var ph = parseFloat(st2.getPropertyValue('--ensor-reader-progress-h').trim());
        if (!ph || isNaN(ph)) {
            ph = 3;
        }
        return headerBottom + ph + 6;
    }

    /** Evita solapar título/meta: el chip solo se muestra cuando la cabecera ya subió por encima de la zona fija. */
    function isReaderIntroPastTopicChip() {
        var head = root.querySelector('.ensor-reader-head');
        if (!head) {
            var y = window.pageYOffset || d.documentElement.scrollTop || 0;
            return y > 160;
        }
        var chipTop = getReaderChipTopPx();
        return head.getBoundingClientRect().bottom < chipTop + 4;
    }

    function applyTopicChipVisibility() {
        if (!topicChip || !topicChipText) return;
        var label = (topicChipText.textContent || '').trim();
        if (!label) {
            topicChip.classList.remove('is-visible');
            return;
        }
        topicChip.classList.toggle('is-visible', isReaderIntroPastTopicChip());
    }

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

    /** Ancla estable para el bloque de quiz (TOC y #hash). */
    function ensureQuizSectionId() {
        var quiz = contentEl.querySelector('.ensor-quiz[data-quiz]');
        if (!quiz) return null;
        if (!quiz.id) {
            var slug = (quiz.getAttribute('data-slug') || 'quiz').trim();
            var safe = slug.replace(/[^a-zA-Z0-9_-]/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '');
            quiz.id = safe ? ('ensor-quiz-' + safe) : 'ensor-quiz-log';
        }
        return quiz;
    }

    function buildTOC(headings, quizEl) {
        var tocList = d.querySelectorAll('.ensor-reader-toc__list');
        if (!tocList.length) return [];

        quizEl = quizEl || ensureQuizSectionId();

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
        if (quizEl && quizEl.id) {
            html += '<li class="ensor-reader-toc__item ensor-reader-toc__item--quiz">' +
                '<a href="#' + quizEl.id + '" class="ensor-reader-toc__link" data-target="' + quizEl.id + '">' +
                escapeHtml('Quiz') +
                '</a></li>';
        }
        if (!html) return [];

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
            var label = '';
            if (match) {
                if (match.classList && match.classList.contains('ensor-quiz')) {
                    label = 'Quiz';
                } else {
                    label = match.textContent.replace(/#$/, '').trim();
                }
            }
            topicChipText.textContent = label;
            applyTopicChipVisibility();
        }
    }

    function watchHeadings(headings, quizEl) {
        if (!('IntersectionObserver' in window)) return;
        quizEl = quizEl || ensureQuizSectionId();
        var toWatch = Array.prototype.slice.call(headings || []);
        if (quizEl) {
            toWatch.push(quizEl);
        }
        if (!toWatch.length) return;

        var current = toWatch[0].id;
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
        for (var i = 0; i < toWatch.length; i++) obs.observe(toWatch[i]);
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
        // Mapa audiencia -> primer DOM node con esa audiencia (para hacer scroll)
        var firstSectionFor = {};
        sections.forEach(function (sec) {
            var audAttr = (sec.getAttribute('data-aud') || '').trim().toLowerCase();
            if (!audAttr) return;
            // Asegurar que ninguna sección esté oculta por configuración previa
            sec.hidden = false;
            // Permitir múltiples audiencias por sección, separadas por espacio o coma
            var auds = audAttr.split(/[\s,]+/).filter(Boolean);
            sec.setAttribute('data-aud', auds.join(' '));
            sec.setAttribute('data-aud-label', auds.map(function (a) { return labelsMap[a] || titleCase(a); }).join(' · '));
            auds.forEach(function (a) {
                present[a] = true;
                if (!firstSectionFor[a]) firstSectionFor[a] = sec;
            });
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

        // Ahora es navegación, no filtro: cambiamos rol y label.
        bar.setAttribute('role', 'navigation');
        bar.setAttribute('aria-label', 'Saltar a sección del log');
        bar.dataset.mode = 'jump';

        bar.innerHTML = '<span class="ensor-reader-aud__label">Ir a</span>' +
            keys.map(function (a) {
                var sec = firstSectionFor[a];
                var href = sec && sec.id ? '#' + sec.id :
                           (sec && sec.querySelector('[id]') ? '#' + sec.querySelector('[id]').id : '');
                return '<a class="ensor-reader-aud__chip" data-aud="' + escapeHtml(a) + '"' +
                       (href ? ' href="' + escapeHtml(href) + '"' : '') +
                       '>' + escapeHtml(labelsMap[a] || titleCase(a)) + '</a>';
            }).join('');

        // Offset vertical del scroll: depende del progress bar fijo + accesibilidad toolbar
        function getScrollOffset() {
            var pb = d.querySelector('.ensor-reader-progress');
            if (pb) {
                return Math.round(pb.getBoundingClientRect().bottom) + 10;
            }
            var st = getComputedStyle(d.documentElement);
            var raw = st.getPropertyValue('--ensor-site-header-offset').trim();
            var topPx = parseFloat(raw);
            if (!topPx || isNaN(topPx)) {
                topPx = 104;
            }
            var ph = parseFloat(st.getPropertyValue('--ensor-reader-progress-h'));
            if (!ph || isNaN(ph)) ph = 3;
            return Math.round(topPx + ph) + 10;
        }

        function flashSection(sec) {
            if (!sec) return;
            sec.classList.remove('is-jump-target');
            // forzar reflow para reiniciar la animación
            void sec.offsetWidth;
            sec.classList.add('is-jump-target');
            setTimeout(function () { sec.classList.remove('is-jump-target'); }, 2200);
        }

        function jumpTo(aud) {
            var sec = firstSectionFor[aud];
            if (!sec) return;
            var rect = sec.getBoundingClientRect();
            var y = window.scrollY + rect.top - getScrollOffset();
            window.scrollTo({ top: Math.max(0, y), behavior: 'smooth' });
            flashSection(sec);
        }

        bar.addEventListener('click', function (e) {
            var btn = e.target.closest('.ensor-reader-aud__chip');
            if (!btn) return;
            e.preventDefault();
            var aud = btn.dataset.aud;
            var chips = bar.querySelectorAll('.ensor-reader-aud__chip');
            chips.forEach(function (c) { c.classList.toggle('is-active', c === btn); });
            jumpTo(aud);
        });

        // Mientras el usuario scrollea, marcamos como activo el chip de la sección visible
        if ('IntersectionObserver' in window) {
            var ioMap = {};
            sections.forEach(function (sec) {
                var firstAud = (sec.getAttribute('data-aud') || '').split(/\s+/)[0];
                if (firstAud) ioMap[firstAud] = sec;
            });
            var visibleAud = null;
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        var aud = (entry.target.getAttribute('data-aud') || '').split(/\s+/)[0];
                        if (!aud || aud === visibleAud) return;
                        visibleAud = aud;
                        var chips = bar.querySelectorAll('.ensor-reader-aud__chip');
                        chips.forEach(function (c) {
                            c.classList.toggle('is-active', c.dataset.aud === aud);
                        });
                    }
                });
            }, { rootMargin: '-30% 0px -55% 0px', threshold: 0.01 });
            sections.forEach(function (sec) { io.observe(sec); });
        }
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
        syncSiteHeaderOffset();
        var headings = ensureHeadingIds();
        var quizEl = ensureQuizSectionId();
        buildTOC(headings, quizEl);
        watchHeadings(headings, quizEl);
        initAudienceChips();
        initMobileSheet();
        initAiPrompts();
        updateProgress();
        function onScrollResize() {
            scheduleSyncSiteHeaderOffset();
            updateProgress();
            applyTopicChipVisibility();
        }
        window.addEventListener('scroll', onScrollResize, { passive: true });
        window.addEventListener('resize', onScrollResize, { passive: true });
        window.addEventListener('load', function () {
            scheduleSyncSiteHeaderOffset();
            updateProgress();
            applyTopicChipVisibility();
        });
        requestAnimationFrame(function () {
            scheduleSyncSiteHeaderOffset();
            updateProgress();
            applyTopicChipVisibility();
        });
    });
})();

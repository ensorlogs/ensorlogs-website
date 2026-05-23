/*!
 * Ensorlogs · Quiz al final de cada log + contador "Logs Completados".
 *
 * Markup esperado (lo emiten Python script y WP):
 *
 *   <section class="ensor-quiz"
 *            data-slug="wordpress-vale-pena-aprender-2026"
 *            data-quiz='{"questions":[ … ]}'>
 *     <!-- vacío, lo monta este script -->
 *   </section>
 *
 * Estado completado: localStorage ensorlogs_completed_logs_v1
 *   { "<slug>": { "completedAt": "<ISO>", "score": N } }
 *
 * Eventos emitidos en document:
 *   - "ensorlogs:quiz-ready"    → tras montar
 *   - "ensorlogs:completed"     → tras marcar un log como completado
 */
(function () {
    'use strict';
    var d = document;
    var STORAGE_KEY = 'ensorlogs_completed_logs_v1';
    var MAX_WRONG_BEFORE_REVEAL = 3;

    function pageLang() {
        var path = (d.location && d.location.pathname) || '';
        if (/\/en(\/|$)/.test(path)) {
            return 'en';
        }
        var meta = d.querySelector('meta[name="ensor-lang"]');
        if (meta && meta.getAttribute('content') === 'en') {
            return 'en';
        }
        return 'es';
    }

    var STR = {
        es: {
            counterText: 'Logs Completados',
            counterAria: 'Logs completados: ',
            logDone: 'Log completado',
            logPending: 'Pendiente · quiz al final',
            cardDone: 'Completado',
            cardPending: 'Pendiente',
            verify: 'Verificar respuesta',
            retry: 'Volver a intentar',
            quizEyebrow: 'QUIZ.LOG · COMPRENSIÓN',
            quizTitle: 'Pon a prueba lo que aprendiste',
            quizDesc: 'Responde estas preguntas para confirmar que el log quedó claro. Cuando aciertes todas se desbloquea el botón para marcar el log como leído.',
            progress: ' de ',
            progressSuffix: ' aciertos',
            completeLabel: 'He leído y comprendido el log',
            verifyOk: '✓ Respuesta correcta',
            verifyBad: '✗ No es esa',
            feedbackOk: 'Correcto, ese punto quedó claro.',
            feedbackHintPrefix: 'No es esa. Pista: ',
            feedbackBad: 'No es esa. Repasa el log y vuelve a intentarlo.',
            feedbackRevealPrefix: 'Tras tres intentos, la respuesta correcta es la que ves marcada. ',
            feedbackReveal: 'Tras tres intentos, la respuesta correcta queda marcada. Repasa el log con calma.',
            doneBtn: 'Completado'
        },
        en: {
            counterText: 'Completed Logs',
            counterAria: 'Completed logs: ',
            logDone: 'Log completed',
            logPending: 'Pending · quiz at the end',
            cardDone: 'Completed',
            cardPending: 'Pending',
            verify: 'Check answer',
            retry: 'Try again',
            quizEyebrow: 'QUIZ.LOG · CHECK',
            quizTitle: 'Test what you learned',
            quizDesc: 'Answer these questions to confirm the log stuck. When you get them all right, you can mark the log as read.',
            progress: ' of ',
            progressSuffix: ' correct',
            completeLabel: 'I have read and understood this log',
            verifyOk: '✓ Correct answer',
            verifyBad: '✗ Not that one',
            feedbackOk: 'Correct — that point is clear.',
            feedbackHintPrefix: 'Not that one. Hint: ',
            feedbackBad: 'Not that one. Re-read the log and try again.',
            feedbackRevealPrefix: 'After three tries, the correct option is highlighted. ',
            feedbackReveal: 'After three tries, the correct option is highlighted. Re-read the log carefully.',
            doneBtn: 'Completed'
        }
    };

    function L() {
        return STR[pageLang()] || STR.es;
    }

    /* --------------------------------------------------------------- helpers */
    function loadCompleted() {
        try {
            var raw = localStorage.getItem(STORAGE_KEY);
            var data = raw ? JSON.parse(raw) : {};
            return (data && typeof data === 'object') ? data : {};
        } catch (e) { return {}; }
    }
    function saveCompleted(map) {
        try { localStorage.setItem(STORAGE_KEY, JSON.stringify(map)); } catch (e) {}
    }
    function countCompleted() {
        var map = loadCompleted();
        return Object.keys(map).length;
    }
    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }
    function shuffleStable(arr) {
        return arr.slice();
    }
    function ready(fn) {
        if (d.readyState !== 'loading') fn();
        else d.addEventListener('DOMContentLoaded', fn);
    }

    /* ------------------------------------------------------ contador header */
    function findBrandHosts() {
        // Cada nodo .ensor-wordmark está dentro de un contenedor de marca;
        // Anclas del logo en header (no dentro del <a> para evitar enlaces anidados).
        return Array.prototype.slice.call(
            d.querySelectorAll('header .nav > a.inline-flex, .mobile_menu > a.inline-flex')
        ).filter(function (logoLink) {
            return logoLink.querySelector('.ensor-wordmark');
        });
    }
    function findBrandColumn(logoLink) {
        return logoLink.querySelector('span.flex.flex-col, span.flex-col');
    }

    function counterBlogHref() {
        try {
            var parts = (location.pathname || '/').split('/').filter(Boolean);
            if (parts.length >= 2 && parts[parts.length - 2] === 'articulos') return '../blog.html';
            var meta = d.querySelector('meta[name="ensor-blog-url"]');
            if (meta && meta.content) return meta.content;
        } catch (e) {}
        return 'blog.html';
    }

    function buildCounterEl() {
        var a = d.createElement('a');
        a.className = 'ensor-completed-counter';
        a.href = counterBlogHref();
        a.setAttribute('aria-label', L().counterAria + '0');
        a.innerHTML =
            '<span class="ensor-completed-counter__text">' + L().counterText + '</span>' +
            '<span class="ensor-completed-counter__num" aria-hidden="true">0</span>';
        return a;
    }

    /** Quita contadores dentro de la columna marca (wordmark → tagline, sin contador en medio). */
    function stripCounterFromBrandColumn(logoLink) {
        var col = findBrandColumn(logoLink);
        if (!col) return;
        var inside = col.querySelector(':scope > .ensor-completed-counter');
        if (inside) inside.remove();
    }

    function stripStaleHeaderCounter(logoLink) {
        var host = logoLink.parentNode;
        if (!host) return;
        var stale = logoLink.nextElementSibling;
        if (stale && stale.classList && stale.classList.contains('ensor-completed-counter')) {
            stale.remove();
        }
        var nav = logoLink.closest('.nav');
        if (nav) {
            var inNav = nav.querySelector(':scope > .ensor-completed-counter');
            if (inNav) inNav.remove();
        }
    }

    /** Contador solo en el listado del menú lateral (no junto al wordmark). */
    function ensureMobileNavCounter(mobileRoot) {
        var nav = mobileRoot.querySelector('.my-12');
        if (!nav) return null;
        var existing = nav.querySelector(':scope > .ensor-completed-counter');
        if (existing) return existing;
        var a = buildCounterEl();
        a.classList.add('ensor-completed-counter--drawer');
        nav.insertBefore(a, nav.firstChild);
        return a;
    }

    function ensureCounter(logoLink) {
        stripCounterFromBrandColumn(logoLink);
        stripStaleHeaderCounter(logoLink);

        var mobileRoot = logoLink.closest('.mobile_menu');
        if (mobileRoot) {
            return ensureMobileNavCounter(mobileRoot);
        }
        return null;
    }
    function refreshCounters() {
        var n = countCompleted();
        var counters = Array.prototype.slice.call(d.querySelectorAll('.ensor-completed-counter'));
        counters.forEach(function (c) {
            var num = c.querySelector('.ensor-completed-counter__num');
            if (num) num.textContent = String(n);
            c.setAttribute('aria-label', L().counterAria + String(n));
            c.classList.toggle('is-empty', n === 0);
        });
        // Cuando cambian los completados, también refrescamos las cards del listado.
        try { refreshLogCards(); } catch (e) {}
    }
    function initHeaderCounter() {
        var hosts = findBrandHosts();
        var mobileDone = false;
        hosts.forEach(function (logoLink) {
            if (logoLink.closest('.mobile_menu')) {
                if (!mobileDone) {
                    ensureCounter(logoLink);
                    mobileDone = true;
                } else {
                    stripCounterFromBrandColumn(logoLink);
                }
            } else {
                stripCounterFromBrandColumn(logoLink);
                stripStaleHeaderCounter(logoLink);
            }
        });
        var drawer = d.querySelector('.mobile_menu');
        if (drawer && !drawer.querySelector('.my-12 .ensor-completed-counter')) {
            ensureMobileNavCounter(drawer);
        }
        refreshCounters();
    }

    /* -------------------------------------------------------- badge estado */
    function applyLogStatusBadge(slug, done) {
        var badges = Array.prototype.slice.call(d.querySelectorAll('.ensor-log-status[data-slug="' + slug + '"]'));
        badges.forEach(function (b) {
            b.classList.toggle('is-done', !!done);
            var label = b.querySelector('.ensor-log-status__label');
            if (label) label.textContent = done ? L().logDone : L().logPending;
        });
    }

    /* ----------------------------------------------- cards del listado blog */
    function slugFromHref(href) {
        if (!href) return '';
        // Soporta rutas estáticas (articulos/xxx.html) y WP (/blog/xxx/).
        var staticMatch = href.match(/articulos\/([^./?#]+)\.html?/i);
        if (staticMatch) return staticMatch[1];
        var wpMatch = href.match(/\/(?:blog|logs|articulos?)\/([^/?#]+)\/?$/i);
        if (wpMatch) return wpMatch[1];
        return '';
    }

    function findCardSlug(card) {
        if (card.dataset && card.dataset.slug) return card.dataset.slug;
        var links = card.querySelectorAll('a[href]');
        for (var i = 0; i < links.length; i++) {
            var s = slugFromHref(links[i].getAttribute('href'));
            if (s) return s;
        }
        return '';
    }

    function ensureCardBadge(card, done) {
        // Busca o crea el badge dentro del thumbnail (esquina superior izquierda).
        var thumb = card.querySelector('.thumbnail') || card.firstElementChild || card;
        var badge = card.querySelector('.ensor-log-card-status');
        if (!badge) {
            badge = d.createElement('span');
            badge.className = 'ensor-log-card-status';
            badge.setAttribute('aria-hidden', 'false');
            var icon  = d.createElement('span');
            icon.className = 'ensor-log-card-status__icon';
            icon.setAttribute('aria-hidden', 'true');
            var label = d.createElement('span');
            label.className = 'ensor-log-card-status__label';
            badge.appendChild(icon);
            badge.appendChild(label);
            thumb.appendChild(badge);
        }
        badge.classList.toggle('is-done', !!done);
        badge.classList.toggle('is-pending', !done);
        var lbl = badge.querySelector('.ensor-log-card-status__label');
        if (lbl) lbl.textContent = done ? L().cardDone : L().cardPending;
    }

    function refreshLogCards() {
        var completed = loadCompleted();
        var cards = Array.prototype.slice.call(d.querySelectorAll(
            '.blog-item, .ensor-log-card, [data-log-card]'
        ));
        cards.forEach(function (card) {
            var slug = findCardSlug(card);
            if (!slug) return;
            card.setAttribute('data-slug', slug);
            var done = !!completed[slug];
            card.classList.toggle('is-log-done', done);
            card.classList.toggle('is-log-pending', !done);
            ensureCardBadge(card, done);
        });
    }

    /* ----------------------------------------------------- montaje del quiz */
    function buildQuestion(q, idx, total) {
        var li = d.createElement('div');
        li.className = 'ensor-quiz__question';
        li.setAttribute('data-q-idx', String(idx));
        li.setAttribute('data-state', 'idle');

        var qTitle = d.createElement('p');
        qTitle.className = 'ensor-quiz__q';
        qTitle.innerHTML = '<span class="ensor-quiz__qnum">' + (idx + 1) + '/' + total + '</span> · ' + escapeHtml(q.q);
        li.appendChild(qTitle);

        var ol = d.createElement('ol');
        ol.className = 'ensor-quiz__options';
        (q.options || []).forEach(function (opt, oi) {
            var li2 = d.createElement('li');
            var lbl = d.createElement('label');
            lbl.className = 'ensor-quiz__option';
            lbl.innerHTML =
                '<input type="radio" name="q' + idx + '" value="' + oi + '" aria-describedby="ensor-quiz-q' + idx + '-fb">' +
                '<span>' + escapeHtml(opt) + '</span>';
            li2.appendChild(lbl);
            ol.appendChild(li2);
        });
        li.appendChild(ol);

        var actions = d.createElement('div');
        actions.className = 'ensor-quiz__actions';
        var verify = d.createElement('button');
        verify.type = 'button';
        verify.className = 'ensor-quiz__verify';
        verify.textContent = L().verify;
        verify.disabled = true;
        actions.appendChild(verify);

        var retry = d.createElement('button');
        retry.type = 'button';
        retry.className = 'ensor-quiz__retry';
        retry.textContent = L().retry;
        retry.style.display = 'none';
        actions.appendChild(retry);

        li.appendChild(actions);

        var fb = d.createElement('p');
        fb.className = 'ensor-quiz__feedback';
        fb.id = 'ensor-quiz-q' + idx + '-fb';
        li.appendChild(fb);

        return { el: li, verifyBtn: verify, retryBtn: retry, feedback: fb, wrongAttempts: 0 };
    }

    function mountQuiz(section) {
        var raw = section.getAttribute('data-quiz');
        if (!raw) return;
        var data;
        try { data = JSON.parse(raw); } catch (e) { return; }
        var questions = (data && Array.isArray(data.questions)) ? data.questions : [];
        if (!questions.length) return;
        var slug = section.getAttribute('data-slug') || '';

        section.innerHTML = '';

        var intro = d.createElement('div');
        intro.className = 'ensor-quiz__intro';
        intro.innerHTML =
            '<div>' +
                '<p class="ensor-quiz__eyebrow">' + L().quizEyebrow + '</p>' +
                '<h2 class="ensor-quiz__title">' + L().quizTitle + '</h2>' +
                '<p class="ensor-quiz__desc">' + L().quizDesc + '</p>' +
            '</div>';
        section.appendChild(intro);

        var qStates = new Array(questions.length).fill('idle');
        var built = questions.map(function (q, i) { return buildQuestion(q, i, questions.length); });
        built.forEach(function (b) { section.appendChild(b.el); });

        var summary = d.createElement('div');
        summary.className = 'ensor-quiz__summary';
        summary.innerHTML =
            '<div>' +
                '<div class="ensor-quiz__progress js-progress">0' + L().progress + questions.length + L().progressSuffix + '</div>' +
                '<div class="ensor-quiz__progress-bar" aria-hidden="true"><div class="ensor-quiz__progress-fill js-progress-fill"></div></div>' +
            '</div>' +
            '<button type="button" class="ensor-quiz__complete" disabled aria-disabled="true">' +
                '<span class="ensor-quiz__complete-label">' + L().completeLabel + '</span>' +
            '</button>';
        section.appendChild(summary);

        var completeBtn = summary.querySelector('.ensor-quiz__complete');
        var progressEl  = summary.querySelector('.js-progress');
        var fillEl      = summary.querySelector('.js-progress-fill');

        function updateProgress() {
            var rights = qStates.filter(function (s) { return s === 'right'; }).length;
            progressEl.textContent = rights + L().progress + questions.length + L().progressSuffix;
            fillEl.style.width = Math.round((rights / questions.length) * 100) + '%';
            if (rights === questions.length) {
                completeBtn.classList.add('is-unlocked');
                completeBtn.disabled = false;
                completeBtn.removeAttribute('aria-disabled');
                completeBtn.querySelector('.ensor-quiz__complete-label').textContent = L().completeLabel;
            } else {
                completeBtn.classList.remove('is-unlocked');
                completeBtn.disabled = true;
                completeBtn.setAttribute('aria-disabled', 'true');
            }
        }

        built.forEach(function (b, i) {
            var radios = b.el.querySelectorAll('input[type="radio"]');
            radios.forEach(function (r) {
                r.addEventListener('change', function () {
                    if (b.el.getAttribute('data-state') !== 'right') {
                        b.verifyBtn.disabled = false;
                    }
                });
            });
            b.verifyBtn.addEventListener('click', function () {
                var selected = b.el.querySelector('input[type="radio"]:checked');
                if (!selected) return;
                var picked = Number(selected.value);
                var correct = Number(questions[i].correct);
                var options = b.el.querySelectorAll('.ensor-quiz__option');
                options.forEach(function (op) { op.classList.remove('is-correct', 'is-wrong'); });

                if (picked === correct) {
                    b.el.setAttribute('data-state', 'right');
                    qStates[i] = 'right';
                    options[picked].classList.add('is-correct');
                    b.verifyBtn.textContent = L().verifyOk;
                    b.retryBtn.style.display = 'none';
                    b.feedback.textContent = questions[i].explanation || L().feedbackOk;
                    // Bloquea radios
                    radios.forEach(function (r) { r.disabled = true; });
                } else {
                    b.wrongAttempts += 1;
                    options[picked].classList.add('is-wrong');
                    b.verifyBtn.textContent = L().verifyBad;
                    b.retryBtn.style.display = '';
                    qStates[i] = 'wrong';

                    if (b.wrongAttempts >= MAX_WRONG_BEFORE_REVEAL) {
                        b.el.setAttribute('data-state', 'revealed');
                        options[correct].classList.add('is-correct');
                        var revealText = (questions[i].explanation || '').trim();
                        b.feedback.textContent = revealText
                            ? L().feedbackRevealPrefix + revealText
                            : L().feedbackReveal;
                    } else {
                        b.el.setAttribute('data-state', 'wrong');
                        var hintText = (questions[i].hint || '').trim();
                        b.feedback.textContent = hintText
                            ? L().feedbackHintPrefix + hintText
                            : L().feedbackBad;
                    }
                }
                updateProgress();
            });
            b.retryBtn.addEventListener('click', function () {
                if (b.el.getAttribute('data-state') === 'revealed') {
                    b.wrongAttempts = 0;
                }
                b.el.setAttribute('data-state', 'idle');
                qStates[i] = 'idle';
                b.verifyBtn.textContent = L().verify;
                b.verifyBtn.disabled = true;
                b.retryBtn.style.display = 'none';
                var radiosRetry = b.el.querySelectorAll('input[type="radio"]');
                radiosRetry.forEach(function (r) { r.disabled = false; r.checked = false; });
                var options = b.el.querySelectorAll('.ensor-quiz__option');
                options.forEach(function (op) { op.classList.remove('is-correct', 'is-wrong'); });
                b.feedback.textContent = '';
                updateProgress();
            });
        });

        completeBtn.addEventListener('click', function () {
            if (completeBtn.disabled || completeBtn.classList.contains('is-done')) return;
            var map = loadCompleted();
            map[slug] = {
                completedAt: new Date().toISOString(),
                score: questions.length
            };
            saveCompleted(map);
            markCompleteUI();
            try {
                d.dispatchEvent(new CustomEvent('ensorlogs:completed', {
                    detail: { slug: slug, total: questions.length }
                }));
            } catch (e) {}
        });

        function markCompleteUI() {
            completeBtn.classList.add('is-done');
            completeBtn.classList.remove('is-unlocked');
            completeBtn.disabled = true;
            completeBtn.querySelector('.ensor-quiz__complete-label').textContent = L().doneBtn;
            applyLogStatusBadge(slug, true);
            refreshCounters();
        }

        // Si el log ya está completado: marcarlo y permitir reintentar igual.
        var completedMap = loadCompleted();
        if (slug && completedMap[slug]) {
            // Auto-marca todas las preguntas como correctas (visualmente) y bloquea.
            built.forEach(function (b, i) {
                var correct = Number(questions[i].correct);
                var radios = b.el.querySelectorAll('input[type="radio"]');
                if (radios[correct]) radios[correct].checked = true;
                var options = b.el.querySelectorAll('.ensor-quiz__option');
                options[correct] && options[correct].classList.add('is-correct');
                b.el.setAttribute('data-state', 'right');
                b.verifyBtn.textContent = '✓ Respuesta correcta';
                b.verifyBtn.disabled = true;
                b.feedback.textContent = questions[i].explanation || 'Quedó claro la primera vez.';
                radios.forEach(function (r) { r.disabled = true; });
                qStates[i] = 'right';
            });
            updateProgress();
            markCompleteUI();
        } else {
            updateProgress();
            applyLogStatusBadge(slug, false);
        }
    }

    /* --------------------------------------------------------------- init */
    ready(function () {
        initHeaderCounter();
        refreshLogCards();
        Array.prototype.slice.call(d.querySelectorAll('.ensor-quiz[data-quiz]')).forEach(mountQuiz);

        // Cuando alguien complete un log, también refresca contadores en
        // cualquier otra pestaña/instancia.
        window.addEventListener('storage', function (e) {
            if (e.key === STORAGE_KEY) refreshCounters();
        });
        d.addEventListener('ensorlogs:completed', refreshCounters);

        try {
            d.dispatchEvent(new CustomEvent('ensorlogs:quiz-ready'));
        } catch (e) {}
    });

    /* Mini API para depuración */
    window.EnsorLogsCompleted = {
        list: loadCompleted,
        clear: function () { try { localStorage.removeItem(STORAGE_KEY); refreshCounters(); } catch (e) {} },
        count: countCompleted
    };
})();

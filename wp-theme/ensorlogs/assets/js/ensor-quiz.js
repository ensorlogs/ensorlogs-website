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
 * Estado completado: localStorage `ensorlogs_completed_logs_v1`
 *   { "<slug>": { "completedAt": "<ISO>", "score": N } }
 *
 * Eventos emitidos en `document`:
 *   - "ensorlogs:quiz-ready"    → tras montar
 *   - "ensorlogs:completed"     → tras marcar un log como completado
 */
(function () {
    'use strict';
    var d = document;
    var STORAGE_KEY = 'ensorlogs_completed_logs_v1';

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
        // Cada nodo `.ensor-wordmark` está dentro de un contenedor de marca;
        // pegamos el contador justo después del rule del tagline.
        var nodes = Array.prototype.slice.call(d.querySelectorAll('.ensor-wordmark'));
        return nodes.map(function (n) {
            return n.parentNode || null;
        }).filter(Boolean);
    }
    function ensureCounter(host) {
        var existing = host.querySelector('.ensor-completed-counter');
        if (existing) return existing;
        var blogHref = (function () {
            // Si estamos en /articulos/ vamos a ../blog.html, si no, blog.html
            try {
                var parts = (location.pathname || '/').split('/').filter(Boolean);
                if (parts.length >= 2 && parts[parts.length - 2] === 'articulos') return '../blog.html';
                // En WP: la página de blog suele estar en /blog/
                var meta = d.querySelector('meta[name="ensor-blog-url"]');
                if (meta && meta.content) return meta.content;
            } catch (e) {}
            return 'blog.html';
        })();
        var a = d.createElement('a');
        a.className = 'ensor-completed-counter';
        a.href = blogHref;
        a.setAttribute('aria-label', 'Logs completados');
        a.innerHTML =
            '<span class="ensor-completed-counter__text">Logs Completados</span>' +
            '<span class="ensor-completed-counter__num">0</span>';
        // Inserta justo después de la regla del tagline (si existe).
        var anchor = host.querySelector('.ensor-tagline-rule')
                  || host.querySelector('.ensor-wordmark');
        if (anchor && anchor.parentNode) anchor.parentNode.insertBefore(a, anchor.nextSibling);
        else host.appendChild(a);
        return a;
    }
    function refreshCounters() {
        var n = countCompleted();
        var counters = Array.prototype.slice.call(d.querySelectorAll('.ensor-completed-counter'));
        counters.forEach(function (c) {
            var num = c.querySelector('.ensor-completed-counter__num');
            if (num) num.textContent = String(n);
            c.classList.toggle('is-empty', n === 0);
        });
        // Cuando cambian los completados, también refrescamos las cards del listado.
        try { refreshLogCards(); } catch (e) {}
    }
    function initHeaderCounter() {
        var hosts = findBrandHosts();
        hosts.forEach(ensureCounter);
        refreshCounters();
    }

    /* -------------------------------------------------------- badge estado */
    function applyLogStatusBadge(slug, done) {
        var badges = Array.prototype.slice.call(d.querySelectorAll('.ensor-log-status[data-slug="' + slug + '"]'));
        badges.forEach(function (b) {
            b.classList.toggle('is-done', !!done);
            var label = b.querySelector('.ensor-log-status__label');
            if (label) label.textContent = done ? 'Log completado' : 'Pendiente · quiz al final';
        });
    }

    /* ----------------------------------------------- cards del listado blog */
    function slugFromHref(href) {
        if (!href) return '';
        // Soporta rutas estáticas (`articulos/xxx.html`) y WP (`/blog/xxx/`).
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
        if (lbl) lbl.textContent = done ? 'Completado' : 'Pendiente';
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
        verify.textContent = 'Verificar respuesta';
        verify.disabled = true;
        actions.appendChild(verify);

        var retry = d.createElement('button');
        retry.type = 'button';
        retry.className = 'ensor-quiz__retry';
        retry.textContent = 'Volver a intentar';
        retry.style.display = 'none';
        actions.appendChild(retry);

        li.appendChild(actions);

        var fb = d.createElement('p');
        fb.className = 'ensor-quiz__feedback';
        fb.id = 'ensor-quiz-q' + idx + '-fb';
        li.appendChild(fb);

        return { el: li, verifyBtn: verify, retryBtn: retry, feedback: fb };
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
                '<p class="ensor-quiz__eyebrow">QUIZ.LOG · COMPRENSIÓN</p>' +
                '<h2 class="ensor-quiz__title">Pon a prueba lo que aprendiste</h2>' +
                '<p class="ensor-quiz__desc">Responde estas preguntas para confirmar que el log quedó claro. Cuando aciertes todas se desbloquea el botón para marcar el log como leído.</p>' +
            '</div>';
        section.appendChild(intro);

        var qStates = new Array(questions.length).fill('idle');
        var built = questions.map(function (q, i) { return buildQuestion(q, i, questions.length); });
        built.forEach(function (b) { section.appendChild(b.el); });

        var summary = d.createElement('div');
        summary.className = 'ensor-quiz__summary';
        summary.innerHTML =
            '<div>' +
                '<div class="ensor-quiz__progress js-progress">0 de ' + questions.length + ' aciertos</div>' +
                '<div class="ensor-quiz__progress-bar" aria-hidden="true"><div class="ensor-quiz__progress-fill js-progress-fill"></div></div>' +
            '</div>' +
            '<button type="button" class="ensor-quiz__complete" disabled aria-disabled="true">' +
                '<span class="ensor-quiz__complete-label">He leído y comprendido el log</span>' +
            '</button>';
        section.appendChild(summary);

        var completeBtn = summary.querySelector('.ensor-quiz__complete');
        var progressEl  = summary.querySelector('.js-progress');
        var fillEl      = summary.querySelector('.js-progress-fill');

        function updateProgress() {
            var rights = qStates.filter(function (s) { return s === 'right'; }).length;
            progressEl.textContent = rights + ' de ' + questions.length + ' aciertos';
            fillEl.style.width = Math.round((rights / questions.length) * 100) + '%';
            if (rights === questions.length) {
                completeBtn.classList.add('is-unlocked');
                completeBtn.disabled = false;
                completeBtn.removeAttribute('aria-disabled');
                completeBtn.querySelector('.ensor-quiz__complete-label').textContent = 'He leído y comprendido el log';
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
                    b.verifyBtn.textContent = '✓ Respuesta correcta';
                    b.retryBtn.style.display = 'none';
                    b.feedback.textContent = questions[i].explanation || 'Correcto, ese punto quedó claro.';
                    // Bloquea radios
                    radios.forEach(function (r) { r.disabled = true; });
                } else {
                    b.el.setAttribute('data-state', 'wrong');
                    qStates[i] = 'wrong';
                    options[picked].classList.add('is-wrong');
                    options[correct].classList.add('is-correct');
                    b.verifyBtn.textContent = '✗ No es esa';
                    b.retryBtn.style.display = '';
                    b.feedback.textContent = questions[i].explanation
                        ? 'Aún no. ' + questions[i].explanation
                        : 'No es esa. Repasa el log y vuelve a intentarlo.';
                }
                updateProgress();
            });
            b.retryBtn.addEventListener('click', function () {
                b.el.setAttribute('data-state', 'idle');
                qStates[i] = 'idle';
                b.verifyBtn.textContent = 'Verificar respuesta';
                b.verifyBtn.disabled = true;
                b.retryBtn.style.display = 'none';
                var radios = b.el.querySelectorAll('input[type="radio"]');
                radios.forEach(function (r) { r.disabled = false; r.checked = false; });
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
            completeBtn.querySelector('.ensor-quiz__complete-label').textContent = 'Completado';
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

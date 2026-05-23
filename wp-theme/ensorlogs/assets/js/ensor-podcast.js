/*!
 * Ensorlogs · Podcast del log ("Comentarios del autor")
 *
 * Convierte una card .ensor-podcast-card[data-audio] en un mini reproductor
 * sticky con: play/pause, ±15s, velocidad, barra de progreso, lista de
 * capítulos clicables.
 *
 * El HTML mínimo esperado (lo emiten tanto el script Python como el theme):
 *
 *   <div class="ensor-podcast-card"
 *        data-audio="/ruta/al/audio.mp3"
 *        data-title="Comentario del autor sobre [log]"
 *        data-chapters='[{"time":0,"title":"Intro"},{"time":80,"title":"Contexto"}]'>
 *     <button class="ensor-podcast-card__play" type="button">…</button>
 *     <div class="ensor-podcast-card__meta">…</div>
 *   </div>
 *
 * El mini reproductor se inyecta una vez al hacer click; se queda en memoria
 * para reanudar la reproducción donde se quedó si se cierra y se vuelve a abrir.
 */
(function () {
    'use strict';
    var d = document;

    function ready(fn) {
        if (d.readyState !== 'loading') fn();
        else d.addEventListener('DOMContentLoaded', fn);
    }

    var SPEEDS = [1, 1.25, 1.5, 2, 0.75];

    function formatTime(s) {
        s = Math.max(0, Math.floor(s || 0));
        var m = Math.floor(s / 60);
        var r = s - m * 60;
        return m + ':' + (r < 10 ? '0' : '') + r;
    }

    function parseChapters(raw) {
        if (!raw) return [];
        try {
            var arr = JSON.parse(raw);
            if (!Array.isArray(arr)) return [];
            return arr
                .map(function (c) { return { time: Number(c.time) || 0, title: String(c.title || '') }; })
                .filter(function (c) { return c.title; })
                .sort(function (a, b) { return a.time - b.time; });
        } catch (e) {
            return [];
        }
    }

    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function createPlayer(card) {
        var src = card.getAttribute('data-audio');
        if (!src) return null;
        var title = card.getAttribute('data-title') || 'Comentario del autor';
        var chapters = parseChapters(card.getAttribute('data-chapters'));
        var speedIdx = 0;

        // Marca el body para que se reserve espacio inferior
        d.body.classList.add('ensor-reader-has-podcast');

        var mini = d.createElement('div');
        mini.className = 'ensor-podcast-mini';
        mini.setAttribute('role', 'region');
        mini.setAttribute('aria-label', 'Reproductor de audio del log');
        mini.innerHTML = [
            '<div class="ensor-podcast-mini__inner">',
                '<button class="ensor-podcast-mini__btn" type="button" data-action="back15" aria-label="Atrasar 15 segundos">',
                    '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5V2L7 6l5 4V7a5 5 0 1 1-5 5H5a7 7 0 1 0 7-7z"/><text x="12" y="16" text-anchor="middle" font-size="6" font-weight="700" fill="currentColor">15</text></svg>',
                '</button>',
                '<button class="ensor-podcast-mini__btn" type="button" data-action="toggle" aria-label="Reproducir / pausar">',
                    '<svg viewBox="0 0 24 24" aria-hidden="true" class="js-icon-play"><path d="M8 5v14l11-7z"/></svg>',
                    '<svg viewBox="0 0 24 24" aria-hidden="true" class="js-icon-pause" style="display:none"><path d="M6 5h4v14H6zM14 5h4v14h-4z"/></svg>',
                '</button>',
                '<button class="ensor-podcast-mini__btn" type="button" data-action="fwd15" aria-label="Adelantar 15 segundos">',
                    '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5V2l5 4-5 4V7a5 5 0 1 0 5 5h2a7 7 0 1 1-7-7z"/><text x="12" y="16" text-anchor="middle" font-size="6" font-weight="700" fill="currentColor">15</text></svg>',
                '</button>',

                '<div class="ensor-podcast-mini__progress-wrap">',
                    '<div class="ensor-podcast-mini__topline">',
                        '<span class="ensor-podcast-mini__chapter">' + escapeHtml(title) + '</span>',
                        '<span class="ensor-podcast-mini__time"><span class="js-cur">0:00</span> / <span class="js-dur">' + escapeHtml(card.getAttribute('data-duration') || '--:--') + '</span></span>',
                    '</div>',
                    '<input type="range" class="ensor-podcast-mini__progress" min="0" max="0" value="0" step="0.1" aria-label="Posición del audio">',
                '</div>',

                '<div class="ensor-podcast-mini__right">',
                    chapters.length
                        ? '<button class="ensor-podcast-mini__expand" type="button" data-action="expand" aria-expanded="false">Capítulos <svg class="ensor-podcast-mini__expand-icon" width="10" height="10" viewBox="0 0 12 12" aria-hidden="true"><path d="M2 4l4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></button>'
                        : '',
                    '<button class="ensor-podcast-mini__speed" type="button" data-action="speed" aria-label="Velocidad">' + SPEEDS[0] + 'x</button>',
                    '<button class="ensor-podcast-mini__close" type="button" data-action="close" aria-label="Cerrar reproductor"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>',
                '</div>',
            '</div>',
            chapters.length
                ? '<div class="ensor-podcast-mini__chapters" hidden>' +
                      '<p class="ensor-podcast-mini__chapters-title">Capítulos</p>' +
                      '<ul class="ensor-podcast-mini__chapter-list">' +
                          chapters.map(function (c, i) {
                              return '<li>' +
                                  '<button class="ensor-podcast-mini__chapter-item" type="button" data-time="' + c.time + '" data-idx="' + i + '">' +
                                      '<span class="ensor-podcast-mini__chapter-time">' + formatTime(c.time) + '</span>' +
                                      '<span class="ensor-podcast-mini__chapter-name">' + escapeHtml(c.title) + '</span>' +
                                  '</button>' +
                              '</li>';
                          }).join('') +
                      '</ul>' +
                  '</div>'
                : '',
        ].join('');

        var audio = d.createElement('audio');
        audio.preload = 'metadata';
        audio.src = src;
        audio.setAttribute('playsinline', '');
        mini.appendChild(audio);
        d.body.appendChild(mini);

        var iconPlay  = mini.querySelector('.js-icon-play');
        var iconPause = mini.querySelector('.js-icon-pause');
        var elCur     = mini.querySelector('.js-cur');
        var elDur     = mini.querySelector('.js-dur');
        var elProg    = mini.querySelector('.ensor-podcast-mini__progress');
        var elChapter = mini.querySelector('.ensor-podcast-mini__chapter');
        var btnSpeed  = mini.querySelector('[data-action="speed"]');
        var chaptersWrap = mini.querySelector('.ensor-podcast-mini__chapters');
        var expandBtn = mini.querySelector('[data-action="expand"]');

        function setPlayingUI(playing) {
            iconPlay.style.display  = playing ? 'none' : '';
            iconPause.style.display = playing ? '' : 'none';
            card.classList.toggle('is-playing', playing);
            card.querySelector('.ensor-podcast-card__play')
                .setAttribute('aria-label', playing ? 'Pausar comentario del autor' : 'Reproducir comentario del autor');
        }

        function currentChapter() {
            if (!chapters.length) return null;
            var t = audio.currentTime || 0;
            var current = chapters[0];
            for (var i = 0; i < chapters.length; i++) {
                if (chapters[i].time <= t) current = chapters[i];
                else break;
            }
            return current;
        }

        function updateChapterUI() {
            var c = currentChapter();
            if (!c) {
                elChapter.textContent = title;
                return;
            }
            elChapter.textContent = title + ' · ' + c.title;
            if (!chaptersWrap) return;
            var items = chaptersWrap.querySelectorAll('.ensor-podcast-mini__chapter-item');
            items.forEach(function (it) {
                it.classList.toggle('is-current', Number(it.getAttribute('data-time')) === c.time);
            });
        }

        audio.addEventListener('loadedmetadata', function () {
            elDur.textContent = formatTime(audio.duration);
            elProg.max = audio.duration || 0;
        });
        audio.addEventListener('timeupdate', function () {
            elCur.textContent = formatTime(audio.currentTime);
            elProg.value = audio.currentTime;
            updateChapterUI();
        });
        audio.addEventListener('ended', function () { setPlayingUI(false); });

        mini.addEventListener('click', function (e) {
            var t = e.target.closest('[data-action], [data-time]');
            if (!t) return;
            var action = t.getAttribute('data-action');
            if (action === 'toggle') {
                if (audio.paused) audio.play(); else audio.pause();
            } else if (action === 'back15') {
                audio.currentTime = Math.max(0, audio.currentTime - 15);
            } else if (action === 'fwd15') {
                audio.currentTime = Math.min(audio.duration || 0, audio.currentTime + 15);
            } else if (action === 'speed') {
                speedIdx = (speedIdx + 1) % SPEEDS.length;
                audio.playbackRate = SPEEDS[speedIdx];
                btnSpeed.textContent = SPEEDS[speedIdx] + 'x';
            } else if (action === 'close') {
                audio.pause();
                mini.classList.remove('is-open');
            } else if (action === 'expand' && chaptersWrap) {
                var open = !mini.classList.contains('is-expanded');
                mini.classList.toggle('is-expanded', open);
                chaptersWrap.hidden = !open;
                expandBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
            } else if (t.hasAttribute('data-time')) {
                var time = Number(t.getAttribute('data-time')) || 0;
                audio.currentTime = time;
                if (audio.paused) audio.play();
            }
        });

        audio.addEventListener('play', function () { setPlayingUI(true); });
        audio.addEventListener('pause', function () { setPlayingUI(false); });

        elProg.addEventListener('input', function () {
            audio.currentTime = Number(elProg.value) || 0;
        });

        function open() {
            mini.classList.add('is-open');
            // Pausa otros reproductores HTML5 que estén sonando.
            Array.prototype.slice.call(d.querySelectorAll('audio,video')).forEach(function (m) {
                if (m !== audio && !m.paused) m.pause();
            });
            audio.play().catch(function () { /* autoplay puede fallar; el botón quedará en pause */ });
        }

        return { mini: mini, audio: audio, open: open };
    }

    ready(function () {
        var cards = Array.prototype.slice.call(d.querySelectorAll('.ensor-podcast-card[data-audio]'));
        if (!cards.length) return;
        cards.forEach(function (card) {
            var instance = null;
            card.addEventListener('click', function (e) {
                var btn = e.target.closest('.ensor-podcast-card__play');
                if (!btn && !card.contains(e.target)) return;
                if (!btn) return;
                e.preventDefault();
                if (!instance) instance = createPlayer(card);
                if (instance) instance.open();
            });
        });
    });
})();

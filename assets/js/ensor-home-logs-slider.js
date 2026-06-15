/**
 * Home: carrusel de logs recientes (imagen + título sincronizados).
 */
(function () {
    'use strict';

    var AUTOPLAY_MS = 6500;

    function prefersReducedMotion() {
        return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    function initSlider(root) {
        if (!root || root.getAttribute('data-ensor-home-logs-slider-ready') === '1') {
            return;
        }

        var mediaTrack = root.querySelector('[data-ensor-home-logs-slider-media]');
        var titleTrack = root.querySelector('[data-ensor-home-logs-slider-titles]');
        var prevBtn = root.querySelector('[data-ensor-home-logs-slider-prev]');
        var nextBtn = root.querySelector('[data-ensor-home-logs-slider-next]');
        if (!mediaTrack || !titleTrack) {
            return;
        }

        var mediaSlides = mediaTrack.querySelectorAll('[data-ensor-home-logs-slider-slide]');
        var titleSlides = titleTrack.querySelectorAll('[data-ensor-home-logs-slider-slide]');
        var count = mediaSlides.length;

        if (count === 0) {
            return;
        }

        root.setAttribute('data-ensor-home-logs-slider-ready', '1');
        root.setAttribute('data-slide-count', String(count));

        var index = 0;
        var timer = null;
        var reduced = prefersReducedMotion();

        function setActive(i) {
            index = (i + count) % count;
            var offset = -index * 100;
            mediaTrack.style.transform = 'translate3d(' + offset + '%,0,0)';
            titleTrack.style.transform = 'translate3d(' + offset + '%,0,0)';
            root.setAttribute('data-active-slide', String(index + 1));

            for (var s = 0; s < count; s++) {
                var active = s === index;
                mediaSlides[s].setAttribute('aria-hidden', active ? 'false' : 'true');
                if (titleSlides[s]) {
                    titleSlides[s].setAttribute('aria-hidden', active ? 'false' : 'true');
                }
            }
        }

        function stopAutoplay() {
            if (timer !== null) {
                window.clearInterval(timer);
                timer = null;
            }
        }

        function startAutoplay() {
            stopAutoplay();
            if (reduced || count <= 1) {
                return;
            }
            timer = window.setInterval(function () {
                setActive(index + 1);
            }, AUTOPLAY_MS);
        }

        function go(delta) {
            setActive(index + delta);
            startAutoplay();
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', function () {
                go(-1);
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', function () {
                go(1);
            });
        }

        root.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowLeft') {
                event.preventDefault();
                go(-1);
            } else if (event.key === 'ArrowRight') {
                event.preventDefault();
                go(1);
            }
        });

        root.addEventListener('mouseenter', stopAutoplay);
        root.addEventListener('mouseleave', startAutoplay);
        root.addEventListener('focusin', stopAutoplay);
        root.addEventListener('focusout', function (event) {
            if (!root.contains(event.relatedTarget)) {
                startAutoplay();
            }
        });

        if (count <= 1) {
            root.classList.add('ensor-home-logs-slider--single');
        }

        setActive(0);
        startAutoplay();
    }

    function boot() {
        var sliders = document.querySelectorAll('[data-ensor-home-logs-slider]');
        for (var i = 0; i < sliders.length; i++) {
            initSlider(sliders[i]);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();

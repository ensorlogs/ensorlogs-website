/**
 * Valoración con estrellas al final de cada log.
 */
(function () {
    'use strict';

    function cfg() {
        return window.ensorLogRating || {};
    }

    function formatStats(average, count) {
        var i18n = cfg().i18n || {};
        var tpl = i18n.stats || 'Promedio de puntuación %s / 5. Total de votos: %d';
        var avg = Number(average).toFixed(1);
        return tpl.replace('%s', avg).replace('%d', String(count));
    }

    function setStars(stars, value, mode) {
        var v = Number(value) || 0;
        stars.forEach(function (btn) {
            var n = Number(btn.getAttribute('data-value')) || 0;
            btn.classList.toggle('is-active', n <= v);
            btn.classList.toggle('is-hover', mode === 'hover' && n <= v);
        });
    }

    function mount(section) {
        var postId = Number(section.getAttribute('data-post-id')) || 0;
        if (!postId) {
            return;
        }

        var stars = Array.prototype.slice.call(section.querySelectorAll('.ensor-log-rating__star'));
        var statsEl = section.querySelector('.ensor-log-rating__stats');
        var emptyEl = section.querySelector('.ensor-log-rating__empty');
        var thanksEl = section.querySelector('.ensor-log-rating__thanks');
        var errorEl = section.querySelector('.ensor-log-rating__error');
        var userRating = Number(section.getAttribute('data-user-rating')) || 0;
        var voted = section.hasAttribute('data-voted') || userRating > 0;
        var busy = false;

        function showError(msg) {
            if (!errorEl) {
                return;
            }
            errorEl.textContent = msg || '';
            errorEl.hidden = !msg;
        }

        function applyStats(average, count) {
            section.setAttribute('data-average', String(average));
            section.setAttribute('data-count', String(count));
            if (statsEl) {
                statsEl.textContent = formatStats(average, count);
                statsEl.hidden = count <= 0;
            }
            if (emptyEl) {
                emptyEl.hidden = count > 0;
            }
        }

        function markVoted(rating) {
            voted = true;
            userRating = rating;
            section.setAttribute('data-voted', '1');
            section.setAttribute('data-user-rating', String(rating));
            setStars(stars, rating, 'selected');
            stars.forEach(function (btn) {
                btn.disabled = true;
            });
            if (thanksEl) {
                thanksEl.hidden = false;
            }
            var hint = section.querySelector('.ensor-log-rating__hint');
            if (hint) {
                hint.hidden = true;
            }
        }

        if (userRating > 0) {
            setStars(stars, userRating, 'selected');
        }

        stars.forEach(function (btn) {
            btn.addEventListener('mouseenter', function () {
                if (voted || busy) {
                    return;
                }
                setStars(stars, btn.getAttribute('data-value'), 'hover');
            });
            btn.addEventListener('focus', function () {
                if (voted || busy) {
                    return;
                }
                setStars(stars, btn.getAttribute('data-value'), 'hover');
            });
            btn.addEventListener('mouseleave', function () {
                if (voted || busy) {
                    return;
                }
                setStars(stars, userRating, 'selected');
                stars.forEach(function (s) {
                    s.classList.remove('is-hover');
                });
            });
            btn.addEventListener('blur', function () {
                if (voted || busy) {
                    return;
                }
                stars.forEach(function (s) {
                    s.classList.remove('is-hover');
                });
            });
            btn.addEventListener('click', function () {
                if (voted || busy) {
                    return;
                }
                var rating = Number(btn.getAttribute('data-value')) || 0;
                if (rating < 1 || rating > 5) {
                    return;
                }
                submitRating(section, postId, rating, function (data) {
                    applyStats(data.average, data.count);
                    markVoted(data.userRating || rating);
                    showError('');
                    try {
                        localStorage.setItem('ensor_log_rating_' + postId, String(rating));
                    } catch (e) {
                        /* ignore */
                    }
                });
            });
        });
    }

    function submitRating(section, postId, rating, onSuccess) {
        var config = cfg();
        var url = config.restUrl || ('/wp-json/ensorlogs/v1/log/' + postId + '/rate');
        var busy = section.querySelector('.ensor-log-rating__stars');
        if (busy) {
            busy.classList.add('is-busy');
        }

        fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': config.nonce || ''
            },
            body: JSON.stringify({ rating: rating })
        })
            .then(function (res) {
                return res.json().then(function (body) {
                    return { ok: res.ok, status: res.status, body: body };
                });
            })
            .then(function (result) {
                if (busy) {
                    busy.classList.remove('is-busy');
                }
                if (result.ok && result.body) {
                    onSuccess(result.body);
                    return;
                }
                var i18n = config.i18n || {};
                var msg = i18n.error;
                if (result.status === 409) {
                    msg = i18n.already || msg;
                    if (result.body && result.body.userRating) {
                        onSuccess(result.body);
                        return;
                    }
                }
                if (result.body && result.body.message) {
                    msg = result.body.message;
                }
                var errorEl = section.querySelector('.ensor-log-rating__error');
                if (errorEl) {
                    errorEl.textContent = msg || '';
                    errorEl.hidden = !msg;
                }
            })
            .catch(function () {
                if (busy) {
                    busy.classList.remove('is-busy');
                }
                var errorEl = section.querySelector('.ensor-log-rating__error');
                var i18n = cfg().i18n || {};
                if (errorEl) {
                    errorEl.textContent = i18n.error || '';
                    errorEl.hidden = false;
                }
            });
    }

    function init() {
        Array.prototype.slice.call(document.querySelectorAll('.ensor-log-rating')).forEach(mount);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

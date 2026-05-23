/*!
 * Ensorlogs · Modal newsletter (Mailchimp API)
 */
(function () {
    'use strict';
    var d = document;
    var cfg = typeof window.ensorNewsletter === 'object' ? window.ensorNewsletter : {};
    var BODY_LOCK = 'ensor-newsletter-open';

    var lastFocus = null;

    function getModal() {
        return d.getElementById('ensor-newsletter-modal');
    }

    function isOpen() {
        var m = getModal();
        return !!(m && m.classList.contains('is-open'));
    }

    function getFocusables(root) {
        return Array.prototype.slice
            .call(
                root.querySelectorAll(
                    'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
                )
            )
            .filter(function (el) {
                return !el.hasAttribute('disabled') && el.getAttribute('aria-hidden') !== 'true';
            });
    }

    function getFeedback(form) {
        return (
            form.querySelector('.ensor-newsletter-form__feedback') ||
            form.querySelector('.ensor-newsletter-form__status')
        );
    }

    function ensureFeedback(form) {
        var feedback = getFeedback(form);
        if (feedback) {
            return feedback;
        }
        feedback = d.createElement('div');
        feedback.className = 'ensor-newsletter-form__feedback';
        feedback.setAttribute('role', 'status');
        feedback.setAttribute('aria-live', 'polite');
        feedback.setAttribute('aria-atomic', 'true');
        feedback.setAttribute('aria-hidden', 'true');
        feedback.hidden = true;
        form.appendChild(feedback);
        return feedback;
    }

    function clearFeedback(form) {
        if (!form) {
            return;
        }
        form.classList.remove('ensor-newsletter-form--success', 'ensor-newsletter-form--error');
        var feedback = getFeedback(form);
        if (!feedback) {
            return;
        }
        feedback.textContent = '';
        feedback.hidden = true;
        feedback.setAttribute('aria-hidden', 'true');
        feedback.classList.remove('is-error', 'is-success', 'is-visible');
    }

    function setFeedback(form, message, isError) {
        var feedback = ensureFeedback(form);
        form.classList.remove('ensor-newsletter-form--success', 'ensor-newsletter-form--error');
        feedback.textContent = message || '';
        feedback.classList.remove('is-error', 'is-success', 'is-visible');

        if (message) {
            feedback.hidden = false;
            feedback.removeAttribute('hidden');
            feedback.setAttribute('aria-hidden', 'false');
            feedback.classList.add('is-visible');
            feedback.classList.toggle('is-error', !!isError);
            feedback.classList.toggle('is-success', !isError);
            form.classList.add(isError ? 'ensor-newsletter-form--error' : 'ensor-newsletter-form--success');
            if (typeof feedback.scrollIntoView === 'function') {
                feedback.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            }
            return;
        }

        feedback.hidden = true;
        feedback.setAttribute('aria-hidden', 'true');
    }

    function setSubmitting(form, isSubmitting) {
        var submitBtn = form.querySelector('.ensor-newsletter-submit');
        if (!submitBtn) {
            return;
        }
        if (!submitBtn.dataset.ensorDefaultLabel) {
            submitBtn.dataset.ensorDefaultLabel = submitBtn.textContent || '';
        }
        submitBtn.disabled = !!isSubmitting;
        submitBtn.setAttribute('aria-busy', isSubmitting ? 'true' : 'false');
        submitBtn.textContent = isSubmitting
            ? cfg.sending || 'Enviando…'
            : submitBtn.dataset.ensorDefaultLabel;
    }

    function openModal() {
        var modal = getModal();
        if (!modal) {
            return;
        }
        lastFocus = d.activeElement;
        modal.removeAttribute('hidden');
        modal.setAttribute('aria-hidden', 'false');
        modal.classList.add('is-open');
        d.body.classList.add(BODY_LOCK);

        modal.querySelectorAll('.ensor-newsletter-native-form').forEach(clearFeedback);

        var emailInput = modal.querySelector(
            '.ensor-newsletter-native-form input[type="email"]'
        );
        var closeBtn = modal.querySelector('.ensor-newsletter-modal__close');
        var focusables = getFocusables(modal);
        var target = emailInput || closeBtn || focusables[0] || modal;
        if (typeof target.focus === 'function') {
            target.focus();
        }
    }

    function closeModal() {
        var modal = getModal();
        if (!modal) {
            return;
        }
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        modal.setAttribute('hidden', '');
        d.body.classList.remove(BODY_LOCK);

        if (lastFocus && typeof lastFocus.focus === 'function') {
            lastFocus.focus();
        }
        lastFocus = null;
    }

    function onCloseActivate(e) {
        if (!isOpen()) {
            return;
        }
        e.preventDefault();
        e.stopPropagation();
        closeModal();
    }

    function onModalClick(e) {
        if (!isOpen()) {
            return;
        }
        if (e.target.closest('[data-ensor-newsletter-close]')) {
            onCloseActivate(e);
            return;
        }
        if (e.target === e.currentTarget) {
            onCloseActivate(e);
        }
    }

    function bindModal() {
        var modal = getModal();
        if (!modal || modal.getAttribute('data-ensor-newsletter-bound') === '1') {
            return;
        }
        modal.setAttribute('data-ensor-newsletter-bound', '1');
        modal.addEventListener('click', onModalClick);

        modal.querySelectorAll('[data-ensor-newsletter-close]').forEach(function (el) {
            el.addEventListener('click', onCloseActivate);
        });
    }

    function onKeydown(e) {
        var modal = getModal();
        if (!modal || !modal.classList.contains('is-open')) {
            return;
        }
        if (e.key === 'Escape') {
            e.preventDefault();
            closeModal();
            return;
        }
        if (e.key !== 'Tab') {
            return;
        }
        var focusables = getFocusables(modal);
        if (focusables.length < 2) {
            return;
        }
        var first = focusables[0];
        var last = focusables[focusables.length - 1];
        if (e.shiftKey && d.activeElement === first) {
            e.preventDefault();
            last.focus();
        } else if (!e.shiftKey && d.activeElement === last) {
            e.preventDefault();
            first.focus();
        }
    }

    function parseAjaxResponse(res) {
        return res.text().then(function (text) {
            var trimmed = (text || '').trim();
            if (trimmed === '0' || trimmed === '-1') {
                return {
                    success: false,
                    data: {
                        message:
                            'La sesión caducó. Recarga la página e inténtalo de nuevo.',
                    },
                };
            }
            if (!trimmed) {
                return {
                    success: false,
                    data: {
                        message:
                            cfg.errorGeneric ||
                            'No se pudo suscribir. Inténtalo de nuevo.',
                    },
                };
            }
            try {
                return JSON.parse(trimmed);
            } catch (err) {
                return {
                    success: false,
                    data: {
                        message:
                            cfg.errorGeneric ||
                            'No se pudo suscribir. Inténtalo de nuevo.',
                    },
                };
            }
        });
    }

    function fetchAjaxAction(actionName) {
        if (!cfg.ajaxUrl || !actionName) {
            return Promise.resolve(null);
        }
        var sep = cfg.ajaxUrl.indexOf('?') >= 0 ? '&' : '?';
        var url =
            cfg.ajaxUrl +
            sep +
            'action=' +
            encodeURIComponent(actionName);
        return fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: {
                'Cache-Control': 'no-cache',
                Pragma: 'no-cache',
                'X-Requested-With': 'XMLHttpRequest',
            },
        }).then(parseAjaxResponse);
    }

    function syncConfigHints() {
        return fetchAjaxAction(cfg.statusAction || 'ensor_newsletter_status')
            .then(function (data) {
                if (!data || !data.success || !data.data) {
                    return;
                }
                var configured = !!data.data.configured;
                var message = data.data.message || '';
                d.querySelectorAll('[data-ensor-config-hint]').forEach(function (hint) {
                    if (configured) {
                        hint.hidden = true;
                        hint.textContent = '';
                        var input = hint.closest('form');
                        if (input) {
                            var email = input.querySelector('input[type="email"]');
                            if (email) {
                                email.removeAttribute('aria-describedby');
                            }
                        }
                        return;
                    }
                    if (message) {
                        hint.textContent = message;
                    }
                    hint.hidden = false;
                    hint.removeAttribute('hidden');
                });
            })
            .catch(function () {
                /* Si falla la comprobación, se mantiene el aviso del HTML. */
            });
    }

    function fetchFreshNonce() {
        return fetchAjaxAction(cfg.nonceAction || 'ensor_newsletter_refresh_nonce')
            .then(function (data) {
                if (data && data.success && data.data && data.data.nonce) {
                    cfg.nonce = data.data.nonce;
                }
            })
            .catch(function () {
                /* Sigue con el nonce embebido en la página. */
            });
    }

    function postSubscribe(form, email, isRetry) {
        var emailInput = form.querySelector('input[type="email"]');
        var body = new FormData();
        body.append('action', cfg.action || 'ensor_newsletter_subscribe');
        body.append('nonce', cfg.nonce || '');
        body.append('email', email);

        return fetch(cfg.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: body,
        })
            .then(parseAjaxResponse)
            .then(function (data) {
                if (data && data.success) {
                    var msg =
                        (data.data && data.data.message) ||
                        cfg.successMessage ||
                        'Te has suscrito correctamente. ¡Gracias!';
                    setFeedback(form, msg, false);
                    if (emailInput) {
                        emailInput.value = '';
                    }
                    return;
                }
                var errMsg =
                    (data && data.data && data.data.message) ||
                    cfg.errorGeneric ||
                    'No se pudo suscribir. Inténtalo de nuevo.';

                if (
                    !isRetry &&
                    errMsg &&
                    errMsg.toLowerCase().indexOf('sesión caducó') !== -1
                ) {
                    return fetchFreshNonce().then(function () {
                        return postSubscribe(form, email, true);
                    });
                }

                setFeedback(form, errMsg, true);
            });
    }

    function submitNewsletterForm(form) {
        if (!form || !form.classList.contains('ensor-newsletter-native-form')) {
            return;
        }

        if (!cfg.ajaxUrl) {
            setFeedback(
                form,
                'No se pudo enviar la suscripción. Recarga la página e inténtalo de nuevo.',
                true
            );
            return;
        }

        var emailInput = form.querySelector('input[type="email"]');
        var email = emailInput ? String(emailInput.value || '').trim() : '';

        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            setFeedback(
                form,
                cfg.errorGeneric || 'Introduce un correo válido.',
                true
            );
            if (emailInput && typeof emailInput.focus === 'function') {
                emailInput.focus();
            }
            return;
        }

        setSubmitting(form, true);
        setFeedback(form, cfg.sending || 'Enviando…', false);

        fetchFreshNonce()
            .then(function () {
                return postSubscribe(form, email, false);
            })
            .catch(function () {
                setFeedback(
                    form,
                    cfg.errorGeneric || 'No se pudo suscribir. Inténtalo de nuevo.',
                    true
                );
            })
            .finally(function () {
                setSubmitting(form, false);
            });
    }

    function ready(fn) {
        if (d.readyState !== 'loading') {
            fn();
        } else {
            d.addEventListener('DOMContentLoaded', fn);
        }
    }

    d.addEventListener(
        'submit',
        function (e) {
            var form = e.target;
            if (
                !form ||
                !form.classList ||
                !form.classList.contains('ensor-newsletter-native-form')
            ) {
                return;
            }
            e.preventDefault();
            e.stopPropagation();
            submitNewsletterForm(form);
        },
        true
    );

    d.addEventListener('click', function (e) {
        if (e.target.closest('.ensor-newsletter-open')) {
            e.preventDefault();
            openModal();
            return;
        }
        var submitBtn = e.target.closest('.ensor-newsletter-submit');
        if (submitBtn) {
            var form = submitBtn.closest('.ensor-newsletter-native-form');
            if (form) {
                e.preventDefault();
                submitNewsletterForm(form);
            }
        }
    });

    d.addEventListener('keydown', onKeydown);
    ready(function () {
        bindModal();
        syncConfigHints();
    });
})();

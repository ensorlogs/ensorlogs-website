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

    function setStatus(form, message, isError) {
        var status = form.querySelector('.ensor-newsletter-form__status');
        if (!status) {
            return;
        }
        status.textContent = message;
        status.hidden = !message;
        status.classList.toggle('is-error', !!isError);
        status.classList.toggle('is-success', !isError && !!message);
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
            if (!text) {
                return null;
            }
            try {
                return JSON.parse(text);
            } catch (err) {
                return null;
            }
        });
    }

    function handleFormSubmit(e) {
        var form = e.target.closest('.ensor-newsletter-native-form');
        if (!form) {
            return;
        }
        e.preventDefault();

        if (!cfg.ajaxUrl) {
            setStatus(
                form,
                'No se pudo enviar la suscripción. Recarga la página e inténtalo de nuevo.',
                true
            );
            return;
        }

        var emailInput = form.querySelector('input[type="email"]');
        var submitBtn = form.querySelector('.ensor-newsletter-submit');
        var email = emailInput ? String(emailInput.value || '').trim() : '';

        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            setStatus(form, cfg.errorGeneric || 'Introduce un correo válido.', true);
            if (emailInput && typeof emailInput.focus === 'function') {
                emailInput.focus();
            }
            return;
        }

        setStatus(form, cfg.sending || 'Enviando…', false);
        if (submitBtn) {
            submitBtn.disabled = true;
        }

        var body = new FormData();
        body.append('action', cfg.action || 'ensor_newsletter_subscribe');
        body.append('nonce', cfg.nonce || '');
        body.append('email', email);

        fetch(cfg.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: body,
        })
            .then(parseAjaxResponse)
            .then(function (data) {
                if (data && data.success) {
                    var msg =
                        (data.data && data.data.message) ||
                        '¡Listo! Revisa tu correo si hace falta confirmar.';
                    setStatus(form, msg, false);
                    if (emailInput) {
                        emailInput.value = '';
                    }
                    return;
                }
                var errMsg =
                    (data && data.data && data.data.message) ||
                    cfg.errorGeneric ||
                    'No se pudo suscribir.';
                setStatus(form, errMsg, true);
            })
            .catch(function () {
                setStatus(form, cfg.errorGeneric || 'No se pudo suscribir.', true);
            })
            .finally(function () {
                if (submitBtn) {
                    submitBtn.disabled = false;
                }
            });
    }

    function ready(fn) {
        if (d.readyState !== 'loading') {
            fn();
        } else {
            d.addEventListener('DOMContentLoaded', fn);
        }
    }

    d.addEventListener('click', function (e) {
        if (e.target.closest('.ensor-newsletter-open')) {
            e.preventDefault();
            openModal();
        }
    });

    d.addEventListener('submit', handleFormSubmit);
    d.addEventListener('keydown', onKeydown);
    ready(bindModal);
})();

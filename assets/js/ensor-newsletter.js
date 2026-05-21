/*!
 * Ensorlogs · Modal newsletter (Mailchimp API)
 */
(function () {
    'use strict';
    var d = document;
    var cfg = typeof window.ensorNewsletter === 'object' ? window.ensorNewsletter : {};
    var BODY_LOCK = 'ensor-newsletter-open';

    var modal = null;
    var lastFocus = null;

    function getModal() {
        return d.getElementById('ensor-newsletter-modal');
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
        modal = getModal();
        if (!modal) {
            return;
        }
        lastFocus = d.activeElement;
        modal.removeAttribute('hidden');
        modal.setAttribute('aria-hidden', 'false');
        modal.classList.add('is-open');
        d.body.classList.add(BODY_LOCK);

        var closeBtn = modal.querySelector('.ensor-newsletter-modal__close');
        var focusables = getFocusables(modal);
        var target = closeBtn || focusables[0] || modal;
        if (typeof target.focus === 'function') {
            target.focus();
        }
    }

    function closeModal() {
        modal = getModal();
        if (!modal) {
            return;
        }
        modal.setAttribute('aria-hidden', 'true');
        modal.classList.remove('is-open');
        modal.setAttribute('hidden', '');
        d.body.classList.remove(BODY_LOCK);

        if (lastFocus && typeof lastFocus.focus === 'function') {
            lastFocus.focus();
        }
        lastFocus = null;
    }

    function onKeydown(e) {
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

    function handleFormSubmit(e) {
        var form = e.target.closest('.ensor-newsletter-native-form');
        if (!form || !cfg.ajaxUrl) {
            return;
        }
        e.preventDefault();

        var emailInput = form.querySelector('input[type="email"]');
        var submitBtn = form.querySelector('.ensor-newsletter-submit');
        var email = emailInput ? String(emailInput.value || '').trim() : '';

        if (!email) {
            setStatus(form, cfg.errorGeneric || 'Introduce un correo válido.', true);
            return;
        }

        setStatus(form, '', false);
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
            .then(function (res) {
                return res.json();
            })
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

    d.addEventListener('click', function (e) {
        if (e.target.closest('.ensor-newsletter-open')) {
            e.preventDefault();
            openModal();
            return;
        }
        if (e.target.closest('[data-ensor-newsletter-close]')) {
            e.preventDefault();
            closeModal();
        }
    });

    d.addEventListener('submit', handleFormSubmit);
    d.addEventListener('keydown', onKeydown);
})();

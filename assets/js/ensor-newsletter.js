/*!
 * Ensorlogs · Modal newsletter (Mailchimp API)
 */
(function () {
    'use strict';
    var d = document;
    var cfg = typeof window.ensorNewsletter === 'object' ? window.ensorNewsletter : {};
    var BODY_LOCK = 'ensor-newsletter-open';
    var AUTO_OPEN_DEFAULT_MS = 8000;
    var DISMISS_STORAGE_KEY = 'ensor-newsletter-dismissed';
    var SUBSCRIBED_STORAGE_KEY = 'ensor-newsletter-subscribed';
    var DISMISS_TTL_MS = 7 * 24 * 60 * 60 * 1000;

    var lastFocus = null;
    var autoOpenTimer = null;

    function getModal() {
        return d.getElementById('ensor-newsletter-modal');
    }

    function hydrateCfg() {
        var modal = getModal();
        if (!modal) {
            return cfg;
        }
        var raw = modal.getAttribute('data-ensor-newsletter');
        if (!raw) {
            return cfg;
        }
        try {
            var parsed = JSON.parse(raw);
            if (parsed && typeof parsed === 'object') {
                Object.keys(parsed).forEach(function (key) {
                    cfg[key] = parsed[key];
                });
            }
        } catch (e) {
            /* Mantiene wp_localize_script / inline de respaldo. */
        }
        return cfg;
    }

    function isMailchimpReady() {
        hydrateCfg();
        var c = cfg.configured;
        return c === true || c === 'true' || c === 1 || c === '1';
    }

    function configHintMessage() {
        hydrateCfg();
        return (
            cfg.configMessage ||
            'Configura API key y Audience ID en Personalizar → Ensorlogs → Newsletter.'
        );
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
        var submitBtn = form.querySelector('.ensor-newsletter-submit');
        if (submitBtn) {
            form.insertBefore(feedback, submitBtn);
        } else {
            form.appendChild(feedback);
        }
        return feedback;
    }

    function clearFeedback(form) {
        if (!form) {
            return;
        }
        form.classList.remove(
            'ensor-newsletter-form--success',
            'ensor-newsletter-form--error',
            'ensor-newsletter-form--pending'
        );
        var feedback = getFeedback(form);
        if (!feedback) {
            return;
        }
        feedback.textContent = '';
        feedback.hidden = true;
        feedback.setAttribute('aria-hidden', 'true');
        feedback.classList.remove('is-error', 'is-success', 'is-visible', 'is-pending');
    }

    function setFeedback(form, message, state) {
        var feedback = ensureFeedback(form);
        form.classList.remove(
            'ensor-newsletter-form--success',
            'ensor-newsletter-form--error',
            'ensor-newsletter-form--pending'
        );
        feedback.textContent = message || '';
        feedback.classList.remove('is-error', 'is-success', 'is-visible', 'is-pending');

        if (message) {
            feedback.hidden = false;
            feedback.removeAttribute('hidden');
            feedback.setAttribute('aria-hidden', 'false');
            feedback.classList.add('is-visible');

            if (state === 'error') {
                feedback.classList.add('is-error');
                form.classList.add('ensor-newsletter-form--error');
            } else if (state === 'success') {
                feedback.classList.add('is-success');
                form.classList.add('ensor-newsletter-form--success');
            } else if (state === 'pending') {
                feedback.classList.add('is-pending');
                form.classList.add('ensor-newsletter-form--pending');
            }

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

    function applyConfigHints(configured, message) {
        cfg.configured = !!configured;
        if (message) {
            cfg.configMessage = message;
        }

        d.querySelectorAll('[data-ensor-config-hint]').forEach(function (hint) {
            var form = hint.closest('form');
            var email = form ? form.querySelector('input[type="email"]') : null;

            if (configured) {
                hint.hidden = true;
                hint.setAttribute('hidden', '');
                hint.textContent = '';
                hint.classList.remove('is-warning');
                if (email) {
                    email.removeAttribute('aria-describedby');
                }
                return;
            }

            if (message) {
                hint.textContent = message;
            }
            hint.hidden = false;
            hint.removeAttribute('hidden');
            hint.classList.add('is-warning');
            if (email && hint.id) {
                email.setAttribute('aria-describedby', hint.id);
            }
        });
    }

    function openModal() {
        var modal = getModal();
        if (!modal) {
            return;
        }
        hydrateCfg();
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
        markDismissed();
    }

    function getAutoOpenDelayMs() {
        hydrateCfg();
        if (cfg.autoOpenEnabled === false || cfg.autoOpenEnabled === 'false') {
            return 0;
        }
        var delay = cfg.autoOpenDelayMs;
        if (delay === false || delay === 0 || delay === '0') {
            return 0;
        }
        if (typeof delay === 'number' && delay > 0) {
            return delay;
        }
        if (typeof delay === 'string' && /^\d+$/.test(delay)) {
            return parseInt(delay, 10);
        }
        return AUTO_OPEN_DEFAULT_MS;
    }

    function markDismissed() {
        try {
            localStorage.setItem(DISMISS_STORAGE_KEY, String(Date.now()));
        } catch (e) {
            /* Sin almacenamiento: el popup puede volver en otra visita. */
        }
    }

    function markSubscribed() {
        try {
            localStorage.setItem(SUBSCRIBED_STORAGE_KEY, '1');
            localStorage.removeItem(DISMISS_STORAGE_KEY);
        } catch (e) {
            /* ignore */
        }
    }

    function shouldAutoOpen() {
        if (!getModal()) {
            return false;
        }
        if (isOpen()) {
            return false;
        }
        try {
            if (localStorage.getItem(SUBSCRIBED_STORAGE_KEY) === '1') {
                return false;
            }
            var dismissed = localStorage.getItem(DISMISS_STORAGE_KEY);
            if (dismissed) {
                var ts = parseInt(dismissed, 10);
                if (!isNaN(ts) && Date.now() - ts < DISMISS_TTL_MS) {
                    return false;
                }
            }
        } catch (e) {
            /* Si no hay localStorage, seguimos. */
        }
        return true;
    }

    function isPreloaderVisible() {
        var pre = d.querySelector('.preloader');
        if (!pre) {
            return false;
        }
        var style = window.getComputedStyle(pre);
        return (
            style.display !== 'none' &&
            style.visibility !== 'hidden' &&
            parseFloat(style.opacity || '1') > 0.05
        );
    }

    function tryAutoOpen() {
        if (!shouldAutoOpen() || isOpen()) {
            return;
        }
        if (d.hidden || isPreloaderVisible()) {
            autoOpenTimer = window.setTimeout(tryAutoOpen, 400);
            return;
        }
        openModal();
    }

    function scheduleAutoOpen() {
        var delayMs = getAutoOpenDelayMs();
        if (!delayMs || !shouldAutoOpen()) {
            return;
        }
        if (autoOpenTimer) {
            clearTimeout(autoOpenTimer);
        }
        autoOpenTimer = window.setTimeout(function () {
            autoOpenTimer = null;
            tryAutoOpen();
        }, delayMs);
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
        hydrateCfg();
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
                    applyConfigHints(isMailchimpReady(), configHintMessage());
                    return;
                }
                var configured = !!data.data.configured;
                var message = data.data.message || '';
                applyConfigHints(configured, message);
            })
            .catch(function () {
                applyConfigHints(isMailchimpReady(), configHintMessage());
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
        hydrateCfg();
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
                    setFeedback(form, msg, 'success');
                    markSubscribed();
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

                setFeedback(form, errMsg, 'error');
            });
    }

    function submitNewsletterForm(form) {
        if (!form || !form.classList.contains('ensor-newsletter-native-form')) {
            return;
        }

        hydrateCfg();

        if (!cfg.ajaxUrl) {
            setFeedback(
                form,
                'No se pudo enviar la suscripción. Recarga la página e inténtalo de nuevo.',
                'error'
            );
            return;
        }

        var emailInput = form.querySelector('input[type="email"]');
        var email = emailInput ? String(emailInput.value || '').trim() : '';

        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            setFeedback(
                form,
                cfg.invalidEmail || 'Introduce un correo válido.',
                'error'
            );
            if (emailInput && typeof emailInput.focus === 'function') {
                emailInput.focus();
            }
            return;
        }

        if (!isMailchimpReady()) {
            setFeedback(form, configHintMessage(), 'error');
            return;
        }

        setSubmitting(form, true);
        setFeedback(form, cfg.sending || 'Enviando…', 'pending');

        fetchFreshNonce()
            .then(function () {
                return postSubscribe(form, email, false);
            })
            .catch(function () {
                setFeedback(
                    form,
                    cfg.errorGeneric || 'No se pudo suscribir. Inténtalo de nuevo.',
                    'error'
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
            if (autoOpenTimer) {
                clearTimeout(autoOpenTimer);
                autoOpenTimer = null;
            }
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
        hydrateCfg();
        bindModal();
        applyConfigHints(isMailchimpReady(), configHintMessage());
        syncConfigHints();
        scheduleAutoOpen();
    });
})();

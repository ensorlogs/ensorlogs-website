/**
 * Formulario de contacto: renderiza Cloudflare Turnstile y bloquea envío hasta verificar.
 */
(function () {
    'use strict';

    var mount = document.getElementById('ensor-contact-turnstile');
    var form = document.getElementById('contact-form');
    if (!mount || !form) {
        return;
    }

    var siteKey = mount.getAttribute('data-sitekey') || '';
    if (!siteKey) {
        return;
    }

    var submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.setAttribute('aria-disabled', 'true');
    }

    function turnstileTheme() {
        return document.documentElement.classList.contains('dark') ? 'dark' : 'light';
    }

    function enableSubmit() {
        if (!submitBtn) {
            return;
        }
        submitBtn.disabled = false;
        submitBtn.removeAttribute('aria-disabled');
    }

    function renderWidget() {
        if (typeof window.turnstile === 'undefined' || typeof window.turnstile.render !== 'function') {
            return;
        }
        mount.innerHTML = '';
        window.turnstile.render(mount, {
            sitekey: siteKey,
            theme: turnstileTheme(),
            callback: enableSubmit,
            'expired-callback': function () {
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.setAttribute('aria-disabled', 'true');
                }
            },
            'error-callback': function () {
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.setAttribute('aria-disabled', 'true');
                }
            },
        });
    }

    if (typeof window.turnstile !== 'undefined') {
        renderWidget();
    } else {
        window.addEventListener('load', renderWidget);
    }
})();

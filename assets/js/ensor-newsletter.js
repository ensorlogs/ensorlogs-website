/*!
 * Ensorlogs · Modal newsletter (Mailchimp plugin)
 */
(function () {
    'use strict';
    var d = document;
    var BODY_LOCK = 'ensor-newsletter-open';

    var modal = null;
    var lastFocus = null;

    function getModal() {
        return d.getElementById('ensor-newsletter-modal');
    }

    function getFocusables(root) {
        return Array.prototype.slice.call(
            root.querySelectorAll(
                'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
            )
        ).filter(function (el) {
            return !el.hasAttribute('disabled') && el.getAttribute('aria-hidden') !== 'true';
        });
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

    d.addEventListener('keydown', onKeydown);
})();

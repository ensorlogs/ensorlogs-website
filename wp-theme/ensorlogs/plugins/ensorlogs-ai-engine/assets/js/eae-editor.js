(function () {
    'use strict';

    var cfg = typeof window.EAE_CFG === 'object' ? window.EAE_CFG : {};

    function byId(id) {
        return document.getElementById(id);
    }

    function setStatus(message, isError) {
        var node = byId('eae-status');
        if (!node) {
            return;
        }
        node.textContent = message || '';
        node.classList.toggle('is-error', !!isError);
        node.classList.toggle('is-success', !!message && !isError);
    }

    function getPayload() {
        return {
            topic: (byId('eae-topic') || {}).value || '',
            context: (byId('eae-context') || {}).value || '',
            experience: (byId('eae-experience') || {}).value || '',
            teach: (byId('eae-teach') || {}).value || '',
            logType: (byId('eae-log-type') || {}).value || '',
            level: (byId('eae-level') || {}).value || '',
            audience: (byId('eae-audience') || {}).value || '',
        };
    }

    function insertIntoGutenberg(html) {
        if (!window.wp || !window.wp.data || !window.wp.data.dispatch) {
            return false;
        }
        try {
            window.wp.data.dispatch('core/editor').editPost({ content: html });
            return true;
        } catch (err) {
            return false;
        }
    }

    function insertIntoClassic(html) {
        var textarea = document.getElementById('content');
        if (window.tinymce && window.tinymce.get('content')) {
            window.tinymce.get('content').setContent(html);
            return true;
        }
        if (textarea) {
            textarea.value = html;
            return true;
        }
        return false;
    }

    function insertContent(html) {
        return insertIntoGutenberg(html) || insertIntoClassic(html);
    }

    function setBusy(button, busy) {
        if (!button) {
            return;
        }
        if (!button.dataset.defaultText) {
            button.dataset.defaultText = button.textContent || '';
        }
        button.disabled = !!busy;
        button.textContent = busy ? (cfg.defaultMessages && cfg.defaultMessages.working) || 'Generando…' : button.dataset.defaultText;
    }

    function onGenerateClick(e) {
        e.preventDefault();
        var button = e.currentTarget;
        var payload = getPayload();

        if (!payload.topic.trim()) {
            setStatus('Debes escribir el tema del LOG.', true);
            return;
        }

        if (!cfg.apiConfigured) {
            setStatus((cfg.defaultMessages && cfg.defaultMessages.missingApiKey) || 'Configura la API key.', true);
            return;
        }

        setBusy(button, true);
        setStatus((cfg.defaultMessages && cfg.defaultMessages.working) || 'Generando…', false);

        fetch(cfg.restUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': cfg.nonce || '',
            },
            body: JSON.stringify(payload),
        })
            .then(function (res) {
                return res.json().then(function (json) {
                    return { ok: res.ok, data: json };
                });
            })
            .then(function (result) {
                if (!result.ok || !result.data || !result.data.ok) {
                    throw new Error((result.data && (result.data.message || result.data.error)) || 'No se pudo generar.');
                }
                if (!insertContent(result.data.html || '')) {
                    throw new Error('No se pudo insertar en el editor actual.');
                }
                setStatus((cfg.defaultMessages && cfg.defaultMessages.success) || 'Log insertado.', false);
            })
            .catch(function (err) {
                setStatus(err && err.message ? err.message : 'No se pudo generar el LOG.', true);
            })
            .finally(function () {
                setBusy(button, false);
            });
    }

    function init() {
        var button = byId('eae-generate');
        if (!button) {
            return;
        }
        button.addEventListener('click', onGenerateClick);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

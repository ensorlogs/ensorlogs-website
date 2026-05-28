(function () {
    'use strict';

    function cfg() {
        return typeof window.EAE_CFG === 'object' && window.EAE_CFG ? window.EAE_CFG : {};
    }

    function byId(id) {
        return document.getElementById(id);
    }

    function setStatus(message, isError) {
        var node = byId('eae-status');
        if (!node) {
            if (message) {
                window.alert(message);
            }
            return;
        }
        node.textContent = message || '';
        node.classList.toggle('is-error', !!isError);
        node.classList.toggle('is-success', !!message && !isError);
    }

    function getSelectedStacks() {
        var boxes = document.querySelectorAll('#eae-stacks input[name="eae_stack[]"]:checked');
        var out = [];
        boxes.forEach(function (el) {
            if (el.value) {
                out.push(el.value);
            }
        });
        return out;
    }

    function getPostId() {
        var hidden = byId('post_ID');
        if (hidden && hidden.value) {
            return parseInt(hidden.value, 10) || 0;
        }
        return cfg().postId || 0;
    }

    function getPayload() {
        return {
            topic: (byId('eae-topic') || {}).value || '',
            context: (byId('eae-context') || {}).value || '',
            experience: (byId('eae-experience') || {}).value || '',
            teach: (byId('eae-teach') || {}).value || '',
            postId: getPostId(),
            stacks: getSelectedStacks(),
        };
    }

    function isBlockEditor() {
        if (cfg().isBlockEditor) {
            return true;
        }
        return document.body && document.body.classList.contains('block-editor-page');
    }

    function insertIntoBlockEditor(markup) {
        if (!markup || !window.wp || !window.wp.blocks || !window.wp.data) {
            return false;
        }
        try {
            var blocks = window.wp.blocks.parse(markup);
            if (!blocks || !blocks.length) {
                blocks = window.wp.blocks.rawHandler({ HTML: markup });
            }
            var dispatch = window.wp.data.dispatch('core/block-editor');
            if (dispatch && typeof dispatch.resetBlocks === 'function') {
                dispatch.resetBlocks(blocks);
                return true;
            }
        } catch (err) {
            console.error('EAE block editor insert:', err);
        }
        return false;
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

    function insertContent(html, blockContent) {
        if (isBlockEditor() && blockContent) {
            if (insertIntoBlockEditor(blockContent)) {
                return true;
            }
        }
        if (isBlockEditor() && html) {
            if (insertIntoBlockEditor(html)) {
                return true;
            }
        }
        return insertIntoClassic(html || blockContent || '');
    }

    function syncListingFields(sync, quizText) {
        if (!sync || typeof sync !== 'object') {
            return;
        }
        var temas = byId('ensor_temas');
        if (temas && sync.stacks) {
            temas.value = sync.stacks;
        }
        var primary = byId('ensor_primary_tema');
        if (primary && sync.primaryTema) {
            primary.value = sync.primaryTema;
        }
        var quiz = byId('ensor_quiz');
        if (quiz && quizText) {
            quiz.value = quizText;
        }
        var title = byId('title');
        if (title && sync.title && !title.value.trim()) {
            title.value = sync.title;
        }
    }

    function setBusy(button, busy) {
        if (!button) {
            return;
        }
        if (!button.dataset.defaultText) {
            button.dataset.defaultText = button.textContent || '';
        }
        button.disabled = !!busy;
        button.textContent = busy
            ? (cfg().defaultMessages && cfg().defaultMessages.working) || 'Generando…'
            : button.dataset.defaultText;
    }

    function parseResponse(res) {
        return res.text().then(function (text) {
            var data = null;
            if (text) {
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    data = null;
                }
            }
            return { ok: res.ok, data: data, status: res.status, raw: text };
        });
    }

    function onGenerateClick(e) {
        e.preventDefault();
        e.stopPropagation();

        var c = cfg();
        var button = e.currentTarget;
        var payload = getPayload();

        if (!payload.topic.trim()) {
            setStatus((c.defaultMessages && c.defaultMessages.missingTopic) || 'Escribe el tema del LOG.', true);
            return;
        }

        if (!c.apiConfigured) {
            var keyMsg =
                (c.defaultMessages && c.defaultMessages.missingApiKey) ||
                'Configura la API key en Ajustes > Ensorlogs AI Engine.';
            setStatus(keyMsg, true);
            return;
        }

        if (!c.restUrl) {
            setStatus('No se cargó la configuración del panel. Recarga la página.', true);
            return;
        }

        setBusy(button, true);
        setStatus((c.defaultMessages && c.defaultMessages.working) || 'Generando…', false);

        fetch(c.restUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': c.nonce || '',
            },
            body: JSON.stringify(payload),
        })
            .then(parseResponse)
            .then(function (result) {
                if (!result.ok || !result.data || !result.data.ok) {
                    var msg =
                        (result.data && (result.data.message || result.data.error)) ||
                        ('Error del servidor (' + result.status + ').');
                    throw new Error(msg);
                }

                var html = result.data.html || '';
                var blockContent = result.data.blockContent || '';
                var inserted = insertContent(html, blockContent);

                syncListingFields(result.data.sync, result.data.quizText);

                if (inserted) {
                    setStatus((c.defaultMessages && c.defaultMessages.success) || 'LOG insertado.', false);
                } else if (result.data.sync && result.data.sync.savedPost) {
                    setStatus(
                        (c.defaultMessages && c.defaultMessages.savedReload) ||
                            'LOG guardado. Recarga la página para verlo en el editor.',
                        false
                    );
                } else {
                    throw new Error('No se pudo insertar en el editor. Guarda borrador y recarga.');
                }
            })
            .catch(function (err) {
                setStatus(err && err.message ? err.message : 'No se pudo generar el LOG.', true);
            })
            .finally(function () {
                setBusy(button, false);
            });
    }

    function bindGenerateButton() {
        var button = byId('eae-generate');
        if (!button || button.dataset.eaeBound === '1') {
            return;
        }
        button.dataset.eaeBound = '1';
        button.addEventListener('click', onGenerateClick);
    }

    function init() {
        bindGenerateButton();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    document.addEventListener('click', function (e) {
        var t = e.target;
        if (t && t.id === 'eae-generate') {
            onGenerateClick(e);
        }
    });
})();

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
        if (typeof window.switchEditors !== 'undefined') {
            try {
                window.switchEditors.go('content', 'tmce');
            } catch (e1) {}
        }
        if (window.tinymce && window.tinymce.get('content')) {
            window.tinymce.get('content').setContent(html);
            window.tinymce.get('content').save();
            return true;
        }
        var textarea = document.getElementById('content');
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

    function setBusy(button, busy, busyLabel) {
        if (!button) {
            return;
        }
        if (!button.dataset.defaultText) {
            button.dataset.defaultText = button.textContent || '';
        }
        button.disabled = !!busy;
        if (busy && busyLabel) {
            button.textContent = busyLabel;
        } else if (!busy) {
            button.textContent = button.dataset.defaultText;
        }
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

    function postJson(url, payload) {
        var c = cfg();
        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': c.nonce || '',
            },
            body: JSON.stringify(payload),
        }).then(parseResponse);
    }

    function validateTopic(payload) {
        if (!payload.topic.trim()) {
            setStatus(
                (cfg().defaultMessages && cfg().defaultMessages.missingTopic) ||
                    'Escribe el tema del LOG.',
                true
            );
            return false;
        }
        return true;
    }

    function showPromptPreview(prompt) {
        var wrap = byId('eae-prompt-preview-wrap');
        var area = byId('eae-prompt-preview');
        var toggle = byId('eae-toggle-prompt');
        if (area) {
            area.value = prompt;
        }
        if (wrap) {
            wrap.hidden = false;
        }
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'true');
            toggle.textContent = toggle.dataset.hideLabel || 'Ocultar prompt';
        }
    }

    function copyTextToClipboard(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(text);
        }
        return new Promise(function (resolve, reject) {
            var area = byId('eae-prompt-preview');
            if (!area) {
                reject(new Error('no textarea'));
                return;
            }
            area.value = text;
            area.removeAttribute('readonly');
            area.focus();
            area.select();
            try {
                var ok = document.execCommand('copy');
                area.setAttribute('readonly', 'readonly');
                if (ok) {
                    resolve();
                } else {
                    reject(new Error('execCommand failed'));
                }
            } catch (err) {
                area.setAttribute('readonly', 'readonly');
                reject(err);
            }
        });
    }

    function onCopyPromptClick(e) {
        e.preventDefault();
        e.stopPropagation();

        var c = cfg();
        var button = e.currentTarget;
        var payload = getPayload();

        if (!validateTopic(payload)) {
            return;
        }
        if (!c.buildPromptUrl) {
            setStatus('No se cargó la configuración del panel. Recarga la página.', true);
            return;
        }

        setBusy(
            button,
            true,
            (c.defaultMessages && c.defaultMessages.buildingPrompt) || 'Montando prompt…'
        );
        setStatus((c.defaultMessages && c.defaultMessages.buildingPrompt) || 'Montando prompt…', false);

        postJson(c.buildPromptUrl, payload)
            .then(function (result) {
                if (!result.ok || !result.data || !result.data.ok || !result.data.prompt) {
                    var msg =
                        (result.data && (result.data.error || result.data.message)) ||
                        ('Error del servidor (' + result.status + ').');
                    throw new Error(msg);
                }
                var prompt = result.data.prompt;
                showPromptPreview(prompt);
                return copyTextToClipboard(prompt);
            })
            .then(function () {
                setStatus(
                    (c.defaultMessages && c.defaultMessages.promptCopied) ||
                        'Prompt copiado. Pégalo en ChatGPT.',
                    false
                );
            })
            .catch(function (err) {
                var preview = byId('eae-prompt-preview');
                if (preview && preview.value) {
                    setStatus(
                        (c.defaultMessages && c.defaultMessages.copyFailed) ||
                            'Copia el prompt del cuadro de abajo.',
                        true
                    );
                    return;
                }
                setStatus(err && err.message ? err.message : 'No se pudo crear el prompt.', true);
            })
            .finally(function () {
                setBusy(button, false);
            });
    }

    function onTogglePromptClick(e) {
        e.preventDefault();
        var wrap = byId('eae-prompt-preview-wrap');
        var toggle = e.currentTarget;
        if (!wrap || !toggle) {
            return;
        }
        var show = wrap.hidden;
        wrap.hidden = !show;
        toggle.setAttribute('aria-expanded', show ? 'true' : 'false');
        if (!toggle.dataset.showLabel) {
            toggle.dataset.showLabel = toggle.textContent || 'Ver prompt';
            toggle.dataset.hideLabel = 'Ocultar prompt';
        }
        toggle.textContent = show ? toggle.dataset.hideLabel : toggle.dataset.showLabel;
        if (show && !byId('eae-prompt-preview').value.trim()) {
            onCopyPromptClick(e);
        }
    }

    function onImportHtmlClick(e) {
        e.preventDefault();
        e.stopPropagation();

        var c = cfg();
        var button = e.currentTarget;
        var payload = getPayload();
        var htmlField = byId('eae-html-paste');
        var html = htmlField ? htmlField.value.trim() : '';

        if (!validateTopic(payload)) {
            return;
        }
        if (!html) {
            setStatus(
                (c.defaultMessages && c.defaultMessages.missingHtml) ||
                    'Pega el HTML de ChatGPT.',
                true
            );
            return;
        }
        if (!c.importHtmlUrl) {
            setStatus('No se cargó la configuración del panel. Recarga la página.', true);
            return;
        }

        payload.html = html;
        setBusy(
            button,
            true,
            (c.defaultMessages && c.defaultMessages.importing) || 'Insertando…'
        );
        setStatus((c.defaultMessages && c.defaultMessages.importing) || 'Insertando…', false);

        postJson(c.importHtmlUrl, payload)
            .then(function (result) {
                if (!result.ok || !result.data || !result.data.ok) {
                    var msg =
                        (result.data && (result.data.error || result.data.message)) ||
                        ('Error del servidor (' + result.status + ').');
                    throw new Error(msg);
                }

                var editorHtml = result.data.html || '';
                var blockContent = result.data.blockContent || '';
                var inserted = insertContent(editorHtml, blockContent);

                syncListingFields(result.data.sync, result.data.quizText);

                if (inserted) {
                    setStatus(
                        (c.defaultMessages && c.defaultMessages.importSuccess) ||
                            'LOG insertado.',
                        false
                    );
                    var box = byId('postdivrich');
                    if (box && box.scrollIntoView) {
                        setTimeout(function () {
                            box.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }, 200);
                    }
                } else if (result.data.sync && result.data.sync.savedPost) {
                    setStatus(
                        (c.defaultMessages && c.defaultMessages.savedReload) ||
                            'LOG guardado. Recarga la página.',
                        false
                    );
                } else {
                    throw new Error('No se pudo insertar en el editor.');
                }
            })
            .catch(function (err) {
                setStatus(err && err.message ? err.message : 'No se pudo insertar el HTML.', true);
            })
            .finally(function () {
                setBusy(button, false);
            });
    }

    function bindButton(id, handler, flag) {
        var button = byId(id);
        if (!button || button.dataset[flag] === '1') {
            return;
        }
        button.dataset[flag] = '1';
        button.addEventListener('click', handler);
    }

    function init() {
        bindButton('eae-copy-prompt', onCopyPromptClick, 'eaeBoundCopy');
        bindButton('eae-import-html', onImportHtmlClick, 'eaeBoundImport');
        bindButton('eae-toggle-prompt', onTogglePromptClick, 'eaeBoundToggle');
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    document.addEventListener('click', function (e) {
        var t = e.target;
        if (!t || !t.id) {
            return;
        }
        if (t.id === 'eae-copy-prompt') {
            onCopyPromptClick(e);
        } else if (t.id === 'eae-import-html') {
            onImportHtmlClick(e);
        } else if (t.id === 'eae-toggle-prompt') {
            onTogglePromptClick(e);
        }
    });
})();

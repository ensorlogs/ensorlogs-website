<?php
/**
 * Script inline del panel IA (fallback si el JS externo no carga).
 *
 * @package Ensorlogs_AI_Engine
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @param array<string, mixed> $cfg
 */
function eae_print_inline_generator_script(array $cfg): void
{
    $json = wp_json_encode($cfg);
    if (!is_string($json)) {
        $json = '{}';
    }
    ?>
<script type="text/javascript" id="eae-inline-generator" data-cfasync="false" data-no-optimize="1">
(function () {
    'use strict';
    window.EAE_CFG = Object.assign(window.EAE_CFG || {}, <?php echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>);

    if (window.EAE_CFG && window.EAE_CFG._eaeInlineBooted) {
        return;
    }
    if (window.EAE_CFG) {
        window.EAE_CFG._eaeInlineBooted = true;
    }

    function cfg() {
        return window.EAE_CFG && typeof window.EAE_CFG === 'object' ? window.EAE_CFG : {};
    }

    function $(id) {
        return document.getElementById(id);
    }

    function setStatus(msg, isError) {
        var el = $('eae-status');
        if (el) {
            el.textContent = msg || '';
            el.classList.toggle('is-error', !!isError);
            el.classList.toggle('is-success', !!msg && !isError);
        }
    }

    function payload() {
        var stacks = [];
        document.querySelectorAll('#eae-stacks input[name="eae_stack[]"]:checked').forEach(function (n) {
            if (n.value) {
                stacks.push(n.value);
            }
        });
        var pid = 0;
        if ($('post_ID') && $('post_ID').value) {
            pid = parseInt($('post_ID').value, 10) || 0;
        }
        if (!pid) {
            pid = cfg().postId || 0;
        }
        var topic = ($('eae-topic') && $('eae-topic').value) ? $('eae-topic').value.trim() : '';
        return {
            topic: topic,
            context: ($('eae-context') && $('eae-context').value) || '',
            experience: ($('eae-experience') && $('eae-experience').value) || '',
            teach: ($('eae-teach') && $('eae-teach').value) || '',
            postId: pid,
            stacks: stacks
        };
    }

    function postJson(url, body) {
        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': cfg().nonce || ''
            },
            body: JSON.stringify(body)
        }).then(function (res) {
            return res.text().then(function (text) {
                var data = null;
                try {
                    data = text ? JSON.parse(text) : null;
                } catch (e) {
                    data = null;
                }
                return { ok: res.ok, data: data, status: res.status };
            });
        });
    }

    function insertClassic(html) {
        if (!html) {
            return false;
        }
        if (typeof switchEditors !== 'undefined') {
            try {
                switchEditors.go('content', 'tmce');
            } catch (e1) {}
        }
        var ed = typeof window.tinymce !== 'undefined' ? window.tinymce.get('content') : null;
        if (ed && typeof ed.setContent === 'function') {
            ed.setContent(html);
            ed.save();
        }
        var ta = $('content');
        if (ta) {
            ta.value = html;
        }
        return !!(ed || ta);
    }

    function syncQuizField(quizText) {
        if (!quizText) {
            return;
        }
        var field = $('ensor_quiz');
        if (field) {
            field.value = quizText;
        }
    }

    function onCopyPrompt(ev) {
        if (ev) {
            ev.preventDefault();
            ev.stopPropagation();
        }
        var c = cfg();
        var p = payload();
        if (!p.topic) {
            setStatus((c.defaultMessages && c.defaultMessages.missingTopic) || 'Escribe el tema.', true);
            return;
        }
        if (!c.buildPromptUrl) {
            setStatus('Recarga la página.', true);
            return;
        }
        var btn = $('eae-copy-prompt');
        if (btn) {
            btn.disabled = true;
        }
        postJson(c.buildPromptUrl, p)
            .then(function (r) {
                if (!r.ok || !r.data || !r.data.prompt) {
                    throw new Error((r.data && r.data.message) || 'Error ' + r.status);
                }
                var preview = $('eae-prompt-preview');
                var wrap = $('eae-prompt-preview-wrap');
                if (preview) {
                    preview.value = r.data.prompt;
                }
                if (wrap) {
                    wrap.hidden = false;
                }
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    return navigator.clipboard.writeText(r.data.prompt);
                }
            })
            .then(function () {
                setStatus((c.defaultMessages && c.defaultMessages.promptCopied) || 'Prompt copiado.', false);
            })
            .catch(function (err) {
                setStatus(err && err.message ? err.message : 'Error al copiar prompt.', true);
            })
            .finally(function () {
                if (btn) {
                    btn.disabled = false;
                }
            });
    }

    function onImportHtml(ev) {
        if (ev) {
            ev.preventDefault();
            ev.stopPropagation();
        }
        var c = cfg();
        var p = payload();
        var html = ($('eae-html-paste') && $('eae-html-paste').value) ? $('eae-html-paste').value.trim() : '';
        if (!p.topic) {
            setStatus((c.defaultMessages && c.defaultMessages.missingTopic) || 'Escribe el tema.', true);
            return;
        }
        if (!html) {
            setStatus((c.defaultMessages && c.defaultMessages.missingHtml) || 'Pega el HTML.', true);
            return;
        }
        if (!c.importHtmlUrl) {
            setStatus('Recarga la página.', true);
            return;
        }
        p.html = html;
        var btn = $('eae-import-html');
        if (btn) {
            btn.disabled = true;
        }
        postJson(c.importHtmlUrl, p)
            .then(function (r) {
                if (!r.ok || !r.data || !r.data.ok) {
                    throw new Error((r.data && r.data.message) || 'Error ' + r.status);
                }
                if (!insertClassic(r.data.html || r.data.blockContent || '')) {
                    throw new Error('No se pudo insertar en el editor.');
                }
                syncQuizField(r.data.quizText || '');
                setStatus((c.defaultMessages && c.defaultMessages.importSuccess) || 'Insertado.', false);
            })
            .catch(function (err) {
                setStatus(err && err.message ? err.message : 'Error al insertar.', true);
            })
            .finally(function () {
                if (btn) {
                    btn.disabled = false;
                }
            });
    }

    function bind(id, fn) {
        var el = $(id);
        if (!el || el.dataset.eaeInline === '1') {
            return;
        }
        el.dataset.eaeInline = '1';
        el.addEventListener('click', fn);
    }

    bind('eae-copy-prompt', onCopyPrompt);
    bind('eae-import-html', onImportHtml);

    document.addEventListener('click', function (e) {
        var t = e.target;
        if (!t || !t.id) {
            return;
        }
        if (t.id === 'eae-copy-prompt') {
            onCopyPrompt(e);
        }
        if (t.id === 'eae-import-html') {
            onImportHtml(e);
        }
    });
})();
</script>
    <?php
}

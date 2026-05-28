<?php
/**
 * Script inline del botón GENERAR (no depende de JS externo optimizado).
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

    function topicValue() {
        var t = ($('eae-topic') && $('eae-topic').value) ? $('eae-topic').value.trim() : '';
        if (t) {
            return t;
        }
        var c = ($('eae-context') && $('eae-context').value) ? $('eae-context').value.trim() : '';
        return c ? c.slice(0, 220) : '';
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
        return {
            topic: topicValue(),
            context: ($('eae-context') && $('eae-context').value) || '',
            experience: ($('eae-experience') && $('eae-experience').value) || '',
            teach: ($('eae-teach') && $('eae-teach').value) || '',
            postId: pid,
            stacks: stacks
        };
    }

    function isClassicEditor() {
        return !!($('postdivrich') || ($('content') && $('content').tagName === 'TEXTAREA'));
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
        }
        var ta = $('content');
        if (ta) {
            ta.value = html;
        }
        if (ed && typeof ed.save === 'function') {
            ed.save();
        }
        var box = $('postdivrich');
        if (box && box.scrollIntoView) {
            setTimeout(function () {
                box.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 200);
        }
        return !!(ed || ta);
    }

    function insertBlocks(markup) {
        if (!markup || !window.wp || !window.wp.blocks || !window.wp.data) {
            return false;
        }
        try {
            var blocks = window.wp.blocks.parse(markup);
            if (!blocks || !blocks.length) {
                blocks = window.wp.blocks.rawHandler({ HTML: markup });
            }
            var d = window.wp.data.dispatch('core/block-editor');
            if (d && d.resetBlocks) {
                d.resetBlocks(blocks);
                return true;
            }
        } catch (e2) {
            console.error('EAE blocks', e2);
        }
        return false;
    }

    function insertContent(html, blockContent) {
        if (cfg().isBlockEditor && !isClassicEditor()) {
            if (insertBlocks(blockContent || html)) {
                return true;
            }
        }
        return insertClassic(html || blockContent || '');
    }

    function syncQuizField(quizText) {
        if (!quizText) {
            return;
        }
        var field = $('ensor_quiz');
        if (field) {
            field.value = quizText;
            field.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }

    function parseRes(res) {
        return res.text().then(function (text) {
            var data = null;
            try {
                data = text ? JSON.parse(text) : null;
            } catch (e3) {
                data = null;
            }
            return { ok: res.ok, data: data, status: res.status };
        });
    }

    function onGenerate(ev) {
        if (ev) {
            ev.preventDefault();
            ev.stopPropagation();
        }
        var c = cfg();
        var btn = $('eae-generate');
        var p = payload();

        if (!p.topic) {
            setStatus((c.defaultMessages && c.defaultMessages.missingTopic) || 'Escribe el tema del LOG.', true);
            return;
        }
        if (!c.apiConfigured) {
            setStatus((c.defaultMessages && c.defaultMessages.missingApiKey) || 'Configura la API key en Ajustes.', true);
            return;
        }
        if (!c.restUrl) {
            setStatus('No se cargó la configuración. Recarga la página.', true);
            return;
        }

        if (btn) {
            if (!btn.dataset.eaeLabel) {
                btn.dataset.eaeLabel = btn.textContent || '';
            }
            btn.disabled = true;
            btn.textContent = (c.defaultMessages && c.defaultMessages.working) || 'Generando…';
        }
        setStatus((c.defaultMessages && c.defaultMessages.working) || 'Generando…', false);

        fetch(c.restUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': c.nonce || ''
            },
            body: JSON.stringify(p)
        })
            .then(parseRes)
            .then(function (r) {
                if (!r.ok || !r.data || !r.data.ok) {
                    var m = (r.data && (r.data.error || r.data.message)) || ('Error ' + r.status);
                    throw new Error(m);
                }
                var html = r.data.html || '';
                var blockContent = r.data.blockContent || '';
                if (!insertContent(html, blockContent)) {
                    if (r.data.sync && r.data.sync.savedPost) {
                        setStatus(
                            (c.defaultMessages && c.defaultMessages.savedReload) ||
                                'LOG guardado. Recarga para verlo en el editor.',
                            false
                        );
                        return;
                    }
                    throw new Error('No se pudo insertar en el editor.');
                }
                syncQuizField(r.data.quizText || '');
                setStatus((c.defaultMessages && c.defaultMessages.success) || 'LOG generado.', false);
            })
            .catch(function (err) {
                setStatus(err && err.message ? err.message : 'No se pudo generar.', true);
            })
            .finally(function () {
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = btn.dataset.eaeLabel || 'GENERAR LOG ENSORLOGS';
                }
            });
    }

    function bind() {
        var btn = $('eae-generate');
        if (!btn || btn.dataset.eaeInline === '1') {
            return;
        }
        btn.dataset.eaeInline = '1';
        btn.setAttribute('type', 'button');
        btn.addEventListener('click', onGenerate);
    }

    bind();
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bind);
    }
    document.addEventListener('click', function (e) {
        var t = e.target;
        if (t && t.id === 'eae-generate') {
            onGenerate(e);
        }
    });
})();
</script>
    <?php
}

/*!
 * Ensorlogs · Crear y traducir logs vinculados (ES / EN).
 */
(function ($) {
    'use strict';

    function msg(key, fallback) {
        return (window.EnsorArticleTranslations && EnsorArticleTranslations.i18n && EnsorArticleTranslations.i18n[key]) || fallback;
    }

    function post(action, data, $btn) {
        var cfg = window.EnsorArticleTranslations || {};
        if (!cfg.ajaxUrl || !cfg.nonce || !cfg.postId) {
            return;
        }
        var label = $btn.text();
        $btn.prop('disabled', true).text(msg('working', 'Procesando…'));

        return $.post(cfg.ajaxUrl, $.extend({
            action: action,
            nonce: cfg.nonce,
            post_id: cfg.postId
        }, data || {}))
            .always(function () {
                $btn.prop('disabled', false).text(label);
            });
    }

    $(function () {
        $('#ensor-create-translation').on('click', function () {
            var $btn = $(this);
            var targetLang = $btn.data('target-lang') || 'en';
            post('ensorlogs_create_article_translation', { target_lang: targetLang }, $btn)
                .done(function (res) {
                    if (res && res.success && res.data && res.data.editUrl) {
                        window.location.href = res.data.editUrl;
                        return;
                    }
                    window.alert((res && res.data && res.data.message) || msg('createFail', 'No se pudo crear la traducción.'));
                })
                .fail(function () {
                    window.alert(msg('createFail', 'No se pudo crear la traducción.'));
                });
        });

        $('#ensor-auto-translate').on('click', function () {
            var $btn = $(this);
            if (!window.confirm('¿Sobrescribir título, extracto y contenido de la traducción vinculada con una traducción automática? Podrás corregirla después.')) {
                return;
            }
            post('ensorlogs_auto_translate_article', {}, $btn)
                .done(function (res) {
                    if (res && res.success) {
                        if (res.data && res.data.editUrl) {
                            window.location.href = res.data.editUrl;
                        } else {
                            window.location.reload();
                        }
                        return;
                    }
                    window.alert((res && res.data && res.data.message) || msg('translateFail', 'No se pudo traducir automáticamente.'));
                })
                .fail(function () {
                    window.alert(msg('translateFail', 'No se pudo traducir automáticamente.'));
                });
        });

        $('#ensor-unlink-translation').on('click', function () {
            var $cb = $('#ensor_unlink_translation');
            if ($cb.length) {
                $cb.prop('checked', true);
            }
        });
    });
}(jQuery));

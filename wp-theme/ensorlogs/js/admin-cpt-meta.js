/**
 * Biblioteca de medios y vista previa para campos de imagen en meta de artículos/proyectos.
 */
(function ($) {
    'use strict';

    function themeBase() {
        if (typeof ensorAdminCpt !== 'undefined' && ensorAdminCpt.themeUri) {
            return String(ensorAdminCpt.themeUri).replace(/\/?$/, '/');
        }
        return '';
    }

    function previewSrc(raw) {
        var url = String(raw || '').trim();
        if (!url) {
            return '';
        }
        if (/^https?:\/\//i.test(url)) {
            return url;
        }
        if (/^assets\//.test(url)) {
            var base = themeBase();
            if (base) {
                return base + url.replace(/^\/+/, '');
            }
        }
        return '';
    }

    function setPreview($input, src) {
        var $pv = $input.closest('.ensor-cpt-meta__row').find('.ensor-cpt-meta__preview');
        if (!$pv.length) {
            return;
        }
        if (src) {
            $pv.html('<img src="' + String(src).replace(/"/g, '') + '" alt="">');
        } else {
            $pv.empty();
        }
    }

    $(document).on('click', '.ensor-cpt-pick-media', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var targetId = $btn.data('target');
        var $input = $('#' + targetId);
        if (!$input.length || typeof wp === 'undefined' || !wp.media) {
            return;
        }
        var libraryType = String($btn.data('mime') || 'image');
        var frame = wp.media({
            title: $btn.data('title') || 'Elegir archivo',
            button: { text: $btn.data('button') || 'Usar este archivo' },
            library: { type: libraryType },
            multiple: false
        });
        frame.on('select', function () {
            var att = frame.state().get('selection').first().toJSON();
            var url = att.url || '';
            $input.val(url).trigger('change');
            setPreview($input, previewSrc(url));
        });
        frame.open();
    });

    $(document).on('change input', '.ensor-cpt-meta__img-field', function () {
        var $in = $(this);
        setPreview($in, previewSrc($in.val()));
    });
})(jQuery);

/**
 * Biblioteca de medios, vista previa de imagen y panel de audio del log (podcast).
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
        var $pv = $input.closest('.ensor-cpt-meta__row').find('.ensor-cpt-meta__preview').not('.ensor-cpt-meta__preview--audio');
        if (!$pv.length) {
            return;
        }
        if (src) {
            $pv.html('<img src="' + String(src).replace(/"/g, '') + '" alt="">');
        } else {
            $pv.empty();
        }
    }

    function setPodcastAudioPreview($srcInput) {
        var $row = $srcInput.closest('.ensor-cpt-meta__row');
        var $pv = $row.find('.ensor-cpt-meta__preview--audio');
        if (!$pv.length) {
            return;
        }
        var u = String($srcInput.val() || '').trim();
        if (u && /^https?:\/\//i.test(u)) {
            $pv.empty().append(
                $('<audio>', { controls: true, preload: 'none' }).attr('src', u)
            );
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
            var attachField = $btn.data('attach-target');
            if (attachField) {
                var $af = $('#' + attachField);
                if ($af.length) {
                    $af.val(att.id ? String(att.id) : '');
                }
            }
            if (targetId === 'ensor_podcast_src') {
                setPodcastAudioPreview($input);
            }
        });
        frame.open();
    });

    $(document).on('click', '.ensor-cpt-clear-podcast-audio', function (e) {
        e.preventDefault();
        var $w = $(this).closest('.ensor-cpt-meta--podcast');
        $w.find('#ensor_podcast_src').val('').trigger('change');
        $w.find('#ensor_podcast_attachment_id').val('');
        $w.find('.ensor-cpt-meta__preview--audio').empty();
    });

    $(document).on('change input', '.ensor-cpt-meta__img-field', function () {
        var $in = $(this);
        setPreview($in, previewSrc($in.val()));
    });

    $(document).on('change input', '#ensor_podcast_src', function () {
        var $in = $(this);
        $('#ensor_podcast_attachment_id').val('');
        setPodcastAudioPreview($in);
    });
})(jQuery);

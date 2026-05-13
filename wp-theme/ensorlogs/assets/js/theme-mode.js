/**
 * =============================================================================
 * Modo claro / oscuro — Ensorlogs
 * =============================================================================
 *
 * Sitio estático y WordPress: jQuery puede ir en noConflict (sin ``$`` global).
 * Envolvemos en ``(function ($) { … })(jQuery);`` para que funcione en ambos.
 *
 * Parámetro de URL ``?version=dark|light`` (opcional)
 */
(function ($) {
    'use strict';

    function setDarkTheme() {
        document.documentElement.classList.add('dark');
        try {
            localStorage.theme = 'dark';
        } catch (e) {}
        $('.btn_theme_switch').removeClass('btn-light').addClass('btn-dark');
    }

    function setLightTheme() {
        document.documentElement.classList.remove('dark');
        try {
            localStorage.theme = 'light';
        } catch (e) {}
        $('.btn_theme_switch').removeClass('btn-dark').addClass('btn-light');
    }

    function syncButtonToStoredTheme() {
        var isDark = false;
        try {
            isDark = localStorage.theme === 'dark';
        } catch (e) {
            isDark = false;
        }
        if (isDark) {
            document.documentElement.classList.add('dark');
            $('.btn_theme_switch').removeClass('btn-light').addClass('btn-dark');
        } else {
            document.documentElement.classList.remove('dark');
            $('.btn_theme_switch').removeClass('btn-dark').addClass('btn-light');
        }
    }

    function getUrlParameter(sParam) {
        var sPageURL = window.location.search.substring(1),
            sURLVariables = sPageURL.split('&'),
            sParameterName,
            i;

        for (i = 0; i < sURLVariables.length; i++) {
            sParameterName = sURLVariables[i].split('=');

            if (sParameterName[0] === sParam) {
                if (sParameterName[1] === undefined) {
                    return true;
                }
                try {
                    return decodeURIComponent(sParameterName[1].replace(/\+/g, ' '));
                } catch (e) {
                    return false;
                }
            }
        }
        return false;
    }

    $(function () {
        var version = getUrlParameter('version');
        if (version === 'dark') {
            setDarkTheme();
        } else if (version === 'light') {
            setLightTheme();
        } else {
            syncButtonToStoredTheme();
        }
        $(document).on('click', '.btn_theme_switch', function () {
            var isDark = document.documentElement.classList.contains('dark');
            if (isDark) {
                setLightTheme();
            } else {
                setDarkTheme();
            }
        });
    });
})(jQuery);

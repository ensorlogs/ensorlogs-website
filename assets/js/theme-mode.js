/**
 * =============================================================================
 * Modo claro / oscuro — Ensorlogs
 * =============================================================================
 *
 * Idea central
 * -------------
 * Guardamos la preferencia en ``localStorage`` bajo la clave ``theme``:
 *   - Valor ``'light'``  → el usuario eligió explícitamente modo claro.
 *   - Cualquier otro caso → tratamos como oscuro (incluye primera visita).
 *
 * La clase ``dark`` en ``<html>`` es lo que activa los estilos “oscuros” de Tailwind
 * (prefijo ``dark:`` en las clases del HTML).
 *
 * Por qué hay TAMBIÉN un script inline en el ``<head>`` de cada página
 * --------------------------------------------------------------------
 * Ese mini script corre **antes** de que el body pinte mucho contenido y evita
 * un flash de fondo claro. Este archivo, en cambio, sincroniza el **botón**
 * del interruptor (clases ``btn-light`` / ``btn-dark``) y escucha clics.
 *
 * Parámetro de URL ``?version=dark|light`` (opcional)
 * ----------------------------------------------------
 * Útil para compartir un enlace forzando un modo (demos, capturas).
 *
 * Dependencia: jQuery (``$``) — debe cargarse antes que este script en el HTML.
 */
(function syncThemeOnLoad() {
    if (typeof jQuery === 'undefined') {
        return;
    }
    if (localStorage.theme !== 'light') {
        document.documentElement.classList.add('dark');
        $('.btn_theme_switch').removeClass('btn-light').addClass('btn-dark');
    } else {
        document.documentElement.classList.remove('dark');
        $('.btn_theme_switch').removeClass('btn-dark').addClass('btn-light');
    }
})();

function setDarkTheme() {
    document.documentElement.classList.add('dark');
    localStorage.theme = 'dark';
    $('.btn_theme_switch').removeClass('btn-light').addClass('btn-dark');
}

function setLightTheme() {
    document.documentElement.classList.remove('dark');
    localStorage.theme = 'light';
    $('.btn_theme_switch').removeClass('btn-dark').addClass('btn-light');
}

/** Lee ``?nombre=valor`` de la URL de forma manual (sin URLSearchParams en IE antiguo). */
var getUrlParameter = function getUrlParameter(sParam) {
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
                /* decodeURIComponent puede lanzar URIError si el % está mal formado */
                return decodeURIComponent(sParameterName[1].replace(/\+/g, ' '));
            } catch (e) {
                return false;
            }
        }
    }
    return false;
};

var version = getUrlParameter('version');
if (version) {
    if (version === 'dark') {
        setDarkTheme();
    } else if (version === 'light') {
        setLightTheme();
    }
}

$('.btn_theme_switch').on('click', function () {
    if (localStorage.theme === 'light') {
        setDarkTheme();
    } else {
        setLightTheme();
    }
});

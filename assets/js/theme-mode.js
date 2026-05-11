/** Tema: por defecto oscuro; solo modo claro si el usuario eligió explícitamente `light`. */
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

var getUrlParameter = function getUrlParameter(sParam) {
    var sPageURL = window.location.search.substring(1),
        sURLVariables = sPageURL.split('&'),
        sParameterName,
        i;

    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');

        if (sParameterName[0] === sParam) {
            return sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
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

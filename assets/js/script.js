/*------------------------------------------------------------------
[Custom Script JS]

Project Name  :     Ensorlogs Web (ensorlogsweb)
Version       :     1.0.0
Last Update   :     19 May 2024
Author	      :	    Themearray
Support	      :	    themearray@gmail.com
------------------------------------------------------------------*/

(function($) {
'use strict';

    /* Mensaje aleatorio bajo el logo del preloader (estilo geek) */
    (function preloaderRandomMessage() {
        var messages = [
            'Que la fuerza te acompañe',
            'Compilando el universo…',
            'sudo make me a sandwich',
            'There is no place like 127.0.0.1',
            'La respuesta es 42. ¿Cuál era la pregunta?',
            'rm -rf /  — mejor no, ¿no?',
            'git commit -m "arreglo_final_definitivo_v3"',
            'No es un bug, es una feature no documentada',
            'En producción sí confío… en los backups',
            'Cargando píxeles con amor y cafeína',
            'Hello, World! (pero en serio)',
            '*ping* *ping* ¿Hay alguien en el servidor?',
            'Alineando los satélites…',
            'Importando conocimiento desde la nube',
            'Respawning en 3… 2…',
            'May the source be with you',
            'Estado: pensando en binario',
            'Optimizando la matriz…',
            'wget la paciencia — conexión lenta',
            'chmod +x ./vida && ./vida'
        ];
        var el = document.querySelector('.ensor-preloader-loading');
        if (!el || !messages.length) {
            return;
        }
        var i = Math.floor(Math.random() * messages.length);
        el.textContent = messages[i];
    })();

    /* ============================================================ */
    /* PRELOADER START
    /* ============================================================ */
    (function preloaderHide() {
        var done = false;
        /* Tiempo mínimo visible: animación del logo + lectura del mensaje geek */
        var MIN_VISIBLE_MS = 1200;
        var FADE_OUT_MS = 500;
        var t0 =
            typeof performance !== 'undefined' &&
            performance.timing &&
            performance.timing.navigationStart
                ? performance.timing.navigationStart
                : Date.now();

        function hide() {
            if (done) {
                return;
            }
            var elapsed = Date.now() - t0;
            var wait = Math.max(0, MIN_VISIBLE_MS - elapsed);
            setTimeout(function () {
                if (done) {
                    return;
                }
                done = true;
                $('.preloader').fadeOut(FADE_OUT_MS);
            }, wait);
        }
        $(window).on('load', hide);
        /* file://, iframes o recursos bloqueados: load puede no dispararse */
        $(function () {
            setTimeout(hide, 14000);
        });
    })();
    /* Preloader End */



    /* ============================================================ */
    /* Onpage scrolling START
    /* ============================================================ */
    $('a:not([href="#"])').click(function() {
        if (location.pathname.replace(/^\//,'') === this.pathname.replace(/^\//,'') && location.hostname === this.hostname) {
            var target = $(this.hash);
            target = target.length ? target : $('[name=' + this.hash.slice(1) +']');
            if (target.length) {
                $('html, body').animate({
                    scrollTop: target.offset().top - 80
                }, 1000);
                return false;
            }
        }
    });
    /* Onpage Scrolling End*/
    /* ============================================================ */
    /* MOBILE MENU START
    /* ============================================================ */
    function mobile_menu(selector, actionSelector) {
        var mobile_menu = $(selector);
        mobile_menu.on('click', function() {
            $(selector).toggleClass('is-menu-open');
        });

        var hamburgerbtn = $(selector);
        hamburgerbtn.on('click', function() {
            $(actionSelector).toggleClass('is-menu-open');
        });

        $(document).on('click', function(e) {
            var selectorType = $(actionSelector).add(mobile_menu);
            if (
                selectorType.is(e.target) !== true &&
                selectorType.has(e.target).length === 0
            ) {
                $(actionSelector).removeClass('is-menu-open');
                $(selector).removeClass('is-menu-open');
            }
        });
        $('.mobile_menu .main-menu a, .mobile_overlay').on('click', function(e) {
            $(actionSelector).removeClass('is-menu-open');
            $(selector).removeClass('is-menu-open');
        });
    }
    mobile_menu(
        '.menu_toggle, .close_menu',
        '.mobile_menu, .mobile_overlay'
    );
    /* Mobile menu End */

    /* ============================================================ */
    /* StickyHeader
    /* ============================================================ */
    var fixed_top = $("header");
    $(window).on('scroll', function () {
        if ($(this).scrollTop() > 100) {
            fixed_top.addClass("is-sticky");
        } else {
            fixed_top.removeClass("is-sticky");
        }
    });

    /* ============================================================ */
    /* Servic Slider start
    /* ============================================================ */
    if ($('.testimonial.swiper').length) {
        new Swiper('.testimonial.swiper', {
            spaceBetween: 25,
            slidesPerView: 1,
            loop: 1,
            speed: 800,
            autoplay: {
                delay: 5000,
            },
            breakpoints: {
                768: {
                    slidesPerView: 2,
                },
                1200: {
                    slidesPerView: 3,
                },
            },
            navigation: {
                nextEl: '.serviceSlideNav .button-next',
                prevEl: '.serviceSlideNav .button-prev',
            },
            pagination: {
                el: ".serviceSlider .swiper-pagination",
                type: "progressbar",
            },
        });
    }
    // Service Slider End

    AOS.init({
        duration: 1500,
        once: true,
    })

    /* ============================================================ */
    /* Scroll Top
    /* ============================================================ */
    var $scrolltop = $('#scroll-top');
    $(window).on('scroll', function () {
        if ($(this).scrollTop() > $(this).height()) {
            $scrolltop.addClass('btn-show').removeClass('btn-hide');
        } else {
            $scrolltop.addClass('btn-hide').removeClass('btn-show');
        }
    });
    $("a[href='#top']").on('click', function () {
        $('html, body').animate( {
                scrollTop: 0,
        }, 1000);
        return false;
    });

    /* ============================================================ */
    /* Hover Tilt effect of widget
    /* ============================================================ */
    $('.widget').tilt({
        maxTilt: 15,
        perspective: 1500,
        easing: "cubic-bezier(.03,.98,.52,.99)",
        speed: 1200,
        scale: 1.03
    });


})(jQuery);
// jQuery Ended
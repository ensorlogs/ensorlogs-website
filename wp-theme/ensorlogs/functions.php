<?php
/**
 * Ensorlogs theme — migración desde HTML estático.
 *
 * @package Ensorlogs
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ENSORLOGS_THEME_VERSION', '1.10.33');

require_once get_template_directory() . '/inc/helpers.php';
require_once get_template_directory() . '/inc/block-content.php';
require_once get_template_directory() . '/inc/stacks.php';
require_once get_template_directory() . '/inc/post-types.php';
require_once get_template_directory() . '/inc/cpt-admin-ui.php';
require_once get_template_directory() . '/inc/seed.php';
require_once get_template_directory() . '/inc/seo-performance.php';
require_once get_template_directory() . '/inc/security.php';
require_once get_template_directory() . '/inc/newsletter.php';
require_once get_template_directory() . '/inc/customizer.php';
require_once get_template_directory() . '/inc/updater.php';
require_once get_template_directory() . '/inc/reader.php';
require_once get_template_directory() . '/inc/block-patterns.php';
require_once get_template_directory() . '/inc/i18n.php';
require_once get_template_directory() . '/inc/legal-i18n.php';
require_once get_template_directory() . '/inc/article-lang.php';
require_once get_template_directory() . '/inc/article-translations.php';

// Ensorlogs AI Engine (incluido dentro del tema para no depender de wp-content/plugins).
$ensor_ai_engine = get_template_directory() . '/plugins/ensorlogs-ai-engine/ensorlogs-ai-engine.php';
if (is_readable($ensor_ai_engine)) {
    require_once $ensor_ai_engine;
}
require_once get_template_directory() . '/inc/project-content-i18n.php';

add_action(
    'after_switch_theme',
    static function (): void {
        if (get_template() === 'ensorlogs') {
            flush_rewrite_rules(false);
        }
    }
);

add_action('wp_head', static function (): void {
    $icon = get_template_directory_uri() . '/assets/img/favicon.png';
    echo '<link rel="icon" href="' . esc_url($icon) . '" sizes="any">' . "\n";

    $cookies_page = get_page_by_path('legal/cookies');
    if (!$cookies_page) {
        $cookies_page = get_page_by_path('cookies');
    }
    $cookies_url = $cookies_page ? get_permalink($cookies_page) : home_url('/legal/cookies/');
    if (function_exists('ensorlogs_current_lang') && ensorlogs_current_lang() === 'en' && function_exists('ensorlogs_lang_url')) {
        $cookies_url = ensorlogs_lang_url('/legal/cookies/');
    }
    echo '<meta name="ensor-cookies-url" content="' . esc_url($cookies_url) . '">' . "\n";

    $blog_page = get_page_by_path('blog');
    $blog_url  = $blog_page ? get_permalink($blog_page) : home_url('/blog/');
    echo '<meta name="ensor-blog-url" content="' . esc_url($blog_url) . '">' . "\n";
}, 3);

/**
 * Viewport al final de wp_head para que gane sobre plugins/duplicados (texto diminuto / layout “desktop” en móvil).
 */
add_action(
    'wp_head',
    static function (): void {
        echo '<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">' . "\n";
    },
    99999
);

add_action('after_setup_theme', static function (): void {
    load_theme_textdomain('ensorlogs', get_template_directory() . '/languages');
    add_theme_support('automatic-feed-links');
    add_theme_support('responsive-embeds');
    add_theme_support('wp-block-styles');
    add_theme_support('align-wide');
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    set_post_thumbnail_size(900, 506, true);
    add_image_size('ensor-card', 900, 506, true);
    add_image_size('ensor-card-2x', 1800, 1012, true);
    add_image_size('ensor-hero', 1600, 900, true);
    add_theme_support(
        'html5',
        array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script')
    );
    add_theme_support('custom-logo', array('height' => 240, 'width' => 240, 'flex-height' => true, 'flex-width' => true));
    register_nav_menus(
        array(
            'primary' => __('Menú principal (opcional)', 'ensorlogs'),
        )
    );
});

/**
 * Devuelve la URL de la hoja de estilos de fuentes (o cadena vacía si se
 * usan fuentes locales). Por defecto usa Bunny Fonts (fonts.bunny.net),
 * que es un mirror privacy-friendly de Google Fonts (no recoge IPs) para
 * cumplir RGPD sin renunciar a cache de CDN.
 *
 * Si quieres self-hosting total: coloca un archivo
 * `assets/fonts/bricolage/bricolage.css` en el tema con `@font-face` apuntando
 * a los `.woff2` locales y se usará ese automáticamente.
 */
function ensorlogs_fonts_url(): string
{
    $local = get_template_directory() . '/assets/fonts/bricolage/bricolage.css';
    if (is_readable($local)) {
        return trailingslashit(get_template_directory_uri()) . 'assets/fonts/bricolage/bricolage.css';
    }
    return (string) apply_filters(
        'ensorlogs_fonts_url',
        'https://fonts.bunny.net/css?family=bricolage-grotesque:200,300,400,500,600,700,800&display=swap'
    );
}

add_action('wp_enqueue_scripts', static function (): void {
    $uri = get_template_directory_uri();
    $v   = ENSORLOGS_THEME_VERSION;

    $fonts_url = ensorlogs_fonts_url();
    if ($fonts_url !== '') {
        wp_enqueue_style('ensorlogs-fonts', $fonts_url, array(), null);
    }
    wp_enqueue_style('ensorlogs-fa', $uri . '/assets/css/fontAwesome5Pro.css', array(), $v);
    wp_enqueue_style('ensorlogs-style', $uri . '/assets/css/style.min.css', array('ensorlogs-fa'), $v);
    wp_enqueue_style('ensorlogs-brand', $uri . '/assets/css/ensor-brand.css', array('ensorlogs-style'), $v);

    // Globales site-wide (accesibilidad + cookies + quiz/contador)
    wp_enqueue_style('ensorlogs-a11y', $uri . '/assets/css/ensor-a11y.css', array('ensorlogs-brand'), $v);
    wp_enqueue_style('ensorlogs-lang', $uri . '/assets/css/ensor-lang-switch.css', array('ensorlogs-a11y'), $v);
    wp_enqueue_style('ensorlogs-cookies', $uri . '/assets/css/ensor-cookies.css', array('ensorlogs-brand'), $v);
    wp_enqueue_style('ensorlogs-quiz', $uri . '/assets/css/ensor-quiz.css', array('ensorlogs-brand'), $v);

    // Páginas legales: solo se carga el CSS branded cuando se renderizan.
    if (is_page() && is_page_template('page-legal.php')) {
        wp_enqueue_style('ensorlogs-legal', $uri . '/assets/css/ensor-legal.css', array('ensorlogs-brand'), $v);
    }

    wp_enqueue_script('jquery');
    wp_enqueue_script('ensorlogs-waypoints', $uri . '/assets/js/waypoints.min.js', array('jquery'), $v, true);
    wp_enqueue_script('ensorlogs-tw', $uri . '/assets/js/tw-elements.umd.min.js', array('jquery'), $v, true);
    wp_enqueue_script('ensorlogs-aos', $uri . '/assets/js/aos.js', array('jquery'), $v, true);
    wp_enqueue_script('ensorlogs-tilt', $uri . '/assets/js/tilt.jquery.min.js', array('jquery'), $v, true);
    wp_enqueue_script('ensorlogs-script', $uri . '/assets/js/script.js', array('jquery'), $v, true);
    wp_enqueue_script('ensorlogs-nav', $uri . '/assets/js/nav-volver.js', array('jquery'), $v, true);
    $home_path = wp_parse_url(home_url('/'), PHP_URL_PATH);
    if (!is_string($home_path) || $home_path === '') {
        $home_path = '/';
    }
    wp_localize_script(
        'ensorlogs-nav',
        'ENSORLOGS',
        array(
            'homePath' => $home_path,
        )
    );
    wp_enqueue_script('ensorlogs-theme-mode', $uri . '/assets/js/theme-mode.js', array('jquery'), $v, true);

    // Site-wide accesibilidad + cookies + quiz (sin dependencias jQuery, defer)
    wp_enqueue_script('ensorlogs-lang', $uri . '/assets/js/ensor-lang-switch.js', array(), $v, true);
    wp_enqueue_script('ensorlogs-a11y', $uri . '/assets/js/ensor-a11y.js', array(), $v, true);
    wp_enqueue_script('ensorlogs-cookies', $uri . '/assets/js/ensor-cookies.js', array(), $v, true);
    wp_enqueue_script('ensorlogs-quiz', $uri . '/assets/js/ensor-quiz.js', array(), $v, true);

    if (function_exists('ensorlogs_newsletter_enabled') && ensorlogs_newsletter_enabled()) {
        wp_enqueue_style(
            'ensorlogs-newsletter',
            $uri . '/assets/css/ensor-newsletter.css',
            array('ensorlogs-brand'),
            $v
        );
        wp_enqueue_script(
            'ensorlogs-newsletter',
            $uri . '/assets/js/ensor-newsletter.js',
            array(),
            $v,
            true
        );
        $newsletter_cfg = ensorlogs_newsletter_client_config();
        wp_localize_script('ensorlogs-newsletter', 'ensorNewsletter', $newsletter_cfg);
        wp_add_inline_script(
            'ensorlogs-newsletter',
            'window.ensorNewsletter=Object.assign(window.ensorNewsletter||{},'
            . wp_json_encode($newsletter_cfg)
            . ');',
            'before'
        );
    }

    if (is_singular(array('ensor_article', 'ensor_project'))) {
        wp_enqueue_style('ensorlogs-swiper', $uri . '/assets/css/swiper-bundle.min.css', array('ensorlogs-brand'), $v);
        wp_enqueue_script('ensorlogs-swiper', $uri . '/assets/js/swiper-bundle.min.js', array('jquery'), $v, true);
    }

    if (is_singular('ensor_article')) {
        wp_enqueue_style(
            'ensorlogs-reader',
            $uri . '/assets/css/ensor-reader.css',
            array('ensorlogs-brand'),
            $v
        );
        wp_enqueue_script(
            'ensorlogs-reader',
            $uri . '/assets/js/ensor-reader.js',
            array(),
            $v,
            true
        );
        wp_enqueue_style(
            'ensorlogs-podcast',
            $uri . '/assets/css/ensor-podcast.css',
            array('ensorlogs-reader'),
            $v
        );
        wp_enqueue_script(
            'ensorlogs-podcast',
            $uri . '/assets/js/ensor-podcast.js',
            array(),
            $v,
            true
        );
    }

    if (is_page('blog')) {
        wp_enqueue_script(
            'ensorlogs-blog-filter',
            $uri . '/js/blog-tema-filter-wp.js',
            array('jquery'),
            $v,
            true
        );
        $blog_path = wp_parse_url(trailingslashit(home_url('blog')), PHP_URL_PATH);
        if (!is_string($blog_path) || $blog_path === '') {
            $blog_path = '/blog/';
        }
        wp_add_inline_script(
            'ensorlogs-blog-filter',
            'window.ENSOR_BLOG_PATH=' . wp_json_encode($blog_path) . ';',
            'before'
        );
    }

    if (is_page('projects')) {
        wp_enqueue_script(
            'ensorlogs-projects-filter',
            $uri . '/js/projects-tema-filter-wp.js',
            array('jquery'),
            $v,
            true
        );
        $projects_path = wp_parse_url(trailingslashit(home_url('projects')), PHP_URL_PATH);
        if (!is_string($projects_path) || $projects_path === '') {
            $projects_path = '/projects/';
        }
        wp_add_inline_script(
            'ensorlogs-projects-filter',
            'window.ENSOR_PROJECTS_PATH=' . wp_json_encode($projects_path) . ';',
            'before'
        );
    }

});

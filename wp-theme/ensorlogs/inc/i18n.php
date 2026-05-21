<?php
/**
 * Idioma ES / EN (prefijo /en/ en URLs).
 *
 * @package Ensorlogs
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ENSORLOGS_LANG_QUERY_VAR', 'ensor_lang');

/**
 * Registra query var y reglas de reescritura.
 */
function ensorlogs_i18n_register(): void
{
    add_rewrite_tag('%' . ENSORLOGS_LANG_QUERY_VAR . '%', '([^&]+)');

    add_rewrite_rule('^en/?$', 'index.php?' . ENSORLOGS_LANG_QUERY_VAR . '=en', 'top');
    add_rewrite_rule('^en/legal/([^/]+)/?$', 'index.php?pagename=legal/$matches[1]&' . ENSORLOGS_LANG_QUERY_VAR . '=en', 'top');
    add_rewrite_rule('^en/([^/]+)/?$', 'index.php?pagename=$matches[1]&' . ENSORLOGS_LANG_QUERY_VAR . '=en', 'top');
}
add_action('init', 'ensorlogs_i18n_register');

/**
 * @param string[] $vars
 * @return string[]
 */
function ensorlogs_i18n_query_vars(array $vars): array
{
    $vars[] = ENSORLOGS_LANG_QUERY_VAR;
    return $vars;
}
add_filter('query_vars', 'ensorlogs_i18n_query_vars');

/**
 * Resuelve portada en /en/.
 *
 * @param WP $wp
 */
function ensorlogs_i18n_parse_request(WP $wp): void
{
    $lang = $wp->query_vars[ENSORLOGS_LANG_QUERY_VAR] ?? '';
    if ($lang !== 'en') {
        return;
    }
    if (!empty($wp->query_vars['pagename']) || !empty($wp->query_vars['page_id'])) {
        return;
    }
    $front = (int) get_option('page_on_front');
    if ($front > 0) {
        $wp->query_vars['page_id'] = $front;
        unset($wp->query_vars[ENSORLOGS_LANG_QUERY_VAR]);
        $wp->query_vars[ENSORLOGS_LANG_QUERY_VAR] = 'en';
    }
}
add_action('parse_request', 'ensorlogs_i18n_parse_request', 5);

/**
 * Idioma activo: es | en.
 */
function ensorlogs_current_lang(): string
{
    $lang = get_query_var(ENSORLOGS_LANG_QUERY_VAR);
    return $lang === 'en' ? 'en' : 'es';
}

/**
 * Traducción inline (sin .mo) para cadenas del tema.
 *
 * @param string $es Texto español.
 * @param string $en Texto inglés.
 */
function ensorlogs_t(string $es, string $en): string
{
    return ensorlogs_current_lang() === 'en' ? $en : $es;
}

/**
 * URL con prefijo /en/ si corresponde.
 *
 * @param string $path Ruta relativa, p. ej. /about/ o /contact/.
 */
function ensorlogs_lang_url(string $path = '/'): string
{
    $path = '/' . ltrim($path, '/');
    if (ensorlogs_current_lang() === 'en') {
        if ($path === '/') {
            return trailingslashit(home_url('/en'));
        }
        return home_url('/en' . $path);
    }
    return home_url($path);
}

/**
 * URL de la versión en el otro idioma (para el conmutador).
 */
function ensorlogs_lang_alternate_url(): string
{
    $lang = ensorlogs_current_lang();
    $request = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '/';
    $path    = (string) wp_parse_url($request, PHP_URL_PATH);
    if (!is_string($path) || $path === '') {
        $path = '/';
    }

    $home_path = (string) wp_parse_url(home_url('/'), PHP_URL_PATH);
    if ($home_path && str_starts_with($path, $home_path)) {
        $path = substr($path, strlen($home_path)) ?: '/';
    }
    $path = '/' . ltrim($path, '/');

    if ($lang === 'en') {
        if (str_starts_with($path, '/en')) {
            $path = substr($path, 3) ?: '/';
        }
        return home_url($path === '/' ? '/' : $path);
    }

    if ($path === '/') {
        return trailingslashit(home_url('/en'));
    }
    return home_url('/en' . $path);
}

/**
 * Meta idioma + alternativa para ensor-lang-switch.js
 */
function ensorlogs_i18n_head_meta(): void
{
    $lang = ensorlogs_current_lang();
    $alt  = ensorlogs_lang_alternate_url();
    $assets_base = trailingslashit(get_template_directory_uri()) . 'assets';
    echo '<meta name="ensor-lang" content="' . esc_attr($lang) . '">' . "\n";
    echo '<meta name="ensor-lang-alt" content="' . esc_url($alt) . '">' . "\n";
    echo '<meta name="ensor-assets-base" content="' . esc_attr($assets_base) . '">' . "\n";
}
add_action('wp_head', 'ensorlogs_i18n_head_meta', 2);

/**
 * Atributo lang en <html>.
 *
 * @param string $output
 */
function ensorlogs_i18n_language_attributes(string $output): string
{
    if (ensorlogs_current_lang() === 'en') {
        return preg_replace('/lang="[^"]*"/', 'lang="en-US"', $output) ?: 'lang="en-US"';
    }
    return $output;
}
add_filter('language_attributes', 'ensorlogs_i18n_language_attributes');

/**
 * Enlaces hreflang básicos (ES por defecto + EN) en páginas estáticas del tema.
 */
function ensorlogs_i18n_hreflang(): void
{
    if (!is_page() && !is_front_page()) {
        return;
    }
    $alt  = ensorlogs_lang_alternate_url();
    $here = get_permalink();
    if (!is_string($here) || $here === '') {
        $here = ensorlogs_current_lang() === 'en' ? trailingslashit(home_url('/en')) : home_url('/');
    }
    if (ensorlogs_current_lang() === 'en') {
        echo '<link rel="alternate" hreflang="en" href="' . esc_url($here) . '">' . "\n";
        echo '<link rel="alternate" hreflang="es" href="' . esc_url($alt) . '">' . "\n";
    } else {
        echo '<link rel="alternate" hreflang="es" href="' . esc_url($here) . '">' . "\n";
        echo '<link rel="alternate" hreflang="en" href="' . esc_url($alt) . '">' . "\n";
    }
}
add_action('wp_head', 'ensorlogs_i18n_hreflang', 4);

/**
 * Resuelve fragmento .en.fragment.html si existe.
 *
 * @param string $filename Nombre del fragmento.
 */
/**
 * Bandera del encabezado según idioma (VE = ES, US = EN).
 */
function ensorlogs_header_flag_html(): string
{
    $uri = get_template_directory_uri();
    return '<img src="' . esc_url($uri) . '/assets/img/flag-venezuela.svg" alt="Hecho desde Venezuela" width="20" height="14" loading="lazy" decoding="async" class="ensor-flag-ve inline-block align-[-2px] ml-1"/>';
}

/**
 * Tagline localizada.
 */
function ensorlogs_get_tagline_localized(): string
{
    $es = function_exists('ensorlogs_get_tagline') ? ensorlogs_get_tagline() : 'Bitácora de un geek';
    return ensorlogs_t($es, "A geek's logbook");
}

function ensorlogs_i18n_fragment_filename(string $filename): string
{
    if (ensorlogs_current_lang() !== 'en') {
        return $filename;
    }
    $en_name = preg_replace('/\.fragment\.html$/', '.en.fragment.html', $filename);
    if (!is_string($en_name)) {
        return $filename;
    }
    $en_path = get_template_directory() . '/partials/' . $en_name;
    if (is_readable($en_path)) {
        return $en_name;
    }
    return $filename;
}

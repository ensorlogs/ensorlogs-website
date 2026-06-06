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
define('ENSORLOGS_I18N_REWRITE_OPTION', 'ensorlogs_i18n_rewrite_ver');

/**
 * Ruta relativa al home de WordPress (p. ej. /en/about/).
 */
function ensorlogs_i18n_relative_path(): string
{
    $request = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '/';
    $path    = (string) wp_parse_url($request, PHP_URL_PATH);
    if ($path === '') {
        $path = '/';
    }

    $home_path = (string) wp_parse_url(home_url('/'), PHP_URL_PATH);
    $home_path = $home_path !== '' ? untrailingslashit($home_path) : '';
    if ($home_path !== '' && str_starts_with($path, $home_path)) {
        $path = substr($path, strlen($home_path)) ?: '/';
    }

    $path = '/' . ltrim($path, '/');
    return $path === '/' ? '/' : rtrim($path, '/') . '/';
}

/**
 * ¿La URL actual está bajo /en/?
 */
function ensorlogs_i18n_uri_is_en(): bool
{
    $path = ensorlogs_i18n_relative_path();
    return $path === '/en/' || str_starts_with($path, '/en/');
}

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
add_action('init', 'ensorlogs_i18n_register', 10);

/**
 * Fuerza ensor_lang=en en cualquier petición bajo /en/ (antes de la consulta).
 *
 * @param WP $wp
 */
function ensorlogs_i18n_parse_request_lang(WP $wp): void
{
    if (!ensorlogs_i18n_uri_is_en()) {
        return;
    }
    $wp->query_vars[ENSORLOGS_LANG_QUERY_VAR] = 'en';
}
add_action('parse_request', 'ensorlogs_i18n_parse_request_lang', 1);

/**
 * Evita que redirect_canonical mande /en/ → / (causa principal del “vuelve a español”).
 *
 * @param string|false $redirect_url
 * @param string       $requested_url
 * @return string|false
 */
function ensorlogs_i18n_disable_canonical_redirect($redirect_url, string $requested_url)
{
    unset($requested_url);
    if (ensorlogs_i18n_uri_is_en()) {
        return false;
    }
    if (get_query_var(ENSORLOGS_LANG_QUERY_VAR) === 'en') {
        return false;
    }
    return $redirect_url;
}
add_filter('redirect_canonical', 'ensorlogs_i18n_disable_canonical_redirect', 0, 2);

/**
 * Canonical correcto en rutas /en/.
 *
 * @param string|false $canonical_url
 */
function ensorlogs_i18n_canonical_url($canonical_url)
{
    if (!ensorlogs_i18n_uri_is_en() && get_query_var(ENSORLOGS_LANG_QUERY_VAR) !== 'en') {
        return $canonical_url;
    }
    $rel = ensorlogs_i18n_relative_path();
    if ($rel === '/') {
        return $canonical_url;
    }
    return trailingslashit(home_url($rel));
}
add_filter('wp_get_canonical_url', 'ensorlogs_i18n_canonical_url', 10, 1);

/**
 * Refresca permalinks cuando cambia la versión del tema (producción sin re-guardar enlaces).
 */
function ensorlogs_i18n_maybe_flush_rewrites(): void
{
    if (!function_exists('get_option') || !defined('ENSORLOGS_THEME_VERSION')) {
        return;
    }
    $stored = (string) get_option(ENSORLOGS_I18N_REWRITE_OPTION, '');
    if ($stored === ENSORLOGS_THEME_VERSION) {
        return;
    }
    flush_rewrite_rules(false);
    update_option(ENSORLOGS_I18N_REWRITE_OPTION, ENSORLOGS_THEME_VERSION);
}
add_action('init', 'ensorlogs_i18n_maybe_flush_rewrites', 99);

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
 * Resuelve ID de página desde ruta /en/...
 */
function ensorlogs_i18n_resolve_page_id_from_path(string $rel_path): int
{
    $rel_path = '/' . trim($rel_path, '/');
    if ($rel_path === '/en' || $rel_path === '/en/') {
        if (get_option('show_on_front') === 'page') {
            return (int) get_option('page_on_front');
        }
        return 0;
    }

    if (!str_starts_with($rel_path, '/en/')) {
        return 0;
    }

    $sub = trim(substr($rel_path, 4), '/');
    if ($sub === '') {
        return (int) get_option('page_on_front');
    }

    // Artículos CPT: lo resuelven las reglas de article-lang.php.
    if (str_starts_with($sub, 'articulos/')) {
        return 0;
    }

    $page = get_page_by_path($sub);
    return $page instanceof WP_Post ? (int) $page->ID : 0;
}

/**
 * Asigna page_id a /en/ (portada) antes de la consulta principal.
 *
 * @param array<string, mixed> $query_vars
 * @return array<string, mixed>
 */
function ensorlogs_i18n_request(array $query_vars): array
{
    $lang = isset($query_vars[ENSORLOGS_LANG_QUERY_VAR]) ? (string) $query_vars[ENSORLOGS_LANG_QUERY_VAR] : '';
    if ($lang !== 'en') {
        return $query_vars;
    }

    if (!empty($query_vars['pagename']) || !empty($query_vars['page_id']) || !empty($query_vars['name'])) {
        return $query_vars;
    }

    if (get_option('show_on_front') === 'page') {
        $front = (int) get_option('page_on_front');
        if ($front > 0) {
            $query_vars['page_id'] = $front;
        }
    }

    return $query_vars;
}
add_filter('request', 'ensorlogs_i18n_request');

/**
 * Evita 404 en /en/ si los permalinks no se regeneraron o la consulta falló.
 *
 * @param bool     $preempt
 * @param WP_Query $wp_query
 */
function ensorlogs_i18n_pre_handle_404(bool $preempt, WP_Query $wp_query): bool
{
    if ($preempt || !$wp_query->is_main_query()) {
        return $preempt;
    }

    $rel = ensorlogs_i18n_relative_path();
    if (!str_starts_with($rel, '/en')) {
        return $preempt;
    }

    $page_id = ensorlogs_i18n_resolve_page_id_from_path($rel);
    if ($page_id <= 0) {
        return $preempt;
    }

    $wp_query->query(
        array(
            'page_id'                => $page_id,
            ENSORLOGS_LANG_QUERY_VAR => 'en',
        )
    );

    $wp_query->is_404         = false;
    $wp_query->is_page        = true;
    $wp_query->is_singular    = true;
    $wp_query->is_archive     = false;
    $wp_query->is_home        = false;
    $wp_query->is_front_page  = (
        get_option('show_on_front') === 'page'
        && $page_id === (int) get_option('page_on_front')
    );

    return true;
}
add_filter('pre_handle_404', 'ensorlogs_i18n_pre_handle_404', 10, 2);

/**
 * Idioma activo: es | en.
 */
function ensorlogs_current_lang(): string
{
    $lang = get_query_var(ENSORLOGS_LANG_QUERY_VAR);
    if ($lang === 'en') {
        return 'en';
    }
    if (ensorlogs_i18n_uri_is_en()) {
        return 'en';
    }
    return 'es';
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
    if (is_singular('ensor_article') && function_exists('ensorlogs_get_article_peer_post')) {
        $peer = ensorlogs_get_article_peer_post((int) get_queried_object_id());
        if ($peer instanceof WP_Post) {
            $link = get_permalink($peer);
            if (is_string($link) && $link !== '') {
                return $link;
            }
        }
    }

    $lang = ensorlogs_current_lang();
    $path = ensorlogs_i18n_relative_path();

    if ($lang === 'en') {
        if (str_starts_with($path, '/en/')) {
            $path = substr($path, 3) ?: '/';
        } elseif ($path === '/en/') {
            $path = '/';
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
    $lang        = ensorlogs_current_lang();
    $alt         = ensorlogs_lang_alternate_url();
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
    echo '<link rel="alternate" hreflang="x-default" href="' . esc_url(home_url('/')) . '">' . "\n";
}
add_action('wp_head', 'ensorlogs_i18n_hreflang', 4);

/**
 * Bandera del encabezado (siempre Venezuela).
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

/**
 * Resuelve fragmento .en.fragment.html si existe.
 *
 * @param string $filename Nombre del fragmento.
 */
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

<?php
/**
 * Helpers: rutas del tema y renderizado de fragmentos HTML migrados.
 *
 * @package Ensorlogs
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * URL del directorio del tema (para assets).
 */
function ensorlogs_theme_uri(string $path = ''): string
{
    return trailingslashit(get_template_directory_uri()) . ltrim($path, '/');
}

/**
 * Lee un fragmento en partials/*.fragment.html y sustituye marcadores.
 *
 * Marcadores: %%THEME_URI%%, %%HOME%% (home con slash final).
 */
function ensorlogs_render_fragment(string $filename): string
{
    $dir = get_template_directory() . '/partials/';
    $path = $dir . ltrim($filename, '/');
    if (!is_readable($path)) {
        return '<!-- ensorlogs: falta el fragmento ' . esc_html($filename) . ' -->';
    }
    $html = (string) file_get_contents($path);
    return str_replace(
        array('%%THEME_URI%%', '%%HOME%%'),
        array(esc_url(get_template_directory_uri()), trailingslashit(esc_url(home_url('/')))),
        $html
    );
}

/**
 * Extrae el cuerpo editable de un HTML migrado.
 *
 * Reconoce, en este orden:
 *   1. <div class="ensor-reader-body ..."> (chrome lector v1.3+) → inner.
 *   2. <div class="details-body ..."> (chrome lector v1.2 / clásico Tailwind) → inner.
 *   3. <div class="main-content ..."> (logs antiguos sin chrome) → bloque completo hasta el marker.
 *
 * Devolver sólo el cuerpo editable evita el doble-chrome cuando el template
 * single-ensor_article.php pone su propia barra de progreso, TOC y header.
 */
function ensorlogs_extract_main_content_html(string $html): string
{
    if (preg_match('#<div[^>]*class=("|\')(?:[^"\']*\s)?ensor-reader-body(?:\s[^"\']*)?\1[^>]*>#i', $html, $m, PREG_OFFSET_CAPTURE)) {
        $open_pos = (int) $m[0][1];
        $inner    = (string) ensorlogs_extract_inner_after_div($html, $open_pos);
        if ($inner !== '') {
            return trim($inner);
        }
    }
    if (preg_match('#<(?P<tag>div|article)[^>]*class=("|\')(?:[^"\']*\s)?ensor-legal-body(?:\s[^"\']*)?\2[^>]*>#i', $html, $m, PREG_OFFSET_CAPTURE)) {
        $open_pos = (int) $m[0][1];
        $tag      = strtolower($m['tag'][0]);
        $inner    = (string) ensorlogs_extract_inner_after_block($html, $open_pos, $tag);
        if ($inner !== '') {
            return trim($inner);
        }
    }
    if (preg_match('#<div[^>]*class=("|\')(?:[^"\']*\s)?details-body(?:\s[^"\']*)?\1[^>]*>#i', $html, $m, PREG_OFFSET_CAPTURE)) {
        $open_pos = (int) $m[0][1];
        $inner    = (string) ensorlogs_extract_inner_after_div($html, $open_pos);
        if ($inner !== '') {
            return trim($inner);
        }
    }
    $start = strpos($html, '<div class="main-content');
    if ($start === false) {
        return '';
    }
    $sub = substr($html, $start);
    $marker = '<!--~~./ end Main Content ~~-->';
    $end = strpos($sub, $marker);
    if ($end === false) {
        return trim($sub);
    }
    return trim(substr($sub, 0, $end));
}

/**
 * Devuelve el HTML interior de un <div> dado por la posición de apertura.
 *
 * Recorre balanceando aperturas y cierres de <div> hasta encontrar el cierre
 * que corresponde al `<div>` inicial.
 */
function ensorlogs_extract_inner_after_div(string $html, int $open_pos): string
{
    return ensorlogs_extract_inner_after_block($html, $open_pos, 'div');
}

/**
 * Devuelve el HTML interior de un bloque (div|article|section|main…) dado.
 *
 * @param string $html  HTML completo.
 * @param int    $open_pos Posición del `<tag` (inicio).
 * @param string $tag   Nombre del tag (sin `<`).
 */
function ensorlogs_extract_inner_after_block(string $html, int $open_pos, string $tag = 'div'): string
{
    $tag = strtolower($tag);
    $open_marker  = '<' . $tag;
    $close_marker = '</' . $tag . '>';
    $open_len     = strlen($open_marker);
    $close_len    = strlen($close_marker);

    $gt = strpos($html, '>', $open_pos);
    if ($gt === false) {
        return '';
    }
    $cursor = $gt + 1;
    $depth = 1;
    $len = strlen($html);
    while ($cursor < $len) {
        $open  = stripos($html, $open_marker, $cursor);
        $close = stripos($html, $close_marker, $cursor);
        if ($close === false) {
            return '';
        }
        if ($open !== false && $open < $close) {
            // Solo cuenta como apertura si el char siguiente es ` ` o `>` (evita matchear <article-foo).
            $next = substr($html, $open + $open_len, 1);
            if ($next === ' ' || $next === '>' || $next === "\n" || $next === "\t") {
                $depth++;
            }
            $cursor = $open + $open_len;
        } else {
            $depth--;
            if ($depth <= 0) {
                return substr($html, $gt + 1, $close - $gt - 1);
            }
            $cursor = $close + $close_len;
        }
    }
    return '';
}

/**
 * Sustituye rutas relativas del HTML exportado por URLs de WordPress / del tema.
 */
function ensorlogs_relink_migrated_body(string $html): string
{
    $home = trailingslashit(home_url('/'));
    $t_uri = trailingslashit(get_template_directory_uri());
    $pairs = array(
        '../index.html' => $home,
        '../about.html' => $home . 'about/',
        '../projects.html' => $home . 'projects/',
        '../services.html' => $home . 'services/',
        '../contact.html' => $home . 'contact/',
        '../credentials.html' => $home . 'credentials/',
        '../assets/' => $t_uri . 'assets/',
    );
    $html = str_replace('../blog.html?tema=', $home . 'blog/?tema=', $html);
    $html = str_replace('../blog.html', $home . 'blog/', $html);
    $html = str_replace(array_keys($pairs), array_values($pairs), $html);
    $out = preg_replace_callback(
        '#href="\\.\\./articulos/([^"]+\\.html)"#',
        static function (array $m) use ($home): string {
            return 'href="' . esc_url($home . 'articulos/' . $m[1]) . '"';
        },
        $html
    );
    if (is_string($out)) {
        $html = $out;
    }
    $out2 = preg_replace_callback(
        '#href="\\.\\./proyectos/([^"]+\\.html)"#',
        static function (array $m) use ($home): string {
            return 'href="' . esc_url($home . 'proyectos/' . $m[1]) . '"';
        },
        $html
    );
    if (is_string($out2)) {
        $html = $out2;
    }
    return $html;
}

/**
 * Título legible desde &lt;title&gt; del HTML estático.
 */
function ensorlogs_parse_title_from_static_html(string $html): string
{
    if (!preg_match('/<title>\\s*([^<]+?)\\s*</i', $html, $m)) {
        return '';
    }
    $t = trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    $parts = explode('|', $t, 2);
    return trim($parts[0]);
}

/**
 * Etiqueta legible para pills de tema (coincide con el HTML estático).
 */
function ensorlogs_tema_label(string $slug): string
{
    $slug = strtolower($slug);
    $map   = array(
        'wordpress'  => 'WordPress',
        'linux'        => 'Linux',
        'ia'           => 'IA',
        'database'     => 'Database',
        'crm'          => 'CRM',
        'marketing'    => 'Marketing',
        'python'       => 'Python',
        'google'       => 'Google',
        'servidores'   => 'Servidores',
        'it'           => 'IT',
        'windows'      => 'Windows',
        'mac'          => 'Mac',
    );
    return $map[ $slug ] ?? ucfirst($slug);
}

/**
 * URL pública para imagen de tarjeta: absoluta o ruta `assets/...` del tema.
 */
function ensorlogs_resolve_public_asset_url(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $value)) {
        return esc_url($value);
    }
    if (str_starts_with($value, 'assets/')) {
        return esc_url(ensorlogs_theme_uri($value));
    }
    return '';
}

/**
 * Invalida todas las cachés transient generadas por el tema (listados,
 * release de GitHub, etc.). Se llama cuando se publica / actualiza / elimina
 * cualquier `ensor_article` o `ensor_project`, o cuando cambian sus términos.
 */
function ensorlogs_flush_listing_cache(): void
{
    delete_transient('ensorlogs_blog_list_v1');
    delete_transient('ensorlogs_projects_list_v1');
}

add_action(
    'save_post',
    static function (int $post_id, WP_Post $post): void {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!in_array($post->post_type, array('ensor_article', 'ensor_project'), true)) {
            return;
        }
        ensorlogs_flush_listing_cache();
    },
    99,
    2
);

add_action(
    'deleted_post',
    static function (int $post_id, WP_Post $post = null): void {
        if (!$post instanceof WP_Post) {
            return;
        }
        if (in_array($post->post_type, array('ensor_article', 'ensor_project'), true)) {
            ensorlogs_flush_listing_cache();
        }
    },
    10,
    2
);

add_action(
    'set_object_terms',
    static function (int $object_id, $terms, $tt_ids, string $taxonomy): void {
        if ($taxonomy !== 'ensor_tema') {
            return;
        }
        ensorlogs_flush_listing_cache();
    },
    10,
    4
);

add_action(
    'customize_save_after',
    static function (): void {
        ensorlogs_flush_listing_cache();
    }
);

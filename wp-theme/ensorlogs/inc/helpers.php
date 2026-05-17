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
 * Marcadores para la tarjeta “último log” en fragmentos (p. ej. portada).
 *
 * @return array<string, string> clave %%PLACEHOLDER%% => valor ya escapado para HTML.
 */
function ensorlogs_latest_log_fragment_tokens(): array
{
    $blog_url     = trailingslashit(esc_url(home_url('/'))) . 'blog/';
    $fallback_img = esc_url(ensorlogs_theme_uri('assets/img/Ensorlogs%20Blog.png'));

    $defaults = array(
        '%%LATEST_LOG_URL%%'         => $blog_url,
        '%%LATEST_LOG_TITLE%%'       => esc_html__('Ver artículos y aprendizajes', 'ensorlogs'),
        '%%LATEST_LOG_TITLE_ATTR%%'  => esc_attr__('Bitácora ENSOR.LOGS', 'ensorlogs'),
        '%%LATEST_LOG_IMG%%'         => $fallback_img,
    );

    $posts = get_posts(
        array(
            'post_type'              => 'ensor_article',
            'post_status'            => 'publish',
            'posts_per_page'         => 1,
            'orderby'                => 'date',
            'order'                  => 'DESC',
            'no_found_rows'          => true,
            'ignore_sticky_posts'    => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        )
    );
    if (empty($posts[0]) || !($posts[0] instanceof WP_Post)) {
        return $defaults;
    }

    $post  = $posts[0];
    $pid   = (int) $post->ID;
    $url   = esc_url(get_permalink($post));
    $title = get_the_title($post);
    $img   = '';
    if (has_post_thumbnail($pid)) {
        $thumb = get_the_post_thumbnail_url($pid, 'large');
        if (is_string($thumb) && $thumb !== '') {
            $img = $thumb;
        }
    }
    if ($img === '') {
        $card = (string) get_post_meta($pid, '_ensor_card_image', true);
        if ($card !== '' && function_exists('ensorlogs_resolve_public_asset_url')) {
            $img = (string) ensorlogs_resolve_public_asset_url($card);
        }
    }
    if ($img === '') {
        $img = $fallback_img;
    }

    return array(
        '%%LATEST_LOG_URL%%'        => $url,
        '%%LATEST_LOG_TITLE%%'      => esc_html($title),
        '%%LATEST_LOG_TITLE_ATTR%%' => esc_attr($title),
        '%%LATEST_LOG_IMG%%'        => esc_url($img),
    );
}

/**
 * HTML de los dos segmentos del marquee de la terminal de inicio (logs recientes, clicables).
 *
 * @return string Dos bloques `.ensor-marquee__segment` (el segundo con aria-hidden para el bucle).
 */
function ensorlogs_home_log_ticker_segments_html(): string
{
    $posts = get_posts(
        array(
            'post_type'              => 'ensor_article',
            'post_status'            => 'publish',
            'posts_per_page'         => 12,
            'orderby'                => 'date',
            'order'                  => 'DESC',
            'no_found_rows'          => true,
            'ignore_sticky_posts'    => true,
            'update_post_meta_cache' => false,
            'update_term_meta_cache' => false,
        )
    );

    $prefix = '<span class="ensor-terminal-ticker__prefix">' . esc_html__('tail -f bitácora.log —', 'ensorlogs') . ' </span>';

    if ($posts === array()) {
        $inner = $prefix . '<span class="ensor-terminal-ticker__fallback">' . esc_html__('automatización · PropTech · CRM · analítica · infraestructura · aprendizaje continuo', 'ensorlogs') . '</span>';
    } else {
        $chunks = array();
        foreach ($posts as $p) {
            if (!($p instanceof WP_Post)) {
                continue;
            }
            $title = get_the_title($p);
            if (!is_string($title) || trim($title) === '') {
                continue;
            }
            $chunks[] = '<a class="ensor-terminal-ticker__link" href="' . esc_url(get_permalink($p)) . '">' . esc_html($title) . '</a>';
        }
        if ($chunks === array()) {
            $inner = $prefix . '<span class="ensor-terminal-ticker__fallback">' . esc_html__('automatización · PropTech · CRM · analítica · infraestructura · aprendizaje continuo', 'ensorlogs') . '</span>';
        } else {
            $sep   = ' <span class="ensor-terminal-ticker__sep" aria-hidden="true">·</span> ';
            $inner = $prefix . implode($sep, $chunks);
        }
    }

    $body = '<span class="inline-flex items-center gap-x-3">' . $inner . '</span>';
    $seg  = static function (bool $duplicate) use ($body): string {
        $attrs = 'class="ensor-marquee__segment ensor-marquee__segment--matrix text-xs md:text-sm whitespace-nowrap"';
        if ($duplicate) {
            $attrs .= ' aria-hidden="true"';
        }
        return '<div ' . $attrs . '>' . $body . '</div>';
    };

    return $seg(false) . $seg(true);
}

/**
 * Lee un fragmento en partials/*.fragment.html y sustituye marcadores.
 *
 * Marcadores: %%THEME_URI%%, %%HOME%%, %%LATEST_LOG_*%% (último ensor_article publicado),
 * %%HOME_LOG_TICKER_SEGMENTS%% (marquee terminal: últimos logs con enlace).
 */
function ensorlogs_render_fragment(string $filename): string
{
    $dir = get_template_directory() . '/partials/';
    $path = $dir . ltrim($filename, '/');
    if (!is_readable($path)) {
        return '<!-- ensorlogs: falta el fragmento ' . esc_html($filename) . ' -->';
    }
    $html = (string) file_get_contents($path);
    $search  = array('%%THEME_URI%%', '%%HOME%%');
    $replace = array(esc_url(get_template_directory_uri()), trailingslashit(esc_url(home_url('/'))));
    $html    = str_replace($search, $replace, $html);
    if (strpos($html, '%%LATEST_LOG_') !== false) {
        $latest = ensorlogs_latest_log_fragment_tokens();
        $html   = str_replace(array_keys($latest), array_values($latest), $html);
    }
    if (strpos($html, '%%HOME_LOG_TICKER_SEGMENTS%%') !== false) {
        $html = str_replace('%%HOME_LOG_TICKER_SEGMENTS%%', ensorlogs_home_log_ticker_segments_html(), $html);
    }
    if (strpos($html, '%%CONTACT_') !== false && function_exists('ensorlogs_contact_fragment_tokens')) {
        $contact = ensorlogs_contact_fragment_tokens();
        $html    = str_replace(array_keys($contact), array_values($contact), $html);
    }
    return $html;
}

/**
 * Devuelve el fragmento renderizado, sustituyendo la zona editable
 *   <!-- ensor:editable -->...<!-- /ensor:editable -->
 * por el HTML que pase el editor de la página (the_content del post).
 *
 * Si $editable_html viene vacío (o solo espacios/HTML vacío), se devuelve
 * el fragmento intacto (con sus textos por defecto).
 *
 * @param string $filename       Nombre del archivo en partials/.
 * @param string $editable_html  HTML del editor de la página WP (ya filtrado).
 */
function ensorlogs_render_fragment_editable(string $filename, string $editable_html = ''): string
{
    $html = ensorlogs_render_fragment($filename);
    if (trim(wp_strip_all_tags($editable_html)) === '') {
        return $html;
    }
    $pattern = '/<!--\s*ensor:editable\s*-->.*?<!--\s*\/ensor:editable\s*-->/is';
    if (!preg_match($pattern, $html)) {
        // El fragmento no tiene zona editable: dejamos el contenido por defecto.
        return $html;
    }
    $replacement = "<!-- ensor:editable -->\n" . $editable_html . "\n<!-- /ensor:editable -->";
    return (string) preg_replace($pattern, $replacement, $html, 1);
}

/**
 * Devuelve el HTML por defecto que vive dentro de la zona editable de
 * un fragmento (entre los marcadores `<!-- ensor:editable -->`).
 * Útil para pre-rellenar el editor del post al hacer seed.
 *
 * @return string HTML "limpio" entre los marcadores, o cadena vacía.
 */
function ensorlogs_extract_fragment_editable_default(string $filename): string
{
    $html = ensorlogs_render_fragment($filename);
    if (preg_match('/<!--\s*ensor:editable\s*-->(.*?)<!--\s*\/ensor:editable\s*-->/is', $html, $m)) {
        return trim($m[1]);
    }
    return '';
}

/**
 * Mapa página-WP (slug) → fragmento que debe usar.
 *
 * Si una página de WP tiene un slug listado aquí, su editor de bloques
 * sirve para *sobrescribir la zona editable* del fragmento correspondiente.
 *
 * @return array<string, string> slug => filename del fragment
 */
function ensorlogs_page_fragments_map(): array
{
    return array(
        'inicio'   => 'home-body.fragment.html',
        'about'    => 'page-about.fragment.html',
        'services' => 'page-services.fragment.html',
        'projects' => 'page-projects.fragment.html',
        'blog'     => 'page-blog.fragment.html',
        'contact'  => 'page-contact.fragment.html',
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
 * Enlaces del HTML exportado `href="proyecto-slug.html"` o `href="wordpress-slug.html"`
 * (misma carpeta que el fichero estático) → permalink canónico del CPT.
 */
function ensorlogs_relink_cpt_filename_hrefs(string $html): string
{
    $pairs = array(
        '#href=(["\'])(proyecto-[a-z0-9-]+\.html)\1#i'   => 'proyectos/',
        '#href=(["\'])(wordpress-[a-z0-9-]+\.html)\1#i' => 'articulos/',
    );
    foreach ($pairs as $pattern => $basepath) {
        $out = preg_replace_callback(
            $pattern,
            static function (array $m) use ($basepath): string {
                $q    = $m[1];
                $file = $m[2];
                $slug = preg_replace('/\\.html$/i', '', $file);
                $path = $basepath . $slug;
                $url  = user_trailingslashit(home_url($path));
                return 'href=' . $q . esc_url($url) . $q;
            },
            $html
        );
        if (is_string($out)) {
            $html = $out;
        }
    }
    return $html;
}

/**
 * Siguiente / anterior proyecto publicado según menu_order (y fecha como desempate),
 * en bucle: el último enlaza al primero.
 */
function ensorlogs_adjacent_ensor_project_permalink(int $post_id, string $direction = 'next'): string
{
    if ($post_id <= 0) {
        return '';
    }
    $post = get_post($post_id);
    if (!$post instanceof WP_Post || $post->post_type !== 'ensor_project') {
        return '';
    }
    $ids = get_posts(
        array(
            'post_type'              => 'ensor_project',
            'post_status'            => 'publish',
            'posts_per_page'         => -1,
            'orderby'                => array(
                'menu_order' => 'ASC',
                'date'       => 'DESC',
            ),
            'fields'                 => 'ids',
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        )
    );
    $ids = array_values(array_map('intval', $ids));
    if (count($ids) < 2) {
        return '';
    }
    $idx = array_search($post_id, $ids, true);
    if ($idx === false) {
        return '';
    }
    if ($direction === 'prev') {
        $j = ($idx - 1 + count($ids)) % count($ids);
    } else {
        $j = ($idx + 1) % count($ids);
    }
    $target = (int) $ids[ $j ];
    $link   = get_permalink($target);
    return is_string($link) && $link !== '' ? $link : '';
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
        static function (array $m): string {
            $slug = preg_replace('/\\.html$/i', '', $m[1]);
            $path  = 'articulos/' . $slug;
            return 'href="' . esc_url(user_trailingslashit(home_url($path))) . '"';
        },
        $html
    );
    if (is_string($out)) {
        $html = $out;
    }
    $out2 = preg_replace_callback(
        '#href="\\.\\./proyectos/([^"]+\\.html)"#',
        static function (array $m): string {
            $slug = preg_replace('/\\.html$/i', '', $m[1]);
            $path  = 'proyectos/' . $slug;
            return 'href="' . esc_url(user_trailingslashit(home_url($path))) . '"';
        },
        $html
    );
    if (is_string($out2)) {
        $html = $out2;
    }
    return ensorlogs_relink_cpt_filename_hrefs($html);
}

/**
 * Sustituye el CTA antiguo (solo «Solicitar caso completo» con href="#") por
 * los dos botones con URLs absolutas (contacto + servicios / cómo ayudarte).
 */
function ensorlogs_project_replace_legacy_cta_block(string $html): string
{
    $legacy = <<<'HTML'
                    <li>
                        <a href="#" class="border border-darkGray dark:border-pastelGrey flex items-center justify-center gap-2 text-darkGray dark:text-pastelGrey rounded-4xl font-semibold py-3 transition-all duration-200 hover:bg-darkGray hover:text-white dark:hover:bg-pastelGrey dark:hover:text-darkGray">
                            Solicitar caso completo
                            <svg width="20" height="14" viewBox="0 0 20 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M15.5955 6H0.25C0.111929 6 0 6.11193 0 6.25V7.75C0 7.88807 0.111929 8 0.25 8H15.5955C15.7813 8 15.8997 8.19333 15.8083 8.35517C15.2155 9.40517 14.0746 11.2034 14.0035 13.7498C13.9996 13.8878 14.1119 14 14.25 14H15.5764C15.8209 14 16.0284 13.8228 16.0765 13.5832C16.6149 10.8994 17.9604 8.6482 19.7374 7.20503C19.8668 7.09996 19.8668 6.90004 19.7374 6.79497C17.9604 5.3518 16.6149 3.10055 16.0765 0.416824C16.0284 0.17718 15.8209 0 15.5764 0H14.25C14.1119 0 13.9996 0.112221 14.0035 0.250238C14.0746 2.79663 15.2155 4.59483 15.8083 5.64483C15.8997 5.80667 15.7813 6 15.5955 6Z" fill="currentcolor"/>
                            </svg>
                        </a>
                    </li>
HTML;
    if (strpos($html, $legacy) === false) {
        return $html;
    }
    $contact  = esc_url(user_trailingslashit(home_url('/contact/')));
    $services = esc_url(user_trailingslashit(home_url('/services/')));
    $fresh    = <<<HTML
                    <li class="w-full sm:min-w-0">
                        <div class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center justify-end gap-3">
                            <a href="{$contact}" class="border border-darkGray dark:border-pastelGrey inline-flex items-center justify-center gap-2 text-darkGray dark:text-pastelGrey rounded-4xl font-semibold py-3 px-4 sm:px-5 transition-all duration-200 hover:bg-darkGray hover:text-white dark:hover:bg-pastelGrey dark:hover:text-darkGray text-center no-underline">
                                Solicitar caso completo
                                <svg width="20" height="14" viewBox="0 0 20 14" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path d="M15.5955 6H0.25C0.111929 6 0 6.11193 0 6.25V7.75C0 7.88807 0.111929 8 0.25 8H15.5955C15.7813 8 15.8997 8.19333 15.8083 8.35517C15.2155 9.40517 14.0746 11.2034 14.0035 13.7498C13.9996 13.8878 14.1119 14 14.25 14H15.5764C15.8209 14 16.0284 13.8228 16.0765 13.5832C16.6149 10.8994 17.9604 8.6482 19.7374 7.20503C19.8668 7.09996 19.8668 6.90004 19.7374 6.79497C17.9604 5.3518 16.6149 3.10055 16.0765 0.416824C16.0284 0.17718 15.8209 0 15.5764 0H14.25C14.1119 0 13.9996 0.112221 14.0035 0.250238C14.0746 2.79663 15.2155 4.59483 15.8083 5.64483C15.8997 5.80667 15.7813 6 15.5955 6Z" fill="currentcolor"/>
                                </svg>
                            </a>
                            <a href="{$services}" class="border border-flasWhite dark:border-flasBlack bg-gradient-to-b from-milkWhite to-seashell dark:from-metalBlack dark:to-oilBlack inline-flex items-center justify-center gap-2 text-darkGray dark:text-pastelGrey rounded-4xl font-semibold py-3 px-4 sm:px-5 transition-all duration-200 hover:border-darkGray dark:hover:border-pastelGrey text-center no-underline">
                                Solicita mi servicio
                            </a>
                        </div>
                    </li>
HTML;
    return str_replace($legacy, $fresh, $html);
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
 * cualquier `ensor_article` o `ensor_project`, o cuando cambian sus términos,
 * o la meta `_thumbnail_id` (imagen destacada) de esos tipos.
 */
function ensorlogs_flush_listing_cache(): void
{
    delete_transient('ensorlogs_blog_list_v1');
    delete_transient('ensorlogs_projects_list_v1');
}

/**
 * Vacía cachés de listado si cambia la imagen destacada (`_thumbnail_id`) de un log o proyecto.
 */
function ensorlogs_maybe_flush_listing_cache_for_thumbnail(int $object_id, string $meta_key): void
{
    if ($meta_key !== '_thumbnail_id' || $object_id <= 0) {
        return;
    }
    $pt = get_post_type($object_id);
    if (in_array($pt, array('ensor_article', 'ensor_project'), true)) {
        ensorlogs_flush_listing_cache();
    }
}

add_action(
    'added_post_meta',
    static function ($meta_id, int $object_id, string $meta_key): void {
        ensorlogs_maybe_flush_listing_cache_for_thumbnail($object_id, $meta_key);
    },
    10,
    3
);

add_action(
    'updated_post_meta',
    static function ($meta_id, int $object_id, string $meta_key): void {
        ensorlogs_maybe_flush_listing_cache_for_thumbnail($object_id, $meta_key);
    },
    10,
    3
);

add_action(
    'deleted_post_meta',
    static function ($meta_ids, int $object_id, string $meta_key): void {
        ensorlogs_maybe_flush_listing_cache_for_thumbnail($object_id, $meta_key);
    },
    10,
    3
);

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

/**
 * Singular proyecto: migración CTA antigua, relinks del HTML exportado y
 * «Ver siguiente caso» según menu_order.
 */
add_filter(
    'the_content',
    static function ($content) {
        if (!is_singular('ensor_project') || !is_string($content) || $content === '') {
            return $content;
        }
        $content = ensorlogs_project_replace_legacy_cta_block($content);
        $content = ensorlogs_relink_migrated_body($content);
        $next    = ensorlogs_adjacent_ensor_project_permalink((int) get_queried_object_id(), 'next');
        if ($next === '') {
            return $content;
        }
        $out = preg_replace_callback(
            '#<a(\s[^>]+)>(\s*Ver siguiente caso\s*)</a>#iu',
            static function (array $m) use ($next): string {
                $attrs = $m[1];
                if (stripos($attrs, 'ensor-btn-primary') === false) {
                    return '<a' . $m[1] . '>' . $m[2] . '</a>';
                }
                $attrs2 = preg_replace(
                    '#\s+href=(["\'])[^"\']*\1#i',
                    ' href="' . esc_url($next) . '"',
                    $attrs,
                    1
                );
                if (!is_string($attrs2) || $attrs2 === $attrs) {
                    $attrs2 = rtrim($attrs) . ' href="' . esc_url($next) . '"';
                }
                return '<a' . $attrs2 . '>' . $m[2] . '</a>';
            },
            $content,
            1
        );
        return is_string($out) ? $out : $content;
    },
    8
);

/**
 * Singular log: relinks del HTML exportado (../blog, articulos/*.html, etc.).
 */
add_filter(
    'the_content',
    static function ($content) {
        if (!is_singular('ensor_article') || !is_string($content) || $content === '') {
            return $content;
        }
        return ensorlogs_relink_migrated_body($content);
    },
    8
);

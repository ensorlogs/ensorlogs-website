<?php
/**
 * SEO básico + rendimiento (sin sustituir a Rank Math / Yoast si están activos).
 *
 * @package Ensorlogs
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ¿Hay un plugin SEO que ya meta etiquetas sociales / descripción?
 */
function ensorlogs_has_seo_plugin(): bool
{
    return defined('WPSEO_VERSION')
        || defined('RANK_MATH_VERSION')
        || defined('AIOSEO_VERSION')
        || defined('SEOPRESS_VERSION')
        || class_exists('WP_Meta_SEO', false);
}

/**
 * URL absoluta para Open Graph (imagen por defecto del tema o del Customizer).
 */
function ensorlogs_default_social_image_url(): string
{
    if (function_exists('ensorlogs_get_og_default_image_url')) {
        $u = ensorlogs_get_og_default_image_url();
        if (is_string($u) && $u !== '') {
            return $u;
        }
    }
    return trailingslashit(get_template_directory_uri()) . 'assets/img/Logos/ensorlogs2.png';
}

/**
 * Descripción corta para meta/OG (singular, página, portada).
 */
function ensorlogs_meta_description(): string
{
    if (is_category() || is_tag() || is_tax()) {
        $d = get_the_archive_description();
        if (is_string($d) && $d !== '') {
            return wp_strip_all_tags($d);
        }
    }
    if (is_singular()) {
        $post = get_queried_object();
        if ($post instanceof WP_Post) {
            if ($post->post_excerpt !== '') {
                return wp_strip_all_tags($post->post_excerpt);
            }
            return wp_trim_words(wp_strip_all_tags($post->post_content), 35, '…');
        }
    }
    if (is_home() && !is_front_page()) {
        return wp_strip_all_tags(get_bloginfo('description'));
    }
    $blog_desc = get_bloginfo('description', 'display');
    if (is_string($blog_desc) && $blog_desc !== '') {
        return wp_strip_all_tags($blog_desc);
    }
    if (function_exists('ensorlogs_get_default_meta_description')) {
        $d = ensorlogs_get_default_meta_description();
        if ($d !== '') {
            return wp_strip_all_tags($d);
        }
    }
    return wp_strip_all_tags(
        __('Consultor IT, CRM, automatización y operaciones digitales — Ensorlogs.', 'ensorlogs')
    );
}

/**
 * Imagen destacada para OG (artículo/proyecto con meta de tarjeta, logo por defecto).
 */
function ensorlogs_social_image_url(): string
{
    if (is_singular(array('ensor_article', 'ensor_project'))) {
        $id = get_queried_object_id();
        if ($id > 0) {
            $thumb = get_the_post_thumbnail_url($id, 'large');
            if (is_string($thumb) && $thumb !== '') {
                return $thumb;
            }
            if (get_post_type($id) === 'ensor_article') {
                $card = (string) get_post_meta($id, '_ensor_card_image', true);
                if ($card !== '' && filter_var($card, FILTER_VALIDATE_URL)) {
                    return $card;
                }
            }
        }
    }
    if (is_singular() && has_post_thumbnail()) {
        $u = get_the_post_thumbnail_url(get_queried_object_id(), 'large');
        if (is_string($u) && $u !== '') {
            return $u;
        }
    }
    $custom_logo_id = (int) get_theme_mod('custom_logo');
    if ($custom_logo_id > 0) {
        $logo = wp_get_attachment_image_url($custom_logo_id, 'full');
        if (is_string($logo) && $logo !== '') {
            return $logo;
        }
    }
    return ensorlogs_default_social_image_url();
}

/**
 * Meta description + Open Graph + Twitter (solo si no hay plugin SEO).
 */
add_action(
    'wp_head',
    static function (): void {
        if (ensorlogs_has_seo_plugin()) {
            return;
        }
        $desc = esc_attr(wp_strip_all_tags(ensorlogs_meta_description()));
        if ($desc !== '') {
            echo '<meta name="description" content="' . $desc . '">' . "\n";
        }
        $title = wp_get_document_title();
        $url   = function_exists('wp_get_canonical_url') ? wp_get_canonical_url() : false;
        if (!is_string($url) || $url === '') {
            $url = is_singular() ? get_permalink() : home_url('/');
        }
        $url = esc_url($url);
        $img = esc_url(ensorlogs_social_image_url());
        $og_locale = function_exists('ensorlogs_current_lang') && ensorlogs_current_lang() === 'en' ? 'en_US' : 'es_ES';
        echo '<meta property="og:locale" content="' . esc_attr($og_locale) . '">' . "\n";
        $og_type = (is_singular('ensor_article') || is_singular('post')) ? 'article' : 'website';
        echo '<meta property="og:type" content="' . esc_attr($og_type) . '">' . "\n";
        echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
        echo '<meta property="og:description" content="' . $desc . '">' . "\n";
        echo '<meta property="og:url" content="' . $url . '">' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";
        echo '<meta property="og:image" content="' . $img . '">' . "\n";
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr($title) . '">' . "\n";
        echo '<meta name="twitter:description" content="' . $desc . '">' . "\n";
        echo '<meta name="twitter:image" content="' . $img . '">' . "\n";
        if (is_singular(array('post', 'ensor_article', 'ensor_project')) && get_post()) {
            $t = get_post_time('c', true);
            if (is_string($t) && $t !== '') {
                echo '<meta property="article:published_time" content="' . esc_attr($t) . '">' . "\n";
            }
            $mod = get_post_modified_time('c', true);
            if (is_string($mod) && $mod !== '') {
                echo '<meta property="article:modified_time" content="' . esc_attr($mod) . '">' . "\n";
            }
        }
    },
    4
);

/**
 * JSON-LD: WebSite en portada; BlogPosting / WebPage en singular.
 */
add_action(
    'wp_head',
    static function (): void {
        if (ensorlogs_has_seo_plugin()) {
            return;
        }
        $site_url = home_url('/');
        $name     = wp_strip_all_tags(get_bloginfo('name'));
        $graph    = array();

        if (is_front_page()) {
            $graph[] = array(
                '@type'       => 'WebSite',
                '@id'         => $site_url . '#website',
                'url'         => $site_url,
                'name'        => $name,
                'description' => ensorlogs_meta_description(),
                'inLanguage'  => 'es-ES',
                'publisher'   => array(
                    '@id' => $site_url . '#person',
                ),
            );
            $author_name = function_exists('ensorlogs_get_author_name') ? ensorlogs_get_author_name() : 'Ensor Sánchez';
            $author_job  = function_exists('ensorlogs_get_author_job')
                ? ensorlogs_get_author_job()
                : __('Consultor IT · Operaciones digitales', 'ensorlogs');
            $socials_arr = function_exists('ensorlogs_get_social_links') ? ensorlogs_get_social_links() : array();
            $same_as     = array_values($socials_arr);
            if (empty($same_as)) {
                $same_as = array('https://www.linkedin.com/in/ensorsanchez/');
            }
            $graph[] = array(
                '@type'       => 'Person',
                '@id'         => $site_url . '#person',
                'name'        => $author_name,
                'url'         => $site_url,
                'jobTitle'    => $author_job,
                'description' => ensorlogs_meta_description(),
                'sameAs'      => $same_as,
            );
        } elseif (is_singular('ensor_article')) {
            $post = get_post();
            if ($post instanceof WP_Post) {
                $graph[] = array(
                    '@type'            => 'BlogPosting',
                    'headline'         => get_the_title($post),
                    'description'      => ensorlogs_meta_description(),
                    'datePublished'    => get_post_time('c', true, $post),
                    'dateModified'     => get_post_modified_time('c', true, $post),
                    'mainEntityOfPage' => array('@type' => 'WebPage', '@id' => get_permalink($post)),
                    'url'              => get_permalink($post),
                    'inLanguage'       => 'es-ES',
                    'author'           => array(
                        '@type' => 'Person',
                        'name'  => function_exists('ensorlogs_get_author_name') ? ensorlogs_get_author_name() : 'Ensor Sánchez',
                        'url'   => $site_url,
                    ),
                    'publisher'        => array(
                        '@type' => 'Organization',
                        'name'  => $name,
                        'url'   => $site_url,
                        'logo'  => array(
                            '@type' => 'ImageObject',
                            'url'   => ensorlogs_default_social_image_url(),
                        ),
                    ),
                );
            }
        } elseif (is_singular('ensor_project')) {
            $post = get_post();
            if ($post instanceof WP_Post) {
                $graph[] = array(
                    '@type'         => 'CreativeWork',
                    'name'          => get_the_title($post),
                    'description'   => ensorlogs_meta_description(),
                    'url'           => get_permalink($post),
                    'datePublished' => get_post_time('c', true, $post),
                    'dateModified'  => get_post_modified_time('c', true, $post),
                    'inLanguage'    => 'es-ES',
                );
            }
        } elseif (is_singular('page')) {
            $graph[] = array(
                '@type'       => 'WebPage',
                'name'        => wp_get_document_title(),
                'description' => ensorlogs_meta_description(),
                'url'         => esc_url(get_permalink()),
                'inLanguage'  => 'es-ES',
                'isPartOf'    => array('@id' => $site_url . '#website'),
            );
        }

        if ($graph === array()) {
            return;
        }
        $out = array(
            '@context' => 'https://schema.org',
            '@graph'   => $graph,
        );
        echo '<script type="application/ld+json">' . wp_json_encode($out, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
    },
    99
);

/**
 * Pistas de red para fuentes y CDN (mejora LCP de fuentes). Evitamos
 * duplicar lo que ya hayan añadido WP/otros plugins comparando por host.
 */
add_filter(
    'wp_resource_hints',
    static function (array $urls, string $relation_type): array {
        $extras    = array();
        $fonts_url = function_exists('ensorlogs_fonts_url') ? ensorlogs_fonts_url() : '';
        $fonts_host = $fonts_url !== '' ? (string) wp_parse_url($fonts_url, PHP_URL_HOST) : '';
        if ($relation_type === 'preconnect') {
            if ($fonts_host !== '') {
                $extras[] = 'https://' . $fonts_host;
            }
            if (strpos($fonts_url, 'fonts.googleapis.com') !== false) {
                $extras[] = 'https://fonts.gstatic.com';
            }
        } elseif ($relation_type === 'dns-prefetch') {
            $extras = array('https://cdn.jsdelivr.net');
            if ($fonts_host !== '') {
                $extras[] = 'https://' . $fonts_host;
            }
        }
        if ($extras === array()) {
            return $urls;
        }
        $known_hosts = array();
        foreach ($urls as $entry) {
            $href = is_array($entry) && isset($entry['href']) ? (string) $entry['href'] : (string) $entry;
            $host = wp_parse_url($href, PHP_URL_HOST);
            if (is_string($host) && $host !== '') {
                $known_hosts[strtolower($host)] = true;
            }
        }
        foreach ($extras as $url) {
            $host = wp_parse_url($url, PHP_URL_HOST);
            if (is_string($host) && $host !== '' && empty($known_hosts[strtolower($host)])) {
                $urls[] = $url;
                $known_hosts[strtolower($host)] = true;
            }
        }
        return $urls;
    },
    10,
    2
);

/**
 * Carga CSS de bloques solo cuando hace falta (menos CSS en páginas híbridas).
 */
add_filter('should_load_separate_core_block_assets', '__return_true');

/**
 * Menos JS/CSS por defecto en el front (emojis, enlaces WP legacy).
 */
add_action(
    'init',
    static function (): void {
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('admin_print_styles', 'print_emoji_styles');
        add_filter('emoji_svg_url', '__return_false');
        remove_action('wp_head', 'wp_generator');
    },
    20
);

add_action(
    'init',
    static function (): void {
        remove_action('wp_head', 'rsd_link');
        remove_action('wp_head', 'wlwmanifest_link');
        remove_action('wp_head', 'wp_shortlink_wp_head');
    },
    30
);

add_action(
    'wp_enqueue_scripts',
    static function (): void {
        if (!is_user_logged_in()) {
            wp_dequeue_style('dashicons');
        }
    },
    100
);

/**
 * Defer en scripts del pie del tema. jQuery se sirve sin defer para
 * mantener compatibilidad con WordPress y plugins que asumen su disponibilidad
 * inmediata. El resto de scripts del tema esperan al DOM sin bloquear el render.
 */
add_filter(
    'script_loader_tag',
    static function (string $tag, string $handle, string $src): string {
        $defer = array(
            'ensorlogs-waypoints',
            'ensorlogs-tw',
            'ensorlogs-aos',
            'ensorlogs-tilt',
            'ensorlogs-script',
            'ensorlogs-nav',
            'ensorlogs-theme-mode',
            'ensorlogs-swiper',
            'ensorlogs-blog-filter',
            'ensorlogs-projects-filter',
            'ensorlogs-reader',
            'ensorlogs-newsletter',
        );
        if (!in_array($handle, $defer, true)) {
            return $tag;
        }
        if (preg_match('/\sdefer(\s|=|>)/i', $tag)) {
            return $tag;
        }
        return preg_replace('/<script(?![^>]*\sdefer)/i', '<script defer', $tag, 1) ?? $tag;
    },
    10,
    3
);

/**
 * Añade decoding async y lazy a imágenes del contenido (cuando WordPress no lo hace).
 */
add_filter(
    'the_content',
    static function (string $content): string {
        if ($content === '' || !str_contains($content, '<img') || is_feed() || is_admin() || (defined('REST_REQUEST') && REST_REQUEST)) {
            return $content;
        }
        return preg_replace_callback(
            '/<img\b([^>]*?)>/i',
            static function (array $m): string {
                $attrs = $m[1];
                if (stripos($attrs, 'loading=') === false) {
                    $attrs .= ' loading="lazy"';
                }
                if (stripos($attrs, 'decoding=') === false) {
                    $attrs .= ' decoding="async"';
                }
                return '<img' . $attrs . '>';
            },
            $content
        ) ?? $content;
    },
    20
);

/**
 * robots: noindex en resultados de búsqueda interna vacíos o feos para SEO.
 */
add_filter(
    'wp_robots',
    static function (array $robots): array {
        if (is_search()) {
            $q = get_search_query();
            if ($q === '' || strlen($q) < 2) {
                $robots['noindex'] = true;
            }
        }
        return $robots;
    },
    10
);

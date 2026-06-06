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
 * Slug de página estructural para SEO (home, about, blog, projects…).
 */
function ensorlogs_seo_page_slug(): string
{
    if (is_front_page()) {
        return 'home';
    }
    if (!is_page()) {
        return '';
    }
    $post = get_queried_object();
    return $post instanceof WP_Post ? $post->post_name : '';
}

/**
 * Secciones principales del sitio (alineadas al menú de cabecera).
 *
 * @return array<int, array{slug: string, name: string, path: string}>
 */
function ensorlogs_seo_primary_sections(): array
{
    $en = function_exists('ensorlogs_current_lang') && ensorlogs_current_lang() === 'en';
    if ($en) {
        return array(
            array('slug' => 'about', 'name' => 'About me', 'path' => '/about/'),
            array('slug' => 'projects', 'name' => 'Projects', 'path' => '/projects/'),
            array('slug' => 'blog', 'name' => "Let's talk about…", 'path' => '/blog/'),
            array('slug' => 'services', 'name' => 'How can I help?', 'path' => '/services/'),
        );
    }
    return array(
        array('slug' => 'about', 'name' => 'Sobre mi', 'path' => '/about/'),
        array('slug' => 'projects', 'name' => 'Proyectos', 'path' => '/projects/'),
        array('slug' => 'blog', 'name' => 'Hablemos de…', 'path' => '/blog/'),
        array('slug' => 'services', 'name' => '¿Cómo puedo ayudarte?', 'path' => '/services/'),
    );
}

/**
 * @return array<string, string>
 */
function ensorlogs_seo_titles_map(): array
{
    $en = function_exists('ensorlogs_current_lang') && ensorlogs_current_lang() === 'en';
    if ($en) {
        return array(
            'home'     => 'Ensor Logs | Engineer, lecturer and digital logbook',
            'about'    => 'About me | Ensor Sánchez | Ensorlogs',
            'projects' => 'Projects | IT portfolio and real cases | Ensorlogs',
            'blog'     => "Let's talk about… | IT logs and articles | Ensorlogs",
            'services' => 'Services | IT consulting and workshops | Ensorlogs',
            'contact'  => 'Contact | Ensor Sánchez | Ensorlogs',
        );
    }
    return array(
        'home'     => 'Ensor Logs | Ingeniero, profesor y bitácora digital',
        'about'    => 'Sobre mi | Ensor Sánchez | Ensorlogs',
        'projects' => 'Proyectos | Portfolio IT y casos reales | Ensorlogs',
        'blog'     => 'Hablemos de… | Logs IT y bitácora | Ensorlogs',
        'services' => 'Servicios | Consultoría IT y talleres | Ensorlogs',
        'contact'  => 'Contacto | Ensor Sánchez | Ensorlogs',
    );
}

/**
 * @return array<string, string>
 */
function ensorlogs_seo_descriptions_map(): array
{
    $en = function_exists('ensorlogs_current_lang') && ensorlogs_current_lang() === 'en';
    if ($en) {
        return array(
            'home'     => 'Ensor Logs: engineer, lecturer and digital explorer. I document what I learn about IT, WordPress, data and automation.',
            'about'    => 'About Ensor Sánchez: engineer, lecturer and public logbook. Background, community work and how I collaborate with teams and students.',
            'projects' => 'Real IT and PropTech projects: WordPress, CRM, automation, cloud and data. Filterable portfolio with context and deliverables.',
            'blog'     => "Let's talk about… IT logs on WordPress, data, automation and digital operations. Practical articles for students, teachers and teams.",
            'services' => 'IT consulting, CRM, automation, web and workshops. Concrete deliverables with documentation your team can maintain.',
            'contact'  => 'Contact Ensor Sánchez for projects, workshops or collaboration. Brief form and Calendly for a 30-minute call.',
        );
    }
    return array(
        'home'     => 'Ensor Logs: ingeniero, profesor y explorador digital. Lo que aprendo lo documento y lo comparto — logs sobre IT, WordPress, datos y automatización.',
        'about'    => 'Sobre mi: Ensor Sánchez, ingeniero, profesor y bitácora pública. Trayectoria IT, comunidades tech y cómo trabajo con equipos y estudiantes.',
        'projects' => 'Proyectos reales de IT y PropTech: WordPress, CRM, automatización, cloud y datos. Portfolio filtrable con contexto y entregables.',
        'blog'     => 'Hablemos de… logs sobre WordPress, datos, automatización y operaciones digitales. Artículos prácticos para estudiantes, docentes y equipos.',
        'services' => 'Servicios IT, CRM, automatización, web y talleres. Entregables concretos con documentación para que tu equipo pueda mantenerlos.',
        'contact'  => 'Contacto con Ensor Sánchez para proyectos, talleres o colaboración. Formulario de briefing y Calendly para una llamada de 30 minutos.',
    );
}

/**
 * URL absoluta de una sección principal.
 *
 * @param array{slug: string, name: string, path: string} $section
 */
function ensorlogs_seo_section_url(array $section): string
{
    if (function_exists('ensorlogs_lang_url')) {
        return ensorlogs_lang_url($section['path']);
    }
    return home_url($section['path']);
}

/**
 * ¿Página legal (hija de /legal/ o aviso/privacidad/cookies)?
 */
function ensorlogs_seo_is_legal_page(WP_Post $post): bool
{
    if ($post->post_name === 'legal') {
        return true;
    }
    if (in_array($post->post_name, array('aviso-legal', 'privacidad', 'cookies', 'accesibilidad'), true)) {
        return true;
    }
    if ((int) $post->post_parent > 0) {
        $parent = get_post((int) $post->post_parent);
        if ($parent instanceof WP_Post && $parent->post_name === 'legal') {
            return true;
        }
    }
    return false;
}

/**
 * Elementos SiteNavigationElement + ItemList para JSON-LD de portada.
 *
 * @return array<int, array<string, mixed>>
 */
function ensorlogs_seo_navigation_graph(string $site_url): array
{
    $graph   = array();
    $list    = array();
    $pos     = 1;
    $lang    = function_exists('ensorlogs_current_lang') && ensorlogs_current_lang() === 'en' ? 'en-US' : 'es-ES';
    foreach (ensorlogs_seo_primary_sections() as $section) {
        $url = ensorlogs_seo_section_url($section);
        $graph[] = array(
            '@type'      => 'SiteNavigationElement',
            '@id'        => trailingslashit($url) . '#navigation',
            'name'       => $section['name'],
            'url'        => $url,
            'inLanguage' => $lang,
            'isPartOf'   => array('@id' => $site_url . '#website'),
        );
        $list[] = array(
            '@type'    => 'ListItem',
            'position' => $pos,
            'name'     => $section['name'],
            'item'     => $url,
        );
        ++$pos;
    }
    $graph[] = array(
        '@type'           => 'ItemList',
        '@id'             => $site_url . '#primary-nav',
        'name'            => function_exists('ensorlogs_current_lang') && ensorlogs_current_lang() === 'en'
            ? 'Main sections'
            : 'Secciones principales',
        'itemListElement' => $list,
    );
    return $graph;
}

/**
 * Títulos SEO por página estructural (sin plugin SEO externo).
 *
 * @param string $title
 */
function ensorlogs_seo_document_title(string $title): string
{
    if (ensorlogs_has_seo_plugin()) {
        return $title;
    }
    $slug = ensorlogs_seo_page_slug();
    if ($slug === '') {
        return $title;
    }
    $map = ensorlogs_seo_titles_map();
    return isset($map[$slug]) && $map[$slug] !== '' ? $map[$slug] : $title;
}
add_filter('pre_get_document_title', 'ensorlogs_seo_document_title', 20);

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
    if (!ensorlogs_has_seo_plugin()) {
        $slug = ensorlogs_seo_page_slug();
        if ($slug !== '') {
            $map = ensorlogs_seo_descriptions_map();
            if (isset($map[$slug]) && $map[$slug] !== '') {
                return $map[$slug];
            }
        }
    }
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
            $lang_code = function_exists('ensorlogs_current_lang') && ensorlogs_current_lang() === 'en' ? 'en-US' : 'es-ES';
            $graph[] = array(
                '@type'       => 'WebSite',
                '@id'         => $site_url . '#website',
                'url'         => $site_url,
                'name'        => $name,
                'description' => ensorlogs_meta_description(),
                'inLanguage'  => $lang_code,
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
            $graph = array_merge($graph, ensorlogs_seo_navigation_graph($site_url));
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
            $page = get_queried_object();
            $slug = $page instanceof WP_Post ? $page->post_name : '';
            $lang = function_exists('ensorlogs_current_lang') && ensorlogs_current_lang() === 'en' ? 'en-US' : 'es-ES';
            $page_type = 'WebPage';
            if ($slug === 'blog') {
                $page_type = 'Blog';
            } elseif ($slug === 'projects') {
                $page_type = 'CollectionPage';
            }
            $graph[] = array(
                '@type'       => $page_type,
                'name'        => wp_get_document_title(),
                'description' => ensorlogs_meta_description(),
                'url'         => esc_url(get_permalink()),
                'inLanguage'  => $lang,
                'isPartOf'    => array('@id' => $site_url . '#website'),
            );
            if ($page instanceof WP_Post) {
                $titles = ensorlogs_seo_titles_map();
                $crumb_name = isset($titles[$slug]) ? preg_replace('/\s*\|\s*Ensorlogs.*/', '', $titles[$slug]) : get_the_title($page);
                if (!is_string($crumb_name) || $crumb_name === '') {
                    $crumb_name = get_the_title($page);
                }
                $graph[] = array(
                    '@type'           => 'BreadcrumbList',
                    'itemListElement' => array(
                        array(
                            '@type'    => 'ListItem',
                            'position' => 1,
                            'name'     => $name,
                            'item'     => $site_url,
                        ),
                        array(
                            '@type'    => 'ListItem',
                            'position' => 2,
                            'name'     => $crumb_name,
                            'item'     => get_permalink($page),
                        ),
                    ),
                );
            }
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
            'ensorlogs-log-rating',
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
 * SiteGround Optimizer: no combinar scripts del tema (evita romper IIFE y wp_localize).
 */
add_filter(
    'sgo_js_combine_exclude',
    static function ($exclude): array {
        if (!is_array($exclude)) {
            $exclude = array();
        }
        $files = array(
            'ensor-a11y.js',
            'ensor-cookies.js',
            'ensor-lang-switch.js',
            'ensor-newsletter.js',
            'ensor-quiz.js',
            'ensor-log-rating.js',
            'ensor-reader.js',
        );
        foreach ($files as $file) {
            $exclude[] = $file;
        }
        $exclude[] = 'ensorlogs-newsletter';
        $exclude[] = 'ensorlogs-a11y';
        $exclude[] = 'ensorlogs-cookies';
        $exclude[] = 'ensorlogs-quiz';
        $exclude[] = 'ensorlogs-log-rating';

        return $exclude;
    }
);

add_filter(
    'sgo_javascript_combine_exclude',
    static function ($exclude): array {
        if (!is_array($exclude)) {
            $exclude = array();
        }
        $files = array(
            'ensor-a11y.js',
            'ensor-cookies.js',
            'ensor-lang-switch.js',
            'ensor-newsletter.js',
            'ensor-quiz.js',
            'ensor-log-rating.js',
            'ensor-reader.js',
        );
        foreach ($files as $file) {
            $exclude[] = $file;
        }

        return $exclude;
    }
);

add_filter(
    'sgo_javascript_minify_exclude',
    static function ($exclude): array {
        if (!is_array($exclude)) {
            $exclude = array();
        }
        $exclude[] = 'ensor-a11y.js';
        $exclude[] = 'ensor-newsletter.js';

        return $exclude;
    }
);

/**
 * Marca scripts críticos del tema para que Optimizer no los fusione (atributo en el tag).
 */
add_filter(
    'script_loader_tag',
    static function (string $tag, string $handle, string $src): string {
        $no_combine = array(
            'ensorlogs-a11y',
            'ensorlogs-cookies',
            'ensorlogs-lang',
            'ensorlogs-newsletter',
            'ensorlogs-quiz',
            'ensorlogs-reader',
        );
        if (!in_array($handle, $no_combine, true)) {
            return $tag;
        }
        if (str_contains($tag, 'data-cfasync="false"')) {
            return $tag;
        }
        $tag = preg_replace('/<script/i', '<script data-cfasync="false"', $tag, 1) ?? $tag;
        if (!str_contains($tag, 'data-no-optimize')) {
            $tag = preg_replace('/<script/i', '<script data-no-optimize="1"', $tag, 1) ?? $tag;
        }
        return $tag;
    },
    20,
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

/**
 * Prioridad en sitemap: subir secciones principales; bajar legales y contacto.
 *
 * @param array<string, mixed> $entry
 * @param WP_Post              $post
 * @return array<string, mixed>
 */
function ensorlogs_seo_sitemap_page_entry(array $entry, WP_Post $post): array
{
    $priorities = array(
        'about'    => 0.95,
        'projects' => 0.95,
        'blog'     => 0.95,
        'services' => 0.85,
        'contact'  => 0.65,
        'legal'    => 0.25,
    );
    if (isset($priorities[$post->post_name])) {
        $entry['priority'] = $priorities[$post->post_name];
        return $entry;
    }
    if (ensorlogs_seo_is_legal_page($post)) {
        $entry['priority'] = 0.25;
    }
    return $entry;
}
add_filter('wp_sitemap_pages_entry', 'ensorlogs_seo_sitemap_page_entry', 10, 2);

/**
 * Asegura referencia al sitemap nativo de WordPress en robots.txt.
 *
 * @param string $output
 * @param bool   $public
 */
function ensorlogs_seo_robots_txt(string $output, bool $public): string
{
    if (!$public) {
        return $output;
    }
    $sitemap = home_url('/wp-sitemap.xml');
    if (stripos($output, 'Sitemap:') === false) {
        $output .= "\nSitemap: {$sitemap}\n";
    }
    return $output;
}
add_filter('robots_txt', 'ensorlogs_seo_robots_txt', 10, 2);

/**
 * Enlace al sitemap en <head> (refuerzo para crawlers).
 */
add_action(
    'wp_head',
    static function (): void {
        if (ensorlogs_has_seo_plugin()) {
            return;
        }
        echo '<link rel="sitemap" type="application/xml" title="Sitemap" href="' . esc_url(home_url('/wp-sitemap.xml')) . '">' . "\n";
    },
    3
);

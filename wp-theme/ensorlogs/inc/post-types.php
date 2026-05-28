<?php
/**
 * Tipos de contenido migrados: artículos y proyectos.
 *
 * Permalinks canónicos: /articulos/{slug}/ y /proyectos/{slug}/ (WordPress).
 * Se mantienen reglas de reescritura para /articulos/{slug}.html por compatibilidad;
 * esas URLs redirigen 301 a la forma canónica.
 *
 * @package Ensorlogs
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', static function (): void {
    register_post_type(
        'ensor_article',
        array(
            'labels'              => array(
                'name'               => __('Logs', 'ensorlogs'),
                'singular_name'      => __('Log', 'ensorlogs'),
                'menu_name'          => __('Logs', 'ensorlogs'),
                'add_new'            => __('Añadir log', 'ensorlogs'),
                'add_new_item'       => __('Añadir nuevo log', 'ensorlogs'),
                'edit_item'          => __('Editar log', 'ensorlogs'),
                'new_item'           => __('Nuevo log', 'ensorlogs'),
                'view_item'          => __('Ver log', 'ensorlogs'),
                'view_items'         => __('Ver logs', 'ensorlogs'),
                'search_items'       => __('Buscar logs', 'ensorlogs'),
                'not_found'          => __('No se encontraron logs', 'ensorlogs'),
                'not_found_in_trash' => __('No hay logs en la papelera', 'ensorlogs'),
                'all_items'          => __('Todos los logs', 'ensorlogs'),
                'archives'           => __('Archivo de logs', 'ensorlogs'),
                'attributes'         => __('Atributos del log', 'ensorlogs'),
                'featured_image'     => __('Imagen destacada del log', 'ensorlogs'),
            ),
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_nav_menus'   => false,
            'show_in_rest'        => true,
            'has_archive'         => false,
            'exclude_from_search' => false,
            'rewrite'             => array(
                'slug'       => 'articulos',
                'with_front' => false,
            ),
            'supports'            => array('title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'revisions'),
            'menu_icon'           => 'dashicons-media-document',
            'capability_type'     => 'post',
            'map_meta_cap'        => true,
            // Sin plantilla de bloques: el log se edita en clásico + panel IA (evita conflictos en post-new).
            'template'            => array(),
            'template_lock'       => false,
        )
    );

    register_post_type(
        'ensor_project',
        array(
            'labels'              => array(
                'name'          => __('Proyectos (casos)', 'ensorlogs'),
                'singular_name' => __('Proyecto', 'ensorlogs'),
                'add_new_item'  => __('Añadir proyecto', 'ensorlogs'),
                'edit_item'     => __('Editar proyecto', 'ensorlogs'),
            ),
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_nav_menus'   => false,
            'show_in_rest'        => true,
            'has_archive'         => false,
            'exclude_from_search' => false,
            'rewrite'             => array(
                'slug'       => 'proyectos',
                'with_front' => false,
            ),
            'supports'            => array('title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'revisions'),
            'menu_icon'           => 'dashicons-portfolio',
            'capability_type'     => 'post',
            'map_meta_cap'        => true,
            'template'            => function_exists('ensorlogs_cpt_default_block_template')
                ? ensorlogs_cpt_default_block_template('ensor_project')
                : array(),
            'template_lock'       => false,
        )
    );

    register_taxonomy(
        'ensor_tema',
        array('ensor_article', 'ensor_project'),
        array(
            'labels'            => array(
                'name'              => __('Stacks', 'ensorlogs'),
                'singular_name'     => __('Stack', 'ensorlogs'),
                'menu_name'         => __('Stacks', 'ensorlogs'),
                'all_items'         => __('Todos los stacks', 'ensorlogs'),
                'edit_item'         => __('Editar stack', 'ensorlogs'),
                'view_item'         => __('Ver stack', 'ensorlogs'),
                'update_item'       => __('Actualizar stack', 'ensorlogs'),
                'add_new_item'      => __('Añadir stack', 'ensorlogs'),
                'new_item_name'     => __('Nuevo stack', 'ensorlogs'),
                'search_items'      => __('Buscar stacks', 'ensorlogs'),
                'popular_items'     => __('Stacks populares', 'ensorlogs'),
                'choose_from_most_used' => __('Elige entre los más usados', 'ensorlogs'),
                'separate_items_with_commas' => __('Separa los stacks con comas', 'ensorlogs'),
                'add_or_remove_items'        => __('Añadir o quitar stacks', 'ensorlogs'),
            ),
            'public'            => true,
            'hierarchical'      => false,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'show_in_nav_menus' => false,
            'rewrite'           => array(
                'slug'         => 'stack',
                'with_front'   => false,
                'hierarchical' => false,
            ),
            'query_var'         => 'ensor_tema',
        )
    );

    // Compatibilidad con URLs antiguas /tema/<slug>/.
    add_rewrite_rule('^tema/([^/]+)/?$', 'index.php?ensor_tema=$matches[1]', 'top');

    add_rewrite_rule('^articulos/([^/]+)\\.html/?$', 'index.php?post_type=ensor_article&name=$matches[1]', 'top');
    add_rewrite_rule('^proyectos/([^/]+)\\.html/?$', 'index.php?post_type=ensor_project&name=$matches[1]', 'top');
}, 0);

/**
 * Asegura que los términos del whitelist existan. Se ejecuta una vez por
 * carga si el option de version está atrasada (idempotente con `wp_insert_term`).
 */
function ensorlogs_seed_taxonomy_terms(): void
{
    if (!taxonomy_exists('ensor_tema')) {
        return;
    }
    $choices = function_exists('ensorlogs_primary_tema_choices')
        ? ensorlogs_primary_tema_choices()
        : array();
    foreach ($choices as $slug => $label) {
        if (term_exists($slug, 'ensor_tema')) {
            continue;
        }
        wp_insert_term($label, 'ensor_tema', array('slug' => $slug));
    }
}

add_action(
    'init',
    static function (): void {
        if (get_template() !== 'ensorlogs') {
            return;
        }
        $key   = 'ensorlogs_tax_seed_version';
        $stored = (int) get_option($key, 0);
        if ($stored >= 1) {
            return;
        }
        ensorlogs_seed_taxonomy_terms();
        update_option($key, 1);
    },
    20
);

/**
 * Sincroniza la meta `_ensor_temas` (string separado por espacios) con la
 * taxonomía `ensor_tema`. Esto permite usar la taxonomía como fuente
 * canónica sin romper el flujo actual donde el usuario edita el string.
 */
function ensorlogs_sync_temas_meta_to_taxonomy(int $post_id, string $value): void
{
    if (!taxonomy_exists('ensor_tema')) {
        return;
    }
    $value = trim($value);
    if ($value === '') {
        wp_set_object_terms($post_id, array(), 'ensor_tema', false);
        return;
    }
    $slugs = array_filter(array_unique(array_map('sanitize_title', preg_split('/\s+/', $value) ?: array())));
    if (empty($slugs)) {
        wp_set_object_terms($post_id, array(), 'ensor_tema', false);
        return;
    }
    foreach ($slugs as $slug) {
        if (!term_exists($slug, 'ensor_tema')) {
            $label = function_exists('ensorlogs_tema_label') ? ensorlogs_tema_label($slug) : ucfirst($slug);
            wp_insert_term($label, 'ensor_tema', array('slug' => $slug));
        }
    }
    wp_set_object_terms($post_id, array_values($slugs), 'ensor_tema', false);
}

add_action(
    'updated_post_meta',
    static function ($meta_id, int $object_id, string $meta_key, $meta_value): void {
        if ($meta_key !== '_ensor_temas') {
            return;
        }
        $post = get_post($object_id);
        if (!$post instanceof WP_Post) {
            return;
        }
        if (!in_array($post->post_type, array('ensor_article', 'ensor_project'), true)) {
            return;
        }
        ensorlogs_sync_temas_meta_to_taxonomy($object_id, is_string($meta_value) ? $meta_value : '');
    },
    10,
    4
);

add_action(
    'added_post_meta',
    static function ($meta_id, int $object_id, string $meta_key, $meta_value): void {
        if ($meta_key !== '_ensor_temas') {
            return;
        }
        $post = get_post($object_id);
        if (!$post instanceof WP_Post) {
            return;
        }
        if (!in_array($post->post_type, array('ensor_article', 'ensor_project'), true)) {
            return;
        }
        ensorlogs_sync_temas_meta_to_taxonomy($object_id, is_string($meta_value) ? $meta_value : '');
    },
    10,
    4
);

/**
 * Migración única: para CPT existentes, si tienen meta `_ensor_temas` pero
 * todavía no tienen términos asignados en `ensor_tema`, los sincroniza.
 */
add_action(
    'init',
    static function (): void {
        if (get_template() !== 'ensorlogs') {
            return;
        }
        $key   = 'ensorlogs_tax_migration_done';
        if ((int) get_option($key, 0) >= 1) {
            return;
        }
        if (!taxonomy_exists('ensor_tema')) {
            return;
        }
        $ids = get_posts(
            array(
                'post_type'      => array('ensor_article', 'ensor_project'),
                'post_status'    => 'any',
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'no_found_rows'  => true,
                'meta_query'     => array(
                    array(
                        'key'     => '_ensor_temas',
                        'compare' => 'EXISTS',
                    ),
                ),
            )
        );
        foreach ($ids as $id) {
            $value = (string) get_post_meta((int) $id, '_ensor_temas', true);
            ensorlogs_sync_temas_meta_to_taxonomy((int) $id, $value);
        }
        update_option($key, 1);
    },
    30
);

/**
 * Redirige URLs legacy con .html a los permalinks canónicos del CPT.
 */
add_action(
    'template_redirect',
    static function (): void {
        if (!is_singular(array('ensor_article', 'ensor_project'))) {
            return;
        }
        $req = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
        $path = (string) wp_parse_url($req, PHP_URL_PATH);
        if ($path === '' || stripos($path, '.html') === false) {
            return;
        }
        $id = (int) get_queried_object_id();
        if ($id <= 0) {
            return;
        }
        $url = get_permalink($id);
        if (!is_string($url) || $url === '') {
            return;
        }
        wp_safe_redirect($url, 301);
        exit;
    },
    0
);

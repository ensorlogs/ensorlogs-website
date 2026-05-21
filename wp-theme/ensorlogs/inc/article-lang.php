<?php
/**
 * Idioma del log (ES / EN) — meta, admin, listados y permalinks.
 *
 * @package Ensorlogs
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ENSORLOGS_ARTICLE_LANG_META', '_ensor_lang');

/**
 * @return string es|en
 */
function ensorlogs_get_post_lang(int $post_id = 0): string
{
    if ($post_id <= 0) {
        $post_id = get_the_ID();
    }
    if ($post_id <= 0) {
        return 'es';
    }
    $lang = (string) get_post_meta($post_id, ENSORLOGS_ARTICLE_LANG_META, true);
    return $lang === 'en' ? 'en' : 'es';
}

/**
 * Etiqueta visible del idioma (fija: Español / English).
 */
function ensorlogs_lang_label(string $lang): string
{
    return $lang === 'en' ? 'English' : 'Español';
}

/**
 * HTML de la etiqueta de idioma en tarjetas de log.
 */
function ensorlogs_lang_badge_html(int $post_id = 0): string
{
    $lang  = ensorlogs_get_post_lang($post_id);
    $label = ensorlogs_lang_label($lang);
    $mod   = $lang === 'en' ? 'en' : 'es';
    return '<span class="ensor-lang-badge ensor-lang-badge--' . esc_attr($mod) . '">' . esc_html($label) . '</span>';
}

/**
 * Meta query para listar logs del idioma activo (los sin meta cuentan como ES).
 *
 * @return array<string, mixed>
 */
function ensorlogs_article_lang_meta_query(): array
{
    $lang = function_exists('ensorlogs_current_lang') ? ensorlogs_current_lang() : 'es';
    if ($lang === 'en') {
        return array(
            array(
                'key'     => ENSORLOGS_ARTICLE_LANG_META,
                'value'   => 'en',
                'compare' => '=',
            ),
        );
    }
    return array(
        'relation' => 'OR',
        array(
            'key'     => ENSORLOGS_ARTICLE_LANG_META,
            'value'   => 'es',
            'compare' => '=',
        ),
        array(
            'key'     => ENSORLOGS_ARTICLE_LANG_META,
            'compare' => 'NOT EXISTS',
        ),
        array(
            'key'     => ENSORLOGS_ARTICLE_LANG_META,
            'value'   => '',
            'compare' => '=',
        ),
    );
}

add_action(
    'init',
    static function (): void {
        $auth = static function ($allowed, string $meta_key, int $object_id): bool {
            if ($object_id > 0) {
                return current_user_can('edit_post', $object_id);
            }
            return current_user_can('edit_posts');
        };
        register_post_meta(
            'ensor_article',
            ENSORLOGS_ARTICLE_LANG_META,
            array(
                'type'              => 'string',
                'single'            => true,
                'default'           => 'es',
                'show_in_rest'      => true,
                'auth_callback'     => $auth,
                'sanitize_callback' => static function ($value): string {
                    return $value === 'en' ? 'en' : 'es';
                },
            )
        );
    },
    20
);

add_action(
    'add_meta_boxes',
    static function (): void {
        add_meta_box(
            'ensor_article_lang',
            __('Idioma del log', 'ensorlogs'),
            'ensorlogs_render_article_lang_metabox',
            'ensor_article',
            'side',
            'high'
        );
    }
);

/**
 * @param WP_Post $post
 */
function ensorlogs_render_article_lang_metabox(WP_Post $post): void
{
    wp_nonce_field('ensorlogs_article_lang', 'ensorlogs_article_lang_nonce');
    $lang = ensorlogs_get_post_lang((int) $post->ID);
    ?>
    <p>
        <label for="ensor_article_lang_select" class="screen-reader-text"><?php esc_html_e('Idioma', 'ensorlogs'); ?></label>
        <select name="ensor_article_lang" id="ensor_article_lang_select" style="width:100%;">
            <option value="es" <?php selected($lang, 'es'); ?>><?php esc_html_e('Español', 'ensorlogs'); ?></option>
            <option value="en" <?php selected($lang, 'en'); ?>><?php esc_html_e('Inglés', 'ensorlogs'); ?></option>
        </select>
    </p>
    <p class="description">
        <?php esc_html_e('Los logs en inglés aparecen en /en/blog/ y llevan la etiqueta «English». Los españoles en el blog principal.', 'ensorlogs'); ?>
    </p>
    <?php
}

add_action(
    'save_post_ensor_article',
    static function (int $post_id): void {
        if (!isset($_POST['ensorlogs_article_lang_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ensorlogs_article_lang_nonce'])), 'ensorlogs_article_lang')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        $lang = isset($_POST['ensor_article_lang']) && $_POST['ensor_article_lang'] === 'en' ? 'en' : 'es';
        update_post_meta($post_id, ENSORLOGS_ARTICLE_LANG_META, $lang);
        delete_transient('ensorlogs_blog_list_v2_es');
        delete_transient('ensorlogs_blog_list_v2_en');
    }
);

add_filter(
    'manage_ensor_article_posts_columns',
    static function (array $columns): array {
        $out = array();
        foreach ($columns as $key => $label) {
            $out[$key] = $label;
            if ($key === 'title') {
                $out['ensor_lang'] = __('Idioma', 'ensorlogs');
            }
        }
        if (!isset($out['ensor_lang'])) {
            $out['ensor_lang'] = __('Idioma', 'ensorlogs');
        }
        return $out;
    }
);

add_action(
    'manage_ensor_article_posts_custom_column',
    static function (string $column, int $post_id): void {
        if ($column !== 'ensor_lang') {
            return;
        }
        echo esc_html(ensorlogs_lang_label(ensorlogs_get_post_lang($post_id)));
    },
    10,
    2
);

add_filter(
    'post_type_link',
    static function (string $post_link, WP_Post $post): string {
        if ($post->post_type !== 'ensor_article') {
            return $post_link;
        }
        if (ensorlogs_get_post_lang((int) $post->ID) === 'en') {
            return home_url('/en/articulos/' . $post->post_name . '/');
        }
        return $post_link;
    },
    10,
    2
);

add_action(
    'init',
    static function (): void {
        add_rewrite_rule(
            '^en/articulos/([^/]+)/?$',
            'index.php?ensor_article=$matches[1]&' . ENSORLOGS_LANG_QUERY_VAR . '=en',
            'top'
        );
    },
    11
);

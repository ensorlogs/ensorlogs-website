<?php
/**
 * Traducciones enlazadas de logs (dos entradas: ES + EN).
 *
 * @package Ensorlogs
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ENSORLOGS_ARTICLE_TRANSLATION_META', '_ensor_translation_peer');

/**
 * ID del log vinculado en el otro idioma (0 si no hay).
 */
function ensorlogs_get_article_peer_id(int $post_id = 0): int
{
    if ($post_id <= 0) {
        $post_id = get_the_ID();
    }
    if ($post_id <= 0) {
        return 0;
    }
    return max(0, (int) get_post_meta($post_id, ENSORLOGS_ARTICLE_TRANSLATION_META, true));
}

/**
 * @return WP_Post|null
 */
function ensorlogs_get_article_peer_post(int $post_id = 0): ?WP_Post
{
    $peer_id = ensorlogs_get_article_peer_id($post_id);
    if ($peer_id <= 0) {
        return null;
    }
    $peer = get_post($peer_id);
    if (!$peer instanceof WP_Post || $peer->post_type !== 'ensor_article') {
        return null;
    }
    return $peer;
}

/**
 * Enlaza dos logs como traducción mutua.
 */
function ensorlogs_link_article_peers(int $post_a, int $post_b): void
{
    if ($post_a <= 0 || $post_b <= 0 || $post_a === $post_b) {
        return;
    }
    update_post_meta($post_a, ENSORLOGS_ARTICLE_TRANSLATION_META, $post_b);
    update_post_meta($post_b, ENSORLOGS_ARTICLE_TRANSLATION_META, $post_a);
    delete_transient('ensorlogs_blog_list_v2_es');
    delete_transient('ensorlogs_blog_list_v2_en');
}

/**
 * Quita el vínculo entre dos logs.
 */
function ensorlogs_unlink_article_peer(int $post_id): void
{
    $peer_id = ensorlogs_get_article_peer_id($post_id);
    delete_post_meta($post_id, ENSORLOGS_ARTICLE_TRANSLATION_META);
    if ($peer_id > 0) {
        delete_post_meta($peer_id, ENSORLOGS_ARTICLE_TRANSLATION_META);
    }
}

/**
 * Idioma opuesto.
 */
function ensorlogs_opposite_article_lang(string $lang): string
{
    return $lang === 'en' ? 'es' : 'en';
}

/**
 * Crea un borrador en el otro idioma y lo vincula.
 *
 * @return int|WP_Error ID del nuevo log
 */
function ensorlogs_create_article_translation(int $source_id, string $target_lang)
{
    $source = get_post($source_id);
    if (!$source instanceof WP_Post || $source->post_type !== 'ensor_article') {
        return new WP_Error('invalid_source', __('Log de origen no válido.', 'ensorlogs'));
    }

    $target_lang = $target_lang === 'en' ? 'en' : 'es';
    $source_lang = ensorlogs_get_post_lang($source_id);
    if ($source_lang === $target_lang) {
        return new WP_Error('same_lang', __('El idioma destino debe ser distinto al de este log.', 'ensorlogs'));
    }

    $existing = ensorlogs_get_article_peer_id($source_id);
    if ($existing > 0 && get_post_status($existing)) {
        return new WP_Error(
            'peer_exists',
            __('Ya existe una traducción vinculada.', 'ensorlogs'),
            array('peer_id' => $existing)
        );
    }

    $new_id = wp_insert_post(
        array(
            'post_type'    => 'ensor_article',
            'post_status'  => 'draft',
            'post_title'   => $source->post_title,
            'post_content' => $source->post_content,
            'post_excerpt' => $source->post_excerpt,
            'post_name'    => $source->post_name,
        ),
        true
    );

    if (is_wp_error($new_id)) {
        return $new_id;
    }

    update_post_meta((int) $new_id, ENSORLOGS_ARTICLE_LANG_META, $target_lang);
    ensorlogs_link_article_peers($source_id, (int) $new_id);

    $thumb = (int) get_post_thumbnail_id($source_id);
    if ($thumb > 0) {
        set_post_thumbnail((int) $new_id, $thumb);
    }

    $taxonomies = get_object_taxonomies('ensor_article');
    foreach ($taxonomies as $taxonomy) {
        $terms = wp_get_object_terms($source_id, $taxonomy, array('fields' => 'ids'));
        if (!is_wp_error($terms) && $terms !== array()) {
            wp_set_object_terms((int) $new_id, $terms, $taxonomy);
        }
    }

    return (int) $new_id;
}

/**
 * Traducción automática (MyMemory + filtro para APIs propias).
 */
function ensorlogs_machine_translate_text(string $text, string $from, string $to): string
{
    $text = trim($text);
    if ($text === '' || $from === $to) {
        return $text;
    }

    $filtered = apply_filters('ensorlogs_machine_translate_text', null, $text, $from, $to);
    if (is_string($filtered) && $filtered !== '') {
        return $filtered;
    }

    if (!preg_match('/\p{L}/u', wp_strip_all_tags($text))) {
        return $text;
    }

    $chunks = ensorlogs_machine_translate_chunk_strings($text, 420);
    $out    = array();
    foreach ($chunks as $chunk) {
        if ($chunk === '' || !preg_match('/\p{L}/u', wp_strip_all_tags($chunk))) {
            $out[] = $chunk;
            continue;
        }
        $translated = ensorlogs_mymemory_translate($chunk, $from, $to);
        $out[]      = $translated !== '' ? $translated : $chunk;
        usleep(120000);
    }

    return implode('', $out);
}

/**
 * Trocea HTML/texto para no superar límites de la API gratuita.
 *
 * @return list<string>
 */
function ensorlogs_machine_translate_chunk_strings(string $html, int $max_len): array
{
    if (strlen($html) <= $max_len) {
        return array($html);
    }

    $parts = preg_split('/(<[^>]+>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
    if (!is_array($parts)) {
        return array($html);
    }

    $chunks  = array();
    $current = '';
    foreach ($parts as $part) {
        if ($part === '') {
            continue;
        }
        if (strlen($current) + strlen($part) > $max_len && $current !== '') {
            $chunks[] = $current;
            $current  = '';
        }
        $current .= $part;
    }
    if ($current !== '') {
        $chunks[] = $current;
    }

    return $chunks !== array() ? $chunks : array($html);
}

/**
 * MyMemory (gratuito; mejor con email en Personalizar para más cuota).
 */
function ensorlogs_mymemory_translate(string $text, string $from, string $to): string
{
    $url  = add_query_arg(
        array(
            'q'        => $text,
            'langpair' => $from . '|' . $to,
        ),
        'https://api.mymemory.translated.net/get'
    );

    $email = trim((string) get_theme_mod('ensor_translation_mymemory_email', ''));
    if ($email !== '' && is_email($email)) {
        $url = add_query_arg('de', $email, $url);
    }

    $response = wp_remote_get(
        $url,
        array(
            'timeout' => 25,
            'headers' => array('Accept' => 'application/json'),
        )
    );

    if (is_wp_error($response)) {
        return '';
    }

    $code = (int) wp_remote_retrieve_response_code($response);
    if ($code < 200 || $code >= 300) {
        return '';
    }

    $body = json_decode((string) wp_remote_retrieve_body($response), true);
    if (!is_array($body) || !isset($body['responseData']['translatedText'])) {
        return '';
    }

    $translated = (string) $body['responseData']['translatedText'];
    if ($translated === '' || strtoupper($translated) === 'INVALID LANGUAGE PAIR SELECTED') {
        return '';
    }

    return $translated;
}

/**
 * Rellena título, extracto y contenido del par con traducción automática.
 *
 * @return true|WP_Error
 */
function ensorlogs_auto_translate_article_pair(int $source_id)
{
    $source = get_post($source_id);
    if (!$source instanceof WP_Post || $source->post_type !== 'ensor_article') {
        return new WP_Error('invalid_source', __('Log no válido.', 'ensorlogs'));
    }

    $peer = ensorlogs_get_article_peer_post($source_id);
    if (!$peer instanceof WP_Post) {
        return new WP_Error('no_peer', __('Vincula o crea primero la versión en el otro idioma.', 'ensorlogs'));
    }

    $from = ensorlogs_get_post_lang($source_id);
    $to   = ensorlogs_get_post_lang((int) $peer->ID);
    if ($from === $to) {
        return new WP_Error('same_lang', __('Ambos logs tienen el mismo idioma.', 'ensorlogs'));
    }

    $title   = ensorlogs_machine_translate_text($source->post_title, $from, $to);
    $excerpt = ensorlogs_machine_translate_text($source->post_excerpt, $from, $to);
    $content = ensorlogs_machine_translate_text($source->post_content, $from, $to);

    if ($title === '' && $source->post_title !== '') {
        return new WP_Error('translate_failed', __('No se pudo traducir. Revisa tu conexión o edita la traducción a mano.', 'ensorlogs'));
    }

    $updated = wp_update_post(
        array(
            'ID'           => (int) $peer->ID,
            'post_title'   => $title !== '' ? $title : $peer->post_title,
            'post_excerpt' => $excerpt !== '' ? $excerpt : $peer->post_excerpt,
            'post_content' => $content !== '' ? $content : $peer->post_content,
        ),
        true
    );

    if (is_wp_error($updated)) {
        return $updated;
    }

    return true;
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
            ENSORLOGS_ARTICLE_TRANSLATION_META,
            array(
                'type'              => 'integer',
                'single'            => true,
                'show_in_rest'      => true,
                'auth_callback'     => $auth,
                'sanitize_callback' => static function ($value): int {
                    return max(0, (int) $value);
                },
            )
        );
    },
    21
);

add_action(
    'wp_trash_post',
    static function (int $post_id): void {
        $post = get_post($post_id);
        if (!$post instanceof WP_Post || $post->post_type !== 'ensor_article') {
            return;
        }
        ensorlogs_unlink_article_peer($post_id);
    }
);

add_action(
    'before_delete_post',
    static function (int $post_id): void {
        $post = get_post($post_id);
        if (!$post instanceof WP_Post || $post->post_type !== 'ensor_article') {
            return;
        }
        ensorlogs_unlink_article_peer($post_id);
    }
);

add_action(
    'wp_ajax_ensorlogs_create_article_translation',
    static function (): void {
        check_ajax_referer('ensorlogs_article_translation', 'nonce');

        $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
        $target  = isset($_POST['target_lang']) ? sanitize_key((string) wp_unslash($_POST['target_lang'])) : '';

        if ($post_id <= 0 || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error(array('message' => __('Sin permiso.', 'ensorlogs')), 403);
        }

        $result = ensorlogs_create_article_translation($post_id, $target);
        if (is_wp_error($result)) {
            $data = array('message' => $result->get_error_message());
            if ($result->get_error_code() === 'peer_exists') {
                $err_data = $result->get_error_data();
                $peer_id  = is_array($err_data) && isset($err_data['peer_id']) ? (int) $err_data['peer_id'] : 0;
                if ($peer_id <= 0) {
                    $peer_id = ensorlogs_get_article_peer_id($post_id);
                }
                if ($peer_id > 0) {
                    $data['editUrl'] = get_edit_post_link($peer_id, 'raw');
                }
            }
            wp_send_json_error($data);
        }

        wp_send_json_success(
            array(
                'peerId'  => $result,
                'editUrl' => get_edit_post_link($result, 'raw'),
                'message' => __('Borrador creado. Revisa y publica la traducción.', 'ensorlogs'),
            )
        );
    }
);

add_action(
    'wp_ajax_ensorlogs_auto_translate_article',
    static function (): void {
        check_ajax_referer('ensorlogs_article_translation', 'nonce');

        $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
        if ($post_id <= 0 || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error(array('message' => __('Sin permiso.', 'ensorlogs')), 403);
        }

        $result = ensorlogs_auto_translate_article_pair($post_id);
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        $peer = ensorlogs_get_article_peer_post($post_id);
        wp_send_json_success(
            array(
                'message' => __('Traducción automática aplicada al borrador vinculado. Revísala antes de publicar.', 'ensorlogs'),
                'editUrl' => $peer instanceof WP_Post ? get_edit_post_link((int) $peer->ID, 'raw') : '',
            )
        );
    }
);

add_action(
    'admin_enqueue_scripts',
    static function (string $hook_suffix): void {
        if (!in_array($hook_suffix, array('post.php', 'post-new.php'), true)) {
            return;
        }
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || $screen->post_type !== 'ensor_article') {
            return;
        }

        global $post;
        $post_id = ($post instanceof WP_Post) ? (int) $post->ID : 0;

        wp_enqueue_script(
            'ensorlogs-article-translations',
            get_template_directory_uri() . '/assets/js/ensor-article-translations.js',
            array('jquery'),
            ENSORLOGS_THEME_VERSION,
            true
        );
        wp_localize_script(
            'ensorlogs-article-translations',
            'EnsorArticleTranslations',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('ensorlogs_article_translation'),
                'postId'  => $post_id,
                'i18n'    => array(
                    'working'     => __('Procesando…', 'ensorlogs'),
                    'createFail'  => __('No se pudo crear la traducción.', 'ensorlogs'),
                    'translateFail' => __('No se pudo traducir automáticamente.', 'ensorlogs'),
                ),
            )
        );
    }
);

add_action(
    'wp_head',
    static function (): void {
        if (!is_singular('ensor_article')) {
            return;
        }
        $peer = ensorlogs_get_article_peer_post((int) get_queried_object_id());
        if (!$peer instanceof WP_Post) {
            return;
        }
        $here = get_permalink();
        $alt  = get_permalink($peer);
        if (!is_string($here) || !is_string($alt) || $here === '' || $alt === '') {
            return;
        }
        $here_lang = ensorlogs_get_post_lang((int) get_queried_object_id());
        if ($here_lang === 'en') {
            echo '<link rel="alternate" hreflang="en" href="' . esc_url($here) . '">' . "\n";
            echo '<link rel="alternate" hreflang="es" href="' . esc_url($alt) . '">' . "\n";
        } else {
            echo '<link rel="alternate" hreflang="es" href="' . esc_url($here) . '">' . "\n";
            echo '<link rel="alternate" hreflang="en" href="' . esc_url($alt) . '">' . "\n";
        }
    },
    4
);

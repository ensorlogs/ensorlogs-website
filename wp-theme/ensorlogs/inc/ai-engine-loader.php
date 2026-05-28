<?php
/**
 * Carga diferida del Ensorlogs AI Engine (evita tumbar themes.php y el front).
 *
 * @package Ensorlogs
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @return bool
 */
function ensorlogs_should_load_ai_engine(): bool
{
    if (defined('ENSORLOGS_DISABLE_AI_ENGINE') && ENSORLOGS_DISABLE_AI_ENGINE) {
        return false;
    }

    if (defined('REST_REQUEST') && REST_REQUEST) {
        $uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
        if (strpos($uri, 'ensorlogs-ai/') !== false) {
            return true;
        }
    }

    if (!is_admin()) {
        return false;
    }

    global $pagenow;
    $pagenow = is_string($pagenow) ? $pagenow : '';

    if ($pagenow === 'options-general.php') {
        $page = isset($_GET['page']) ? sanitize_key((string) wp_unslash($_GET['page'])) : '';
        return $page === 'ensorlogs-ai-engine';
    }

    if (!in_array($pagenow, array('post.php', 'post-new.php'), true)) {
        return false;
    }

    if ($pagenow === 'post-new.php') {
        $post_type = isset($_GET['post_type'])
            ? sanitize_key((string) wp_unslash($_GET['post_type']))
            : 'post';
        return $post_type === 'ensor_article';
    }

    if (!isset($_GET['post'])) {
        return false;
    }

    $post = get_post((int) $_GET['post']);
    return $post instanceof WP_Post && $post->post_type === 'ensor_article';
}

/**
 * Carga e inicia el motor IA una sola vez por request.
 */
function ensorlogs_load_ai_engine(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    if (!ensorlogs_should_load_ai_engine()) {
        return;
    }

    $bootstrap = get_template_directory() . '/plugins/ensorlogs-ai-engine/ensorlogs-ai-engine.php';
    if (!is_readable($bootstrap)) {
        return;
    }

    require_once $bootstrap;

    if (function_exists('ensorlogs_ai_engine_boot')) {
        ensorlogs_ai_engine_boot();
    }
}

add_action('plugins_loaded', 'ensorlogs_load_ai_engine', 20);
add_action('rest_api_init', 'ensorlogs_load_ai_engine', 5);

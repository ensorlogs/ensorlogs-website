<?php
/**
 * Carga diferida del Ensorlogs AI Engine (evita tumbar themes.php y el front).
 *
 * @package Ensorlogs
 */

if (!defined('ABSPATH')) {
    exit;
}

/** Ruta del plugin suelto en wp-content/plugins (provoca "Cannot redeclare class" si sigue activo). */
define('ENSORLOGS_AI_ENGINE_PLUGIN_SLUG', 'ensorlogs-ai-engine/ensorlogs-ai-engine.php');

/**
 * Desactiva copia suelta del plugin IA si está activa (el motor va dentro del tema).
 */
function ensorlogs_deactivate_standalone_ai_plugin(): void
{
    if (!function_exists('is_plugin_active')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    if (!is_plugin_active(ENSORLOGS_AI_ENGINE_PLUGIN_SLUG)) {
        return;
    }
    deactivate_plugins(ENSORLOGS_AI_ENGINE_PLUGIN_SLUG, true);
}

add_action('plugins_loaded', 'ensorlogs_deactivate_standalone_ai_plugin', 1);

/**
 * Logs: editor clásico + panel ENSORLOGS AI ENGINE encima del contenido.
 */
add_filter(
    'use_block_editor_for_post_type',
    static function ($use_block_editor, $post_type) {
        if ($post_type === 'ensor_article') {
            return false;
        }
        return $use_block_editor;
    },
    100,
    2
);

add_filter(
    'use_block_editor_for_post',
    static function ($use_block_editor, $post) {
        if ($post instanceof WP_Post && $post->post_type === 'ensor_article') {
            return false;
        }
        return $use_block_editor;
    },
    100,
    2
);

/**
 * @return bool
 */
function ensorlogs_should_load_ai_engine_for_rest(): bool
{
    if (defined('ENSORLOGS_DISABLE_AI_ENGINE') && ENSORLOGS_DISABLE_AI_ENGINE) {
        return false;
    }
    if (!defined('REST_REQUEST') || !REST_REQUEST) {
        return false;
    }
    $uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
    return strpos($uri, 'ensorlogs-ai/') !== false;
}

/**
 * @return bool
 */
function ensorlogs_is_log_editor_admin_screen(): bool
{
    if (!is_admin()) {
        return false;
    }
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    return $screen instanceof WP_Screen
        && $screen->base === 'post'
        && $screen->post_type === 'ensor_article';
}

/**
 * Carga e inicia el motor IA (una vez por request).
 */
function ensorlogs_require_ai_engine_boot(): void
{
    static $booted = false;
    if ($booted) {
        return;
    }

    if (defined('ENSORLOGS_DISABLE_AI_ENGINE') && ENSORLOGS_DISABLE_AI_ENGINE) {
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

    $booted = true;
}

/**
 * @param WP_Screen|null $screen
 */
function ensorlogs_boot_ai_engine_for_screen($screen): void
{
    if (!$screen instanceof WP_Screen) {
        return;
    }

    if ($screen->base === 'post' && $screen->post_type === 'ensor_article') {
        ensorlogs_require_ai_engine_boot();
    }
}

add_action(
    'admin_enqueue_scripts',
    static function (string $hook_suffix): void {
        if (!in_array($hook_suffix, array('post.php', 'post-new.php'), true)) {
            return;
        }
        if (!ensorlogs_is_log_editor_admin_screen()) {
            return;
        }
        ensorlogs_require_ai_engine_boot();
    },
    1
);

add_action(
    'current_screen',
    static function (): void {
        ensorlogs_boot_ai_engine_for_screen(get_current_screen());
    },
    20
);

add_action(
    'rest_api_init',
    static function (): void {
        if (ensorlogs_should_load_ai_engine_for_rest()) {
            ensorlogs_require_ai_engine_boot();
        }
    },
    5
);

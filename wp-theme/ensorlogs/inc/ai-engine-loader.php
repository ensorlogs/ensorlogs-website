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
 * Logs usan editor clásico para el panel ENSORLOGS AI ENGINE encima del contenido.
 */
add_filter(
    'use_block_editor_for_post_type',
    static function ($use_block_editor, $post_type) {
        if ($post_type === 'ensor_article') {
            return false;
        }
        return $use_block_editor;
    },
    10,
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
 * Arranca el motor IA en pantallas de admin que lo necesitan.
 *
 * @param WP_Screen|null $screen
 */
function ensorlogs_boot_ai_engine_for_screen($screen): void
{
    if (!$screen instanceof WP_Screen) {
        return;
    }

    if ($screen->base === 'post' && $screen->post_type === 'ensor_article') {
        ensorlogs_require_ai_engine_boot();
        return;
    }

    if ($screen->id === 'settings_page_ensorlogs-ai-engine') {
        ensorlogs_require_ai_engine_boot();
    }
}

add_action(
    'current_screen',
    static function (): void {
        ensorlogs_boot_ai_engine_for_screen(get_current_screen());
    },
    5
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

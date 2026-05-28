<?php
/**
 * Plugin Name: Ensorlogs AI Engine
 * Description: Panel de IA para generar Logs editoriales dentro del CPT ensor_article sin tocar el frontend.
 * Version: 0.1.8
 * Author: Ensorlogs
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ENSORLOGS_AI_ENGINE_VERSION', '0.1.8');
define('ENSORLOGS_AI_ENGINE_FILE', __FILE__);
define('ENSORLOGS_AI_ENGINE_DIR', plugin_dir_path(__FILE__));

$eae_theme_root = function_exists('get_template_directory') ? get_template_directory() : '';
if ($eae_theme_root !== '' && strpos(__FILE__, $eae_theme_root) === 0) {
    define('ENSORLOGS_AI_ENGINE_URL', trailingslashit(get_template_directory_uri()) . 'plugins/ensorlogs-ai-engine/');
} else {
    define('ENSORLOGS_AI_ENGINE_URL', plugin_dir_url(__FILE__));
}

/** Nombres de opción WP (no son secretos; el valor vive en la base de datos). */
if (!defined('EAE_WP_OPTION_OPENAI')) {
    define('EAE_WP_OPTION_OPENAI', 'ensorlogs_ai_openai_api_key');
}
if (!defined('EAE_WP_OPTION_MODEL')) {
    define('EAE_WP_OPTION_MODEL', 'ensorlogs_ai_openai_model');
}

/**
 * Carga includes del motor IA; si falta un archivo no tumba todo WordPress.
 */
function ensorlogs_ai_engine_load_includes(): bool
{
    $base  = ENSORLOGS_AI_ENGINE_DIR . 'includes/';
    $files = array(
        'class-eae-config.php',
        'editorial-manual.php',
        'class-eae-prompt.php',
        'class-eae-openai.php',
        'class-eae-rest.php',
        'class-eae-admin.php',
        'eae-inline-boot.php',
    );

    foreach ($files as $file) {
        if (!is_readable($base . $file)) {
            if (function_exists('error_log')) {
                error_log('Ensorlogs AI Engine: archivo no encontrado — ' . $file);
            }
            return false;
        }
    }

    foreach ($files as $file) {
        require_once $base . $file;
    }

    return class_exists('EAE_Admin', false) && class_exists('EAE_Rest', false);
}

if (!ensorlogs_ai_engine_load_includes()) {
    return;
}

$boot = static function (): void {
    EAE_Admin::init();
    EAE_Rest::init();
};

if (did_action('plugins_loaded')) {
    $boot();
} else {
    add_action('plugins_loaded', $boot);
}

<?php
/**
 * Plugin Name: Ensorlogs AI Engine
 * Description: Panel de IA para generar Logs editoriales dentro del CPT ensor_article sin tocar el frontend.
 * Version: 0.1.11
 * Author: Ensorlogs
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ENSORLOGS_AI_ENGINE_VERSION', '0.1.11');
define('ENSORLOGS_AI_ENGINE_FILE', __FILE__);
define('ENSORLOGS_AI_ENGINE_DIR', plugin_dir_path(__FILE__));

$eae_theme_root = function_exists('get_template_directory') ? get_template_directory() : '';
if ($eae_theme_root !== '' && strpos(__FILE__, $eae_theme_root) === 0) {
    define('ENSORLOGS_AI_ENGINE_URL', trailingslashit(get_template_directory_uri()) . 'plugins/ensorlogs-ai-engine/');
} else {
    define('ENSORLOGS_AI_ENGINE_URL', plugin_dir_url(__FILE__));
}

/**
 * Detecta mezcla de archivos 1.10.44 (EAE_Config) que provoca fatal en admin_init.
 */
function ensorlogs_ai_engine_has_incompatible_mix(): bool
{
    $base = ENSORLOGS_AI_ENGINE_DIR . 'includes/';
    $check = array('class-eae-admin.php', 'class-eae-rest.php');
    foreach ($check as $file) {
        $path = $base . $file;
        if (!is_readable($path)) {
            continue;
        }
        $src = file_get_contents($path);
        if (is_string($src) && strpos($src, 'EAE_Config') !== false) {
            return true;
        }
    }
    return is_readable($base . 'class-eae-config.php');
}

/**
 * Carga includes del motor IA; si falta un archivo no tumba WordPress.
 */
function ensorlogs_ai_engine_load_includes(): bool
{
    if (ensorlogs_ai_engine_has_incompatible_mix()) {
        if (function_exists('error_log')) {
            error_log(
                'Ensorlogs AI Engine: archivos mezclados (restos de 1.10.44). ' .
                'Borra wp-content/themes/ensorlogs y sube el zip completo del tema.'
            );
        }
        return false;
    }

    $base  = ENSORLOGS_AI_ENGINE_DIR . 'includes/';
    $files = array(
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

/**
 * Inicia hooks del motor IA (llamado desde inc/ai-engine-loader.php).
 */
function ensorlogs_ai_engine_boot(): void
{
    static $booted = false;
    if ($booted) {
        return;
    }

    if (!ensorlogs_ai_engine_load_includes()) {
        if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log('Ensorlogs AI Engine: no se cargó; el sitio sigue activo sin panel IA.');
        }
        return;
    }

    $booted = true;
    EAE_Admin::init();
    EAE_Rest::init();
}

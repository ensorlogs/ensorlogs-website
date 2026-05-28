<?php
/**
 * Plugin Name: Ensorlogs AI Engine
 * Description: Panel de IA para generar Logs editoriales dentro del CPT ensor_article sin tocar el frontend.
 * Version: 0.1.0
 * Author: Ensorlogs
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ENSORLOGS_AI_ENGINE_VERSION', '0.1.6');
define('ENSORLOGS_AI_ENGINE_FILE', __FILE__);
define('ENSORLOGS_AI_ENGINE_DIR', plugin_dir_path(__FILE__));
$eae_theme_root = function_exists('get_template_directory') ? get_template_directory() : '';
if ($eae_theme_root !== '' && strpos(__FILE__, $eae_theme_root) === 0) {
    define('ENSORLOGS_AI_ENGINE_URL', trailingslashit(get_template_directory_uri()) . 'plugins/ensorlogs-ai-engine/');
} else {
    define('ENSORLOGS_AI_ENGINE_URL', plugin_dir_url(__FILE__));
}

require_once ENSORLOGS_AI_ENGINE_DIR . 'includes/editorial-manual.php';
require_once ENSORLOGS_AI_ENGINE_DIR . 'includes/class-eae-prompt.php';
require_once ENSORLOGS_AI_ENGINE_DIR . 'includes/class-eae-openai.php';
require_once ENSORLOGS_AI_ENGINE_DIR . 'includes/class-eae-rest.php';
require_once ENSORLOGS_AI_ENGINE_DIR . 'includes/class-eae-admin.php';
require_once ENSORLOGS_AI_ENGINE_DIR . 'includes/eae-inline-boot.php';

$boot = static function (): void {
    EAE_Admin::init();
    EAE_Rest::init();
};

if (did_action('plugins_loaded')) {
    $boot();
} else {
    add_action('plugins_loaded', $boot);
}

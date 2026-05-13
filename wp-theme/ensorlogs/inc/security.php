<?php
/**
 * Endurecimiento básico: XML-RPC, REST API, cabeceras y enumeración de usuarios.
 *
 * Filosofía: defensa en profundidad. Cada filtro es reversible mediante un
 * filtro propio del tema (`ensorlogs_disable_<feature>`) para que sea fácil
 * activarlo / desactivarlo desde un plugin auxiliar o desde `wp-config.php`.
 *
 * @package Ensorlogs
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * XML-RPC fuera. Si necesitas Jetpack o apps móviles que dependan de XML-RPC,
 * define `ENSORLOGS_ALLOW_XMLRPC` a true en wp-config.php (o devuelve true en
 * el filtro `ensorlogs_disable_xmlrpc`).
 */
add_filter(
    'xmlrpc_enabled',
    static function ($enabled) {
        if (defined('ENSORLOGS_ALLOW_XMLRPC') && ENSORLOGS_ALLOW_XMLRPC) {
            return $enabled;
        }
        if (!apply_filters('ensorlogs_disable_xmlrpc', true)) {
            return $enabled;
        }
        return false;
    }
);

add_filter(
    'wp_headers',
    static function (array $headers): array {
        if (defined('ENSORLOGS_ALLOW_XMLRPC') && ENSORLOGS_ALLOW_XMLRPC) {
            return $headers;
        }
        if (!apply_filters('ensorlogs_disable_xmlrpc', true)) {
            return $headers;
        }
        unset($headers['X-Pingback']);
        return $headers;
    }
);

add_filter('pings_open', '__return_false', 9999);
add_filter('pre_option_default_pingback_flag', '__return_zero');

/**
 * Bloquea la enumeración de usuarios vía REST cuando el visitante no está
 * autenticado, y vía permalinks `?author=N`. Útil contra fuerza bruta.
 */
add_filter(
    'rest_endpoints',
    static function (array $endpoints): array {
        if (!apply_filters('ensorlogs_block_rest_users', true)) {
            return $endpoints;
        }
        if (is_user_logged_in()) {
            return $endpoints;
        }
        if (isset($endpoints['/wp/v2/users'])) {
            unset($endpoints['/wp/v2/users']);
        }
        if (isset($endpoints['/wp/v2/users/(?P<id>[\d]+)'])) {
            unset($endpoints['/wp/v2/users/(?P<id>[\d]+)']);
        }
        return $endpoints;
    }
);

add_action(
    'template_redirect',
    static function (): void {
        if (!apply_filters('ensorlogs_block_author_enum', true)) {
            return;
        }
        if (is_admin() || is_user_logged_in()) {
            return;
        }
        if (isset($_GET['author']) && (string) $_GET['author'] !== '') {
            wp_safe_redirect(home_url('/'), 301);
            exit;
        }
    }
);

/**
 * Cabeceras de seguridad sensatas. No incluimos `Content-Security-Policy`
 * porque definirla mal rompe scripts inline (analytics, Elementor, etc.).
 */
add_action(
    'send_headers',
    static function (): void {
        if (is_admin()) {
            return;
        }
        if (!apply_filters('ensorlogs_security_headers', true)) {
            return;
        }
        if (!headers_sent()) {
            header('X-Content-Type-Options: nosniff');
            header('Referrer-Policy: strict-origin-when-cross-origin');
            header('X-Frame-Options: SAMEORIGIN');
            header(
                'Permissions-Policy: ' .
                'accelerometer=(), camera=(), geolocation=(), gyroscope=(), ' .
                'magnetometer=(), microphone=(), payment=(), usb=()'
            );
            if (is_ssl()) {
                header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
            }
        }
    },
    1
);

/**
 * Si WP_DEBUG_DISPLAY no se ha forzado a false en wp-config.php, lo apagamos
 * para que los errores PHP no se filtren al front. WP_DEBUG_LOG sigue
 * funcionando si está activo.
 */
add_action(
    'plugins_loaded',
    static function (): void {
        if (!apply_filters('ensorlogs_hide_php_errors', true)) {
            return;
        }
        if (defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY === false) {
            return;
        }
        if (is_admin() || (defined('WP_DEBUG') && WP_DEBUG && !defined('WP_DEBUG_DISPLAY'))) {
            return;
        }
        @ini_set('display_errors', '0');
    },
    1
);

/**
 * Limitador suave de intentos de login por IP. Bloquea 15 min tras 6 fallos
 * consecutivos. Si tienes un plugin (Wordfence, Limit Login Attempts) ya
 * activo, devuelve `false` en `ensorlogs_throttle_login`.
 */
function ensorlogs_login_client_ip(): string
{
    $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

function ensorlogs_login_throttle_key(string $ip): string
{
    return 'ensorlogs_login_fail_' . md5($ip);
}

add_filter(
    'authenticate',
    static function ($user, $username, $password) {
        if (!apply_filters('ensorlogs_throttle_login', true)) {
            return $user;
        }
        if ($username === '' || $password === '') {
            return $user;
        }
        $ip      = ensorlogs_login_client_ip();
        $key     = ensorlogs_login_throttle_key($ip);
        $entry   = get_transient($key);
        $count   = is_array($entry) && isset($entry['n']) ? (int) $entry['n'] : 0;
        $blocked = is_array($entry) && isset($entry['blocked']) && (int) $entry['blocked'] > time();
        if ($blocked) {
            return new WP_Error(
                'ensorlogs_login_blocked',
                __('Demasiados intentos fallidos. Espera 15 minutos antes de volver a intentarlo.', 'ensorlogs')
            );
        }
        return $user;
    },
    1,
    3
);

add_action(
    'wp_login_failed',
    static function (string $username): void {
        if (!apply_filters('ensorlogs_throttle_login', true)) {
            return;
        }
        $ip    = ensorlogs_login_client_ip();
        $key   = ensorlogs_login_throttle_key($ip);
        $entry = get_transient($key);
        $count = is_array($entry) && isset($entry['n']) ? (int) $entry['n'] : 0;
        $count++;
        $blocked = 0;
        if ($count >= 6) {
            $blocked = time() + 15 * MINUTE_IN_SECONDS;
        }
        set_transient(
            $key,
            array('n' => $count, 'blocked' => $blocked),
            HOUR_IN_SECONDS
        );
    }
);

add_action(
    'wp_login',
    static function (string $user_login, $user): void {
        $ip = ensorlogs_login_client_ip();
        delete_transient(ensorlogs_login_throttle_key($ip));
    },
    10,
    2
);

/**
 * Quita la versión de WordPress de RSS y otros sitios secundarios.
 */
add_filter('the_generator', '__return_empty_string');

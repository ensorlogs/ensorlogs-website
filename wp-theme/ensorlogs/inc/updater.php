<?php
/**
 * Auto-actualización del tema desde GitHub Releases (solo versión visible al público).
 *
 * Los releases muestran el tag vX.Y.Z y las notas, sin adjuntar ensorlogs.zip.
 * La actualización en WordPress usa la API de GitHub con token (wp-config.php).
 *
 * define('ENSORLOGS_GITHUB_TOKEN', 'ghp_...'); // obligatorio para actualizar
 * define('ENSORLOGS_GITHUB_REPO', 'ensorlogs/ensorlogs-website'); // opcional
 *
 * Repo privado recomendado: el código del tema no se descarga sin el token.
 *
 * @package Ensorlogs
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('ENSORLOGS_GITHUB_REPO')) {
    define('ENSORLOGS_GITHUB_REPO', 'ensorlogs/ensorlogs-website');
}
if (!defined('ENSORLOGS_GITHUB_ASSET')) {
    define('ENSORLOGS_GITHUB_ASSET', 'ensorlogs.zip');
}

/** Esquema interno para que WordPress no use URLs públicas de assets. */
define('ENSORLOGS_GH_PACKAGE_SCHEME', 'ensorlogs-gh-tag://');

/** @var string|null Último error de la API de GitHub (panel de mantenimiento). */
$GLOBALS['ensorlogs_github_last_error'] = null;

/**
 * @return array<string, string>
 */
function ensorlogs_github_headers(): array
{
    $headers = array(
        'Accept'               => 'application/vnd.github+json',
        'X-GitHub-Api-Version' => '2022-11-28',
        'User-Agent'           => 'Ensorlogs-Theme-Updater',
    );
    if (ensorlogs_github_token_configured()) {
        $headers['Authorization'] = 'Bearer ' . ENSORLOGS_GITHUB_TOKEN;
    }
    return $headers;
}

function ensorlogs_github_token_configured(): bool
{
    return defined('ENSORLOGS_GITHUB_TOKEN') && ENSORLOGS_GITHUB_TOKEN !== '';
}

/**
 * Mensaje legible del último fallo al consultar GitHub.
 */
function ensorlogs_github_get_last_error(): string
{
    global $ensorlogs_github_last_error;
    return is_string($ensorlogs_github_last_error) ? $ensorlogs_github_last_error : '';
}

/**
 * @param array<string, mixed> $args
 * @return array{code: int, body: mixed, error: string}
 */
function ensorlogs_github_api_get(string $path, array $args = array()): array
{
    global $ensorlogs_github_last_error;

    $repo = (string) apply_filters('ensorlogs_github_repo', ENSORLOGS_GITHUB_REPO);
    if ($repo === '') {
        $ensorlogs_github_last_error = __('Repositorio GitHub vacío (`ENSORLOGS_GITHUB_REPO`).', 'ensorlogs');
        return array('code' => 0, 'body' => null, 'error' => $ensorlogs_github_last_error);
    }

    $query = !empty($args) ? '?' . http_build_query($args) : '';
    $url   = sprintf(
        'https://api.github.com/repos/%s%s',
        ensorlogs_rawurlencode_path($repo),
        $path . $query
    );

    $response = wp_remote_get(
        $url,
        array(
            'timeout' => 20,
            'headers' => ensorlogs_github_headers(),
        )
    );

    if (is_wp_error($response)) {
        $ensorlogs_github_last_error = $response->get_error_message();
        return array('code' => 0, 'body' => null, 'error' => $ensorlogs_github_last_error);
    }

    $code = (int) wp_remote_retrieve_response_code($response);
    $raw  = (string) wp_remote_retrieve_body($response);
    $body = json_decode($raw, true);

    if ($code === 403) {
        $ensorlogs_github_last_error = __(
            'GitHub devolvió 403. Añade `ENSORLOGS_GITHUB_TOKEN` en wp-config.php (PAT con scope repo).',
            'ensorlogs'
        );
        return array('code' => $code, 'body' => $body, 'error' => $ensorlogs_github_last_error);
    }

    if ($code === 404) {
        $ensorlogs_github_last_error = __(
            'No hay releases publicados. Publica un tag `vX.Y.Z` (workflow Release Theme).',
            'ensorlogs'
        );
        return array('code' => $code, 'body' => $body, 'error' => $ensorlogs_github_last_error);
    }

    if ($code < 200 || $code >= 300) {
        $msg = is_array($body) && isset($body['message']) ? (string) $body['message'] : '';
        $ensorlogs_github_last_error = sprintf(
            /* translators: 1: HTTP code, 2: API message */
            __('GitHub respondió %1$d%2$s', 'ensorlogs'),
            $code,
            $msg !== '' ? ': ' . $msg : ''
        );
        return array('code' => $code, 'body' => $body, 'error' => $ensorlogs_github_last_error);
    }

    $ensorlogs_github_last_error = null;
    return array('code' => $code, 'body' => $body, 'error' => '');
}

/**
 * @param array<int, array<string, mixed>> $releases
 * @return array<string, mixed>|null
 */
function ensorlogs_github_pick_best_release(array $releases): ?array
{
    $best     = null;
    $best_ver = '0';

    foreach ($releases as $release) {
        if (!is_array($release)) {
            continue;
        }
        if (!empty($release['draft']) || !empty($release['prerelease'])) {
            continue;
        }
        $tag = isset($release['tag_name']) ? (string) $release['tag_name'] : '';
        if ($tag === '') {
            continue;
        }
        $ver = ensorlogs_normalize_version($tag);
        if ($best === null || version_compare($ver, $best_ver, '>')) {
            $best     = $release;
            $best_ver = $ver;
        }
    }

    return $best;
}

/**
 * @return array<string, mixed>|null
 */
function ensorlogs_github_get_latest_release(bool $force = false): ?array
{
    $key = 'ensorlogs_gh_release';
    if (!$force) {
        $cached = get_transient($key);
        if (is_array($cached)) {
            return $cached;
        }
    }

    $release = null;
    $result  = ensorlogs_github_api_get('/releases/latest');

    if ($result['code'] === 200 && is_array($result['body']) && !empty($result['body']['tag_name'])) {
        $release = $result['body'];
    }

    if ($release === null) {
        $list = ensorlogs_github_api_get('/releases', array('per_page' => 30));
        if ($list['code'] === 200 && is_array($list['body'])) {
            $release = ensorlogs_github_pick_best_release($list['body']);
        }
    }

    if ($release === null) {
        delete_transient($key);
        return null;
    }

    set_transient($key, $release, 6 * HOUR_IN_SECONDS);
    return $release;
}

function ensorlogs_rawurlencode_path(string $path): string
{
    $parts = explode('/', $path);
    return implode('/', array_map('rawurlencode', $parts));
}

function ensorlogs_normalize_version(string $tag): string
{
    $tag = trim($tag);
    if ($tag !== '' && (strncmp($tag, 'v', 1) === 0 || strncmp($tag, 'V', 1) === 0)) {
        $tag = substr($tag, 1);
    }
    return $tag;
}

/**
 * Identificador interno para el upgrader (no expone browser_download_url).
 */
function ensorlogs_github_package_descriptor(array $release): string
{
    $tag = isset($release['tag_name']) ? (string) $release['tag_name'] : '';
    if ($tag === '') {
        return '';
    }
    return ENSORLOGS_GH_PACKAGE_SCHEME . $tag;
}

/**
 * @deprecated Solo compatibilidad; no devuelve URLs públicas de assets.
 */
function ensorlogs_github_package_url(array $release): string
{
    return ensorlogs_github_package_descriptor($release);
}

/**
 * Localiza wp-theme/ensorlogs dentro del zipball de GitHub.
 */
function ensorlogs_locate_theme_dir_in_tree(string $root): ?string
{
    $root = trailingslashit($root);
    $direct = array(
        $root . 'wp-theme/ensorlogs',
        $root . 'ensorlogs',
    );
    foreach ($direct as $path) {
        if (is_dir($path) && is_readable($path . '/style.css')) {
            return $path;
        }
    }

    $entries = @scandir($root);
    if (!is_array($entries)) {
        return null;
    }
    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $nested = $root . $entry;
        if (!is_dir($nested)) {
            continue;
        }
        $theme = $nested . '/wp-theme/ensorlogs';
        if (is_dir($theme) && is_readable($theme . '/style.css')) {
            return $theme;
        }
        if (is_dir($nested . '/ensorlogs') && is_readable($nested . '/ensorlogs/style.css')) {
            return $nested . '/ensorlogs';
        }
    }

    return null;
}

/**
 * Empaqueta el directorio del tema como ensorlogs.zip listo para Theme_Upgrader.
 *
 * @return string|WP_Error Ruta al zip temporal.
 */
function ensorlogs_github_build_package_zip_from_tag(string $tag)
{
    if (!ensorlogs_github_token_configured()) {
        return new WP_Error(
            'ensorlogs_no_github_token',
            __(
                'Define `ENSORLOGS_GITHUB_TOKEN` en wp-config.php para actualizar el tema desde GitHub.',
                'ensorlogs'
            )
        );
    }

    $repo = (string) apply_filters('ensorlogs_github_repo', ENSORLOGS_GITHUB_REPO);
    $url  = sprintf(
        'https://api.github.com/repos/%s/zipball/%s',
        ensorlogs_rawurlencode_path($repo),
        rawurlencode($tag)
    );

    $response = wp_remote_get(
        $url,
        array(
            'timeout'  => 300,
            'headers'  => array_merge(
                ensorlogs_github_headers(),
                array('Accept' => 'application/vnd.github+json')
            ),
            'stream'   => true,
            'filename' => wp_tempnam('ensorlogs-src'),
        )
    );

    if (is_wp_error($response)) {
        return $response;
    }

    $code = (int) wp_remote_retrieve_response_code($response);
    if ($code < 200 || $code >= 300) {
        return new WP_Error(
            'ensorlogs_github_download',
            sprintf(
                /* translators: %d: HTTP status */
                __('No se pudo descargar el código del tag (%d).', 'ensorlogs'),
                $code
            )
        );
    }

    $src_zip = $response['filename'] ?? '';
    if ($src_zip === '' || !is_readable($src_zip)) {
        return new WP_Error('ensorlogs_github_download', __('Descarga vacía desde GitHub.', 'ensorlogs'));
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';

    $unpacked = wp_tempnam('ensorlogs-unpack');
    if (!$unpacked) {
        return new WP_Error('ensorlogs_temp', __('No se pudo crear carpeta temporal.', 'ensorlogs'));
    }
    @unlink($unpacked);

    $dest_dir = $unpacked . '-dir';
    wp_mkdir_p($dest_dir);

    $unzip = unzip_file($src_zip, $dest_dir);
    @unlink($src_zip);

    if (is_wp_error($unzip)) {
        return $unzip;
    }

    $theme_dir = ensorlogs_locate_theme_dir_in_tree($dest_dir);
    if ($theme_dir === null) {
        return new WP_Error(
            'ensorlogs_theme_path',
            __('El zip de GitHub no contiene wp-theme/ensorlogs.', 'ensorlogs')
        );
    }

    if (!class_exists('ZipArchive')) {
        return new WP_Error('ensorlogs_zip', __('PHP ZipArchive no está disponible en el servidor.', 'ensorlogs'));
    }

    $out_zip = wp_tempnam('ensorlogs-package');
    if ($out_zip === false) {
        return new WP_Error('ensorlogs_temp', __('No se pudo crear el zip del tema.', 'ensorlogs'));
    }
    @unlink($out_zip);

    $zip = new ZipArchive();
    if ($zip->open($out_zip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        return new WP_Error('ensorlogs_zip', __('No se pudo abrir el zip de salida.', 'ensorlogs'));
    }

    $slug   = get_template();
    $parent = $slug . '/';
    $zip->addEmptyDir($slug);

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($theme_dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $file) {
        if (!$file->isFile()) {
            continue;
        }
        $full = $file->getPathname();
        $rel  = $parent . substr($full, strlen(trailingslashit($theme_dir)));
        $zip->addFile($full, str_replace('\\', '/', $rel));
    }

    $zip->close();

    return $out_zip;
}

add_filter(
    'pre_set_site_transient_update_themes',
    static function ($transient) {
        if (!is_object($transient)) {
            return $transient;
        }
        if (!ensorlogs_github_token_configured()) {
            return $transient;
        }
        $release = ensorlogs_github_get_latest_release();
        if ($release === null) {
            return $transient;
        }
        $tag = isset($release['tag_name']) ? (string) $release['tag_name'] : '';
        if ($tag === '') {
            return $transient;
        }
        $new_version = ensorlogs_normalize_version($tag);
        $current     = defined('ENSORLOGS_THEME_VERSION') ? ENSORLOGS_THEME_VERSION : '0';
        if (version_compare($new_version, $current, '<=')) {
            return $transient;
        }
        $package = ensorlogs_github_package_descriptor($release);
        if ($package === '') {
            return $transient;
        }
        $slug = get_template();
        if (!isset($transient->response)) {
            $transient->response = array();
        }
        $transient->response[ $slug ] = array(
            'theme'       => $slug,
            'new_version' => $new_version,
            'url'         => isset($release['html_url']) ? (string) $release['html_url'] : '',
            'package'     => $package,
        );
        return $transient;
    }
);

add_filter(
    'upgrader_pre_download',
    static function ($reply, $package, $upgrader, $hook_extra) {
        if (!is_string($package) || strpos($package, ENSORLOGS_GH_PACKAGE_SCHEME) !== 0) {
            return $reply;
        }
        if (!isset($hook_extra['theme']) || $hook_extra['theme'] !== get_template()) {
            return $reply;
        }

        $tag = substr($package, strlen(ENSORLOGS_GH_PACKAGE_SCHEME));
        if ($tag === '') {
            return new WP_Error('ensorlogs_package', __('Tag de release inválido.', 'ensorlogs'));
        }

        return ensorlogs_github_build_package_zip_from_tag($tag);
    },
    10,
    4
);

add_filter(
    'upgrader_source_selection',
    static function ($source, $remote_source, $upgrader, $hook_extra) {
        if (!is_string($source) || $source === '') {
            return $source;
        }
        if (!isset($hook_extra['theme']) || $hook_extra['theme'] !== get_template()) {
            return $source;
        }
        global $wp_filesystem;
        if (!$wp_filesystem) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }
        $expected = trailingslashit(dirname($source)) . get_template();
        if (rtrim($source, '/\\') === rtrim($expected, '/\\')) {
            return $source;
        }
        if ($wp_filesystem->move(rtrim($source, '/\\'), rtrim($expected, '/\\'), true)) {
            return trailingslashit($expected);
        }
        return $source;
    },
    10,
    4
);

add_action(
    'admin_post_ensorlogs_check_update',
    static function (): void {
        if (!current_user_can('update_themes')) {
            wp_die(esc_html__('Sin permisos.', 'ensorlogs'), '', array('response' => 403));
        }
        check_admin_referer('ensorlogs_check_update');
        delete_transient('ensorlogs_gh_release');
        ensorlogs_github_get_latest_release(true);
        delete_site_transient('update_themes');
        wp_safe_redirect(
            add_query_arg(
                array('page' => 'ensorlogs-seed', 'checked' => '1'),
                admin_url('themes.php')
            )
        );
        exit;
    }
);

<?php
/**
 * Auto-actualización del tema desde GitHub Releases.
 *
 * Publica un release en GitHub con el asset `ensorlogs.zip` (tag vX.Y.Z).
 * El workflow `.github/workflows/release-theme.yml` lo genera al pushear el tag.
 *
 * Configuración: `ENSORLOGS_GITHUB_REPO` (`owner/repo`) en wp-config.php.
 * Repos privados: `ENSORLOGS_GITHUB_TOKEN` (PAT con scope `repo` o `public_repo`).
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

/** @var string|null Último error de la API de GitHub (para el panel de mantenimiento). */
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
    if (defined('ENSORLOGS_GITHUB_TOKEN') && ENSORLOGS_GITHUB_TOKEN !== '') {
        $headers['Authorization'] = 'Bearer ' . ENSORLOGS_GITHUB_TOKEN;
    }
    return $headers;
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
            'GitHub devolvió 403 (límite de API o repo privado). Define `ENSORLOGS_GITHUB_TOKEN` en wp-config.php.',
            'ensorlogs'
        );
        return array('code' => $code, 'body' => $body, 'error' => $ensorlogs_github_last_error);
    }

    if ($code === 404) {
        $ensorlogs_github_last_error = __(
            'No hay releases publicados en el repo. Publica un tag `vX.Y.Z` con el workflow de release (asset ensorlogs.zip).',
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
 * Elige el release más reciente que tenga el zip del tema.
 *
 * @param array<int, array<string, mixed>> $releases
 * @return array<string, mixed>|null
 */
function ensorlogs_github_pick_best_release(array $releases): ?array
{
    $asset_name = (string) apply_filters('ensorlogs_github_asset_name', ENSORLOGS_GITHUB_ASSET);
    $best       = null;
    $best_ver   = '0';

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
        $has_zip = false;
        if (!empty($release['assets']) && is_array($release['assets'])) {
            foreach ($release['assets'] as $asset) {
                if (!is_array($asset)) {
                    continue;
                }
                if (
                    isset($asset['name'], $asset['browser_download_url'])
                    && (string) $asset['name'] === $asset_name
                    && (string) $asset['browser_download_url'] !== ''
                ) {
                    $has_zip = true;
                    break;
                }
            }
        }
        if (!$has_zip) {
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
 * Devuelve el release publicado más reciente con ensorlogs.zip. Cacheado 6 horas.
 *
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

    $result = ensorlogs_github_api_get('/releases/latest');
    $release = null;

    if ($result['code'] === 200 && is_array($result['body'])) {
        $candidate = $result['body'];
        if (ensorlogs_github_package_url($candidate) !== '') {
            $release = $candidate;
        }
    }

    if ($release === null) {
        $list = ensorlogs_github_api_get('/releases', array('per_page' => 30));
        if ($list['code'] === 200 && is_array($list['body'])) {
            $release = ensorlogs_github_pick_best_release($list['body']);
            if ($release === null && ensorlogs_github_get_last_error() === '') {
                global $ensorlogs_github_last_error;
                $ensorlogs_github_last_error = sprintf(
                    /* translators: %s: nombre del asset zip */
                    __('Hay releases pero ninguno incluye el asset `%s`. Sube el zip en el release de GitHub.', 'ensorlogs'),
                    (string) apply_filters('ensorlogs_github_asset_name', ENSORLOGS_GITHUB_ASSET)
                );
            }
        }
    }

    if ($release === null) {
        delete_transient($key);
        return null;
    }

    set_transient($key, $release, 6 * HOUR_IN_SECONDS);
    return $release;
}

/**
 * Codifica cada segmento por separado y conserva las barras (`owner/repo`).
 */
function ensorlogs_rawurlencode_path(string $path): string
{
    $parts = explode('/', $path);
    return implode('/', array_map('rawurlencode', $parts));
}

/**
 * Limpia un tag de GitHub (`v1.2.3` → `1.2.3`).
 */
function ensorlogs_normalize_version(string $tag): string
{
    $tag = trim($tag);
    if ($tag !== '' && (strncmp($tag, 'v', 1) === 0 || strncmp($tag, 'V', 1) === 0)) {
        $tag = substr($tag, 1);
    }
    return $tag;
}

/**
 * URL de descarga del asset ensorlogs.zip (requerido para actualizar en WordPress).
 */
function ensorlogs_github_package_url(array $release): string
{
    $asset_name = (string) apply_filters('ensorlogs_github_asset_name', ENSORLOGS_GITHUB_ASSET);
    if (!empty($release['assets']) && is_array($release['assets'])) {
        foreach ($release['assets'] as $asset) {
            if (!is_array($asset)) {
                continue;
            }
            $name = isset($asset['name']) ? (string) $asset['name'] : '';
            if ($name === $asset_name && !empty($asset['browser_download_url'])) {
                return (string) $asset['browser_download_url'];
            }
        }
    }
    return '';
}

/**
 * Inyecta la actualización en el sondeo de WordPress.
 */
add_filter(
    'pre_set_site_transient_update_themes',
    static function ($transient) {
        if (!is_object($transient)) {
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
        $package = ensorlogs_github_package_url($release);
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

/**
 * Renombra la carpeta del zipball si algún release antiguo no traía ensorlogs/ en la raíz.
 */
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

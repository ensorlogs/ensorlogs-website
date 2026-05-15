<?php
/**
 * Auto-actualización del tema desde GitHub Releases.
 *
 * Cuando publicas un nuevo release en GitHub con un asset llamado
 * `ensorlogs.zip`, WordPress detecta la nueva versión y propone la
 * actualización igual que con cualquier tema del directorio oficial.
 *
 * Configuración: ajusta la constante `ENSORLOGS_GITHUB_REPO`
 * (`owner/repo`) en wp-config.php si tu repo no es el por defecto.
 * Para repos privados define `ENSORLOGS_GITHUB_TOKEN` con un PAT
 * de GitHub con scope `repo`.
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

/**
 * Construye los headers para hablar con la API de GitHub.
 *
 * @return array<string, string>
 */
function ensorlogs_github_headers(): array
{
    $headers = array(
        'Accept'     => 'application/vnd.github+json',
        'User-Agent' => 'Ensorlogs-Theme-Updater',
    );
    if (defined('ENSORLOGS_GITHUB_TOKEN') && ENSORLOGS_GITHUB_TOKEN !== '') {
        $headers['Authorization'] = 'Bearer ' . ENSORLOGS_GITHUB_TOKEN;
    }
    return $headers;
}

/**
 * Devuelve el release "latest" de GitHub. Cacheado 6 horas en transient.
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
    $repo = (string) apply_filters('ensorlogs_github_repo', ENSORLOGS_GITHUB_REPO);
    if ($repo === '') {
        return null;
    }
    $url = sprintf('https://api.github.com/repos/%s/releases/latest', ensorlogs_rawurlencode_path($repo));
    $response = wp_remote_get(
        $url,
        array(
            'timeout'    => 15,
            'headers'    => ensorlogs_github_headers(),
            'user-agent' => 'Ensorlogs-Theme-Updater',
        )
    );
    if (is_wp_error($response)) {
        return null;
    }
    $code = (int) wp_remote_retrieve_response_code($response);
    if ($code !== 200) {
        return null;
    }
    $body = json_decode((string) wp_remote_retrieve_body($response), true);
    if (!is_array($body)) {
        return null;
    }
    set_transient($key, $body, 6 * HOUR_IN_SECONDS);
    return $body;
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
 * Devuelve la URL del asset `ensorlogs.zip` del release. Si el release no
 * tiene assets, usa `zipball_url` (que descarga el árbol completo en una
 * carpeta sin el slug del tema, así que sólo se usa como último recurso).
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
    if (!empty($release['zipball_url'])) {
        return (string) $release['zipball_url'];
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
 * Si el package descargado es el `zipball_url` de GitHub, viene con una
 * carpeta tipo `owner-repo-abc1234/`. WordPress espera que el tema esté en
 * `ensorlogs/`. Renombramos la carpeta antes de la instalación.
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

/**
 * Botón en Apariencia → Temas → detalle del tema → "Buscar actualizaciones".
 * También expone un enlace en la página del seed.
 */
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

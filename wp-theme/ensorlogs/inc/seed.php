<?php
/**
 * Importación idempotente de páginas, artículos y proyectos.
 *
 * Política IMPORTANTE: el seed NUNCA sobrescribe contenido existente en la
 * base de datos. Si un slug ya existe (en cualquier estado, incluido papelera),
 * se omite. Solo se crean elementos que no estén en la base de datos.
 *
 * Esto permite actualizar el tema (subir una nueva versión a WordPress) sin
 * machacar los artículos / proyectos que el usuario haya editado o creado
 * desde el escritorio.
 *
 * @package Ensorlogs
 */

if (!defined('ABSPATH')) {
    exit;
}

const ENSORLOGS_SEED_VERSION = 9;
const ENSORLOGS_SEED_META_KEY = '_ensor_seeded_at';
const ENSORLOGS_PAGE_CONTENT_SEED_META = '_ensor_page_content_seeded';
const ENSORLOGS_PAGE_EDITABLE_SYNC_OPTION = 'ensorlogs_page_editable_sync_version';

/**
 * Crea las páginas estructurales del sitio si no existen. Si existen
 * (cualquier estado) se respetan tal como están: solo se restauran de la
 * papelera y se publican si fueron descartadas.
 *
 * @return array<string, int> slug => post ID
 */
function ensorlogs_seed_ensure_pages(): array
{
    $defs = array(
        'inicio'      => array('title' => 'Inicio', 'content' => ''),
        'about'       => array('title' => 'Sobre mi', 'content' => ''),
        'blog'        => array('title' => 'Hablemos de…', 'content' => ''),
        'projects'    => array('title' => 'Proyectos', 'content' => ''),
        'services'    => array('title' => 'Servicios', 'content' => ''),
        'contact'     => array('title' => 'Contacto', 'content' => ''),
        'credentials' => array('title' => 'Credenciales', 'content' => ''),
    );
    $ids = array();
    foreach ($defs as $slug => $info) {
        $found = get_posts(
            array(
                'post_type'      => 'page',
                'name'           => $slug,
                'post_status'    => 'any',
                'posts_per_page' => 1,
            )
        );
        if (!empty($found)) {
            $page = $found[0];
            $pid  = (int) $page->ID;
            if ($page->post_status === 'trash') {
                wp_untrash_post($pid);
                wp_update_post(
                    array(
                        'ID'          => $pid,
                        'post_status' => 'publish',
                    )
                );
            }
            $ids[ $slug ] = $pid;
            continue;
        }
        $pid = wp_insert_post(
            array(
                'post_type'    => 'page',
                'post_status'  => 'publish',
                'post_title'   => $info['title'],
                'post_name'    => $slug,
                'post_content' => $info['content'],
            ),
            true
        );
        if (is_wp_error($pid) || (int) $pid <= 0) {
            continue;
        }
        $ids[ $slug ] = (int) $pid;
    }
    if (!empty($ids['inicio'])) {
        if (get_option('show_on_front') !== 'page') {
            update_option('show_on_front', 'page');
        }
        if ((int) get_option('page_on_front') !== (int) $ids['inicio']) {
            update_option('page_on_front', $ids['inicio']);
        }
    }
    return $ids;
}

/**
 * ¿Ya existe un post con este slug (en cualquier estado)?
 */
function ensorlogs_seed_post_exists(string $post_type, string $slug): bool
{
    $found = get_posts(
        array(
            'post_type'      => $post_type,
            'name'           => $slug,
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        )
    );
    return !empty($found);
}

/**
 * Crea un artículo desde el HTML estático, solo si no existe ya.
 */
function ensorlogs_seed_insert_article(array $row, int $menu_order): void
{
    if (ensorlogs_seed_post_exists('ensor_article', (string) $row['post_name'])) {
        return;
    }
    $path = get_template_directory() . '/seed-html/articulos/' . $row['file'];
    if (!is_readable($path)) {
        return;
    }
    $raw   = (string) file_get_contents($path);
    $body  = ensorlogs_blockify_html_for_editor(
        ensorlogs_relink_migrated_body(ensorlogs_extract_main_content_html($raw)),
        'article'
    );
    $title = ensorlogs_parse_title_from_static_html($raw);
    if ($title === '') {
        $title = (string) $row['post_name'];
    }
    $args = array(
        'post_type'    => 'ensor_article',
        'post_title'   => $title,
        'post_name'    => (string) $row['post_name'],
        'post_content' => $body,
        'post_status'  => 'publish',
        'post_date'    => (string) $row['datetime'],
        'post_excerpt' => (string) $row['card_excerpt'],
        'menu_order'   => $menu_order,
    );
    $id = (int) wp_insert_post(wp_slash($args), true);
    if ($id <= 0) {
        return;
    }
    update_post_meta($id, '_ensor_temas', (string) $row['temas']);
    update_post_meta($id, '_ensor_primary_tema', (string) $row['primary_tema']);
    update_post_meta($id, '_ensor_card_image', (string) $row['card_image']);
    update_post_meta($id, '_ensor_card_excerpt', (string) $row['card_excerpt']);
    update_post_meta($id, ENSORLOGS_SEED_META_KEY, gmdate('c'));
}

/**
 * Crea un proyecto desde el HTML estático, solo si no existe ya.
 */
function ensorlogs_seed_insert_project(array $row, int $menu_order): void
{
    if (ensorlogs_seed_post_exists('ensor_project', (string) $row['post_name'])) {
        return;
    }
    $path = get_template_directory() . '/seed-html/proyectos/' . $row['file'];
    if (!is_readable($path)) {
        return;
    }
    $raw   = (string) file_get_contents($path);
    $body  = ensorlogs_blockify_html_for_editor(
        ensorlogs_relink_migrated_body(ensorlogs_extract_main_content_html($raw)),
        'project'
    );
    $title = ensorlogs_parse_title_from_static_html($raw);
    if ($title === '') {
        $title = (string) $row['title'];
    }
    $args = array(
        'post_type'    => 'ensor_project',
        'post_title'   => $title,
        'post_name'    => (string) $row['post_name'],
        'post_content' => $body,
        'post_status'  => 'publish',
        'menu_order'   => $menu_order,
    );
    $id = (int) wp_insert_post(wp_slash($args), true);
    if ($id <= 0) {
        return;
    }
    update_post_meta($id, '_ensor_temas', (string) $row['temas']);
    update_post_meta($id, '_ensor_item_class', (string) $row['item_class']);
    update_post_meta($id, '_ensor_img_rel', (string) $row['img_rel']);
    update_post_meta($id, '_ensor_subtitle', (string) $row['subtitle']);
    update_post_meta($id, '_ensor_list_title', (string) $row['title']);
    update_post_meta($id, '_ensor_tag_slugs', wp_json_encode((array) $row['tags']));
    update_post_meta($id, ENSORLOGS_SEED_META_KEY, gmdate('c'));
}

/**
 * Crea las páginas legales (aviso, privacidad, cookies, accesibilidad)
 * como hijas de la página "legal" si no existen ya. Idempotente.
 */
function ensorlogs_seed_ensure_legal_pages(): void
{
    $parent = get_page_by_path('legal');
    if (!$parent) {
        $pid = wp_insert_post(
            array(
                'post_type'    => 'page',
                'post_status'  => 'publish',
                'post_title'   => __('Legal', 'ensorlogs'),
                'post_name'    => 'legal',
                'post_content' => '<p>' . esc_html__('Documentos legales y de transparencia de Ensorlogs.', 'ensorlogs') . '</p>',
            ),
            true
        );
        $parent_id = is_wp_error($pid) ? 0 : (int) $pid;
    } else {
        $parent_id = (int) $parent->ID;
    }

    $defs = array(
        'aviso-legal'   => __('Aviso legal y condiciones de uso', 'ensorlogs'),
        'privacidad'    => __('Política de privacidad', 'ensorlogs'),
        'cookies'       => __('Política de cookies', 'ensorlogs'),
        'accesibilidad' => __('Declaración de accesibilidad', 'ensorlogs'),
    );

    // Excerpt (lead) por defecto para cada página legal.
    $leads = array(
        'privacidad'    => __('Esta política describe qué datos personales tratamos cuando interactúas con Ensorlogs, con qué finalidad, durante cuánto tiempo y qué derechos puedes ejercer en cualquier momento.', 'ensorlogs'),
        'cookies'       => __('Aquí explicamos qué cookies (y tecnologías equivalentes) usamos en Ensorlogs, para qué sirven y cómo puedes controlarlas en cualquier momento desde la propia web o tu navegador.', 'ensorlogs'),
        'aviso-legal'   => __('Información obligatoria del titular del sitio, las condiciones bajo las que se ofrece el servicio y el marco legal aplicable a cualquier persona que navegue por Ensorlogs.', 'ensorlogs'),
        'accesibilidad' => __('Nuestro compromiso para que cualquier persona pueda leer, escuchar y usar Ensorlogs: medidas técnicas aplicadas, contenidos pendientes y cómo avisarnos si encuentras una barrera.', 'ensorlogs'),
    );

    foreach ($defs as $slug => $title) {
        $existing = get_page_by_path('legal/' . $slug);
        if ($existing) {
            // Asegura que la página existente use el template branded.
            $tpl = get_post_meta($existing->ID, '_wp_page_template', true);
            if ($tpl !== 'page-legal.php') {
                update_post_meta($existing->ID, '_wp_page_template', 'page-legal.php');
            }
            continue;
        }
        $file = get_template_directory() . '/seed-html/legal/' . $slug . '.html';
        $body = '';
        if (is_readable($file)) {
            $raw  = (string) file_get_contents($file);
            $body = ensorlogs_extract_main_content_html($raw);
        }
        if ($body === '') {
            $body = '<p>' . esc_html($title) . '</p>';
        }
        $new_id = wp_insert_post(
            array(
                'post_type'    => 'page',
                'post_status'  => 'publish',
                'post_title'   => $title,
                'post_name'    => $slug,
                'post_parent'  => $parent_id,
                'post_content' => $body,
                'post_excerpt' => isset($leads[$slug]) ? $leads[$slug] : '',
            ),
            true
        );
        if (!is_wp_error($new_id) && $new_id) {
            update_post_meta((int) $new_id, '_wp_page_template', 'page-legal.php');
        }
    }
}

/**
 * Pre-rellena el `post_content` de las páginas estructurales (inicio, about,
 * services, projects, blog, contact) con el HTML que vive dentro de
 * `<!-- ensor:editable -->` del fragment correspondiente, **solo si**:
 *   1. La página existe.
 *   2. Su `post_content` está vacío.
 *   3. Aún no fue pre-rellenada (meta `_ensor_page_content_seeded` ausente).
 *
 * Esto le da al usuario un punto de partida editable cuando abre la página
 * en WordPress; si decide vaciar el editor y guardar, la web vuelve al
 * default del fragment (no se rompe nada).
 */
function ensorlogs_seed_prefill_page_contents(): void
{
    if (!function_exists('ensorlogs_page_fragments_map')
        || !function_exists('ensorlogs_extract_fragment_editable_default')) {
        return;
    }
    foreach (ensorlogs_page_fragments_map() as $slug => $fragment) {
        $found = get_posts(array(
            'post_type'      => 'page',
            'name'           => $slug,
            'post_status'    => 'any',
            'posts_per_page' => 1,
        ));
        if (empty($found) || !$found[0] instanceof WP_Post) {
            continue;
        }
        $page = $found[0];
        $pid  = (int) $page->ID;
        // Política idempotente: si ya fue pre-rellenada o si el editor ya
        // tiene contenido, NO tocamos nada.
        if (get_post_meta($pid, ENSORLOGS_PAGE_CONTENT_SEED_META, true)) {
            continue;
        }
        if (trim((string) $page->post_content) !== '') {
            // El usuario ya escribió algo: respetamos pero marcamos como seeded
            // para no volver a evaluarlo en futuras versiones.
            update_post_meta($pid, ENSORLOGS_PAGE_CONTENT_SEED_META, ENSORLOGS_SEED_VERSION);
            continue;
        }
        $default_html = ensorlogs_extract_fragment_editable_default($fragment);
        if ($default_html === '') {
            continue;
        }
        wp_update_post(array(
            'ID'           => $pid,
            'post_content' => wp_slash($default_html),
        ));
        update_post_meta($pid, ENSORLOGS_PAGE_CONTENT_SEED_META, ENSORLOGS_SEED_VERSION);
    }
}

/**
 * Recorre el manifest y crea (nunca sobrescribe) los posts que falten.
 */
function ensorlogs_run_theme_seed(): void
{
    require_once get_template_directory() . '/inc/seed-manifest.php';
    ensorlogs_seed_ensure_pages();
    ensorlogs_seed_prefill_page_contents();
    ensorlogs_seed_ensure_legal_pages();
    $i = 0;
    foreach (ensorlogs_seed_article_manifest() as $row) {
        ensorlogs_seed_insert_article($row, $i);
        ++$i;
    }
    $i = 0;
    foreach (ensorlogs_seed_project_manifest() as $row) {
        ensorlogs_seed_insert_project($row, $i);
        ++$i;
    }
    update_option('ensorlogs_seed_version', ENSORLOGS_SEED_VERSION);
}

/**
 * Marcadores del copy antiguo de home/about (ES). Si el editor WP aún los
 * contiene, se puede refrescar desde el fragment del tema sin machacar textos
 * personalizados que no coincidan.
 *
 * @return array<int, string>
 */
function ensorlogs_page_editable_stale_markers(): array
{
    return array(
        'Esta es mi bitácora pública',
        'Soy ingeniero en sistemas',
        'curioso compulsivo',
        'Disponible para proyectos',
        'También para cursos y talleres',
        'Tres ideas la mueven',
        'con gente y comunidades que saben',
        'registro de tres ideas',
        'con más geeks',
        'registro de ese viaje',
        'documento el proceso',
        'Tres ideas:',
    );
}

function ensorlogs_content_has_stale_editable_marker(string $html): bool
{
    $plain = wp_strip_all_tags($html);
    if ($plain === '') {
        return false;
    }
    foreach (ensorlogs_page_editable_stale_markers() as $marker) {
        if (stripos($plain, $marker) !== false) {
            return true;
        }
    }
    return false;
}

/**
 * Sincroniza inicio/about cuando el editor WP conserva el copy antiguo.
 */
function ensorlogs_sync_stale_page_editable_content(): void
{
    if (!function_exists('ensorlogs_page_fragments_map')
        || !function_exists('ensorlogs_extract_fragment_editable_default')) {
        return;
    }

    $targets = array('inicio', 'about');
    $map     = ensorlogs_page_fragments_map();

    foreach ($targets as $slug) {
        if (!isset($map[$slug])) {
            continue;
        }
        $found = get_posts(array(
            'post_type'      => 'page',
            'name'           => $slug,
            'post_status'    => 'any',
            'posts_per_page' => 1,
        ));
        if (empty($found) || !$found[0] instanceof WP_Post) {
            continue;
        }
        $page    = $found[0];
        $current = (string) $page->post_content;
        if (trim(wp_strip_all_tags($current)) === '') {
            continue;
        }
        if (!ensorlogs_content_has_stale_editable_marker($current)) {
            continue;
        }
        $default = ensorlogs_extract_fragment_editable_default($map[$slug]);
        if ($default === '') {
            continue;
        }
        wp_update_post(array(
            'ID'           => (int) $page->ID,
            'post_content' => wp_slash($default),
        ));
    }
}

function ensorlogs_maybe_sync_page_editable_content(): void
{
    if (!defined('ENSORLOGS_THEME_VERSION')) {
        return;
    }
    $stored = (string) get_option(ENSORLOGS_PAGE_EDITABLE_SYNC_OPTION, '');
    if ($stored === ENSORLOGS_THEME_VERSION) {
        return;
    }
    ensorlogs_sync_stale_page_editable_content();
    update_option(ENSORLOGS_PAGE_EDITABLE_SYNC_OPTION, ENSORLOGS_THEME_VERSION, false);
}

/**
 * Permite forzar la importación de nuevos elementos al activar / actualizar
 * el tema sin tocar nada de lo que ya esté en la base de datos.
 */
add_action(
    'init',
    static function (): void {
        if (get_template() !== 'ensorlogs') {
            return;
        }
        $stored = (int) get_option('ensorlogs_seed_version', 0);
        if ($stored >= ENSORLOGS_SEED_VERSION) {
            return;
        }
        ensorlogs_run_theme_seed();
        flush_rewrite_rules(false);
    },
    100
);

add_action(
    'init',
    static function (): void {
        if (get_template() !== 'ensorlogs') {
            return;
        }
        ensorlogs_maybe_sync_page_editable_content();
    },
    5
);

/**
 * Acción manual del administrador: re-ejecutar el seed (siempre sin
 * sobrescribir contenido). Útil tras restaurar la base de datos o cuando
 * se añaden nuevos artículos al manifest sin subir la versión.
 */
add_action(
    'admin_post_ensorlogs_run_seed',
    static function (): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('No tienes permisos para ejecutar el seed.', 'ensorlogs'), '', array('response' => 403));
        }
        check_admin_referer('ensorlogs_run_seed');
        ensorlogs_run_theme_seed();
        flush_rewrite_rules(false);
        wp_safe_redirect(
            add_query_arg(
                array('page' => 'ensorlogs-seed', 'seed' => 'done'),
                admin_url('themes.php')
            )
        );
        exit;
    }
);

add_action(
    'admin_menu',
    static function (): void {
        add_theme_page(
            __('Importar contenido Ensorlogs', 'ensorlogs'),
            __('Ensorlogs · Seed', 'ensorlogs'),
            'manage_options',
            'ensorlogs-seed',
            'ensorlogs_render_seed_admin_page'
        );
    }
);

function ensorlogs_render_seed_admin_page(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }
    $done    = isset($_GET['seed']) && $_GET['seed'] === 'done';
    $checked = isset($_GET['checked']) && $_GET['checked'] === '1';
    $release = function_exists('ensorlogs_github_get_latest_release')
        ? ensorlogs_github_get_latest_release(isset($_GET['checked']))
        : null;
    $gh_error = function_exists('ensorlogs_github_get_last_error')
        ? ensorlogs_github_get_last_error()
        : '';
    $tag      = is_array($release) && isset($release['tag_name']) ? (string) $release['tag_name'] : '';
    $latest   = function_exists('ensorlogs_normalize_version') ? ensorlogs_normalize_version($tag) : $tag;
    $current  = defined('ENSORLOGS_THEME_VERSION') ? ENSORLOGS_THEME_VERSION : '0';
    $has_new   = $latest !== '' && version_compare($latest, $current, '>');
    $has_token = function_exists('ensorlogs_github_token_configured') && ensorlogs_github_token_configured();
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Ensorlogs · Mantenimiento', 'ensorlogs'); ?></h1>
        <?php if ($done) : ?>
            <div class="notice notice-success is-dismissible">
                <p><?php esc_html_e('Seed completado. Solo se crearon los elementos que faltaban; el contenido existente se respetó.', 'ensorlogs'); ?></p>
            </div>
        <?php endif; ?>
        <?php if ($checked) : ?>
            <div class="notice notice-info is-dismissible">
                <p><?php esc_html_e('Comprobación de actualizaciones completada.', 'ensorlogs'); ?></p>
            </div>
        <?php endif; ?>

        <h2 class="title"><?php esc_html_e('Importar contenido inicial', 'ensorlogs'); ?></h2>
        <p>
            <?php esc_html_e('Crea los artículos y proyectos definidos en el manifest del tema. Nunca sobrescribe contenido existente: si un slug ya está en la base de datos, se omite.', 'ensorlogs'); ?>
        </p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="ensorlogs_run_seed">
            <?php wp_nonce_field('ensorlogs_run_seed'); ?>
            <?php submit_button(__('Ejecutar seed (no destructivo)', 'ensorlogs'), 'primary'); ?>
        </form>

        <hr>

        <h2 class="title"><?php esc_html_e('Actualizaciones del tema (GitHub)', 'ensorlogs'); ?></h2>
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row"><?php esc_html_e('Versión instalada', 'ensorlogs'); ?></th>
                    <td><code><?php echo esc_html((string) $current); ?></code></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Última versión en GitHub', 'ensorlogs'); ?></th>
                    <td>
                        <?php if ($latest !== '') : ?>
                            <code><?php echo esc_html($latest); ?></code>
                            <?php if ($has_new) : ?>
                                <span style="color:#d63638;font-weight:600;">
                                    <?php esc_html_e('Actualización disponible', 'ensorlogs'); ?>
                                </span>
                            <?php else : ?>
                                <span style="color:#1a7f37;font-weight:600;">
                                    <?php esc_html_e('Estás al día', 'ensorlogs'); ?>
                                </span>
                            <?php endif; ?>
                        <?php else : ?>
                            <em><?php esc_html_e('No se pudo consultar GitHub.', 'ensorlogs'); ?></em>
                            <?php if ($gh_error !== '') : ?>
                                <p class="description" style="margin-top:0.5em;">
                                    <?php echo esc_html($gh_error); ?>
                                </p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Repositorio configurado', 'ensorlogs'); ?></th>
                    <td>
                        <code><?php echo esc_html((string) (defined('ENSORLOGS_GITHUB_REPO') ? ENSORLOGS_GITHUB_REPO : '')); ?></code>
                        <p class="description">
                            <?php
                            esc_html_e(
                                'Cada tag publica ensorlogs.zip en el release. WordPress lo instala desde Actualizaciones.',
                                'ensorlogs'
                            );
                            ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Token GitHub (wp-config)', 'ensorlogs'); ?></th>
                    <td>
                        <?php if ($has_token) : ?>
                            <span style="color:#1a7f37;font-weight:600;">
                                <?php esc_html_e('Configurado (repo privado)', 'ensorlogs'); ?>
                            </span>
                        <?php else : ?>
                            <span style="color:#1a7f37;font-weight:600;">
                                <?php esc_html_e('No necesario si el repo es público y el release trae ensorlogs.zip', 'ensorlogs'); ?>
                            </span>
                        <?php endif; ?>
                    </td>
                </tr>
            </tbody>
        </table>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="ensorlogs_check_update">
            <?php wp_nonce_field('ensorlogs_check_update'); ?>
            <?php submit_button(__('Buscar actualizaciones ahora', 'ensorlogs'), 'secondary', 'submit', false); ?>
        </form>
        <?php if ($has_new) : ?>
            <p style="margin-top:1em;">
                <?php
                printf(
                    /* translators: %s: enlace al panel de actualizaciones */
                    esc_html__('Ve a %s para instalar la nueva versión (solo desde tu WordPress, no hay descarga pública del zip).', 'ensorlogs'),
                    '<a href="' . esc_url(admin_url('update-core.php')) . '">' . esc_html__('Escritorio → Actualizaciones', 'ensorlogs') . '</a>'
                );
                ?>
            </p>
        <?php endif; ?>
    </div>
    <?php
}

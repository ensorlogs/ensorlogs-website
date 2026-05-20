<?php
/**
 * Listado de proyectos (tarjetas desde CPT ensor_project).
 *
 * Imagen de la tarjeta: imagen destacada del proyecto si existe; si no,
 * meta `_ensor_img_rel` (assets del tema); por último, logo del tema.
 *
 * Mismo patrón de caché que el blog (6 horas, invalidación en `save_post`
 * y al cambiar `_thumbnail_id`).
 *
 * @package Ensorlogs
 */

if (!defined('ABSPATH')) {
    exit;
}

// Si la página WP de slug "projects" tiene contenido en su editor, lo usamos
// para sustituir la zona <!-- ensor:editable --> del fragment.
$ensor_proj_editable = '';
$ensor_proj_q = get_posts(array(
    'post_type'      => 'page',
    'name'           => 'projects',
    'posts_per_page' => 1,
    'post_status'    => 'publish',
));
if (!empty($ensor_proj_q) && $ensor_proj_q[0] instanceof WP_Post) {
    $ensor_proj_editable = apply_filters('the_content', $ensor_proj_q[0]->post_content);
    $ensor_proj_editable = ensorlogs_normalize_intro_heading($ensor_proj_editable, 'proyectos-intro-heading');
}

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo ensorlogs_render_fragment_editable('projects-list-top.fragment.html', $ensor_proj_editable);

$ensor_cache_key = 'ensorlogs_projects_list_v1';
$ensor_cached    = (defined('WP_DEBUG') && WP_DEBUG) ? false : get_transient($ensor_cache_key);
if (is_string($ensor_cached) && $ensor_cached !== '') {
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $ensor_cached;
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo ensorlogs_render_fragment('projects-list-bottom.fragment.html');
    return;
}

ob_start();

$proj_q = new WP_Query(
    array(
        'post_type'      => 'ensor_project',
        'posts_per_page' => -1,
        'orderby'        => array(
            'menu_order' => 'ASC',
            'date'       => 'DESC',
        ),
        'post_status'    => 'publish',
        'no_found_rows'  => true,
    )
);

while ($proj_q->have_posts()) {
    $proj_q->the_post();
    $pid       = get_the_ID();
    $link      = get_permalink($pid);
    $tax_terms = get_the_terms($pid, 'ensor_tema');
    $temas     = '';
    if (is_array($tax_terms) && !empty($tax_terms)) {
        $slugs = array();
        foreach ($tax_terms as $t) {
            if ($t instanceof WP_Term) {
                $slugs[] = (string) $t->slug;
            }
        }
        $temas = implode(' ', $slugs);
    }
    if ($temas === '') {
        $temas = (string) get_post_meta($pid, '_ensor_temas', true);
    }
    $iclass = (string) get_post_meta($pid, '_ensor_item_class', true);
    if ($iclass === '') {
        $iclass = 'project-item group';
    }
    $img_rel = (string) get_post_meta($pid, '_ensor_img_rel', true);
    $sub     = (string) get_post_meta($pid, '_ensor_subtitle', true);
    $list_t  = (string) get_post_meta($pid, '_ensor_list_title', true);
    if ($list_t === '') {
        $list_t = get_the_title();
    }
    // Imagen destacada del CPT primero (editor WP); `_ensor_img_rel` solo si no hay thumbnail.
    $img_src = '';
    if (has_post_thumbnail($pid)) {
        $thumb = get_the_post_thumbnail_url($pid, 'large');
        $img_src = is_string($thumb) && $thumb !== '' ? esc_url($thumb) : '';
    }
    if ($img_src === '') {
        $img_src = ensorlogs_resolve_public_asset_url($img_rel);
    }
    if ($img_src === '') {
        $img_src = esc_url(get_template_directory_uri() . '/assets/img/Logos/ensorlogs2.png');
    }
    $tags_raw = (string) get_post_meta($pid, '_ensor_tag_slugs', true);
    $tags     = json_decode($tags_raw, true);
    if (!is_array($tags)) {
        $tags = array();
    }
    ?>
    <div class="<?php echo esc_attr($iclass); ?>" data-temas="<?php echo esc_attr($temas); ?>">
        <div class="thumbnail rounded-lg overflow-hidden">
            <a href="<?php echo esc_url($link); ?>">
                <img
                    src="<?php echo esc_url($img_src); ?>"
                    alt="<?php echo esc_attr($list_t); ?>"
                    class="h-56 sm:h-64 lg:h-72 xl:h-96 w-full object-cover object-center transition-all duration-300 group-hover:scale-110"
                >
            </a>
        </div>
        <div class="description px-4 py-6 pb-4 space-y-2">
            <p class="text-xs uppercase tracking-wide text-nobelGray dark:text-slateGray font-medium">
                <?php echo esc_html($sub); ?>
            </p>
            <div class="flex flex-wrap gap-1.5" aria-label="<?php esc_attr_e('Stacks del proyecto', 'ensorlogs'); ?>">
                <?php
                foreach ($tags as $tag_slug) {
                    $tag_slug = (string) $tag_slug;
                    $tu       = esc_url(trailingslashit(home_url('/')) . 'projects/?tema=' . rawurlencode($tag_slug));
                    ?>
                    <a href="<?php echo esc_url($tu); ?>" class="text-xs font-medium px-2 py-0.5 rounded-full border border-flasWhite dark:border-flasBlack bg-white/65 dark:bg-black/25 text-darkGray dark:text-pastelGrey hover:border-[var(--ensor-accent)] transition-colors"><?php echo esc_html(ensorlogs_tema_label($tag_slug)); ?></a>
                    <?php
                }
                ?>
            </div>
            <h4 class="text-lg xl:text-xl 2xl:text-2xl font-semibold text-darkGray dark:text-pastelGrey">
                <a href="<?php echo esc_url($link); ?>" class="flex items-start gap-4 justify-between">
                    <?php echo esc_html($list_t); ?>
                    <span class="ml-auto max-md:hidden shrink-0 group-hover:text-darkGray dark:group-hover:text-pastelGrey">
                        <svg class="w-11 text-nobelGray group-hover:text-darkGray dark:text-slateGray dark:group-hover:text-white transition-all duration-300" viewBox="0 0 46 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M26 16.1284C26.4158 16.9337 26.9612 17.6894 27.6363 18.3644C31.151 21.8792 36.8495 21.8792 40.3642 18.3644C43.8789 14.8497 43.8789 9.15127 40.3642 5.63653C36.8495 2.12181 31.151 2.12181 27.6363 5.63653C26.9612 6.31161 26.4158 7.06729 26 7.87259" stroke="currentcolor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                            <path d="M1 12H39L35 16M35 8L36.5 9.5" stroke="currentcolor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                    </span>
                </a>
            </h4>
        </div>
    </div>
    <?php
}
wp_reset_postdata();

$ensor_rendered = ob_get_clean();
set_transient($ensor_cache_key, $ensor_rendered, 6 * HOUR_IN_SECONDS);
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo $ensor_rendered;

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo ensorlogs_render_fragment('projects-list-bottom.fragment.html');

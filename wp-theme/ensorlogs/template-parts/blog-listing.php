<?php
/**
 * Listado del blog (tarjetas desde CPT ensor_article).
 *
 * Cachea el HTML resultante 6 horas por idioma/dark-mode neutro. La caché se
 * invalida al guardar / mover a papelera / restaurar cualquier `ensor_article`
 * (ver `inc/helpers.php`).
 *
 * @package Ensorlogs
 */

if (!defined('ABSPATH')) {
    exit;
}

// Si la página WP de slug "blog" tiene contenido en su editor, lo usamos
// para sustituir la zona <!-- ensor:editable --> del fragment.
$ensor_blog_editable = '';
$ensor_blog_q = get_posts(array(
    'post_type'      => 'page',
    'name'           => 'blog',
    'posts_per_page' => 1,
    'post_status'    => 'publish',
));
if (!empty($ensor_blog_q) && $ensor_blog_q[0] instanceof WP_Post) {
    $ensor_blog_editable = apply_filters('the_content', $ensor_blog_q[0]->post_content);
    $ensor_blog_editable = ensorlogs_normalize_intro_heading($ensor_blog_editable, 'blog-temas-heading');
}

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo ensorlogs_render_fragment_editable('blog-list-top.fragment.html', $ensor_blog_editable);

$ensor_ui_lang   = function_exists('ensorlogs_current_lang') ? ensorlogs_current_lang() : 'es';
$ensor_cache_key = 'ensorlogs_blog_list_v2_' . $ensor_ui_lang;
$ensor_cached    = (defined('WP_DEBUG') && WP_DEBUG) ? false : get_transient($ensor_cache_key);
if (is_string($ensor_cached) && $ensor_cached !== '') {
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $ensor_cached;
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo ensorlogs_render_fragment('blog-list-bottom.fragment.html');
    return;
}

ob_start();

$blog_query_args = array(
    'post_type'      => 'ensor_article',
    'posts_per_page' => -1,
    'orderby'        => array(
        'menu_order' => 'ASC',
        'date'       => 'DESC',
    ),
    'post_status'    => 'publish',
    'no_found_rows'  => true,
);
if (function_exists('ensorlogs_article_lang_meta_query')) {
    $blog_query_args['meta_query'] = ensorlogs_article_lang_meta_query();
}
$blog_q = new WP_Query($blog_query_args);

$t_logo = esc_url(get_template_directory_uri() . '/assets/img/Logos/ensorlogs2.png');

while ($blog_q->have_posts()) {
    $blog_q->the_post();
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
    $primary  = (string) get_post_meta($pid, '_ensor_primary_tema', true);
    $card_img = (string) get_post_meta($pid, '_ensor_card_image', true);
    $img_src   = ensorlogs_resolve_public_asset_url($card_img);
    if ($img_src === '' && has_post_thumbnail($pid)) {
        $thumb = get_the_post_thumbnail_url($pid, 'large');
        $img_src = is_string($thumb) && $thumb !== '' ? esc_url($thumb) : '';
    }
    if ($img_src === '') {
        $img_src = esc_url(get_template_directory_uri() . '/assets/img/Logos/ensorlogs2.png');
    }
    $card_txt = (string) get_post_meta($pid, '_ensor_card_excerpt', true);
    if ($card_txt === '') {
        $card_txt = get_the_excerpt();
    }
    $tema_url = esc_url(trailingslashit(home_url('/')) . 'blog/?tema=' . rawurlencode($primary));
    $time_attr = get_the_date('c');
    $time_show = strtolower(mysql2date('M Y', get_post()->post_date, true));
    ?>
    <div class="blog-item group flex flex-col h-full min-w-0" data-temas="<?php echo esc_attr($temas); ?>">
        <div class="thumbnail relative h-44 sm:h-48 overflow-hidden rounded-xl shrink-0">
            <a href="<?php echo esc_url($link); ?>" class="block h-full">
                <img src="<?php echo esc_url($img_src); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" width="900" height="506" decoding="async" loading="lazy" class="h-full w-full object-cover transition-all duration-300 group-hover:scale-105">
            </a>
            <div class="tags absolute right-3 top-3 flex flex-wrap gap-1.5 justify-end max-w-[70%]">
                <?php if (function_exists('ensorlogs_lang_badge_html')) : ?>
                    <?php
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo ensorlogs_lang_badge_html($pid);
                    ?>
                <?php endif; ?>
                <a href="<?php echo esc_url($tema_url); ?>" class="bg-white/60 dark:bg-black/50 transition-all px-3 py-1 rounded-3xl text-darkGray dark:text-pastelGrey text-sm"><?php echo esc_html(ensorlogs_tema_label($primary)); ?></a>
            </div>
        </div>
        <div class="description flex flex-col flex-1 px-3 pt-4 pb-4 min-h-0">
            <h2 class="text-lg font-bold text-darkGray dark:text-pastelGrey leading-snug line-clamp-3">
                <a href="<?php echo esc_url($link); ?>" class="inline bg-gradient-to-r from-current from-0% to-current to-100% bg-no-repeat bg-[length:0_1px] bg-[0_95%] ease-in-out duration-200 transition-[background-size] hover:bg-[length:100%_1px]"><?php the_title(); ?></a>
            </h2>
            <div class="meta my-3 text-sm text-darkGray dark:text-pastelGrey">
                <span class="text-regular inline-flex items-center gap-2">
                    <img class="w-8 h-8 shrink-0 rounded-full object-contain object-center bg-gradient-to-b from-milkWhite to-seashell dark:from-metalBlack dark:to-oilBlack border border-flasWhite dark:border-flasBlack" src="<?php echo esc_url($t_logo); ?>" alt="EnsorLogs">
                    <span class="author-name"><span><?php echo esc_html(function_exists('ensorlogs_t') ? ensorlogs_t('Por', 'By') : __('Por', 'ensorlogs')); ?></span> EnsorLogs</span>
                </span>
                <time class="text-regular block mt-1.5 opacity-90" datetime="<?php echo esc_attr($time_attr); ?>"><?php echo esc_html($time_show); ?></time>
            </div>
            <p class="text-sm leading-snug text-darkGray dark:text-pastelGrey line-clamp-3 flex-1"><?php echo esc_html($card_txt); ?></p>
            <p class="mt-4 pt-1">
                <a href="<?php echo esc_url($link); ?>" class="ensor-cta-hablemos inline-flex w-full items-center justify-center shrink-0 font-semibold py-2 px-4 text-sm leading-snug rounded-full no-underline" aria-label="<?php echo esc_attr(function_exists('ensorlogs_t') ? ensorlogs_t('Leer log completo', 'Read full log') : __('Leer log completo', 'ensorlogs')); ?>"><span><?php echo esc_html(function_exists('ensorlogs_t') ? ensorlogs_t('Leer log', 'Read log') : __('Leer log', 'ensorlogs')); ?></span></a>
            </p>
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
echo ensorlogs_render_fragment('blog-list-bottom.fragment.html');

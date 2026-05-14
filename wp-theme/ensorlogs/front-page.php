<?php
/**
 * Portada (fragmento migrado desde index.html).
 *
 * El editor de la página "Inicio" (slug `inicio`) controla la zona marcada
 * con `<!-- ensor:editable -->` dentro del fragment. Si el editor está vacío
 * se usa el HTML por defecto del fragment.
 *
 * @package Ensorlogs
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$ensor_editable = '';
$ensor_query    = get_posts(array(
    'post_type'      => 'page',
    'name'           => 'inicio',
    'posts_per_page' => 1,
    'post_status'    => 'publish',
));
if (!empty($ensor_query) && $ensor_query[0] instanceof WP_Post) {
    $ensor_editable = apply_filters('the_content', $ensor_query[0]->post_content);
}

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo ensorlogs_render_fragment_editable('home-body.fragment.html', $ensor_editable);

get_footer();

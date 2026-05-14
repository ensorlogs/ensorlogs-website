<?php
/**
 * Página Contacto (slug: contact).
 *
 * El editor controla la zona marcada con `<!-- ensor:editable -->` dentro
 * del fragment (cabecera del formulario).
 *
 * @package Ensorlogs
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$ensor_editable = '';
if (have_posts()) {
    while (have_posts()) {
        the_post();
        $ensor_editable = apply_filters('the_content', get_the_content());
    }
}

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo ensorlogs_render_fragment_editable('page-contact.fragment.html', $ensor_editable);

get_footer();

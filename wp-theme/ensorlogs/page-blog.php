<?php
/**
 * Página Blog / Hablemos de… (slug: blog).
 *
 * @package Ensorlogs
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
get_template_part('template-parts/blog-listing');
get_footer();

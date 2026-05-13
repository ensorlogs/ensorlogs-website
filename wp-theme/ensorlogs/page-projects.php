<?php
/**
 * Página Proyectos (slug: projects).
 *
 * @package Ensorlogs
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
get_template_part('template-parts/projects-listing');
get_footer();

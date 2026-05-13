<?php
/**
 * Página Servicios (slug: services).
 *
 * @package Ensorlogs
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo ensorlogs_render_fragment('page-services.fragment.html');
get_footer();

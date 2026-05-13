<?php
/**
 * Página Credenciales (slug: credentials).
 *
 * @package Ensorlogs
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo ensorlogs_render_fragment('page-credentials.fragment.html');
get_footer();

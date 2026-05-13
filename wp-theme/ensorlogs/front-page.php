<?php
/**
 * Portada (fragmento migrado desde index.html).
 *
 * @package Ensorlogs
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo ensorlogs_render_fragment('home-body.fragment.html');
get_footer();

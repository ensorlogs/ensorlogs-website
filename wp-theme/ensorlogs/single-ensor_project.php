<?php
/**
 * Proyecto: HTML importado o contenido en bloques (Gutenberg).
 *
 * @package Ensorlogs
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
while (have_posts()) {
    the_post();
    $content = get_post()->post_content;
    $has_shell = (bool) preg_match('/class=["\'][^"\']*main-content/m', $content);

    if ($has_shell) {
        if (function_exists('has_blocks') && has_blocks($content)) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo apply_filters('the_content', $content);
        } else {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $content;
        }
        continue;
    }
    ?>
<div class="main-content mt-28 md:mt-32 lg:mt-36 xl:mt-48">
    <div class="container max-w-4xl">
        <article id="post-<?php the_ID(); ?>" <?php post_class(''); ?>>
            <div class="entry-content ensor-wp-content max-w-none">
                <?php
                if (function_exists('has_blocks') && has_blocks($content)) {
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo apply_filters('the_content', $content);
                } else {
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo $content;
                }
                ?>
            </div>
        </article>
    </div>
</div>
    <?php
}
get_footer();

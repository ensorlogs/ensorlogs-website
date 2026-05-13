<?php
/**
 * Página genérica: contenido del editor / Elementor.
 *
 * @package Ensorlogs
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>
<div class="main-content mt-28 md:mt-32 lg:mt-36 xl:mt-48">
    <div class="container space-y-6">
        <?php
        while (have_posts()) {
            the_post();
            ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class('max-w-none'); ?>>
                <div class="entry-content ensor-wp-content">
                    <?php the_content(); ?>
                </div>
            </article>
            <?php
        }
        ?>
    </div>
</div>
<?php
get_footer();

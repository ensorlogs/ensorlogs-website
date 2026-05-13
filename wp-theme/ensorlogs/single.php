<?php
/**
 * Entrada individual.
 *
 * @package Ensorlogs
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>
<div class="main-content mt-28 md:mt-32 lg:mt-36 xl:mt-48">
    <div class="container max-w-4xl space-y-6">
        <?php
        while (have_posts()) {
            the_post();
            ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class('rounded-2xl p-6 md:p-10 border border-flasWhite dark:border-flasBlack bg-gradient-to-b from-milkWhite to-seashell dark:from-metalBlack dark:to-oilBlack'); ?> data-aos="fade-up">
                <header class="space-y-2">
                    <h1 class="text-3xl md:text-4xl font-bold text-powerBlack dark:text-pastelGrey"><?php the_title(); ?></h1>
                    <p class="text-sm text-darkGray dark:text-pastelGrey"><?php echo esc_html(get_the_date()); ?></p>
                </header>
                <div class="entry-content ensor-wp-content mt-8 max-w-none">
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

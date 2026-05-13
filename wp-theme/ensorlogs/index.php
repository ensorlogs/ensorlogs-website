<?php
/**
 * Índice de entradas (cuando la portada muestra las últimas publicaciones)
 * y reserva del tema.
 *
 * @package Ensorlogs
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>
<div class="main-content mt-28 md:mt-32 lg:mt-36 xl:mt-48">
    <div class="container space-y-8">
        <?php if (have_posts()) : ?>
            <header class="rounded-2xl p-6 md:p-8 bg-gradient-to-b from-milkWhite to-seashell dark:from-metalBlack dark:to-oilBlack border border-flasWhite dark:border-flasBlack" data-aos="fade-up">
                <h1 class="text-2xl md:text-3xl font-bold text-powerBlack dark:text-pastelGrey"><?php esc_html_e('Entradas', 'ensorlogs'); ?></h1>
            </header>
            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                <?php
                while (have_posts()) {
                    the_post();
                    ?>
                    <article <?php post_class('rounded-2xl p-6 border border-flasWhite dark:border-flasBlack bg-gradient-to-b from-milkWhite to-seashell dark:from-metalBlack dark:to-oilBlack'); ?> data-aos="fade-up">
                        <h2 class="text-lg font-semibold text-powerBlack dark:text-pastelGrey">
                            <a href="<?php the_permalink(); ?>" class="hover:underline"><?php the_title(); ?></a>
                        </h2>
                        <p class="mt-2 text-sm text-darkGray dark:text-pastelGrey"><?php echo esc_html(get_the_date()); ?></p>
                        <div class="mt-4 text-regular text-darkGray dark:text-pastelGrey line-clamp-4">
                            <?php the_excerpt(); ?>
                        </div>
                        <p class="mt-4">
                            <a href="<?php the_permalink(); ?>" class="ensor-cta-hablemos inline-flex items-center justify-center shrink-0 font-semibold py-2 px-5 leading-snug rounded-full text-sm">
                                <span><?php esc_html_e('Leer más', 'ensorlogs'); ?></span>
                            </a>
                        </p>
                    </article>
                    <?php
                }
                ?>
            </div>
            <div class="flex justify-center gap-4 pt-4">
                <?php the_posts_pagination(array('mid_size' => 2)); ?>
            </div>
        <?php else : ?>
            <p class="text-powerBlack dark:text-pastelGrey"><?php esc_html_e('No hay entradas todavía.', 'ensorlogs'); ?></p>
        <?php endif; ?>
    </div>
</div>
<?php
get_footer();

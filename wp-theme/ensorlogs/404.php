<?php
/**
 * Página no encontrada.
 *
 * @package Ensorlogs
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>
<div class="main-content mt-28 md:mt-32 lg:mt-36 xl:mt-48">
    <div class="container max-w-2xl text-center space-y-6 rounded-2xl p-8 md:p-12 bg-gradient-to-b from-milkWhite to-seashell dark:from-metalBlack dark:to-oilBlack border border-flasWhite dark:border-flasBlack" data-aos="fade-up">
        <h1 class="text-3xl md:text-4xl font-bold text-powerBlack dark:text-pastelGrey"><?php esc_html_e('404 — Página no encontrada', 'ensorlogs'); ?></h1>
        <p class="text-darkGray dark:text-pastelGrey"><?php esc_html_e('El enlace puede estar roto o la página ya no existe.', 'ensorlogs'); ?></p>
        <p>
            <a href="<?php echo esc_url(home_url('/')); ?>" class="ensor-cta-hablemos inline-flex items-center justify-center shrink-0 font-semibold py-2 px-5 md:py-2.5 md:px-7 leading-snug rounded-full">
                <span><?php esc_html_e('Volver al inicio', 'ensorlogs'); ?></span>
            </a>
        </p>
    </div>
</div>
<?php
get_footer();

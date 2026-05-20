<?php
/**
 * Pie global + scripts.
 *
 * @package Ensorlogs
 */
if (!defined('ABSPATH')) {
    exit;
}
?>
    <footer class="mt-24 pb-8" data-aos="fade-up">
        <?php
        $ensor_footer_heading_default = __('¿Interesado en trabajar conmigo?', 'ensorlogs');
        $ensor_footer_cta_link        = __('Contáctame', 'ensorlogs');
        $ensor_footer_lead            = __(
            'Escríbeme para un proyecto, una colaboración técnica, un taller o un curso. También me gusta hablar con gente de la comunidad y aprender de quienes saben.',
            'ensorlogs'
        );
        if (function_exists('ensorlogs_get_footer_cta')) {
            $ensor_footer_heading_default = ensorlogs_get_footer_cta();
        }
        ?>
        <div class="container text-center">
            <div class="ensor-footer-cta" data-aos="fade-up">
                <span class="ensor-footer-cta__icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="4"></circle>
                        <path d="M16 8v5a3 3 0 0 0 6 0v-1a10 10 0 1 0-3.9 7.92"></path>
                    </svg>
                </span>
                <h5 class="ensor-footer-cta__heading">
                    <?php echo wp_kses(nl2br(esc_html($ensor_footer_heading_default)), array('br' => array())); ?>
                    <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="ensor-footer-cta__link"><?php echo esc_html($ensor_footer_cta_link); ?></a>
                </h5>
                <p class="ensor-footer-cta__lead">
                    <?php echo esc_html($ensor_footer_lead); ?>
                </p>
                <?php if (function_exists('ensorlogs_render_newsletter_button')) : ?>
                    <p class="ensor-footer-cta__newsletter">
                        <?php
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        echo ensorlogs_render_newsletter_button('ensor-footer-newsletter-btn');
                        ?>
                    </p>
                <?php endif; ?>
                <p class="ensor-footer-cta__alt">
                    <a href="https://calendly.com/ensorlogs/30min" target="_blank" rel="noopener noreferrer" class="ensor-footer-cta__alt-link">
                        <i class="far fa-calendar-alt" aria-hidden="true"></i>
                        <span><?php esc_html_e('¿Prefieres una llamada? Reserva 30 min en Calendly', 'ensorlogs'); ?></span>
                        <i class="fal fa-arrow-right text-xs" aria-hidden="true"></i>
                    </a>
                </p>
                <p class="ensor-footer-cta__copy">
                    &copy;<?php echo esc_html(gmdate('Y')); ?>
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="text-darkGray font-medium dark:text-white">Ensorlogs</a>.
                    <?php esc_html_e('Todos los derechos reservados', 'ensorlogs'); ?>
                    <span class="ensor-footer-cta__love" aria-label="<?php esc_attr_e('Hecho con amor y WordPress', 'ensorlogs'); ?>">
                        · <?php esc_html_e('Hecho con', 'ensorlogs'); ?>
                        <span class="ensor-footer-cta__heart" aria-hidden="true">❤️</span>
                        <?php esc_html_e('y', 'ensorlogs'); ?>
                        <a href="https://wordpress.org/" target="_blank" rel="noopener noreferrer" class="ensor-footer-cta__wp">WordPress</a>
                    </span>
                </p>
            </div>
        </div>
        <?php
        $ensor_legal_links = array(
            'legal/aviso-legal' => __('Aviso legal', 'ensorlogs'),
            'legal/privacidad' => __('Privacidad', 'ensorlogs'),
            'legal/cookies'    => __('Cookies', 'ensorlogs'),
            'legal/accesibilidad' => __('Accesibilidad', 'ensorlogs'),
        );
        ?>
        <div id="ensor-legal-row" class="container max-w-[1180px] mx-auto px-4 pt-2 pb-6">
            <nav aria-label="<?php esc_attr_e('Enlaces legales', 'ensorlogs'); ?>" class="flex flex-wrap gap-x-5 gap-y-2 text-xs text-darkGray/85 dark:text-pastelGrey/70 border-t border-black/5 dark:border-white/5 pt-4">
                <?php foreach ($ensor_legal_links as $slug => $label) :
                    $page = get_page_by_path($slug);
                    $url  = $page ? get_permalink($page) : home_url('/' . $slug . '/');
                    ?>
                    <a href="<?php echo esc_url($url); ?>" class="hover:underline"><?php echo esc_html($label); ?></a>
                <?php endforeach; ?>
                <button type="button" class="ensor-cookies-reopen" data-ensor-cookies-open><?php esc_html_e('Preferencias de cookies', 'ensorlogs'); ?></button>
            </nav>
        </div>
    </footer>

</main>

<a href="#top" title="<?php esc_attr_e('Scroll Top', 'ensorlogs'); ?>" id="scroll-top" class="topbutton fixed right-4 xl:right-7 2xl:right-8 bottom-6 xl:bottom-7.5 w-13 h-13 text-lg rounded-full bg-white dark:bg-powerBlack shadow-sm shadow-slate-400 grid place-content-center text-black dark:text-white opacity-0 invisible transition duration-200 [&.btn-show]:opacity-100 [&.btn-show]:visible z-[9999]">
    <i class="far fa-level-up-alt"></i>
</a>

<?php
if (function_exists('ensorlogs_render_newsletter_modal')) {
    ensorlogs_render_newsletter_modal();
}
?>

<?php wp_footer(); ?>
</body>
</html>

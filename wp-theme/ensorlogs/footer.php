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
        $ensor_footer_heading_default = function_exists('ensorlogs_get_footer_cta')
            ? ensorlogs_get_footer_cta()
            : (function_exists('ensorlogs_t')
                ? ensorlogs_t('¿Interesado en trabajar conmigo?', 'Interested in working with me?')
                : __('¿Interesado en trabajar conmigo?', 'ensorlogs'));
        $ensor_footer_cta_link        = function_exists('ensorlogs_t')
            ? ensorlogs_t('Contáctame', 'Contact me')
            : __('Contáctame', 'ensorlogs');
        ?>
        <div class="container text-center">
            <div class="ensor-footer-cta" data-aos="fade-up">
                <span class="ensor-footer-cta__icon ensor-footer-cta__icon--logo" aria-hidden="true">
                    <img src="<?php echo esc_url(function_exists('ensorlogs_brand_logo_url') ? ensorlogs_brand_logo_url() : get_template_directory_uri() . '/assets/img/Logos/ensorlogs2.png'); ?>" alt="" width="64" height="64" decoding="async" class="ensor-footer-cta__logo-img">
                </span>
                <h5 class="ensor-footer-cta__heading">
                    <?php echo wp_kses(nl2br(esc_html($ensor_footer_heading_default)), array('br' => array())); ?>
                    <a href="<?php echo esc_url(function_exists('ensorlogs_lang_url') ? ensorlogs_lang_url('/contact/') : home_url('/contact/')); ?>" class="ensor-footer-cta__link"><?php echo esc_html($ensor_footer_cta_link); ?></a>
                </h5>
                <?php if (function_exists('ensorlogs_footer_social_nav_html')) : ?>
                    <?php
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo ensorlogs_footer_social_nav_html();
                    ?>
                <?php endif; ?>
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
                        <span><?php echo esc_html(function_exists('ensorlogs_t') ? ensorlogs_t('¿Prefieres una llamada? Reserva 30 min en Calendly', 'Prefer a call? Book 30 min on Calendly') : __('¿Prefieres una llamada? Reserva 30 min en Calendly', 'ensorlogs')); ?></span>
                        <i class="fal fa-arrow-right text-xs" aria-hidden="true"></i>
                    </a>
                </p>
                <p class="ensor-footer-cta__copy">
                    &copy;<?php echo esc_html(gmdate('Y')); ?>
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="text-darkGray font-medium dark:text-white">Ensorlogs</a>.
                    <?php echo esc_html(function_exists('ensorlogs_t') ? ensorlogs_t('Todos los derechos reservados', 'All rights reserved') : __('Todos los derechos reservados', 'ensorlogs')); ?>
                    <span class="ensor-footer-cta__love" aria-label="<?php echo esc_attr(function_exists('ensorlogs_t') ? ensorlogs_t('Hecho con amor y WordPress', 'Made with love and WordPress') : __('Hecho con amor y WordPress', 'ensorlogs')); ?>">
                        · <?php echo esc_html(function_exists('ensorlogs_t') ? ensorlogs_t('Hecho con', 'Made with') : __('Hecho con', 'ensorlogs')); ?>
                        <span class="ensor-footer-cta__heart" aria-hidden="true">❤️</span>
                        <?php echo esc_html(function_exists('ensorlogs_t') ? ensorlogs_t('y', 'and') : __('y', 'ensorlogs')); ?>
                        <a href="https://wordpress.org/" target="_blank" rel="noopener noreferrer" class="ensor-footer-cta__wp">WordPress</a>
                    </span>
                </p>
            </div>
        </div>
        <?php
        $ensor_legal_links = array(
            'legal/aviso-legal' => function_exists('ensorlogs_t') ? ensorlogs_t('Aviso legal', 'Legal notice') : __('Aviso legal', 'ensorlogs'),
            'legal/privacidad' => function_exists('ensorlogs_t') ? ensorlogs_t('Privacidad', 'Privacy') : __('Privacidad', 'ensorlogs'),
            'legal/cookies'    => function_exists('ensorlogs_t') ? ensorlogs_t('Cookies', 'Cookies') : __('Cookies', 'ensorlogs'),
            'legal/accesibilidad' => function_exists('ensorlogs_t') ? ensorlogs_t('Accesibilidad', 'Accessibility') : __('Accesibilidad', 'ensorlogs'),
        );
        ?>
        <div id="ensor-legal-row" class="container max-w-[1180px] mx-auto px-4 pt-2 pb-6">
            <nav aria-label="<?php echo esc_attr(function_exists('ensorlogs_t') ? ensorlogs_t('Enlaces legales', 'Legal links') : __('Enlaces legales', 'ensorlogs')); ?>" class="flex flex-wrap gap-x-5 gap-y-2 text-xs text-darkGray/85 dark:text-pastelGrey/70 border-t border-black/5 dark:border-white/5 pt-4">
                <?php foreach ($ensor_legal_links as $slug => $label) :
                    $page = get_page_by_path($slug);
                    if (function_exists('ensorlogs_lang_url')) {
                        $url = ensorlogs_lang_url('/' . $slug . '/');
                    } else {
                        $url = $page ? get_permalink($page) : home_url('/' . $slug . '/');
                    }
                    ?>
                    <a href="<?php echo esc_url($url); ?>" class="hover:underline"><?php echo esc_html($label); ?></a>
                <?php endforeach; ?>
                <button type="button" class="ensor-cookies-reopen" data-ensor-cookies-open><?php echo esc_html(function_exists('ensorlogs_t') ? ensorlogs_t('Preferencias de cookies', 'Cookie preferences') : __('Preferencias de cookies', 'ensorlogs')); ?></button>
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

<?php
/**
 * Newsletter popup (Mailchimp plugin) + botones «Notifícame».
 *
 * @package Ensorlogs
 */

if (!defined('ABSPATH')) {
    exit;
}

function ensorlogs_newsletter_enabled(): bool
{
    return (bool) get_theme_mod('ensor_newsletter_enabled', true);
}

function ensorlogs_get_newsletter_title(): string
{
    return (string) get_theme_mod(
        'ensor_newsletter_title',
        __('Entérate de los nuevos logs', 'ensorlogs')
    );
}

function ensorlogs_get_newsletter_description(): string
{
    return (string) get_theme_mod(
        'ensor_newsletter_description',
        __(
            'Suscríbete gratis a la lista y te aviso cuando publique un log nuevo: WordPress, datos, automatización y lo que vaya aprendiendo en la bitácora.',
            'ensorlogs'
        )
    );
}

function ensorlogs_newsletter_privacy_url(): string
{
    $page = get_page_by_path('legal/privacidad');
    if (!$page) {
        $page = get_page_by_path('privacidad');
    }
    if ($page instanceof WP_Post) {
        $permalink = get_permalink($page);
        if (is_string($permalink) && $permalink !== '') {
            return $permalink;
        }
    }

    return home_url('/legal/privacidad/');
}

function ensorlogs_get_mailchimp_form_html(): string
{
    if (function_exists('mailchimpSF_signup_form')) {
        ob_start();
        mailchimpSF_signup_form();
        $html = (string) ob_get_clean();
        if (trim($html) !== '') {
            return $html;
        }
    }

    if (shortcode_exists('mc4wp_form')) {
        $html = do_shortcode('[mc4wp_form]');
        if (is_string($html) && trim($html) !== '') {
            return $html;
        }
    }

    if (shortcode_exists('mailchimp')) {
        $html = do_shortcode('[mailchimp]');
        if (is_string($html) && trim($html) !== '') {
            return $html;
        }
    }

    if (shortcode_exists('mailchimp_sf_form')) {
        $html = do_shortcode('[mailchimp_sf_form]');
        if (is_string($html) && trim($html) !== '') {
            return $html;
        }
    }

    return '<p class="ensor-newsletter-form__fallback">'
        . esc_html__(
            'Activa el plugin Mailchimp en WordPress y conecta la lista ensorlogs para mostrar el formulario aquí.',
            'ensorlogs'
        )
        . '</p>';
}

function ensorlogs_render_newsletter_button(string $extra_classes = '', string $label = ''): string
{
    if (!ensorlogs_newsletter_enabled()) {
        return '';
    }

    if ($label === '') {
        $label = __('Notifícame', 'ensorlogs');
    }

    $classes = trim(
        'ensor-newsletter-open ensor-btn ensor-btn-outline rounded-full text-regular py-2 lg:py-3 px-7 lg:px-10 ' . $extra_classes
    );

    return sprintf(
        '<button type="button" class="%1$s" aria-haspopup="dialog" aria-controls="ensor-newsletter-modal">%2$s</button>',
        esc_attr($classes),
        esc_html($label)
    );
}

/**
 * Bloque de suscripción para la página Contacto (sin formulario de mensajes).
 */
function ensorlogs_contact_subscribe_block(): string
{
    if (!ensorlogs_newsletter_enabled()) {
        return '<p class="text-sm text-nobelGray dark:text-slateGray">'
            . esc_html__(
                'La suscripción por correo se activa cuando conectas Mailchimp en WordPress.',
                'ensorlogs'
            )
            . '</p>';
    }

    $button = ensorlogs_render_newsletter_button(
        'ensor-btn-primary',
        __('Suscribirme a la lista', 'ensorlogs')
    );

    return '<div class="ensor-contact-subscribe">'
        . '<p class="text-darkGray dark:text-pastelGrey leading-relaxed max-w-xl">'
        . esc_html__(
            'Recibe un aviso cuando publique un log nuevo: WordPress, datos, automatización y lo que vaya documentando en la bitácora.',
            'ensorlogs'
        )
        . '</p>'
        . '<p class="ensor-contact-newsletter">'
        . $button
        . '</p>'
        . '<p class="text-xs text-nobelGray dark:text-slateGray">'
        . esc_html__('Sin spam. Puedes darte de baja cuando quieras.', 'ensorlogs')
        . '</p>'
        . '</div>';
}

/**
 * @return array<string, string>
 */
function ensorlogs_newsletter_fragment_tokens(): array
{
    return array(
        '%%NEWSLETTER_BUTTON%%' => ensorlogs_render_newsletter_button(),
    );
}

function ensorlogs_render_newsletter_modal(): void
{
    if (!ensorlogs_newsletter_enabled()) {
        return;
    }

    $title       = ensorlogs_get_newsletter_title();
    $description = ensorlogs_get_newsletter_description();
    $privacy_url = ensorlogs_newsletter_privacy_url();
    $form_html   = ensorlogs_get_mailchimp_form_html();
    ?>
    <div
        id="ensor-newsletter-modal"
        class="ensor-newsletter-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="ensor-newsletter-title"
        aria-describedby="ensor-newsletter-desc"
        aria-hidden="true"
        hidden
    >
        <div class="ensor-newsletter-modal__overlay" data-ensor-newsletter-close tabindex="-1"></div>
        <div class="ensor-newsletter-modal__panel">
            <button
                type="button"
                class="ensor-newsletter-modal__close"
                data-ensor-newsletter-close
                aria-label="<?php esc_attr_e('Cerrar', 'ensorlogs'); ?>"
            >
                <span aria-hidden="true">&times;</span>
            </button>
            <h2 id="ensor-newsletter-title" class="ensor-newsletter-modal__title">
                <?php echo esc_html($title); ?>
            </h2>
            <p id="ensor-newsletter-desc" class="ensor-newsletter-modal__lead">
                <?php echo esc_html($description); ?>
            </p>
            <div class="ensor-newsletter-modal__form ensor-newsletter-form">
                <?php
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo $form_html;
                ?>
            </div>
            <p class="ensor-newsletter-modal__legal">
                <?php
                echo wp_kses(
                    sprintf(
                        /* translators: %s: privacy policy URL */
                        __('Al suscribirte aceptas recibir correos de la lista Ensorlogs. Consulta la <a href="%s">política de privacidad</a>.', 'ensorlogs'),
                        esc_url($privacy_url)
                    ),
                    array(
                        'a' => array(
                            'href' => array(),
                        ),
                    )
                );
                ?>
            </p>
        </div>
    </div>
    <?php
}

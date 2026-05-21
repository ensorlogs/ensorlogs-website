<?php
/**
 * Newsletter popup + suscripción Mailchimp (API).
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
    $default = function_exists('ensorlogs_t')
        ? ensorlogs_t('Entérate de los nuevos logs', 'Get notified about new logs')
        : __('Entérate de los nuevos logs', 'ensorlogs');

    return (string) get_theme_mod('ensor_newsletter_title', $default);
}

function ensorlogs_get_newsletter_description(): string
{
    $default = function_exists('ensorlogs_t')
        ? ensorlogs_t(
            'Suscríbete gratis a la lista y te aviso cuando publique un log nuevo: WordPress, datos, automatización y lo que vaya aprendiendo en la bitácora.',
            'Subscribe to the list for free — I will let you know when I publish a new log on WordPress, data, automation and whatever I learn in the logbook.'
        )
        : __(
            'Suscríbete gratis a la lista y te aviso cuando publique un log nuevo: WordPress, datos, automatización y lo que vaya aprendiendo en la bitácora.',
            'ensorlogs'
        );

    return (string) get_theme_mod('ensor_newsletter_description', $default);
}

function ensorlogs_newsletter_privacy_url(): string
{
    if (function_exists('ensorlogs_lang_url')) {
        return ensorlogs_lang_url('/legal/privacidad/');
    }

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

function ensorlogs_mailchimp_api_key(): string
{
    return trim((string) get_theme_mod('ensor_mailchimp_api_key', ''));
}

function ensorlogs_mailchimp_list_id(): string
{
    return trim((string) get_theme_mod('ensor_mailchimp_list_id', ''));
}

function ensorlogs_mailchimp_member_status(): string
{
    $status = (string) get_theme_mod('ensor_mailchimp_status', 'subscribed');

    return $status === 'pending' ? 'pending' : 'subscribed';
}

/**
 * @return array{dc: string, key: string}|null
 */
function ensorlogs_mailchimp_parse_api_key(string $api_key): ?array
{
    if ($api_key === '' || strpos($api_key, '-') === false) {
        return null;
    }

    $parts = explode('-', $api_key);
    $dc    = strtolower((string) array_pop($parts));
    $key   = implode('-', $parts);

    if ($key === '' || $dc === '') {
        return null;
    }

    return array(
        'dc'  => $dc,
        'key' => $api_key,
    );
}

function ensorlogs_mailchimp_configured(): bool
{
    $parsed = ensorlogs_mailchimp_parse_api_key(ensorlogs_mailchimp_api_key());

    return $parsed !== null && ensorlogs_mailchimp_list_id() !== '';
}

/**
 * @return array{ok: bool, message: string}
 */
function ensorlogs_mailchimp_subscribe_email(string $email): array
{
    if (!is_email($email)) {
        return array(
            'ok'      => false,
            'message' => function_exists('ensorlogs_t')
                ? ensorlogs_t('Introduce un correo válido.', 'Enter a valid email address.')
                : __('Introduce un correo válido.', 'ensorlogs'),
        );
    }

    if (!ensorlogs_mailchimp_configured()) {
        return array(
            'ok'      => false,
            'message' => function_exists('ensorlogs_t')
                ? ensorlogs_t(
                    'Falta configurar Mailchimp en Apariencia → Personalizar → Ensorlogs → Newsletter.',
                    'Mailchimp is not configured yet. Go to Appearance → Customize → Ensorlogs → Newsletter.'
                )
                : __('Falta configurar Mailchimp en Apariencia → Personalizar → Ensorlogs → Newsletter.', 'ensorlogs'),
        );
    }

    $parsed  = ensorlogs_mailchimp_parse_api_key(ensorlogs_mailchimp_api_key());
    $list_id = ensorlogs_mailchimp_list_id();
    $url     = sprintf(
        'https://%s.api.mailchimp.com/3.0/lists/%s/members',
        rawurlencode($parsed['dc']),
        rawurlencode($list_id)
    );

    $body = wp_json_encode(
        array(
            'email_address' => $email,
            'status'        => ensorlogs_mailchimp_member_status(),
        )
    );

    $response = wp_remote_post(
        $url,
        array(
            'timeout' => 15,
            'headers' => array(
                'Authorization' => 'apikey ' . $parsed['key'],
                'Content-Type'  => 'application/json',
            ),
            'body'    => $body,
        )
    );

    if (is_wp_error($response)) {
        return array(
            'ok'      => false,
            'message' => function_exists('ensorlogs_t')
                ? ensorlogs_t('No se pudo conectar con Mailchimp. Inténtalo más tarde.', 'Could not connect to Mailchimp. Try again later.')
                : __('No se pudo conectar con Mailchimp. Inténtalo más tarde.', 'ensorlogs'),
        );
    }

    $code = (int) wp_remote_retrieve_response_code($response);
    $raw  = (string) wp_remote_retrieve_body($response);
    $data = json_decode($raw, true);

    if ($code >= 200 && $code < 300) {
        return array(
            'ok'      => true,
            'message' => function_exists('ensorlogs_t')
                ? ensorlogs_t('¡Listo! Revisa tu correo si hace falta confirmar la suscripción.', 'Done! Check your inbox if you need to confirm your subscription.')
                : __('¡Listo! Revisa tu correo si hace falta confirmar la suscripción.', 'ensorlogs'),
        );
    }

    if ($code === 400 && is_array($data)) {
        $title = isset($data['title']) ? (string) $data['title'] : '';
        if ($title === 'Member Exists') {
            return array(
                'ok'      => true,
                'message' => function_exists('ensorlogs_t')
                    ? ensorlogs_t('Este correo ya está en la lista.', 'This email is already on the list.')
                    : __('Este correo ya está en la lista.', 'ensorlogs'),
            );
        }
        if (!empty($data['detail']) && is_string($data['detail'])) {
            return array(
                'ok'      => false,
                'message' => $data['detail'],
            );
        }
    }

    return array(
        'ok'      => false,
        'message' => function_exists('ensorlogs_t')
            ? ensorlogs_t('No se pudo completar la suscripción. Revisa la configuración de Mailchimp.', 'Subscription could not be completed. Check your Mailchimp settings.')
            : __('No se pudo completar la suscripción. Revisa la configuración de Mailchimp.', 'ensorlogs'),
    );
}

function ensorlogs_ajax_newsletter_subscribe(): void
{
    check_ajax_referer('ensor_newsletter_subscribe', 'nonce');

    $email = isset($_POST['email']) ? sanitize_email(wp_unslash((string) $_POST['email'])) : '';
    $result = ensorlogs_mailchimp_subscribe_email($email);

    if ($result['ok']) {
        wp_send_json_success(array('message' => $result['message']));
    }

    wp_send_json_error(array('message' => $result['message']));
}

add_action('wp_ajax_ensor_newsletter_subscribe', 'ensorlogs_ajax_newsletter_subscribe');
add_action('wp_ajax_nopriv_ensor_newsletter_subscribe', 'ensorlogs_ajax_newsletter_subscribe');

function ensorlogs_render_newsletter_button(string $extra_classes = '', string $label = ''): string
{
    if (!ensorlogs_newsletter_enabled()) {
        return '';
    }

    if ($label === '') {
        $label = function_exists('ensorlogs_t')
            ? ensorlogs_t('Notifícame', 'Notify me')
            : __('Notifícame', 'ensorlogs');
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

function ensorlogs_render_newsletter_form(): string
{
    $email_label = function_exists('ensorlogs_t')
        ? ensorlogs_t('Tu correo electrónico', 'Your email address')
        : __('Tu correo electrónico', 'ensorlogs');
    $submit_label = function_exists('ensorlogs_t')
        ? ensorlogs_t('Suscribirme', 'Subscribe')
        : __('Suscribirme', 'ensorlogs');
    $placeholder = $email_label;

    $configured = ensorlogs_mailchimp_configured();

    ob_start();
    ?>
    <form class="ensor-newsletter-native-form" action="#" method="post" novalidate>
        <label for="ensor-newsletter-email"><?php echo esc_html($email_label); ?></label>
        <input
            type="email"
            id="ensor-newsletter-email"
            name="email"
            placeholder="<?php echo esc_attr($placeholder); ?>"
            required
            autocomplete="email"
            <?php echo $configured ? '' : ' aria-describedby="ensor-newsletter-config-hint"'; ?>
        >
        <?php if (!$configured) : ?>
            <p id="ensor-newsletter-config-hint" class="ensor-newsletter-form__hint" role="status">
                <?php
                echo esc_html__(
                    'Configura API key y Audience ID en Personalizar → Ensorlogs → Newsletter.',
                    'ensorlogs'
                );
                ?>
            </p>
        <?php endif; ?>
        <button type="submit" class="ensor-newsletter-submit"><?php echo esc_html($submit_label); ?></button>
        <p class="ensor-newsletter-form__status" role="status" aria-live="polite" hidden></p>
    </form>
    <?php
    return (string) ob_get_clean();
}

/**
 * Bloque de suscripción para la página Contacto (sin formulario de mensajes).
 */
function ensorlogs_contact_subscribe_block(): string
{
    if (!ensorlogs_newsletter_enabled()) {
        return '<p class="text-sm text-nobelGray dark:text-slateGray">'
            . esc_html__(
                'La suscripción por correo se activa en Personalizar → Ensorlogs → Newsletter.',
                'ensorlogs'
            )
            . '</p>';
    }

    $button = ensorlogs_render_newsletter_button(
        'ensor-btn-primary',
        function_exists('ensorlogs_t')
            ? ensorlogs_t('Suscribirme a la lista', 'Subscribe to the list')
            : __('Suscribirme a la lista', 'ensorlogs')
    );

    $lead = function_exists('ensorlogs_t')
        ? ensorlogs_t(
            'Recibe un aviso cuando publique un log nuevo: WordPress, datos, automatización y lo que vaya documentando en la bitácora.',
            'Get notified when I publish a new log on WordPress, data, automation and what I document in the logbook.'
        )
        : __(
            'Recibe un aviso cuando publique un log nuevo: WordPress, datos, automatización y lo que vaya documentando en la bitácora.',
            'ensorlogs'
        );

    return '<div class="ensor-contact-subscribe">'
        . '<p class="text-darkGray dark:text-pastelGrey leading-relaxed max-w-xl">'
        . esc_html($lead)
        . '</p>'
        . '<p class="ensor-contact-newsletter">'
        . $button
        . '</p>'
        . '<p class="text-xs text-nobelGray dark:text-slateGray">'
        . esc_html__(
            'Sin spam. Puedes darte de baja cuando quieras.',
            'ensorlogs'
        )
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
    $form_html   = ensorlogs_render_newsletter_form();
    $close_label = function_exists('ensorlogs_t')
        ? ensorlogs_t('Cerrar', 'Close')
        : __('Cerrar', 'ensorlogs');
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
                aria-label="<?php echo esc_attr($close_label); ?>"
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
                $legal_line = function_exists('ensorlogs_t')
                    ? ensorlogs_t(
                        'Al suscribirte aceptas recibir correos de la lista Ensorlogs. Consulta la <a href="%s">política de privacidad</a>.',
                        'By subscribing you agree to receive emails from the Ensorlogs list. See the <a href="%s">privacy policy</a>.'
                    )
                    : __('Al suscribirte aceptas recibir correos de la lista Ensorlogs. Consulta la <a href="%s">política de privacidad</a>.', 'ensorlogs');
                echo wp_kses(
                    sprintf(
                        /* translators: %s: privacy policy URL */
                        $legal_line,
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

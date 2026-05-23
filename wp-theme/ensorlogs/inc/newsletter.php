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
    if (defined('ENSOR_MAILCHIMP_API_KEY') && is_string(ENSOR_MAILCHIMP_API_KEY)) {
        $from_const = trim(ENSOR_MAILCHIMP_API_KEY);
        if ($from_const !== '') {
            return $from_const;
        }
    }

    return trim((string) get_theme_mod('ensor_mailchimp_api_key', ''));
}

function ensorlogs_mailchimp_list_id(): string
{
    if (defined('ENSOR_MAILCHIMP_LIST_ID') && is_string(ENSOR_MAILCHIMP_LIST_ID)) {
        $from_const = trim(ENSOR_MAILCHIMP_LIST_ID);
        if ($from_const !== '') {
            return $from_const;
        }
    }

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
    return ensorlogs_mailchimp_config_issue() === '';
}

/**
 * Motivo por el que Mailchimp no está listo (cadena vacía = OK).
 */
function ensorlogs_mailchimp_config_issue(): string
{
    $api_key = ensorlogs_mailchimp_api_key();
    $list_id = ensorlogs_mailchimp_list_id();

    if ($api_key === '') {
        return 'api_key_missing';
    }

    if (ensorlogs_mailchimp_parse_api_key($api_key) === null) {
        return 'api_key_invalid';
    }

    if ($list_id === '') {
        return 'list_id_missing';
    }

    return '';
}

function ensorlogs_mailchimp_config_hint_message(): string
{
    $issue = ensorlogs_mailchimp_config_issue();

    if ($issue === 'api_key_missing') {
        return function_exists('ensorlogs_t')
            ? ensorlogs_t(
                'Falta la API key. En Personalizar → Ensorlogs → Newsletter pégala de nuevo y pulsa «Publicar» (el campo en blanco no la guarda).',
                'API key missing. In Customize → Ensorlogs → Newsletter paste it again and click «Publish» (a blank field does not save it).'
            )
            : __(
                'Falta la API key. En Personalizar → Ensorlogs → Newsletter pégala de nuevo y pulsa «Publicar» (el campo en blanco no la guarda).',
                'ensorlogs'
            );
    }

    if ($issue === 'api_key_invalid') {
        return function_exists('ensorlogs_t')
            ? ensorlogs_t(
                'La API key no tiene el formato correcto (debe terminar en -us21, -us22, etc.).',
                'The API key format is invalid (it must end with -us21, -us22, etc.).'
            )
            : __(
                'La API key no tiene el formato correcto (debe terminar en -us21, -us22, etc.).',
                'ensorlogs'
            );
    }

    if ($issue === 'list_id_missing') {
        return function_exists('ensorlogs_t')
            ? ensorlogs_t(
                'Falta el Audience ID. Cópialo en Mailchimp → Audience → Settings.',
                'Audience ID missing. Copy it from Mailchimp → Audience → Settings.'
            )
            : __('Falta el Audience ID. Cópialo en Mailchimp → Audience → Settings.', 'ensorlogs');
    }

    return function_exists('ensorlogs_t')
        ? ensorlogs_t(
            'Configura API key y Audience ID en Personalizar → Ensorlogs → Newsletter.',
            'Set the API key and Audience ID under Customize → Ensorlogs → Newsletter.'
        )
        : __('Configura API key y Audience ID en Personalizar → Ensorlogs → Newsletter.', 'ensorlogs');
}

function ensorlogs_mailchimp_subscriber_hash(string $email): string
{
    return md5(strtolower(trim($email)));
}

function ensorlogs_newsletter_success_message(bool $already_member = false): string
{
    if ($already_member) {
        return function_exists('ensorlogs_t')
            ? ensorlogs_t(
                'Este correo ya estaba en la lista. ¡Gracias por seguir aquí!',
                'This email was already on the list. Thanks for staying with us!'
            )
            : __('Este correo ya estaba en la lista. ¡Gracias por seguir aquí!', 'ensorlogs');
    }

    $status = ensorlogs_mailchimp_member_status();
    if ($status === 'pending') {
        return function_exists('ensorlogs_t')
            ? ensorlogs_t(
                'Te has suscrito correctamente. Revisa tu correo para confirmar.',
                'You subscribed successfully. Check your inbox to confirm.'
            )
            : __('Te has suscrito correctamente. Revisa tu correo para confirmar.', 'ensorlogs');
    }

    return function_exists('ensorlogs_t')
        ? ensorlogs_t('Te has suscrito correctamente. ¡Gracias!', 'You subscribed successfully. Thank you!')
        : __('Te has suscrito correctamente. ¡Gracias!', 'ensorlogs');
}

/**
 * @param array<string, mixed>|null $data
 */
function ensorlogs_mailchimp_error_message(int $code, ?array $data): string
{
    if (is_array($data)) {
        $title  = isset($data['title']) ? (string) $data['title'] : '';
        $detail = isset($data['detail']) ? (string) $data['detail'] : '';

        if ($title === 'Member Exists') {
            return ensorlogs_newsletter_success_message(true);
        }

        if (
            $title === 'Member In Compliance State'
            || stripos($detail, 'confirm') !== false
            || stripos($detail, 'opt-in') !== false
        ) {
            return function_exists('ensorlogs_t')
                ? ensorlogs_t(
                    'Tu audiencia usa doble opt-in. En Personalizar elige «Pendiente (doble opt-in)» o confirma el correo que envía Mailchimp.',
                    'Your audience uses double opt-in. Choose «Pending (double opt-in)» in Customize or confirm the email Mailchimp sends.'
                )
                : __(
                    'Tu audiencia usa doble opt-in. En Personalizar elige «Pendiente (doble opt-in)» o confirma el correo que envía Mailchimp.',
                    'ensorlogs'
                );
        }

        if ($detail !== '') {
            return $detail;
        }
    }

    if ($code === 401 || $code === 403) {
        return function_exists('ensorlogs_t')
            ? ensorlogs_t('API key de Mailchimp no válida. Revísala en Personalizar.', 'Invalid Mailchimp API key. Check it in Customize.')
            : __('API key de Mailchimp no válida. Revísala en Personalizar.', 'ensorlogs');
    }

    if ($code === 404) {
        return function_exists('ensorlogs_t')
            ? ensorlogs_t('Audience ID incorrecto. Copia el ID de Mailchimp → Audience → Settings.', 'Wrong Audience ID. Copy it from Mailchimp → Audience → Settings.')
            : __('Audience ID incorrecto. Copia el ID de Mailchimp → Audience → Settings.', 'ensorlogs');
    }

    return function_exists('ensorlogs_t')
        ? ensorlogs_t('No se pudo completar la suscripción. Revisa la configuración de Mailchimp.', 'Subscription could not be completed. Check your Mailchimp settings.')
        : __('No se pudo completar la suscripción. Revisa la configuración de Mailchimp.', 'ensorlogs');
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
    $status  = ensorlogs_mailchimp_member_status();
    $url     = sprintf(
        'https://%s.api.mailchimp.com/3.0/lists/%s/members/%s',
        rawurlencode($parsed['dc']),
        rawurlencode($list_id),
        ensorlogs_mailchimp_subscriber_hash($email)
    );

    $payload = wp_json_encode(
        array(
            'email_address' => $email,
            'status_if_new' => $status,
            'status'        => $status,
        )
    );

    $response = wp_remote_request(
        $url,
        array(
            'method'  => 'PUT',
            'timeout' => 15,
            'headers' => array(
                'Authorization' => 'apikey ' . $parsed['key'],
                'Content-Type'  => 'application/json',
            ),
            'body'    => $payload,
        )
    );

    if (is_wp_error($response)) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log('Ensorlogs Mailchimp: ' . $response->get_error_message());
        }

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
    $data = is_array($data) ? $data : null;

    if ($code >= 200 && $code < 300) {
        return array(
            'ok'      => true,
            'message' => ensorlogs_newsletter_success_message(false),
        );
    }

    if (defined('WP_DEBUG') && WP_DEBUG && $raw !== '') {
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        error_log('Ensorlogs Mailchimp HTTP ' . $code . ': ' . $raw);
    }

    $error_message = ensorlogs_mailchimp_error_message($code, $data);
    $is_duplicate  = is_array($data) && isset($data['title']) && (string) $data['title'] === 'Member Exists';

    return array(
        'ok'      => $is_duplicate,
        'message' => $error_message,
    );
}

function ensorlogs_ajax_newsletter_subscribe(): void
{
    $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash((string) $_POST['nonce'])) : '';
    if ($nonce === '' || !wp_verify_nonce($nonce, 'ensor_newsletter_subscribe')) {
        wp_send_json_error(
            array(
                'message' => function_exists('ensorlogs_t')
                    ? ensorlogs_t(
                        'La sesión caducó. Recarga la página e inténtalo de nuevo.',
                        'Your session expired. Reload the page and try again.'
                    )
                    : __('La sesión caducó. Recarga la página e inténtalo de nuevo.', 'ensorlogs'),
            )
        );
    }

    $email = isset($_POST['email']) ? sanitize_email(wp_unslash((string) $_POST['email'])) : '';
    $result = ensorlogs_mailchimp_subscribe_email($email);

    if ($result['ok']) {
        wp_send_json_success(array('message' => $result['message']));
    }

    wp_send_json_error(array('message' => $result['message']));
}

add_action('wp_ajax_ensor_newsletter_subscribe', 'ensorlogs_ajax_newsletter_subscribe');
add_action('wp_ajax_nopriv_ensor_newsletter_subscribe', 'ensorlogs_ajax_newsletter_subscribe');

/**
 * Devuelve un nonce nuevo (admin-ajax no suele ir en caché de página).
 */
function ensorlogs_ajax_newsletter_refresh_nonce(): void
{
    wp_send_json_success(
        array(
            'nonce' => wp_create_nonce('ensor_newsletter_subscribe'),
        )
    );
}

add_action('wp_ajax_ensor_newsletter_refresh_nonce', 'ensorlogs_ajax_newsletter_refresh_nonce');
add_action('wp_ajax_nopriv_ensor_newsletter_refresh_nonce', 'ensorlogs_ajax_newsletter_refresh_nonce');

/**
 * Estado de configuración (no expone secretos). Sirve para corregir avisos en HTML cacheado.
 */
function ensorlogs_ajax_newsletter_status(): void
{
    $issue = ensorlogs_mailchimp_config_issue();

    wp_send_json_success(
        array(
            'configured' => $issue === '',
            'issue'      => $issue,
            'message'    => $issue === '' ? '' : ensorlogs_mailchimp_config_hint_message(),
        )
    );
}

add_action('wp_ajax_ensor_newsletter_status', 'ensorlogs_ajax_newsletter_status');
add_action('wp_ajax_nopriv_ensor_newsletter_status', 'ensorlogs_ajax_newsletter_status');

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

    $config_issue = ensorlogs_mailchimp_config_issue();

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
            aria-describedby="ensor-newsletter-config-hint"
        >
        <p
            id="ensor-newsletter-config-hint"
            class="ensor-newsletter-form__hint"
            role="status"
            data-ensor-config-hint
            <?php echo $config_issue === '' ? 'hidden' : ''; ?>
        ><?php echo esc_html(ensorlogs_mailchimp_config_hint_message()); ?></p>
        <button type="submit" class="ensor-newsletter-submit"><?php echo esc_html($submit_label); ?></button>
        <div
            class="ensor-newsletter-form__feedback"
            role="status"
            aria-live="polite"
            aria-atomic="true"
            aria-hidden="true"
            hidden
        ></div>
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
        <div class="ensor-newsletter-modal__overlay" aria-hidden="true"></div>
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

<?php
/**
 * Contact form handling for the WordPress contact page.
 *
 * @package Ensorlogs
 */

if (!defined('ABSPATH')) {
    exit;
}

const ENSORLOGS_CONTACT_RECIPIENT = 'hello@ensorlogs.com';
const ENSORLOGS_CONTACT_CAPTCHA = 11;
const ENSORLOGS_CONTACT_MAX_NAME = 200;
const ENSORLOGS_CONTACT_MAX_SUBJECT = 300;
const ENSORLOGS_CONTACT_MAX_MESSAGE = 30000;

add_action('admin_post_nopriv_ensorlogs_contact', 'ensorlogs_handle_contact_form');
add_action('admin_post_ensorlogs_contact', 'ensorlogs_handle_contact_form');

/**
 * Tokens dinámicos para el fragmento HTML de contacto.
 *
 * @return array<string, string>
 */
function ensorlogs_contact_fragment_tokens(): array
{
    return array(
        '%%CONTACT_FORM_ACTION%%' => esc_url(admin_url('admin-post.php')),
        '%%CONTACT_FORM_NONCE%%'  => wp_nonce_field('ensorlogs_contact_form', 'ensorlogs_contact_nonce', false, false),
        '%%CONTACT_STATUS%%'      => ensorlogs_contact_status_notice(),
    );
}

/**
 * Procesa el POST público del formulario de contacto.
 */
function ensorlogs_handle_contact_form(): void
{
    $nonce = sanitize_text_field(ensorlogs_contact_post_field('ensorlogs_contact_nonce'));
    if ($nonce === '' || !wp_verify_nonce($nonce, 'ensorlogs_contact_form')) {
        ensorlogs_contact_redirect('invalid');
    }

    $name     = sanitize_text_field(ensorlogs_contact_post_field('clientName'));
    $email    = sanitize_email(ensorlogs_contact_post_field('clientEmail'));
    $subject  = sanitize_text_field(ensorlogs_contact_post_field('contactSubject'));
    $message  = sanitize_textarea_field(ensorlogs_contact_post_field('contact__message'));
    $captcha  = sanitize_text_field(ensorlogs_contact_post_field('contact_captcha'));
    $website  = sanitize_text_field(ensorlogs_contact_post_field('website'));

    if ($website !== '') {
        ensorlogs_contact_redirect('spam');
    }

    if ($captcha === '' || (int) $captcha !== ENSORLOGS_CONTACT_CAPTCHA) {
        ensorlogs_contact_redirect('captcha');
    }

    if ($name === '' || $email === '' || $subject === '' || $message === '') {
        ensorlogs_contact_redirect('missing');
    }

    if (
        ensorlogs_contact_strlen($name) > ENSORLOGS_CONTACT_MAX_NAME ||
        ensorlogs_contact_strlen($subject) > ENSORLOGS_CONTACT_MAX_SUBJECT ||
        ensorlogs_contact_strlen($message) > ENSORLOGS_CONTACT_MAX_MESSAGE
    ) {
        ensorlogs_contact_redirect('too_long');
    }

    if (!is_email($email) || ensorlogs_contact_has_header_injection($email)) {
        ensorlogs_contact_redirect('bad_email');
    }

    if (ensorlogs_contact_has_header_injection($name) || ensorlogs_contact_has_header_injection($subject)) {
        ensorlogs_contact_redirect('invalid');
    }

    $recipient = sanitize_email((string) apply_filters('ensorlogs_contact_recipient', ENSORLOGS_CONTACT_RECIPIENT));
    if (!is_email($recipient)) {
        $recipient = ENSORLOGS_CONTACT_RECIPIENT;
    }

    $subject_line = '[Contacto ensorlogs.com] ' . ensorlogs_contact_strip_crlf($subject);
    $body         = sprintf(
        "Nombre: %s\nEmail: %s\nAsunto: %s\n\nMensaje:\n%s",
        $name,
        $email,
        $subject,
        $message
    );
    $headers      = array(
        'Content-Type: text/plain; charset=UTF-8',
        'From: Ensorlogs <' . $recipient . '>',
        'Reply-To: ' . ensorlogs_contact_strip_crlf($email),
    );

    $sent = wp_mail($recipient, $subject_line, $body, $headers);

    ensorlogs_contact_redirect($sent ? 'sent' : 'error');
}

/**
 * Mensaje visible tras volver del procesamiento del formulario.
 */
function ensorlogs_contact_status_notice(): string
{
    $status = '';
    if (isset($_GET['contact'])) {
        $raw_status = wp_unslash($_GET['contact']);
        $status     = is_scalar($raw_status) ? sanitize_key((string) $raw_status) : '';
    }
    if ($status === '') {
        return '';
    }

    $messages = array(
        'sent'      => array('success', __('Mensaje enviado. Gracias por escribir, te responderé lo antes posible.', 'ensorlogs')),
        'captcha'   => array('error', __('La verificación anti-spam no coincide. Revisa la suma e inténtalo de nuevo.', 'ensorlogs')),
        'missing'   => array('error', __('Completa todos los campos obligatorios antes de enviar el formulario.', 'ensorlogs')),
        'too_long'  => array('error', __('El texto es demasiado largo. Acorta el nombre, asunto o mensaje e inténtalo de nuevo.', 'ensorlogs')),
        'bad_email' => array('error', __('Indica una dirección de correo electrónico válida.', 'ensorlogs')),
        'spam'      => array('error', __('No se pudo procesar el envío. Si eres una persona, vuelve a intentarlo.', 'ensorlogs')),
        'invalid'   => array('error', __('La sesión del formulario caducó. Recarga la página e inténtalo de nuevo.', 'ensorlogs')),
        'error'     => array('error', __('El servidor no pudo enviar el mensaje. Escríbeme directamente a hello@ensorlogs.com o inténtalo más tarde.', 'ensorlogs')),
    );

    if (!isset($messages[$status])) {
        return '';
    }

    $type    = $messages[$status][0];
    $message = $messages[$status][1];
    $classes = 'success' === $type
        ? 'border-green-500/30 bg-green-500/10 text-darkGray dark:text-pastelGrey'
        : 'border-red-500/30 bg-red-500/10 text-darkGray dark:text-pastelGrey';

    return '<div class="mt-6 rounded-2xl border p-4 text-sm ' . esc_attr($classes) . '" role="status" aria-live="polite">' . esc_html($message) . '</div>';
}

/**
 * Redirige de vuelta a la página de contacto con un estado simple.
 */
function ensorlogs_contact_redirect(string $status): void
{
    $url = add_query_arg('contact', rawurlencode($status), ensorlogs_contact_page_url());
    wp_safe_redirect($url . '#contact-form');
    exit;
}

/**
 * URL canónica de la página Contacto.
 */
function ensorlogs_contact_page_url(): string
{
    $page = get_page_by_path('contact');
    if ($page instanceof WP_Post) {
        $permalink = get_permalink($page);
        if (is_string($permalink) && $permalink !== '') {
            return $permalink;
        }
    }

    return home_url('/contact/');
}

/**
 * Devuelve un campo POST como texto simple, ignorando arrays enviados por bots.
 */
function ensorlogs_contact_post_field(string $key): string
{
    if (!isset($_POST[$key])) {
        return '';
    }

    $value = wp_unslash($_POST[$key]);
    return is_scalar($value) ? (string) $value : '';
}

/**
 * Longitud UTF-8 con fallback para hostings sin mbstring.
 */
function ensorlogs_contact_strlen(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
}

/**
 * Evita inyección de cabeceras SMTP en valores usados como cabeceras.
 */
function ensorlogs_contact_has_header_injection(string $value): bool
{
    return preg_match('/[\r\n\0]|%0A|%0D|%08|%09/i', $value) === 1;
}

/**
 * Limpia caracteres no permitidos en cabeceras.
 */
function ensorlogs_contact_strip_crlf(string $value): string
{
    return str_replace(array("\r", "\n", "\0"), '', $value);
}

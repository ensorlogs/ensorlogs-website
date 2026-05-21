<?php
/**
 * Cloudflare Turnstile para el formulario de contacto.
 *
 * @package Ensorlogs
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clave de sitio (pública) configurada en el Personalizador.
 */
function ensorlogs_turnstile_site_key(): string
{
    return sanitize_text_field((string) get_theme_mod('ensor_contact_turnstile_site_key', ''));
}

/**
 * Clave secreta (solo servidor).
 */
function ensorlogs_turnstile_secret_key(): string
{
    return sanitize_text_field((string) get_theme_mod('ensor_contact_turnstile_secret_key', ''));
}

/**
 * Turnstile listo para validar envíos.
 */
function ensorlogs_turnstile_is_configured(): bool
{
    return ensorlogs_turnstile_site_key() !== '' && ensorlogs_turnstile_secret_key() !== '';
}

/**
 * HTML del widget en el formulario de contacto.
 */
function ensorlogs_turnstile_field_markup(): string
{
    if (!ensorlogs_turnstile_is_configured()) {
        if (current_user_can('manage_options')) {
            $customize = admin_url('customize.php?autofocus[section]=ensor_section_contact');
            return '<div class="sm:col-span-2 rounded-2xl border border-amber-500/40 bg-amber-500/10 p-4 text-sm text-darkGray dark:text-pastelGrey" role="status">'
                . '<p class="font-semibold mb-1">' . esc_html__('Formulario: falta configurar Turnstile', 'ensorlogs') . '</p>'
                . '<p>' . esc_html__(
                    'Ve a Apariencia → Personalizar → Ensorlogs → Formulario de contacto y pega las claves de Cloudflare Turnstile.',
                    'ensorlogs'
                ) . '</p>'
                . '<p class="mt-2"><a class="underline font-medium" href="' . esc_url($customize) . '">'
                . esc_html__('Abrir configuración', 'ensorlogs') . '</a></p></div>';
        }

        return '<div class="sm:col-span-2 rounded-2xl border border-red-500/30 bg-red-500/10 p-4 text-sm" role="alert">'
            . esc_html__('El formulario de contacto no está disponible en este momento.', 'ensorlogs')
            . '</div>';
    }

    $site_key = ensorlogs_turnstile_site_key();

    return '<div class="input-item sm:col-span-2 ensor-contact-turnstile-wrap">'
        . '<div id="ensor-contact-turnstile" class="ensor-contact-turnstile" data-sitekey="' . esc_attr($site_key) . '"></div>'
        . '<p id="captcha-hint" class="text-xs text-nobelGray dark:text-slateGray mt-2">'
        . esc_html__('Verificación anti-spam con Cloudflare Turnstile.', 'ensorlogs')
        . '</p></div>';
}

/**
 * Valida el token enviado por Turnstile contra la API de Cloudflare.
 */
function ensorlogs_turnstile_verify_submission(): bool
{
    if (!ensorlogs_turnstile_is_configured()) {
        return false;
    }

    $token = isset($_POST['cf-turnstile-response']) && is_scalar($_POST['cf-turnstile-response'])
        ? sanitize_text_field((string) wp_unslash($_POST['cf-turnstile-response']))
        : '';

    if ($token === '') {
        return false;
    }

    $remote_ip = '';
    if (isset($_SERVER['REMOTE_ADDR']) && is_string($_SERVER['REMOTE_ADDR'])) {
        $remote_ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
    }

    $response = wp_remote_post(
        'https://challenges.cloudflare.com/turnstile/v0/siteverify',
        array(
            'timeout' => 15,
            'body'    => array(
                'secret'   => ensorlogs_turnstile_secret_key(),
                'response' => $token,
                'remoteip' => $remote_ip,
            ),
        )
    );

    if (is_wp_error($response)) {
        return false;
    }

    $code = (int) wp_remote_retrieve_response_code($response);
    if ($code < 200 || $code >= 300) {
        return false;
    }

    $body = json_decode((string) wp_remote_retrieve_body($response), true);
    return is_array($body) && !empty($body['success']);
}

/**
 * Aviso en el escritorio si el formulario está activo pero sin claves.
 */
add_action(
    'admin_notices',
    static function (): void {
        if (!current_user_can('manage_options') || ensorlogs_turnstile_is_configured()) {
            return;
        }
        $customize = admin_url('customize.php?autofocus[section]=ensor_section_contact');
        echo '<div class="notice notice-warning"><p><strong>Ensorlogs:</strong> '
            . esc_html__(
                'El formulario de contacto necesita Cloudflare Turnstile (claves en Personalizar → Ensorlogs → Formulario de contacto).',
                'ensorlogs'
            )
            . ' <a href="' . esc_url($customize) . '">' . esc_html__('Configurar ahora', 'ensorlogs') . '</a></p></div>';
    }
);

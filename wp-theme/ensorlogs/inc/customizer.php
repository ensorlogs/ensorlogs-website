<?php
/**
 * Personalizador del tema: tagline, autor, redes sociales, color, OG.
 *
 * Todos los valores tienen defaults idénticos al copy hardcoded actual; si
 * el usuario no toca el Customizer, el sitio se ve igual que antes.
 *
 * @package Ensorlogs
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @return array<string, array{label:string,default:string,icon:string}>
 */
function ensorlogs_social_networks(): array
{
    return array(
        'linkedin' => array(
            'label'   => __('LinkedIn URL', 'ensorlogs'),
            'default' => 'https://www.linkedin.com/in/ensorsanchez/',
            'icon'    => 'linkedin',
        ),
        'github'   => array(
            'label'   => __('GitHub URL', 'ensorlogs'),
            'default' => '',
            'icon'    => 'github',
        ),
        'twitter'  => array(
            'label'   => __('Twitter / X URL', 'ensorlogs'),
            'default' => '',
            'icon'    => 'twitter',
        ),
        'instagram' => array(
            'label'   => __('Instagram URL', 'ensorlogs'),
            'default' => '',
            'icon'    => 'instagram',
        ),
        'youtube'  => array(
            'label'   => __('YouTube URL', 'ensorlogs'),
            'default' => '',
            'icon'    => 'youtube',
        ),
        'mastodon' => array(
            'label'   => __('Mastodon URL', 'ensorlogs'),
            'default' => '',
            'icon'    => 'mastodon',
        ),
        'bluesky'  => array(
            'label'   => __('Bluesky URL', 'ensorlogs'),
            'default' => '',
            'icon'    => 'bluesky',
        ),
    );
}

/**
 * Valor por defecto + sanitización de un campo URL del Customizer.
 */
function ensorlogs_sanitize_url($value): string
{
    return esc_url_raw((string) $value);
}

function ensorlogs_sanitize_hex_color($value): string
{
    $value = sanitize_hex_color((string) $value);
    return is_string($value) ? $value : '';
}

function ensorlogs_sanitize_theme_default_mode($value): string
{
    $value = is_string($value) ? strtolower($value) : '';
    return in_array($value, array('light', 'dark', 'system'), true) ? $value : 'system';
}

function ensorlogs_sanitize_checkbox($value): bool
{
    return (bool) $value;
}

/**
 * Helpers para leer los valores con default razonable.
 */
function ensorlogs_get_tagline(): string
{
    return (string) get_theme_mod('ensor_tagline', __('Bitácora de un geek', 'ensorlogs'));
}

function ensorlogs_get_footer_cta(): string
{
    return (string) get_theme_mod('ensor_footer_cta', __('¿Interesado en trabajar conmigo?', 'ensorlogs'));
}

function ensorlogs_get_author_name(): string
{
    return (string) get_theme_mod('ensor_author_name', 'Ensor Sánchez');
}

function ensorlogs_get_author_job(): string
{
    return (string) get_theme_mod('ensor_author_job', __('Consultor IT · Operaciones digitales', 'ensorlogs'));
}

function ensorlogs_get_default_meta_description(): string
{
    return (string) get_theme_mod(
        'ensor_default_description',
        __('Consultor IT, CRM, automatización y operaciones digitales — Ensorlogs.', 'ensorlogs')
    );
}

function ensorlogs_get_accent_color(): string
{
    $value = ensorlogs_sanitize_hex_color(get_theme_mod('ensor_accent_color', '#d9a300'));
    return $value === '' ? '#d9a300' : $value;
}

function ensorlogs_get_theme_default_mode(): string
{
    return ensorlogs_sanitize_theme_default_mode(get_theme_mod('ensor_default_mode', 'light'));
}

function ensorlogs_get_social_links(): array
{
    $out = array();
    foreach (ensorlogs_social_networks() as $slug => $info) {
        $url = (string) get_theme_mod('ensor_social_' . $slug, $info['default']);
        $url = esc_url_raw($url);
        if ($url !== '') {
            $out[ $slug ] = $url;
        }
    }
    return $out;
}

function ensorlogs_get_og_default_image_url(): string
{
    $id = (int) get_theme_mod('ensor_og_image', 0);
    if ($id > 0) {
        $u = wp_get_attachment_image_url($id, 'large');
        if (is_string($u) && $u !== '') {
            return $u;
        }
    }
    return trailingslashit(get_template_directory_uri()) . 'assets/img/Logos/ensorlogs2.png';
}

add_action(
    'customize_register',
    static function (WP_Customize_Manager $wp_customize): void {
        $wp_customize->add_panel(
            'ensorlogs',
            array(
                'title'       => __('Ensorlogs', 'ensorlogs'),
                'description' => __('Ajustes globales del tema (eslogan, autor, sociales, SEO, color).', 'ensorlogs'),
                'priority'    => 30,
            )
        );

        /* ---------- Identidad ---------- */
        $wp_customize->add_section(
            'ensor_section_identity',
            array(
                'title' => __('Identidad', 'ensorlogs'),
                'panel' => 'ensorlogs',
            )
        );

        $wp_customize->add_setting('ensor_tagline', array(
            'default'           => __('Bitácora de un geek', 'ensorlogs'),
            'transport'         => 'refresh',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control('ensor_tagline', array(
            'label'   => __('Eslogan corto (debajo del logo)', 'ensorlogs'),
            'section' => 'ensor_section_identity',
            'type'    => 'text',
        ));

        $wp_customize->add_setting('ensor_footer_cta', array(
            'default'           => __('¿Interesado en trabajar conmigo?', 'ensorlogs'),
            'transport'         => 'refresh',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control('ensor_footer_cta', array(
            'label'   => __('Frase del CTA en el pie', 'ensorlogs'),
            'section' => 'ensor_section_identity',
            'type'    => 'text',
        ));

        $wp_customize->add_setting('ensor_author_name', array(
            'default'           => 'Ensor Sánchez',
            'transport'         => 'refresh',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control('ensor_author_name', array(
            'label'       => __('Nombre del autor (JSON-LD)', 'ensorlogs'),
            'description' => __('Aparece en datos estructurados schema.org como `Person.name`.', 'ensorlogs'),
            'section'     => 'ensor_section_identity',
            'type'        => 'text',
        ));

        $wp_customize->add_setting('ensor_author_job', array(
            'default'           => __('Consultor IT · Operaciones digitales', 'ensorlogs'),
            'transport'         => 'refresh',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control('ensor_author_job', array(
            'label'   => __('Cargo del autor (JSON-LD)', 'ensorlogs'),
            'section' => 'ensor_section_identity',
            'type'    => 'text',
        ));

        /* ---------- Redes sociales ---------- */
        $wp_customize->add_section(
            'ensor_section_social',
            array(
                'title' => __('Redes sociales', 'ensorlogs'),
                'panel' => 'ensorlogs',
            )
        );

        foreach (ensorlogs_social_networks() as $slug => $info) {
            $setting_id = 'ensor_social_' . $slug;
            $wp_customize->add_setting(
                $setting_id,
                array(
                    'default'           => $info['default'],
                    'transport'         => 'refresh',
                    'sanitize_callback' => 'ensorlogs_sanitize_url',
                )
            );
            $wp_customize->add_control(
                $setting_id,
                array(
                    'label'   => $info['label'],
                    'section' => 'ensor_section_social',
                    'type'    => 'url',
                )
            );
        }

        /* ---------- SEO / Social cards ---------- */
        $wp_customize->add_section(
            'ensor_section_seo',
            array(
                'title' => __('SEO y tarjetas sociales', 'ensorlogs'),
                'panel' => 'ensorlogs',
            )
        );

        $wp_customize->add_setting('ensor_default_description', array(
            'default'           => __('Consultor IT, CRM, automatización y operaciones digitales — Ensorlogs.', 'ensorlogs'),
            'transport'         => 'refresh',
            'sanitize_callback' => 'sanitize_textarea_field',
        ));
        $wp_customize->add_control('ensor_default_description', array(
            'label'       => __('Descripción por defecto', 'ensorlogs'),
            'description' => __('Se usa cuando ni el plugin SEO ni el extracto del post aportan una buena descripción.', 'ensorlogs'),
            'section'     => 'ensor_section_seo',
            'type'        => 'textarea',
        ));

        $wp_customize->add_setting('ensor_og_image', array(
            'default'           => 0,
            'transport'         => 'refresh',
            'sanitize_callback' => 'absint',
        ));
        $wp_customize->add_control(
            new WP_Customize_Media_Control(
                $wp_customize,
                'ensor_og_image',
                array(
                    'label'     => __('Imagen Open Graph por defecto', 'ensorlogs'),
                    'section'   => 'ensor_section_seo',
                    'mime_type' => 'image',
                )
            )
        );

        /* ---------- Newsletter ---------- */
        $wp_customize->add_section(
            'ensor_section_newsletter',
            array(
                'title' => __('Newsletter (Notifícame)', 'ensorlogs'),
                'panel' => 'ensorlogs',
            )
        );

        $wp_customize->add_setting(
            'ensor_newsletter_enabled',
            array(
                'default'           => true,
                'transport'         => 'refresh',
                'sanitize_callback' => 'ensorlogs_sanitize_checkbox',
            )
        );
        $wp_customize->add_control(
            'ensor_newsletter_enabled',
            array(
                'label'   => __('Mostrar botones «Notifícame» y el modal', 'ensorlogs'),
                'section' => 'ensor_section_newsletter',
                'type'    => 'checkbox',
            )
        );

        $wp_customize->add_setting(
            'ensor_newsletter_title',
            array(
                'default'           => __('Entérate de los nuevos logs', 'ensorlogs'),
                'transport'         => 'refresh',
                'sanitize_callback' => 'sanitize_text_field',
            )
        );
        $wp_customize->add_control(
            'ensor_newsletter_title',
            array(
                'label'       => __('Título del modal', 'ensorlogs'),
                'description' => __('Deja el encabezado del plugin Mailchimp en blanco o corto para no duplicar este texto.', 'ensorlogs'),
                'section'     => 'ensor_section_newsletter',
                'type'        => 'text',
            )
        );

        $wp_customize->add_setting(
            'ensor_newsletter_description',
            array(
                'default'           => __(
                    'Suscríbete gratis a la lista y te aviso cuando publique un log nuevo: WordPress, datos, automatización y lo que vaya aprendiendo en la bitácora.',
                    'ensorlogs'
                ),
                'transport'         => 'refresh',
                'sanitize_callback' => 'sanitize_textarea_field',
            )
        );
        $wp_customize->add_control(
            'ensor_newsletter_description',
            array(
                'label'   => __('Descripción del modal', 'ensorlogs'),
                'section' => 'ensor_section_newsletter',
                'type'    => 'textarea',
            )
        );

        /* ---------- Apariencia ---------- */
        $wp_customize->add_section(
            'ensor_section_appearance',
            array(
                'title' => __('Apariencia', 'ensorlogs'),
                'panel' => 'ensorlogs',
            )
        );

        $wp_customize->add_setting('ensor_accent_color', array(
            'default'           => '#d9a300',
            'transport'         => 'refresh',
            'sanitize_callback' => 'ensorlogs_sanitize_hex_color',
        ));
        $wp_customize->add_control(
            new WP_Customize_Color_Control(
                $wp_customize,
                'ensor_accent_color',
                array(
                    'label'   => __('Color de acento', 'ensorlogs'),
                    'section' => 'ensor_section_appearance',
                )
            )
        );

        $wp_customize->add_setting('ensor_default_mode', array(
            'default'           => 'light',
            'transport'         => 'refresh',
            'sanitize_callback' => 'ensorlogs_sanitize_theme_default_mode',
        ));
        $wp_customize->add_control('ensor_default_mode', array(
            'label'   => __('Modo claro / oscuro por defecto', 'ensorlogs'),
            'section' => 'ensor_section_appearance',
            'type'    => 'select',
            'choices' => array(
                'system' => __('Preferencia del sistema', 'ensorlogs'),
                'dark'   => __('Siempre oscuro', 'ensorlogs'),
                'light'  => __('Siempre claro', 'ensorlogs'),
            ),
        ));

        /* ---------- Formulario de contacto (Turnstile) ---------- */
        $wp_customize->add_section(
            'ensor_section_contact',
            array(
                'title'       => __('Formulario de contacto', 'ensorlogs'),
                'description' => __(
                    'Crea un widget en Cloudflare Turnstile (gratis) y pega aquí las claves. Sin plugin extra.',
                    'ensorlogs'
                ),
                'panel'       => 'ensorlogs',
            )
        );

        $wp_customize->add_setting('ensor_contact_turnstile_site_key', array(
            'default'           => '',
            'transport'         => 'refresh',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control('ensor_contact_turnstile_site_key', array(
            'label'       => __('Turnstile — Site Key (pública)', 'ensorlogs'),
            'description' => __('Dashboard Cloudflare → Turnstile → tu sitio → Site Key.', 'ensorlogs'),
            'section'     => 'ensor_section_contact',
            'type'        => 'text',
        ));

        $wp_customize->add_setting('ensor_contact_turnstile_secret_key', array(
            'default'           => '',
            'transport'         => 'refresh',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control('ensor_contact_turnstile_secret_key', array(
            'label'       => __('Turnstile — Secret Key (privada)', 'ensorlogs'),
            'description' => __('Solo en el servidor. No la compartas.', 'ensorlogs'),
            'section'     => 'ensor_section_contact',
            'type'        => 'password',
        ));
    }
);

/**
 * Inyecta el color de acento como variable CSS global.
 */
add_action(
    'wp_head',
    static function (): void {
        $accent = ensorlogs_get_accent_color();
        if ($accent === '' || $accent === '#d9a300') {
            return;
        }
        echo '<style id="ensor-customizer-vars">:root{--ensor-accent:' . esc_attr($accent) . ';}</style>' . "\n";
    },
    20
);

/**
 * Reemplaza el snippet inline de modo (dark/light) si el usuario eligió
 * forzar un modo concreto. Por defecto sigue respetando preferencia del
 * sistema + localStorage.
 */
add_filter(
    'ensorlogs_default_mode_attr',
    static function ($current) {
        $mode = ensorlogs_get_theme_default_mode();
        return $mode;
    }
);

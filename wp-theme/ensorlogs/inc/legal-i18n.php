<?php
/**
 * Páginas legales ES / EN (cuerpo desde seed-html/legal).
 *
 * @package Ensorlogs
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Slugs de páginas legales soportadas.
 *
 * @return string[]
 */
function ensorlogs_legal_slugs(): array
{
    return array('aviso-legal', 'privacidad', 'cookies', 'accesibilidad');
}

/**
 * Título visible por slug e idioma.
 */
function ensorlogs_legal_title(string $slug): string
{
    $map = array(
        'aviso-legal' => array(
            'es' => 'Aviso legal y condiciones de uso',
            'en' => 'Legal notice and terms of use',
        ),
        'privacidad' => array(
            'es' => 'Política de privacidad',
            'en' => 'Privacy policy',
        ),
        'cookies' => array(
            'es' => 'Política de cookies',
            'en' => 'Cookie policy',
        ),
        'accesibilidad' => array(
            'es' => 'Declaración de accesibilidad',
            'en' => 'Accessibility statement',
        ),
    );
    $lang = function_exists('ensorlogs_current_lang') ? ensorlogs_current_lang() : 'es';
    if (isset($map[$slug][$lang])) {
        return $map[$slug][$lang];
    }
    return $slug;
}

/**
 * Lead (hero) por slug e idioma.
 */
function ensorlogs_legal_lead(string $slug): string
{
    $map = array(
        'privacidad' => array(
            'es' => 'Esta política describe qué datos personales tratamos cuando interactúas con Ensorlogs, con qué finalidad, durante cuánto tiempo y qué derechos puedes ejercer en cualquier momento.',
            'en' => 'This policy describes what personal data we process when you interact with Ensorlogs, for what purpose, how long we keep it, and what rights you can exercise at any time.',
        ),
        'cookies' => array(
            'es' => 'Aquí explicamos qué cookies (y tecnologías equivalentes) usamos en Ensorlogs, para qué sirven y cómo puedes controlarlas en cualquier momento desde la propia web o tu navegador.',
            'en' => 'Here we explain which cookies (and equivalent technologies) we use on Ensorlogs, what they are for, and how you can control them at any time from this site or your browser.',
        ),
        'aviso-legal' => array(
            'es' => 'Información obligatoria del titular del sitio, las condiciones bajo las que se ofrece el servicio y el marco legal aplicable a cualquier persona que navegue por Ensorlogs.',
            'en' => 'Mandatory information about the site owner, the terms under which the service is offered, and the legal framework for anyone browsing Ensorlogs.',
        ),
        'accesibilidad' => array(
            'es' => 'Nuestro compromiso para que cualquier persona pueda leer, escuchar y usar Ensorlogs: medidas técnicas aplicadas, contenidos pendientes y cómo avisarnos si encuentras una barrera.',
            'en' => 'Our commitment so anyone can read, hear and use Ensorlogs: technical measures in place, pending content, and how to tell us if you hit a barrier.',
        ),
    );
    $lang = function_exists('ensorlogs_current_lang') ? ensorlogs_current_lang() : 'es';
    return $map[$slug][$lang] ?? '';
}

/**
 * Etiquetas del hero por slug.
 *
 * @return string[]
 */
function ensorlogs_legal_tags(string $slug): array
{
    $lang = function_exists('ensorlogs_current_lang') ? ensorlogs_current_lang() : 'es';
    $tags = array(
        'privacidad'    => array('RGPD', 'LOPDGDD', 'LSSI-CE'),
        'cookies'       => $lang === 'en'
            ? array('Cookies', 'GDPR', 'LSSI-CE')
            : array('Cookies', 'RGPD', 'LSSI-CE'),
        'aviso-legal'   => $lang === 'en'
            ? array('Legal notice', 'Terms', 'Intellectual property')
            : array('Aviso legal', 'Condiciones', 'Propiedad intelectual'),
        'accesibilidad' => array('WCAG 2.2 AA', 'UNE-EN 301 549', 'EAA'),
    );
    return $tags[$slug] ?? array();
}

/**
 * Cuerpo HTML del artículo legal (solo secciones h2/p).
 */
function ensorlogs_legal_body_html(string $slug): string
{
    if (!in_array($slug, ensorlogs_legal_slugs(), true)) {
        return '';
    }
    $lang   = function_exists('ensorlogs_current_lang') ? ensorlogs_current_lang() : 'es';
    $suffix = $lang === 'en' ? '.en' : '';
    $file   = get_template_directory() . '/seed-html/legal/' . $slug . $suffix . '.html';
    if (!is_readable($file)) {
        $file = get_template_directory() . '/seed-html/legal/' . $slug . '.html';
    }
    if (!is_readable($file)) {
        return '';
    }
    $body = trim((string) file_get_contents($file));
    return $body;
}

/**
 * Etiquetas de navegación cruzada.
 *
 * @return array<string, string> slug => etiqueta
 */
function ensorlogs_legal_cross_labels(): array
{
    return array(
        'aviso-legal'   => function_exists('ensorlogs_t') ? ensorlogs_t('Aviso legal', 'Legal notice') : 'Aviso legal',
        'privacidad'    => function_exists('ensorlogs_t') ? ensorlogs_t('Privacidad', 'Privacy') : 'Privacidad',
        'cookies'       => function_exists('ensorlogs_t') ? ensorlogs_t('Cookies', 'Cookies') : 'Cookies',
        'accesibilidad' => function_exists('ensorlogs_t') ? ensorlogs_t('Accesibilidad', 'Accessibility') : 'Accesibilidad',
    );
}

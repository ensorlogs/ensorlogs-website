<?php
/**
 * "Stacks" (categorías técnicas) usadas en Logs y Proyectos.
 *
 * Catálogo único de stacks soportados con:
 *   - Label legible
 *   - Icono SVG inline (sin dependencia externa, sin requests extra)
 *   - Color de acento opcional para badges (CSS)
 *
 * Se usa en los badges arriba del Log, en los chips del listado y en los
 * filtros del blog / proyectos.
 *
 * @package Ensorlogs
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @return array<string, array{label:string,color:string,icon:string}>
 */
function ensorlogs_stack_catalog(): array
{
    static $catalog = null;
    if ($catalog !== null) {
        return $catalog;
    }

    $svg = static function (string $path, string $viewbox = '0 0 24 24'): string {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="' . $viewbox . '" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
    };

    $catalog = array(
        'wordpress'  => array(
            'label' => 'WordPress',
            'color' => '#21759b',
            'icon'  => $svg('<circle cx="12" cy="12" r="9"/><path d="M3.2 9.5h7.6M5.5 14.5l3.5-9M14.7 14.5l3.5-9M16 6.5l-2 8M9 6.5l2 8"/>'),
        ),
        'linux'      => array(
            'label' => 'Linux',
            'color' => '#3b3b3b',
            'icon'  => $svg('<path d="M12 3c-2.2 0-3.5 1.7-3.5 4.5 0 1.8.5 3.3 1.4 4.7-1.5 1.2-3.2 3.4-3.4 5.6-.1 1 .3 1.7 1 2 1 .4 2.4-.5 3.4-1.5.5-.5 1-.5 1.6-.5s1 .1 1.6.5c.9 1 2.3 1.9 3.4 1.5.7-.3 1.1-1 1-2-.2-2.2-1.9-4.4-3.4-5.6.9-1.4 1.4-2.9 1.4-4.7C15.5 4.7 14.2 3 12 3z"/><circle cx="10.5" cy="8" r=".7" fill="currentColor"/><circle cx="13.5" cy="8" r=".7" fill="currentColor"/>'),
        ),
        'ia'         => array(
            'label' => 'IA',
            'color' => '#7c3aed',
            'icon'  => $svg('<rect x="5" y="6" width="14" height="12" rx="3"/><circle cx="9.5" cy="12" r="1.1" fill="currentColor"/><circle cx="14.5" cy="12" r="1.1" fill="currentColor"/><path d="M12 3v3M8 21v-3M16 21v-3"/>'),
        ),
        'database'   => array(
            'label' => 'Database',
            'color' => '#0ea5e9',
            'icon'  => $svg('<ellipse cx="12" cy="6" rx="7" ry="3"/><path d="M5 6v6c0 1.7 3.1 3 7 3s7-1.3 7-3V6M5 12v6c0 1.7 3.1 3 7 3s7-1.3 7-3v-6"/>'),
        ),
        'crm'        => array(
            'label' => 'CRM',
            'color' => '#e11d48',
            'icon'  => $svg('<circle cx="9" cy="9" r="3"/><path d="M3 20c.6-3 3-5 6-5s5.4 2 6 5M15 11h6M15 15h4"/>'),
        ),
        'marketing'  => array(
            'label' => 'Marketing',
            'color' => '#f97316',
            'icon'  => $svg('<path d="M3 11l14-5v12L3 13zM3 11v2M7 12.4v4.6c0 1 .8 2 2 2h.5c1 0 1.5-1 1.5-2v-4"/>'),
        ),
        'python'     => array(
            'label' => 'Python',
            'color' => '#facc15',
            'icon'  => $svg('<path d="M9 4h6a3 3 0 0 1 3 3v3H9V8H6a3 3 0 0 0-3 3v3a3 3 0 0 0 3 3h3v-2"/><path d="M15 20H9a3 3 0 0 1-3-3v-3h9v2h3a3 3 0 0 0 3-3v-3a3 3 0 0 0-3-3h-3v2"/><circle cx="9" cy="6.5" r=".6" fill="currentColor"/><circle cx="15" cy="17.5" r=".6" fill="currentColor"/>'),
        ),
        'google'     => array(
            'label' => 'Google',
            'color' => '#4285f4',
            'icon'  => $svg('<path d="M21 12.2c0-.7-.1-1.3-.2-1.9H12v3.7h5.1c-.2 1.2-.9 2.2-2 2.9v2.4h3.2c1.9-1.7 3-4.3 3-7.1z"/><path d="M12 21c2.7 0 5-.9 6.6-2.4l-3.2-2.5c-.9.6-2 1-3.4 1-2.6 0-4.9-1.8-5.7-4.2H3.1v2.6C4.7 18.7 8 21 12 21z"/><path d="M6.3 12.8c-.2-.6-.3-1.2-.3-1.8s.1-1.2.3-1.8V6.6H3.1A9 9 0 0 0 2 11c0 1.5.4 2.9 1.1 4.1l3.2-2.3z"/><path d="M12 6.6c1.5 0 2.8.5 3.8 1.5l2.8-2.8C17 3.9 14.7 3 12 3 8 3 4.7 5.3 3.1 8.4l3.2 2.6C7.1 8.4 9.4 6.6 12 6.6z"/>'),
        ),
        'servidores' => array(
            'label' => 'Servidores',
            'color' => '#10b981',
            'icon'  => $svg('<rect x="4" y="4" width="16" height="6" rx="2"/><rect x="4" y="14" width="16" height="6" rx="2"/><circle cx="8" cy="7" r=".7" fill="currentColor"/><circle cx="8" cy="17" r=".7" fill="currentColor"/><path d="M14 7h3M14 17h3"/>'),
        ),
        'it'         => array(
            'label' => 'IT',
            'color' => '#6366f1',
            'icon'  => $svg('<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M9 20h6M12 16v4"/>'),
        ),
        'windows'    => array(
            'label' => 'Windows',
            'color' => '#0078d4',
            'icon'  => $svg('<path d="M3 7l8-1v6H3zM11 6l10-1.5V12H11zM3 13h8v5l-8-1zM11 13h10v6.5L11 18z"/>'),
        ),
        'excel'      => array(
            'label' => 'Excel',
            'color' => '#217346',
            'icon'  => $svg('<path d="M4 4h10l6 6v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/><path d="M14 4v6h6M8 11h8M8 15h5"/>'),
        ),
    );
    return $catalog;
}

function ensorlogs_stack_get(string $slug): ?array
{
    $slug = sanitize_key($slug);
    $catalog = ensorlogs_stack_catalog();
    return $catalog[$slug] ?? null;
}

function ensorlogs_stack_label(string $slug): string
{
    $entry = ensorlogs_stack_get($slug);
    if ($entry !== null) {
        return $entry['label'];
    }
    if (function_exists('ensorlogs_tema_label')) {
        return ensorlogs_tema_label($slug);
    }
    return ucfirst($slug);
}

/**
 * Devuelve el HTML del icono SVG inline para un stack.
 * Si el slug no está en el catálogo, devuelve cadena vacía.
 */
function ensorlogs_stack_icon(string $slug): string
{
    $entry = ensorlogs_stack_get($slug);
    if ($entry === null) {
        return '';
    }
    return (string) $entry['icon'];
}

/**
 * Render badge (icon + label) enlazable al filtro del blog/proyectos.
 *
 * @param string $slug  Slug del stack.
 * @param string $base  Base URL del filtro (ej.: '/blog/?tema=' o '/projects/?tema=').
 * @param string $extra Clases extra para el badge.
 */
function ensorlogs_stack_badge_html(string $slug, string $base = '', string $extra = ''): string
{
    $slug = sanitize_key($slug);
    $entry = ensorlogs_stack_get($slug);
    if ($entry === null) {
        $label = function_exists('ensorlogs_tema_label') ? ensorlogs_tema_label($slug) : ucfirst($slug);
        $icon = '';
        $color = '';
    } else {
        $label = $entry['label'];
        $icon  = (string) $entry['icon'];
        $color = (string) $entry['color'];
    }
    $href = $base === ''
        ? esc_url(trailingslashit(home_url('/')) . 'blog/?tema=' . rawurlencode($slug))
        : esc_url($base . rawurlencode($slug));

    $extra_attr = $extra !== '' ? ' ' . esc_attr($extra) : '';
    $style = $color !== '' ? ' style="--ensor-stack-color:' . esc_attr($color) . '"' : '';
    $out  = '<a href="' . $href . '" class="ensor-reader-stack' . $extra_attr . '"' . $style . ' rel="tag">';
    if ($icon !== '') {
        $out .= '<span class="ensor-reader-stack__icon" aria-hidden="true">' . $icon . '</span>';
    }
    $out .= '<span class="ensor-reader-stack__label">' . esc_html($label) . '</span>';
    $out .= '</a>';
    return $out;
}

/**
 * Compatibilidad: alias semántico para los listados de blog/proyectos.
 */
function ensorlogs_stack_label_filter(string $slug): string
{
    return ensorlogs_stack_label($slug);
}

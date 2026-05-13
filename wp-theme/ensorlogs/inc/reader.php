<?php
/**
 * Reader UX server-side: ids automáticos en H2/H3 y detección de audiencias.
 *
 * Las funciones más pesadas (TOC y filtro) las hace el JS en cliente, pero
 * dejamos los anchors generados en server para evitar saltos de layout y
 * para que los lectores que no ejecuten JS tengan IDs estables.
 *
 * @package Ensorlogs
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Inyecta `id="slug"` a todos los <h2>/<h3> que no lo tengan, dentro del
 * contenido del Log o del proyecto. Usa DOMDocument cuando puede, regex
 * como fallback.
 */
function ensorlogs_inject_heading_ids(string $html): string
{
    if ($html === '' || strpos($html, '<h2') === false && strpos($html, '<h3') === false) {
        return $html;
    }

    if (class_exists('DOMDocument')) {
        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        // Forzamos UTF-8; envolvemos en root para preservar fragmentos.
        $loaded = $doc->loadHTML(
            '<?xml encoding="UTF-8"?><div id="ensorroot">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        if (!$loaded) {
            return ensorlogs_inject_heading_ids_regex($html);
        }
        $xpath = new DOMXPath($doc);
        $headings = $xpath->query('//h2|//h3');
        if (!$headings) {
            return $html;
        }
        $seen = array();
        foreach ($headings as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }
            if ($node->hasAttribute('id') && $node->getAttribute('id') !== '') {
                continue;
            }
            $text = $node->textContent;
            $slug = sanitize_title($text);
            if ($slug === '') {
                continue;
            }
            $base = $slug;
            $n = 1;
            while (isset($seen[$slug])) {
                $n++;
                $slug = $base . '-' . $n;
            }
            $seen[$slug] = true;
            $node->setAttribute('id', $slug);
        }
        $out = '';
        $root = $doc->getElementById('ensorroot');
        if ($root) {
            foreach ($root->childNodes as $child) {
                $out .= $doc->saveHTML($child);
            }
            return $out;
        }
    }
    return ensorlogs_inject_heading_ids_regex($html);
}

function ensorlogs_inject_heading_ids_regex(string $html): string
{
    $seen = array();
    return (string) preg_replace_callback(
        '#<(h[23])([^>]*)>(.*?)</\1>#is',
        static function (array $m) use (&$seen): string {
            $tag = $m[1];
            $attrs = $m[2];
            $inner = $m[3];
            if (preg_match('/\sid=("|\')[^"\']+\1/i', $attrs)) {
                return $m[0];
            }
            $text = wp_strip_all_tags($inner);
            $slug = sanitize_title($text);
            if ($slug === '') {
                return $m[0];
            }
            $base = $slug;
            $n = 1;
            while (isset($seen[$slug])) {
                $n++;
                $slug = $base . '-' . $n;
            }
            $seen[$slug] = true;
            return '<' . $tag . ' id="' . esc_attr($slug) . '"' . $attrs . '>' . $inner . '</' . $tag . '>';
        },
        $html
    );
}

/**
 * Solo aplica al contenido principal de Logs (artículos) en singular.
 */
add_filter(
    'the_content',
    static function (string $content): string {
        if (!is_singular('ensor_article')) {
            return $content;
        }
        if (is_feed() || is_admin()) {
            return $content;
        }
        return ensorlogs_inject_heading_ids($content);
    },
    25
);

/**
 * Devuelve un array de audiencias presentes en el HTML del Log.
 * Detecta atributos `data-aud="..."` en cualquier elemento.
 *
 * @return array<int, string>
 */
function ensorlogs_detect_audiences(string $html): array
{
    if ($html === '' || strpos($html, 'data-aud') === false) {
        return array();
    }
    preg_match_all('/data-aud=(["\\\'])\\s*([^"\\\']+)\\s*\1/i', $html, $matches);
    if (empty($matches[2])) {
        return array();
    }
    $set = array();
    foreach ($matches[2] as $value) {
        $parts = preg_split('/[\\s,]+/', strtolower($value)) ?: array();
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p !== '') {
                $set[$p] = true;
            }
        }
    }
    return array_keys($set);
}

/**
 * Devuelve la lista canónica de audiencias con label legible.
 * Si añades una audiencia nueva en CSS / JS, añádela aquí también.
 *
 * @return array<string, string>
 */
function ensorlogs_audience_labels(): array
{
    return array(
        'student'      => __('Para estudiantes', 'ensorlogs'),
        'professional' => __('Para profesionales', 'ensorlogs'),
        'teacher'      => __('Para profesores', 'ensorlogs'),
        'data'         => __('Datos y referencias', 'ensorlogs'),
        'beginner'     => __('Para empezar', 'ensorlogs'),
        'advanced'     => __('Avanzado', 'ensorlogs'),
        'client'       => __('Para clientes', 'ensorlogs'),
    );
}

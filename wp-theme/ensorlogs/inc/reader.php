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
 * Inyecta al final del contenido del log las secciones pedagógicas guardadas
 * desde las cajas meta del backend (Contexto, Datos, Como estudiante, …).
 * No duplica una sección si el editor de bloques ya emite una con el mismo
 * `data-aud`. Se ejecuta ANTES de la inyección automática de ids en H2/H3
 * para que las secciones nuevas también reciban anchors.
 *
 * @return string contenido con secciones inyectadas.
 */
function ensorlogs_append_section_metas_to_content(string $content): string
{
    if (!function_exists('ensorlogs_article_sections')) {
        return $content;
    }
    $post_id = (int) get_the_ID();
    if ($post_id <= 0) {
        return $content;
    }
    $sections = ensorlogs_article_sections();
    if (!$sections) {
        return $content;
    }
    $appended = '';
    foreach ($sections as $sec_key => $sec_def) {
        $value = (string) get_post_meta($post_id, '_ensor_section_' . $sec_key, true);
        if (trim(wp_strip_all_tags($value)) === '') {
            continue;
        }
        // Si el contenido ya trae una sección con esa audiencia, respetamos
        // lo que el editor de bloques generó (no duplicamos).
        $aud_re = preg_quote($sec_def['aud'], '/');
        if (preg_match('/data-aud=["\']\\s*[^"\']*\\b' . $aud_re . '\\b/i', $content)) {
            continue;
        }
        $label  = $sec_def['label'];
        $aud    = $sec_def['aud'];
        $body   = trim($value);
        // Aseguramos que la primera línea sin tags arranque con un <h2>.
        if (stripos($body, '<h2') === false) {
            $heading_id = sanitize_title('como-' . $aud);
            // Encabezados especiales: context/data conservan su nombre canónico.
            if ($sec_key === 'context') {
                $heading_id = 'contexto';
            } elseif ($sec_key === 'data') {
                $heading_id = 'datos';
            }
            $body = '<h2 id="' . esc_attr($heading_id) . '">' . esc_html($label) . '</h2>' . $body;
        }
        $appended .= "\n<section class=\"ensor-aud-section\" data-aud=\"" . esc_attr($aud) . "\">\n"
                  . $body
                  . "\n</section>\n";
    }
    return $appended === '' ? $content : ($content . $appended);
}

/**
 * Solo aplica al contenido principal de Logs (artículos) en singular.
 * Orden:
 *   - prioridad 22 → inyectar secciones desde meta
 *   - prioridad 25 → inyectar ids automáticos en H2/H3
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
        return ensorlogs_append_section_metas_to_content($content);
    },
    22
);
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
 * Devuelve las audiencias de un log que vienen *de las cajas meta del backend*
 * (no del contenido del editor de bloques). Si la caja meta tiene texto
 * útil (no solo HTML vacío), su audiencia entra en el filtro de navegación.
 *
 * @param int $post_id ID del log.
 * @return array<int, string>
 */
function ensorlogs_article_section_audiences_with_value(int $post_id): array
{
    if ($post_id <= 0 || !function_exists('ensorlogs_article_sections')) {
        return array();
    }
    $auds = array();
    foreach (ensorlogs_article_sections() as $sec_key => $sec_def) {
        $v = (string) get_post_meta($post_id, '_ensor_section_' . $sec_key, true);
        if (trim(wp_strip_all_tags($v)) !== '') {
            $auds[] = $sec_def['aud'];
        }
    }
    return $auds;
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

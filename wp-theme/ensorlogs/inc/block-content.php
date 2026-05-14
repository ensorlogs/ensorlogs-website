<?php
/**
 * Convierte HTML migrado (sin marcadores de bloque) en contenido Gutenberg
 * por secciones, y plantillas por defecto para nuevos logs / proyectos.
 *
 * @package Ensorlogs
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ¿El contenido ya está en formato bloques?
 */
function ensorlogs_post_content_is_block_markup(string $content): bool
{
    return strpos(trim($content), '<!-- wp:') !== false;
}

/**
 * Serializa un único bloque core/html.
 */
function ensorlogs_serialize_core_html_block(string $html): string
{
    $html = trim($html);
    if ($html === '') {
        return '';
    }
    if (!function_exists('serialize_blocks')) {
        return $html;
    }
    $block = array(
        'blockName'    => 'core/html',
        'attrs'        => array(),
        'innerBlocks'  => array(),
        'innerHTML'    => $html,
        'innerContent' => array($html),
    );
    return (string) serialize_blocks(array($block));
}

/**
 * Quita la envoltura externa `main-content` si existe (HTML de proyectos migrados).
 */
function ensorlogs_blockify_strip_outer_main_content(string $html): string
{
    $html = trim($html);
    if ($html === '' || stripos($html, 'main-content') === false) {
        return $html;
    }
    if (!preg_match('/<div[^>]*class=("|\')[^"\']*main-content[^"\']*\1[^>]*>/i', $html, $m, PREG_OFFSET_CAPTURE)) {
        return $html;
    }
    $open = (int) $m[0][1];
    $inner = ensorlogs_extract_inner_after_div($html, $open);
    return trim($inner) !== '' ? trim($inner) : $html;
}

/**
 * Trocea HTML de proyecto: hijos directos de `.container.space-y-12`.
 *
 * @return list<string>
 */
function ensorlogs_blockify_project_container_chunks(string $html): array
{
    $html = trim($html);
    if ($html === '' || !class_exists('DOMDocument')) {
        return array($html);
    }
    libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    $wrapped = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body><div id="ensor-chunk-root">' . $html . '</div></body></html>';
    $loaded = $doc->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    if (!$loaded) {
        return array($html);
    }
    $xpath   = new DOMXPath($doc);
    $query   = "//*[@id='ensor-chunk-root']//div[contains(concat(' ', normalize-space(@class), ' '), ' container ') and contains(concat(' ', normalize-space(@class), ' '), ' space-y-12 ')]";
    $nodes   = $xpath->query($query);
    $chunks  = array();
    if ($nodes instanceof DOMNodeList && $nodes->length > 0) {
        $container = $nodes->item(0);
        if ($container instanceof DOMElement) {
            foreach ($container->childNodes as $child) {
                if ($child->nodeType === XML_ELEMENT_NODE) {
                    $chunks[] = trim((string) $doc->saveHTML($child));
                }
            }
        }
    }
    return $chunks !== array() ? $chunks : array($html);
}

/**
 * Trocea por encabezados h2 de primer nivel (logs con muchas secciones).
 *
 * @return list<string>
 */
function ensorlogs_blockify_split_by_h2(string $html): array
{
    $html = trim($html);
    if ($html === '' || !preg_match('/<h2\b/i', $html)) {
        return array($html);
    }
    $parts = preg_split('/(?=<h2\b)/i', $html, -1, PREG_SPLIT_NO_EMPTY);
    if (!is_array($parts) || $parts === array()) {
        return array($html);
    }
    $out = array();
    foreach ($parts as $p) {
        $p = trim((string) $p);
        if ($p !== '') {
            $out[] = $p;
        }
    }
    return $out !== array() ? $out : array($html);
}

/**
 * Convierte HTML plano migrado en bloques Gutenberg (grupos + HTML por sección).
 *
 * @param string $html    Cuerpo ya extraído / relinkado.
 * @param string $context `article` | `project`.
 */
function ensorlogs_blockify_html_for_editor(string $html, string $context): string
{
    $html = trim($html);
    if ($html === '' || ensorlogs_post_content_is_block_markup($html)) {
        return $html;
    }
    if (!function_exists('serialize_blocks')) {
        return $html;
    }

    $chunks = array();
    if ($context === 'project') {
        $stripped = ensorlogs_blockify_strip_outer_main_content($html);
        $chunks   = ensorlogs_blockify_project_container_chunks($stripped);
        if (count($chunks) === 1 && $chunks[0] === $stripped) {
            $chunks = ensorlogs_blockify_split_by_h2($stripped);
        }
    } else {
        $chunks = ensorlogs_blockify_split_by_h2($html);
    }

    if ($chunks === array()) {
        return ensorlogs_serialize_core_html_block($html);
    }

    $block_objs = array();
    foreach ($chunks as $chunk) {
        $chunk = trim((string) $chunk);
        if ($chunk === '') {
            continue;
        }
        $block_objs[] = array(
            'blockName'    => 'core/html',
            'attrs'        => array(),
            'innerBlocks'  => array(),
            'innerHTML'    => $chunk,
            'innerContent' => array($chunk),
        );
    }
    if ($block_objs === array()) {
        return ensorlogs_serialize_core_html_block($html);
    }
    $out = trim((string) serialize_blocks($block_objs));
    return $out !== '' ? $out : ensorlogs_serialize_core_html_block($html);
}

/**
 * Plantilla de bloques por defecto al crear un log o proyecto nuevo.
 *
 * Tres bloques de párrafo separados (puedes sustituirlos por columnas,
 * imágenes, vídeo, encabezados, etc. con el botón +).
 *
 * @return list<array{0:string,1:array<string,mixed>,2?:list<array>}>
 */
function ensorlogs_cpt_default_block_template(string $post_type): array
{
    $is_project = $post_type === 'ensor_project';
    if ($is_project) {
        $p1 = __('Sección 1 — Resumen del caso: cliente, problema, stack y CTA. Sustituye este párrafo o usa + para imágenes, vídeo, columnas o listas.', 'ensorlogs');
        $p2 = __('Sección 2 — Contexto y alcance: antecedentes, restricciones y entregables.', 'ensorlogs');
        $p3 = __('Sección 3 — Solución y resultados: implementación, capturas, métricas y aprendizajes.', 'ensorlogs');
    } else {
        $p1 = __('Sección 1 — Entrada: gancho y promesa del artículo. La imagen destacada va en el panel lateral.', 'ensorlogs');
        $p2 = __('Sección 2 — Desarrollo: argumento, figuras, vídeos embebidos, citas.', 'ensorlogs');
        $p3 = __('Sección 3 — Cierre: conclusiones, recursos y siguiente paso.', 'ensorlogs');
    }

    return array(
        array('core/paragraph', array('placeholder' => $p1)),
        array('core/separator', array()),
        array('core/paragraph', array('placeholder' => $p2)),
        array('core/separator', array()),
        array('core/paragraph', array('placeholder' => $p3)),
    );
}

/**
 * Convierte en bloques todos los logs/proyectos que aún están en HTML plano.
 *
 * @return int Número de entradas actualizadas.
 */
function ensorlogs_blockify_all_plain_cpts(): int
{
    $q = new WP_Query(
        array(
            'post_type'              => array('ensor_article', 'ensor_project'),
            'post_status'            => 'any',
            'posts_per_page'         => -1,
            'fields'                 => 'ids',
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        )
    );
    $updated = 0;
    foreach ($q->posts as $pid) {
        $pid = (int) $pid;
        $post = get_post($pid);
        if (!$post instanceof WP_Post) {
            continue;
        }
        $content = (string) $post->post_content;
        if ($content === '' || ensorlogs_post_content_is_block_markup($content)) {
            continue;
        }
        $ctx  = $post->post_type === 'ensor_project' ? 'project' : 'article';
        $next = ensorlogs_blockify_html_for_editor($content, $ctx);
        if ($next === '' || $next === $content) {
            continue;
        }
        wp_update_post(
            array(
                'ID'           => $pid,
                'post_content' => wp_slash($next),
            )
        );
        ++$updated;
    }
    wp_reset_postdata();
    if ($updated > 0 && function_exists('ensorlogs_flush_listing_cache')) {
        ensorlogs_flush_listing_cache();
    }
    return $updated;
}

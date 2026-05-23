<?php
/**
 * Traducción del contenido de proyectos (CPT) en rutas /en/.
 *
 * @package Ensorlogs
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Pares ES → EN cargados desde data/projects-en-translations.json.
 *
 * @return array<int, array{0: string, 1: string}>
 */
function ensorlogs_project_en_pairs(): array
{
    static $pairs = null;
    if ($pairs !== null) {
        return $pairs;
    }

    $path = get_template_directory() . '/data/projects-en-translations.json';
    if (!is_readable($path)) {
        $pairs = array();
        return $pairs;
    }

    $raw = json_decode((string) file_get_contents($path), true);
    if (!is_array($raw)) {
        $pairs = array();
        return $pairs;
    }

    $pairs = array();
    foreach ($raw as $es => $en) {
        if (!is_string($es) || !is_string($en) || strlen($es) < 4) {
            continue;
        }
        $pairs[] = array($es, $en);
    }

    usort(
        $pairs,
        static function (array $a, array $b): int {
            return strlen($b[0]) <=> strlen($a[0]);
        }
    );

    return $pairs;
}

/**
 * Sustituye cadenas españolas del HTML importado por su equivalente EN.
 */
function ensorlogs_translate_project_html(string $html): string
{
    if ($html === '') {
        return $html;
    }

    foreach (ensorlogs_project_en_pairs() as $pair) {
        $html = str_replace($pair[0], $pair[1], $html);
    }

    return $html;
}

add_filter(
    'the_content',
    static function ($content) {
        if (
            !is_string($content)
            || $content === ''
            || !is_singular('ensor_project')
            || !function_exists('ensorlogs_current_lang')
            || ensorlogs_current_lang() !== 'en'
        ) {
            return $content;
        }

        return ensorlogs_translate_project_html($content);
    },
    4
);

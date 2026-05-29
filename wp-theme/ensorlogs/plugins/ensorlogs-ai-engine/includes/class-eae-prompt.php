<?php
/**
 * Prompt builder and HTML guardrails.
 */

if (!defined('ABSPATH')) {
    exit;
}

final class EAE_Prompt
{
    /**
     * Prompt único para pegar en ChatGPT (Plus): manual editorial + brief del log.
     *
     * @param array<string,mixed> $input
     */
    public static function build_chatgpt_prompt(array $input): string
    {
        $manual     = function_exists('eae_get_editorial_manual') ? eae_get_editorial_manual() : '';
        $brief      = self::build_brief_block($input);
        $experience = trim((string) ($input['experience'] ?? ''));

        $experience_rule = '';
        if ($experience !== '') {
            $experience_rule = "\n\n--- EXPERIENCIA PERSONAL DE ENSOR (obligatoria) ---\n\n"
                . "La experiencia personal proporcionada por Ensor en el brief debe utilizarse obligatoriamente dentro de:\n"
                . "- Algunas Palabras\n"
                . "- Reflexión EnsorLogs\n\n"
                . "No ignorar esta información.\n"
                . "No resumirla en una sola frase.\n"
                . "Debe integrarse de forma natural dentro del contenido.\n";
        }

        return trim(
            "Amplifica la voz de Ensor y escribe un LOG completo para EnsorLogs siguiendo el manual y el brief.\n"
            . "El protagonista es Ensor, no un redactor externo.\n\n"
            . $manual
            . $experience_rule
            . "\n\n--- BRIEF DE ESTE LOG ---\n\n"
            . "Si el brief trae errores ortográficos evidentes o nombres técnicos mal escritos, corrígelos al redactar "
            . "sin cambiar la intención de Ensor.\n\n"
            . $brief
            . "\n\n--- SALIDA ---\n\n"
            . "Toda la respuesta debe ser un único HTML continuo.\n"
            . "No dividir el contenido.\n"
            . "No usar markdown.\n"
            . "No usar bloques de código.\n"
            . "No añadir explicaciones fuera del HTML.\n"
            . "La respuesta debe poder copiarse directamente y pegarse en WordPress.\n\n"
            . "Incluye las 7 secciones del manual en orden: Algunas Palabras, Datos Reales, "
            . "¿Eres estudiante?, ¿Eres profesor?, Como profesional, Reflexión EnsorLogs y LOG QUESTIONS (5 ítems).\n"
            . "No incluyas sección ensor-quiz ni data-quiz."
        );
    }

    /**
     * Bloque de brief reutilizable.
     *
     * @param array<string,mixed> $input
     */
    public static function build_brief_block(array $input): string
    {
        $topic      = trim((string) ($input['topic'] ?? ''));
        $context    = trim((string) ($input['context'] ?? ''));
        $experience = trim((string) ($input['experience'] ?? ''));
        $teach      = trim((string) ($input['teach'] ?? ''));
        $stacks     = $input['stacks'] ?? array();
        if (!is_array($stacks)) {
            $stacks = array();
        }
        $stack_labels = array();
        foreach ($stacks as $slug) {
            $slug = sanitize_title((string) $slug);
            if ($slug === '') {
                continue;
            }
            $term = get_term_by('slug', $slug, 'ensor_tema');
            $stack_labels[] = $term instanceof WP_Term ? $term->name : $slug;
        }
        $log_type = $stack_labels ? implode(', ', $stack_labels) : 'General';
        $level    = 'Básico';
        $audience = 'Estudiantes';

        $lines = array('Tema: ' . $topic);
        if ($context !== '') {
            $lines[] = 'Contexto/enfoque: ' . $context;
        }
        if ($experience !== '') {
            $lines[] = 'Experiencia personal: ' . $experience;
        }
        if ($teach !== '') {
            $lines[] = 'Qué enseñar: ' . $teach;
        }
        $lines[] = 'Stacks / tipo de log: ' . $log_type;
        $lines[] = 'Nivel técnico: ' . $level;
        $lines[] = 'Público principal: ' . $audience;

        return implode("\n", $lines);
    }

    public static function sanitize_generated_html(string $html): string
    {
        $allowed = array(
            'section' => array(
                'class'     => true,
                'data-aud'  => true,
                'data-quiz' => true,
            ),
            'h2'         => array('id' => true),
            'p'          => array(),
            'strong'     => array(),
            'em'         => array(),
            'mark'       => array(),
            'a'          => array('href' => true, 'target' => true, 'rel' => true),
            'ul'         => array(),
            'ol'         => array(),
            'li'         => array(),
            'blockquote' => array(),
            'code'       => array(),
            'pre'        => array(),
        );

        $clean = wp_kses($html, $allowed);
        $clean = self::enforce_required_sections($clean);
        return trim($clean);
    }

    /**
     * Quita la sección quiz del HTML del editor y devuelve texto para meta _ensor_quiz.
     *
     * @return array{html:string,quiz_text:string}
     */
    public static function extract_quiz_for_meta(string $html): array
    {
        $quiz_text = '';
        if (preg_match(
            '/<section[^>]*class="[^"]*ensor-quiz[^"]*"[^>]*data-quiz=(["\'])(.*?)\1[^>]*>\s*<\/section>/is',
            $html,
            $m
        )) {
            $json_raw = html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $decoded  = json_decode($json_raw, true);
            if (is_array($decoded) && !empty($decoded['questions']) && is_array($decoded['questions'])) {
                $quiz_text = self::quiz_json_to_textarea($decoded['questions']);
            }
            $html = str_replace($m[0], '', $html);
        }
        return array(
            'html'      => trim($html),
            'quiz_text' => $quiz_text,
        );
    }

    /**
     * @param list<array<string,mixed>> $questions
     */
    public static function quiz_json_to_textarea(array $questions): string
    {
        $letters = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H');
        $blocks  = array();
        foreach ($questions as $item) {
            if (!is_array($item)) {
                continue;
            }
            $q       = trim((string) ($item['q'] ?? ''));
            $options = $item['options'] ?? array();
            if ($q === '' || !is_array($options) || count($options) < 2) {
                continue;
            }
            $correct = isset($item['correct']) ? (int) $item['correct'] : 0;
            $lines   = array('Q: ' . $q);
            foreach ($options as $idx => $opt) {
                $letter = $letters[$idx] ?? chr(65 + (int) $idx);
                $suffix = ((int) $idx === $correct) ? ' *' : '';
                $lines[] = $letter . ': ' . trim((string) $opt) . $suffix;
            }
            $hint = trim((string) ($item['hint'] ?? ''));
            if ($hint !== '') {
                $lines[] = 'P: ' . $hint;
            }
            $explanation = trim((string) ($item['explanation'] ?? ''));
            if ($explanation !== '') {
                $lines[] = 'E: ' . $explanation;
            }
            $blocks[] = implode("\n", $lines);
        }
        return implode("\n---\n", $blocks);
    }

    private static function enforce_required_sections(string $html): string
    {
        $required = array(
            'context'      => 'Algunas Palabras',
            'data'         => 'Datos Reales',
            'student'      => '¿Eres estudiante?',
            'teacher'      => '¿Eres profesor?',
            'professional' => 'Como profesional',
        );
        foreach ($required as $aud => $title) {
            $needle = 'data-aud="' . $aud . '"';
            if (stripos($html, $needle) !== false) {
                continue;
            }
            $html .= "\n" . '<section class="ensor-aud-section" data-aud="' . esc_attr($aud) . '">'
                . '<h2>' . esc_html($title) . '</h2>'
                . '<p></p>'
                . '</section>';
        }
        return $html;
    }
}

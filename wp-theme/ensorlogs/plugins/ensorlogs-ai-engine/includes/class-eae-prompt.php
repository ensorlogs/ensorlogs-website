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
        $manual = function_exists('eae_get_editorial_manual') ? eae_get_editorial_manual() : '';
        $brief  = self::build_brief_block($input);

        return trim(
            "=== ENSORLOGS — PROMPT PARA CHATGPT ===\n\n"
            . "Copia todo este mensaje en un chat nuevo de ChatGPT (con tu cuenta Plus).\n"
            . "Cuando ChatGPT responda, copia solo el HTML y pégalo en WordPress con «Insertar HTML en el editor».\n\n"
            . "--- MANUAL EDITORIAL (obligatorio) ---\n\n"
            . $manual
            . "\n\n--- BRIEF DE ESTE LOG ---\n\n"
            . $brief
            . "\n\n--- INSTRUCCIÓN DE SALIDA ---\n\n"
            . "Genera el LOG completo para EnsorLogs siguiendo el manual editorial.\n"
            . "Responde ÚNICAMENTE con HTML válido del log, sin markdown, sin explicaciones fuera del HTML "
            . "y sin bloques de código.\n"
            . "Incluye todas las secciones obligatorias (Algunas Palabras, Datos Reales, audiencias, "
            . "Reflexión EnsorLogs, LOG QUESTIONS con 5 ítems y LOG CHECK en data-quiz con 3 preguntas)."
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

        return implode(
            "\n",
            array(
                'Tema: ' . $topic,
                'Contexto/enfoque: ' . $context,
                'Experiencia personal: ' . ($experience !== '' ? $experience : '(ninguna indicada)'),
                'Qué enseñar: ' . $teach,
                'Stacks / tipo de log: ' . $log_type,
                'Nivel técnico: ' . $level,
                'Público principal: ' . $audience,
            )
        );
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
        if (stripos($html, 'class="ensor-quiz"') === false) {
            $empty_quiz = wp_json_encode(
                array(
                    'questions' => array(),
                )
            );
            $html .= "\n" . '<section class="ensor-quiz" data-quiz="' . esc_attr((string) $empty_quiz) . '"></section>';
        }
        return $html;
    }
}

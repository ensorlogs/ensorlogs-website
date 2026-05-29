<?php
/**
 * Prompt builder, RAW EnsorLogs → HTML, and HTML guardrails.
 */

if (!defined('ABSPATH')) {
    exit;
}

final class EAE_Prompt
{
    /**
     * Prompt único para pegar en ChatGPT: manual + brief.
     *
     * @param array<string,mixed> $input
     */
    public static function build_chatgpt_prompt(array $input): string
    {
        $manual = function_exists('eae_get_editorial_manual') ? eae_get_editorial_manual() : '';
        $brief  = self::build_brief_block($input);

        return trim($manual . "\n\n--- BRIEF DE ESTE LOG ---\n\n" . $brief);
    }

    /**
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

        $lines = array(
            'Tema: ' . $topic,
            'Contexto o enfoque: ' . ($context !== '' ? $context : '(no indicado)'),
            'Experiencia personal: ' . ($experience !== '' ? $experience : '(no indicada)'),
            'Qué quiero enseñar: ' . ($teach !== '' ? $teach : '(no indicado)'),
            'Stack: ' . $log_type,
            'Nivel: ' . $level,
            'Público: ' . $audience,
        );

        return implode("\n", $lines);
    }

    public static function is_raw_format(string $text): bool
    {
        return (bool) preg_match(
            '/\[(ALGUNAS_PALABRAS|DATOS_REALES|ESTUDIANTE|PROFESOR|PROFESIONAL|REFLEXION)\]/i',
            $text
        );
    }

    /**
     * Convierte formato RAW EnsorLogs al HTML del editor.
     */
    public static function raw_to_html(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }

        $sections = array(
            'ALGUNAS_PALABRAS' => array('aud' => '', 'title' => 'Algunas Palabras', 'plain' => true),
            'DATOS_REALES'     => array('aud' => 'data', 'title' => 'Datos Reales'),
            'ESTUDIANTE'       => array('aud' => 'student', 'title' => '¿Eres estudiante?'),
            'PROFESOR'         => array('aud' => 'teacher', 'title' => '¿Eres profesor?'),
            'PROFESIONAL'      => array('aud' => 'professional', 'title' => 'Como profesional'),
            'REFLEXION'        => array('aud' => '', 'title' => 'Reflexión EnsorLogs', 'plain' => true),
        );

        $html = '';
        foreach ($sections as $tag => $meta) {
            $pattern = '/\[' . preg_quote($tag, '/') . '\]\s*([\s\S]*?)\s*\[\/' . preg_quote($tag, '/') . '\]/i';
            if (!preg_match($pattern, $raw, $match)) {
                continue;
            }
            $body = self::raw_body_to_html(trim($match[1]));
            if ($body === '') {
                continue;
            }
            if (!empty($meta['plain'])) {
                $html .= '<h2>' . esc_html($meta['title']) . '</h2>' . $body . "\n";
                continue;
            }
            $html .= '<section class="ensor-aud-section" data-aud="' . esc_attr($meta['aud']) . '">'
                . '<h2>' . esc_html($meta['title']) . '</h2>'
                . $body
                . '</section>' . "\n";
        }

        return trim($html);
    }

    private static function raw_body_to_html(string $body): string
    {
        if ($body === '') {
            return '';
        }

        $body = preg_replace_callback(
            '/\[CITA\]\s*([\s\S]*?)\s*\[\/CITA\]/i',
            static function (array $m): string {
                return "\n\n" . self::raw_quote_to_html($m[1]) . "\n\n";
            },
            $body
        ) ?? $body;

        $body = preg_replace_callback(
            '/\[IDEA_CLAVE\]\s*([\s\S]*?)\s*\[\/IDEA_CLAVE\]/i',
            static function (array $m): string {
                return "\n\n" . self::raw_callout_to_html($m[1]) . "\n\n";
            },
            $body
        ) ?? $body;

        $body = preg_replace_callback(
            '/\[PROMPT\]\s*([\s\S]*?)\s*\[\/PROMPT\]/i',
            static function (array $m): string {
                return "\n\n" . self::raw_prompt_to_html($m[1]) . "\n\n";
            },
            $body
        ) ?? $body;

        $body = preg_replace_callback(
            '/\[CODE\]\s*([\s\S]*?)\s*\[\/CODE\]/i',
            static function (array $m): string {
                return "\n\n<pre><code>" . esc_html($m[1]) . "</code></pre>\n\n";
            },
            $body
        ) ?? $body;

        $body = preg_replace_callback(
            '/\[COMMAND\]\s*([\s\S]*?)\s*\[\/COMMAND\]/i',
            static function (array $m): string {
                return "\n\n<pre><code>" . esc_html($m[1]) . "</code></pre>\n\n";
            },
            $body
        ) ?? $body;

        $chunks = preg_split('/\n\s*\n/', trim($body)) ?: array();
        $html   = '';

        foreach ($chunks as $chunk) {
            $chunk = trim($chunk);
            if ($chunk === '') {
                continue;
            }
            if (str_starts_with($chunk, '<pre') || str_starts_with($chunk, '<div') || str_starts_with($chunk, '<blockquote')) {
                $html .= $chunk;
                continue;
            }
            if (preg_match('/^FUENTES CONSULTADAS\s*$/iu', $chunk)) {
                $html .= '<p><strong>FUENTES CONSULTADAS</strong></p>';
                continue;
            }
            if (self::is_internal_subtitle($chunk)) {
                $title = preg_replace('/^#{1,3}\s*/u', '', $chunk);
                $html .= '<h3>' . self::raw_inline_format($title) . '</h3>';
                continue;
            }
            if (preg_match('/^-\s/m', $chunk)) {
                $html .= self::raw_list_to_html($chunk);
                continue;
            }
            if (preg_match('/^\d+\.\s/m', $chunk)) {
                $html .= self::raw_ol_to_html($chunk);
                continue;
            }
            $html .= '<p>' . nl2br(self::raw_inline_format($chunk), false) . '</p>';
        }

        return $html;
    }

    /**
     * Convierte **negrita** y *cursiva* del RAW a HTML seguro.
     */
    private static function raw_inline_format(string $text): string
    {
        $text = esc_html($text);
        $text = preg_replace('/==(.+?)==/s', '<mark>$1</mark>', $text) ?? $text;
        $text = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text) ?? $text;
        $text = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/s', '<em>$1</em>', $text) ?? $text;
        return $text;
    }

    private static function raw_quote_to_html(string $content): string
    {
        $content = trim($content);
        if ($content === '') {
            return '';
        }
        return '<blockquote><p>' . nl2br(self::raw_inline_format($content), false) . '</p></blockquote>';
    }

    private static function raw_callout_to_html(string $content): string
    {
        $content = trim($content);
        if ($content === '') {
            return '';
        }
        return '<div class="ensor-reader-callout"><p>' . nl2br(self::raw_inline_format($content), false) . '</p></div>';
    }

    private static function is_internal_subtitle(string $chunk): bool
    {
        if (preg_match('/^#{1,3}\s+\S/u', $chunk)) {
            return strpos($chunk, "\n") === false;
        }
        if (strpos($chunk, "\n") !== false) {
            return false;
        }
        $len = mb_strlen($chunk);
        if ($len < 12 || $len > 180) {
            return false;
        }
        if (preg_match('/^(introducción|características|beneficios|conclusión|resumen)$/iu', $chunk)) {
            return false;
        }
        return (bool) preg_match('/^.+:.+$/u', $chunk);
    }

    private static function raw_list_to_html(string $chunk): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $chunk) ?: array();
        $items = array();
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if (preg_match('/^[-·•]\s*(.+)$/', $line, $m)) {
                $items[] = '<li>' . self::raw_inline_format(trim($m[1])) . '</li>';
            } elseif ($items) {
                $items[count($items) - 1] = rtrim($items[count($items) - 1], '</li>')
                    . ' ' . self::raw_inline_format($line) . '</li>';
            }
        }
        if (!$items) {
            return '<p>' . nl2br(self::raw_inline_format($chunk), false) . '</p>';
        }
        return '<ul>' . implode('', $items) . '</ul>';
    }

    private static function raw_ol_to_html(string $chunk): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $chunk) ?: array();
        $items = array();
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if (preg_match('/^\d+\.\s*(.+)$/', $line, $m)) {
                $items[] = '<li>' . self::raw_inline_format(trim($m[1])) . '</li>';
            } elseif ($items) {
                $items[count($items) - 1] = rtrim($items[count($items) - 1], '</li>')
                    . ' ' . self::raw_inline_format($line) . '</li>';
            }
        }
        if (!$items) {
            return '<p>' . nl2br(self::raw_inline_format($chunk), false) . '</p>';
        }
        return '<ol>' . implode('', $items) . '</ol>';
    }

    /**
     * Bloque Prompt IA (mismo markup que articulos/wordpress-vale-pena-aprender-2026).
     */
    private static function raw_prompt_to_html(string $content): string
    {
        $content = trim($content);
        if ($content === '') {
            return '';
        }

        $title = 'Prompt IA';
        $body  = $content;

        if (preg_match('/^Prompt IA\s*[—\-:]/iu', $content)) {
            $lines = preg_split('/\r\n|\r|\n/', $content, 2);
            if (is_array($lines) && isset($lines[0]) && trim($lines[0]) !== '') {
                $title = trim($lines[0]);
                $body  = isset($lines[1]) ? trim($lines[1]) : '';
            }
        }

        if ($body === '') {
            $body = $content;
        }

        return '<div class="ensor-ai-prompt">'
            . '<p><strong>' . esc_html($title) . '</strong></p>'
            . '<pre>' . esc_html($body) . '</pre>'
            . '</div>';
    }

    /**
     * Convierte contenido RAW EnsorLogs pegado en el Paso 3.
     */
    public static function normalize_import_content(string $content): string
    {
        $content = trim($content);
        if ($content === '' || !self::is_raw_format($content)) {
            return '';
        }
        return self::raw_to_html($content);
    }

    public static function sanitize_generated_html(string $html): string
    {
        $allowed = array(
            'div'        => array('class' => true),
            'section' => array(
                'class'     => true,
                'data-aud'  => true,
                'data-quiz' => true,
            ),
            'h2'         => array('id' => true),
            'h3'         => array('id' => true),
            'p'          => array(),
            'strong'     => array(),
            'em'         => array(),
            'mark'       => array(),
            'a'          => array('href' => true, 'target' => true, 'rel' => true),
            'ul'         => array(),
            'ol'         => array(),
            'li'         => array(),
            'blockquote' => array(),
            'code'       => array('class' => true),
            'pre'        => array('class' => true),
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

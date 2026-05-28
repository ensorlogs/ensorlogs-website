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
     * @param array<string,string> $input
     */
    public static function build_master_prompt(array $input): string
    {
        $topic      = trim((string) ($input['topic'] ?? ''));
        $context    = trim((string) ($input['context'] ?? ''));
        $experience = trim((string) ($input['experience'] ?? ''));
        $teach      = trim((string) ($input['teach'] ?? ''));
        $log_type   = trim((string) ($input['logType'] ?? ''));
        $level      = trim((string) ($input['level'] ?? ''));
        $audience   = trim((string) ($input['audience'] ?? ''));

        return implode(
            "\n",
            array(
                'Eres EnsorLogs AI ENGINE.',
                'Tono obligatorio: 70% cercano y humano, 30% técnico.',
                'Contexto editorial: venezolano geek enseñando lo que aprende.',
                'Prohibido tono corporativo, SEO fake, clickbait o frases robóticas.',
                '',
                'Genera SOLO HTML semántico, sin markdown ni texto fuera del HTML.',
                'Mantén exactamente estas secciones y clases:',
                '- <section class="ensor-aud-section" data-aud="context"><h2>Algunas Palabras</h2>...</section>',
                '- <section class="ensor-aud-section" data-aud="data"><h2>Datos Reales</h2>...</section>',
                '- <section class="ensor-aud-section" data-aud="student"><h2>¿Eres estudiante?</h2>...</section>',
                '- <section class="ensor-aud-section" data-aud="teacher"><h2>¿Eres profesor?</h2>...</section>',
                '- <section class="ensor-aud-section" data-aud="professional"><h2>Como profesional</h2>...</section>',
                '- <section class="ensor-aud-section" data-aud="context"><h2>Reflexión EnsorLogs</h2>...</section>',
                '- <section class="ensor-aud-section" data-aud="student"><h2>LOG QUESTIONS</h2><ul>...</ul></section>',
                '- <section class="ensor-quiz" data-quiz=\'{"questions":[...]}\'></section>',
                '',
                'Reglas de salida:',
                '- Usa H2 obligatoriamente en cada sección.',
                '- Explica simple y luego técnico.',
                '- Incluye analogías humanas cuando ayuden.',
                '- No inventes clases CSS nuevas.',
                '- No añadas <script>, <style>, iframes ni inline JS.',
                '',
                'Datos del log a desarrollar:',
                'Tema: ' . $topic,
                'Contexto/enfoque: ' . $context,
                'Experiencia personal: ' . $experience,
                'Qué enseñar: ' . $teach,
                'Tipo de log: ' . $log_type,
                'Nivel técnico: ' . $level,
                'Público principal: ' . $audience,
                '',
                'Para LOG QUESTIONS genera 5 preguntas concretas en lista.',
                'Para LOG CHECK en data-quiz devuelve 3 preguntas con formato:',
                '{"questions":[{"q":"...","options":["...","...","...","..."],"correct":1,"hint":"...","explanation":"..."}]}',
            )
        );
    }

    public static function sanitize_generated_html(string $html): string
    {
        $allowed = array(
            'section' => array(
                'class'    => true,
                'data-aud' => true,
                'data-quiz'=> true,
            ),
            'h2'      => array('id' => true),
            'p'       => array(),
            'strong'  => array(),
            'em'      => array(),
            'mark'    => array(),
            'a'       => array('href' => true, 'target' => true, 'rel' => true),
            'ul'      => array(),
            'ol'      => array(),
            'li'      => array(),
            'blockquote' => array(),
            'code'    => array(),
            'pre'     => array(),
        );

        $clean = wp_kses($html, $allowed);
        $clean = self::enforce_required_sections($clean);
        return trim($clean);
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

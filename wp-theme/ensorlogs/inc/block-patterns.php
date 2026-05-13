<?php
/**
 * Patrones de bloques Gutenberg propios del tema Ensorlogs.
 *
 * Al editar un Log puedes insertar de un click:
 *  - Secciones tipo "panel" filtrables por audiencia: Contexto, Datos,
 *    Como estudiante, Como profesor, Como profesional.
 *  - Bloque "Prompt IA" para pegar dentro de cualquier sección.
 *  - Callouts y resaltado tipo marcador amarillo.
 *
 * @package Ensorlogs
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action(
    'init',
    static function (): void {
        if (!function_exists('register_block_pattern_category')) {
            return;
        }
        register_block_pattern_category(
            'ensorlogs-logs',
            array('label' => __('Ensorlogs · Logs', 'ensorlogs'))
        );
    },
    20
);

add_action(
    'init',
    static function (): void {
        if (!function_exists('register_block_pattern')) {
            return;
        }

        /**
         * Genera el HTML de una sección por audiencia con cabecera y placeholders.
         */
        $aud_section = static function (string $key, string $title, string $intro, string $bullets, string $prompt_title, string $prompt_body): string {
            $bullets_html = '';
            foreach (array_filter(array_map('trim', explode("\n", $bullets))) as $line) {
                $bullets_html .= '<!-- wp:list-item --><li>' . wp_kses_post($line) . '</li><!-- /wp:list-item -->';
            }
            return sprintf(
                '<!-- wp:group {"tagName":"section","className":"ensor-aud-section"} -->' .
                '<section class="wp-block-group ensor-aud-section" data-aud="%1$s">' .

                '<!-- wp:heading {"level":2} -->' .
                '<h2>%2$s</h2>' .
                '<!-- /wp:heading -->' .

                '<!-- wp:paragraph -->' .
                '<p>%3$s</p>' .
                '<!-- /wp:paragraph -->' .

                ($bullets_html
                    ? '<!-- wp:list --><ul>' . $bullets_html . '</ul><!-- /wp:list -->'
                    : ''
                ) .

                '<!-- wp:group {"className":"ensor-ai-prompt"} -->' .
                '<div class="wp-block-group ensor-ai-prompt">' .
                '<!-- wp:paragraph --><p><strong>%4$s</strong></p><!-- /wp:paragraph -->' .
                '<!-- wp:preformatted --><pre class="wp-block-preformatted">%5$s</pre><!-- /wp:preformatted -->' .
                '</div>' .
                '<!-- /wp:group -->' .

                '</section>' .
                '<!-- /wp:group -->',
                esc_attr($key),
                esc_html($title),
                esc_html($intro),
                esc_html($prompt_title),
                esc_html($prompt_body)
            );
        };

        register_block_pattern(
            'ensorlogs/section-context',
            array(
                'title'       => __('Log · Contexto', 'ensorlogs'),
                'description' => __('Sección que explica el asunto y la situación actual.', 'ensorlogs'),
                'categories'  => array('ensorlogs-logs'),
                'keywords'    => array('contexto', 'context', 'introducción'),
                'content'     => $aud_section(
                    'context',
                    __('Contexto', 'ensorlogs'),
                    __('Explica el asunto y la situación actual. ¿Qué está pasando con este tema en 2026, dónde encaja en tu experiencia y qué problema resuelve para el lector?', 'ensorlogs'),
                    "Qué se dice afuera vs. qué pasa cuando lo aterrizas\nTu mirada personal (3-4 frases)\nA quién le sirve este log",
                    __('Prompt IA — resumir el contexto', 'ensorlogs'),
                    __("Eres editor técnico. Resume en 4 frases el contexto actual de [tema]\nen 2026, mencionando: 1) la postura mayoritaria en redes, 2) lo que\nrealmente está pasando en empresas / educación, 3) por qué este log\nes útil para el lector. Tono honesto, sin clichés de marketing.", 'ensorlogs')
                ),
            )
        );

        register_block_pattern(
            'ensorlogs/section-data',
            array(
                'title'       => __('Log · Datos y estadísticas', 'ensorlogs'),
                'description' => __('Cifras, gráficos y fuentes que validan el contexto.', 'ensorlogs'),
                'categories'  => array('ensorlogs-logs'),
                'keywords'    => array('datos', 'estadísticas', 'gráficos', 'data'),
                'content'     => $aud_section(
                    'data',
                    __('Datos', 'ensorlogs'),
                    __('Coloca aquí cifras, gráficos o capturas que respalden el contexto. Cita fuentes verificables y avisa al lector cuando un número sea aproximado.', 'ensorlogs'),
                    "Cifra principal con fuente y fecha\nCifra secundaria que matiza\nUn dato anti-narrativa (lo que sorprende)",
                    __('Prompt IA — verificar y enmarcar cifras', 'ensorlogs'),
                    __("Eres analista de datos. Dado el dato bruto [pegar aquí], devuelve:\n1) cifra redondeada útil para un público no técnico,\n2) qué fuente oficial y de qué fecha la respalda,\n3) un matiz o limitación que el lector debería conocer,\n4) una frase corta lista para citar en el log.", 'ensorlogs')
                ),
            )
        );

        register_block_pattern(
            'ensorlogs/section-student',
            array(
                'title'       => __('Log · Como estudiante', 'ensorlogs'),
                'description' => __('Cómo aprovecharlo siendo estudiante.', 'ensorlogs'),
                'categories'  => array('ensorlogs-logs'),
                'keywords'    => array('estudiante', 'student', 'aprender'),
                'content'     => $aud_section(
                    'student',
                    __('Como estudiante', 'ensorlogs'),
                    __('Qué puedes aprovechar tú para aprender este tema y cómo te ayudaría siendo estudiante. Sé concreto: pasos, recursos gratis, cómo demostrarlo en tu portafolio.', 'ensorlogs'),
                    "Primer paso aprovechable hoy mismo\nRecurso gratuito recomendado\nForma de validarlo (mini proyecto o entregable)",
                    __('Prompt IA — plan de estudio personalizado', 'ensorlogs'),
                    __("Actúa como mentor técnico. Diseña un plan de 4 semanas\n(1 h/día) para un estudiante que quiere aprender [tema] y\npoder ofrecerlo como servicio o demostrarlo en una entrevista.\nIncluye: temas por semana, un mini-proyecto entregable cada\nsemana y 3 errores comunes a evitar. Idioma: español neutro.", 'ensorlogs')
                ),
            )
        );

        register_block_pattern(
            'ensorlogs/section-teacher',
            array(
                'title'       => __('Log · Como profesor', 'ensorlogs'),
                'description' => __('Tips para enseñar este tema en clase.', 'ensorlogs'),
                'categories'  => array('ensorlogs-logs'),
                'keywords'    => array('profesor', 'teacher', 'docente', 'enseñar'),
                'content'     => $aud_section(
                    'teacher',
                    __('Como profesor', 'ensorlogs'),
                    __('Cómo enseñarías esto a tus alumnos: qué dinámica, qué evaluación, qué errores típicos vas a corregir, qué anécdota cuentas. Esta sección comparte recursos para otros docentes.', 'ensorlogs'),
                    "Dinámica de aula o tutoría sugerida\nRúbrica corta (5 criterios con peso)\nAnécdota o ejemplo que siempre funciona",
                    __('Prompt IA — rúbrica + feedback', 'ensorlogs'),
                    __("Actúa como profesor de tecnologías. Dado el proyecto de un\nestudiante sobre [tema], genera: 1) rúbrica con 5 criterios\ncon peso porcentual, 2) feedback constructivo dirigido al\nalumno en tono cálido, 3) tres preguntas para defensa oral.\nMarca prioridades alta / media / baja en los problemas.", 'ensorlogs')
                ),
            )
        );

        register_block_pattern(
            'ensorlogs/section-professional',
            array(
                'title'       => __('Log · Como profesional', 'ensorlogs'),
                'description' => __('Cómo lo usas en proyectos reales.', 'ensorlogs'),
                'categories'  => array('ensorlogs-logs'),
                'keywords'    => array('profesional', 'professional', 'trabajo', 'cliente'),
                'content'     => $aud_section(
                    'professional',
                    __('Como profesional', 'ensorlogs'),
                    __('Cómo lo has usado en tu vida profesional, qué ventajas saca el cliente y qué desventajas hay que tener en cuenta. Recomendaciones concretas para otros pros.', 'ensorlogs'),
                    "Caso real (cliente / proyecto resumido sin datos sensibles)\nVentaja medible (ahorro, tiempo, conversión)\nDesventaja que conviene transparentar",
                    __('Prompt IA — auditoría pre-propuesta', 'ensorlogs'),
                    __("Eres consultor senior. Dado este sitio / sistema, analiza en\n5 puntos: 1) estado actual, 2) rendimiento, 3) riesgos de\nseguridad visibles, 4) accesibilidad básica, 5) oportunidades.\nEntrega una propuesta en tres tramos: quick wins (1-2 semanas),\nmedio plazo (1-3 meses) y estructural.", 'ensorlogs')
                ),
            )
        );

        // Bloque "Prompt IA" suelto, para meterlo donde quieras.
        register_block_pattern(
            'ensorlogs/ai-prompt',
            array(
                'title'       => __('Prompt IA (callout)', 'ensorlogs'),
                'description' => __('Bloque para compartir un prompt listo para copiar.', 'ensorlogs'),
                'categories'  => array('ensorlogs-logs'),
                'keywords'    => array('prompt', 'ia', 'ai', 'chatgpt', 'gemini'),
                'content'     =>
                    '<!-- wp:group {"className":"ensor-ai-prompt"} -->' .
                    '<div class="wp-block-group ensor-ai-prompt">' .
                    '<!-- wp:paragraph --><p><strong>' . esc_html__('Prompt IA — describe en una frase qué hace', 'ensorlogs') . '</strong></p><!-- /wp:paragraph -->' .
                    '<!-- wp:preformatted --><pre class="wp-block-preformatted">' .
                    esc_html__("Eres [rol]. Tu tarea es [objetivo].\nDevuelve: [formato esperado].\nReglas: [tono, idioma, longitud].", 'ensorlogs') .
                    '</pre><!-- /wp:preformatted -->' .
                    '</div>' .
                    '<!-- /wp:group -->',
            )
        );

        register_block_pattern(
            'ensorlogs/callout-takeaway',
            array(
                'title'       => __('Callout · Idea clave', 'ensorlogs'),
                'description' => __('Bloque resaltado para la idea principal del log.', 'ensorlogs'),
                'categories'  => array('ensorlogs-logs'),
                'keywords'    => array('callout', 'takeaway', 'destacado'),
                'content'     => '<!-- wp:group {"className":"ensor-reader-callout"} -->' .
                    '<div class="wp-block-group ensor-reader-callout">' .
                    '<!-- wp:paragraph --><p><strong>' . esc_html__('Idea clave:', 'ensorlogs') . '</strong> ' .
                    esc_html__('Una frase corta que el lector se debería llevar de esta sección.', 'ensorlogs') .
                    '</p><!-- /wp:paragraph -->' .
                    '</div><!-- /wp:group -->',
            )
        );

        register_block_pattern(
            'ensorlogs/highlight-line',
            array(
                'title'       => __('Línea destacada (marker amarillo)', 'ensorlogs'),
                'description' => __('Un párrafo con palabra clave resaltada al estilo marcador.', 'ensorlogs'),
                'categories'  => array('ensorlogs-logs'),
                'keywords'    => array('highlight', 'marker', 'destacado'),
                'content'     => '<!-- wp:paragraph -->' .
                    '<p>' . esc_html__('Tu frase principal con ', 'ensorlogs') . '<mark>' . esc_html__('una palabra clave resaltada', 'ensorlogs') . '</mark>' .
                    esc_html__(' que destaque sobre el resto.', 'ensorlogs') . '</p>' .
                    '<!-- /wp:paragraph -->',
            )
        );
    },
    30
);

/**
 * Permite que `<mark>`, `<section>` y atributos `data-aud` pasen por el
 * saneamiento de WordPress al guardar el contenido de un Log.
 */
add_filter(
    'wp_kses_allowed_html',
    static function ($tags, $context) {
        if ($context !== 'post') {
            return $tags;
        }
        if (!is_array($tags)) {
            return $tags;
        }
        $allow = array('class' => true, 'id' => true, 'data-aud' => true, 'data-copy' => true);
        if (!isset($tags['mark'])) {
            $tags['mark'] = array('class' => true);
        }
        if (!isset($tags['section'])) {
            $tags['section'] = $allow;
        }
        foreach (array('div', 'section', 'aside', 'p', 'span') as $t) {
            if (isset($tags[$t]) && is_array($tags[$t])) {
                $tags[$t] = array_merge($tags[$t], $allow);
            }
        }
        return $tags;
    },
    10,
    2
);

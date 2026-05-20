<?php
/**
 * Editor visual (bloques) + panel meta para tarjetas de listado (artículos y proyectos).
 *
 * @package Ensorlogs
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Opciones del desplegable «tema principal» (filtro del blog).
 *
 * @return array<string, string> slug => etiqueta
 */
function ensorlogs_primary_tema_choices(): array
{
    return array(
        'wordpress'  => 'WordPress',
        'linux'      => 'Linux',
        'ia'         => 'IA',
        'database'   => 'Database',
        'crm'        => 'CRM',
        'marketing'  => 'Marketing',
        'python'     => 'Python',
        'google'     => 'Google',
        'servidores' => 'Servidores',
        'it'         => 'IT',
        'windows'    => 'Windows',
        'mac'        => 'Mac',
    );
}

/**
 * @param string $value espacio-separado: "wordpress ia it"
 */
function ensorlogs_sanitize_temas_string(?string $value): string
{
    $value = preg_replace('/\s+/', ' ', trim((string) $value));
    if ($value === '') {
        return '';
    }
    $parts = array_filter(explode(' ', strtolower($value)));
    return implode(' ', array_unique($parts));
}

/**
 * Lista "wordpress, crm, it" → JSON ["wordpress","crm","it"].
 */
function ensorlogs_sanitize_project_tags_list(?string $value): string
{
    $parts = preg_split('/[\s,]+/', (string) $value, -1, PREG_SPLIT_NO_EMPTY);
    $out   = array();
    foreach ($parts as $p) {
        $t = sanitize_title($p);
        if ($t !== '') {
            $out[] = $t;
        }
    }
    $out = array_values(array_unique($out));
    return wp_json_encode($out);
}

/**
 * Imagen de tarjeta: URL absoluta o ruta relativa al tema `assets/...`.
 */
function ensorlogs_sanitize_img_src_field(?string $value): string
{
    $value = trim(wp_strip_all_tags((string) $value));
    if ($value === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $value)) {
        return esc_url_raw($value);
    }
    if (preg_match('#^assets/[a-zA-Z0-9_./-]+$#', $value)) {
        return $value;
    }
    return '';
}

/**
 * Clases de ancho del bloque en el grid de proyectos.
 */
function ensorlogs_sanitize_project_item_class(?string $value): string
{
    $allowed = array(
        'project-item group',
        'project-item group sm:col-span-2',
    );
    $value = trim((string) $value);
    return in_array($value, $allowed, true) ? $value : 'project-item group';
}

/**
 * Estilos del editor de bloques. Los presets de color, tipografía y layout
 * viven en `theme.json` (raíz del tema). Aquí solo cargamos CSS adicional
 * para que el canvas se parezca al front.
 */
add_action(
    'after_setup_theme',
    static function (): void {
        add_theme_support('editor-styles');
        add_editor_style(
            array(
                'assets/css/ensor-brand.css',
                'assets/css/editor-canvas.css',
            )
        );
    },
    25
);

/**
 * TinyMCE (editor clásico): registra el plugin `ensor_mark` y añade el
 * botón "Marker" en la barra de herramientas para resaltar con <mark>.
 */
add_filter(
    'mce_external_plugins',
    static function (array $plugins): array {
        $url = trailingslashit(get_template_directory_uri()) . 'assets/js/tinymce-ensor-mark.js';
        $plugins['ensor_mark'] = add_query_arg('v', ENSORLOGS_THEME_VERSION, $url);
        return $plugins;
    }
);

add_filter(
    'mce_buttons',
    static function (array $buttons): array {
        if (!in_array('ensor_mark', $buttons, true)) {
            $buttons[] = 'ensor_mark';
        }
        return $buttons;
    }
);

add_filter(
    'block_editor_settings_all',
    static function (array $settings, $context): array {
        $post = (is_object($context) && isset($context->post)) ? $context->post : null;
        if (!$post instanceof WP_Post) {
            return $settings;
        }
        if (!in_array($post->post_type, array('ensor_article', 'ensor_project'), true)) {
            return $settings;
        }
        if ($post->post_type === 'ensor_article') {
            $settings['bodyPlaceholder'] = __(
                'Escribe con bloques (párrafos, columnas, imágenes…). Si importaste HTML, WordPress lo mostrará en un bloque: puedes añadir bloques arriba o abajo sin perder el diseño.',
                'ensorlogs'
            );
        } else {
            $settings['bodyPlaceholder'] = __(
                'Describe el caso con bloques. Puedes combinar texto, imágenes y grupos para sustituir poco a poco el HTML importado.',
                'ensorlogs'
            );
        }
        return $settings;
    },
    10,
    2
);

/**
 * Catálogo canónico de las secciones pedagógicas que un log puede tener.
 * Cada entrada define la audiencia (`aud`, mismo valor que `data-aud` del front),
 * el slug del heading que se generará (`id`), la etiqueta visible y un texto
 * de ayuda con sugerencias didácticas (incluida recomendación de prompts de IA).
 *
 * Modifica este array si quieres añadir o cambiar secciones; las metaboxes,
 * el registro de metas, el sanitizer y el renderer en `the_content` se
 * regeneran automáticamente a partir de aquí.
 *
 * @return array<string, array{aud:string,label:string,help:string,prompt:string}>
 */
function ensorlogs_article_sections(): array
{
    return array(
        'context' => array(
            'aud'    => 'context',
            'label'  => __('Contexto', 'ensorlogs'),
            'help'   => __('Explica la situación y el contexto actual del tema. ¿Qué pasa ahora? ¿Por qué este log existe? Ubica al lector antes de entrar en el detalle.', 'ensorlogs'),
            'prompt' => __('Prompt sugerido para IA: «Dame un resumen objetivo del estado actual de [TEMA] en 2026, citando 3 fuentes recientes con enlaces, en máximo 180 palabras y tono periodístico.»', 'ensorlogs'),
        ),
        'data' => array(
            'aud'    => 'data',
            'label'  => __('Datos', 'ensorlogs'),
            'help'   => __('Estadísticas, gráficos, cifras, capturas o referencias que respalden lo que dices en Contexto. Si tienes imágenes, súbelas con el botón «Añadir medios».', 'ensorlogs'),
            'prompt' => __('Prompt sugerido para IA: «Dame 5 cifras verificables sobre [TEMA] en 2025-2026 con fuente original (W3Techs, StatCounter, GitHub, Stack Overflow Survey, etc.) en formato lista.»', 'ensorlogs'),
        ),
        'student' => array(
            'aud'    => 'student',
            'label'  => __('Como estudiante', 'ensorlogs'),
            'help'   => __('Qué puede aprovechar alguien que recién empieza para aprender el tema. Recursos, atajos, errores comunes, dónde practicar. Recomienda IA y prompts útiles para estudiar.', 'ensorlogs'),
            'prompt' => __('Prompt sugerido para IA: «Soy estudiante de [CARRERA] y quiero practicar [TEMA] desde cero en 2 semanas. Dame un plan diario con recursos gratuitos y ejercicios autoevaluables.»', 'ensorlogs'),
        ),
        'teacher' => array(
            'aud'    => 'teacher',
            'label'  => __('Como profesor', 'ensorlogs'),
            'help'   => __('Tips para enseñarlo: dinámicas, evaluaciones, anécdotas de clase, recursos que recomiendas o evitas. Pensado para profesores y formadores que adapten este material.', 'ensorlogs'),
            'prompt' => __('Prompt sugerido para IA: «Diseña una actividad evaluable de 45 min para enseñar [TEMA] a estudiantes universitarios de primer año, con rúbrica y criterios claros.»', 'ensorlogs'),
        ),
        'professional' => array(
            'aud'    => 'professional',
            'label'  => __('Como profesional', 'ensorlogs'),
            'help'   => __('Cómo lo usas tú en proyectos reales: ventajas, desventajas, decisiones de stack, casos donde sí encaja y casos donde no. Aquí compartes tu experiencia profesional.', 'ensorlogs'),
            'prompt' => __('Prompt sugerido para IA: «Actúa como senior con 8 años en [TEMA]. Lista los 5 errores más comunes en producción y qué patrón aplicar en cada caso.»', 'ensorlogs'),
        ),
    );
}

/**
 * Devuelve un callback de saneamiento por cada meta_key, para que el valor
 * sea seguro tanto si llega desde el panel clásico como desde la REST API.
 */
function ensorlogs_meta_sanitizer(string $meta_key): callable
{
    // Secciones pedagógicas con WYSIWYG: aceptan HTML básico (wp_kses_post).
    if (strpos($meta_key, '_ensor_section_') === 0) {
        return static function ($value): string {
            return wp_kses_post(is_string($value) ? $value : '');
        };
    }
    switch ($meta_key) {
        case '_ensor_card_image':
        case '_ensor_img_rel':
            return static function ($value): string {
                return ensorlogs_sanitize_img_src_field(is_string($value) ? $value : '');
            };
        case '_ensor_card_excerpt':
            return static function ($value): string {
                return sanitize_textarea_field(is_string($value) ? $value : '');
            };
        case '_ensor_temas':
            return static function ($value): string {
                return ensorlogs_sanitize_temas_string(is_string($value) ? $value : '');
            };
        case '_ensor_primary_tema':
            return static function ($value): string {
                $v       = is_string($value) ? sanitize_key($value) : '';
                $choices = ensorlogs_primary_tema_choices();
                return array_key_exists($v, $choices) ? $v : '';
            };
        case '_ensor_item_class':
            return static function ($value): string {
                return ensorlogs_sanitize_project_item_class(is_string($value) ? $value : '');
            };
        case '_ensor_podcast_attachment_id':
            return static function ($value): int {
                return absint($value);
            };
        case '_ensor_podcast_src':
            return static function ($value): string {
                $v = trim(is_string($value) ? $value : '');
                return $v === '' ? '' : esc_url_raw($v);
            };
        case '_ensor_podcast_chapters':
            return static function ($value): string {
                return ensorlogs_sanitize_podcast_chapters(is_string($value) ? $value : '');
            };
        case '_ensor_podcast_guests':
            return static function ($value): string {
                $g = wp_strip_all_tags(is_string($value) ? $value : '');
                $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $g) ?: array()));
                return implode("\n", $lines);
            };
        case '_ensor_podcast_title':
        case '_ensor_podcast_eyebrow':
        case '_ensor_podcast_duration':
        case '_ensor_podcast_narrator':
            return static function ($value): string {
                return sanitize_text_field(is_string($value) ? $value : '');
            };
        case '_ensor_tag_slugs':
            return static function ($value): string {
                if (!is_string($value)) {
                    return wp_json_encode(array());
                }
                $value = trim($value);
                if ($value === '') {
                    return wp_json_encode(array());
                }
                $decoded = json_decode($value, true);
                if (is_array($decoded)) {
                    $out = array();
                    foreach ($decoded as $slug) {
                        $s = sanitize_title((string) $slug);
                        if ($s !== '') {
                            $out[] = $s;
                        }
                    }
                    return wp_json_encode(array_values(array_unique($out)));
                }
                return ensorlogs_sanitize_project_tags_list($value);
            };
        case '_ensor_subtitle':
        case '_ensor_list_title':
        default:
            return static function ($value): string {
                return sanitize_text_field(is_string($value) ? $value : '');
            };
    }
}

add_action(
    'init',
    static function (): void {
        $auth = static function ($allowed, string $meta_key, int $object_id): bool {
            if ($object_id > 0) {
                return current_user_can('edit_post', $object_id);
            }
            return current_user_can('edit_posts');
        };

        $article_meta = array(
            '_ensor_card_image'              => 'string',
            '_ensor_card_excerpt'            => 'string',
            '_ensor_temas'                   => 'string',
            '_ensor_primary_tema'            => 'string',
            '_ensor_podcast_attachment_id'   => 'integer',
            '_ensor_podcast_src'             => 'string',
            '_ensor_podcast_title'           => 'string',
            '_ensor_podcast_eyebrow'         => 'string',
            '_ensor_podcast_duration'        => 'string',
            '_ensor_podcast_chapters'        => 'string',
            '_ensor_podcast_guests'          => 'string',
            '_ensor_podcast_narrator'        => 'string',
        );
        // Secciones pedagógicas (Contexto, Datos, Como estudiante, …) editables
        // como cajas meta WYSIWYG independientes — se inyectan al final del log.
        foreach (array_keys(ensorlogs_article_sections()) as $sec_key) {
            $article_meta['_ensor_section_' . $sec_key] = 'string';
        }
        foreach ($article_meta as $key => $type) {
            $args = array(
                'single'            => true,
                'show_in_rest'      => true,
                'auth_callback'     => $auth,
                'sanitize_callback' => ensorlogs_meta_sanitizer($key),
            );
            if ($type === 'integer') {
                $args['type']    = 'integer';
                $args['default'] = 0;
            } else {
                $args['type'] = 'string';
            }
            register_post_meta('ensor_article', $key, $args);
        }
        $project_meta = array(
            '_ensor_subtitle'   => 'string',
            '_ensor_list_title' => 'string',
            '_ensor_img_rel'    => 'string',
            '_ensor_item_class' => 'string',
            '_ensor_temas'      => 'string',
            '_ensor_tag_slugs'  => 'string',
        );
        foreach ($project_meta as $key => $type) {
            register_post_meta(
                'ensor_project',
                $key,
                array(
                    'type'              => $type,
                    'single'            => true,
                    'show_in_rest'      => true,
                    'auth_callback'     => $auth,
                    'sanitize_callback' => ensorlogs_meta_sanitizer($key),
                )
            );
        }
    },
    15
);

add_action(
    'add_meta_boxes',
    static function (): void {
        add_meta_box(
            'ensor_article_listing',
            __('Tarjeta del log', 'ensorlogs'),
            'ensorlogs_render_article_listing_metabox',
            'ensor_article',
            'normal',
            'high'
        );
        add_meta_box(
            'ensor_article_podcast',
            __('Audio del log (reproductor)', 'ensorlogs'),
            'ensorlogs_render_article_podcast_metabox',
            'ensor_article',
            'normal',
            'high'
        );
        add_meta_box(
            'ensor_article_quiz',
            __('Quiz de comprensión del log', 'ensorlogs'),
            'ensorlogs_render_article_quiz_metabox',
            'ensor_article',
            'normal',
            'default'
        );
        // Una caja meta WYSIWYG por sección pedagógica.
        // Aparecen plegadas por defecto; se inyectan en el contenido del log
        // si contienen texto. El editor TinyMCE incluye «Añadir medios»
        // para subir fotos / videos, igual que el editor principal.
        foreach (ensorlogs_article_sections() as $sec_key => $sec_def) {
            add_meta_box(
                'ensor_article_section_' . $sec_key,
                /* translators: %s: nombre legible de la sección (Contexto, Datos, …). */
                sprintf(__('Sección · %s', 'ensorlogs'), $sec_def['label']),
                static function ($post) use ($sec_key): void {
                    ensorlogs_render_article_section_metabox($post, $sec_key);
                },
                'ensor_article',
                'normal',
                'low'
            );
        }
        add_meta_box(
            'ensor_project_listing',
            __('Tarjeta del proyecto', 'ensorlogs'),
            'ensorlogs_render_project_listing_metabox',
            'ensor_project',
            'normal',
            'high'
        );
    }
);

/**
 * @param mixed $post WP_Post o null
 */
function ensorlogs_render_article_listing_metabox($post): void
{
    if (!$post instanceof WP_Post) {
        return;
    }
    wp_nonce_field('ensor_cpt_meta_save', 'ensor_cpt_meta_nonce');
    $img     = (string) get_post_meta($post->ID, '_ensor_card_image', true);
    $excerpt = (string) get_post_meta($post->ID, '_ensor_card_excerpt', true);
    $temas   = (string) get_post_meta($post->ID, '_ensor_temas', true);
    $primary = (string) get_post_meta($post->ID, '_ensor_primary_tema', true);
    ?>
    <div class="ensor-cpt-meta">
        <p class="ensor-cpt-meta__help">
            <?php esc_html_e('Estos campos alimentan la cuadrícula de «Hablemos de…» (imagen, texto corto y filtros por stack). El cuerpo largo del log se edita en el editor de bloques de abajo.', 'ensorlogs'); ?>
        </p>
        <div class="ensor-cpt-meta__section">
            <h4 class="ensor-cpt-meta__title"><?php esc_html_e('Imagen de la tarjeta', 'ensorlogs'); ?></h4>
            <div class="ensor-cpt-meta__row">
                <label for="ensor_card_image"><?php esc_html_e('URL de la imagen', 'ensorlogs'); ?></label>
                <input type="text" class="large-text ensor-cpt-meta__img-field" id="ensor_card_image" name="_ensor_card_image" value="<?php echo esc_attr($img); ?>" autocomplete="off">
                <div class="ensor-cpt-meta__actions">
                    <button type="button" class="button ensor-cpt-pick-media" data-target="ensor_card_image" data-title="<?php esc_attr_e('Imagen de la tarjeta', 'ensorlogs'); ?>" data-button="<?php esc_attr_e('Usar esta imagen', 'ensorlogs'); ?>">
                        <?php esc_html_e('Elegir de la biblioteca', 'ensorlogs'); ?>
                    </button>
                </div>
                <div class="ensor-cpt-meta__preview" aria-hidden="true">
                    <?php
                    $pv = ensorlogs_resolve_public_asset_url($img);
                    if ($pv !== '') {
                        echo '<img src="' . esc_url($pv) . '" alt="">';
                    }
                    ?>
                </div>
            </div>
        </div>
        <div class="ensor-cpt-meta__section">
            <h4 class="ensor-cpt-meta__title"><?php esc_html_e('Resumen (listado)', 'ensorlogs'); ?></h4>
            <div class="ensor-cpt-meta__row">
                <label for="ensor_card_excerpt"><?php esc_html_e('Texto bajo el título en la tarjeta', 'ensorlogs'); ?></label>
                <textarea id="ensor_card_excerpt" name="_ensor_card_excerpt" rows="3" class="large-text"><?php echo esc_textarea($excerpt); ?></textarea>
            </div>
        </div>
        <div class="ensor-cpt-meta__section">
            <h4 class="ensor-cpt-meta__title"><?php esc_html_e('Stacks del log', 'ensorlogs'); ?></h4>
            <div class="ensor-cpt-meta__row">
                <label for="ensor_temas"><?php esc_html_e('Stacks (slugs separados por espacio)', 'ensorlogs'); ?></label>
                <input type="text" class="large-text" id="ensor_temas" name="_ensor_temas" value="<?php echo esc_attr($temas); ?>" placeholder="wordpress ia it">
                <p class="description"><?php esc_html_e('Ejemplo: wordpress marketing google — se sincronizan automáticamente con la taxonomía Stacks.', 'ensorlogs'); ?></p>
            </div>
            <div class="ensor-cpt-meta__row">
                <label for="ensor_primary_tema"><?php esc_html_e('Stack principal (badge grande del log)', 'ensorlogs'); ?></label>
                <select id="ensor_primary_tema" name="_ensor_primary_tema" class="large-text">
                    <?php foreach (ensorlogs_primary_tema_choices() as $slug => $label) : ?>
                        <option value="<?php echo esc_attr($slug); ?>" <?php selected($primary, $slug); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="ensor-cpt-meta__section">
            <h4 class="ensor-cpt-meta__title"><?php esc_html_e('Imagen destacada', 'ensorlogs'); ?></h4>
            <p class="ensor-cpt-meta__help"><?php esc_html_e('Si dejas vacía la URL de arriba, el tema puede usar la imagen destacada de la entrada para redes y miniaturas.', 'ensorlogs'); ?></p>
        </div>
    </div>
    <?php
}

/**
 * Sanea el textarea de capítulos: una línea por capítulo
 * en formato "0:00 Título del capítulo".
 */
function ensorlogs_sanitize_podcast_chapters(string $raw): string
{
    $raw   = wp_strip_all_tags($raw);
    $lines = preg_split('/\r\n|\r|\n/', $raw) ?: array();
    $clean = array();
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        if (preg_match('/^(\d{1,2}:\d{2}(?::\d{2})?)\s+(.{1,200})$/u', $line, $m)) {
            $clean[] = $m[1] . ' ' . sanitize_text_field($m[2]);
        }
    }
    return implode("\n", $clean);
}

/**
 * Comprueba si el ID es un adjunto de WordPress con MIME audio/*.
 */
function ensorlogs_is_audio_attachment_id(int $attachment_id): bool
{
    if ($attachment_id <= 0) {
        return false;
    }
    $p = get_post($attachment_id);
    if (!$p instanceof WP_Post || $p->post_type !== 'attachment') {
        return false;
    }
    $mime = get_post_mime_type($attachment_id);
    return is_string($mime) && str_starts_with($mime, 'audio/');
}

/**
 * URL del audio del log: adjunto de Mediateca si existe; si no, meta `_ensor_podcast_src`.
 *
 * @return string URL escapada con esc_url_raw o cadena vacía.
 */
function ensorlogs_get_podcast_audio_url(int $post_id): string
{
    $aid = absint(get_post_meta($post_id, '_ensor_podcast_attachment_id', true));
    if ($aid > 0 && ensorlogs_is_audio_attachment_id($aid)) {
        $u = wp_get_attachment_url($aid);
        if (is_string($u) && $u !== '') {
            return esc_url_raw($u);
        }
    }
    $src = trim((string) get_post_meta($post_id, '_ensor_podcast_src', true));
    return $src === '' ? '' : esc_url_raw($src);
}

/**
 * Convierte un string "1:23" o "1:02:30" a segundos.
 */
function ensorlogs_podcast_time_to_seconds(string $value): int
{
    $value = trim($value);
    if ($value === '') {
        return 0;
    }
    if (strpos($value, ':') === false) {
        return (int) $value;
    }
    $parts = array_map('intval', explode(':', $value));
    if (count($parts) === 2) {
        return $parts[0] * 60 + $parts[1];
    }
    if (count($parts) === 3) {
        return $parts[0] * 3600 + $parts[1] * 60 + $parts[2];
    }
    return 0;
}

/**
 * Devuelve los capítulos como array preparado para JSON.
 *
 * @return array<int,array{time:int,title:string}>
 */
function ensorlogs_podcast_chapters_array(int $post_id): array
{
    $raw   = (string) get_post_meta($post_id, '_ensor_podcast_chapters', true);
    $lines = preg_split('/\r\n|\r|\n/', $raw) ?: array();
    $out   = array();
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        if (preg_match('/^(\S+)\s+(.+)$/u', $line, $m)) {
            $out[] = array(
                'time'  => ensorlogs_podcast_time_to_seconds($m[1]),
                'title' => $m[2],
            );
        }
    }
    return $out;
}

/**
 * Devuelve el bloque HTML "Comentario del autor" listo para insertar
 * en el header del log. Cadena vacía si no hay audio configurado.
 */
function ensorlogs_render_podcast_card(int $post_id): string
{
    $src = ensorlogs_get_podcast_audio_url($post_id);
    $src = trim($src);
    if ($src === '') {
        return '';
    }
    $title    = (string) get_post_meta($post_id, '_ensor_podcast_title', true);
    if ($title === '') {
        $title = __('Escúchame contarte este log', 'ensorlogs');
    }
    $eyebrow  = (string) get_post_meta($post_id, '_ensor_podcast_eyebrow', true);
    $duration = (string) get_post_meta($post_id, '_ensor_podcast_duration', true);
    $guests   = (string) get_post_meta($post_id, '_ensor_podcast_guests', true);
    $narrator = trim((string) get_post_meta($post_id, '_ensor_podcast_narrator', true));
    $chapters = ensorlogs_podcast_chapters_array($post_id);
    $has_guests = ($guests !== '');

    if ($eyebrow === '') {
        $eyebrow = $has_guests
            ? __("LOG.MP3 · CON INVITADO", 'ensorlogs')
            : __("LOG.MP3 · DIRECTOR'S CUT", 'ensorlogs');
    }

    $sub_parts = array();
    if ($duration !== '') {
        $sub_parts[] = $duration;
    }
    if (!empty($chapters)) {
        $sub_parts[] = sprintf(_n('%d capítulo', '%d capítulos', count($chapters), 'ensorlogs'), count($chapters));
    }
    if ($narrator !== '') {
        $sub_parts[] = sprintf(
            /* translators: %s: nombre del narrador (p. ej. Ensor). */
            __('narrado por %s', 'ensorlogs'),
            $narrator
        );
    } else {
        $sub_parts[] = __('narrado por Ensor', 'ensorlogs');
    }
    $sub_text   = implode(' · ', $sub_parts);

    $guests_html = '';
    if ($has_guests) {
        $items = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $guests) ?: array()));
        if ($items) {
            $chips = array();
            foreach ($items as $g) {
                $chips[] = '<span class="ensor-podcast-card__guest">' . esc_html($g) . '</span>';
            }
            $guests_html = '<div class="ensor-podcast-card__guests" aria-label="' . esc_attr__('Invitados', 'ensorlogs') . '">' . implode('', $chips) . '</div>';
        }
    }

    return '<div class="ensor-podcast-card"'
        . ' data-audio="' . esc_url($src) . '"'
        . ' data-duration="' . esc_attr($duration) . '"'
        . ' data-title="' . esc_attr($title) . '"'
        . ' data-chapters="' . esc_attr(wp_json_encode($chapters)) . '"'
        . '>'
        . '<button type="button" class="ensor-podcast-card__play" aria-label="' . esc_attr__('Reproducir comentario del autor', 'ensorlogs') . '">'
        . '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>'
        . '</button>'
        . '<div class="ensor-podcast-card__meta">'
        . '<span class="ensor-podcast-card__eyebrow">' . esc_html($eyebrow) . '</span>'
        . '<strong class="ensor-podcast-card__title">' . esc_html($title) . '</strong>'
        . '<span class="ensor-podcast-card__sub">' . esc_html($sub_text) . '</span>'
        . $guests_html
        . '</div>'
        . '</div>';
}

/**
 * @param mixed $post WP_Post o null
 */
function ensorlogs_render_article_podcast_metabox($post): void
{
    if (!$post instanceof WP_Post) {
        return;
    }
    wp_nonce_field('ensor_cpt_meta_save', 'ensor_cpt_meta_nonce');
    $attach_id = absint(get_post_meta($post->ID, '_ensor_podcast_attachment_id', true));
    $src       = (string) get_post_meta($post->ID, '_ensor_podcast_src', true);
    $title     = (string) get_post_meta($post->ID, '_ensor_podcast_title', true);
    $eyebrow   = (string) get_post_meta($post->ID, '_ensor_podcast_eyebrow', true);
    $duration  = (string) get_post_meta($post->ID, '_ensor_podcast_duration', true);
    $chapters  = (string) get_post_meta($post->ID, '_ensor_podcast_chapters', true);
    $guests    = (string) get_post_meta($post->ID, '_ensor_podcast_guests', true);
    $narrator  = (string) get_post_meta($post->ID, '_ensor_podcast_narrator', true);
    ?>
    <div class="ensor-cpt-meta ensor-cpt-meta--podcast">
        <p class="ensor-cpt-meta__help">
            <?php esc_html_e('Cada log puede tener su propio archivo de audio. Súbelo desde la Mediateca (pestaña «Subir archivos») o pega la URL pública del .mp3 / .m4a. La tarjeta con play aparece en la cabecera del log cuando hay audio.', 'ensorlogs'); ?>
        </p>
        <div class="ensor-cpt-meta__section">
            <h4 class="ensor-cpt-meta__title"><?php esc_html_e('Archivo de audio', 'ensorlogs'); ?></h4>
            <input type="hidden" id="ensor_podcast_attachment_id" name="_ensor_podcast_attachment_id" value="<?php echo esc_attr((string) $attach_id); ?>">
            <div class="ensor-cpt-meta__row">
                <label for="ensor_podcast_src"><?php esc_html_e('URL del audio', 'ensorlogs'); ?></label>
                <input type="url" class="large-text" id="ensor_podcast_src" name="_ensor_podcast_src" value="<?php echo esc_attr($src); ?>" placeholder="https://…/tu-log.mp3" autocomplete="off">
                <div class="ensor-cpt-meta__actions">
                    <button type="button" class="button button-primary ensor-cpt-pick-media" data-target="ensor_podcast_src" data-attach-target="ensor_podcast_attachment_id" data-mime="audio" data-title="<?php esc_attr_e('Subir o elegir audio del log', 'ensorlogs'); ?>" data-button="<?php esc_attr_e('Usar este archivo', 'ensorlogs'); ?>">
                        <?php esc_html_e('Subir o elegir desde la Mediateca', 'ensorlogs'); ?>
                    </button>
                    <button type="button" class="button ensor-cpt-clear-podcast-audio">
                        <?php esc_html_e('Quitar audio', 'ensorlogs'); ?>
                    </button>
                </div>
                <p class="description"><?php esc_html_e('Al elegir un archivo en la Mediateca se guarda el enlace y el ID del adjunto. Si editas la URL a mano, se desvincula el adjunto.', 'ensorlogs'); ?></p>
                <div class="ensor-cpt-meta__preview ensor-cpt-meta__preview--audio" aria-hidden="true">
                    <?php
                    $pv = trim($src);
                    if ($pv !== '') {
                        echo '<audio controls preload="none" src="' . esc_url($pv) . '"></audio>';
                    }
                    ?>
                </div>
            </div>
            <div class="ensor-cpt-meta__row">
                <label for="ensor_podcast_title"><?php esc_html_e('Título visible en la card', 'ensorlogs'); ?></label>
                <input type="text" class="large-text" id="ensor_podcast_title" name="_ensor_podcast_title" value="<?php echo esc_attr($title); ?>" placeholder="<?php esc_attr_e('Escúchame contarte este log', 'ensorlogs'); ?>">
                <p class="description"><?php esc_html_e('Vacío = se usa el default de marca «Escúchame contarte este log».', 'ensorlogs'); ?></p>
            </div>
            <div class="ensor-cpt-meta__row">
                <label for="ensor_podcast_eyebrow"><?php esc_html_e('Etiqueta superior (eyebrow)', 'ensorlogs'); ?></label>
                <input type="text" class="large-text" id="ensor_podcast_eyebrow" name="_ensor_podcast_eyebrow" value="<?php echo esc_attr($eyebrow); ?>" placeholder="LOG.MP3 · DIRECTOR'S CUT">
                <p class="description"><?php esc_html_e('Vacío = «LOG.MP3 · DIRECTOR’S CUT» (o «LOG.MP3 · CON INVITADO» si hay invitados).', 'ensorlogs'); ?></p>
            </div>
            <div class="ensor-cpt-meta__row">
                <label for="ensor_podcast_duration"><?php esc_html_e('Duración (mm:ss)', 'ensorlogs'); ?></label>
                <input type="text" class="regular-text" id="ensor_podcast_duration" name="_ensor_podcast_duration" value="<?php echo esc_attr($duration); ?>" placeholder="12:34">
            </div>
            <div class="ensor-cpt-meta__row">
                <label for="ensor_podcast_narrator"><?php esc_html_e('Narrador (línea inferior)', 'ensorlogs'); ?></label>
                <input type="text" class="regular-text" id="ensor_podcast_narrator" name="_ensor_podcast_narrator" value="<?php echo esc_attr($narrator); ?>" placeholder="<?php esc_attr_e('Ensor', 'ensorlogs'); ?>">
                <p class="description"><?php esc_html_e('Vacío = se muestra «narrado por Ensor».', 'ensorlogs'); ?></p>
            </div>
        </div>
        <div class="ensor-cpt-meta__section">
            <h4 class="ensor-cpt-meta__title"><?php esc_html_e('Capítulos', 'ensorlogs'); ?></h4>
            <div class="ensor-cpt-meta__row">
                <p class="description"><?php esc_html_e('Una línea por capítulo. Formato: «mm:ss Título» (o «hh:mm:ss»).', 'ensorlogs'); ?></p>
                <textarea id="ensor_podcast_chapters" name="_ensor_podcast_chapters" rows="6" class="large-text code" placeholder="0:00 Intro&#10;1:20 Contexto&#10;3:40 Datos"><?php echo esc_textarea($chapters); ?></textarea>
            </div>
        </div>
        <div class="ensor-cpt-meta__section">
            <h4 class="ensor-cpt-meta__title"><?php esc_html_e('Invitados (opcional)', 'ensorlogs'); ?></h4>
            <div class="ensor-cpt-meta__row">
                <p class="description"><?php esc_html_e('Una línea por invitado, ej.: «Nombre · Rol».', 'ensorlogs'); ?></p>
                <textarea id="ensor_podcast_guests" name="_ensor_podcast_guests" rows="3" class="large-text"><?php echo esc_textarea($guests); ?></textarea>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Parser del textarea del quiz. Formato esperado (bloques separados por
 * líneas con tres guiones `---`):
 *
 *     Q: ¿Pregunta?
 *     A: Opción 1
 *     B: Opción 2 *
 *     C: Opción 3
 *     D: Opción 4
 *     E: Explicación cuando se acierta.
 *
 * La opción correcta se marca con un asterisco final (` *`). El asterisco
 * es opcional si se prefiere marcar con `*:` al principio.
 *
 * Devuelve un array de preguntas listo para JSON.
 *
 * @return list<array{q:string,options:list<string>,correct:int,explanation:string}>
 */
function ensorlogs_parse_quiz_textarea(string $raw): array
{
    $raw    = wp_strip_all_tags($raw);
    $blocks = preg_split('/^\s*---\s*$/m', $raw) ?: array();
    $out    = array();
    foreach ($blocks as $block) {
        $lines = preg_split('/\r\n|\r|\n/', $block) ?: array();
        $q          = '';
        $options    = array();
        $correctIdx = -1;
        $explanation = '';
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if (preg_match('/^Q:\s*(.+)$/iu', $line, $m)) {
                $q = $m[1];
            } elseif (preg_match('/^([A-Ja-j]):\s*(.+)$/u', $line, $m)) {
                $text = $m[2];
                if (substr($text, -1) === '*') {
                    $correctIdx = count($options);
                    $text = rtrim(rtrim($text, '*'));
                }
                $options[] = $text;
            } elseif (preg_match('/^E:\s*(.+)$/iu', $line, $m)) {
                $explanation = $m[1];
            }
        }
        if ($q !== '' && count($options) >= 2) {
            if ($correctIdx < 0) {
                $correctIdx = 0;
            }
            $out[] = array(
                'q'           => $q,
                'options'     => $options,
                'correct'     => $correctIdx,
                'explanation' => $explanation,
            );
        }
    }
    return $out;
}

/**
 * Devuelve el HTML del badge "Pendiente · quiz al final" para inyectar
 * en la cabecera del log si hay quiz definido.
 */
function ensorlogs_render_quiz_status_badge(int $post_id): string
{
    $raw = (string) get_post_meta($post_id, '_ensor_quiz', true);
    if ($raw === '') {
        return '';
    }
    if (!ensorlogs_parse_quiz_textarea($raw)) {
        return '';
    }
    $slug = (string) get_post_field('post_name', $post_id);
    return '<span class="ensor-log-status" data-slug="' . esc_attr($slug) . '" aria-live="polite">'
        . '<span class="ensor-log-status__dot" aria-hidden="true"></span>'
        . '<span class="ensor-log-status__label">' . esc_html__('Pendiente · quiz al final', 'ensorlogs') . '</span>'
        . '</span>';
}

/**
 * Devuelve el HTML de la sección <section class="ensor-quiz"> para inyectar
 * al final del cuerpo del log. Cadena vacía si no hay quiz configurado.
 */
function ensorlogs_render_quiz_section(int $post_id): string
{
    $raw  = (string) get_post_meta($post_id, '_ensor_quiz', true);
    if ($raw === '') {
        return '';
    }
    $questions = ensorlogs_parse_quiz_textarea($raw);
    if (!$questions) {
        return '';
    }
    $slug    = (string) get_post_field('post_name', $post_id);
    $payload = wp_json_encode(array('questions' => $questions));
    $quiz_id = 'ensor-quiz-' . sanitize_title($slug);
    return '<section class="ensor-quiz" id="' . esc_attr($quiz_id) . '" data-slug="' . esc_attr($slug) . '" '
        . 'data-quiz="' . esc_attr($payload) . '"></section>';
}

/**
 * @param mixed $post WP_Post o null
 */
function ensorlogs_render_article_quiz_metabox($post): void
{
    if (!$post instanceof WP_Post) {
        return;
    }
    wp_nonce_field('ensor_cpt_meta_save', 'ensor_cpt_meta_nonce');
    $raw = (string) get_post_meta($post->ID, '_ensor_quiz', true);
    ?>
    <div class="ensor-cpt-meta">
        <p class="ensor-cpt-meta__help">
            <?php esc_html_e('Añade un quiz al final del log para que la lectora o el lector pueda comprobar que entendió. Cada pregunta es un bloque; los bloques se separan con una línea «---».', 'ensorlogs'); ?>
        </p>
        <div class="ensor-cpt-meta__section">
            <h4 class="ensor-cpt-meta__title"><?php esc_html_e('Formato', 'ensorlogs'); ?></h4>
            <ul style="margin-left:1rem;list-style:disc;font-size:13px;line-height:1.55;">
                <li><code>Q:</code> <?php esc_html_e('pregunta', 'ensorlogs'); ?></li>
                <li><code>A:</code> <code>B:</code> <code>C:</code> <code>D:</code>… <?php esc_html_e('opciones (mínimo 2). Marca la correcta con un asterisco al final.', 'ensorlogs'); ?></li>
                <li><code>E:</code> <?php esc_html_e('explicación opcional que se muestra al verificar.', 'ensorlogs'); ?></li>
                <li><code>---</code> <?php esc_html_e('separador entre preguntas.', 'ensorlogs'); ?></li>
            </ul>
        </div>
        <div class="ensor-cpt-meta__section">
            <h4 class="ensor-cpt-meta__title"><?php esc_html_e('Preguntas', 'ensorlogs'); ?></h4>
            <div class="ensor-cpt-meta__row">
                <textarea id="ensor_quiz" name="_ensor_quiz" rows="18" class="large-text code" placeholder="Q: ¿Pregunta?&#10;A: Opción 1&#10;B: Opción 2 *&#10;C: Opción 3&#10;D: Opción 4&#10;E: Por qué la B es correcta.&#10;---&#10;Q: ¿Otra pregunta?&#10;A: ..."><?php echo esc_textarea($raw); ?></textarea>
            </div>
        </div>
        <?php
        $parsed = ensorlogs_parse_quiz_textarea($raw);
        if ($parsed) :
            ?>
            <p style="font-size:12px;color:#388b3a;">
                <?php echo esc_html(sprintf(_n('Detectada %d pregunta válida.', 'Detectadas %d preguntas válidas.', count($parsed), 'ensorlogs'), count($parsed))); ?>
            </p>
            <?php
        elseif ($raw !== '') :
            ?>
            <p style="font-size:12px;color:#b32d2e;">
                <?php esc_html_e('Aún no detecto preguntas válidas. Recuerda separar con «---» y marcar la opción correcta con un asterisco al final.', 'ensorlogs'); ?>
            </p>
            <?php
        endif;
        ?>
    </div>
    <?php
}

/**
 * Renderiza un metabox WYSIWYG para una sección pedagógica del log.
 * Cada sección (Contexto, Datos, Como estudiante, …) tiene su propio editor
 * TinyMCE con «Añadir medios», ayuda contextual y un prompt sugerido de IA.
 *
 * @param mixed  $post     WP_Post o null.
 * @param string $sec_key  Clave de la sección (context, data, student, …).
 */
function ensorlogs_render_article_section_metabox($post, string $sec_key): void
{
    if (!$post instanceof WP_Post) {
        return;
    }
    $sections = ensorlogs_article_sections();
    if (!isset($sections[$sec_key])) {
        return;
    }
    $sec       = $sections[$sec_key];
    $meta_key  = '_ensor_section_' . $sec_key;
    $value     = (string) get_post_meta($post->ID, $meta_key, true);
    $editor_id = 'ensor_section_' . $sec_key;
    // Solo emitimos el nonce una vez por pantalla; el resto de metaboxes ya lo
    // declararon, así que comprobamos primero si ya existe en la página.
    static $nonce_done = false;
    if (!$nonce_done) {
        wp_nonce_field('ensor_cpt_meta_save', 'ensor_cpt_meta_nonce');
        $nonce_done = true;
    }
    ?>
    <div class="ensor-cpt-meta ensor-cpt-section">
        <p class="ensor-cpt-meta__help">
            <strong><?php echo esc_html($sec['label']); ?></strong> ·
            <?php echo esc_html($sec['help']); ?>
        </p>
        <p class="ensor-cpt-meta__help" style="background:#fffaf0;border-left:3px solid #d9a300;padding:0.6rem 0.8rem;border-radius:4px;">
            <span style="font-weight:600;text-transform:uppercase;font-size:11px;letter-spacing:.04em;color:#7a5b00;display:block;margin-bottom:0.25rem;">
                <?php esc_html_e('Idea de prompt para IA', 'ensorlogs'); ?>
            </span>
            <code style="background:transparent;padding:0;font-size:12px;"><?php echo esc_html($sec['prompt']); ?></code>
        </p>
        <?php
        wp_editor(
            $value,
            $editor_id,
            array(
                'textarea_name' => $meta_key,
                'textarea_rows' => 10,
                'media_buttons' => true,
                'teeny'         => false,
                'tinymce'       => array(
                    'wpautop'           => true,
                    'block_formats'     => 'Párrafo=p;Cita=blockquote;Encabezado 1=h1;Encabezado 3=h3;Encabezado 4=h4;Código=pre',
                    'toolbar1'          => 'formatselect,bold,italic,bullist,numlist,blockquote,alignleft,aligncenter,link,unlink,wp_more,spellchecker,wp_add_media,fullscreen,wp_adv',
                    'toolbar2'          => 'underline,strikethrough,hr,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help',
                ),
                'quicktags'     => array('buttons' => 'strong,em,link,block,del,ins,ul,ol,li,code,more,close'),
                'editor_class'  => 'ensor-section-editor ensor-section-editor--' . esc_attr($sec_key),
            )
        );
        ?>
        <p class="description" style="margin-top:0.6rem;">
            <?php
            printf(
                /* translators: %s: data-aud value, e.g. "context" */
                esc_html__('Si dejas este campo vacío, no se añade nada al log. Si tienes texto, se inyecta automáticamente al final del contenido con la marca data-aud="%s" para que aparezca el filtro de navegación de arriba.', 'ensorlogs'),
                esc_html($sec['aud'])
            );
            ?>
        </p>
    </div>
    <?php
}

/**
 * @param mixed $post WP_Post o null
 */
function ensorlogs_render_project_listing_metabox($post): void
{
    if (!$post instanceof WP_Post) {
        return;
    }
    wp_nonce_field('ensor_cpt_meta_save', 'ensor_cpt_meta_nonce');
    $sub       = (string) get_post_meta($post->ID, '_ensor_subtitle', true);
    $list_t    = (string) get_post_meta($post->ID, '_ensor_list_title', true);
    $img_rel   = (string) get_post_meta($post->ID, '_ensor_img_rel', true);
    $iclass    = (string) get_post_meta($post->ID, '_ensor_item_class', true);
    $temas     = (string) get_post_meta($post->ID, '_ensor_temas', true);
    $tags_raw  = (string) get_post_meta($post->ID, '_ensor_tag_slugs', true);
    $tags_list = '';
    $decoded   = json_decode($tags_raw, true);
    if (is_array($decoded)) {
        $tags_list = implode(', ', $decoded);
    }
    if ($iclass === '') {
        $iclass = 'project-item group';
    }
    ?>
    <div class="ensor-cpt-meta">
        <p class="ensor-cpt-meta__help">
            <?php esc_html_e('Estos datos pintan la tarjeta en «Proyectos» (imagen ancha, rúbrica, chips de stack y título). El relato completo va en el editor de bloques.', 'ensorlogs'); ?>
        </p>
        <div class="ensor-cpt-meta__section">
            <h4 class="ensor-cpt-meta__title"><?php esc_html_e('Rúbrica', 'ensorlogs'); ?></h4>
            <div class="ensor-cpt-meta__row">
                <label for="ensor_subtitle"><?php esc_html_e('Línea pequeña sobre el título', 'ensorlogs'); ?></label>
                <input type="text" class="large-text" id="ensor_subtitle" name="_ensor_subtitle" value="<?php echo esc_attr($sub); ?>">
            </div>
        </div>
        <div class="ensor-cpt-meta__section">
            <h4 class="ensor-cpt-meta__title"><?php esc_html_e('Título en la tarjeta', 'ensorlogs'); ?></h4>
            <div class="ensor-cpt-meta__row">
                <label for="ensor_list_title"><?php esc_html_e('Puede ser más largo que el título SEO de la página', 'ensorlogs'); ?></label>
                <input type="text" class="large-text" id="ensor_list_title" name="_ensor_list_title" value="<?php echo esc_attr($list_t); ?>">
            </div>
        </div>
        <div class="ensor-cpt-meta__section">
            <h4 class="ensor-cpt-meta__title"><?php esc_html_e('Imagen de la tarjeta', 'ensorlogs'); ?></h4>
            <div class="ensor-cpt-meta__row">
                <label for="ensor_img_rel"><?php esc_html_e('URL completa o ruta del tema (assets/...)', 'ensorlogs'); ?></label>
                <input type="text" class="large-text ensor-cpt-meta__img-field" id="ensor_img_rel" name="_ensor_img_rel" value="<?php echo esc_attr($img_rel); ?>" placeholder="assets/img/projects/img1.png">
                <div class="ensor-cpt-meta__actions">
                    <button type="button" class="button ensor-cpt-pick-media" data-target="ensor_img_rel" data-title="<?php esc_attr_e('Imagen del proyecto', 'ensorlogs'); ?>" data-button="<?php esc_attr_e('Usar esta imagen', 'ensorlogs'); ?>">
                        <?php esc_html_e('Elegir de la biblioteca', 'ensorlogs'); ?>
                    </button>
                </div>
                <div class="ensor-cpt-meta__preview" aria-hidden="true">
                    <?php
                    $pv = ensorlogs_resolve_public_asset_url($img_rel);
                    if ($pv !== '') {
                        echo '<img src="' . esc_url($pv) . '" alt="">';
                    }
                    ?>
                </div>
                <p class="description"><?php esc_html_e('Si el proyecto tiene imagen destacada (panel lateral), esa es la que verás en «Proyectos». Este campo solo se usa cuando no hay destacada: URL de la biblioteca o ruta del tema como assets/img/projects/img1.png.', 'ensorlogs'); ?></p>
            </div>
        </div>
        <div class="ensor-cpt-meta__section">
            <h4 class="ensor-cpt-meta__title"><?php esc_html_e('Ancho en la cuadrícula', 'ensorlogs'); ?></h4>
            <div class="ensor-cpt-meta__row">
                <label for="ensor_item_class"><?php esc_html_e('Layout', 'ensorlogs'); ?></label>
                <select id="ensor_item_class" name="_ensor_item_class" class="large-text">
                    <option value="project-item group" <?php selected($iclass, 'project-item group'); ?>><?php esc_html_e('Una columna', 'ensorlogs'); ?></option>
                    <option value="project-item group sm:col-span-2" <?php selected($iclass, 'project-item group sm:col-span-2'); ?>><?php esc_html_e('Ancho doble (primera fila)', 'ensorlogs'); ?></option>
                </select>
            </div>
        </div>
        <div class="ensor-cpt-meta__section">
            <h4 class="ensor-cpt-meta__title"><?php esc_html_e('Stacks del proyecto', 'ensorlogs'); ?></h4>
            <div class="ensor-cpt-meta__row">
                <label for="ensor_proj_temas"><?php esc_html_e('Stacks (slugs separados por espacio)', 'ensorlogs'); ?></label>
                <input type="text" class="large-text" id="ensor_proj_temas" name="_ensor_temas" value="<?php echo esc_attr($temas); ?>">
            </div>
            <div class="ensor-cpt-meta__row">
                <label for="ensor_proj_tags"><?php esc_html_e('Etiquetas de la tarjeta (slugs separados por coma)', 'ensorlogs'); ?></label>
                <input type="text" class="large-text" id="ensor_proj_tags" name="ensor_project_tags_list" value="<?php echo esc_attr($tags_list); ?>" placeholder="wordpress, crm, database">
            </div>
        </div>
    </div>
    <?php
}

add_action(
    'save_post',
    static function (int $post_id, WP_Post $post): void {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!isset($_POST['ensor_cpt_meta_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ensor_cpt_meta_nonce'])), 'ensor_cpt_meta_save')) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        if (!in_array($post->post_type, array('ensor_article', 'ensor_project'), true)) {
            return;
        }
        if ($post->post_type === 'ensor_article') {
            if (isset($_POST['_ensor_card_image'])) {
                update_post_meta(
                    $post_id,
                    '_ensor_card_image',
                    ensorlogs_sanitize_img_src_field((string) wp_unslash($_POST['_ensor_card_image']))
                );
            }
            if (isset($_POST['_ensor_card_excerpt'])) {
                update_post_meta($post_id, '_ensor_card_excerpt', sanitize_textarea_field(wp_unslash((string) $_POST['_ensor_card_excerpt'])));
            }
            if (isset($_POST['_ensor_temas'])) {
                update_post_meta($post_id, '_ensor_temas', ensorlogs_sanitize_temas_string((string) wp_unslash($_POST['_ensor_temas'])));
            }
            if (isset($_POST['_ensor_primary_tema'])) {
                $p = sanitize_key(wp_unslash((string) $_POST['_ensor_primary_tema']));
                if (array_key_exists($p, ensorlogs_primary_tema_choices())) {
                    update_post_meta($post_id, '_ensor_primary_tema', $p);
                }
            }
            $attach_in = isset($_POST['_ensor_podcast_attachment_id']) ? absint(wp_unslash($_POST['_ensor_podcast_attachment_id'])) : null;
            if ($attach_in !== null) {
                if ($attach_in > 0 && ensorlogs_is_audio_attachment_id($attach_in) && current_user_can('edit_post', $attach_in)) {
                    update_post_meta($post_id, '_ensor_podcast_attachment_id', $attach_in);
                    $file_url = wp_get_attachment_url($attach_in);
                    if (is_string($file_url) && $file_url !== '') {
                        update_post_meta($post_id, '_ensor_podcast_src', esc_url_raw($file_url));
                    }
                } else {
                    delete_post_meta($post_id, '_ensor_podcast_attachment_id');
                    if (isset($_POST['_ensor_podcast_src'])) {
                        $src = trim((string) wp_unslash($_POST['_ensor_podcast_src']));
                        update_post_meta($post_id, '_ensor_podcast_src', $src === '' ? '' : esc_url_raw($src));
                    }
                }
            } elseif (isset($_POST['_ensor_podcast_src'])) {
                $src = trim((string) wp_unslash($_POST['_ensor_podcast_src']));
                update_post_meta($post_id, '_ensor_podcast_src', $src === '' ? '' : esc_url_raw($src));
            }
            if (isset($_POST['_ensor_podcast_title'])) {
                update_post_meta($post_id, '_ensor_podcast_title', sanitize_text_field((string) wp_unslash($_POST['_ensor_podcast_title'])));
            }
            if (isset($_POST['_ensor_podcast_eyebrow'])) {
                update_post_meta($post_id, '_ensor_podcast_eyebrow', sanitize_text_field((string) wp_unslash($_POST['_ensor_podcast_eyebrow'])));
            }
            if (isset($_POST['_ensor_podcast_duration'])) {
                $dur = trim((string) wp_unslash($_POST['_ensor_podcast_duration']));
                $dur = preg_replace('/[^0-9:]/', '', $dur) ?? '';
                update_post_meta($post_id, '_ensor_podcast_duration', $dur);
            }
            if (isset($_POST['_ensor_podcast_narrator'])) {
                update_post_meta($post_id, '_ensor_podcast_narrator', sanitize_text_field((string) wp_unslash($_POST['_ensor_podcast_narrator'])));
            }
            if (isset($_POST['_ensor_podcast_chapters'])) {
                update_post_meta(
                    $post_id,
                    '_ensor_podcast_chapters',
                    ensorlogs_sanitize_podcast_chapters((string) wp_unslash($_POST['_ensor_podcast_chapters']))
                );
            }
            if (isset($_POST['_ensor_podcast_guests'])) {
                $g = wp_strip_all_tags((string) wp_unslash($_POST['_ensor_podcast_guests']));
                $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $g) ?: array()));
                update_post_meta($post_id, '_ensor_podcast_guests', implode("\n", $lines));
            }
            if (isset($_POST['_ensor_quiz'])) {
                update_post_meta(
                    $post_id,
                    '_ensor_quiz',
                    wp_strip_all_tags((string) wp_unslash($_POST['_ensor_quiz']))
                );
            }
            // Secciones pedagógicas WYSIWYG: aceptan HTML básico via wp_kses_post.
            foreach (array_keys(ensorlogs_article_sections()) as $sec_key) {
                $meta_key = '_ensor_section_' . $sec_key;
                if (isset($_POST[$meta_key])) {
                    update_post_meta(
                        $post_id,
                        $meta_key,
                        wp_kses_post((string) wp_unslash($_POST[$meta_key]))
                    );
                }
            }
            return;
        }
        if (isset($_POST['_ensor_subtitle'])) {
            update_post_meta($post_id, '_ensor_subtitle', sanitize_text_field(wp_unslash((string) $_POST['_ensor_subtitle'])));
        }
        if (isset($_POST['_ensor_list_title'])) {
            update_post_meta($post_id, '_ensor_list_title', sanitize_text_field(wp_unslash((string) $_POST['_ensor_list_title'])));
        }
        if (isset($_POST['_ensor_img_rel'])) {
            update_post_meta($post_id, '_ensor_img_rel', ensorlogs_sanitize_img_src_field((string) wp_unslash($_POST['_ensor_img_rel'])));
        }
        if (isset($_POST['_ensor_item_class'])) {
            update_post_meta($post_id, '_ensor_item_class', ensorlogs_sanitize_project_item_class((string) wp_unslash($_POST['_ensor_item_class'])));
        }
        if (isset($_POST['_ensor_temas'])) {
            update_post_meta($post_id, '_ensor_temas', ensorlogs_sanitize_temas_string((string) wp_unslash($_POST['_ensor_temas'])));
        }
        if (isset($_POST['ensor_project_tags_list'])) {
            update_post_meta(
                $post_id,
                '_ensor_tag_slugs',
                ensorlogs_sanitize_project_tags_list((string) wp_unslash($_POST['ensor_project_tags_list']))
            );
        }
    },
    10,
    2
);

/**
 * Aviso amigable en el editor de las páginas estructurales (inicio, about,
 * services, projects, blog, contact) explicando que el contenido del editor
 * SUSTITUYE la zona <!-- ensor:editable --> del fragment del tema.
 *
 * Se imprime tanto en el editor clásico (`edit_form_after_title`) como
 * en Gutenberg (vía `admin_notices`, que el bloque "notice" recoge).
 */
function ensorlogs_render_structural_page_notice(WP_Post $post): void
{
    if ($post->post_type !== 'page' || !function_exists('ensorlogs_page_fragments_map')) {
        return;
    }
    $map = ensorlogs_page_fragments_map();
    if (!isset($map[$post->post_name])) {
        return;
    }
    $labels = array(
        'inicio'   => __('la portada (Inicio)', 'ensorlogs'),
        'about'    => __('la página «Sobre mí»', 'ensorlogs'),
        'services' => __('la página «Servicios»', 'ensorlogs'),
        'projects' => __('la cabecera de «Proyectos»', 'ensorlogs'),
        'blog'     => __('la cabecera de «Hablemos de…» (blog)', 'ensorlogs'),
        'contact'  => __('la cabecera del formulario de «Contacto»', 'ensorlogs'),
    );
    $label = $labels[$post->post_name] ?? $post->post_name;
    ?>
    <div class="notice notice-info" style="border-left-color:#d9a300;">
        <p style="margin: .5em 0 0; font-weight: 600;">
            <?php
            printf(
                /* translators: %s: nombre legible de la zona, ej. "la portada (Inicio)". */
                esc_html__('Estás editando %s.', 'ensorlogs'),
                esc_html($label)
            );
            ?>
        </p>
        <p style="margin: .25em 0 .5em; font-size: 13px; line-height: 1.5;">
            <?php esc_html_e('El contenido que escribas aquí sustituye el bloque intro/lead del diseño actual. Si vacías el editor y guardas, la web vuelve al texto por defecto del tema (no se rompe nada).', 'ensorlogs'); ?>
        </p>
    </div>
    <?php
}

// Editor clásico (TinyMCE):
add_action(
    'edit_form_after_title',
    'ensorlogs_render_structural_page_notice'
);

// Gutenberg muestra los admin_notices con su componente "Notices".
add_action(
    'admin_notices',
    static function (): void {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || $screen->base !== 'post' || $screen->post_type !== 'page') {
            return;
        }
        $post = get_post();
        if ($post instanceof WP_Post) {
            ensorlogs_render_structural_page_notice($post);
        }
    }
);

add_action(
    'admin_enqueue_scripts',
    static function (string $hook_suffix): void {
        if (!in_array($hook_suffix, array('post.php', 'post-new.php'), true)) {
            return;
        }
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || !in_array($screen->post_type, array('ensor_article', 'ensor_project'), true)) {
            return;
        }
        wp_enqueue_media();
        wp_enqueue_style(
            'ensor-admin-cpt-meta',
            get_template_directory_uri() . '/assets/css/admin-cpt-meta.css',
            array(),
            ENSORLOGS_THEME_VERSION
        );
        wp_enqueue_script(
            'ensor-admin-cpt-meta',
            get_template_directory_uri() . '/js/admin-cpt-meta.js',
            array('jquery', 'media-editor'),
            ENSORLOGS_THEME_VERSION,
            true
        );
        wp_localize_script(
            'ensor-admin-cpt-meta',
            'ensorAdminCpt',
            array(
                'themeUri' => trailingslashit(get_template_directory_uri()),
            )
        );
    }
);

add_action(
    'enqueue_block_editor_assets',
    static function (): void {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || !in_array((string) $screen->post_type, array('ensor_article', 'ensor_project'), true)) {
            return;
        }
        wp_enqueue_style(
            'ensor-block-editor-chrome',
            get_template_directory_uri() . '/assets/css/block-editor-chrome.css',
            array('wp-edit-blocks'),
            ENSORLOGS_THEME_VERSION
        );
    }
);

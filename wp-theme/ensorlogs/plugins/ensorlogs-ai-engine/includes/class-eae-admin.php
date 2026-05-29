<?php
/**
 * Admin UI for Ensorlogs AI Engine.
 */

if (!defined('ABSPATH')) {
    exit;
}

final class EAE_Admin
{
    private const PANEL_ID = 'ensorlogs_ai_engine_panel';

    /** @var bool */
    private static $panel_rendered = false;

    public static function init(): void
    {
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
        add_action('edit_form_after_title', array(__CLASS__, 'render_panel_after_title'), 5);
        add_action('add_meta_boxes', array(__CLASS__, 'hide_legacy_section_metaboxes'), 100);
        add_filter('script_loader_tag', array(__CLASS__, 'script_loader_tag'), 10, 2);
    }

    /**
     * Evita que optimizadores (p. ej. SiteGround) rompan o difieran el script del panel.
     *
     * @param string $tag
     * @param string $handle
     */
    public static function script_loader_tag(string $tag, string $handle): string
    {
        if ($handle !== 'ensorlogs-ai-engine-editor') {
            return $tag;
        }
        if (strpos($tag, 'data-cfasync') === false) {
            $tag = str_replace('<script ', '<script data-cfasync="false" data-no-optimize="1" ', $tag);
        }
        return $tag;
    }

    /**
     * @return array<string, mixed>
     */
    public static function get_client_config(int $post_id = 0): array
    {
        return array(
            'buildPromptUrl'  => esc_url_raw(rest_url('ensorlogs-ai/v1/build-prompt')),
            'importHtmlUrl'   => esc_url_raw(rest_url('ensorlogs-ai/v1/import-html')),
            'nonce'           => wp_create_nonce('wp_rest'),
            'postId'          => $post_id,
            'isBlockEditor'   => self::post_uses_block_editor($post_id),
            'defaultMessages' => array(
                'buildingPrompt' => __('Montando prompt con el manual editorial…', 'ensorlogs'),
                'promptCopied'   => __('Prompt copiado. Pégalo en ChatGPT, espera el HTML y vuelve aquí.', 'ensorlogs'),
                'importing'      => __('Insertando HTML en el editor…', 'ensorlogs'),
                'importSuccess'  => __('LOG insertado en el editor. Revisa antes de publicar.', 'ensorlogs'),
                'savedReload'    => __('LOG guardado. Si no lo ves, guarda borrador y recarga la página.', 'ensorlogs'),
                'missingTopic'   => __('Escribe el tema del LOG antes de copiar el prompt.', 'ensorlogs'),
                'missingHtml'    => __('Pega el HTML que devolvió ChatGPT.', 'ensorlogs'),
                'copyFailed'     => __('No se pudo copiar al portapapeles. Usa el cuadro de abajo y cópialo a mano (Ctrl+C).', 'ensorlogs'),
            ),
        );
    }

    public static function post_uses_block_editor(int $post_id = 0): bool
    {
        if (!function_exists('use_block_editor_for_post_type')) {
            return false;
        }
        if ($post_id > 0 && function_exists('use_block_editor_for_post')) {
            return (bool) use_block_editor_for_post($post_id);
        }
        return (bool) use_block_editor_for_post_type('ensor_article');
    }

    /**
     * Justo después del título y antes de «Añadir medios» / el editor (editor clásico).
     *
     * @param WP_Post $post
     */
    public static function render_panel_after_title($post): void
    {
        if (self::$panel_rendered) {
            return;
        }
        if (!$post instanceof WP_Post || $post->post_type !== 'ensor_article') {
            return;
        }
        if (!current_user_can('edit_posts')) {
            return;
        }
        if ($post->ID > 0 && !current_user_can('edit_post', $post->ID)) {
            return;
        }
        self::$panel_rendered = true;
        ?>
        <div id="<?php echo esc_attr(self::PANEL_ID); ?>" class="postbox eae-panel-postbox">
            <div class="postbox-header">
                <h2 class="hndle ui-sortable-handle">
                    <span><?php esc_html_e('ENSORLOGS — Brief para ChatGPT', 'ensorlogs'); ?></span>
                </h2>
            </div>
            <div class="inside">
                <?php self::render_panel_fields($post); ?>
            </div>
        </div>
        <?php
    }

    public static function hide_legacy_section_metaboxes(): void
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || $screen->post_type !== 'ensor_article') {
            return;
        }

        // Secciones pedagógicas duplicadas (la IA rellena el editor principal).
        // El quiz sigue en su metabox para editarlo a mano o revisar lo que generó la IA.
        $legacy = array(
            'ensor_article_section_context',
            'ensor_article_section_data',
            'ensor_article_section_student',
            'ensor_article_section_teacher',
            'ensor_article_section_professional',
        );
        foreach ($legacy as $id) {
            remove_meta_box($id, 'ensor_article', 'normal');
            remove_meta_box($id, 'ensor_article', 'advanced');
            remove_meta_box($id, 'ensor_article', 'side');
        }
    }

    public static function enqueue_assets(string $hook): void
    {
        if (!in_array($hook, array('post.php', 'post-new.php'), true)) {
            return;
        }
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || $screen->post_type !== 'ensor_article') {
            return;
        }

        $style_deps = array('wp-admin', 'common');
        if (wp_style_is('ensor-admin-cpt-meta', 'registered')) {
            $style_deps[] = 'ensor-admin-cpt-meta';
        }

        wp_enqueue_style(
            'ensorlogs-ai-engine-admin',
            ENSORLOGS_AI_ENGINE_URL . 'assets/css/eae-admin.css',
            $style_deps,
            ENSORLOGS_AI_ENGINE_VERSION
        );

        wp_enqueue_script(
            'ensorlogs-ai-engine-editor',
            ENSORLOGS_AI_ENGINE_URL . 'assets/js/eae-editor.js',
            array(),
            ENSORLOGS_AI_ENGINE_VERSION,
            true
        );

        $post_id = isset($_GET['post']) ? absint($_GET['post']) : 0;
        $cfg     = self::get_client_config($post_id);
        wp_localize_script('ensorlogs-ai-engine-editor', 'EAE_CFG', $cfg);
        wp_add_inline_script(
            'ensorlogs-ai-engine-editor',
            'window.EAE_CFG=Object.assign(window.EAE_CFG||{},' . wp_json_encode($cfg) . ');',
            'before'
        );
    }

    /**
     * @param WP_Post $post
     */
    public static function render_panel_fields($post): void
    {
        $stacks = self::get_stack_choices();
        $selected = self::get_selected_stack_slugs((int) $post->ID);
        ?>
        <div class="ensor-cpt-meta eae-panel-wrap">
            <p class="eae-intro">
                <?php esc_html_e('1) Completa el brief. 2) Copia el prompt y pégalo en ChatGPT. 3) Pega el HTML abajo e insértalo en el editor. El quiz lo editas aparte en «Quiz de comprensión del log».', 'ensorlogs'); ?>
            </p>
            <ol class="eae-steps" aria-label="<?php esc_attr_e('Pasos con ChatGPT', 'ensorlogs'); ?>">
                <li><?php esc_html_e('Brief en WordPress', 'ensorlogs'); ?></li>
                <li><?php esc_html_e('ChatGPT genera el HTML', 'ensorlogs'); ?></li>
                <li><?php esc_html_e('Pegar HTML aquí → editor', 'ensorlogs'); ?></li>
            </ol>

            <div class="ensor-cpt-meta__section">
                <h3 class="ensor-cpt-meta__title"><?php esc_html_e('Brief del log', 'ensorlogs'); ?></h3>
                <div class="ensor-cpt-meta__row">
                    <label for="eae-topic"><?php esc_html_e('¿Qué quieres hablar en este LOG?', 'ensorlogs'); ?></label>
                    <input type="text" class="large-text" id="eae-topic" maxlength="220" placeholder="<?php esc_attr_e('Ej: WordPress en 2026 para estudiantes venezolanos', 'ensorlogs'); ?>" />
                </div>
                <div class="ensor-cpt-meta__row">
                    <label for="eae-context"><?php esc_html_e('Contexto o enfoque', 'ensorlogs'); ?></label>
                    <textarea id="eae-context" class="large-text" rows="4" placeholder="<?php esc_attr_e('Situación actual, por qué importa ahora, qué quieres dejar claro.', 'ensorlogs'); ?>"></textarea>
                </div>
                <div class="ensor-cpt-meta__row">
                    <label for="eae-experience"><?php esc_html_e('Experiencia personal (opcional)', 'ensorlogs'); ?></label>
                    <textarea id="eae-experience" class="large-text" rows="3" placeholder="<?php esc_attr_e('Anécdota real, error que cometiste, aprendizaje en el camino.', 'ensorlogs'); ?>"></textarea>
                </div>
                <div class="ensor-cpt-meta__row">
                    <label for="eae-teach"><?php esc_html_e('¿Qué quieres enseñar?', 'ensorlogs'); ?></label>
                    <textarea id="eae-teach" class="large-text" rows="4" placeholder="<?php esc_attr_e('Qué debe llevarse el lector al final del log.', 'ensorlogs'); ?>"></textarea>
                </div>
            </div>

            <div class="ensor-cpt-meta__section">
                <h3 class="ensor-cpt-meta__title"><?php esc_html_e('Stack (categorías)', 'ensorlogs'); ?></h3>
                <p class="description"><?php esc_html_e('Se asignan al taxonomía Stacks del sitio (ensor_tema), igual que la tarjeta del listado.', 'ensorlogs'); ?></p>
                <?php if ($stacks) : ?>
                    <div class="eae-stack-grid" id="eae-stacks">
                        <?php foreach ($stacks as $slug => $label) : ?>
                            <label class="eae-stack-item">
                                <input
                                    type="checkbox"
                                    name="eae_stack[]"
                                    value="<?php echo esc_attr($slug); ?>"
                                    <?php checked(in_array($slug, $selected, true)); ?>
                                />
                                <span><?php echo esc_html($label); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <p class="description"><?php esc_html_e('No hay stacks registrados. Crea términos en Logs → Stacks.', 'ensorlogs'); ?></p>
                <?php endif; ?>
            </div>

            <div class="ensor-cpt-meta__section eae-section-prompt">
                <h3 class="ensor-cpt-meta__title"><?php esc_html_e('Paso 2 — Prompt para ChatGPT', 'ensorlogs'); ?></h3>
                <p class="eae-actions">
                    <button type="button" class="button button-primary button-hero" id="eae-copy-prompt">
                        <?php esc_html_e('Copiar prompt para ChatGPT', 'ensorlogs'); ?>
                    </button>
                    <button type="button" class="button" id="eae-toggle-prompt" aria-expanded="false" aria-controls="eae-prompt-preview">
                        <?php esc_html_e('Ver prompt', 'ensorlogs'); ?>
                    </button>
                </p>
                <div class="eae-prompt-preview-wrap" id="eae-prompt-preview-wrap" hidden>
                    <label for="eae-prompt-preview" class="screen-reader-text"><?php esc_html_e('Vista previa del prompt', 'ensorlogs'); ?></label>
                    <textarea id="eae-prompt-preview" class="large-text code" rows="12" readonly placeholder="<?php esc_attr_e('El prompt aparecerá aquí al pulsar «Copiar prompt» o «Ver prompt».', 'ensorlogs'); ?>"></textarea>
                </div>
            </div>

            <div class="ensor-cpt-meta__section eae-section-import">
                <h3 class="ensor-cpt-meta__title"><?php esc_html_e('Paso 3 — Respuesta de ChatGPT', 'ensorlogs'); ?></h3>
                <div class="ensor-cpt-meta__row">
                    <label for="eae-html-paste"><?php esc_html_e('Pega aquí el HTML del log', 'ensorlogs'); ?></label>
                    <textarea id="eae-html-paste" class="large-text code" rows="10" placeholder="<?php esc_attr_e('Pega solo el HTML que devolvió ChatGPT (sin explicaciones).', 'ensorlogs'); ?>"></textarea>
                </div>
                <p class="eae-actions">
                    <button type="button" class="button button-secondary button-hero" id="eae-import-html">
                        <?php esc_html_e('Insertar HTML en el editor', 'ensorlogs'); ?>
                    </button>
                    <span class="eae-status" id="eae-status" role="status" aria-live="polite"></span>
                </p>
            </div>
        </div>
        <?php
        if (function_exists('eae_print_inline_generator_script')) {
            eae_print_inline_generator_script(self::get_client_config((int) $post->ID));
        }
    }

    /**
     * @return array<string, string>
     */
    private static function get_stack_choices(): array
    {
        if (!taxonomy_exists('ensor_tema')) {
            return array();
        }
        $terms = get_terms(
            array(
                'taxonomy'   => 'ensor_tema',
                'hide_empty' => false,
            )
        );
        if (is_wp_error($terms) || !is_array($terms)) {
            return array();
        }
        $choices = array();
        foreach ($terms as $term) {
            if ($term instanceof WP_Term) {
                $choices[$term->slug] = $term->name;
            }
        }
        return $choices;
    }

    /**
     * @return list<string>
     */
    private static function get_selected_stack_slugs(int $post_id): array
    {
        if ($post_id <= 0) {
            return array();
        }
        $slugs = wp_get_object_terms($post_id, 'ensor_tema', array('fields' => 'slugs'));
        if (is_wp_error($slugs) || !is_array($slugs)) {
            $meta = trim((string) get_post_meta($post_id, '_ensor_temas', true));
            if ($meta === '') {
                return array();
            }
            return array_values(array_filter(array_map('sanitize_title', preg_split('/\s+/', $meta) ?: array())));
        }
        return array_values(array_map('sanitize_title', $slugs));
    }
}

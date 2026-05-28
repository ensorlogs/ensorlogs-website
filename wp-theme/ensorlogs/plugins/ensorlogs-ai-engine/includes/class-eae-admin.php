<?php
/**
 * Admin UI for Ensorlogs AI Engine.
 */

if (!defined('ABSPATH')) {
    exit;
}

final class EAE_Admin
{
    private const OPTION_API_KEY = 'ensorlogs_ai_openai_api_key';
    private const OPTION_MODEL = 'ensorlogs_ai_openai_model';
    private const PANEL_ID = 'ensorlogs_ai_engine_panel';

    /** @var bool */
    private static $panel_rendered = false;

    public static function init(): void
    {
        add_action('admin_menu', array(__CLASS__, 'register_settings_page'));
        if (did_action('admin_init')) {
            self::register_settings();
        } else {
            add_action('admin_init', array(__CLASS__, 'register_settings'));
        }
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
            'restUrl'         => esc_url_raw(rest_url('ensorlogs-ai/v1/generate-log')),
            'nonce'           => wp_create_nonce('wp_rest'),
            'postId'          => $post_id,
            'apiConfigured'   => ((string) get_option(self::OPTION_API_KEY, '')) !== '',
            'apiSettingsUrl'  => esc_url_raw(admin_url('options-general.php?page=ensorlogs-ai-engine')),
            'isBlockEditor'   => self::post_uses_block_editor($post_id),
            'defaultMessages' => array(
                'working'       => __('Generando log con ENSORLOGS AI ENGINE…', 'ensorlogs'),
                'success'       => __('LOG generado e insertado en el editor.', 'ensorlogs'),
                'savedReload'   => __('LOG guardado. Si no lo ves, guarda borrador y recarga la página.', 'ensorlogs'),
                'missingApiKey' => __('Falta API Key de OpenAI. Configúrala en Ajustes > Ensorlogs AI Engine.', 'ensorlogs'),
                'missingTopic'  => __('Escribe el tema del LOG antes de generar.', 'ensorlogs'),
            ),
        );
    }

    public static function register_settings_page(): void
    {
        add_options_page(
            __('Ensorlogs AI Engine', 'ensorlogs'),
            __('Ensorlogs AI Engine', 'ensorlogs'),
            'manage_options',
            'ensorlogs-ai-engine',
            array(__CLASS__, 'render_settings_page')
        );
    }

    public static function register_settings(): void
    {
        register_setting(
            'ensorlogs_ai_engine',
            self::OPTION_API_KEY,
            array(
                'type'              => 'string',
                'sanitize_callback' => array(__CLASS__, 'sanitize_api_key'),
                'default'           => '',
                'show_in_rest'      => false,
            )
        );

        register_setting(
            'ensorlogs_ai_engine',
            self::OPTION_MODEL,
            array(
                'type'              => 'string',
                'sanitize_callback' => static function ($value): string {
                    $allowed = array('gpt-5.5', 'gpt-4.1', 'gpt-4o', 'gpt-4o-mini');
                    $value   = sanitize_text_field((string) $value);
                    return in_array($value, $allowed, true) ? $value : 'gpt-4o';
                },
                'default'           => 'gpt-4o',
                'show_in_rest'      => false,
            )
        );
    }

    public static function sanitize_api_key($value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            $stored = get_option(self::OPTION_API_KEY, '');
            return is_string($stored) ? $stored : '';
        }
        return sanitize_text_field($value);
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
        if (!current_user_can('edit_post', $post->ID)) {
            return;
        }
        self::$panel_rendered = true;
        ?>
        <div id="<?php echo esc_attr(self::PANEL_ID); ?>" class="postbox eae-panel-postbox">
            <div class="postbox-header">
                <h2 class="hndle ui-sortable-handle">
                    <span><?php esc_html_e('ENSORLOGS AI ENGINE', 'ensorlogs'); ?></span>
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

    public static function render_settings_page(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        $stored_key = (string) get_option(self::OPTION_API_KEY, '');
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Ensorlogs AI Engine', 'ensorlogs'); ?></h1>
            <p><?php esc_html_e('La API Key conecta tu cuenta de OpenAI (facturación y límites en tu panel). En cada generación enviamos el manual editorial EnsorLogs completo más el brief del log, para que el HTML respete tono, secciones y estructura del sitio.', 'ensorlogs'); ?></p>
            <form method="post" action="options.php">
                <?php settings_fields('ensorlogs_ai_engine'); ?>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="ensorlogs-ai-openai-key"><?php esc_html_e('OpenAI API Key', 'ensorlogs'); ?></label>
                            </th>
                            <td>
                                <input
                                    id="ensorlogs-ai-openai-key"
                                    name="<?php echo esc_attr(self::OPTION_API_KEY); ?>"
                                    type="password"
                                    class="regular-text"
                                    autocomplete="off"
                                    spellcheck="false"
                                    placeholder="sk-..."
                                />
                                <p class="description">
                                    <?php
                                    echo $stored_key !== ''
                                        ? esc_html__('Ya hay una clave guardada. Si dejas el campo vacío y guardas, se mantiene.', 'ensorlogs')
                                        : esc_html__('Pega la API key aquí. Este campo no muestra la clave guardada por seguridad.', 'ensorlogs');
                                    ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="ensorlogs-ai-openai-model"><?php esc_html_e('Modelo', 'ensorlogs'); ?></label>
                            </th>
                            <td>
                                <?php $model = (string) get_option(self::OPTION_MODEL, 'gpt-4o'); ?>
                                <select id="ensorlogs-ai-openai-model" name="<?php echo esc_attr(self::OPTION_MODEL); ?>">
                                    <option value="gpt-4o" <?php selected($model, 'gpt-4o'); ?>>gpt-4o (recomendado)</option>
                                    <option value="gpt-4o-mini" <?php selected($model, 'gpt-4o-mini'); ?>>gpt-4o-mini</option>
                                    <option value="gpt-4.1" <?php selected($model, 'gpt-4.1'); ?>>gpt-4.1</option>
                                    <option value="gpt-5.5" <?php selected($model, 'gpt-5.5'); ?>>gpt-5.5 → gpt-4o</option>
                                </select>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <?php submit_button(__('Guardar configuración', 'ensorlogs')); ?>
            </form>
        </div>
        <?php
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
                <?php esc_html_e('Completa el brief y pulsa generar: el contenido aparecerá en el editor de abajo (párrafos y secciones) para que lo revises antes de publicar. El quiz de comprensión se edita en la caja «Quiz de comprensión del log» más abajo (también se rellena al generar si la IA lo incluye).', 'ensorlogs'); ?>
            </p>

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

            <p class="eae-actions">
                <button type="button" class="button button-primary button-hero" id="eae-generate">
                    <?php esc_html_e('GENERAR LOG ENSORLOGS', 'ensorlogs'); ?>
                </button>
                <span class="eae-status" id="eae-status" role="status" aria-live="polite"></span>
            </p>
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

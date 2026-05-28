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
    private const META_BOX_ID = 'ensorlogs_ai_engine_panel';

    public static function init(): void
    {
        add_action('admin_menu', array(__CLASS__, 'register_settings_page'));
        add_action('admin_init', array(__CLASS__, 'register_settings'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
        add_action('add_meta_boxes', array(__CLASS__, 'register_meta_box'), 5);
        add_action('add_meta_boxes', array(__CLASS__, 'hide_legacy_section_metaboxes'), 100);
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
                    $allowed = array('gpt-5.5', 'gpt-4.1');
                    $value   = sanitize_text_field((string) $value);
                    return in_array($value, $allowed, true) ? $value : 'gpt-5.5';
                },
                'default'           => 'gpt-5.5',
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

    public static function register_meta_box(): void
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || $screen->post_type !== 'ensor_article') {
            return;
        }

        add_meta_box(
            self::META_BOX_ID,
            __('ENSORLOGS AI ENGINE', 'ensorlogs'),
            array(__CLASS__, 'render_meta_box'),
            'ensor_article',
            'normal',
            'high'
        );
    }

    public static function hide_legacy_section_metaboxes(): void
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || $screen->post_type !== 'ensor_article') {
            return;
        }

        $legacy = array(
            'ensor_article_section_context',
            'ensor_article_section_data',
            'ensor_article_section_student',
            'ensor_article_section_teacher',
            'ensor_article_section_professional',
            'ensor_article_quiz',
        );
        foreach ($legacy as $id) {
            remove_meta_box($id, 'ensor_article', 'normal');
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
        wp_localize_script(
            'ensorlogs-ai-engine-editor',
            'EAE_CFG',
            array(
                'restUrl'        => esc_url_raw(rest_url('ensorlogs-ai/v1/generate-log')),
                'nonce'          => wp_create_nonce('wp_rest'),
                'postId'          => $post_id,
                'apiConfigured'  => ((string) get_option(self::OPTION_API_KEY, '')) !== '',
                'apiSettingsUrl' => esc_url_raw(admin_url('options-general.php?page=ensorlogs-ai-engine')),
                'defaultMessages' => array(
                    'working'       => __('Generando log con ENSORLOGS AI ENGINE…', 'ensorlogs'),
                    'success'       => __('Log generado e insertado en el editor. Stack actualizado.', 'ensorlogs'),
                    'missingApiKey' => __('Falta API Key de OpenAI. Configúrala en Ajustes > Ensorlogs AI Engine.', 'ensorlogs'),
                    'missingTopic'  => __('Escribe el tema del LOG antes de generar.', 'ensorlogs'),
                ),
            )
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
            <p><?php esc_html_e('Configura la clave de OpenAI para generar Logs desde el editor del CPT Logs.', 'ensorlogs'); ?></p>
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
                                <?php $model = (string) get_option(self::OPTION_MODEL, 'gpt-5.5'); ?>
                                <select id="ensorlogs-ai-openai-model" name="<?php echo esc_attr(self::OPTION_MODEL); ?>">
                                    <option value="gpt-5.5" <?php selected($model, 'gpt-5.5'); ?>>gpt-5.5</option>
                                    <option value="gpt-4.1" <?php selected($model, 'gpt-4.1'); ?>>gpt-4.1</option>
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
    public static function render_meta_box($post): void
    {
        if (!$post instanceof WP_Post || !current_user_can('edit_post', $post->ID)) {
            return;
        }

        $stacks = self::get_stack_choices();
        $selected = self::get_selected_stack_slugs((int) $post->ID);
        ?>
        <div class="ensor-cpt-meta eae-panel-wrap">
            <p class="eae-intro">
                <?php esc_html_e('Genera el LOG completo con tono Ensorlogs. El prompt editorial mantiene las secciones por audiencia (estudiante, profesor, profesional); el brief de arriba aporta el contexto general del tema.', 'ensorlogs'); ?>
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

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

    public static function init(): void
    {
        add_action('admin_menu', array(__CLASS__, 'register_settings_page'));
        add_action('admin_init', array(__CLASS__, 'register_settings'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
        add_action('edit_form_after_title', array(__CLASS__, 'render_editor_panel'));
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

    public static function enqueue_assets(string $hook): void
    {
        if (!in_array($hook, array('post.php', 'post-new.php'), true)) {
            return;
        }
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || $screen->post_type !== 'ensor_article') {
            return;
        }

        wp_enqueue_style(
            'ensorlogs-ai-engine-admin',
            ENSORLOGS_AI_ENGINE_URL . 'assets/css/eae-admin.css',
            array(),
            ENSORLOGS_AI_ENGINE_VERSION
        );

        wp_enqueue_script(
            'ensorlogs-ai-engine-editor',
            ENSORLOGS_AI_ENGINE_URL . 'assets/js/eae-editor.js',
            array('wp-data'),
            ENSORLOGS_AI_ENGINE_VERSION,
            true
        );

        wp_localize_script(
            'ensorlogs-ai-engine-editor',
            'EAE_CFG',
            array(
                'restUrl'         => esc_url_raw(rest_url('ensorlogs-ai/v1/generate-log')),
                'nonce'           => wp_create_nonce('wp_rest'),
                'apiConfigured'   => ((string) get_option(self::OPTION_API_KEY, '')) !== '',
                'apiSettingsUrl'  => esc_url_raw(admin_url('options-general.php?page=ensorlogs-ai-engine')),
                'defaultMessages' => array(
                    'working'       => __('Generando log con ENSORLOGS AI ENGINE…', 'ensorlogs'),
                    'success'       => __('Log generado e insertado en el editor.', 'ensorlogs'),
                    'missingApiKey' => __('Falta API Key de OpenAI. Configúrala en Ajustes > Ensorlogs AI Engine.', 'ensorlogs'),
                ),
            )
        );
    }

    public static function render_editor_panel(WP_Post $post): void
    {
        if ($post->post_type !== 'ensor_article' || !current_user_can('edit_post', $post->ID)) {
            return;
        }
        ?>
        <section class="eae-panel" id="ensorlogs-ai-engine-panel" aria-labelledby="eae-title">
            <header class="eae-panel__header">
                <h2 id="eae-title"><?php esc_html_e('ENSORLOGS AI ENGINE', 'ensorlogs'); ?></h2>
                <p><?php esc_html_e('Genera un LOG completo con el tono editorial EnsorLogs, sin tocar tu frontend.', 'ensorlogs'); ?></p>
            </header>

            <div class="eae-grid">
                <label class="eae-field">
                    <span><?php esc_html_e('¿Qué quieres hablar en este LOG?', 'ensorlogs'); ?></span>
                    <input type="text" id="eae-topic" maxlength="180" />
                </label>

                <label class="eae-field eae-field--full">
                    <span><?php esc_html_e('Contexto o enfoque', 'ensorlogs'); ?></span>
                    <textarea id="eae-context" rows="3"></textarea>
                </label>

                <label class="eae-field eae-field--full">
                    <span><?php esc_html_e('Experiencia personal', 'ensorlogs'); ?></span>
                    <textarea id="eae-experience" rows="3"></textarea>
                </label>

                <label class="eae-field eae-field--full">
                    <span><?php esc_html_e('¿Qué quieres enseñar?', 'ensorlogs'); ?></span>
                    <textarea id="eae-teach" rows="3"></textarea>
                </label>

                <label class="eae-field">
                    <span><?php esc_html_e('Tipo de LOG', 'ensorlogs'); ?></span>
                    <select id="eae-log-type">
                        <option>WordPress</option>
                        <option>IA</option>
                        <option>Automatización</option>
                        <option>Desarrollo Web</option>
                        <option>Opinión</option>
                        <option>Reflexión</option>
                        <option>Tutorial</option>
                        <option>Stack</option>
                    </select>
                </label>

                <label class="eae-field">
                    <span><?php esc_html_e('Nivel técnico', 'ensorlogs'); ?></span>
                    <select id="eae-level">
                        <option><?php esc_html_e('Básico', 'ensorlogs'); ?></option>
                        <option><?php esc_html_e('Intermedio', 'ensorlogs'); ?></option>
                        <option><?php esc_html_e('Avanzado', 'ensorlogs'); ?></option>
                    </select>
                </label>

                <label class="eae-field">
                    <span><?php esc_html_e('Público principal', 'ensorlogs'); ?></span>
                    <select id="eae-audience">
                        <option><?php esc_html_e('Estudiantes', 'ensorlogs'); ?></option>
                        <option><?php esc_html_e('Profesores', 'ensorlogs'); ?></option>
                        <option><?php esc_html_e('Profesionales', 'ensorlogs'); ?></option>
                        <option><?php esc_html_e('Mixto', 'ensorlogs'); ?></option>
                    </select>
                </label>
            </div>

            <div class="eae-actions">
                <button type="button" class="button button-primary button-hero" id="eae-generate">
                    <?php esc_html_e('GENERAR LOG ENSORLOGS', 'ensorlogs'); ?>
                </button>
                <p class="eae-status" id="eae-status" role="status" aria-live="polite"></p>
            </div>
        </section>
        <?php
    }
}

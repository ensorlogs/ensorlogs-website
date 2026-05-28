<?php
/**
 * REST endpoint for AI log generation.
 */

if (!defined('ABSPATH')) {
    exit;
}

final class EAE_Rest
{
    private const OPTION_API_KEY = 'ensorlogs_ai_openai_api_key';
    private const OPTION_MODEL   = 'ensorlogs_ai_openai_model';

    public static function init(): void
    {
        add_action('rest_api_init', array(__CLASS__, 'register_routes'));
    }

    public static function register_routes(): void
    {
        register_rest_route(
            'ensorlogs-ai/v1',
            '/generate-log',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'permission_callback' => array(__CLASS__, 'can_generate'),
                'callback'            => array(__CLASS__, 'generate_log'),
                'args'                => array(
                    'topic'      => array('required' => true, 'type' => 'string'),
                    'context'    => array('required' => false, 'type' => 'string'),
                    'experience' => array('required' => false, 'type' => 'string'),
                    'teach'      => array('required' => false, 'type' => 'string'),
                    'postId'     => array('required' => false, 'type' => 'integer'),
                    'stacks'     => array(
                        'required' => false,
                        'type'     => 'array',
                        'items'    => array('type' => 'string'),
                    ),
                ),
            )
        );
    }

    public static function can_generate(WP_REST_Request $request): bool
    {
        if (!current_user_can('edit_posts')) {
            return false;
        }
        $nonce = $request->get_header('X-WP-Nonce');
        return is_string($nonce) && wp_verify_nonce($nonce, 'wp_rest');
    }

    public static function generate_log(WP_REST_Request $request): WP_REST_Response
    {
        $api_key = trim((string) get_option(self::OPTION_API_KEY, ''));
        if ($api_key === '') {
            return new WP_REST_Response(
                array(
                    'ok'      => false,
                    'message' => __('Falta API Key de OpenAI en Ajustes > Ensorlogs AI Engine.', 'ensorlogs'),
                ),
                400
            );
        }

        $stacks_raw = $request->get_param('stacks');
        $stacks     = array();
        if (is_array($stacks_raw)) {
            foreach ($stacks_raw as $slug) {
                $slug = sanitize_title((string) $slug);
                if ($slug !== '') {
                    $stacks[] = $slug;
                }
            }
        }
        $stacks = array_values(array_unique($stacks));

        $input = array(
            'topic'      => sanitize_text_field((string) $request->get_param('topic')),
            'context'    => sanitize_textarea_field((string) $request->get_param('context')),
            'experience' => sanitize_textarea_field((string) $request->get_param('experience')),
            'teach'      => sanitize_textarea_field((string) $request->get_param('teach')),
            'stacks'     => $stacks,
        );

        if ($input['topic'] === '') {
            return new WP_REST_Response(
                array(
                    'ok'      => false,
                    'message' => __('Debes escribir el tema del LOG.', 'ensorlogs'),
                ),
                400
            );
        }

        $prompt = EAE_Prompt::build_master_prompt($input);
        $model  = (string) get_option(self::OPTION_MODEL, 'gpt-5.5');
        if (!in_array($model, array('gpt-5.5', 'gpt-4.1'), true)) {
            $model = 'gpt-5.5';
        }

        $result = EAE_OpenAI::generate_html($api_key, $model, $prompt);
        if (!$result['ok']) {
            return new WP_REST_Response(
                array(
                    'ok'      => false,
                    'message' => __('No se pudo generar el LOG en este momento.', 'ensorlogs'),
                    'error'   => $result['error'],
                ),
                502
            );
        }

        $clean_html = EAE_Prompt::sanitize_generated_html($result['content']);
        if ($clean_html === '') {
            return new WP_REST_Response(
                array(
                    'ok'      => false,
                    'message' => __('La IA no devolvió contenido utilizable.', 'ensorlogs'),
                ),
                422
            );
        }

        $extracted   = EAE_Prompt::extract_quiz_for_meta($clean_html);
        $editor_html = $extracted['html'];
        $quiz_text   = $extracted['quiz_text'];

        $post_id         = absint($request->get_param('postId'));
        $block_content   = self::content_for_editor_storage($editor_html, $post_id);
        $sync            = array();
        if ($post_id > 0 && current_user_can('edit_post', $post_id)) {
            $sync = self::sync_post_meta($post_id, $input['topic'], $stacks, $quiz_text, $block_content);
        }

        return new WP_REST_Response(
            array(
                'ok'            => true,
                'message'       => __('LOG generado correctamente.', 'ensorlogs'),
                'html'          => $editor_html,
                'blockContent'  => $block_content,
                'quizText'      => $quiz_text,
                'sync'          => $sync,
            ),
            200
        );
    }

    private static function content_for_editor_storage(string $editor_html, int $post_id): string
    {
        if ($editor_html === '') {
            return '';
        }
        $use_blocks = class_exists('EAE_Admin')
            ? EAE_Admin::post_uses_block_editor($post_id)
            : (function_exists('use_block_editor_for_post_type') && use_block_editor_for_post_type('ensor_article'));

        if (!$use_blocks || !function_exists('ensorlogs_blockify_html_for_editor')) {
            return $editor_html;
        }

        return ensorlogs_blockify_html_for_editor($editor_html, 'article');
    }

    /**
     * @param list<string> $stacks
     * @return array<string, mixed>
     */
    private static function sync_post_meta(int $post_id, string $topic, array $stacks, string $quiz_text, string $block_content): array
    {
        $sync = array(
            'stacks'      => '',
            'primaryTema' => '',
            'title'       => '',
            'savedPost'   => false,
        );

        if ($block_content !== '') {
            $updated = wp_update_post(
                array(
                    'ID'           => $post_id,
                    'post_content' => $block_content,
                ),
                true
            );
            $sync['savedPost'] = !is_wp_error($updated) && $updated > 0;
        }

        if ($stacks) {
            $temas_str = implode(' ', $stacks);
            update_post_meta($post_id, '_ensor_temas', $temas_str);
            if (function_exists('ensorlogs_sync_temas_meta_to_taxonomy')) {
                ensorlogs_sync_temas_meta_to_taxonomy($post_id, $temas_str);
            } elseif (taxonomy_exists('ensor_tema')) {
                wp_set_object_terms($post_id, $stacks, 'ensor_tema', false);
            }
            $sync['stacks'] = $temas_str;

            $primary = $stacks[0];
            if (function_exists('ensorlogs_primary_tema_choices')) {
                $choices = ensorlogs_primary_tema_choices();
                if (array_key_exists($primary, $choices)) {
                    update_post_meta($post_id, '_ensor_primary_tema', $primary);
                    $sync['primaryTema'] = $primary;
                }
            } else {
                update_post_meta($post_id, '_ensor_primary_tema', $primary);
                $sync['primaryTema'] = $primary;
            }
        }

        if ($quiz_text !== '') {
            update_post_meta($post_id, '_ensor_quiz', $quiz_text);
        }

        $post = get_post($post_id);
        if ($post instanceof WP_Post && $post->post_title === '' && $topic !== '') {
            wp_update_post(
                array(
                    'ID'         => $post_id,
                    'post_title' => $topic,
                )
            );
            $sync['title'] = $topic;
        }

        return $sync;
    }
}

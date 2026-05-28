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
                    'logType'    => array('required' => false, 'type' => 'string'),
                    'level'      => array('required' => false, 'type' => 'string'),
                    'audience'   => array('required' => false, 'type' => 'string'),
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

        $input = array(
            'topic'      => sanitize_text_field((string) $request->get_param('topic')),
            'context'    => sanitize_textarea_field((string) $request->get_param('context')),
            'experience' => sanitize_textarea_field((string) $request->get_param('experience')),
            'teach'      => sanitize_textarea_field((string) $request->get_param('teach')),
            'logType'    => sanitize_text_field((string) $request->get_param('logType')),
            'level'      => sanitize_text_field((string) $request->get_param('level')),
            'audience'   => sanitize_text_field((string) $request->get_param('audience')),
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

        return new WP_REST_Response(
            array(
                'ok'      => true,
                'message' => __('LOG generado correctamente.', 'ensorlogs'),
                'html'    => $clean_html,
            ),
            200
        );
    }
}

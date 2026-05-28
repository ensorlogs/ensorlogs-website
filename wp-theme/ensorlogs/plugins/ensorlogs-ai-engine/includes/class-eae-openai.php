<?php
/**
 * OpenAI client wrapper.
 */

if (!defined('ABSPATH')) {
    exit;
}

final class EAE_OpenAI
{
    private const API_URL = 'https://api.openai.com/v1/responses';

    /**
     * @return array{ok:bool, content:string, error:string}
     */
    public static function generate_html(string $api_key, string $model, string $prompt): array
    {
        $body = array(
            'model'       => $model,
            'input'       => $prompt,
            'temperature' => 0.7,
            'max_output_tokens' => 2200,
        );

        $response = wp_remote_post(
            self::API_URL,
            array(
                'timeout' => 45,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type'  => 'application/json',
                ),
                'body'    => wp_json_encode($body),
            )
        );

        if (is_wp_error($response)) {
            return array(
                'ok'      => false,
                'content' => '',
                'error'   => (string) $response->get_error_message(),
            );
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        $raw  = (string) wp_remote_retrieve_body($response);
        $json = json_decode($raw, true);

        if ($code < 200 || $code >= 300 || !is_array($json)) {
            return array(
                'ok'      => false,
                'content' => '',
                'error'   => 'OpenAI HTTP ' . $code,
            );
        }

        $text = '';
        if (isset($json['output_text']) && is_string($json['output_text'])) {
            $text = $json['output_text'];
        } elseif (isset($json['output']) && is_array($json['output'])) {
            foreach ($json['output'] as $chunk) {
                if (!is_array($chunk) || empty($chunk['content']) || !is_array($chunk['content'])) {
                    continue;
                }
                foreach ($chunk['content'] as $item) {
                    if (is_array($item) && ($item['type'] ?? '') === 'output_text' && isset($item['text']) && is_string($item['text'])) {
                        $text .= $item['text'];
                    }
                }
            }
        }

        if (trim($text) === '') {
            return array(
                'ok'      => false,
                'content' => '',
                'error'   => 'OpenAI no devolvió contenido.',
            );
        }

        return array(
            'ok'      => true,
            'content' => $text,
            'error'   => '',
        );
    }
}

<?php
/**
 * OpenAI client wrapper.
 */

if (!defined('ABSPATH')) {
    exit;
}

final class EAE_OpenAI
{
    private const CHAT_URL      = 'https://api.openai.com/v1/chat/completions';
    private const RESPONSES_URL = 'https://api.openai.com/v1/responses';

    /**
     * @return array{ok:bool, content:string, error:string}
     */
    public static function generate_html(string $api_key, string $model, string $prompt): array
    {
        $resolved = self::resolve_model($model);

        $chat = self::request_chat($api_key, $resolved, $prompt);
        if ($chat['ok']) {
            return $chat;
        }

        $responses = self::request_responses($api_key, $resolved, $prompt);
        if ($responses['ok']) {
            return $responses;
        }

        $error = $chat['error'] !== '' ? $chat['error'] : $responses['error'];
        return array(
            'ok'      => false,
            'content' => '',
            'error'   => $error !== '' ? $error : 'OpenAI no respondió.',
        );
    }

    public static function resolve_model(string $model): string
    {
        $model = sanitize_text_field($model);
        $map   = array(
            'gpt-5.5' => 'gpt-4.1',
            'gpt-4.1' => 'gpt-4.1',
        );
        return $map[$model] ?? 'gpt-4.1';
    }

    /**
     * @return array{ok:bool, content:string, error:string}
     */
    private static function request_chat(string $api_key, string $model, string $prompt): array
    {
        $body = array(
            'model'       => $model,
            'temperature' => 0.7,
            'max_tokens'  => 4096,
            'messages'    => array(
                array(
                    'role'    => 'system',
                    'content' => 'Eres EnsorLogs AI ENGINE. Devuelve solo HTML semántico válido, sin markdown ni explicaciones fuera del HTML.',
                ),
                array(
                    'role'    => 'user',
                    'content' => $prompt,
                ),
            ),
        );

        $response = wp_remote_post(
            self::CHAT_URL,
            array(
                'timeout' => 90,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type'  => 'application/json',
                ),
                'body'    => wp_json_encode($body),
            )
        );

        return self::parse_http_response($response, 'chat');
    }

    /**
     * @return array{ok:bool, content:string, error:string}
     */
    private static function request_responses(string $api_key, string $model, string $prompt): array
    {
        $body = array(
            'model'             => $model,
            'input'             => $prompt,
            'temperature'       => 0.7,
            'max_output_tokens' => 4096,
        );

        $response = wp_remote_post(
            self::RESPONSES_URL,
            array(
                'timeout' => 90,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type'  => 'application/json',
                ),
                'body'    => wp_json_encode($body),
            )
        );

        return self::parse_http_response($response, 'responses');
    }

    /**
     * @param array<string,mixed>|WP_Error $response
     * @return array{ok:bool, content:string, error:string}
     */
    private static function parse_http_response($response, string $mode): array
    {
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
            $detail = '';
            if (is_array($json) && isset($json['error']['message']) && is_string($json['error']['message'])) {
                $detail = ' — ' . $json['error']['message'];
            }
            return array(
                'ok'      => false,
                'content' => '',
                'error'   => 'OpenAI HTTP ' . $code . $detail,
            );
        }

        $text = '';
        if ($mode === 'chat' && isset($json['choices'][0]['message']['content']) && is_string($json['choices'][0]['message']['content'])) {
            $text = $json['choices'][0]['message']['content'];
        } elseif ($mode === 'responses') {
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
        }

        $text = trim($text);
        if ($text === '') {
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

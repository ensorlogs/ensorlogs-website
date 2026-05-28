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
    public static function generate_html(string $openai_credential, string $model, string $prompt): array
    {
        $models  = self::models_to_try($model);
        $errors  = array();

        foreach ($models as $try_model) {
            $chat = self::request_chat($openai_credential, $try_model, $prompt);
            if ($chat['ok']) {
                $chat['content'] = self::normalize_model_output($chat['content']);
                if ($chat['content'] !== '') {
                    return $chat;
                }
                $errors[] = $try_model . ': respuesta vacía tras normalizar';
            } else {
                $errors[] = $try_model . ': ' . $chat['error'];
            }

            $responses = self::request_responses($openai_credential, $try_model, $prompt);
            if ($responses['ok']) {
                $responses['content'] = self::normalize_model_output($responses['content']);
                if ($responses['content'] !== '') {
                    return $responses;
                }
                $errors[] = $try_model . ' (responses): respuesta vacía';
            } else {
                $errors[] = $try_model . ' (responses): ' . $responses['error'];
            }
        }

        return array(
            'ok'      => false,
            'content' => '',
            'error'   => implode(' | ', array_slice($errors, 0, 3)),
        );
    }

    /**
     * @return list<string>
     */
    public static function models_to_try(string $model): array
    {
        $primary = self::resolve_model($model);
        $list    = array($primary, 'gpt-4o', 'gpt-4o-mini');
        $out     = array();
        foreach ($list as $m) {
            $m = sanitize_text_field($m);
            if ($m !== '' && !in_array($m, $out, true)) {
                $out[] = $m;
            }
        }
        return $out;
    }

    public static function resolve_model(string $model): string
    {
        $model = sanitize_text_field($model);
        $map   = array(
            'gpt-5.5'   => 'gpt-4o',
            'gpt-4.1'   => 'gpt-4.1',
            'gpt-4o'    => 'gpt-4o',
            'gpt-4o-mini' => 'gpt-4o-mini',
        );
        return $map[$model] ?? 'gpt-4o';
    }

    public static function normalize_model_output(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }
        if (preg_match('/^```(?:html)?\s*\n?(.*?)\n?```\s*$/is', $text, $m)) {
            $text = trim((string) $m[1]);
        }
        return trim($text);
    }

    /**
     * @return array{ok:bool, content:string, error:string}
     */
    private static function request_chat(string $openai_credential, string $model, string $prompt): array
    {
        $system = class_exists('EAE_Prompt') ? EAE_Prompt::build_system_prompt() : '';

        $body = array(
            'model'       => $model,
            'temperature' => 0.7,
            'messages'    => array(
                array(
                    'role'    => 'system',
                    'content' => $system !== '' ? $system : 'Eres EnsorLogs AI ENGINE. Devuelve solo HTML semántico válido.',
                ),
                array(
                    'role'    => 'user',
                    'content' => $prompt,
                ),
            ),
        );

        if (self::model_uses_completion_tokens($model)) {
            $body['max_completion_tokens'] = 4096;
        } else {
            $body['max_tokens'] = 4096;
        }

        $response = wp_remote_post(
            self::CHAT_URL,
            array(
                'timeout' => 120,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $openai_credential,
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
    private static function request_responses(string $openai_credential, string $model, string $prompt): array
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
                'timeout' => 120,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $openai_credential,
                    'Content-Type'  => 'application/json',
                ),
                'body'    => wp_json_encode($body),
            )
        );

        return self::parse_http_response($response, 'responses');
    }

    private static function model_uses_completion_tokens(string $model): bool
    {
        return preg_match('/^gpt-4(\.|$|-)/', $model) === 1
            || str_starts_with($model, 'gpt-4o')
            || str_starts_with($model, 'o');
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
                $detail = $json['error']['message'];
            } elseif ($raw !== '' && strlen($raw) < 400) {
                $detail = $raw;
            }
            return array(
                'ok'      => false,
                'content' => '',
                'error'   => $detail !== '' ? ('HTTP ' . $code . ': ' . $detail) : ('HTTP ' . $code),
            );
        }

        $text = '';
        if ($mode === 'chat' && isset($json['choices'][0]['message']['content'])) {
            $content = $json['choices'][0]['message']['content'];
            if (is_string($content)) {
                $text = $content;
            }
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
                'error'   => 'OpenAI no devolvió texto en la respuesta.',
            );
        }

        return array(
            'ok'      => true,
            'content' => $text,
            'error'   => '',
        );
    }
}

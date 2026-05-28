<?php
/**
 * Nombres de opciones WP (no son secretos; la clave vive en la base de datos).
 */

if (!defined('ABSPATH')) {
    exit;
}

final class EAE_Config
{
    /** Option name en wp_options para la clave de OpenAI (valor guardado en BD, no en código). */
    public const OPENAI_OPTION = 'ensorlogs_ai_openai_api_key';

    public const MODEL_OPTION = 'ensorlogs_ai_openai_model';
}

<?php
/**
 * Alias de opciones WP del AI Engine (valores en BD, no en código).
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('EAE_Config', false)) {
    final class EAE_Config
    {
        public const OPENAI_OPTION = EAE_WP_OPTION_OPENAI;

        public const MODEL_OPTION = EAE_WP_OPTION_MODEL;
    }
}

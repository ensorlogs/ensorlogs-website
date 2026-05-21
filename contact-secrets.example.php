<?php
/**
 * Copia este archivo como contact-secrets.php (no lo subas a GitHub).
 *
 * 1. Crea un widget en https://dash.cloudflare.com/ → Turnstile
 * 2. Dominio: ensorlogs.com (y localhost si pruebas en local)
 * 3. Pega las claves aquí y en contact.html (atributo data-sitekey del div Turnstile)
 */
declare(strict_types=1);

/** Clave secreta (solo servidor, validación en contact-form.php). */
const ENSOR_TURNSTILE_SECRET = '0x4AAAAAAA...tu_secret_key';

/** Clave pública (misma que data-sitekey en contact.html). */
const ENSOR_TURNSTILE_SITE_KEY = '0x4AAAAAAA...tu_site_key';

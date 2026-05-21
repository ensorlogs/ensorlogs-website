<?php
declare(strict_types=1);

/**
 * =============================================================================
 * Formulario de contacto — Ensorlogs (modo enseñanza / producción)
 * =============================================================================
 *
 * Flujo HTTP
 * -----------
 * 1. ``contact.html`` envía un POST a este script.
 * 2. Si el método no es POST, redirigimos a la página del formulario (evita GET directo).
 * 3. Validamos datos (honeypot, captcha sencillo, email, longitudes, patrones raros).
 * 4. Si todo va bien, llamamos a ``mail()`` del hosting y mostramos una página HTML
 *    de resultado (éxito o error). No devolvemos JSON: es a posta, para hosting compartido.
 *
 * Seguridad (conceptos que puedes buscar en libros / MDN)
 * -------------------------------------------------------
 * - **Honeypot**: campo oculto que los humanos no rellenan; los bots a veces sí.
 * - **Header injection**: nunca concatenar entrada del usuario en cabeceras de correo
 *   sin quitar saltos de línea (``\\r``, ``\\n``). Por eso ``ensor_strip_crlf()``.
 * - **XSS en la salida**: todo lo que mostramos en HTML pasa por ``htmlspecialchars()``.
 * - **Cabeceras HTTP** (``ensor_security_headers``): endurecen al navegador (nosniff, etc.).
 *
 * Requisitos en el servidor
 * -------------------------
 * PHP con ``mail()`` funcional o canal SMTP según SiteGround / tu proveedor.
 *
 * Ver también: ``README.md``, ``docs/ARQUITECTURA.md``.
 */

function ensor_security_headers(): void
{
    if (headers_sent()) {
        return;
    }
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

/** Quita caracteres que podrían inyectar cabeceras SMTP adicionales si se copian tal cual. */
function ensor_strip_crlf(string $s): string
{
    return str_replace(["\r", "\n", "\0"], '', $s);
}

/** Longitud de string UTF-8 segura aunque ``mbstring`` no esté instalada (fallback a ``strlen``). */
function ensor_len(string $s): int
{
    return function_exists('mb_strlen') ? mb_strlen($s, 'UTF-8') : strlen($s);
}

/** Comprobación heurística heredada de la plantilla: tab/saltos codificados en URL. */
function isInjected(string $str): bool
{
    $injections = ['(\n+)', '(\r+)', '(\t+)', '(%0A+)', '(%0D+)', '(%08+)', '(%09+)'];
    $inject = '/' . implode('|', $injections) . '/i';

    return preg_match($inject, $str) === 1;
}

ensor_security_headers();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: contact.html', true, 302);
    exit;
}

const WEBMASTER_EMAIL = 'hello@ensorlogs.com';
const MAX_NAME_LEN = 200;

$ensor_turnstile_secret = '';
if (is_readable(__DIR__ . '/contact-secrets.php')) {
    require_once __DIR__ . '/contact-secrets.php';
    if (defined('ENSOR_TURNSTILE_SECRET')) {
        $ensor_turnstile_secret = (string) ENSOR_TURNSTILE_SECRET;
    }
}
const MAX_SUBJECT_LEN = 300;
const MAX_MESSAGE_LEN = 30000;

$name = trim($_POST['clientName'] ?? '');
$email_address = trim($_POST['clientEmail'] ?? '');
$subject = trim($_POST['contactSubject'] ?? '');
$message = trim($_POST['contact__message'] ?? '');
$turnstile_token = trim($_POST['cf-turnstile-response'] ?? '');
$honeypot = trim($_POST['website'] ?? '');

$result = 'send_failed';
$page_heading = 'No se pudo enviar';
$page_message = 'Vuelve a intentarlo desde el formulario de contacto.';

/**
 * @param string $secret
 * @param string $token
 */
function ensor_static_turnstile_verify(string $secret, string $token): bool
{
    if ($secret === '' || $token === '') {
        return false;
    }
    $remote_ip = isset($_SERVER['REMOTE_ADDR']) && is_string($_SERVER['REMOTE_ADDR'])
        ? $_SERVER['REMOTE_ADDR']
        : '';
    $payload = http_build_query(
        array(
            'secret'   => $secret,
            'response' => $token,
            'remoteip' => $remote_ip,
        )
    );
    $ctx = stream_context_create(
        array(
            'http' => array(
                'method'  => 'POST',
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $payload,
                'timeout' => 15,
            ),
        )
    );
    $raw = @file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify', false, $ctx);
    if (!is_string($raw) || $raw === '') {
        return false;
    }
    $json = json_decode($raw, true);
    return is_array($json) && !empty($json['success']);
}

if ($honeypot !== '') {
    $result = 'spam';
} elseif ($ensor_turnstile_secret === '') {
    $result = 'captcha_config';
    $page_heading = 'Formulario no configurado';
    $page_message = 'Falta contact-secrets.php con la clave secreta de Turnstile (copia contact-secrets.example.php).';
} elseif (!ensor_static_turnstile_verify($ensor_turnstile_secret, $turnstile_token)) {
    $result = 'captcha';
    $page_heading = 'Verificación incorrecta';
    $page_message = 'No se pudo verificar el captcha. Vuelve al formulario, completa la verificación e inténtalo de nuevo.';
} elseif ($name === '' || $email_address === '' || $subject === '' || $message === '') {
    $result = 'missing';
    $page_heading = 'Faltan datos';
    $page_message = 'Completa todos los campos obligatorios antes de enviar el formulario.';
} elseif (ensor_len($name) > MAX_NAME_LEN || ensor_len($subject) > MAX_SUBJECT_LEN || ensor_len($message) > MAX_MESSAGE_LEN) {
    $result = 'too_long';
    $page_heading = 'Texto demasiado largo';
    $page_message = 'Acorta el nombre, el asunto o el mensaje e inténtalo de nuevo.';
} elseif (! filter_var($email_address, FILTER_VALIDATE_EMAIL)) {
    $result = 'bad_email';
    $page_heading = 'Correo no válido';
    $page_message = 'Indica una dirección de correo electrónico válida.';
} elseif (isInjected($email_address) || isInjected($name) || isInjected($subject) || isInjected($message)) {
    $result = 'injection';
} else {
    $reply_safe = ensor_strip_crlf($email_address);
    $subject_safe = ensor_strip_crlf($subject);
    if ($subject_safe === '') {
        $subject_safe = '(sin asunto)';
    }
    $body = "Nombre: {$name}\r\n"
        . "Email: {$email_address}\r\n"
        . "Asunto: {$subject}\r\n\r\n"
        . "Mensaje:\r\n{$message}";

    $subject_line = '[Contacto ensorlogs.com] ' . $subject_safe;
    $headers = 'From: Ensorlogs <' . WEBMASTER_EMAIL . '>' . "\r\n"
        . 'Reply-To: ' . $reply_safe . "\r\n"
        . 'MIME-Version: 1.0' . "\r\n"
        . 'Content-Type: text/plain; charset=UTF-8' . "\r\n"
        . 'Content-Transfer-Encoding: 8bit' . "\r\n"
        . 'X-Mailer: PHP/' . phpversion();

    // -f mejora la entrega en algunos hosts cuando coincide con el dominio autorizado
    $extra = '-f' . WEBMASTER_EMAIL;

    $sent = mail(WEBMASTER_EMAIL, $subject_line, $body, $headers, $extra);

    if ($sent) {
        $result = 'ok';
        $page_heading = 'Mensaje enviado';
        $page_message = 'Gracias por escribir. Tu mensaje se ha enviado a hello@ensorlogs.com y te responderé lo antes posible.';
    } else {
        $page_heading = 'No se pudo enviar';
        $page_message = 'El servidor no pudo completar el envío en este momento. Por favor escribe directamente a hello@ensorlogs.com o inténtalo más tarde.';
    }
}

$page_title = $result === 'ok' ? 'Mensaje enviado | Ensorlogs' : 'Envío de formulario | Ensorlogs';

if (! headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es" class="">
<head>
    <meta charset="UTF-8">
    <script>(function(){try{if(localStorage.theme!=='light')document.documentElement.classList.add('dark')}catch(e){document.documentElement.classList.add('dark')}})();</script>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="shortcut icon" href="assets/img/favicon.png" sizes="any">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,200..800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/fontAwesome5Pro.css">
    <link rel="stylesheet" href="assets/css/style.min.css">
    <link rel="stylesheet" href="assets/css/ensor-brand.css">
</head>
<body class="relative bg-[#F5F7F9] dark:bg-powerBlack">
    <main class="app">
        <div class="main-content mt-28 md:mt-32 lg:mt-36 xl:mt-48">
            <div class="container space-y-6">
                <div class="max-w-screen-lg mx-auto rounded-2xl p-6 xl:p-10 bg-gradient-to-b from-milkWhite to-seashell dark:from-metalBlack dark:to-oilBlack border border-gray-100 dark:border-white/5">
                    <h1 class="font-bold text-darkGray dark:text-pastelGrey text-xl lg:text-2xl mb-2">
                        <?php echo htmlspecialchars($page_heading, ENT_QUOTES, 'UTF-8'); ?>
                    </h1>
                    <p class="text-darkGray dark:text-pastelGrey">
                        <?php echo htmlspecialchars($page_message, ENT_QUOTES, 'UTF-8'); ?>
                    </p>
                    <?php if ($result === 'ok') : ?>
                        <p class="mt-6">
                            <a href="index.html" class="group ensor-btn ensor-btn-primary text-regular py-2 lg:py-3 px-7 lg:px-10 inline-flex items-center gap-2">
                                <svg class="w-11 shrink-0 text-current opacity-90 transition-all duration-300 group-hover:mr-2" viewBox="0 0 65 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path d="M20 7.87214C19.5842 7.06683 19.0388 6.31114 18.3637 5.63604C14.849 2.1213 9.15055 2.1213 5.6358 5.63604C2.12108 9.15076 2.12106 14.8492 5.6358 18.364C9.15052 21.8787 14.849 21.8787 18.3637 18.364C19.0388 17.6889 19.5842 16.9332 20 16.1279" stroke="currentcolor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M64 12L7 12L11 8M11 16L9.5 14.5" stroke="currentcolor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                Volver al inicio
                            </a>
                        </p>
                    <?php else : ?>
                        <p class="mt-6 flex flex-wrap gap-4">
                            <a href="contact.html" class="ensor-btn ensor-btn-primary text-regular py-2 lg:py-3 px-7 lg:px-10">
                                Volver al formulario
                            </a>
                            <a href="mailto:hello@ensorlogs.com" class="ensor-btn ensor-btn-outline text-regular py-2 lg:py-3 px-7 lg:px-10">
                                Escribir a hello@ensorlogs.com
                            </a>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</body>
</html>

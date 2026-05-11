<?php
declare(strict_types=1);

/**
 * Procesa el formulario de contact.html y envía el mensaje a hello@ensorlogs.com
 * Requiere PHP con mail() configurado en el servidor (hosting con envío SMTP/sendmail).
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: contact.html', true, 302);
    exit;
}

function isInjected(string $str): bool
{
    $injections = ['(\n+)', '(\r+)', '(\t+)', '(%0A+)', '(%0D+)', '(%08+)', '(%09+)'];
    $inject = '/' . implode('|', $injections) . '/i';

    return preg_match($inject, $str) === 1;
}

const WEBMASTER_EMAIL = 'hello@ensorlogs.com';
const EXPECTED_CAPTCHA = 11;

$name = trim($_POST['clientName'] ?? '');
$email_address = trim($_POST['clientEmail'] ?? '');
$subject = trim($_POST['contactSubject'] ?? '');
$message = trim($_POST['contact__message'] ?? '');
$captcha_in = trim($_POST['contact_captcha'] ?? '');
$honeypot = trim($_POST['website'] ?? '');

$result = 'send_failed';
$page_heading = 'No se pudo enviar';
$page_message = 'Vuelve a intentarlo desde el formulario de contacto.';

if ($honeypot !== '') {
    $result = 'spam';
} elseif ($captcha_in === '' || (int) $captcha_in !== EXPECTED_CAPTCHA) {
    $result = 'captcha';
    $page_heading = 'Verificación incorrecta';
    $page_message = 'La respuesta anti-spam no coincide. Pulsa atrás en el navegador y revisa la suma (5 + 6).';
} elseif ($name === '' || $email_address === '' || $subject === '' || $message === '') {
    $result = 'missing';
    $page_heading = 'Faltan datos';
    $page_message = 'Completa todos los campos obligatorios antes de enviar el formulario.';
} elseif (! filter_var($email_address, FILTER_VALIDATE_EMAIL)) {
    $result = 'bad_email';
    $page_heading = 'Correo no válido';
    $page_message = 'Indica una dirección de correo electrónico válida.';
} elseif (isInjected($email_address) || isInjected($name) || isInjected($subject) || isInjected($message)) {
    $result = 'injection';
} else {
    $body = "Nombre: {$name}\r\n"
        . "Email: {$email_address}\r\n"
        . "Asunto: {$subject}\r\n\r\n"
        . "Mensaje:\r\n{$message}";

    $subject_line = '[Contacto ensorlogs.com] ' . $subject;
    $headers = 'From: Ensorlogs <' . WEBMASTER_EMAIL . '>' . "\r\n"
        . 'Reply-To: ' . $email_address . "\r\n"
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

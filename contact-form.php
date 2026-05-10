<!DOCTYPE html>
<html lang="en" class="">

<head>
    <!-- Basic Page Needs
    ================================================== -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <!-- Specific Meta
    ================================================== -->
    <meta name="viewport" 
        content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <meta name="keyword"
        content="resume, cv, portfolio, vcard, responsive, retina, jquery, css3, tailwindcss, material CV, creative, designer, developer, online cv, online resume, powerful portfolio, professional, landing page">
    <meta name="description" 
        content="bentoMan - Personal portfolio resume template">
    <meta name="author" content="Themearray">

    <!-- Site Title
    ================================================== -->
    <title>BentoMan - Personal portfolio resume template</title>

    <!-- Site Favicon
    ================================================== -->
    <link rel="shortcut icon" href="assets/img/favicon.png" sizes="any">

    <!-- Google Fonts
    ================================================== -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,200..800&display=swap" rel="stylesheet">

    <!-- All CSS Here
    ================================================== -->
    <link rel="stylesheet" href="assets/css/fontAwesome5Pro.css">
    <link rel="stylesheet" href="assets/css/swiper-bundle.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.min.css">
    <link rel="stylesheet" href="assets/css/ensor-brand.css">

</head>

<body class="relative bg-[#F5F7F9] dark:bg-powerBlack">
    <!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    Start Preloader
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
    <div class="preloader fixed z-999 h-screen w-full left-0 top-0 flex items-center justify-center bg-seashell dark:bg-darkGray">
        <h2 class="load-text z-20 text-xl font-light tracking-[15px] uppercase">
            <span class="![animation-delay:_0s]">L</span>
            <span class="![animation-delay:_0.1s]">o</span>
            <span class="![animation-delay:_0.2s]">a</span>
            <span class="![animation-delay:_0.3s]">d</span>
            <span class="![animation-delay:_0.4s]">i</span>
            <span class="![animation-delay:_0.5s]">n</span>
            <span class="![animation-delay:_0.6s]">g</span>
        </h2>
    </div>
    <!--~~./ end Preloader ~~-->

    <main class="app">

    <!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    Start Header
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
    <header class="my-4 lg:my-6 fixed top-0 w-full left-0 z-50 transition-all duration-300 [&.is-sticky]:mt-0">
        <div class="container">
            <div class="nav max-md:py-3 py-5 px-4 lg:px-6 xl:px-8 flex items-center justify-between bg-white dark:bg-[#1B1C1C] rounded-xl shadow-lg shadow-black/5">
                <a href="index.html">
                    <!-- Light Version Logo -->
                    <img 
                        src="assets/img/logo.svg" 
                        alt="bentoMan - Personal portfolio resume template"
                        class="max-md:w-32 dark:hidden"
                    >
                    <!-- Dark version Logo -->
                    <img 
                        src="assets/img/logo-dark.svg" 
                        alt="bentoMan - Personal portfolio resume template"
                        class="max-md:w-32 hidden dark:block"
                    >
                </a>
                <div class="main-menu *:transition-all *:text-darkGray *:inline-flex *:items-center dark:*:text-pastelGrey *:border *:border-transparent *:duration-300 *:px-6 *:py-2 *:leading-normal *:rounded-4xl *:font-semibold max-lg:hidden">

                    <a href="index.html" class="dark:[&.active]:text-white [&.active]:border-nobelGray dark:[&.active]:border-white/20 hover:text-smokeGray dark:hover:text-white">
                        Home
                    </a>
                    <a href="about.html" class="dark:[&.active]:text-white [&.active]:border-nobelGray dark:[&.active]:border-white/20 hover:text-smokeGray dark:hover:text-white">
                        About
                    </a>
                    <a href="projects.html" class="dark:[&.active]:text-white [&.active]:border-nobelGray dark:[&.active]:border-white/20 hover:text-smokeGray dark:hover:text-white">
                        Projects
                    </a>
                    <a href="blog.html" class="dark:[&.active]:text-white [&.active]:border-nobelGray dark:[&.active]:border-white/20 hover:text-smokeGray dark:hover:text-white">
                        Blog
                    </a>
                    <a href="contact.html" class="active dark:[&.active]:text-white [&.active]:border-nobelGray dark:[&.active]:border-white/20 hover:text-smokeGray dark:hover:text-white">
                        Contact
                    </a>
                </div>
                <div class="button-groups flex items-center gap-4 *:border *:border-flasWhite *:dark:border-flasBlack *:bg-gradient-to-b *:from-milkWhite *:to-seashell dark:*:from-metalBlack dark:*:to-oilBlack">
                    <button class="btn_theme_switch w-13 h-13 max-md:w-12 max-md:h-12 relative rounded-full group *:w-5 *:h-5 *:max-md:w-6 *:max-md:h-6 *:absolute *:top-1/2 *:left-1/2 *:-translate-x-1/2 *:-translate-y-1/2 " aria-label="Dark - Light Switch">
                        <svg  class="sun group-[&.btn-light]:opacity-0" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M11 17.5C14.5899 17.5 17.5 14.5899 17.5 11C17.5 7.41015 14.5899 4.5 11 4.5C7.41015 4.5 4.5 7.41015 4.5 11C4.5 14.5899 7.41015 17.5 11 17.5Z" stroke="#CDD0DA" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M18.14 18.14L18.01 18.01M18.01 3.99L18.14 3.86L18.01 3.99ZM3.86 18.14L3.99 18.01L3.86 18.14ZM11 1.08V1V1.08ZM11 21V20.92V21ZM1.08 11H1H1.08ZM21 11H20.92H21ZM3.99 3.99L3.86 3.86L3.99 3.99Z" stroke="#CDD0DA" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <svg class="moon opacity-0 group-[&.btn-light]:opacity-100" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M1.03009 11.42C1.39009 16.57 5.76009 20.76 10.9901 20.99C14.6801 21.15 17.9801 19.43 19.9601 16.72C20.7801 15.61 20.3401 14.87 18.9701 15.12C18.3001 15.24 17.6101 15.29 16.8901 15.26C12.0001 15.06 8.00009 10.97 7.98009 6.13996C7.97009 4.83996 8.24009 3.60996 8.73009 2.48996C9.27009 1.24996 8.62009 0.659961 7.37009 1.18996C3.41009 2.85996 0.70009 6.84996 1.03009 11.42Z" stroke="#2F3236" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <a href="contact.html" class="text-darkGray dark:text-pastelGrey font-semibold py-2.5 px-8 max-md:hidden leading-1.75 rounded-3xl">
                        Lets Talk
                    </a>
                    <button type="button" class="text-darkGray dark:text-pastelGrey menu_toggle flex items-center text-xl lg:hidden">
                        <i class="fal fa-bars"></i>
                    </button>
                </div>
            </div>
        </div>
    </header>
    <!-- Mobile Menu Start -->
    <div class="mobile_menu fixed transition-all duration-300 top-0 -left-96 h-full bg-white dark:bg-powerBlack z-999 p-6 w-80 shadow-lg [&.is-menu-open]:left-0 flex flex-col lg:hidden">
        <div class="relative">
            <a href="index.html">
                <img 
                    src="assets/img/logo.svg" 
                    alt="bentoMan - Personal portfolio resume template"
                    class="max-md:w-32"
                >
            </a>
            <button class="close_menu absolute right-0 top-0 w-8 h-8 bg-powerBlack dark:bg-pastelGrey text-white dark:text-black flex items-center justify-center text-sm rounded-sm">
                <i class="fal fa-times"></i>
            </button>
        </div>
        <div class="my-12 h-calc(100vh_-_16rem) overflow-y-scroll space-y-2 *:text-powerBlack *:bg-slate-50 dark:*:bg-metalBlack dark:*:text-white *:flex *:items-center *:justify-between *:py-2.5 *:px-4 *:rounded-md *:text-regular">
            <a href="index.html" class="[&.active]:bg-powerBlack dark:[&.active]:bg-black [&.active]:text-white dark:[&.active]:text-white">
                Home
            </a>
            <a href="about.html" class="[&.active]:bg-powerBlack dark:[&.active]:bg-black [&.active]:text-white dark:[&.active]:text-white">
                About
            </a>
            <a href="projects.html" class="[&.active]:bg-powerBlack dark:[&.active]:bg-black [&.active]:text-white dark:[&.active]:text-white">
                Projects
            </a>
            <a href="services.html" class="[&.active]:bg-powerBlack dark:[&.active]:bg-black [&.active]:text-white dark:[&.active]:text-white">
                Services
            </a>
            <a href="blog.html" class="[&.active]:bg-powerBlack dark:[&.active]:bg-black [&.active]:text-white dark:[&.active]:text-white">
                Blog
            </a>
            <a href="contact.html" class="active [&.active]:bg-powerBlack dark:[&.active]:bg-black [&.active]:text-white dark:[&.active]:text-white">
                Contact
            </a>
        </div>
        <div class="mt-auto">
            <div class="cta_button text-center space-y-1">
                <p  class="font-medium">
                    Have an idea?
                </p>
                <a href="#" class="bg-darkGray dark:bg-metalBlack inline-flex text-white font-semibold py-3 px-16 leading-1.75 rounded-md">
                    Let's Talk
                </a>
            </div>
        </div>
    </div>
    <!-- Mobile menu end -->

    <!-- Overlay for mobile menu visible -->
    <div class="mobile_overlay fixed inset-0 z-50 bg-black/60 transition-all opacity-0 invisible [&.is-menu-open]:opacity-100 [&.is-menu-open]:visible lg:hidden"></div>
    <!--~~./ end Header ~~-->

    
    <!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    Start Main Content
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
    <div class="main-content mt-28 md:mt-32 lg:mt-36 xl:mt-48">
        <div class="container space-y-6">

            <div class="max-w-screen-lg text-center mx-auto rounded-2xl p-4 lg:p-6 xl:p-10 bg-gradient-to-b from-milkWhite to-seashell dark:from-metalBlack dark:to-oilBlack border border-gray-100 dark:border-white/5">
                <div>
                    <?php
                        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                            header('Location: contact.html', true, 302);
                            exit;
                        }

                        function isInjected($str) {
                            $injections = array('(\n+)',
                                '(\r+)',
                                '(\t+)',
                                '(%0A+)',
                                '(%0D+)',
                                '(%08+)',
                                '(%09+)'
                            );
                            $inject = join('|', $injections);
                            $inject = "/$inject/i";
                            return preg_match($inject, $str);
                        }

                        $webmaster_email = 'hello@ensorlogs.com';
                        $name = trim($_POST['clientName'] ?? '');
                        $email_address = trim($_POST['clientEmail'] ?? '');
                        $subject = trim($_POST['contactSubject'] ?? '');
                        $message = trim($_POST['contact__message'] ?? '');
                        $captcha_in = trim($_POST['contact_captcha'] ?? '');
                        $honeypot = trim($_POST['website'] ?? '');

                        $expected_captcha = 11;

                        if ($honeypot !== '') {
                            ?>
                                <h1 class="font-bold text-darkGray dark:text-pastelGrey text-xl lg:text-2xl mb-2">
                                    No se pudo enviar
                                </h1>
                                <p>
                                    Vuelve a intentarlo desde el formulario de contacto.
                                </p>
                            <?php
                        } elseif ((int) $captcha_in !== $expected_captcha) {
                            ?>
                                <h1 class="font-bold text-darkGray dark:text-pastelGrey text-xl lg:text-2xl mb-2">
                                    Verificación incorrecta
                                </h1>
                                <p>
                                    La respuesta anti-spam no coincide. Pulsa atrás en el navegador y revisa la suma (5 + 6).
                                </p>
                            <?php
                        } elseif ($name === '' || $email_address === '' || $subject === '' || $message === '') {
                            ?>
                                <h1 class="font-bold text-darkGray dark:text-pastelGrey text-xl lg:text-2xl mb-2">
                                    Faltan datos
                                </h1>
                                <p>
                                    Completa todos los campos obligatorios antes de enviar el formulario.
                                </p>
                            <?php
                        } elseif (! filter_var($email_address, FILTER_VALIDATE_EMAIL)) {
                            ?>
                                <h1 class="font-bold text-darkGray dark:text-pastelGrey text-xl lg:text-2xl mb-2">
                                    Correo no válido
                                </h1>
                                <p>
                                    Indica una dirección de correo electrónico válida.
                                </p>
                            <?php
                        } elseif (isInjected($email_address) || isInjected($name) || isInjected($subject) || isInjected($message)) {
                            ?>
                                <h1 class="font-bold text-darkGray dark:text-pastelGrey text-xl lg:text-2xl mb-2">
                                    No se pudo enviar
                                </h1>
                                <p>
                                    El mensaje contiene caracteres no permitidos. Revísalo e inténtalo de nuevo.
                                </p>
                            <?php
                        } else {
                            $msg = "Nombre: " . $name . "\r\n" .
                                "Email: " . $email_address . "\r\n" .
                                "Asunto: " . $subject . "\r\n\r\n" .
                                "Mensaje:\r\n" . $message;

                            $subject_line = '[Contacto ensorlogs.com] ' . $subject;
                            $headers = 'From: Ensorlogs <hello@ensorlogs.com>' . "\r\n" .
                                'Reply-To: ' . $email_address . "\r\n" .
                                'Content-Type: text/plain; charset=UTF-8' . "\r\n" .
                                'X-Mailer: PHP/' . phpversion();

                            @mail($webmaster_email, $subject_line, $msg, $headers);
                            ?>
                                <h1 class="font-bold text-darkGray dark:text-pastelGrey text-xl lg:text-2xl mb-2">
                                    Mensaje enviado
                                </h1>
                                <p>
                                    Gracias por escribir. Tu mensaje se ha enviado a hello@ensorlogs.com y te responderé lo antes posible.
                                </p>
                                <br>
                                <a href="index.html" class="group ensor-btn ensor-btn-primary text-regular py-2 lg:py-3 px-7 lg:px-10 inline-flex items-center gap-2">
                                    <svg class="w-11 shrink-0 text-current opacity-90 transition-all duration-300 group-hover:mr-2" viewBox="0 0 65 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M20 7.87214C19.5842 7.06683 19.0388 6.31114 18.3637 5.63604C14.849 2.1213 9.15055 2.1213 5.6358 5.63604C2.12108 9.15076 2.12106 14.8492 5.6358 18.364C9.15052 21.8787 14.849 21.8787 18.3637 18.364C19.0388 17.6889 19.5842 16.9332 20 16.1279" stroke="currentcolor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M64 12L7 12L11 8M11 16L9.5 14.5" stroke="currentcolor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    Volver al inicio
                                </a>
                            <?php
                        }
                    ?>
                </div>

            </div>

        </div>
    </div>
    <!--~~./ end Main Content ~~-->



    <!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    Start Footer
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
    <footer class="mt-24 pb-8">
        <div class="container text-center space-y-6">
            <h5 class="text-powerBlack dark:text-pastelGrey font-semibold text-4xl xl:text-6xl">
                Let's Talk
            </h5>
            <p>
                <a href="#" class="text-powerBlack dark:text-pastelGrey py-4 px-12 rounded-4xl bg-gradient-to-b from-milkWhite to-seashell dark:from-metalBlack dark:to-oilBlack text-regular xl:text-lg inline-flex items-center border border-gray-100  dark:border-white/5">
                    youremail@domain.com
                </a>
            </p>
            <p>
                &copy;2024 <a href="#" class="text-darkGray font-medium dark:text-white">bentoMan</a>. All Rights Reserved
            </p>
        </div>
    </footer>
    <!--~~ Footer End ~~-->

    </main>
    <!--~~ Main End ~~-->
 
    
    
    <!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        Start ScrollToTop
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
    <a href='#top' title='Scroll Top' id='scroll-top' class='topbutton fixed right-4 xl:right-7 2xl:right-8 bottom-6 xl:bottom-7.5 w-13 h-13 text-lg rounded-full bg-white dark:bg-powerBlack shadow-sm shadow-slate-400 grid place-content-center text-black dark:text-white opacity-0 invisible transition duration-200 [&.btn-show]:opacity-100 [&.btn-show]:visible z-[9999]'>
        <i class='far fa-level-up-alt'></i>
    </a>
    <!--~~ End Scroll to Top ~~-->

    <!-- Js Library Start -->
    <script src="assets/js/jquery-3.6.0.min.js"></script>
    <script src="assets/js/waypoints.min.js"></script>
    <script src="assets/js/tw-elements.umd.min.js"></script>
    <script src="assets/js/swiper-bundle.min.js"></script>
    <script src="assets/js/aos.js"></script>
    <script src="assets/js/tilt.jquery.min.js"></script>
    <script src="assets/js/script.js"></script>
    <script src="assets/js/theme-mode.js"></script>
    <!-- Js Library End -->
</body>

</html>
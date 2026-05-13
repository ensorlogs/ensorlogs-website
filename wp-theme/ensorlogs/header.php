<?php
/**
 * Cabecera global (misma estructura que el HTML estático).
 *
 * @package Ensorlogs
 */
if (!defined('ABSPATH')) {
    exit;
}
$t_uri    = get_template_directory_uri();
$ensor_mode = function_exists('ensorlogs_get_theme_default_mode') ? ensorlogs_get_theme_default_mode() : 'light';
?><!DOCTYPE html>
<html id="top" <?php language_attributes(); ?> class="<?php echo $ensor_mode === 'dark' ? 'dark' : ''; ?>">
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <script>(function(){try{var m=<?php echo wp_json_encode($ensor_mode); ?>;var ls=null;try{ls=localStorage.theme;}catch(e){}if(ls==='dark'){document.documentElement.classList.add('dark');return;}if(ls==='light'){document.documentElement.classList.remove('dark');return;}if(m==='dark'){document.documentElement.classList.add('dark');return;}if(m==='system'&&window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches){document.documentElement.classList.add('dark');return;}document.documentElement.classList.remove('dark');}catch(e){}})();</script>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <?php $ensor_fonts_url = function_exists('ensorlogs_fonts_url') ? ensorlogs_fonts_url() : ''; ?>
    <?php if ($ensor_fonts_url !== '' && strpos($ensor_fonts_url, 'fonts.bunny.net') !== false) : ?>
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <?php elseif ($ensor_fonts_url !== '' && strpos($ensor_fonts_url, 'fonts.googleapis.com') !== false) : ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <?php endif; ?>
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
    <link rel="preload" as="image" href="<?php echo esc_url($t_uri); ?>/assets/img/Logos/ensorlogs2.png">
    <?php wp_head(); ?>
</head>

<body <?php body_class('relative bg-[#F5F7F9] dark:bg-powerBlack'); ?>>
<?php wp_body_open(); ?>
<a class="ensor-skip-link" href="#main-content"><?php esc_html_e('Saltar al contenido principal', 'ensorlogs'); ?></a>
<div class="preloader fixed z-999 h-screen w-full left-0 top-0 flex flex-col items-center justify-center bg-white dark:bg-powerBlack" role="status" aria-live="polite" aria-busy="true">
    <div class="ensor-preloader-inner flex flex-col items-center gap-7 px-6">
        <div class="ensor-preloader-logo">
            <div class="ensor-preloader-logo-zoom" aria-hidden="true">
                <img src="<?php echo esc_url($t_uri); ?>/assets/img/Logos/ensorlogs2.png" alt="" width="240" height="240" decoding="async" fetchpriority="high" class="ensor-preloader-base-img">
                <div class="ensor-preloader-fill">
                    <img src="<?php echo esc_url($t_uri); ?>/assets/img/Logos/ensorlogs2.png" alt="Ensorlogs" width="240" height="240" decoding="async" class="ensor-preloader-fill-img">
                </div>
            </div>
        </div>
        <p class="ensor-preloader-loading text-center text-[11px] font-semibold text-powerBlack md:text-xs dark:text-pastelGrey"></p>
    </div>
</div>

<main id="main-content" class="app">

<header class="my-4 lg:my-6 fixed top-0 w-full left-0 z-50 transition-all duration-300 [&.is-sticky]:mt-0">
    <div class="container">
        <div class="nav max-md:py-3 py-5 px-4 lg:px-6 xl:px-8 flex items-center justify-between bg-white dark:bg-[#1B1C1C] rounded-xl shadow-lg shadow-black/5">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="inline-flex items-center gap-3 min-w-0">
                <span class="ensor-header-logo-wrap rounded-full overflow-hidden border border-black/10 dark:border-white/10 shrink-0 grid place-content-center p-1.5">
                    <img src="<?php echo esc_url($t_uri); ?>/assets/img/Logos/ensorlogs2.png" alt="Ensorlogs" class="ensor-header-logo-img w-full h-full">
                </span>
                <span class="flex flex-col justify-center min-w-0 leading-none">
                    <span class="ensor-wordmark block text-powerBlack dark:text-white font-black tracking-tighter text-[1.05rem] md:text-xl uppercase">
                        ENSOR.<span class="ensor-logo-accent">LOGS</span>
                    </span>
                    <span class="mt-1 text-[11px] md:text-xs font-medium text-darkGray dark:text-pastelGrey tracking-tight leading-snug">
                        <?php echo esc_html(function_exists('ensorlogs_get_tagline') ? ensorlogs_get_tagline() : __('Bitácora de un geek', 'ensorlogs')); ?>
                        <img src="<?php echo esc_url(get_template_directory_uri()); ?>/assets/img/flag-venezuela.svg" alt="Hecho desde Venezuela" width="20" height="14" loading="lazy" decoding="async" class="ensor-flag-ve inline-block align-[-2px] ml-1"/>
                    </span>
                    <span class="ensor-tagline-rule mt-1.5 h-0.5 max-w-[12rem] rounded-full" aria-hidden="true"></span>
                </span>
            </a>
            <div class="main-menu *:transition-all *:text-darkGray *:inline-flex *:items-center dark:*:text-pastelGrey *:border *:border-transparent *:duration-300 *:px-6 *:py-2 *:leading-normal *:rounded-4xl *:font-semibold max-lg:hidden">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="ensor-nav-volver dark:[&.active]:text-white [&.active]:border-nobelGray dark:[&.active]:border-white/20 hover:text-smokeGray dark:hover:text-white">
                    Volver
                </a>
                <a href="<?php echo esc_url(home_url('/blog/')); ?>" class="dark:[&.active]:text-white [&.active]:border-nobelGray dark:[&.active]:border-white/20 hover:text-smokeGray dark:hover:text-white">
                    Hablemos de…
                </a>
                <a href="<?php echo esc_url(home_url('/about/')); ?>" class="dark:[&.active]:text-white [&.active]:border-nobelGray dark:[&.active]:border-white/20 hover:text-smokeGray dark:hover:text-white">
                    Sobre mi
                </a>
                <a href="<?php echo esc_url(home_url('/projects/')); ?>" class="dark:[&.active]:text-white [&.active]:border-nobelGray dark:[&.active]:border-white/20 hover:text-smokeGray dark:hover:text-white">
                    Proyectos
                </a>
                <a href="<?php echo esc_url(home_url('/services/')); ?>" class="dark:[&.active]:text-white [&.active]:border-nobelGray dark:[&.active]:border-white/20 hover:text-smokeGray dark:hover:text-white">
                    ¿Cómo puedo ayudarte?
                </a>
            </div>
            <div class="button-groups flex items-center gap-4 *:border *:border-flasWhite *:dark:border-flasBlack *:bg-gradient-to-b *:from-milkWhite *:to-seashell dark:*:from-metalBlack dark:*:to-oilBlack">
                <button class="btn_theme_switch btn-dark w-13 h-13 max-md:w-12 max-md:h-12 relative rounded-full group *:w-5 *:h-5 *:max-md:w-6 *:max-md:h-6 *:absolute *:top-1/2 *:left-1/2 *:-translate-x-1/2 *:-translate-y-1/2 " aria-label="Dark - Light Switch">
                    <svg class="sun group-[&.btn-light]:opacity-0" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M11 17.5C14.5899 17.5 17.5 14.5899 17.5 11C17.5 7.41015 14.5899 4.5 11 4.5C7.41015 4.5 4.5 7.41015 4.5 11C4.5 14.5899 7.41015 17.5 11 17.5Z" stroke="#CDD0DA" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M18.14 18.14L18.01 18.01M18.01 3.99L18.14 3.86L18.01 3.99ZM3.86 18.14L3.99 18.01L3.86 18.14ZM11 1.08V1V1.08ZM11 21V20.92V21ZM1.08 11H1H1.08ZM21 11H20.92H21ZM3.99 3.99L3.86 3.86L3.99 3.99Z" stroke="#CDD0DA" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <svg class="moon opacity-0 group-[&.btn-light]:opacity-100" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M1.03009 11.42C1.39009 16.57 5.76009 20.76 10.9901 20.99C14.6801 21.15 17.9801 19.43 19.9601 16.72C20.7801 15.61 20.3401 14.87 18.9701 15.12C18.3001 15.24 17.6101 15.29 16.8901 15.26C12.0001 15.06 8.00009 10.97 7.98009 6.13996C7.97009 4.83996 8.24009 3.60996 8.73009 2.48996C9.27009 1.24996 8.62009 0.659961 7.37009 1.18996C3.41009 2.85996 0.70009 6.84996 1.03009 11.42Z" stroke="#2F3236" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="ensor-cta-hablemos inline-flex items-center justify-center shrink-0 font-semibold py-2 px-5 md:py-2.5 md:px-7 leading-snug rounded-full">
                    <span>Hablemos</span>
                </a>
                <button type="button" class="text-darkGray dark:text-pastelGrey menu_toggle flex items-center text-xl lg:hidden">
                    <i class="fal fa-bars"></i>
                </button>
            </div>
        </div>
    </div>
</header>

<div class="mobile_menu fixed transition-all duration-300 top-0 -left-96 h-full bg-white dark:bg-powerBlack z-999 p-6 w-80 shadow-lg [&.is-menu-open]:left-0 flex flex-col lg:hidden">
    <div class="relative">
        <a href="<?php echo esc_url(home_url('/')); ?>" class="inline-flex items-center gap-2.5 pr-10">
            <span class="ensor-header-logo-wrap shrink-0 rounded-full overflow-hidden border border-black/10 dark:border-white/10 grid place-content-center p-1.5">
                <img src="<?php echo esc_url($t_uri); ?>/assets/img/Logos/ensorlogs2.png" alt="Ensorlogs" class="ensor-header-logo-img w-full h-full">
            </span>
            <span class="flex flex-col justify-center min-w-0 leading-none">
                <span class="ensor-wordmark block text-powerBlack dark:text-white font-black tracking-tighter text-base uppercase">
                    ENSOR.<span class="ensor-logo-accent">LOGS</span>
                </span>
                <span class="mt-0.5 text-[10px] font-medium text-darkGray dark:text-pastelGrey tracking-tight leading-snug">
                    <?php echo esc_html(function_exists('ensorlogs_get_tagline') ? ensorlogs_get_tagline() : __('Bitácora de un geek', 'ensorlogs')); ?>
                    <img src="<?php echo esc_url(get_template_directory_uri()); ?>/assets/img/flag-venezuela.svg" alt="Hecho desde Venezuela" width="20" height="14" loading="lazy" decoding="async" class="ensor-flag-ve inline-block align-[-2px] ml-1"/>
                </span>
                <span class="ensor-tagline-rule mt-1 h-0.5 max-w-[10rem] rounded-full" aria-hidden="true"></span>
            </span>
        </a>
        <button class="close_menu absolute right-0 top-0 w-8 h-8 bg-powerBlack dark:bg-pastelGrey text-white dark:text-black flex items-center justify-center text-sm rounded-sm">
            <i class="fal fa-times"></i>
        </button>
    </div>
    <div class="my-12 h-calc(100vh_-_16rem) overflow-y-scroll space-y-2 *:text-powerBlack *:bg-slate-50 dark:*:bg-metalBlack dark:*:text-white *:flex *:items-center *:justify-between *:py-2.5 *:px-4 *:rounded-md *:text-regular">
        <a href="<?php echo esc_url(home_url('/')); ?>" class="ensor-nav-volver [&.active]:bg-powerBlack dark:[&.active]:bg-black [&.active]:text-white dark:[&.active]:text-white">
            Volver
        </a>
        <a href="<?php echo esc_url(home_url('/blog/')); ?>" class="[&.active]:bg-powerBlack dark:[&.active]:bg-black [&.active]:text-white dark:[&.active]:text-white">
            Hablemos de…
        </a>
        <a href="<?php echo esc_url(home_url('/about/')); ?>" class="[&.active]:bg-powerBlack dark:[&.active]:bg-black [&.active]:text-white dark:[&.active]:text-white">
            Sobre mi
        </a>
        <a href="<?php echo esc_url(home_url('/projects/')); ?>" class="[&.active]:bg-powerBlack dark:[&.active]:bg-black [&.active]:text-white dark:[&.active]:text-white">
            Proyectos
        </a>
        <a href="<?php echo esc_url(home_url('/services/')); ?>" class="[&.active]:bg-powerBlack dark:[&.active]:bg-black [&.active]:text-white dark:[&.active]:text-white">
            ¿Cómo puedo ayudarte?
        </a>
    </div>
    <div class="mt-auto">
        <div class="cta_button text-center space-y-1">
            <p class="font-medium"><?php esc_html_e('¿Hablamos?', 'ensorlogs'); ?></p>
            <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="bg-darkGray dark:bg-metalBlack inline-flex text-white font-semibold py-3 px-16 leading-1.75 rounded-md">
                Contactar
            </a>
        </div>
    </div>
</div>

<div class="mobile_overlay fixed inset-0 z-50 bg-black/60 transition-all opacity-0 invisible [&.is-menu-open]:opacity-100 [&.is-menu-open]:visible lg:hidden"></div>

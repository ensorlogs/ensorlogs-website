<?php
/**
 * Log individual (CPT ensor_article).
 *
 * Renderiza:
 *  - Barra de progreso (fija, debajo del header del sitio).
 *  - Chip flotante con el tema de la sección actual.
 *  - Header con título, meta y badges de Stacks (taxonomía).
 *  - Filtro por audiencia (estudiante/profesional/profesor/datos…) si el
 *    contenido tiene secciones marcadas con data-aud.
 *  - TOC sticky en desktop + sheet flotante en móvil.
 *  - Cuerpo: el HTML migrado del editor clásico o bloques de Gutenberg.
 *
 * Compatibilidad: si el HTML antiguo trae su propia "shell" con `main-content`,
 * se renderiza tal cual (se respeta la versión migrada).
 *
 * @package Ensorlogs
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

while (have_posts()) {
    the_post();
    $post     = get_post();
    $content  = $post->post_content;
    // Compatibilidad con logs antiguos que guardaron el bloque <div class="main-content mt-28 ..."> completo
    // (en estos no aplicamos chrome porque ya lo trae el propio HTML migrado).
    $has_shell = (bool) preg_match('/<div[^>]*class=["\'][^"\']*main-content\s+mt-28/m', $content);

    if ($has_shell) {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo apply_filters('the_content', $content);
        continue;
    }

    $pid       = (int) $post->ID;
    $stacks    = array();
    $tax_terms = get_the_terms($pid, 'ensor_tema');
    if (is_array($tax_terms)) {
        foreach ($tax_terms as $t) {
            if ($t instanceof WP_Term) {
                $stacks[] = (string) $t->slug;
            }
        }
    }
    if (empty($stacks)) {
        $stacks_str = (string) get_post_meta($pid, '_ensor_temas', true);
        if ($stacks_str !== '') {
            $stacks = array_values(array_filter(array_map('sanitize_key', preg_split('/\s+/', $stacks_str) ?: array())));
        }
    }
    $primary    = (string) get_post_meta($pid, '_ensor_primary_tema', true);
    if ($primary === '' && !empty($stacks)) {
        $primary = (string) $stacks[0];
    }
    $stacks = array_values(array_unique($stacks));

    $hero_src = '';
    if (has_post_thumbnail($pid)) {
        $h = get_the_post_thumbnail_url($pid, 'large');
        if (is_string($h) && $h !== '') {
            $hero_src = $h;
        }
    }
    if ($hero_src === '') {
        $card = (string) get_post_meta($pid, '_ensor_card_image', true);
        $hero_src = function_exists('ensorlogs_resolve_public_asset_url')
            ? ensorlogs_resolve_public_asset_url($card)
            : '';
    }

    $audiences = array();
    if (function_exists('ensorlogs_detect_audiences')) {
        $audiences = ensorlogs_detect_audiences($content);
    }
    // También consideramos audiencias que vengan de las cajas meta (Contexto,
    // Datos, Como estudiante, …); estas se inyectan en `the_content` con un
    // filter a prioridad 22, así que en este momento aún no están en `$content`.
    if (function_exists('ensorlogs_article_section_audiences_with_value')) {
        $meta_auds = ensorlogs_article_section_audiences_with_value($pid);
        if (!empty($meta_auds)) {
            $audiences = array_values(array_unique(array_merge($audiences, $meta_auds)));
        }
    }
    $audience_labels = function_exists('ensorlogs_audience_labels') ? ensorlogs_audience_labels() : array();

    $time_attr = get_the_date('c');
    $time_show = strtolower(mysql2date('M Y', $post->post_date, true));
    $blog_filter_base = trailingslashit(home_url('/')) . 'blog/?tema=';
    ?>
    <div class="ensor-reader-progress" aria-hidden="true">
        <div class="ensor-reader-progress__fill"></div>
    </div>
    <div class="ensor-reader-topic" role="status" aria-live="polite">
        <span class="ensor-reader-topic__dot" aria-hidden="true"></span>
        <span class="ensor-reader-topic__text"></span>
    </div>

    <article id="post-<?php the_ID(); ?>" <?php post_class('ensor-reader main-content mt-28 md:mt-32 lg:mt-36 xl:mt-44'); ?>>
        <div class="container">
            <header class="ensor-reader-head mx-auto" data-aos="fade-up">
                <h1 class="font-bold text-3xl lg:text-4xl xl:text-5xl text-powerBlack dark:text-pastelGrey mb-3 leading-tight">
                    <?php the_title(); ?>
                </h1>
                <ul class="meta flex flex-wrap items-center gap-3 sm:gap-4 lg:gap-6 mb-6 text-sm lg:text-base text-darkGray dark:text-pastelGrey">
                    <?php if (function_exists('ensorlogs_lang_badge_html')) : ?>
                        <li>
                            <?php
                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            echo ensorlogs_lang_badge_html($pid);
                            ?>
                        </li>
                    <?php endif; ?>
                    <?php if ($primary !== '') : ?>
                        <li>
                            <?php
                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            echo function_exists('ensorlogs_stack_badge_html')
                                ? ensorlogs_stack_badge_html($primary, $blog_filter_base, 'ensor-reader-stack--xl')
                                : esc_html(ucfirst($primary));
                            ?>
                        </li>
                    <?php endif; ?>
                    <li>
                        <i class="fal fa-clock" aria-hidden="true"></i>
                        <time datetime="<?php echo esc_attr($time_attr); ?>"><?php echo esc_html(mb_convert_case($time_show, MB_CASE_TITLE, 'UTF-8')); ?></time>
                    </li>
                    <li>
                        <i class="fal fa-user" aria-hidden="true"></i>
                        <?php echo esc_html(function_exists('ensorlogs_get_author_name') ? ensorlogs_get_author_name() : 'EnsorLogs'); ?>
                    </li>
                    <li>
                        <i class="fal fa-book-open" aria-hidden="true"></i>
                        <?php
                        $words   = str_word_count(wp_strip_all_tags($content));
                        $minutes = max(1, (int) ceil($words / 220));
                        /* translators: %d: minutes to read */
                        $read_fmt = function_exists('ensorlogs_t')
                            ? ensorlogs_t('%d min de lectura', '%d min read')
                            : _n('%d min de lectura', '%d min de lectura', $minutes, 'ensorlogs');
                        echo esc_html(sprintf($read_fmt, $minutes));
                        ?>
                    </li>
                    <?php
                    if (function_exists('ensorlogs_render_quiz_status_badge')) {
                        $ensor_quiz_badge = ensorlogs_render_quiz_status_badge((int) get_the_ID());
                        if ($ensor_quiz_badge !== '') {
                            echo '<li>';
                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            echo $ensor_quiz_badge;
                            echo '</li>';
                        }
                    }
                    ?>
                </ul>

                <?php if (!empty($stacks) || !empty($audiences)) : ?>
                    <div class="ensor-reader-headcard">
                        <?php if (!empty($stacks)) : ?>
                            <div class="ensor-reader-stacks" aria-label="<?php esc_attr_e('Stacks tratados en este log', 'ensorlogs'); ?>">
                                <?php
                                foreach ($stacks as $slug) {
                                    if (function_exists('ensorlogs_stack_badge_html')) {
                                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                        echo ensorlogs_stack_badge_html((string) $slug, $blog_filter_base);
                                    }
                                }
                                ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($audiences)) : ?>
                            <nav class="ensor-reader-aud" aria-label="<?php esc_attr_e('Saltar a sección del log', 'ensorlogs'); ?>">
                                <!-- chips inyectadas por JS -->
                            </nav>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php
                if (function_exists('ensorlogs_render_podcast_card')) {
                    $ensor_podcast_html = ensorlogs_render_podcast_card((int) get_the_ID());
                    if ($ensor_podcast_html !== '') {
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        echo $ensor_podcast_html;
                    }
                }
                ?>
            </header>

            <?php if ($hero_src !== '') : ?>
            <div class="ensor-reader-hero ensor-reader-hero--parallax max-w-screen-lg mx-auto mt-2 mb-8 md:mb-10">
                <div class="ensor-reader-hero__frame">
                    <img
                        src="<?php echo esc_url($hero_src); ?>"
                        alt="<?php echo esc_attr(get_the_title()); ?>"
                        width="1600" height="900"
                        decoding="async" loading="eager" fetchpriority="high"
                        class="ensor-article-hero ensor-reader-hero__img rounded-xl xl:rounded-2xl w-full object-cover object-center"
                    >
                </div>
            </div>
            <?php endif; ?>

            <div class="ensor-reader-layout mx-auto">
                <aside class="ensor-reader-toc hidden lg:block" aria-label="<?php esc_attr_e('Índice del log', 'ensorlogs'); ?>">
                    <p class="ensor-reader-toc__title"><?php esc_html_e('En este log', 'ensorlogs'); ?></p>
                    <ul class="ensor-reader-toc__list"></ul>
                </aside>
                <div>
                    <div class="ensor-reader-body entry-content ensor-wp-content max-w-none">
                        <?php
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        echo apply_filters('the_content', $content);
                        ?>
                    </div>

                    <?php
                    if (function_exists('ensorlogs_render_quiz_section')) {
                        $ensor_quiz_html = ensorlogs_render_quiz_section((int) get_the_ID());
                        if ($ensor_quiz_html !== '') {
                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            echo $ensor_quiz_html;
                        }
                    }
                    ?>

                    <?php
                    if (function_exists('ensorlogs_render_log_rating_section')) {
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        echo ensorlogs_render_log_rating_section((int) get_the_ID());
                    }
                    ?>

                    <?php
                    if (function_exists('ensorlogs_render_log_share_section')) {
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        echo ensorlogs_render_log_share_section((int) get_the_ID());
                    }
                    ?>

                    <footer class="ensor-reader-footer mt-10 grid md:grid-cols-2 gap-4 md:gap-6 items-center">
                        <div class="ensor-reader-stacks-block flex flex-wrap items-center gap-3" aria-label="<?php esc_attr_e('Stacks del log', 'ensorlogs'); ?>">
                            <?php if (!empty($stacks)) : ?>
                                <span class="ensor-reader-stacks-label"><?php esc_html_e('Stacks usados:', 'ensorlogs'); ?></span>
                                <div class="ensor-reader-stacks flex flex-wrap gap-2">
                                    <?php
                                    if (function_exists('ensorlogs_stack_badge_html')) {
                                        foreach ($stacks as $slug) {
                                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                            echo ensorlogs_stack_badge_html((string) $slug, $blog_filter_base);
                                        }
                                    }
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="flex flex-wrap items-center gap-3 md:justify-end">
                            <a href="<?php echo esc_url(home_url('/blog/')); ?>" class="ensor-cta-hablemos inline-flex items-center justify-center shrink-0 font-semibold py-2 px-5 md:py-2.5 md:px-7 leading-snug rounded-full no-underline">
                                <span><?php esc_html_e('Más logs', 'ensorlogs'); ?></span>
                            </a>
                            <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="ensor-cta-hablemos inline-flex items-center justify-center shrink-0 font-semibold py-2 px-5 md:py-2.5 md:px-7 leading-snug rounded-full no-underline">
                                <span><?php esc_html_e('Hablemos', 'ensorlogs'); ?></span>
                            </a>
                            <?php
                            if (function_exists('ensorlogs_render_newsletter_button')) {
                                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                echo ensorlogs_render_newsletter_button();
                            }
                            ?>
                        </div>
                    </footer>
                </div>
            </div>
        </div>
    </article>

    <button type="button" class="ensor-reader-toc-toggle lg:hidden" aria-label="<?php esc_attr_e('Ver índice', 'ensorlogs'); ?>">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M4 6h13M4 12h13M4 18h13"/>
            <circle cx="20" cy="6" r="1.2" fill="currentColor"/>
            <circle cx="20" cy="12" r="1.2" fill="currentColor"/>
            <circle cx="20" cy="18" r="1.2" fill="currentColor"/>
        </svg>
    </button>
    <div class="ensor-reader-toc-sheet" role="dialog" aria-label="<?php esc_attr_e('Índice del log', 'ensorlogs'); ?>">
        <div class="ensor-reader-toc-sheet__panel">
            <p class="ensor-reader-toc__title"><?php esc_html_e('En este log', 'ensorlogs'); ?></p>
            <ul class="ensor-reader-toc__list"></ul>
        </div>
    </div>
    <?php
}

get_footer();

<?php
/**
 * Template Name: Documento legal (Ensorlogs)
 *
 * ES: contenido de la página en WordPress (post_content), con fallback al seed en español.
 * EN (/en/legal/...): título, lead y cuerpo desde seed-html/legal/*.en.html.
 *
 * @package Ensorlogs
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

while (have_posts()) :
    the_post();

    $post_id      = get_the_ID();
    $page_slug    = get_post_field('post_name', $post_id);
    $page_excerpt = has_excerpt() ? get_the_excerpt() : '';
    $lang_en      = function_exists('ensorlogs_current_lang') && ensorlogs_current_lang() === 'en';

    $page_title = function_exists('ensorlogs_legal_title')
        ? ensorlogs_legal_title($page_slug)
        : get_the_title();

    $lead = '';
    if ($lang_en && function_exists('ensorlogs_legal_lead')) {
        $lead = ensorlogs_legal_lead($page_slug);
    } elseif ($page_excerpt !== '') {
        $lead = $page_excerpt;
    } elseif (function_exists('ensorlogs_legal_lead')) {
        $lead = ensorlogs_legal_lead($page_slug);
    }

    $tags = function_exists('ensorlogs_legal_tags')
        ? ensorlogs_legal_tags($page_slug)
        : array();

    $updated_ts    = (int) get_post_modified_time('U', true);
    $updated_label = $updated_ts ? wp_date('d/m/Y', $updated_ts) : '';

    $contact_email = apply_filters('ensorlogs_contact_email', get_option('admin_email'));
    $contact_url   = function_exists('ensorlogs_lang_url')
        ? ensorlogs_lang_url('/contact/')
        : (get_page_by_path('contact') ? get_permalink(get_page_by_path('contact')) : home_url('/contact/'));

    $cross_links = function_exists('ensorlogs_legal_cross_labels')
        ? ensorlogs_legal_cross_labels()
        : array();

    $seed_body = function_exists('ensorlogs_legal_body_html') ? ensorlogs_legal_body_html($page_slug) : '';
    ?>
    <main class="ensor-legal-page mt-28 md:mt-32 lg:mt-40 mb-12" id="main-content">
        <section class="ensor-legal-hero" aria-labelledby="legal-title">
            <div class="ensor-legal-hero__inner container max-w-[1180px] mx-auto px-4">
                <p class="ensor-legal-hero__eyebrow"><?php echo esc_html(function_exists('ensorlogs_t') ? ensorlogs_t('Documento legal · Ensorlogs', 'Legal document · Ensorlogs') : __('Documento legal · Ensorlogs', 'ensorlogs')); ?></p>
                <h1 id="legal-title" class="ensor-legal-hero__title"><?php echo esc_html($page_title); ?></h1>
                <?php if ($lead !== '') : ?>
                    <p class="ensor-legal-hero__lead"><?php echo esc_html($lead); ?></p>
                <?php endif; ?>
                <div class="ensor-legal-hero__meta">
                    <?php if ($updated_label !== '') : ?>
                        <span class="ensor-legal-pill ensor-legal-pill--accent"><span class="ensor-legal-pill__dot"></span><?php
                            echo esc_html(
                                function_exists('ensorlogs_t')
                                    ? sprintf(ensorlogs_t('Actualizado · %s', 'Updated · %s'), $updated_label)
                                    : sprintf(__('Actualizado · %s', 'ensorlogs'), $updated_label)
                            );
                        ?></span>
                    <?php endif; ?>
                    <?php foreach ($tags as $tag) : ?>
                        <span class="ensor-legal-pill"><?php echo esc_html($tag); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <div class="ensor-legal-wrap container max-w-[1180px] mx-auto px-4">
            <article id="post-<?php the_ID(); ?>" <?php post_class('ensor-legal-body entry-content ensor-wp-content'); ?>>
                <?php
                if ($lang_en && $seed_body !== '') {
                    echo wp_kses_post($seed_body);
                } elseif (!$lang_en) {
                    the_content();
                    if (!get_the_content() && $seed_body !== '') {
                        echo wp_kses_post($seed_body);
                    }
                } elseif ($seed_body !== '') {
                    echo wp_kses_post($seed_body);
                } else {
                    the_content();
                }
                ?>
            </article>
        </div>

        <div class="ensor-legal-foot container max-w-[1180px] mx-auto px-4">
            <div class="ensor-legal-cta-card">
                <div class="ensor-legal-cta-card__text">
                    <p class="ensor-legal-cta-card__title"><?php echo esc_html(function_exists('ensorlogs_t') ? ensorlogs_t('¿Te queda alguna duda con este documento?', 'Questions about this document?') : __('¿Te queda alguna duda con este documento?', 'ensorlogs')); ?></p>
                    <p class="ensor-legal-cta-card__sub">
                        <?php
                        if (function_exists('ensorlogs_t')) {
                            printf(
                                esc_html(ensorlogs_t(
                                    'Escríbenos a %1$s%2$s y te respondo personalmente. Si prefieres una conversación, puedes ir directo al formulario de contacto.',
                                    'Email us at %1$s%2$s and I will reply personally. If you prefer a conversation, go straight to the contact form.'
                                )),
                                '<a href="mailto:' . esc_attr($contact_email) . '">' . esc_html($contact_email),
                                '</a>'
                            );
                        } else {
                            printf(
                                esc_html__('Escríbenos a %1$s%2$s y te respondo personalmente. Si prefieres una conversación, puedes ir directo al formulario de contacto.', 'ensorlogs'),
                                '<a href="mailto:' . esc_attr($contact_email) . '">' . esc_html($contact_email),
                                '</a>'
                            );
                        }
                        ?>
                    </p>
                </div>
                <a class="ensor-legal-cta-card__action" href="<?php echo esc_url($contact_url); ?>">
                    <span><?php echo esc_html(function_exists('ensorlogs_t') ? ensorlogs_t('Hablemos', "Let's talk") : __('Hablemos', 'ensorlogs')); ?></span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="M13 5l7 7-7 7"/></svg>
                </a>
            </div>
            <nav class="ensor-legal-cross" aria-label="<?php echo esc_attr(function_exists('ensorlogs_t') ? ensorlogs_t('Otros documentos legales', 'Other legal documents') : __('Otros documentos legales', 'ensorlogs')); ?>">
                <p class="ensor-legal-cross__title"><?php echo esc_html(function_exists('ensorlogs_t') ? ensorlogs_t('Documentos relacionados', 'Related documents') : __('Documentos relacionados', 'ensorlogs')); ?></p>
                <?php foreach ($cross_links as $slug => $label) :
                    if (!get_page_by_path('legal/' . $slug)) {
                        continue;
                    }
                    $url = function_exists('ensorlogs_lang_url')
                        ? ensorlogs_lang_url('/legal/' . $slug . '/')
                        : get_permalink(get_page_by_path('legal/' . $slug));
                    $is_current = ($slug === $page_slug);
                    ?>
                    <a href="<?php echo esc_url($url); ?>"<?php echo $is_current ? ' class="is-current" aria-current="page"' : ''; ?>><?php echo esc_html($label); ?></a>
                <?php endforeach; ?>
            </nav>
        </div>
    </main>
    <?php
endwhile;

get_footer();

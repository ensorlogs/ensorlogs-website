<?php
/**
 * Template Name: Documento legal (Ensorlogs)
 *
 * Renderiza páginas legales (privacidad, cookies, aviso legal, accesibilidad)
 * con el branding del sitio: hero con eyebrow / pills, body editable y CTA
 * final con navegación cruzada.
 *
 * El contenido editable (`post_content`) debe contener solo los `<h2>`/`<p>`
 * de cada sección; el chrome lo envuelve este template.
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
    $page_title   = get_the_title();
    $page_excerpt = has_excerpt() ? get_the_excerpt() : '';

    // Mapa de meta por slug: lead + tags personalizados.
    $legal_meta = array(
        'privacidad' => array(
            'lead' => __('Esta política describe qué datos personales tratamos cuando interactúas con Ensorlogs, con qué finalidad, durante cuánto tiempo y qué derechos puedes ejercer en cualquier momento.', 'ensorlogs'),
            'tags' => array('RGPD', 'LOPDGDD', 'LSSI-CE'),
        ),
        'cookies' => array(
            'lead' => __('Aquí explicamos qué cookies (y tecnologías equivalentes) usamos en Ensorlogs, para qué sirven y cómo puedes controlarlas en cualquier momento desde la propia web o tu navegador.', 'ensorlogs'),
            'tags' => array('Cookies', 'RGPD', 'LSSI-CE'),
        ),
        'aviso-legal' => array(
            'lead' => __('Información obligatoria del titular del sitio, las condiciones bajo las que se ofrece el servicio y el marco legal aplicable a cualquier persona que navegue por Ensorlogs.', 'ensorlogs'),
            'tags' => array('Aviso legal', 'Condiciones', 'Propiedad intelectual'),
        ),
        'accesibilidad' => array(
            'lead' => __('Nuestro compromiso para que cualquier persona pueda leer, escuchar y usar Ensorlogs: medidas técnicas aplicadas, contenidos pendientes y cómo avisarnos si encuentras una barrera.', 'ensorlogs'),
            'tags' => array('WCAG 2.2 AA', 'UNE-EN 301 549', 'EAA'),
        ),
    );

    $lead = $page_excerpt !== ''
        ? $page_excerpt
        : (isset($legal_meta[$page_slug]['lead']) ? $legal_meta[$page_slug]['lead'] : '');
    $tags = isset($legal_meta[$page_slug]['tags']) ? $legal_meta[$page_slug]['tags'] : array();

    $updated_ts    = (int) get_post_modified_time('U', true);
    $updated_label = $updated_ts ? wp_date('d/m/Y', $updated_ts) : '';

    // Email de contacto: filtrable, con fallback al option de admin.
    $contact_email = apply_filters('ensorlogs_contact_email', get_option('admin_email'));
    $contact_page = get_page_by_path('contact');
    $contact_url  = $contact_page ? get_permalink($contact_page) : home_url('/contact/');

    // Navegación cruzada (todas las páginas legales que existan).
    $cross_links = array(
        'aviso-legal'   => __('Aviso legal', 'ensorlogs'),
        'privacidad'    => __('Privacidad', 'ensorlogs'),
        'cookies'       => __('Cookies', 'ensorlogs'),
        'accesibilidad' => __('Accesibilidad', 'ensorlogs'),
    );
    ?>
    <main class="ensor-legal-page mt-28 md:mt-32 lg:mt-40 mb-12" id="main-content">
        <section class="ensor-legal-hero" aria-labelledby="legal-title">
            <div class="ensor-legal-hero__inner container max-w-[1180px] mx-auto px-4">
                <p class="ensor-legal-hero__eyebrow"><?php esc_html_e('Documento legal · Ensorlogs', 'ensorlogs'); ?></p>
                <h1 id="legal-title" class="ensor-legal-hero__title"><?php echo esc_html($page_title); ?></h1>
                <?php if ($lead !== '') : ?>
                    <p class="ensor-legal-hero__lead"><?php echo esc_html($lead); ?></p>
                <?php endif; ?>
                <div class="ensor-legal-hero__meta">
                    <?php if ($updated_label !== '') : ?>
                        <span class="ensor-legal-pill ensor-legal-pill--accent"><span class="ensor-legal-pill__dot"></span><?php
                            /* translators: %s: fecha de última actualización (d/m/Y). */
                            printf(esc_html__('Actualizado · %s', 'ensorlogs'), esc_html($updated_label));
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
                <?php the_content(); ?>
            </article>
        </div>

        <div class="ensor-legal-foot container max-w-[1180px] mx-auto px-4">
            <div class="ensor-legal-cta-card">
                <div class="ensor-legal-cta-card__text">
                    <p class="ensor-legal-cta-card__title"><?php esc_html_e('¿Te queda alguna duda con este documento?', 'ensorlogs'); ?></p>
                    <p class="ensor-legal-cta-card__sub">
                        <?php
                        printf(
                            /* translators: 1: email anchor markup, 2: closing anchor. */
                            esc_html__('Escríbenos a %1$s%2$s y te respondo personalmente. Si prefieres una conversación, puedes ir directo al formulario de contacto.', 'ensorlogs'),
                            '<a href="mailto:' . esc_attr($contact_email) . '">' . esc_html($contact_email),
                            '</a>'
                        );
                        ?>
                    </p>
                </div>
                <a class="ensor-legal-cta-card__action" href="<?php echo esc_url($contact_url); ?>">
                    <span><?php esc_html_e('Hablemos', 'ensorlogs'); ?></span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="M13 5l7 7-7 7"/></svg>
                </a>
            </div>
            <nav class="ensor-legal-cross" aria-label="<?php esc_attr_e('Otros documentos legales', 'ensorlogs'); ?>">
                <p class="ensor-legal-cross__title"><?php esc_html_e('Documentos relacionados', 'ensorlogs'); ?></p>
                <?php foreach ($cross_links as $slug => $label) :
                    $page = get_page_by_path('legal/' . $slug);
                    if (!$page) {
                        continue;
                    }
                    $is_current = ($slug === $page_slug);
                    ?>
                    <a href="<?php echo esc_url(get_permalink($page)); ?>"<?php echo $is_current ? ' class="is-current" aria-current="page"' : ''; ?>><?php echo esc_html($label); ?></a>
                <?php endforeach; ?>
            </nav>
        </div>
    </main>
    <?php
endwhile;

get_footer();

<?php
/**
 * Compartir log en redes sociales (CPT ensor_article).
 *
 * @package Ensorlogs
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @return array<int, array{key: string, label: string, url: string, brand: string}>
 */
function ensorlogs_get_log_share_links(int $post_id): array
{
    if ($post_id <= 0 || get_post_type($post_id) !== 'ensor_article') {
        return array();
    }

    $permalink = get_permalink($post_id);
    if (!is_string($permalink) || $permalink === '') {
        return array();
    }

    $title = get_the_title($post_id);
    $title = is_string($title) ? wp_strip_all_tags($title) : '';
    $url   = $permalink;

    $tweet_text = $title;
    if (function_exists('ensorlogs_t')) {
        $tweet_text = $title . ' — EnsorLogs';
    }

    $wa_text = rawurlencode($title . ' ' . $url);
    $mail_subject = rawurlencode($title);
    $mail_body    = rawurlencode(
        (function_exists('ensorlogs_t')
            ? ensorlogs_t('Te comparto este log de EnsorLogs:', 'Sharing this EnsorLogs log with you:')
            : __('Te comparto este log de EnsorLogs:', 'ensorlogs'))
        . "\n\n"
        . $title
        . "\n"
        . $url
    );

    $networks = array(
        array(
            'key'   => 'linkedin',
            'label' => 'LinkedIn',
            'url'   => 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode($url),
            'brand' => 'linkedin',
        ),
        array(
            'key'   => 'twitter',
            'label' => function_exists('ensorlogs_t') ? ensorlogs_t('X (Twitter)', 'X (Twitter)') : 'X (Twitter)',
            'url'   => 'https://twitter.com/intent/tweet?url=' . rawurlencode($url) . '&text=' . rawurlencode($tweet_text),
            'brand' => 'twitter',
        ),
        array(
            'key'   => 'facebook',
            'label' => 'Facebook',
            'url'   => 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($url),
            'brand' => 'facebook',
        ),
        array(
            'key'   => 'whatsapp',
            'label' => 'WhatsApp',
            'url'   => 'https://wa.me/?text=' . $wa_text,
            'brand' => 'whatsapp',
        ),
        array(
            'key'   => 'email',
            'label' => function_exists('ensorlogs_t') ? ensorlogs_t('Email', 'Email') : 'Email',
            'url'   => 'mailto:?subject=' . $mail_subject . '&body=' . $mail_body,
            'brand' => 'email',
        ),
    );

    return $networks;
}

function ensorlogs_log_share_icon_svg(string $brand): string
{
    switch ($brand) {
        case 'linkedin':
            return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M20.45 20.45h-3.56v-5.57c0-1.33-.03-3.04-1.85-3.04-1.85 0-2.13 1.45-2.13 2.94v5.67H9.35V9h3.42v1.56h.05c.47-.9 1.63-1.85 3.35-1.85 3.58 0 4.24 2.36 4.24 5.43v6.31zM5.34 7.43a2.06 2.06 0 1 1 0-4.12 2.06 2.06 0 0 1 0 4.12zM7.12 20.45H3.56V9h3.56v11.45z"/></svg>';
        case 'twitter':
            return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231 5.451-6.231zm-1.161 17.52h1.833L7.084 4.126H5.117L17.083 19.77z"/></svg>';
        case 'facebook':
            return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047V9.413c0-3.025 1.792-4.697 4.533-4.697 1.312 0 2.686.236 2.686.236v2.971H15.83c-1.491 0-1.956.93-1.956 1.874v2.25h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z"/></svg>';
        case 'whatsapp':
            return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>';
        case 'email':
        default:
            return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm0 4-8 5L4 8V6l8 5 8-5v2z"/></svg>';
    }
}

function ensorlogs_render_log_share_section(int $post_id): string
{
    $links = ensorlogs_get_log_share_links($post_id);
    if (!$links) {
        return '';
    }

    $title = function_exists('ensorlogs_t')
        ? ensorlogs_t('Compártelo en tus redes', 'Share on social media')
        : __('Compártelo en tus redes', 'ensorlogs');

    ob_start();
    ?>
    <section class="ensor-log-share" aria-label="<?php echo esc_attr($title); ?>">
        <h2 class="ensor-log-share__title"><?php echo esc_html($title); ?></h2>
        <ul class="ensor-log-share__list">
            <?php foreach ($links as $link) : ?>
                <?php
                $is_email = $link['brand'] === 'email';
                $target   = $is_email ? '_self' : '_blank';
                $rel      = $is_email ? '' : 'noopener noreferrer';
                ?>
                <li>
                    <a
                        class="ensor-log-share__btn ensor-log-share__btn--<?php echo esc_attr($link['brand']); ?>"
                        href="<?php echo esc_url($link['url']); ?>"
                        target="<?php echo esc_attr($target); ?>"
                        <?php echo $rel !== '' ? 'rel="' . esc_attr($rel) . '"' : ''; ?>
                    >
                        <span class="ensor-log-share__icon">
                            <?php
                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            echo ensorlogs_log_share_icon_svg($link['brand']);
                            ?>
                        </span>
                        <span class="ensor-log-share__label"><?php echo esc_html($link['label']); ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php
    return (string) ob_get_clean();
}

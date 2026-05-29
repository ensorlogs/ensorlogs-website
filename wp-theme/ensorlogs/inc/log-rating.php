<?php
/**
 * Valoración con estrellas al final de cada log (CPT ensor_article).
 *
 * @package Ensorlogs
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @return array{average: float, count: int}
 */
function ensorlogs_get_log_rating_stats(int $post_id): array
{
    if ($post_id <= 0) {
        return array('average' => 0.0, 'count' => 0);
    }
    $count = (int) get_post_meta($post_id, '_ensor_rating_count', true);
    $sum   = (int) get_post_meta($post_id, '_ensor_rating_sum', true);
    if ($count <= 0 || $sum <= 0) {
        return array('average' => 0.0, 'count' => 0);
    }
    return array(
        'average' => round($sum / $count, 1),
        'count'   => $count,
    );
}

function ensorlogs_log_rating_fingerprint(int $post_id): string
{
    $ip = '';
    if (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = (string) wp_unslash($_SERVER['REMOTE_ADDR']);
    }
    $ua = '';
    if (!empty($_SERVER['HTTP_USER_AGENT'])) {
        $ua = (string) wp_unslash($_SERVER['HTTP_USER_AGENT']);
    }
    return hash('sha256', $post_id . '|' . $ip . '|' . $ua . '|' . wp_salt('auth'));
}

/**
 * @return int 1–5 si ya votó; 0 si no.
 */
function ensorlogs_get_user_log_rating(int $post_id): int
{
    if ($post_id <= 0) {
        return 0;
    }
    $voters = get_post_meta($post_id, '_ensor_rating_voters', true);
    if (!is_array($voters)) {
        return 0;
    }
    $fp = ensorlogs_log_rating_fingerprint($post_id);
    if (!isset($voters[$fp])) {
        return 0;
    }
    $rating = (int) $voters[$fp];
    return ($rating >= 1 && $rating <= 5) ? $rating : 0;
}

/**
 * @return array{average: float, count: int, userRating: int}|WP_Error
 */
function ensorlogs_submit_log_rating(int $post_id, int $rating)
{
    if ($post_id <= 0 || get_post_type($post_id) !== 'ensor_article') {
        return new WP_Error('invalid_post', __('Log no válido.', 'ensorlogs'), array('status' => 404));
    }
    if ($rating < 1 || $rating > 5) {
        return new WP_Error('invalid_rating', __('La valoración debe ser entre 1 y 5 estrellas.', 'ensorlogs'), array('status' => 400));
    }

    $fp     = ensorlogs_log_rating_fingerprint($post_id);
    $voters = get_post_meta($post_id, '_ensor_rating_voters', true);
    if (!is_array($voters)) {
        $voters = array();
    }
    if (isset($voters[$fp])) {
        return new WP_Error('already_rated', __('Ya valoraste este log.', 'ensorlogs'), array('status' => 409));
    }

    $voters[$fp] = $rating;
    update_post_meta($post_id, '_ensor_rating_voters', $voters);

    $count = (int) get_post_meta($post_id, '_ensor_rating_count', true);
    $sum   = (int) get_post_meta($post_id, '_ensor_rating_sum', true);
    update_post_meta($post_id, '_ensor_rating_count', $count + 1);
    update_post_meta($post_id, '_ensor_rating_sum', $sum + $rating);

    $stats = ensorlogs_get_log_rating_stats($post_id);
    return array(
        'average'     => $stats['average'],
        'count'       => $stats['count'],
        'userRating'  => $rating,
    );
}

function ensorlogs_render_log_rating_section(int $post_id): string
{
    if ($post_id <= 0 || get_post_type($post_id) !== 'ensor_article') {
        return '';
    }

    $stats       = ensorlogs_get_log_rating_stats($post_id);
    $user_rating = ensorlogs_get_user_log_rating($post_id);
    $title       = function_exists('ensorlogs_t')
        ? ensorlogs_t('¿De cuánta utilidad te ha parecido este contenido?', 'How useful did you find this content?')
        : __('¿De cuánta utilidad te ha parecido este contenido?', 'ensorlogs');
    $hint = function_exists('ensorlogs_t')
        ? ensorlogs_t('¡Haz clic en las estrellas para valorarlo!', 'Click the stars to rate it!')
        : __('¡Haz clic en las estrellas para valorarlo!', 'ensorlogs');
    $thanks = function_exists('ensorlogs_t')
        ? ensorlogs_t('Gracias por tu valoración.', 'Thanks for your rating.')
        : __('Gracias por tu valoración.', 'ensorlogs');
    $stats_label = ensorlogs_format_log_rating_stats($stats['average'], $stats['count']);

    $attrs = array(
        'class'             => 'ensor-log-rating',
        'data-post-id'      => (string) $post_id,
        'data-average'      => (string) $stats['average'],
        'data-count'        => (string) $stats['count'],
        'data-user-rating'  => (string) $user_rating,
        'aria-label'        => $title,
    );
    if ($user_rating > 0) {
        $attrs['data-voted'] = '1';
    }

    $attr_html = '';
    foreach ($attrs as $key => $value) {
        $attr_html .= ' ' . $key . '="' . esc_attr($value) . '"';
    }

    ob_start();
    ?>
    <section<?php echo $attr_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
        <h2 class="ensor-log-rating__title"><?php echo esc_html($title); ?></h2>
        <p class="ensor-log-rating__hint"><?php echo esc_html($hint); ?></p>
        <div class="ensor-log-rating__stars" role="group" aria-label="<?php echo esc_attr($title); ?>">
            <?php for ($i = 1; $i <= 5; $i++) : ?>
                <?php
                $star_label = function_exists('ensorlogs_t')
                    ? ensorlogs_t(sprintf('%d de 5 estrellas', $i), sprintf('%d out of 5 stars', $i))
                    : sprintf(__('%d de 5 estrellas', 'ensorlogs'), $i);
                ?>
                <button
                    type="button"
                    class="ensor-log-rating__star"
                    data-value="<?php echo esc_attr((string) $i); ?>"
                    aria-label="<?php echo esc_attr($star_label); ?>"
                    <?php echo $user_rating > 0 ? ' disabled' : ''; ?>
                >
                    <svg class="ensor-log-rating__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path d="M12 2.5l2.76 5.59 6.17.9-4.47 4.36 1.06 6.15L12 16.98 6.48 19.5l1.06-6.15L3.07 8.99l6.17-.9L12 2.5z"/>
                    </svg>
                </button>
            <?php endfor; ?>
        </div>
        <p class="ensor-log-rating__stats"<?php echo $stats['count'] <= 0 ? ' hidden' : ''; ?>>
            <?php echo esc_html($stats_label); ?>
        </p>
        <p class="ensor-log-rating__empty"<?php echo $stats['count'] > 0 ? ' hidden' : ''; ?>>
            <?php
            echo esc_html(
                function_exists('ensorlogs_t')
                    ? ensorlogs_t('Sé la primera persona en valorar este log.', 'Be the first to rate this log.')
                    : __('Sé la primera persona en valorar este log.', 'ensorlogs')
            );
            ?>
        </p>
        <p class="ensor-log-rating__thanks"<?php echo $user_rating <= 0 ? ' hidden' : ''; ?>>
            <?php echo esc_html($thanks); ?>
        </p>
        <p class="ensor-log-rating__error" role="alert" hidden></p>
    </section>
    <?php
    return (string) ob_get_clean();
}

function ensorlogs_format_log_rating_stats(float $average, int $count): string
{
    if ($count <= 0) {
        return '';
    }
    if (function_exists('ensorlogs_t')) {
        return ensorlogs_t(
            sprintf('Promedio de puntuación %s / 5. Total de votos: %d', number_format_i18n($average, 1), $count),
            sprintf('Average rating %s / 5. Total votes: %d', number_format_i18n($average, 1), $count)
        );
    }
    return sprintf(
        /* translators: 1: average rating, 2: vote count */
        __('Promedio de puntuación %1$s / 5. Total de votos: %2$d', 'ensorlogs'),
        number_format_i18n($average, 1),
        $count
    );
}

function ensorlogs_log_rating_client_config(int $post_id): array
{
    $stats = ensorlogs_get_log_rating_stats($post_id);
    return array(
        'restUrl'     => rest_url('ensorlogs/v1/log/' . $post_id . '/rate'),
        'nonce'       => wp_create_nonce('wp_rest'),
        'postId'      => $post_id,
        'average'     => $stats['average'],
        'count'       => $stats['count'],
        'userRating'  => ensorlogs_get_user_log_rating($post_id),
        'i18n'        => array(
            'stats'   => function_exists('ensorlogs_t')
                ? ensorlogs_t('Promedio de puntuación %s / 5. Total de votos: %d', 'Average rating %s / 5. Total votes: %d')
                : __('Promedio de puntuación %s / 5. Total de votos: %d', 'ensorlogs'),
            'thanks'  => function_exists('ensorlogs_t')
                ? ensorlogs_t('Gracias por tu valoración.', 'Thanks for your rating.')
                : __('Gracias por tu valoración.', 'ensorlogs'),
            'error'   => function_exists('ensorlogs_t')
                ? ensorlogs_t('No se pudo guardar tu valoración. Inténtalo de nuevo.', 'Could not save your rating. Please try again.')
                : __('No se pudo guardar tu valoración. Inténtalo de nuevo.', 'ensorlogs'),
            'already' => function_exists('ensorlogs_t')
                ? ensorlogs_t('Ya valoraste este log.', 'You already rated this log.')
                : __('Ya valoraste este log.', 'ensorlogs'),
        ),
    );
}

function ensorlogs_rest_submit_log_rating(WP_REST_Request $request)
{
    $nonce = $request->get_header('X-WP-Nonce');
    if (!is_string($nonce) || !wp_verify_nonce($nonce, 'wp_rest')) {
        return new WP_Error('invalid_nonce', __('Sesión no válida.', 'ensorlogs'), array('status' => 403));
    }

    $post_id = (int) $request->get_param('id');
    $rating  = (int) $request->get_param('rating');
    $result  = ensorlogs_submit_log_rating($post_id, $rating);
    if (is_wp_error($result)) {
        return $result;
    }
    return rest_ensure_response($result);
}

add_action(
    'rest_api_init',
    static function (): void {
        register_rest_route(
            'ensorlogs/v1',
            '/log/(?P<id>\d+)/rate',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => 'ensorlogs_rest_submit_log_rating',
                'permission_callback' => '__return_true',
                'args'                => array(
                    'id'     => array(
                        'required'          => true,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ),
                    'rating' => array(
                        'required'          => true,
                        'type'              => 'integer',
                        'minimum'           => 1,
                        'maximum'           => 5,
                        'sanitize_callback' => 'absint',
                    ),
                ),
            )
        );
    }
);

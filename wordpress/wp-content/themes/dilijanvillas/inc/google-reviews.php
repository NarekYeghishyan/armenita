<?php
/**
 * Google Maps / Places reviews for the ratings section.
 *
 * @package dilijanvillas
 */

if (!defined('ABSPATH')) {
    exit;
}

define('DILIJANVILLAS_GOOGLE_REVIEWS_SETTINGS_KEY', 'dilijanvillas_google_reviews_settings');
define('DILIJANVILLAS_GOOGLE_REVIEWS_TRANSIENT', 'dilijanvillas_google_reviews_cache');

/**
 * Default Google Maps listing URL.
 */
function dilijanvillas_google_reviews_default_maps_url()
{
    return 'https://www.google.com/maps/place/Armenita+family+resort/@40.7445006,44.8606746,17z/data=!4m8!3m7!1s0x4041ad4ed8776a81:0xebcf8a015c790cec!8m2!3d40.7445006!4d44.8606746!9m1!1b1!16s%2Fg%2F11zbqksw6d?entry=ttu';
}

/**
 * @return array<string,mixed>
 */
function dilijanvillas_get_google_reviews_settings()
{
    $defaults = array(
        'api_key' => '',
        'place_id' => '',
        'maps_url' => dilijanvillas_google_reviews_default_maps_url(),
        'search_query' => 'Armenita family resort Dilijan Armenia',
        'max_reviews' => 3,
        'cache_hours' => 12,
    );

    $stored = get_option(DILIJANVILLAS_GOOGLE_REVIEWS_SETTINGS_KEY, array());
    if (!is_array($stored)) {
        $stored = array();
    }

    return array_merge($defaults, $stored);
}

/**
 * Register Google Reviews settings page.
 */
function dilijanvillas_register_google_reviews_settings()
{
    register_setting(
        'dilijanvillas_google_reviews_settings_group',
        DILIJANVILLAS_GOOGLE_REVIEWS_SETTINGS_KEY,
        array(
            'type' => 'array',
            'sanitize_callback' => 'dilijanvillas_sanitize_google_reviews_settings',
            'default' => array(),
        )
    );

    add_options_page(
        __('Google Reviews', 'dilijanvillas'),
        __('Google Reviews', 'dilijanvillas'),
        'manage_options',
        'dilijanvillas-google-reviews',
        'dilijanvillas_render_google_reviews_settings_page'
    );
}
add_action('admin_menu', 'dilijanvillas_register_google_reviews_settings');

/**
 * @param array<string,mixed> $input Raw settings.
 * @return array<string,mixed>
 */
function dilijanvillas_sanitize_google_reviews_settings($input)
{
    $input = is_array($input) ? $input : array();
    $stored = dilijanvillas_get_google_reviews_settings();

    $api_key = isset($input['api_key']) ? sanitize_text_field((string) $input['api_key']) : '';
    if ($api_key === '' && !empty($stored['api_key'])) {
        $api_key = (string) $stored['api_key'];
    }

    $settings = array(
        'api_key' => $api_key,
        'place_id' => isset($input['place_id']) ? sanitize_text_field((string) $input['place_id']) : '',
        'maps_url' => !empty($input['maps_url']) ? esc_url_raw((string) $input['maps_url']) : dilijanvillas_google_reviews_default_maps_url(),
        'search_query' => !empty($input['search_query']) ? sanitize_text_field((string) $input['search_query']) : 'Armenita family resort Dilijan Armenia',
        'max_reviews' => isset($input['max_reviews']) ? max(1, min(5, (int) $input['max_reviews'])) : 3,
        'cache_hours' => isset($input['cache_hours']) ? max(1, min(168, (int) $input['cache_hours'])) : 12,
    );

    delete_transient(DILIJANVILLAS_GOOGLE_REVIEWS_TRANSIENT);

    return $settings;
}

/**
 * Render Google Reviews settings page.
 */
function dilijanvillas_render_google_reviews_settings_page()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $settings = dilijanvillas_get_google_reviews_settings();
    $cached = get_transient(DILIJANVILLAS_GOOGLE_REVIEWS_TRANSIENT);
    ?>
    <div class="wrap">
      <h1><?php esc_html_e('Google Reviews', 'dilijanvillas'); ?></h1>
      <p><?php esc_html_e('Connect Google Places API to show live rating and reviews beside TripAdvisor on the site.', 'dilijanvillas'); ?></p>
      <?php if (is_array($cached) && !empty($cached['rating'])) : ?>
        <div class="notice notice-success">
          <p>
            <?php
            printf(
                esc_html__('Cached data: %1$s / 5 (%2$d reviews). Last sync: %3$s.', 'dilijanvillas'),
                esc_html(number_format((float) $cached['rating'], 1)),
                (int) ($cached['user_rating_count'] ?? 0),
                esc_html((string) ($cached['fetched_at'] ?? ''))
            );
            ?>
          </p>
        </div>
      <?php endif; ?>
      <form method="post" action="options.php">
        <?php settings_fields('dilijanvillas_google_reviews_settings_group'); ?>
        <table class="form-table" role="presentation">
          <tr>
            <th scope="row"><label for="google_reviews_api_key"><?php esc_html_e('Google Places API key', 'dilijanvillas'); ?></label></th>
            <td>
              <input type="password" class="regular-text" id="google_reviews_api_key" name="<?php echo esc_attr(DILIJANVILLAS_GOOGLE_REVIEWS_SETTINGS_KEY); ?>[api_key]" value="<?php echo esc_attr((string) $settings['api_key']); ?>" autocomplete="off" />
              <p class="description"><?php esc_html_e('Enable Places API (New) in Google Cloud Console. Billing must be active.', 'dilijanvillas'); ?></p>
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="google_reviews_place_id"><?php esc_html_e('Place ID (optional)', 'dilijanvillas'); ?></label></th>
            <td>
              <input type="text" class="regular-text" id="google_reviews_place_id" name="<?php echo esc_attr(DILIJANVILLAS_GOOGLE_REVIEWS_SETTINGS_KEY); ?>[place_id]" value="<?php echo esc_attr((string) $settings['place_id']); ?>" />
              <p class="description"><?php esc_html_e('Leave empty to auto-detect from the search query below.', 'dilijanvillas'); ?></p>
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="google_reviews_search_query"><?php esc_html_e('Search query', 'dilijanvillas'); ?></label></th>
            <td>
              <input type="text" class="regular-text" id="google_reviews_search_query" name="<?php echo esc_attr(DILIJANVILLAS_GOOGLE_REVIEWS_SETTINGS_KEY); ?>[search_query]" value="<?php echo esc_attr((string) $settings['search_query']); ?>" />
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="google_reviews_maps_url"><?php esc_html_e('Google Maps URL', 'dilijanvillas'); ?></label></th>
            <td>
              <input type="url" class="large-text" id="google_reviews_maps_url" name="<?php echo esc_attr(DILIJANVILLAS_GOOGLE_REVIEWS_SETTINGS_KEY); ?>[maps_url]" value="<?php echo esc_attr((string) $settings['maps_url']); ?>" />
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="google_reviews_max_reviews"><?php esc_html_e('Reviews to display', 'dilijanvillas'); ?></label></th>
            <td>
              <input type="number" min="1" max="5" id="google_reviews_max_reviews" name="<?php echo esc_attr(DILIJANVILLAS_GOOGLE_REVIEWS_SETTINGS_KEY); ?>[max_reviews]" value="<?php echo esc_attr((string) $settings['max_reviews']); ?>" />
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="google_reviews_cache_hours"><?php esc_html_e('Cache (hours)', 'dilijanvillas'); ?></label></th>
            <td>
              <input type="number" min="1" max="168" id="google_reviews_cache_hours" name="<?php echo esc_attr(DILIJANVILLAS_GOOGLE_REVIEWS_SETTINGS_KEY); ?>[cache_hours]" value="<?php echo esc_attr((string) $settings['cache_hours']); ?>" />
            </td>
          </tr>
        </table>
        <?php submit_button(); ?>
      </form>
    </div>
    <?php
}

/**
 * Render star characters for a 0–5 rating.
 */
function dilijanvillas_render_rating_stars($rating)
{
    $rating = max(0, min(5, (float) $rating));
    $filled = (int) round($rating);

    return str_repeat('★', $filled) . str_repeat('☆', 5 - $filled);
}

/**
 * Fetch and cache Google review data.
 *
 * @return array<string,mixed>|null
 */
function dilijanvillas_get_google_reviews_data()
{
    $settings = dilijanvillas_get_google_reviews_settings();
    if ($settings['api_key'] === '') {
        return null;
    }

    $cached = get_transient(DILIJANVILLAS_GOOGLE_REVIEWS_TRANSIENT);
    if (is_array($cached) && !empty($cached['rating'])) {
        return $cached;
    }

    $fresh = dilijanvillas_fetch_google_reviews_data($settings);
    if (!is_array($fresh) || empty($fresh['rating'])) {
        return null;
    }

    $fresh['fetched_at'] = current_time('mysql');
    set_transient(
        DILIJANVILLAS_GOOGLE_REVIEWS_TRANSIENT,
        $fresh,
        (int) $settings['cache_hours'] * HOUR_IN_SECONDS
    );

    if ($fresh['place_id'] !== '' && $settings['place_id'] === '') {
        $settings['place_id'] = $fresh['place_id'];
        update_option(DILIJANVILLAS_GOOGLE_REVIEWS_SETTINGS_KEY, $settings);
    }

    return $fresh;
}

/**
 * @param array<string,mixed> $settings Settings.
 * @return array<string,mixed>|null
 */
function dilijanvillas_fetch_google_reviews_data(array $settings)
{
    $place_id = trim((string) $settings['place_id']);
    if ($place_id === '') {
        $place_id = dilijanvillas_google_reviews_resolve_place_id($settings);
    }

    if ($place_id === '') {
        return null;
    }

    $url = 'https://places.googleapis.com/v1/places/' . rawurlencode($place_id);
    $response = wp_remote_get(
        $url,
        array(
            'timeout' => 20,
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-Goog-Api-Key' => (string) $settings['api_key'],
                'X-Goog-FieldMask' => 'id,displayName,rating,userRatingCount,reviews,googleMapsUri',
            ),
        )
    );

    if (is_wp_error($response)) {
        return null;
    }

    $status = (int) wp_remote_retrieve_response_code($response);
    $body = json_decode((string) wp_remote_retrieve_body($response), true);
    if ($status !== 200 || !is_array($body)) {
        return null;
    }

    $reviews = array();
    $raw_reviews = isset($body['reviews']) && is_array($body['reviews']) ? $body['reviews'] : array();
    $max_reviews = max(1, min(5, (int) $settings['max_reviews']));

    foreach (array_slice($raw_reviews, 0, $max_reviews) as $review) {
        if (!is_array($review)) {
            continue;
        }

        $text = '';
        if (isset($review['text']['text'])) {
            $text = trim((string) $review['text']['text']);
        } elseif (isset($review['text']) && is_string($review['text'])) {
            $text = trim($review['text']);
        }

        if ($text === '') {
            continue;
        }

        $author = '';
        if (isset($review['authorAttribution']['displayName'])) {
            $author = trim((string) $review['authorAttribution']['displayName']);
        }

        $reviews[] = array(
            'review' => $text,
            'name' => $author !== '' ? $author : __('Google review', 'dilijanvillas'),
            'rating' => isset($review['rating']) ? (float) $review['rating'] : 0,
            'time' => isset($review['relativePublishTimeDescription'])
                ? trim((string) $review['relativePublishTimeDescription'])
                : '',
        );
    }

    $maps_url = !empty($body['googleMapsUri'])
        ? esc_url_raw((string) $body['googleMapsUri'])
        : esc_url_raw((string) $settings['maps_url']);

    return array(
        'place_id' => isset($body['id']) ? (string) $body['id'] : $place_id,
        'name' => isset($body['displayName']['text']) ? (string) $body['displayName']['text'] : 'Google',
        'rating' => isset($body['rating']) ? (float) $body['rating'] : 0,
        'user_rating_count' => isset($body['userRatingCount']) ? (int) $body['userRatingCount'] : 0,
        'maps_url' => $maps_url,
        'reviews' => $reviews,
    );
}

/**
 * @param array<string,mixed> $settings Settings.
 */
function dilijanvillas_google_reviews_resolve_place_id(array $settings)
{
    $response = wp_remote_post(
        'https://places.googleapis.com/v1/places:searchText',
        array(
            'timeout' => 20,
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-Goog-Api-Key' => (string) $settings['api_key'],
                'X-Goog-FieldMask' => 'places.id,places.displayName',
            ),
            'body' => wp_json_encode(
                array(
                    'textQuery' => (string) $settings['search_query'],
                    'languageCode' => 'en',
                )
            ),
        )
    );

    if (is_wp_error($response)) {
        return '';
    }

    $status = (int) wp_remote_retrieve_response_code($response);
    $body = json_decode((string) wp_remote_retrieve_body($response), true);
    if ($status !== 200 || !is_array($body) || empty($body['places'][0]['id'])) {
        return '';
    }

    return (string) $body['places'][0]['id'];
}

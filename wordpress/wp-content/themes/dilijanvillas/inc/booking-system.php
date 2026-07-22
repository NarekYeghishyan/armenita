<?php
/**
 * Booking system integration for Armenita Family Resort theme.
 *
 * @package dilijanvillas
 */

if (!defined('ABSPATH')) {
    exit;
}

const DILIJANVILLAS_BOOKING_SETTINGS_KEY = 'dilijanvillas_booking_settings';

/**
 * Get booking settings with defaults.
 *
 * @return array<string,mixed>
 */
function dilijanvillas_get_booking_settings()
{
    $defaults = array(
        'currency' => 'AMD',
        // Nightly prices live in Price period entries, not in these settings.
        'season_start' => '',
        'season_end' => '',
        'season_rate' => 0,
        'paylink_integration_mode' => 'armenia_integration',
        'paylink_test_mode' => false,
        'paylink_checkout_language' => '',
        'paylink_api_url' => '',
        'paylink_api_token' => '',
        'paylink_merchant_id' => '',
        'paylink_callback_url' => '',
        'paylink_am_api_base' => 'https://integration.apitest.paylink.am',
        'paylink_am_request_type' => 'Booking',
    );

    $settings = get_option(DILIJANVILLAS_BOOKING_SETTINGS_KEY, array());
    if (!is_array($settings)) {
        $settings = array();
    }

    $settings = array_merge($defaults, $settings);

    // A row saved before these defaults existed holds an empty string, which
    // array_merge() keeps — fall back so the integration is not left half-configured.
    foreach (array('currency', 'paylink_integration_mode', 'paylink_am_api_base', 'paylink_am_request_type') as $key) {
        if (trim((string) $settings[$key]) === '') {
            $settings[$key] = $defaults[$key];
        }
    }

    // Credentials may be defined in wp-config.php instead. That keeps the
    // partner key out of both the options table and theme source control, and
    // lets test/production differ without touching the database.
    $constant_map = array(
        'paylink_merchant_id' => 'DILIJANVILLAS_PAYLINK_PARTNER_ID',
        'paylink_api_token' => 'DILIJANVILLAS_PAYLINK_PARTNER_KEY',
        'paylink_am_api_base' => 'DILIJANVILLAS_PAYLINK_API_BASE',
    );
    foreach ($constant_map as $key => $constant) {
        if (defined($constant) && trim((string) constant($constant)) !== '') {
            $settings[$key] = trim((string) constant($constant));
        }
    }

    return $settings;
}

/**
 * Whether a PayLink setting is locked by a wp-config.php constant.
 *
 * @param string $key Settings key.
 * @return bool
 */
function dilijanvillas_paylink_setting_from_constant($key)
{
    $constant_map = array(
        'paylink_merchant_id' => 'DILIJANVILLAS_PAYLINK_PARTNER_ID',
        'paylink_api_token' => 'DILIJANVILLAS_PAYLINK_PARTNER_KEY',
        'paylink_am_api_base' => 'DILIJANVILLAS_PAYLINK_API_BASE',
    );

    return isset($constant_map[$key])
        && defined($constant_map[$key])
        && trim((string) constant($constant_map[$key])) !== '';
}

/**
 * Register booking custom post type.
 */
function dilijanvillas_register_booking_cpt()
{
    register_post_type(
        'dv_booking',
        array(
            'labels' => array(
                'name' => __('Bookings', 'dilijanvillas'),
                'singular_name' => __('Booking', 'dilijanvillas'),
                'menu_name' => __('Bookings', 'dilijanvillas'),
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'supports' => array('title'),
            'capability_type' => 'post',
            'map_meta_cap' => true,
        )
    );
}
add_action('init', 'dilijanvillas_register_booking_cpt');

/**
 * Register manual unavailable range custom post type.
 */
function dilijanvillas_register_unavailable_cpt()
{
    register_post_type(
        'dv_unavailable',
        array(
            'labels' => array(
                'name' => __('Unavailable periods', 'dilijanvillas'),
                'singular_name' => __('Unavailable period', 'dilijanvillas'),
                'menu_name' => __('Unavailable periods', 'dilijanvillas'),
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'supports' => array('title'),
            'capability_type' => 'post',
            'map_meta_cap' => true,
        )
    );
}
add_action('init', 'dilijanvillas_register_unavailable_cpt');

/**
 * Register custom price period custom post type.
 */
function dilijanvillas_register_price_period_cpt()
{
    register_post_type(
        'dv_price_period',
        array(
            'labels' => array(
                'name' => __('Price periods', 'dilijanvillas'),
                'singular_name' => __('Price period', 'dilijanvillas'),
                'menu_name' => __('Price periods', 'dilijanvillas'),
                'add_new_item' => __('Add new price period', 'dilijanvillas'),
                'edit_item' => __('Edit price period', 'dilijanvillas'),
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'supports' => array('title'),
            'capability_type' => 'post',
            'map_meta_cap' => true,
        )
    );
}
add_action('init', 'dilijanvillas_register_price_period_cpt');

/**
 * Booking statuses that block a date range.
 *
 * @return array<int,string>
 */
function dilijanvillas_get_blocking_booking_statuses()
{
    return array('pending', 'confirmed', 'paid', 'payment_pending');
}

/**
 * Register booking settings.
 */
function dilijanvillas_register_booking_settings()
{
    register_setting(
        'dilijanvillas_booking_settings_group',
        DILIJANVILLAS_BOOKING_SETTINGS_KEY,
        array(
            'type' => 'array',
            'sanitize_callback' => 'dilijanvillas_sanitize_booking_settings',
            'default' => array(),
        )
    );

    add_options_page(
        __('Booking Settings', 'dilijanvillas'),
        __('Booking Settings', 'dilijanvillas'),
        'manage_options',
        'dilijanvillas-booking-settings',
        'dilijanvillas_render_booking_settings_page'
    );
}
add_action('admin_menu', 'dilijanvillas_register_booking_settings');

/**
 * Sanitize booking settings.
 *
 * @param array<string,mixed> $input Raw settings.
 * @return array<string,mixed>
 */
function dilijanvillas_sanitize_booking_settings($input)
{
    $input = is_array($input) ? $input : array();
    $stored = get_option(DILIJANVILLAS_BOOKING_SETTINGS_KEY, array());
    if (!is_array($stored)) {
        $stored = array();
    }

    $hosted_mode = 'hosted_checkout';
    $raw_mode = isset($input['paylink_integration_mode']) ? sanitize_text_field((string) $input['paylink_integration_mode']) : '';
    if ($raw_mode === 'legacy_json_post') {
        $hosted_mode = 'legacy_json_post';
    } elseif ($raw_mode === 'armenia_integration') {
        $hosted_mode = 'armenia_integration';
    }

    $new_secret = isset($input['paylink_api_token']) ? sanitize_text_field((string) $input['paylink_api_token']) : '';
    if ($new_secret === '' && isset($stored['paylink_api_token'])) {
        $new_secret = (string) $stored['paylink_api_token'];
    }

    $paylink_am_base = !empty($input['paylink_am_api_base']) ? esc_url_raw((string) $input['paylink_am_api_base']) : '';
    if ($paylink_am_base === '' && !empty($stored['paylink_am_api_base'])) {
        $paylink_am_base = (string) $stored['paylink_am_api_base'];
    }

    $paylink_am_request_type_raw = isset($input['paylink_am_request_type']) ? sanitize_text_field(trim((string) $input['paylink_am_request_type'])) : '';
    if ($paylink_am_request_type_raw === '' && !empty($stored['paylink_am_request_type'])) {
        $paylink_am_request_type_raw = sanitize_text_field((string) $stored['paylink_am_request_type']);
    }
    $paylink_am_request_type_final = $paylink_am_request_type_raw !== '' ? $paylink_am_request_type_raw : 'Booking';

    return array(
        'currency' => !empty($input['currency']) ? sanitize_text_field((string) $input['currency']) : 'AMD',
        'season_start' => !empty($input['season_start']) ? sanitize_text_field((string) $input['season_start']) : '',
        'season_end' => !empty($input['season_end']) ? sanitize_text_field((string) $input['season_end']) : '',
        'season_rate' => isset($input['season_rate']) ? max(0, (float) $input['season_rate']) : 0,
        'paylink_integration_mode' => $hosted_mode,
        'paylink_test_mode' => !empty($input['paylink_test_mode']),
        'paylink_checkout_language' => !empty($input['paylink_checkout_language']) ? preg_replace('/[^a-z_-]/', '', strtolower((string) $input['paylink_checkout_language'])) : '',
        'paylink_api_url' => !empty($input['paylink_api_url']) ? esc_url_raw((string) $input['paylink_api_url']) : '',
        'paylink_api_token' => $new_secret,
        'paylink_merchant_id' => !empty($input['paylink_merchant_id']) ? sanitize_text_field((string) $input['paylink_merchant_id']) : '',
        'paylink_callback_url' => !empty($input['paylink_callback_url']) ? esc_url_raw((string) $input['paylink_callback_url']) : '',
        'paylink_am_api_base' => $paylink_am_base,
        'paylink_am_request_type' => $paylink_am_request_type_final,
    );
}

/**
 * Render booking settings page.
 */
function dilijanvillas_render_booking_settings_page()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $settings = dilijanvillas_get_booking_settings();
    ?>
    <div class="wrap">
      <h1><?php esc_html_e('Booking Settings', 'dilijanvillas'); ?></h1>
      <form method="post" action="options.php">
        <?php settings_fields('dilijanvillas_booking_settings_group'); ?>
        <table class="form-table" role="presentation">
          <tr>
            <th scope="row"><label for="currency"><?php esc_html_e('Currency', 'dilijanvillas'); ?></label></th>
            <td><input id="currency" type="text" name="<?php echo esc_attr(DILIJANVILLAS_BOOKING_SETTINGS_KEY); ?>[currency]" value="<?php echo esc_attr((string) $settings['currency']); ?>" class="regular-text" /></td>
          </tr>
          <tr>
            <th scope="row"><?php esc_html_e('Nightly rate & weekend multiplier', 'dilijanvillas'); ?></th>
            <td>
              <p class="description">
                <?php esc_html_e('Now set per unit, in the "Booking pricing" block when editing a Cottage or Private villa page — each unit can have its own price.', 'dilijanvillas'); ?>
              </p>
            </td>
          </tr>
          <tr>
            <th scope="row"><?php esc_html_e('Seasonal override', 'dilijanvillas'); ?></th>
            <td>
              <label>
                <?php esc_html_e('Start', 'dilijanvillas'); ?>
                <input type="date" name="<?php echo esc_attr(DILIJANVILLAS_BOOKING_SETTINGS_KEY); ?>[season_start]" value="<?php echo esc_attr((string) $settings['season_start']); ?>" />
              </label>
              <label style="margin-left:12px;">
                <?php esc_html_e('End', 'dilijanvillas'); ?>
                <input type="date" name="<?php echo esc_attr(DILIJANVILLAS_BOOKING_SETTINGS_KEY); ?>[season_end]" value="<?php echo esc_attr((string) $settings['season_end']); ?>" />
              </label>
              <label style="margin-left:12px;">
                <?php esc_html_e('Nightly rate', 'dilijanvillas'); ?>
                <input type="number" min="0" step="0.01" name="<?php echo esc_attr(DILIJANVILLAS_BOOKING_SETTINGS_KEY); ?>[season_rate]" value="<?php echo esc_attr((string) $settings['season_rate']); ?>" />
              </label>
            </td>
          </tr>
          <tr>
            <th scope="row"><?php esc_html_e('Paylink integration mode', 'dilijanvillas'); ?></th>
            <td>
              <select id="paylink_integration_mode" name="<?php echo esc_attr(DILIJANVILLAS_BOOKING_SETTINGS_KEY); ?>[paylink_integration_mode]">
                <?php $pl_mode = (string) $settings['paylink_integration_mode']; ?>
                <option value="armenia_integration" <?php selected($pl_mode === 'armenia_integration'); ?>><?php esc_html_e('Armenia — PayLink Integration API (recommended)', 'dilijanvillas'); ?></option>
                <option value="hosted_checkout" <?php selected($pl_mode === 'hosted_checkout'); ?>><?php esc_html_e('KZ-style hosted checkout (/ctp/api/checkouts)', 'dilijanvillas'); ?></option>
                <option value="legacy_json_post" <?php selected($pl_mode === 'legacy_json_post'); ?>><?php esc_html_e('Legacy custom JSON POST (Bearer)', 'dilijanvillas'); ?></option>
              </select>
              <p class="description">
                <?php esc_html_e('Armenia mode matches integration.apitest.paylink.am: authorize with partner Id + partner key, register payment request, redirect to redirectUrl.', 'dilijanvillas'); ?>
                <a href="https://integration.apitest.paylink.am/swagger/index.html" target="_blank" rel="noopener noreferrer">Swagger UI</a>
              </p>
            </td>
          </tr>
          <tr>
            <th scope="row"><?php esc_html_e('Paylink sandbox / test', 'dilijanvillas'); ?></th>
            <td>
              <label><input type="checkbox" name="<?php echo esc_attr(DILIJANVILLAS_BOOKING_SETTINGS_KEY); ?>[paylink_test_mode]" value="1" <?php checked(!empty($settings['paylink_test_mode'])); ?> /> <?php esc_html_e('Test mode (hosted KZ checkout only: sends checkout.test = true)', 'dilijanvillas'); ?></label>
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="paylink_checkout_language"><?php esc_html_e('Checkout language', 'dilijanvillas'); ?></label></th>
            <td>
              <input id="paylink_checkout_language" type="text" name="<?php echo esc_attr(DILIJANVILLAS_BOOKING_SETTINGS_KEY); ?>[paylink_checkout_language]" value="<?php echo esc_attr((string) $settings['paylink_checkout_language']); ?>" class="regular-text" placeholder="en" maxlength="16" />
              <p class="description"><?php esc_html_e('Optional ISO-ish code for the hosted payment page (e.g. en, ru, hy). Leave empty to use site / Polylang language when possible.', 'dilijanvillas'); ?></p>
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="paylink_am_api_base"><?php esc_html_e('Paylink Armenia — API base URL', 'dilijanvillas'); ?></label></th>
            <td>
              <input id="paylink_am_api_base" type="url" name="<?php echo esc_attr(DILIJANVILLAS_BOOKING_SETTINGS_KEY); ?>[paylink_am_api_base]" value="<?php echo esc_attr((string) $settings['paylink_am_api_base']); ?>" class="large-text code" placeholder="https://integration.apitest.paylink.am" />
              <p class="description">
                <?php esc_html_e('Root URL only (no /api path). Test: integration.apitest.paylink.am. Production URL is provided by PayLink.', 'dilijanvillas'); ?>
              </p>
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="paylink_am_request_type"><?php esc_html_e('Paylink Armenia — request type', 'dilijanvillas'); ?></label></th>
            <td>
              <input id="paylink_am_request_type" type="text" name="<?php echo esc_attr(DILIJANVILLAS_BOOKING_SETTINGS_KEY); ?>[paylink_am_request_type]" value="<?php echo esc_attr((string) ($settings['paylink_am_request_type'] ?? 'Booking')); ?>" class="regular-text" />
              <p class="description"><?php esc_html_e('Value for RegisterRequest.requestType (configured in your PayLink merchant profile). Default: Booking.', 'dilijanvillas'); ?></p>
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="paylink_api_url"><?php esc_html_e('Hosted KZ checkout URL', 'dilijanvillas'); ?></label></th>
            <td>
              <input id="paylink_api_url" type="url" name="<?php echo esc_attr(DILIJANVILLAS_BOOKING_SETTINGS_KEY); ?>[paylink_api_url]" value="<?php echo esc_attr((string) $settings['paylink_api_url']); ?>" class="large-text code" placeholder="https://checkout.paylink.kz/ctp/api/checkouts" />
              <p class="description">
                <?php esc_html_e('Only for “KZ-style hosted checkout”: full POST URL …/ctp/api/checkouts.', 'dilijanvillas'); ?>
              </p>
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="paylink_api_token"><?php esc_html_e('Paylink secret key', 'dilijanvillas'); ?></label></th>
            <td><input id="paylink_api_token" type="password" name="<?php echo esc_attr(DILIJANVILLAS_BOOKING_SETTINGS_KEY); ?>[paylink_api_token]" value="" class="regular-text" autocomplete="new-password" placeholder="<?php echo esc_attr(__('Leave unchanged to keep current secret', 'dilijanvillas')); ?>" />
              <p class="description"><?php esc_html_e('Armenia: partnerKey for /api/authorization/authorize. Hosted KZ: HTTP Basic secret. Legacy: Bearer API token.', 'dilijanvillas'); ?></p>
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="paylink_merchant_id"><?php esc_html_e('Paylink shop ID', 'dilijanvillas'); ?></label></th>
            <td><input id="paylink_merchant_id" type="text" name="<?php echo esc_attr(DILIJANVILLAS_BOOKING_SETTINGS_KEY); ?>[paylink_merchant_id]" value="<?php echo esc_attr((string) $settings['paylink_merchant_id']); ?>" class="regular-text" />
              <p class="description"><?php esc_html_e('Armenia: partnerId for authorize. Hosted KZ: Basic auth Shop ID. Legacy: merchantId in JSON.', 'dilijanvillas'); ?></p>
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="paylink_callback_url"><?php esc_html_e('Guest return URL base', 'dilijanvillas'); ?></label></th>
            <td><input id="paylink_callback_url" type="url" name="<?php echo esc_attr(DILIJANVILLAS_BOOKING_SETTINGS_KEY); ?>[paylink_callback_url]" value="<?php echo esc_attr((string) $settings['paylink_callback_url']); ?>" class="large-text code" /></td>
          </tr>
          <tr>
            <th scope="row"><?php esc_html_e('Paylink webhook URL (hosted KZ only)', 'dilijanvillas'); ?></th>
            <td><code class="large-text"><?php echo esc_html(rest_url('dilijanvillas/v1/paylink-webhook')); ?></code>
              <p class="description"><?php esc_html_e('Armenian Integration API polls payment status after the guest returns (no server webhook required). Kazakhstan-style hosted flows can use notification_url pointing here.', 'dilijanvillas'); ?></p>
            </td>
          </tr>
        </table>
        <?php submit_button(); ?>
      </form>
      <?php do_action('dilijanvillas_booking_settings_after_form'); ?>
    </div>
    <?php
}

/**
 * Get accommodation pages based on templates.
 *
 * @return array<int,array<string,mixed>>
 */
function dilijanvillas_get_booking_accommodations()
{
    $lang = function_exists('pll_current_language') ? pll_current_language('slug') : '';
    $template_slugs = array('cottage.php', 'private-willa.php');
    $output = array();
    $seen = array();

    foreach ($template_slugs as $template_slug) {
        $args = array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'orderby' => array(
                'menu_order' => 'ASC',
                'title' => 'ASC',
            ),
            'order' => 'ASC',
            'meta_key' => '_wp_page_template',
            'meta_value' => $template_slug,
            'suppress_filters' => false,
        );

        if ($lang) {
            $args['lang'] = $lang;
        }

        $page_ids = get_posts($args);
        if (!is_array($page_ids)) {
            continue;
        }

        foreach ($page_ids as $page_id) {
            $page_id = (int) $page_id;
            if (!$page_id || isset($seen[$page_id])) {
                continue;
            }

            $seen[$page_id] = true;
            $max_guests = 0;
            if (function_exists('get_field')) {
                $max_guests = (int) get_field('max_guests', $page_id);
            }
            $output[] = array(
                'id' => $page_id,
                'title' => (string) get_the_title($page_id),
                'url' => (string) get_permalink($page_id),
                'template' => $template_slug,
                'maxGuests' => $max_guests > 0 ? $max_guests : 12,
            );
        }
    }

    return $output;
}

/**
 * Get all accommodation pages across every language (admin context).
 * Each entry includes the language code (when Polylang is active).
 *
 * @return array<int,array<string,mixed>>
 */
function dilijanvillas_get_all_accommodations_for_admin()
{
    $template_slugs = array('cottage.php', 'private-willa.php');
    $output = array();
    $seen = array();
    $languages = array();
    if (function_exists('pll_languages_list')) {
        $languages = (array) pll_languages_list(array('fields' => 'slug'));
    }

    foreach ($template_slugs as $template_slug) {
        $base_args = array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'orderby' => array(
                'menu_order' => 'ASC',
                'title' => 'ASC',
            ),
            'order' => 'ASC',
            'meta_key' => '_wp_page_template',
            'meta_value' => $template_slug,
            'suppress_filters' => false,
        );

        $batches = array();
        if (!empty($languages)) {
            foreach ($languages as $lang_slug) {
                $args = $base_args;
                $args['lang'] = $lang_slug;
                $batches[$lang_slug] = $args;
            }
        } else {
            $batches[''] = $base_args;
        }

        foreach ($batches as $lang_slug => $args) {
            $page_ids = get_posts($args);
            if (!is_array($page_ids)) {
                continue;
            }
            foreach ($page_ids as $page_id) {
                $page_id = (int) $page_id;
                if (!$page_id || isset($seen[$page_id])) {
                    continue;
                }
                $seen[$page_id] = true;
                $output[] = array(
                    'id' => $page_id,
                    'title' => (string) get_the_title($page_id),
                    'template' => $template_slug,
                    'lang' => (string) $lang_slug,
                );
            }
        }
    }

    return $output;
}

/**
 * Expand a list of page IDs to include every Polylang translation.
 *
 * @param array<int,int> $page_ids Source page IDs.
 * @return array<int,int> De-duplicated list including translations.
 */
function dilijanvillas_expand_pages_to_all_languages(array $page_ids)
{
    $expanded = array();
    foreach ($page_ids as $page_id) {
        $page_id = (int) $page_id;
        if ($page_id <= 0) {
            continue;
        }
        $expanded[$page_id] = true;

        if (function_exists('pll_get_post_translations')) {
            $translations = pll_get_post_translations($page_id);
            if (is_array($translations)) {
                foreach ($translations as $translated_id) {
                    $translated_id = (int) $translated_id;
                    if ($translated_id > 0) {
                        $expanded[$translated_id] = true;
                    }
                }
            }
        } elseif (function_exists('pll_languages_list') && function_exists('pll_get_post')) {
            $langs = (array) pll_languages_list(array('fields' => 'slug'));
            foreach ($langs as $lang_slug) {
                $translated_id = (int) pll_get_post($page_id, $lang_slug);
                if ($translated_id > 0) {
                    $expanded[$translated_id] = true;
                }
            }
        }
    }
    return array_map('intval', array_keys($expanded));
}

/**
 * Validate YYYY-MM-DD date string.
 *
 * @param string $date Date string.
 * @return bool
 */
function dilijanvillas_is_valid_booking_date($date)
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return false;
    }
    $timestamp = strtotime($date . ' 12:00:00');
    if (!$timestamp) {
        return false;
    }
    return gmdate('Y-m-d', $timestamp) === $date;
}

/**
 * Check if two ranges overlap.
 *
 * @param string $startA Start A.
 * @param string $endA End A.
 * @param string $startB Start B.
 * @param string $endB End B.
 * @return bool
 */
function dilijanvillas_booking_ranges_overlap($startA, $endA, $startB, $endB)
{
    return $startA <= $endB && $endA >= $startB;
}

/**
 * Collect blocked ranges from bookings and manual periods.
 *
 * @param int $accommodation_id Accommodation page ID.
 * @return array<int,array<string,mixed>>
 */
function dilijanvillas_get_blocked_ranges($accommodation_id)
{
    $blocked = array();
    $accommodation_id = (int) $accommodation_id;
    $booking_statuses = dilijanvillas_get_blocking_booking_statuses();

    $sibling_ids = $accommodation_id > 0
        ? dilijanvillas_expand_pages_to_all_languages(array($accommodation_id))
        : array();
    if (empty($sibling_ids) && $accommodation_id > 0) {
        $sibling_ids = array($accommodation_id);
    }

    $bookings = $accommodation_id > 0
        ? get_posts(
            array(
                'post_type' => 'dv_booking',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'fields' => 'ids',
                'meta_query' => array(
                    array(
                        'key' => '_dv_accommodation_id',
                        'value' => $sibling_ids,
                        'compare' => 'IN',
                        'type' => 'NUMERIC',
                    ),
                ),
            )
        )
        : array();

    foreach ($bookings as $booking_id) {
        $status = (string) get_post_meta((int) $booking_id, '_dv_status', true);
        if (!in_array($status, $booking_statuses, true)) {
            continue;
        }

        $existing_start = (string) get_post_meta((int) $booking_id, '_dv_checkin', true);
        $existing_end = (string) get_post_meta((int) $booking_id, '_dv_checkout', true);
        if (!dilijanvillas_is_valid_booking_date($existing_start) || !dilijanvillas_is_valid_booking_date($existing_end)) {
            continue;
        }

        $blocked[] = array(
            'start' => $existing_start,
            'end' => $existing_end,
            'source' => 'booking',
            'sourceId' => (int) $booking_id,
            'status' => $status,
        );
    }

    $unavailable_meta_query = array(
        'relation' => 'AND',
        array(
            'key' => '_dv_unavailable_status',
            'value' => 'active',
            'compare' => '=',
        ),
    );
    if ($accommodation_id > 0) {
        $unavailable_meta_query[] = array(
            'relation' => 'OR',
            array(
                'key' => '_dv_accommodation_ids',
                'value' => $sibling_ids,
                'compare' => 'IN',
                'type' => 'NUMERIC',
            ),
            array(
                'key' => '_dv_accommodation_ids',
                'value' => 0,
                'compare' => '=',
                'type' => 'NUMERIC',
            ),
            array(
                'key' => '_dv_accommodation_id',
                'value' => $sibling_ids,
                'compare' => 'IN',
                'type' => 'NUMERIC',
            ),
            array(
                'key' => '_dv_accommodation_id',
                'value' => 0,
                'compare' => '=',
                'type' => 'NUMERIC',
            ),
        );
    } else {
        $unavailable_meta_query[] = array(
            'key' => '_dv_accommodation_ids',
            'value' => 0,
            'compare' => '=',
            'type' => 'NUMERIC',
        );
    }

    $unavailable_ranges = get_posts(
        array(
            'post_type' => 'dv_unavailable',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => $unavailable_meta_query,
        )
    );

    foreach ($unavailable_ranges as $range_id) {
        $existing_start = (string) get_post_meta((int) $range_id, '_dv_unavailable_start', true);
        $existing_end = (string) get_post_meta((int) $range_id, '_dv_unavailable_end', true);
        if (!dilijanvillas_is_valid_booking_date($existing_start) || !dilijanvillas_is_valid_booking_date($existing_end)) {
            continue;
        }
        if ($existing_end < $existing_start) {
            continue;
        }

        $blocked[] = array(
            'start' => $existing_start,
            'end' => $existing_end,
            'source' => 'manual_block',
            'sourceId' => (int) $range_id,
            'status' => 'blocked',
        );
    }

    return $blocked;
}

/**
 * Check availability by scanning blocked ranges.
 *
 * @param int    $accommodation_id Accommodation page ID.
 * @param string $start_date       Check-in.
 * @param string $end_date         Check-out.
 * @return array<string,mixed>
 */
function dilijanvillas_check_booking_availability($accommodation_id, $start_date, $end_date)
{
    $blocked_ranges = dilijanvillas_get_blocked_ranges((int) $accommodation_id);
    foreach ($blocked_ranges as $range) {
        $existing_start = isset($range['start']) ? (string) $range['start'] : '';
        $existing_end = isset($range['end']) ? (string) $range['end'] : '';
        if ($existing_start === '' || $existing_end === '') {
            continue;
        }
        if (dilijanvillas_booking_ranges_overlap($start_date, $end_date, $existing_start, $existing_end)) {
            return array(
                'available' => false,
                'reason' => 'reserved',
                'source' => isset($range['source']) ? (string) $range['source'] : '',
                'sourceId' => isset($range['sourceId']) ? (int) $range['sourceId'] : 0,
            );
        }
    }

    return array(
        'available' => true,
        'reason' => '',
        'source' => '',
        'sourceId' => 0,
    );
}

/**
 * Calculate booking price.
 *
 * @param string $start_date Start date.
 * @param string $end_date   End date.
 * @return array<string,mixed>
 */
function dilijanvillas_calculate_booking_price($start_date, $end_date, $accommodation_id = 0)
{
    $settings = dilijanvillas_get_booking_settings();

    // Prices come from Price period entries. A night with no period stays at
    // zero and leaves has_admin_price false, which blocks the booking.
    $base_rate = 0.0;

    $season_start = (string) $settings['season_start'];
    $season_end = (string) $settings['season_end'];
    $season_rate = max(0, (float) $settings['season_rate']);

    $start = strtotime($start_date . ' 12:00:00');
    $end = strtotime($end_date . ' 12:00:00');
    if (!$start || !$end || $end < $start) {
        return array(
            'nights' => 0,
            'total' => 0,
            'currency' => (string) $settings['currency'],
            'has_admin_price' => false,
            'covered_nights' => 0,
        );
    }

    $price_periods = dilijanvillas_get_price_periods((int) $accommodation_id);

    $nights = (int) round(($end - $start) / 86400) + 1;
    $total = 0.0;
    $covered_nights = 0;
    for ($i = 0; $i < $nights; $i++) {
        $night_ts = strtotime('+' . $i . ' day', $start);
        $date_key = gmdate('Y-m-d', $night_ts);

        $rate = $base_rate;

        if (
            $season_rate > 0
            && dilijanvillas_is_valid_booking_date($season_start)
            && dilijanvillas_is_valid_booking_date($season_end)
            && $date_key >= $season_start
            && $date_key <= $season_end
        ) {
            $rate = $season_rate;
        }

        $custom_rate = null;
        foreach ($price_periods as $period) {
            if (!isset($period['start'], $period['end'], $period['rate'])) {
                continue;
            }
            if ($date_key >= $period['start'] && $date_key <= $period['end']) {
                $custom_rate = (float) $period['rate'];
                break;
            }
        }

        // The period rate is the price as entered — no weekend surcharge.
        if ($custom_rate !== null && $custom_rate >= 0) {
            $rate = $custom_rate;
            $covered_nights++;
        }

        $total += $rate;
    }

    $has_admin_price = ($nights > 0 && $covered_nights === $nights);

    return array(
        'nights' => $nights,
        'total' => $has_admin_price ? round($total, 2) : 0,
        'currency' => (string) $settings['currency'],
        'has_admin_price' => $has_admin_price,
        'covered_nights' => $covered_nights,
    );
}

/**
 * Collect active custom price periods that apply to a given accommodation.
 *
 * @param int $accommodation_id Accommodation page ID.
 * @return array<int,array<string,mixed>>
 */
/**
 * Weekdays that count as the weekend for the minimum-stay rule.
 *
 * ISO-8601 day numbers as returned by date('N'): 5 = Friday, 6 = Saturday,
 * 7 = Sunday.
 *
 * @return array<int,int>
 */
function dilijanvillas_weekend_day_numbers()
{
    return array(5, 6, 7);
}

/**
 * Minimum nights demanded because the stay touches a weekend.
 *
 * Each Price period carries a "Weekend multiplier": when the requested stay
 * includes a Friday, Saturday or Sunday priced by that period, the stay must
 * be at least that many nights. The strictest matching period wins.
 *
 * @param int    $accommodation_id Accommodation page ID.
 * @param string $start_date       Check-in (Y-m-d).
 * @param string $end_date         Check-out (Y-m-d).
 * @return int Required nights, 0 when nothing applies.
 */
function dilijanvillas_get_weekend_min_nights($accommodation_id, $start_date, $end_date)
{
    $start = strtotime($start_date . ' 12:00:00');
    $end = strtotime($end_date . ' 12:00:00');
    if (!$start || !$end || $end < $start) {
        return 0;
    }

    $price_periods = dilijanvillas_get_price_periods((int) $accommodation_id);
    if (empty($price_periods)) {
        return 0;
    }

    $weekend_days = dilijanvillas_weekend_day_numbers();
    $nights = (int) round(($end - $start) / DAY_IN_SECONDS) + 1;
    $required = 0;

    for ($i = 0; $i < $nights; $i++) {
        $night_ts = strtotime('+' . $i . ' day', $start);
        if (!in_array((int) gmdate('N', $night_ts), $weekend_days, true)) {
            continue;
        }

        $date_key = gmdate('Y-m-d', $night_ts);
        foreach ($price_periods as $period) {
            if (!isset($period['start'], $period['end'])) {
                continue;
            }
            if ($date_key < $period['start'] || $date_key > $period['end']) {
                continue;
            }
            $min_nights = isset($period['weekend_min_nights']) ? (int) $period['weekend_min_nights'] : 0;
            if ($min_nights > $required) {
                $required = $min_nights;
            }
            break;
        }
    }

    return $required;
}

function dilijanvillas_get_price_periods($accommodation_id)
{
    $accommodation_id = (int) $accommodation_id;
    $period_ids = get_posts(
        array(
            'post_type' => 'dv_price_period',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_dv_price_status',
                    'value' => 'active',
                    'compare' => '=',
                ),
                $accommodation_id > 0
                    ? array(
                        'relation' => 'OR',
                        array(
                            'key' => '_dv_accommodation_ids',
                            'value' => dilijanvillas_expand_pages_to_all_languages(array($accommodation_id)),
                            'compare' => 'IN',
                            'type' => 'NUMERIC',
                        ),
                        array(
                            'key' => '_dv_accommodation_ids',
                            'value' => 0,
                            'compare' => '=',
                            'type' => 'NUMERIC',
                        ),
                        array(
                            'key' => '_dv_accommodation_id',
                            'value' => dilijanvillas_expand_pages_to_all_languages(array($accommodation_id)),
                            'compare' => 'IN',
                            'type' => 'NUMERIC',
                        ),
                        array(
                            'key' => '_dv_accommodation_id',
                            'value' => 0,
                            'compare' => '=',
                            'type' => 'NUMERIC',
                        ),
                    )
                    : array(
                        'key' => '_dv_accommodation_ids',
                        'value' => 0,
                        'compare' => '=',
                        'type' => 'NUMERIC',
                    ),
            ),
        )
    );

    $periods = array();
    if (!is_array($period_ids)) {
        return $periods;
    }

    foreach ($period_ids as $period_id) {
        $start = (string) get_post_meta((int) $period_id, '_dv_price_start', true);
        $end = (string) get_post_meta((int) $period_id, '_dv_price_end', true);
        $rate = (float) get_post_meta((int) $period_id, '_dv_price_rate', true);
        $weekend_min_nights = (int) get_post_meta((int) $period_id, '_dv_price_weekend_min_nights', true);
        if (!dilijanvillas_is_valid_booking_date($start) || !dilijanvillas_is_valid_booking_date($end)) {
            continue;
        }
        if ($end < $start) {
            continue;
        }

        $periods[] = array(
            'id' => (int) $period_id,
            'start' => $start,
            'end' => $end,
            'rate' => max(0, $rate),
            'weekend_min_nights' => max(0, $weekend_min_nights),
        );
    }

    return $periods;
}

/**
 * Currency codes handled as exponent 0 in PayLink amount fields (integer major units).
 *
 * @return array<int,string>
 */
function dilijanvillas_paylink_zero_decimal_currencies()
{
    return array('BIF', 'CLP', 'DJF', 'GNF', 'ISK', 'JPY', 'KMF', 'KRW', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF');
}

/**
 * Convert major currency units to smallest PayLink-compatible integer units.
 *
 * @param float  $amount_major Major units from booking total.
 * @param string $currency     Currency code.
 * @return int
 */
function dilijanvillas_paylink_amount_to_minor_units($amount_major, $currency)
{
    $currency = strtoupper(trim((string) $currency));
    $amount_major = max(0, (float) $amount_major);
    if ($currency === '') {
        $currency = 'AMD';
    }

    $mult = in_array($currency, dilijanvillas_paylink_zero_decimal_currencies(), true) ? 1 : 100;

    return (int) max(1, (int) round($amount_major * $mult));
}

/**
 * Detect checkout language preference for hosted PayLink.
 *
 * @param array<string,mixed> $settings Booking settings row.
 * @return string Two-letter-ish code PayLink understands.
 */
function dilijanvillas_paylink_resolve_checkout_language($settings)
{
    if (!empty($settings['paylink_checkout_language'])) {
        return (string) $settings['paylink_checkout_language'];
    }

    if (function_exists('pll_current_language')) {
        $slug = (string) pll_current_language('slug');
        $map = array(
            'hy' => 'hy',
            'ru' => 'ru',
            'en' => 'en',
        );
        if ($slug !== '' && isset($map[$slug])) {
            return $map[$slug];
        }
    }

    $locale = strtolower((string) get_locale());
    if (strpos($locale, 'hy') === 0) {
        return 'hy';
    }
    if (strpos($locale, 'ru') === 0) {
        return 'ru';
    }

    return 'en';
}

/**
 * Visitor return URL appended with booking reference (success / decline / cancel all land here unless custom).
 *
 * @param array<string,mixed> $settings   Booking settings.
 * @param int                 $booking_id Booking post ID.
 * @return string Absolute URL (escaped-safe for JSON).
 */
function dilijanvillas_paylink_build_guest_return_base_url($settings, $booking_id)
{
    $booking_id = (int) $booking_id;

    // Prefer the page the guest actually started the booking from, so paying
    // returns them exactly where they were instead of a single global page.
    $base = dilijanvillas_sanitize_internal_return_url(
        (string) get_post_meta($booking_id, '_dv_return_url', true)
    );

    if ($base === '') {
        $base = trim((string) $settings['paylink_callback_url']);
    }
    if ($base === '') {
        $base = home_url('/');
    }

    return (string) add_query_arg(
        array(
            'dv_booking' => $booking_id,
            'dv_payment_return' => 1,
        ),
        esc_url_raw($base)
    );
}

/**
 * Accept a return URL only when it points back at this site.
 *
 * PayLink redirects the guest to whatever we put in backUrl, so an unchecked
 * value from the browser would be an open redirect.
 *
 * @param string $url Candidate URL supplied by the booking form.
 * @return string Clean same-site URL, or empty string when it is not ours.
 */
function dilijanvillas_sanitize_internal_return_url($url)
{
    $url = trim((string) $url);
    if ($url === '') {
        return '';
    }

    $host = wp_parse_url($url, PHP_URL_HOST);
    $home_host = wp_parse_url(home_url('/'), PHP_URL_HOST);

    if (empty($host) || empty($home_host) || strtolower($host) !== strtolower($home_host)) {
        return '';
    }

    $scheme = strtolower((string) wp_parse_url($url, PHP_URL_SCHEME));
    if ($scheme !== '' && !in_array($scheme, array('http', 'https'), true)) {
        return '';
    }

    // Strip our own markers plus PayLink's id so they are not duplicated later.
    return (string) remove_query_arg(array('dv_booking', 'dv_payment_return', 'id'), $url);
}

/**
 * Best-effort extract of checkout / transaction redirect URLs from assorted PayLink payloads.
 *
 * @param mixed $decoded JSON decoded array.
 * @return string Absolute URL or empty string.
 */
function dilijanvillas_paylink_extract_redirect_from_response($decoded)
{
    if (!is_array($decoded)) {
        return '';
    }

    if (!empty($decoded['checkout']['redirect_url']) && filter_var((string) $decoded['checkout']['redirect_url'], FILTER_VALIDATE_URL)) {
        return (string) $decoded['checkout']['redirect_url'];
    }
    if (!empty($decoded['data']['checkout']['redirect_url']) && filter_var((string) $decoded['data']['checkout']['redirect_url'], FILTER_VALIDATE_URL)) {
        return (string) $decoded['data']['checkout']['redirect_url'];
    }
    if (!empty($decoded['transaction']['redirect_url']) && filter_var((string) $decoded['transaction']['redirect_url'], FILTER_VALIDATE_URL)) {
        return (string) $decoded['transaction']['redirect_url'];
    }
    foreach (array('paymentUrl', 'payment_url', 'url', 'checkoutUrl', 'pay_url', 'redirectUrl', 'redirect_url') as $key) {
        if (!empty($decoded[$key]) && filter_var((string) $decoded[$key], FILTER_VALIDATE_URL)) {
            return (string) $decoded[$key];
        }
    }

    if (!empty($decoded['checkout']) && is_array($decoded['checkout'])) {
        foreach (array('redirect_url', 'pay_url', 'payment_url') as $subkey) {
            if (!empty($decoded['checkout'][$subkey]) && filter_var((string) $decoded['checkout'][$subkey], FILTER_VALIDATE_URL)) {
                return (string) $decoded['checkout'][$subkey];
            }
        }
    }

    return '';
}

/**
 * Normalize Armenian PayLink Integration API base (no trailing slash).
 *
 * @param string $base Root URL such as https://integration.apitest.paylink.am
 * @return string
 */
function dilijanvillas_paylink_am_normalize_root($base)
{
    return rtrim(trim((string) $base), '/');
}

/**
 * Obtain JWT for Armenian Integration API (/api/authorization/authorize).
 * Caches transient using token expiration minus a short skew.
 *
 * @param array<string,mixed> $settings Booking settings.
 * @return string Bearer token inner value or empty.
 */
function dilijanvillas_paylink_am_token_cache_key($settings)
{
    $partner_id = trim((string) $settings['paylink_merchant_id']);
    $partner_key = trim((string) $settings['paylink_api_token']);
    $api_root = dilijanvillas_paylink_am_normalize_root((string) $settings['paylink_am_api_base']);

    // API root is part of the key so a test token is never reused against prod.
    return 'dv_pl_am_' . md5($api_root . '|' . $partner_id . '|' . $partner_key);
}

function dilijanvillas_paylink_am_get_bearer_token($settings, $force_refresh = false)
{
    $partner_id = trim((string) $settings['paylink_merchant_id']);
    $partner_key = trim((string) $settings['paylink_api_token']);
    $api_root = dilijanvillas_paylink_am_normalize_root((string) $settings['paylink_am_api_base']);

    if ($partner_id === '' || $partner_key === '' || $api_root === '') {
        dilijanvillas_paylink_log(
            'authorize',
            'error',
            array('message' => __('PayLink settings incomplete: API base URL, partnerId or partnerKey is empty.', 'dilijanvillas'))
        );

        return '';
    }

    $cache_key = dilijanvillas_paylink_am_token_cache_key($settings);
    if ($force_refresh) {
        delete_transient($cache_key);
    } else {
        $cached = get_transient($cache_key);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }
    }

    $authorize_url = $api_root . '/api/authorization/authorize';
    $response = wp_remote_post(
        $authorize_url,
        array(
            'timeout' => 25,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ),
            'body' => wp_json_encode(
                array(
                    'partnerId' => $partner_id,
                    'partnerKey' => $partner_key,
                )
            ),
        )
    );

    if (is_wp_error($response)) {
        list($status, $message) = dilijanvillas_paylink_describe_response($response);
        dilijanvillas_paylink_log('authorize', 'error', array('endpoint' => $authorize_url, 'status' => $status, 'message' => $message));

        return '';
    }

    $code = (int) wp_remote_retrieve_response_code($response);
    if ($code < 200 || $code >= 300) {
        list($status, $message) = dilijanvillas_paylink_describe_response($response);
        dilijanvillas_paylink_log('authorize', 'error', array('endpoint' => $authorize_url, 'status' => $status, 'message' => $message));

        return '';
    }

    $json = json_decode((string) wp_remote_retrieve_body($response), true);
    $token_inner = '';
    if (isset($json['accessToken']) && is_array($json['accessToken'])) {
        $token_inner = trim((string) ($json['accessToken']['token'] ?? ''));
    }

    if ($token_inner === '') {
        dilijanvillas_paylink_log(
            'authorize',
            'error',
            array(
                'endpoint' => $authorize_url,
                'status' => $code,
                'message' => __('HTTP 200 but accessToken.token was empty or missing in the response.', 'dilijanvillas'),
            )
        );

        return '';
    }

    dilijanvillas_paylink_log('authorize', 'ok', array('endpoint' => $authorize_url, 'status' => $code));

    $expiration_raw = isset($json['accessToken']['expiration']) ? (string) $json['accessToken']['expiration'] : '';

    // The API returns a naive timestamp on Yerevan time (UTC+4) with no zone
    // suffix, while WordPress keeps PHP on UTC. Feeding that to strtotime()
    // overshoots by the offset and we would cache a token that is already dead,
    // so only trust the value when it actually carries a zone.
    $exp_ts = false;
    if ($expiration_raw !== '' && preg_match('/(Z|[+\-]\d{2}:?\d{2})$/i', $expiration_raw)) {
        $exp_ts = strtotime($expiration_raw);
    }

    $ttl = 15 * MINUTE_IN_SECONDS;
    if ($exp_ts !== false && $exp_ts > time()) {
        $ttl = max(120, (int) ($exp_ts - time() - 120));
    }

    // Observed access-token lifetime is one hour; never cache close to that.
    $ttl = min($ttl, 45 * MINUTE_IN_SECONDS);

    set_transient($cache_key, $token_inner, $ttl);

    return $token_inner;
}

/**
 * Register a payment link via Armenian Integration API and return visitor redirect URL.
 *
 * Swagger: POST /api/request/register.
 *
 * @param int    $booking_id      Booking CPT ID.
 * @param float  $amount_major    Total amount in major currency units (API expects number/double).
 * @param string $currency        Booking currency code.
 * @param string $customer_name   Guest name for requestInfo helper text.
 * @param string $customer_email  Unused by API payload (kept for future extensions).
 * @return string Nullable redirectUrl from PayLink or empty string.
 */
function dilijanvillas_paylink_am_register_payment_redirect($booking_id, $amount_major, $currency, $customer_name, $customer_email)
{
    unset($customer_email); // Reserved for upcoming API extensions.

    $settings = dilijanvillas_get_booking_settings();
    $api_root = dilijanvillas_paylink_am_normalize_root((string) $settings['paylink_am_api_base']);
    $jwt = dilijanvillas_paylink_am_get_bearer_token($settings);

    if ($api_root === '' || $jwt === '') {
        // authorize() already logged the reason; note which booking was lost.
        dilijanvillas_paylink_log(
            'register',
            'error',
            array(
                'booking_id' => (int) $booking_id,
                'message' => __('Skipped: no API base URL or no bearer token (see the authorize entry above).', 'dilijanvillas'),
            )
        );

        return '';
    }

    $booking_id = (int) $booking_id;
    $tracking = 'DV-' . $booking_id;
    update_post_meta($booking_id, '_dv_paylink_tracking_id', $tracking);

    $currency = strtoupper(trim((string) $currency));
    if ($currency === '') {
        $currency = strtoupper(trim((string) $settings['currency']));
    }

    $request_type = trim((string) ($settings['paylink_am_request_type'] ?? 'Booking'));
    if ($request_type === '') {
        $request_type = 'Booking';
    }

    $guest_name_trim = trim((string) $customer_name);
    $request_info = $tracking . ($guest_name_trim !== '' ? ' — ' . $guest_name_trim : '');

    $register_body = array(
        'allowAnonymous' => true,
        'amount' => round(max(0, (float) $amount_major), 2),
        'isActive' => true,
        'isFlexible' => false,
        'requestType' => $request_type,
        'currency' => $currency,
        'language' => dilijanvillas_paylink_resolve_checkout_language($settings),
        'backUrl' => dilijanvillas_paylink_build_guest_return_base_url($settings, $booking_id),
        'requestInfo' => $request_info,
    );

    $register_url = $api_root . '/api/request/register';
    $post_register = static function ($token) use ($register_url, $register_body) {
        return wp_remote_post(
            $register_url,
            array(
                'timeout' => 30,
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ),
                'body' => wp_json_encode($register_body),
            )
        );
    };

    $response = $post_register($jwt);

    // A cached token can still expire mid-flight; re-authorize once rather than
    // dropping the booking on the floor without a payment link.
    if (!is_wp_error($response) && in_array((int) wp_remote_retrieve_response_code($response), array(401, 403), true)) {
        dilijanvillas_paylink_log(
            'register',
            'error',
            array(
                'endpoint' => $register_url,
                'status' => (int) wp_remote_retrieve_response_code($response),
                'message' => __('Token rejected — re-authorizing and retrying once.', 'dilijanvillas'),
                'booking_id' => $booking_id,
            )
        );

        $jwt_retry = dilijanvillas_paylink_am_get_bearer_token($settings, true);
        if ($jwt_retry !== '') {
            $response = $post_register($jwt_retry);
        }
    }

    if (is_wp_error($response) || (int) wp_remote_retrieve_response_code($response) < 200 || (int) wp_remote_retrieve_response_code($response) >= 300) {
        list($status, $message) = dilijanvillas_paylink_describe_response($response);
        dilijanvillas_paylink_log(
            'register',
            'error',
            array('endpoint' => $register_url, 'status' => $status, 'message' => $message, 'booking_id' => $booking_id)
        );
        update_post_meta($booking_id, '_dv_paylink_last_error', sprintf('HTTP %d — %s', $status, $message));

        return '';
    }

    $code = (int) wp_remote_retrieve_response_code($response);
    $json = json_decode((string) wp_remote_retrieve_body($response), true);
    $redirect_url = '';
    if (is_array($json)) {
        $redirect_candidate = isset($json['redirectUrl']) ? trim((string) $json['redirectUrl']) : '';
        if ($redirect_candidate !== '' && filter_var($redirect_candidate, FILTER_VALIDATE_URL)) {
            $redirect_url = $redirect_candidate;
        }
        if (!empty($json['requestId'])) {
            update_post_meta($booking_id, '_dv_paylink_request_id', sanitize_text_field((string) $json['requestId']));
        }
    }

    if ($redirect_url === '') {
        $detail = __('HTTP 200 but redirectUrl was empty or not a valid URL.', 'dilijanvillas');
        dilijanvillas_paylink_log(
            'register',
            'error',
            array('endpoint' => $register_url, 'status' => $code, 'message' => $detail, 'booking_id' => $booking_id)
        );
        update_post_meta($booking_id, '_dv_paylink_last_error', $detail);

        return '';
    }

    delete_post_meta($booking_id, '_dv_paylink_last_error');
    dilijanvillas_paylink_log(
        'register',
        'ok',
        array('endpoint' => $register_url, 'status' => $code, 'booking_id' => $booking_id)
    );

    return $redirect_url;
}

/**
 * Query Armenian Integration API payments for request UUID (GET /api/payment/{requestId}).
 *
 * @param array<string,mixed> $settings Booking settings row.
 * @param string               $request_uuid Registered request id.
 * @return array<int,mixed>|null Rows or null on failure.
 */
function dilijanvillas_paylink_am_fetch_payment_rows($settings, $request_uuid)
{
    $request_uuid = trim((string) $request_uuid);
    if ($request_uuid === '') {
        return null;
    }

    $api_root = dilijanvillas_paylink_am_normalize_root((string) $settings['paylink_am_api_base']);
    $jwt = dilijanvillas_paylink_am_get_bearer_token($settings);

    if ($api_root === '' || $jwt === '') {
        return null;
    }

    $endpoint = $api_root . '/api/payment/' . rawurlencode($request_uuid);
    $response = wp_remote_get(
        $endpoint,
        array(
            'timeout' => 25,
            'headers' => array(
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $jwt,
            ),
        )
    );

    if (is_wp_error($response)) {
        return null;
    }

    $resp_code = (int) wp_remote_retrieve_response_code($response);
    if ($resp_code < 200 || $resp_code >= 300) {
        return null;
    }

    $json = json_decode((string) wp_remote_retrieve_body($response), true);
    return is_array($json) ? $json : null;
}

/**
 * Look a payment up by the id PayLink appends to backUrl.
 *
 * This is the documented callback path: the guest comes back to
 * `backUrl?id=payment_id`, and that id is resolved via get-by-order-id.
 *
 * @param array<string,mixed> $settings Booking settings row.
 * @param string              $order_id Value of the `id` query parameter.
 * @return array<int,mixed>|null Rows or null on failure.
 */
function dilijanvillas_paylink_am_fetch_payment_by_order_id($settings, $order_id)
{
    $order_id = trim((string) $order_id);
    if ($order_id === '') {
        return null;
    }

    $api_root = dilijanvillas_paylink_am_normalize_root((string) $settings['paylink_am_api_base']);
    $jwt = dilijanvillas_paylink_am_get_bearer_token($settings);

    if ($api_root === '' || $jwt === '') {
        return null;
    }

    // add_query_arg() encodes the value itself — do not pre-encode it.
    $endpoint = add_query_arg('id', $order_id, $api_root . '/api/payment/get-by-order-id');
    $response = wp_remote_get(
        $endpoint,
        array(
            'timeout' => 25,
            'headers' => array(
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $jwt,
            ),
        )
    );

    $resp_code = is_wp_error($response) ? 0 : (int) wp_remote_retrieve_response_code($response);
    if (is_wp_error($response) || $resp_code < 200 || $resp_code >= 300) {
        list($status, $message) = dilijanvillas_paylink_describe_response($response);
        dilijanvillas_paylink_log('get-by-order-id', 'error', array('endpoint' => $endpoint, 'status' => $status, 'message' => $message));

        return null;
    }

    $json = json_decode((string) wp_remote_retrieve_body($response), true);
    if (!is_array($json)) {
        return null;
    }

    // A single object is normalised to a one-row list for the caller.
    return isset($json['requestId']) || isset($json['orderId']) ? array($json) : $json;
}

/**
 * After guest returns from PayLink Armenia, reconcile payment approval against API.
 *
 * @param int $booking_id CPT ID dv_booking.
 * @return void
 */
function dilijanvillas_paylink_am_sync_booking_status($booking_id, $order_id = '')
{
    $booking_id = (int) $booking_id;
    $post = get_post($booking_id);
    if (!$post || $post->post_type !== 'dv_booking') {
        return;
    }

    $settings = dilijanvillas_get_booking_settings();
    if (($settings['paylink_integration_mode'] ?? '') !== 'armenia_integration') {
        return;
    }

    $request_uuid = trim((string) get_post_meta($booking_id, '_dv_paylink_request_id', true));
    $order_id = trim((string) $order_id);
    if ($request_uuid === '' && $order_id === '') {
        return;
    }

    $booking_total = (float) get_post_meta($booking_id, '_dv_total', true);
    $booking_currency = strtoupper(trim((string) get_post_meta($booking_id, '_dv_currency', true)));

    // Documented callback path first, then the request-scoped lookup.
    $rows = null;
    if ($order_id !== '') {
        $rows = dilijanvillas_paylink_am_fetch_payment_by_order_id($settings, $order_id);
    }
    if (empty($rows) && $request_uuid !== '') {
        $rows = dilijanvillas_paylink_am_fetch_payment_rows($settings, $request_uuid);
    }

    if (is_array($rows)) {
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $approved = !empty($row['paymentApproved']);
            $amt = isset($row['amount']) ? (float) $row['amount'] : null;
            $cur = isset($row['currency']) ? strtoupper(trim((string) $row['currency'])) : '';
            $status_text = isset($row['paymentStatus']) ? strtoupper(trim((string) $row['paymentStatus'])) : '';

            if (!$approved && (($status_text !== '' && strpos($status_text, 'APPROV') !== false))) {
                $approved = true;
            }

            if (!$approved || $amt === null) {
                continue;
            }

            $money_ok = abs($amt - $booking_total) < 0.02;
            $currency_ok = $cur === '' || $booking_currency === '' || ($cur === $booking_currency);

            if ($money_ok && $currency_ok) {
                update_post_meta($booking_id, '_dv_status', 'paid');
                update_post_meta($booking_id, '_dv_paylink_paid_detected_at', gmdate('c'));
                update_post_meta($booking_id, '_dv_paylink_order_id', isset($row['orderId']) ? (int) $row['orderId'] : 0);

                return;
            }
        }
    }

    $api_root = dilijanvillas_paylink_am_normalize_root((string) $settings['paylink_am_api_base']);
    $jwt = dilijanvillas_paylink_am_get_bearer_token($settings);

    if ($request_uuid !== '' && $api_root !== '' && $jwt !== '') {
        $req_endpoint = $api_root . '/api/request/' . rawurlencode($request_uuid);
        $req_response = wp_remote_get(
            $req_endpoint,
            array(
                'timeout' => 25,
                'headers' => array(
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $jwt,
                ),
            )
        );

        if (!is_wp_error($req_response)) {
            $code = (int) wp_remote_retrieve_response_code($req_response);
            if ($code >= 200 && $code < 300) {
                $body = json_decode((string) wp_remote_retrieve_body($req_response), true);
                if (is_array($body) && !empty($body['paidCount']) && (int) $body['paidCount'] > 0) {
                    update_post_meta($booking_id, '_dv_status', 'paid');
                    update_post_meta($booking_id, '_dv_paylink_paid_detected_at', gmdate('c'));

                    return;
                }
            }
        }
    }
}

/**
 * When guests land after PayLink Armenia backUrl, reconcile payment asynchronously.
 *
 * @return void
 */
function dilijanvillas_paylink_am_template_maybe_sync_payment()
{
    if (empty($_GET['dv_payment_return'])) {
        return;
    }

    if (empty($_GET['dv_booking'])) {
        return;
    }

    $booking_id = (int) sanitize_text_field((string) wp_unslash($_GET['dv_booking']));
    if ($booking_id <= 0) {
        return;
    }

    // Already settled — do not let a public URL trigger repeat API calls.
    if ((string) get_post_meta($booking_id, '_dv_status', true) === 'paid') {
        return;
    }

    // PayLink appends the payment id to backUrl; it is the documented lookup key.
    $order_id = isset($_GET['id']) ? sanitize_text_field((string) wp_unslash($_GET['id'])) : '';

    dilijanvillas_paylink_am_sync_booking_status($booking_id, $order_id);
}
add_action('template_redirect', 'dilijanvillas_paylink_am_template_maybe_sync_payment');

/**
 * Create hosted PayLink checkout session and return visitor redirect URL.
 *
 * Matches PayLink "Create a payment token" API (e.g. checkout host /ctp/api/checkouts).
 *
 * @param int    $booking_id      Booking CPT ID.
 * @param float  $amount_major    Total in major currency units (matches booking calculator).
 * @param string $currency        Booking currency ISO code.
 * @param string $customer_name   Guest name.
 * @param string $customer_email  Guest email (optional).
 * @return string Hosted checkout HTTPS URL or empty string on failure.
 */
function dilijanvillas_build_paylink_hosted_checkout_redirect($booking_id, $amount_major, $currency, $customer_name, $customer_email)
{
    $settings = dilijanvillas_get_booking_settings();

    $api_url = trim((string) $settings['paylink_api_url']);
    $secret = trim((string) $settings['paylink_api_token']);
    $shop_id = trim((string) $settings['paylink_merchant_id']);

    if ($api_url === '' || $secret === '' || $shop_id === '') {
        return '';
    }

    $currency = strtoupper(trim((string) $currency));
    if ($currency === '') {
        $currency = strtoupper(trim((string) $settings['currency']));
    }

    $booking_id = (int) $booking_id;
    $minor_amount = dilijanvillas_paylink_amount_to_minor_units($amount_major, $currency);
    $tracking_id = 'DV-' . $booking_id;
    update_post_meta($booking_id, '_dv_paylink_tracking_id', $tracking_id);

    $notification_url = (string) rest_url('dilijanvillas/v1/paylink-webhook');
    $return_url_base = dilijanvillas_paylink_build_guest_return_base_url($settings, $booking_id);

    $name_parts = preg_split('/\s+/', trim((string) $customer_name));
    $first_name = $name_parts[0] ?? '';
    $last_name = count($name_parts) > 1 ? implode(' ', array_slice($name_parts, 1)) : '';

    $customer_block = array();
    if ($first_name !== '') {
        $customer_block['first_name'] = $first_name;
    }
    if ($last_name !== '') {
        $customer_block['last_name'] = $last_name;
    }
    if (is_email($customer_email)) {
        $customer_block['email'] = $customer_email;
    }

    $checkout_payload = array(
        'checkout' => array(
            'transaction_type' => 'payment',
            'attempts' => 3,
            'iframe' => false,
            'test' => !empty($settings['paylink_test_mode']),
            'settings' => array(
                'return_url' => $return_url_base,
                'success_url' => $return_url_base,
                'fail_url' => $return_url_base,
                'cancel_url' => $return_url_base,
                'decline_url' => $return_url_base,
                'notification_url' => $notification_url,
                'language' => dilijanvillas_paylink_resolve_checkout_language($settings),
            ),
            'order' => array(
                'currency' => $currency,
                'amount' => $minor_amount,
                'description' => sprintf(
                    /* translators: %d: booking numeric ID */
                    __('Armenita Family Resort booking #%d', 'dilijanvillas'),
                    $booking_id
                ),
                'tracking_id' => $tracking_id,
            ),
        ),
    );

    if (!empty($customer_block)) {
        $checkout_payload['checkout']['customer'] = $customer_block;
    }

    $response = wp_remote_post(
        $api_url,
        array(
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'X-API-Version' => '2',
                'Authorization' => 'Basic ' . base64_encode($shop_id . ':' . $secret),
            ),
            'body' => wp_json_encode($checkout_payload),
        )
    );

    if (is_wp_error($response)) {
        return '';
    }

    $code = (int) wp_remote_retrieve_response_code($response);
    if ($code < 200 || $code >= 300) {
        return '';
    }

    $json = json_decode((string) wp_remote_retrieve_body($response), true);
    return dilijanvillas_paylink_extract_redirect_from_response($json);
}

/**
 * Legacy Bearer + merchant JSON gateway (backward compatible with earlier theme drafts).
 *
 * @param int    $booking_id   Booking CPT ID.
 * @param float  $amount_major Total due.
 * @param string $currency     ISO currency.
 * @return string Redirect URL if provider returns any known key.
 */
function dilijanvillas_build_paylink_legacy_json_redirect($booking_id, $amount_major, $currency)
{
    $settings = dilijanvillas_get_booking_settings();
    $api_url = trim((string) $settings['paylink_api_url']);
    $token = trim((string) $settings['paylink_api_token']);
    $merchant_id = trim((string) $settings['paylink_merchant_id']);
    if ($api_url === '' || $token === '' || $merchant_id === '') {
        return '';
    }

    update_post_meta((int) $booking_id, '_dv_paylink_tracking_id', 'DV-' . (int) $booking_id);

    $callback_url = trim((string) $settings['paylink_callback_url']);
    if ($callback_url === '') {
        $callback_url = home_url('/');
    }

    $currency = strtoupper(trim((string) $currency));
    if ($currency === '') {
        $currency = strtoupper(trim((string) $settings['currency']));
    }

    $payload = array(
        'merchantId' => $merchant_id,
        'orderId' => 'DV-' . (int) $booking_id,
        'amount' => (float) $amount_major,
        'currency' => $currency,
        'description' => sprintf('Armenita Family Resort booking #%d', (int) $booking_id),
        'callbackUrl' => $callback_url,
    );

    $response = wp_remote_post(
        $api_url,
        array(
            'timeout' => 20,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ),
            'body' => wp_json_encode($payload),
        )
    );

    if (is_wp_error($response)) {
        return '';
    }

    $resp_code = (int) wp_remote_retrieve_response_code($response);
    if ($resp_code < 200 || $resp_code >= 300) {
        return '';
    }

    $json = json_decode((string) wp_remote_retrieve_body($response), true);
    return dilijanvillas_paylink_extract_redirect_from_response($json);
}

/**
 * Build PayLink payment redirect URL depending on configured integration mode.
 *
 * @param int    $booking_id      CPT ID for dv_booking.
 * @param float  $amount_major    Total payable.
 * @param string $currency        Booking currency ISO code.
 * @param string $customer_name   Customer name.
 * @param string $customer_email  Email (optional).
 * @return string HTTPS redirect target or empty if PayLink inactive / failed.
 */
function dilijanvillas_build_paylink_payment_redirect($booking_id, $amount_major, $currency, $customer_name, $customer_email)
{
    $settings = dilijanvillas_get_booking_settings();
    $mode = isset($settings['paylink_integration_mode']) ? (string) $settings['paylink_integration_mode'] : 'hosted_checkout';

    if ($mode === 'armenia_integration') {
        return dilijanvillas_paylink_am_register_payment_redirect(
            $booking_id,
            $amount_major,
            $currency,
            $customer_name,
            $customer_email
        );
    }

    if ($mode === 'legacy_json_post') {
        return dilijanvillas_build_paylink_legacy_json_redirect($booking_id, $amount_major, $currency);
    }

    return dilijanvillas_build_paylink_hosted_checkout_redirect(
        $booking_id,
        $amount_major,
        $currency,
        $customer_name,
        $customer_email
    );
}

/**
 * Convenience wrapper loading customer details from booking meta after save_post.
 *
 * @param int   $booking_id Booking ID.
 * @param float $amount     Total payable.
 * @return string Checkout URL if PayLink succeeds.
 */
function dilijanvillas_build_paylink_url($booking_id, $amount)
{
    $booking_id = (int) $booking_id;
    if ($booking_id <= 0) {
        return '';
    }

    $currency = trim((string) get_post_meta($booking_id, '_dv_currency', true));
    $settings = dilijanvillas_get_booking_settings();
    if ($currency === '') {
        $currency = (string) $settings['currency'];
    }

    return dilijanvillas_build_paylink_payment_redirect(
        $booking_id,
        (float) $amount,
        $currency,
        (string) get_post_meta($booking_id, '_dv_customer_name', true),
        (string) get_post_meta($booking_id, '_dv_customer_email', true)
    );
}

/**
 * REST callback for PayLink webhooks (`notification_url`).
 *
 * Marks bookings `paid` when transaction reports success.
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response|WP_HTTP_Response Response.
 */
function dilijanvillas_rest_paylink_webhook_callback($request)
{
    $decoded = json_decode((string) $request->get_body(), true);

    $tracking_raw = '';

    if (is_array($decoded)) {
        if (!empty($decoded['transaction']['tracking_id'])) {
            $tracking_raw = (string) $decoded['transaction']['tracking_id'];
        } elseif (!empty($decoded['tracking_id'])) {
            $tracking_raw = (string) $decoded['tracking_id'];
        } elseif (!empty($decoded['order']['tracking_id'])) {
            $tracking_raw = (string) $decoded['order']['tracking_id'];
        }

        $booking_match = preg_match('/^DV-(\d+)/i', trim($tracking_raw), $matches_bid);

        $transaction_status = '';
        if ($booking_match) {
            if (!empty($decoded['transaction']['status'])) {
                $transaction_status = strtolower(trim((string) $decoded['transaction']['status']));
            }
            $payment_gate_status = '';
            if (isset($decoded['transaction']['payment']['status'])) {
                $payment_gate_status = strtolower(trim((string) $decoded['transaction']['payment']['status']));
            }
            $booking_id = (int) $matches_bid[1];
            $post = get_post($booking_id);

            if ($post && $post->post_type === 'dv_booking') {
                if (!empty($decoded['transaction']['uid'])) {
                    update_post_meta(
                        $booking_id,
                        '_dv_paylink_transaction_uid',
                        sanitize_text_field((string) $decoded['transaction']['uid'])
                    );
                }

                $looks_paid =
                    ($transaction_status === 'successful')
                    || ($payment_gate_status === 'successful');

                if (!$looks_paid && !empty($decoded['last_transaction']['status'])) {
                    $looks_paid = strtolower((string) $decoded['last_transaction']['status']) === 'successful';
                }

                if ($looks_paid) {
                    $expected_minor = dilijanvillas_paylink_amount_to_minor_units(
                        (float) get_post_meta($booking_id, '_dv_total', true),
                        (string) get_post_meta($booking_id, '_dv_currency', true)
                    );
                    $tx_amount = isset($decoded['transaction']['amount']) ? (int) $decoded['transaction']['amount'] : null;
                    $tx_currency = '';
                    if (!empty($decoded['transaction']['currency'])) {
                        $tx_currency = strtoupper(trim((string) $decoded['transaction']['currency']));
                    }
                    $booking_currency = strtoupper(trim((string) get_post_meta($booking_id, '_dv_currency', true)));

                    $amount_matches = ($tx_amount === null) || ($tx_amount === $expected_minor);
                    $currency_matches = $tx_currency === '' || ($booking_currency !== '' && $tx_currency === $booking_currency);

                    if ($amount_matches && $currency_matches) {
                        update_post_meta($booking_id, '_dv_status', 'paid');
                        update_post_meta($booking_id, '_dv_paylink_paid_detected_at', gmdate('c'));
                    }
                }
            }
        }
    }

    return rest_ensure_response(array('received' => true));
}

/**
 * Register PayLink REST routes for hosted checkout notifications.
 */
function dilijanvillas_register_paylink_rest_webhook()
{
    register_rest_route(
        'dilijanvillas/v1',
        '/paylink-webhook',
        array(
            'methods' => 'POST',
            'callback' => 'dilijanvillas_rest_paylink_webhook_callback',
            'permission_callback' => '__return_true',
        )
    );
}
add_action('rest_api_init', 'dilijanvillas_register_paylink_rest_webhook');

/**
 * Return booking config payload for frontend.
 *
 * @return array<string,mixed>
 */
function dilijanvillas_get_booking_frontend_config()
{
    $settings = dilijanvillas_get_booking_settings();
    return array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('dilijanvillas_booking_nonce'),
        'currency' => (string) $settings['currency'],
        'accommodations' => dilijanvillas_get_booking_accommodations(),
    );
}

/**
 * Handle AJAX check availability.
 */
function dilijanvillas_ajax_check_booking()
{
    check_ajax_referer('dilijanvillas_booking_nonce', 'nonce');
    nocache_headers();

    $accommodation_id = isset($_POST['accommodation_id']) ? (int) $_POST['accommodation_id'] : 0;
    $start_date = isset($_POST['start_date']) ? sanitize_text_field((string) $_POST['start_date']) : '';
    $end_date = isset($_POST['end_date']) ? sanitize_text_field((string) $_POST['end_date']) : '';

    if ($accommodation_id <= 0 || !dilijanvillas_is_valid_booking_date($start_date) || !dilijanvillas_is_valid_booking_date($end_date) || $end_date < $start_date) {
        wp_send_json_error(array('message' => 'Invalid booking request.'), 400);
    }

    $availability = dilijanvillas_check_booking_availability($accommodation_id, $start_date, $end_date);
    $price = dilijanvillas_calculate_booking_price($start_date, $end_date, $accommodation_id);
    $weekend_min_nights = dilijanvillas_get_weekend_min_nights($accommodation_id, $start_date, $end_date);

    wp_send_json_success(
        array(
            'available' => !empty($availability['available']),
            'reason' => (string) $availability['reason'],
            'source' => isset($availability['source']) ? (string) $availability['source'] : '',
            'sourceId' => isset($availability['sourceId']) ? (int) $availability['sourceId'] : 0,
            'price' => $price,
            'weekend_min_nights' => $weekend_min_nights,
            'weekend_min_nights_met' => $weekend_min_nights <= 0 || (int) $price['nights'] >= $weekend_min_nights,
        )
    );
}
add_action('wp_ajax_dilijanvillas_check_booking', 'dilijanvillas_ajax_check_booking');
add_action('wp_ajax_nopriv_dilijanvillas_check_booking', 'dilijanvillas_ajax_check_booking');

/**
 * Return blocked date ranges for a given accommodation.
 */
function dilijanvillas_ajax_get_blocked_ranges()
{
    check_ajax_referer('dilijanvillas_booking_nonce', 'nonce');
    nocache_headers();

    $accommodation_id = isset($_POST['accommodation_id']) ? (int) $_POST['accommodation_id'] : 0;
    if ($accommodation_id <= 0) {
        wp_send_json_error(array('message' => 'Invalid accommodation.'), 400);
    }

    $blocked = dilijanvillas_get_blocked_ranges($accommodation_id);
    wp_send_json_success(
        array(
            'ranges' => $blocked,
            'generated_at' => time(),
        )
    );
}
add_action('wp_ajax_dilijanvillas_get_blocked_ranges', 'dilijanvillas_ajax_get_blocked_ranges');
add_action('wp_ajax_nopriv_dilijanvillas_get_blocked_ranges', 'dilijanvillas_ajax_get_blocked_ranges');

/**
 * Handle AJAX booking creation.
 */
function dilijanvillas_ajax_create_booking()
{
    check_ajax_referer('dilijanvillas_booking_nonce', 'nonce');
    nocache_headers();

    $honeypot = isset($_POST['website']) ? trim((string) $_POST['website']) : '';
    if ($honeypot !== '') {
        wp_send_json_error(array('message' => 'Spam detected.'), 400);
    }

    $accommodation_id = isset($_POST['accommodation_id']) ? (int) $_POST['accommodation_id'] : 0;
    $start_date = isset($_POST['start_date']) ? sanitize_text_field((string) $_POST['start_date']) : '';
    $end_date = isset($_POST['end_date']) ? sanitize_text_field((string) $_POST['end_date']) : '';
    $guests = isset($_POST['guests']) ? max(1, (int) $_POST['guests']) : 1;
    $has_children = isset($_POST['has_children']) && $_POST['has_children'] === 'yes' ? 'yes' : 'no';
    $children_count = isset($_POST['children_count']) ? max(0, (int) $_POST['children_count']) : 0;
    $name = isset($_POST['name']) ? sanitize_text_field((string) $_POST['name']) : '';
    $phone = isset($_POST['phone']) ? sanitize_text_field((string) $_POST['phone']) : '';
    $email = isset($_POST['email']) ? sanitize_email((string) $_POST['email']) : '';

    if ($accommodation_id <= 0 || !dilijanvillas_is_valid_booking_date($start_date) || !dilijanvillas_is_valid_booking_date($end_date) || $end_date < $start_date) {
        wp_send_json_error(array('message' => 'Invalid dates or accommodation.'), 400);
    }
    if ($name === '' || $phone === '') {
        wp_send_json_error(array('message' => 'Name and phone are required.'), 400);
    }

    $availability = dilijanvillas_check_booking_availability($accommodation_id, $start_date, $end_date);
    if (empty($availability['available'])) {
        wp_send_json_error(array('message' => 'Selected dates are unavailable.'), 409);
    }

    $price = dilijanvillas_calculate_booking_price($start_date, $end_date, $accommodation_id);

    // Weekend minimum stay, configured per Price period. Enforced here because
    // the browser check can be bypassed.
    $weekend_min_nights = dilijanvillas_get_weekend_min_nights($accommodation_id, $start_date, $end_date);
    if ($weekend_min_nights > 0 && (int) $price['nights'] < $weekend_min_nights) {
        wp_send_json_error(
            array(
                'message' => sprintf(
                    /* translators: %d: minimum number of nights */
                    _n(
                        'Weekend stays require at least %d night.',
                        'Weekend stays require at least %d nights.',
                        $weekend_min_nights,
                        'dilijanvillas'
                    ),
                    $weekend_min_nights
                ),
                'weekend_min_nights' => $weekend_min_nights,
            ),
            409
        );
    }

    $accommodation_title = $accommodation_id > 0 ? (string) get_the_title((int) $accommodation_id) : '';
    if ($accommodation_title === '') {
        $accommodation_title = __('Accommodation', 'dilijanvillas');
    }
    $booking_id = wp_insert_post(
        array(
            'post_type' => 'dv_booking',
            'post_status' => 'publish',
            'post_title' => sprintf('Booking %s – %s - %s - %s', $start_date, $end_date, $name, $accommodation_title),
        ),
        true
    );

    if (is_wp_error($booking_id) || !$booking_id) {
        wp_send_json_error(array('message' => 'Could not create booking.'), 500);
    }

    update_post_meta((int) $booking_id, '_dv_status', 'pending');
    update_post_meta((int) $booking_id, '_dv_accommodation_id', $accommodation_id);
    update_post_meta((int) $booking_id, '_dv_checkin', $start_date);
    update_post_meta((int) $booking_id, '_dv_checkout', $end_date);
    update_post_meta((int) $booking_id, '_dv_guests', $guests);
    update_post_meta((int) $booking_id, '_dv_has_children', $has_children);
    update_post_meta((int) $booking_id, '_dv_children_count', $children_count);
    update_post_meta((int) $booking_id, '_dv_customer_name', $name);
    update_post_meta((int) $booking_id, '_dv_customer_phone', $phone);
    update_post_meta((int) $booking_id, '_dv_customer_email', $email);
    update_post_meta((int) $booking_id, '_dv_total', (float) $price['total']);
    update_post_meta((int) $booking_id, '_dv_currency', (string) $price['currency']);
    update_post_meta((int) $booking_id, '_dv_nights', (int) $price['nights']);

    // Must be stored before the payment link is built — it becomes backUrl.
    $return_url = isset($_POST['return_url'])
        ? dilijanvillas_sanitize_internal_return_url((string) wp_unslash($_POST['return_url']))
        : '';
    if ($return_url !== '') {
        update_post_meta((int) $booking_id, '_dv_return_url', $return_url);
    }

    $payment_url = dilijanvillas_build_paylink_url((int) $booking_id, (float) $price['total']);
    if ($payment_url !== '') {
        update_post_meta((int) $booking_id, '_dv_status', 'payment_pending');
        update_post_meta((int) $booking_id, '_dv_payment_url', $payment_url);
    }

    wp_send_json_success(
        array(
            'booking_id' => (int) $booking_id,
            'payment_url' => $payment_url,
            'payment_error' => $payment_url === ''
                ? (string) get_post_meta((int) $booking_id, '_dv_paylink_last_error', true)
                : '',
            'price' => $price,
        )
    );
}
add_action('wp_ajax_dilijanvillas_create_booking', 'dilijanvillas_ajax_create_booking');
add_action('wp_ajax_nopriv_dilijanvillas_create_booking', 'dilijanvillas_ajax_create_booking');

/**
 * Booking statuses shown in admin.
 *
 * @return array<string,string>
 */
function dilijanvillas_get_booking_status_labels()
{
    return array(
        'pending' => __('Pending', 'dilijanvillas'),
        'confirmed' => __('Confirmed', 'dilijanvillas'),
        'paid' => __('Paid', 'dilijanvillas'),
        'payment_pending' => __('Payment pending', 'dilijanvillas'),
        'cancelled' => __('Cancelled', 'dilijanvillas'),
    );
}

/**
 * Register booking admin metaboxes.
 */
function dilijanvillas_register_booking_admin_metaboxes()
{
    add_meta_box(
        'dilijanvillas-booking-details',
        __('Booking details', 'dilijanvillas'),
        'dilijanvillas_render_booking_details_metabox',
        'dv_booking',
        'normal',
        'high'
    );

    add_meta_box(
        'dilijanvillas-unavailable-details',
        __('Unavailable period details', 'dilijanvillas'),
        'dilijanvillas_render_unavailable_details_metabox',
        'dv_unavailable',
        'normal',
        'high'
    );

    add_meta_box(
        'dilijanvillas-price-period-details',
        __('Price period details', 'dilijanvillas'),
        'dilijanvillas_render_price_period_metabox',
        'dv_price_period',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'dilijanvillas_register_booking_admin_metaboxes');

/**
 * Render dv_booking details metabox.
 *
 * @param WP_Post $post Current post.
 */
function dilijanvillas_render_booking_details_metabox($post)
{
    wp_nonce_field('dilijanvillas_save_booking_meta', 'dilijanvillas_booking_meta_nonce');

    $status = (string) get_post_meta($post->ID, '_dv_status', true);
    $accommodation_id = (int) get_post_meta($post->ID, '_dv_accommodation_id', true);
    $checkin = (string) get_post_meta($post->ID, '_dv_checkin', true);
    $checkout = (string) get_post_meta($post->ID, '_dv_checkout', true);
    $guests = (int) get_post_meta($post->ID, '_dv_guests', true);
    $has_children = (string) get_post_meta($post->ID, '_dv_has_children', true);
    $children_count = (int) get_post_meta($post->ID, '_dv_children_count', true);
    $customer_name = (string) get_post_meta($post->ID, '_dv_customer_name', true);
    $customer_phone = (string) get_post_meta($post->ID, '_dv_customer_phone', true);
    $customer_email = (string) get_post_meta($post->ID, '_dv_customer_email', true);
    $payment_url = (string) get_post_meta($post->ID, '_dv_payment_url', true);
    $total = (float) get_post_meta($post->ID, '_dv_total', true);
    $currency = (string) get_post_meta($post->ID, '_dv_currency', true);
    $nights = (int) get_post_meta($post->ID, '_dv_nights', true);

    if ($status === '') {
        $status = 'pending';
    }
    if ($guests <= 0) {
        $guests = 2;
    }
    if ($has_children !== 'yes') {
        $has_children = 'no';
    }
    if ($children_count <= 0) {
        $children_count = 1;
    }
    if ($currency === '') {
        $currency = 'AMD';
    }

    $status_labels = dilijanvillas_get_booking_status_labels();
    $accommodations = dilijanvillas_get_booking_accommodations();
    ?>
    <table class="form-table" role="presentation">
      <tr>
        <th scope="row"><label for="dv_booking_status"><?php esc_html_e('Status', 'dilijanvillas'); ?></label></th>
        <td>
          <select id="dv_booking_status" name="dv_booking_status">
            <?php foreach ($status_labels as $key => $label) : ?>
              <option value="<?php echo esc_attr($key); ?>" <?php selected($status, $key); ?>><?php echo esc_html($label); ?></option>
            <?php endforeach; ?>
          </select>
        </td>
      </tr>
      <tr>
        <th scope="row"><label for="dv_booking_accommodation"><?php esc_html_e('Accommodation', 'dilijanvillas'); ?></label></th>
        <td>
          <select id="dv_booking_accommodation" name="dv_booking_accommodation">
            <option value="0"><?php esc_html_e('Select accommodation', 'dilijanvillas'); ?></option>
            <?php foreach ($accommodations as $accommodation) : ?>
              <?php $option_id = isset($accommodation['id']) ? (int) $accommodation['id'] : 0; ?>
              <option value="<?php echo esc_attr((string) $option_id); ?>" <?php selected($accommodation_id, $option_id); ?>>
                <?php echo esc_html(isset($accommodation['title']) ? (string) $accommodation['title'] : ('#' . $option_id)); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </td>
      </tr>
      <tr>
        <th scope="row"><label for="dv_booking_checkin"><?php esc_html_e('Check-in', 'dilijanvillas'); ?></label></th>
        <td><input id="dv_booking_checkin" type="date" name="dv_booking_checkin" value="<?php echo esc_attr($checkin); ?>" /></td>
      </tr>
      <tr>
        <th scope="row"><label for="dv_booking_checkout"><?php esc_html_e('Check-out', 'dilijanvillas'); ?></label></th>
        <td><input id="dv_booking_checkout" type="date" name="dv_booking_checkout" value="<?php echo esc_attr($checkout); ?>" /></td>
      </tr>
      <tr>
        <th scope="row"><label for="dv_booking_guests"><?php esc_html_e('Guests', 'dilijanvillas'); ?></label></th>
        <td><input id="dv_booking_guests" type="number" min="1" max="20" name="dv_booking_guests" value="<?php echo esc_attr((string) $guests); ?>" /></td>
      </tr>
      <tr>
        <th scope="row"><label for="dv_booking_has_children"><?php esc_html_e('Children', 'dilijanvillas'); ?></label></th>
        <td>
          <select id="dv_booking_has_children" name="dv_booking_has_children">
            <option value="no" <?php selected($has_children, 'no'); ?>><?php esc_html_e('No', 'dilijanvillas'); ?></option>
            <option value="yes" <?php selected($has_children, 'yes'); ?>><?php esc_html_e('Yes', 'dilijanvillas'); ?></option>
          </select>
        </td>
      </tr>
      <tr>
        <th scope="row"><label for="dv_booking_children_count"><?php esc_html_e('Children count', 'dilijanvillas'); ?></label></th>
        <td><input id="dv_booking_children_count" type="number" min="0" max="20" name="dv_booking_children_count" value="<?php echo esc_attr((string) $children_count); ?>" /></td>
      </tr>
      <tr>
        <th scope="row"><label for="dv_booking_customer_name"><?php esc_html_e('Customer name', 'dilijanvillas'); ?></label></th>
        <td><input id="dv_booking_customer_name" type="text" class="regular-text" name="dv_booking_customer_name" value="<?php echo esc_attr($customer_name); ?>" /></td>
      </tr>
      <tr>
        <th scope="row"><label for="dv_booking_customer_phone"><?php esc_html_e('Customer phone', 'dilijanvillas'); ?></label></th>
        <td><input id="dv_booking_customer_phone" type="text" class="regular-text" name="dv_booking_customer_phone" value="<?php echo esc_attr($customer_phone); ?>" /></td>
      </tr>
      <tr>
        <th scope="row"><label for="dv_booking_customer_email"><?php esc_html_e('Customer email', 'dilijanvillas'); ?></label></th>
        <td><input id="dv_booking_customer_email" type="email" class="regular-text" name="dv_booking_customer_email" value="<?php echo esc_attr($customer_email); ?>" /></td>
      </tr>
      <tr>
        <th scope="row"><label for="dv_booking_total"><?php esc_html_e('Total amount', 'dilijanvillas'); ?></label></th>
        <td><input id="dv_booking_total" type="number" min="0" step="0.01" name="dv_booking_total" value="<?php echo esc_attr((string) $total); ?>" /></td>
      </tr>
      <tr>
        <th scope="row"><label for="dv_booking_currency"><?php esc_html_e('Currency', 'dilijanvillas'); ?></label></th>
        <td><input id="dv_booking_currency" type="text" class="small-text" name="dv_booking_currency" value="<?php echo esc_attr($currency); ?>" /></td>
      </tr>
      <tr>
        <th scope="row"><label for="dv_booking_nights"><?php esc_html_e('Nights', 'dilijanvillas'); ?></label></th>
        <td><input id="dv_booking_nights" type="number" min="1" name="dv_booking_nights" value="<?php echo esc_attr((string) $nights); ?>" /></td>
      </tr>
      <tr>
        <th scope="row"><label for="dv_booking_payment_url"><?php esc_html_e('Payment URL', 'dilijanvillas'); ?></label></th>
        <td><input id="dv_booking_payment_url" type="url" class="large-text" name="dv_booking_payment_url" value="<?php echo esc_attr($payment_url); ?>" /></td>
      </tr>
    </table>
    <?php
}

/**
 * Render dv_unavailable details metabox.
 *
 * @param WP_Post $post Current post.
 */
function dilijanvillas_render_unavailable_details_metabox($post)
{
    wp_nonce_field('dilijanvillas_save_unavailable_meta', 'dilijanvillas_unavailable_meta_nonce');

    $legacy_accommodation_id = (int) get_post_meta($post->ID, '_dv_accommodation_id', true);
    $accommodation_ids = get_post_meta($post->ID, '_dv_accommodation_ids', false);
    if (!is_array($accommodation_ids)) {
        $accommodation_ids = array();
    }
    $accommodation_ids = array_values(array_unique(array_map('intval', $accommodation_ids)));
    if (empty($accommodation_ids) && $legacy_accommodation_id > 0) {
        $accommodation_ids = array($legacy_accommodation_id);
    }
    $all_accommodations_flag = !empty($accommodation_ids) ? in_array(0, $accommodation_ids, true) : ($legacy_accommodation_id === 0 && get_post_meta($post->ID, '_dv_accommodation_id', true) !== '');

    $apply_all_languages = get_post_meta($post->ID, '_dv_apply_all_languages', true);
    $is_new_post = ($post->post_status === 'auto-draft');
    if ($apply_all_languages === '' && $is_new_post) {
        $apply_all_languages = '1';
    }

    $status = (string) get_post_meta($post->ID, '_dv_unavailable_status', true);
    $start = (string) get_post_meta($post->ID, '_dv_unavailable_start', true);
    $end = (string) get_post_meta($post->ID, '_dv_unavailable_end', true);
    $note = (string) get_post_meta($post->ID, '_dv_unavailable_note', true);

    if ($status === '') {
        $status = 'active';
    }

    $accommodations = dilijanvillas_get_all_accommodations_for_admin();

    $groups = array();
    foreach ($accommodations as $accommodation) {
        $lang_slug = isset($accommodation['lang']) ? (string) $accommodation['lang'] : '';
        $groups[$lang_slug][] = $accommodation;
    }
    ksort($groups);
    $selected_lookup = array_flip(array_map('intval', $accommodation_ids));
    ?>
    <table class="form-table" role="presentation">
      <tr>
        <th scope="row"><label for="dv_unavailable_accommodations"><?php esc_html_e('Accommodations', 'dilijanvillas'); ?></label></th>
        <td>
          <label style="display:block;margin-bottom:8px;">
            <input type="checkbox" name="dv_unavailable_all_accommodations" value="1" <?php checked($all_accommodations_flag, true); ?> />
            <?php esc_html_e('Block for ALL accommodations (overrides selection below)', 'dilijanvillas'); ?>
          </label>
          <select id="dv_unavailable_accommodations" name="dv_unavailable_accommodations[]" multiple size="<?php echo esc_attr(max(6, min(14, count($accommodations) + count($groups)))); ?>" style="min-width:320px;max-width:100%;">
            <?php foreach ($groups as $lang_slug => $items) : ?>
              <?php
                $label = $lang_slug !== '' ? strtoupper($lang_slug) : __('Default', 'dilijanvillas');
              ?>
              <optgroup label="<?php echo esc_attr($label); ?>">
                <?php foreach ($items as $accommodation) : ?>
                  <?php
                    $option_id = isset($accommodation['id']) ? (int) $accommodation['id'] : 0;
                    $title = isset($accommodation['title']) ? (string) $accommodation['title'] : ('#' . $option_id);
                    $template = isset($accommodation['template']) ? (string) $accommodation['template'] : '';
                    $type_hint = $template === 'private-willa.php' ? __('Villa', 'dilijanvillas') : __('Cottage', 'dilijanvillas');
                    $is_selected = isset($selected_lookup[$option_id]);
                  ?>
                  <option value="<?php echo esc_attr((string) $option_id); ?>" <?php selected($is_selected, true); ?>>
                    <?php echo esc_html($title . ' — ' . $type_hint); ?>
                  </option>
                <?php endforeach; ?>
              </optgroup>
            <?php endforeach; ?>
          </select>
          <p class="description"><?php esc_html_e('Hold Ctrl / Cmd to select multiple pages. Choose translations directly or use the toggle below.', 'dilijanvillas'); ?></p>
          <label style="display:block;margin-top:8px;">
            <input type="checkbox" name="dv_unavailable_apply_all_languages" value="1" <?php checked($apply_all_languages, '1'); ?> />
            <?php esc_html_e('Also apply to all language versions of the selected pages', 'dilijanvillas'); ?>
          </label>
        </td>
      </tr>
      <tr>
        <th scope="row"><label for="dv_unavailable_status"><?php esc_html_e('Status', 'dilijanvillas'); ?></label></th>
        <td>
          <select id="dv_unavailable_status" name="dv_unavailable_status">
            <option value="active" <?php selected($status, 'active'); ?>><?php esc_html_e('Active (blocked)', 'dilijanvillas'); ?></option>
            <option value="inactive" <?php selected($status, 'inactive'); ?>><?php esc_html_e('Inactive', 'dilijanvillas'); ?></option>
          </select>
        </td>
      </tr>
      <tr>
        <th scope="row"><label for="dv_unavailable_start"><?php esc_html_e('Start date', 'dilijanvillas'); ?></label></th>
        <td><input id="dv_unavailable_start" type="date" name="dv_unavailable_start" value="<?php echo esc_attr($start); ?>" /></td>
      </tr>
      <tr>
        <th scope="row"><label for="dv_unavailable_end"><?php esc_html_e('End date', 'dilijanvillas'); ?></label></th>
        <td><input id="dv_unavailable_end" type="date" name="dv_unavailable_end" value="<?php echo esc_attr($end); ?>" /></td>
      </tr>
      <tr>
        <th scope="row"><label for="dv_unavailable_note"><?php esc_html_e('Internal note', 'dilijanvillas'); ?></label></th>
        <td><textarea id="dv_unavailable_note" name="dv_unavailable_note" rows="3" class="large-text"><?php echo esc_textarea($note); ?></textarea></td>
      </tr>
    </table>
    <?php
}

/**
 * Save dv_booking metabox values.
 *
 * @param int $post_id Post ID.
 */
function dilijanvillas_save_booking_metabox($post_id)
{
    if (!isset($_POST['dilijanvillas_booking_meta_nonce'])) {
        return;
    }
    if (!wp_verify_nonce((string) $_POST['dilijanvillas_booking_meta_nonce'], 'dilijanvillas_save_booking_meta')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    if (get_post_type($post_id) !== 'dv_booking') {
        return;
    }

    $status_labels = dilijanvillas_get_booking_status_labels();
    $status = isset($_POST['dv_booking_status']) ? sanitize_text_field((string) $_POST['dv_booking_status']) : 'pending';
    if (!isset($status_labels[$status])) {
        $status = 'pending';
    }

    $accommodation_id = isset($_POST['dv_booking_accommodation']) ? (int) $_POST['dv_booking_accommodation'] : 0;
    $checkin = isset($_POST['dv_booking_checkin']) ? sanitize_text_field((string) $_POST['dv_booking_checkin']) : '';
    $checkout = isset($_POST['dv_booking_checkout']) ? sanitize_text_field((string) $_POST['dv_booking_checkout']) : '';
    $guests = isset($_POST['dv_booking_guests']) ? max(1, (int) $_POST['dv_booking_guests']) : 1;
    $has_children = isset($_POST['dv_booking_has_children']) && $_POST['dv_booking_has_children'] === 'yes' ? 'yes' : 'no';
    $children_count = isset($_POST['dv_booking_children_count']) ? max(0, (int) $_POST['dv_booking_children_count']) : 0;
    $customer_name = isset($_POST['dv_booking_customer_name']) ? sanitize_text_field((string) $_POST['dv_booking_customer_name']) : '';
    $customer_phone = isset($_POST['dv_booking_customer_phone']) ? sanitize_text_field((string) $_POST['dv_booking_customer_phone']) : '';
    $customer_email = isset($_POST['dv_booking_customer_email']) ? sanitize_email((string) $_POST['dv_booking_customer_email']) : '';
    $payment_url = isset($_POST['dv_booking_payment_url']) ? esc_url_raw((string) $_POST['dv_booking_payment_url']) : '';
    $manual_total = isset($_POST['dv_booking_total']) ? max(0, (float) $_POST['dv_booking_total']) : 0;
    $manual_currency = isset($_POST['dv_booking_currency']) ? sanitize_text_field((string) $_POST['dv_booking_currency']) : '';
    $manual_nights = isset($_POST['dv_booking_nights']) ? max(0, (int) $_POST['dv_booking_nights']) : 0;

    update_post_meta($post_id, '_dv_status', $status);
    update_post_meta($post_id, '_dv_accommodation_id', $accommodation_id);
    update_post_meta($post_id, '_dv_guests', $guests);
    update_post_meta($post_id, '_dv_has_children', $has_children);
    update_post_meta($post_id, '_dv_children_count', $children_count);
    update_post_meta($post_id, '_dv_customer_name', $customer_name);
    update_post_meta($post_id, '_dv_customer_phone', $customer_phone);
    update_post_meta($post_id, '_dv_customer_email', $customer_email);
    update_post_meta($post_id, '_dv_payment_url', $payment_url);

    if (dilijanvillas_is_valid_booking_date($checkin)) {
        update_post_meta($post_id, '_dv_checkin', $checkin);
    }
    if (dilijanvillas_is_valid_booking_date($checkout)) {
        update_post_meta($post_id, '_dv_checkout', $checkout);
    }

    if (dilijanvillas_is_valid_booking_date($checkin) && dilijanvillas_is_valid_booking_date($checkout) && $checkout >= $checkin) {
        $price = dilijanvillas_calculate_booking_price($checkin, $checkout, (int) $accommodation_id);
        $total = $manual_total > 0 ? $manual_total : (float) $price['total'];
        $nights = $manual_nights > 0 ? $manual_nights : (int) $price['nights'];
        $currency = $manual_currency !== '' ? $manual_currency : (string) $price['currency'];
        update_post_meta($post_id, '_dv_total', $total);
        update_post_meta($post_id, '_dv_nights', $nights);
        update_post_meta($post_id, '_dv_currency', $currency);
    } else {
        update_post_meta($post_id, '_dv_total', $manual_total);
        update_post_meta($post_id, '_dv_nights', $manual_nights);
        update_post_meta($post_id, '_dv_currency', $manual_currency !== '' ? $manual_currency : 'AMD');
    }

    $post = get_post($post_id);
    if ($post && (trim((string) $post->post_title) === '' || strtolower(trim((string) $post->post_title)) === 'auto draft')) {
        $title_name = $customer_name !== '' ? $customer_name : __('Guest', 'dilijanvillas');
        $title_accommodation = $accommodation_id > 0 ? (string) get_the_title((int) $accommodation_id) : '';
        if ($title_accommodation === '') {
            $title_accommodation = __('Accommodation', 'dilijanvillas');
        }
        if ($checkin !== '' && $checkout !== '') {
            $title = sprintf(__('Booking %1$s – %2$s - %3$s - %4$s', 'dilijanvillas'), $checkin, $checkout, $title_name, $title_accommodation);
        } elseif ($checkin !== '') {
            $title = sprintf(__('Booking %1$s - %2$s - %3$s', 'dilijanvillas'), $checkin, $title_name, $title_accommodation);
        } else {
            $title = sprintf(__('Booking - %1$s - %2$s', 'dilijanvillas'), $title_name, $title_accommodation);
        }
        remove_action('save_post_dv_booking', 'dilijanvillas_save_booking_metabox');
        wp_update_post(
            array(
                'ID' => $post_id,
                'post_title' => $title,
            )
        );
        add_action('save_post_dv_booking', 'dilijanvillas_save_booking_metabox');
    }
}
add_action('save_post_dv_booking', 'dilijanvillas_save_booking_metabox');

/**
 * Save dv_unavailable metabox values.
 *
 * @param int $post_id Post ID.
 */
function dilijanvillas_save_unavailable_metabox($post_id)
{
    if (!isset($_POST['dilijanvillas_unavailable_meta_nonce'])) {
        return;
    }
    if (!wp_verify_nonce((string) $_POST['dilijanvillas_unavailable_meta_nonce'], 'dilijanvillas_save_unavailable_meta')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    if (get_post_type($post_id) !== 'dv_unavailable') {
        return;
    }

    $all_accommodations = isset($_POST['dv_unavailable_all_accommodations']) && (string) $_POST['dv_unavailable_all_accommodations'] === '1';
    $apply_all_languages = isset($_POST['dv_unavailable_apply_all_languages']) && (string) $_POST['dv_unavailable_apply_all_languages'] === '1';
    $raw_ids = isset($_POST['dv_unavailable_accommodations']) ? (array) $_POST['dv_unavailable_accommodations'] : array();
    $selected_ids = array();
    foreach ($raw_ids as $raw_id) {
        $clean_id = (int) $raw_id;
        if ($clean_id > 0) {
            $selected_ids[$clean_id] = true;
        }
    }
    $selected_ids = array_keys($selected_ids);

    if (!$all_accommodations && $apply_all_languages && !empty($selected_ids)) {
        $selected_ids = dilijanvillas_expand_pages_to_all_languages($selected_ids);
    }

    $status = isset($_POST['dv_unavailable_status']) && $_POST['dv_unavailable_status'] === 'inactive' ? 'inactive' : 'active';
    $start = isset($_POST['dv_unavailable_start']) ? sanitize_text_field((string) $_POST['dv_unavailable_start']) : '';
    $end = isset($_POST['dv_unavailable_end']) ? sanitize_text_field((string) $_POST['dv_unavailable_end']) : '';
    $note = isset($_POST['dv_unavailable_note']) ? sanitize_textarea_field((string) $_POST['dv_unavailable_note']) : '';

    delete_post_meta($post_id, '_dv_accommodation_ids');
    if ($all_accommodations) {
        update_post_meta($post_id, '_dv_accommodation_id', 0);
        add_post_meta($post_id, '_dv_accommodation_ids', 0, false);
    } else {
        $primary_id = !empty($selected_ids) ? (int) $selected_ids[0] : 0;
        update_post_meta($post_id, '_dv_accommodation_id', $primary_id);
        foreach ($selected_ids as $sid) {
            add_post_meta($post_id, '_dv_accommodation_ids', (int) $sid, false);
        }
    }

    update_post_meta($post_id, '_dv_apply_all_languages', $apply_all_languages ? '1' : '0');
    update_post_meta($post_id, '_dv_unavailable_status', $status);
    update_post_meta($post_id, '_dv_unavailable_note', $note);

    if (dilijanvillas_is_valid_booking_date($start)) {
        update_post_meta($post_id, '_dv_unavailable_start', $start);
    }
    if (dilijanvillas_is_valid_booking_date($end)) {
        update_post_meta($post_id, '_dv_unavailable_end', $end);
    }

    $post = get_post($post_id);
    if ($post && (trim((string) $post->post_title) === '' || strtolower(trim((string) $post->post_title)) === 'auto draft')) {
        if ($all_accommodations) {
            $title_scope = __('All accommodations', 'dilijanvillas');
        } elseif (count($selected_ids) === 1) {
            $title_scope = get_the_title((int) $selected_ids[0]);
        } elseif (count($selected_ids) > 1) {
            $names = array();
            foreach (array_slice($selected_ids, 0, 3) as $sid) {
                $names[] = get_the_title((int) $sid);
            }
            $title_scope = implode(' + ', array_filter($names));
            if (count($selected_ids) > 3) {
                $title_scope .= ' ' . sprintf(__('(+%d more)', 'dilijanvillas'), count($selected_ids) - 3);
            }
        } else {
            $title_scope = __('No accommodation', 'dilijanvillas');
        }
        $title = sprintf(__('Blocked: %1$s to %2$s (%3$s)', 'dilijanvillas'), $start !== '' ? $start : '—', $end !== '' ? $end : '—', $title_scope);
        remove_action('save_post_dv_unavailable', 'dilijanvillas_save_unavailable_metabox');
        wp_update_post(
            array(
                'ID' => $post_id,
                'post_title' => $title,
            )
        );
        add_action('save_post_dv_unavailable', 'dilijanvillas_save_unavailable_metabox');
    }
}
add_action('save_post_dv_unavailable', 'dilijanvillas_save_unavailable_metabox');

/**
 * Render dv_price_period details metabox.
 *
 * @param WP_Post $post Current post.
 */
function dilijanvillas_render_price_period_metabox($post)
{
    wp_nonce_field('dilijanvillas_save_price_period_meta', 'dilijanvillas_price_period_meta_nonce');

    $legacy_accommodation_id = (int) get_post_meta($post->ID, '_dv_accommodation_id', true);
    $accommodation_ids = get_post_meta($post->ID, '_dv_accommodation_ids', false);
    if (!is_array($accommodation_ids)) {
        $accommodation_ids = array();
    }
    $accommodation_ids = array_values(array_unique(array_map('intval', $accommodation_ids)));
    if (empty($accommodation_ids) && $legacy_accommodation_id > 0) {
        $accommodation_ids = array($legacy_accommodation_id);
    }
    $all_accommodations_flag = !empty($accommodation_ids) ? in_array(0, $accommodation_ids, true) : ($legacy_accommodation_id === 0 && get_post_meta($post->ID, '_dv_accommodation_id', true) !== '');

    $apply_all_languages = get_post_meta($post->ID, '_dv_apply_all_languages', true);
    $is_new_post = ($post->post_status === 'auto-draft');
    if ($apply_all_languages === '' && $is_new_post) {
        $apply_all_languages = '1';
    }

    $status = (string) get_post_meta($post->ID, '_dv_price_status', true);
    if ($status === '') {
        $status = 'active';
    }
    $start = (string) get_post_meta($post->ID, '_dv_price_start', true);
    $end = (string) get_post_meta($post->ID, '_dv_price_end', true);
    $rate = (string) get_post_meta($post->ID, '_dv_price_rate', true);
    $weekend_min_nights = (string) get_post_meta($post->ID, '_dv_price_weekend_min_nights', true);
    $note = (string) get_post_meta($post->ID, '_dv_price_note', true);

    $settings = dilijanvillas_get_booking_settings();
    $currency = (string) $settings['currency'];

    $accommodations = dilijanvillas_get_all_accommodations_for_admin();

    $groups = array();
    foreach ($accommodations as $accommodation) {
        $lang_slug = isset($accommodation['lang']) ? (string) $accommodation['lang'] : '';
        $groups[$lang_slug][] = $accommodation;
    }
    ksort($groups);
    $selected_lookup = array_flip(array_map('intval', $accommodation_ids));
    ?>
    <table class="form-table" role="presentation">
      <tr>
        <th scope="row"><label for="dv_price_period_accommodations"><?php esc_html_e('Accommodations', 'dilijanvillas'); ?></label></th>
        <td>
          <label style="display:block;margin-bottom:8px;">
            <input type="checkbox" name="dv_price_period_all_accommodations" value="1" <?php checked($all_accommodations_flag, true); ?> />
            <?php esc_html_e('Apply price to ALL accommodations (overrides selection below)', 'dilijanvillas'); ?>
          </label>
          <select id="dv_price_period_accommodations" name="dv_price_period_accommodations[]" multiple size="<?php echo esc_attr(max(6, min(14, count($accommodations) + count($groups)))); ?>" style="min-width:320px;max-width:100%;">
            <?php foreach ($groups as $lang_slug => $items) : ?>
              <?php $label = $lang_slug !== '' ? strtoupper($lang_slug) : __('Default', 'dilijanvillas'); ?>
              <optgroup label="<?php echo esc_attr($label); ?>">
                <?php foreach ($items as $accommodation) : ?>
                  <?php
                    $option_id = isset($accommodation['id']) ? (int) $accommodation['id'] : 0;
                    $title = isset($accommodation['title']) ? (string) $accommodation['title'] : ('#' . $option_id);
                    $template = isset($accommodation['template']) ? (string) $accommodation['template'] : '';
                    $type_hint = $template === 'private-willa.php' ? __('Villa', 'dilijanvillas') : __('Cottage', 'dilijanvillas');
                    $is_selected = isset($selected_lookup[$option_id]);
                  ?>
                  <option value="<?php echo esc_attr((string) $option_id); ?>" <?php selected($is_selected, true); ?>>
                    <?php echo esc_html($title . ' — ' . $type_hint); ?>
                  </option>
                <?php endforeach; ?>
              </optgroup>
            <?php endforeach; ?>
          </select>
          <p class="description"><?php esc_html_e('Hold Ctrl / Cmd to select multiple pages. Choose translations directly or use the toggle below.', 'dilijanvillas'); ?></p>
          <label style="display:block;margin-top:8px;">
            <input type="checkbox" name="dv_price_period_apply_all_languages" value="1" <?php checked($apply_all_languages, '1'); ?> />
            <?php esc_html_e('Also apply to all language versions of the selected pages', 'dilijanvillas'); ?>
          </label>
        </td>
      </tr>
      <tr>
        <th scope="row"><label for="dv_price_period_status"><?php esc_html_e('Status', 'dilijanvillas'); ?></label></th>
        <td>
          <select id="dv_price_period_status" name="dv_price_period_status">
            <option value="active" <?php selected($status, 'active'); ?>><?php esc_html_e('Active', 'dilijanvillas'); ?></option>
            <option value="inactive" <?php selected($status, 'inactive'); ?>><?php esc_html_e('Inactive', 'dilijanvillas'); ?></option>
          </select>
        </td>
      </tr>
      <tr>
        <th scope="row"><label for="dv_price_period_start"><?php esc_html_e('Start date', 'dilijanvillas'); ?></label></th>
        <td><input id="dv_price_period_start" type="date" name="dv_price_period_start" value="<?php echo esc_attr($start); ?>" /></td>
      </tr>
      <tr>
        <th scope="row"><label for="dv_price_period_end"><?php esc_html_e('End date', 'dilijanvillas'); ?></label></th>
        <td>
          <input id="dv_price_period_end" type="date" name="dv_price_period_end" value="<?php echo esc_attr($end); ?>" />
          <p class="description"><?php esc_html_e('Both start and end dates are inclusive. A booking is priced only when its check-in AND check-out dates both fall inside this range.', 'dilijanvillas'); ?></p>
        </td>
      </tr>
      <tr>
        <th scope="row"><label for="dv_price_period_rate"><?php esc_html_e('Nightly rate', 'dilijanvillas'); ?></label></th>
        <td>
          <input id="dv_price_period_rate" type="number" min="0" step="0.01" name="dv_price_period_rate" value="<?php echo esc_attr($rate); ?>" class="small-text" />
          <code style="margin-left:6px;"><?php echo esc_html($currency); ?></code>
          <p class="description"><?php esc_html_e('Charged as entered for every night inside the period.', 'dilijanvillas'); ?></p>
        </td>
      </tr>
      <tr>
        <th scope="row"><label for="dv_price_period_weekend_min_nights"><?php esc_html_e('Weekend multiplier', 'dilijanvillas'); ?></label></th>
        <td>
          <input id="dv_price_period_weekend_min_nights" type="number" min="0" step="1" name="dv_price_period_weekend_min_nights" value="<?php echo esc_attr($weekend_min_nights); ?>" class="small-text" />
          <?php esc_html_e('nights minimum', 'dilijanvillas'); ?>
          <p class="description">
            <?php esc_html_e('Minimum length of stay when the dates include a Friday, Saturday or Sunday inside this period. Example: set 2, and a guest picking Saturday cannot book fewer than 2 nights. Leave 0 or empty for no restriction.', 'dilijanvillas'); ?>
          </p>
        </td>
      </tr>
      <tr>
        <th scope="row"><label for="dv_price_period_note"><?php esc_html_e('Internal note', 'dilijanvillas'); ?></label></th>
        <td><textarea id="dv_price_period_note" name="dv_price_period_note" rows="3" class="large-text"><?php echo esc_textarea($note); ?></textarea></td>
      </tr>
    </table>
    <?php
}

/**
 * Save dv_price_period metabox values.
 *
 * @param int $post_id Post ID.
 */
function dilijanvillas_save_price_period_metabox($post_id)
{
    if (!isset($_POST['dilijanvillas_price_period_meta_nonce'])) {
        return;
    }
    if (!wp_verify_nonce((string) $_POST['dilijanvillas_price_period_meta_nonce'], 'dilijanvillas_save_price_period_meta')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    if (get_post_type($post_id) !== 'dv_price_period') {
        return;
    }

    $all_accommodations = isset($_POST['dv_price_period_all_accommodations']) && (string) $_POST['dv_price_period_all_accommodations'] === '1';
    $apply_all_languages = isset($_POST['dv_price_period_apply_all_languages']) && (string) $_POST['dv_price_period_apply_all_languages'] === '1';
    $raw_ids = isset($_POST['dv_price_period_accommodations']) ? (array) $_POST['dv_price_period_accommodations'] : array();
    $selected_ids = array();
    foreach ($raw_ids as $raw_id) {
        $clean_id = (int) $raw_id;
        if ($clean_id > 0) {
            $selected_ids[$clean_id] = true;
        }
    }
    $selected_ids = array_keys($selected_ids);
    if (!$all_accommodations && $apply_all_languages && !empty($selected_ids)) {
        $selected_ids = dilijanvillas_expand_pages_to_all_languages($selected_ids);
    }

    $status = isset($_POST['dv_price_period_status']) && $_POST['dv_price_period_status'] === 'inactive' ? 'inactive' : 'active';
    $start = isset($_POST['dv_price_period_start']) ? sanitize_text_field((string) $_POST['dv_price_period_start']) : '';
    $end = isset($_POST['dv_price_period_end']) ? sanitize_text_field((string) $_POST['dv_price_period_end']) : '';
    $rate = isset($_POST['dv_price_period_rate']) ? max(0, (float) $_POST['dv_price_period_rate']) : 0;
    $weekend_min_nights = isset($_POST['dv_price_period_weekend_min_nights']) ? max(0, (int) $_POST['dv_price_period_weekend_min_nights']) : 0;
    $note = isset($_POST['dv_price_period_note']) ? sanitize_textarea_field((string) $_POST['dv_price_period_note']) : '';

    delete_post_meta($post_id, '_dv_accommodation_ids');
    if ($all_accommodations) {
        update_post_meta($post_id, '_dv_accommodation_id', 0);
        add_post_meta($post_id, '_dv_accommodation_ids', 0, false);
    } else {
        $primary_id = !empty($selected_ids) ? (int) $selected_ids[0] : 0;
        update_post_meta($post_id, '_dv_accommodation_id', $primary_id);
        foreach ($selected_ids as $sid) {
            add_post_meta($post_id, '_dv_accommodation_ids', (int) $sid, false);
        }
    }

    update_post_meta($post_id, '_dv_apply_all_languages', $apply_all_languages ? '1' : '0');
    update_post_meta($post_id, '_dv_price_status', $status);
    update_post_meta($post_id, '_dv_price_rate', $rate);
    update_post_meta($post_id, '_dv_price_weekend_min_nights', $weekend_min_nights);
    update_post_meta($post_id, '_dv_price_note', $note);

    if (dilijanvillas_is_valid_booking_date($start)) {
        update_post_meta($post_id, '_dv_price_start', $start);
    }
    if (dilijanvillas_is_valid_booking_date($end)) {
        update_post_meta($post_id, '_dv_price_end', $end);
    }

    $post = get_post($post_id);
    if ($post && (trim((string) $post->post_title) === '' || strtolower(trim((string) $post->post_title)) === 'auto draft')) {
        if ($all_accommodations) {
            $title_scope = __('All accommodations', 'dilijanvillas');
        } elseif (count($selected_ids) === 1) {
            $title_scope = get_the_title((int) $selected_ids[0]);
        } elseif (count($selected_ids) > 1) {
            $names = array();
            foreach (array_slice($selected_ids, 0, 3) as $sid) {
                $names[] = get_the_title((int) $sid);
            }
            $title_scope = implode(' + ', array_filter($names));
            if (count($selected_ids) > 3) {
                $title_scope .= ' ' . sprintf(__('(+%d more)', 'dilijanvillas'), count($selected_ids) - 3);
            }
        } else {
            $title_scope = __('No accommodation', 'dilijanvillas');
        }
        $settings = dilijanvillas_get_booking_settings();
        $title = sprintf(
            __('Price %1$s %2$s: %3$s to %4$s (%5$s)', 'dilijanvillas'),
            number_format((float) $rate, 2),
            (string) $settings['currency'],
            $start !== '' ? $start : '—',
            $end !== '' ? $end : '—',
            $title_scope
        );
        remove_action('save_post_dv_price_period', 'dilijanvillas_save_price_period_metabox');
        wp_update_post(
            array(
                'ID' => $post_id,
                'post_title' => $title,
            )
        );
        add_action('save_post_dv_price_period', 'dilijanvillas_save_price_period_metabox');
    }
}
add_action('save_post_dv_price_period', 'dilijanvillas_save_price_period_metabox');

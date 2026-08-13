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
        'paylink_integration_mode' => 'armenia_integration',
        'paylink_test_mode' => false,
        'paylink_api_url' => '',
        'paylink_api_token' => '',
        'paylink_merchant_id' => '',
        'paylink_callback_url' => '',
        'paylink_am_api_base' => 'https://integration.apitest.paylink.am',
        'paylink_am_request_type' => 'Booking',
        'payment_hold_minutes' => 30,
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
 * Dates are only taken off the calendar once the money is in: "paid" is set by
 * the PayLink sync, "confirmed" is the admin vouching for a booking settled some
 * other way (transfer, cash). A guest who opens the payment page and walks away
 * leaves the booking on "payment_pending", and those nights stay on sale.
 *
 * @return array<int,string>
 */
function dilijanvillas_get_blocking_booking_statuses()
{
    return array('confirmed', 'paid');
}

/**
 * How long a booking holds its dates while the guest is on the payment page.
 *
 * Long enough to type card details and pass 3-D Secure, short enough that an
 * abandoned checkout does not keep the nights off sale for the rest of the day.
 * Set on the Booking Settings screen.
 *
 * @return int Minutes; zero or less disables the hold entirely.
 */
function dilijanvillas_get_payment_hold_minutes()
{
    $settings = dilijanvillas_get_booking_settings();
    $minutes = isset($settings['payment_hold_minutes']) ? (int) $settings['payment_hold_minutes'] : 30;

    return (int) apply_filters('dilijanvillas_payment_hold_minutes', $minutes);
}

/**
 * Head start the payment link gets over the date hold.
 *
 * The link has to be refused before the nights are back on sale, never after:
 * a payment authorised in that gap would land on dates another guest may already
 * have bought. Clock drift between this server and PayLink, plus the seconds a
 * charge takes to go through, are what this margin absorbs.
 *
 * @return int Minutes subtracted from the hold when setting expirationDate.
 */
function dilijanvillas_get_payment_link_margin_minutes()
{
    return max(0, (int) apply_filters('dilijanvillas_payment_link_margin_minutes', 2));
}

/**
 * How long the PayLink page itself stays payable.
 *
 * @return int Minutes; zero means no deadline is sent to PayLink.
 */
function dilijanvillas_get_payment_link_minutes()
{
    $hold = dilijanvillas_get_payment_hold_minutes();
    if ($hold <= 0) {
        return 0;
    }

    return max(1, $hold - dilijanvillas_get_payment_link_margin_minutes());
}

/**
 * Is this booking still inside its payment window?
 *
 * Only meaningful for "payment_pending" — the state a booking is in between
 * being handed a PayLink URL and the payment being confirmed.
 *
 * @param int $booking_id Booking post ID.
 * @return bool True while the dates are held for this guest.
 */
function dilijanvillas_booking_payment_hold_is_active($booking_id)
{
    $minutes = dilijanvillas_get_payment_hold_minutes();
    if ($minutes <= 0) {
        return false;
    }

    $started = trim((string) get_post_meta((int) $booking_id, '_dv_payment_hold_started_at', true));
    $started_ts = $started !== '' ? strtotime($started) : false;

    // Bookings made before the hold existed fall back to their creation time,
    // which for those is the same moment the payment link was issued.
    if (!$started_ts) {
        $started_ts = (int) get_post_time('U', true, (int) $booking_id);
    }

    if ($started_ts <= 0) {
        return false;
    }

    return (time() - $started_ts) < ($minutes * MINUTE_IN_SECONDS);
}

/**
 * Cancel bookings whose payment window has closed.
 *
 * A guest handed a PayLink URL sits on "payment_pending" until they pay. The
 * dates come back on sale on their own once the hold lapses (see
 * dilijanvillas_booking_payment_hold_is_active), but the booking itself used to
 * stay "payment_pending" indefinitely, which reads in the admin list as a live
 * checkout that never resolves. Once the window is shut the checkout is dead:
 * mark it "cancelled" so the Bookings list tells the truth and the Status filter
 * counts it correctly.
 *
 * One last reconcile guards the one case where it would be wrong to cancel: the
 * guest paid but never came back to the site to fire the backUrl sync. If the
 * money is in, the booking becomes "paid" here instead of being cancelled.
 *
 * @return int Number of bookings cancelled.
 */
function dilijanvillas_cancel_expired_payment_holds()
{
    // With the hold disabled nothing is ever "expired" — leave statuses alone.
    if (dilijanvillas_get_payment_hold_minutes() <= 0) {
        return 0;
    }

    $booking_ids = get_posts(
        array(
            'post_type' => 'dv_booking',
            'post_status' => 'publish',
            'posts_per_page' => 50,
            'fields' => 'ids',
            'no_found_rows' => true,
            'meta_query' => array(
                array('key' => '_dv_status', 'value' => 'payment_pending'),
            ),
        )
    );

    $cancelled = 0;
    foreach ($booking_ids as $booking_id) {
        $booking_id = (int) $booking_id;

        // Still inside the window — the guest may be finishing payment now.
        if (dilijanvillas_booking_payment_hold_is_active($booking_id)) {
            continue;
        }

        // Close the PayLink link FIRST and proceed only once PayLink confirms it
        // is inactive. expirationDate alone does not stop the page taking money,
        // so the request is deactivated outright. If that cannot be confirmed the
        // booking is left on payment_pending — its dates stay blocked and the next
        // sweep retries — because the nights must never reopen while the link could
        // still be paid. (No registered link counts as already closed.)
        if (!dilijanvillas_paylink_am_deactivate_request($booking_id)) {
            continue;
        }

        // The link is now closed. Last chance to notice a payment that landed
        // just before it closed (or without the guest returning to the site): if
        // it settled, the booking is "paid" now, not expired.
        if (dilijanvillas_paylink_am_sync_booking_status($booking_id, '')) {
            continue;
        }

        // The sync may have flipped the status on another path; re-read before
        // overwriting so a booking settled mid-sweep is never clobbered.
        if ((string) get_post_meta($booking_id, '_dv_status', true) !== 'payment_pending') {
            continue;
        }

        // Link confirmed dead, still unpaid — only now free the dates.
        update_post_meta($booking_id, '_dv_status', 'cancelled');
        update_post_meta($booking_id, '_dv_cancelled_at', gmdate('c'));
        update_post_meta($booking_id, '_dv_cancel_reason', 'payment_hold_expired');
        $cancelled++;
    }

    return $cancelled;
}
add_action('dilijanvillas_expire_payment_holds', 'dilijanvillas_cancel_expired_payment_holds');

/**
 * Run the expired-hold sweep on ordinary traffic, not only on WP-Cron.
 *
 * WP-Cron fires only when the site is hit, and a quiet site — or one with
 * DISABLE_WP_CRON set — can leave the five-minute event unfired for hours. The
 * dates free themselves regardless (availability is recomputed live), but the
 * booking stays stuck on "payment_pending" until the sweep actually runs, so the
 * Bookings list lies. Piggy-backing on normal requests keeps the status honest;
 * a one-minute transient lock means only the first hit in each minute pays the
 * cost, and the sweep is a cheap query whenever nothing has actually expired.
 *
 * @return void
 */
function dilijanvillas_maybe_sweep_payment_holds()
{
    if (dilijanvillas_get_payment_hold_minutes() <= 0) {
        return;
    }
    if (get_transient('dilijanvillas_hold_sweep_lock')) {
        return;
    }
    set_transient('dilijanvillas_hold_sweep_lock', 1, MINUTE_IN_SECONDS);

    dilijanvillas_cancel_expired_payment_holds();
}
// Admin page loads (so the Bookings list is correct the moment it is opened) and
// front-end views (so statuses catch up even with no admin around) both nudge it.
add_action('admin_init', 'dilijanvillas_maybe_sweep_payment_holds');
add_action('template_redirect', 'dilijanvillas_maybe_sweep_payment_holds');

/**
 * Add the recurring interval used to sweep expired checkouts.
 *
 * WordPress ships nothing more frequent than hourly, but the hold is counted in
 * minutes; a stalled booking would otherwise linger up to an hour before its
 * status caught up. Five minutes keeps the list current without waking WP-Cron
 * every minute.
 *
 * @param array<string,array<string,mixed>> $schedules Existing cron intervals.
 * @return array<string,array<string,mixed>>
 */
function dilijanvillas_register_cron_schedules($schedules)
{
    if (!isset($schedules['dilijanvillas_five_minutes'])) {
        $schedules['dilijanvillas_five_minutes'] = array(
            'interval' => 5 * MINUTE_IN_SECONDS,
            'display' => __('Every 5 minutes (Dilijan Villas)', 'dilijanvillas'),
        );
    }

    return $schedules;
}
add_filter('cron_schedules', 'dilijanvillas_register_cron_schedules');

/**
 * Keep the payment-hold sweep on the cron calendar.
 */
function dilijanvillas_schedule_payment_hold_sweep()
{
    if (!wp_next_scheduled('dilijanvillas_expire_payment_holds')) {
        wp_schedule_event(time(), 'dilijanvillas_five_minutes', 'dilijanvillas_expire_payment_holds');
    }
}
add_action('init', 'dilijanvillas_schedule_payment_hold_sweep');

/**
 * Drop the sweep from cron when this theme is deactivated.
 */
function dilijanvillas_unschedule_payment_hold_sweep()
{
    $timestamp = wp_next_scheduled('dilijanvillas_expire_payment_holds');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'dilijanvillas_expire_payment_holds');
    }
}
add_action('switch_theme', 'dilijanvillas_unschedule_payment_hold_sweep');

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

    // Below the safety margin the payment link would be dead on arrival, so the
    // shortest hold worth offering is a few minutes. Zero is kept as the explicit
    // "no hold, no deadline" escape hatch.
    $hold_minutes_raw = isset($input['payment_hold_minutes']) ? (int) $input['payment_hold_minutes'] : 30;
    if ($hold_minutes_raw < 0) {
        $hold_minutes_raw = 0;
    }
    if ($hold_minutes_raw > 0) {
        $hold_minutes_raw = max(
            dilijanvillas_get_payment_link_margin_minutes() + 1,
            min(1440, $hold_minutes_raw)
        );
    }

    return array(
        'currency' => !empty($input['currency']) ? sanitize_text_field((string) $input['currency']) : 'AMD',
        'paylink_integration_mode' => $hosted_mode,
        'paylink_test_mode' => !empty($input['paylink_test_mode']),
        'paylink_api_url' => !empty($input['paylink_api_url']) ? esc_url_raw((string) $input['paylink_api_url']) : '',
        'paylink_api_token' => $new_secret,
        'paylink_merchant_id' => !empty($input['paylink_merchant_id']) ? sanitize_text_field((string) $input['paylink_merchant_id']) : '',
        'paylink_callback_url' => !empty($input['paylink_callback_url']) ? esc_url_raw((string) $input['paylink_callback_url']) : '',
        'paylink_am_api_base' => $paylink_am_base,
        'paylink_am_request_type' => $paylink_am_request_type_final,
        'payment_hold_minutes' => $hold_minutes_raw,
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
            <th scope="row"><label for="payment_hold_minutes"><?php esc_html_e('Hold dates during payment', 'dilijanvillas'); ?></label></th>
            <td>
              <?php
              $hold_minutes_value = dilijanvillas_get_payment_hold_minutes();
              $link_minutes_value = dilijanvillas_get_payment_link_minutes();
              $margin_minutes_value = dilijanvillas_get_payment_link_margin_minutes();
              ?>
              <input id="payment_hold_minutes" type="number" min="0" max="1440" step="1" name="<?php echo esc_attr(DILIJANVILLAS_BOOKING_SETTINGS_KEY); ?>[payment_hold_minutes]" value="<?php echo esc_attr((string) $hold_minutes_value); ?>" class="small-text" />
              <span class="description"><?php esc_html_e('minutes', 'dilijanvillas'); ?></span>
              <p class="description">
                <?php esc_html_e('While a guest is on the payment page their dates disappear from everyone else\'s calendar. When this runs out the nights go back on sale by themselves.', 'dilijanvillas'); ?>
              </p>
              <p class="description">
                <?php
                if ($hold_minutes_value > 0) {
                    printf(
                        /* translators: 1: payment link lifetime, 2: safety margin, 3: hold length — all in minutes */
                        esc_html__('The PayLink page itself stops accepting payment after %1$d min — %2$d min before the hold ends, so a payment can never go through on nights that are already back on sale. Hold %3$d min → link %1$d min.', 'dilijanvillas'),
                        (int) $link_minutes_value,
                        (int) $margin_minutes_value,
                        (int) $hold_minutes_value
                    );
                } else {
                    esc_html_e('Set to 0: dates are never held and the payment link has no deadline. Two guests can then pay for the same nights.', 'dilijanvillas');
                }
                ?>
              </p>
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
        $is_hold = false;

        if (!in_array($status, $booking_statuses, true)) {
            // Not confirmed/paid — the only other state that holds nights is a
            // checkout in progress. It keeps them until the booking is actually
            // cancelled (which also closes the PayLink link), so the dates never
            // reopen while a live link could still take money for them. The hold
            // clock no longer frees the dates; it only tells the sweep when this
            // booking is due to be cancelled.
            if ($status !== 'payment_pending') {
                continue;
            }

            $is_hold = true;
        }

        $existing_start = (string) get_post_meta((int) $booking_id, '_dv_checkin', true);
        $existing_end = (string) get_post_meta((int) $booking_id, '_dv_checkout', true);
        if (!dilijanvillas_is_valid_booking_date($existing_start) || !dilijanvillas_is_valid_booking_date($existing_end)) {
            continue;
        }

        $blocked[] = array(
            'start' => $existing_start,
            'end' => $existing_end,
            'source' => $is_hold ? 'payment_hold' : 'booking',
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
 * Find an existing blocked range that a booking's dates would collide with.
 *
 * Same idea as dilijanvillas_check_booking_availability, but it ignores the
 * booking being saved so editing a live booking never conflicts with itself,
 * and it hands back the offending range so the caller can explain the clash.
 *
 * @param int    $accommodation_id    Accommodation page ID.
 * @param string $checkin             Check-in (Y-m-d).
 * @param string $checkout            Check-out (Y-m-d).
 * @param int    $exclude_booking_id  Booking to leave out of the comparison.
 * @return array<string,mixed>|null   The conflicting range, or null when free.
 */
function dilijanvillas_find_booking_date_conflict($accommodation_id, $checkin, $checkout, $exclude_booking_id = 0)
{
    $checkin = (string) $checkin;
    $checkout = (string) $checkout;
    if (!dilijanvillas_is_valid_booking_date($checkin) || !dilijanvillas_is_valid_booking_date($checkout)) {
        return null;
    }

    $exclude_booking_id = (int) $exclude_booking_id;
    foreach (dilijanvillas_get_blocked_ranges((int) $accommodation_id) as $range) {
        $source = isset($range['source']) ? (string) $range['source'] : '';
        $source_id = isset($range['sourceId']) ? (int) $range['sourceId'] : 0;

        // The booking being edited is already in the blocked list once it holds
        // dates — skip it so it does not report a clash with its own nights.
        if ($exclude_booking_id > 0
            && $source_id === $exclude_booking_id
            && in_array($source, array('booking', 'payment_hold'), true)) {
            continue;
        }

        $start = isset($range['start']) ? (string) $range['start'] : '';
        $end = isset($range['end']) ? (string) $range['end'] : '';
        if ($start === '' || $end === '') {
            continue;
        }
        if (dilijanvillas_booking_ranges_overlap($checkin, $checkout, $start, $end)) {
            return $range;
        }
    }

    return null;
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
 * Reduce a language slug, locale or HTML lang attribute to a code PayLink knows.
 *
 * @param string $language E.g. "ru", "ru_RU", "en-US".
 * @return string 'hy', 'ru', 'en', or '' when the site language is none of these.
 */
function dilijanvillas_normalize_checkout_language($language)
{
    $language = strtolower(trim((string) $language));
    if ($language === '') {
        return '';
    }

    // Keep the primary subtag only: ru_RU, en-US and ru all become the same code.
    $language = preg_replace('/[^a-z].*$/', '', $language);

    return in_array($language, array('hy', 'ru', 'en'), true) ? $language : '';
}

/**
 * Checkout language for the hosted PayLink page — the language the guest was
 * browsing the site in.
 *
 * The booking carries that language itself: the payment link is built inside an
 * admin-ajax request (and can be rebuilt later from wp-admin), where
 * pll_current_language() no longer describes the visitor's page.
 *
 * @param int $booking_id Booking CPT ID, 0 when unknown.
 * @return string Two-letter-ish code PayLink understands.
 */
function dilijanvillas_paylink_resolve_checkout_language($booking_id = 0)
{
    $booking_id = (int) $booking_id;
    if ($booking_id > 0) {
        $stored = dilijanvillas_normalize_checkout_language((string) get_post_meta($booking_id, '_dv_lang', true));
        if ($stored !== '') {
            return $stored;
        }
    }

    if (function_exists('pll_current_language')) {
        $current = dilijanvillas_normalize_checkout_language((string) pll_current_language('slug'));
        if ($current !== '') {
            return $current;
        }
    }

    $locale = dilijanvillas_normalize_checkout_language((string) get_locale());

    return $locale !== '' ? $locale : 'en';
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
 * Format an expiry instant the way this API talks about time.
 *
 * Every timestamp in the PayLink Integration API spec is UTC with a trailing Z
 * (e.g. expirationDate "2024-03-01T00:00:00Z"), so the deadline is sent as an
 * explicit UTC instant. That marker is the point: an earlier version sent the
 * clock time on Yerevan local time with no zone suffix, and PayLink — comparing
 * the value against its own UTC clock — read those digits as UTC and kept the
 * link payable for the +4h Yerevan offset past the intended window. A Z-marked
 * instant cannot be misread that way, which is what makes the hold-minus-margin
 * deadline actually take effect.
 *
 * (Responses come back naive on Yerevan time — the token handler above corrects
 * for that separately; how the API renders its output does not change how it
 * parses the UTC value we send in.)
 *
 * @param int $timestamp Unix time the link should stop working.
 * @return string ISO-8601 UTC with zone, e.g. 2026-07-24T14:30:00Z.
 */
function dilijanvillas_paylink_am_format_expiration($timestamp)
{
    return gmdate('Y-m-d\TH:i:s\Z', (int) $timestamp);
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

    // A booking link must be paid once, for exactly the calculated total. Without
    // maxCount the PayLink page shows a quantity stepper (+/-) that multiplies the
    // amount, so the guest could pay for several nights' totals at once; capping it
    // at one payment pins the page to the single amount we registered. isFlexible
    // false likewise blocks typing a different amount.
    $guest_return_url = dilijanvillas_paylink_build_guest_return_base_url($settings, $booking_id);

    $register_body = array(
        // Anonymous on purpose: the guest already gave name, phone and email in
        // our own booking form, so making PayLink ask for them again just adds
        // fields to its page. Reconciliation never reads those payer fields — it
        // matches on the requestId UUID and the "DV-{id}" tracking string carried
        // in requestInfo below — so dropping them off the PayLink page costs us
        // nothing and leaves the guest with only the card entry to fill in.
        'allowAnonymous' => true,
        'amount' => round(max(0, (float) $amount_major), 2),
        'isActive' => true,
        'isFlexible' => false,
        'maxCount' => 1,
        'requestType' => $request_type,
        'currency' => $currency,
        'language' => dilijanvillas_paylink_resolve_checkout_language($booking_id),
        'backUrl' => $guest_return_url,
        // The Integration API carries a second redirect target that the payment
        // page uses once the card is charged; with only backUrl set the guest can
        // be left sitting on PayLink after paying. Same destination either way, so
        // both land on ?dv_booking=&dv_payment_return=1 and trigger the sync below.
        'paymentWebRedirectUrl' => $guest_return_url,
        'requestInfo' => $request_info,
    );

    // Same clock as the date hold, minus a safety margin: the link must stop
    // taking money slightly *before* the nights go back on sale, never after,
    // or a late payment lands on dates somebody else has bought since.
    $link_minutes = dilijanvillas_get_payment_link_minutes();
    if ($link_minutes > 0) {
        $register_body['expirationDate'] = dilijanvillas_paylink_am_format_expiration(
            time() + ($link_minutes * MINUTE_IN_SECONDS)
        );
    }

    $register_url = $api_root . '/api/request/register';
    $post_register = static function ($token, $body) use ($register_url) {
        return wp_remote_post(
            $register_url,
            array(
                'timeout' => 30,
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ),
                'body' => wp_json_encode($body),
            )
        );
    };

    $response = $post_register($jwt, $register_body);

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
            $jwt = $jwt_retry;
            $response = $post_register($jwt, $register_body);
        }
    }

    // paymentWebRedirectUrl is not in the partner PDF, and a stricter deployment
    // could reject the payload over it or over the expirationDate format, so an
    // otherwise-valid booking must not be lost to a fussy 400. A booking with no
    // payment link at all is worse than one without a return redirect or a
    // deadline, so drop both once before giving up. The date hold still expires
    // locally either way.
    $optional_fields = array('paymentWebRedirectUrl', 'expirationDate');
    $has_optional = (bool) array_intersect($optional_fields, array_keys($register_body));

    if (!is_wp_error($response) && (int) wp_remote_retrieve_response_code($response) === 400 && $has_optional) {
        dilijanvillas_paylink_log(
            'register',
            'error',
            array(
                'endpoint' => $register_url,
                'status' => 400,
                'message' => __('Rejected with paymentWebRedirectUrl / expirationDate — retrying once without them.', 'dilijanvillas'),
                'booking_id' => $booking_id,
            )
        );

        foreach ($optional_fields as $optional_field) {
            unset($register_body[$optional_field]);
        }

        $response = $post_register($jwt, $register_body);
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
 * Close a PayLink payment request so its hosted page can no longer be paid.
 *
 * expirationDate is accepted by the Integration API (register returns 200) but is
 * not enforced on the hosted payment page — the link stays payable past the
 * deadline. So an abandoned checkout is closed for real here instead: PUT
 * /api/request/modify with isActive:false (and isArchived:true) flips the request
 * off. Without this, the guest — or anyone holding the link — could still pay
 * after the nights went back on sale, landing money on dates someone else may
 * have bought since.
 *
 * Idempotent: the requestId and a "_dv_paylink_deactivated_at" marker keep the
 * five-minute cron sweep from re-calling the API once a link is already closed.
 *
 * @param int $booking_id Booking CPT ID.
 * @return bool True when the link is confirmed closed (deactivated now, already
 *              deactivated, or never registered); false when PayLink would not
 *              confirm it — callers must then NOT free the dates yet.
 */
function dilijanvillas_paylink_am_deactivate_request($booking_id)
{
    $booking_id = (int) $booking_id;
    $request_id = trim((string) get_post_meta($booking_id, '_dv_paylink_request_id', true));

    // No registered request (non-Armenian mode, or the link build failed) means
    // there is no payable link, so the "cannot be paid" guarantee already holds —
    // report closed so callers may safely proceed to cancel.
    if ($request_id === '') {
        return true;
    }

    // Already closed once — don't hammer the API on every sweep.
    if (trim((string) get_post_meta($booking_id, '_dv_paylink_deactivated_at', true)) !== '') {
        return true;
    }

    $settings = dilijanvillas_get_booking_settings();
    $api_root = dilijanvillas_paylink_am_normalize_root((string) $settings['paylink_am_api_base']);
    $jwt = dilijanvillas_paylink_am_get_bearer_token($settings);
    if ($api_root === '' || $jwt === '') {
        return false;
    }

    $request_type = trim((string) ($settings['paylink_am_request_type'] ?? 'Booking'));
    if ($request_type === '') {
        $request_type = 'Booking';
    }

    // ModifyRequest requires requestId, requestType, allowAnonymous and isActive.
    $body = array(
        'requestId' => $request_id,
        'requestType' => $request_type,
        'allowAnonymous' => true,
        'isActive' => false,
        'isArchived' => true,
    );

    $modify_url = $api_root . '/api/request/modify';
    $put_modify = static function ($token) use ($modify_url, $body) {
        return wp_remote_request(
            $modify_url,
            array(
                'method' => 'PUT',
                'timeout' => 25,
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ),
                'body' => wp_json_encode($body),
            )
        );
    };

    $response = $put_modify($jwt);

    // A cached token can lapse mid-flight; re-authorize once, same as register does.
    if (!is_wp_error($response) && in_array((int) wp_remote_retrieve_response_code($response), array(401, 403), true)) {
        $jwt_retry = dilijanvillas_paylink_am_get_bearer_token($settings, true);
        if ($jwt_retry !== '') {
            $response = $put_modify($jwt_retry);
        }
    }

    $code = is_wp_error($response) ? 0 : (int) wp_remote_retrieve_response_code($response);
    if ($code >= 200 && $code < 300) {
        update_post_meta($booking_id, '_dv_paylink_deactivated_at', gmdate('c'));
        dilijanvillas_paylink_log('modify', 'ok', array('endpoint' => $modify_url, 'status' => $code, 'booking_id' => $booking_id));

        return true;
    }

    list($status, $message) = dilijanvillas_paylink_describe_response($response);
    dilijanvillas_paylink_log('modify', 'error', array('endpoint' => $modify_url, 'status' => $status, 'message' => $message, 'booking_id' => $booking_id));

    return false;
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
 * @param int    $booking_id CPT ID dv_booking.
 * @param string $order_id   Payment id PayLink appended to the return URL, if any.
 * @return bool True when PayLink confirms the booking total was paid.
 */
function dilijanvillas_paylink_am_sync_booking_status($booking_id, $order_id = '')
{
    $booking_id = (int) $booking_id;
    $post = get_post($booking_id);
    if (!$post || $post->post_type !== 'dv_booking') {
        return false;
    }

    $settings = dilijanvillas_get_booking_settings();
    if (($settings['paylink_integration_mode'] ?? '') !== 'armenia_integration') {
        return false;
    }

    $request_uuid = trim((string) get_post_meta($booking_id, '_dv_paylink_request_id', true));
    $order_id = trim((string) $order_id);
    if ($request_uuid === '' && $order_id === '') {
        return false;
    }

    // Lets the admin tell "PayLink says not paid yet" apart from "never checked".
    update_post_meta($booking_id, '_dv_paylink_last_check', gmdate('c'));

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

                return true;
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

                    return true;
                }
            }
        }
    }

    return false;
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
 * Tell the returning guest what happened, on the page they booked from.
 *
 * The sync above has already run on template_redirect, so the stored status is
 * current by the time the footer renders. Deliberately says nothing beyond
 * paid / not-yet — the booking id travels in a public URL.
 *
 * @return void
 */
function dilijanvillas_render_payment_return_notice()
{
    if (empty($_GET['dv_payment_return']) || empty($_GET['dv_booking'])) {
        return;
    }

    $booking_id = (int) sanitize_text_field((string) wp_unslash($_GET['dv_booking']));
    if ($booking_id <= 0) {
        return;
    }

    $post = get_post($booking_id);
    if (!$post || $post->post_type !== 'dv_booking') {
        return;
    }

    $paid = (string) get_post_meta($booking_id, '_dv_status', true) === 'paid';
    $lang = dilijanvillas_paylink_resolve_checkout_language($booking_id);

    $strings = array(
        'hy' => array(
            'paid_title' => 'Վճարումն ընդունված է',
            'paid_text' => 'Ձեր #%d ամրագրումը հաստատված է։ Շնորհակալություն։',
            'wait_title' => 'Վճարումը մշակվում է',
            'wait_text' => '#%d ամրագրման վճարման հաստատումը դեռ չի ստացվել։ Եթե գումարը դուրս է գրվել, մենք կհաստատենք ամրագրումը ձեռքով։',
            'close' => 'Փակել',
        ),
        'ru' => array(
            'paid_title' => 'Оплата получена',
            'paid_text' => 'Бронь №%d подтверждена. Спасибо!',
            'wait_title' => 'Оплата обрабатывается',
            'wait_text' => 'Подтверждение оплаты по брони №%d пока не получено. Если деньги списаны, мы подтвердим бронь вручную.',
            'close' => 'Закрыть',
        ),
        'en' => array(
            'paid_title' => 'Payment received',
            'paid_text' => 'Booking #%d is confirmed. Thank you!',
            'wait_title' => 'Payment is being processed',
            'wait_text' => 'We have not received confirmation for booking #%d yet. If you were charged, we will confirm the booking manually.',
            'close' => 'Close',
        ),
    );

    $dict = isset($strings[$lang]) ? $strings[$lang] : $strings['en'];
    $title = $paid ? $dict['paid_title'] : $dict['wait_title'];
    $text = sprintf($paid ? $dict['paid_text'] : $dict['wait_text'], $booking_id);
    $accent = $paid ? '#1a7f37' : '#996800';

    // Dismiss by dropping the markers from the URL — no script needed.
    $dismiss_url = remove_query_arg(array('dv_booking', 'dv_payment_return', 'id'));
    ?>
    <div style="position:fixed;left:50%;bottom:24px;transform:translateX(-50%);z-index:9999;max-width:min(440px,calc(100vw - 32px));width:100%;box-sizing:border-box;display:flex;gap:12px;align-items:flex-start;padding:16px 18px;background:#fff;border-left:4px solid <?php echo esc_attr($accent); ?>;border-radius:10px;box-shadow:0 10px 30px rgba(0,0,0,.18);font-size:15px;line-height:1.45;color:#222;">
      <div style="flex:1;">
        <strong style="display:block;margin-bottom:4px;color:<?php echo esc_attr($accent); ?>;"><?php echo esc_html($title); ?></strong>
        <span><?php echo esc_html($text); ?></span>
      </div>
      <a href="<?php echo esc_url($dismiss_url); ?>" aria-label="<?php echo esc_attr($dict['close']); ?>" style="flex:0 0 auto;color:#666;text-decoration:none;font-size:20px;line-height:1;padding:2px 4px;">&times;</a>
    </div>
    <?php
}
add_action('wp_footer', 'dilijanvillas_render_payment_return_notice');

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
                'language' => dilijanvillas_paylink_resolve_checkout_language($booking_id),
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
        // Read here, while we are still rendering the visitor's page: the booking
        // request itself goes to admin-ajax.php, which has no page language.
        'lang' => dilijanvillas_paylink_resolve_checkout_language(),
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
    $lang = isset($_POST['lang']) ? dilijanvillas_normalize_checkout_language((string) $_POST['lang']) : '';

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

    // Must be stored before the payment link is built — it picks the language of
    // the PayLink page, and is what a link rebuilt from wp-admin reads later.
    if ($lang !== '') {
        update_post_meta((int) $booking_id, '_dv_lang', $lang);
    }

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
        // Starts the window in which these dates stay off the calendar for
        // everyone else; see dilijanvillas_booking_payment_hold_is_active().
        update_post_meta((int) $booking_id, '_dv_payment_hold_started_at', gmdate('c'));
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
        <th scope="row"><label for="dv_booking_accommodation"><?php esc_html_e('Accommodation', 'dilijanvillas'); ?> <span class="description">*</span></label></th>
        <td>
          <select id="dv_booking_accommodation" name="dv_booking_accommodation" required>
            <option value=""><?php esc_html_e('Select accommodation', 'dilijanvillas'); ?></option>
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
        <th scope="row"><label for="dv_booking_dates"><?php esc_html_e('Check-in — Check-out', 'dilijanvillas'); ?> <span class="description">*</span></label></th>
        <td>
          <?php // One range picker drives the two hidden values below. Without flatpickr the native date pair stays visible and usable. ?>
          <input id="dv_booking_dates" type="text" class="regular-text" autocomplete="off" placeholder="<?php echo esc_attr(__('Select stay dates', 'dilijanvillas')); ?>" style="display:none;" />
          <span class="dv-booking-native-dates">
            <label style="margin-right:12px;"><?php esc_html_e('Check-in', 'dilijanvillas'); ?>
              <input id="dv_booking_checkin" type="date" name="dv_booking_checkin" value="<?php echo esc_attr($checkin); ?>" required />
            </label>
            <label><?php esc_html_e('Check-out', 'dilijanvillas'); ?>
              <input id="dv_booking_checkout" type="date" name="dv_booking_checkout" value="<?php echo esc_attr($checkout); ?>" required />
            </label>
          </span>
          <p class="description"><?php esc_html_e('Accommodation, check-in and check-out are required. Without them the booking blocks no dates and is kept as a draft.', 'dilijanvillas'); ?></p>
        </td>
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
        <th scope="row"><label for="dv_booking_payment_url"><?php esc_html_e('Payment URL', 'dilijanvillas'); ?></label></th>
        <td><input id="dv_booking_payment_url" type="url" class="large-text" name="dv_booking_payment_url" value="<?php echo esc_attr($payment_url); ?>" /></td>
      </tr>
      <tr>
        <th scope="row"><?php esc_html_e('PayLink payment', 'dilijanvillas'); ?></th>
        <td><?php dilijanvillas_render_booking_paylink_panel($post->ID); ?></td>
      </tr>
    </table>
    <?php
}

/**
 * PayLink state for one booking, plus the on-demand re-check.
 *
 * Guests who close the tab instead of returning never trigger the automatic
 * sync, so the booking would sit on "Payment pending" even though the card was
 * charged. This asks PayLink directly and flips the status when it confirms.
 *
 * @param int $booking_id Booking post ID.
 * @return void
 */
function dilijanvillas_render_booking_paylink_panel($booking_id)
{
    $booking_id = (int) $booking_id;
    $request_id = trim((string) get_post_meta($booking_id, '_dv_paylink_request_id', true));
    $order_id = (int) get_post_meta($booking_id, '_dv_paylink_order_id', true);
    $paid_at = trim((string) get_post_meta($booking_id, '_dv_paylink_paid_detected_at', true));
    $checked_at = trim((string) get_post_meta($booking_id, '_dv_paylink_last_check', true));
    $last_error = trim((string) get_post_meta($booking_id, '_dv_paylink_last_error', true));

    $format_stamp = static function ($iso) {
        $ts = $iso !== '' ? strtotime($iso) : false;

        return $ts ? wp_date('Y-m-d H:i', $ts) : '';
    };

    // Explains why these nights are (or are no longer) off the public calendar.
    $booking_status = (string) get_post_meta($booking_id, '_dv_status', true);

    if (in_array($booking_status, dilijanvillas_get_blocking_booking_statuses(), true)) {
        $hold_label = __('dates blocked — booking is paid / confirmed', 'dilijanvillas');
    } elseif ($booking_status === 'payment_pending' && dilijanvillas_booking_payment_hold_is_active($booking_id)) {
        $hold_started = trim((string) get_post_meta($booking_id, '_dv_payment_hold_started_at', true));
        $hold_start_ts = $hold_started !== '' ? strtotime($hold_started) : (int) get_post_time('U', true, $booking_id);
        $hold_label = sprintf(
            /* translators: %s: local time the date hold expires */
            __('held until %s', 'dilijanvillas'),
            wp_date('H:i', $hold_start_ts + (dilijanvillas_get_payment_hold_minutes() * MINUTE_IN_SECONDS))
        );
    } else {
        $hold_label = __('not held — dates are on sale', 'dilijanvillas');
    }

    $rows = array(
        __('Request ID', 'dilijanvillas') => $request_id !== '' ? $request_id : '—',
        __('Order ID', 'dilijanvillas') => $order_id > 0 ? (string) $order_id : '—',
        __('Payment confirmed at', 'dilijanvillas') => $format_stamp($paid_at) !== '' ? $format_stamp($paid_at) : '—',
        __('Last checked', 'dilijanvillas') => $format_stamp($checked_at) !== '' ? $format_stamp($checked_at) : __('never', 'dilijanvillas'),
        __('Date hold', 'dilijanvillas') => $hold_label,
    );

    echo '<ul style="margin:0 0 10px;">';
    foreach ($rows as $label => $value) {
        printf('<li style="margin:0 0 2px;"><strong>%s:</strong> <code>%s</code></li>', esc_html($label), esc_html($value));
    }
    echo '</ul>';

    if ($last_error !== '') {
        printf(
            '<p style="margin:0 0 10px;color:#b32d2e;"><strong>%s:</strong> %s</p>',
            esc_html__('Last PayLink error', 'dilijanvillas'),
            esc_html($last_error)
        );
    }

    if ($request_id === '' && $order_id <= 0) {
        printf(
            '<p class="description">%s</p>',
            esc_html__('No PayLink request registered for this booking yet — nothing to check.', 'dilijanvillas')
        );

        return;
    }

    $check_url = wp_nonce_url(
        add_query_arg(
            array(
                'action' => 'dilijanvillas_check_payment',
                'booking' => $booking_id,
            ),
            admin_url('admin-post.php')
        ),
        'dilijanvillas_check_payment_' . $booking_id
    );

    printf(
        '<a href="%s" class="button">%s</a> <span class="description">%s</span>',
        esc_url($check_url),
        esc_html__('Check payment status', 'dilijanvillas'),
        esc_html__('Asks PayLink and sets the status to Paid when the full amount is confirmed. Unsaved edits on this screen are lost.', 'dilijanvillas')
    );
}

/**
 * Handle the "Check payment status" button on the booking edit screen.
 *
 * @return void
 */
function dilijanvillas_admin_check_paylink_payment()
{
    $booking_id = isset($_GET['booking']) ? (int) $_GET['booking'] : 0;

    if ($booking_id <= 0 || !current_user_can('edit_post', $booking_id)) {
        wp_die(esc_html__('You are not allowed to check this booking.', 'dilijanvillas'), '', array('response' => 403));
    }

    check_admin_referer('dilijanvillas_check_payment_' . $booking_id);

    // A known order id is the documented lookup; the request uuid is the fallback
    // the sync uses on its own.
    $known_order_id = (int) get_post_meta($booking_id, '_dv_paylink_order_id', true);
    $paid = dilijanvillas_paylink_am_sync_booking_status(
        $booking_id,
        $known_order_id > 0 ? (string) $known_order_id : ''
    );

    wp_safe_redirect(
        add_query_arg(
            'dv_payment_checked',
            $paid ? 'paid' : 'unpaid',
            (string) get_edit_post_link($booking_id, 'url')
        )
    );
    exit;
}
add_action('admin_post_dilijanvillas_check_payment', 'dilijanvillas_admin_check_paylink_payment');

/**
 * Report the result of a manual payment check.
 *
 * @return void
 */
function dilijanvillas_admin_payment_check_notice()
{
    if (empty($_GET['dv_payment_checked'])) {
        return;
    }

    $result = sanitize_text_field((string) wp_unslash($_GET['dv_payment_checked']));

    if ($result === 'paid') {
        printf(
            '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
            esc_html__('PayLink confirmed the payment — this booking is now marked as Paid.', 'dilijanvillas')
        );

        return;
    }

    printf(
        '<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
        esc_html__('PayLink has no confirmed payment for this booking yet. Set the status to Paid by hand if you have other proof.', 'dilijanvillas')
    );
}
add_action('admin_notices', 'dilijanvillas_admin_payment_check_notice');

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
        <th scope="row"><label for="dv_unavailable_start"><?php esc_html_e('Start date', 'dilijanvillas'); ?> <span class="description">*</span></label></th>
        <td><input id="dv_unavailable_start" type="date" name="dv_unavailable_start" value="<?php echo esc_attr($start); ?>" required /></td>
      </tr>
      <tr>
        <th scope="row"><label for="dv_unavailable_end"><?php esc_html_e('End date', 'dilijanvillas'); ?> <span class="description">*</span></label></th>
        <td>
          <input id="dv_unavailable_end" type="date" name="dv_unavailable_end" value="<?php echo esc_attr($end); ?>" required />
          <p class="description"><?php esc_html_e('Both dates are required. Without them the period blocks nothing, so it will be kept as a draft.', 'dilijanvillas'); ?></p>
        </td>
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

    $previous_status = (string) get_post_meta($post_id, '_dv_status', true);

    // Cancelling frees the dates, so close the PayLink link FIRST and accept the
    // cancellation only once PayLink confirms the link is dead. If it will not
    // confirm, refuse the change and keep the previous status, so the dates stay
    // blocked — the nights must never reopen while the link could still be paid.
    // The admin is told why via an admin notice and can retry.
    if ($status === 'cancelled' && $previous_status !== 'cancelled'
        && !dilijanvillas_paylink_am_deactivate_request($post_id)) {
        set_transient('dilijanvillas_cancel_blocked_' . get_current_user_id(), (int) $post_id, MINUTE_IN_SECONDS);
        $status = $previous_status !== '' ? $previous_status : 'payment_pending';
    }

    // Keep the admin in step with live availability: a booking may only take
    // nights off the calendar (confirmed / paid / payment_pending) when those
    // nights are not already held by another booking or a manual unavailable
    // period for the same unit — otherwise two guests would own the same dates.
    // The dates entered are kept, but the status is dropped to "pending" (which
    // blocks nothing) and the admin is told which range it clashed with. A
    // booking that is not trying to occupy dates is left untouched.
    $occupies_dates = in_array($status, dilijanvillas_get_blocking_booking_statuses(), true)
        || $status === 'payment_pending';
    if ($occupies_dates
        && $accommodation_id > 0
        && dilijanvillas_is_valid_booking_date($checkin)
        && dilijanvillas_is_valid_booking_date($checkout)
        && $checkout >= $checkin) {
        $conflict = dilijanvillas_find_booking_date_conflict($accommodation_id, $checkin, $checkout, (int) $post_id);
        if ($conflict !== null) {
            $status = 'pending';
            set_transient(
                'dilijanvillas_booking_conflict_' . get_current_user_id(),
                array(
                    'booking' => (int) $post_id,
                    'start' => (string) ($conflict['start'] ?? ''),
                    'end' => (string) ($conflict['end'] ?? ''),
                    'source' => (string) ($conflict['source'] ?? ''),
                ),
                MINUTE_IN_SECONDS
            );
        }
    }

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
        $currency = $manual_currency !== '' ? $manual_currency : (string) $price['currency'];
        update_post_meta($post_id, '_dv_total', $total);
        // Nights follow the chosen period — there is no field to override them.
        update_post_meta($post_id, '_dv_nights', (int) $price['nights']);
        update_post_meta($post_id, '_dv_currency', $currency);
    } else {
        // Without a usable period there is nothing to recount from, so the stored
        // night count is left as it is instead of being wiped to zero.
        update_post_meta($post_id, '_dv_total', $manual_total);
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

    // A booking needs an accommodation and a valid date range to hold nights,
    // price a stay or reach the guest. Saved without them it is kept as a draft
    // so it never sits "published" doing nothing (see admin notice below).
    dilijanvillas_guard_booking_required($post_id, $accommodation_id, $checkin, $checkout);
}
add_action('save_post_dv_booking', 'dilijanvillas_save_booking_metabox');

/**
 * Whether a booking carries the fields it needs to be published.
 *
 * @param int    $accommodation_id Submitted accommodation ID (0 when unset).
 * @param string $checkin          Submitted check-in date (Y-m-d).
 * @param string $checkout         Submitted check-out date (Y-m-d).
 * @return bool
 */
function dilijanvillas_booking_required_fields_present($accommodation_id, $checkin, $checkout)
{
    return (int) $accommodation_id > 0
        && dilijanvillas_is_valid_booking_date($checkin)
        && dilijanvillas_is_valid_booking_date($checkout)
        && $checkout >= $checkin;
}

/**
 * Keep a booking without its essential fields out of publish.
 *
 * Accommodation, check-in and check-out are what make a booking a booking: with
 * any of them missing it holds no nights, prices nothing and cannot reach the
 * guest, so a published entry would just sit there doing nothing. It is pushed
 * back to draft and the reason is stored for an admin notice. Mirrors
 * dilijanvillas_guard_period_dates for the period post types.
 *
 * @param int    $post_id          Booking post ID.
 * @param int    $accommodation_id Submitted accommodation ID (0 when unset).
 * @param string $checkin          Submitted check-in date (Y-m-d).
 * @param string $checkout         Submitted check-out date (Y-m-d).
 * @return bool True when every required field is present.
 */
function dilijanvillas_guard_booking_required($post_id, $accommodation_id, $checkin, $checkout)
{
    if (dilijanvillas_booking_required_fields_present($accommodation_id, $checkin, $checkout)) {
        delete_post_meta($post_id, '_dv_booking_required_error');

        return true;
    }

    $missing = array();
    if ((int) $accommodation_id <= 0) {
        $missing[] = __('an accommodation', 'dilijanvillas');
    }
    if (!dilijanvillas_is_valid_booking_date($checkin)) {
        $missing[] = __('a check-in date', 'dilijanvillas');
    }
    if (!dilijanvillas_is_valid_booking_date($checkout)) {
        $missing[] = __('a check-out date', 'dilijanvillas');
    }

    if (empty($missing)) {
        // Every field is present, so the only way here is a backwards range.
        $message = __('Check-out cannot be earlier than check-in — booking saved as draft.', 'dilijanvillas');
    } else {
        $message = sprintf(
            /* translators: %s: comma-separated list of the missing required fields */
            __('A booking needs %s — saved as draft.', 'dilijanvillas'),
            implode(', ', $missing)
        );
    }

    update_post_meta($post_id, '_dv_booking_required_error', $message);

    $post = get_post($post_id);
    if ($post && $post->post_status === 'publish') {
        remove_action('save_post_dv_booking', 'dilijanvillas_save_booking_metabox');
        wp_update_post(
            array(
                'ID' => $post_id,
                'post_status' => 'draft',
            )
        );
        add_action('save_post_dv_booking', 'dilijanvillas_save_booking_metabox');
    }

    return false;
}

/**
 * Show why a booking was forced back to draft.
 */
function dilijanvillas_booking_required_admin_notice()
{
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->post_type !== 'dv_booking') {
        return;
    }

    $post_id = isset($_GET['post']) ? (int) $_GET['post'] : 0;
    if ($post_id <= 0) {
        return;
    }

    $message = (string) get_post_meta($post_id, '_dv_booking_required_error', true);
    if ($message === '') {
        return;
    }

    printf('<div class="notice notice-error"><p>%s</p></div>', esc_html($message));
}
add_action('admin_notices', 'dilijanvillas_booking_required_admin_notice');

/**
 * Load flatpickr on the Booking edit screen so Check-in / Check-out show which
 * days are free and which are already taken for the chosen accommodation.
 *
 * The busy days come from the same source the public booking form uses
 * (dilijanvillas_get_blocked_ranges over admin-ajax), so the admin sees exactly
 * what a guest would: confirmed / paid bookings, live payment holds and manual
 * unavailable periods. The booking being edited is excluded client-side so it
 * never greys out its own nights.
 *
 * @param string $hook Current admin page.
 * @return void
 */
function dilijanvillas_enqueue_booking_admin_calendar($hook)
{
    if ($hook !== 'post.php' && $hook !== 'post-new.php') {
        return;
    }
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->post_type !== 'dv_booking') {
        return;
    }

    // Same flatpickr build the front-end booking form loads.
    wp_enqueue_style('flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css', array(), '4.6.13');
    wp_enqueue_script('flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js', array(), '4.6.13', true);

    wp_localize_script(
        'flatpickr',
        'DV_BOOKING_ADMIN',
        array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dilijanvillas_booking_nonce'),
            'bookingId' => isset($_GET['post']) ? (int) $_GET['post'] : 0,
        )
    );

    wp_add_inline_style('flatpickr', dilijanvillas_booking_admin_calendar_css());
    wp_add_inline_script('flatpickr', dilijanvillas_booking_admin_calendar_js());
}
add_action('admin_enqueue_scripts', 'dilijanvillas_enqueue_booking_admin_calendar');

/**
 * Styles marking already-taken days on the admin booking calendar.
 *
 * @return string
 */
function dilijanvillas_booking_admin_calendar_css()
{
    return '.flatpickr-day.dv-admin-day-blocked,'
        . '.flatpickr-day.dv-admin-day-blocked:hover{'
        . 'background:#fbe9e7;color:#b32d2e;text-decoration:line-through;border-color:transparent;}'
        . '#dv_booking_dates{max-width:22em;}'
        . '#dv_booking_checkin,#dv_booking_checkout{max-width:12em;}';
}

/**
 * Inline script turning the two date fields into flatpickr calendars that grey
 * out and red-mark days already blocked for the selected accommodation.
 *
 * Reads config from window.DV_BOOKING_ADMIN. Degrades to the native date inputs
 * when flatpickr is unavailable (e.g. the CDN is blocked), so the fields keep
 * working either way.
 *
 * @return string
 */
function dilijanvillas_booking_admin_calendar_js()
{
    return <<<'JS'
(function () {
  var cfg = window.DV_BOOKING_ADMIN || {};
  var ajaxUrl = cfg.ajaxUrl || window.ajaxurl || '';
  var nonce = cfg.nonce || '';
  var bookingId = parseInt(cfg.bookingId || 0, 10) || 0;

  function ready(fn) {
    if (document.readyState !== 'loading') { fn(); }
    else { document.addEventListener('DOMContentLoaded', fn); }
  }

  ready(function () {
    var accSelect = document.getElementById('dv_booking_accommodation');
    var rangeInput = document.getElementById('dv_booking_dates');
    var checkin = document.getElementById('dv_booking_checkin');
    var checkout = document.getElementById('dv_booking_checkout');
    var nativeWrap = document.querySelector('.dv-booking-native-dates');
    // Without flatpickr the native check-in / check-out pair stays visible and usable.
    if (!rangeInput || !checkin || !checkout || typeof window.flatpickr !== 'function') { return; }

    // One input drives the two hidden values: reveal the range field, retire the
    // native pair, and move the required flag onto the visible control (a hidden
    // required input would block submit without a focusable field to point at).
    rangeInput.style.display = '';
    if (nativeWrap) { nativeWrap.style.display = 'none'; }
    checkin.removeAttribute('required');
    checkout.removeAttribute('required');
    rangeInput.setAttribute('required', 'required');

    var blocked = [];
    var picker = null;

    function pad(n) { return (n < 10 ? '0' : '') + n; }
    function ymd(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }

    function isBlocked(s) {
      if (!s) { return false; }
      for (var i = 0; i < blocked.length; i++) {
        if (blocked[i].start && blocked[i].end && s >= blocked[i].start && s <= blocked[i].end) { return true; }
      }
      return false;
    }

    function markDay(el) {
      if (!el || !el.dateObj) { return; }
      if (isBlocked(ymd(el.dateObj))) { el.classList.add('dv-admin-day-blocked'); }
      else { el.classList.remove('dv-admin-day-blocked'); }
    }

    function markAll(fp) {
      if (fp && fp.days) { Array.prototype.forEach.call(fp.days.children, markDay); }
    }

    function applyDisable() {
      var spec = blocked.map(function (r) { return { from: r.start, to: r.end }; });
      if (picker) { picker.set('disable', spec); }
    }

    function fetchRanges() {
      var accId = accSelect ? String(accSelect.value || '').trim() : '';
      if (!ajaxUrl || !nonce || !accId) { blocked = []; applyDisable(); return; }
      var body = new URLSearchParams();
      body.set('action', 'dilijanvillas_get_blocked_ranges');
      body.set('nonce', nonce);
      body.set('accommodation_id', accId);
      body.set('_', String(Date.now()));
      fetch(ajaxUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        credentials: 'same-origin',
        body: body.toString()
      }).then(function (r) { return r.json(); }).then(function (json) {
        var ranges = (json && json.success && json.data && Array.isArray(json.data.ranges)) ? json.data.ranges : [];
        blocked = ranges.filter(function (r) {
          var src = String(r.source || '');
          var sid = parseInt(r.sourceId || 0, 10) || 0;
          // Never grey out the booking currently being edited against itself.
          if (bookingId > 0 && sid === bookingId && (src === 'booking' || src === 'payment_hold')) { return false; }
          return true;
        }).map(function (r) {
          return { start: String(r.start || '').trim(), end: String(r.end || '').trim() };
        }).filter(function (r) { return r.start && r.end && r.end >= r.start; });
      }).catch(function () { blocked = []; }).then(function () { applyDisable(); });
    }

    var hadBoth = checkin.value && checkout.value;
    // Past days are not bookable. Keep an already-set past check-in selectable so
    // editing an older booking still shows its dates instead of clearing them.
    var todayStr = ymd(new Date());
    var minDate = (checkin.value && checkin.value < todayStr) ? checkin.value : todayStr;
    picker = window.flatpickr(rangeInput, {
      mode: 'range',
      dateFormat: 'Y-m-d',
      disableMobile: true,
      minDate: minDate,
      defaultDate: hadBoth ? [checkin.value, checkout.value] : (checkin.value ? [checkin.value] : []),
      onDayCreate: function (a, b, c, el) { markDay(el); },
      onMonthChange: function (a, b, fp) { markAll(fp); },
      onYearChange: function (a, b, fp) { markAll(fp); },
      onChange: function (dates, str, fp) {
        checkin.value = dates.length >= 1 ? fp.formatDate(dates[0], 'Y-m-d') : '';
        checkout.value = dates.length >= 2 ? fp.formatDate(dates[1], 'Y-m-d') : '';
      }
    });

    if (accSelect) { accSelect.addEventListener('change', fetchRanges); }
    fetchRanges();
  });
})();
JS;
}

/**
 * Tell the admin when a manual cancellation was refused because the PayLink link
 * could not be confirmed closed — the booking was deliberately left as it was so
 * its dates keep blocking, and the cancellation can be retried.
 */
function dilijanvillas_booking_cancel_blocked_notice()
{
    $key = 'dilijanvillas_cancel_blocked_' . get_current_user_id();
    $blocked_id = (int) get_transient($key);
    if ($blocked_id <= 0) {
        return;
    }
    delete_transient($key);

    printf(
        '<div class="notice notice-error is-dismissible"><p>%s</p></div>',
        esc_html(
            sprintf(
                /* translators: %d: booking ID */
                __('Booking #%d was NOT cancelled: PayLink would not confirm the payment link is closed, so the dates stay blocked to prevent payment on reopened nights. Check PayLink diagnostics and try again.', 'dilijanvillas'),
                $blocked_id
            )
        )
    );
}
add_action('admin_notices', 'dilijanvillas_booking_cancel_blocked_notice');

/**
 * Tell the admin when a booking was kept off the calendar because its dates
 * overlap a range that is already blocked. The booking was saved as "pending"
 * (which holds no nights) instead of confirmed/paid, so no double booking is
 * created; the admin can pick free dates and set the status again.
 */
function dilijanvillas_booking_conflict_notice()
{
    $key = 'dilijanvillas_booking_conflict_' . get_current_user_id();
    $data = get_transient($key);
    if (!is_array($data) || empty($data['booking'])) {
        return;
    }
    delete_transient($key);

    $start = isset($data['start']) ? (string) $data['start'] : '';
    $end = isset($data['end']) ? (string) $data['end'] : '';
    $source = isset($data['source']) ? (string) $data['source'] : '';
    $source_label = $source === 'manual_block'
        ? __('a manual unavailable period', 'dilijanvillas')
        : __('another booking', 'dilijanvillas');

    printf(
        '<div class="notice notice-error is-dismissible"><p>%s</p></div>',
        esc_html(
            sprintf(
                /* translators: 1: booking ID, 2: conflicting range source, 3: range start, 4: range end */
                __('Booking #%1$d was saved as "Pending" and does NOT hold the dates: they overlap %2$s (%3$s → %4$s) for this accommodation. Choose free dates, then set the status to Confirmed or Paid.', 'dilijanvillas'),
                (int) $data['booking'],
                $source_label,
                $start !== '' ? $start : '—',
                $end !== '' ? $end : '—'
            )
        )
    );
}
add_action('admin_notices', 'dilijanvillas_booking_conflict_notice');

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

    dilijanvillas_guard_period_dates(
        $post_id,
        $start,
        $end,
        'save_post_dv_unavailable',
        'dilijanvillas_save_unavailable_metabox'
    );

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
 * Whether a period has a usable date range.
 *
 * @param string $start Start date (Y-m-d).
 * @param string $end   End date (Y-m-d).
 * @return bool
 */
function dilijanvillas_period_dates_are_valid($start, $end)
{
    return dilijanvillas_is_valid_booking_date($start)
        && dilijanvillas_is_valid_booking_date($end)
        && $end >= $start;
}

/**
 * Keep a period without a usable date range out of publish.
 *
 * Such an entry blocks no dates and prices no nights, so publishing it just
 * creates an item that silently does nothing. It is pushed back to draft and
 * the reason is stored for an admin notice.
 *
 * @param int    $post_id       Period post ID.
 * @param string $start         Submitted start date.
 * @param string $end           Submitted end date.
 * @param string $save_hook     Hook to detach while updating, avoiding recursion.
 * @param string $save_callback Callback registered on that hook.
 * @return bool True when the dates are usable.
 */
function dilijanvillas_guard_period_dates($post_id, $start, $end, $save_hook, $save_callback)
{
    if (dilijanvillas_period_dates_are_valid($start, $end)) {
        delete_post_meta($post_id, '_dv_period_date_error');

        return true;
    }

    $message = (dilijanvillas_is_valid_booking_date($start) && dilijanvillas_is_valid_booking_date($end))
        ? __('End date cannot be earlier than start date — saved as draft.', 'dilijanvillas')
        : __('Start date and end date are both required — saved as draft.', 'dilijanvillas');

    update_post_meta($post_id, '_dv_period_date_error', $message);

    $post = get_post($post_id);
    if ($post && $post->post_status === 'publish') {
        remove_action($save_hook, $save_callback);
        wp_update_post(
            array(
                'ID' => $post_id,
                'post_status' => 'draft',
            )
        );
        add_action($save_hook, $save_callback);
    }

    return false;
}

/**
 * Show why a period was forced back to draft.
 */
function dilijanvillas_period_date_admin_notice()
{
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || !in_array($screen->post_type, array('dv_unavailable', 'dv_price_period'), true)) {
        return;
    }

    $post_id = isset($_GET['post']) ? (int) $_GET['post'] : 0;
    if ($post_id <= 0) {
        return;
    }

    $message = (string) get_post_meta($post_id, '_dv_period_date_error', true);
    if ($message === '') {
        return;
    }

    printf('<div class="notice notice-error"><p>%s</p></div>', esc_html($message));
}
add_action('admin_notices', 'dilijanvillas_period_date_admin_notice');

/**
 * Add Customer name, Status, Accommodation, Check-in, Check-out and Total
 * amount columns to the Bookings list.
 *
 * The generated booking title alone does not say who is where and when, so the
 * list was unusable without opening every entry. The title also keeps the name
 * it was created with, so a customer renamed afterwards only shows correctly in
 * the Customer name column.
 *
 * @param array<string,string> $columns Existing columns.
 * @return array<string,string>
 */
function dilijanvillas_booking_columns($columns)
{
    $added = array(
        'dv_booking_customer' => __('Customer name', 'dilijanvillas'),
        'dv_booking_status' => __('Status', 'dilijanvillas'),
        'dv_booking_accommodation' => __('Accommodation', 'dilijanvillas'),
        'dv_booking_checkin' => __('Check-in', 'dilijanvillas'),
        'dv_booking_checkout' => __('Check-out', 'dilijanvillas'),
        'dv_booking_total' => __('Total amount', 'dilijanvillas'),
    );

    $reordered = array();
    foreach ($columns as $key => $label) {
        $reordered[$key] = $label;
        if ($key === 'title') {
            $reordered = array_merge($reordered, $added);
        }
    }

    if (!isset($reordered['dv_booking_status'])) {
        $reordered = array_merge($reordered, $added);
    }

    return $reordered;
}
add_filter('manage_dv_booking_posts_columns', 'dilijanvillas_booking_columns');

/**
 * Render the custom Bookings columns.
 *
 * Dates print as stored (Y-m-d), matching the date inputs on the edit screen.
 *
 * @param string $column  Column key.
 * @param int    $post_id Row post ID.
 */
function dilijanvillas_booking_column_content($column, $post_id)
{
    if ($column === 'dv_booking_customer') {
        $customer_name = trim((string) get_post_meta($post_id, '_dv_customer_name', true));
        if ($customer_name === '') {
            echo '<span style="color:#8c8f94;">&mdash;</span>';

            return;
        }

        echo esc_html($customer_name);

        return;
    }

    if ($column === 'dv_booking_status') {
        $status = (string) get_post_meta($post_id, '_dv_status', true);
        if ($status === '') {
            $status = 'pending';
        }

        $labels = dilijanvillas_get_booking_status_labels();
        $label = isset($labels[$status]) ? $labels[$status] : $status;

        $styles = array(
            'paid' => 'color:#1a7f37;font-weight:600;',
            'confirmed' => 'color:#2271b1;font-weight:600;',
            'pending' => 'color:#996800;',
            'payment_pending' => 'color:#996800;',
            'cancelled' => 'color:#8c8f94;',
        );

        printf(
            '<span style="%s">%s %s</span>',
            esc_attr(isset($styles[$status]) ? $styles[$status] : ''),
            $status === 'cancelled' ? '&#9675;' : '&#9679;',
            esc_html($label)
        );

        return;
    }

    if ($column === 'dv_booking_accommodation') {
        $accommodation_id = (int) get_post_meta($post_id, '_dv_accommodation_id', true);
        if ($accommodation_id <= 0) {
            echo '<span style="color:#8c8f94;">&mdash;</span>';

            return;
        }

        $title = trim((string) get_the_title($accommodation_id));
        echo esc_html($title !== '' ? $title : ('#' . $accommodation_id));

        return;
    }

    if ($column === 'dv_booking_total') {
        $total_raw = get_post_meta($post_id, '_dv_total', true);
        if ($total_raw === '' || $total_raw === null) {
            echo '<span style="color:#8c8f94;">&mdash;</span>';

            return;
        }

        $total = (float) $total_raw;
        $currency = trim((string) get_post_meta($post_id, '_dv_currency', true));
        if ($currency === '') {
            $currency = 'AMD';
        }

        printf(
            '%s <code>%s</code>',
            esc_html(number_format_i18n($total, (floor($total) === $total ? 0 : 2))),
            esc_html($currency)
        );

        return;
    }

    $date_columns = array(
        'dv_booking_checkin' => '_dv_checkin',
        'dv_booking_checkout' => '_dv_checkout',
    );

    if (!isset($date_columns[$column])) {
        return;
    }

    $value = (string) get_post_meta($post_id, $date_columns[$column], true);
    if ($value === '') {
        echo '<span style="color:#8c8f94;">&mdash;</span>';

        return;
    }

    echo esc_html($value);
}
add_action('manage_dv_booking_posts_custom_column', 'dilijanvillas_booking_column_content', 10, 2);

/**
 * One entry per accommodation for the Bookings filter.
 *
 * Translations of one page are the same accommodation for the admin, so they
 * collapse into a single option labelled with the default-language title.
 *
 * @return array<int,array<string,mixed>>
 */
function dilijanvillas_get_booking_filter_accommodations()
{
    $preferred_lang = function_exists('pll_default_language') ? (string) pll_default_language('slug') : '';
    $groups = array();

    foreach (dilijanvillas_get_all_accommodations_for_admin() as $accommodation) {
        $id = isset($accommodation['id']) ? (int) $accommodation['id'] : 0;
        if ($id <= 0) {
            continue;
        }

        $key = $id;
        if (function_exists('pll_get_post_translations')) {
            $translations = pll_get_post_translations($id);
            if (is_array($translations) && !empty($translations)) {
                $key = min(array_map('intval', $translations));
            }
        }

        $lang = isset($accommodation['lang']) ? (string) $accommodation['lang'] : '';
        $is_preferred = $preferred_lang !== '' && $lang === $preferred_lang;
        if (isset($groups[$key]) && !$is_preferred) {
            continue;
        }

        $title = isset($accommodation['title']) ? trim((string) $accommodation['title']) : '';
        $groups[$key] = array(
            'id' => $id,
            'title' => $title !== '' ? $title : ('#' . $id),
        );
    }

    return array_values($groups);
}

/**
 * Accommodation dropdown above the Bookings list.
 *
 * @param string $post_type Current list table post type.
 */
function dilijanvillas_booking_accommodation_filter($post_type)
{
    if ($post_type !== 'dv_booking') {
        return;
    }

    $accommodations = dilijanvillas_get_booking_filter_accommodations();
    if (empty($accommodations)) {
        return;
    }

    $selected = isset($_GET['dv_accommodation']) ? (int) $_GET['dv_accommodation'] : 0;

    echo '<select name="dv_accommodation">';
    printf(
        '<option value="0">%s</option>',
        esc_html__('All accommodations', 'dilijanvillas')
    );
    foreach ($accommodations as $accommodation) {
        printf(
            '<option value="%d" %s>%s</option>',
            (int) $accommodation['id'],
            selected($selected, (int) $accommodation['id'], false),
            esc_html((string) $accommodation['title'])
        );
    }
    echo '</select>';
}
add_action('restrict_manage_posts', 'dilijanvillas_booking_accommodation_filter');

/**
 * Status dropdown above the Bookings list.
 *
 * @param string $post_type Current list table post type.
 */
function dilijanvillas_booking_status_filter($post_type)
{
    if ($post_type !== 'dv_booking') {
        return;
    }

    $labels = dilijanvillas_get_booking_status_labels();
    $selected = isset($_GET['dv_status']) ? sanitize_key(wp_unslash($_GET['dv_status'])) : '';

    echo '<select name="dv_status">';
    printf(
        '<option value="">%s</option>',
        esc_html__('All statuses', 'dilijanvillas')
    );
    foreach ($labels as $key => $label) {
        printf(
            '<option value="%s" %s>%s</option>',
            esc_attr($key),
            selected($selected, $key, false),
            esc_html($label)
        );
    }
    echo '</select>';
}
add_action('restrict_manage_posts', 'dilijanvillas_booking_status_filter');

/**
 * Apply the Bookings accommodation filter.
 *
 * It matches every translation of the chosen page, because a booking stores the
 * page the guest actually booked on — the hy, ru and en versions of one cottage
 * are the same accommodation here.
 *
 * @param WP_Query $query Current query.
 */
function dilijanvillas_filter_bookings_admin_query($query)
{
    global $pagenow;

    if (!is_admin() || $pagenow !== 'edit.php' || !$query->is_main_query()) {
        return;
    }
    if ($query->get('post_type') !== 'dv_booking') {
        return;
    }

    $meta_query = $query->get('meta_query');
    if (!is_array($meta_query)) {
        $meta_query = array();
    }

    $accommodation_id = isset($_GET['dv_accommodation']) ? (int) $_GET['dv_accommodation'] : 0;
    if ($accommodation_id > 0) {
        $page_ids = dilijanvillas_expand_pages_to_all_languages(array($accommodation_id));
        if (empty($page_ids)) {
            $page_ids = array($accommodation_id);
        }
        $meta_query[] = array(
            'key' => '_dv_accommodation_id',
            'value' => $page_ids,
            'compare' => 'IN',
            'type' => 'NUMERIC',
        );
    }

    $status = isset($_GET['dv_status']) ? sanitize_key(wp_unslash($_GET['dv_status'])) : '';
    $status_labels = dilijanvillas_get_booking_status_labels();
    if ($status !== '' && isset($status_labels[$status])) {
        if ($status === 'pending') {
            // A booking with no stored status shows as Pending in the list (see the
            // column renderer), so the filter has to catch those rows too — not just
            // the ones carrying the explicit 'pending' value.
            $meta_query[] = array(
                'relation' => 'OR',
                array('key' => '_dv_status', 'value' => 'pending'),
                array('key' => '_dv_status', 'compare' => 'NOT EXISTS'),
            );
        } else {
            $meta_query[] = array(
                'key' => '_dv_status',
                'value' => $status,
            );
        }
    }

    if (!empty($meta_query)) {
        $query->set('meta_query', $meta_query);
    }
}
add_action('pre_get_posts', 'dilijanvillas_filter_bookings_admin_query');

/**
 * Let the Bookings search box match the customer name and phone.
 *
 * WordPress searches post titles only, and a booking title keeps the name it was
 * created with — a customer renamed afterwards became unfindable, and the phone
 * was never in the title at all. The stored `_dv_customer_name` and
 * `_dv_customer_phone` are ORed into the existing title search with an EXISTS
 * subquery, so no join can duplicate rows.
 *
 * @param string   $search Search SQL, already prefixed with " AND ".
 * @param WP_Query $query  Current query.
 * @return string
 */
function dilijanvillas_search_bookings_by_customer($search, $query)
{
    global $pagenow, $wpdb;

    if ($search === '' || !is_admin() || $pagenow !== 'edit.php' || !$query->is_main_query()) {
        return $search;
    }
    if ($query->get('post_type') !== 'dv_booking') {
        return $search;
    }

    $term = trim((string) $query->get('s'));
    if ($term === '') {
        return $search;
    }

    $like = '%' . $wpdb->esc_like($term) . '%';
    $clauses = array(
        $wpdb->prepare("dv_search_meta.meta_key = '_dv_customer_name' AND dv_search_meta.meta_value LIKE %s", $like),
        $wpdb->prepare("dv_search_meta.meta_key = '_dv_customer_phone' AND dv_search_meta.meta_value LIKE %s", $like),
    );

    // Phones are stored as typed — "+374 55 12 34 56", "+37455123456", "055 12 34 56"
    // are the same number — so digits are also compared against the stored value
    // stripped of its formatting.
    $digits = preg_replace('/\D+/', '', $term);
    if ($digits !== '') {
        $normalized = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(dv_search_meta.meta_value,"
            . " ' ', ''), '-', ''), '(', ''), ')', ''), '+', ''), '.', '')";

        $digit_variants = array($digits);
        // A national number typed with its trunk "0" still has to find the number
        // stored in international form.
        if (strlen($digits) > 1 && $digits[0] === '0') {
            $digit_variants[] = substr($digits, 1);
        }

        foreach ($digit_variants as $variant) {
            $clauses[] = $wpdb->prepare(
                "dv_search_meta.meta_key = '_dv_customer_phone' AND {$normalized} LIKE %s",
                '%' . $wpdb->esc_like($variant) . '%'
            );
        }
    }

    $customer_match = "EXISTS (SELECT 1 FROM {$wpdb->postmeta} AS dv_search_meta"
        . " WHERE dv_search_meta.post_id = {$wpdb->posts}.ID"
        . ' AND ((' . implode(') OR (', $clauses) . ')))';

    // Core hands over " AND (<title conditions>)"; widen that group instead of
    // appending, or the OR would swallow the rest of the WHERE clause.
    $conditions = preg_replace('/^\s*AND\s+/i', '', $search);

    return ' AND (' . $conditions . ' OR ' . $customer_match . ') ';
}
add_filter('posts_search', 'dilijanvillas_search_bookings_by_customer', 10, 2);

/**
 * Add Status, Accommodations, Start date, End date and Internal note columns to
 * the Unavailable periods list.
 *
 * Same reason as the Price periods list: the title alone says nothing about what
 * a period blocks or when, so every row had to be opened to be understood.
 *
 * @param array<string,string> $columns Existing columns.
 * @return array<string,string>
 */
function dilijanvillas_unavailable_columns($columns)
{
    $added = array(
        'dv_status' => __('Status', 'dilijanvillas'),
        'dv_unavailable_scope' => __('Accommodations', 'dilijanvillas'),
        'dv_unavailable_start' => __('Start date', 'dilijanvillas'),
        'dv_unavailable_end' => __('End date', 'dilijanvillas'),
        'dv_unavailable_note' => __('Internal note', 'dilijanvillas'),
    );

    $reordered = array();
    foreach ($columns as $key => $label) {
        $reordered[$key] = $label;
        if ($key === 'title') {
            $reordered = array_merge($reordered, $added);
        }
    }

    if (!isset($reordered['dv_status'])) {
        $reordered = array_merge($reordered, $added);
    }

    return $reordered;
}
add_filter('manage_dv_unavailable_posts_columns', 'dilijanvillas_unavailable_columns');

/**
 * Render the custom Unavailable periods columns.
 *
 * Dates print as stored (Y-m-d) so they match the date inputs on the edit screen
 * and stay unambiguous for the mixed hy/ru/en admins.
 *
 * @param string $column  Column key.
 * @param int    $post_id Row post ID.
 */
function dilijanvillas_unavailable_column_content($column, $post_id)
{
    if ($column === 'dv_status') {
        $status = (string) get_post_meta($post_id, '_dv_unavailable_status', true);
        if ($status === '') {
            $status = 'active';
        }

        if ($status === 'active') {
            printf(
                '<span style="color:#1a7f37;font-weight:600;">&#9679; %s</span>',
                esc_html__('Active', 'dilijanvillas')
            );

            return;
        }

        printf(
            '<span style="color:#8c8f94;">&#9675; %s</span>',
            esc_html__('Inactive', 'dilijanvillas')
        );

        return;
    }

    if ($column === 'dv_unavailable_scope') {
        dilijanvillas_render_period_scope_cell($post_id);

        return;
    }

    $date_columns = array(
        'dv_unavailable_start' => '_dv_unavailable_start',
        'dv_unavailable_end' => '_dv_unavailable_end',
    );

    if (isset($date_columns[$column])) {
        $value = (string) get_post_meta($post_id, $date_columns[$column], true);
        if ($value === '') {
            echo '<span style="color:#8c8f94;">&mdash;</span>';

            return;
        }

        echo esc_html($value);

        return;
    }

    if ($column !== 'dv_unavailable_note') {
        return;
    }

    $note = trim((string) get_post_meta($post_id, '_dv_unavailable_note', true));
    if ($note === '') {
        echo '<span style="color:#8c8f94;">&mdash;</span>';

        return;
    }

    echo esc_html(wp_trim_words($note, 12, '…'));
}
add_action('manage_dv_unavailable_posts_custom_column', 'dilijanvillas_unavailable_column_content', 10, 2);

/**
 * Print the Accommodations cell of a price or unavailable period.
 *
 * Both post types store the scope in the same meta and both lists need the same
 * summary, so the rendering lives in one place.
 *
 * @param int $post_id Period post ID.
 */
function dilijanvillas_render_period_scope_cell($post_id)
{
    $ids = dilijanvillas_get_period_accommodation_ids($post_id);

    if (empty($ids)) {
        echo '<span style="color:#8c8f94;">&mdash;</span>';

        return;
    }

    if (in_array(0, $ids, true)) {
        printf(
            '<span style="color:#1a7f37;font-weight:600;">%s</span>',
            esc_html__('All accommodations', 'dilijanvillas')
        );

        return;
    }

    // Translations of one page are a single accommodation for the admin —
    // collapse them so an "all languages" period stays readable.
    $groups = array();
    foreach ($ids as $id) {
        $key = $id;
        if (function_exists('pll_get_post_translations')) {
            $translations = pll_get_post_translations($id);
            if (is_array($translations) && !empty($translations)) {
                $key = min(array_map('intval', $translations));
            }
        }
        if (!isset($groups[$key])) {
            $groups[$key] = $id;
        }
    }

    $names = array();
    foreach (array_slice(array_values($groups), 0, 3) as $id) {
        $title = trim((string) get_the_title($id));
        $names[] = $title !== '' ? $title : ('#' . $id);
    }

    $output = implode(', ', $names);
    if (count($groups) > 3) {
        $output .= ' ' . sprintf(
            /* translators: %d: number of further accommodations. */
            __('(+%d more)', 'dilijanvillas'),
            count($groups) - 3
        );
    }

    echo esc_html($output);
}

/**
 * Add Status, Accommodations, Start date, End date and Nightly rate columns to
 * the Price periods list.
 *
 * Without them the list shows only the title, so the dates and the rate — the
 * whole content of a period — are invisible until you open each entry.
 *
 * @param array<string,string> $columns Existing columns.
 * @return array<string,string>
 */
function dilijanvillas_price_period_columns($columns)
{
    $added = array(
        'dv_price_status' => __('Status', 'dilijanvillas'),
        'dv_price_scope' => __('Accommodations', 'dilijanvillas'),
        'dv_price_start' => __('Start date', 'dilijanvillas'),
        'dv_price_end' => __('End date', 'dilijanvillas'),
        'dv_price_rate' => __('Nightly rate', 'dilijanvillas'),
    );

    $reordered = array();
    foreach ($columns as $key => $label) {
        $reordered[$key] = $label;
        if ($key === 'title') {
            $reordered = array_merge($reordered, $added);
        }
    }

    // No title column (unlikely, but the list is filterable): append instead of
    // silently dropping the columns.
    if (!isset($reordered['dv_price_status'])) {
        $reordered = array_merge($reordered, $added);
    }

    return $reordered;
}
add_filter('manage_dv_price_period_posts_columns', 'dilijanvillas_price_period_columns');

/**
 * Render the custom Price periods columns.
 *
 * Dates print as stored (Y-m-d) so they match the date inputs on the edit screen
 * and stay unambiguous for the mixed hy/ru/en admins.
 *
 * @param string $column  Column key.
 * @param int    $post_id Row post ID.
 */
function dilijanvillas_price_period_column_content($column, $post_id)
{
    if ($column === 'dv_price_status') {
        $status = (string) get_post_meta($post_id, '_dv_price_status', true);
        if ($status === '') {
            $status = 'active';
        }

        if ($status === 'active') {
            printf(
                '<span style="color:#1a7f37;font-weight:600;">&#9679; %s</span>',
                esc_html__('Active', 'dilijanvillas')
            );

            return;
        }

        printf(
            '<span style="color:#8c8f94;">&#9675; %s</span>',
            esc_html__('Inactive', 'dilijanvillas')
        );

        return;
    }

    if ($column === 'dv_price_scope') {
        dilijanvillas_render_period_scope_cell($post_id);

        return;
    }

    $date_columns = array(
        'dv_price_start' => '_dv_price_start',
        'dv_price_end' => '_dv_price_end',
    );

    if (isset($date_columns[$column])) {
        $value = (string) get_post_meta($post_id, $date_columns[$column], true);
        if ($value === '') {
            echo '<span style="color:#8c8f94;">&mdash;</span>';

            return;
        }

        echo esc_html($value);

        return;
    }

    if ($column !== 'dv_price_rate') {
        return;
    }

    $rate = (string) get_post_meta($post_id, '_dv_price_rate', true);
    if ($rate === '') {
        echo '<span style="color:#8c8f94;">&mdash;</span>';

        return;
    }

    $rate_value = (float) $rate;
    $settings = dilijanvillas_get_booking_settings();

    printf(
        '%s <code>%s</code>',
        esc_html(number_format_i18n($rate_value, (floor($rate_value) === $rate_value ? 0 : 2))),
        esc_html((string) $settings['currency'])
    );
}
add_action('manage_dv_price_period_posts_custom_column', 'dilijanvillas_price_period_column_content', 10, 2);

/**
 * Accommodation IDs a period (price or unavailable) is stored against.
 *
 * Falls back to the legacy single-ID meta so periods saved before the
 * multi-select still report their scope. A 0 in the list means "all".
 *
 * @param int $post_id Period post ID.
 * @return array<int,int>
 */
function dilijanvillas_get_period_accommodation_ids($post_id)
{
    $ids = get_post_meta((int) $post_id, '_dv_accommodation_ids', false);
    if (!is_array($ids)) {
        $ids = array();
    }
    $ids = array_values(array_unique(array_map('intval', $ids)));

    if (empty($ids)) {
        $legacy = get_post_meta((int) $post_id, '_dv_accommodation_id', true);
        if ($legacy !== '') {
            $ids = array((int) $legacy);
        }
    }

    return $ids;
}

/**
 * Price periods that affect one accommodation (or the global ones).
 *
 * Matches the front-end rule in dilijanvillas_get_price_periods(): a period
 * counts when it is assigned to the page (or any of its translations) and also
 * when it is assigned to ALL accommodations, because such a period does price
 * this page too. Runs as a separate ID query so the OR meta joins cannot
 * duplicate rows in the list table.
 *
 * @param int $accommodation_id Page ID, or 0 for "all accommodations" periods only.
 * @return array<int,int>
 */
function dilijanvillas_get_price_period_ids_for_accommodation($accommodation_id)
{
    $accommodation_id = (int) $accommodation_id;

    $global_clause = array(
        'relation' => 'OR',
        array(
            'key' => '_dv_accommodation_ids',
            'value' => 0,
            'compare' => '=',
            'type' => 'NUMERIC',
        ),
        array(
            'key' => '_dv_accommodation_id',
            'value' => 0,
            'compare' => '=',
            'type' => 'NUMERIC',
        ),
    );

    if ($accommodation_id > 0) {
        $page_ids = dilijanvillas_expand_pages_to_all_languages(array($accommodation_id));
        if (empty($page_ids)) {
            $page_ids = array($accommodation_id);
        }

        $meta_query = array(
            'relation' => 'OR',
            array(
                'key' => '_dv_accommodation_ids',
                'value' => $page_ids,
                'compare' => 'IN',
                'type' => 'NUMERIC',
            ),
            array(
                'key' => '_dv_accommodation_id',
                'value' => $page_ids,
                'compare' => 'IN',
                'type' => 'NUMERIC',
            ),
            $global_clause,
        );
    } else {
        $meta_query = $global_clause;
    }

    $found = get_posts(
        array(
            'post_type' => 'dv_price_period',
            'post_status' => array('publish', 'draft', 'pending', 'future', 'private', 'trash'),
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
            'suppress_filters' => false,
            'meta_query' => $meta_query,
        )
    );

    if (!is_array($found)) {
        return array();
    }

    return array_values(array_unique(array_map('intval', $found)));
}

/**
 * Accommodation dropdown above the Price periods list table.
 *
 * @param string $post_type Current list table post type.
 */
function dilijanvillas_price_period_admin_filter($post_type)
{
    if ($post_type !== 'dv_price_period') {
        return;
    }

    $selected = isset($_GET['dv_accommodation']) ? sanitize_text_field((string) $_GET['dv_accommodation']) : '';

    $accommodations = dilijanvillas_get_all_accommodations_for_admin();
    $groups = array();
    foreach ($accommodations as $accommodation) {
        $lang_slug = isset($accommodation['lang']) ? (string) $accommodation['lang'] : '';
        $groups[$lang_slug][] = $accommodation;
    }
    ksort($groups);
    ?>
    <label class="screen-reader-text" for="dv_accommodation"><?php esc_html_e('Filter by accommodation', 'dilijanvillas'); ?></label>
    <select id="dv_accommodation" name="dv_accommodation">
      <option value=""><?php esc_html_e('All accommodations', 'dilijanvillas'); ?></option>
      <option value="0" <?php selected($selected, '0'); ?>><?php esc_html_e('Global periods only (apply to all)', 'dilijanvillas'); ?></option>
      <?php foreach ($groups as $lang_slug => $items) : ?>
        <?php $label = $lang_slug !== '' ? strtoupper($lang_slug) : __('Default', 'dilijanvillas'); ?>
        <optgroup label="<?php echo esc_attr($label); ?>">
          <?php foreach ($items as $accommodation) : ?>
            <?php
              $option_id = isset($accommodation['id']) ? (int) $accommodation['id'] : 0;
              $title = isset($accommodation['title']) ? (string) $accommodation['title'] : ('#' . $option_id);
              $template = isset($accommodation['template']) ? (string) $accommodation['template'] : '';
              $type_hint = $template === 'private-willa.php' ? __('Villa', 'dilijanvillas') : __('Cottage', 'dilijanvillas');
            ?>
            <option value="<?php echo esc_attr((string) $option_id); ?>" <?php selected($selected, (string) $option_id); ?>>
              <?php echo esc_html($title . ' — ' . $type_hint); ?>
            </option>
          <?php endforeach; ?>
        </optgroup>
      <?php endforeach; ?>
    </select>
    <?php
}
add_action('restrict_manage_posts', 'dilijanvillas_price_period_admin_filter');

/**
 * Apply the accommodation dropdown to the Price periods list query.
 *
 * @param WP_Query $query Current query.
 */
function dilijanvillas_price_period_admin_filter_query($query)
{
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }
    if ($query->get('post_type') !== 'dv_price_period') {
        return;
    }
    if (!isset($_GET['dv_accommodation']) || (string) $_GET['dv_accommodation'] === '') {
        return;
    }

    $matching = dilijanvillas_get_price_period_ids_for_accommodation((int) $_GET['dv_accommodation']);

    // Empty result: post__in with a 0 keeps the list empty instead of showing
    // everything, which is what an unfiltered post__in would do.
    $query->set('post__in', !empty($matching) ? $matching : array(0));
}
add_action('pre_get_posts', 'dilijanvillas_price_period_admin_filter_query');

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
        <th scope="row"><label for="dv_price_period_start"><?php esc_html_e('Start date', 'dilijanvillas'); ?> <span class="description">*</span></label></th>
        <td><input id="dv_price_period_start" type="date" name="dv_price_period_start" value="<?php echo esc_attr($start); ?>" required /></td>
      </tr>
      <tr>
        <th scope="row"><label for="dv_price_period_end"><?php esc_html_e('End date', 'dilijanvillas'); ?> <span class="description">*</span></label></th>
        <td>
          <input id="dv_price_period_end" type="date" name="dv_price_period_end" value="<?php echo esc_attr($end); ?>" required />
          <p class="description"><?php esc_html_e('Both dates are required and inclusive. A booking is priced only when its check-in AND check-out dates both fall inside this range.', 'dilijanvillas'); ?></p>
        </td>
      </tr>
      <tr>
        <th scope="row"><label for="dv_price_period_rate"><?php esc_html_e('Nightly rate', 'dilijanvillas'); ?></label></th>
        <td>
          <?php /* Wider than WordPress's .small-text (65px) — rates in AMD run to six digits. */ ?>
          <input id="dv_price_period_rate" type="number" min="0" step="0.01" name="dv_price_period_rate" value="<?php echo esc_attr($rate); ?>" style="width:195px;" />
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

    dilijanvillas_guard_period_dates(
        $post_id,
        $start,
        $end,
        'save_post_dv_price_period',
        'dilijanvillas_save_price_period_metabox'
    );
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

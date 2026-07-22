<?php
/**
 * PayLink integration diagnostics: error log + connection test button.
 *
 * Every failure branch in the Armenian Integration API path returns an empty
 * string, so a misconfiguration is otherwise invisible in wp-admin: the booking
 * is created, gets no payment URL, and nothing says why. This module records
 * what actually happened and surfaces it on the Booking Settings screen.
 *
 * @package dilijanvillas
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('DILIJANVILLAS_PAYLINK_LOG_KEY')) {
    define('DILIJANVILLAS_PAYLINK_LOG_KEY', 'dilijanvillas_paylink_log');
}

/**
 * How many exchanges are kept in the ring buffer.
 *
 * @return int
 */
function dilijanvillas_paylink_log_limit()
{
    return 25;
}

/**
 * Strip secrets and clip long values before anything is persisted.
 *
 * Tokens must never end up in an options row that any admin can read.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function dilijanvillas_paylink_scrub($value)
{
    $value = (string) $value;

    $value = (string) preg_replace('/(eyJ[A-Za-z0-9_\-]+\.){2}[A-Za-z0-9_\-]+/', '[jwt]', $value);
    $value = (string) preg_replace(
        '/"(partnerKey|token|accessToken|refreshToken)"\s*:\s*"[^"]*"/i',
        '"$1":"[redacted]"',
        $value
    );

    $value = trim(preg_replace('/\s+/u', ' ', $value));

    if (strlen($value) > 500) {
        $value = substr($value, 0, 500) . '…';
    }

    return $value;
}

/**
 * Record one PayLink API exchange.
 *
 * @param string              $stage   Short label, e.g. "authorize" or "register".
 * @param string              $result  "ok" or "error".
 * @param array<string,mixed> $context endpoint / status / message / booking_id.
 * @return void
 */
function dilijanvillas_paylink_log($stage, $result, $context = array())
{
    $entry = array(
        'time' => gmdate('c'),
        'stage' => sanitize_text_field((string) $stage),
        'result' => $result === 'ok' ? 'ok' : 'error',
        'endpoint' => isset($context['endpoint']) ? dilijanvillas_paylink_scrub($context['endpoint']) : '',
        'status' => isset($context['status']) ? (int) $context['status'] : 0,
        'message' => isset($context['message']) ? dilijanvillas_paylink_scrub($context['message']) : '',
        'booking_id' => isset($context['booking_id']) ? (int) $context['booking_id'] : 0,
    );

    $log = get_option(DILIJANVILLAS_PAYLINK_LOG_KEY, array());
    if (!is_array($log)) {
        $log = array();
    }

    array_unshift($log, $entry);
    update_option(DILIJANVILLAS_PAYLINK_LOG_KEY, array_slice($log, 0, dilijanvillas_paylink_log_limit()), false);

    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log(
            sprintf(
                '[PayLink] %s %s status=%d %s',
                $entry['stage'],
                $entry['result'],
                $entry['status'],
                $entry['message']
            )
        );
    }
}

/**
 * Turn a wp_remote_* result into (status, human-readable message).
 *
 * Understands the ProblemDetails shape the API returns on 4xx.
 *
 * @param array<string,mixed>|WP_Error $response Result of wp_remote_get/post.
 * @return array{0:int,1:string}
 */
function dilijanvillas_paylink_describe_response($response)
{
    if (is_wp_error($response)) {
        return array(0, $response->get_error_message());
    }

    $status = (int) wp_remote_retrieve_response_code($response);
    $body = (string) wp_remote_retrieve_body($response);
    $decoded = json_decode($body, true);

    if (is_array($decoded)) {
        $bits = array();
        foreach (array('title', 'detail') as $key) {
            if (!empty($decoded[$key])) {
                $bits[] = (string) $decoded[$key];
            }
        }
        if (!empty($bits)) {
            return array($status, implode(' — ', $bits));
        }
    }

    return array($status, $body !== '' ? $body : __('Empty response body.', 'dilijanvillas'));
}

/**
 * Which settings the Armenian Integration API cannot work without.
 *
 * @param array<string,mixed> $settings Booking settings.
 * @return array<int,string> Human-readable labels of the missing fields.
 */
function dilijanvillas_paylink_missing_settings($settings)
{
    $missing = array();

    if (trim((string) ($settings['paylink_am_api_base'] ?? '')) === '') {
        $missing[] = __('Paylink Armenia — API base URL', 'dilijanvillas');
    }
    if (trim((string) ($settings['paylink_merchant_id'] ?? '')) === '') {
        $missing[] = __('Paylink shop ID (partnerId)', 'dilijanvillas');
    }
    if (trim((string) ($settings['paylink_api_token'] ?? '')) === '') {
        $missing[] = __('Paylink secret key (partnerKey)', 'dilijanvillas');
    }

    return $missing;
}

/**
 * Force a real authorize round-trip and report the outcome.
 *
 * @return void
 */
function dilijanvillas_paylink_admin_test_connection()
{
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Insufficient permissions.', 'dilijanvillas'));
    }
    check_admin_referer('dilijanvillas_paylink_test');

    $settings = dilijanvillas_get_booking_settings();
    $missing = dilijanvillas_paylink_missing_settings($settings);

    if (!empty($missing)) {
        dilijanvillas_paylink_log(
            'authorize',
            'error',
            array('message' => __('Settings incomplete: ', 'dilijanvillas') . implode(', ', $missing))
        );
        $outcome = 'missing';
    } else {
        // Drop the cached JWT so this really hits the API.
        delete_transient(dilijanvillas_paylink_am_token_cache_key($settings));
        $outcome = dilijanvillas_paylink_am_get_bearer_token($settings) !== '' ? 'ok' : 'fail';
    }

    wp_safe_redirect(
        add_query_arg(
            array(
                'page' => 'dilijanvillas-booking-settings',
                'dv_paylink_test' => $outcome,
            ),
            admin_url('options-general.php')
        )
    );
    exit;
}
add_action('admin_post_dilijanvillas_paylink_test', 'dilijanvillas_paylink_admin_test_connection');

/**
 * Wipe the diagnostics log.
 *
 * @return void
 */
function dilijanvillas_paylink_admin_clear_log()
{
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Insufficient permissions.', 'dilijanvillas'));
    }
    check_admin_referer('dilijanvillas_paylink_clear_log');

    delete_option(DILIJANVILLAS_PAYLINK_LOG_KEY);

    wp_safe_redirect(
        add_query_arg(
            array('page' => 'dilijanvillas-booking-settings'),
            admin_url('options-general.php')
        )
    );
    exit;
}
add_action('admin_post_dilijanvillas_paylink_clear_log', 'dilijanvillas_paylink_admin_clear_log');

/**
 * Diagnostics panel rendered under the Booking Settings form.
 *
 * @return void
 */
function dilijanvillas_paylink_render_diagnostics_panel()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $settings = dilijanvillas_get_booking_settings();
    $missing = dilijanvillas_paylink_missing_settings($settings);
    $mode = (string) ($settings['paylink_integration_mode'] ?? '');
    $outcome = isset($_GET['dv_paylink_test']) ? sanitize_key((string) $_GET['dv_paylink_test']) : '';

    $log = get_option(DILIJANVILLAS_PAYLINK_LOG_KEY, array());
    if (!is_array($log)) {
        $log = array();
    }
    ?>
    <hr />
    <h2><?php esc_html_e('PayLink diagnostics', 'dilijanvillas'); ?></h2>

    <?php if ($outcome === 'ok') : ?>
      <div class="notice notice-success"><p><?php esc_html_e('Authorization succeeded — partnerId and partnerKey are accepted by this API base.', 'dilijanvillas'); ?></p></div>
    <?php elseif ($outcome === 'fail') : ?>
      <div class="notice notice-error"><p><?php esc_html_e('Authorization failed. See the log below for the exact response.', 'dilijanvillas'); ?></p></div>
    <?php elseif ($outcome === 'missing') : ?>
      <div class="notice notice-warning"><p><?php esc_html_e('Cannot test: required PayLink settings are still empty.', 'dilijanvillas'); ?></p></div>
    <?php endif; ?>

    <?php if ($mode !== 'armenia_integration') : ?>
      <div class="notice notice-warning inline">
        <p><?php esc_html_e('Integration mode is not "Armenia — PayLink Integration API". The diagnostics below only cover the Armenian flow.', 'dilijanvillas'); ?></p>
      </div>
    <?php endif; ?>

    <?php
    $from_constants = array();
    foreach (array('paylink_am_api_base' => 'API base URL', 'paylink_merchant_id' => 'partnerId', 'paylink_api_token' => 'partnerKey') as $key => $label) {
        if (dilijanvillas_paylink_setting_from_constant($key)) {
            $from_constants[] = $label;
        }
    }
    ?>

    <?php if (!empty($from_constants)) : ?>
      <div class="notice notice-info inline">
        <p>
          <?php
          printf(
              /* translators: %s: comma-separated list of setting names */
              esc_html__('Taken from wp-config.php constants (the fields above are ignored for these): %s', 'dilijanvillas'),
              esc_html(implode(', ', $from_constants))
          );
          ?>
        </p>
      </div>
    <?php endif; ?>

    <?php if (!empty($missing)) : ?>
      <div class="notice notice-error inline">
        <p><strong><?php esc_html_e('No payment link can be created — these fields are empty:', 'dilijanvillas'); ?></strong></p>
        <ul style="list-style:disc;margin-left:20px;">
          <?php foreach ($missing as $label) : ?>
            <li><?php echo esc_html($label); ?></li>
          <?php endforeach; ?>
        </ul>
        <p><?php esc_html_e('Fill them in above and save, or define them in wp-config.php (above the "That\'s all, stop editing!" line):', 'dilijanvillas'); ?></p>
        <pre style="background:#f6f7f7;padding:10px;overflow-x:auto;">define( 'DILIJANVILLAS_PAYLINK_API_BASE',   'https://integration.apitest.paylink.am' );
define( 'DILIJANVILLAS_PAYLINK_PARTNER_ID',  'your_partner_id' );
define( 'DILIJANVILLAS_PAYLINK_PARTNER_KEY', 'your_partner_key' );</pre>
      </div>
    <?php endif; ?>

    <p>
      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline;">
        <input type="hidden" name="action" value="dilijanvillas_paylink_test" />
        <?php wp_nonce_field('dilijanvillas_paylink_test'); ?>
        <?php submit_button(__('Test PayLink connection', 'dilijanvillas'), 'secondary', 'submit', false); ?>
      </form>
      <?php if (!empty($log)) : ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline;margin-left:8px;">
          <input type="hidden" name="action" value="dilijanvillas_paylink_clear_log" />
          <?php wp_nonce_field('dilijanvillas_paylink_clear_log'); ?>
          <?php submit_button(__('Clear log', 'dilijanvillas'), 'link-delete', 'submit', false); ?>
        </form>
      <?php endif; ?>
    </p>

    <?php if (empty($log)) : ?>
      <p class="description"><?php esc_html_e('No PayLink API calls recorded yet. Press "Test PayLink connection", or submit a booking on the site.', 'dilijanvillas'); ?></p>
    <?php else : ?>
      <table class="widefat striped">
        <thead>
          <tr>
            <th style="width:150px;"><?php esc_html_e('Time (UTC)', 'dilijanvillas'); ?></th>
            <th style="width:90px;"><?php esc_html_e('Stage', 'dilijanvillas'); ?></th>
            <th style="width:70px;"><?php esc_html_e('Result', 'dilijanvillas'); ?></th>
            <th style="width:60px;"><?php esc_html_e('HTTP', 'dilijanvillas'); ?></th>
            <th style="width:80px;"><?php esc_html_e('Booking', 'dilijanvillas'); ?></th>
            <th><?php esc_html_e('Detail', 'dilijanvillas'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($log as $entry) : ?>
            <tr>
              <td><?php echo esc_html((string) ($entry['time'] ?? '')); ?></td>
              <td><code><?php echo esc_html((string) ($entry['stage'] ?? '')); ?></code></td>
              <td>
                <?php if (($entry['result'] ?? '') === 'ok') : ?>
                  <span style="color:#1a7f37;">OK</span>
                <?php else : ?>
                  <strong style="color:#b32d2e;"><?php esc_html_e('error', 'dilijanvillas'); ?></strong>
                <?php endif; ?>
              </td>
              <td><?php echo (int) ($entry['status'] ?? 0) > 0 ? esc_html((string) $entry['status']) : '—'; ?></td>
              <td>
                <?php
                $log_booking_id = (int) ($entry['booking_id'] ?? 0);
                if ($log_booking_id > 0) {
                    printf(
                        '<a href="%s">#%d</a>',
                        esc_url(get_edit_post_link($log_booking_id) ?: '#'),
                        $log_booking_id
                    );
                } else {
                    echo '—';
                }
                ?>
              </td>
              <td>
                <?php if (!empty($entry['endpoint'])) : ?>
                  <div><code><?php echo esc_html((string) $entry['endpoint']); ?></code></div>
                <?php endif; ?>
                <?php echo esc_html((string) ($entry['message'] ?? '')); ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <p class="description"><?php esc_html_e('Tokens and partner keys are redacted before anything is stored.', 'dilijanvillas'); ?></p>
    <?php endif; ?>
    <?php
}
add_action('dilijanvillas_booking_settings_after_form', 'dilijanvillas_paylink_render_diagnostics_panel');

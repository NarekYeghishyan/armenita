<?php
/**
 * Full-site password gate (session + persistent browser cookie + localStorage).
 *
 * @package dilijanvillas
 */

if (!defined('ABSPATH')) {
    exit;
}

/** Site access password (preview / staging lock). */
const DV_SITE_ACCESS_PASSWORD = '789Aa';

/** $_SESSION key when the visitor has unlocked the site. */
const DV_SITE_ACCESS_SESSION_KEY = 'dv_site_access_ok';

/** Persistent browser cookie name. */
const DV_SITE_ACCESS_COOKIE_NAME = 'dv_site_access';

/** How long the browser remembers access after a successful login. */
const DV_SITE_ACCESS_COOKIE_DAYS = 30;

/** localStorage key used by the front-end script. */
const DV_SITE_ACCESS_STORAGE_KEY = 'dv_site_access_ok';

/**
 * Signed token stored in cookie / localStorage (not the raw password).
 *
 * @return string
 */
function dilijanvillas_site_access_get_browser_token()
{
    return hash_hmac('sha256', DV_SITE_ACCESS_PASSWORD, wp_salt('dv_site_access_gate_v1'));
}

/**
 * @param string $token Candidate token.
 * @return bool
 */
function dilijanvillas_site_access_token_is_valid($token)
{
    $token = (string) $token;

    return $token !== '' && hash_equals(dilijanvillas_site_access_get_browser_token(), $token);
}

/**
 * Start PHP session on the public site when needed.
 *
 * @return void
 */
function dilijanvillas_site_access_maybe_start_session()
{
    if (is_admin()) {
        return;
    }

    if (session_status() === PHP_SESSION_NONE) {
        if (headers_sent()) {
            return;
        }

        session_start(
            array(
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax',
                'use_strict_mode' => true,
            )
        );
    }
}

/**
 * Whether the visitor has a valid persistent browser cookie.
 *
 * @return bool
 */
function dilijanvillas_site_access_has_valid_cookie()
{
    $cookie = isset($_COOKIE[DV_SITE_ACCESS_COOKIE_NAME])
        ? (string) wp_unslash($_COOKIE[DV_SITE_ACCESS_COOKIE_NAME])
        : '';

    return dilijanvillas_site_access_token_is_valid($cookie);
}

/**
 * Whether the current browser has passed the gate (session or cookie).
 *
 * @return bool
 */
function dilijanvillas_site_access_is_unlocked()
{
    dilijanvillas_site_access_maybe_start_session();

    if (!empty($_SESSION[DV_SITE_ACCESS_SESSION_KEY])) {
        return true;
    }

    if (dilijanvillas_site_access_has_valid_cookie()) {
        $_SESSION[DV_SITE_ACCESS_SESSION_KEY] = 1;

        return true;
    }

    return false;
}

/**
 * Write a long-lived HttpOnly cookie so the browser remembers access.
 *
 * @return void
 */
function dilijanvillas_site_access_set_browser_cookie()
{
    if (headers_sent()) {
        return;
    }

    $token = dilijanvillas_site_access_get_browser_token();
    $expires = time() + (DAY_IN_SECONDS * DV_SITE_ACCESS_COOKIE_DAYS);

    $path = defined('COOKIEPATH') && COOKIEPATH ? COOKIEPATH : '/';
    $domain = defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : '';

    setcookie(
        DV_SITE_ACCESS_COOKIE_NAME,
        $token,
        array(
            'expires' => $expires,
            'path' => $path,
            'domain' => $domain,
            'secure' => is_ssl(),
            'httponly' => true,
            'samesite' => 'Lax',
        )
    );

    $_COOKIE[DV_SITE_ACCESS_COOKIE_NAME] = $token;
}

/**
 * Mark the current session and browser as unlocked.
 *
 * @return void
 */
function dilijanvillas_site_access_set_unlocked()
{
    dilijanvillas_site_access_maybe_start_session();
    $_SESSION[DV_SITE_ACCESS_SESSION_KEY] = 1;
    dilijanvillas_site_access_set_browser_cookie();
}

/**
 * Whether the full-screen gate should render on this request.
 *
 * @return bool
 */
function dilijanvillas_site_access_should_show_gate()
{
    if (is_admin()) {
        return false;
    }

    if (dilijanvillas_site_access_is_unlocked()) {
        return false;
    }

    return true;
}

/**
 * AJAX: verify password and persist unlock in session + cookie.
 *
 * @return void
 */
function dilijanvillas_ajax_site_access_unlock()
{
    check_ajax_referer('dilijanvillas_site_access', 'nonce');

    $password = isset($_POST['password']) ? (string) wp_unslash($_POST['password']) : '';

    if ($password !== DV_SITE_ACCESS_PASSWORD) {
        wp_send_json_error(
            array(
                'message' => __('Incorrect password. Please try again.', 'dilijanvillas'),
            ),
            403
        );
    }

    dilijanvillas_site_access_set_unlocked();

    wp_send_json_success(
        array(
            'message' => __('Access granted.', 'dilijanvillas'),
            'browserToken' => dilijanvillas_site_access_get_browser_token(),
            'storageKey' => DV_SITE_ACCESS_STORAGE_KEY,
        )
    );
}
add_action('wp_ajax_nopriv_dilijanvillas_site_access_unlock', 'dilijanvillas_ajax_site_access_unlock');
add_action('wp_ajax_dilijanvillas_site_access_unlock', 'dilijanvillas_ajax_site_access_unlock');

/**
 * AJAX: restore access from a token previously saved in localStorage.
 *
 * @return void
 */
function dilijanvillas_ajax_site_access_restore()
{
    check_ajax_referer('dilijanvillas_site_access', 'nonce');

    $token = isset($_POST['token']) ? (string) wp_unslash($_POST['token']) : '';

    if (!dilijanvillas_site_access_token_is_valid($token)) {
        wp_send_json_error(
            array(
                'message' => __('Saved access expired. Please enter the password again.', 'dilijanvillas'),
            ),
            403
        );
    }

    dilijanvillas_site_access_set_unlocked();

    wp_send_json_success(
        array(
            'message' => __('Welcome back.', 'dilijanvillas'),
        )
    );
}
add_action('wp_ajax_nopriv_dilijanvillas_site_access_restore', 'dilijanvillas_ajax_site_access_restore');
add_action('wp_ajax_dilijanvillas_site_access_restore', 'dilijanvillas_ajax_site_access_restore');

/**
 * Pass gate config to the main script when the gate is active.
 *
 * @return void
 */
function dilijanvillas_site_access_localize_script()
{
    if (!dilijanvillas_site_access_should_show_gate()) {
        return;
    }

    wp_localize_script(
        'dilijanvillas-main',
        'DV_SITE_ACCESS',
        array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dilijanvillas_site_access'),
            'action' => 'dilijanvillas_site_access_unlock',
            'restoreAction' => 'dilijanvillas_site_access_restore',
            'storageKey' => DV_SITE_ACCESS_STORAGE_KEY,
        )
    );
}
add_action('wp_enqueue_scripts', 'dilijanvillas_site_access_localize_script', 20);

/**
 * Lock page scroll while the gate is visible.
 *
 * @param array<int,string> $classes Body classes.
 * @return array<int,string>
 */
function dilijanvillas_site_access_body_class($classes)
{
    if (dilijanvillas_site_access_should_show_gate()) {
        $classes[] = 'site-access-locked';
    }

    return $classes;
}
add_filter('body_class', 'dilijanvillas_site_access_body_class');

/**
 * Output full-screen password overlay in the footer.
 *
 * @return void
 */
function dilijanvillas_render_site_access_gate()
{
    if (!dilijanvillas_site_access_should_show_gate()) {
        return;
    }
    ?>
    <div id="site-access-gate" class="site-access-gate" role="dialog" aria-modal="true" aria-labelledby="site-access-gate-title" aria-describedby="site-access-gate-desc">
      <div class="site-access-gate__backdrop" aria-hidden="true"></div>
      <div class="site-access-gate__panel">
        <p class="site-access-gate__eyebrow"><?php esc_html_e('Armenita Family Resort', 'dilijanvillas'); ?></p>
        <h2 id="site-access-gate-title" class="site-access-gate__title"><?php esc_html_e('Private access', 'dilijanvillas'); ?></h2>
        <p id="site-access-gate-desc" class="site-access-gate__lead"><?php esc_html_e('Enter the password to view this site.', 'dilijanvillas'); ?></p>
        <form class="site-access-gate__form" data-site-access-form novalidate>
          <label class="site-access-gate__field">
            <span class="screen-reader-text"><?php esc_html_e('Password', 'dilijanvillas'); ?></span>
            <input
              type="password"
              name="site_access_password"
              class="site-access-gate__input"
              autocomplete="current-password"
              required
              autofocus
              data-site-access-input
            />
          </label>
          <p class="site-access-gate__error" data-site-access-error hidden role="alert"></p>
          <button type="submit" class="btn btn--primary site-access-gate__submit" data-site-access-submit>
            <?php esc_html_e('Enter site', 'dilijanvillas'); ?>
          </button>
        </form>
      </div>
    </div>
    <?php
}
add_action('wp_footer', 'dilijanvillas_render_site_access_gate', 5);

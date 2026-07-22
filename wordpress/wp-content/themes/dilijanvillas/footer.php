    <div id="lightbox" class="lightbox" role="dialog" aria-modal="true" aria-label="Gallery" aria-hidden="true" hidden>
      <button type="button" class="lightbox__backdrop" tabindex="-1" aria-label="Close"></button>
      <div class="lightbox__inner">
        <button type="button" class="lightbox__close" data-lightbox-close aria-label="Close">&times;</button>
        <button type="button" class="lightbox__nav lightbox__nav--prev" data-lightbox-prev aria-label="Prev">‹</button>
        <div class="lightbox__stage"><img class="lightbox__img" src="" alt="" decoding="async" /></div>
        <button type="button" class="lightbox__nav lightbox__nav--next" data-lightbox-next aria-label="Next">›</button>
        <p class="lightbox__counter" data-lightbox-counter></p>
      </div>
    </div>
    <footer class="footer">
      <div class="container footer__shell">
        <div class="footer__brand-row">
          <span class="footer__name" data-i18n="hero_title">Armenita Family Resort</span>
          <span class="footer__dot" aria-hidden="true"></span>
          <span class="footer__tag" data-i18n="footer_tag">Հայաստան</span>
        </div>
        <p class="footer__copy" data-i18n="footer_copy_full">© 2024 Armenita Family Resort. All rights reserved.</p>
        <p class="footer__legal">
          <?php
            // Get current language; default to en.
            if (function_exists('pll_current_language')) {
              $lang = pll_current_language();
            } elseif (defined('ICL_LANGUAGE_CODE')) {
              $lang = ICL_LANGUAGE_CODE;
            } else {
              $lang = 'en';
            }

            $resolve_page_url = static function ($slugs, $fallback = '#') use ($lang) {
              $slugs = is_array($slugs) ? $slugs : array($slugs);

              foreach ($slugs as $slug) {
                if (!$slug) {
                  continue;
                }

                $page = get_page_by_path($slug);
                if (!$page) {
                  continue;
                }

                $page_id = (int) $page->ID;
                if (function_exists('pll_get_post') && $lang) {
                  $translated_id = pll_get_post($page_id, $lang);
                  if (!empty($translated_id)) {
                    $page_id = (int) $translated_id;
                  }
                }

                $url = get_permalink($page_id);
                if (!empty($url)) {
                  return $url;
                }
              }

              return $fallback;
            };

            $privacy_page_id = (int) get_option('wp_page_for_privacy_policy');
            if ($privacy_page_id && function_exists('pll_get_post') && $lang) {
              $translated_privacy_id = pll_get_post($privacy_page_id, $lang);
              if (!empty($translated_privacy_id)) {
                $privacy_page_id = (int) $translated_privacy_id;
              }
            }
            $privacy_url = $privacy_page_id ? get_permalink($privacy_page_id) : '';
            if (empty($privacy_url)) {
              $privacy_url = $resolve_page_url(array('privacy-policy', 'privacy_policy', 'privacy'));
            }

            $terms_page_id = 280;
            if ($terms_page_id && function_exists('pll_get_post') && $lang) {
              $translated_terms_id = pll_get_post($terms_page_id, $lang);
              if (!empty($translated_terms_id)) {
                $terms_page_id = (int) $translated_terms_id;
              }
            }
            $terms_url = $terms_page_id ? get_permalink($terms_page_id) : '';
            if (empty($terms_url)) {
              $terms_url = $resolve_page_url(array('terms-of-service', 'terms-and-conditions', 'terms'), '#');
            }
          ?>
          <a href="<?php echo esc_url($privacy_url); ?>" data-i18n="footer_privacy">Privacy Policy</a>
          <span aria-hidden="true">|</span>
          <a href="<?php echo esc_url($terms_url); ?>" data-i18n="footer_terms">Terms of Service</a>
        </p>
        <p class="footer__credit">
          <span data-i18n="footer_site_by">Site by</span> <a href="https://creativeweb.am/" target="_blank" rel="noopener noreferrer">Creative Web</a>
        </p>
      </div>
    </footer>

    <button type="button" class="scroll-top" data-scroll-top hidden aria-hidden="true" data-i18n-aria="footer_top_aria">
      <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 15l-6-6-6 6" /></svg>
    </button>

    <div
      class="contact-popup booking-popup"
      data-booking-popup
      hidden
      aria-hidden="true"
      role="dialog"
      aria-modal="true"
      aria-labelledby="booking-popup-title"
    >
      <button type="button" class="contact-popup__backdrop" data-booking-popup-close aria-label="Close"></button>
      <div class="contact-popup__inner booking-popup__inner">
        <button type="button" class="contact-popup__close" data-booking-popup-close aria-label="Close">&times;</button>
        <div class="booking-popup__panel">
          <h2 id="booking-popup-title" class="booking-popup__title" data-i18n="booking_popup_title">Book your stay</h2>
          <p class="booking-popup__lead" data-i18n="booking_popup_lead">
            Pick your dates and guest count. The estimated total in AMD appears above Pay when dates are set.
          </p>
          <form class="booking-selector booking-selector--popup-dates booking-selector--luxury" data-booking-form data-booking-nightly-usd="120">
            <label class="booking-selector__field booking-selector__field--full-row">
              <span class="booking-selector__label"><span class="booking-selector__icon">🏠</span><span data-i18n="booking_label_accommodation_type">Accommodation type</span></span>
              <select name="accommodation" data-booking-accommodation required>
                <option value=""><?php esc_html_e('Choose accommodation', 'dilijanvillas'); ?></option>
              </select>
            </label>
            <div
              class="booking-selector__field booking-selector__field--full-row booking-selector__dates-range"
              role="group"
              aria-labelledby="booking-stay-dates-label"
            >
              <div id="booking-stay-dates-label" class="booking-selector__label">
                <span class="booking-selector__icon" aria-hidden="true">📅</span>
                <span data-i18n="booking_label_stay_dates">Stay period</span>
              </div>
              <div class="booking-selector__dates-range-shell">
                <input
                  type="text"
                  class="booking-selector__range-input"
                  data-booking-date-range
                  autocomplete="off"
                  placeholder="Select stay dates"
                  data-i18n-placeholder="booking_label_stay_dates"
                />
                <span class="booking-selector__dates-sep" aria-hidden="true">–</span>
                <input
                  type="hidden"
                  name="startDate"
                  required
                  autocomplete="off"
                  data-i18n-aria="booking_label_checkin"
                />
                <input
                  type="hidden"
                  name="endDate"
                  required
                  autocomplete="off"
                  data-i18n-aria="booking_label_checkout"
                />
              </div>
            </div>
            <label class="booking-selector__field">
              <span class="booking-selector__label"><span class="booking-selector__icon">👥</span>Guests</span>
              <select name="guests">
                <option value="1">1</option>
                <option value="2" selected>2</option>
                <option value="3">3</option>
                <option value="4">4</option>
                <option value="5">5</option>
                <option value="6">6</option>
                <option value="7">7</option>
                <option value="8">8</option>
              </select>
            </label>
            <label class="booking-selector__field">
              <span class="booking-selector__label">
                <span class="booking-selector__icon" aria-hidden="true">🧸</span>
                <span data-i18n="booking_label_children">Traveling with children?</span>
              </span>
              <select name="hasChildren" data-booking-children-toggle>
                <option value="no" selected data-i18n="booking_children_opt_no">No</option>
                <option value="yes" data-i18n="booking_children_opt_yes">Yes</option>
              </select>
            </label>
            <label class="booking-selector__field is-hidden" data-booking-children-count>
              <span class="booking-selector__label">
                <span class="booking-selector__icon" aria-hidden="true">🧒</span>
                <span data-i18n="booking_label_children_count">How many children?</span>
              </span>
              <select name="childrenCount">
                <option value="1" selected>1</option>
                <option value="2">2</option>
                <option value="3">3</option>
                <option value="4">4</option>
                <option value="5">5</option>
                <option value="6">6</option>
              </select>
            </label>
            <label class="booking-selector__field booking-selector__field--full-row">
              <span class="booking-selector__label"><span class="booking-selector__icon">🙍</span><span data-i18n="booking_label_name">Name</span></span>
              <input type="text" name="fullName" required autocomplete="name" />
            </label>
            <label class="booking-selector__field">
              <span class="booking-selector__label"><span class="booking-selector__icon">📞</span><span data-i18n="booking_label_phone">Phone</span></span>
              <div class="booking-selector__phone" data-booking-phone>
                <select class="booking-selector__phone-cc" name="phoneCountry" data-booking-phone-country aria-label="Country code">
                  <option value="374" data-iso="AM" selected>+374 AM</option>
                  <option value="7" data-iso="RU">+7 RU</option>
                  <option value="995" data-iso="GE">+995 GE</option>
                  <option value="380" data-iso="UA">+380 UA</option>
                  <option value="1" data-iso="US">+1 US/CA</option>
                  <option value="44" data-iso="GB">+44 UK</option>
                  <option value="49" data-iso="DE">+49 DE</option>
                  <option value="33" data-iso="FR">+33 FR</option>
                  <option value="39" data-iso="IT">+39 IT</option>
                  <option value="34" data-iso="ES">+34 ES</option>
                  <option value="90" data-iso="TR">+90 TR</option>
                  <option value="971" data-iso="AE">+971 AE</option>
                  <option value="972" data-iso="IL">+972 IL</option>
                  <option value="98" data-iso="IR">+98 IR</option>
                  <option value="86" data-iso="CN">+86 CN</option>
                  <option value="91" data-iso="IN">+91 IN</option>
                </select>
                <input type="tel" class="booking-selector__phone-input" name="phone" required autocomplete="tel" placeholder="99 999 999" inputmode="tel" />
              </div>
            </label>
            <label class="booking-selector__field">
              <span class="booking-selector__label"><span class="booking-selector__icon">✉️</span><span data-i18n="booking_label_email">Email</span></span>
              <input type="email" name="email" autocomplete="email" />
            </label>
            <input type="text" name="website" value="" autocomplete="off" tabindex="-1" aria-hidden="true" style="position:absolute;left:-9999px;opacity:0;" />
            <p class="booking-popup__price booking-popup__price--in-form booking-selector__availability" aria-live="polite" data-booking-availability></p>
            <p class="booking-popup__price booking-popup__price--in-form booking-selector__price-summary" aria-live="polite">
              <span data-i18n="booking_price_label">Estimated total</span>:
              <strong class="booking-popup__price-amount" data-booking-price-amount>—</strong>
              <span class="booking-popup__price-currency" data-i18n="booking_price_currency">AMD</span>
            </p>
            <button type="submit" class="btn btn--primary booking-selector__submit" data-i18n="booking_submit_pay">Pay</button>
          </form>
        </div>
      </div>
    </div>
    <?php wp_footer(); ?>
  </body>
</html>

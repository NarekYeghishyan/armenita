<?php
/*
Template Name: Private Villa
*/
?>
<?php get_header(); ?>

<main>
    <div class="header-hero-wrap" data-header-hero-wrap>
      <section class="hero" id="hero" aria-label="Intro">
        <div class="hero__parallax" data-parallax-layer="bg">
          <div class="hero__static-bg" id="heroStaticBg" aria-hidden="true" data-hero-banner="<?php the_field('beground_img') ?>"></div>
          <div class="hero__video-wrap">
            <?php $villa_hero_video = trim((string) get_field('video_cot_vill')); ?>
            <video class="hero__video" autoplay muted loop playsinline preload="auto" aria-hidden="true"<?php echo $villa_hero_video === '' ? ' hidden' : ''; ?>>
              <?php if ($villa_hero_video !== '') : ?>
                <source src="<?php echo esc_url($villa_hero_video); ?>" type="<?php echo esc_attr(dilijanvillas_get_video_mime_from_url($villa_hero_video)); ?>" />
              <?php endif; ?>
            </video>
            <div class="hero__video-fallback" aria-hidden="true"></div>
          </div>
          <div class="hero__veil"></div>
          <div class="hero__glow hero__glow--1"></div>
          <div class="hero__glow hero__glow--2"></div>
        </div>

        <?php if (get_field('title_small') || get_field('title_big') || get_field('description')): ?>
          <?php
            $text_direction = strtolower(trim((string) get_field('text_direction')));
            $text_orientation = strtolower(trim((string) get_field('text_orentation')));
            $hero_content_classes = array('hero__content');

            if ($text_direction === 'bottom') {
              $hero_content_classes[] = 'hero__content--bottom';
            }

            if ($text_orientation === 'left') {
              $hero_content_classes[] = 'hero__content--left';
            } else {
              $hero_content_classes[] = 'hero__content--center';
            }
          ?>
          <div class="<?php echo esc_attr(implode(' ', $hero_content_classes)); ?>" data-parallax-layer="fg">
            <div class="container">
            <p class="hero__eyebrow reveal reveal--visible" data-reveal="">
              <span class="hero__line"></span>
              <span><?php the_field('title_small') ?></span>
            </p>
            <h1 class="hero__title reveal reveal--visible" data-reveal=""><?php the_field('title_big') ?></h1>
            <p class="hero__subtitle reveal reveal--visible" data-reveal=""><?php the_field('description') ?></p>
            </div>
          </div>
        <?php endif; ?>

        <button type="button" class="hero__scroll" aria-label="Scroll down" data-scroll-down>
          <span class="hero__scroll-icon"></span>
        </button>
      </section>
      </div>
      <section class="section stay-unit" id="stay-unit-details">
        <div class="container">
          <article class="stay-unit__card reveal" data-reveal>
            <div class="stay-unit__heading">
              <h2 class="section__title" ><?php the_title() ?></h2>
              <div class="section__lead"><?php echo wp_kses_post((string) get_field('description_cottage')); ?></div>
            </div>
            <div class="stay-unit__media stay-slider" data-stay-slider>
              <div class="stay-slider__track" data-stay-slider-track>
                <?php
                $gallery_items = get_field('gallery_block');
                $slide_index = 0;

                if (is_array($gallery_items)) :
                  foreach ($gallery_items as $item) :
                    $media_url = '';
                    $media_mime = '';
                    $media_source = $item;

                    if (is_array($item)) {
                      if (!empty($item['video_or_image'])) {
                        $media_source = $item['video_or_image'];
                      } elseif (!empty($item['image_or_video'])) {
                        $media_source = $item['image_or_video'];
                      } elseif (!empty($item['url'])) {
                        $media_source = $item['url'];
                      }
                    }

                    if (is_array($media_source)) {
                      if (!empty($media_source['url'])) {
                        $media_url = (string) $media_source['url'];
                      } elseif (!empty($media_source['ID'])) {
                        $attachment_id = (int) $media_source['ID'];
                        $media_url = (string) wp_get_attachment_url($attachment_id);
                        $media_mime = (string) get_post_mime_type($attachment_id);
                      }

                      if ($media_mime === '' && !empty($media_source['mime_type'])) {
                        $media_mime = (string) $media_source['mime_type'];
                      }
                    } elseif (is_numeric($media_source)) {
                      $attachment_id = (int) $media_source;
                      $media_url = (string) wp_get_attachment_url($attachment_id);
                      $media_mime = (string) get_post_mime_type($attachment_id);
                    } elseif (is_string($media_source)) {
                      $media_url = trim($media_source);
                    }

                    if ($media_url === '') {
                      continue;
                    }

                    $ext = strtolower((string) pathinfo((string) parse_url($media_url, PHP_URL_PATH), PATHINFO_EXTENSION));
                    if ($media_mime === '') {
                      $filetype = wp_check_filetype($media_url);
                      $media_mime = !empty($filetype['type']) ? (string) $filetype['type'] : '';
                    }
                    $is_video = strpos($media_mime, 'video/') === 0 || in_array($ext, ['mp4', 'webm', 'ogg', 'mov', 'm4v'], true);
                    ?>
                    <div class="stay-slider__slide<?php echo $slide_index === 0 ? ' is-active' : ''; ?>" data-stay-slide>
                      <?php if ($is_video): ?>
                        <video class="stay-unit__video" muted loop playsinline preload="metadata" data-stay-slide-video>
                          <source src="<?php echo esc_url($media_url); ?>" type="<?php echo esc_attr($media_mime !== '' ? $media_mime : 'video/mp4'); ?>" />
                        </video>
                      <?php else: ?>
                        <img class="stay-unit__image" src="<?php echo esc_url($media_url); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" loading="lazy" />
                      <?php endif; ?>
                    </div>
                    <?php
                    $slide_index++;
                  endforeach;
                endif;
                ?>
              </div>
              <button type="button" class="stay-slider__nav stay-slider__nav--prev" data-stay-prev aria-label="Previous">‹</button>
              <button type="button" class="stay-slider__nav stay-slider__nav--next" data-stay-next aria-label="Next">›</button>
              <div class="stay-slider__dots" data-stay-dots></div>
            </div>
            <div class="stay-feature__body stay-unit__content">
              <?php
              $has_icon_text_items = static function ($items) {
                if (!is_array($items)) {
                  return false;
                }

                foreach ($items as $item) {
                  $item_text = '';
                  if (is_array($item)) {
                    $item_text = isset($item['text']) ? trim((string) $item['text']) : '';
                  } elseif (is_string($item)) {
                    $item_text = trim($item);
                  }

                  if ($item_text !== '') {
                    return true;
                  }
                }

                return false;
              };

              $general_points = get_field('general');
              if ($has_icon_text_items($general_points)) :
              ?>
              <ul class="stay-feature__chips">
                <?php
                foreach ($general_points as $point) :
                  $point_text = '';
                  $point_icon = '';

                  if (is_array($point)) {
                    $point_text = isset($point['text']) ? trim((string) $point['text']) : '';
                    $point_icon = $point['icon'] ?? '';
                  }

                  if ($point_text === '') {
                    continue;
                  }

                  $icon_url = '';
                  $icon_text = '';

                  if (is_array($point_icon)) {
                    if (!empty($point_icon['url'])) {
                      $icon_url = (string) $point_icon['url'];
                    } elseif (!empty($point_icon['ID'])) {
                      $icon_url = (string) wp_get_attachment_url((int) $point_icon['ID']);
                    }
                  } elseif (is_numeric($point_icon)) {
                    $icon_url = (string) wp_get_attachment_url((int) $point_icon);
                  } elseif (is_string($point_icon)) {
                    $candidate = trim($point_icon);
                    if ($candidate !== '' && preg_match('/^https?:\/\//i', $candidate)) {
                      $icon_url = $candidate;
                    } else {
                      $icon_text = $candidate;
                    }
                  }
                  ?>
                  <li>
                    <?php if ($icon_url !== '') : ?>
                      <img src="<?php echo esc_url($icon_url); ?>" alt="" loading="lazy" width="16" height="16" />
                    <?php elseif ($icon_text !== '') : ?>
                      <span aria-hidden="true"><?php echo esc_html($icon_text); ?></span>
                    <?php endif; ?>
                    <?php echo esc_html($point_text); ?>
                  </li>
                  <?php
                endforeach;
                ?>
              </ul>
              <?php endif; ?>
              <?php
              $render_icon_text_items = static function ($items) {
                if (!is_array($items)) {
                  return;
                }

                foreach ($items as $item) {
                  $item_text = '';
                  $item_icon = '';

                  if (is_array($item)) {
                    $item_text = isset($item['text']) ? trim((string) $item['text']) : '';
                    $item_icon = $item['icon'] ?? '';
                  } elseif (is_string($item)) {
                    $item_text = trim($item);
                  }

                  if ($item_text === '') {
                    continue;
                  }

                  $icon_url = '';
                  $icon_text = '';

                  if (is_array($item_icon)) {
                    if (!empty($item_icon['url'])) {
                      $icon_url = (string) $item_icon['url'];
                    } elseif (!empty($item_icon['ID'])) {
                      $icon_url = (string) wp_get_attachment_url((int) $item_icon['ID']);
                    }
                  } elseif (is_numeric($item_icon)) {
                    $icon_url = (string) wp_get_attachment_url((int) $item_icon);
                  } elseif (is_string($item_icon)) {
                    $candidate = trim($item_icon);
                    if ($candidate !== '' && preg_match('/^https?:\/\//i', $candidate)) {
                      $icon_url = $candidate;
                    } else {
                      $icon_text = $candidate;
                    }
                  }
                  ?>
                  <li>
                    <?php if ($icon_url !== '') : ?>
                      <img src="<?php echo esc_url($icon_url); ?>" alt="" loading="lazy" width="16" height="16" />
                    <?php elseif ($icon_text !== '') : ?>
                      <span aria-hidden="true"><?php echo esc_html($icon_text); ?></span>
                    <?php endif; ?>
                    <?php echo esc_html($item_text); ?>
                  </li>
                  <?php
                }
              };

              $whats_inside_items = get_field('whats_inside');
              $amenities_items = get_field('amenits');
              if (!is_array($amenities_items)) {
                $amenities_items = get_field('amenities');
              }
              $room_features_items = get_field('root_features');
              if (!is_array($room_features_items)) {
                $room_features_items = get_field('room_features');
              }
              $room_types_items = get_field('room_types');

              $has_whats_inside = $has_icon_text_items($whats_inside_items);
              $has_amenities = $has_icon_text_items($amenities_items);
              $has_room_features = $has_icon_text_items($room_features_items);
              $has_room_types = $has_icon_text_items($room_types_items);
              $has_more_points = $has_whats_inside || $has_amenities || $has_room_features || $has_room_types;
              ?>
              <?php if ($has_more_points) : ?>
              <div class="stay-feature__more">
                <?php if ($has_whats_inside) : ?>
                  <h4 data-i18n="stay_section_whats_inside">What's inside</h4>
                  <ul class="stay-feature__amenities">
                    <?php $render_icon_text_items($whats_inside_items); ?>
                  </ul>
                <?php endif; ?>

                <?php if ($has_amenities) : ?>
                  <h4 data-i18n="stay_section_amenities">Amenities</h4>
                  <ul class="stay-feature__amenities">
                    <?php $render_icon_text_items($amenities_items); ?>
                  </ul>
                <?php endif; ?>

                <?php if ($has_room_features) : ?>
                  <h4 data-i18n="stay_section_room_features">Room features</h4>
                  <ul class="stay-feature__amenities">
                    <?php $render_icon_text_items($room_features_items); ?>
                  </ul>
                <?php endif; ?>

                <?php if ($has_room_types) : ?>
                  <h4 data-i18n="stay_section_room_types">Room types</h4>
                  <ul class="stay-feature__amenities">
                    <?php $render_icon_text_items($room_types_items); ?>
                  </ul>
                <?php endif; ?>
              </div>
              <?php endif; ?>
            </div>
            <div class="stay-unit__actions">
              <form class="booking-selector booking-selector--popup-dates booking-selector--luxury" data-booking-form data-booking-nightly-usd="180">
                <input type="hidden" name="accommodation" data-booking-accommodation value="<?php echo esc_attr((string) get_the_ID()); ?>" />
                <div
                  class="booking-selector__field booking-selector__field--full-row booking-selector__dates-range"
                  role="group"
                  aria-labelledby="villa-booking-stay-dates-label"
                >
                  <div id="villa-booking-stay-dates-label" class="booking-selector__label">
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
                    <input type="hidden" name="startDate" required autocomplete="off" data-i18n-aria="booking_label_checkin" />
                    <input type="hidden" name="endDate" required autocomplete="off" data-i18n-aria="booking_label_checkout" />
                  </div>
                </div>
                <label class="booking-selector__field">
                  <span class="booking-selector__label"><span class="booking-selector__icon">👥</span><span data-i18n="booking_label_guests">Guests</span></span>
                  <select name="guests">
                    <option value="1">1</option>
                    <option value="2" selected>2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5</option>
                    <option value="6">6</option>
                    <option value="7">7</option>
                    <option value="8">8</option>
                    <option value="9">9</option>
                    <option value="10">10</option>
                    <option value="11">11</option>
                    <option value="12">12</option>
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
          </article>
        </div>
      </section>
      <?php
        $ratings_lang = function_exists('pll_current_language') ? pll_current_language() : '';
        $ratings_source_page_id = (int) get_option('page_on_front');
        if ($ratings_source_page_id && function_exists('pll_get_post') && !empty($ratings_lang)) {
          $translated_ratings_page_id = pll_get_post($ratings_source_page_id, $ratings_lang);
          if (!empty($translated_ratings_page_id)) {
            $ratings_source_page_id = (int) $translated_ratings_page_id;
          }
        }
        set_query_var('ratings_source_page_id', $ratings_source_page_id);
      ?>
      <?php get_template_part('template-parts/sections/ratings'); ?>
      <?php get_template_part('template-parts/sections/contact'); ?>
    </main>

<?php get_footer(); ?>
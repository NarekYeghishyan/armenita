<?php
/*
Template Name: Book your stay
Description: This part is optional, but helpful for describing the Post Template
*/
?>
<?php get_header(); ?>
    <main>
      <div class="header-hero-wrap" data-header-hero-wrap>
        <section class="hero about-cinematic about-cinematic--page-hero" id="hero" aria-label="Intro">
          <?php
            $hero_background_img = trim((string) get_field('beground_img'));
            $hero_video_url = trim((string) get_field('video_home'));
          ?>
          <div class="hero__parallax" data-parallax-layer="bg">
            <div class="hero__static-bg" id="heroStaticBg" aria-hidden="true" data-hero-banner="<?php echo esc_url($hero_background_img); ?>"></div>
            <div class="hero__video-wrap">
              <?php if ($hero_video_url !== '') : ?>
                <video class="hero__video" autoplay muted loop playsinline preload="auto" aria-hidden="true" <?php echo $hero_background_img !== '' ? 'poster="' . esc_url($hero_background_img) . '"' : ''; ?><?php echo $hero_video_url === '' ? ' hidden' : ''; ?>>
                  <?php if ($hero_video_url !== '') : ?>
                    <source src="<?php echo esc_url($hero_video_url); ?>" type="<?php echo esc_attr(dilijanvillas_get_video_mime_from_url($hero_video_url)); ?>" />
                  <?php endif; ?>
                </video>
              <?php else : ?>
                <video class="hero__video is-hidden" autoplay muted loop playsinline preload="auto" aria-hidden="true">
                  <source src="" type="video/mp4" />
                </video>
              <?php endif; ?>
              <div class="hero__video-fallback" aria-hidden="true"></div>
            </div>
            <div class="hero__veil"></div>
            <div class="hero__glow hero__glow--1"></div>
            <div class="hero__glow hero__glow--2"></div>
          </div>
          <?php
            $stay_text_direction = strtolower(trim((string) get_field('text_direction')));
            $stay_text_orientation = strtolower(trim((string) get_field('text_orentation')));
            $stay_content_classes = array('container', 'about-cinematic__content');

            $stay_is_bottom = $stay_text_direction === 'bottom'
              || $stay_text_direction === 'buttom'
              || strpos($stay_text_direction, 'bottom') !== false
              || strpos($stay_text_direction, 'buttom') !== false;

            $stay_is_center = $stay_text_orientation === 'center'
              || strpos($stay_text_orientation, 'center') !== false;

            $stay_is_left = $stay_text_orientation === 'left'
              || strpos($stay_text_orientation, 'left') !== false;

            if ($stay_is_bottom) {
              $stay_content_classes[] = 'about-cinematic__content--bottom';
            }

            if ($stay_is_center) {
              $stay_content_classes[] = 'about-cinematic__content--center';
            } elseif ($stay_is_left) {
              $stay_content_classes[] = 'about-cinematic__content--left';
            }
          ?>
          <div class="<?php echo esc_attr(implode(' ', $stay_content_classes)); ?>">
            <p class="hero__eyebrow"><span class="hero__line"></span><span ><?php the_field('title_small') ?></span></p>
            <h1 class="about-cinematic__title"><?php the_field('title_big') ?></h1>
            <p class="about-cinematic__lead" >
              <?php the_field('description') ?>
            </p>
            <div class="hero__actions">
              <?php foreach (get_field('buttons') as $key => $button): ?>
                <a class="btn btn--<?php echo $key === 0 ? 'primary' : 'ghost' ?>" href="<?php echo $button['link_of_button'] ?>"><?php echo $button['text_of_button'] ?></a>
              <?php endforeach; ?>
            </div>
          </div>
            <button type="button" class="hero__scroll" aria-label="Scroll down" data-scroll-down>
              <span class="hero__scroll-icon"></span>
            </button>
        </section>
      </div>
      
      <section class="section stay-showcase">
        <div class="container">
          <header class="stay-showcase__head reveal" data-reveal>
            <p class="hero__eyebrow"><span class="hero__line"></span><span data-i18n="stay_heading">Villas</span></p>
            <h2 class="section__title"><?php the_field('title_cot') ?></h2>
            <p class="stay-showcase__lead"><?php the_field('description_cot') ?></p>
          </header>

          <?php
          $stay_has_icon_text_items = static function ($items) {
            if (!is_array($items)) {
              return false;
            }
            foreach ($items as $item) {
              $text = '';
              if (is_array($item)) {
                $text = isset($item['text']) ? trim((string) $item['text']) : '';
              } elseif (is_string($item)) {
                $text = trim($item);
              }
              if ($text !== '') {
                return true;
              }
            }
            return false;
          };

          $stay_render_icon_text_items = static function ($items) {
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

          $stay_current_lang = function_exists('pll_current_language') ? pll_current_language('slug') : '';
          $cottage_query_args = array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => array(
              'menu_order' => 'ASC',
              'title' => 'ASC',
            ),
            'order' => 'ASC',
            'meta_key' => '_wp_page_template',
            'meta_value' => 'cottage.php',
            'suppress_filters' => false,
          );
          if ($stay_current_lang) {
            $cottage_query_args['lang'] = $stay_current_lang;
          }
          $cottage_pages = get_posts($cottage_query_args);
          $cottage_page_ids = array();
          $seen_cottage_ids = array();
          foreach ($cottage_pages as $cottage_page_row) {
            $resolved_page_id = (int) $cottage_page_row->ID;
            if ($resolved_page_id <= 0) {
              continue;
            }

            if (function_exists('pll_get_post') && $stay_current_lang) {
              $translated_id = pll_get_post($resolved_page_id, $stay_current_lang);
              if (!empty($translated_id)) {
                $resolved_page_id = (int) $translated_id;
              }
            }

            if ($resolved_page_id <= 0 || isset($seen_cottage_ids[$resolved_page_id])) {
              continue;
            }

            if ((string) get_page_template_slug($resolved_page_id) !== 'cottage.php') {
              continue;
            }

            $cottage_page_ids[] = $resolved_page_id;
            $seen_cottage_ids[$resolved_page_id] = true;
          }
          ?>
          <?php foreach ($cottage_page_ids as $cottage_index => $cottage_page_id) : ?>
            <?php
            $card_classes = 'stay-feature reveal';
            if ($cottage_index % 2 === 1) {
              $card_classes .= ' stay-feature--reverse';
            }

            $cottage_description = wp_strip_all_tags((string) get_field('description_cottage', $cottage_page_id));
            $cottage_general = get_field('general', $cottage_page_id);
            $cottage_inside = get_field('whats_inside', $cottage_page_id);
            $cottage_amenities = get_field('amenits', $cottage_page_id);
            if (!is_array($cottage_amenities)) {
              $cottage_amenities = get_field('amenities', $cottage_page_id);
            }
            $cottage_features = get_field('root_features', $cottage_page_id);
            if (!is_array($cottage_features)) {
              $cottage_features = get_field('room_features', $cottage_page_id);
            }
            $cottage_types = get_field('room_types', $cottage_page_id);
            $cottage_has_more = $stay_has_icon_text_items($cottage_inside)
              || $stay_has_icon_text_items($cottage_amenities)
              || $stay_has_icon_text_items($cottage_features)
              || $stay_has_icon_text_items($cottage_types);
            $cottage_gallery = get_field('gallery_block', $cottage_page_id);
            ?>
            <article class="<?php echo esc_attr($card_classes); ?>" data-reveal>
              <div class="stay-feature__media stay-slider" data-stay-slider>
                <div class="stay-slider__track" data-stay-slider-track>
                  <?php
                  $slide_index = 0;
                  if (is_array($cottage_gallery)) :
                    foreach ($cottage_gallery as $slide_item) :
                      $media_source = $slide_item;
                      if (is_array($slide_item) && isset($slide_item['video_or_image'])) {
                        $media_source = $slide_item['video_or_image'];
                      } elseif (is_array($slide_item) && isset($slide_item['image_or_video'])) {
                        $media_source = $slide_item['image_or_video'];
                      }

                      $media_url = '';
                      $media_mime = '';
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
                      $is_video = strpos($media_mime, 'video/') === 0 || in_array($ext, array('mp4', 'webm', 'ogg', 'mov', 'm4v'), true);
                      ?>
                      <div class="stay-slider__slide<?php echo $slide_index === 0 ? ' is-active' : ''; ?>" data-stay-slide>
                        <?php if ($is_video) : ?>
                          <video muted loop playsinline preload="metadata" data-stay-slide-video>
                            <source src="<?php echo esc_url($media_url); ?>" type="<?php echo esc_attr($media_mime !== '' ? $media_mime : 'video/mp4'); ?>" />
                          </video>
                        <?php else : ?>
                          <img src="<?php echo esc_url($media_url); ?>" alt="<?php echo esc_attr(get_the_title($cottage_page_id)); ?>" loading="lazy" />
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
                <span class="stay-feature__badge"><?php echo esc_html(get_the_title($cottage_page_id)); ?></span>
              </div>
              <div class="stay-feature__body">
                <h3><?php echo esc_html(get_the_title($cottage_page_id)); ?></h3>
                <?php if ($cottage_description !== '') : ?>
                  <p><?php echo esc_html($cottage_description); ?></p>
                <?php endif; ?>

                <?php if ($stay_has_icon_text_items($cottage_general)) : ?>
                  <ul class="stay-feature__chips">
                    <?php $stay_render_icon_text_items($cottage_general); ?>
                  </ul>
                <?php endif; ?>

                <?php if ($cottage_has_more) : ?>
                  <div class="stay-feature__more">
                    <?php if ($stay_has_icon_text_items($cottage_inside)) : ?>
                      <h4 data-i18n="stay_section_whats_inside">What's inside</h4>
                      <ul class="stay-feature__amenities">
                        <?php $stay_render_icon_text_items($cottage_inside); ?>
                      </ul>
                    <?php endif; ?>
                    <?php if ($stay_has_icon_text_items($cottage_amenities)) : ?>
                      <h4 data-i18n="stay_section_amenities">Amenities</h4>
                      <ul class="stay-feature__amenities">
                        <?php $stay_render_icon_text_items($cottage_amenities); ?>
                      </ul>
                    <?php endif; ?>
                    <?php if ($stay_has_icon_text_items($cottage_features)) : ?>
                      <h4 data-i18n="stay_section_room_features">Room features</h4>
                      <ul class="stay-feature__amenities">
                        <?php $stay_render_icon_text_items($cottage_features); ?>
                      </ul>
                    <?php endif; ?>
                    <?php if ($stay_has_icon_text_items($cottage_types)) : ?>
                      <h4 data-i18n="stay_section_room_types">Room types</h4>
                      <ul class="stay-feature__amenities">
                        <?php $stay_render_icon_text_items($cottage_types); ?>
                      </ul>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>

                <div class="stay-feature__actions">
                  <button type="button" class="btn btn--primary btn--book-now" data-booking-popup-open data-i18n="about_book_now">Book now</button>
                  <a class="btn btn--ghost" href="<?php echo esc_url(get_permalink($cottage_page_id)); ?>">See more</a>
                </div>
              </div>
            </article>
          <?php endforeach; ?>

          <header class="stay-showcase__head reveal" data-reveal>
            <p class="hero__eyebrow"><span class="hero__line"></span><span>Private villas</span></p>
            <h2 class="section__title"><?php the_field('title_willa') ?></h2>
            <p class="stay-showcase__lead"><?php the_field('description_villa') ?></p>
          </header>

          <?php
          $villa_query_args = array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => array(
              'menu_order' => 'ASC',
              'title' => 'ASC',
            ),
            'order' => 'ASC',
            'meta_key' => '_wp_page_template',
            'meta_value' => 'private-willa.php',
            'suppress_filters' => false,
          );
          if ($stay_current_lang) {
            $villa_query_args['lang'] = $stay_current_lang;
          }
          $villa_pages = get_posts($villa_query_args);
          $villa_page_ids = array();
          $seen_villa_ids = array();
          foreach ($villa_pages as $villa_page_row) {
            $resolved_page_id = (int) $villa_page_row->ID;
            if ($resolved_page_id <= 0) {
              continue;
            }

            if (function_exists('pll_get_post') && $stay_current_lang) {
              $translated_id = pll_get_post($resolved_page_id, $stay_current_lang);
              if (!empty($translated_id)) {
                $resolved_page_id = (int) $translated_id;
              }
            }

            if ($resolved_page_id <= 0 || isset($seen_villa_ids[$resolved_page_id])) {
              continue;
            }

            if ((string) get_page_template_slug($resolved_page_id) !== 'private-willa.php') {
              continue;
            }

            $villa_page_ids[] = $resolved_page_id;
            $seen_villa_ids[$resolved_page_id] = true;
          }
          ?>
          <?php foreach ($villa_page_ids as $villa_index => $villa_page_id) : ?>
            <?php
            $card_classes = 'stay-feature reveal';
            if ($villa_index % 2 === 1) {
              $card_classes .= ' stay-feature--reverse';
            }

            $villa_description = wp_strip_all_tags((string) get_field('description_cottage', $villa_page_id));
            $villa_general = get_field('general', $villa_page_id);
            $villa_inside = get_field('whats_inside', $villa_page_id);
            $villa_amenities = get_field('amenits', $villa_page_id);
            if (!is_array($villa_amenities)) {
              $villa_amenities = get_field('amenities', $villa_page_id);
            }
            $villa_features = get_field('root_features', $villa_page_id);
            if (!is_array($villa_features)) {
              $villa_features = get_field('room_features', $villa_page_id);
            }
            $villa_types = get_field('room_types', $villa_page_id);
            $villa_has_more = $stay_has_icon_text_items($villa_inside)
              || $stay_has_icon_text_items($villa_amenities)
              || $stay_has_icon_text_items($villa_features)
              || $stay_has_icon_text_items($villa_types);
            $villa_gallery = get_field('gallery_block', $villa_page_id);
            ?>
            <article class="<?php echo esc_attr($card_classes); ?>" data-reveal>
              <div class="stay-feature__media stay-slider" data-stay-slider>
                <div class="stay-slider__track" data-stay-slider-track>
                  <?php
                  $slide_index = 0;
                  if (is_array($villa_gallery)) :
                    foreach ($villa_gallery as $slide_item) :
                      $media_source = $slide_item;
                      if (is_array($slide_item) && isset($slide_item['video_or_image'])) {
                        $media_source = $slide_item['video_or_image'];
                      } elseif (is_array($slide_item) && isset($slide_item['image_or_video'])) {
                        $media_source = $slide_item['image_or_video'];
                      }

                      $media_url = '';
                      $media_mime = '';
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
                      $is_video = strpos($media_mime, 'video/') === 0 || in_array($ext, array('mp4', 'webm', 'ogg', 'mov', 'm4v'), true);
                      ?>
                      <div class="stay-slider__slide<?php echo $slide_index === 0 ? ' is-active' : ''; ?>" data-stay-slide>
                        <?php if ($is_video) : ?>
                          <video muted loop playsinline preload="metadata" data-stay-slide-video>
                            <source src="<?php echo esc_url($media_url); ?>" type="<?php echo esc_attr($media_mime !== '' ? $media_mime : 'video/mp4'); ?>" />
                          </video>
                        <?php else : ?>
                          <img src="<?php echo esc_url($media_url); ?>" alt="<?php echo esc_attr(get_the_title($villa_page_id)); ?>" loading="lazy" />
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
                <span class="stay-feature__badge"><?php echo esc_html(get_the_title($villa_page_id)); ?></span>
              </div>
              <div class="stay-feature__body">
                <h3><?php echo esc_html(get_the_title($villa_page_id)); ?></h3>
                <?php if ($villa_description !== '') : ?>
                  <p><?php echo esc_html($villa_description); ?></p>
                <?php endif; ?>

                <?php if ($stay_has_icon_text_items($villa_general)) : ?>
                  <ul class="stay-feature__chips">
                    <?php $stay_render_icon_text_items($villa_general); ?>
                  </ul>
                <?php endif; ?>

                <?php if ($villa_has_more) : ?>
                  <div class="stay-feature__more">
                    <?php if ($stay_has_icon_text_items($villa_inside)) : ?>
                      <h4 data-i18n="stay_section_whats_inside">What's inside</h4>
                      <ul class="stay-feature__amenities">
                        <?php $stay_render_icon_text_items($villa_inside); ?>
                      </ul>
                    <?php endif; ?>
                    <?php if ($stay_has_icon_text_items($villa_amenities)) : ?>
                      <h4 data-i18n="stay_section_amenities">Amenities</h4>
                      <ul class="stay-feature__amenities">
                        <?php $stay_render_icon_text_items($villa_amenities); ?>
                      </ul>
                    <?php endif; ?>
                    <?php if ($stay_has_icon_text_items($villa_features)) : ?>
                      <h4 data-i18n="stay_section_room_features">Room features</h4>
                      <ul class="stay-feature__amenities">
                        <?php $stay_render_icon_text_items($villa_features); ?>
                      </ul>
                    <?php endif; ?>
                    <?php if ($stay_has_icon_text_items($villa_types)) : ?>
                      <h4 data-i18n="stay_section_room_types">Room types</h4>
                      <ul class="stay-feature__amenities">
                        <?php $stay_render_icon_text_items($villa_types); ?>
                      </ul>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>

                <div class="stay-feature__actions">
                  <button type="button" class="btn btn--primary btn--book-now" data-booking-popup-open data-i18n="about_book_now">Book now</button>
                  <a class="btn btn--ghost" href="<?php echo esc_url(get_permalink($villa_page_id)); ?>">See more</a>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
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

      <section class="section section--contact" id="contact">
        <div class="container">
          <div class="contact">
            <div class="contact__info-card reveal" data-reveal>
              <div class="contact__text">
                <h2 class="section__title reveal" data-reveal data-i18n="contact_title">Կապ</h2>
                <p class="contact__intro reveal" data-reveal data-i18n="contact_intro">Զանգահարեք կամ գրեք Instagram-ում։</p>
                <address class="contact__block contact__block--card reveal" data-reveal>
                  <p class="contact__row">
                    <span class="contact__icon" aria-hidden="true">
                      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    </span>
                    <span data-i18n="contact_address">Armenia, Dilijan, Armenia, 3901</span>
                  </p>
                  <p class="contact__row contact__row--phone">
                    <span class="contact__icon" aria-hidden="true">
                      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    </span>
                    <a class="contact__link" href="tel:+37494605665"><span data-i18n="contact_phone">094 605665</span></a>
                    <a class="header__dropdown-whatsapp contact__wa-inline" href="https://wa.me/37494605665" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.52 3.48A11.86 11.86 0 0 0 12.06 0C5.5 0 .16 5.33.16 11.9c0 2.1.55 4.16 1.6 5.98L0 24l6.3-1.64a11.8 11.8 0 0 0 5.76 1.48h.01c6.56 0 11.9-5.34 11.9-11.9 0-3.18-1.24-6.17-3.45-8.46zM12.07 21.8h-.01a9.83 9.83 0 0 1-5.01-1.37l-.36-.22-3.74.97 1-3.65-.24-.38a9.84 9.84 0 0 1-1.5-5.25c0-5.44 4.42-9.86 9.86-9.86 2.63 0 5.1 1.02 6.96 2.9a9.78 9.78 0 0 1 2.88 6.95c0 5.44-4.43 9.87-9.84 9.87zm5.4-7.4c-.3-.15-1.78-.88-2.06-.98-.28-.1-.48-.15-.68.15-.2.3-.78.98-.95 1.18-.18.2-.35.23-.65.08-.3-.15-1.25-.46-2.38-1.47-.88-.78-1.47-1.75-1.65-2.05-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.53.15-.18.2-.3.3-.5.1-.2.05-.38-.03-.53-.08-.15-.68-1.64-.94-2.25-.25-.6-.5-.5-.68-.51h-.58c-.2 0-.53.08-.8.38-.28.3-1.05 1.03-1.05 2.5s1.08 2.9 1.23 3.1c.15.2 2.12 3.24 5.14 4.54.72.31 1.28.5 1.72.64.72.23 1.37.2 1.88.12.58-.09 1.78-.73 2.03-1.43.25-.7.25-1.3.18-1.43-.07-.13-.27-.2-.57-.35z"/></svg>
                      <span>WhatsApp</span>
                    </a>
                  </p>
                  <p class="contact__row">
                    <span class="contact__icon" aria-hidden="true">
                      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
                    </span>
                    <a class="contact__link" href="https://www.instagram.com/dilijan_villas/" target="_blank" rel="noopener noreferrer"><span data-i18n="contact_instagram">Instagram — @dilijan_villas</span></a>
                  </p>
                  <p class="contact__row">
                    <span class="contact__icon" aria-hidden="true">
                      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3V2z"/></svg>
                    </span>
                    <a class="contact__link" href="https://www.facebook.com/profile.php?id=61559238815878" target="_blank" rel="noopener noreferrer"><span data-i18n="contact_facebook">Facebook</span></a>
                  </p>
                </address>
              </div>
              <div class="contact__quote-inline reveal" data-reveal>
                <figure class="contact__quote contact__quote--inline">
                  <p class="contact__quote-brand">Armenita Family Resort</p>
                  <blockquote class="contact__tagline">
                    <span data-i18n="contact_tagline">Էկո հանգիստ Դիլիջանի կենտրոնում։</span>
                  </blockquote>
                </figure>
              </div>
            </div>
            <div class="contact__map-card reveal" data-reveal>
              <div class="contact__map-head">
                <span class="contact__map-kicker" data-i18n="contact_map_title">Գտնվելու վայր</span>
              </div>
              <div class="contact__map-wrap">
                <iframe
                  class="contact__map"
                  loading="lazy"
                  referrerpolicy="no-referrer-when-downgrade"
                  allowfullscreen
                  title="Google Maps"
                  data-i18n-aria="contact_map_aria"
                  src="https://maps.google.com/maps?width=100%25&amp;height=600&amp;hl=hy&amp;q=40.73973875595698%2C%2044.851870306840816&amp;t=&amp;z=16&amp;ie=UTF8&amp;iwloc=B&amp;output=embed"
                ></iframe>
              </div>
              <a class="contact__map-btn" href="https://www.google.com/maps/search/?api=1&amp;query=40.73973875595698%2C44.851870306840816" target="_blank" rel="noopener noreferrer">
                <span data-i18n="contact_map_link">Բացել Google Maps-ում</span>
                <svg class="contact__map-btn-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
              </a>
            </div>
          </div>
        </div>
      </section>
    </main>
    <?php get_footer(); ?>  
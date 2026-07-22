<?php
/*
Template Name: Events and activates
Description: Events and activates page
*/
?>
<?php get_header(); ?>
    <main>
      <div class="header-hero-wrap" data-header-hero-wrap>
      <section class="about-cinematic about-cinematic--page-hero" id="hero" aria-label="Events">
        <?php
          $resolve_media_url = static function ($field_value) {
            if (is_array($field_value)) {
              if (!empty($field_value['url'])) {
                return trim((string) $field_value['url']);
              }
              if (!empty($field_value['ID'])) {
                $from_id = wp_get_attachment_url((int) $field_value['ID']);
                return $from_id ? trim((string) $from_id) : '';
              }
              return '';
            }

            if (is_numeric($field_value)) {
              $from_id = wp_get_attachment_url((int) $field_value);
              return $from_id ? trim((string) $from_id) : '';
            }

            return is_string($field_value) ? trim($field_value) : '';
          };

          $hero_video_url = $resolve_media_url(get_field('video_events'));
          $hero_background_img = $resolve_media_url(get_field('beground_img'));
        ?>
        <?php if ($hero_background_img !== '') : ?>
          <div class="about-cinematic__bg" data-parallax-bg aria-hidden="true" style="--parallax-speed: 0.18; background-image: url('<?php echo esc_url($hero_background_img); ?>');"></div>
        <?php endif; ?>
        <?php if ($hero_video_url !== '') : ?>
          <video class="about-cinematic__video" autoplay muted loop playsinline preload="auto" aria-hidden="true" <?php echo $hero_background_img !== '' ? 'poster="' . esc_url($hero_background_img) . '"' : ''; ?>>
            <source src="<?php echo esc_url($hero_video_url); ?>" type="<?php echo esc_attr(dilijanvillas_get_video_mime_from_url($hero_video_url)); ?>" />
          </video>
        <?php endif; ?>
        <div class="about-cinematic__veil"></div>
        <?php
          $events_text_direction = strtolower(trim((string) get_field('text_direction')));
          $events_text_orientation = strtolower(trim((string) get_field('text_orentation')));
          $events_content_classes = array('container', 'about-cinematic__content');

          if ($events_text_direction === 'bottom') {
            $events_content_classes[] = 'about-cinematic__content--bottom';
          }

          if ($events_text_orientation === 'left') {
            $events_content_classes[] = 'about-cinematic__content--left';
          } elseif ($events_text_orientation === 'center') {
            $events_content_classes[] = 'about-cinematic__content--center';
          } elseif ($events_text_direction === 'bottom') {
            // When only vertical direction is set to bottom, keep horizontal alignment on left.
            $events_content_classes[] = 'about-cinematic__content--left';
          }
        ?>
        <div class="<?php echo esc_attr(implode(' ', $events_content_classes)); ?>">
          <p class="hero__eyebrow"><span class="hero__line"></span><span><?php the_field('title_small') ?></span></p>
          <h1 class="about-cinematic__title"><?php the_field('title_big') ?></h1>
          <p class="about-cinematic__lead">
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

      <section class="section events-page" id="tours">
        <div class="container">
          <h2 class="section__title section__title--center reveal" data-reveal><?php the_field('title_toure') ?></h2>
          <p class="events-page__intro reveal" data-reveal><?php the_field('description_toure') ?></p>
          <div class="events-page__grid">
            <?php foreach (get_field('tours') as $key => $tour): ?>
              <article class="events-page__card reveal" data-reveal>
              <div class="events-page__card-preview">
                <div class="events-page__card-body events-page__card-body--stack">
                  <?php
                    $tour_title = isset($tour['title']) ? trim((string) $tour['title']) : '';
                    if ($tour_title !== '') :
                  ?>
                    <h3 class="events-page__card-title"><?php echo esc_html($tour_title); ?></h3>
                  <?php endif; ?>
                  <?php echo wp_kses_post(isset($tour['description']) ? $tour['description'] : ''); ?>
                  <a class="header__dropdown-whatsapp" href="<?php echo esc_url(isset($tour['whatsapp_number']) ? $tour['whatsapp_number'] : ''); ?>" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.52 3.48A11.86 11.86 0 0 0 12.06 0C5.5 0 .16 5.33.16 11.9c0 2.1.55 4.16 1.6 5.98L0 24l6.3-1.64a11.8 11.8 0 0 0 5.76 1.48h.01c6.56 0 11.9-5.34 11.9-11.9 0-3.18-1.24-6.17-3.45-8.46zM12.07 21.8h-.01a9.83 9.83 0 0 1-5.01-1.37l-.36-.22-3.74.97 1-3.65-.24-.38a9.84 9.84 0 0 1-1.5-5.25c0-5.44 4.42-9.86 9.86-9.86 2.63 0 5.1 1.02 6.96 2.9a9.78 9.78 0 0 1 2.88 6.95c0 5.44-4.43 9.87-9.84 9.87zm5.4-7.4c-.3-.15-1.78-.88-2.06-.98-.28-.1-.48-.15-.68.15-.2.3-.78.98-.95 1.18-.18.2-.35.23-.65.08-.3-.15-1.25-.46-2.38-1.47-.88-.78-1.47-1.75-1.65-2.05-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.53.15-.18.2-.3.3-.5.1-.2.05-.38-.03-.53-.08-.15-.68-1.64-.94-2.25-.25-.6-.5-.5-.68-.51h-.58c-.2 0-.53.08-.8.38-.28.3-1.05 1.03-1.05 2.5s1.08 2.9 1.23 3.1c.15.2 2.12 3.24 5.14 4.54.72.31 1.28.5 1.72.64.72.23 1.37.2 1.88.12.58-.09 1.78-.73 2.03-1.43.25-.7.25-1.3.18-1.43-.07-.13-.27-.2-.57-.35z"/></svg>
                    <span>WhatsApp</span>
                  </a>
                </div>
                <figure class="events-page__card-media stay-slider" data-stay-slider>
                  <div class="stay-slider__track" data-stay-slider-track>
                    <?php
                      $events_gallery_items = array();
                      if (!empty($tour['gallery']) && is_array($tour['gallery'])) {
                        $events_gallery_items = $tour['gallery'];
                      } else {
                        $events_gallery_items = get_field('gallery');
                      }
                      $events_rendered_slide_index = 0;
                      if (!empty($events_gallery_items) && is_array($events_gallery_items)) :
                        foreach ($events_gallery_items as $gallery_item) :
                          $media_url = '';
                          $media_id = 0;
                          $media_mime = '';
                          $media_source = $gallery_item;

                          if (is_array($gallery_item) && array_key_exists('image_or_video', $gallery_item)) {
                            $media_source = $gallery_item['image_or_video'];
                          }

                          if (is_numeric($media_source)) {
                            $media_id = (int) $media_source;
                            $media_url = (string) wp_get_attachment_url($media_id);
                            $media_mime = (string) get_post_mime_type($media_id);
                          } elseif (is_array($media_source)) {
                            if (!empty($media_source['ID'])) {
                              $media_id = (int) $media_source['ID'];
                            }
                            if (!empty($media_source['url'])) {
                              $media_url = (string) $media_source['url'];
                            } elseif ($media_id) {
                              $media_url = (string) wp_get_attachment_url($media_id);
                            }
                            if (!empty($media_source['mime_type'])) {
                              $media_mime = (string) $media_source['mime_type'];
                            } elseif ($media_id) {
                              $media_mime = (string) get_post_mime_type($media_id);
                            }
                          } elseif (is_string($media_source)) {
                            $media_url = (string) $media_source;
                          }

                          if ($media_url === '') {
                            continue;
                          }

                          if ($media_mime === '') {
                            $media_type = wp_check_filetype($media_url);
                            $media_mime = !empty($media_type['type']) ? (string) $media_type['type'] : '';
                          }
                          $is_video = strpos($media_mime, 'video/') === 0;
                          $slide_class = 'stay-slider__slide' . ($events_rendered_slide_index === 0 ? ' is-active' : '');
                    ?>
                      <div class="<?php echo esc_attr($slide_class); ?>" data-stay-slide>
                        <?php if ($is_video) : ?>
                          <video autoplay muted loop playsinline preload="metadata">
                            <source src="<?php echo esc_url($media_url); ?>" type="<?php echo esc_attr($media_mime !== '' ? $media_mime : 'video/mp4'); ?>" />
                          </video>
                        <?php else : ?>
                          <img src="<?php echo esc_url($media_url); ?>" alt="" loading="lazy" width="800" height="500" />
                        <?php endif; ?>
                      </div>
                    <?php
                          $events_rendered_slide_index++;
                        endforeach;
                      endif;
                    ?>
                  </div>
                  <button type="button" class="stay-slider__nav stay-slider__nav--prev" data-stay-prev aria-label="Previous">‹</button>
                  <button type="button" class="stay-slider__nav stay-slider__nav--next" data-stay-next aria-label="Next">›</button>
                  <div class="stay-slider__dots" data-stay-dots></div>
                </figure>
              </div>
            </article>
            <?php endforeach; ?>
          </div>
        </div>
      </section>

      <section class="section events-page" id="diving">
        <div class="container">
          <h2 class="section__title section__title--center reveal" data-reveal><?php the_field('title_experience') ?></h2>
          <p class="events-page__intro reveal" data-reveal><?php the_field('description_diving') ?></p>
          <div class="events-page__grid">
            <?php foreach (get_field('diving_experience') as $key => $diving): ?>
              <article class="events-page__card reveal" data-reveal>
                <div class="events-page__card-preview">
                  <div class="events-page__card-body events-page__card-body--stack">
                    <?php
                      $diving_title = isset($diving['title']) ? trim((string) $diving['title']) : '';
                      if ($diving_title !== '') :
                    ?>
                      <h3 class="events-page__card-title"><?php echo esc_html($diving_title); ?></h3>
                    <?php endif; ?>
                    <?php echo wp_kses_post(isset($diving['description']) ? $diving['description'] : ''); ?>
                    <a class="header__dropdown-whatsapp" href="<?php echo esc_url(isset($diving['whatsapp_number']) ? $diving['whatsapp_number'] : ''); ?>" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.52 3.48A11.86 11.86 0 0 0 12.06 0C5.5 0 .16 5.33.16 11.9c0 2.1.55 4.16 1.6 5.98L0 24l6.3-1.64a11.8 11.8 0 0 0 5.76 1.48h.01c6.56 0 11.9-5.34 11.9-11.9 0-3.18-1.24-6.17-3.45-8.46zM12.07 21.8h-.01a9.83 9.83 0 0 1-5.01-1.37l-.36-.22-3.74.97 1-3.65-.24-.38a9.84 9.84 0 0 1-1.5-5.25c0-5.44 4.42-9.86 9.86-9.86 2.63 0 5.1 1.02 6.96 2.9a9.78 9.78 0 0 1 2.88 6.95c0 5.44-4.43 9.87-9.84 9.87zm5.4-7.4c-.3-.15-1.78-.88-2.06-.98-.28-.1-.48-.15-.68.15-.2.3-.78.98-.95 1.18-.18.2-.35.23-.65.08-.3-.15-1.25-.46-2.38-1.47-.88-.78-1.47-1.75-1.65-2.05-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.53.15-.18.2-.3.3-.5.1-.2.05-.38-.03-.53-.08-.15-.68-1.64-.94-2.25-.25-.6-.5-.5-.68-.51h-.58c-.2 0-.53.08-.8.38-.28.3-1.05 1.03-1.05 2.5s1.08 2.9 1.23 3.1c.15.2 2.12 3.24 5.14 4.54.72.31 1.28.5 1.72.64.72.23 1.37.2 1.88.12.58-.09 1.78-.73 2.03-1.43.25-.7.25-1.3.18-1.43-.07-.13-.27-.2-.57-.35z"/></svg>
                      <span>WhatsApp</span>
                    </a>
                  </div>
                  <figure class="events-page__card-media stay-slider" data-stay-slider>
                    <div class="stay-slider__track" data-stay-slider-track>
                      <?php
                        $diving_gallery_items = $diving['gallery'];
                        $diving_rendered_slide_index = 0;
                        if (!empty($diving_gallery_items) && is_array($diving_gallery_items)) :
                          foreach ($diving_gallery_items as $gallery_item) :
                            $media_url = '';
                            $media_id = 0;
                            $media_mime = '';
                            $media_source = $gallery_item;

                            if (is_array($gallery_item) && array_key_exists('image_or_video', $gallery_item)) {
                              $media_source = $gallery_item['image_or_video'];
                            }

                            if (is_numeric($media_source)) {
                              $media_id = (int) $media_source;
                              $media_url = (string) wp_get_attachment_url($media_id);
                              $media_mime = (string) get_post_mime_type($media_id);
                            } elseif (is_array($media_source)) {
                              if (!empty($media_source['ID'])) {
                                $media_id = (int) $media_source['ID'];
                              }
                              if (!empty($media_source['url'])) {
                                $media_url = (string) $media_source['url'];
                              } elseif ($media_id) {
                                $media_url = (string) wp_get_attachment_url($media_id);
                              }
                              if (!empty($media_source['mime_type'])) {
                                $media_mime = (string) $media_source['mime_type'];
                              } elseif ($media_id) {
                                $media_mime = (string) get_post_mime_type($media_id);
                              }
                            } elseif (is_string($media_source)) {
                              $media_url = (string) $media_source;
                            }

                            if ($media_url === '') {
                              continue;
                            }

                            if ($media_mime === '') {
                              $media_type = wp_check_filetype($media_url);
                              $media_mime = !empty($media_type['type']) ? (string) $media_type['type'] : '';
                            }
                            $is_video = strpos($media_mime, 'video/') === 0;
                            $slide_class = 'stay-slider__slide' . ($diving_rendered_slide_index === 0 ? ' is-active' : '');
                      ?>
                        <div class="<?php echo esc_attr($slide_class); ?>" data-stay-slide>
                          <?php if ($is_video) : ?>
                            <video autoplay muted loop playsinline preload="metadata">
                              <source src="<?php echo esc_url($media_url); ?>" type="<?php echo esc_attr($media_mime !== '' ? $media_mime : 'video/mp4'); ?>" />
                            </video>
                          <?php else : ?>
                            <img src="<?php echo esc_url($media_url); ?>" alt="" loading="lazy" width="800" height="500" />
                          <?php endif; ?>
                        </div>
                      <?php
                            $diving_rendered_slide_index++;
                          endforeach;
                        endif;
                      ?>
                    </div>
                    <button type="button" class="stay-slider__nav stay-slider__nav--prev" data-stay-prev aria-label="Previous">‹</button>
                    <button type="button" class="stay-slider__nav stay-slider__nav--next" data-stay-next aria-label="Next">›</button>
                    <div class="stay-slider__dots" data-stay-dots></div>
                  </figure>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
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
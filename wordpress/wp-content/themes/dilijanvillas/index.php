<?php get_header(); ?>
    <main>
      <div id="stay" class="scroll-anchor" aria-hidden="true"></div>
      <div class="header-hero-wrap" data-header-hero-wrap>
      <section class="hero" id="hero" aria-label="Intro">
        <div class="hero__parallax" data-parallax-layer="bg">
          <?php
            $index_post_id = (int) get_queried_object_id();
            if ($index_post_id <= 0) {
              $index_post_id = (int) get_the_ID();
            }
            $index_hero_video = dilijanvillas_get_acf_media_url('video_home', $index_post_id);
            $index_hero_banner = dilijanvillas_get_acf_media_url('beground_img', $index_post_id);
          ?>
          <div class="hero__static-bg" id="heroStaticBg" aria-hidden="true" data-hero-banner="<?php echo esc_url($index_hero_banner); ?>"></div>
          <div class="hero__video-wrap">
            <video
              class="hero__video"
              autoplay
              muted
              loop
              playsinline
              preload="none"
              aria-hidden="true"
              <?php echo $index_hero_banner !== '' ? 'poster="' . esc_url($index_hero_banner) . '"' : ''; ?>
              <?php echo $index_hero_video === '' ? ' hidden' : ''; ?>
            >
              <?php if ($index_hero_video !== '') : ?>
                <?php /* data-src: JS подставит настоящий src после window.load, чтобы видео не тормозило загрузку страницы */ ?>
                <source data-src="<?php echo esc_url($index_hero_video); ?>" type="<?php echo esc_attr(dilijanvillas_get_video_mime_from_url($index_hero_video)); ?>" />
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

      <section class="section section--events-quick" id="stay-highlights">
        <div class="container">
          <div class="events-quick__head reveal" data-reveal>
            <p class="hero__eyebrow"><span class="hero__line"></span><span><?php the_field('title_book') ?></span></p>
            <h2 class="section__title"><?php the_field('section_title') ?></h2>
            <p class="events-quick__intro"><?php the_field('documentation_book') ?></p>
          </div>
          <div class="events-quick__grid">
            <?php
            $home_current_lang = function_exists('pll_current_language') ? pll_current_language('slug') : '';

            $home_collect_template_pages = static function ($template_slug) use ($home_current_lang) {
              $query_args = array(
                'post_type' => 'page',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'orderby' => array(
                  'menu_order' => 'ASC',
                  'title' => 'ASC',
                ),
                'order' => 'ASC',
                'meta_key' => '_wp_page_template',
                'meta_value' => $template_slug,
                'suppress_filters' => false,
              );
              if ($home_current_lang) {
                $query_args['lang'] = $home_current_lang;
              }

              $rows = get_posts($query_args);
              $page_ids = array();
              $seen = array();
              foreach ($rows as $row) {
                $page_id = (int) $row->ID;
                if ($page_id <= 0) {
                  continue;
                }
                if (function_exists('pll_get_post') && $home_current_lang) {
                  $translated_id = pll_get_post($page_id, $home_current_lang);
                  if (!empty($translated_id)) {
                    $page_id = (int) $translated_id;
                  }
                }
                if ($page_id <= 0 || isset($seen[$page_id])) {
                  continue;
                }
                if ((string) get_page_template_slug($page_id) !== $template_slug) {
                  continue;
                }
                $page_ids[] = $page_id;
                $seen[$page_id] = true;
              }
              return $page_ids;
            };

            $home_resolve_card_media = static function ($page_id) {
              $normalize_source = static function ($media_source) {
                $extracted = function_exists('dilijanvillas_extract_media_from_source')
                  ? dilijanvillas_extract_media_from_source($media_source)
                  : array('url' => '', 'mime' => '');
                $media_url = isset($extracted['url']) ? trim((string) $extracted['url']) : '';
                $media_mime = isset($extracted['mime']) ? (string) $extracted['mime'] : '';
                if ($media_url === '') {
                  return null;
                }
                $is_video = function_exists('dilijanvillas_is_video_media')
                  ? dilijanvillas_is_video_media($media_url, $media_mime)
                  : (strpos($media_mime, 'video/') === 0);
                return array(
                  'url' => $media_url,
                  'type' => $is_video ? 'video' : 'image',
                  'mime' => $media_mime,
                );
              };

              /** Постер видео — поле "Background image" (beground_img) той же страницы. */
              $with_poster = static function ($resolved) use ($page_id) {
                if ($resolved['type'] !== 'video') {
                  $resolved['poster'] = '';
                  return $resolved;
                }
                $resolved['poster'] = function_exists('dilijanvillas_get_acf_media_url')
                  ? dilijanvillas_get_acf_media_url('beground_img', $page_id)
                  : '';
                return $resolved;
              };

              $thumb = (int) get_post_thumbnail_id($page_id);
              if ($thumb > 0) {
                $mime = (string) get_post_mime_type($thumb);
                $url = (string) wp_get_attachment_image_url($thumb, 'large');
                if ($url === '') {
                  $url = (string) wp_get_attachment_url($thumb);
                }
                $from_thumb = $normalize_source(array('url' => $url, 'mime_type' => $mime, 'ID' => $thumb));
                if ($from_thumb) {
                  return $with_poster($from_thumb);
                }
              }

              $gallery = get_field('gallery_block', $page_id);
              if (is_array($gallery)) {
                foreach ($gallery as $slide_item) {
                  $candidates = array($slide_item);
                  if (is_array($slide_item)) {
                    foreach (array('video_or_image', 'image_or_video', 'imgvideo', 'image', 'video', 'file', 'url') as $key) {
                      if (array_key_exists($key, $slide_item) && $slide_item[$key] !== '' && $slide_item[$key] !== null) {
                        $candidates[] = $slide_item[$key];
                      }
                    }
                  }
                  foreach ($candidates as $candidate) {
                    $resolved = $normalize_source($candidate);
                    if ($resolved) {
                      return $with_poster($resolved);
                    }
                  }
                }
              }
              return array('url' => '', 'type' => '', 'mime' => '', 'poster' => '');
            };

            $home_stay_cards = array();
            foreach ($home_collect_template_pages('cottage.php') as $page_id) {
              $home_stay_cards[] = array(
                'page_id' => $page_id,
                'kind_key' => 'stay_unit_kind_cottage',
                'kind_label' => __('Cottage', 'dilijanvillas'),
              );
            }
            foreach ($home_collect_template_pages('private-willa.php') as $page_id) {
              $home_stay_cards[] = array(
                'page_id' => $page_id,
                'kind_key' => 'stay_unit_kind_villa',
                'kind_label' => __('Private villa', 'dilijanvillas'),
              );
            }
            ?>

            <?php foreach ($home_stay_cards as $home_card) :
              $home_card_id = (int) $home_card['page_id'];
              $home_card_title = get_the_title($home_card_id);
              $home_card_link = get_permalink($home_card_id);
              $home_card_description = wp_strip_all_tags((string) get_field('description_cottage', $home_card_id));
              if ($home_card_description === '') {
                $home_card_description = (string) get_post_field('post_excerpt', $home_card_id);
              }
              $home_card_media = $home_resolve_card_media($home_card_id);
              ?>
              <article class="events-quick__card reveal" data-reveal>
                <?php
                get_template_part(
                  'template-parts/components/events-quick-media',
                  null,
                  array(
                    'link' => $home_card_link,
                    'media' => $home_card_media,
                    'alt' => $home_card_title,
                  )
                );
                ?>
                <div class="events-quick__body">
                  <h3><?php echo esc_html($home_card_title); ?></h3>
                  <?php if ($home_card_description !== '') : ?>
                    <p><?php echo esc_html($home_card_description); ?></p>
                  <?php endif; ?>
                  <a class="events-quick__link" href="<?php echo esc_url($home_card_link); ?>" data-i18n="home_view_details">View details</a>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        </div>
      </section>

      <section class="section section--events-quick" id="events-activities">
        <div class="container">
          <div class="events-quick__head reveal" data-reveal>
            <p class="hero__eyebrow"><span class="hero__line"></span><span><?php the_field('title_events') ?></span></p>
            <h2 class="section__title"><?php the_field('section_title_events') ?></h2>
            <p class="events-quick__intro"><?php the_field('documentation_tevents') ?></p>
          </div>
          <div class="events-quick__grid">
            <?php
            $home_events_source_id = 27;
            if (function_exists('pll_current_language') && function_exists('pll_get_post')) {
              $home_events_lang = pll_current_language('slug');
              if ($home_events_lang) {
                $home_events_translated_id = pll_get_post($home_events_source_id, $home_events_lang);
                if (!empty($home_events_translated_id)) {
                  $home_events_source_id = (int) $home_events_translated_id;
                }
              }
            }

            $home_events_page_url = '';
            $home_events_page_query = array(
              'post_type' => 'page',
              'post_status' => 'publish',
              'posts_per_page' => 1,
              'fields' => 'ids',
              'meta_key' => '_wp_page_template',
              'meta_value' => 'events.php',
              'suppress_filters' => false,
            );
            if (function_exists('pll_current_language')) {
              $home_events_lang_slug = pll_current_language('slug');
              if ($home_events_lang_slug) {
                $home_events_page_query['lang'] = $home_events_lang_slug;
              }
            }
            $home_events_page_ids = get_posts($home_events_page_query);
            if (!empty($home_events_page_ids)) {
              $home_events_page_url = (string) get_permalink((int) $home_events_page_ids[0]);
            }

            /** Постер видео — поле "Background image" (beground_img) страницы событий. */
            $home_events_poster = function_exists('dilijanvillas_get_acf_media_url')
              ? dilijanvillas_get_acf_media_url('beground_img', $home_events_source_id)
              : '';
            if ($home_events_poster === '' && !empty($home_events_page_ids) && function_exists('dilijanvillas_get_acf_media_url')) {
              $home_events_poster = dilijanvillas_get_acf_media_url('beground_img', (int) $home_events_page_ids[0]);
            }

            $home_event_resolve_media = static function ($gallery) use ($home_events_poster) {
              if (!is_array($gallery)) {
                return array('url' => '', 'type' => '', 'mime' => '', 'poster' => '');
              }
              foreach ($gallery as $gallery_item) {
                $media_source = $gallery_item;
                if (is_array($gallery_item) && array_key_exists('image_or_video', $gallery_item)) {
                  $media_source = $gallery_item['image_or_video'];
                } elseif (is_array($gallery_item) && array_key_exists('video_or_image', $gallery_item)) {
                  $media_source = $gallery_item['video_or_image'];
                }
                $extracted = function_exists('dilijanvillas_extract_media_from_source')
                  ? dilijanvillas_extract_media_from_source($media_source)
                  : array('url' => '', 'mime' => '');
                $media_url = isset($extracted['url']) ? trim((string) $extracted['url']) : '';
                $media_mime = isset($extracted['mime']) ? (string) $extracted['mime'] : '';
                if ($media_url === '') {
                  continue;
                }
                $is_video = function_exists('dilijanvillas_is_video_media')
                  ? dilijanvillas_is_video_media($media_url, $media_mime)
                  : (strpos($media_mime, 'video/') === 0);
                return array(
                  'url' => $media_url,
                  'type' => $is_video ? 'video' : 'image',
                  'mime' => $media_mime,
                  'poster' => $is_video ? $home_events_poster : '',
                );
              }
              return array('url' => '', 'type' => '', 'mime' => '', 'poster' => '');
            };

            $home_event_cards = array();
            $home_tours = get_field('tours', $home_events_source_id);
            if (is_array($home_tours)) {
              foreach ($home_tours as $home_tour) {
                $home_event_cards[] = array(
                  'anchor' => 'tours',
                  'description' => isset($home_tour['description']) ? (string) $home_tour['description'] : '',
                  'gallery' => isset($home_tour['gallery']) ? $home_tour['gallery'] : array(),
                );
              }
            }
            $home_diving = get_field('diving_experience', $home_events_source_id);
            if (is_array($home_diving)) {
              foreach ($home_diving as $home_diving_item) {
                $home_event_cards[] = array(
                  'anchor' => 'diving',
                  'description' => isset($home_diving_item['description']) ? (string) $home_diving_item['description'] : '',
                  'gallery' => isset($home_diving_item['gallery']) ? $home_diving_item['gallery'] : array(),
                );
              }
            }
            ?>

            <?php foreach ($home_event_cards as $home_event_card) :
              $home_event_media = $home_event_resolve_media($home_event_card['gallery']);
              $home_event_link = $home_events_page_url !== ''
                ? $home_events_page_url . '#' . $home_event_card['anchor']
                : '#' . $home_event_card['anchor'];
              $home_event_alt = wp_strip_all_tags((string) $home_event_card['description']);
              if ($home_event_alt !== '') {
                $home_event_alt = wp_trim_words($home_event_alt, 10, '');
              }
              ?>
              <article class="events-quick__card reveal" data-reveal>
                <?php
                get_template_part(
                  'template-parts/components/events-quick-media',
                  null,
                  array(
                    'link' => $home_event_link,
                    'media' => $home_event_media,
                    'alt' => $home_event_alt,
                  )
                );
                ?>
                <div class="events-quick__body">
                  <?php if ($home_event_card['description'] !== '') : ?>
                    <div class="events-quick__rich"><?php echo wp_kses_post($home_event_card['description']); ?></div>
                  <?php endif; ?>
                  <a class="events-quick__link" href="<?php echo esc_url($home_event_link); ?>" data-i18n="home_view_details">View details</a>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        </div>
      </section>

      <?php
        $gallery_home_items = function_exists('dilijanvillas_normalize_gallery_items')
          ? dilijanvillas_normalize_gallery_items(get_field('gallery_home'))
          : array();
        $gallery_home_background = trim((string) get_field('background_of_section_gallery'));
        $gallery_section_title = trim((string) get_field('section_title_gallery'));
        if ($gallery_section_title === '') {
          $gallery_section_title = 'Պատկերասրահ';
        }
      ?>
      <?php if (!empty($gallery_home_items)) : ?>
        <?php
          get_template_part(
            'template-parts/sections/gallery-grid',
            null,
            array(
              'items' => $gallery_home_items,
              'background_url' => $gallery_home_background,
              'title' => $gallery_section_title,
            )
          );
        ?>
      <?php endif; ?>

      <section class="section section--videos" id="videos" aria-label="Videos">
        <div class="section__bg section__bg--parallax section__bg--videos" data-parallax-bg style="--parallax-speed: 0.16; background-image: url('<?php the_field('background_of_section') ?>')"></div>
        <div class="container">
          <?php
            $videos_section_title = trim((string) get_field('section_title_videos'));
            if ($videos_section_title === '') {
              $videos_section_title = 'Տեսանյութեր';
            }
          ?>
          <h2 class="section__title section__title--center reveal" data-reveal><?php echo esc_html($videos_section_title); ?></h2>
          <div class="videos-showcase">
            <?php foreach (get_field('videos_home') as $video): ?>
              <figure class="videos-showcase__item reveal" data-reveal>
                <div class="videos-showcase__inner">
                  <video class="videos-showcase__video" controls playsinline preload="metadata">
                    <?php
                      $showcase_video_url = is_array($video) && isset($video['video']) ? trim((string) $video['video']) : '';
                    ?>
                    <?php if ($showcase_video_url !== '') : ?>
                      <source src="<?php echo esc_url($showcase_video_url); ?>" type="<?php echo esc_attr(dilijanvillas_get_video_mime_from_url($showcase_video_url)); ?>" />
                    <?php endif; ?>
                  </video>
                </div>
              </figure>
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

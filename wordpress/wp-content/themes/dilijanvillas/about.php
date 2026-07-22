<?php
/**
 * Template Name: About
 */
?>

<?php get_header(); ?>
    <main>
      <div class="header-hero-wrap" data-header-hero-wrap>
      <section class="about-cinematic about-cinematic--page-hero" id="hero" aria-label="About us">
        <?php
          $about_video_url = trim((string) get_field('video'));
          $about_background_img = trim((string) get_field('beground_img'));
        ?>
        <?php if ($about_video_url !== '') : ?>
          <video class="about-cinematic__video" autoplay muted loop playsinline preload="auto" aria-hidden="true">
            <source src="<?php echo esc_url($about_video_url); ?>" type="<?php echo esc_attr(dilijanvillas_get_video_mime_from_url($about_video_url)); ?>" />
          </video>
        <?php elseif ($about_background_img !== '') : ?>
          <div class="about-cinematic__bg" data-parallax-bg aria-hidden="true" style="--parallax-speed: 0.18; background-image: url('<?php echo esc_url($about_background_img); ?>');"></div>
        <?php endif; ?>
        <div class="about-cinematic__veil"></div>
        <?php
          $about_text_direction = strtolower(trim((string) get_field('text_direction')));
          $about_text_orientation = strtolower(trim((string) get_field('text_orentation')));
          $about_content_classes = array('container', 'about-cinematic__content');

          if ($about_text_direction === 'bottom') {
            $about_content_classes[] = 'about-cinematic__content--bottom';
          }

          if ($about_text_orientation === 'left') {
            $about_content_classes[] = 'about-cinematic__content--left';
          } else {
            $about_content_classes[] = 'about-cinematic__content--center';
          }
        ?>
        <div class="<?php echo esc_attr(implode(' ', $about_content_classes)); ?>">
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

      <section class="section">
        <div class="container about-story">
          <div class="about-story__media about-slider" data-about-slider>
            <?php
              $about_slider_items = get_field('gallery_about_2');
              $rendered_slide_index = 0;
              if (!empty($about_slider_items) && is_array($about_slider_items)) :
                foreach ($about_slider_items as $key => $media_item) :
                  $media_url = '';
                  $media_alt = '';

                  if (is_array($media_item)) {
                    if (!empty($media_item['videoimage']) && is_string($media_item['videoimage'])) {
                      $media_url = (string) $media_item['videoimage'];
                    } elseif (!empty($media_item['videoimage']['url'])) {
                      $media_url = (string) $media_item['videoimage']['url'];
                      $media_alt = !empty($media_item['videoimage']['alt']) ? (string) $media_item['videoimage']['alt'] : '';
                    } elseif (!empty($media_item['url'])) {
                      $media_url = (string) $media_item['url'];
                      $media_alt = !empty($media_item['alt']) ? (string) $media_item['alt'] : '';
                    }
                  } elseif (is_string($media_item)) {
                    $media_url = $media_item;
                  }

                  if ($media_url === '') {
                    continue;
                  }

                  $mime_type = wp_check_filetype($media_url);
                  $is_video = !empty($mime_type['type']) && strpos((string) $mime_type['type'], 'video/') === 0;
                  $slide_class = 'about-slider__img' . ($rendered_slide_index === 0 ? ' is-active' : '');
            ?>
              <?php if ($is_video) : ?>
                <video class="<?php echo esc_attr($slide_class); ?>" muted loop playsinline autoplay preload="metadata">
                  <source src="<?php echo esc_url($media_url); ?>" type="<?php echo esc_attr($mime_type['type']); ?>" />
                </video>
              <?php else : ?>
                <img class="<?php echo esc_attr($slide_class); ?>" src="<?php echo esc_url($media_url); ?>" alt="<?php echo esc_attr($media_alt); ?>" loading="lazy" />
              <?php endif; ?>
            <?php
                  $rendered_slide_index++;
                endforeach;
              endif;
            ?>
            <button type="button" class="about-slider__nav about-slider__nav--prev" data-about-prev aria-label="Previous media">‹</button>
            <button type="button" class="about-slider__nav about-slider__nav--next" data-about-next aria-label="Next media">›</button>
          </div>
          <?php
            $about_story_text = (string) get_field('description_2_section');
            // Prevent duplicated slider markup if editor content contains an extra about-slider block.
            $about_story_text = preg_replace('/<div[^>]*class="[^"]*about-story__media[^"]*about-slider[^"]*"[^>]*>.*?<\/div>/is', '', $about_story_text);
            $has_about_story_wrapper = strpos($about_story_text, 'about-story__text') !== false;
            if ($has_about_story_wrapper) {
              echo wp_kses_post($about_story_text);
            } else {
              echo '<article class="about-story__text">' . wp_kses_post($about_story_text) . '</article>';
            }
          ?>
        </div>
      </section>

      <section class="section">
        <div class="container about-experience">
            <?php foreach (get_field('facts') as $key => $experience): ?>
              <article class="about-experience__card">
                <?php echo $experience['text'] ?>
              </article>
            <?php endforeach; ?>
        </div>
      </section>

      <?php
        $about_gallery_items = get_field('gallery_about');
        $about_gallery_has_media = false;
        if (!empty($about_gallery_items) && is_array($about_gallery_items)) {
          foreach ($about_gallery_items as $gallery_probe) {
            if (!is_array($gallery_probe) || empty($gallery_probe['imgvideo'])) {
              continue;
            }
            if (is_string($gallery_probe['imgvideo']) && trim($gallery_probe['imgvideo']) !== '') {
              $about_gallery_has_media = true;
              break;
            }
            if (is_array($gallery_probe['imgvideo']) && !empty($gallery_probe['imgvideo']['url'])) {
              $about_gallery_has_media = true;
              break;
            }
          }
        }
      ?>
      <?php if ($about_gallery_has_media) : ?>
      <section class="section">
        <div class="container about-gallery">
          <?php
            foreach ($about_gallery_items as $gallery) :
              $media_url = '';
              if (is_array($gallery) && !empty($gallery['imgvideo'])) {
                if (is_string($gallery['imgvideo'])) {
                  $media_url = (string) $gallery['imgvideo'];
                } elseif (is_array($gallery['imgvideo']) && !empty($gallery['imgvideo']['url'])) {
                  $media_url = (string) $gallery['imgvideo']['url'];
                }
              }

              if ($media_url === '') {
                continue;
              }

              $media_filetype = wp_check_filetype($media_url);
              $is_video = !empty($media_filetype['type']) && strpos((string) $media_filetype['type'], 'video/') === 0;
          ?>
            <?php if ($is_video) : ?>
              <video class="about-gallery__item" autoplay muted loop playsinline preload="metadata">
                <source src="<?php echo esc_url($media_url); ?>" type="<?php echo esc_attr($media_filetype['type']); ?>" />
              </video>
            <?php else : ?>
              <div class="about-gallery__item" style="background-image: url('<?php echo esc_url($media_url); ?>')"></div>
            <?php endif; ?>
          <?php
            endforeach;
          ?>
        </div>
      </section>
      <?php endif; ?>

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
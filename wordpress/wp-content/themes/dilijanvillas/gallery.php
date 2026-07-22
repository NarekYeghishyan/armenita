<?php
/*
Template Name: Gallery
Description: Photo gallery page
*/
?>
<?php get_header(); ?>
    <main>
      <div class="header-hero-wrap" data-header-hero-wrap>
      <?php
        $gallery_post_id = (int) get_queried_object_id();
        if ($gallery_post_id <= 0) {
          $gallery_post_id = (int) get_the_ID();
        }

        $resolve_media_url = static function ($field_value) {
          if (is_array($field_value)) {
            if (!empty($field_value['url'])) {
              return trim((string) $field_value['url']);
            }
            if (!empty($field_value['ID'])) {
              $from_id = wp_get_attachment_url((int) $field_value['ID']);
              return $from_id ? trim((string) $from_id) : '';
            }
            if (!empty($field_value['id'])) {
              $from_id = wp_get_attachment_url((int) $field_value['id']);
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

        $get_gallery_field = static function ($field_name) use ($gallery_post_id) {
          if (!function_exists('get_field') || $gallery_post_id <= 0) {
            return null;
          }

          $value = get_field($field_name, $gallery_post_id);
          if ($value !== null && $value !== false && $value !== '') {
            return $value;
          }

          // Fallback: raw post meta (IDs) when ACF return format differs.
          $raw = get_post_meta($gallery_post_id, $field_name, true);
          return ($raw !== '' && $raw !== null) ? $raw : $value;
        };

        $gallery_hero_video_url = $resolve_media_url($get_gallery_field('video_home'));
        $gallery_hero_background_img = $resolve_media_url($get_gallery_field('beground_img'));
        $gallery_page_title = trim((string) $get_gallery_field('title_big'));
        if ($gallery_page_title === '') {
          $gallery_page_title = $gallery_post_id > 0 ? get_the_title($gallery_post_id) : get_the_title();
        }
        $gallery_page_subtitle = trim((string) $get_gallery_field('title_small'));
        $gallery_page_description = trim((string) $get_gallery_field('description'));
        $has_gallery_hero = $gallery_hero_video_url !== '' || $gallery_hero_background_img !== '' || $gallery_page_subtitle !== '' || $gallery_page_description !== '';
      ?>
      <?php if ($has_gallery_hero) : ?>
      <section class="about-cinematic about-cinematic--page-hero" id="hero" aria-label="Gallery">
        <?php if ($gallery_hero_background_img !== '') : ?>
          <div class="about-cinematic__bg" data-parallax-bg aria-hidden="true" style="--parallax-speed: 0.18; background-image: url('<?php echo esc_url($gallery_hero_background_img); ?>');"></div>
        <?php endif; ?>
        <?php if ($gallery_hero_video_url !== '') : ?>
          <video class="about-cinematic__video" autoplay muted loop playsinline preload="auto" aria-hidden="true" <?php echo $gallery_hero_background_img !== '' ? 'poster="' . esc_url($gallery_hero_background_img) . '"' : ''; ?>>
            <source src="<?php echo esc_url($gallery_hero_video_url); ?>" type="<?php echo esc_attr(dilijanvillas_get_video_mime_from_url($gallery_hero_video_url)); ?>" />
          </video>
        <?php endif; ?>
        <div class="about-cinematic__veil"></div>
        <?php
          $gallery_text_direction = strtolower(trim((string) $get_gallery_field('text_direction')));
          $gallery_text_orientation = strtolower(trim((string) $get_gallery_field('text_orentation')));
          $gallery_content_classes = array('container', 'about-cinematic__content');

          if ($gallery_text_direction === 'bottom') {
            $gallery_content_classes[] = 'about-cinematic__content--bottom';
          }

          if ($gallery_text_orientation === 'left') {
            $gallery_content_classes[] = 'about-cinematic__content--left';
          } else {
            $gallery_content_classes[] = 'about-cinematic__content--center';
          }
        ?>
        <div class="<?php echo esc_attr(implode(' ', $gallery_content_classes)); ?>">
          <?php if ($gallery_page_subtitle !== '') : ?>
            <p class="hero__eyebrow"><span class="hero__line"></span><span><?php echo esc_html($gallery_page_subtitle); ?></span></p>
          <?php endif; ?>
          <h1 class="about-cinematic__title" data-i18n="gallery_title"><?php echo esc_html($gallery_page_title); ?></h1>
          <?php if ($gallery_page_description !== '') : ?>
            <p class="about-cinematic__lead"><?php echo esc_html($gallery_page_description); ?></p>
          <?php endif; ?>
        </div>
      </section>
      <?php endif; ?>
      </div>

      <?php
        $gallery_raw = $get_gallery_field('gallery_page');
        // Older / mistaken field names editors may have used on this page.
        if (empty($gallery_raw)) {
          $gallery_raw = $get_gallery_field('gallery');
        }
        if (empty($gallery_raw)) {
          $gallery_raw = $get_gallery_field('gallery_home');
        }

        $gallery_page_items = function_exists('dilijanvillas_normalize_gallery_items')
          ? dilijanvillas_normalize_gallery_items($gallery_raw)
          : array();
        $gallery_section_background = $resolve_media_url($get_gallery_field('background_of_section_gallery'));
      ?>

      <?php if (!empty($gallery_page_items)) : ?>
        <?php
          get_template_part(
            'template-parts/sections/gallery-grid',
            null,
            array(
              'items' => $gallery_page_items,
              'background_url' => $gallery_section_background,
              'section_class' => $has_gallery_hero ? 'section section--gallery section--gallery-page' : 'section section--gallery section--gallery-page section--gallery-page-first',
              'show_title' => !$has_gallery_hero,
              'title' => $gallery_page_title,
            )
          );
        ?>
      <?php else : ?>
        <section class="section section--gallery section--gallery-page<?php echo $has_gallery_hero ? '' : ' section--gallery-page-first'; ?>">
          <div class="container">
            <h1 class="section__title section__title--center" data-i18n="gallery_title"><?php echo esc_html($gallery_page_title); ?></h1>
            <p class="section__lead section__lead--center" data-i18n="gallery_empty_message">Add photos in the Gallery field in the page editor.</p>
          </div>
        </section>
      <?php endif; ?>
    </main>
<?php get_footer(); ?>

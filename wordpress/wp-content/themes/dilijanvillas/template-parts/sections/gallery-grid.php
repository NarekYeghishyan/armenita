<?php
/**
 * Gallery grid section (homepage preview or full gallery page).
 *
 * @package dilijanvillas
 */

if (!defined('ABSPATH')) {
    exit;
}

$args = isset($args) && is_array($args) ? $args : array();

$gallery_items = !empty($args['items']) && is_array($args['items']) ? $args['items'] : array();
if (empty($gallery_items)) {
    return;
}

$section_id = !empty($args['section_id']) ? (string) $args['section_id'] : 'gallery';
$grid_id = !empty($args['grid_id']) ? (string) $args['grid_id'] : 'galleryGrid';
$section_class = !empty($args['section_class']) ? (string) $args['section_class'] : 'section section--gallery';
$background_url = !empty($args['background_url']) ? (string) $args['background_url'] : '';
$title = array_key_exists('title', $args) ? (string) $args['title'] : '';
$title_i18n = !empty($args['title_i18n']) ? (string) $args['title_i18n'] : 'gallery_title';
$show_title = !array_key_exists('show_title', $args) || !empty($args['show_title']);
$parallax_speed = !empty($args['parallax_speed']) ? (string) $args['parallax_speed'] : '0.22';
// Fancybox replaces the built-in #lightbox for this grid (see initFancyboxGallery in js/app.js).
$use_fancybox = !empty($args['use_fancybox']);
$fancybox_group = !empty($args['fancybox_group']) ? (string) $args['fancybox_group'] : $grid_id;
?>

<section class="<?php echo esc_attr($section_class); ?>" id="<?php echo esc_attr($section_id); ?>">
  <?php if ($background_url !== '') : ?>
    <div
      class="section__bg section__bg--parallax section__bg--gallery"
      data-parallax-bg
      style="--parallax-speed: <?php echo esc_attr($parallax_speed); ?>; background-image: url('<?php echo esc_url($background_url); ?>')"
    ></div>
  <?php endif; ?>
  <div class="container">
    <?php if ($show_title) : ?>
      <h2 class="section__title section__title--center reveal" data-reveal data-i18n="<?php echo esc_attr($title_i18n); ?>">
        <?php echo esc_html($title !== '' ? $title : 'Պատկերասրահ'); ?>
      </h2>
    <?php endif; ?>
    <div class="gallery" id="<?php echo esc_attr($grid_id); ?>" data-gallery-root>
      <?php foreach ($gallery_items as $gallery_index => $gallery_item) :
        $zoom_src = !empty($gallery_item['zoom_src']) ? (string) $gallery_item['zoom_src'] : (string) $gallery_item['full_src'];
        $figure_classes = 'gallery__item reveal';
        if ((int) $gallery_index === 0) {
          $figure_classes .= ' gallery__item--lg';
        } elseif (((int) $gallery_index + 1) % 4 === 0) {
          $figure_classes .= ' gallery__item--wide';
        }
      ?>
        <figure class="<?php echo esc_attr($figure_classes); ?>" data-reveal>
          <?php if ($use_fancybox) : ?>
            <a
              class="gallery__open"
              href="<?php echo esc_url($zoom_src); ?>"
              data-fancybox="<?php echo esc_attr($fancybox_group); ?>"
              <?php if ($gallery_item['image_alt'] !== '') : ?>
                data-caption="<?php echo esc_attr($gallery_item['image_alt']); ?>"
              <?php else : ?>
                aria-label="Open"
              <?php endif; ?>
            >
              <img
                src="<?php echo esc_url($gallery_item['preview_src']); ?>"
                alt="<?php echo esc_attr($gallery_item['image_alt']); ?>"
                loading="lazy"
              />
            </a>
          <?php else : ?>
            <button
              type="button"
              class="gallery__open"
              data-gallery-open
              data-gallery-full="<?php echo esc_url($gallery_item['full_src']); ?>"
              aria-label="Open"
            >
              <img
                src="<?php echo esc_url($gallery_item['preview_src']); ?>"
                alt="<?php echo esc_attr($gallery_item['image_alt']); ?>"
                loading="lazy"
              />
            </button>
          <?php endif; ?>
        </figure>
      <?php endforeach; ?>
    </div>
  </div>
</section>

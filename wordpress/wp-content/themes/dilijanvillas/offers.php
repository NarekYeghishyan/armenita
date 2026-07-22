<?php
/*
Template Name: Offers
Description: Offers page
*/
?>
<?php get_header(); ?>
    <main>
      <div class="header-hero-wrap" data-header-hero-wrap>
      <section class="about-cinematic about-cinematic--page-hero" id="hero" aria-label="Offers">
        <?php
          $offers_video_url = trim((string) get_field('video_home'));
          $offers_background_img = trim((string) get_field('beground_img'));
        ?>
        <?php if ($offers_background_img !== '') : ?>
          <div class="about-cinematic__bg" aria-hidden="true" style="background-image: url('<?php echo esc_url($offers_background_img); ?>');"></div>
        <?php endif; ?>
        <?php if ($offers_video_url !== '') : ?>
          <video class="about-cinematic__video" autoplay muted loop playsinline preload="auto" aria-hidden="true" <?php echo $offers_background_img !== '' ? 'poster="' . esc_url($offers_background_img) . '"' : ''; ?>>
            <source src="<?php echo esc_url($offers_video_url); ?>" type="<?php echo esc_attr(dilijanvillas_get_video_mime_from_url($offers_video_url)); ?>" />
          </video>
        <?php endif; ?>
        <div class="about-cinematic__veil"></div>
        <?php
          $offers_text_direction = strtolower(trim((string) get_field('text_direction')));
          $offers_text_orientation = strtolower(trim((string) get_field('text_orentation')));
          $offers_content_classes = array('container', 'about-cinematic__content');

          if ($offers_text_direction === 'bottom') {
            $offers_content_classes[] = 'about-cinematic__content--bottom';
          }

          if ($offers_text_orientation === 'left') {
            $offers_content_classes[] = 'about-cinematic__content--left';
          } else {
            $offers_content_classes[] = 'about-cinematic__content--center';
          }
        ?>
        <div class="<?php echo esc_attr(implode(' ', $offers_content_classes)); ?>">
          <p class="hero__eyebrow"><span class="hero__line"></span><span><?php the_field('title_small') ?></span></p>
          <h1 class="about-cinematic__title"><?php the_field('title_big') ?></h1>
          <p class="about-cinematic__lead">
            <?php the_field('description') ?>
          </p>
          <form class="booking-selector booking-selector--luxury" data-booking-form>
            <label class="booking-selector__field">
              <span class="booking-selector__label">
                <span class="booking-selector__icon">🗓</span><span data-i18n="booking_label_stay_length">Stay length</span></span>
                <select name="duration">
                  <?php foreach (get_field('stay_length') as $accommodation_type) : ?>
                      <option value="<?php echo $accommodation_type['duration']; ?>"><?php echo $accommodation_type['duration']; ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
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
              </select>
            </label>
            <label class="booking-selector__field booking-selector__field--full-row">
              <span class="booking-selector__label">
                <span class="booking-selector__icon" aria-hidden="true">🧸</span>
                <span data-i18n="booking_label_children">Traveling with children?</span>
              </span>
              <select name="hasChildren" data-booking-children-toggle>
                <option value="no" selected data-i18n="booking_children_opt_no">No</option>
                <option value="yes" data-i18n="booking_children_opt_yes">Yes</option>
              </select>
            </label>
            <label class="booking-selector__field booking-selector__field--full-row is-hidden" data-booking-children-count>
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
              <span class="booking-selector__label"><span class="booking-selector__icon">🏡</span><span data-i18n="booking_label_accommodation_type">Accommodation type</span></span>
              <select name="accommodation">
                <?php foreach (get_field('accommodation_type') as $accommodation_type) : ?>
                  <option value="<?php echo $accommodation_type['type']; ?>"><?php echo $accommodation_type['type']; ?></option>
                <?php endforeach; ?>
              </select>
            </label>
            <button type="submit" class="btn btn--primary booking-selector__submit" data-i18n="booking_submit_check">Check availability</button>
          </form>
        </div>
        <button type="button" class="hero__scroll" aria-label="Scroll down" data-scroll-down>
          <span class="hero__scroll-icon"></span>
        </button>
      </section>
      </div>

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
<?php
$ratings_source_page_id = (int) get_query_var('ratings_source_page_id');
if ($ratings_source_page_id <= 0) {
  $ratings_source_page_id = dilijanvillas_get_home_page_id();
}

$ratings_translated_source_page_id = $ratings_source_page_id;
$ratings_current_lang = dilijanvillas_get_current_lang_slug();

if (function_exists('pll_get_post') && $ratings_current_lang && $ratings_source_page_id > 0) {
  $translated_page_id = (int) pll_get_post($ratings_source_page_id, $ratings_current_lang);
  if ($translated_page_id > 0) {
    $ratings_translated_source_page_id = $translated_page_id;
  }
}

$ratings_get_field = static function ($field_key) use ($ratings_translated_source_page_id, $ratings_source_page_id) {
  $value = get_field($field_key, $ratings_translated_source_page_id);
  if (($value === null || $value === '' || $value === false) && $ratings_translated_source_page_id !== $ratings_source_page_id) {
    $value = get_field($field_key, $ratings_source_page_id);
  }
  return $value;
};

$ratings_title_small = (string) $ratings_get_field('title_review');
$ratings_title_big = (string) $ratings_get_field('big_title_review');

$google_reviews_data = function_exists('dilijanvillas_get_google_reviews_data')
  ? dilijanvillas_get_google_reviews_data($ratings_get_field)
  : null;
$google_has_data = is_array($google_reviews_data)
  && (
    !empty($google_reviews_data['rating'])
    || !empty($google_reviews_data['rating_display'])
    || !empty($google_reviews_data['count_text'])
  );

$google_review_cards = array();
if ($google_has_data && !empty($google_reviews_data['reviews']) && is_array($google_reviews_data['reviews'])) {
  $google_review_cards = $google_reviews_data['reviews'];
}

$google_rating_value = $google_has_data
  ? (string) ($google_reviews_data['rating_display'] ?? '')
  : '';
if ($google_rating_value === '' && $google_has_data && !empty($google_reviews_data['rating'])) {
  $google_rating_value = number_format((float) $google_reviews_data['rating'], 1) . ' / 5';
}
$google_rating_count = $google_has_data
  ? (string) ($google_reviews_data['count_text'] ?? '')
  : '';
if ($google_rating_count === '' && $google_has_data && !empty($google_reviews_data['user_rating_count'])) {
  $google_rating_count = sprintf(
    _n('%d review on Google', '%d reviews on Google', (int) $google_reviews_data['user_rating_count'], 'dilijanvillas'),
    (int) $google_reviews_data['user_rating_count']
  );
}
$google_maps_url = $google_has_data && !empty($google_reviews_data['maps_url'])
  ? (string) $google_reviews_data['maps_url']
  : '';
$google_brand = $google_has_data && !empty($google_reviews_data['brand'])
  ? (string) $google_reviews_data['brand']
  : 'Google';
$google_stars = $google_has_data && !empty($google_reviews_data['rating'])
  ? dilijanvillas_render_rating_stars((float) $google_reviews_data['rating'])
  : dilijanvillas_render_rating_stars(dilijanvillas_parse_rating_value($google_rating_value));
?>
<section class="section">
  <div class="container ratings">
    <div class="ratings__head">
      <p class="hero__eyebrow"><span class="hero__line"></span><span><?php echo esc_html($ratings_title_small); ?></span></p>
      <h2 class="section__title"><?php echo esc_html($ratings_title_big); ?></h2>
    </div>

    <?php if ($google_has_data) : ?>
      <div class="ratings__scores">
        <article class="ratings__score ratings__score--google">
          <p class="ratings__brand"><?php echo esc_html($google_brand); ?></p>
          <p class="ratings__value"><?php echo esc_html($google_rating_value); ?></p>
          <p class="ratings__stars" aria-hidden="true"><?php echo esc_html($google_stars); ?></p>
          <p class="ratings__count"><?php echo esc_html($google_rating_count); ?></p>
          <?php if ($google_maps_url !== '') : ?>
            <a class="btn btn--ghost" href="<?php echo esc_url($google_maps_url); ?>" target="_blank" rel="noopener noreferrer" data-i18n="reviews_google_btn">Read on Google</a>
          <?php endif; ?>
        </article>
      </div>
    <?php endif; ?>

    <?php if (!empty($google_review_cards)) : ?>
      <div class="ratings__reviews">
        <?php foreach ($google_review_cards as $review) : ?>
          <?php
            $review_text = isset($review['review']) ? trim((string) $review['review']) : '';
            if ($review_text === '') {
              continue;
            }
            $review_name = isset($review['name']) ? trim((string) $review['name']) : '';
            $review_time = isset($review['time']) ? trim((string) $review['time']) : '';
          ?>
          <article class="ratings__review">
            <p><?php echo esc_html($review_text); ?></p>
            <span>
              <?php echo esc_html($review_name); ?>
              <?php if ($review_time !== '') : ?>
                · <?php echo esc_html($review_time); ?>
              <?php endif; ?>
            </span>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

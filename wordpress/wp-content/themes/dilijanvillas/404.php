<?php
/**
 * 404 — Page not found.
 *
 * @package dilijanvillas
 */

add_filter(
    'body_class',
    static function ($classes) {
        $classes[] = 'is-error404';
        return $classes;
    }
);

get_header();

$current_lang = function_exists('pll_current_language') ? pll_current_language('slug') : '';
$ui_lang = in_array($current_lang, array('hy', 'ru', 'en'), true) ? $current_lang : 'en';

$ui_strings = array(
    'hy' => array(
        'eyebrow'  => 'Սխալ 404',
        'title'    => 'Էջը չի գտնվել',
        'lead'     => 'Հնարավոր է էջը տեղափոխվել է կամ այլևս չկա։ Վերադարձեք գլխավոր էջ կամ կապ հաստատեք մեզ հետ։',
        'home'     => 'Գլխավոր էջ',
        'contact'  => 'Գրել մեզ',
    ),
    'ru' => array(
        'eyebrow'  => 'Ошибка 404',
        'title'    => 'Страница не найдена',
        'lead'     => 'Возможно, страница была перемещена или больше не существует. Вернитесь на главную или свяжитесь с нами.',
        'home'     => 'На главную',
        'contact'  => 'Написать нам',
    ),
    'en' => array(
        'eyebrow'  => 'Error 404',
        'title'    => 'Page not found',
        'lead'     => 'The page you are looking for may have moved or no longer exists. Return to the homepage or get in touch with us.',
        'home'     => 'Back to home',
        'contact'  => 'Contact us',
    ),
);

$strings = $ui_strings[$ui_lang];
$home_url = home_url('/');
$whatsapp_url = 'https://wa.me/37494605665';
$bg_image = get_template_directory_uri() . '/images/518675308_17899676184230600_7116829930131710070_n.jpg';
?>

<main class="error404-main">
  <section class="error404" id="error404" aria-labelledby="error404-title">
    <div
      class="error404__backdrop"
      aria-hidden="true"
      style="background-image: url('<?php echo esc_url($bg_image); ?>');"
    ></div>
    <div class="error404__veil" aria-hidden="true"></div>

    <div class="error404__inner">
      <p class="error404__eyebrow">
        <span class="error404__line" aria-hidden="true"></span>
        <span><?php echo esc_html($strings['eyebrow']); ?></span>
      </p>

      <div class="error404__code-wrap" aria-hidden="true">
        <span class="error404__digit">4</span>
        <span class="error404__compass" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="32" cy="32" r="26" />
            <circle cx="32" cy="32" r="3" fill="currentColor" stroke="none" />
            <path d="M32 12v6 M32 46v6 M12 32h6 M46 32h6" />
            <path d="M22 22l8 8 12-18-18 12-8-8z" fill="currentColor" stroke="none" opacity="0.85" />
          </svg>
        </span>
        <span class="error404__digit">4</span>
      </div>

      <h1 id="error404-title" class="error404__title">
        <?php echo esc_html($strings['title']); ?>
      </h1>

      <p class="error404__lead">
        <?php echo esc_html($strings['lead']); ?>
      </p>

      <div class="error404__actions">
        <a class="btn btn--primary error404__btn" href="<?php echo esc_url($home_url); ?>">
          <?php echo esc_html($strings['home']); ?>
        </a>
        <a
          class="btn btn--ghost error404__btn"
          href="<?php echo esc_url($whatsapp_url); ?>"
          target="_blank"
          rel="noopener noreferrer"
        >
          <?php echo esc_html($strings['contact']); ?>
        </a>
      </div>
    </div>
  </section>
</main>

<?php get_footer(); ?>

<?php
/*
Template Name: Map
Description: Map page
*/
?>
<?php get_header(); ?>
    <main>
      <?php
        $map_coordination = trim((string) get_field('coordination'));
        if ($map_coordination === '') {
          $map_coordination = '40.73973875595698, 44.851870306840816';
        }

        $map_coordination = preg_replace('/\s*,\s*/', ', ', $map_coordination);
        $map_query = rawurlencode($map_coordination);
        $map_embed_url = 'https://maps.google.com/maps?width=100%25&height=600&hl=hy&q=' . $map_query . '&t=&z=16&ie=UTF8&iwloc=B&output=embed';
        $map_search_url = 'https://www.google.com/maps?q=' . $map_query;
      ?>
      <section class="section map-page" id="map-page">
        <iframe
          class="map-page__frame"
          loading="lazy"
          referrerpolicy="no-referrer-when-downgrade"
          allowfullscreen
          title="Google Maps"
          data-i18n-aria="contact_map_aria"
          src="<?php echo esc_url($map_embed_url); ?>"
        ></iframe>
        <a
          class="btn btn--primary map-page__open-btn"
          href="<?php echo esc_url($map_search_url); ?>"
          target="_blank"
          rel="noopener noreferrer"
          data-i18n="contact_map_link"
        >Բացել Google Maps-ում</a>
      </section>

      <?php
        $contact_home_page_id = dilijanvillas_get_contact_home_page_id();

        $contact_map_coordination = trim((string) get_field('map_coordination', $contact_home_page_id));
        if ($contact_map_coordination === '') {
          $contact_map_coordination = '40.73973875595698, 44.851870306840816';
        }
        $contact_map_coordination = preg_replace('/\s*,\s*/', ', ', $contact_map_coordination);
        $contact_map_query = rawurlencode($contact_map_coordination);
        $contact_map_search_url = 'https://www.google.com/maps/search/?api=1&query=' . $contact_map_query;

        $contact_phone_value = trim((string) get_field('phone', $contact_home_page_id));
        $contact_phone_tel = preg_replace('/\s+/', '', $contact_phone_value);

        $contact_instagram_text = dilijanvillas_get_contact_social_field($contact_home_page_id, 'instagram_text');
        $contact_instagram_link = dilijanvillas_get_contact_social_field($contact_home_page_id, 'instagram_link');
        $contact_instagram_text_2 = dilijanvillas_get_contact_social_field($contact_home_page_id, 'instagram_text_2');
        $contact_instagram_link_2 = dilijanvillas_get_contact_social_field($contact_home_page_id, 'instagram_link_2');

        $contact_facebook_text = dilijanvillas_get_contact_social_field($contact_home_page_id, 'facebook_text');
        $contact_facebook_link = dilijanvillas_get_contact_social_field($contact_home_page_id, 'facebook_link');
        $contact_facebook_text_2 = dilijanvillas_get_contact_social_field($contact_home_page_id, 'facebook_text_2');
        $contact_facebook_link_2 = dilijanvillas_get_contact_social_field($contact_home_page_id, 'facebook_link_2');
      ?>
      <section class="section section--contact map-contact" id="contact">
        <div class="container">
          <div class="contact">
            <div class="contact__info-card reveal" data-reveal>
              <div class="contact__text">
                <h2 class="section__title reveal" data-reveal data-i18n="contact_title">Կապ</h2>
                <p class="contact__intro reveal" data-reveal><?php echo esc_html((string) get_field('text_contact', $contact_home_page_id)); ?></p>
                <address class="contact__block contact__block--card reveal" data-reveal>
                  <p class="contact__row">
                    <span class="contact__icon" aria-hidden="true">
                      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    </span>
                    <span><?php echo esc_html((string) get_field('location', $contact_home_page_id)); ?></span>
                  </p>
                  <p class="contact__row contact__row--phone">
                    <span class="contact__icon" aria-hidden="true">
                      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    </span>
                    <a class="contact__link" href="tel:<?php echo esc_attr($contact_phone_tel); ?>"><span><?php echo esc_html($contact_phone_value); ?></span></a>
                    <a class="header__dropdown-whatsapp contact__wa-inline" href="<?php echo esc_url((string) get_field('whatsapp_link', $contact_home_page_id)); ?>" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.52 3.48A11.86 11.86 0 0 0 12.06 0C5.5 0 .16 5.33.16 11.9c0 2.1.55 4.16 1.6 5.98L0 24l6.3-1.64a11.8 11.8 0 0 0 5.76 1.48h.01c6.56 0 11.9-5.34 11.9-11.9 0-3.18-1.24-6.17-3.45-8.46zM12.07 21.8h-.01a9.83 9.83 0 0 1-5.01-1.37l-.36-.22-3.74.97 1-3.65-.24-.38a9.84 9.84 0 0 1-1.5-5.25c0-5.44 4.42-9.86 9.86-9.86 2.63 0 5.1 1.02 6.96 2.9a9.78 9.78 0 0 1 2.88 6.95c0 5.44-4.43 9.87-9.84 9.87zm5.4-7.4c-.3-.15-1.78-.88-2.06-.98-.28-.1-.48-.15-.68.15-.2.3-.78.98-.95 1.18-.18.2-.35.23-.65.08-.3-.15-1.25-.46-2.38-1.47-.88-.78-1.47-1.75-1.65-2.05-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.53.15-.18.2-.3.3-.5.1-.2.05-.38-.03-.53-.08-.15-.68-1.64-.94-2.25-.25-.6-.5-.5-.68-.51h-.58c-.2 0-.53.08-.8.38-.28.3-1.05 1.03-1.05 2.5s1.08 2.9 1.23 3.1c.15.2 2.12 3.24 5.14 4.54.72.31 1.28.5 1.72.64.72.23 1.37.2 1.88.12.58-.09 1.78-.73 2.03-1.43.25-.7.25-1.3.18-1.43-.07-.13-.27-.2-.57-.35z"/></svg>
                      <span>WhatsApp</span>
                    </a>
                  </p>
                  <?php if (dilijanvillas_has_contact_social_row($contact_instagram_link, $contact_instagram_text)) : ?>
                  <p class="contact__row">
                    <span class="contact__icon" aria-hidden="true">
                      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
                    </span>
                    <a class="contact__link" href="<?php echo esc_url($contact_instagram_link); ?>" target="_blank" rel="noopener noreferrer"><span data-i18n-cms><?php echo esc_html($contact_instagram_text); ?></span></a>
                  </p>
                  <?php endif; ?>
                  <?php if (dilijanvillas_has_contact_social_row($contact_instagram_link_2, $contact_instagram_text_2)) : ?>
                  <p class="contact__row">
                    <span class="contact__icon" aria-hidden="true">
                      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
                    </span>
                    <a class="contact__link" href="<?php echo esc_url($contact_instagram_link_2); ?>" target="_blank" rel="noopener noreferrer"><span data-i18n-cms><?php echo esc_html($contact_instagram_text_2); ?></span></a>
                  </p>
                  <?php endif; ?>
                  <?php if (dilijanvillas_has_contact_social_row($contact_facebook_link, $contact_facebook_text)) : ?>
                  <p class="contact__row">
                    <span class="contact__icon" aria-hidden="true">
                      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3V2z"/></svg>
                    </span>
                    <a class="contact__link" href="<?php echo esc_url($contact_facebook_link); ?>" target="_blank" rel="noopener noreferrer"><span data-i18n-cms><?php echo esc_html($contact_facebook_text); ?></span></a>
                  </p>
                  <?php endif; ?>
                  <?php if (dilijanvillas_has_contact_social_row($contact_facebook_link_2, $contact_facebook_text_2)) : ?>
                  <p class="contact__row">
                    <span class="contact__icon" aria-hidden="true">
                      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3V2z"/></svg>
                    </span>
                    <a class="contact__link" href="<?php echo esc_url($contact_facebook_link_2); ?>" target="_blank" rel="noopener noreferrer"><span data-i18n-cms><?php echo esc_html($contact_facebook_text_2); ?></span></a>
                  </p>
                  <?php endif; ?>
                </address>
              </div>
              <div class="map-contact__quick-icons" aria-label="Quick contact actions">
                <a class="map-contact__icon-link" href="<?php echo esc_url($contact_map_search_url); ?>" target="_blank" rel="noopener noreferrer" aria-label="Open location">
                  <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                </a>
                <a class="map-contact__icon-link" href="tel:<?php echo esc_attr($contact_phone_tel); ?>" aria-label="Call us">
                  <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                </a>
                <a class="map-contact__icon-link" href="<?php echo esc_url((string) get_field('whatsapp_link', $contact_home_page_id)); ?>" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp">
                  <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.52 3.48A11.86 11.86 0 0 0 12.06 0C5.5 0 .16 5.33.16 11.9c0 2.1.55 4.16 1.6 5.98L0 24l6.3-1.64a11.8 11.8 0 0 0 5.76 1.48h.01c6.56 0 11.9-5.34 11.9-11.9 0-3.18-1.24-6.17-3.45-8.46zM12.07 21.8h-.01a9.83 9.83 0 0 1-5.01-1.37l-.36-.22-3.74.97 1-3.65-.24-.38a9.84 9.84 0 0 1-1.5-5.25c0-5.44 4.42-9.86 9.86-9.86 2.63 0 5.1 1.02 6.96 2.9a9.78 9.78 0 0 1 2.88 6.95c0 5.44-4.43 9.87-9.84 9.87zm5.4-7.4c-.3-.15-1.78-.88-2.06-.98-.28-.1-.48-.15-.68.15-.2.3-.78.98-.95 1.18-.18.2-.35.23-.65.08-.3-.15-1.25-.46-2.38-1.47-.88-.78-1.47-1.75-1.65-2.05-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.53.15-.18.2-.3.3-.5.1-.2.05-.38-.03-.53-.08-.15-.68-1.64-.94-2.25-.25-.6-.5-.5-.68-.51h-.58c-.2 0-.53.08-.8.38-.28.3-1.05 1.03-1.05 2.5s1.08 2.9 1.23 3.1c.15.2 2.12 3.24 5.14 4.54.72.31 1.28.5 1.72.64.72.23 1.37.2 1.88.12.58-.09 1.78-.73 2.03-1.43.25-.7.25-1.3.18-1.43-.07-.13-.27-.2-.57-.35z"/></svg>
                </a>
                <?php if ($contact_instagram_link !== '') : ?>
                <a class="map-contact__icon-link" href="<?php echo esc_url($contact_instagram_link); ?>" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
                  <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2.2" y="2.2" width="19.6" height="19.6" rx="5"/><circle cx="12" cy="12" r="4.1"/><circle cx="17.3" cy="6.7" r="1.1" fill="currentColor" stroke="none"/></svg>
                </a>
                <?php endif; ?>
              </div>
              <div class="contact__quote-inline reveal" data-reveal>
                <figure class="contact__quote contact__quote--inline">
                  <p class="contact__quote-brand"><?php echo esc_html((string) get_field('under_tilte', $contact_home_page_id)); ?></p>
                  <blockquote class="contact__tagline">
                    <span><?php echo esc_html((string) get_field('under_text', $contact_home_page_id)); ?></span>
                  </blockquote>
                </figure>
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>

    <?php get_footer(); ?>
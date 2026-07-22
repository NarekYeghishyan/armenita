<!DOCTYPE html>
<html <?php language_attributes(); ?>>
  <head>
    <script>
      (function () {
        try {
          var u = new URL(window.location.href);
          var p = u.pathname;
          if (!p.endsWith("/")) p = p.replace(/\/[^/]+$/, "/");
          u.pathname = p;
          u.hash = "";
          u.search = "";
          var b = document.createElement("base");
          b.href = u.href;
          var h = document.head || document.getElementsByTagName("head")[0];
          if (h) h.insertBefore(b, h.firstChild);
        } catch (e) {}
      })();
    </script>
    <meta charset="<?php bloginfo('charset'); ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
    <meta name="theme-color" content="#121820" />
    <link rel="icon" href="<?php bloginfo('template_url'); ?>/images/logo.png" type="image/png" />
    <?php wp_head(); ?>
  </head>
  <body <?php body_class('has-hero-header'); ?>>
    <?php wp_body_open(); ?>
    <div class="mesh" aria-hidden="true"></div>
    <div class="noise" aria-hidden="true"></div>
    <div id="top" class="scroll-anchor" aria-hidden="true"></div>
    <?php
    $current_lang = function_exists('pll_current_language') ? pll_current_language('slug') : '';

    $dilijanvillas_get_page_url = static function ($slugs, $fallback = '#') use ($current_lang) {
        $slugs = is_array($slugs) ? $slugs : array($slugs);

        foreach ($slugs as $slug) {
            if (!$slug) {
                continue;
            }

            $page = get_page_by_path($slug);
            if (!$page) {
                continue;
            }

            $page_id = (int) $page->ID;
            if (function_exists('pll_get_post') && $current_lang) {
                $translated_id = pll_get_post($page_id, $current_lang);
                if (!empty($translated_id)) {
                    return get_permalink($translated_id);
                }
            }

            return get_permalink($page_id);
        }

        return $fallback;
    };

    $offers_url = $dilijanvillas_get_page_url(array('offers'));
    $gallery_url = function_exists('dilijanvillas_get_gallery_page_url')
        ? dilijanvillas_get_gallery_page_url($current_lang)
        : $dilijanvillas_get_page_url(array('gallery', 'patkerasrah', 'galeriya'));
    $map_url = $dilijanvillas_get_page_url(array('map'));
    $blog_url = $dilijanvillas_get_page_url(array('blog'));
    $restaurant_group_label = ($current_lang === 'hy' ? 'Ակտիվ հանգիստ և ժամանց' : ($current_lang === 'ru' ? 'Активный отдых и досуг' : 'Adventure & Leisure'));
    $tour_group_label = ($current_lang === 'hy' ? 'Ռեստորան' : ($current_lang === 'ru' ? 'Ресторан' : 'Restaurant'));
    $template_group_labels = array(
        'cottage.php' => ($current_lang === 'hy' ? 'Վիլլաներ' : ($current_lang === 'ru' ? 'Виллы' : 'Villas')),
        'private-willa.php' => ($current_lang === 'hy' ? 'Մասնավոր վիլլա' : ($current_lang === 'ru' ? 'Частная вилла' : 'Private Villa')),
        '__other__' => 'Other',
        'tours' => $tour_group_label,
        'diving_experience' => $restaurant_group_label,
    );

    $dilijanvillas_get_file_url = static function ($field_value) {
        if (is_array($field_value)) {
            if (!empty($field_value['url'])) {
                return (string) $field_value['url'];
            }
            if (!empty($field_value['ID'])) {
                $from_id = wp_get_attachment_url((int) $field_value['ID']);
                return $from_id ? (string) $from_id : '';
            }
            return '';
        }

        if (is_numeric($field_value)) {
            $from_id = wp_get_attachment_url((int) $field_value);
            return $from_id ? (string) $from_id : '';
        }

        return is_string($field_value) ? trim($field_value) : '';
    };

    $dilijanvillas_get_menu_preview = static function ($menu_item) use ($current_lang, $dilijanvillas_get_file_url) {
        $preview = array(
            'video' => '',
            'image' => '',
            'description' => '',
            'show_whatsapp' => false,
            'whatsapp_url' => '',
        );

        if (!function_exists('get_field')) {
            return $preview;
        }

        if ($menu_item->object !== 'page' || empty($menu_item->object_id)) {
            return $preview;
        }

        $page_id = (int) $menu_item->object_id;
        if (function_exists('pll_get_post') && $current_lang) {
            $translated_id = pll_get_post($page_id, $current_lang);
            if (!empty($translated_id)) {
                $page_id = (int) $translated_id;
            }
        }

        if (!$page_id) {
            return $preview;
        }

        $file_value = get_field('file_menu', $page_id);
        $file_url = $dilijanvillas_get_file_url($file_value);
        if (!empty($file_url)) {
            $path = (string) parse_url($file_url, PHP_URL_PATH);
            $ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
            $video_exts = array('mp4', 'webm', 'ogg', 'ogv', 'mov', 'm4v');
            if (in_array($ext, $video_exts, true)) {
                $preview['video'] = $file_url;
            } else {
                $preview['image'] = $file_url;
            }
        }

        $description = get_field('description_menu', $page_id);
        if (is_string($description)) {
            $preview['description'] = trim(wp_kses_post($description));
        }

        $whatsapp_field = get_field('whatsapp_menu', $page_id);
        $whatsapp_url = '';
        if (is_array($whatsapp_field) && isset($whatsapp_field['url']) && is_string($whatsapp_field['url'])) {
            $raw_whatsapp_value = trim($whatsapp_field['url']);
        } elseif (is_string($whatsapp_field)) {
            $raw_whatsapp_value = trim($whatsapp_field);
        } else {
            $raw_whatsapp_value = '';
        }

        if ($raw_whatsapp_value !== '') {
            if (filter_var($raw_whatsapp_value, FILTER_VALIDATE_URL)) {
                $whatsapp_url = $raw_whatsapp_value;
            } else {
                // Admin provides only a phone number; build a wa.me URL.
                $digits_only = preg_replace('/\D+/', '', $raw_whatsapp_value);
                if (!empty($digits_only)) {
                    $whatsapp_url = 'https://wa.me/' . $digits_only;
                }
            }
        }

        if (!empty($whatsapp_url)) {
            $preview['show_whatsapp'] = true;
            $preview['whatsapp_url'] = $whatsapp_url;
        }

        return $preview;
    };

    $dilijanvillas_is_events_item = static function ($menu_item) {
        $title = strtolower(trim(wp_strip_all_tags((string) $menu_item->title)));
        if (strpos($title, 'event') !== false || strpos($title, 'ակտիվ') !== false) {
            return true;
        }

        if ($menu_item->object === 'page' && !empty($menu_item->object_id)) {
            $page_id = (int) $menu_item->object_id;
            $slug = (string) get_post_field('post_name', $page_id);
            if (in_array($slug, array('events-and-activates', 'events-and-activities', 'events', 'event'), true)) {
                return true;
            }
            $template = (string) get_page_template_slug($page_id);
            if (in_array($template, array('Events and activates.php', 'events-and-activates.php', 'events.php'), true)) {
                return true;
            }
        }

        $url_path = (string) parse_url((string) $menu_item->url, PHP_URL_PATH);
        return stripos($url_path, 'event') !== false;
    };

    $dilijanvillas_is_menu_item_active = static function ($menu_item) {
        if (!empty($menu_item->current) || !empty($menu_item->current_item_parent) || !empty($menu_item->current_item_ancestor)) {
            return true;
        }

        $active_classes = array(
            'current-menu-item',
            'current_page_item',
            'current-menu-parent',
            'current_page_parent',
            'current-menu-ancestor',
            'current_page_ancestor',
        );

        if (!empty($menu_item->classes) && is_array($menu_item->classes)) {
            foreach ($menu_item->classes as $class_name) {
                if (in_array((string) $class_name, $active_classes, true)) {
                    return true;
                }
            }
        }

        $item_path = (string) parse_url((string) $menu_item->url, PHP_URL_PATH);
        if ($item_path === '') {
            return false;
        }

        $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
        $current_path = (string) parse_url($request_uri, PHP_URL_PATH);

        $normalize_path = static function ($path) {
            $path = trim((string) $path);
            if ($path === '') {
                return '/';
            }

            $path = '/' . ltrim($path, '/');
            $normalized = rtrim($path, '/');
            return $normalized !== '' ? $normalized : '/';
        };

        return $normalize_path($item_path) === $normalize_path($current_path);
    };

    $dilijanvillas_build_events_groups = static function ($post_id) use ($dilijanvillas_get_file_url, $current_lang, $restaurant_group_label, $tour_group_label) {
        if (!function_exists('get_field')) {
            return array();
        }

        $source_post_id = (int) $post_id;
        if (function_exists('pll_get_post') && $current_lang) {
            $translated_id = pll_get_post($source_post_id, $current_lang);
            if (!empty($translated_id)) {
                $source_post_id = (int) $translated_id;
            }
        }

        $definitions = array(
            'diving_experience' => 'diving_experience',
            'tours' => 'tours',
        );

        $group_labels = array(
            'tours' => $tour_group_label,
            'diving_experience' => $restaurant_group_label,
        );

        $groups = array();
        foreach ($definitions as $group_key => $field_key) {
            $rows = get_field($field_key, $source_post_id);
            if ((empty($rows) || !is_array($rows)) && $source_post_id !== (int) $post_id) {
                // Fallback to base post if translated post has no rows.
                $rows = get_field($field_key, (int) $post_id);
            }
            if (empty($rows) || !is_array($rows)) {
                continue;
            }

            $group_label = $group_labels[$group_key] ?? $group_key;
            $items = array();
            foreach ($rows as $index => $row) {
                if (!is_array($row)) {
                    continue;
                }

                $title = '';
                foreach (array('menu_title', 'title', 'name', 'label', 'item_title') as $title_key) {
                    if (!empty($row[$title_key]) && is_string($row[$title_key])) {
                        $title = trim($row[$title_key]);
                        break;
                    }
                }
                if ($title === '') {
                    $title = $group_label . ' ' . ($index + 1);
                }

                $url = '#';
                foreach (array('url', 'link', 'page_url') as $url_key) {
                    if (!empty($row[$url_key]) && is_string($row[$url_key])) {
                        $url = trim($row[$url_key]);
                        break;
                    }
                }

                $description_raw = isset($row['menu_description']) && is_string($row['menu_description']) ? $row['menu_description'] : '';
                $description = trim(wp_kses_post($description_raw));

                $file_url = $dilijanvillas_get_file_url($row['menu_img'] ?? '');
                $video = '';
                $image = '';
                if (!empty($file_url)) {
                    $path = (string) parse_url($file_url, PHP_URL_PATH);
                    $ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
                    $video_exts = array('mp4', 'webm', 'ogg', 'ogv', 'mov', 'm4v');
                    if (in_array($ext, $video_exts, true)) {
                        $video = $file_url;
                    } else {
                        $image = $file_url;
                    }
                }

                $whatsapp_url = '';
                $whatsapp_raw = isset($row['whatsapp_number']) && is_string($row['whatsapp_number']) ? trim($row['whatsapp_number']) : '';
                if ($whatsapp_raw !== '') {
                    if (filter_var($whatsapp_raw, FILTER_VALIDATE_URL)) {
                        $whatsapp_url = $whatsapp_raw;
                    } else {
                        $digits_only = preg_replace('/\D+/', '', $whatsapp_raw);
                        if (!empty($digits_only)) {
                            $whatsapp_url = 'https://wa.me/' . $digits_only;
                        }
                    }
                }

                $items[] = array(
                    'item' => (object) array(
                        'title' => $title,
                        'url' => $url,
                    ),
                    'preview' => array(
                        'video' => $video,
                        'image' => $image,
                        'description' => $description,
                        'show_whatsapp' => !empty($whatsapp_url),
                        'whatsapp_url' => $whatsapp_url,
                    ),
                );
            }

            if (!empty($items)) {
                $groups[$group_key] = $items;
            }
        }

        return $groups;
    };

    $dilijanvillas_build_stay_template_groups = static function ($child_items) use ($current_lang, $dilijanvillas_get_menu_preview) {
        $grouped_children = array(
            'cottage.php' => array(),
            'private-willa.php' => array(),
            '__other__' => array(),
        );
        $first_preview = null;
        $seen_page_ids = array();
        $template_keys = array('cottage.php', 'private-willa.php');
        $current_page_id = (int) get_queried_object_id();
        $default_lang = function_exists('pll_default_language') ? (string) pll_default_language('slug') : '';

        foreach ($template_keys as $template_key) {
            $query_args = array(
                'post_type' => 'page',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'fields' => 'ids',
                // Pages admin drag order (menu_order). Do not sort by title —
                // bedroom numbers in titles ("1 Bedroom", "2 Bedroom") would
                // override equal menu_order values on Polylang translations.
                'orderby' => array(
                    'menu_order' => 'ASC',
                    'ID' => 'ASC',
                ),
                'order' => 'ASC',
                'suppress_filters' => false,
                'meta_query' => array(
                    array(
                        'key' => '_wp_page_template',
                        'value' => $template_key,
                        'compare' => '=',
                    ),
                ),
            );

            if ($current_lang) {
                $query_args['lang'] = $current_lang;
            }

            $page_ids = get_posts($query_args);
            if (!is_array($page_ids)) {
                $page_ids = array();
            }

            // Enforce CMS page order in PHP: current-language menu_order first;
            // when tied (common on EN/RU translations), follow default-language twin.
            usort(
                $page_ids,
                static function ($page_a, $page_b) use ($default_lang) {
                    $page_a = (int) $page_a;
                    $page_b = (int) $page_b;

                    $order_a = (int) get_post_field('menu_order', $page_a);
                    $order_b = (int) get_post_field('menu_order', $page_b);

                    if ($order_a === $order_b && $default_lang !== '' && function_exists('pll_get_post')) {
                        $default_a = pll_get_post($page_a, $default_lang);
                        $default_b = pll_get_post($page_b, $default_lang);
                        if (!empty($default_a)) {
                            $order_a = (int) get_post_field('menu_order', (int) $default_a);
                        }
                        if (!empty($default_b)) {
                            $order_b = (int) get_post_field('menu_order', (int) $default_b);
                        }
                    }

                    if ($order_a !== $order_b) {
                        return $order_a <=> $order_b;
                    }

                    return $page_a <=> $page_b;
                }
            );

            foreach ($page_ids as $page_id) {
                $page_id = (int) $page_id;
                if (!$page_id || isset($seen_page_ids[$page_id])) {
                    continue;
                }

                if ((string) get_page_template_slug($page_id) !== $template_key) {
                    continue;
                }

                $menu_like_item = (object) array(
                    'ID' => 0,
                    'object' => 'page',
                    'object_id' => $page_id,
                    'title' => (string) get_the_title($page_id),
                    'url' => (string) get_permalink($page_id),
                    'current' => $current_page_id === $page_id,
                    'current_item_parent' => false,
                    'current_item_ancestor' => false,
                    'classes' => array(),
                );

                if ($menu_like_item->title === '' || $menu_like_item->url === '') {
                    continue;
                }

                $preview_data = $dilijanvillas_get_menu_preview($menu_like_item);
                if ($first_preview === null) {
                    $first_preview = $preview_data;
                }

                $grouped_children[$template_key][] = array(
                    'item' => $menu_like_item,
                    'preview' => $preview_data,
                );
                $seen_page_ids[$page_id] = true;
            }
        }

        foreach ($child_items as $child_item) {
            $template_key = '__other__';
            if ($child_item->object === 'page' && !empty($child_item->object_id)) {
                $template_slug = get_page_template_slug((int) $child_item->object_id);
                if (in_array($template_slug, $template_keys, true)) {
                    if (!empty($grouped_children[$template_slug])) {
                        continue;
                    }
                    $template_key = $template_slug;
                }
            }

            
            $preview_data = $dilijanvillas_get_menu_preview($child_item);
            if ($first_preview === null) {
                $first_preview = $preview_data;
            }
            $grouped_children[$template_key][] = array(
                'item' => $child_item,
                'preview' => $preview_data,
            );
        }

        return array(
            'groups' => $grouped_children,
            'first_preview' => $first_preview,
        );
    };

    $events_dropdown_groups = $dilijanvillas_build_events_groups(25);

    $general_menu_items = array();
    $general_menu_children_by_parent = array();
    $menu_locations = get_nav_menu_locations();
    $menu_id = 0;
    if (function_exists('dilijanvillas_get_nav_menu_id')) {
        $menu_id = (int) dilijanvillas_get_nav_menu_id('header_general');
    } elseif (!empty($menu_locations['header_general'])) {
        $menu_id = (int) $menu_locations['header_general'];
        if (function_exists('pll_get_term') && $current_lang) {
            $translated_menu_id = pll_get_term($menu_id, $current_lang);
            if (!empty($translated_menu_id)) {
                $menu_id = (int) $translated_menu_id;
            }
        }
    }

    if ($menu_id > 0) {
        $menu_items = wp_get_nav_menu_items($menu_id);
        if (!empty($menu_items) && is_array($menu_items)) {
            foreach ($menu_items as $menu_item) {
                if (empty($menu_item->title)) {
                    continue;
                }

                $menu_item->url = !empty($menu_item->url) ? $menu_item->url : '#';
                $parent_id = (int) $menu_item->menu_item_parent;
                if ($parent_id > 0) {
                    if (!isset($general_menu_children_by_parent[$parent_id])) {
                        $general_menu_children_by_parent[$parent_id] = array();
                    }
                    $general_menu_children_by_parent[$parent_id][] = $menu_item;
                } else {
                    $general_menu_items[] = $menu_item;
                }
            }
        }
    }

    // WP menus replace the hardcoded fallback nav. If Gallery is missing from the
    // assigned header_general menu (common on HY), inject it so the link still shows.
    $menu_has_gallery = false;
    foreach ($general_menu_items as $menu_item) {
        $linked_page_id = ($menu_item->object === 'page' && !empty($menu_item->object_id))
            ? (int) $menu_item->object_id
            : 0;
        if (function_exists('dilijanvillas_nav_item_is_gallery')
            && dilijanvillas_nav_item_is_gallery((string) $menu_item->url, (string) $menu_item->title, $linked_page_id)
        ) {
            $menu_has_gallery = true;
            break;
        }
    }

    if (!$menu_has_gallery && !empty($gallery_url) && $gallery_url !== '#') {
        $gallery_nav_label = function_exists('dilijanvillas_get_gallery_nav_label')
            ? dilijanvillas_get_gallery_nav_label($current_lang)
            : 'Gallery';
        $gallery_page_id = function_exists('dilijanvillas_get_gallery_page_id')
            ? (int) dilijanvillas_get_gallery_page_id($current_lang)
            : 0;
        $is_gallery_current = $gallery_page_id > 0 && (int) get_queried_object_id() === $gallery_page_id;
        if (!$is_gallery_current && function_exists('dilijanvillas_nav_item_is_gallery')) {
            $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
            $is_gallery_current = dilijanvillas_nav_item_is_gallery($request_uri, '', 0);
        }

        $gallery_menu_item = (object) array(
            'ID' => -46001,
            'object' => 'custom',
            'object_id' => $gallery_page_id,
            'title' => $gallery_nav_label,
            'url' => $gallery_url,
            'menu_item_parent' => 0,
            'current' => $is_gallery_current,
            'current_item_parent' => false,
            'current_item_ancestor' => false,
            'classes' => $is_gallery_current ? array('current-menu-item') : array(),
        );

        // Prefer the right track (index 3+) so left stay/offers groups stay intact.
        if (count($general_menu_items) >= 3) {
            array_splice($general_menu_items, 3, 0, array($gallery_menu_item));
        } else {
            $general_menu_items[] = $gallery_menu_item;
        }
    }

    $left_menu_items = array_slice($general_menu_items, 0, 3);
    $right_menu_items = array_slice($general_menu_items, 3);
    ?>

    <header class="header" id="site-header">
      <button type="button" class="header__burger" id="menu-toggle" aria-label="Menu" aria-expanded="false" aria-controls="primary-nav" data-menu-toggle>
        <span class="header__burger-line" aria-hidden="true"></span>
        <span class="header__burger-line" aria-hidden="true"></span>
        <span class="header__burger-line" aria-hidden="true"></span>
      </button>
      <a class="header__brand header__brand--nav" href="<?php echo esc_url(home_url('/')); ?>" data-i18n-aria="hero_title">
        <img class="header__logo" src="<?php bloginfo('template_url'); ?>/images/logo.png" alt="" width="48" height="48" />
      </a>
      <nav class="header__nav" id="primary-nav" aria-label="Primary">
        <div class="header__menu-drawer">
        <div class="header__track header__track--left">
        <?php if (!empty($left_menu_items)) : ?>
          <?php foreach ($left_menu_items as $menu_item) : ?>
            <?php
            $menu_item_id = (int) $menu_item->ID;
            $child_items = isset($general_menu_children_by_parent[$menu_item_id]) ? $general_menu_children_by_parent[$menu_item_id] : array();
            $is_events_menu_item = $dilijanvillas_is_events_item($menu_item);
            $event_groups_for_item = $is_events_menu_item ? $events_dropdown_groups : array();
            ?>
            <?php if (!empty($child_items) || !empty($event_groups_for_item)) : ?>
              <?php
              $initial_preview = array(
                  'video' => '',
                  'image' => '',
                  'description' => '<p>Choose your stay by category and open the exact page directly.</p>',
                  'show_whatsapp' => false,
                  'whatsapp_url' => '',
              );
              $initial_assigned = false;
              if (!empty($event_groups_for_item) && empty($child_items)) {
                  $grouped_children = $event_groups_for_item;
                  foreach ($grouped_children as $event_group_items) {
                      foreach ($event_group_items as $event_group_row) {
                          $preview_data = $event_group_row['preview'];
                          if (!$initial_assigned) {
                              $initial_preview = array(
                                  'video' => (string) $preview_data['video'],
                                  'image' => (string) $preview_data['image'],
                                  'description' => !empty($preview_data['description']) ? (string) $preview_data['description'] : $initial_preview['description'],
                                  'show_whatsapp' => !empty($preview_data['show_whatsapp']),
                                  'whatsapp_url' => !empty($preview_data['whatsapp_url']) ? (string) $preview_data['whatsapp_url'] : '',
                              );
                              $initial_assigned = true;
                              break 2;
                          }
                      }
                  }
              } else {
                  $template_group_data = $dilijanvillas_build_stay_template_groups($child_items);
                  $grouped_children = $template_group_data['groups'];
                  if (!$initial_assigned && !empty($template_group_data['first_preview'])) {
                      $preview_data = $template_group_data['first_preview'];
                      $initial_preview = array(
                          'video' => (string) $preview_data['video'],
                          'image' => (string) $preview_data['image'],
                          'description' => !empty($preview_data['description']) ? (string) $preview_data['description'] : $initial_preview['description'],
                          'show_whatsapp' => !empty($preview_data['show_whatsapp']),
                          'whatsapp_url' => !empty($preview_data['whatsapp_url']) ? (string) $preview_data['whatsapp_url'] : '',
                      );
                      $initial_assigned = true;
                  }
              }
              ?>
              <div class="header__item header__item--dropdown" data-nav-dropdown>
                <button
                  type="button"
                  class="header__link header__link--button header__link--dropdown<?php echo $dilijanvillas_is_menu_item_active($menu_item) ? ' header__link--active' : ''; ?>"
                  aria-expanded="false"
                  data-dropdown-toggle
                  data-dropdown-url="<?php echo esc_url($menu_item->url); ?>"
                >
                  <span><?php echo esc_html($menu_item->title); ?></span>
                </button>
                <div class="header__dropdown" data-dropdown-panel>
                  <div class="header__dropdown-hub">
                    <a class="header__dropdown-hub-link" href="<?php echo esc_url($menu_item->url); ?>" data-i18n="nav_dropdown_full_page">Open full page</a>
                  </div>
                  <div class="header__dropdown-preview">
                    <?php if (!empty($initial_preview['video'])) : ?>
                      <video class="header__dropdown-video" data-dropdown-video autoplay muted loop playsinline preload="metadata">
                        <source src="<?php echo esc_url($initial_preview['video']); ?>" type="<?php echo esc_attr(dilijanvillas_get_video_mime_from_url($initial_preview['video'])); ?>" data-dropdown-video-source />
                      </video>
                    <?php else : ?>
                      <video class="header__dropdown-video" data-dropdown-video autoplay muted loop playsinline preload="metadata" hidden>
                        <source src="" type="video/mp4" data-dropdown-video-source />
                      </video>
                    <?php endif; ?>
                    <?php if (!empty($initial_preview['image'])) : ?>
                      <img class="header__dropdown-image" src="<?php echo esc_url($initial_preview['image']); ?>" alt="" data-dropdown-image loading="lazy" />
                    <?php else : ?>
                      <img class="header__dropdown-image" src="" alt="" data-dropdown-image loading="lazy" hidden />
                    <?php endif; ?>
                    <div class="header__dropdown-desc" data-dropdown-description>
                      <?php echo wp_kses_post($initial_preview['description']); ?>
                    </div>
                    <a
                      class="header__dropdown-whatsapp"
                      data-dropdown-whatsapp-button
                      href="<?php echo esc_url($initial_preview['whatsapp_url']); ?>"
                      target="_blank"
                      rel="noopener noreferrer"
                      aria-label="WhatsApp"
                      <?php echo !empty($initial_preview['show_whatsapp']) ? '' : 'hidden'; ?>
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.52 3.48A11.86 11.86 0 0 0 12.06 0C5.5 0 .16 5.33.16 11.9c0 2.1.55 4.16 1.6 5.98L0 24l6.3-1.64a11.8 11.8 0 0 0 5.76 1.48h.01c6.56 0 11.9-5.34 11.9-11.9 0-3.18-1.24-6.17-3.45-8.46zM12.07 21.8h-.01a9.83 9.83 0 0 1-5.01-1.37l-.36-.22-3.74.97 1-3.65-.24-.38a9.84 9.84 0 0 1-1.5-5.25c0-5.44 4.42-9.86 9.86-9.86 2.63 0 5.1 1.02 6.96 2.9a9.78 9.78 0 0 1 2.88 6.95c0 5.44-4.43 9.87-9.84 9.87zm5.4-7.4c-.3-.15-1.78-.88-2.06-.98-.28-.1-.48-.15-.68.15-.2.3-.78.98-.95 1.18-.18.2-.35.23-.65.08-.3-.15-1.25-.46-2.38-1.47-.88-.78-1.47-1.75-1.65-2.05-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.53.15-.18.2-.3.3-.5.1-.2.05-.38-.03-.53-.08-.15-.68-1.64-.94-2.25-.25-.6-.5-.5-.68-.51h-.58c-.2 0-.53.08-.8.38-.28.3-1.05 1.03-1.05 2.5s1.08 2.9 1.23 3.1c.15.2 2.12 3.24 5.14 4.54.72.31 1.28.5 1.72.64.72.23 1.37.2 1.88.12.58-.09 1.78-.73 2.03-1.43.25-.7.25-1.3.18-1.43-.07-.13-.27-.2-.57-.35z"/></svg>
                      <span>WhatsApp</span>
                    </a>
                  </div>
                  <div class="header__dropdown-links">
                    <div class="header__dropdown-groups">
                      <?php $active_assigned = false; ?>
                      <?php foreach ($grouped_children as $group_key => $group_items) : ?>
                        <?php if (empty($group_items)) continue; ?>
                        <div class="header__dropdown-group">
                          <p class="header__dropdown-heading"><?php echo esc_html($template_group_labels[$group_key] ?? $group_key); ?></p>
                          <?php foreach ($group_items as $group_row) : ?>
                            <?php
                            $is_active = !$active_assigned;
                            $active_assigned = true;
                            $group_item = $group_row['item'];
                            $preview_data = $group_row['preview'];
                            ?>
                            <a
                              class="header__sublink<?php echo $is_active ? ' header__sublink--active' : ''; ?>"
                              href="<?php echo esc_url($group_item->url); ?>"
                              data-dropdown-link
                              <?php if (!empty($preview_data['video'])) : ?>data-dropdown-video="<?php echo esc_url($preview_data['video']); ?>"<?php endif; ?>
                              <?php if (!empty($preview_data['image'])) : ?>data-dropdown-image="<?php echo esc_url($preview_data['image']); ?>"<?php endif; ?>
                              data-dropdown-desc-html="<?php echo esc_attr(!empty($preview_data['description']) ? $preview_data['description'] : ''); ?>"
                              data-dropdown-whatsapp="<?php echo !empty($preview_data['show_whatsapp']) ? '1' : '0'; ?>"
                              data-dropdown-whatsapp-url="<?php echo esc_url(!empty($preview_data['whatsapp_url']) ? $preview_data['whatsapp_url'] : ''); ?>"
                            ><?php echo esc_html($group_item->title); ?></a>
                          <?php endforeach; ?>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  </div>
                </div>
              </div>
            <?php else : ?>
              <a class="header__link<?php echo $dilijanvillas_is_menu_item_active($menu_item) ? ' header__link--active' : ''; ?>" href="<?php echo esc_url($menu_item->url); ?>"><?php echo esc_html($menu_item->title); ?></a>
            <?php endif; ?>
          <?php endforeach; ?>
        <?php else : ?>
          <a class="header__link" href="<?php echo esc_url(home_url('/')); ?>">Home</a>
          <a class="header__link" href="<?php echo esc_url($offers_url); ?>">Offers</a>
          <a class="header__link" href="<?php echo esc_url($map_url); ?>">Map</a>
        <?php endif; ?>
        </div>
        <div class="header__track header__track--right">
        <?php if (!empty($right_menu_items)) : ?>
          <?php foreach ($right_menu_items as $menu_item) : ?>
            <?php
            $menu_item_id = (int) $menu_item->ID;
            $child_items = isset($general_menu_children_by_parent[$menu_item_id]) ? $general_menu_children_by_parent[$menu_item_id] : array();
            $is_events_menu_item = $dilijanvillas_is_events_item($menu_item);
            $event_groups_for_item = $is_events_menu_item ? $events_dropdown_groups : array();
            ?>
            <?php if (!empty($child_items) || !empty($event_groups_for_item)) : ?>
              <?php
              $initial_preview = array(
                  'video' => '',
                  'image' => '',
                  'description' => '<p>Choose a category and open the exact page directly.</p>',
                  'show_whatsapp' => false,
                  'whatsapp_url' => '',
              );
              $initial_assigned = false;
              if (!empty($event_groups_for_item) && empty($child_items)) {
                  $grouped_children = $event_groups_for_item;
                  foreach ($grouped_children as $event_group_items) {
                      foreach ($event_group_items as $event_group_row) {
                          $preview_data = $event_group_row['preview'];
                          if (!$initial_assigned) {
                              $initial_preview = array(
                                  'video' => (string) $preview_data['video'],
                                  'image' => (string) $preview_data['image'],
                                  'description' => !empty($preview_data['description']) ? (string) $preview_data['description'] : $initial_preview['description'],
                                  'show_whatsapp' => !empty($preview_data['show_whatsapp']),
                                  'whatsapp_url' => !empty($preview_data['whatsapp_url']) ? (string) $preview_data['whatsapp_url'] : '',
                              );
                              $initial_assigned = true;
                              break 2;
                          }
                      }
                  }
              } else {
                  $template_group_data = $dilijanvillas_build_stay_template_groups($child_items);
                  $grouped_children = $template_group_data['groups'];
                  if (!$initial_assigned && !empty($template_group_data['first_preview'])) {
                      $preview_data = $template_group_data['first_preview'];
                      $initial_preview = array(
                          'video' => (string) $preview_data['video'],
                          'image' => (string) $preview_data['image'],
                          'description' => !empty($preview_data['description']) ? (string) $preview_data['description'] : $initial_preview['description'],
                          'show_whatsapp' => !empty($preview_data['show_whatsapp']),
                          'whatsapp_url' => !empty($preview_data['whatsapp_url']) ? (string) $preview_data['whatsapp_url'] : '',
                      );
                      $initial_assigned = true;
                  }
              }
              ?>
              <div class="header__item header__item--dropdown" data-nav-dropdown>
                <button
                  type="button"
                  class="header__link header__link--button header__link--dropdown<?php echo $dilijanvillas_is_menu_item_active($menu_item) ? ' header__link--active' : ''; ?>"
                  aria-expanded="false"
                  data-dropdown-toggle
                  data-dropdown-url="<?php echo esc_url($menu_item->url); ?>"
                >
                  <span><?php echo esc_html($menu_item->title); ?></span>
                </button>
                <div class="header__dropdown" data-dropdown-panel>
                  <div class="header__dropdown-hub">
                    <a class="header__dropdown-hub-link" href="<?php echo esc_url($menu_item->url); ?>" data-i18n="nav_dropdown_full_page">Open full page</a>
                  </div>
                  <div class="header__dropdown-preview">
                    <?php if (!empty($initial_preview['video'])) : ?>
                      <video class="header__dropdown-video" data-dropdown-video autoplay muted loop playsinline preload="metadata">
                        <source src="<?php echo esc_url($initial_preview['video']); ?>" type="<?php echo esc_attr(dilijanvillas_get_video_mime_from_url($initial_preview['video'])); ?>" data-dropdown-video-source />
                      </video>
                    <?php else : ?>
                      <video class="header__dropdown-video" data-dropdown-video autoplay muted loop playsinline preload="metadata" hidden>
                        <source src="" type="video/mp4" data-dropdown-video-source />
                      </video>
                    <?php endif; ?>
                    <?php if (!empty($initial_preview['image'])) : ?>
                      <img class="header__dropdown-image" src="<?php echo esc_url($initial_preview['image']); ?>" alt="" data-dropdown-image loading="lazy" />
                    <?php else : ?>
                      <img class="header__dropdown-image" src="" alt="" data-dropdown-image loading="lazy" hidden />
                    <?php endif; ?>
                    <div class="header__dropdown-desc" data-dropdown-description>
                      <?php echo wp_kses_post($initial_preview['description']); ?>
                    </div>
                    <a
                      class="header__dropdown-whatsapp"
                      data-dropdown-whatsapp-button
                      href="<?php echo esc_url($initial_preview['whatsapp_url']); ?>"
                      target="_blank"
                      rel="noopener noreferrer"
                      aria-label="WhatsApp"
                      <?php echo !empty($initial_preview['show_whatsapp']) ? '' : 'hidden'; ?>
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.52 3.48A11.86 11.86 0 0 0 12.06 0C5.5 0 .16 5.33.16 11.9c0 2.1.55 4.16 1.6 5.98L0 24l6.3-1.64a11.8 11.8 0 0 0 5.76 1.48h.01c6.56 0 11.9-5.34 11.9-11.9 0-3.18-1.24-6.17-3.45-8.46zM12.07 21.8h-.01a9.83 9.83 0 0 1-5.01-1.37l-.36-.22-3.74.97 1-3.65-.24-.38a9.84 9.84 0 0 1-1.5-5.25c0-5.44 4.42-9.86 9.86-9.86 2.63 0 5.1 1.02 6.96 2.9a9.78 9.78 0 0 1 2.88 6.95c0 5.44-4.43 9.87-9.84 9.87zm5.4-7.4c-.3-.15-1.78-.88-2.06-.98-.28-.1-.48-.15-.68.15-.2.3-.78.98-.95 1.18-.18.2-.35.23-.65.08-.3-.15-1.25-.46-2.38-1.47-.88-.78-1.47-1.75-1.65-2.05-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.53.15-.18.2-.3.3-.5.1-.2.05-.38-.03-.53-.08-.15-.68-1.64-.94-2.25-.25-.6-.5-.5-.68-.51h-.58c-.2 0-.53.08-.8.38-.28.3-1.05 1.03-1.05 2.5s1.08 2.9 1.23 3.1c.15.2 2.12 3.24 5.14 4.54.72.31 1.28.5 1.72.64.72.23 1.37.2 1.88.12.58-.09 1.78-.73 2.03-1.43.25-.7.25-1.3.18-1.43-.07-.13-.27-.2-.57-.35z"/></svg>
                      <span>WhatsApp</span>
                    </a>
                  </div>
                  <div class="header__dropdown-links">
                    <div class="header__dropdown-groups">
                      <?php $active_assigned = false; ?>
                      <?php foreach ($grouped_children as $group_key => $group_items) : ?>
                        <?php if (empty($group_items)) continue; ?>
                        <div class="header__dropdown-group">
                          <p class="header__dropdown-heading"><?php echo esc_html($template_group_labels[$group_key] ?? $group_key); ?></p>
                          <?php foreach ($group_items as $group_row) : ?>
                            <?php
                            $is_active = !$active_assigned;
                            $active_assigned = true;
                            $group_item = $group_row['item'];
                            $preview_data = $group_row['preview'];
                            ?>
                            <a
                              class="header__sublink<?php echo $is_active ? ' header__sublink--active' : ''; ?>"
                              href="<?php echo esc_url($group_item->url); ?>"
                              data-dropdown-link
                              <?php if (!empty($preview_data['video'])) : ?>data-dropdown-video="<?php echo esc_url($preview_data['video']); ?>"<?php endif; ?>
                              <?php if (!empty($preview_data['image'])) : ?>data-dropdown-image="<?php echo esc_url($preview_data['image']); ?>"<?php endif; ?>
                              data-dropdown-desc-html="<?php echo esc_attr(!empty($preview_data['description']) ? $preview_data['description'] : ''); ?>"
                              data-dropdown-whatsapp="<?php echo !empty($preview_data['show_whatsapp']) ? '1' : '0'; ?>"
                              data-dropdown-whatsapp-url="<?php echo esc_url(!empty($preview_data['whatsapp_url']) ? $preview_data['whatsapp_url'] : ''); ?>"
                            ><?php echo esc_html($group_item->title); ?></a>
                          <?php endforeach; ?>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  </div>
                </div>
              </div>
            <?php else : ?>
              <?php if (!empty($menu_item->url) && !empty($menu_item->title)) : ?>
                <a class="header__link<?php echo $dilijanvillas_is_menu_item_active($menu_item) ? ' header__link--active' : ''; ?>" href="<?php echo esc_url($menu_item->url); ?>"><?php echo esc_html($menu_item->title); ?></a>
              <?php endif; ?>
            <?php endif; ?>
          <?php endforeach; ?>
        <?php else : ?>
          <a class="header__link" href="<?php echo esc_url($gallery_url); ?>" data-i18n="nav_gallery">Պատկերասրահ</a>
          <a class="header__link" href="<?php echo esc_url($map_url); ?>" data-i18n="nav_videos">Քարտեզ</a>
          <a class="header__link" href="<?php echo esc_url($blog_url); ?>">Blog</a>
        <?php endif; ?>
        <div class="lang">
          <?php
          $lang_labels = array(
              'hy' => 'Հայ',
              'ru' => 'Рус',
              'en' => 'EN',
          );
          ?>
          <?php if (function_exists('pll_the_languages')) : ?>
            <?php $languages = pll_the_languages(array('raw' => 1, 'hide_if_empty' => 0)); ?>
            <?php if (!empty($languages) && is_array($languages)) : ?>
              <select
                id="site-lang"
                class="lang__select"
                name="site-lang"
                aria-label="Language"
                onchange="if (this.value) { window.location.href = this.value; }"
              >
                <?php foreach ($languages as $language) : ?>
                  <?php
                  $slug = isset($language['slug']) ? $language['slug'] : '';
                  $label = isset($lang_labels[$slug]) ? $lang_labels[$slug] : (isset($language['name']) ? $language['name'] : strtoupper($slug));
                  ?>
                  <option value="<?php echo esc_url($language['url']); ?>" <?php selected(!empty($language['current_lang']), true); ?>>
                    <?php echo esc_html($label); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            <?php else : ?>
              <select id="site-lang" class="lang__select" name="site-lang" aria-label="Language">
                <option value="hy">Հայ</option>
                <option value="ru">Рус</option>
                <option value="en">EN</option>
              </select>
            <?php endif; ?>
          <?php else : ?>
            <select id="site-lang" class="lang__select" name="site-lang" aria-label="Language">
              <option value="hy">Հայ</option>
              <option value="ru">Рус</option>
              <option value="en">EN</option>
            </select>
          <?php endif; ?>
        </div>
        </div>
        </div>
      </nav>
      <div class="header__backdrop" data-menu-close aria-hidden="true"></div>
    </header>

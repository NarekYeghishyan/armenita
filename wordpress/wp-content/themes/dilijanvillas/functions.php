<?php
/**
 * Theme setup and assets.
 *
 * @package dilijanvillas
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once get_template_directory() . '/inc/paylink-diagnostics.php';
require_once get_template_directory() . '/inc/booking-system.php';
// Site access gate disabled — site is open to visitors.
// require_once get_template_directory() . '/inc/site-access-gate.php';
require_once get_template_directory() . '/inc/polylang-home.php';
require_once get_template_directory() . '/inc/contact-social-fields.php';
require_once get_template_directory() . '/inc/acf-polylang-locations.php';
require_once get_template_directory() . '/inc/acf-seo.php';
require_once get_template_directory() . '/inc/acf-gallery-page.php';
require_once get_template_directory() . '/inc/acf-stay-unit-fields.php';
require_once get_template_directory() . '/inc/google-reviews.php';

function dilijanvillas_theme_setup()
{
    add_theme_support('title-tag');
    register_nav_menus(
        array(
            'header_general' => __('General', 'dilijanvillas'),
        )
    );
}
add_action('after_setup_theme', 'dilijanvillas_theme_setup');

function dilijanvillas_maybe_create_general_menu()
{
    if (get_option('dilijanvillas_general_menu_initialized')) {
        return;
    }

    $menu_name = 'General';
    $menu = wp_get_nav_menu_object($menu_name);
    $menu_id = $menu ? (int) $menu->term_id : 0;

    if (!$menu_id) {
        $menu_id = wp_create_nav_menu($menu_name);
    }

    if (!is_wp_error($menu_id) && $menu_id) {
        $locations = get_theme_mod('nav_menu_locations', array());
        if (empty($locations['header_general'])) {
            $locations['header_general'] = (int) $menu_id;
            set_theme_mod('nav_menu_locations', $locations);
        }
    }

    update_option('dilijanvillas_general_menu_initialized', 1);
}
add_action('init', 'dilijanvillas_maybe_create_general_menu');

function dilijanvillas_enqueue_assets()
{
    wp_enqueue_style(
        'dilijanvillas-google-fonts',
        'https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&family=Outfit:wght@300;400;500;600;700&family=Noto+Sans+Armenian:wght@300;400;500;600;700&family=Noto+Serif+Armenian:wght@400;600;700&display=swap',
        array(),
        null
    );

    wp_enqueue_style(
        'flatpickr',
        'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css',
        array(),
        '4.6.13'
    );

    wp_enqueue_style(
        'dilijanvillas-main',
        get_template_directory_uri() . '/css/styles.css',
        array('dilijanvillas-google-fonts', 'flatpickr'),
        filemtime(get_template_directory() . '/css/styles.css')
    );

    wp_enqueue_script(
        'flatpickr',
        'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js',
        array(),
        '4.6.13',
        true
    );

    wp_enqueue_script(
        'dilijanvillas-main',
        get_template_directory_uri() . '/js/app.js',
        array('flatpickr'),
        filemtime(get_template_directory() . '/js/app.js'),
        true
    );

    wp_localize_script(
        'dilijanvillas-main',
        'DV_BOOKING',
        dilijanvillas_get_booking_frontend_config()
    );
}
add_action('wp_enqueue_scripts', 'dilijanvillas_enqueue_assets');


	/*                      Remove                          */
	remove_action ( 'wp_head' , 'rsd_link' );
	remove_action ( 'wp_head' , 'wlwmanifest_link' );
	remove_action ( 'wp_head' , 'wp_generator' );
	show_admin_bar ( false );

/**
 * Allow SVG uploads in media library.
 */
function dilijanvillas_allow_svg_uploads($mimes)
{
    $mimes['svg'] = 'image/svg+xml';
    $mimes['svgz'] = 'image/svg+xml';
    return $mimes;
}
add_filter('upload_mimes', 'dilijanvillas_allow_svg_uploads');

/**
 * Fix WordPress filetype check for SVG files.
 */
function dilijanvillas_fix_svg_filetype_check($data, $file, $filename, $mimes)
{
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if ($ext === 'svg' || $ext === 'svgz') {
        $data['ext'] = $ext;
        $data['type'] = 'image/svg+xml';
        $data['proper_filename'] = $filename;
    }
    return $data;
}
add_filter('wp_check_filetype_and_ext', 'dilijanvillas_fix_svg_filetype_check', 10, 4);

/**
 * Resolve MIME type for HTML5 <source type=""> from a video URL or attachment.
 *
 * @param string $url Video file URL.
 * @return string MIME type (defaults to video/mp4).
 */
function dilijanvillas_get_video_mime_from_url($url)
{
    $url = trim((string) $url);
    if ($url === '') {
        return 'video/mp4';
    }

    $path = (string) parse_url($url, PHP_URL_PATH);
    $ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));

    $map = array(
        'mp4' => 'video/mp4',
        'm4v' => 'video/mp4',
        'webm' => 'video/webm',
        'ogv' => 'video/ogg',
        'ogg' => 'video/ogg',
        'mov' => 'video/quicktime',
        'qt' => 'video/quicktime',
    );

    if (isset($map[$ext])) {
        return $map[$ext];
    }

    $filetype = wp_check_filetype($path);
    if (!empty($filetype['type']) && strpos((string) $filetype['type'], 'video/') === 0) {
        return (string) $filetype['type'];
    }

    return 'video/mp4';
}

/**
 * Ensure .mov uploads are recognized as QuickTime video.
 *
 * @param array<string,string> $mimes Allowed mime types.
 * @return array<string,string>
 */
function dilijanvillas_allow_video_uploads($mimes)
{
    $mimes['mov'] = 'video/quicktime';
    $mimes['qt'] = 'video/quicktime';
    $mimes['m4v'] = 'video/mp4';
    $mimes['webm'] = 'video/webm';

    return $mimes;
}
add_filter('upload_mimes', 'dilijanvillas_allow_video_uploads');

/**
 * Fix WordPress filetype detection for .mov files.
 *
 * @param array<string,mixed> $data     File data.
 * @param string              $file     Full file path.
 * @param string              $filename File name.
 * @param array<string,mixed> $mimes    Allowed mimes.
 * @return array<string,mixed>
 */
function dilijanvillas_fix_mov_filetype_check($data, $file, $filename, $mimes)
{
    unset($file, $mimes);

    $ext = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));
    if ($ext === 'mov' || $ext === 'qt') {
        $data['ext'] = $ext;
        $data['type'] = 'video/quicktime';
        $data['proper_filename'] = $filename;
    }

    return $data;
}
add_filter('wp_check_filetype_and_ext', 'dilijanvillas_fix_mov_filetype_check', 10, 4);

/**
 * Whether a media URL/MIME is video.
 *
 * @param string $url  Media URL.
 * @param string $mime Optional MIME type.
 */
function dilijanvillas_is_video_media($url, $mime = '')
{
    $mime = strtolower(trim((string) $mime));
    if ($mime !== '' && strpos($mime, 'video/') === 0) {
        return true;
    }

    $path = (string) parse_url((string) $url, PHP_URL_PATH);
    $ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));

    return in_array($ext, array('mp4', 'webm', 'ogg', 'ogv', 'mov', 'm4v', 'qt'), true);
}

/**
 * Extract URL and MIME from an ACF file/image/video field value.
 *
 * @param mixed $media_source Field value.
 * @return array{url:string,mime:string}
 */
function dilijanvillas_extract_media_from_source($media_source)
{
    $media_url = '';
    $media_mime = '';

    if (is_array($media_source)) {
        if (!empty($media_source['url'])) {
            $media_url = (string) $media_source['url'];
        } elseif (!empty($media_source['ID'])) {
            $attachment_id = (int) $media_source['ID'];
            $media_url = (string) wp_get_attachment_url($attachment_id);
            $media_mime = (string) get_post_mime_type($attachment_id);
        }
        if ($media_mime === '' && !empty($media_source['mime_type'])) {
            $media_mime = (string) $media_source['mime_type'];
        }
    } elseif (is_numeric($media_source)) {
        $attachment_id = (int) $media_source;
        $media_url = (string) wp_get_attachment_url($attachment_id);
        $media_mime = (string) get_post_mime_type($attachment_id);
    } elseif (is_string($media_source)) {
        $media_url = trim($media_source);
    }

    if ($media_url !== '' && $media_mime === '') {
        $filetype = wp_check_filetype((string) parse_url($media_url, PHP_URL_PATH));
        $media_mime = !empty($filetype['type']) ? (string) $filetype['type'] : '';
    }

    return array(
        'url' => $media_url,
        'mime' => $media_mime,
    );
}

/**
 * Resolve an ACF media field to a URL (handles ID, array, or URL string).
 *
 * @param string $field_name ACF field name.
 * @param int    $post_id    Optional post ID.
 * @return string
 */
function dilijanvillas_get_acf_media_url($field_name, $post_id = 0)
{
    $field_name = trim((string) $field_name);
    if ($field_name === '') {
        return '';
    }

    $post_id = (int) $post_id;
    if ($post_id <= 0) {
        $post_id = (int) get_queried_object_id();
    }
    if ($post_id <= 0) {
        $post_id = (int) get_the_ID();
    }

    $value = null;
    if (function_exists('get_field')) {
        $value = get_field($field_name, $post_id > 0 ? $post_id : false);
    }

    if ($value === null || $value === false || $value === '') {
        $raw = $post_id > 0 ? get_post_meta($post_id, $field_name, true) : '';
        if ($raw !== '' && $raw !== false) {
            $value = $raw;
        }
    }

    $media = dilijanvillas_extract_media_from_source($value);
    return trim((string) $media['url']);
}

/**
 * Preload hero banner image on the front page for faster first paint.
 */
function dilijanvillas_preload_front_page_hero_assets()
{
    echo '<link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>' . "\n";
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";

    if (!is_front_page()) {
        return;
    }

    $post_id = (int) get_queried_object_id();
    if ($post_id <= 0) {
        $post_id = (int) get_the_ID();
    }

    $banner_url = dilijanvillas_get_acf_media_url('beground_img', $post_id);
    if ($banner_url !== '') {
        printf(
            '<link rel="preload" as="image" href="%s" fetchpriority="high">' . "\n",
            esc_url($banner_url)
        );
    }
}
add_action('wp_head', 'dilijanvillas_preload_front_page_hero_assets', 1);

/**
 * Normalize an ACF gallery field into preview/full image URLs.
 *
 * Accepts ACF array objects, attachment IDs, or image URL strings.
 *
 * @param mixed $gallery ACF gallery field value.
 * @return array<int, array{preview_src:string,full_src:string,image_alt:string}>
 */
function dilijanvillas_normalize_gallery_items($gallery)
{
    $items = array();

    if (empty($gallery)) {
        return $items;
    }

    if (!is_array($gallery)) {
        $gallery = array($gallery);
    }

    foreach ($gallery as $gallery_item) {
        $image_id = 0;
        $image_alt = '';
        $preview_src = '';
        $full_src = '';

        if (is_array($gallery_item)) {
            if (!empty($gallery_item['ID'])) {
                $image_id = (int) $gallery_item['ID'];
            } elseif (!empty($gallery_item['id'])) {
                $image_id = (int) $gallery_item['id'];
            } elseif (!empty($gallery_item['attachment_id'])) {
                $image_id = (int) $gallery_item['attachment_id'];
            }

            $image_alt = !empty($gallery_item['alt']) ? (string) $gallery_item['alt'] : '';
            if (!empty($gallery_item['sizes']['medium_large'])) {
                $preview_src = (string) $gallery_item['sizes']['medium_large'];
            } elseif (!empty($gallery_item['sizes']['medium'])) {
                $preview_src = (string) $gallery_item['sizes']['medium'];
            } elseif (!empty($gallery_item['sizes']['large'])) {
                $preview_src = (string) $gallery_item['sizes']['large'];
            } elseif (!empty($gallery_item['url'])) {
                $preview_src = (string) $gallery_item['url'];
            }

            if (!empty($gallery_item['sizes']['large'])) {
                $full_src = (string) $gallery_item['sizes']['large'];
            } elseif (!empty($gallery_item['sizes']['full'])) {
                $full_src = (string) $gallery_item['sizes']['full'];
            } elseif (!empty($gallery_item['url'])) {
                $full_src = (string) $gallery_item['url'];
            }
        } elseif (is_numeric($gallery_item)) {
            $image_id = (int) $gallery_item;
        } elseif (is_string($gallery_item)) {
            $candidate = trim($gallery_item);
            if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_URL)) {
                $preview_src = $candidate;
                $full_src = $candidate;
            } elseif (ctype_digit($candidate)) {
                $image_id = (int) $candidate;
            }
        }

        if ($image_id) {
            if ($preview_src === '') {
                $preview_src = (string) wp_get_attachment_image_url($image_id, 'medium_large');
            }
            if ($preview_src === '') {
                $preview_src = (string) wp_get_attachment_image_url($image_id, 'medium');
            }
            if ($preview_src === '') {
                $preview_src = (string) wp_get_attachment_image_url($image_id, 'large');
            }
            if ($preview_src === '') {
                $preview_src = (string) wp_get_attachment_image_url($image_id, 'full');
            }
            if ($full_src === '') {
                $full_src = (string) wp_get_attachment_image_url($image_id, 'large');
            }
            if ($full_src === '') {
                $full_src = (string) wp_get_attachment_image_url($image_id, 'full');
            }
            if ($image_alt === '') {
                $image_alt = (string) get_post_meta($image_id, '_wp_attachment_image_alt', true);
            }
        }

        if ($preview_src === '' || $full_src === '') {
            continue;
        }

        $items[] = array(
            'preview_src' => $preview_src,
            'full_src' => $full_src,
            'image_alt' => $image_alt,
        );
    }

    return $items;
}

/**
 * Whether a page uses the Gallery template (path variants allowed).
 *
 * @param int $page_id Page ID.
 * @return bool
 */
function dilijanvillas_is_gallery_page_template($page_id)
{
    $page_id = (int) $page_id;
    if ($page_id <= 0) {
        return false;
    }

    $template = (string) get_page_template_slug($page_id);
    if ($template === '') {
        return false;
    }

    $template = str_replace('\\', '/', $template);
    return $template === 'gallery.php' || substr($template, -strlen('/gallery.php')) === '/gallery.php';
}

/**
 * Resolve the Gallery page ID for a language (template first, then slug).
 *
 * @param string $lang Optional language slug.
 * @return int
 */
function dilijanvillas_get_gallery_page_id($lang = '')
{
    $lang = $lang !== '' ? $lang : (function_exists('pll_current_language') ? (string) pll_current_language('slug') : '');

    $resolve_for_lang = static function ($page_id) use ($lang) {
        $page_id = (int) $page_id;
        if ($page_id <= 0) {
            return 0;
        }

        if ($lang && function_exists('pll_get_post')) {
            $translated_id = (int) pll_get_post($page_id, $lang);
            if ($translated_id > 0) {
                return $translated_id;
            }

            if (function_exists('pll_get_post_language') && pll_get_post_language($page_id, 'slug') === $lang) {
                return $page_id;
            }

            // No translation for this language — keep searching.
            return 0;
        }

        return $page_id;
    };

    $template_query = array(
        'post_type' => 'page',
        'post_status' => 'publish',
        'posts_per_page' => 50,
        'fields' => 'ids',
        'orderby' => 'ID',
        'order' => 'ASC',
        'suppress_filters' => true,
        'meta_query' => array(
            'relation' => 'OR',
            array(
                'key' => '_wp_page_template',
                'value' => 'gallery.php',
                'compare' => '=',
            ),
            array(
                'key' => '_wp_page_template',
                'value' => '/gallery.php',
                'compare' => 'LIKE',
            ),
        ),
    );

    if (function_exists('pll_languages_list')) {
        $template_query['lang'] = '';
    }

    $template_page_ids = get_posts($template_query);
    if (!is_array($template_page_ids)) {
        $template_page_ids = array();
    }

    $template_page_ids = array_values(array_filter(array_map('intval', $template_page_ids)));

    if ($lang && function_exists('pll_get_post_language')) {
        foreach ($template_page_ids as $candidate_id) {
            if (pll_get_post_language($candidate_id, 'slug') === $lang) {
                return $candidate_id;
            }
        }
    }

    foreach ($template_page_ids as $candidate_id) {
        $resolved_id = $resolve_for_lang($candidate_id);
        if ($resolved_id > 0) {
            return $resolved_id;
        }
    }

    if (!empty($template_page_ids) && !$lang) {
        return (int) $template_page_ids[0];
    }

    // Last resort when translations are missing: still return a gallery template page.
    if (!empty($template_page_ids)) {
        return (int) $template_page_ids[0];
    }

    $slugs = array('gallery', 'gallery-2', 'patkerasrah', 'galeriya', 'galereya', 'galerea');
    foreach ($slugs as $slug) {
        $page = get_page_by_path($slug, OBJECT, 'page');
        if (!$page && function_exists('pll_get_post')) {
            // Polylang can hide other-language pages from get_page_by_path.
            $slug_pages = get_posts(
                array(
                    'post_type' => 'page',
                    'post_status' => 'publish',
                    'name' => $slug,
                    'posts_per_page' => 5,
                    'fields' => 'ids',
                    'suppress_filters' => true,
                    'lang' => '',
                )
            );
            if (!empty($slug_pages)) {
                $page = get_post((int) $slug_pages[0]);
            }
        }

        if (!$page) {
            continue;
        }

        $resolved_id = $resolve_for_lang((int) $page->ID);
        if ($resolved_id > 0) {
            return $resolved_id;
        }

        if (!$lang) {
            return (int) $page->ID;
        }
    }

    return 0;
}

/**
 * Permalink for the Gallery page (template or slug), Polylang-aware.
 *
 * @param string $lang Optional language slug.
 * @return string
 */
function dilijanvillas_get_gallery_page_url($lang = '')
{
    $page_id = dilijanvillas_get_gallery_page_id($lang);
    if ($page_id <= 0) {
        return '#';
    }

    $permalink = get_permalink($page_id);
    return $permalink ? (string) $permalink : '#';
}

/**
 * Localized Gallery nav label.
 *
 * @param string $lang Optional language slug.
 * @return string
 */
function dilijanvillas_get_gallery_nav_label($lang = '')
{
    $lang = $lang !== '' ? $lang : (function_exists('pll_current_language') ? (string) pll_current_language('slug') : '');

    if ($lang === 'ru') {
        return 'Галерея';
    }

    if ($lang === 'en') {
        return 'Gallery';
    }

    return 'Պատկերասրահ';
}

/**
 * Whether a nav URL/title looks like the Gallery page.
 *
 * @param string $url   Menu item URL.
 * @param string $title Menu item title.
 * @param int    $page_id Optional linked page ID.
 * @return bool
 */
function dilijanvillas_nav_item_is_gallery($url, $title = '', $page_id = 0)
{
    $page_id = (int) $page_id;
    if ($page_id > 0 && dilijanvillas_is_gallery_page_template($page_id)) {
        return true;
    }

    $title_normalized = function_exists('mb_strtolower')
        ? mb_strtolower(trim(wp_strip_all_tags((string) $title)), 'UTF-8')
        : strtolower(trim(wp_strip_all_tags((string) $title)));

    $gallery_titles = array('gallery', 'галерея', 'պատկերասրահ', 'patkerasrah', 'galeriya', 'galereya');
    if ($title_normalized !== '' && in_array($title_normalized, $gallery_titles, true)) {
        return true;
    }

    $path = (string) parse_url((string) $url, PHP_URL_PATH);
    $path = strtolower(trim($path, '/'));
    if ($path === '') {
        return false;
    }

    $segments = explode('/', $path);
    $last = (string) end($segments);
    $gallery_slugs = array('gallery', 'gallery-2', 'patkerasrah', 'galeriya', 'galereya', 'galerea');

    return in_array($last, $gallery_slugs, true) || strpos($last, 'gallery') !== false;
}


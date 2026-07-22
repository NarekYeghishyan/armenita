<?php
/**
 * SEO Title + Meta Description (ACF) for all pages.
 *
 * @package dilijanvillas
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Whether a major SEO plugin is active (defer title/meta to it).
 *
 * @return bool
 */
function dilijanvillas_seo_plugin_active()
{
    if (defined('WPSEO_VERSION') || class_exists('WPSEO_Options', false)) {
        return true;
    }

    if (defined('RANK_MATH_VERSION') || class_exists('RankMath', false)) {
        return true;
    }

    if (defined('AIOSEO_VERSION') || function_exists('aioseo')) {
        return true;
    }

    if (defined('SEOPRESS_VERSION') || function_exists('seopress_init')) {
        return true;
    }

    return false;
}

/**
 * Current singular page/post ID for SEO fields (Polylang-aware via current post).
 *
 * @return int
 */
function dilijanvillas_get_seo_post_id()
{
    if (is_singular()) {
        return (int) get_queried_object_id();
    }

    if (is_front_page()) {
        $page_on_front = (int) get_option('page_on_front');
        if ($page_on_front > 0) {
            return dilijanvillas_translate_seo_post_id($page_on_front);
        }
    }

    // Static posts page: is_singular() is false here, so without this the SEO
    // fields filled on that page in wp-admin would be silently ignored.
    if (is_home()) {
        $page_for_posts = (int) get_option('page_for_posts');
        if ($page_for_posts > 0) {
            return dilijanvillas_translate_seo_post_id($page_for_posts);
        }
    }

    return 0;
}

/**
 * Map a page ID to its Polylang translation for the current language.
 *
 * @param int $post_id Source page ID.
 * @return int Translated ID, or the original when there is no translation.
 */
function dilijanvillas_translate_seo_post_id($post_id)
{
    $post_id = (int) $post_id;
    if ($post_id <= 0 || !function_exists('pll_current_language') || !function_exists('pll_get_post')) {
        return $post_id;
    }

    $lang = pll_current_language('slug');
    if (!is_string($lang) || $lang === '') {
        return $post_id;
    }

    $translated_id = (int) pll_get_post($post_id, $lang);
    return $translated_id > 0 ? $translated_id : $post_id;
}

/**
 * Read an SEO ACF field for the current (or given) post.
 *
 * @param string   $field_name Field name.
 * @param int|null $post_id    Optional post ID.
 * @return string
 */
function dilijanvillas_get_seo_field($field_name, $post_id = null)
{
    if (!function_exists('get_field')) {
        return '';
    }

    if ($post_id === null) {
        $post_id = dilijanvillas_get_seo_post_id();
    }

    $post_id = (int) $post_id;
    if ($post_id <= 0) {
        return '';
    }

    return trim((string) get_field($field_name, $post_id));
}

/**
 * Register the SEO field group for all Pages.
 */
function dilijanvillas_register_seo_field_group()
{
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group(
        array(
            'key' => 'group_dilijanvillas_seo',
            'title' => 'SEO',
            'fields' => array(
                array(
                    'key' => 'field_dv_seo_tab',
                    'label' => 'SEO',
                    'name' => '',
                    'type' => 'tab',
                    'instructions' => '',
                    'required' => 0,
                    'placement' => 'top',
                    'endpoint' => 0,
                ),
                array(
                    'key' => 'field_dv_seo_title',
                    'label' => 'SEO Title',
                    'name' => 'seo_title',
                    'type' => 'text',
                    'instructions' => 'Optional. Overrides the browser/search title for this page. Aim for about 50–60 characters. Leave empty to use the default WordPress title.',
                    'required' => 0,
                    'default_value' => '',
                    'maxlength' => 70,
                    'placeholder' => '',
                    'prepend' => '',
                    'append' => '',
                ),
                array(
                    'key' => 'field_dv_seo_description',
                    'label' => 'Meta Description',
                    'name' => 'seo_description',
                    'type' => 'textarea',
                    'instructions' => 'Optional. Search-result snippet for this page. Aim for about 150–160 characters. Leave empty to omit a custom meta description.',
                    'required' => 0,
                    'default_value' => '',
                    'maxlength' => 200,
                    'rows' => 3,
                    'new_lines' => '',
                    'placeholder' => '',
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'page',
                    ),
                ),
            ),
            'menu_order' => 30,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'hide_on_screen' => '',
            'active' => true,
            'description' => 'Per-page SEO title and meta description (works with Polylang translations).',
            'show_in_rest' => 0,
        )
    );
}
add_action('acf/init', 'dilijanvillas_register_seo_field_group');

/**
 * Use ACF SEO Title when set (unless an SEO plugin handles titles).
 *
 * @param array<string, string> $parts Title parts.
 * @return array<string, string>
 */
function dilijanvillas_filter_document_title_parts($parts)
{
    if (dilijanvillas_seo_plugin_active() || is_admin()) {
        return $parts;
    }

    $seo_title = dilijanvillas_get_seo_field('seo_title');
    if ($seo_title === '') {
        return $parts;
    }

    $parts['title'] = $seo_title;
    // Custom SEO titles are usually complete; drop site name suffix.
    unset($parts['site'], $parts['tagline']);

    return $parts;
}
add_filter('document_title_parts', 'dilijanvillas_filter_document_title_parts', 20);

/**
 * Resolve the best meta description for the current page.
 *
 * Falls back through: explicit SEO field -> the page's own ACF content
 * fields -> the excerpt -> the site tagline, so a description is always
 * output even when the SEO field was left empty in the admin.
 *
 * @return string Clean, length-limited description (may be empty).
 */
function dilijanvillas_resolve_seo_description()
{
    // 1. Explicit per-page SEO field.
    $description = dilijanvillas_get_seo_field('seo_description');

    // 2. Fall back to the page's own ACF content fields (cottage / villa pages).
    if ($description === '') {
        foreach (array('description', 'description_cottage') as $field) {
            $value = dilijanvillas_get_seo_field($field);
            if ($value !== '') {
                $description = $value;
                break;
            }
        }
    }

    // 3. Fall back to the WordPress excerpt.
    if ($description === '') {
        $post_id = dilijanvillas_get_seo_post_id();
        if ($post_id > 0) {
            $excerpt = get_the_excerpt($post_id);
            if (is_string($excerpt)) {
                $description = $excerpt;
            }
        }
    }

    // 4. Final fallback: the site tagline.
    if ($description === '') {
        $description = (string) get_bloginfo('description');
    }

    // Normalise: drop shortcodes/markup, collapse whitespace.
    $description = wp_strip_all_tags(strip_shortcodes((string) $description));
    $description = preg_replace('/\s+/u', ' ', (string) $description);
    $description = trim((string) $description);

    // Keep to a sensible snippet length (~160 chars).
    if (function_exists('mb_strlen') && mb_strlen($description) > 160) {
        $description = rtrim(mb_substr($description, 0, 157)) . '…';
    } elseif (!function_exists('mb_strlen') && strlen($description) > 160) {
        $description = rtrim(substr($description, 0, 157)) . '…';
    }

    return $description;
}

/**
 * Best Open Graph image for the current page (featured image with fallback).
 *
 * @return string Absolute image URL.
 */
function dilijanvillas_resolve_og_image()
{
    $post_id = dilijanvillas_get_seo_post_id();
    if ($post_id > 0 && has_post_thumbnail($post_id)) {
        $url = get_the_post_thumbnail_url($post_id, 'large');
        if (is_string($url) && $url !== '') {
            return $url;
        }
    }

    return get_template_directory_uri()
        . '/images/504327955_17896732026230600_4835684111846667008_n.jpg';
}

/**
 * Canonical URL for the current request.
 *
 * @return string
 */
function dilijanvillas_current_url()
{
    if (is_singular()) {
        $permalink = get_permalink();
        if (is_string($permalink) && $permalink !== '') {
            return $permalink;
        }
    }

    global $wp;
    $path = isset($wp->request) ? (string) $wp->request : '';

    return home_url($path === '' ? '/' : '/' . trailingslashit($path));
}

/**
 * Output meta description (with fallback) for every page.
 */
function dilijanvillas_output_seo_meta_description()
{
    if (dilijanvillas_seo_plugin_active() || is_admin()) {
        return;
    }

    $description = dilijanvillas_resolve_seo_description();
    if ($description === '') {
        return;
    }

    echo '<meta name="description" content="' . esc_attr($description) . '" />' . "\n";
}
add_action('wp_head', 'dilijanvillas_output_seo_meta_description', 1);

/**
 * Output Open Graph + Twitter Card tags so shared links show a proper
 * title, description and image preview (Facebook, WhatsApp, Telegram, etc.).
 */
function dilijanvillas_output_open_graph_tags()
{
    if (dilijanvillas_seo_plugin_active() || is_admin() || is_feed()) {
        return;
    }

    $title = wp_get_document_title();
    $description = dilijanvillas_resolve_seo_description();
    $url = dilijanvillas_current_url();
    $image = dilijanvillas_resolve_og_image();
    $type = (is_singular() && !is_front_page()) ? 'article' : 'website';

    // Normalise the locale to Facebook's language_TERRITORY format.
    $locale = get_locale();
    if (strpos($locale, '_') === false) {
        $locale_map = array('hy' => 'hy_AM', 'en' => 'en_US', 'ru' => 'ru_RU');
        $locale = isset($locale_map[$locale]) ? $locale_map[$locale] : $locale;
    }

    $meta = array(
        array('property', 'og:type', $type),
        array('property', 'og:site_name', (string) get_bloginfo('name')),
        array('property', 'og:locale', $locale),
        array('property', 'og:url', $url),
        array('property', 'og:title', $title),
        array('property', 'og:description', $description),
        array('property', 'og:image', $image),
        array('name', 'twitter:card', 'summary_large_image'),
        array('name', 'twitter:title', $title),
        array('name', 'twitter:description', $description),
        array('name', 'twitter:image', $image),
    );

    foreach ($meta as $tag) {
        list($attr, $key, $value) = $tag;
        if ($value === '' || $value === null) {
            continue;
        }
        echo '<meta ' . $attr . '="' . esc_attr($key) . '" content="'
            . esc_attr($value) . '" />' . "\n";
    }
}
add_action('wp_head', 'dilijanvillas_output_open_graph_tags', 5);

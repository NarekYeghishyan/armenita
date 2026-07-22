<?php
/**
 * Polylang homepage + menu helpers.
 *
 * @package dilijanvillas
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Current language slug (hy, en, ru).
 */
function dilijanvillas_get_current_lang_slug()
{
    if (!function_exists('pll_current_language')) {
        return '';
    }

    $slug = pll_current_language('slug');
    return is_string($slug) ? $slug : '';
}

/**
 * Homepage ID for the current Polylang language.
 */
function dilijanvillas_get_home_page_id()
{
    static $resolved = null;
    if ($resolved !== null) {
        return $resolved;
    }

    $resolved = 0;

    if (function_exists('PLL') && isset(PLL()->curlang->page_on_front)) {
        $lang_front_page_id = (int) PLL()->curlang->page_on_front;
        if ($lang_front_page_id > 0) {
            $resolved = $lang_front_page_id;
            return $resolved;
        }
    }

    $front_page_id = (int) get_option('page_on_front');
    if ($front_page_id <= 0) {
        return $resolved;
    }

    $lang = dilijanvillas_get_current_lang_slug();
    if ($lang && function_exists('pll_get_post')) {
        $translated_id = (int) pll_get_post($front_page_id, $lang);
        if ($translated_id > 0) {
            $resolved = $translated_id;
            return $resolved;
        }
    }

    $resolved = $front_page_id;
    return $resolved;
}

/**
 * Whether a page is the homepage (any Polylang translation).
 */
function dilijanvillas_is_home_page($page_id = 0)
{
    if ($page_id <= 0) {
        if (is_front_page() && is_page()) {
            return true;
        }

        $page_id = (int) get_queried_object_id();
    }

    if ($page_id <= 0) {
        return false;
    }

    if (function_exists('PLL') && isset(PLL()->curlang->page_on_front)) {
        if ($page_id === (int) PLL()->curlang->page_on_front) {
            return true;
        }
    }

    $front_page_id = (int) get_option('page_on_front');
    if ($front_page_id > 0 && $page_id === $front_page_id) {
        return true;
    }

    if (!function_exists('pll_get_post_translations') || $front_page_id <= 0) {
        return false;
    }

    $translations = pll_get_post_translations($front_page_id);
    if (!is_array($translations)) {
        return false;
    }

    return in_array($page_id, array_map('intval', $translations), true);
}

/**
 * Use the homepage layout for every translated front page.
 */
function dilijanvillas_force_home_template($template)
{
    if (!is_page() || is_admin()) {
        return $template;
    }

    if (!dilijanvillas_is_home_page()) {
        return $template;
    }

    $home_template = locate_template(array('front-page.php', 'index.php'));
    return $home_template !== '' ? $home_template : $template;
}
add_filter('template_include', 'dilijanvillas_force_home_template', 99);

/**
 * Resolve a nav menu ID for the current language.
 */
function dilijanvillas_get_nav_menu_id($location)
{
    $locations = get_nav_menu_locations();
    if (empty($locations[$location])) {
        return 0;
    }

    $menu_id = (int) $locations[$location];
    $lang = dilijanvillas_get_current_lang_slug();

    if ($lang !== '') {
        $polylang = get_option('polylang');
        $theme_slug = get_option('stylesheet');
        if (
            is_array($polylang)
            && !empty($polylang['nav_menus'][$theme_slug][$location][$lang])
        ) {
            return (int) $polylang['nav_menus'][$theme_slug][$location][$lang];
        }

        if (function_exists('pll_get_term')) {
            $translated_menu_id = (int) pll_get_term($menu_id, $lang);
            if ($translated_menu_id > 0) {
                return $translated_menu_id;
            }
        }
    }

    return $menu_id;
}

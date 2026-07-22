<?php
/**
 * ACF location rules for Polylang (language + per-language front pages).
 *
 * @package dilijanvillas
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register custom ACF location rule types.
 */
function dilijanvillas_register_acf_polylang_locations()
{
    if (!function_exists('acf_register_location_type')) {
        return;
    }

    acf_register_location_type('Dilijanvillas_ACF_Location_Polylang_Language');
    acf_register_location_type('Dilijanvillas_ACF_Location_Polylang_Front_Page');
    acf_register_location_type('Dilijanvillas_ACF_Location_All_Home_Pages');
}
add_action('acf/init', 'dilijanvillas_register_acf_polylang_locations');

/**
 * Treat "Front Page" as every Polylang homepage translation (HY/EN/RU).
 */
function dilijanvillas_acf_match_polylang_front_page($result, $rule, $screen, $field_group)
{
    if (($rule['param'] ?? '') !== 'page_type' || ($rule['value'] ?? '') !== 'front_page') {
        return $result;
    }

    $post_id = isset($screen['post_id']) ? (int) $screen['post_id'] : 0;
    if ($post_id <= 0) {
        return $result;
    }

    return dilijanvillas_is_home_page($post_id);
}
add_filter('acf/location/rule_match/page_type', 'dilijanvillas_acf_match_polylang_front_page', 20, 4);

/**
 * Append language code to page titles in ACF location dropdowns.
 * Rebuilds the list with pages from every Polylang language.
 *
 * @param array $choices Page choices.
 * @return array
 */
function dilijanvillas_acf_page_location_choices($choices)
{
    if (!is_array($choices)) {
        $choices = array();
    }

    if (!function_exists('pll_languages_list')) {
        return $choices;
    }

    $query_args = array(
        'post_type' => 'page',
        'post_status' => array('publish', 'draft', 'private', 'pending', 'future'),
        'posts_per_page' => -1,
        'orderby' => array(
            'menu_order' => 'ASC',
            'title' => 'ASC',
        ),
        'order' => 'ASC',
        'lang' => '',
        'suppress_filters' => false,
    );

    $pages = get_posts($query_args);
    if (empty($pages)) {
        $query_args['suppress_filters'] = true;
        unset($query_args['lang']);
        $pages = get_posts($query_args);
    }

    if (empty($pages)) {
        return $choices;
    }

    $all_choices = array();
    foreach ($pages as $page) {
        $page_id = (int) $page->ID;
        if ($page_id <= 0) {
            continue;
        }

        $title = get_the_title($page_id);
        if ($title === '') {
            $title = sprintf(__('Page #%d', 'dilijanvillas'), $page_id);
        }

        $lang = function_exists('pll_get_post_language')
            ? pll_get_post_language($page_id, 'slug')
            : '';
        if ($lang !== '') {
            $title = sprintf('%s [%s]', $title, strtoupper($lang));
        }

        $all_choices[$page_id] = $title;
    }

    return !empty($all_choices) ? $all_choices : $choices;
}
add_filter('acf/location/rule_values/page', 'dilijanvillas_acf_page_location_choices');

if (!class_exists('Dilijanvillas_ACF_Location_Polylang_Language') && class_exists('ACF_Location')) :

class Dilijanvillas_ACF_Location_Polylang_Language extends ACF_Location
{
    public function initialize()
    {
        $this->name = 'polylang_language';
        $this->label = __('Language', 'dilijanvillas');
        $this->category = 'Polylang';
        $this->object_type = 'post';
        $this->object_subtype = 'page';
    }

    public function match($rule, $screen, $field_group)
    {
        $post_id = isset($screen['post_id']) ? (int) $screen['post_id'] : 0;
        if ($post_id <= 0 || !function_exists('pll_get_post_language')) {
            return false;
        }

        $post_lang = pll_get_post_language($post_id, 'slug');
        if ($post_lang === '') {
            return false;
        }

        return $this->compare_to_rule($post_lang, $rule);
    }

    public function get_values($rule)
    {
        return dilijanvillas_acf_get_polylang_language_choices();
    }
}

endif;

if (!class_exists('Dilijanvillas_ACF_Location_Polylang_Front_Page') && class_exists('ACF_Location')) :

class Dilijanvillas_ACF_Location_Polylang_Front_Page extends ACF_Location
{
    public function initialize()
    {
        $this->name = 'polylang_front_page';
        $this->label = __('Front Page (by language)', 'dilijanvillas');
        $this->category = 'Polylang';
        $this->object_type = 'post';
        $this->object_subtype = 'page';
    }

    public function match($rule, $screen, $field_group)
    {
        $post_id = isset($screen['post_id']) ? (int) $screen['post_id'] : 0;
        $lang_slug = isset($rule['value']) ? (string) $rule['value'] : '';

        if ($post_id <= 0 || $lang_slug === '') {
            return false;
        }

        if (function_exists('PLL') && isset(PLL()->model)) {
            $language = PLL()->model->get_language($lang_slug);
            if ($language && isset($language->page_on_front) && (int) $language->page_on_front === $post_id) {
                return true;
            }
        }

        if (!function_exists('pll_get_post_language')) {
            return false;
        }

        return pll_get_post_language($post_id, 'slug') === $lang_slug && dilijanvillas_is_home_page($post_id);
    }

    public function get_values($rule)
    {
        $choices = array();

        if (function_exists('PLL') && isset(PLL()->model)) {
            foreach (PLL()->model->get_languages_list() as $language) {
                $page_id = isset($language->page_on_front) ? (int) $language->page_on_front : 0;
                if ($page_id <= 0) {
                    continue;
                }

                $choices[$language->slug] = sprintf(
                    '%s — %s',
                    $language->name,
                    get_the_title($page_id)
                );
            }

            return $choices;
        }

        if (function_exists('pll_languages_list')) {
            $languages = pll_languages_list(array('fields' => false));
            if (is_array($languages)) {
                foreach ($languages as $language) {
                    $page_id = isset($language->page_on_front) ? (int) $language->page_on_front : 0;
                    if ($page_id <= 0) {
                        continue;
                    }

                    $choices[$language->slug] = sprintf(
                        '%s — %s',
                        $language->name,
                        get_the_title($page_id)
                    );
                }
            }
        }

        return $choices;
    }
}

endif;

if (!class_exists('Dilijanvillas_ACF_Location_All_Home_Pages') && class_exists('ACF_Location')) :

class Dilijanvillas_ACF_Location_All_Home_Pages extends ACF_Location
{
    public function initialize()
    {
        $this->name = 'all_home_pages';
        $this->label = __('Home Pages (all languages)', 'dilijanvillas');
        $this->category = 'Polylang';
        $this->object_type = 'post';
        $this->object_subtype = 'page';
    }

    public function match($rule, $screen, $field_group)
    {
        $post_id = isset($screen['post_id']) ? (int) $screen['post_id'] : 0;
        if ($post_id <= 0) {
            return false;
        }

        if (($rule['value'] ?? '') !== 'all') {
            return false;
        }

        return dilijanvillas_is_home_page($post_id);
    }

    public function get_values($rule)
    {
        $labels = array();

        if (function_exists('PLL') && isset(PLL()->model)) {
            foreach (PLL()->model->get_languages_list() as $language) {
                $page_id = isset($language->page_on_front) ? (int) $language->page_on_front : 0;
                if ($page_id <= 0) {
                    continue;
                }

                $labels[] = sprintf('%s: %s', $language->name, get_the_title($page_id));
            }
        }

        $summary = !empty($labels) ? implode(' · ', $labels) : __('HY, EN, RU homepages', 'dilijanvillas');

        return array(
            'all' => $summary,
        );
    }
}

endif;

/**
 * Language slug => label map for ACF location rules.
 *
 * @return array<string, string>
 */
function dilijanvillas_acf_get_polylang_language_choices()
{
    $choices = array();

    if (function_exists('PLL') && isset(PLL()->model)) {
        foreach (PLL()->model->get_languages_list() as $language) {
            $choices[$language->slug] = $language->name;
        }

        return $choices;
    }

    if (function_exists('pll_languages_list')) {
        $languages = pll_languages_list(array('fields' => false));
        if (is_array($languages)) {
            foreach ($languages as $language) {
                if (isset($language->slug, $language->name)) {
                    $choices[$language->slug] = $language->name;
                }
            }
        }
    }

    return $choices;
}

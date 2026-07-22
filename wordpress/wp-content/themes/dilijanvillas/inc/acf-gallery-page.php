<?php
/**
 * ACF field group for the Gallery page template.
 *
 * @package dilijanvillas
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('DILIJANVILLAS_GALLERY_ACF_VERSION')) {
    define('DILIJANVILLAS_GALLERY_ACF_VERSION', '3');
}

/**
 * Load ACF JSON from the theme folder (append; do not remove other paths).
 *
 * @param array $paths Existing load paths.
 * @return array
 */
function dilijanvillas_acf_json_load_point($paths)
{
    $theme_json = get_template_directory() . '/acf-json';
    if (!in_array($theme_json, $paths, true)) {
        $paths[] = $theme_json;
    }

    return $paths;
}
add_filter('acf/settings/load_json', 'dilijanvillas_acf_json_load_point');

/**
 * Match Gallery ACF location for gallery.php and path variants.
 *
 * @param bool  $result      Default match result.
 * @param array $rule        Location rule.
 * @param array $screen      Current screen.
 * @param array $field_group Field group.
 * @return bool
 */
function dilijanvillas_acf_match_gallery_page_template($result, $rule, $screen, $field_group)
{
    if (($rule['param'] ?? '') !== 'page_template') {
        return $result;
    }

    $expected = (string) ($rule['value'] ?? '');
    if ($expected !== 'gallery.php' && substr(str_replace('\\', '/', $expected), -strlen('/gallery.php')) !== '/gallery.php') {
        return $result;
    }

    $post_id = isset($screen['post_id']) ? (int) $screen['post_id'] : 0;
    if ($post_id <= 0 && !empty($screen['page_template'])) {
        $template = str_replace('\\', '/', (string) $screen['page_template']);
        $is_gallery = $template === 'gallery.php' || substr($template, -strlen('/gallery.php')) === '/gallery.php';
        return ($rule['operator'] ?? '==') === '!=' ? !$is_gallery : $is_gallery;
    }

    if ($post_id <= 0) {
        return $result;
    }

    $is_gallery = function_exists('dilijanvillas_is_gallery_page_template')
        ? dilijanvillas_is_gallery_page_template($post_id)
        : ((string) get_page_template_slug($post_id) === 'gallery.php');

    return ($rule['operator'] ?? '==') === '!=' ? !$is_gallery : $is_gallery;
}
add_filter('acf/location/rule_match/page_template', 'dilijanvillas_acf_match_gallery_page_template', 20, 4);

/**
 * Register Gallery page fields in admin (template: gallery.php).
 */
function dilijanvillas_register_gallery_page_acf_fields()
{
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group(
        array(
            'key' => 'group_dv_gallery_page',
            'title' => 'Gallery Page',
            'fields' => array(
                array(
                    'key' => 'field_dv_gallery_tab_hero',
                    'label' => 'Hero',
                    'name' => 'hero',
                    'type' => 'tab',
                    'placement' => 'top',
                ),
                array(
                    'key' => 'field_dv_gallery_video_home',
                    'label' => 'Video',
                    'name' => 'video_home',
                    'type' => 'file',
                    'return_format' => 'url',
                    'library' => 'all',
                    'mime_types' => 'mp4,webm,mov',
                ),
                array(
                    'key' => 'field_dv_gallery_beground_img',
                    'label' => 'Background image',
                    'name' => 'beground_img',
                    'type' => 'image',
                    'return_format' => 'url',
                    'preview_size' => 'medium',
                    'library' => 'all',
                ),
                array(
                    'key' => 'field_dv_gallery_title_small',
                    'label' => 'Title small',
                    'name' => 'title_small',
                    'type' => 'text',
                ),
                array(
                    'key' => 'field_dv_gallery_title_big',
                    'label' => 'Title big',
                    'name' => 'title_big',
                    'type' => 'text',
                ),
                array(
                    'key' => 'field_dv_gallery_description',
                    'label' => 'Description',
                    'name' => 'description',
                    'type' => 'textarea',
                    'rows' => 3,
                ),
                array(
                    'key' => 'field_dv_gallery_text_direction',
                    'label' => 'Text direction',
                    'name' => 'text_direction',
                    'type' => 'select',
                    'choices' => array(
                        'Center' => 'Center',
                        'Bottom' => 'Bottom',
                    ),
                    'default_value' => 'Center',
                    'return_format' => 'value',
                ),
                array(
                    'key' => 'field_dv_gallery_text_orentation',
                    'label' => 'Text orentation',
                    'name' => 'text_orentation',
                    'type' => 'select',
                    'choices' => array(
                        'Left' => 'Left',
                        'Center' => 'Center',
                    ),
                    'default_value' => 'Center',
                    'return_format' => 'value',
                ),
                array(
                    'key' => 'field_dv_gallery_tab_gallery',
                    'label' => 'Gallery',
                    'name' => 'gallery_section',
                    'type' => 'tab',
                    'placement' => 'top',
                ),
                array(
                    'key' => 'field_dv_gallery_page',
                    'label' => 'Gallery',
                    'name' => 'gallery_page',
                    'type' => 'gallery',
                    'instructions' => 'Add photos for the gallery page grid.',
                    'return_format' => 'array',
                    'preview_size' => 'medium',
                    'insert' => 'append',
                    'library' => 'all',
                ),
                array(
                    'key' => 'field_dv_gallery_bg_section',
                    'label' => 'Background of section gallery',
                    'name' => 'background_of_section_gallery',
                    'type' => 'image',
                    'return_format' => 'url',
                    'preview_size' => 'medium',
                    'library' => 'all',
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'page_template',
                        'operator' => '==',
                        'value' => 'gallery.php',
                    ),
                ),
            ),
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'hide_on_screen' => array(
                'the_content',
            ),
            'active' => true,
        )
    );
}
add_action('acf/init', 'dilijanvillas_register_gallery_page_acf_fields', 5);

/**
 * Import the Gallery field group into the DB once so it appears in ACF admin.
 */
function dilijanvillas_import_gallery_page_acf_group()
{
    if (!function_exists('acf_get_field_group') || !function_exists('acf_import_field_group')) {
        return;
    }

    if ((string) get_option('dilijanvillas_gallery_acf_imported', '') === DILIJANVILLAS_GALLERY_ACF_VERSION) {
        return;
    }

    $existing = acf_get_field_group('group_dv_gallery_page');
    if (!empty($existing)) {
        update_option('dilijanvillas_gallery_acf_imported', DILIJANVILLAS_GALLERY_ACF_VERSION, false);
        return;
    }

    $json_path = get_template_directory() . '/acf-json/group_dv_gallery_page.json';
    if (is_readable($json_path)) {
        $json = json_decode((string) file_get_contents($json_path), true);
        if (is_array($json) && !empty($json['key'])) {
            acf_import_field_group($json);
            update_option('dilijanvillas_gallery_acf_imported', DILIJANVILLAS_GALLERY_ACF_VERSION, false);
            return;
        }
    }

    if (!function_exists('acf_get_local_field_group') || !function_exists('acf_get_local_fields')) {
        return;
    }

    $field_group = acf_get_local_field_group('group_dv_gallery_page');
    if (empty($field_group)) {
        return;
    }

    $field_group['fields'] = acf_get_local_fields($field_group['key']);
    acf_import_field_group($field_group);
    update_option('dilijanvillas_gallery_acf_imported', DILIJANVILLAS_GALLERY_ACF_VERSION, false);
}
add_action('acf/init', 'dilijanvillas_import_gallery_page_acf_group', 20);

/**
 * Remind editors to choose the Gallery template when creating the page.
 */
function dilijanvillas_gallery_page_admin_notice()
{
    if (!is_admin() || !function_exists('get_current_screen')) {
        return;
    }

    $screen = get_current_screen();
    if (!$screen || $screen->base !== 'post' || $screen->post_type !== 'page') {
        return;
    }

    $post_id = isset($_GET['post']) ? (int) $_GET['post'] : 0;
    if ($post_id <= 0) {
        return;
    }

    if (function_exists('dilijanvillas_is_gallery_page_template') && dilijanvillas_is_gallery_page_template($post_id)) {
        return;
    }

    $post = get_post($post_id);
    if (!$post) {
        return;
    }

    $slug = sanitize_title($post->post_name);
    $title = sanitize_title($post->post_title);
    $looks_like_gallery = in_array($slug, array('gallery', 'gallery-2', 'patkerasrah', 'galeriya', 'galereya'), true)
        || in_array($title, array('gallery', 'patkerasrah', 'galeriya', 'galereya', 'galerea'), true);

    if (!$looks_like_gallery) {
        return;
    }

    echo '<div class="notice notice-warning"><p>';
    echo esc_html__('Select page template "Gallery" under Page Attributes, then add photos in the Gallery field (gallery_page). Also add this page to Appearance → Menus (header_general) for each language if you manage menus manually.', 'dilijanvillas');
    echo '</p></div>';
}
add_action('admin_notices', 'dilijanvillas_gallery_page_admin_notice');

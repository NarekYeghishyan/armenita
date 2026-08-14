<?php
/**
 * "Events and activates" → Tours: per-row poster image for the home page card.
 *
 * The home page events grid picks the first item of the tour gallery, so a tour
 * whose gallery starts on a video falls back to the shared page background and
 * the editor has no way to choose the still per card. This adds a "Poster"
 * image subfield to the Tours repeater. That field group lives in the database
 * (not in acf-json), and a local subfield would hide the group's own database
 * subfields — ACF reads either the local children or the database ones, never
 * both — so the subfield is written into the database once instead.
 *
 * @package dilijanvillas
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('DILIJANVILLAS_EVENTS_TOURS_POSTER_VERSION')) {
    define('DILIJANVILLAS_EVENTS_TOURS_POSTER_VERSION', '1');
}

/** Tours repeater of the "Events and activates" field group. */
if (!defined('DILIJANVILLAS_EVENTS_TOURS_FIELD_KEY')) {
    define('DILIJANVILLAS_EVENTS_TOURS_FIELD_KEY', 'field_6a00ee85a1ab8');
}

/** Poster subfield created by this file. */
if (!defined('DILIJANVILLAS_EVENTS_TOURS_POSTER_FIELD_KEY')) {
    define('DILIJANVILLAS_EVENTS_TOURS_POSTER_FIELD_KEY', 'field_dv_tours_poster');
}

/**
 * Locate the Tours repeater field.
 *
 * The key lookup covers the current database; the name lookup is the fallback
 * for a database where the group was re-created. The events group also holds a
 * tab named "tours", hence the type check.
 *
 * @return array Field array, or an empty array when not found.
 */
function dilijanvillas_get_events_tours_repeater()
{
    if (!function_exists('acf_get_field')) {
        return array();
    }

    $field = acf_get_field(DILIJANVILLAS_EVENTS_TOURS_FIELD_KEY);
    if (is_array($field) && !empty($field['ID']) && ($field['type'] ?? '') === 'repeater') {
        return $field;
    }

    $field_posts = get_posts(
        array(
            'post_type' => 'acf-field',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'suppress_filters' => false,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'acf_field_name' => 'tours',
        )
    );

    foreach ($field_posts as $field_post) {
        $candidate = acf_get_field($field_post->post_name);
        if (is_array($candidate) && !empty($candidate['ID']) && ($candidate['type'] ?? '') === 'repeater') {
            return $candidate;
        }
    }

    return array();
}

/**
 * Add the "Poster" subfield to the Tours repeater once.
 */
function dilijanvillas_install_events_tours_poster_field()
{
    if (!function_exists('acf_get_field') || !function_exists('acf_update_field') || !function_exists('acf_get_fields')) {
        return;
    }

    if ((string) get_option('dilijanvillas_events_tours_poster_field', '') === DILIJANVILLAS_EVENTS_TOURS_POSTER_VERSION) {
        return;
    }

    $repeater = dilijanvillas_get_events_tours_repeater();
    if (empty($repeater['ID'])) {
        // The group may not be imported yet — retry on a later request.
        return;
    }

    $sub_fields = !empty($repeater['sub_fields']) && is_array($repeater['sub_fields'])
        ? $repeater['sub_fields']
        : acf_get_fields($repeater);

    foreach ((array) $sub_fields as $sub_field) {
        if (is_array($sub_field) && ($sub_field['name'] ?? '') === 'poster') {
            update_option('dilijanvillas_events_tours_poster_field', DILIJANVILLAS_EVENTS_TOURS_POSTER_VERSION, false);
            return;
        }
    }

    acf_update_field(
        array(
            'key' => DILIJANVILLAS_EVENTS_TOURS_POSTER_FIELD_KEY,
            'label' => 'Poster (home page card)',
            'name' => 'poster',
            'type' => 'image',
            'instructions' => 'Image for this tour in the "Events and activities" cards on the home page. Leave empty to keep using the gallery.',
            'required' => 0,
            'return_format' => 'url',
            'preview_size' => 'medium',
            'library' => 'all',
            'parent' => (int) $repeater['ID'],
            'menu_order' => 20,
        )
    );

    update_option('dilijanvillas_events_tours_poster_field', DILIJANVILLAS_EVENTS_TOURS_POSTER_VERSION, false);
}
add_action('acf/init', 'dilijanvillas_install_events_tours_poster_field', 25);

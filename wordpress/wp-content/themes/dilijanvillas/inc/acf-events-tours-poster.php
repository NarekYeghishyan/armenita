<?php
/**
 * "Events and activates" → Tours: per-row poster image for the home page card.
 *
 * The home page events grid shows the first item of the tour gallery, so a tour
 * whose gallery opens on a video falls back to the shared page background and
 * the editor cannot choose the still per card.
 *
 * The events field group is not stored in the database — it ships as local JSON
 * (acf-json/group_6a00dd1619d2a.json), and for a local group ACF reads the
 * fields from its local store and ignores the acf-field rows in the database
 * entirely. So the subfield is registered the same way the group itself is:
 * as a local field parented to the Tours repeater. It is appended right after
 * the JSON group is included, which puts it last in every Tours row.
 *
 * @package dilijanvillas
 */

if (!defined('ABSPATH')) {
    exit;
}

/** Tours repeater of the "Events and activates" field group. */
if (!defined('DILIJANVILLAS_EVENTS_TOURS_FIELD_KEY')) {
    define('DILIJANVILLAS_EVENTS_TOURS_FIELD_KEY', 'field_6a00ee85a1ab8');
}

/** Poster subfield added by this file. */
if (!defined('DILIJANVILLAS_EVENTS_TOURS_POSTER_FIELD_KEY')) {
    define('DILIJANVILLAS_EVENTS_TOURS_POSTER_FIELD_KEY', 'field_dv_tours_poster');
}

/**
 * Settings of the poster subfield.
 *
 * @return array
 */
function dilijanvillas_events_tours_poster_field_settings()
{
    return array(
        'key' => DILIJANVILLAS_EVENTS_TOURS_POSTER_FIELD_KEY,
        'parent' => DILIJANVILLAS_EVENTS_TOURS_FIELD_KEY,
        'label' => 'Poster (home page card)',
        'name' => 'poster',
        'type' => 'image',
        'instructions' => 'Image for this tour in the "Events and activities" cards on the home page. Leave empty to keep using the gallery.',
        'required' => 0,
        'return_format' => 'url',
        'preview_size' => 'medium',
        'library' => 'all',
    );
}

/**
 * Whether the repeater already carries a "poster" subfield.
 *
 * Saving the field group in wp-admin persists whatever the editor screen shows,
 * including this subfield, so the check keeps it from being added twice.
 *
 * @param array $sub_fields Subfields of the repeater.
 * @return bool
 */
function dilijanvillas_events_tours_has_poster_field($sub_fields)
{
    foreach ((array) $sub_fields as $sub_field) {
        if (is_array($sub_field) && ($sub_field['name'] ?? '') === 'poster') {
            return true;
        }
    }

    return false;
}

/**
 * Append the poster subfield to the local Tours repeater.
 *
 * Runs on acf/include_fields after the local JSON groups are registered, and
 * reads only the local store: acf_get_field() would cache the repeater without
 * the new subfield for the rest of the request.
 */
function dilijanvillas_register_events_tours_poster_field()
{
    if (!function_exists('acf_get_local_field') || !function_exists('acf_add_local_field')) {
        return;
    }

    $repeater = acf_get_local_field(DILIJANVILLAS_EVENTS_TOURS_FIELD_KEY);
    if (!is_array($repeater) || ($repeater['type'] ?? '') !== 'repeater') {
        // Group lives in the database — handled by the load_field filter below.
        return;
    }

    if (dilijanvillas_events_tours_has_poster_field(acf_get_local_fields(DILIJANVILLAS_EVENTS_TOURS_FIELD_KEY))) {
        return;
    }

    acf_add_local_field(dilijanvillas_events_tours_poster_field_settings());
}
add_action('acf/include_fields', 'dilijanvillas_register_events_tours_poster_field', 20);

/**
 * Same subfield for a database-stored repeater.
 *
 * A local subfield cannot be used there: ACF reads either the local children or
 * the database ones, so registering it locally would hide the group's own
 * subfields. Appending on load keeps both.
 *
 * @param array $field Repeater field.
 * @return array
 */
function dilijanvillas_append_events_tours_poster_field($field)
{
    if (!is_array($field) || ($field['type'] ?? '') !== 'repeater') {
        return $field;
    }

    if (function_exists('acf_is_local_field') && acf_is_local_field(DILIJANVILLAS_EVENTS_TOURS_FIELD_KEY)) {
        return $field;
    }

    $sub_fields = isset($field['sub_fields']) && is_array($field['sub_fields']) ? $field['sub_fields'] : array();
    if (dilijanvillas_events_tours_has_poster_field($sub_fields)) {
        return $field;
    }

    $poster = dilijanvillas_events_tours_poster_field_settings();
    $poster['parent'] = $field['key'];
    $sub_fields[] = function_exists('acf_validate_field') ? acf_validate_field($poster) : $poster;
    $field['sub_fields'] = $sub_fields;

    return $field;
}
add_filter('acf/load_field/key=' . DILIJANVILLAS_EVENTS_TOURS_FIELD_KEY, 'dilijanvillas_append_events_tours_poster_field');

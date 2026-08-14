<?php
/**
 * "Events and activates": per-row poster image for the home page cards.
 *
 * The home page events grid shows the first item of the row gallery, so a row
 * whose gallery opens on a video falls back to the shared page background and
 * the editor cannot choose the still per card. Both repeaters that feed that
 * grid — Tours and Diving experience — get a "Poster" image subfield.
 *
 * The events field group is not stored in the database — it ships as local JSON
 * (acf-json/group_6a00dd1619d2a.json), and for a local group ACF reads the
 * fields from its local store and ignores the acf-field rows in the database
 * entirely. So the subfields are registered the same way the group itself is:
 * as local fields parented to each repeater. They are appended right after the
 * JSON group is included, which puts the poster last in every row.
 *
 * @package dilijanvillas
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Repeaters of the "Events and activates" group that feed the home page grid.
 *
 * Keyed by field name, which is also the fallback lookup when a database is
 * rebuilt with different field keys.
 *
 * @return array<string,array{key:string,poster_key:string}>
 */
function dilijanvillas_events_poster_repeaters()
{
    return array(
        'tours' => array(
            'key' => 'field_6a00ee85a1ab8',
            'poster_key' => 'field_dv_tours_poster',
        ),
        'diving_experience' => array(
            'key' => 'field_6a00efd5481b0',
            'poster_key' => 'field_dv_diving_poster',
        ),
    );
}

/**
 * Settings of one poster subfield.
 *
 * @param string $poster_key  Key of the poster field.
 * @param string $parent_key  Key of the repeater it belongs to.
 * @return array
 */
function dilijanvillas_events_poster_field_settings($poster_key, $parent_key)
{
    return array(
        'key' => $poster_key,
        'parent' => $parent_key,
        'label' => 'Poster (home page card)',
        'name' => 'poster',
        'type' => 'image',
        'instructions' => 'Image for this card in the "Events and activities" section on the home page. Leave empty to keep using the gallery.',
        'required' => 0,
        'return_format' => 'url',
        'preview_size' => 'medium',
        'library' => 'all',
    );
}

/**
 * Whether a repeater already carries a "poster" subfield.
 *
 * Saving the field group in wp-admin persists whatever the editor screen shows,
 * including these subfields, so the check keeps them from being added twice.
 *
 * @param array $sub_fields Subfields of the repeater.
 * @return bool
 */
function dilijanvillas_events_has_poster_field($sub_fields)
{
    foreach ((array) $sub_fields as $sub_field) {
        if (is_array($sub_field) && ($sub_field['name'] ?? '') === 'poster') {
            return true;
        }
    }

    return false;
}

/**
 * Resolve a repeater in the local store by key, then by name.
 *
 * The group holds a tab and a repeater under the same name, hence the type
 * check on both lookups.
 *
 * @param string $field_key  Expected field key.
 * @param string $field_name Field name.
 * @return array Local field array, or an empty array.
 */
function dilijanvillas_get_local_repeater($field_key, $field_name)
{
    if (!function_exists('acf_get_local_field')) {
        return array();
    }

    foreach (array($field_key, $field_name) as $selector) {
        $field = acf_get_local_field($selector);
        if (is_array($field) && ($field['type'] ?? '') === 'repeater') {
            return $field;
        }
    }

    return array();
}

/**
 * Append the poster subfields to the local repeaters.
 *
 * Runs on acf/include_fields after the local JSON groups are registered, and
 * reads only the local store: acf_get_field() would cache a repeater without
 * the new subfield for the rest of the request.
 */
function dilijanvillas_register_events_poster_fields()
{
    if (!function_exists('acf_add_local_field') || !function_exists('acf_get_local_fields')) {
        return;
    }

    foreach (dilijanvillas_events_poster_repeaters() as $field_name => $repeater_args) {
        $repeater = dilijanvillas_get_local_repeater($repeater_args['key'], $field_name);
        if (empty($repeater)) {
            // Group lives in the database — handled by the load_field filter below.
            continue;
        }

        if (dilijanvillas_events_has_poster_field(acf_get_local_fields($repeater['key']))) {
            continue;
        }

        acf_add_local_field(
            dilijanvillas_events_poster_field_settings($repeater_args['poster_key'], $repeater['key'])
        );
    }
}
add_action('acf/include_fields', 'dilijanvillas_register_events_poster_fields', 20);

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
function dilijanvillas_append_events_poster_field($field)
{
    if (!is_array($field) || ($field['type'] ?? '') !== 'repeater') {
        return $field;
    }

    $repeaters = dilijanvillas_events_poster_repeaters();
    $field_name = (string) ($field['name'] ?? '');
    if (!isset($repeaters[$field_name])) {
        return $field;
    }

    if (function_exists('acf_is_local_field') && acf_is_local_field((string) ($field['key'] ?? ''))) {
        return $field;
    }

    $sub_fields = isset($field['sub_fields']) && is_array($field['sub_fields']) ? $field['sub_fields'] : array();
    if (dilijanvillas_events_has_poster_field($sub_fields)) {
        return $field;
    }

    $poster = dilijanvillas_events_poster_field_settings($repeaters[$field_name]['poster_key'], $field['key']);
    $sub_fields[] = function_exists('acf_validate_field') ? acf_validate_field($poster) : $poster;
    $field['sub_fields'] = $sub_fields;

    return $field;
}
foreach (array_keys(dilijanvillas_events_poster_repeaters()) as $dilijanvillas_poster_repeater_name) {
    add_filter('acf/load_field/name=' . $dilijanvillas_poster_repeater_name, 'dilijanvillas_append_events_poster_field');
}
unset($dilijanvillas_poster_repeater_name);

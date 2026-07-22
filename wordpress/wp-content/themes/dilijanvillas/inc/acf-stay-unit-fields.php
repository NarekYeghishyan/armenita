<?php
/**
 * Stay unit (Cottage / Private Villa) ACF adjustments.
 *
 * @package dilijanvillas
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('DILIJANVILLAS_STAY_UNIT_DESC_FIELD_VERSION')) {
    define('DILIJANVILLAS_STAY_UNIT_DESC_FIELD_VERSION', '1');
}

/**
 * Ensure Presentation "Description" uses the WordPress editor in admin.
 *
 * @param array $field ACF field settings.
 * @return array
 */
function dilijanvillas_stay_unit_description_as_wysiwyg($field)
{
    if (($field['name'] ?? '') !== 'description_cottage') {
        return $field;
    }

    $field['type'] = 'wysiwyg';
    $field['tabs'] = 'all';
    $field['toolbar'] = 'full';
    $field['media_upload'] = 1;
    $field['delay'] = 0;

    return $field;
}
add_filter('acf/load_field/name=description_cottage', 'dilijanvillas_stay_unit_description_as_wysiwyg');

/**
 * Persist the WYSIWYG field type in ACF for Cottage and Private Villa groups.
 */
function dilijanvillas_upgrade_stay_unit_description_field()
{
    if (!function_exists('acf_get_field') || !function_exists('acf_update_field')) {
        return;
    }

    if ((string) get_option('dilijanvillas_stay_unit_desc_wysiwyg', '') === DILIJANVILLAS_STAY_UNIT_DESC_FIELD_VERSION) {
        return;
    }

    $field_keys = array(
        'field_6a0386f151055',
        'field_6a0454cb8507d',
    );

    foreach ($field_keys as $field_key) {
        $field = acf_get_field($field_key);
        if (empty($field) || !is_array($field)) {
            continue;
        }

        if (($field['type'] ?? '') === 'wysiwyg') {
            continue;
        }

        $field['type'] = 'wysiwyg';
        $field['tabs'] = 'all';
        $field['toolbar'] = 'full';
        $field['media_upload'] = 1;
        $field['delay'] = 0;

        acf_update_field($field);
    }

    update_option('dilijanvillas_stay_unit_desc_wysiwyg', DILIJANVILLAS_STAY_UNIT_DESC_FIELD_VERSION, false);
}
add_action('acf/init', 'dilijanvillas_upgrade_stay_unit_description_field', 25);

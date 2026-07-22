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

/**
 * Page templates that can be booked, and therefore need their own pricing.
 *
 * @return array<int,string>
 */
function dilijanvillas_stay_unit_templates()
{
    return array('cottage.php', 'private-willa.php');
}

/**
 * Per-unit nightly pricing fields.
 *
 * These used to live in Settings -> Booking Settings as one global rate for
 * the whole site, which cannot express "every cottage costs something
 * different". They are registered per page instead.
 */
function dilijanvillas_register_stay_unit_pricing_fields()
{
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    $location = array();
    foreach (dilijanvillas_stay_unit_templates() as $template) {
        $location[] = array(
            array(
                'param' => 'page_template',
                'operator' => '==',
                'value' => $template,
            ),
        );
    }

    acf_add_local_field_group(
        array(
            'key' => 'group_dilijanvillas_stay_pricing',
            'title' => 'Booking pricing',
            'fields' => array(
                array(
                    'key' => 'field_dv_stay_base_nightly_rate',
                    'label' => 'Base nightly rate',
                    'name' => 'stay_base_nightly_rate',
                    'type' => 'number',
                    'instructions' => 'Nightly price for this unit, in the site currency. Used for nights that no price period covers. Note that a booking is only accepted when every night of the stay is covered by a price period.',
                    'required' => 0,
                    'default_value' => '',
                    'min' => 0,
                    'step' => 0.01,
                    'placeholder' => '0',
                ),
                array(
                    'key' => 'field_dv_stay_weekend_multiplier',
                    'label' => 'Weekend multiplier',
                    'name' => 'stay_weekend_multiplier',
                    'type' => 'number',
                    'instructions' => 'Applied to Friday, Saturday and Sunday nights for this unit. 1 = no surcharge, 1.25 = plus 25%. Leave empty for 1. A price period can opt out of it individually.',
                    'required' => 0,
                    'default_value' => 1,
                    'min' => 1,
                    'step' => 0.01,
                    'placeholder' => '1',
                ),
            ),
            'location' => $location,
            'menu_order' => 20,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'hide_on_screen' => '',
            'active' => true,
            'description' => 'Per-unit nightly rate and weekend surcharge (works with Polylang translations).',
            'show_in_rest' => 0,
        )
    );
}
add_action('acf/init', 'dilijanvillas_register_stay_unit_pricing_fields');

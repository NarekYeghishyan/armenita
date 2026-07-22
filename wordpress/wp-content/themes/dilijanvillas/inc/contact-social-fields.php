<?php
/**
 * Contact social field helpers (ACF).
 *
 * @package dilijanvillas
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Resolve translated homepage ID for contact ACF fields.
 */
function dilijanvillas_get_contact_home_page_id()
{
    return dilijanvillas_get_home_page_id();
}

/**
 * Read a contact social ACF value, trying common duplicate field names.
 */
function dilijanvillas_get_contact_social_field($page_id, $field_name)
{
    if (!$page_id || !function_exists('get_field')) {
        return '';
    }

    $candidates = array($field_name);

    if (preg_match('/^(instagram|facebook)_(text|link)_2$/', $field_name, $matches)) {
        $candidates[] = $matches[1] . '_2_' . $matches[2];
        $candidates[] = $matches[1] . '_' . $matches[2] . '_copy';
        $candidates[] = $matches[1] . '_' . $matches[2] . '2';
    }

    foreach (array_unique($candidates) as $key) {
        $value = trim((string) get_field($key, $page_id));
        if ($value !== '') {
            return $value;
        }
    }

    return '';
}

/**
 * Whether a social row should render (link is required).
 */
function dilijanvillas_has_contact_social_row($link, $text)
{
    return trim((string) $link) !== '';
}

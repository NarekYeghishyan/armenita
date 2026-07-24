<?php
/**
 * Country dial codes for the booking form phone field.
 *
 * The booking forms live in three templates (footer.php, cottage.php,
 * private-willa.php) and previously each repeated the same short hand-written
 * list of 16 countries. The list is kept here once so the forms cannot drift
 * apart.
 *
 * The <option> value is the dial code digits only: app.js reads it, strips any
 * non-digits and prefixes the typed number with it, so the value must never
 * contain a "+" or spaces. North American numbering plan territories carry
 * their full prefix (Jamaica = 1876) so the prefix is actually dialable.
 *
 * @package dilijanvillas
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Every selectable country/territory as array(ISO 3166-1 alpha-2, name, dial code).
 *
 * Ordered by English name — the order here is what renders, there is no runtime
 * sort, because accented names (Côte d'Ivoire, Réunion, São Tomé) do not sort
 * correctly under a plain byte comparison.
 *
 * @return array<int, array{0:string,1:string,2:string}>
 */
function dilijanvillas_get_phone_countries()
{
    return array(
        array('AF', 'Afghanistan', '93'),
        array('AX', 'Åland Islands', '358'),
        array('AL', 'Albania', '355'),
        array('DZ', 'Algeria', '213'),
        array('AS', 'American Samoa', '1684'),
        array('AD', 'Andorra', '376'),
        array('AO', 'Angola', '244'),
        array('AI', 'Anguilla', '1264'),
        array('AG', 'Antigua & Barbuda', '1268'),
        array('AR', 'Argentina', '54'),
        array('AM', 'Armenia', '374'),
        array('AW', 'Aruba', '297'),
        array('AU', 'Australia', '61'),
        array('AT', 'Austria', '43'),
        array('AZ', 'Azerbaijan', '994'),
        array('BS', 'Bahamas', '1242'),
        array('BH', 'Bahrain', '973'),
        array('BD', 'Bangladesh', '880'),
        array('BB', 'Barbados', '1246'),
        array('BY', 'Belarus', '375'),
        array('BE', 'Belgium', '32'),
        array('BZ', 'Belize', '501'),
        array('BJ', 'Benin', '229'),
        array('BM', 'Bermuda', '1441'),
        array('BT', 'Bhutan', '975'),
        array('BO', 'Bolivia', '591'),
        array('BA', 'Bosnia & Herzegovina', '387'),
        array('BW', 'Botswana', '267'),
        array('BR', 'Brazil', '55'),
        array('VG', 'British Virgin Islands', '1284'),
        array('BN', 'Brunei', '673'),
        array('BG', 'Bulgaria', '359'),
        array('BF', 'Burkina Faso', '226'),
        array('BI', 'Burundi', '257'),
        array('KH', 'Cambodia', '855'),
        array('CM', 'Cameroon', '237'),
        array('CA', 'Canada', '1'),
        array('CV', 'Cape Verde', '238'),
        array('BQ', 'Caribbean Netherlands', '599'),
        array('KY', 'Cayman Islands', '1345'),
        array('CF', 'Central African Republic', '236'),
        array('TD', 'Chad', '235'),
        array('CL', 'Chile', '56'),
        array('CN', 'China', '86'),
        array('CO', 'Colombia', '57'),
        array('KM', 'Comoros', '269'),
        array('CG', 'Congo - Brazzaville', '242'),
        array('CD', 'Congo - Kinshasa', '243'),
        array('CK', 'Cook Islands', '682'),
        array('CR', 'Costa Rica', '506'),
        array('CI', 'Côte d’Ivoire', '225'),
        array('HR', 'Croatia', '385'),
        array('CU', 'Cuba', '53'),
        array('CW', 'Curaçao', '599'),
        array('CY', 'Cyprus', '357'),
        array('CZ', 'Czechia', '420'),
        array('DK', 'Denmark', '45'),
        array('DJ', 'Djibouti', '253'),
        array('DM', 'Dominica', '1767'),
        array('DO', 'Dominican Republic', '1809'),
        array('EC', 'Ecuador', '593'),
        array('EG', 'Egypt', '20'),
        array('SV', 'El Salvador', '503'),
        array('GQ', 'Equatorial Guinea', '240'),
        array('ER', 'Eritrea', '291'),
        array('EE', 'Estonia', '372'),
        array('SZ', 'Eswatini', '268'),
        array('ET', 'Ethiopia', '251'),
        array('FK', 'Falkland Islands', '500'),
        array('FO', 'Faroe Islands', '298'),
        array('FJ', 'Fiji', '679'),
        array('FI', 'Finland', '358'),
        array('FR', 'France', '33'),
        array('GF', 'French Guiana', '594'),
        array('PF', 'French Polynesia', '689'),
        array('GA', 'Gabon', '241'),
        array('GM', 'Gambia', '220'),
        array('GE', 'Georgia', '995'),
        array('DE', 'Germany', '49'),
        array('GH', 'Ghana', '233'),
        array('GI', 'Gibraltar', '350'),
        array('GR', 'Greece', '30'),
        array('GL', 'Greenland', '299'),
        array('GD', 'Grenada', '1473'),
        array('GP', 'Guadeloupe', '590'),
        array('GU', 'Guam', '1671'),
        array('GT', 'Guatemala', '502'),
        array('GG', 'Guernsey', '44'),
        array('GN', 'Guinea', '224'),
        array('GW', 'Guinea-Bissau', '245'),
        array('GY', 'Guyana', '592'),
        array('HT', 'Haiti', '509'),
        array('HN', 'Honduras', '504'),
        array('HK', 'Hong Kong', '852'),
        array('HU', 'Hungary', '36'),
        array('IS', 'Iceland', '354'),
        array('IN', 'India', '91'),
        array('ID', 'Indonesia', '62'),
        array('IR', 'Iran', '98'),
        array('IQ', 'Iraq', '964'),
        array('IE', 'Ireland', '353'),
        array('IM', 'Isle of Man', '44'),
        array('IL', 'Israel', '972'),
        array('IT', 'Italy', '39'),
        array('JM', 'Jamaica', '1876'),
        array('JP', 'Japan', '81'),
        array('JE', 'Jersey', '44'),
        array('JO', 'Jordan', '962'),
        array('KZ', 'Kazakhstan', '7'),
        array('KE', 'Kenya', '254'),
        array('KI', 'Kiribati', '686'),
        array('XK', 'Kosovo', '383'),
        array('KW', 'Kuwait', '965'),
        array('KG', 'Kyrgyzstan', '996'),
        array('LA', 'Laos', '856'),
        array('LV', 'Latvia', '371'),
        array('LB', 'Lebanon', '961'),
        array('LS', 'Lesotho', '266'),
        array('LR', 'Liberia', '231'),
        array('LY', 'Libya', '218'),
        array('LI', 'Liechtenstein', '423'),
        array('LT', 'Lithuania', '370'),
        array('LU', 'Luxembourg', '352'),
        array('MO', 'Macao', '853'),
        array('MG', 'Madagascar', '261'),
        array('MW', 'Malawi', '265'),
        array('MY', 'Malaysia', '60'),
        array('MV', 'Maldives', '960'),
        array('ML', 'Mali', '223'),
        array('MT', 'Malta', '356'),
        array('MH', 'Marshall Islands', '692'),
        array('MQ', 'Martinique', '596'),
        array('MR', 'Mauritania', '222'),
        array('MU', 'Mauritius', '230'),
        array('YT', 'Mayotte', '262'),
        array('MX', 'Mexico', '52'),
        array('FM', 'Micronesia', '691'),
        array('MD', 'Moldova', '373'),
        array('MC', 'Monaco', '377'),
        array('MN', 'Mongolia', '976'),
        array('ME', 'Montenegro', '382'),
        array('MS', 'Montserrat', '1664'),
        array('MA', 'Morocco', '212'),
        array('MZ', 'Mozambique', '258'),
        array('MM', 'Myanmar', '95'),
        array('NA', 'Namibia', '264'),
        array('NR', 'Nauru', '674'),
        array('NP', 'Nepal', '977'),
        array('NL', 'Netherlands', '31'),
        array('NC', 'New Caledonia', '687'),
        array('NZ', 'New Zealand', '64'),
        array('NI', 'Nicaragua', '505'),
        array('NE', 'Niger', '227'),
        array('NG', 'Nigeria', '234'),
        array('NU', 'Niue', '683'),
        array('NF', 'Norfolk Island', '672'),
        array('KP', 'North Korea', '850'),
        array('MK', 'North Macedonia', '389'),
        array('MP', 'Northern Mariana Islands', '1670'),
        array('NO', 'Norway', '47'),
        array('OM', 'Oman', '968'),
        array('PK', 'Pakistan', '92'),
        array('PW', 'Palau', '680'),
        array('PS', 'Palestine', '970'),
        array('PA', 'Panama', '507'),
        array('PG', 'Papua New Guinea', '675'),
        array('PY', 'Paraguay', '595'),
        array('PE', 'Peru', '51'),
        array('PH', 'Philippines', '63'),
        array('PL', 'Poland', '48'),
        array('PT', 'Portugal', '351'),
        array('PR', 'Puerto Rico', '1787'),
        array('QA', 'Qatar', '974'),
        array('RE', 'Réunion', '262'),
        array('RO', 'Romania', '40'),
        array('RU', 'Russia', '7'),
        array('RW', 'Rwanda', '250'),
        array('WS', 'Samoa', '685'),
        array('SM', 'San Marino', '378'),
        array('ST', 'São Tomé & Príncipe', '239'),
        array('SA', 'Saudi Arabia', '966'),
        array('SN', 'Senegal', '221'),
        array('RS', 'Serbia', '381'),
        array('SC', 'Seychelles', '248'),
        array('SL', 'Sierra Leone', '232'),
        array('SG', 'Singapore', '65'),
        array('SX', 'Sint Maarten', '1721'),
        array('SK', 'Slovakia', '421'),
        array('SI', 'Slovenia', '386'),
        array('SB', 'Solomon Islands', '677'),
        array('SO', 'Somalia', '252'),
        array('ZA', 'South Africa', '27'),
        array('KR', 'South Korea', '82'),
        array('SS', 'South Sudan', '211'),
        array('ES', 'Spain', '34'),
        array('LK', 'Sri Lanka', '94'),
        array('BL', 'St. Barthélemy', '590'),
        array('SH', 'St. Helena', '290'),
        array('KN', 'St. Kitts & Nevis', '1869'),
        array('LC', 'St. Lucia', '1758'),
        array('MF', 'St. Martin', '590'),
        array('PM', 'St. Pierre & Miquelon', '508'),
        array('VC', 'St. Vincent & Grenadines', '1784'),
        array('SD', 'Sudan', '249'),
        array('SR', 'Suriname', '597'),
        array('SE', 'Sweden', '46'),
        array('CH', 'Switzerland', '41'),
        array('SY', 'Syria', '963'),
        array('TW', 'Taiwan', '886'),
        array('TJ', 'Tajikistan', '992'),
        array('TZ', 'Tanzania', '255'),
        array('TH', 'Thailand', '66'),
        array('TL', 'Timor-Leste', '670'),
        array('TG', 'Togo', '228'),
        array('TK', 'Tokelau', '690'),
        array('TO', 'Tonga', '676'),
        array('TT', 'Trinidad & Tobago', '1868'),
        array('TN', 'Tunisia', '216'),
        array('TR', 'Turkey', '90'),
        array('TM', 'Turkmenistan', '993'),
        array('TC', 'Turks & Caicos Islands', '1649'),
        array('TV', 'Tuvalu', '688'),
        array('VI', 'U.S. Virgin Islands', '1340'),
        array('UG', 'Uganda', '256'),
        array('UA', 'Ukraine', '380'),
        array('AE', 'United Arab Emirates', '971'),
        array('GB', 'United Kingdom', '44'),
        array('US', 'United States', '1'),
        array('UY', 'Uruguay', '598'),
        array('UZ', 'Uzbekistan', '998'),
        array('VU', 'Vanuatu', '678'),
        array('VA', 'Vatican City', '39'),
        array('VE', 'Venezuela', '58'),
        array('VN', 'Vietnam', '84'),
        array('WF', 'Wallis & Futuna', '681'),
        array('EH', 'Western Sahara', '212'),
        array('YE', 'Yemen', '967'),
        array('ZM', 'Zambia', '260'),
        array('ZW', 'Zimbabwe', '263'),
    );
}

/**
 * ISO codes shown in the shortcut group at the top of the select.
 *
 * These are the countries the previous hand-written list covered, i.e. where
 * guests actually book from. Keeping them one click away means adding the full
 * list does not make the common case slower.
 *
 * @return array<int, string>
 */
function dilijanvillas_get_frequent_phone_countries()
{
    return array('AM', 'RU', 'GE', 'UA', 'US', 'GB', 'DE', 'FR', 'IT', 'ES', 'TR', 'AE', 'IL', 'IR', 'CN', 'IN');
}

/**
 * Maximum national number length (digits after the dial code), by ISO code.
 *
 * Only countries whose length is known for certain are listed. Everything else
 * falls back to the E.164 ceiling in dilijanvillas_get_phone_nsn_limit(), which
 * is always a valid upper bound, just a loose one.
 *
 * Where a country allows a range, the LARGEST valid length is used. Too high
 * only lets a guest type a stray digit; too low would block a real number and
 * cost a booking.
 *
 * @return array<string, int>
 */
function dilijanvillas_get_phone_nsn_max()
{
    return array(
        // Where guests actually book from.
        'AM' => 8,  'RU' => 10, 'GE' => 9,  'UA' => 9,  'US' => 10, 'CA' => 10,
        'GB' => 10, 'DE' => 11, 'FR' => 9,  'IT' => 11, 'ES' => 9,  'TR' => 10,
        'AE' => 9,  'IL' => 9,  'IR' => 10, 'CN' => 11, 'IN' => 10,
        // Europe.
        'AT' => 13, 'BE' => 9,  'BG' => 9,  'BY' => 9,  'CH' => 9,  'CY' => 8,
        'CZ' => 9,  'DK' => 8,  'EE' => 8,  'FI' => 12, 'GR' => 10, 'HR' => 9,
        'HU' => 9,  'IE' => 9,  'IS' => 9,  'LT' => 8,  'LU' => 9,  'LV' => 8,
        'MD' => 8,  'ME' => 8,  'MK' => 8,  'MT' => 8,  'NL' => 9,  'NO' => 8,
        'PL' => 9,  'PT' => 9,  'RO' => 9,  'RS' => 9,  'SE' => 13, 'SI' => 8,
        'SK' => 9,  'AL' => 9,  'BA' => 8,
        // Asia and Oceania.
        'AU' => 9,  'AZ' => 9,  'BD' => 10, 'HK' => 8,  'ID' => 12, 'JP' => 10,
        'KG' => 9,  'KR' => 10, 'KZ' => 10, 'LK' => 9,  'MN' => 8,  'MY' => 10,
        'NP' => 10, 'NZ' => 10, 'PH' => 10, 'PK' => 10, 'SG' => 8,  'TH' => 9,
        'TJ' => 9,  'TW' => 9,  'UZ' => 9,  'VN' => 10,
        // Middle East and Africa.
        'BH' => 8,  'DZ' => 9,  'EG' => 10, 'GH' => 9,  'IQ' => 10, 'JO' => 9,
        'KE' => 9,  'KW' => 8,  'LB' => 8,  'MA' => 9,  'NG' => 10, 'OM' => 8,
        'QA' => 8,  'SA' => 9,  'TN' => 8,  'ZA' => 9,
        // Americas.
        'AR' => 10, 'BR' => 11, 'CL' => 9,  'CO' => 10, 'MX' => 10, 'PE' => 9,
    );
}

/**
 * Digits a guest may type after the dial code for one country.
 *
 * E.164 caps a full international number at 15 digits, so whatever the dial code
 * consumes is unavailable to the national part. That bound is correct everywhere,
 * which makes it a safe default for countries missing from the table above.
 *
 * @param string $iso  ISO alpha-2 code.
 * @param string $dial Dial code digits.
 * @return int
 */
function dilijanvillas_get_phone_nsn_limit($iso, $dial)
{
    $known = dilijanvillas_get_phone_nsn_max();
    $iso = strtoupper((string) $iso);

    if (isset($known[$iso])) {
        return (int) $known[$iso];
    }

    return max(4, 15 - strlen((string) $dial));
}

/**
 * How the national number is grouped as the guest types, by ISO code.
 *
 * 'groups' is the run of digit-group sizes and 'sep' the character between them.
 * Countries absent from this table fall back to groups of three separated by a
 * space, which is what the field did for everyone before.
 *
 * @return array<string, array{groups: array<int, int>, sep: string}>
 */
function dilijanvillas_get_phone_formats()
{
    return array(
        // Armenian numbers are read and written in pairs: 94-60-56-65.
        'AM' => array('groups' => array(2, 2, 2, 2), 'sep' => '-'),
    );
}

/**
 * Render one <option> for the phone country select.
 *
 * Shared by both optgroups so the attribute set cannot drift between them.
 *
 * @param array{0:string,1:string,2:string} $country  ISO code, name, dial code.
 * @param bool                              $selected Mark as the selected option.
 * @return void
 */
function dilijanvillas_render_phone_country_option($country, $selected = false)
{
    list($code, $name, $dial) = $country;

    $formats = dilijanvillas_get_phone_formats();
    $format_attr = '';
    if (isset($formats[$code])) {
        $format_attr = sprintf(
            ' data-nsn-groups="%s" data-nsn-sep="%s"',
            esc_attr(implode(',', (array) $formats[$code]['groups'])),
            esc_attr((string) $formats[$code]['sep'])
        );
    }

    printf(
        '<option value="%s" data-iso="%s" data-nsn-max="%s"%s%s>+%s %s</option>',
        esc_attr($dial),
        esc_attr($code),
        esc_attr((string) dilijanvillas_get_phone_nsn_limit($code, $dial)),
        $format_attr,
        ($selected ? ' selected' : ''),
        esc_html($dial),
        esc_html($name)
    );
}

/**
 * Render the <option> list for a booking form phone country select.
 *
 * The dial code leads each label ("+374 Armenia") because the select is narrow:
 * whatever is truncated, the code the guest is choosing stays visible.
 *
 * @param string $selected_iso ISO alpha-2 code preselected on page load.
 * @return void
 */
function dilijanvillas_render_phone_country_options($selected_iso = 'AM')
{
    $countries = dilijanvillas_get_phone_countries();
    $frequent = dilijanvillas_get_frequent_phone_countries();
    $selected_iso = strtoupper((string) $selected_iso);

    $by_iso = array();
    foreach ($countries as $country) {
        $by_iso[$country[0]] = $country;
    }

    $lang = function_exists('pll_current_language') ? (string) pll_current_language('slug') : '';
    if ($lang === 'hy') {
        $frequent_label = 'Հաճախ ընտրվող';
        $all_label = 'Բոլոր երկրները';
    } elseif ($lang === 'ru') {
        $frequent_label = 'Часто выбирают';
        $all_label = 'Все страны';
    } else {
        $frequent_label = 'Frequently used';
        $all_label = 'All countries';
    }

    // Only the shortcut group carries `selected`. If both groups marked it, the
    // browser would honour the last one and the shortcut would look unselected.
    echo '<optgroup label="' . esc_attr($frequent_label) . '">';
    foreach ($frequent as $iso) {
        if (!isset($by_iso[$iso])) {
            continue;
        }
        dilijanvillas_render_phone_country_option($by_iso[$iso], $by_iso[$iso][0] === $selected_iso);
    }
    echo '</optgroup>';

    echo '<optgroup label="' . esc_attr($all_label) . '">';
    foreach ($countries as $country) {
        dilijanvillas_render_phone_country_option($country);
    }
    echo '</optgroup>';
}

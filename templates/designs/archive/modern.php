<?php
/**
 * Moderne arkiv-rammeverk for kurs
 * 
 * Dette rammeverket gir en mer visuell presentasjon med full-bredde header
 * og moderne designelementer.
 */

if (!defined('ABSPATH')) exit;

// Last inn nødvendige avhengigheter
if (!function_exists('get_course_languages')) {
    require_once KURSAGENTEN_PATH . 'templates/includes/queries.php';
}

// Initialiser hovedspørring og filterinnstillinger
$query = get_course_dates_query();

$top_filters = get_option('kursagenten_top_filters', []);
$left_filters = get_option('kursagenten_left_filters', []);
$filter_types = get_option('kursagenten_filter_types', []);
$available_filters = get_option('kursagenten_available_filters', []);

// Konverter filterinnstillinger til arrays hvis de er lagret som kommaseparerte strenger
if (!is_array($top_filters)) {
    $top_filters = explode(',', $top_filters);
}
if (!is_array($left_filters)) {
    $left_filters = explode(',', $left_filters);
}

// Sjekk om venstre kolonne er tom og sett passende klasse
$has_left_filters = !empty($left_filters) && is_array($left_filters) && count(array_filter($left_filters)) > 0;
$left_column_class = $has_left_filters ? 'col-1-4' : 'col-1 hidden-left-column';

// Sjekk om søk er det eneste filteret på toppen og sett passende klasse
$is_search_only = is_array($top_filters) && count($top_filters) === 1 && in_array('search', $top_filters);
$search_class = $is_search_only ? 'wide-search' : '';

// Definer taksonomi og meta-feltdatastruktur for filtre
$taxonomy_data = [
    'categories' => [
        'taxonomy' => 'coursecategory',
        'terms' => get_terms(['taxonomy' => 'coursecategory', 'hide_empty' => true]),
        'url_key' => 'k',
        'filter_key' => 'categories',
    ],
    'locations' => [
        'taxonomy' => 'course_location',
        'terms' => get_terms(['taxonomy' => 'course_location', 'hide_empty' => true]),
        'url_key' => 'sted',
        'filter_key' => 'locations',
    ],
    'instructors' => [
        'taxonomy' => 'instructors',
        'terms' => get_terms(['taxonomy' => 'instructors', 'hide_empty' => true]),
        'url_key' => 'i',
        'filter_key' => 'instructors',
    ],
    'language' => [
        'taxonomy' => '',
        'terms' => get_course_languages(),
        'url_key' => 'sprak',
        'filter_key' => 'language',
    ],
    'months' => [
        'taxonomy' => '',
        'terms' => get_course_months(),
        'url_key' => 'mnd',
        'filter_key' => 'months',
    ]
];

// **Meta-felt
// Språk
// Hent språk fra meta-felt for kursdatoer
$args = [
    'post_type'      => 'coursedate',
    'posts_per_page' => -1,
    'fields'         => 'ids',
];

$coursedates = get_posts($args);
$language_terms = [];

// Hent språk fra meta-felt for kursdatoer
foreach ($coursedates as $post_id) {
    $meta_language = get_post_meta($post_id, 'course_language', true);
    if (!empty($meta_language)) {
        $language_terms[] = $meta_language;
    }
}
$language_terms = array_unique($language_terms);
$taxonomy_data['language']['terms'] = $language_terms;

// **Bring terms and meta data together
// Prepare filter-information
$filter_display_info = [];
foreach ($available_filters as $filter_key => $filter_info) {
    $filter_display_info[$filter_key] = [
        'label' => $filter_info['label'] ?? '',
        'placeholder' => $filter_info['placeholder'] ?? 'Velg',
        'filter_key' => $taxonomy_data[$filter_key]['filter_key'] ?? '',
        'url_key' => $taxonomy_data[$filter_key]['url_key'] ?? ''
    ];
}

// Last inn layout-wrapper
kursagenten_get_layout_template();
?>

<!-- Filter Settings for JavaScript -->
<script id="filter-settings" type="application/json">
    <?php
    // Export filter configuration for JavaScript usage
    $filter_data = [
        'top_filters' => get_option('kursagenten_top_filters', []),
        'left_filters' => get_option('kursagenten_left_filters', []),
        'filter_types' => get_option('kursagenten_filter_types', []),
        'available_filters' => get_option('kursagenten_available_filters', []),
    ];
    echo json_encode($filter_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);
    ?>
</script> 
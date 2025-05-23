<?php
/**
 * Mobile filters template
 * 
 * This template is loaded via AJAX when viewing on mobile devices
 */

if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(dirname(dirname(dirname(__FILE__)))) . '/');
}


// Hent språk direkte fra meta-felter hvis funksjonen ikke er tilgjengelig
function get_languages_from_meta() {
    global $wpdb;
    $languages = $wpdb->get_col("
        SELECT DISTINCT meta_value 
        FROM {$wpdb->postmeta} 
        WHERE meta_key = 'course_language' 
        AND meta_value != ''
    ");
    return array_map(function($lang) {
        return (object) [
            'name' => ucfirst($lang),
            'value' => strtolower($lang)
        ];
    }, $languages);
}

// Debug logging
error_log('Loading mobile filters template');
error_log('KURSAG_PLUGIN_DIR: ' . (defined('KURSAG_PLUGIN_DIR') ? KURSAG_PLUGIN_DIR : 'Not defined'));

// Hent filter-innstillinger
$top_filters = get_option('kursagenten_top_filters', []);
$left_filters = get_option('kursagenten_left_filters', []);
$filter_types = get_option('kursagenten_filter_types', []);
$available_filters = get_option('kursagenten_available_filters', []);

error_log('Top filters: ' . print_r($top_filters, true));
error_log('Left filters: ' . print_r($left_filters, true));

// Konverter filter-innstillinger til arrays hvis de er lagret som komma-separerte strenger
if (!is_array($top_filters)) {
    $top_filters = explode(',', $top_filters);
}
if (!is_array($left_filters)) {
    $left_filters = explode(',', $left_filters);
}

// Kombiner alle filtre for mobil visning
$all_filters = array_unique(array_merge($top_filters, $left_filters));

// Fjern tomme verdier
$all_filters = array_filter($all_filters);

// Sorter filtre slik at søk kommer først
if (in_array('search', $all_filters)) {
    // Fjern søk fra arrayet
    $all_filters = array_diff($all_filters, ['search']);
    // Legg til søk først i arrayet
    array_unshift($all_filters, 'search');
}

// Debug logging for å se hvilke filtre som er aktive
error_log('Combined filters for mobile: ' . print_r($all_filters, true));
error_log('Available filters: ' . print_r($available_filters, true));

// Definer taxonomy og meta felt data struktur for filtre
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
        'terms' => function_exists('get_course_languages') ? get_course_languages() : get_languages_from_meta(),
        'url_key' => 'sprak',
        'filter_key' => 'language',
    ],
    'months' => [
        'taxonomy' => '',
        'terms' => function_exists('get_course_months') ? get_course_months() : [],
        'url_key' => 'mnd',
        'filter_key' => 'months',
    ]
];

// Debug logging for terms
foreach ($taxonomy_data as $key => $data) {
    error_log("Terms for {$key}: " . print_r($data['terms'], true));
}

// Hent aktive filtre fra URL
$active_filters = [];
$filter_params = ['k', 'sted', 'i', 'sprak', 'mnd', 'dato', 'search'];
foreach ($filter_params as $param) {
    if (isset($_GET[$param])) {
        $active_filters[$param] = explode(',', $_GET[$param]);
    }
}

// Start output buffering
ob_start();
?>

<div class="mobile-filter-content">
    <?php 
    if (empty($all_filters)) {
        echo '<div class="mobile-filter-error"><p>Ingen filtre er konfigurert.</p></div>';
    } else {
        foreach ($all_filters as $filter) : 
            // Sjekk om filteret er gyldig
            if (!isset($available_filters[$filter])) {
                error_log('Skipping invalid filter: ' . $filter);
                continue;
            }
            ?>
            <div class="mobile-filter-section">
                <?php
                $current_filter_info = $available_filters[$filter];
                $filter_label = $current_filter_info['label'] ?? '';
                
                if (!empty($filter_label)) : ?>
                    <h5><?php echo esc_html($filter_label); ?></h5>
                <?php endif; ?>

                <?php if ($filter === 'search') : ?>
                    <input type="text" 
                           class="filter-search" 
                           placeholder="<?php echo esc_attr($current_filter_info['placeholder'] ?? 'Søk etter kurs...'); ?>"
                           value="<?php echo isset($_GET['search']) ? esc_attr($_GET['search']) : ''; ?>">

                <?php elseif ($filter === 'date') : ?>
                    <?php
                    $date = "";
                    if (isset($_GET['dato'])) {
                        $dates = explode('-', $_GET['dato']);
                        if (count($dates) === 2) {
                            $from_date = ka_format_date(\DateTime::createFromFormat('d.m.Y', trim($dates[0]))->format('Y-m-d'));
                            $to_date = ka_format_date(\DateTime::createFromFormat('d.m.Y', trim($dates[1]))->format('Y-m-d'));
                            $date = sprintf('%s - %s', $from_date, $to_date);
                        }
                    }
                    ?>
                    <div class="date-range-wrapper">
                        <input type="text" 
                                id="date-range-mobile" 
                               class="caleran caleran-mobile ka-caleran"
                               data-filter-key="date"
                               data-url-key="dato"
                               name="calendar-input"
                               placeholder="<?php echo esc_attr($current_filter_info['placeholder'] ?? 'Velg fra-til dato'); ?>"
                               value="<?php echo esc_attr($date); ?>"
                               aria-label="Velg datoer">
                        <i class="ka-icon icon-chevron-down"></i>
                        
                    </div>
                    <a href="#" class="reset-date-filter" style="display: <?php echo $date ? 'block' : 'none'; ?>; font-size: var(--ka-font-xs); color: rgb(235, 121, 121); margin-top: 5px; text-decoration: none;">Nullstill dato</a>

                <?php elseif (isset($taxonomy_data[$filter])) : ?>
                    <div class="filter-list">
                        <?php 
                        if ($filter === 'categories') {
                            // Bruk display_category_hierarchy for kategorier
                            $selected_categories = isset($active_filters[$taxonomy_data[$filter]['url_key']]) ? 
                                array_map(function($slug) {
                                    $term = get_term_by('slug', $slug, 'coursecategory');
                                    return $term ? $term->term_id : 0;
                                }, $active_filters[$taxonomy_data[$filter]['url_key']]) : [];
                            
                            // Hent kategorier med parent-informasjon
                            $categories = get_terms([
                                'taxonomy' => 'coursecategory',
                                'hide_empty' => true,
                                'orderby' => 'menu_order',
                                'order' => 'ASC',
                                'parent' => 0, // Hent kun toppnivå-kategorier
                                'meta_query' => [
                                    'relation' => 'OR',
                                    [
                                        'key' => 'hide_in_course_list',
                                        'value' => 'Vis',
                                    ],
                                    [
                                        'key' => 'hide_in_course_list',
                                        'compare' => 'NOT EXISTS'
                                    ]
                                ]
                            ]);

                            if (!is_wp_error($categories)) {
                                foreach ($categories as $term) {
                                    // Vis toppnivå-kategorien
                                    $is_checked = in_array($term->term_id, $selected_categories) ? 'checked' : '';
                                    echo '<div class="filter-category">';
                                    echo '<label class="filter-list-item checkbox">';
                                    echo '<input type="checkbox" 
                                        class="filter-checkbox"
                                        value="' . esc_attr($term->slug) . '" 
                                        data-filter-key="categories" 
                                        data-url-key="k" 
                                        ' . $is_checked . '>';
                                    echo '<span class="checkbox-label">' . esc_html($term->name) . '</span>';
                                    echo '</label>';
                                    echo '</div>';

                                    // Hent og vis underkategorier for denne termen
                                    $child_terms = get_terms([
                                        'taxonomy' => 'coursecategory',
                                        'hide_empty' => true,
                                        'parent' => $term->term_id,
                                        'orderby' => 'menu_order',
                                        'order' => 'ASC',
                                        'meta_query' => [
                                            'relation' => 'OR',
                                            [
                                                'key' => 'hide_in_course_list',
                                                'value' => 'Vis',
                                            ],
                                            [
                                                'key' => 'hide_in_course_list',
                                                'compare' => 'NOT EXISTS'
                                            ]
                                        ]
                                    ]);
                                    
                                    if (!is_wp_error($child_terms)) {
                                        foreach ($child_terms as $child) {
                                            $is_checked = in_array($child->term_id, $selected_categories) ? 'checked' : '';
                                            echo '<div class="filter-category has-parent" data-parent-id="' . esc_attr($term->term_id) . '">';
                                            echo '<label class="filter-list-item checkbox">';
                                            echo '<input type="checkbox" 
                                                class="filter-checkbox"
                                                value="' . esc_attr($child->slug) . '" 
                                                data-filter-key="categories" 
                                                data-url-key="k" 
                                                ' . $is_checked . '>';
                                            echo '<span class="checkbox-label">' . esc_html($child->name) . '</span>';
                                            echo '</label>';
                                            echo '</div>';
                                        }
                                    }
                                }
                            }
                        } else {
                            // Behold eksisterende kode for andre filtre
                            $terms = $taxonomy_data[$filter]['terms'];
                            if (!empty($terms)) :
                                foreach ($terms as $term) : 
                                    $term_value = is_object($term) ? ($filter === 'months' ? $term->value : $term->slug) : strtolower($term);
                                    $is_checked = isset($active_filters[$taxonomy_data[$filter]['url_key']]) && 
                                                in_array($term_value, $active_filters[$taxonomy_data[$filter]['url_key']]);
                                    ?>
                                    <label class="filter-list-item checkbox">
                                        <input type="checkbox" 
                                               class="filter-checkbox"
                                               value="<?php echo esc_attr($term_value); ?>"
                                               data-filter-key="<?php echo esc_attr($taxonomy_data[$filter]['filter_key']); ?>"
                                               data-url-key="<?php echo esc_attr($taxonomy_data[$filter]['url_key']); ?>"
                                               <?php checked($is_checked); ?>>
                                        <span class="checkbox-label">
                                            <?php echo esc_html(is_object($term) ? $term->name : ucfirst($term)); ?>
                                        </span>
                                    </label>
                                <?php endforeach;
                            else :
                                echo '<p>Ingen alternativer tilgjengelig</p>';
                            endif;
                        }
                        ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach;
    } ?>

    <div class="mobile-filter-footer">
        <button class="apply-filters-button">Vis resultater</button>
        <button class="reset-filters-button">Nullstill filter</button>
    </div>
</div>

<?php
$output = ob_get_clean();
error_log('Template output length: ' . strlen($output));
echo $output; // Echo instead of return 
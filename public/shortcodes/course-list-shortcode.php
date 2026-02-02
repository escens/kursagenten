<?php
/**
 * Course List Shortcode
 * 
 * Displays a list of courses with filters and pagination
 * 
 * Shortcode usage examples:
 * [kursliste] - Shows all courses
 * [kursliste kategori="dans"] - Shows only dance courses
 * [kursliste sted="bærum"] - Shows only courses in Bærum
 * [kursliste kategori="dans" sted="oslo"] - Shows dance courses in Oslo
 * [kursliste språk="norsk"] - Shows only Norwegian courses
 * [kursliste måned="01"] - Shows only January courses
 * [kursliste list_type="compact"] - Shows compact list type
 * [kursliste list_type="grid"] - Shows grid list type
 * [kursliste list_type="standard"] - Shows standard list type
 * [kursliste antall="10"] - Shows the first 10 courses
 * [kursliste bilder="yes"] - Shows images
 * [kursliste bilder="no"] - Shows no images
 * [kursliste klasse="min-klasse"] - Shows class attribute
 * 
 * @package Kursagenten
 * @subpackage Shortcodes
 */

if (!defined('ABSPATH')) exit;

/**
 * Register the [kursliste] shortcode
 */
function kursagenten_course_list_shortcode($atts) {
    // Hvis $atts er en string, prøv å parse den manuelt
    if (is_string($atts)) {
        $raw_atts = $atts;
        $atts = array();
        
        // Parse attributter manuelt
        if (preg_match_all('/(\w+)=([^"\s]+|"[^"]*")/', $raw_atts, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $key = $match[1];
                $value = trim($match[2], '"');
                $atts[$key] = $value;
            }
        }
    }
    
    // Hvis $atts[0] inneholder attributter, parse dem manuelt
    if (isset($atts[0]) && is_string($atts[0])) {
        $raw_attrs = $atts[0];
        $parsed_atts = array();
        
        // Parse attributter fra format som "språk=norsk måned=9" eller "språk=\"norsk\" måned=\"9\""
        if (preg_match_all('/([a-zA-ZæøåÆØÅ]+)=["\']?([^"\'\s]+)["\']?/', $raw_attrs, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $key = $match[1];
                $value = $match[2];
                $parsed_atts[$key] = $value;
            }
        }
        
        // Merge med eksisterende $atts
        $atts = array_merge($atts, $parsed_atts);
    }
    
    // Hvis $atts har flere elementer, parse dem også
    foreach ($atts as $index => $value) {
        if ($index > 0 && is_string($value)) {
            // Parse attributter fra format som "måned=9"
            if (preg_match_all('/([a-zA-ZæøåÆØÅ]+)=["\']?([^"\'\s]+)["\']?/', $value, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $key = $match[1];
                    $value = $match[2];
                    $atts[$key] = $value;
                }
            }
        }
    }
    
    // Parse shortcode attributes
    $atts = shortcode_atts(array(
        'kategori' => '',
        'sted' => '',
        'lokasjon' => '',
        'st' => '',
        'instruktør' => '',
        'språk' => '',
        'måned' => '',
        'force_standard_view' => 'false',
        'klasse' => '',
        'list_type' => '', // standard, grid, compact
        'bilder' => '', // yes, no - overstyr bildeinnstillinger
        'antall' => '' // Begrens antall kurs som vises
    ), $atts, 'kursliste');

    // Load required dependencies
    if (!function_exists('get_course_languages')) {
        require_once dirname(dirname(__FILE__)) . '/templates/includes/queries.php';
    }

    // Include AJAX filter functionality
    require_once dirname(dirname(__FILE__)) . '/templates/includes/course-ajax-filter.php';

    // Set $_REQUEST parameters based on shortcode attributes
    // This allows the existing filter system to work with shortcode parameters
    $has_shortcode_filters = false;
    $shortcode_params = [];
    $active_shortcode_filters = []; // Track which filters are active in shortcode
    $limit_courses = isset($atts['antall']) ? absint($atts['antall']) : 0;
    $limit_mode = $limit_courses > 0;
    
    // Map transport-parameter "st" (slug eller "ikke-slug") til sted-navn i $_REQUEST/$_GET
    $incoming_st = '';
    if (!empty($atts['st'])) {
        $incoming_st = (string)$atts['st'];
    } elseif (isset($_GET['st']) && $_GET['st'] !== '') {
        // Tillat mapping fra URL når shortcoden starter (ikke via JS)
        $incoming_st = (string)$_GET['st'];
    }
    if ($incoming_st !== '') {
        $st_value = trim($incoming_st);
        $neg_prefix = 'ikke-';
        $is_neg = (stripos($st_value, $neg_prefix) === 0);
        $slug = $is_neg ? substr($st_value, strlen($neg_prefix)) : $st_value;
        $slug = sanitize_title($slug);
        $term = get_term_by('slug', $slug, 'ka_course_location');
        if ($is_neg) {
            // Negativ spørring: inkluder alle lokasjoner unntatt denne
            $exclude_name = $term && !is_wp_error($term)
                ? $term->name
                : ucwords(str_replace('-', ' ', $slug));
            $all_terms = get_terms([
                'taxonomy' => 'ka_course_location',
                'hide_empty' => false,
                'fields' => 'all',
            ]);
            $allowed_names = [];
            if (!is_wp_error($all_terms) && !empty($all_terms)) {
                foreach ($all_terms as $t) {
                    if ($t->name !== $exclude_name) {
                        $allowed_names[] = $t->name;
                    }
                }
            }
            // Sett REQUEST til kommaseparert liste for OR-union (spørringslaget bruker '=' pr verdi)
            $mapped = implode(',', $allowed_names);
            $_REQUEST['sted'] = $mapped;
            $_GET['sted'] = $mapped;
        } else {
            // Positiv spørring: enkel lokasjonsnavn
            $mapped = $term && !is_wp_error($term)
                ? $term->name
                : ucwords(str_replace('-', ' ', $slug));
            $_REQUEST['sted'] = $mapped;
            $_GET['sted'] = $mapped;
        }
        // Viktig: Ikke legg 'sted' fra st i $shortcode_params slik at chip ikke vises normalt
        // Hvis sc=0, skjul chip eksplisitt ved å markere som shortcode-parameter
        if (isset($_GET['sc']) && (string)$_GET['sc'] === '0') {
            $shortcode_params['sted'] = $_REQUEST['sted'];
            $has_shortcode_filters = true;
            $active_shortcode_filters[] = 'locations';
        }
    }
    
    if (!empty($atts['kategori'])) {
        $_REQUEST['k'] = $atts['kategori'];
        $_GET['k'] = $atts['kategori'];
        $shortcode_params['k'] = $atts['kategori'];
        $active_shortcode_filters[] = 'categories';
        $has_shortcode_filters = true;
    }
    if (!empty($atts['sted'])) {
        $_REQUEST['sted'] = $atts['sted'];
        $_GET['sted'] = $atts['sted'];
        $shortcode_params['sted'] = $atts['sted'];
        $active_shortcode_filters[] = 'locations';
        $has_shortcode_filters = true;
    }
    if (!empty($atts['lokasjon'])) {
        $_REQUEST['sted'] = $atts['lokasjon'];
        $_GET['sted'] = $atts['lokasjon'];
        $shortcode_params['sted'] = $atts['lokasjon'];
        $active_shortcode_filters[] = 'locations';
        $has_shortcode_filters = true;
    }
    if (!empty($atts['instruktør'])) {
        $_REQUEST['i'] = $atts['instruktør'];
        $_GET['i'] = $atts['instruktør'];
        $shortcode_params['i'] = $atts['instruktør'];
        $active_shortcode_filters[] = 'instructors';
        $has_shortcode_filters = true;
    }
    if (!empty($atts['språk'])) {
        // Konverter til lowercase for å matche get_course_languages() format
        $language_value = strtolower($atts['språk']);
        $_REQUEST['sprak'] = $language_value;
        $_GET['sprak'] = $language_value;
        $shortcode_params['sprak'] = $language_value;
        $active_shortcode_filters[] = 'language';
        $has_shortcode_filters = true;
    }
    if (!empty($atts['måned'])) {
        // Konverter til padded format for å matche get_course_months() format
        $month_value = str_pad($atts['måned'], 2, '0', STR_PAD_LEFT);
        $_REQUEST['mnd'] = $month_value;
        $_GET['mnd'] = $month_value;
        $shortcode_params['mnd'] = $month_value;
        $active_shortcode_filters[] = 'months';
        $has_shortcode_filters = true;
    }

    if ($limit_mode) {
        $_REQUEST['per_page'] = $limit_courses;
        $_GET['per_page'] = $limit_courses;
        $shortcode_params['per_page'] = $limit_courses;
    }

    // Hent valgt listetype fra innstillinger eller shortcode parameter
    $list_type = !empty($atts['list_type']) ? $atts['list_type'] : get_option('kursagenten_archive_list_type', 'standard');

    // Last inn riktig CSS-fil basert på listetype (dynamisk)
    wp_enqueue_style(
        'kursagenten-list-' . $list_type,
        KURSAG_PLUGIN_URL . '/assets/css/public/list-' . $list_type . '.css',
        array(),
        KURSAG_VERSION
    );

    // Enqueue required styles
    wp_enqueue_style('kursagenten-course-style', KURSAG_PLUGIN_URL . '/assets/css/public/frontend-course-style.css', array(), KURSAG_VERSION);
    wp_enqueue_style('kursagenten-datepicker-style', KURSAG_PLUGIN_URL . '/assets/css/public/datepicker-caleran.min.css', array(), KURSAG_VERSION);

    // Enqueue required scripts
    //wp_enqueue_script('kursagenten-iframe-resizer', 'https://embed.kursagenten.no/js/iframe-resizer/iframeResizer.min.js', array(), null, true);
    //wp_enqueue_script('kursagenten-slidein-panel', KURSAG_PLUGIN_URL . '/assets/js/public/course-slidein-panel.js', array('jquery', 'kursagenten-iframe-resizer'), KURSAG_VERSION, true);
    if ( ! wp_script_is('kursagenten-iframe-resizer', 'enqueued') ) {
        wp_enqueue_script('kursagenten-iframe-resizer', 'https://embed.kursagenten.no/js/iframe-resizer/iframeResizer.min.js', [], null, true);
    }
    if ( ! wp_script_is('kursagenten-slidein-panel', 'enqueued') ) {
        wp_enqueue_script('kursagenten-slidein-panel', KURSAG_PLUGIN_URL . '/assets/js/public/course-slidein-panel.js', ['jquery', 'kursagenten-iframe-resizer'], KURSAG_VERSION, true);
    }
    // Datepicker dependencies first
    if ( ! wp_script_is('kursagenten-datepicker-moment', 'enqueued') ) {
        wp_enqueue_script('kursagenten-datepicker-moment', KURSAG_PLUGIN_URL . '/assets/js/public/datepicker/moment.min.js', array(), KURSAG_VERSION);
    }
    if ( ! wp_script_is('kursagenten-datepicker-script', 'enqueued') ) {
        wp_enqueue_script('kursagenten-datepicker-script', KURSAG_PLUGIN_URL . '/assets/js/public/datepicker/caleran.min.js', ['kursagenten-datepicker-moment'], KURSAG_VERSION);
    }
    // Main AJAX filter script after deps
    wp_enqueue_script('kursagenten-ajax-filter', 
        KURSAG_PLUGIN_URL . '/assets/js/public/course-ajax-filter.js', 
        array(
            'jquery', 
            'kursagenten-slidein-panel',
            'kursagenten-datepicker-script'
        ), 
        KURSAG_VERSION, 
        true
    );
    wp_enqueue_script('kursagenten-expand-content', KURSAG_PLUGIN_URL . '/assets/js/public/course-expand-content.js', array(), KURSAG_VERSION);

    // Localize script with necessary data
    wp_localize_script(
        'kursagenten-ajax-filter',
        'kurskalender_data',
        array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'filter_nonce' => wp_create_nonce('filter_nonce'),
            'shortcode_params' => $shortcode_params,
            'has_shortcode_filters' => $has_shortcode_filters,
            'active_shortcode_filters' => $active_shortcode_filters
        )
    );

    // Initialize main course query and filter settings
    $query = get_course_dates_query();
    $displayed_course_count = ($limit_mode && $query instanceof WP_Query)
        ? $query->post_count
        : ($query instanceof WP_Query ? $query->found_posts : 0);

    // Håndter side-parameter
    $paged = (get_query_var('side')) ? get_query_var('side') : 1;
    $query->set('paged', $paged);

    $top_filters = get_option('kursagenten_top_filters', []);
    $left_filters = get_option('kursagenten_left_filters', []);
    $filter_types = get_option('kursagenten_filter_types', []);
    $available_filters = get_option('kursagenten_available_filters', []);

    // Convert filter settings to arrays if they're stored as comma-separated strings
    if (!is_array($top_filters)) {
        $top_filters = explode(',', $top_filters);
    }
    if (!is_array($left_filters)) {
        $left_filters = explode(',', $left_filters);
    }

    // Check if left column is empty and set appropriate class
    $has_left_filters = !empty($left_filters) && is_array($left_filters) && count(array_filter($left_filters)) > 0;
    $left_column_class = $has_left_filters ? 'col-1-4' : 'col-1 hidden-left-column';

    // Check if search is the only filter on top and set appropriate class
    $is_search_only = is_array($top_filters) && count($top_filters) === 1 && in_array('search', $top_filters);
    $search_class = $is_search_only ? 'wide-search' : '';

    // Function to check if a filter should be hidden due to shortcode parameters
    if (!function_exists('should_hide_filter')) {
        function should_hide_filter($filter_key, $active_shortcode_filters) {
            // Spesiell håndtering for ka_coursecategory taksonomi-sider
            if ($filter_key === 'categories' && is_tax('ka_coursecategory')) {
                // Sjekk om vi er på en foreldrekategori (som har barn)
                $current_term = get_queried_object();
                if ($current_term && $current_term->parent == 0) {
                    // Vi er på en foreldrekategori - vis barnekategorier
                    return false;
                } else {
                    // Vi er på en underkategori - skjul hele kategori-filteret
                    return true;
                }
            }
            return in_array($filter_key, $active_shortcode_filters);
        }
    }

    // Hent aktive filtre fra URL for å beregne counts
    $active_filters = [];
    $filter_params = ['k', 'sted', 'i', 'sprak', 'mnd', 'dato', 'sok'];
    foreach ($filter_params as $param) {
        if (isset($_GET[$param])) {
            $active_filters[$param] = explode(',', $_GET[$param]);
        }
    }

    // Spesiell håndtering for ka_coursecategory taksonomi-sider
    $category_terms = function_exists('get_filtered_terms_for_context') ? get_filtered_terms_for_context('ka_coursecategory') : get_filtered_terms('ka_coursecategory');

    // Pre-prosessere kategorier for å identifisere foreldre (de som har barn)
    $parent_term_ids = [];
    foreach ($category_terms as $term) {
        if (isset($term->parent_id) && $term->parent_id > 0) {
            $parent_term_ids[] = $term->parent_id;
        }
    }
    $parent_term_ids = array_unique($parent_term_ids);
    
    // Legg til has_children og is_parent metadata på hver kategori
    foreach ($category_terms as $term) {
        $term->has_children = in_array($term->term_id, $parent_term_ids);
        $term->is_parent = ($term->parent_id == 0 || !isset($term->parent_id));
    }

    $taxonomy_data = [
        'categories' => [
            'taxonomy' => 'ka_coursecategory',
            'terms' => $category_terms,
            'url_key' => 'k',
            'filter_key' => 'categories',
            'counts' => get_filter_value_counts('categories', $active_filters),
        ],
        'locations' => [
            'taxonomy' => 'ka_course_location',
            'terms' => get_filtered_location_terms(),
            'url_key' => 'sted',
            'filter_key' => 'locations',
            'counts' => get_filter_value_counts('locations', $active_filters),
        ],
        'instructors' => [
            'taxonomy' => 'ka_instructors',
            'terms' => get_filtered_terms('ka_instructors'),
            'url_key' => 'i',
            'filter_key' => 'instructors',
            'counts' => get_filter_value_counts('instructors', $active_filters),
        ],
        'language' => [
            'taxonomy' => '',
            'terms' => get_filtered_languages(),
            'url_key' => 'sprak',
            'filter_key' => 'language',
            'counts' => get_filter_value_counts('language', $active_filters),
        ],
        'months' => [
            'taxonomy' => '',
            'terms' => get_filtered_months(),
            'url_key' => 'mnd',
            'filter_key' => 'months',
            'counts' => get_filter_value_counts('months', $active_filters),
        ]
    ];

    // Skjul lokasjoner som ikke har noen kurs (count = 0) når ingen filtre er aktive
    $has_active_filters = false;
    foreach ($active_filters as $key => $vals) {
        if (!empty($vals)) { $has_active_filters = true; break; }
    }
    if (!$has_active_filters && !empty($taxonomy_data['locations']['terms'])) {
        $location_counts = $taxonomy_data['locations']['counts'] ?? [];
        $taxonomy_data['locations']['terms'] = array_values(array_filter($taxonomy_data['locations']['terms'], function($term) use ($location_counts) {
            $name = is_object($term) && isset($term->name) ? $term->name : '';
            return isset($location_counts[$name]) && (int)$location_counts[$name] > 0;
        }));
    }


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

    // Start output buffering
    ob_start();
    
    // Add custom class if provided
    $custom_class = !empty($atts['klasse']) ? ' ' . esc_attr($atts['klasse']) : '';
    ?>
    <div id="ka" class="kursagenten-wrapper<?php echo $custom_class; ?>">
    <main id="ka-m" class="kursagenten-main" role="main">
        <div class="ka-container">
            <!-- Mobile Filter Overlay -->

            <div class="mobile-filter-overlay">
                <div class="mobile-filter-header">
                    <h3>Filter</h3>
                    <button class="close-filter-button">
                        <i class="ka-icon icon-close"></i>
                    </button>
                </div>
                <div class="mobile-filter-content">
                    <!-- Filtre vil bli lagt til her via JavaScript -->
                </div>
            </div>
            <!-- End Mobile Filter -->

            <article class="ka-outer-container course-container">
                <section class="ka-section ka-main-content ka-courselist">
                    <div class="ka-content-container inner-container top-filter-section">
                        <div class="course-grid <?php echo esc_attr($left_column_class); ?>">
                            <?php if ($has_left_filters) : ?>
                            <div class="left-column"></div>
                            <?php endif; ?>

                            <div class="filter-container filter-top">
                                <!-- Dynamic Filter Generation -->
                                <?php foreach ($top_filters as $filter) : ?>
                                    <?php 
                                    // Skip filters that are active in shortcode
                                    if (should_hide_filter($filter, $active_shortcode_filters)) {
                                        continue;
                                    }
                                    ?>
                                    <div class="filter-item <?php echo esc_attr($filter_types[$filter] ?? ''); ?> <?php echo esc_attr($search_class); ?>">
                                        <?php 
                                        if ($filter === 'search') : ?>
                                            <input type="text" id="search" name="search" class="filter-search <?php echo esc_attr($search_class); ?>" placeholder="Søk etter kurs...">
                                        <?php elseif ($filter === 'date') : ?>
                                            <?php
                                            $date = "";
                                            if (isset($_REQUEST['dato'])) {
                                                $dates = explode('-', $_REQUEST['dato']);
                                                if (count($dates) === 2) {
                                                    $from_date = ka_format_date(\DateTime::createFromFormat('d.m.Y', trim($dates[0]))->format('Y-m-d'));
                                                    $to_date = ka_format_date(\DateTime::createFromFormat('d.m.Y', trim($dates[1]))->format('Y-m-d'));
                                                    $date = sprintf('%s - %s', $from_date, $to_date);
                                                }
                                            }
                                            
                                            $is_left_filter = in_array('date', $left_filters);
                                            $caleran_class = $is_left_filter ? 'caleran caleran-left' : 'caleran';
                                            ?>
                                            <div class="date-range-wrapper">
                                                <input type="text" 
                                                        id="date-range" 
                                                        class="<?php echo esc_attr($caleran_class); ?> ka-caleran"
                                                        data-filter-key="date"
                                                        data-url-key="dato"
                                                        name="calendar-input"
                                                        placeholder="Velg fra-til dato"
                                                        value="<?php echo esc_attr($date); ?>"
                                                        aria-label="Velg datoer">
                                                <i class="ka-icon icon-chevron-down"></i>
                                                <a href="#" class="reset-date-filter" style="display: <?php echo $date ? 'block' : 'none'; ?>; font-size: var(--ka-font-xs); color: rgb(235, 121, 121); margin-top: 5px; text-decoration: none;">Nullstill dato</a>
                                            </div>
                                        <?php elseif (!empty($taxonomy_data[$filter]['terms'])) : ?>
                                            <?php 
                                            if ($filter_types[$filter] === 'chips') : ?>
                                                <!-- Chip-style Filter Display -->
                                                <div class="filter-chip-wrapper">
                                                    <?php foreach ($taxonomy_data[$filter]['terms'] as $term) : ?>
                                                        <?php
                                                        // Spesiell håndtering for måned-filter
                                                        if ($filter === 'months') {
                                                            $chip_value = $term['value'];
                                                            $chip_name = $term['name'];
                                                        } else {
                                                            // For locations, bruk term-navn (med diakritikk) som filterverdi, ikke slug
                                                            if ($taxonomy_data[$filter]['filter_key'] === 'locations' && is_object($term)) {
                                                                $chip_value = $term->name;
                                                                $chip_name = $term->name;
                                                            } else {
                                                                $chip_value = is_object($term) ? $term->slug : (is_string($term) ? strtolower($term) : '');
                                                                $chip_name = is_object($term) ? $term->name : (is_string($term) ? ucfirst($term) : '');
                                                            }
                                                        }
                                                        ?>
                                                        <button class="chip filter-chip"
                                                            data-filter-key="<?php echo esc_attr($taxonomy_data[$filter]['filter_key']); ?>"
                                                            data-url-key="<?php echo esc_attr($taxonomy_data[$filter]['url_key']); ?>"
                                                            data-filter="<?php echo esc_attr($chip_value); ?>">
                                                            <?php echo esc_html($chip_name); ?>
                                                        </button>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php elseif ($filter_types[$filter] === 'list') : ?>
                                                <?php 
                                                ?>
                                                <div id="filter-list-<?php echo esc_attr($taxonomy_data[$filter]['filter_key']); ?>" class="filter">
                                                    <div class="filter-dropdown">
                                                        <?php
                                                        $current_filter_info = $filter_display_info[$filter] ?? [];
                                                        $filter_label = $current_filter_info['label'] ?? '';
                                                        $filter_placeholder = $current_filter_info['placeholder'] ?? 'Velg';

                                                        $url_key = $taxonomy_data[$filter]['url_key'];
                                                        $active_filters = isset($_GET[$url_key]) ? explode(',', $_GET[$url_key]) : [];


                                                        if (empty($active_filters)) {
                                                            $display_text = $filter_placeholder;
                                                        } else {
                                                            $active_names = [];
                                                            foreach ($active_filters as $slug) {
                                                                foreach ($taxonomy_data[$filter]['terms'] as $term) {
                                                                    if (is_object($term) && $term->slug === $slug) {
                                                                        $active_names[] = $term->name;
                                                                        break;
                                                                    }
                                                                }
                                                            }

                                                            $display_text = count($active_names) <= 2 ?
                                                                implode(', ', $active_names) :
                                                                sprintf('%d %s valgt', count($active_names), strtolower($filter_label));
                                                        }

                                                        $has_active_filters = !empty($active_filters) ? 'has-active-filters' : '';
                                                        ?>
                                                        <div class="filter-dropdown-toggle <?php echo esc_attr($has_active_filters); ?>"
                                                            data-filter="<?php echo esc_attr($filter); ?>"
                                                            data-label="<?php echo esc_attr($filter_label); ?>"
                                                            data-placeholder="<?php echo esc_attr($filter_placeholder); ?>">
                                                            <span class="selected-text"><?php echo esc_html($display_text); ?></span>
                                                            <span class="dropdown-icon"><i class="ka-icon icon-chevron-down"></i></span>
                                                        </div>
                                                        <div class="filter-dropdown-content">
                                                            <?php 
                                                            if ($filter === 'categories') {
                                                                
                                                                
                                                                foreach ($taxonomy_data['categories']['terms'] as $category) {
                                                                    
                                                                    $is_checked = in_array($category->slug, $active_filters) ? 'checked' : '';
                                                                    $parent_class = isset($category->parent_class) ? $category->parent_class : '';
                                                                    $parent_id_attr = isset($category->parent_id) ? ' data-parent-id="' . esc_attr($category->parent_id) . '"' : '';
                                                                    
                                                                    // Legg til klasser basert på parent/child-status
                                                                    $hierarchy_classes = '';
                                                                    if (isset($category->has_children) && $category->has_children) {
                                                                        $hierarchy_classes .= ' ka-parent';
                                                                    }
                                                                    if (isset($category->parent_id) && $category->parent_id > 0) {
                                                                        $hierarchy_classes .= ' ka-child';
                                                                    }
                                                                    
                                                                    // Sjekk om denne kategorien har 0 kurs
                                                                    $count = $taxonomy_data['categories']['counts'][$category->slug] ?? 0;
                                                                    $empty_class = ($count === 0) ? ' filter-empty' : ' filter-available';
                                                                    
                                                                    echo '<div class="filter-category' . ($parent_class ? ' ' . $parent_class : '') . $hierarchy_classes . '" data-term-id="' . esc_attr($category->term_id) . '"' . $parent_id_attr . '>';
                                                                    echo '<label class="filter-list-item checkbox' . $empty_class . '">';
                                                                    echo '<input type="checkbox" 
                                                                        class="filter-checkbox"
                                                                        value="' . esc_attr($category->slug) . '" 
                                                                        data-filter-key="categories" 
                                                                        data-url-key="k" 
                                                                        ' . $is_checked . '>';
                                                                    echo '<span class="checkbox-label">' . esc_html($category->name) . '</span>';
                                                                    echo '</label>';
                                                                    echo '</div>';
                                                                }
                                                            } else {
                                                                // For andre filtre, vis en enkel liste
                                                                foreach ($taxonomy_data[$filter]['terms'] as $term) {
                                                                    // Spesiell håndtering for måned-filter
                                                                    if ($filter === 'months') {
                                                                        $term_value = $term['value'];
                                                                        $term_name = $term['name'];
                                                                    } else {
                                                                        // For locations-listen, bruk term-navn (med æ/ø/å) som URL-verdi
                                                                        if ($taxonomy_data[$filter]['filter_key'] === 'locations' && is_object($term)) {
                                                                            $term_value = $term->name;
                                                                            $term_name = $term->name;
                                                                        } else {
                                                                            $term_value = is_object($term) ? $term->slug : (is_string($term) ? strtolower($term) : '');
                                                                            $term_name = is_object($term) ? $term->name : (is_string($term) ? ucfirst($term) : '');
                                                                        }
                                                                    }
                                                                    $is_checked = in_array($term_value, $active_filters) ? 'checked' : '';
                                                                    
                                                                    // Sjekk om denne termen har 0 kurs
                                                                    $count = $taxonomy_data[$filter]['counts'][$term_value] ?? 0;
                                                                    $empty_class = ($count === 0) ? ' filter-empty' : ' filter-available';
                                                                    ?>
                                                                    <label class="filter-list-item checkbox<?php echo $empty_class; ?>">
                                                                        <input type="checkbox" 
                                                                               class="filter-checkbox"
                                                                               value="<?php echo esc_attr($term_value); ?>"
                                                                               data-filter-key="<?php echo esc_attr($taxonomy_data[$filter]['filter_key']); ?>"
                                                                               data-url-key="<?php echo esc_attr($taxonomy_data[$filter]['url_key']); ?>"
                                                                               <?php echo $is_checked; ?>>
                                                                        <span class="checkbox-label"><?php echo esc_html($term_name); ?></span>
                                                                    </label>
                                                                    <?php
                                                                }
                                                            }
                                                            ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>

                                <div id="active-filters-container">
                                    <div id="active-filters" class="active-filters"></div>
                                    <a href="#" id="reset-filters" class="reset-filters reset-filters-btn">Nullstill filter</a>
                                </div>
                            </div>
                        </div>
                    </div>



                    <!-- Main Content Container with Columns -->
                    <div class="ka-content-container inner-container main-section">
                        <div class="course-grid <?php echo esc_attr($left_column_class); ?>">
                            <!-- Left Column left filters-->
                            <div class="left-column">
                                <?php if ($has_left_filters) : ?>
                                    <div class="filter-container left-filter-section">
                                        <?php foreach ($left_filters as $filter) : ?>
                                            <?php 
                                            // Skip filters that are active in shortcode
                                            if (should_hide_filter($filter, $active_shortcode_filters)) {
                                                continue;
                                            }
                                            ?>

                                            <div class="filter-item">
                                                <?php
                                                $current_filter_info = $filter_display_info[$filter] ?? [];
                                                $filter_label = $current_filter_info['label'] ?? '';
                                                ?>
                                                <h5><?php echo $filter_label; ?></h5>
                                                <?php if ($filter === 'search') : ?>
                                                    <input type="text" id="search" name="search" class="filter-search <?php echo esc_attr($search_class); ?>" placeholder="Søk etter kurs...">
                                                <?php elseif ($filter === 'date') : ?>
                                                    <?php
                                                    $date = "";
                                                    if (isset($_REQUEST['dato'])) {
                                                        $dates = explode('-', $_REQUEST['dato']);
                                                        if (count($dates) === 2) {
                                                            $from_date = ka_format_date(\DateTime::createFromFormat('d.m.Y', trim($dates[0]))->format('Y-m-d'));
                                                            $to_date = ka_format_date(\DateTime::createFromFormat('d.m.Y', trim($dates[1]))->format('Y-m-d'));
                                                            $date = sprintf('%s - %s', $from_date, $to_date);
                                                        }
                                                    }
                                                    
                                                    $is_left_filter = in_array('date', $left_filters);
                                                    $caleran_class = $is_left_filter ? 'caleran caleran-left' : 'caleran';
                                                    ?>
                                                    <div class="date-range-wrapper">
                                                        <input type="text" 
                                                                id="date-range" 
                                                                class="<?php echo esc_attr($caleran_class); ?> ka-caleran"
                                                                data-filter-key="date"
                                                                data-url-key="dato"
                                                                name="calendar-input"
                                                                placeholder="Velg fra-til dato"
                                                                value="<?php echo esc_attr($date); ?>"
                                                                aria-label="Velg datoer">
                                                        <i class="ka-icon icon-chevron-down"></i>
                                                        <a href="#" class="reset-date-filter" style="display: <?php echo $date ? 'block' : 'none'; ?>; font-size: var(--ka-font-xs); color: rgb(235, 121, 121); margin-top: 5px; text-decoration: none;">Nullstill dato</a>
                                                    </div>
                                                <?php elseif (!empty($taxonomy_data[$filter]['terms'])) : ?>
                                                    <?php 
                                                    if ($filter_types[$filter] === 'chips') : ?>
                                                        <div class="filter-chip-wrapper">
                                                            <?php foreach ($taxonomy_data[$filter]['terms'] as $term) : ?>
                                                                <?php
                                                                // Spesiell håndtering for måned-filter
                                                                if ($filter === 'months') {
                                                                    $chip_value = $term['value'];
                                                                    $chip_name = $term['name'];
                                                                } else {
                                                                    // For locations, bruk term-navn (med diakritikk) som filterverdi, ikke slug
                                                                    if ($taxonomy_data[$filter]['filter_key'] === 'locations' && is_object($term)) {
                                                                        $chip_value = $term->name;
                                                                        $chip_name = $term->name;
                                                                    } else {
                                                                        $chip_value = is_object($term) ? $term->slug : (is_string($term) ? strtolower($term) : '');
                                                                        $chip_name = is_object($term) ? $term->name : (is_string($term) ? ucfirst($term) : '');
                                                                    }
                                                                }
                                                                ?>
                                                                <button class="chip filter-chip"
                                                                    data-filter-key="<?php echo esc_attr($taxonomy_data[$filter]['filter_key']); ?>"
                                                                    data-url-key="<?php echo esc_attr($taxonomy_data[$filter]['url_key']); ?>"
                                                                    data-filter="<?php echo esc_attr($chip_value); ?>">
                                                                    <?php echo esc_html($chip_name); ?>
                                                                </button>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php elseif ($filter_types[$filter] === 'list') : ?>
                                                        <?php 
                                                        if ($filter === 'categories') {
                                                        }
                                                        ?>
                                                        <?php 
                                                        // Hent innstillinger for filterhøyde
                                                        $default_height = get_option('kursagenten_filter_default_height', 250);
                                                        $no_collapse_settings = get_option('kursagenten_filter_no_collapse', array());
                                                        $no_collapse = isset($no_collapse_settings[$filter]) && $no_collapse_settings[$filter];
                                                        $data_size = $no_collapse ? 'auto' : $default_height;
                                                        ?>
                                                        <div id="filter-list-<?php echo esc_attr($taxonomy_data[$filter]['filter_key']); ?>" class="filter-list expand-content" data-size="<?php echo esc_attr($data_size); ?>">
                                                            <?php foreach ($taxonomy_data[$filter]['terms'] as $term) : ?>
                                                                <?php 
                                                                if ($filter === 'months') {
                                                                    $term_value = $term['value'];
                                                                    $term_name = $term['name'];
                                                                    $parent_class = '';
                                                                    $parent_id_attr = '';
                                                                } else {
                                                                    // For locations i venstre liste: bruk term-navn (med diakritikk) som verdi
                                                                    if ($taxonomy_data[$filter]['filter_key'] === 'locations' && is_object($term)) {
                                                                        $term_value = $term->name;
                                                                        $term_name = $term->name;
                                                                    } else {
                                                                        $term_value = is_object($term) ? $term->slug : (is_string($term) ? strtolower($term) : '');
                                                                        $term_name = is_object($term) ? $term->name : (is_string($term) ? ucfirst($term) : '');
                                                                    }
                                                                    $parent_class = isset($term->parent_class) ? $term->parent_class : '';
                                                                    $parent_id_attr = isset($term->parent_id) ? ' data-parent-id="' . esc_attr($term->parent_id) . '"' : '';
                                                                    
                                                                    // Legg til klasser basert på parent/child-status
                                                                    $hierarchy_classes = '';
                                                                    if (is_object($term)) {
                                                                        if (isset($term->has_children) && $term->has_children) {
                                                                            $hierarchy_classes .= ' ka-parent';
                                                                        }
                                                                        if (isset($term->parent_id) && $term->parent_id > 0) {
                                                                            $hierarchy_classes .= ' ka-child';
                                                                        }
                                                                    }
                                                                }
                                                                $url_key = $taxonomy_data[$filter]['url_key'];
                                                                
                                                                // Sjekk om denne termen har 0 kurs
                                                                $count = $taxonomy_data[$filter]['counts'][$term_value] ?? 0;
                                                                $empty_class = ($count === 0) ? ' filter-empty' : ' filter-available';
                                                                ?>
                                                                <div class="filter-category<?php echo $parent_class ? ' ' . esc_attr($parent_class) : ''; ?><?php echo $hierarchy_classes; ?>" data-term-id="<?php echo is_object($term) ? esc_attr($term->term_id) : ''; ?>"<?php echo $parent_id_attr; ?>>
                                                                <label class="filter-list-item checkbox<?php echo $empty_class; ?>">
                                                                    <input type="checkbox" class="filter-checkbox"
                                                                        value="<?php echo esc_attr($term_value); ?>"
                                                                        data-filter-key="<?php echo esc_attr($taxonomy_data[$filter]['filter_key']); ?>"
                                                                        data-url-key="<?php echo esc_attr($url_key); ?>">
                                                                    <span class="checkbox-label"><?php echo esc_html($term_name); ?></span>
                                                                </label>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Right Column -->
                            <div class="courselist-items-wrapper right-column">
                                <!-- Mobile Filter Button (Sticky) -->
                                <button class="filter-toggle-button sticky-filter-button">
                                    <div class="ka-icon-wrapper">
                                    <i class="ka-icon icon-filter"></i>
                                    </div>
                                    <span>Filtrer kurs</span>
                                    
                                </button>
                                
                                <?php if ($query instanceof WP_Query && $query->have_posts()) : ?>
                                    <div class="courselist-header">
                                        <div id="courselist-header-left">
                                            <div id="course-count"><?php echo intval($displayed_course_count); ?> kurs <?php echo (!$limit_mode && $query->max_num_pages > 1) ? sprintf("- side %d av %d", $query->get('paged'), $query->max_num_pages) : ''; ?></div>
                                        </div>

                                        <div id="courselist-header-right">
                                            <!-- Antall kurs per side dropdown -->
                                            <div class="per-page-dropdown select-dropdown">
                                                <div class="per-page-dropdown-toggle">
                                                    <span class="selected-text">Vis antall kurs</span>
                                                    <span class="dropdown-icon"><i class="ka-icon icon-chevron-down"></i></span>
                                                </div>
                                                <div class="per-page-dropdown-content select-dropdown-content">
                                                    <?php
                                                    $current_per_page = isset($_REQUEST['per_page']) ? absint($_REQUEST['per_page']) : get_option('kursagenten_courses_per_page', 5);
                                                    $options = array(5, 10, 20, 30, 50);
                                                    foreach ($options as $option) :
                                                        $selected = $current_per_page == $option ? 'selected' : '';
                                                    ?>
                                                        <button class="per-page-option select-option <?php echo $selected; ?>" 
                                                                data-per-page="<?php echo $option; ?>">
                                                            <?php echo $option; ?> kurs
                                                        </button>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>

                                            <div class="sort-dropdown select-dropdown">
                                                <div class="sort-dropdown-toggle">
                                                    <span class="selected-text">Sorter etter</span>
                                                    <span class="dropdown-icon"><i class="ka-icon icon-chevron-down"></i></span>
                                                </div>
                                                <div class="sort-dropdown-content select-dropdown-content">
                                                    <button class="sort-option select-option" data-sort="standard" data-order="">Standard</button>
                                                    <button class="sort-option select-option" data-sort="title" data-order="asc">Fra A til Å</button>
                                                    <button class="sort-option select-option" data-sort="title" data-order="desc">Fra Å til A</button>
                                                    <button class="sort-option select-option" data-sort="price" data-order="asc">Pris lav til høy</button>
                                                    <button class="sort-option select-option" data-sort="price" data-order="desc">Pris høy til lav</button>
                                                    <button class="sort-option select-option" data-sort="date" data-order="asc">Tidligste dato</button>
                                                    <button class="sort-option select-option" data-sort="date" data-order="desc">Seneste dato</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="courselist-items" id="filter-results" data-list-type="<?php echo esc_attr($list_type); ?>">
                                        <?php
                                        $args = [
                                            'course_count' => $query->found_posts,
                                            'query' => $query,
                                            'force_standard_view' => $atts['force_standard_view'] === 'true',
                                            'list_type' => $list_type,
                                            'view_type' => 'all_coursedates',
                                            'is_taxonomy_page' => false,
                                            'shortcode_show_images' => $atts['bilder']
                                        ];

                                        

                                        while ($query->have_posts()) : $query->the_post();
                                            get_course_template_part($args);
                                        endwhile;
                                        ?>
                                    </div>

                                    <?php if (!$limit_mode) : ?>
                                    <div class="pagination-wrapper">
                                        <div class="pagination">
                                        <?php
                                        // Hent gjeldende side URL som base for paginering
                                        $current_url = '';
                                        if (is_tax()) {
                                            // På taksonomisider, bruk term link
                                            $term = get_queried_object();
                                            if ($term instanceof WP_Term) {
                                                $current_url = get_term_link($term);
                                            }
                                        }
                                        if (!$current_url) {
                                            $current_url = get_permalink();
                                        }
                                        if (!$current_url) {
                                            $current_url = home_url('/');
                                        }

                                        // Fjern ALLE query parametere fra URL-en - de skal kun komme fra add_args
                                        $current_url = strtok($current_url, '?');

                        // Legg til kortkode-parametre i add_args hvis de finnes
                        $add_args = array_map(function ($item) {
                            return is_array($item) ? join(',', $item) : $item;
                        }, array_diff_key($_REQUEST, ['side' => true, 'action' => true, 'nonce' => true, 'current_url' => true]));
                                        
                                        // Legg til kortkode-parametre hvis de ikke allerede er i $_REQUEST
                                        if ($has_shortcode_filters) {
                                            foreach ($shortcode_params as $key => $value) {
                                                if (!isset($add_args[$key])) {
                                                    $add_args[$key] = $value;
                                                }
                                            }
                                        }

                                        echo paginate_links([
                                            'base' => $current_url . '%_%',
                                            'current' => max(1, $query->get('paged')),
                                            'format' => '?side=%#%',
                                            'total' => $query->max_num_pages,
                                            'prev_text' => '<i class="ka-icon icon-chevron-left"></i> <span>Forrige</span>',
                                            'next_text' => '<span>Neste</span> <i class="ka-icon icon-chevron-right"></i>',
                                            'add_args' => $add_args
                                        ]);
                                        ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <div class="course-loading" style="display: none;">
                                        <div class="loading-spinner"></div>
                                    </div>
                                <?php
                                    wp_reset_postdata();
                                else :
                                    echo '<p>Ingen kurs tilgjengelige.</p>';
                                endif;
                                ?>
                            </div>
                        </div>
                    </div>
                </section>
            </article>
        </div>
    </main>
</div>

    <!-- Filter Settings for JavaScript -->
    <script id="filter-settings" type="application/json">
        <?php
        $filter_data = [
            'top_filters' => get_option('kursagenten_top_filters', []),
            'left_filters' => get_option('kursagenten_left_filters', []),
            'filter_types' => get_option('kursagenten_filter_types', []),
            'available_filters' => get_option('kursagenten_available_filters', []),
        ];
        echo json_encode($filter_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);
        ?>
    </script>
    <!-- Mobile Filter Handling -->
    <script type="text/javascript">
    
    jQuery(document).ready(function($) {
        // Håndter per-page dropdown
        $('.per-page-dropdown-toggle').on('click', function() {
            $(this).parent().toggleClass('active');
        });

        $('.per-page-option').on('click', function() {
            const perPage = $(this).data('per-page');
            const currentUrl = new URL(window.location.href);
            
            // Fjern problematiske parametere først
            currentUrl.searchParams.delete('current_url');
            currentUrl.searchParams.delete('side');
            
            // Sett per_page
            currentUrl.searchParams.set('per_page', perPage);
            
            // Fjern per_page fra aktive filtre før vi oppdaterer URL
            $('#active-filters .filter-tag[data-param="per_page"]').remove();
            
            // Legg til kortkode-parametre hvis de finnes
            if (kurskalender_data.has_shortcode_filters && kurskalender_data.shortcode_params) {
                Object.keys(kurskalender_data.shortcode_params).forEach(key => {
                    currentUrl.searchParams.set(key, kurskalender_data.shortcode_params[key]);
                });
            }
            
            window.location.href = currentUrl.toString();
        });

        // Lukk dropdown når man klikker utenfor
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.per-page-dropdown').length) {
                $('.per-page-dropdown').removeClass('active');
            }
        });

        // Overstyr standard scroll-oppførsel for mobilfiltre
        $(document).ajaxSuccess(function(event, xhr, settings) {
            if (settings.data && settings.data.includes('action=filter_courses')) {
                // Forhindre scroll hvis vi er i mobilvisning og filteroverlay er synlig
                if ($('.mobile-filter-overlay').is(':visible')) {
                    event.stopImmediatePropagation();
                    return false;
                }
            }
        });

        // Debug logging
        const DEBUG = false;
        function log() { /* no-op */ }


        // Sjekk om elementene eksisterer før vi legger til event listeners
        const filterToggleBtn = $('.filter-toggle-button');
        const mobileOverlay = $('.mobile-filter-overlay');
        const closeFilterBtn = $('.close-filter-button');
        
        // Hold styr på om filtrene er lastet
        let mobileFiltersLoaded = false;
        let isLoadingFilters = false;
        let mobileCaleranInstance = null;  // Ny global variabel for caleran

        // Funksjon for å initialisere datepicker
        function initializeDatepicker() {
            // log('Initialiserer datepicker');
            const mobileDatepicker = document.querySelector('.mobile-filter-content .caleran');
            const dateWrapper = document.querySelector('.mobile-filter-content .date-range-wrapper');
            const dateInputMobile = document.getElementById('date-range-mobile');
            
            if (mobileDatepicker && dateWrapper) {
                // Initialiser Caleran for mobil
                mobileCaleranInstance = caleran(dateInputMobile, {
                    showOnClick: true,
                    autoCloseOnSelect: false,
                    format: "DD.MM.YYYY",
                    rangeOrientation: "vertical",
                    calendarCount: 1,
                    showHeader: true,
                    showFooter: true,
                    showButtons: true,
                    applyLabel: "Bruk",
                    cancelLabel: "Avbryt",
                    showOn: "bottom",
                    arrowOn: "center",
                    autoAlign: true,
                    enableSwipe: true,
                    inline: false,
                    minDate: moment(),
                    startEmpty: true,
                    nextMonthIcon: '<i class="ka-icon icon-chevron-right"></i>',
                    prevMonthIcon: '<i class="ka-icon icon-chevron-left"></i>',
                    rangeIcon: '<i class="ka-icon icon-calendar"></i>',
                    headerSeparator: '<i class="ka-icon icon-chevron-right calendar-header-separator"></i>',
                    rangeLabel: "Velg periode",
                    ranges: [
                        {
                            title: "Neste uke",
                            startDate: moment(),
                            endDate: moment().add(1, 'week')
                        },
                        {
                            title: "Neste 3 måneder",
                            startDate: moment(),
                            endDate: moment().add(3, 'month')
                        },
                        {
                            title: "Neste 6 måneder",
                            startDate: moment(),
                            endDate: moment().add(6, 'month')
                        },
                        {
                            title: "Resten av året",
                            startDate: moment(),
                            endDate: moment().endOf('year')
                        },
                        {
                            title: "Neste år",
                            startDate: moment().add(1, 'year').startOf('year'),
                            endDate: moment().add(1, 'year').endOf('year')
                        }
                    ],
                    verticalOffset: 10,
                    locale: 'nb',
                    onafterselect: function(caleran, startDate, endDate) {
                        if (startDate && endDate) {
                            const fromDate = startDate.format("DD.MM.YYYY");
                            const toDate = endDate.format("DD.MM.YYYY");
                            // Oppdater URL med nye datoer
                            const currentFilters = getCurrentFiltersFromURL();
                            const updatedFilters = {
                                ...currentFilters,
                                dato: `${fromDate}-${toDate}`
                            };
                            updateURLParams(updatedFilters);
                            
                            // Vis nullstill-knappen
                            const resetBtn = dateWrapper.nextElementSibling;
                            if (resetBtn && resetBtn.classList.contains('reset-date-filter')) {
                                resetBtn.style.display = 'block';
                            }
                        }
                    },
                    oncancel: function() {
                        // Fjern dato fra URL
                        const currentFilters = getCurrentFiltersFromURL();
                        delete currentFilters.dato;
                        updateURLParams(currentFilters);
                        
                        // Skjul nullstill-knappen
                        const resetBtn = dateWrapper.nextElementSibling;
                        if (resetBtn && resetBtn.classList.contains('reset-date-filter')) {
                            resetBtn.style.display = 'none';
                        }
                    }
                });

                // Legg til event listener for nullstill-knappen
                document.querySelectorAll('.reset-date-filter').forEach(function(resetBtn) {
                    resetBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        log('Nullstill dato klikket');
                        
                        // Finn datoinput og nullstill den
                        const dateInput = this.parentElement.querySelector('.ka-caleran');
                        if (dateInput) {
                            dateInput.value = '';
                            this.style.display = 'none';
                        }
                        
                        // Nullstill Caleran-instansen hvis den eksisterer
                        if (mobileCaleranInstance) {
                            mobileCaleranInstance.clearInput();
                            mobileCaleranInstance.clear();
                        }
                        
                        // Oppdater URL uten å laste siden på nytt
                        const currentFilters = getCurrentFiltersFromURL();
                        delete currentFilters.dato;
                        const queryString = Object.entries(currentFilters)
                            .map(([key, value]) => `${key}=${Array.isArray(value) ? value.join(',') : value}`)
                            .join('&');
                        
                        const newUrl = `${window.location.pathname}${queryString ? '?' + queryString : ''}`;
                        window.history.pushState({}, '', newUrl);
                        
                        // Trigger AJAX-oppdatering av resultater
                        $.ajax({
                            url: kurskalender_data.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'filter_courses',
                                nonce: kurskalender_data.filter_nonce,
                                ...currentFilters
                            },
                            beforeSend: function() {
                                $('.course-loading').show();
                            },
                            success: function(response) {
                                if (response.success) {
                                    $('#filter-results').html(response.data.html);
                                    $('#course-count').html(response.data.count);
                                }
                            },
                            complete: function() {
                                $('.course-loading').hide();
                            }
                        });
                    });
                });

                // Oppdater visning av nullstill-knapp når dato velges
                if (dateInputMobile) {
                    const observer = new MutationObserver(function(mutations) {
                        mutations.forEach(function(mutation) {
                            if (mutation.type === 'attributes' && mutation.attributeName === 'value') {
                                const resetBtn = dateWrapper.querySelector('.reset-date-filter');
                                if (resetBtn) {
                                    resetBtn.style.display = dateInputMobile.value ? 'block' : 'none';
                                }
                            }
                        });
                    });
                    observer.observe(dateInputMobile, { attributes: true });
                }
            }
        }

                 // Event listener for "Nullstill filter"
         $('.mobile-filter-content .reset-filters-button').on('click', function() {
             log('Reset klikket');
             $('.mobile-filter-content .filter-checkbox').prop('checked', false);
             if (mobileCaleranInstance) {
                 mobileCaleranInstance.clearInput();
             }
             $('.mobile-filter-content .filter-search').val('');
             updateFilters();
             // Oppdater filter counts etter nullstilling
             setTimeout(updateFilterCounts, 500);
         });

        // Funksjon for å hente aktive filtre fra URL
        function getActiveFiltersFromUrl() {
            const urlParams = new URLSearchParams(window.location.search);
            const activeFilters = {};
            
            // Liste over alle mulige filter-parametre
            const filterParams = ['k', 'sted', 'i', 'sprak', 'mnd', 'dato', 'sok'];
            
            filterParams.forEach(param => {
                if (urlParams.has(param)) {
                    const value = urlParams.get(param);
                    // Dekod verdiene før vi splitter på komma
                    activeFilters[param] = value.split(',').map(v => decodeURIComponent(v.trim()));
                }
            });
            
            if (DEBUG) {
                // 
            }
            
            return activeFilters;
        }

        // Funksjon for å gjenopprette aktive filtre
        function restoreActiveFilters() {
            const activeFilters = getActiveFiltersFromUrl();
            
            // Gjenopprett søk
            if (activeFilters.sok) {
                $('.mobile-filter-content .filter-search').val(activeFilters.sok);
            }
            
            // Gjenopprett dato
            if (activeFilters.dato) {
                $('.mobile-filter-content .caleran').val(activeFilters.dato);
            }
            
            // Gjenopprett chips
            $('.mobile-filter-content .filter-chip').each(function() {
                const urlKey = $(this).data('url-key');
                const filterValue = $(this).data('filter');
                
                if (activeFilters[urlKey] && activeFilters[urlKey].includes(filterValue)) {
                    $(this).addClass('active');
                }
            });
            
            // Gjenopprett checkboxes - først fjern alle, deretter sett riktige
            $('.mobile-filter-content .filter-checkbox').prop('checked', false);
            
            $('.mobile-filter-content .filter-checkbox').each(function() {
                const urlKey = $(this).data('url-key');
                const filterValue = $(this).val();
                
                if (activeFilters[urlKey]) {
                    // Spesiell håndtering for måned-filter
                    if (urlKey === 'mnd') {
                        // Konverter månedene til samme format for sammenligning
                        const normalizedValue = filterValue.padStart(2, '0');
                        const normalizedActiveFilters = activeFilters[urlKey].map(m => m.padStart(2, '0'));
                        if (normalizedActiveFilters.includes(normalizedValue)) {
                            $(this).prop('checked', true);
                        }
                    } else {
                        if (activeFilters[urlKey].includes(filterValue)) {
                            $(this).prop('checked', true);
                        }
                    }
                }
            });
            
            if (DEBUG) {
                // 
            }
        }
        
        if (filterToggleBtn.length) {
            filterToggleBtn.on('click', function() {
                // log('Filter-knapp klikket');
                if (mobileOverlay.length && !isLoadingFilters) {
                    if (!mobileFiltersLoaded) {
                        loadMobileFilters();
                    } else {
                        showMobileFilters();
                    }
                }
            });
        }

        if (closeFilterBtn.length) {
            closeFilterBtn.on('click', function() {
                // log('Lukk-knapp klikket');
                // Bygg URL fra aktive filtre og last siden på nytt (samme som "Vis resultater")
                const filters = {};
                $('.mobile-filter-content .filter-checkbox:checked').each(function() {
                    const key = $(this).data('url-key');
                    const val = $(this).val();
                    if (!filters[key]) { filters[key] = []; }
                    if (!filters[key].includes(val)) { filters[key].push(val); }
                });
                const dateRange = $('.mobile-filter-content .caleran').val();
                if (dateRange) { filters['dato'] = dateRange; }
                const searchTerm = $('.mobile-filter-content .filter-search').val();
                if (searchTerm) { filters['sok'] = searchTerm; }

                // Behold eksisterende sortering/per_page hvis de finnes
                const urlParams = new URLSearchParams(window.location.search);
                const sort = urlParams.get('sort');
                const order = urlParams.get('order');
                const perPage = urlParams.get('per_page');
                if (sort) { filters['sort'] = sort; }
                if (order) { filters['order'] = order; }
                if (perPage) { filters['per_page'] = perPage; }

                const searchParams = new URLSearchParams();
                Object.entries(filters).forEach(([key, value]) => {
                    if (Array.isArray(value)) {
                        // Bare legg til hvis arrayen ikke er tom
                        if (value.length > 0) {
                            searchParams.set(key, value.join(','));
                        }
                    } else if (value !== null && value !== undefined && value !== '') {
                        searchParams.set(key, value);
                    }
                });

                const newUrl = `${window.location.pathname}?${searchParams.toString()}`;
                window.location.href = newUrl;
            });
        }

        function showMobileFilters() {
            mobileOverlay.css('display', 'flex');
            $('body').css('overflow', 'hidden');
            // Gjenopprett aktive filtre når overlay vises
            restoreActiveFilters();
        }

        function hideMobileFilters() {
            mobileOverlay.css('display', 'none');
            $('body').css('overflow', 'auto');
        }

        // Funksjon for å laste mobilfiltre via AJAX
        function loadMobileFilters() {
            if (isLoadingFilters) return;
            isLoadingFilters = true;
            
            // log('Laster mobilfiltre via AJAX');
            
            // Vis lasteindikatorer
            const loadingHtml = '<div class="mobile-filter-loading"><div class="loading-spinner"></div></div>';
            $('.mobile-filter-content').remove(); // Fjern eventuelt eksisterende innhold
            mobileOverlay.append(loadingHtml);
            showMobileFilters();

            $.ajax({
                url: kurskalender_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'load_mobile_filters',
                    nonce: kurskalender_data.filter_nonce,
                    active_shortcode_filters: kurskalender_data.active_shortcode_filters || []
                },
                success: function(response) {
                    // log('Mottok AJAX-respons:', response);
                    
                    if (response.success && response.data && response.data.html) {
                        // log('Mobilfiltre lastet');
                        $('.mobile-filter-loading').remove();
                        mobileOverlay.append(response.data.html);
                        mobileFiltersLoaded = true;
                        // Gjenopprett aktive filtre FØRST
                        restoreActiveFilters();
                        // Deretter initialiser hierarkisk funksjonalitet
                        // log('Initialiserer hierarkisk funksjonalitet etter restoreActiveFilters');
                        initHierarchyToggles('.mobile-filter-content');
                        // Deretter initialiser event listeners
                        initializeMobileFilterEvents();
                        // Oppdater filter counts etter at filtrene er lastet
                        setTimeout(updateFilterCounts, 500);
                    } else {
                        log('Feil i AJAX-respons:', response);
                        showError('Kunne ikke laste filtrene. Vennligst prøv igjen.');
                    }
                },
                error: function(xhr, status, error) {
                    log('Feil ved lasting av mobilfiltre:', error);
                    showError('Kunne ikke laste filtrene. Vennligst prøv igjen.');
                },
                complete: function() {
                    isLoadingFilters = false;
                }
            });
        }

        function showError(message) {
            $('.mobile-filter-loading').remove();
            const errorHtml = `
                <div class="mobile-filter-error">
                    <p>${message}</p>
                    <button class="retry-button">Prøv igjen</button>
                </div>
            `;
            mobileOverlay.append(errorHtml);
            
            // Legg til event listener for retry-knappen
            $('.retry-button').on('click', function() {
                $('.mobile-filter-error').remove();
                loadMobileFilters();
            });
        }

        // Hjelpefunksjoner for å håndtere URL-parametre
        function getCurrentFiltersFromURL() {
            const urlParams = new URLSearchParams(window.location.search);
            const filters = {};
            for (const [key, value] of urlParams.entries()) {
                filters[key] = value;
            }
            return filters;
        }

        function updateURLParams(params) {
            const url = new URL(window.location);
            Object.keys(params).forEach(key => {
                if (params[key] === null || params[key] === undefined) {
                    url.searchParams.delete(key);
                } else {
                    url.searchParams.set(key, params[key]);
                }
            });
            window.history.pushState({}, '', url);
        }

        // Oppdater filter counts når filtre endres
        function updateFilterCounts() {
            const currentFilters = getCurrentFiltersFromURL();
            
            $.ajax({
                url: kurskalender_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_filter_counts',
                    nonce: kurskalender_data.filter_nonce,
                    ...currentFilters
                },
                success: function(response) {
                    if (response.success && response.data.counts) {
                        updateFilterCountsDisplay(response.data.counts);
                    }
                }
            });
        }

        // Funksjon for å initialisere event listeners på mobilfiltre
        function initializeMobileFilterEvents() {
            
            // Event listener for checkboxes - oppdater aktive filtre i sanntid
        $('.mobile-filter-content .filter-checkbox').on('change', function() {
                const $checkbox = $(this);
                const $label = $checkbox.closest('label');
                
                // Forhindre endring på tomme filtre
                if ($label.hasClass('filter-empty')) {
                    // Dette er en tom filter - forhindre endring
                    $checkbox.prop('checked', false);
                    return false;
                }
                
                const filterKey = $checkbox.data('filter-key');

                // Speil desktop-UX for kategorier: barn fjerner forelder, forelder fjerner barn
                if (filterKey === 'categories') {
                    const isChecked = $checkbox.is(':checked');
                    const $childrenWrapper = $checkbox.closest('.ka-children');
                    if ($childrenWrapper.length) {
                        // Dette er et barn
                        if (isChecked) {
                            const $parentCategory = $childrenWrapper.prev('.filter-category.toggle-parent');
                            const $parentCheckbox = $parentCategory.find('input.filter-checkbox');
                            if ($parentCheckbox.prop('checked')) {
                                $parentCheckbox.prop('checked', false);
                            }
                        }
                    } else {
                        // Dette er en forelder
                        if (isChecked) {
                            const $children = $checkbox.closest('.filter-category').next('.ka-children');
                            if ($children && $children.length) {
                                $children.find('input.filter-checkbox:checked').prop('checked', false);
                            }
                        }
                    }
                }

                // Hold URL i synk slik at getActiveFiltersFromUrl() reflekterer endringen
                updateURLFromMobileFilters();

                // Oppdater counts litt etter for å sikre riktig URL-tilstand
                setTimeout(updateFilterCounts, 200);
        });

        // Forhindre klikk på tomme filtervalg
            $('.mobile-filter-content .filter-empty .filter-checkbox').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        });

        // Forhindre klikk på tomme filtervalg i desktop-filtre også
        $(document).on('click', '.filter-empty .filter-checkbox', function(e) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        });

            // Event listener for søk - oppdater aktive filtre i sanntid
            $('.mobile-filter-content .filter-search').on('input', function() {
                // Hold URL i synk
                updateURLFromMobileFilters();
            });

            // Event listener for dato-filter - oppdater aktive filtre i sanntid
            $('.mobile-filter-content .caleran').on('change', function() {
                // Hold URL i synk
                updateURLFromMobileFilters();
            });

            // Event listener for "Vis resultater"
            $('.mobile-filter-content .apply-filters-button').on('click', function() {
                // Bygg URL og last siden på nytt for å unngå mellomtilstand med doble chips
                const filters = {};
                $('.mobile-filter-content .filter-checkbox:checked').each(function() {
                    const key = $(this).data('url-key');
                    const val = $(this).val();
                    if (!filters[key]) { filters[key] = []; }
                    if (!filters[key].includes(val)) { filters[key].push(val); }
                });
                const dateRange = $('.mobile-filter-content .caleran').val();
                if (dateRange) { filters['dato'] = dateRange; }
                const searchTerm = $('.mobile-filter-content .filter-search').val();
                if (searchTerm) { filters['sok'] = searchTerm; }

                // Behold eksisterende sortering/per_page hvis de finnes
                const urlParams = new URLSearchParams(window.location.search);
                const sort = urlParams.get('sort');
                const order = urlParams.get('order');
                const perPage = urlParams.get('per_page');
                if (sort) { filters['sort'] = sort; }
                if (order) { filters['order'] = order; }
                if (perPage) { filters['per_page'] = perPage; }

                const searchParams = new URLSearchParams();
                Object.entries(filters).forEach(([key, value]) => {
                    if (Array.isArray(value)) {
                        // Bare legg til hvis arrayen ikke er tom
                        if (value.length > 0) {
                            searchParams.set(key, value.join(','));
                        }
                    } else if (value !== null && value !== undefined && value !== '') {
                        searchParams.set(key, value);
                    }
                });

                const newUrl = `${window.location.pathname}?${searchParams.toString()}`;
                window.location.href = newUrl;
            });

            // Event listener for "Nullstill filter"
            $('.mobile-filter-content .reset-filters-button').on('click', function() {
                // log('Reset klikket');
                $('.mobile-filter-content .filter-checkbox').prop('checked', false);
                $('.mobile-filter-content .caleran').val('').trigger('change');
                $('.mobile-filter-content .filter-search').val('');
                // Synkroniser URL umiddelbart
                updateURLFromMobileFilters();
                // Oppdater filter counts etter nullstilling
                setTimeout(updateFilterCounts, 500);
            });

            // Lytt på desktop-oppdatering av filtre
            window.addEventListener('ka:filters-updated', function(e){
                try {
                    // Desktop filtre oppdateres automatisk via URL
                } catch(err) {}
            });
            

            // Speil fjerning når man klikker på chips over kurslisten (desktop-container)
            $('#active-filters').on('click', '.remove-filter', function() {
                const $chip = $(this).closest('.active-filter-chip');
                const key = $chip.data('filter-key');
                const value = $chip.data('filter-value');
                removeMobileFilter(key, value);
            });
        }

        


        // Funksjon for å fjerne et filter fra mobilfilteret
        function removeMobileFilter(key, value) {
            // Fjern fra mobilfilteret
            if (key === 'dato') {
                $('.mobile-filter-content .caleran').val('');
            } else if (key === 'sok') {
                $('.mobile-filter-content .filter-search').val('');
            } else {
                // For checkboxes, fjern spesifikk verdi
                $(`.mobile-filter-content .filter-checkbox[data-url-key="${key}"][value="${value}"]`).prop('checked', false);
            }
            
            // Oppdater URL først for å sikre at getActiveFiltersFromUrl() får riktige verdier
            updateURLFromMobileFilters();
            
            // Oppdater filter counts etter fjerning
            setTimeout(updateFilterCounts, 200);
        }

        // Funksjon for å oppdatere URL fra mobile filtre uten å laste siden på nytt
        function updateURLFromMobileFilters() {
            const filters = {};
            
            // Samle alle aktive filtre fra mobile filter
            $('.mobile-filter-content .filter-checkbox:checked').each(function() {
                const filterKey = $(this).data('url-key');
                const filterValue = $(this).val();
                if (!filters[filterKey]) {
                    filters[filterKey] = [];
                }
                if (!filters[filterKey].includes(filterValue)) {
                    filters[filterKey].push(filterValue);
                }
            });

            // Håndter dato-filter
            const dateRange = $('.mobile-filter-content .caleran').val();
            if (dateRange) {
                filters['dato'] = dateRange;
            }

            // Håndter søk-filter
            const searchTerm = $('.mobile-filter-content .filter-search').val();
            if (searchTerm) {
                filters['sok'] = searchTerm;
            }

            // Behold eksisterende per_page parameter hvis den finnes
            const urlParams = new URLSearchParams(window.location.search);
            const perPage = urlParams.get('per_page');
            if (perPage) {
                filters['per_page'] = perPage;
            }

            // Bygg URL med riktig encoding
            const searchParams = new URLSearchParams();
            
            // Legg til alle filtre (URLSearchParams håndterer encoding)
            Object.entries(filters).forEach(([key, value]) => {
                if (Array.isArray(value)) {
                    // Bare legg til hvis arrayen ikke er tom
                    if (value.length > 0) {
                        searchParams.set(key, value.join(','));
                    }
                } else {
                    searchParams.set(key, value);
                }
            });

            // Oppdater URL uten å laste siden på nytt
            const newUrl = `${window.location.pathname}?${searchParams.toString()}`;
            window.history.pushState({}, '', newUrl);
        }

        // Oppdater visning av filter counts - kun visuell indikator
        function updateFilterCountsDisplay(counts) {
            // Reset all states before applying new counts to avoid lingering classes
            // Oppdater både desktop og mobile filtre
            const $allLabels = $('.filter-list-item.checkbox, .mobile-filter-content .filter-list-item.checkbox');
            const $allCheckboxes = $allLabels.find('.filter-checkbox');
            $allLabels.removeClass('filter-empty filter-available');
            $allCheckboxes.prop('disabled', false);
            
            // Oppdater kategorier
            if (counts.categories) {
                Object.keys(counts.categories).forEach(slug => {
                    const count = counts.categories[slug];
                    const $element = $(`.filter-checkbox[data-filter-key="categories"][value="${slug}"], .mobile-filter-content .filter-checkbox[data-filter-key="categories"][value="${slug}"]`);
                    if ($element.length) {
                        const $label = $element.closest('label');
                        
                        if (count === 0) {
                            $label.addClass('filter-empty');
                            $element.prop('disabled', true);
                        } else {
                            $label.addClass('filter-available');
                            $element.prop('disabled', false);
                        }
                    }
                });
            }

            // Oppdater andre filtre på samme måte
            ['locations', 'instructors', 'language', 'months'].forEach(filterType => {
                if (counts[filterType]) {
                    Object.keys(counts[filterType]).forEach(value => {
                        const count = counts[filterType][value];
                        const $element = $(`.filter-checkbox[data-filter-key="${filterType}"][value="${value}"], .mobile-filter-content .filter-checkbox[data-filter-key="${filterType}"][value="${value}"]`);
                        if ($element.length) {
                            const $label = $element.closest('label');
                            
                            if (count === 0) {
                                $label.addClass('filter-empty');
                                $element.prop('disabled', true);
                            } else {
                                $label.addClass('filter-available');
                                $element.prop('disabled', false);
                            }
                        }
                    });
                }
            });
        }

        // Sjekk om det finnes en dato i URL-en og vis nullstill-knappen hvis det gjør det
            const urlParams = new URLSearchParams(window.location.search);
            const dateParam = urlParams.get('dato');
            if (dateParam) {
                document.querySelectorAll('.reset-date-filter').forEach(function(resetBtn) {
                    resetBtn.style.display = 'block';
                });
            }

            // Initialiser datepicker
            initializeDatepicker();

            // Legg til event listener for nullstilling av dato
            document.querySelectorAll('.reset-date-filter').forEach(function(resetBtn) {
                resetBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const dateInput = this.parentElement.querySelector('.ka-caleran');
                    if (dateInput && mobileCaleranInstance) {
                        dateInput.value = '';
                        this.style.display = 'none';
                        
                        // Fjern dato fra URL og oppdater filtre
                        const currentFilters = getCurrentFiltersFromURL();
                        delete currentFilters.dato;
                        updateURLParams(currentFilters);
                    }
                });
            });

            // Oppdater visning av nullstill-knapp når dato velges
            const dateInput = document.querySelector('.ka-caleran');
            if (dateInput) {
                const resetBtn = dateInput.parentElement.querySelector('.reset-date-filter');
                const observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'attributes' && mutation.attributeName === 'value') {
                            resetBtn.style.display = dateInput.value ? 'block' : 'none';
                        }
                    });
                });
                observer.observe(dateInput, { attributes: true });
            }
        /* } */

        // Funksjon for å oppdatere filtre og URL
        function updateFilters() {
            const filters = {};
            
            // Samle alle aktive filtre
            $('.mobile-filter-content .filter-chip.active').each(function() {
                const filterKey = $(this).data('url-key');
                const filterValue = $(this).data('filter');
                if (!filters[filterKey]) {
                    filters[filterKey] = [];
                }
                if (!filters[filterKey].includes(filterValue)) {
                    filters[filterKey].push(filterValue);
                }
            });

            $('.mobile-filter-content .filter-checkbox:checked').each(function() {
                const filterKey = $(this).data('url-key');
                const filterValue = $(this).val();
                if (!filters[filterKey]) {
                    filters[filterKey] = [];
                }
                if (!filters[filterKey].includes(filterValue)) {
                    filters[filterKey].push(filterValue);
                }
            });

            // Håndter dato-filter
            const dateRange = $('.mobile-filter-content .caleran').val();
            if (dateRange) {
                filters['dato'] = dateRange;
            }

            // Håndter søk
            const searchTerm = $('.mobile-filter-content .filter-search').val();
            if (searchTerm) {
                filters['sok'] = searchTerm;
            }

            // Behold eksisterende per_page parameter hvis den finnes
            const urlParams = new URLSearchParams(window.location.search);
            const perPage = urlParams.get('per_page');
            if (perPage) {
                filters['per_page'] = perPage;
            }

            // Bygg URL med riktig encoding
            const searchParams = new URLSearchParams();
            
            // Legg til alle filtre (URLSearchParams håndterer encoding)
            Object.entries(filters).forEach(([key, value]) => {
                if (Array.isArray(value)) {
                    searchParams.set(key, value.join(','));
                } else {
                    searchParams.set(key, value);
                }
            });

            // Fjern per_page fra aktive filtre før vi oppdaterer URL
            $('#active-filters .filter-tag[data-param="per_page"]').remove();
            
            // Debug logging
            if (DEBUG) {
                
                
            }
            
            // Bruk searchParams.toString() for å få riktig URL-encoding
            window.location.href = `${window.location.pathname}?${searchParams.toString()}`;
        }

        // Initialiser mobilfiltre ved lasting og ved vindustørrelse-endring
        let isMobile = window.innerWidth <= 768;
        
        function handleResize() {
            const wasMobile = isMobile;
            isMobile = window.innerWidth <= 768;
            
            if (isMobile) {
                filterToggleBtn.show();
            } else {
                filterToggleBtn.hide();
                hideMobileFilters();
            }
        }

        // Kjør ved oppstart
        handleResize();
        
        // Lytt på vindustørrelse-endringer
        $(window).on('resize', handleResize);

        // Hierarkisk kategori: generer toggles og håndter expand/collapse
        function initHierarchyToggles(context) {
            const $ctx = context ? $(context) : $(document);
            if ($ctx.data('kaHierarchyInit')) { return; }
            
            // Legg til toggle-ikon på alle kategorier som har barn (ka-parent klasse)
            $ctx.find('.filter-category.ka-parent').each(function() {
                const $item = $(this);
                if (!$item.data('toggle-initialized')) {
                    const label = $item.find('label.filter-list-item');
                    const toggle = $('<span class="ka-toggle" aria-hidden="true"><i class="ka-icon icon-chevron-right"></i></span>');
                    toggle.insertAfter(label);
                    $item.addClass('toggle-parent');
                    $item.data('toggle-initialized', true);
                }
            });
            // Grupper barn under en container pr forelder
            // Tell antall SYNLIGE hovedkategorier (ekskluder skjulte med .filter-empty)
            const allMainCategories = $ctx.find('.filter-category').filter(function() {
                const $this = $(this);
                // Må være hovedkategori (ikke barn) og synlig (ikke filter-empty eller display:none)
                return !$this.hasClass('ka-child') && 
                       !$this.hasClass('filter-empty') && 
                       $this.css('display') !== 'none';
            });
            const mainCategoryCount = allMainCategories.length;
            
            // Tell antall SYNLIGE hovedkategorier som har barn
            const mainCategoriesWithChildren = $ctx.find('.filter-category.ka-parent').filter(function() {
                const $this = $(this);
                return !$this.hasClass('ka-child') && 
                       !$this.hasClass('filter-empty') && 
                       $this.css('display') !== 'none';
            }).length;
            
            const shouldAutoExpand = mainCategoryCount === 1 || mainCategoriesWithChildren === 1;
            
            // Finn alle foreldre og grupper deres barn
            $ctx.find('.filter-category.ka-parent').each(function() {
                const $parent = $(this);
                const parentId = $parent.data('term-id');
                
                if (!$parent.data('children-wrapped') && parentId) {
                    // Finn alle barn som tilhører denne forelderen
                    const $children = $ctx.find('.filter-category.ka-child[data-parent-id="' + parentId + '"]').detach();
                    
                    if ($children.length) {
                        const $wrap = $('<div class="ka-children"></div>');
                        $children.appendTo($wrap);
                        $wrap.insertAfter($parent);
                        $parent.data('children-wrapped', true);
                        
                        // Sjekk om forelderen eller noen av barna er avkrysset
                        const parentChecked = $parent.find('input.filter-checkbox').is(':checked');
                        const anyChildChecked = $children.find('input.filter-checkbox:checked').length > 0;
                        
                        // Sjekk om dette er en hovedkategori (har ka-parent men ikke ka-child)
                        const isMainCategory = !$parent.hasClass('ka-child');
                        
                        // Åpne hvis:
                        // 1. Forelderen eller noen av barna er avkrysset
                        // 2. Det kun finnes 1 synlig hovedkategori og dette er den
                        if (parentChecked || anyChildChecked || (shouldAutoExpand && isMainCategory)) {
                            $wrap.addClass('open').show();
                            $parent.find('.ka-toggle').addClass('expanded');
                        }
                    }
                }
            });
            $ctx.data('kaHierarchyInit', true);
        }

        function bindHierarchyEvents() {
            function toggleNode(iconTarget) {
                const $iconWrap = $(iconTarget).closest('.ka-toggle');
                const $parent = $iconWrap.closest('.filter-category');
                const $children = $parent.next('.ka-children');
                if (!$children.length) return;
                const isOpen = $children.hasClass('open');
                if (isOpen) {
                    $children.removeClass('open').slideUp(150);
                    $iconWrap.removeClass('expanded');
                } else {
                    $children.addClass('open').slideDown(150);
                    $iconWrap.addClass('expanded');
                }
            }

            // Fjern tidligere handlers
            $(document).off('click.kaToggleLeft pointerdown.kaToggleTop');

            // Venstre kolonne og mobilfilter: bruk click
            $(document).on('click.kaToggleLeft', '.ka-toggle, .ka-toggle *', function(e){
                // Unngå å reagere inne i dropdown, den håndteres under
                if ($(this).closest('.filter-dropdown-content').length) return;
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                toggleNode(this);
            });

            // Topp-filter (dropdown): bruk pointerdown for å slå dropdown-close race
            $(document).on('pointerdown.kaToggleTop', '.filter-dropdown-content .ka-toggle, .filter-dropdown-content .ka-toggle *', function(e){
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                toggleNode(this);
            });
            // Når hovedkategori checkes: åpne barn. Når unchecked: behold åpne hvis noen barn er checket
            $(document).on('change', '.filter-category.toggle-parent input.filter-checkbox', function() {
                const $parent = $(this).closest('.filter-category');
                const $children = $parent.next('.ka-children');
                if (!$children.length) return;
                if (this.checked) {
                    if (!$children.hasClass('open')) {
                        $children.addClass('open').slideDown(150);
                        $parent.find('.ka-toggle').addClass('expanded');
                    }
                } else {
                    const anyChecked = $children.find('input.filter-checkbox:checked').length > 0;
                    if (!anyChecked) {
                        $children.removeClass('open').slideUp(150);
                        $parent.find('.ka-toggle').removeClass('expanded');
                    }
                }
            });
        }

        // Init ved load
        initHierarchyToggles();
        bindHierarchyEvents();

        // Sørg for at hierarki initieres også når dropdown åpnes i top-filteret
        $(document).on('click', '.filter-dropdown-toggle', function() {
            const $dropdown = $(this).closest('.filter-dropdown');
            const $content = $dropdown.find('.filter-dropdown-content');
            // init kun når content blir åpnet (forhindrer duplisering og treghet)
            if (!$content.data('kaHierarchyInit')) {
                initHierarchyToggles($content);
            }
        });
    });
   
    </script>
    <style>
        /* Hierarkisk filter UI */
        #filter-list-categories .filter-category { position: relative; display: flex; align-items: center; }
        #filter-list-categories .filter-category.toggle-parent { display: flex; align-items: center; }
        /* Skjul barn-kategorier som standard før JavaScript har gruppert dem */
        #filter-list-categories .filter-category.ka-child { display: none; }
        /* Vis barn-kategorier når de er inne i ka-children wrapperen */
        #filter-list-categories .ka-children .filter-category.ka-child { display: flex; }
        #filter-list-categories .filter-list-item { position: relative; padding-right: 0; z-index: 1; }
        #filter-list-categories .ka-toggle { display: inline-flex; align-items: center; justify-content: center; width: 14px; height: 14px; cursor: pointer; color: #666; position: relative; z-index: 2; pointer-events: auto; margin-left: 10px; }
        #filter-list-categories .ka-toggle i { transition: transform .2s ease; }
        #filter-list-categories .ka-toggle i.ka-icon { background-color: #9e9e9e; }
        #filter-list-categories .ka-toggle.expanded i { transform: rotate(90deg); }
        #filter-list-categories .ka-children { display: none; margin-left: 8px; margin-bottom: 8px; border-left: 1px solid #75757517; padding: 3px 8px 0 8px; }
        #filter-list-categories .ka-children.open { display: block; }
        #filter-list-categories .filter-list .filter-category.child-of { opacity: 1; }
        .kag .mobile-filter-overlay .filter-list { font-size: 17px; }
        .kag .mobile-filter-overlay .filter-list-item .checkbox-label { margin-top: -4px; }
        #ka .filter-dropdown-content .ka-toggle { margin-bottom: 5px;}
        
        /* Mobilfilter hierarkisk støtte */
        .mobile-filter-content .filter-category { position: relative; display: flex; align-items: center; }
        .mobile-filter-content .filter-category.toggle-parent { display: flex; align-items: center; }
        /* Skjul barn-kategorier som standard før JavaScript har gruppert dem */
        .mobile-filter-content .filter-category.ka-child { display: none; }
        /* Vis barn-kategorier når de er inne i ka-children wrapperen */
        .mobile-filter-content .ka-children .filter-category.ka-child { display: flex; }
        .mobile-filter-content .filter-list-item { position: relative; padding-right: 0; z-index: 1; }
        .mobile-filter-content .ka-toggle { display: inline-flex; align-items: center; justify-content: center; width: 14px; height: 14px; cursor: pointer; color: #666; position: relative; z-index: 2; pointer-events: auto; margin-left: 10px; }
        .mobile-filter-content .ka-toggle i { transition: transform .2s ease; }
        .mobile-filter-content .ka-toggle i.ka-icon { background-color: #9e9e9e; }
        .mobile-filter-content .ka-toggle.expanded i { transform: rotate(90deg); }
        .mobile-filter-content .ka-children { display: none; margin-left: 8px; margin-bottom: 8px; border-left: 1px solid #75757517; padding: 3px 8px 0 8px; }
        .mobile-filter-content .ka-children.open { display: block; }
        .mobile-filter-content .filter-list .filter-category.child-of { opacity: 1; }

      

        /* Styling for shortcode filter information */
        .shortcode-filters-info {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .shortcode-active-filters h4 {
            margin: 0 0 15px 0;
            color: #495057;
            font-size: 18px;
        }
        
        .shortcode-active-filters ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .shortcode-active-filters li {
            display: inline-block;
            background-color: #007cba;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            margin: 0 10px 10px 0;
            font-size: 14px;
        }
        
        .shortcode-active-filters li strong {
            font-weight: 600;
        }

        /*.filter-list-item {
            display: block;
            position: relative;
            transition: padding-left 0.2s ease;
        }
        
        .filter-list-item .checkbox-label {
            display: inline-block;
            margin-left: 5px;
        }
        

        
        .filter-list-item.has-parent::before {
            content: '';
            position: absolute;
            left: 8px;
            top: 50%;
            width: 8px;
            height: 1px;
            background-color: #ccc;
        }
        
        .filter-list-item .filter-checkbox {
            margin-right: 5px;
        }*/
        
        /* Stil for mobil visning */
        .mobile-filter-content .filter-list-item {
            padding: 8px 0;
        }
        
        .mobile-filter-content .filter-list-item .checkbox-label {
            font-size: 16px;
        }

        /* Stil for desktop visning */
        .filter-dropdown-content .filter-list-item.has-parent {
            font-size: 0.95em;
            color: #666;
        }

        /* Visuell indikator for filtervalg - ingen tellere */
        .filter-available {
            opacity: 1;
            pointer-events: auto;
        }

        .filter-empty {
            opacity: 0.4;
            pointer-events: auto; /* Tillat hover for ka-tooltip */
            position: relative;
        }

        .filter-empty .filter-checkbox {
            cursor: not-allowed;
            opacity: 0.5;
        }

        .filter-empty .checkbox-label {
            color: #999;
        }

        /* Visuell indikator med farger */
        .filter-available .checkbox-label {
            color: #333;
        }

        .filter-empty .checkbox-label {
            color: #999;
        }



        .filter-empty:hover::after {
            opacity: 1;
        }
        
        /* Mobilfilter tomme filtre støtte */
        .mobile-filter-content .filter-available {
            opacity: 1;
            pointer-events: auto;
        }

        .mobile-filter-content .filter-empty {
            opacity: 0.4;
            pointer-events: auto; /* Tillat hover for ka-tooltip */
            position: relative;
        }

        .mobile-filter-content .filter-empty .filter-checkbox {
            cursor: not-allowed;
            opacity: 0.5;
        }

        .mobile-filter-content .filter-empty .checkbox-label {
            color: #999;
        }

        .mobile-filter-content .filter-available .checkbox-label {
            color: #333;
        }

        /* Tooltip for tomme filtervalg i mobilfilteret */
        .mobile-filter-content .filter-empty::after {
            content: "Ingen kurs tilgjengelige med valgte filtre. Nullstill filtre hvis du står fast.";
            position: absolute;
            left: 0px;
            top: 50%;
            transform: translateY(-50%);
            background: #333;
            color: white;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 12px;
            white-space: normal;
            z-index: 1000;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s;
            margin-left: 50px;
            min-width: 200px; /* Sikre minimum bredde */
            max-width: 250px; /* Begrens maksimum bredde */
        }

        .mobile-filter-content .filter-empty:hover::after {
            opacity: 1;
        }




        /* Hide on desktop */
        @media (min-width: 769px) {
            .sticky-filter-button {
                display: none;
            }
        }
    </style>
    <?php

    // Return the buffered content
    return ob_get_clean();
}
add_shortcode('kursliste', 'kursagenten_course_list_shortcode'); 

function display_category_hierarchy($parent_id = 0, $depth = 0, $selected_categories = []) {
    // Hent kategorier for denne parent
    $args = [
        'taxonomy' => 'ka_coursecategory',
        'hide_empty' => true,
        'orderby' => 'menu_order',
        'order' => 'ASC',
        'parent' => $parent_id,
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
    ];

    
    $categories = get_terms($args);

    if (is_wp_error($categories)) {
        return;
    }


    if (!empty($categories)) {
        foreach ($categories as $category) {
            
            // Sjekk om kategorien har synlige kurs
            $has_visible_courses = false;
            $courses = get_posts([
                'post_type' => 'ka_course',
                'posts_per_page' => -1,
                'post_status' => 'publish',
                'tax_query' => [
                    [
                        'taxonomy' => 'ka_coursecategory',
                        'field' => 'term_id',
                        'terms' => $category->term_id
                    ]
                ],
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


            if (!empty($courses)) {
                $has_visible_courses = true;
            }

            // Sjekk om kategorien har synlige underkategorier
            $has_visible_children = false;
            $child_categories = get_terms([
                'taxonomy' => 'ka_coursecategory',
                'hide_empty' => true,
                'parent' => $category->term_id,
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


            if (!empty($child_categories) && !is_wp_error($child_categories)) {
                foreach ($child_categories as $child) {
                        $child_courses = get_posts([
                        'post_type' => 'ka_course',
                        'posts_per_page' => -1,
                        'post_status' => 'publish',
                        'tax_query' => [
                            [
                                'taxonomy' => 'ka_coursecategory',
                                'field' => 'term_id',
                                'terms' => $child->term_id
                            ]
                        ],
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

                    if (!empty($child_courses)) {
                        $has_visible_children = true;
                        break;
                    }
                }
            }

            // Vis kategorien hvis den har synlige kurs eller underkategorier
            if ($has_visible_courses || $has_visible_children) {
                $is_checked = in_array($category->term_id, $selected_categories) ? 'checked' : '';
                
                // Bruk parent-informasjonen fra term-objektet
                $parent_class = isset($category->parent_class) ? $category->parent_class : '';
                $parent_id_attr = isset($category->parent_id) ? ' data-parent-id="' . esc_attr($category->parent_id) . '"' : '';
                
                
                echo '<div class="filter-category' . ($parent_class ? ' ' . $parent_class : '') . '"' . $parent_id_attr . '>';
                echo '<label class="filter-list-item checkbox">';
                echo '<input type="checkbox" 
                    class="filter-checkbox"
                    value="' . esc_attr($category->slug) . '" 
                    data-filter-key="categories" 
                    data-url-key="k" 
                    ' . $is_checked . '>';
                echo '<span class="checkbox-label">' . esc_html($category->name) . '</span>';
                echo '</label>';
                echo '</div>';

                // Rekursivt kall for underkategorier
                display_category_hierarchy($category->term_id, $depth + 1, $selected_categories);
            } else {
            }
        }
    }

} 

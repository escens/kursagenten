<?php
/**
 * Course List Shortcode
 * 
 * Displays a list of courses with filters and pagination
 * 
 * @package Kursagenten
 * @subpackage Shortcodes
 */

if (!defined('ABSPATH')) exit;

/**
 * Register the [kursliste] shortcode
 */
function kursagenten_course_list_shortcode($atts) {
    // Sjekk logging-status

    // Load required dependencies
    if (!function_exists('get_course_languages')) {
        require_once dirname(dirname(__FILE__)) . '/templates/includes/queries.php';
    }

    // Include AJAX filter functionality
    require_once dirname(dirname(__FILE__)) . '/templates/includes/course-ajax-filter.php';

    // Hent valgt listetype fra innstillinger
    $list_type = get_option('kursagenten_archive_list_type', 'standard');

    // Last inn riktig CSS-fil basert på listetype
    if ($list_type === 'grid') {
        wp_enqueue_style('kursagenten-list-grid', KURSAG_PLUGIN_URL . '/assets/css/public/list-grid.css', array(), KURSAG_VERSION);
    } else {
        wp_enqueue_style('kursagenten-list-standard', KURSAG_PLUGIN_URL . '/assets/css/public/list-standard.css', array(), KURSAG_VERSION);
    }

    // Enqueue required styles
    wp_enqueue_style('kursagenten-course-style', KURSAG_PLUGIN_URL . '/assets/css/public/frontend-course-style.css', array(), KURSAG_VERSION);
    wp_enqueue_style('kursagenten-datepicker-style', KURSAG_PLUGIN_URL . '/assets/css/public/datepicker-caleran.min.css', array(), KURSAG_VERSION);

    // Enqueue required scripts
    wp_enqueue_script('kursagenten-iframe-resizer', 'https://embed.kursagenten.no/js/iframe-resizer/iframeResizer.min.js', array(), null, true);
    wp_enqueue_script('kursagenten-slidein-panel', KURSAG_PLUGIN_URL . '/assets/js/public/course-slidein-panel.js', array('jquery', 'kursagenten-iframe-resizer'), KURSAG_VERSION, true);
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
    wp_enqueue_script('kursagenten-datepicker-moment', KURSAG_PLUGIN_URL . '/assets/js/public/datepicker/moment.min.js', array(), KURSAG_VERSION);
    wp_enqueue_script('kursagenten-datepicker-script', KURSAG_PLUGIN_URL . '/assets/js/public/datepicker/caleran.min.js', ['kursagenten-datepicker-moment'], KURSAG_VERSION);
    wp_enqueue_script('kursagenten-accordion_script', KURSAG_PLUGIN_URL . '/assets/js/public/course-accordion.js', array(), KURSAG_VERSION);
    wp_enqueue_script('kursagenten-expand-content', KURSAG_PLUGIN_URL . '/assets/js/public/course-expand-content.js', array(), KURSAG_VERSION);

    // Localize script with necessary data
    wp_localize_script(
        'kursagenten-ajax-filter',
        'kurskalender_data',
        array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'filter_nonce' => wp_create_nonce('filter_nonce')
        )
    );

    // Initialize main course query and filter settings
    $query = get_course_dates_query();

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

    $taxonomy_data = [
        'categories' => [
            'taxonomy' => 'coursecategory',
            'terms' => get_filtered_terms('coursecategory'),
            'url_key' => 'k',
            'filter_key' => 'categories',
        ],
        'locations' => [
            'taxonomy' => 'course_location',
            'terms' => get_filtered_terms('course_location'),
            'url_key' => 'sted',
            'filter_key' => 'locations',
        ],
        'instructors' => [
            'taxonomy' => 'instructors',
            'terms' => get_filtered_terms('instructors'),
            'url_key' => 'i',
            'filter_key' => 'instructors',
        ],
        'language' => [
            'taxonomy' => '',
            'terms' => get_filtered_languages(),
            'url_key' => 'sprak',
            'filter_key' => 'language',
        ],
        'months' => [
            'taxonomy' => '',
            'terms' => get_filtered_months(),
            'url_key' => 'mnd',
            'filter_key' => 'months',
        ]
    ];


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
    ?>
    <div id="ka" class="kursagenten-wrapper">
    <main id="ka-m" class="kursagenten-main" role="main">
        <div class="ka-container">
            <!-- Mobile Filter Button & Overlay -->
            <button class="filter-toggle-button">
                <i class="ka-icon icon-filter"></i>
                <span>Filter</span>
            </button>

            <div class="mobile-filter-overlay">
                <div class="mobile-filter-header">
                    <h4>Filter</h4>
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
                                                        // For locations, bruk term-navn (med diakritikk) som filterverdi, ikke slug
                                                        $chip_value = '';
                                                        if ($taxonomy_data[$filter]['filter_key'] === 'locations' && is_object($term)) {
                                                            $chip_value = $term->name;
                                                        } else {
                                                            $chip_value = is_object($term) ? $term->slug : (is_string($term) ? strtolower($term) : '');
                                                        }
                                                        ?>
                                                        <button class="chip filter-chip"
                                                            data-filter-key="<?php echo esc_attr($taxonomy_data[$filter]['filter_key']); ?>"
                                                            data-url-key="<?php echo esc_attr($taxonomy_data[$filter]['url_key']); ?>"
                                                            data-filter="<?php echo esc_attr($chip_value); ?>">
                                                            <?php echo esc_html(is_object($term) ? $term->name : (is_string($term) ? ucfirst($term) : '')); ?>
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
                                                                }
                                                            } else {
                                                                // For andre filtre, vis en enkel liste
                                                                foreach ($taxonomy_data[$filter]['terms'] as $term) {
                                                                    // For locations-listen, bruk term-navn (med æ/ø/å) som URL-verdi
                                                                    if ($taxonomy_data[$filter]['filter_key'] === 'locations' && is_object($term)) {
                                                                        $term_value = $term->name;
                                                                        $term_name = $term->name;
                                                                    } else {
                                                                        $term_value = is_object($term) ? $term->slug : (is_string($term) ? strtolower($term) : '');
                                                                        $term_name = is_object($term) ? $term->name : (is_string($term) ? ucfirst($term) : '');
                                                                    }
                                                                    $is_checked = in_array($term_value, $active_filters) ? 'checked' : '';
                                                                    ?>
                                                                    <label class="filter-list-item checkbox">
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
                                                                <button class="chip filter-chip"
                                                                    data-filter-key="<?php echo esc_attr($taxonomy_data[$filter]['filter_key']); ?>"
                                                                    data-url-key="<?php echo esc_attr($taxonomy_data[$filter]['url_key']); ?>"
                                                                    data-filter="<?php echo esc_attr(is_object($term) ? $term->slug : (is_string($term) ? strtolower($term) : '')); ?>">
                                                                    <?php echo esc_html(is_object($term) ? $term->name : (is_string($term) ? ucfirst($term) : '')); ?>
                                                                </button>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php elseif ($filter_types[$filter] === 'list') : ?>
                                                        <?php 
                                                        if ($filter === 'categories') {
                                                        }
                                                        ?>
                                                        <div id="filter-list-location" class="filter-list expand-content" data-size="130">
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
                                                                    
                                                                }
                                                                $url_key = $taxonomy_data[$filter]['url_key'];
                                                                ?>
                                                                <div class="filter-category<?php echo $parent_class ? ' ' . esc_attr($parent_class) : ''; ?>"<?php echo $parent_id_attr; ?>>
                                                                <label class="filter-list-item checkbox">
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
                                <?php if ($query instanceof WP_Query && $query->have_posts()) : ?>
                                    <div class="courselist-header">
                                        <div id="courselist-header-left">
                                            <div id="course-count"><?php echo $query->found_posts; ?> kurs <?php echo $query->max_num_pages > 1 ? sprintf("- side %d av %d", $query->get('paged'), $query->max_num_pages) : ''; ?></div>                              
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

                                    <div class="courselist-items" id="filter-results">
                                        <?php
                                        $args = [
                                            'course_count' => $query->found_posts,
                                            'query' => $query
                                        ];

                                        

                                        while ($query->have_posts()) : $query->the_post();
                                            get_course_template_part($args);
                                        endwhile;
                                        ?>
                                    </div>

                                    <div class="pagination-wrapper">
                                        <div class="pagination">
                                        <?php
                                        // Hent gjeldende side URL som base for paginering
                                        $current_url = get_permalink();
                                        if (!$current_url) {
                                            $current_url = home_url('/');
                                        }

                                        // Fjern eventuelle eksisterende side-parametre fra URL-en
                                        $current_url = remove_query_arg('side', $current_url);

                                        echo paginate_links([
                                            'base' => $current_url . '%_%',
                                            'current' => max(1, $query->get('paged')),
                                            'format' => '?side=%#%',
                                            'total' => $query->max_num_pages,
                                            'prev_text' => '<i class="ka-icon icon-chevron-left"></i> <span>Forrige</span>',
                                            'next_text' => '<span>Neste</span> <i class="ka-icon icon-chevron-right"></i>',
                                            'add_args' => array_map(function ($item) {
                                                return is_array($item) ? join(',', $item) : $item;
                                            }, array_diff_key($_REQUEST, ['side' => true, 'action' => true, 'nonce' => true]))
                                        ]);
                                        ?>
                                        </div>
                                    </div>

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
    <div id="slidein-overlay"></div>
    <div id="slidein-panel">
        <button class="close-btn" aria-label="Close">&times;</button>
        <iframe id="kursagenten-iframe" src=""></iframe>
    </div>
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
            currentUrl.searchParams.set('per_page', perPage);
            
            // Fjern per_page fra aktive filtre før vi oppdaterer URL
            $('#active-filters .filter-tag[data-param="per_page"]').remove();
            
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
        const DEBUG = true;
        function log(...args) {
            if (DEBUG) console.log('[KA Mobile Filter]:', ...args);
        }

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
            log('Initialiserer datepicker');
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
                console.log('Active filters from URL:', activeFilters);
            }
            
            return activeFilters;
        }

        // Funksjon for å gjenopprette aktive filtre
        function restoreActiveFilters() {
            const activeFilters = getActiveFiltersFromUrl();
            
            // Debug logging
            if (DEBUG) {
                console.log('Restoring filters from URL:', activeFilters);
            }
            
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
            
            // Gjenopprett checkboxes
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
                console.log('Active filters restored');
            }
        }
        
        if (filterToggleBtn.length) {
            filterToggleBtn.on('click', function() {
                log('Filter-knapp klikket');
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
                log('Lukk-knapp klikket');
                hideMobileFilters();
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
            
            log('Laster mobilfiltre via AJAX');
            
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
                    nonce: kurskalender_data.filter_nonce
                },
                success: function(response) {
                    log('Mottok AJAX-respons:', response);
                    
                    if (response.success && response.data && response.data.html) {
                        log('Mobilfiltre lastet');
                        $('.mobile-filter-loading').remove();
                        mobileOverlay.append(response.data.html);
                        mobileFiltersLoaded = true;
                        initializeMobileFilterEvents();
                        // Gjenopprett aktive filtre etter at innholdet er lastet
                        restoreActiveFilters();
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

        // Funksjon for å initialisere event listeners på mobilfiltre
        function initializeMobileFilterEvents() {
            // Event listener for checkboxes
            $('.mobile-filter-content .filter-checkbox').on('change', function() {
                // Checkbox handling håndteres automatisk av browseren
            });

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

            // Event listener for søk
            $('.mobile-filter-content .filter-search').on('input', function() {
                // Søk håndteres når man klikker "Vis resultater"
            });

            // Event listener for "Vis resultater"
            $('.mobile-filter-content .apply-filters-button').on('click', function() {
                log('Vis resultater klikket');
                updateFilters();
                hideMobileFilters();
            });

            // Event listener for "Nullstill filter"
            $('.mobile-filter-content .reset-filters-button').on('click', function() {
                log('Reset klikket');
                $('.mobile-filter-content .filter-checkbox').prop('checked', false);
                $('.mobile-filter-content .caleran').val('').trigger('change');
                $('.mobile-filter-content .filter-search').val('');
                updateFilters();
            });

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
        }

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
            
            // Legg til alle filtre med riktig encoding
            Object.entries(filters).forEach(([key, value]) => {
                if (Array.isArray(value)) {
                    // For array-verdier, bruk encodeURIComponent på hver verdi
                    searchParams.set(key, value.map(v => encodeURIComponent(v)).join(','));
                } else {
                    // For enkle verdier, bruk encodeURIComponent direkte
                    searchParams.set(key, encodeURIComponent(value));
                }
            });

            // Fjern per_page fra aktive filtre før vi oppdaterer URL
            $('#active-filters .filter-tag[data-param="per_page"]').remove();
            
            // Debug logging
            if (DEBUG) {
                console.log('Filter values before update:', filters);
                console.log('Search params:', searchParams.toString());
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
    });
   
    </script>
    <style>
        .kag .mobile-filter-overlay .filter-list {
            font-size: 17px;
        }
        .kag .mobile-filter-overlay .filter-list-item .checkbox-label {
            margin-top: -4px;
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
    </style>
    <?php

    // Return the buffered content
    return ob_get_clean();
}
add_shortcode('kursliste', 'kursagenten_course_list_shortcode'); 

function display_category_hierarchy($parent_id = 0, $depth = 0, $selected_categories = []) {
    // Hent kategorier for denne parent
    $args = [
        'taxonomy' => 'coursecategory',
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
                'post_type' => 'course',
                'posts_per_page' => -1,
                'post_status' => 'publish',
                'tax_query' => [
                    [
                        'taxonomy' => 'coursecategory',
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
                'taxonomy' => 'coursecategory',
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
                        'post_type' => 'course',
                        'posts_per_page' => -1,
                        'post_status' => 'publish',
                        'tax_query' => [
                            [
                                'taxonomy' => 'coursecategory',
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
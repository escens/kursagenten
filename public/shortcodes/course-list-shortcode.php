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

    // Define taxonomy and meta field data structure for filters
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

    // Get language from meta fields for course dates
    $args = [
        'post_type'      => 'coursedate',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ];

    $coursedates = get_posts($args);
    $language_terms = [];

    foreach ($coursedates as $post_id) {
        $meta_language = get_post_meta($post_id, 'course_language', true);
        if (!empty($meta_language)) {
            $language_terms[] = $meta_language;
        }
    }
    $language_terms = array_unique($language_terms);
    $taxonomy_data['language']['terms'] = $language_terms;

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
    <main id="ka-main" class="kursagenten-main" role="main">
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
                                    <div class="filter-item <?php echo esc_attr($filter_types[$filter] ?? ''); ?> <?php echo esc_attr($search_class); ?>">
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
                                                        class="<?php echo esc_attr($caleran_class); ?>"
                                                        data-filter-key="date"
                                                        data-url-key="dato"
                                                        name="calendar-input"
                                                        placeholder="Velg fra-til dato"
                                                        value="<?php echo esc_attr($date); ?>"
                                                        aria-label="Velg datoer">
                                                <i class="ka-icon icon-chevron-down"></i>
                                            </div>
                                        <?php elseif (!empty($taxonomy_data[$filter]['terms'])) : ?>
                                            <?php if ($filter_types[$filter] === 'chips') : ?>
                                                <!-- Chip-style Filter Display -->
                                                <div class="filter-chip-wrapper">
                                                    <?php foreach ($taxonomy_data[$filter]['terms'] as $term) : ?>
                                                        <button class="chip filter-chip"
                                                            data-filter-key="<?php echo esc_attr($taxonomy_data[$filter]['filter_key']); ?>"
                                                            data-url-key="<?php echo esc_attr($taxonomy_data[$filter]['url_key']); ?>"
                                                            data-filter="<?php echo esc_attr(is_object($term) ? $term->slug : strtolower($term)); ?>">
                                                            <?php echo esc_html(is_object($term) ? $term->name : ucfirst($term)); ?>
                                                        </button>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php elseif ($filter_types[$filter] === 'list') : ?>
                                                <!-- List-style Filter Display -->
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
                                                                if ($filter === 'language') {
                                                                    $active_names[] = ucfirst($slug);
                                                                } else {
                                                                    foreach ($taxonomy_data[$filter]['terms'] as $term) {
                                                                        if (is_object($term) && ($filter === 'months' ? $term->value : $term->slug) === $slug) {
                                                                            $active_names[] = $term->name;
                                                                            break;
                                                                        }
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
                                                            <?php foreach ($taxonomy_data[$filter]['terms'] as $term) : ?>
                                                                <label class="filter-list-item checkbox">
                                                                    <?php 
                                                                    $term_value = is_object($term) ? ($filter === 'months' ? $term->value : $term->slug) : strtolower($term);
                                                                    $url_key = $taxonomy_data[$filter]['url_key'];
                                                                    ?>
                                                                    <input type="checkbox" class="filter-checkbox"
                                                                        value="<?php echo esc_attr($term_value); ?>"
                                                                        data-filter-key="<?php echo esc_attr($taxonomy_data[$filter]['filter_key']); ?>"
                                                                        data-url-key="<?php echo esc_attr($url_key); ?>">
                                                                    <span class="checkbox-label"><?php echo esc_html(is_object($term) ? $term->name : ucfirst($term)); ?></span>
                                                                </label>
                                                            <?php endforeach; ?>
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
                                                                class="<?php echo esc_attr($caleran_class); ?>"
                                                                data-filter-key="date"
                                                                data-url-key="dato"
                                                                name="calendar-input"
                                                                placeholder="Velg fra-til dato"
                                                                value="<?php echo esc_attr($date); ?>"
                                                                aria-label="Velg datoer">
                                                        <i class="ka-icon icon-chevron-down"></i>
                                                    </div>
                                                <?php elseif (!empty($taxonomy_data[$filter]['terms'])) : ?>
                                                    <?php if ($filter_types[$filter] === 'chips') : ?>
                                                        <div class="filter-chip-wrapper">
                                                            <?php foreach ($taxonomy_data[$filter]['terms'] as $term) : ?>
                                                                <button class="chip filter-chip"
                                                                    data-filter-key="<?php echo esc_attr($taxonomy_data[$filter]['filter_key']); ?>"
                                                                    data-url-key="<?php echo esc_attr($taxonomy_data[$filter]['url_key']); ?>"
                                                                    data-filter="<?php echo esc_attr(is_object($term) ? $term->slug : strtolower($term)); ?>">
                                                                    <?php echo esc_html(is_object($term) ? $term->name : ucfirst($term)); ?>
                                                                </button>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php elseif ($filter_types[$filter] === 'list') : ?>
                                                        <div id="filter-list-location" class="filter-list expand-content" data-size="100">
                                                            <?php foreach ($taxonomy_data[$filter]['terms'] as $term) : ?>
                                                                <label class="filter-list-item checkbox">

                                                                    <input type="checkbox" class="filter-checkbox"
                                                                        value="<?php echo esc_attr(is_object($term) ? ($filter === 'months' ? $term->value : $term->slug) : strtolower($term)); ?>"
                                                                        data-filter-key="<?php echo esc_attr($taxonomy_data[$filter]['filter_key']); ?>"
                                                                        data-url-key="<?php echo esc_attr($taxonomy_data[$filter]['url_key']); ?>">
                                                                    <span class="checkbox-label"><?php echo esc_html(is_object($term) ? $term->name : ucfirst($term)); ?></span>
                                                                </label>
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
                                            <div class="sort-dropdown">
                                                <div class="sort-dropdown-toggle">
                                                    <span class="selected-text">Sorter etter</span>
                                                    <span class="dropdown-icon"><i class="ka-icon icon-chevron-down"></i></span>
                                                </div>
                                                <div class="sort-dropdown-content">
                                                    <button class="sort-option" data-sort="title" data-order="asc">Fra A til Å</button>
                                                    <button class="sort-option" data-sort="title" data-order="desc">Fra Å til A</button>
                                                    <button class="sort-option" data-sort="price" data-order="asc">Pris lav til høy</button>
                                                    <button class="sort-option" data-sort="price" data-order="desc">Pris høy til lav</button>
                                                    <button class="sort-option" data-sort="date" data-order="asc">Tidligste dato</button>
                                                    <button class="sort-option" data-sort="date" data-order="desc">Seneste dato</button>
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
    /*
    jQuery(document).ready(function($) {
        // Debug logging
        const DEBUG = true;
        function log(...args) {
            if (DEBUG) console.log('[KA Mobile Filter]:', ...args);
        }

        // Sjekk om elementene eksisterer før vi legger til event listeners
        const filterToggleBtn = $('.filter-toggle-button');
        const mobileOverlay = $('.mobile-filter-overlay');
        const closeFilterBtn = $('.close-filter-button');
        
        if (filterToggleBtn.length) {
            filterToggleBtn.on('click', function() {
                log('Filter-knapp klikket');
                if (mobileOverlay.length) {
                    initializeMobileFilters();
                    mobileOverlay.css('display', 'flex');
                    $('body').css('overflow', 'hidden');
                }
            });
        }

        if (closeFilterBtn.length) {
            closeFilterBtn.on('click', function() {
                log('Lukk-knapp klikket');
                if (mobileOverlay.length) {
                    mobileOverlay.css('display', 'none');
                    $('body').css('overflow', 'auto');
                }
            });
        }

        // Funksjon for å flytte filtre til mobil container
        function initializeMobileFilters() {
            log('Initialiserer mobilfiltre');
            const mobileContent = $('.mobile-filter-content');
            if (!mobileContent.length) return;
            
            // Tøm mobil container først
            mobileContent.empty();

            // Samle alle filtre fra både topp og venstre
            const topFilters = $('.filter-container.filter-top .filter-item');
            const leftFilters = $('.left-filter-section .filter-item');
            
            // Legg til i mobil container
            if (topFilters.length || leftFilters.length) {
                // Flytt filtrene til mobil container istedenfor å klone dem
                const filters = topFilters.add(leftFilters).detach();
                
                filters.each(function() {
                    const section = $('<div>').addClass('mobile-filter-section');
                    section.append($(this));
                    mobileContent.append(section);
                });

                // Legg til footer med knapper
                const footer = $('<div>').addClass('mobile-filter-footer')
                    .append('<button class="apply-filters-button">Vis resultater</button>')
                    .append('<button class="reset-filters-button">Nullstill filter</button>');
                
                mobileContent.append(footer);
            }
        }

        // Initialiser mobilfiltre ved lasting og ved vindustørrelse-endring
        let isMobile = window.innerWidth <= 768;
        let originalFilterContainers = null;
        
        function handleResize() {
            const wasMobile = isMobile;
            isMobile = window.innerWidth <= 768;
            
            if (isMobile) {
                filterToggleBtn.show();
            } else {
                filterToggleBtn.hide();
                if (mobileOverlay.length) {
                    mobileOverlay.hide();
                    $('body').css('overflow', 'auto');
                    
                    // Flytt filtrene tilbake til original plassering
                    const mobileFilters = $('.mobile-filter-content .filter-item');
                    if (mobileFilters.length) {
                        const topFilters = mobileFilters.filter((i, el) => {
                            return $(el).find('[data-original-container="top"]').length;
                        });
                        const leftFilters = mobileFilters.filter((i, el) => {
                            return $(el).find('[data-original-container="left"]').length;
                        });
                        
                        $('.filter-container.filter-top').append(topFilters);
                        $('.left-filter-section').append(leftFilters);
                    }
                }
            }
        }

        // Kjør ved oppstart
        handleResize();
        
        // Lytt på vindustørrelse-endringer
        $(window).on('resize', handleResize);

        // Håndter filter-knapper på mobil
        $(document).on('click', '.mobile-filter-overlay .apply-filters-button', function() {
            log('Vis resultater klikket');
            if (mobileOverlay.length) {
                mobileOverlay.hide();
                $('body').css('overflow', 'auto');
            }
        });

        $(document).on('click', '.mobile-filter-overlay .reset-filters-button', function() {
            log('Reset klikket');
            // Trigger eksisterende reset-funksjonalitet
            $('#reset-filters').trigger('click');
        });

        // Merk filtre med original plassering
        $('.filter-container.filter-top .filter-item [data-filter-key]').attr('data-original-container', 'top');
        $('.left-filter-section .filter-item [data-filter-key]').attr('data-original-container', 'left');
    });
    */
    </script>
    <?php

    // Return the buffered content
    return ob_get_clean();
}
add_shortcode('kursliste', 'kursagenten_course_list_shortcode'); 
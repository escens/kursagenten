<?php
// Load required dependencies
if (!function_exists('get_course_languages')) {
    require_once dirname(dirname(__FILE__)) . '/templates/includes/queries.php';
}
get_header(); ?>
<?php
// Initialize main course query and filter settings
$query = get_course_dates_query();

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

// Rett etter $taxonomy_data definisjonen
error_log('Initial months data: ' . print_r($taxonomy_data['months']['terms'], true));

// **Meta fields
// Language
// Get language from meta fields for course dates
$args = [
    'post_type'      => 'coursedate',
    'posts_per_page' => -1,
    'fields'         => 'ids',
];

$coursedates = get_posts($args);
$language_terms = [];

// Get language from meta fields for course dates
foreach ($coursedates as $post_id) {
    $meta_language = get_post_meta($post_id, 'course_language', true);
    if (!empty($meta_language)) {
        $language_terms[] = $meta_language;
    }
}
$language_terms = array_unique($language_terms);
$taxonomy_data['language']['terms'] = $language_terms;

// Months
// Get months from course_first_date meta field

// Legg til månedene i taxonomy_data
//$taxonomy_data['month']['terms'] = $month_terms;

// **Bring terms and meta data together
// Prepare filter-information (place after $available_filters and $taxonomy_data are defined)
$filter_display_info = [];
foreach ($available_filters as $filter_key => $filter_info) {
    $filter_display_info[$filter_key] = [
        'label' => $filter_info['label'] ?? '',
        'placeholder' => $filter_info['placeholder'] ?? 'Velg',
        'filter_key' => $taxonomy_data[$filter_key]['filter_key'] ?? '',
        'url_key' => $taxonomy_data[$filter_key]['url_key'] ?? ''
    ];
}

// Rett før filter-genereringen
error_log('Available filters: ' . print_r($available_filters, true));
error_log('Top filters: ' . print_r($top_filters, true));
error_log('Left filters: ' . print_r($left_filters, true));
error_log('Filter types: ' . print_r($filter_types, true));
?>

<main id="main" class="site-main kursagenten-wrapper" role="main">
    <div class="course-container">
        <div class="heder">
            <h1><?php post_type_archive_title(); ?></h1>
        </div>
        <div class="course-meta">
            <h2><?php the_title(); ?></h2>
        </div>

        <div class="courselist">
            <div class="inner-container filter-section">
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
                                        </div><!-- End .filter-chip-wrapper -->
                                    <?php elseif ($filter_types[$filter] === 'list') : ?>
                                        <!-- List-style Filter Display -->
                                        <div id="filter-list-<?php echo esc_attr($taxonomy_data[$filter]['filter_key']); ?>" class="filter">
                                            <div class="filter-dropdown">
                                                <?php
                                                // Get filter information from prepared array
                                                $current_filter_info = $filter_display_info[$filter] ?? [];
                                                $filter_label = $current_filter_info['label'] ?? '';
                                                $filter_placeholder = $current_filter_info['placeholder'] ?? 'Velg';

                                                // Get active filters from URL parameters
                                                $url_key = $taxonomy_data[$filter]['url_key'];
                                                $active_filters = isset($_GET[$url_key]) ? explode(',', $_GET[$url_key]) : [];

                                                // Generate display text based on active filters
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
                                                <!-- Filter Dropdown Toggle -->
                                                <div class="filter-dropdown-toggle <?php echo esc_attr($has_active_filters); ?>"
                                                    data-filter="<?php echo esc_attr($filter); ?>"
                                                    data-label="<?php echo esc_attr($filter_label); ?>"
                                                    data-placeholder="<?php echo esc_attr($filter_placeholder); ?>">
                                                    <span class="selected-text"><?php echo esc_html($display_text); ?></span>
                                                    <span class="dropdown-icon"><i class="ka-icon icon-chevron-down"></i></span>
                                                </div>
                                                <!-- Filter Dropdown Content -->
                                                <div class="filter-dropdown-content">
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
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div> <!-- End .filter-container filter-top -->
                </div> <!-- End .course-grid -->    
            </div> <!-- End .filter-section (top filters)-->

            <!-- Main Content Container with Columns -->
            <div class="inner-container main-section">
                <div class="course-grid <?php echo esc_attr($left_column_class); ?>">
                    <!-- Left Column -->
                    <?php if ($has_left_filters) : ?>
                        <div class="filter left-column">
                            <div class="filter-container filter-left">
                                <?php foreach ($left_filters as $filter) : ?>
                                    <div class="filter-item">
                                        <?php
                                        // Hent filter info fra den forberedte arrayen
                                        $current_filter_info = $filter_display_info[$filter] ?? [];
                                        $filter_label = $current_filter_info['label'] ?? '';
                                        ?>
                                        <h5><?php echo $filter_label; ?></h5>
                                        <?php if ($filter === 'search') : ?>
                                            <input type="text" id="search" name="search" class="filter-search <?php echo esc_attr($search_class); ?>" placeholder="Søk etter kurs...">
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
                                                <div id="filter-list-location" class="filter-list">
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
                        </div>
                    <?php endif; ?>

                    <!-- Right Column -->
                    <div class="courselist-items-wrapper right-column">
                        <?php if ($query instanceof WP_Query && $query->have_posts()) : ?>
                            <?php

                            // SERGII - This should be changed to the actual number of courses. I want to display something like 22 courses - page 1 of 3
                            // Get total number of courses
                            $course_count = $query->found_posts;
                            $page_count = $query->post_count;
                            $index = 0;
                            ?>

                            <div class="courselist-header">
                                <!-- Course Count and Active Filters Display -->
                                <div id="courselist-header-left" class="active-filters-container">
                                    <div id="course-count"><?php echo $course_count; ?> kurs <?php echo $query->max_num_pages > 1 ? sprintf("- page %d / %d", $query->get('paged'), $query->max_num_pages) : ''; ?></div>
                                    <div id="active-filters" class="active-filters-container"></div>
                                    <a href="#" id="reset-filters" class="reset-filters reset-filters-btn">Nullstill filter</a>
                                </div>

                                <!-- Sorting Controls -->
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

                            <!-- Template part from /partials -->
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

                            <!-- Pagination Controls -->
                            <div class="pagination-wrapper">
                                <div class="pagination">
                                <?php
                                // Get URL rewrite options
                                $url_options = get_option('kag_seo_option_name');
                                $kurs = !empty($url_options['ka_url_rewrite_kurs']) ? $url_options['ka_url_rewrite_kurs'] : 'kurs';

                                // Generate pagination links
                                echo paginate_links([
                                    'base' => get_home_url(null, $kurs) .'?%_%',
                                    'current' => max(1, $query->get('paged')),
                                    'format' => 'side=%#%',
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

                            <!-- Loading Indicator -->
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

                    

                    <!-- Slide-in Panel for Additional Content -->
                    <div id="slidein-overlay"></div>
                    <div id="slidein-panel">
                        <button class="close-btn" aria-label="Close">&times;</button>
                        <iframe id="kursagenten-iframe" src=""></iframe>
                    </div>

                </div> <!-- End .course-grid -->
            </div> <!-- End .main section -->

            <div class="footer">
                <h4>Footer</h4>
            </div>

        </div><!-- End .courselist -->
    </div> <!-- End .course-container -->
</main>
<?php get_footer();?>

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

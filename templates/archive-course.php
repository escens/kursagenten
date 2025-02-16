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

// Check if left column is empty
$has_left_filters = !empty($left_filters);
$left_column_class = $has_left_filters ? '' : 'hidden-left-column';

// Check if search is the only filter on top
$is_search_only = is_array($top_filters) && count($top_filters) === 1 && in_array('search', $top_filters);
$search_class = $is_search_only ? 'wide-search' : '';

// Get data for taxonomies and meta fields
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

// Get language from meta fields for course dates
foreach ($coursedates as $post_id) {
    $meta_language = get_post_meta($post_id, 'course_language', true);
    if (!empty($meta_language)) {
        $language_terms[] = $meta_language;
    }
}
$language_terms = array_unique($language_terms);
$taxonomy_data['language']['terms'] = $language_terms;

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
            <!-- Topp-felt (full bredde) -->
            <div class="inner-container filter-section">
                <div class="filter-container filter-top">
                    <?php foreach ($top_filters as $filter) : ?>
                        <div class="filter-item">
                            <?php if ($filter === 'search') : ?>
                                <input type="text" id="search" name="search" class="filter-search <?php echo esc_attr($search_class); ?>" placeholder="Søk etter kurs">
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
                                    </div><!-- End .filter-chip-wrapper -->
                                <?php elseif ($filter_types[$filter] === 'list') : ?>
                                    <div id="filter-list-<?php echo esc_attr($taxonomy_data[$filter]['filter_key']); ?>" class="filter">
                                        <div class="filter-dropdown">
                                            <?php 
                                            $filter_info = $available_filters[$filter] ?? [];
                                            $filter_label = $filter_info['label'] ?? '';
                                            $filter_placeholder = $filter_info['placeholder'] ?? 'Velg';
                                            
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
                                                            if (is_object($term) && $term->slug === $slug) {
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
                                                        <input type="checkbox" class="filter-checkbox"
                                                            value="<?php echo esc_attr(is_object($term) ? $term->slug : strtolower($term)); ?>"
                                                            data-filter-key="<?php echo esc_attr($taxonomy_data[$filter]['filter_key']); ?>"
                                                            data-url-key="<?php echo esc_attr($taxonomy_data[$filter]['url_key']); ?>">
                                                        <span class="checkbox-label"><?php echo esc_html(is_object($term) ? $term->name : ucfirst($term)); ?></span>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div><
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Hoved-container med kolonner -->
            <div class="inner-container main-section">
                <div class="course-grid col-1-4">
                    <!-- Venstre kolonne -->
                    <div class="filter left-column">
                        <p>Venstre kolonne</p>
                        <?php if ($has_left_filters) : ?>
                            <div class="filter-container filter-left">
                                <p>Filter</p>
                                <?php foreach ($left_filters as $filter) : ?>
                                    <div class="filter-item">
                                        <?php if ($filter === 'search') : ?>
                                            <input type="text" id="search" name="search" class="filter-search <?php echo esc_attr($search_class); ?>" placeholder="Søk etter kurs">
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
                                                                value="<?php echo esc_attr(is_object($term) ? $term->slug : strtolower($term)); ?>"
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

                    <!-- Høyre kolonne -->
                    <div class="courselist-items-wrapper right-column">
                        <?php if ($query instanceof WP_Query && $query->have_posts()) : ?>
                            <?php
                            $course_count = $query->post_count; // Hent totalt antall kurs
                            $index = 0;
                            ?>

                            <div id="course-count"><?php echo $course_count; ?> kurs</div>
                            <div id="active-filters" class="active-filters-container"></div><a href="#" id="reset-filters" class="reset-filters reset-filters-btn">Nullstill filter</a>

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
                            <!-- Paginering -->
                            <div class="pagination">
                                <?php
                                echo paginate_links([
                                    'total'   => $query->max_num_pages,
                                    'current' => max(1, get_query_var('paged')),
                                ]);
                                ?>
                            </div>
                        <?php
                            wp_reset_postdata();
                        else :
                            echo '<p>Ingen kurs tilgjengelige.</p>';
                        endif;
                        ?>
                    </div>

                    <div class="footer">
                        <h4>Footer</h4>
                    </div>

                    <div id="slidein-overlay"></div>
                    <div id="slidein-panel">
                        <button class="close-btn" aria-label="Close">&times;</button>
                        <iframe id="kursagenten-iframe" src=""></iframe>
                        <script type="text/javascript" src="https://embed.kursagenten.no/js/iframe-resizer/iframeResizer.min.js"></script>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<?php get_footer();?>

<script id="filter-settings" type="application/json">
    <?php
    // Export filter settings for JavaScript usage
    $filter_data = [
        'top_filters' => get_option('kursagenten_top_filters', []),
        'left_filters' => get_option('kursagenten_left_filters', []),
        'filter_types' => get_option('kursagenten_filter_types', []),
        'available_filters' => get_option('kursagenten_available_filters', []),
    ];
    echo json_encode($filter_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);
    ?>
</script>

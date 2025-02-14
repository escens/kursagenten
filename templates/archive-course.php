<?php get_header(); ?>
<?php
$query = get_course_dates_query();

$top_filters = get_option('kursagenten_top_filters', []);
if (!is_array($top_filters)) {
    $top_filters = explode(',', $top_filters);
}
$left_filters = get_option('kursagenten_left_filters', []);
if (!is_array($left_filters)) {
    $left_filters = explode(',', $left_filters);
}
$filter_types = get_option('kursagenten_filter_types', []);

// Sjekk om venstre kolonne er tom
$has_left_filters = !empty($left_filters);
$left_column_class = $has_left_filters ? '' : 'hidden-left-column';

// Sjekk om søk er det eneste filteret på toppen
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
        'terms' => [], // Vil fylles dynamisk basert på metafelter
        'url_key' => 'sprak',
        'filter_key' => 'language',
    ]
];

// Hent språk fra metafeltene for kursdatoer
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
            <div class="inner-container">

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
                                </div>
                                <?php elseif ($filter_types[$filter] === 'list') : ?>
                                    <div id="filter-list-<?php echo esc_attr($taxonomy_data[$filter]['filter_key']); ?>" class="filter">
                                    <div class="filter-dropdown">
                                        <div class="filter-dropdown-toggle">
                                                <span>Velg kategori</span>
                                                <span class="dropdown-arrow dropdown-icon"><i class="ka-icon icon-chevron-down"></i></span>
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
                                        </div>
                                    </div>
                                    </div>
                                <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>



                <div class="course-grid col-1-4">


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
    $filter_data = [
        'top_filters' => get_option('kursagenten_top_filters', []),
        'left_filters' => get_option('kursagenten_left_filters', []),
        'filter_types' => get_option('kursagenten_filter_types', []),
    ];
    echo json_encode($filter_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);
    ?>
</script>

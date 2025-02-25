<?php get_header(); ?>
<?php
$query = get_course_dates_query();

$top_filters = get_option('kursagenten_top_filters', []);
if (!is_array($top_filters)) {
    $top_filters = explode(',', $top_filters);
}
$left_filters = get_option('kursagenten_left_filters', []);
$filter_types = get_option('kursagenten_filter_types', []);

// Sjekk om venstre kolonne er tom
$has_left_filters = !empty($left_filters);
$left_column_class = $has_left_filters ? '' : 'hidden-left-column';

// Sjekk om søk er det eneste filteret på toppen
$is_search_only = is_array($top_filters) && count($top_filters) === 1 && in_array('search', $top_filters);

$search_class = $is_search_only ? 'wide-search' : '';

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
                            <input type="text" class="filter-search <?php echo esc_attr($search_class); ?>" placeholder="Søk etter kurs">
                        <?php elseif (!empty($filter_types[$filter]) && $filter_types[$filter] === 'chips') : ?>
                            <div class="filter-chip-wrapper">
                                <button class="chip filter-chip" data-filter="<?php echo esc_attr($filter); ?>">
                                    <?php echo esc_html(ucfirst($filter)); ?>
                                </button>
                            </div>
                        <?php elseif (!empty($filter_types[$filter]) && $filter_types[$filter] === 'list') : ?>
                            <select class="filter-dropdown">
                                <option>Velg <?php echo esc_html($filter); ?></option>
                            </select>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>



                <div class="course-grid col-1-4">


                    <div class="filter left-column">
                        <?php
                        $filter_settings = get_option('design_option_name', []);

                        if (!empty($filter_settings['filter_search'])) {
                            $filter_search = true;
                            $filter_search_position  = isset($filter_settings['filter_search_place']) ? $filter_settings['filter_search_place'] : 'venstre';
                        }
                        if (!empty($filter_settings['filter_categories'])) {
                            $categories = get_terms(['taxonomy' => 'coursecategory', 'hide_empty' => true]);
                            if (!empty($categories) && !is_wp_error($categories)) {
                                $filter_categories = true;
                                $filter_categories_type = isset($filter_settings['filter_categories_type']) ? $filter_settings['filter_categories_type'] : 'chips';
                                $filter_categories_position  = isset($filter_settings['filter_categories_place']) ? $filter_settings['filter_categories_place'] : 'venstre';
                            } else {
                                $filter_categories = false;
                            }
                        }
                        if (!empty($filter_settings['filter_locations'])) {
                            $locations = get_terms(['taxonomy' => 'course_location', 'hide_empty' => true]);
                            if (!empty($locations) && !is_wp_error($locations)) {
                                $filter_locations = true;
                                $filter_locations_type = isset($filter_settings['filter_locations_type']) ? $filter_settings['filter_locations_type'] : 'chips';
                                $filter_locations_position = isset($filter_settings['filter_locations_place']) ? $filter_settings['filter_locations_place'] : 'venstre';
                            } else {
                                $filter_locations = false;
                            }
                        }
                        if (!empty($filter_settings['filter_instructors'])) {
                            $instructors = get_terms(['taxonomy' => 'instructors', 'hide_empty' => true]);
                            if (!empty($instructors) && !is_wp_error($instructors)) {
                                $filter_instructors = true;
                                $filter_instructors_type = isset($filter_settings['filter_instructors_type']) ? $filter_settings['filter_instructors_type'] : 'chips';
                                $filter_instructors_position = isset($filter_settings['filter_instructors_place']) ? $filter_settings['filter_instructors_place'] : 'venstre';
                            } else {
                                $filter_instructors = false;
                            }
                        }
                        if (!empty($filter_settings['filter_date'])) {
                            $filter_date = true;
                        }
                        if (!empty($filter_settings['filter_price'])) {
                            $filter_price = true;
                        }

                        $language = [];
                        $args = [
                            'post_type'      => 'coursedate',
                            'posts_per_page' => -1, // Hent alle kursdatoer
                            'fields'         => 'ids', // Hent kun post-ID-er
                        ];

                        $coursedates = get_posts($args);

                        foreach ($coursedates as $post_id) {
                            $meta_language = get_post_meta($post_id, 'course_language', true); // Hent språk fra hver kursdato
                            if (!empty($meta_language)) {
                                $language[] = $meta_language;
                            }
                        }

                        $language = array_unique($language); // Fjern duplikater

                        if (!empty($language)) {
                            $filter_language = true;
                            $filter_language_type = isset($filter_settings['filter_language_type']) ? $filter_settings['filter_language_type'] : 'chips';
                            $filter_language_position = isset($filter_settings['filter_language_place']) ? $filter_settings['filter_language_place'] : 'venstre';
                        } else {
                            $filter_language = false;
                        }
                        ?>
                        <script id="filter-settings" type="application/json">
                            <?php echo json_encode($filter_settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG); ?>
                        </script>

                        <!-- Searchfield -->
                        <?php if ($filter_search == true): ?>
                            <div id="filter-search" class="filter-<?php echo $filter_search_position; ?>">
                                <input type="text" id="search" name="search" placeholder="Søk etter kurs">
                            </div>
                        <?php endif; ?>

                        <!-- Coursecategory -->
                        <?php if ($filter_categories == true): ?>
                            <div id="filter-list-categories" class="filter-<?php echo $filter_categories_type; ?>">
                                <?php if ($filter_categories_type === "list"): ?>
                                    <div class="filter-dropdown">
                                        <div class="filter-dropdown-toggle">
                                            <span>Velg kategori</span>
                                            <span class="dropdown-arrow dropdown-icon"><i class="ka-icon icon-chevron-down"></i></span>
                                        </div>
                                        <div class="filter-dropdown-content">
                                            <?php foreach ($categories as $category) : ?>
                                                <label class="filter-list-item checkbox">
                                                    <input type="checkbox" class="filter-checkbox category-checkbox"
                                                        value="<?php echo esc_attr($category->slug); ?>"
                                                        data-filter-key="categories"
                                                        data-url-key="k"
                                                        data-filter="<?php echo esc_attr($category->slug); ?>">
                                                    <span class="checkbox-label"><?php echo esc_attr($category->name); ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <?php if ($filter_categories_type === "chips"): ?>
                                    <?php foreach ($categories as $category) : ?>
                                        <button class="chip filter-chip"
                                            data-filter-key="categories"
                                            data-url-key="k"
                                            data-filter="<?php echo esc_attr($category->slug); ?>">
                                            <?php echo esc_html($category->name); ?>
                                        </button>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Dates -- needs more work -->
                        <?php if ($filter_date == true): ?>
                            <div id="filter-date" class="filter-date-container">
                                <label for="date-range">Velg periode:</label>
                                <input type="text" id="date-range" class="caleran"
                                    data-filter-key="date"
                                    data-url-key="dato"
                                    name="calendar-input"
                                    value="">
                            </div>
                        <?php endif; ?>


                        <!-- Locations -->
                        <?php if ($filter_locations == true): ?>
                            <div id="filter-list-location" class="filter-<?php echo $filter_locations_type; ?>">
                                <?php if ($filter_locations_type === "list"): ?>
                                    <?php foreach ($locations as $location) : ?>
                                        <label class="filter-list-item checkbox">
                                            <input type="checkbox" class="filter-checkbox locations-checkbox"
                                                value="<?php echo esc_attr($location->slug); ?>"
                                                data-filter-key="locations"
                                                data-url-key="sted"
                                                data-filter="<?php echo esc_attr($location->slug); ?>">
                                            <span class="checkbox-label"><?php echo esc_attr($location->name); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <?php if ($filter_locations_type === "chips"): ?>
                                    <?php foreach ($locations as $location) : ?>
                                        <button class="chip filter-chip"
                                            data-filter-key="locations"
                                            data-url-key="sted"
                                            data-filter="<?php echo esc_attr($location->slug); ?>">
                                            <?php echo esc_html($location->name); ?>
                                        </button>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Instructors -->
                        <?php if ($filter_instructors == true): ?>
                            <div id="filter-list-instructors" class="filter-<?php echo $filter_instructors_type; ?>">
                                <?php if ($filter_instructors_type === "list"): ?>
                                    <?php foreach ($instructors as $instructor) : ?>
                                        <label class="filter-list-item checkbox">
                                            <input type="checkbox" class="filter-checkbox instructors-checkbox"
                                                value="<?php echo esc_attr($instructor->slug); ?>"
                                                data-filter-key="instructors"
                                                data-url-key="i"
                                                data-filter="<?php echo esc_attr($instructor->slug); ?>">
                                            <span class="checkbox-label"><?php echo esc_attr($instructor->name); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <?php if ($filter_instructors_type === "chips"): ?>
                                    <?php foreach ($instructors as $instructor) : ?>
                                        <button class="chip filter-chip"
                                            data-filter-key="instructors"
                                            data-url-key="i"
                                            data-filter="<?php echo esc_attr($instructor->slug); ?>">
                                            <?php echo esc_html($instructor->name); ?>
                                        </button>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Language -->
                        <?php if ($filter_language == true): ?>
                            <div id="filter-list-language" class="filter-<?php echo $filter_language_type; ?>">
                                <?php if ($filter_language_type === "list"): ?>
                                    <?php foreach ($language as $language_item) : ?>
                                        <label class="filter-list-item checkbox">
                                            <input type="checkbox" class="filter-checkbox language-checkbox"
                                                value="<?php echo esc_attr(strtolower($language_item)); ?>"
                                                data-filter-key="language"
                                                data-url-key="sprak"
                                                data-filter="<?php echo esc_attr(strtolower($language_item)); ?>">
                                            <span class="checkbox-label"><?php echo esc_attr($language_item); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <?php if ($filter_language_type === "chips"): ?>
                                    <?php foreach ($language as $language_item) : ?>
                                        <button class="chip filter-chip"
                                            data-filter-key="language"
                                            data-url-key="sprak"
                                            data-filter="<?php echo esc_attr(strtolower($language_item)); ?>">
                                            <?php echo esc_html($language_item); ?>
                                        </button>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>


                        <!-- Price -- needs more work-->
                        <?php if ($filter_price == true): ?>
                            <div id="filter-price" class="wrapper" data-filter-key="price">
                                <header>
                                    <h2>Prisfilter</h2>
                                    <p>Bruk slider eller skriv inn min og maks pris</p>
                                </header>
                                <div class="price-input">
                                    <div class="field">
                                        <span>Min</span>
                                        <input type="number" id="price-min" class="input-min" value="0" data-filter="price-min">
                                    </div>
                                    <div class="separator">-</div>
                                    <div class="field">
                                        <span>Maks</span>
                                        <input type="number" id="price-max" class="input-max" value="10000" data-filter="price-max">
                                    </div>
                                </div>
                                <div class="slider">
                                    <div class="progress"></div>
                                </div>
                                <div class="range-input">
                                    <input type="range" id="range-min" class="range-min" min="0" max="10000" value="0" step="100" data-filter="range-min">
                                    <input type="range" id="range-max" class="range-max" min="0" max="10000" value="10000" step="100" data-filter="range-max">
                                </div>
                            </div>

                            <!-- Aktivt filter -->
                            <div id="active-price-filter" class="active-filter-chip" data-filter-key="price"></div>
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

                                while ($query->have_posts()) : $query->the_post();

                                    include 'partials/coursedates_default.php';

                                    $index++; // Øk teller for hver iterasjon
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
<?php get_footer(); ?>
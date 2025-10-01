<?php
// Sjekk om template-functions.php er lastet
if (!function_exists('get_course_template_part')) {
    $template_functions_path = KURSAG_PLUGIN_DIR . 'public/templates/includes/template-functions.php';
    if (!file_exists($template_functions_path)) {
        error_log('ERROR: Could not find template functions file at: ' . $template_functions_path);
    } else {
        require_once $template_functions_path;
    }
}

// Funksjon for å hente filtrerte taksonomier med spesiell håndtering for coursecategory taksonomi-sider
function get_filtered_terms_for_context($taxonomy) {
    // Spesiell håndtering for coursecategory taksonomi-sider
    if ($taxonomy === 'coursecategory' && is_tax('coursecategory')) {
        $current_term = get_queried_object();
        if ($current_term && $current_term->taxonomy === 'coursecategory') {
            // Sjekk om vi er på en foreldrekategori (parent = 0)
            if ($current_term->parent == 0) {
                // Vi er på en foreldrekategori, vis kun barnekategoriene
                $child_categories = get_terms([
                    'taxonomy' => $taxonomy,
                    'hide_empty' => true,
                    'parent' => $current_term->term_id,
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
                
                if (!is_wp_error($child_categories) && !empty($child_categories)) {
                    // Legg til parent-informasjon på barnekategoriene
                    foreach ($child_categories as $child) {
                        $child->parent_class = 'has-parent';
                        $child->parent_id = $current_term->term_id;
                    }
                    return $child_categories;
                } else {
                    // Ingen barnekategorier, returner tom array
                    return [];
                }
            }
            // Hvis vi er på en barnekategori, bruk standard funksjon
        }
    }
    
    // For alle andre tilfeller, bruk standard get_filtered_terms
    return get_filtered_terms($taxonomy);
}

// Funksjon for å hente filtrerte taksonomier
function get_filtered_terms($taxonomy) {
    // Hent alle synlige kurs
    $visible_courses = get_posts([
        'post_type' => 'course',
        'posts_per_page' => -1,
        'post_status' => 'publish',
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

    // Hent alle taksonomier med riktige parametre
    $args = [
        'taxonomy' => $taxonomy,
        'hide_empty' => true,
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

    // Legg til spesielle parametre for kategorier
    if ($taxonomy === 'coursecategory') {
        $args['orderby'] = 'menu_order';
        $args['order'] = 'ASC';
        $args['hierarchical'] = true;
        $args['parent'] = 0; // Start med toppnivå kategorier
    }

    $all_terms = get_terms($args);

    // Hent skjulte kategorier
    $hidden_categories = get_terms([
        'taxonomy' => 'coursecategory',
        'hide_empty' => true,
        'meta_query' => [
            [
                'key' => 'hide_in_course_list',
                'value' => 'Skjul',
            ]
        ]
    ]);

    // Filtrer ut taksonomier som bare er knyttet til skjulte kurs eller kurs med skjulte kategorier
    $filtered_terms = array_filter($all_terms, function($term) use ($visible_courses, $hidden_categories) {
        foreach ($visible_courses as $course) {
            if (has_term($term->term_id, $term->taxonomy, $course->ID)) {
                $has_hidden_category = false;
                foreach ($hidden_categories as $hidden_category) {
                    if (has_term($hidden_category->term_id, 'coursecategory', $course->ID)) {
                        $has_hidden_category = true;
                        break;
                    }
                }
                
                if (!$has_hidden_category) {
                    return true;
                }
            }
        }
        return false;
    });

    // For kategorier, hent også underkategorier for de filtrerte termene
    if ($taxonomy === 'coursecategory') {
        $final_terms = [];
        foreach ($filtered_terms as $term) {
            // Legg til toppnivå kategorien
            $term->parent_class = '';
            $term->parent_id = 0;
            $final_terms[] = $term;

            // Hent underkategorier for denne termen
            $child_terms = get_terms([
                'taxonomy' => $taxonomy,
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
                    // Legg til parent-informasjon på underkategoriene
                    $child->parent_class = 'has-parent';
                    $child->parent_id = $term->term_id;
                    $final_terms[] = $child;
                }
            }
        }

        return $final_terms;
    }

    return array_values($filtered_terms);
}

// Funksjon for å hente filtrerte språk
function get_filtered_languages() {
    $visible_courses = get_posts([
        'post_type' => 'course',
        'posts_per_page' => -1,
        'post_status' => 'publish',
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

    $languages = [];
    foreach ($visible_courses as $course) {
        $language = get_post_meta($course->ID, 'course_language', true);
        if (!empty($language)) {
            $languages[$language] = $language;
        }
    }

    return array_values($languages);
}

// Funksjon for å hente filtrerte måneder med årstall-støtte
function get_filtered_months() {
    // Hent kun coursedates siden månedene er lagret der
    $visible_coursedates = get_posts([
        'post_type' => 'coursedate',
        'posts_per_page' => -1,
        'post_status' => 'publish',
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

    $months = [];
    $current_year = (int) date('Y');
    
    foreach ($visible_coursedates as $coursedate) {
        $month = get_post_meta($coursedate->ID, 'course_month', true);
        $first_date = get_post_meta($coursedate->ID, 'course_first_date', true);
        
        if (!empty($month)) {
            $month_num = (int) $month;
            
            // Hvis vi har course_first_date, bruk den for å bestemme år
            if (!empty($first_date)) {
                // Prøv forskjellige datoformater
                $date_obj = null;
                
                // Format 1: Y-m-d H:i:s (fra format_date_for_db)
                $date_obj = DateTime::createFromFormat('Y-m-d H:i:s', $first_date);
                
                // Format 2: Y-m-d (bare dato)
                if (!$date_obj) {
                    $date_obj = DateTime::createFromFormat('Y-m-d', $first_date);
                }
                
                // Format 3: d.m.Y (fra format_date)
                if (!$date_obj) {
                    $date_obj = DateTime::createFromFormat('d.m.Y', $first_date);
                }
                
                if ($date_obj) {
                    $year = (int) $date_obj->format('Y');
                } else {
                    // Fallback til inneværende år hvis dato ikke kan parses
                    $year = $current_year;
                }
            } else {
                // Fallback til inneværende år hvis course_first_date ikke er satt
                $year = $current_year;
            }
            
            // Lag en unik nøkkel som kombinerer måned og år
            $month_year_key = sprintf('%02d%04d', $month_num, $year);
            
            // Bestem visningsnavn basert på år
            if ($year === $current_year) {
                $display_name = mb_ucfirst(date_i18n('F', mktime(0, 0, 0, $month_num, 1)));
            } else {
                $display_name = mb_ucfirst(date_i18n('F', mktime(0, 0, 0, $month_num, 1))) . ' ' . $year;
            }
            
            $months[$month_year_key] = [
                'value' => $month_year_key, // Format: MMYYYY (eks: 092025)
                'name' => $display_name,
                'month' => $month_num,
                'year' => $year,
                'sort_key' => $year * 100 + $month_num // For kronologisk sortering
            ];
        }
    }

    // Sorter månedene kronologisk basert på år og måned
    uasort($months, function($a, $b) {
        return $a['sort_key'] - $b['sort_key'];
    });


    return array_values($months);
}

// Hjelpefunksjon for å gjøre første bokstav stor med støtte for UTF-8
if (!function_exists('mb_ucfirst')) {
    function mb_ucfirst($string) {
        $first = mb_substr($string, 0, 1, 'UTF-8');
        $rest = mb_substr($string, 1, null, 'UTF-8');
        return mb_strtoupper($first, 'UTF-8') . $rest;
    }
}

add_action('wp_ajax_filter_courses', 'filter_courses_handler');
add_action('wp_ajax_nopriv_filter_courses', 'filter_courses_handler');

function filter_courses_handler() {
    // Verifiser nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'filter_nonce')) {
        wp_send_json_error([
            'message' => 'Sikkerhetssjekk feilet. Vennligst oppdater siden og prøv igjen.'
        ], 403);
    }

    try {
        // Debug: Log måned-filter data
        if (defined('WP_DEBUG') && WP_DEBUG) {
            if (isset($_POST['mnd'])) {
                error_log('MONTH DEBUG: POST mnd data: ' . print_r($_POST['mnd'], true));
            }
            error_log('AJAX DEBUG: Starting filter_courses_handler');
        }
        
        // Håndter datofilteret
        $date_param = $_POST['dato'] ?? $_REQUEST['dato'] ?? null;
        
        if (!empty($date_param) && is_string($date_param)) {
            $dates = explode('-', sanitize_text_field($date_param));
            if (count($dates) === 2) {
                $from_date = \DateTime::createFromFormat('d.m.Y', trim($dates[0]));
                $to_date = \DateTime::createFromFormat('d.m.Y', trim($dates[1]));
                
                if ($from_date && $to_date) {
                    $_REQUEST['dato'] = [
                        'from' => $from_date->format('Y-m-d'),
                        'to' => $to_date->format('Y-m-d')
                    ];
                }
            }
        }

        // Håndter søk
        $search_param = $_POST['sok'] ?? $_REQUEST['sok'] ?? null;
        
        if (!empty($search_param)) {
            $_REQUEST['s'] = sanitize_text_field($search_param);
        }

        // Håndter sortering
        $sort = $_POST['sort'] ?? $_REQUEST['sort'] ?? null;
        $order = $_POST['order'] ?? $_REQUEST['order'] ?? null;

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('AJAX DEBUG: About to call get_course_dates_query()');
        }
        $query = get_course_dates_query();
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('AJAX DEBUG: Query completed, found_posts: ' . $query->found_posts);
        }
        
        if ($query->have_posts()) {
            ob_start();
            $context = is_tax() ? 'taxonomy' : 'archive';
            
            while ($query->have_posts()) {
                $query->the_post();
                try {
                    if (!function_exists('get_course_template_part')) {
                        $fallback_template = __DIR__ . '/../list-types/standard.php';
                        include $fallback_template;
                    } else {
                        $style = get_option('kursagenten_archive_list_type', 'standard');
                        $template_path = KURSAG_PLUGIN_DIR . "public/templates/list-types/{$style}.php";
                        
                        if (file_exists($template_path)) {
                            include $template_path;
                        } else {
                            include KURSAG_PLUGIN_DIR . 'public/templates/list-types/standard.php';
                        }
                    }
                } catch (Exception $e) {
                    // Logg bare faktiske feil
                    error_log('Error loading template for post ' . get_the_ID() . ': ' . $e->getMessage());
                }
            }
            wp_reset_postdata();

            // Hent gjeldende forespørsels-URL som base for paginering
            // Determine base URL for pagination
            $current_url = '';
            // Prefer explicit current_url sent from client (AJAX)
            if (!empty($_POST['current_url']) && is_string($_POST['current_url'])) {
                $parsed = wp_parse_url(sanitize_text_field(wp_unslash($_POST['current_url'])));
                if (!empty($parsed['scheme']) && !empty($parsed['host'])) {
                    $path = isset($parsed['path']) ? $parsed['path'] : '';
                    $current_url = home_url($path);
                }
            }
            // Fallbacks
            if (empty($current_url) && !empty($_SERVER['HTTP_REFERER'])) {
                $referer = wp_parse_url($_SERVER['HTTP_REFERER']);
                if ($referer && isset($referer['path'])) {
                    $current_url = home_url($referer['path']);
                }
            }
            if (empty($current_url)) {
                $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
                $path = strtok($request_uri, '?');
                $current_url = home_url($path);
            }

            $current_url = remove_query_arg('side', $current_url);

            $pagination_args = [
                'base' => $current_url . '%_%',
                'current' => max(1, $query->get('paged')),
                'format' => '?side=%#%',
                'total' => $query->max_num_pages,
                'add_args' => array_map(function ($item) {
                    return is_array($item) ? join(',', $item) : $item;
                }, array_diff_key($_REQUEST, ['side' => true, 'action' => true, 'nonce' => true, 'coursedate' => true, 'course' => true]))
            ];

            $pagination = paginate_links($pagination_args);

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('AJAX DEBUG: About to send success response');
            }
            wp_send_json_success([
                'html' => ob_get_clean(),
                'html_pagination' => $pagination,
                'max_num_pages' => $query->max_num_pages,
                'course-count' => sprintf(
                    '%d kurs %s',
                    $query->found_posts,
                    $query->max_num_pages > 1 ? sprintf(
                        '- side %d av %d',
                        $query->get('paged'),
                        $query->max_num_pages
                    ) : ''
                )
            ]);
        } else {
            wp_send_json_success([
                'html' => '<div class="filter-no-results"><p>Ingen kurs funnet. Fjern ett eller flere filtre, eller<a href="#" class="reset-filters"> nullstill alle filtre</a></p></div>',
                'html_pagination' => '',
                'max_num_pages' => 0,
                'course-count' => ''
            ]);
        }
    } catch (Exception $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('AJAX DEBUG: Exception caught: ' . $e->getMessage());
            error_log('AJAX DEBUG: Exception trace: ' . $e->getTraceAsString());
        }
        wp_send_json_error([
            'message' => 'En feil oppstod under filtreringen.'
        ]);
    }
}

add_action('wp_ajax_get_course_price_range', 'get_course_price_range');
add_action('wp_ajax_nopriv_get_course_price_range', 'get_course_price_range');

function get_course_price_range() {
    global $wpdb;

    $min_price = $wpdb->get_var("SELECT MIN(CAST(meta_value AS UNSIGNED)) FROM {$wpdb->postmeta} WHERE meta_key = 'course_price'");
    $max_price = $wpdb->get_var("SELECT MAX(CAST(meta_value AS UNSIGNED)) FROM {$wpdb->postmeta} WHERE meta_key = 'course_price'");

    wp_send_json_success([
        'min_price' => $min_price ? intval($min_price) : 0,
        'max_price' => $max_price ? intval($max_price) : 10000
    ]);
}

function handle_course_filter() {
    check_ajax_referer('kursagenten_ajax_nonce', 'security');

    $filters = $_POST['filters'] ?? [];
    $search = sanitize_text_field($_POST['search'] ?? '');
    $sort = sanitize_text_field($_POST['sort'] ?? '');
    $order = sanitize_text_field($_POST['order'] ?? '');

    $args = get_course_dates_query_args($filters, $search);

    // Legg til sortering
    if ($sort && $order) {
        switch ($sort) {
            case 'title':
                $args['orderby'] = 'title';
                $args['order'] = strtoupper($order);
                break;
            case 'price':
                $args['orderby'] = 'meta_value_num';
                $args['meta_key'] = 'course_price';
                $args['order'] = strtoupper($order);
                break;
            case 'date':
                $args['orderby'] = 'meta_value';
                $args['meta_key'] = 'course_start_date';
                $args['meta_type'] = 'DATE';
                $args['order'] = strtoupper($order);
                break;
        }
    }

    $query = new WP_Query($args);

}

// Legg til AJAX-handler for mobilfiltre
add_action('wp_ajax_load_mobile_filters', 'ka_load_mobile_filters');
add_action('wp_ajax_nopriv_load_mobile_filters', 'ka_load_mobile_filters');

function ka_load_mobile_filters() {
    try {
        if (!check_ajax_referer('filter_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Invalid nonce']);
            return;
        }
        
        // Last inn nødvendige filer
        if (!function_exists('get_course_languages')) {
            $queries_path = KURSAG_PLUGIN_DIR . 'public/templates/includes/queries.php';
            if (!file_exists($queries_path)) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Could not find queries.php at: ' . $queries_path);
                }
                wp_send_json_error(['message' => 'Required files not found']);
                return;
            }
            require_once $queries_path;
        }
        
        // Last inn mobilfilter-malen
        $template_path = KURSAG_PLUGIN_DIR . 'public/templates/mobile-filters.php';
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Looking for template at: ' . $template_path);
        }
        
        if (!file_exists($template_path)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Template file not found at: ' . $template_path);
            }
            wp_send_json_error(['message' => 'Template file not found: ' . $template_path]);
            return;
        }
        
        // Start output buffering
        ob_start();
        include $template_path;
        $html = ob_get_clean();
        
        if (empty($html)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Empty template content from: ' . $template_path);
            }
            wp_send_json_error(['message' => 'Empty template content']);
            return;
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Successfully loaded mobile filters template. Content length: ' . strlen($html));
        }
        wp_send_json_success(['html' => $html]);
        
    } catch (Exception $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Error in ka_load_mobile_filters: ' . $e->getMessage());
        }
        wp_send_json_error(['message' => 'En feil oppstod: ' . $e->getMessage()]);
    }
}

// Legg til AJAX-handler for å hente oppdaterte filter counts
add_action('wp_ajax_get_filter_counts', 'get_filter_counts_handler');
add_action('wp_ajax_nopriv_get_filter_counts', 'get_filter_counts_handler');

function get_filter_counts_handler() {
    // Verifiser nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'filter_nonce')) {
        wp_send_json_error(['message' => 'Sikkerhetssjekk feilet']);
    }

    try {
        // Hent aktive filtre fra POST
        $active_filters = [];
        $filter_params = ['k', 'sted', 'i', 'sprak', 'mnd', 'dato', 'sok'];
        foreach ($filter_params as $param) {
            if (isset($_POST[$param])) {
                $active_filters[$param] = is_array($_POST[$param]) ? $_POST[$param] : explode(',', $_POST[$param]);
            }
        }

        // Hent counts for alle filtertyper
        $counts = [];
        $filter_types = ['categories', 'locations', 'instructors', 'language', 'months'];
        
        foreach ($filter_types as $filter_type) {
            $counts[$filter_type] = get_filter_value_counts($filter_type, $active_filters);
        }

        wp_send_json_success(['counts' => $counts]);
    } catch (Exception $e) {
        wp_send_json_error(['message' => 'En feil oppstod under henting av filter counts']);
    }
}

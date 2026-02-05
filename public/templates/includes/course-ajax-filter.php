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

if (!function_exists('ka_map_legacy_taxonomy')) {
    function ka_map_legacy_taxonomy(string $taxonomy): string {
        $map = [
            'coursecategory'   => 'ka_coursecategory',
            'course_location'  => 'ka_course_location',
            'instructors'      => 'ka_instructors',
        ];

        return $map[$taxonomy] ?? $taxonomy;
    }
}

// Funksjon for å hente filtrerte taksonomier med spesiell håndtering for coursecategory taksonomi-sider
function get_filtered_terms_for_context($taxonomy) {
    $normalized_taxonomy = ka_map_legacy_taxonomy($taxonomy);

    // Spesiell håndtering for ka_coursecategory taksonomi-sider
    if ($normalized_taxonomy === 'ka_coursecategory' && is_tax('ka_coursecategory')) {
        $current_term = get_queried_object();
        if ($current_term && $current_term->taxonomy === 'ka_coursecategory') {
            // Sjekk om vi er på en foreldrekategori (parent = 0)
            if ($current_term->parent == 0) {
                $meta_query = [
                    'relation' => 'OR',
                    [
                        'key' => 'hide_in_course_list',
                        'value' => 'Vis',
                    ],
                    [
                        'key' => 'hide_in_course_list',
                        'compare' => 'NOT EXISTS'
                    ]
                ];

                // Vi er på en foreldrekategori, vis barnekategoriene
                $child_categories = get_terms([
                    'taxonomy' => $normalized_taxonomy,
                    'hide_empty' => true,
                    'parent' => $current_term->term_id,
                    'orderby' => 'menu_order',
                    'order' => 'ASC',
                    'meta_query' => $meta_query
                ]);

                $candidates = [];
                if (!is_wp_error($child_categories) && !empty($child_categories)) {
                    foreach ($child_categories as $child) {
                        $child->parent_class = '';
                        $child->parent_id = 0;
                        $candidates[] = $child;
                    }
                }

                // Hvis innstillingen er aktivert: legg til søskenkategorier (andre toppnivå-kategorier)
                $show_siblings = (get_term_meta($current_term->term_id, 'show_category_filter_on_archive', true) === 'yes');
                if ($show_siblings) {
                    $sibling_categories = get_terms([
                        'taxonomy' => $normalized_taxonomy,
                        'hide_empty' => true,
                        'parent' => 0,
                        'exclude' => [$current_term->term_id],
                        'orderby' => 'menu_order',
                        'order' => 'ASC',
                        'meta_query' => $meta_query
                    ]);
                    if (!is_wp_error($sibling_categories) && !empty($sibling_categories)) {
                        foreach ($sibling_categories as $sibling) {
                            $sibling->parent_class = '';
                            $sibling->parent_id = 0;
                            $candidates[] = $sibling;
                        }
                    }
                }

                // Filter: kun vis kategorier som har minst ett kurs med BÅDE current_term OG denne kategorien (AND)
                $result = ka_filter_terms_with_intersection($candidates, $current_term->term_id, $normalized_taxonomy);
                return $result;
            } else {
                // We are on a child category. If archive category filter is enabled for this term,
                // limit the filter options to categories that share at least one visible coursedate
                // with the current child category.
                $show_filter_on_archive = (get_term_meta($current_term->term_id, 'show_category_filter_on_archive', true) === 'yes');

                if ($show_filter_on_archive) {
                    // Start from the standard visible terms for this taxonomy
                    $all_terms = get_filtered_terms($normalized_taxonomy);

                    if (!empty($all_terms)) {
                        // Reuse the intersection helper so we only keep terms that actually
                        // intersect with the current child category via shared coursedates.
                        return ka_filter_terms_with_intersection($all_terms, $current_term->term_id, $normalized_taxonomy);
                    }
                }
                // If the setting is not enabled or no intersecting terms are found,
                // fall back to the default behavior below.
            }
        }
    }
    
    // For alle andre tilfeller, bruk standard get_filtered_terms
    return get_filtered_terms($normalized_taxonomy);
}

/**
 * Filter terms to only those that have at least one visible coursedate with BOTH the base term AND the candidate term.
 * Used when showing sibling categories with AND logic - only show categories that have overlapping courses.
 *
 * @param array  $terms      Array of term objects (candidates)
 * @param int    $base_term_id Base term ID (e.g. current page category)
 * @param string $taxonomy   Taxonomy name
 * @return array Filtered terms
 */
function ka_filter_terms_with_intersection($terms, $base_term_id, $taxonomy = 'ka_coursecategory') {
    if (empty($terms)) {
        return [];
    }

    $hidden_categories = get_terms([
        'taxonomy' => 'ka_coursecategory',
        'hide_empty' => true,
        'meta_query' => [
            ['key' => 'hide_in_course_list', 'value' => 'Skjul']
        ],
        'fields' => 'ids'
    ]);
    $hidden_ids = !empty($hidden_categories) && !is_wp_error($hidden_categories) ? $hidden_categories : [];

    $base_args = [
        'post_type' => 'ka_coursedate',
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'meta_query' => [
            'relation' => 'OR',
            ['key' => 'hide_in_course_list', 'value' => 'Vis'],
            ['key' => 'hide_in_course_list', 'compare' => 'NOT EXISTS']
        ],
        'tax_query' => [
            'relation' => 'AND',
            ['taxonomy' => $taxonomy, 'field' => 'term_id', 'terms' => [$base_term_id]],
        ]
    ];
    if (!empty($hidden_ids)) {
        $base_args['tax_query'][] = [
            'taxonomy' => $taxonomy,
            'field' => 'term_id',
            'terms' => $hidden_ids,
            'operator' => 'NOT IN'
        ];
    }

    $filtered = [];
    foreach ($terms as $term) {
        $args = $base_args;
        $args['tax_query'][] = ['taxonomy' => $taxonomy, 'field' => 'term_id', 'terms' => [$term->term_id]];
        $q = new WP_Query($args);
        if ($q->found_posts > 0) {
            $filtered[] = $term;
        }
    }
    return $filtered;
}

// Funksjon for å hente filtrerte taksonomier
function get_filtered_terms($taxonomy) {
    $taxonomy = ka_map_legacy_taxonomy($taxonomy);

    // Hent skjulte kategorier
    $hidden_categories = get_terms([
        'taxonomy' => 'ka_coursecategory',
        'hide_empty' => true,
        'meta_query' => [
            [
                'key' => 'hide_in_course_list',
                'value' => 'Skjul',
            ]
        ],
        'fields' => 'ids'
    ]);

    // Hent alle synlige coursedates (ikke courses!)
    $coursedate_args = [
        'post_type' => 'ka_coursedate',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'fields' => 'ids',
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

    // Ekskluder coursedates med skjulte kategorier
    if (!empty($hidden_categories) && !is_wp_error($hidden_categories)) {
        $coursedate_args['tax_query'] = [
            [
                'taxonomy' => 'ka_coursecategory',
                'field' => 'term_id',
                'terms' => $hidden_categories,
                'operator' => 'NOT IN'
            ]
        ];
    }

    $visible_coursedates = get_posts($coursedate_args);

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
    if ($taxonomy === 'ka_coursecategory') {
        $args['orderby'] = 'menu_order';
        $args['order'] = 'ASC';
        $args['hierarchical'] = true;
        $args['parent'] = 0; // Start med toppnivå kategorier
    }

    $all_terms = get_terms($args);

    // Filtrer ut taksonomier som ikke har noen synlige coursedates
    $filtered_terms = array_filter($all_terms, function($term) use ($visible_coursedates, $taxonomy) {
        // Sjekk om minst én av de synlige coursedates har denne termen
        foreach ($visible_coursedates as $coursedate_id) {
            if (has_term($term->term_id, $taxonomy, $coursedate_id)) {
                return true;
            }
        }
        return false;
    });

    // For kategorier, hent også underkategorier for de filtrerte termene
    if ($taxonomy === 'ka_coursecategory') {
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
                // Filtrer også barnekategoriene mot synlige coursedates
                foreach ($child_terms as $child) {
                    $has_visible_coursedate = false;
                    foreach ($visible_coursedates as $coursedate_id) {
                        if (has_term($child->term_id, $taxonomy, $coursedate_id)) {
                            $has_visible_coursedate = true;
                            break;
                        }
                    }
                    
                    if ($has_visible_coursedate) {
                        // Legg til parent-informasjon på underkategoriene
                        $child->parent_class = 'has-parent';
                        $child->parent_id = $term->term_id;
                        $final_terms[] = $child;
                    }
                }
            }
        }

        return $final_terms;
    }

    return array_values($filtered_terms);
}

// Funksjon for å hente filtrerte lokasjoner
function get_filtered_location_terms() {
    // Hent skjulte kategorier
    $hidden_categories = get_terms([
        'taxonomy' => 'ka_coursecategory',
        'hide_empty' => true,
        'meta_query' => [
            [
                'key' => 'hide_in_course_list',
                'value' => 'Skjul',
            ]
        ],
        'fields' => 'ids'
    ]);

    // Hent alle synlige coursedates
    $coursedate_args = [
        'post_type' => 'ka_coursedate',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'fields' => 'ids',
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

    // Ekskluder coursedates med skjulte kategorier
    if (!empty($hidden_categories) && !is_wp_error($hidden_categories)) {
        $coursedate_args['tax_query'] = [
            [
                'taxonomy' => 'ka_coursecategory',
                'field' => 'term_id',
                'terms' => $hidden_categories,
                'operator' => 'NOT IN'
            ]
        ];
    }

    $visible_coursedates = get_posts($coursedate_args);

    // Hent alle lokasjonstermer
    $all_location_terms = get_terms([
        'taxonomy' => 'ka_course_location',
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
    ]);

    if (is_wp_error($all_location_terms) || empty($all_location_terms)) {
        return [];
    }

    // Filtrer ut lokasjoner som ikke har noen synlige coursedates
    $filtered_locations = array_filter($all_location_terms, function($term) use ($visible_coursedates) {
        // Sjekk om minst én av de synlige coursedates har denne lokasjonen
        foreach ($visible_coursedates as $coursedate_id) {
            $location = get_post_meta($coursedate_id, 'ka_course_location', true);
            if ($location === $term->name) {
                return true;
            }
        }
        return false;
    });

    return array_values($filtered_locations);
}

// Funksjon for å hente filtrerte språk
function get_filtered_languages() {
    // Hent skjulte kategorier
    $hidden_categories = get_terms([
        'taxonomy' => 'ka_coursecategory',
        'hide_empty' => true,
        'meta_query' => [
            [
                'key' => 'hide_in_course_list',
                'value' => 'Skjul',
            ]
        ],
        'fields' => 'ids'
    ]);

    // Hent alle synlige coursedates
    $args = [
        'post_type' => 'ka_coursedate',
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
    ];

    // Ekskluder coursedates med skjulte kategorier
    if (!empty($hidden_categories) && !is_wp_error($hidden_categories)) {
        $args['tax_query'] = [
            [
                'taxonomy' => 'ka_coursecategory',
                'field' => 'term_id',
                'terms' => $hidden_categories,
                'operator' => 'NOT IN'
            ]
        ];
    }

    $visible_coursedates = get_posts($args);

    $languages = [];
    foreach ($visible_coursedates as $coursedate) {
        $language = get_post_meta($coursedate->ID, 'ka_course_language', true);
        if (!empty($language)) {
            $languages[strtolower($language)] = strtolower($language);
        }
    }

    return array_values($languages);
}

// Funksjon for å hente filtrerte måneder med årstall-støtte
function get_filtered_months() {
    // Hent skjulte kategorier
    $hidden_categories = get_terms([
        'taxonomy' => 'ka_coursecategory',
        'hide_empty' => true,
        'meta_query' => [
            [
                'key' => 'hide_in_course_list',
                'value' => 'Skjul',
            ]
        ],
        'fields' => 'ids'
    ]);

    // Hent kun coursedates siden månedene er lagret der
    $args = [
        'post_type' => 'ka_coursedate',
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
    ];

    // Ekskluder coursedates med skjulte kategorier
    if (!empty($hidden_categories) && !is_wp_error($hidden_categories)) {
        $args['tax_query'] = [
            [
                'taxonomy' => 'ka_coursecategory',
                'field' => 'term_id',
                'terms' => $hidden_categories,
                'operator' => 'NOT IN'
            ]
        ];
    }

    $visible_coursedates = get_posts($args);

    $months = [];
    $current_year = (int) date('Y');
    
    foreach ($visible_coursedates as $coursedate) {
        $month = get_post_meta($coursedate->ID, 'ka_course_month', true);
        $first_date = get_post_meta($coursedate->ID, 'ka_course_first_date', true);
        
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
        // Debug: Log måned-filter data (commented out to reduce log noise)
        // if (defined('WP_DEBUG') && WP_DEBUG) {
        //     if (isset($_POST['mnd'])) {
        //         error_log('MONTH DEBUG: POST mnd data: ' . print_r($_POST['mnd'], true));
        //     }
        //     error_log('AJAX DEBUG: Starting filter_courses_handler');
        // }
        
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

        // if (defined('WP_DEBUG') && WP_DEBUG) {
        //     error_log('AJAX DEBUG: About to call get_course_dates_query()');
        // }
        $query = get_course_dates_query();
        // if (defined('WP_DEBUG') && WP_DEBUG) {
        //     error_log('AJAX DEBUG: Query completed, found_posts: ' . $query->found_posts);
        // }
        
        if ($query->have_posts()) {
            ob_start();

            // Try to respect the list type that was used on initial render (internal only, not a filter param).
            $incoming_list_type = isset($_POST['internal_list_type'])
                ? sanitize_text_field(wp_unslash($_POST['internal_list_type']))
                : '';

            if ($incoming_list_type !== '') {
                $style = $incoming_list_type;
            } else {
                // Fallback to global archive list type (legacy behaviour).
                $style = (string) get_option('kursagenten_archive_list_type', 'standard');
            }
            if ($style === '') {
                $style = 'standard';
            }

            // For AJAX-filtreringen viser vi alltid alle kursdatoer,
            // akkurat som [kursliste] gjør.
            $view_type = 'all_coursedates';

            // Build args for list-type templates (view_type, etc.)
            $template_args = [
                'list_type' => $style,
                'view_type' => $view_type,
                'is_taxonomy_page' => false,
                'query' => $query,
            ];
            
            while ($query->have_posts()) {
                $query->the_post();
                try {
                    $args = $template_args;
                    if (function_exists('get_course_template_part')) {
                        // Central resolver handles which list-type template to include.
                        get_course_template_part($args);
                    } else {
                        // Fallback to standard list type if template functions are missing.
                        include KURSAG_PLUGIN_DIR . 'public/templates/list-types/standard.php';
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

            // Fjern ALLE query parametere fra URL-en - de skal kun komme fra add_args
            $current_url = strtok($current_url, '?');

            $pagination_args = [
                'base' => $current_url . '%_%',
                'current' => max(1, $query->get('paged')),
                'format' => '?side=%#%',
                'total' => $query->max_num_pages,
                'add_args' => array_map(function ($item) {
                    return is_array($item) ? join(',', $item) : $item;
                }, array_diff_key(
                    $_REQUEST,
                    [
                        'side' => true,
                        'action' => true,
                        'nonce' => true,
                        'current_url' => true,
                        'list_type' => true, // View layout, not a filter - do not propagate in pagination
                        'internal_list_type' => true, // Internal layout hint, never part of URL filters
                        'ka_coursedate' => true,
                        'ka_course' => true,
                        'coursedate' => true,
                        'course' => true,
                    ]
                ))
            ];

            $pagination = paginate_links($pagination_args);

            // if (defined('WP_DEBUG') && WP_DEBUG) {
            //     error_log('AJAX DEBUG: About to send success response');
            // }
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
        // if (defined('WP_DEBUG') && WP_DEBUG) {
        //     error_log('AJAX DEBUG: Exception caught: ' . $e->getMessage());
        //     error_log('AJAX DEBUG: Exception trace: ' . $e->getTraceAsString());
        // }
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
        
        // Hent active_shortcode_filters fra POST data
        $active_shortcode_filters = isset($_POST['active_shortcode_filters']) && is_array($_POST['active_shortcode_filters']) 
            ? $_POST['active_shortcode_filters'] 
            : [];
        
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
    // Prevent caching - counts depend on active filters and must be fresh
    nocache_headers();

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

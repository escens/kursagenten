<?php

/**
 * Retrieve data for the first available coursedate.
 * For use in single-course.php.
 *
 * @param array $related_coursedate Array of related coursedate IDs.
 * @return array|null Returns an array with metadata for the selected coursedate, or null if none found.
 */
function get_selected_coursedate_data($related_coursedate) {
    $earliest_date = null;
    $earliest_full_date = null;
    $selected_coursedate = null;
    $selected_full_coursedate = null;
    $coursedatemissing = true;

    if (!empty($related_coursedate) && is_array($related_coursedate)) {
        foreach ($related_coursedate as $coursedate_id) {
            // Skip if coursedate_id is empty or invalid
            if (empty($coursedate_id) || !get_post($coursedate_id)) {
                continue;
            }

            if (has_hidden_terms($coursedate_id)) {
                continue;
            }

            $course_first_date = get_post_meta($coursedate_id, 'course_first_date', true);
            $is_full = get_post_meta($coursedate_id, 'course_isFull', true) || get_post_meta($coursedate_id, 'course_markedAsFull', true);

            // Hvis course_first_date finnes, sammenlign for å finne den tidligste
            if (!empty($course_first_date)) {
                $coursedatemissing = false;
                $current_date = new DateTime($course_first_date);
                
                if (!$is_full) {
                    // Prioriter ledige kursdatoer
                    if (!$earliest_date || $current_date < $earliest_date) {
                        $earliest_date = $current_date;
                        $selected_coursedate = $coursedate_id;
                    }
                } else {
                    // Lagre også fulle kursdatoer som fallback
                    if (!$earliest_full_date || $current_date < $earliest_full_date) {
                        $earliest_full_date = $current_date;
                        $selected_full_coursedate = $coursedate_id;
                    }
                }
            }
        }

        // Hvis ingen ledige kursdatoer ble funnet, bruk den tidligste fulle kursdatoen
        if (!$selected_coursedate && $selected_full_coursedate) {
            $selected_coursedate = $selected_full_coursedate;
        }
        // Hvis ingen gyldig dato er funnet, velg den første tilgjengelige coursedate
        else if (!$selected_coursedate && !empty($related_coursedate)) {
            $selected_coursedate = reset($related_coursedate);
        }

        if ($selected_coursedate) {
            $return_data = [
                'id' => $selected_coursedate,
                'title' => get_the_title($selected_coursedate),
                'first_date' => ka_format_date(get_post_meta($selected_coursedate, 'course_first_date', true)),
                'last_date' => ka_format_date(get_post_meta($selected_coursedate, 'course_last_date', true)),
                'price' => get_post_meta($selected_coursedate, 'course_price', true),
                'after_price' => get_post_meta($selected_coursedate, 'course_text_after_price', true),
                'duration' => get_post_meta($selected_coursedate, 'course_duration', true),
                'time' => get_post_meta($selected_coursedate, 'course_time', true),
                'language' => get_post_meta($selected_coursedate, 'course_language', true),
                'button_text' => get_post_meta($selected_coursedate, 'course_button_text', true),
                'signup_url' => get_post_meta($selected_coursedate, 'course_signup_url', true),
                'coursedatemissing' => $coursedatemissing,
                'is_full' => get_post_meta($selected_coursedate, 'course_isFull', true) || get_post_meta($selected_coursedate, 'course_markedAsFull', true),
                'show_registration' => get_post_meta($selected_coursedate, 'course_showRegistrationForm', true),
            ];
            return $return_data;
        }
    }

    return [
        'coursedatemissing' => $coursedatemissing,
    ];
}

/**
 * Helper function to check if a post has hidden terms
 */
function has_hidden_terms($post_id) {
    
    $terms = wp_get_post_terms($post_id, 'coursecategory', array('fields' => 'slugs'));
    if (is_wp_error($terms)) {
        //error_log('Feil ved henting av termer for post_id ' . $post_id . ': ' . $terms->get_error_message());
        return false;
    }
    

    $hidden_terms = unserialize(KURSAG_HIDDEN_TERMS);
    
    $intersection = array_intersect($terms, $hidden_terms);
    //error_log('Interseksjon av termer: ' . print_r($intersection, true));

    $has_hidden = !empty($intersection);
    
    return $has_hidden;
}

/**
 * Retrieve and sort all coursedates by date.
 * For use in single-course.php.
 *
 * @param array $related_coursedate Array of related coursedate IDs.
 * @return array Returns an array of sorted coursedate data, including metadata and an indicator for missing first date.
 */
function get_all_sorted_coursedates($related_coursedate) {
    //error_log('get_all_sorted_coursedates - Input: ' . print_r($related_coursedate, true));
    
    $all_coursedates = [];
    $coursedates_without_date = [];

    if (!empty($related_coursedate) && is_array($related_coursedate)) {
        foreach ($related_coursedate as $coursedate_id) {
            //error_log('Prosesserer coursedate_id: ' . $coursedate_id);
            
            if (has_hidden_terms($coursedate_id)) {
                //error_log('Hopper over coursedate_id ' . $coursedate_id . ' - har skjulte termer');
                continue;
            }

            $course_id = get_post_meta($coursedate_id, 'related_course', true);
            //error_log('related_course for ' . $coursedate_id . ': ' . $course_id);

            if ($course_id && has_hidden_terms($course_id)) {
                //error_log('Hopper over coursedate_id ' . $coursedate_id . ' - relatert kurs har skjulte termer');
                continue;
            }

            $course_first_date = ka_format_date(get_post_meta($coursedate_id, 'course_first_date', true));
            //error_log('course_first_date for ' . $coursedate_id . ': ' . $course_first_date);

            $coursedate_data = [
                'id' => $coursedate_id,
                'title' => get_the_title($coursedate_id),
                'course_title' => get_post_meta($coursedate_id, 'course_title', true),
                'first_date' => $course_first_date,
                'last_date' => ka_format_date(get_post_meta($coursedate_id, 'course_last_date', true)),
                'price' => get_post_meta($coursedate_id, 'course_price', true),
                'location' => get_post_meta($coursedate_id, 'course_location', true),
                'duration' => get_post_meta($coursedate_id, 'course_duration', true),
                'time' => get_post_meta($coursedate_id, 'course_time', true),
                'button_text' => get_post_meta($coursedate_id, 'course_button_text', true),
                'signup_url' => get_post_meta($coursedate_id, 'course_signup_url', true),
                'missing_first_date' => empty($course_first_date),
                'is_full' => get_post_meta($coursedate_id, 'course_isFull', true) || get_post_meta($coursedate_id, 'course_markedAsFull', true),
                'course_location_freetext' => get_post_meta($coursedate_id, 'course_location_freetext', true),
                'address_street' => get_post_meta($coursedate_id, 'course_address_street', true),
                'address_number' => get_post_meta($coursedate_id, 'course_address_street_number', true),
                'postal_code' => get_post_meta($coursedate_id, 'course_address_zipcode', true),
                'city' => get_post_meta($coursedate_id, 'course_address_place', true),
                'language' => get_post_meta($coursedate_id, 'course_language', true),
            ];

            if (empty($course_first_date)) {
                $coursedates_without_date[] = $coursedate_data;
                //error_log('Lagt til coursedate uten dato: ' . $coursedate_id);
            } else {
                $all_coursedates[] = $coursedate_data;
                //error_log('Lagt til coursedate med dato: ' . $coursedate_id);
            }
        }

        // Sorter kursdatoer med dato
        usort($all_coursedates, function ($a, $b) {
            return strtotime($a['first_date']) - strtotime($b['first_date']);
        });

        // Legg til kursdatoer uten dato på slutten
        $all_coursedates = array_merge($all_coursedates, $coursedates_without_date);
        //error_log('Returnerer ' . count($all_coursedates) . ' coursedates');
    } else {
        //error_log('Ingen coursedates funnet i input');
    }

    return $all_coursedates;
}

add_filter('posts_join', function (string $join, WP_Query $query) {
	global $wpdb;
	if ($query->get('orderby') == 'course_first_date') {
		$join .= " LEFT JOIN $wpdb->postmeta as pcfd ON (wp_posts.ID = pcfd.post_id AND pcfd.meta_key = 'course_first_date')";
	}
	return $join;
}, 10, 2);

/**
 * Retrieve and sort all coursedates by date.
 * For use in archive-course.php, course calendar.
 *
 * @return WP_Query Returns an array of sorted coursedate data, including metadata and an indicator for missing first date.
 */
function get_course_dates_query($args = []) {
    // Debug logging
    error_log('=== START get_course_dates_query ===');
    error_log('Request parameters: ' . print_r($_REQUEST, true));

    // Håndter request-parametere med støtte for arrays
    $current_page = isset($_REQUEST['side']) ? max(1, intval($_REQUEST['side'])) : 1;
    $locations = isset($_REQUEST['sted']) ? (is_array($_REQUEST['sted']) ? $_REQUEST['sted'] : explode(',', $_REQUEST['sted'])) : [];
    $categories = isset($_REQUEST['k']) ? (is_array($_REQUEST['k']) ? $_REQUEST['k'] : explode(',', $_REQUEST['k'])) : [];
    $instructors = isset($_REQUEST['i']) ? (is_array($_REQUEST['i']) ? $_REQUEST['i'] : explode(',', $_REQUEST['i'])) : [];
    $languages = isset($_REQUEST['sprak']) ? (is_array($_REQUEST['sprak']) ? $_REQUEST['sprak'] : explode(',', $_REQUEST['sprak'])) : [];
    $price_min = isset($_REQUEST['price_min']) ? floatval($_REQUEST['price_min']) : 0;
    $price_max = isset($_REQUEST['price_max']) ? floatval($_REQUEST['price_max']) : PHP_FLOAT_MAX;
    $date_from = isset($_REQUEST['date_from']) ? sanitize_text_field($_REQUEST['date_from']) : '';
    $date_to = isset($_REQUEST['date_to']) ? sanitize_text_field($_REQUEST['date_to']) : '';
    $sort = isset($_REQUEST['sort']) ? sanitize_text_field($_REQUEST['sort']) : '';
    $order = isset($_REQUEST['order']) ? sanitize_text_field($_REQUEST['order']) : 'asc';
    $months = isset($_REQUEST['mnd']) ? (is_array($_REQUEST['mnd']) ? $_REQUEST['mnd'] : explode(',', $_REQUEST['mnd'])) : [];
    $per_page = isset($_REQUEST['per_page']) ? intval($_REQUEST['per_page']) : get_option('kursagenten_courses_per_page', 10);
    $search = isset($_REQUEST['sok']) ? sanitize_text_field($_REQUEST['sok']) : '';
    
    error_log('Processed parameters:');
    error_log('- Locations: ' . print_r($locations, true));
    error_log('- Categories: ' . print_r($categories, true));
    error_log('- Instructors: ' . print_r($instructors, true));
    error_log('- Languages: ' . print_r($languages, true));

    // Håndter dato-parameter hvis den er satt
    if (isset($_REQUEST['dato']) && !empty($_REQUEST['dato'])) {
        $date_parts = explode('-', $_REQUEST['dato']);
        if (count($date_parts) === 2) {
            $date_from = date('Y-m-d', strtotime(str_replace('.', '-', $date_parts[0])));
            $date_to = date('Y-m-d', strtotime(str_replace('.', '-', $date_parts[1])));
        }
    }
    
    // Bygg meta_query
    $meta_query = ['relation' => 'AND'];
    
    // Legg til location filter
    if (!empty($locations)) {
        $location_query = ['relation' => 'OR'];
        foreach ($locations as $location) {
            $location_query[] = [
                'relation' => 'OR',
                [
                    'key' => 'course_location',
                    'value' => $location,
                    'compare' => '='
                ],
                [
                    'key' => 'course_location_freetext',
                    'value' => $location,
                    'compare' => '='
                ]
            ];
        }
        $meta_query[] = $location_query;
        error_log('Location meta_query: ' . print_r($location_query, true));
    }
    
    // Legg til språk filter
    if (!empty($languages)) {
        $language_query = ['relation' => 'OR'];
        foreach ($languages as $language) {
            $language_query[] = [
                'key' => 'course_language',
                'value' => $language,
                'compare' => '='
            ];
        }
        $meta_query[] = $language_query;
    }
    
    // Legg til måned filter
    if (!empty($months)) {
        $month_query = ['relation' => 'OR'];
        foreach ($months as $month) {
            $month_query[] = [
                'key' => 'course_month',
                'value' => $month,
                'compare' => '='
            ];
        }
        $meta_query[] = $month_query;
    }
    
    // Legg til pris filter
    if ($price_min > 0 || $price_max < PHP_FLOAT_MAX) {
        $meta_query[] = [
            'key' => 'course_price',
            'value' => [$price_min, $price_max],
            'type' => 'NUMERIC',
            'compare' => 'BETWEEN'
        ];
    }
    
    // Legg til dato filter
    if (!empty($date_from) || !empty($date_to)) {
        $date_query = ['relation' => 'AND'];
        
        if (!empty($date_from)) {
            $date_query[] = [
                'key' => 'course_first_date',
                'value' => $date_from,
                'compare' => '>=',
                'type' => 'DATE'
            ];
        }
        
        if (!empty($date_to)) {
            $date_query[] = [
                'key' => 'course_first_date',
                'value' => $date_to,
                'compare' => '<=',
                'type' => 'DATE'
            ];
        }
        
        $meta_query[] = $date_query;
    }
    
    // Legg til søk hvis søkeord er angitt
    if (!empty($search)) {
        $meta_query[] = [
            'relation' => 'OR',
            [
                'key' => 'course_title',
                'value' => $search,
                'compare' => 'LIKE'
            ],
            [
                'key' => 'course_description',
                'value' => $search,
                'compare' => 'LIKE'
            ]
        ];
    }
    
    // Bygg tax_query
    $tax_query = ['relation' => 'AND'];
    
    // Hent skjulte kategorier
    $hidden_categories = get_terms([
        'taxonomy' => 'coursecategory',
        'hide_empty' => true,
        'meta_query' => [
            [
                'key' => 'hide_in_course_list',
                'value' => 'Skjul',
            ]
        ],
        'fields' => 'ids'
    ]);

    // Hvis vi har skjulte kategorier, legg til en enkel NOT IN query
    if (!empty($hidden_categories) && !is_wp_error($hidden_categories)) {
        $tax_query[] = [
            'taxonomy' => 'coursecategory',
            'field' => 'term_id',
            'terms' => $hidden_categories,
            'operator' => 'NOT IN'
        ];
    }
    
    // Legg til kategori filter hvis valgt
    if (!empty($categories)) {
        $tax_query[] = [
            'taxonomy' => 'coursecategory',
            'field' => 'slug',
            'terms' => $categories,
            'operator' => 'IN'
        ];
    }
    
    // Legg til instruktør filter
    if (!empty($instructors)) {
        $tax_query[] = [
            'taxonomy' => 'instructors',
            'field' => 'slug',
            'terms' => $instructors,
            'operator' => 'IN'
        ];
    }
    
    // Bygg WP_Query args
    $query_args = [
        'post_type' => 'coursedate',
        'posts_per_page' => $per_page,
        'paged' => $current_page,
        'meta_query' => $meta_query,
        'tax_query' => $tax_query,
        'suppress_filters' => false,
        'post_status' => ['publish']
    ];

    error_log('Final query args: ' . print_r($query_args, true));

    // Legg til søk hvis søkeord er angitt
    if (!empty($search)) {
        $query_args['s'] = $search;
    }
    
    // Legg til filter for å modifisere SQL-spørringen
    add_filter('posts_join', function($join, $query) use ($sort) {
        global $wpdb;
        if ($query->get('post_type') === 'coursedate') {
            $join .= " LEFT JOIN {$wpdb->postmeta} as course_date_meta ON ({$wpdb->posts}.ID = course_date_meta.post_id AND course_date_meta.meta_key = 'course_first_date')";
            
            // Legg til JOIN for pris-sortering
            if ($sort === 'price') {
                $join .= " LEFT JOIN {$wpdb->postmeta} as course_price_meta ON ({$wpdb->posts}.ID = course_price_meta.post_id AND course_price_meta.meta_key = 'course_price')";
            }
            
            // Legg til JOIN for tittel-sortering
            if ($sort === 'title') {
                $join .= " LEFT JOIN {$wpdb->posts} as course_title ON ({$wpdb->posts}.ID = course_title.ID)";
            }
        }
        return $join;
    }, 10, 2);
    
    add_filter('posts_orderby', function($orderby, $query) use ($sort, $order) {
        global $wpdb;
        if ($query->get('post_type') === 'coursedate') {
            $order = strtoupper($order);
            
            switch ($sort) {
                case 'price':
                    // Sorter på pris, med NULL-verdier sist
                    $orderby = "CASE WHEN course_price_meta.meta_value IS NULL THEN 1 ELSE 0 END ASC, 
                               CAST(COALESCE(course_price_meta.meta_value, '999999') AS DECIMAL) {$order}";
                    break;
                    
                case 'title':
                    // Sorter på tittel
                    $orderby = "course_title.post_title {$order}";
                    break;
                    
                case 'date':
                    // Sorter på dato
                    $orderby = "CASE WHEN course_date_meta.meta_value IS NULL THEN 1 ELSE 0 END ASC, 
                               CASE WHEN course_date_meta.meta_value IS NOT NULL 
                                    THEN CAST(course_date_meta.meta_value AS DATE) 
                                    ELSE '9999-12-31' 
                               END {$order}";
                    break;
                    
                default:
                    // Standard sortering: dato først, deretter tittel
                    $orderby = "CASE WHEN course_date_meta.meta_value IS NULL THEN 1 ELSE 0 END ASC, 
                               CASE WHEN course_date_meta.meta_value IS NOT NULL 
                                    THEN CAST(course_date_meta.meta_value AS DATE) 
                                    ELSE '9999-12-31' 
                               END ASC, 
                               {$wpdb->posts}.post_title ASC";
            }
        }
        return $orderby;
    }, 10, 2);
    
    // Kjør query
    $query = new WP_Query($query_args);
    
    error_log('Query SQL: ' . $query->request);
    error_log('Found posts: ' . $query->found_posts);
    error_log('=== END get_course_dates_query ===');

    // Fjern filtrene etter at queryen er kjørt
    remove_all_filters('posts_join');
    remove_all_filters('posts_orderby');
    
    return $query;
}

/**
 * Finn URL for et course med samme location_id som coursedate.
 *
 * @param int $coursedate_id ID for coursedate-innlegget.
 * @return string|null URL til det relaterte kurset, eller null hvis ikke funnet.
 */
function get_course_info_by_location($related_course_id) {
    // Sjekk om related_course_id er angitt
    if (!$related_course_id) {
        return null; // Returner null hvis related_course_id mangler
    }

    // Søk etter course med matching location_id
    $args = [
        'post_type'      => 'course',
        'posts_per_page' => 1,
        'meta_query'     => [
            [
                'key'     => 'location_id',
                'value'   => $related_course_id,
                'compare' => '=',
            ],
        ],
    ];

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        $course = $query->posts[0];
        $course_id = $course->ID;

        // Hent informasjon fra det relaterte kurset
        $course_info = [
            'title'      => get_the_title($course_id),
            'permalink'  => get_permalink($course_id),
            'thumbnail'  => get_the_post_thumbnail_url($course_id, 'thumbnail') ?: KURSAG_PLUGIN_URL . '/assets/images/placeholder-kurs.jpg',
            'thumbnail-medium'  => get_the_post_thumbnail_url($course_id, 'medium') ?: KURSAG_PLUGIN_URL . '/assets/images/placeholder-kurs.jpg',
            'thumbnail-full'  => get_the_post_thumbnail_url($course_id, 'full') ?: KURSAG_PLUGIN_URL . '/assets/images/placeholder-kurs.jpg',
            'excerpt'    => get_the_excerpt($course_id),
        ];

        wp_reset_postdata(); // Rydd opp etter WP_Query
        return $course_info; // Returner informasjonen som en array
    }

    wp_reset_postdata(); // Rydd opp selv om ingen kurs ble funnet
    return null; // Returner null hvis ingen kurs ble funnet
}

function get_course_languages() {
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
            $language_terms[] = (object) [
                'slug' => strtolower($meta_language),
                'name' => ucfirst($meta_language),
                'value' => strtolower($meta_language)
            ];
        }
    }

    // Sorter språkene alfabetisk
    usort($language_terms, function($a, $b) {
        return strcmp($a->name, $b->name);
    });

    return array_unique($language_terms, SORT_REGULAR);
}

/**
 * Get all available months from course dates
 * 
 * @return array Array of month objects with name and number
 */

 function get_course_months() {
    $args = [
        'post_type'      => 'coursedate',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ];

    $coursedates = get_posts($args);
    $month_terms = [];

    foreach ($coursedates as $post_id) {
        $meta_month = get_post_meta($post_id, 'course_month', true);
        if (!empty($meta_month) && is_numeric($meta_month) && $meta_month >= 1 && $meta_month <= 12) {
            $padded_month = str_pad($meta_month, 2, '0', STR_PAD_LEFT);
            $month_terms[] = (object) [
                'slug' => $padded_month,
                'name' => ucfirst(date_i18n('F', strtotime("2024-{$padded_month}-01"))),
                'value' => $padded_month
            ];
        }
    }

    // Sorter månedene etter verdi
    usort($month_terms, function($a, $b) {
        return (int)$a->value - (int)$b->value;
    });

    return array_unique($month_terms, SORT_REGULAR);
}

/**
 * Henter kurs (ikke coursedates) for taksonomi-sidene.
 * For hvert kurs returneres informasjon om kurset sammen med den første tilgjengelige coursedate.
 *
 * @param array $args Spørringsargumenter
 * @return WP_Query Spørring med kurs og relatert coursedate-informasjon
 */
function get_courses_for_taxonomy($args = []) {
    $current_page = isset($args['paged']) ? max(1, intval($args['paged'])) : (isset($_REQUEST['side']) ? max(1, intval($_REQUEST['side'])) : max(1, get_query_var('paged')));

    // Sjekk om vi har en taksonomi-spørring i args
    $taxonomy = '';
    if (isset($args['tax_query']) && is_array($args['tax_query'])) {
        foreach ($args['tax_query'] as $tax_query) {
            if (isset($tax_query['taxonomy'])) {
                $taxonomy = $tax_query['taxonomy'];
                break;
            }
        }
    }

    // Spesiell håndtering for course_location taksonomi
    if ($taxonomy === 'course_location') {
        $default_args = [
            'post_type'      => 'course',
            'posts_per_page' => -1,
            'paged'          => $current_page,
            'tax_query'      => $args['tax_query'] ?? ['relation' => 'AND'],
            'meta_query'     => [
                'relation' => 'AND',
                [
                    'relation' => 'OR',
                    [
                        'key'     => 'is_parent_course',
                        'compare' => 'NOT EXISTS'
                    ],
                    [
                        'key'     => 'is_parent_course',
                        'value'   => 'yes',
                        'compare' => '!='
                    ]
                ],
                [
                    'key'     => 'course_location_freetext',
                    'compare' => 'EXISTS'
                ]
            ]
        ];

        $query_args = wp_parse_args($args, $default_args);
        $query = new WP_Query($query_args);

        // Logg for debugging
        //error_log('Course Location Query Args: ' . print_r($query_args, true));
        //error_log('Found Posts: ' . $query->found_posts);

        return $query;
    }

    // Standard håndtering for andre taksonomier (coursecategory og instructors)
    $default_args = [
        'post_type'      => 'course',
        'posts_per_page' => -1,
        'paged'          => $current_page,
        'tax_query'      => ['relation' => 'AND'],
        'meta_query'     => [
            [
                'key'     => 'is_parent_course',
                'value'   => 'yes',
                'compare' => '='
            ]
        ],
    ];

    // Legg til taksonomi-spørringen for andre taksonomier
    if (isset($args['tax_query']) && is_array($args['tax_query'])) {
        foreach ($args['tax_query'] as $tax_query) {
            if (isset($tax_query['taxonomy']) && $tax_query['taxonomy'] !== 'course_location') {
                $default_args['tax_query'][] = $tax_query;
            }
        }
    }

    $query_args = wp_parse_args($args, $default_args);
    $query = new WP_Query($query_args);

    // Logg for debugging
    //error_log('Standard Taxonomy Query Args: ' . print_r($query_args, true));
    //error_log('Found Posts: ' . $query->found_posts);

    return $query;
}

/**
 * Henter alle tilgjengelige lokasjoner for et kurs
 * Brukes i single-course templates
 * 
 * @param int $post_id ID for kurset
 * @return array Array med lokasjoner som inneholder navn og slug
 */
function get_course_locations($post_id) {
    $locations = array();
    
    // Sjekk om dette er et foreldrekurs
    $is_parent_course = get_post_meta($post_id, 'is_parent_course', true);
    
    if ($is_parent_course === 'yes') {
        // For foreldrekurs, hent alle lokasjoner fra taxonomien
        $location_terms = wp_get_object_terms($post_id, 'course_location');
        if (!is_wp_error($location_terms)) {
            foreach ($location_terms as $term) {
                $locations[] = array(
                    'name' => $term->name,
                    'slug' => $term->slug
                );
            }
        }
    } else {
        // For underkurs, hent hovedkurset og alle dets lokasjoner
        $main_course_id = get_post_meta($post_id, 'main_course_id', true);
        $main_course = get_posts(array(
            'post_type' => 'course',
            'meta_query' => array(
                array(
                    'key' => 'main_course_id',
                    'value' => $main_course_id,
                    'compare' => '='
                ),
                array(
                    'key' => 'is_parent_course',
                    'value' => 'yes',
                    'compare' => '='
                )
            ),
            'posts_per_page' => 1
        ));
        
        if (!empty($main_course)) {
            $location_terms = wp_get_object_terms($main_course[0]->ID, 'course_location');
            if (!is_wp_error($location_terms)) {
                foreach ($location_terms as $term) {
                    $locations[] = array(
                        'name' => $term->name,
                        'slug' => $term->slug
                    );
                }
            }
        }
    }
    
    return $locations;
}

/**
 * Genererer HTML for lokasjonslisten
 * Brukes i single-course templates
 * 
 * @param int $post_id ID for kurset
 * @return string HTML for lokasjonslisten
 */
function display_course_locations($post_id) {
    $locations = get_course_locations($post_id);
    $current_location = get_post_meta($post_id, 'sub_course_location', true);
    $is_parent_course = get_post_meta($post_id, 'is_parent_course', true);
    
    // Hent hovedkursets permalink
    $main_course_url = '';
    if ($is_parent_course !== 'yes') {
        $main_course_id = get_post_meta($post_id, 'main_course_id', true);
        $main_course = get_posts(array(
            'post_type' => 'course',
            'meta_query' => array(
                array(
                    'key' => 'main_course_id',
                    'value' => $main_course_id,
                    'compare' => '='
                ),
                array(
                    'key' => 'is_parent_course',
                    'value' => 'yes',
                    'compare' => '='
                )
            ),
            'posts_per_page' => 1
        ));
        if (!empty($main_course)) {
            $main_course_url = get_permalink($main_course[0]->ID);
        }
    } else {
        $main_course_url = get_permalink($post_id);
    }
    
    // Start HTML output
    $output = '<div class="course-locations-list">';
    $output .= '<ul class="location-tabs">';
    
    // Legg til "Alle" link
    $output .= '<li class="' . ($is_parent_course === 'yes' ? 'active' : '') . '">';
    $output .= '<a href="' . esc_url($main_course_url) . '">Alle</a>';
    $output .= '</li>';
    
    // Legg til alle lokasjoner
    foreach ($locations as $location) {
        $is_active = ($current_location === $location['name']);
        $location_url = $main_course_url . $location['slug'] . '/';
        
        $output .= '<li class="' . ($is_active ? 'active' : '') . '">';
        $output .= '<a href="' . esc_url($location_url) . '">' . esc_html($location['name']) . '</a>';
        $output .= '</li>';
    }
    
    $output .= '</ul>';
    $output .= '</div>';
    
    return $output;
}

/**
 * Registrer innstillinger for kursvisning
 */
function register_course_display_settings() {
    register_setting('general', 'ka_courses_per_page', array(
        'type' => 'integer',
        'default' => 5,
        'sanitize_callback' => 'absint'
    ));

    add_settings_section(
        'ka_course_display_section',
        'Kursvisning',
        null,
        'general'
    );

    add_settings_field(
        'ka_courses_per_page',
        'Antall kurs per side',
        'ka_courses_per_page_callback',
        'general',
        'ka_course_display_section'
    );
}
add_action('admin_init', 'register_course_display_settings');

/**
 * Callback for innstillingsfeltet
 */
function ka_courses_per_page_callback() {
    $value = get_option('ka_courses_per_page', 5);
    echo '<input type="number" name="ka_courses_per_page" value="' . esc_attr($value) . '" min="1" max="50" />';
    echo '<p class="description">Velg standard antall kurs som skal vises per side (1-50)</p>';
}

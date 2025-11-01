<?php

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
                'course_days' => get_post_meta($selected_coursedate, 'course_days', true),
                'language' => get_post_meta($selected_coursedate, 'course_language', true),
                'button_text' => get_post_meta($selected_coursedate, 'course_button_text', true),
                'signup_url' => get_post_meta($selected_coursedate, 'course_signup_url', true),
                'coursedatemissing' => $coursedatemissing,
                'is_full' => get_post_meta($selected_coursedate, 'course_isFull', true) || get_post_meta($selected_coursedate, 'course_markedAsFull', true),
                'show_registration' => get_post_meta($selected_coursedate, 'course_showRegistrationForm', true),
                'course_location_room' => get_post_meta($selected_coursedate, 'course_location_room', true),
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
    
    $terms = wp_get_post_terms($post_id, 'ka_coursecategory', array('fields' => 'slugs'));
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
    $all_coursedates = [];

    if (!empty($related_coursedate) && is_array($related_coursedate)) {
        foreach ($related_coursedate as $coursedate_id) {
            // Skip if coursedate_id is empty or invalid
            if (empty($coursedate_id) || !get_post($coursedate_id)) {
                continue;
            }
            
            if (has_hidden_terms($coursedate_id)) {
                continue;
            }

            $course_id = get_post_meta($coursedate_id, 'related_course', true);
            if ($course_id && has_hidden_terms($course_id)) {
                continue;
            }

            $course_first_date = get_post_meta($coursedate_id, 'course_first_date', true);
            $formatted_first_date = ka_format_date($course_first_date);

            $coursedate_data = [
                'id' => $coursedate_id,
                'title' => get_the_title($coursedate_id),
                'course_title' => get_post_meta($coursedate_id, 'course_title', true),
                'first_date' => $formatted_first_date,
                'last_date' => ka_format_date(get_post_meta($coursedate_id, 'course_last_date', true)),
                'price' => get_post_meta($coursedate_id, 'course_price', true),
                'location' => get_post_meta($coursedate_id, 'course_location', true),
                'duration' => get_post_meta($coursedate_id, 'course_duration', true),
                'time' => get_post_meta($coursedate_id, 'course_time', true),
                'course_days' => get_post_meta($coursedate_id, 'course_days', true),
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
                'course_location_room' => get_post_meta($coursedate_id, 'course_location_room', true),
            ];

            // Legg til alle kursdatoer i hovedarrayen
            $all_coursedates[] = $coursedate_data;
        }

        // Sorter kursdatoer: først de med dato (sortert etter dato), deretter de uten dato
        if (!empty($all_coursedates)) {
            usort($all_coursedates, function ($a, $b) {
                // Hvis begge har dato, sorter etter dato
                if (!empty($a['first_date']) && !empty($b['first_date'])) {
                    return strtotime($a['first_date']) - strtotime($b['first_date']);
                }
                // Hvis bare en har dato, sett den med dato først
                if (!empty($a['first_date'])) return -1;
                if (!empty($b['first_date'])) return 1;
                // Hvis ingen har dato, behold original rekkefølge
                return 0;
            });
        }
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
function get_course_dates_query($per_page = 10, $current_page = 1) {
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
            // Bruk kun taksonomi-baserte lokasjoner (ikke fritekst)
            $location_query[] = [
                'key' => 'course_location',
                'value' => $location,
                'compare' => '='
            ];
        }
        $meta_query[] = $location_query;
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
    
    // Legg til måned filter med årstall-støtte
    if (!empty($months)) {
        $month_query = ['relation' => 'OR'];
        foreach ($months as $month_year) {
            // Parse måned+år format (MMYYYY)
            if (strlen($month_year) === 6 && is_numeric($month_year)) {
                $month = substr($month_year, 0, 2);
                $year = substr($month_year, 2, 4);
                
                // Forenklet: bare bruk course_first_date med dato-intervall
                $month_query[] = [
                    'key' => 'course_first_date',
                    'value' => [
                        $year . '-' . sprintf('%02d', $month) . '-01 00:00:00',
                        $year . '-' . sprintf('%02d', $month) . '-31 23:59:59'
                    ],
                    'compare' => 'BETWEEN',
                    'type' => 'DATETIME'
                ];
            } else {
                // Fallback for gamle format (bare måned)
                $month_query[] = [
                    'key' => 'course_month',
                    'value' => $month_year,
                    'compare' => '='
                ];
            }
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

    // Viktig: Ikke bruk taksonomi-filter for lokasjon her, da ikke alle coursedates
    // nødvendigvis har course_location-term satt. Vi baserer oss på meta_query over.
    
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

    // Hvis vi har skjulte kategorier, legg til en enkel NOT IN query
    if (!empty($hidden_categories) && !is_wp_error($hidden_categories)) {
        $tax_query[] = [
            'taxonomy' => 'ka_coursecategory',
            'field' => 'term_id',
            'terms' => $hidden_categories,
            'operator' => 'NOT IN'
        ];
    }
    
    // Legg til kategori filter hvis valgt
    if (!empty($categories)) {
        // Bruk hierarkisk kategorifiltrering
        $hierarchical_categories = get_hierarchical_category_filter($categories);
        
        if (!empty($hierarchical_categories)) {
            $tax_query[] = [
                'taxonomy' => 'ka_coursecategory',
                'field' => 'slug',
                'terms' => $hierarchical_categories,
                'operator' => 'IN'
            ];
        }
    }
    
    // Legg til instruktør filter
    if (!empty($instructors)) {
        $tax_query[] = [
            'taxonomy' => 'ka_instructors',
            'field' => 'slug',
            'terms' => $instructors,
            'operator' => 'IN'
        ];
    }
    
    // Bygg WP_Query args
    $query_args = [
        'post_type' => 'ka_coursedate',
        'posts_per_page' => $per_page,
        'paged' => $current_page,
        'meta_query' => $meta_query,
        'tax_query' => $tax_query,
        'suppress_filters' => false,
        'post_status' => 'publish'
    ];

    

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
    try {
        $query = new WP_Query($query_args);
    } catch (Exception $e) {
        error_log('QUERY DEBUG: WP_Query failed with error: ' . $e->getMessage());
        error_log('QUERY DEBUG: Error trace: ' . $e->getTraceAsString());
        throw $e;
    }
    
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
        'post_type'      => 'ka_course',
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
        $design_options = get_option('design_option_name');
        $placeholder_image = !empty($design_options['ka_plassholderbilde_kurs'])
            ? $design_options['ka_plassholderbilde_kurs']
            : rtrim(KURSAG_PLUGIN_URL, '/') . '/assets/images/placeholder-kurs.jpg';

        $course_info = [
            'title'      => get_the_title($course_id),
            'permalink'  => get_permalink($course_id),
            'thumbnail'  => get_the_post_thumbnail_url($course_id, 'thumbnail') ?: $placeholder_image,
            'thumbnail-medium'  => get_the_post_thumbnail_url($course_id, 'medium') ?: $placeholder_image,
            'thumbnail-full'  => get_the_post_thumbnail_url($course_id, 'full') ?: $placeholder_image,
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
        'post_type'      => 'ka_coursedate',
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
 * Get all available months from course dates with year support
 * 
 * @return array Array of month objects with name and number
 */
function get_course_months() {
    $args = [
        'post_type'      => 'ka_coursedate',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ];

    $coursedates = get_posts($args);
    $month_terms = [];
    $current_year = (int) date('Y');

    foreach ($coursedates as $post_id) {
        $meta_month = get_post_meta($post_id, 'course_month', true);
        $first_date = get_post_meta($post_id, 'course_first_date', true);
        
        if (!empty($meta_month) && is_numeric($meta_month) && $meta_month >= 1 && $meta_month <= 12) {
            $month_num = (int) $meta_month;
            
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
                $display_name = ucfirst(date_i18n('F', strtotime("2024-{$meta_month}-01")));
            } else {
                $display_name = ucfirst(date_i18n('F', strtotime("2024-{$meta_month}-01"))) . ' ' . $year;
            }
            
            $month_terms[] = (object) [
                'slug' => $month_year_key,
                'name' => $display_name,
                'value' => $month_year_key,
                'month' => $month_num,
                'year' => $year,
                'sort_key' => $year * 100 + $month_num
            ];
        }
    }

    // Sorter månedene kronologisk basert på år og måned
    usort($month_terms, function($a, $b) {
        return $a->sort_key - $b->sort_key;
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

    // Spesiell håndtering for ka_course_location taksonomi
    if ($taxonomy === 'ka_course_location') {
        $default_args = [
            'post_type'      => 'ka_course',
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

    // Standard håndtering for andre taksonomier (ka_coursecategory og ka_instructors)
    $default_args = [
        'post_type'      => 'ka_course',
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
            if (isset($tax_query['taxonomy']) && $tax_query['taxonomy'] !== 'ka_course_location') {
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
        $location_terms = wp_get_object_terms($post_id, 'ka_course_location');
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
            'post_type' => 'ka_course',
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
            $location_terms = wp_get_object_terms($main_course[0]->ID, 'ka_course_location');
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
            'post_type' => 'ka_course',
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

    // Bygg map fra lokasjonsnavn -> underkurs-permalink for å unngå feil slug (f.eks. baerum vs baerum-sandvika)
    // Dette prioriterer faktisk barn-innleggets permalenke fremfor taksonomi-slug.
    $child_location_links = array();
    $parent_main_course_id = ($is_parent_course === 'yes') ? get_post_meta($post_id, 'main_course_id', true) : get_post_meta($post_id, 'main_course_id', true);

    // Hvis vi står på et foreldrekurs har det meta 'is_parent_course' = yes og deler main_course_id med barna
    if ($is_parent_course === 'yes') {
        $parent_post_id = $post_id;
        // main_course_id på foreldrekurset peker på eksternt ID; vi bruker den for å finne barna
        $parent_main_course_id = get_post_meta($post_id, 'main_course_id', true);
    } else if (!empty($main_course)) {
        $parent_post_id = $main_course[0]->ID;
    } else {
        $parent_post_id = 0;
    }

    if (!empty($parent_main_course_id)) {
        $child_courses = get_posts(array(
            'post_type' => 'ka_course',
            'post_status' => array('publish', 'draft'),
            'posts_per_page' => -1,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => 'main_course_id',
                    'value' => $parent_main_course_id,
                    'compare' => '='
                ),
                array(
                    'key' => 'is_parent_course',
                    'compare' => 'NOT EXISTS'
                )
            )
        ));

        foreach ($child_courses as $child) {
            $sub_loc = get_post_meta($child->ID, 'sub_course_location', true);
            if (!empty($sub_loc)) {
                $child_location_links[$sub_loc] = get_permalink($child->ID);
            }
        }
    }
    
    // Start HTML output
    $output = '<div class="course-locations-list">';
    $output .= '<ul class="location-tabs">';
    
    // Legg til "Alle" link
    $output .= '<li class="' . ($is_parent_course === 'yes' ? 'active' : '') . '">';
    $output .= '<a href="' . esc_url($main_course_url) . '" class="button-filter">Alle</a>';
    $output .= '</li>';
    
    // Legg til alle lokasjoner
    foreach ($locations as $location) {
        $is_active = ($current_location === $location['name']);
        // Bruk barn-innleggets permalink hvis vi har en match på lokasjonsnavn; ellers fallback til taxonomi-slug
        if (isset($child_location_links[$location['name']])) {
            $location_url = $child_location_links[$location['name']];
        } else {
            $location_url = $main_course_url . $location['slug'] . '/';
        }
        
        $output .= '<li class="' . ($is_active ? 'active' : '') . '">';
        $output .= '<a href="' . esc_url($location_url) . '" class="button-filter">' . esc_html($location['name']) . '</a>';
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

/**
 * Henter alle unike lokasjoner med tilhørende fritekst for et kurs.
 * 
 * @param array $related_coursedate Array med coursedate IDs.
 * @return array Array med lokasjoner og fritekst.
 */
function get_course_locations_with_freetext($related_coursedate) {
    $locations = [];
    
    if (!empty($related_coursedate) && is_array($related_coursedate)) {
        foreach ($related_coursedate as $coursedate_id) {
            // Skip hvis coursedate_id er tom eller ugyldig
            if (empty($coursedate_id) || !get_post($coursedate_id)) {
                continue;
            }

            $location = get_post_meta($coursedate_id, 'course_location', true);
            $location_freetext = get_post_meta($coursedate_id, 'course_location_freetext', true);

            // Bare legg til hvis både location og freetext er satt
            if (!empty($location) && !empty($location_freetext)) {
                $key = $location . ' - ' . $location_freetext;
                $locations[$key] = [
                    'location' => $location,
                    'freetext' => $location_freetext
                ];
            }
        }
    }
    
    // Sorter lokasjonene alfabetisk
    ksort($locations);
    
    return array_values($locations);
}

/**
 * Henter toppnivå kurskategorier (foreldrekategorier) som brukes i kurslisten
 * 
 * @param WP_Query $query The current query to analyze
 * @return array Array of top-level category objects with name, slug, and count
 */
function get_top_level_categories_from_query($query) {
    $categories = [];
    $category_counts = [];
    
    if (!$query || !$query->have_posts()) {
        return $categories;
    }
    
    // Hent alle kurs fra spørringen
    $posts = $query->posts;
    
    foreach ($posts as $post) {
        // Hent kurskategorier for hvert kurs
        $post_categories = wp_get_object_terms($post->ID, 'ka_coursecategory');
        
        if (!is_wp_error($post_categories) && !empty($post_categories)) {
            // For hvert kurs, legg til alle kategorier det tilhører
            foreach ($post_categories as $category) {
                $slug = $category->slug;
                if (!isset($category_counts[$slug])) {
                    $category_counts[$slug] = [
                        'name' => $category->name,
                        'slug' => $slug,
                        'count' => 0
                    ];
                }
                $category_counts[$slug]['count']++;
            }
        }
    }
    
    // Konverter til array og sorter etter navn
    $categories = array_values($category_counts);
    usort($categories, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
    
    return $categories;
}

/**
 * Håndter hierarkisk kategorifiltrering
 * 
 * Logikk:
 * 1. Hvis bare forelder er valgt: vis alle kurs i forelder og alle barn
 * 2. Hvis forelder + barn er valgt: vis kun kurs i de valgte barna
 * 3. Hvis bare barn er valgt: vis kun kurs i de valgte barna
 * 4. Hvis flere barn under samme forelder er valgt: vis kurs i alle valgte barn
 * 
 * @param array $selected_categories Array av valgte kategorier (slugs)
 * @return array Array av kategorier som skal inkluderes i query
 */
function get_hierarchical_category_filter($selected_categories) {
    if (empty($selected_categories)) {
        return [];
    }
    
    // Hent alle kategorier med hierarki-informasjon
    $all_categories = get_terms([
        'taxonomy' => 'ka_coursecategory',
        'hide_empty' => false,
        'hierarchical' => true,
        'orderby' => 'menu_order',
        'order' => 'ASC'
    ]);
    
    if (is_wp_error($all_categories) || empty($all_categories)) {
        return $selected_categories;
    }
    
    // Lag en mapping av slug til term_id og parent, samt term_id til slug
    $category_map = [];
    $term_id_to_slug = [];
    foreach ($all_categories as $category) {
        $category_map[$category->slug] = [
            'term_id' => $category->term_id,
            'parent' => $category->parent,
            'slug' => $category->slug
        ];
        $term_id_to_slug[$category->term_id] = $category->slug;
    }
    
    // Identifiser foreldre og barn
    $selected_parents = [];
    $selected_children = [];
    $children_of_selected_parents = [];
    $children_by_parent_slug = [];
    
    foreach ($selected_categories as $slug) {
        if (!isset($category_map[$slug])) {
            continue;
        }
        
        $category_info = $category_map[$slug];
        
        if ($category_info['parent'] == 0) {
            // Dette er en forelder
            $selected_parents[] = $slug;
            
            // Finn alle barn til denne forelderen
            $children = get_terms([
                'taxonomy' => 'ka_coursecategory',
                'hide_empty' => false,
                'parent' => $category_info['term_id']
            ]);
            
            if (!is_wp_error($children)) {
                foreach ($children as $child) {
                    $children_of_selected_parents[] = $child->slug;
                    $children_by_parent_slug[$slug][] = $child->slug;
                }
            }
        } else {
            // Dette er et barn
            $selected_children[] = $slug;
        }
    }
    
    // Bestem hvilke kategorier som skal inkluderes
    $categories_to_include = [];
    
    // Hjelpefunksjon: inkluder barn og deres eventuelle barnebarn
    $include_child_with_descendants = function(string $child_slug) use (&$categories_to_include, $category_map) {
        $categories_to_include[] = $child_slug;
        if (isset($category_map[$child_slug])) {
                $child_children = get_terms([
                'taxonomy' => 'ka_coursecategory',
                'hide_empty' => false,
                'parent' => $category_map[$child_slug]['term_id']
            ]);
            if (!is_wp_error($child_children)) {
                foreach ($child_children as $grandchild) {
                    $categories_to_include[] = $grandchild->slug;
                }
            }
        }
    };
    
    // Bygg opp mapping over valgte barn gruppert per forelder-term_id
    $selected_children_by_parent_id = [];
    foreach ($selected_children as $child_slug) {
        if (isset($category_map[$child_slug])) {
            $parent_id = $category_map[$child_slug]['parent'];
            if ($parent_id) {
                $selected_children_by_parent_id[$parent_id][] = $child_slug;
            }
        }
    }

    // Inkluder foreldre- og barnevalg i henhold til reglene
    if (!empty($selected_parents)) {
        foreach ($selected_parents as $parent_slug) {
            $parent_id = $category_map[$parent_slug]['term_id'];
            $children_selected_under_parent = isset($selected_children_by_parent_id[$parent_id]) ? $selected_children_by_parent_id[$parent_id] : [];

            if (!empty($children_selected_under_parent)) {
                // Forelder + minst ett barn av samme forelder valgt: ta KUN de valgte barna (og deres undernivå)
                foreach ($children_selected_under_parent as $child_slug) {
                    $include_child_with_descendants($child_slug);
                }
            } else {
                // Forelder valgt uten noen valgte barn: ta forelder + alle dens barn
                $categories_to_include[] = $parent_slug;
                if (!empty($children_by_parent_slug[$parent_slug])) {
                    foreach ($children_by_parent_slug[$parent_slug] as $child_slug) {
                        $categories_to_include[] = $child_slug;
                    }
                }
            }
        }
    }

    // Inkluder valgte barn der forelder IKKE er valgt i det hele tatt
    if (!empty($selected_children)) {
        foreach ($selected_children as $child_slug) {
            $parent_id = isset($category_map[$child_slug]) ? $category_map[$child_slug]['parent'] : 0;
            $parent_slug = $parent_id && isset($term_id_to_slug[$parent_id]) ? $term_id_to_slug[$parent_id] : '';
            $parent_is_selected = $parent_slug && in_array($parent_slug, $selected_parents, true);
            if (!$parent_is_selected) {
                $include_child_with_descendants($child_slug);
            }
        }
    }
    
    // Merk: Kombinasjonen av reglene over dekker tilfeller med både foreldre og barn valgt
    
    // Fjern duplikater og returner
    return array_unique($categories_to_include);
}

/**
 * Hent antall kurs for hvert filtervalg basert på aktive filtre
 * 
 * @param string $filter_type Type filter (categories, locations, instructors, language, months)
 * @param array $active_filters Aktive filtre fra URL
 * @return array Array med term_slug => count mapping
 */
function get_filter_value_counts($filter_type, $active_filters = []) {
    // Fjern filteret vi sjekker fra aktive filtre for å unngå sirkulær referanse
    $test_filters = $active_filters;
    
    switch ($filter_type) {
        case 'categories':
            unset($test_filters['k']);
            break;
        case 'locations':
            unset($test_filters['sted']);
            break;
        case 'instructors':
            unset($test_filters['i']);
            break;
        case 'language':
            unset($test_filters['sprak']);
            break;
        case 'months':
            unset($test_filters['mnd']);
            break;
    }
    
    // Hent alle termer for denne filtertypen
    $terms = [];
    switch ($filter_type) {
        case 'categories':
            $terms = function_exists('get_filtered_terms_for_context') ? get_filtered_terms_for_context('ka_coursecategory') : get_filtered_terms('ka_coursecategory');
            break;
        case 'locations':
            $terms = get_terms([
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
            break;
        case 'instructors':
            $terms = get_filtered_terms('ka_instructors');
            break;
        case 'language':
            $terms = get_filtered_languages();
            break;
        case 'months':
            $terms = get_filtered_months();
            break;
    }
    
    if (empty($terms) || is_wp_error($terms)) {
        return [];
    }
    
    $counts = [];
    
    // For hver term, sjekk hvor mange kurs som matcher med de aktive filtrene
    foreach ($terms as $term) {
        $term_value = '';
        
        // Hent riktig verdi basert på filtertype
        switch ($filter_type) {
            case 'categories':
                $term_value = $term->slug;
                break;
            case 'locations':
                $term_value = $term->name; // Bruk navn for locations
                break;
            case 'instructors':
                $term_value = $term->slug;
                break;
            case 'language':
                $term_value = $term; // Språk er allerede en string
                break;
            case 'months':
                $term_value = $term['value']; // Måneder har value-felt
                break;
        }
        
        // Legg til denne termen til test-filtrene
        $test_filters_with_term = $test_filters;
        switch ($filter_type) {
            case 'categories':
                $test_filters_with_term['k'] = [$term_value];
                break;
            case 'locations':
                $test_filters_with_term['sted'] = [$term_value];
                break;
            case 'instructors':
                $test_filters_with_term['i'] = [$term_value];
                break;
            case 'language':
                $test_filters_with_term['sprak'] = [$term_value];
                break;
            case 'months':
                $test_filters_with_term['mnd'] = [$term_value];
                break;
        }
        
        // Kjør en test-query med disse filtrene
        $count = get_course_dates_query_for_count($test_filters_with_term);
        
        
        // Lagre count for denne termen
        $term_key = '';
        switch ($filter_type) {
            case 'categories':
                $term_key = $term->slug;
                break;
            case 'locations':
                $term_key = $term->name;
                break;
            case 'instructors':
                $term_key = $term->slug;
                break;
            case 'language':
                $term_key = $term;
                break;
            case 'months':
                $term_key = $term['value'];
                break;
        }
        
        $counts[$term_key] = $count;
    }
    
    return $counts;
}

/**
 * Hjelpefunksjon for å kjøre en query kun for å telle resultater
 * 
 * @param array $filters Filtre å bruke
 * @return int Antall posts som matcher filtrene
 */
function get_course_dates_query_for_count($filters) {
    // Lag en direkte spørring uten å bruke $_REQUEST
    $args = [
        'post_type' => 'ka_coursedate',
        'post_status' => 'publish', // Kun publiserte coursedates
        'posts_per_page' => -1, // Hent alle for å telle
        'fields' => 'ids', // Kun ID-er for raskere telling

        'meta_query' => [
            'relation' => 'AND',
            // Ekskluder skjulte coursedates
            [
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
        ]
    ];
    
    // Legg til måned filter hvis det er spesifisert
    if (!empty($filters['mnd'])) {
        $months = is_array($filters['mnd']) ? $filters['mnd'] : [$filters['mnd']];
        $month_query = ['relation' => 'OR'];
        
        foreach ($months as $month_year) {
            if (strlen($month_year) === 6 && is_numeric($month_year)) {
                $month = substr($month_year, 0, 2);
                $year = substr($month_year, 2, 4);
                
                // Forenklet: bare bruk course_first_date med dato-intervall
                $month_query[] = [
                    'key' => 'course_first_date',
                    'value' => [
                        $year . '-' . sprintf('%02d', $month) . '-01 00:00:00',
                        $year . '-' . sprintf('%02d', $month) . '-31 23:59:59'
                    ],
                    'compare' => 'BETWEEN',
                    'type' => 'DATETIME'
                ];
            } else {
                // Fallback for gamle format (bare måned)
                $month_query[] = [
                    'key' => 'course_month',
                    'value' => $month_year,
                    'compare' => '='
                ];
            }
        }
        $args['meta_query'][] = $month_query;
    }
    
    // Legg til kategori filter hvis det er spesifisert
    if (!empty($filters['k'])) {
        $categories = is_array($filters['k']) ? $filters['k'] : [$filters['k']];
        
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
        
        $args['tax_query'] = ['relation' => 'AND'];
        
        // Hvis vi har skjulte kategorier, legg til en enkel NOT IN query
        if (!empty($hidden_categories) && !is_wp_error($hidden_categories)) {
            $args['tax_query'][] = [
                'taxonomy' => 'ka_coursecategory',
                'field' => 'term_id',
                'terms' => $hidden_categories,
                'operator' => 'NOT IN'
            ];
        }
        
        // Legg til valgte kategorier
        $args['tax_query'][] = [
            'taxonomy' => 'ka_coursecategory',
            'field' => 'slug',
            'terms' => $categories,
            'operator' => 'IN'
        ];
    } else {
        // Hvis ingen kategori-filter er spesifisert, ekskluder skjulte kategorier
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
        
        if (!empty($hidden_categories) && !is_wp_error($hidden_categories)) {
            $args['tax_query'] = [
                'relation' => 'AND',
                [
                    'taxonomy' => 'ka_coursecategory',
                    'field' => 'term_id',
                    'terms' => $hidden_categories,
                    'operator' => 'NOT IN'
                ]
            ];
        }
    }
    
    // Legg til lokasjon filter hvis det er spesifisert
    if (!empty($filters['sted'])) {
        $locations = is_array($filters['sted']) ? $filters['sted'] : [$filters['sted']];
        $location_query = ['relation' => 'OR'];
        foreach ($locations as $location) {
            $location_query[] = [
                'key' => 'course_location',
                'value' => $location,
                'compare' => '='
            ];
        }
        $args['meta_query'][] = $location_query;
    }
    
    // Legg til instruktør filter hvis det er spesifisert
    if (!empty($filters['i'])) {
        $instructors = is_array($filters['i']) ? $filters['i'] : [$filters['i']];
        if (!isset($args['tax_query'])) {
            $args['tax_query'] = ['relation' => 'AND'];
        }
        $args['tax_query'][] = [
            'taxonomy' => 'ka_instructors',
            'field' => 'slug',
            'terms' => $instructors,
            'operator' => 'IN'
        ];
    }
    
    // Legg til språk filter hvis det er spesifisert
    if (!empty($filters['sprak'])) {
        $languages = is_array($filters['sprak']) ? $filters['sprak'] : [$filters['sprak']];
        $language_query = ['relation' => 'OR'];
        foreach ($languages as $language) {
            $language_query[] = [
                'key' => 'course_language',
                'value' => $language,
                'compare' => '='
            ];
        }
        $args['meta_query'][] = $language_query;
    }
    
    
    $query = new WP_Query($args);
    
    return $query->found_posts;
}

<?php

/**
 * Retrieve data for the first available coursedate.
 * For use in single-course.php.
 *
 * @param array $related_coursedate Array of related coursedate IDs.
 * @return array|null Returns an array with metadata for the selected coursedate, or null if none found.
 */
function get_selected_coursedate_data($related_coursedate) {
    error_log('get_selected_coursedate_data input: ' . print_r($related_coursedate, true));

    $earliest_date = null;
    $selected_coursedate = null;
    $coursedatemissing = true; // Anta at datoen mangler til vi finner en

    if (!empty($related_coursedate) && is_array($related_coursedate)) {
        foreach ($related_coursedate as $coursedate_id) {
            error_log('Processing coursedate_id: ' . $coursedate_id);

            // Skip if coursedate_id is empty or invalid
            if (empty($coursedate_id) || !get_post($coursedate_id)) {
                error_log('Invalid coursedate_id: ' . $coursedate_id);
                continue;
            }

            if (has_hidden_terms($coursedate_id)) {
                error_log('Coursedate has hidden terms: ' . $coursedate_id);
                continue;
            }

            $course_first_date = get_post_meta($coursedate_id, 'course_first_date', true);
            error_log('Course first date for ' . $coursedate_id . ': ' . $course_first_date);

            // Hvis course_first_date finnes, sammenlign for å finne den tidligste
            if (!empty($course_first_date)) {
                $coursedatemissing = false; // Gyldig dato funnet
                $current_date = new DateTime($course_first_date);
                if (!$earliest_date || $current_date < $earliest_date) {
                    $earliest_date = $current_date;
                    $selected_coursedate = $coursedate_id;
                }
            }
        }

        // Hvis ingen gyldig dato er funnet, velg den første tilgjengelige coursedate
        if (!$selected_coursedate && !empty($related_coursedate)) {
            $selected_coursedate = reset($related_coursedate);
        }

        if ($selected_coursedate) {
            $result = [
                'id' => $selected_coursedate,
                'title' => get_the_title($selected_coursedate),
                'first_date' => get_post_meta($selected_coursedate, 'course_first_date', true),
                'last_date' => get_post_meta($selected_coursedate, 'course_last_date', true),
                'price' => get_post_meta($selected_coursedate, 'course_price', true),
                'duration' => get_post_meta($selected_coursedate, 'course_duration', true),
                'time' => get_post_meta($selected_coursedate, 'course_time', true),
                'language' => get_post_meta($selected_coursedate, 'course_language', true),
                'button_text' => get_post_meta($selected_coursedate, 'course_button_text', true),
                'signup_url' => get_post_meta($selected_coursedate, 'course_signup_url', true),
                'coursedatemissing' => $coursedatemissing,
            ];
            error_log('Returning result: ' . print_r($result, true));
            return $result;
        }
    }

    $result = [
        'coursedatemissing' => $coursedatemissing, // Returner true hvis ingen gyldige datoer finnes
    ];
    error_log('Returning result: ' . print_r($result, true));
    return $result;
}

/**
 * Helper function to check if a post has hidden terms
 */
function has_hidden_terms($post_id) {
    //error_log("Checking hidden terms for post ID: " . $post_id);

    $terms = wp_get_post_terms($post_id, 'coursecategory', array('fields' => 'slugs'));
    if (is_wp_error($terms)) {
        //error_log("Error getting terms for post ID " . $post_id . ": " . $terms->get_error_message());
        return false;
    }

    $hidden_terms = unserialize(KURSAG_HIDDEN_TERMS);
    $intersection = array_intersect($terms, $hidden_terms);

    //error_log("Post ID: " . $post_id);
    //error_log("Terms found: " . print_r($terms, true));
    //error_log("Hidden terms: " . print_r($hidden_terms, true));
    //error_log("Intersection: " . print_r($intersection, true));
    //error_log("Is hidden: " . (!empty($intersection) ? "true" : "false"));

    return !empty($intersection);
}

/**
 * Retrieve and sort all coursedates by date.
 * For use in single-course.php.
 *
 * @param array $related_coursedate Array of related coursedate IDs.
 * @return array Returns an array of sorted coursedate data, including metadata and an indicator for missing first date.
 */
function get_all_sorted_coursedates($related_coursedate) {
    //error_log("Starting get_all_sorted_coursedates");
    //error_log("Related coursedates: " . print_r($related_coursedate, true));

    $all_coursedates = [];
    $coursedates_without_date = [];

    if (!empty($related_coursedate) && is_array($related_coursedate)) {
        foreach ($related_coursedate as $coursedate_id) {
            //error_log("Processing coursedate ID: " . $coursedate_id);

            // Sjekk om coursedate har skjulte termer
            if (has_hidden_terms($coursedate_id)) {
                //error_log("Coursedate " . $coursedate_id . " has hidden terms, skipping");
                continue;
            }

            // Sjekk tilknyttet kurs
            $course_id = get_post_meta($coursedate_id, 'related_course', true);
            //error_log("Related course ID: " . ($course_id ? $course_id : "none"));

            if ($course_id && has_hidden_terms($course_id)) {
                //error_log("Related course " . $course_id . " has hidden terms, skipping coursedate");
                continue;
            }

            $course_first_date = get_post_meta($coursedate_id, 'course_first_date', true);
            //error_log("Course first date: " . ($course_first_date ? $course_first_date : "none"));

            $coursedate_data = [
                'id' => $coursedate_id,
                'title' => get_the_title($coursedate_id),
                'course_title' => get_post_meta($coursedate_id, 'course_title', true),
                'first_date' => $course_first_date,
                'price' => get_post_meta($coursedate_id, 'course_price', true),
                'location' => get_post_meta($coursedate_id, 'course_location', true),
                'duration' => get_post_meta($coursedate_id, 'course_duration', true),
                'time' => get_post_meta($coursedate_id, 'course_time', true),
                'button_text' => get_post_meta($coursedate_id, 'course_button_text', true),
                'signup_url' => get_post_meta($coursedate_id, 'course_signup_url', true),
                'missing_first_date' => empty($course_first_date),
            ];

            if (empty($course_first_date)) {
                //error_log("Adding coursedate " . $coursedate_id . " to without_date array");
                $coursedates_without_date[] = $coursedate_data;
            } else {
                //error_log("Adding coursedate " . $coursedate_id . " to main array");
                $all_coursedates[] = $coursedate_data;
            }
        }

        //error_log("Before sorting - Number of coursedates with dates: " . count($all_coursedates));
        //error_log("Before sorting - Number of coursedates without dates: " . count($coursedates_without_date));

        // Sorter kursdatoer med dato
        usort($all_coursedates, function ($a, $b) {
            return strtotime($a['first_date']) - strtotime($b['first_date']);
        });

        // Legg til kursdatoer uten dato på slutten
        $all_coursedates = array_merge($all_coursedates, $coursedates_without_date);

        //error_log("Final number of coursedates: " . count($all_coursedates));
    }

    return $all_coursedates;
}

add_filter( 'posts_where', function (string $where, \WP_Query $query) {
	global $wpdb;
	if ($query->get('course_month')) {
		$months = is_array($query->get('course_month')) ? $query->get('course_month') : explode(',', $query->get('course_month'));
		$prepare = join(',', array_fill(0, count($months), '%s'));
		$where .= ' AND DATE_FORMAT(STR_TO_DATE(course_month.meta_value, "%d.%m.%Y"), "%m") IN (' . $wpdb->prepare($prepare, ...$months) . ')';
	}
	return $where;
}, 10, 2 );
add_filter( 'posts_join', function (string $join, WP_Query $query) {
	global $wpdb;
	if ($query->get('course_month')) {
		$join .= " LEFT JOIN $wpdb->postmeta as course_month ON ( wp_posts.ID = course_month.post_id AND course_month.meta_key = 'course_first_date' )";
	}
	if ( $query->get( 'orderby' ) == 'course_first_date' ) {
		$join .= " LEFT JOIN $wpdb->postmeta as pcfd ON ( wp_posts.ID = pcfd.post_id AND pcfd.meta_key = 'course_first_date' )";
	}
	return $join;
}, 10, 2 );
add_filter( 'posts_orderby', function ($orderby_clause, $query) {
	if ( $query->get( 'orderby' ) == 'course_first_date' ) {
		$format = empty($query->get('meta_custom_date_format')) ? "%d.%m.%Y" : $query->get('meta_custom_date_format');
		$order = $query->get( 'order' ) ?? 'ASC';
		$orderby_clause = "pcfd.meta_value IS NULL, STR_TO_DATE(pcfd.meta_value, \"$format\") $order";
	}

	return $orderby_clause;
}, 10, 2 );
add_filter( 'posts_clauses', function ($clauses) {
	global $wpdb;
	$original = [
		"$wpdb->postmeta.meta_key = 'course_first_date' AND CAST($wpdb->postmeta.meta_value AS DATE)"
	];
	$replaces = [
		"$wpdb->postmeta.meta_key = 'course_first_date' AND STR_TO_DATE($wpdb->postmeta.meta_value, \"%d.%m.%Y\")"
	];
	$clauses['where'] = str_replace($original, $replaces, $clauses['where']);
	return $clauses;
} );

/**
 * Retrieve and sort all coursedates by date.
 * For use in archive-course.php, course calendar.
 *
 * @return WP_Query Returns an array of sorted coursedate data, including metadata and an indicator for missing first date.
 */
function get_course_dates_query($args = []) {
	$current_page = isset($args['paged']) ? max(1, intval($args['paged'])) : (isset($_REQUEST['side']) ? max(1, intval($_REQUEST['side'])) : max(1, get_query_var('paged')));

	// Parameter mapping - database_field => incoming_parameter
	$param_mapping = [
		'course_location' => 'sted',
		'course_first_date' => 'dato',
		'coursecategory' => 'k',
		'instructors' => 'i',
		'course_language' => 'sprak',
		'course_price' => 'pris',
	];
	$search_param = 'sok';

	$default_args = [
		'post_type'      => 'coursedate',
		'posts_per_page' => 5,
		'paged'          => $current_page,
		'tax_query'      => ['relation' => 'AND'],
		'meta_query'     => ['relation' => 'AND'],
	];

	// Hent alle termer først
	$all_terms = get_terms([
		'taxonomy' => 'coursecategory',
		'fields' => 'slugs',
		'hide_empty' => false
	]);

	if (!is_wp_error($all_terms)) {
		$hidden_terms = unserialize(KURSAG_HIDDEN_TERMS);
		$visible_terms = array_diff($all_terms, $hidden_terms);

		if (!empty($visible_terms)) {
			$default_args['tax_query'][] = array(
				'relation' => 'OR',
				array(
					'taxonomy' => 'coursecategory',
					'operator' => 'NOT EXISTS'
				),
				array(
					'taxonomy' => 'coursecategory',
					'field'    => 'slug',
					'terms'    => $visible_terms,
					'operator' => 'IN'
				)
			);
		}
	}

	$query_args = wp_parse_args($args, $default_args);

	foreach ($param_mapping as $db_field => $param_name) {
		if (!array_key_exists($param_name, $_REQUEST) || empty($_REQUEST[$param_name])) {
			continue;
		}

		$param = $_REQUEST[$param_name];
		if (is_string($param)) {
			$param = explode(',', urldecode($param));
		}

		if (in_array($db_field, ['coursecategory', 'instructors'])) {
			$query_args['tax_query'][] = [
				'taxonomy' => $db_field,
				'field'    => 'slug',
				'terms'    => $param,
			];
		} else {
			if ($db_field === 'course_first_date') {
				if ( ! empty( $param['from'] ) && ! empty( $param['to'] ) ) {
					$from                       = date( 'Y-m-d', strtotime( $param['from'] ) );
					$to                         = date( 'Y-m-d', strtotime( $param['to'] ) );
					$query_args['meta_query'][] = [
						'key'     => $db_field,
						'value'   => [ $from, $to ],
						'compare' => 'BETWEEN',
						'type'    => 'DATE'
					];
				}
			} else if ($db_field === 'course_price') {
				if (! empty($param['from']) && !empty($param['to'])) {
					$query_args['meta_query'][] = [
						'key'     => $db_field,
						'value'   => [ $param['from'], $param['to'] ],
						'compare' => 'BETWEEN',
						'type'    => 'NUMERIC'
					];
				}
			} else {
				$query_args['meta_query'][] = [
					'key'     => $db_field,
					'value'   => $param,
					'compare' => 'IN',
				];
			}
		}
	}

	if (!empty($_REQUEST['mnd'])) {
		$query_args['course_month'] = $_REQUEST['mnd'];
	}

	if (!empty($_REQUEST[$search_param])) {
		$query_args['s'] = sanitize_text_field($_POST[$search_param]);
	}

	$sort = sanitize_text_field($_REQUEST['sort'] ?? '');
	$order = sanitize_text_field($_REQUEST['order'] ?? 'asc');

	switch ($sort) {
		case 'title':
			$query_args['orderby'] = 'title';
			$query_args['order'] = strtoupper($order);
			break;
		case 'price':
			$query_args['orderby'] = 'meta_value_num';
			$query_args['meta_key'] = 'course_price';
			$query_args['order'] = strtoupper($order);
			break;
		default:
		case 'date':
			$query_args['orderby'] = 'course_first_date';
			$query_args['order'] = strtoupper($order);
			break;
	}

	return new WP_Query($query_args);
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
            'thumbnail'  => get_the_post_thumbnail_url($course_id, 'thumbnail') ?: 'https://plugin.lanseres.no/wp-content/uploads/placeholder-kurs.jpg',
            'excerpt'    => get_the_excerpt($course_id),
        ];

        wp_reset_postdata(); // Rydd opp etter WP_Query
        return $course_info; // Returner informasjonen som en array
    }

    wp_reset_postdata(); // Rydd opp selv om ingen kurs ble funnet
    return null; // Returner null hvis ingen kurs ble funnet
}

function get_course_languages() {
    // ... eksisterende kode ...
}

/**
 * Get all available months from course dates
 * 
 * @return array Array of month objects with name and number
 */
function get_course_months() {
    global $wpdb;
    
    // Get all unique months from course dates
    $months = $wpdb->get_results(
        "SELECT DISTINCT 
            MONTH(STR_TO_DATE(meta_value, '%d.%m.%Y')) as month_num,
            DATE_FORMAT(STR_TO_DATE(meta_value, '%d.%m.%Y'), '%M') as month_name
        FROM {$wpdb->postmeta} 
        WHERE meta_key = 'course_first_date' 
        AND meta_value != '' 
        AND meta_value IS NOT NULL 
        ORDER BY month_num ASC"
    );
    
    // Convert to array of objects with name and number
    $formatted_months = array_map(function($month) {
        return (object) [
            'slug' => str_pad($month->month_num, 2, '0', STR_PAD_LEFT),
            'name' => ucfirst(date_i18n('F', strtotime($month->month_name . ' 1'))),
            'value' => str_pad($month->month_num, 2, '0', STR_PAD_LEFT) // Add value property for checkbox
        ];
    }, $months);
    
    return $formatted_months;
}

/* Check admin/post_types/visibility_management.php for visibility management
   tags/categories: 'skjult', 'skjul', 'usynlig', 'inaktiv', 'ikke-aktiv' are excluded from the main query.*/

/**
 * Modify WP_Query to handle month filtering
 */
add_filter('pre_get_posts', function($query) {
    if (!is_admin() && $query->is_main_query() && isset($_GET['mnd'])) {
        $months = explode(',', $_GET['mnd']);
        if (!empty($months)) {
            $query->set('course_month', $months);
        }
    }
    return $query;
});

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
    $selected_coursedate = null;
    $coursedatemissing = true; // Anta at datoen mangler til vi finner en

    if (!empty($related_coursedate) && is_array($related_coursedate)) {
        foreach ($related_coursedate as $coursedate_id) {
            $course_first_date = get_post_meta($coursedate_id, 'course_first_date', true);

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
            return [
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
        }
    }

    return [
        'coursedatemissing' => $coursedatemissing, // Returner true hvis ingen gyldige datoer finnes
    ];
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
            $course_first_date = get_post_meta($coursedate_id, 'course_first_date', true);

            // Legg til coursedate i listen, uavhengig av om first date er tilgjengelig
            $all_coursedates[] = [
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
                'missing_first_date' => empty($course_first_date), // Indikerer om first_date mangler
            ];
        }

        // Sorter coursedates etter first_date (null verdier vil sorteres til slutt)
        usort($all_coursedates, function ($a, $b) {
            $date_a = empty($a['first_date']) ? PHP_INT_MAX : strtotime($a['first_date']);
            $date_b = empty($b['first_date']) ? PHP_INT_MAX : strtotime($b['first_date']);
            return $date_a - $date_b;
        });
    }

    return $all_coursedates;
}

/**
 * Retrieve and sort all coursedates by date.
 * For use in archive-course.php, course calendar.
 *
 * @return array Returns an array of sorted coursedate data, including metadata and an indicator for missing first date.
 */
function get_course_dates_query($args = []) {
    $default_args = [
        'post_type'      => 'coursedate',
        'posts_per_page' => -1,
        'meta_key'       => 'course_first_date',
        'orderby'        => [
            'meta_value' => 'ASC',
            'course_first_date'   => 'DESC',
        ],
        'meta_query'     => [
            'relation' => 'OR',
            [
                'key'     => 'course_first_date',
                'compare' => 'EXISTS',
            ],
            [
                'key'     => 'course_first_date',
                'compare' => 'NOT EXISTS',
            ],
        ],
    ];

    $query_args = wp_parse_args($args, $default_args);
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
        ];

        wp_reset_postdata(); // Rydd opp etter WP_Query
        return $course_info; // Returner informasjonen som en array
    }

    wp_reset_postdata(); // Rydd opp selv om ingen kurs ble funnet
    return null; // Returner null hvis ingen kurs ble funnet
}



/**
 * Hide posts with specific terms in 'coursecategory' from the main query.
 *
 * Hides posts with terms 'skjult', 'skjul', 'usynlig', 'inaktiv', 'ikke-aktiv'.
 * 
 */
function exclude_hidden_kurs_posts($query) {
    // Check if it's the main query, not in the admin area, and not a single kurs page
    if (!is_admin() && $query->is_main_query() && !is_singular('kurs') && !$query->is_search()) {

        // Define the custom tax_query to exclude posts with specific terms in 'kurskategori'
        $tax_query = array(
            'relation' => 'AND', // Use AND to ensure all conditions are respected
            array(
                'taxonomy' => 'coursecategory',
                'field'    => 'slug',
                'terms'    => array('skjult', 'skjul', 'usynlig', 'inaktiv', 'ikke-aktiv'),
                'operator' => 'NOT IN',
            ),
        );

        // Get the existing tax_query if any
        $existing_tax_query = $query->get('tax_query');

        // Merge the existing tax_query (if any) with our custom one
        if (!empty($existing_tax_query)) {
            $tax_query = array_merge($existing_tax_query, $tax_query);
        }

        // Set the updated tax_query
        $query->set('tax_query', $tax_query);
    }
}
add_action('pre_get_posts', 'exclude_hidden_kurs_posts', 20);
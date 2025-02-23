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
        'get_course_dates' => true,
    ];

    $query_args = wp_parse_args($args, $default_args);
    
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
            $query_args['tax_query'] = array(
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
    
    $query = new WP_Query($query_args);
    
    // Sorter resultatene etter dato med korrekt datohåndtering
    if ($query->have_posts()) {
        $posts_with_date = [];
        $posts_without_date = [];
        
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $date_str = get_post_meta($post_id, 'course_first_date', true);
            
            if (!empty($date_str)) {
                // Konverter fra DD.MM.YYYY til timestamp
                $date_parts = explode('.', $date_str);
                if (count($date_parts) === 3) {
                    $timestamp = strtotime($date_parts[2] . '-' . $date_parts[1] . '-' . $date_parts[0]);
                    $posts_with_date[$post_id] = $timestamp;
                }
            } else {
                $posts_without_date[] = $post_id;
            }
        }
        wp_reset_postdata();
        
        // Sorter etter timestamp
        asort($posts_with_date);
        
        // Kombiner listene
        $sorted_posts = array_merge(array_keys($posts_with_date), $posts_without_date);
        
        if (!empty($sorted_posts)) {
            $query_args['post__in'] = $sorted_posts;
            $query_args['orderby'] = 'post__in';
            return new WP_Query($query_args);
        }
    }
    
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
/* Check admin/post_types/visibility_management.php for visibility management 
   tags/categories: 'skjult', 'skjul', 'usynlig', 'inaktiv', 'ikke-aktiv' are excluded from the main query.*/
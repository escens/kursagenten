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
            'meta_value' => 'ASC', // Sorter etter dato først
            'date'       => 'DESC', // Sorter deretter etter publiseringsdato
        ],
        'meta_query'     => [
            'relation' => 'OR',
            [
                'key'     => 'course_first_date',
                'compare' => 'EXISTS', // Prioriter innlegg med dato
            ],
            [
                'key'     => 'course_first_date',
                'compare' => 'NOT EXISTS', // Legg innlegg uten dato sist
            ],
        ],
    ];

    $query_args = wp_parse_args($args, $default_args);

    // Kjør spørringen
    $query = new WP_Query($query_args);

    // Returner objektet for debugging eller håndtering
    if ($query->have_posts()) {
        return $query; // Returner WP_Query-objektet
    }

    // Hvis ingen resultater finnes, returner et tomt array for konsistens
    return [];
}


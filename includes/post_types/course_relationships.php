<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Validerer en relasjon mellom et kurs og en kursdato
 * 
 * @param int $course_id ID til kurset
 * @param int $coursedate_id ID til kursdatoen
 * @return bool|WP_Error True hvis relasjonen er gyldig, WP_Error hvis ikke
 */
function validate_course_coursedate_relationship($course_id, $coursedate_id) {
    // Sjekk at begge postene eksisterer
    $course = get_post($course_id);
    $coursedate = get_post($coursedate_id);

    if (!$course || !$coursedate) {
        return new WP_Error(
            'invalid_post',
            'En eller begge postene eksisterer ikke',
            ['course_id' => $course_id, 'coursedate_id' => $coursedate_id]
        );
    }

    // Sjekk at postene har riktig post_type
    if ($course->post_type !== 'ka_course' || $coursedate->post_type !== 'ka_coursedate') {
        return new WP_Error(
            'invalid_post_type',
            'En eller begge postene har feil post_type',
            [
                'course_type' => $course->post_type,
                'coursedate_type' => $coursedate->post_type
            ]
        );
    }

    // Sjekk at postene er publiserte eller kladder
    if (!in_array($course->post_status, ['publish', 'draft']) || 
        !in_array($coursedate->post_status, ['publish', 'draft'])) {
        return new WP_Error(
            'invalid_post_status',
            'En eller begge postene har ugyldig status',
            [
                'course_status' => $course->post_status,
                'coursedate_status' => $coursedate->post_status
            ]
        );
    }

    return true;
}

/**
 * Logger endringer i relasjoner mellom kurs og kursdatoer
 * 
 * @param int $course_id ID til kurset
 * @param int $coursedate_id ID til kursdatoen
 * @param string $action Handling som ble utført (add/remove)
 * @param string $message Ekstra melding
 */
function log_relationship_change($course_id, $coursedate_id, $action, $message = '') {
    $log_message = sprintf(
        'Relasjonsendring: %s - Kurs ID: %d, Kursdato ID: %d - %s',
        $action,
        $course_id,
        $coursedate_id,
        $message
    );
    
    error_log($log_message);
}

/**
 * Oppretter eller oppdaterer en relasjon mellom et kurs og en kursdato
 * 
 * @param int $course_id ID til kurset
 * @param int $coursedate_id ID til kursdatoen
 * @return bool|WP_Error True hvis relasjonen ble opprettet/oppdatert, WP_Error hvis ikke
 */
function create_or_update_course_coursedate_relationship($course_id, $coursedate_id) {
    // Valider relasjonen
    $validation = validate_course_coursedate_relationship($course_id, $coursedate_id);
    if (is_wp_error($validation)) {
        return $validation;
    }

    // Oppdater relasjoner for kurset
    $current_coursedates = get_post_meta($course_id, 'ka_course_related_coursedate', true) ?: [];
    if (!is_array($current_coursedates)) {
        $current_coursedates = (array) $current_coursedates;
    }
    
    if (!in_array($coursedate_id, $current_coursedates)) {
        $current_coursedates[] = $coursedate_id;
        update_post_meta($course_id, 'ka_course_related_coursedate', array_unique($current_coursedates));
        log_relationship_change($course_id, $coursedate_id, 'add', 'Kursdato lagt til i kurs');
    }

    // Oppdater relasjoner for kursdatoen
    $current_courses = get_post_meta($coursedate_id, 'ka_course_related_course', true) ?: [];
    if (!is_array($current_courses)) {
        $current_courses = (array) $current_courses;
    }
    
    if (!in_array($course_id, $current_courses)) {
        $current_courses[] = $course_id;
        update_post_meta($coursedate_id, 'ka_course_related_course', array_unique($current_courses));
        log_relationship_change($course_id, $coursedate_id, 'add', 'Kurs lagt til i kursdato');
    }

    return true;
}

/**
 * Fjerner en relasjon mellom et kurs og en kursdato
 * 
 * @param int $course_id ID til kurset
 * @param int $coursedate_id ID til kursdatoen
 * @return bool|WP_Error True hvis relasjonen ble fjernet, WP_Error hvis ikke
 */
function remove_course_coursedate_relationship($course_id, $coursedate_id) {
    // Valider relasjonen
    $validation = validate_course_coursedate_relationship($course_id, $coursedate_id);
    if (is_wp_error($validation)) {
        return $validation;
    }

    // Fjern relasjon fra kurset
    $current_coursedates = get_post_meta($course_id, 'ka_course_related_coursedate', true) ?: [];
    if (is_array($current_coursedates)) {
        $current_coursedates = array_diff($current_coursedates, [$coursedate_id]);
        update_post_meta($course_id, 'ka_course_related_coursedate', array_values($current_coursedates));
        log_relationship_change($course_id, $coursedate_id, 'remove', 'Kursdato fjernet fra kurs');
    }

    // Fjern relasjon fra kursdatoen
    $current_courses = get_post_meta($coursedate_id, 'ka_course_related_course', true) ?: [];
    if (is_array($current_courses)) {
        $current_courses = array_diff($current_courses, [$course_id]);
        update_post_meta($coursedate_id, 'ka_course_related_course', array_values($current_courses));
        log_relationship_change($course_id, $coursedate_id, 'remove', 'Kurs fjernet fra kursdato');
    }

    return true;
}

/**
 * Rydder opp i ugyldige relasjoner
 * 
 * @return array Statistikk over oppryddingen
 */
function cleanup_invalid_relationships() {
    $stats = [
        'removed_course_relations' => 0,
        'removed_coursedate_relations' => 0,
        'errors' => []
    ];

    // Finn alle kurs med relasjoner
    $courses = get_posts([
        'post_type' => 'ka_course',
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key' => 'ka_course_related_coursedate',
                'compare' => 'EXISTS'
            ]
        ]
    ]);

    foreach ($courses as $course) {
        $coursedates = get_post_meta($course->ID, 'ka_course_related_coursedate', true) ?: [];
        if (!is_array($coursedates)) {
            $coursedates = (array) $coursedates;
        }

        foreach ($coursedates as $coursedate_id) {
            $validation = validate_course_coursedate_relationship($course->ID, $coursedate_id);
            if (is_wp_error($validation)) {
                remove_course_coursedate_relationship($course->ID, $coursedate_id);
                $stats['removed_course_relations']++;
                $stats['errors'][] = $validation->get_error_message();
            }
        }
    }

    // Finn alle kursdatoer med relasjoner
    $coursedates = get_posts([
        'post_type' => 'ka_coursedate',
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key' => 'ka_course_related_course',
                'compare' => 'EXISTS'
            ]
        ]
    ]);

    foreach ($coursedates as $coursedate) {
        $courses = get_post_meta($coursedate->ID, 'ka_course_related_course', true) ?: [];
        if (!is_array($courses)) {
            $courses = (array) $courses;
        }

        foreach ($courses as $course_id) {
            $validation = validate_course_coursedate_relationship($course_id, $coursedate->ID);
            if (is_wp_error($validation)) {
                remove_course_coursedate_relationship($course_id, $coursedate->ID);
                $stats['removed_coursedate_relations']++;
                $stats['errors'][] = $validation->get_error_message();
            }
        }
    }

    return $stats;
}

/**
 * Henter alle kursdatoer for et kurs
 * 
 * @param int $course_id ID til kurset
 * @return array Array med kursdatoer
 */
function get_course_coursedates($course_id) {
    $coursedate_ids = get_post_meta($course_id, 'ka_course_related_coursedate', true) ?: [];
    if (!is_array($coursedate_ids)) {
        $coursedate_ids = (array) $coursedate_ids;
    }

    return get_posts([
        'post_type' => 'ka_coursedate',
        'post__in' => $coursedate_ids,
        'posts_per_page' => -1,
        'orderby' => 'meta_value',
        'meta_key' => 'ka_course_first_date',
        'order' => 'ASC'
    ]);
}

/**
 * Henter alle kurs for en kursdato
 * 
 * @param int $coursedate_id ID til kursdatoen
 * @return array Array med kurs
 */
function get_coursedate_courses($coursedate_id) {
    $course_ids = get_post_meta($coursedate_id, 'ka_course_related_course', true) ?: [];
    if (!is_array($course_ids)) {
        $course_ids = (array) $course_ids;
    }

    return get_posts([
        'post_type' => 'ka_course',
        'post__in' => $course_ids,
        'posts_per_page' => -1
    ]);
} 
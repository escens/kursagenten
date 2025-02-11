<?php
// Full synkronisering av kurs
function kursagenten_sync_full_course_list() {
    $courses = kursagenten_get_course_list();

    if (empty($courses)) {
        error_log('No courses found during full sync.');
        return;
    }

    foreach ($courses as $course) {
        $post_id = wp_insert_post([
            'post_title' => sanitize_text_field($course['title']),
            'post_type' => 'kurs',
            'post_status' => 'publish',
        ]);

        if (is_wp_error($post_id)) {
            error_log('Failed to insert course: ' . $course['title']);
            continue;
        }

        // Legg til metainformasjon
        update_post_meta($post_id, 'kurs_id', $course['id']);
        update_post_meta($post_id, 'start_date', sanitize_text_field($course['startDate']));
        update_post_meta($post_id, 'end_date', sanitize_text_field($course['endDate']));
    }
}

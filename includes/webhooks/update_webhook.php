<?php
function kursagenten_update_single_course($enkeltkurs_id) {
    $course_details = kursagenten_get_course_details($enkeltkurs_id);

    if (empty($course_details)) {
        error_log('No course details found for ID: ' . $enkeltkurs_id);
        return;
    }

    $existing_post = get_posts([
        'post_type' => 'enkeltkurs',
        'meta_key' => 'enkeltkurs_id',
        'meta_value' => $enkeltkurs_id,
        'numberposts' => 1,
    ]);

    $post_id = $existing_post ? $existing_post[0]->ID : wp_insert_post([
        'post_title' => sanitize_text_field($course_details['title']),
        'post_type' => 'enkeltkurs',
        'post_status' => 'publish',
    ]);

    if (is_wp_error($post_id)) {
        error_log('Failed to insert/update enkeltkurs: ' . $course_details['title']);
        return;
    }

    // Oppdater metainformasjon
    update_post_meta($post_id, 'enkeltkurs_id', $enkeltkurs_id);
    update_post_meta($post_id, 'start_date', sanitize_text_field($course_details['startDate']));
    update_post_meta($post_id, 'end_date', sanitize_text_field($course_details['endDate']));
    update_post_meta($post_id, 'location', sanitize_text_field($course_details['location']));
}

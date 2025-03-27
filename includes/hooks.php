<?php
/**
 * WordPress hooks for Kursagenten
 *
 * @package kursagenten
 */

/**
 * Bevar kurs metadata når et kurs oppdateres
 *
 * @param int $post_id Post ID
 * @param WP_Post $post Post object
 * @param bool $update Whether this is an existing post being updated
 */
function kursagenten_save_course_metadata($post_id, $post, $update) {
    // Ikke kjør for autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    
    // Ikke kjør for revisjoner
    if (wp_is_post_revision($post_id)) return;
    
    // Bevar eksisterende coursedate metadata
    $existing_coursedates = get_post_meta($post_id, 'course_related_coursedate', true);
    if ($update && empty($_POST['course_related_coursedate']) && !empty($existing_coursedates)) {
        update_post_meta($post_id, 'course_related_coursedate', $existing_coursedates);
    }
}
add_action('save_post_course', 'kursagenten_save_course_metadata', 10, 3); 
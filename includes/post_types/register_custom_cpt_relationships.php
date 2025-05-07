<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Defines relationships between courses and course dates
 */
function related_get_related_post_types($post_type) {
    $relationships = [
        'course' => ['coursedate'],
        'coursedate' => ['course']
    ];
    return $relationships[$post_type] ?? [];
}

add_action('save_post', 'related_save_relationships');
function related_save_relationships($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    if ('revision' === get_post_type($post_id)) return;

    $post_type = get_post_type($post_id);
    if (!in_array($post_type, ['course', 'coursedate'])) return;

    $related_post_types = related_get_related_post_types($post_type);

    foreach ($related_post_types as $related_post_type) {
        $meta_key = 'course_related_' . $related_post_type;
        
        // Hent nye relasjoner fra POST (hvis de finnes)
        $related_ids = isset($_POST['related_' . $related_post_type]) 
            ? array_map('intval', $_POST['related_' . $related_post_type]) 
            : null;

        // Hvis ingen nye relasjoner er sendt inn, behold eksisterende
        if ($related_ids === null) {
            continue;
        }

        // Hent eksisterende relasjoner
        $existing_relations = get_post_meta($post_id, $meta_key, true) ?: [];
        if (!is_array($existing_relations)) {
            $existing_relations = (array) $existing_relations;
        }

        // Finn relasjoner som skal fjernes
        $relations_to_remove = array_diff($existing_relations, $related_ids);
        foreach ($relations_to_remove as $relation_id) {
            if ($post_type === 'course') {
                remove_course_coursedate_relationship($post_id, $relation_id);
            } else {
                remove_course_coursedate_relationship($relation_id, $post_id);
            }
        }

        // Finn relasjoner som skal legges til
        $relations_to_add = array_diff($related_ids, $existing_relations);
        foreach ($relations_to_add as $relation_id) {
            if ($post_type === 'course') {
                create_or_update_course_coursedate_relationship($post_id, $relation_id);
            } else {
                create_or_update_course_coursedate_relationship($relation_id, $post_id);
            }
        }
    }
}

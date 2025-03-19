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
            : array();

        // Oppdater relasjoner for denne posten
        update_post_meta($post_id, $meta_key, $related_ids);

        // Håndter reverse relasjoner
        $reverse_meta_key = 'course_related_' . $post_type;
        
        // Fjern denne posten fra alle tidligere relasjoner
        $existing_relations = get_posts([
            'post_type' => $related_post_type,
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => [
                [
                    'key' => $reverse_meta_key,
                    'value' => $post_id,
                    'compare' => 'LIKE'
                ]
            ]
        ]);

        foreach ($existing_relations as $related_id) {
            $current_relations = get_post_meta($related_id, $reverse_meta_key, true);
            if (is_array($current_relations)) {
                $current_relations = array_diff($current_relations, [$post_id]);
                update_post_meta($related_id, $reverse_meta_key, array_values($current_relations));
            }
        }

        // Legg til denne posten i nye relasjoner
        foreach ($related_ids as $related_id) {
            $current_relations = get_post_meta($related_id, $reverse_meta_key, true) ?: [];
            if (!in_array($post_id, $current_relations)) {
                $current_relations[] = $post_id;
                update_post_meta($related_id, $reverse_meta_key, array_values($current_relations));
            }
        }
    }
}

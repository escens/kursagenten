<?php
// Creates bidirectional relationships between kurs, kursdato, and instruktor

add_action('add_meta_boxes', 'related_add_custom_metabox');
function related_add_custom_metabox() {
    add_meta_box('related_relationships_metabox', 'Relationships', 'related_render_metabox', ['course', 'instructor', 'coursedate'], 'side');
}

function related_render_metabox($post) {
    $post_type = $post->post_type;
    $related_post_types = related_get_related_post_types($post_type);

    foreach ($related_post_types as $related_post_type) {
        $related_posts = get_posts(['post_type' => $related_post_type, 'numberposts' => -1, 'post_status' => 'publish']);
        $related_ids = (array) get_post_meta($post->ID, 'course_related_' . $related_post_type, true);

        echo '<p>' . ucfirst($related_post_type) . ':</p>';
        foreach ($related_posts as $related_post) {
            echo '<input type="checkbox" name="related_' . $related_post_type . '[]" value="' . $related_post->ID . '"';
            echo in_array($related_post->ID, $related_ids) ? ' checked' : '';
            echo ' /> ' . esc_html($related_post->post_title) . '<br />';
        }
    }

    wp_nonce_field('related_save_relationships', 'related_relationships_nonce');
}


function related_get_related_post_types($post_type) {
    $relationships = [
        'course' => ['instructor', 'coursedate'],
        'instructor' => ['course', 'coursedate'],
        'coursedate' => ['course', 'instructor']
    ];
    return $relationships[$post_type] ?? [];
}

add_action('save_post', 'related_save_relationships');
function related_save_relationships($post_id) {
    if (!isset($_POST['related_relationships_nonce']) || !wp_verify_nonce($_POST['related_relationships_nonce'], 'related_save_relationships')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if ('revision' === get_post_type($post_id)) {
        return;
    }

    $post_type = get_post_type($post_id);
    $related_post_types = related_get_related_post_types($post_type);

    foreach ($related_post_types as $related_post_type) {
        $related_ids = isset($_POST['related_' . $related_post_type]) ? array_map('intval', $_POST['related_' . $related_post_type]) : [];
        update_post_meta($post_id, 'course_related_' . $related_post_type, $related_ids);

        // Reverse update
        foreach ($related_ids as $related_id) {
            $current_related = get_post_meta($related_id, 'course_related_' . $post_type, true) ?: [];
            if (!in_array($post_id, $current_related)) {
                $current_related[] = $post_id;
                update_post_meta($related_id, 'course_related_' . $post_type, $current_related);
            }
        }

        // Remove post_id from related CPTs that are no longer checked
        $all_related_posts = get_posts(['post_type' => $related_post_type, 'numberposts' => -1, 'fields' => 'ids']);
        foreach ($all_related_posts as $related_post_id) {
            if (!in_array($related_post_id, $related_ids)) {
                $current_related = get_post_meta($related_post_id, 'course_related_' . $post_type, true) ?: [];
                if (($key = array_search($post_id, $current_related)) !== false) {
                    unset($current_related[$key]);
                    update_post_meta($related_post_id, 'course_related_' . $post_type, $current_related);
                }
            }
        }
    }
}

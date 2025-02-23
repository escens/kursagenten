<?php
if (!defined('ABSPATH')) {
    exit;
}

// Creates bidirectional relationships between kurs, kursdato, and instruktor

add_action('add_meta_boxes', 'related_add_custom_metabox');
function related_add_custom_metabox() {
    add_meta_box('related_relationships_metabox', 'Relationships', 'related_render_metabox', ['course', 'instructor', 'coursedate'], 'side');
}

function related_render_metabox($post) {
    $post_type = $post->post_type;
    $related_post_types = related_get_related_post_types($post_type);

    foreach ($related_post_types as $related_post_type) {
        // Hent eksisterende relasjoner
        $meta_key = 'course_related_' . $related_post_type;
        $related_ids = get_post_meta($post->ID, $meta_key, true);
        
        // Debug logging
        error_log("Metabox for {$post->ID} - Meta key: {$meta_key}");
        error_log("Current related IDs: " . print_r($related_ids, true));
        
        // Sikre at related_ids er en array
        $related_ids = is_array($related_ids) ? $related_ids : array();

        // Hent alle publiserte innlegg av den relaterte typen
        $related_posts = get_posts([
            'post_type' => $related_post_type, 
            'numberposts' => -1, 
            'post_status' => 'publish'
        ]);

        echo '<p>' . ucfirst($related_post_type) . ':</p>';
        foreach ($related_posts as $related_post) {
            $checked = in_array($related_post->ID, $related_ids) ? ' checked' : '';
            printf(
                '<label><input type="checkbox" name="related_%s[]" value="%d"%s /> %s</label><br />',
                esc_attr($related_post_type),
                $related_post->ID,
                $checked,
                esc_html($related_post->post_title)
            );
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
    if (!isset($_POST['related_relationships_nonce']) || 
        !wp_verify_nonce($_POST['related_relationships_nonce'], 'related_save_relationships')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    if ('revision' === get_post_type($post_id)) return;

    $post_type = get_post_type($post_id);
    $related_post_types = related_get_related_post_types($post_type);

    foreach ($related_post_types as $related_post_type) {
        $meta_key = 'course_related_' . $related_post_type;
        
        // Hent nye relasjoner fra POST
        $related_ids = isset($_POST['related_' . $related_post_type]) 
            ? array_map('intval', $_POST['related_' . $related_post_type]) 
            : array();
            
        // Debug logging
        error_log("Saving relationships for post {$post_id}");
        error_log("Meta key: {$meta_key}");
        error_log("New related IDs: " . print_r($related_ids, true));

        // Oppdater relasjoner for denne posten
        update_post_meta($post_id, $meta_key, $related_ids);

        // Oppdater reverse relasjoner
        $reverse_meta_key = 'course_related_' . $post_type;
        
        // Først, fjern denne posten fra alle tidligere relasjoner
        $args = array(
            'post_type' => $related_post_type,
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => array(
                array(
                    'key' => $reverse_meta_key,
                    'value' => $post_id,
                    'compare' => 'LIKE'
                )
            )
        );
        
        $existing_relations = get_posts($args);
        foreach ($existing_relations as $related_id) {
            $current_relations = get_post_meta($related_id, $reverse_meta_key, true);
            if (is_array($current_relations)) {
                $current_relations = array_diff($current_relations, array($post_id));
                update_post_meta($related_id, $reverse_meta_key, array_values($current_relations));
            }
        }

        // Deretter, legg til denne posten i nye relasjoner
        foreach ($related_ids as $related_id) {
            $current_relations = get_post_meta($related_id, $reverse_meta_key, true);
            if (!is_array($current_relations)) {
                $current_relations = array();
            }
            if (!in_array($post_id, $current_relations)) {
                $current_relations[] = $post_id;
                update_post_meta($related_id, $reverse_meta_key, array_values($current_relations));
            }
        }
    }
}

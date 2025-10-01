<?php

// Register custom post type fields
function kurs_register_custom_fields() {
    // Custom fields for Kursdato CPT
    add_action('add_meta_boxes', function() {
        add_meta_box('kursdato_meta', 'Kursdato detaljer', 'kursdato_meta_callback', 'kursdato', 'normal', 'default');
    });

    function kursdato_meta_callback($post) {
        wp_nonce_field('save_kursdato_meta', 'kursdato_meta_nonce');

        $tid = get_post_meta($post->ID, '_kursdato_tid', true);
        $varighet = get_post_meta($post->ID, '_kursdato_varighet', true);
        $status = get_post_meta($post->ID, '_kursdato_status', true);

        echo '<p><label for="kursdato_tid">Tid: </label><input type="time" id="kursdato_tid" name="kursdato_tid" value="' . esc_attr($tid) . '" /></p>';
        echo '<p><label for="kursdato_varighet">Varighet: </label><input type="text" id="kursdato_varighet" name="kursdato_varighet" value="' . esc_attr($varighet) . '" /></p>';
        echo '<p><label for="kursdato_status">Status: </label><input type="text" id="kursdato_status" name="kursdato_status" value="' . esc_attr($status) . '" /></p>';
    }

    add_action('save_post', function($post_id) {
        if (!isset($_POST['kursdato_meta_nonce']) || !wp_verify_nonce($_POST['kursdato_meta_nonce'], 'save_kursdato_meta')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (isset($_POST['kursdato_tid'])) {
            update_post_meta($post_id, '_kursdato_tid', sanitize_text_field($_POST['kursdato_tid']));
        }
        if (isset($_POST['kursdato_varighet'])) {
            update_post_meta($post_id, '_kursdato_varighet', sanitize_text_field($_POST['kursdato_varighet']));
        }
        if (isset($_POST['kursdato_status'])) {
            update_post_meta($post_id, '_kursdato_status', sanitize_text_field($_POST['kursdato_status']));
        }
    });
}
add_action('init', 'kurs_register_custom_fields');
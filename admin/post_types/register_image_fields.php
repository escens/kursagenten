<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Lager et custom field på bilder for å merke om de har blitt hentet fra Kursagenten.
 * Disse bildene skjules i Media-biblioteket for å unngå at de brukes i innlegg og på sider.
 * Videre sjekkes dette feltet i scriptet ....... for å skjule disse bildene i Media, så de ikke blir brukt i innlegg og på sider.
 */

// Register hooks
add_action('admin_init', 'add_custom_image_meta_box');
add_action('attachment_submitbox_misc_actions', 'custom_attachment_meta_box_callback');
add_action('save_post', 'save_custom_image_meta_box_data');
add_action('edit_attachment', 'save_custom_image_meta_box_data');

/**
 * Add custom meta box to image attachments
 */
function add_custom_image_meta_box() {
    $post = get_post();

    if (!$post || $post->post_type !== 'attachment' || strpos($post->post_mime_type, 'image/') !== 0) {
        return;
    }

    add_meta_box(
        'kursbilde_meta_box',
        __('Kursbilde', 'kursagenten'),
        'custom_attachment_meta_box_callback',
        'attachment',
        'side',
        'low'
    );
}

/**
 * Render meta box content
 */
function custom_attachment_meta_box_callback($post) {
    if (!$post || !isset($post->ID)) {
        return;
    }

    $kursbilde = get_post_meta($post->ID, 'er_kursbilde', true);
    $status = $kursbilde ? __('Ja', 'kursagenten') : __('Nei', 'kursagenten');
    
    wp_nonce_field('save_custom_image_meta_box', 'custom_image_meta_box_nonce');
    
    echo '<div class="misc-pub-section misc-pub-kursagenten" style="pointer-events: none; opacity: 0.8;">';
    echo sprintf(
        '%s %s',
        __('Bilde hentet fra Kursagenten:', 'kursagenten'),
        esc_html($status)
    );
    echo '</div>';
}

/**
 * Save meta box data
 */
function save_custom_image_meta_box_data($post_id) {
    // Basic checks
    if (get_post_type($post_id) !== 'attachment') {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Security checks
    if (!isset($_POST['custom_image_meta_box_nonce']) || 
        !wp_verify_nonce($_POST['custom_image_meta_box_nonce'], 'save_custom_image_meta_box')) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Update meta
    $kursbilde = isset($_POST['kursbilde']) ? 1 : 0;
    update_post_meta($post_id, 'er_kursbilde', $kursbilde);
}


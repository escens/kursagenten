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
add_action('before_delete_post', 'handle_course_deletion', 10, 1);

/**
 * Add custom meta box to image attachments
 */
function add_custom_image_meta_box() {
    $post = get_post();

    if (!$post || $post->post_type !== 'attachment' || strpos($post->post_mime_type, 'image/') !== 0) {
        return;
    }

    add_meta_box(
        'course_image_meta_box',
        __('Course Image', 'kursagenten'),
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

    $is_course_image = get_post_meta($post->ID, 'is_course_image', true);
    $status = $is_course_image ? __('Ja', 'kursagenten') : __('Nei', 'kursagenten');
    
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

    $is_course_image = isset($_POST['is_course_image']) ? 1 : 0;
    update_post_meta($post_id, 'is_course_image', $is_course_image);
}

/**
 * Handle deletion of associated images when a course is deleted
 */
function handle_course_deletion($post_id) {
    error_log("=== Starting handle_course_deletion for post_id: $post_id ===");
    
    if (get_post_type($post_id) !== 'ka_course') {
        error_log("Not a course post type, skipping");
        return;
    }

    $thumbnail_id = get_post_thumbnail_id($post_id);
    error_log("Found thumbnail_id: " . ($thumbnail_id ? $thumbnail_id : 'none'));
    
    if ($thumbnail_id) {
        $is_course_image = get_post_meta($thumbnail_id, 'is_course_image', true);
        error_log("Image is_course_image: " . ($is_course_image ? 'yes' : 'no'));
        
        if ($is_course_image) {
            // Check if image is used by other courses
            $other_posts = get_posts([
                'post_type' => 'ka_course',
                'meta_query' => [
                    [
                        'key' => '_thumbnail_id',
                        'value' => $thumbnail_id
                    ]
                ],
                'post__not_in' => [$post_id],
                'posts_per_page' => -1
            ]);

            error_log("Number of other courses using this image: " . count($other_posts));
            
            if (empty($other_posts)) {
                error_log("Deleting image attachment: $thumbnail_id");
                $result = wp_delete_attachment($thumbnail_id, true);
                error_log("Delete result: " . ($result ? 'success' : 'failed'));
            } else {
                error_log("Image is still in use by other courses, not deleting");
            }
        }
    }
    
    error_log("=== Finished handle_course_deletion ===");
}

// Add filter to show correct status in admin
add_filter('attachment_fields_to_edit', 'modify_attachment_fields', 10, 2);

function modify_attachment_fields($form_fields, $post) {
    if (strpos($post->post_mime_type, 'image/') === 0) {
        $is_course_image = get_post_meta($post->ID, 'is_course_image', true);
        $form_fields['is_course_image'] = array(
            'label' => __('Bilde hentet fra Kursagenten', 'kursagenten'),
            'input' => 'html',
            'html' => $is_course_image ? __('Ja', 'kursagenten') : __('Nei', 'kursagenten'),
            'helps' => __('Dette feltet viser om bildet er automatisk hentet fra Kursagenten.', 'kursagenten')
        );
    }
    return $form_fields;
}


<?php

//Require files
require_once KURSAG_PLUGIN_DIR . '/includes/admin/post_types/register_post_types.php';
require_once KURSAG_PLUGIN_DIR . '/includes/admin/post_types/register_taxonomies.php';
require_once KURSAG_PLUGIN_DIR . '/includes/admin/post_types/register_custom_taxonomy_fields.php';
require_once KURSAG_PLUGIN_DIR . '/includes/admin/post_types/register_custom_kurs_fields.php';


// Helper functions
function taxonomy_image_field_scripts($hook) {
    if (in_array($hook, ['edit-tags.php', 'term.php'])) {  // Kun last scriptet på taksonomisider
        wp_enqueue_media();
        wp_enqueue_script('image-upload', plugins_url('../../assets/admin/js/image-upload.js', __FILE__), array('jquery'), null, true);
    }
}
add_action('admin_enqueue_scripts', 'taxonomy_image_field_scripts');





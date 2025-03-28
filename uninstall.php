<?php
// If uninstall not called from WordPress, then exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
$options_to_delete = array(
    'kag_bedriftsinfo_option_name',
    'kag_kursinnst_option_name',
    'kag_seo_option_name',
    'kag_avansert_option_name',
    'design_option_name',
    'kursagenten_template_style',
    'kursagenten_top_filters',
    'kursagenten_left_filters',
    'kursagenten_filter_types',
    'kursagenten_available_filters'
);

foreach ($options_to_delete as $option) {
    delete_option($option);
}

// Delete custom database table
global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}kursdato");

// Delete post meta for course images
$args = array(
    'post_type' => 'attachment',
    'meta_key' => 'is_course_image',
    'posts_per_page' => -1
);

$attachments = get_posts($args);
foreach ($attachments as $attachment) {
    delete_post_meta($attachment->ID, 'is_course_image');
}

// Delete term meta for taxonomies
$taxonomies = array('coursecategory', 'course_location');
foreach ($taxonomies as $taxonomy) {
    $terms = get_terms(array(
        'taxonomy' => $taxonomy,
        'hide_empty' => false
    ));
    
    if (!is_wp_error($terms)) {
        foreach ($terms as $term) {
            delete_term_meta($term->term_id, 'rich_description');
            delete_term_meta($term->term_id, 'image_coursecategory');
            delete_term_meta($term->term_id, 'icon_coursecategory');
            delete_term_meta($term->term_id, 'image_course_location');
        }
    }
}

// Clean up relationships meta
$post_types = array('course', 'coursedate');
foreach ($post_types as $post_type) {
    $posts = get_posts(array(
        'post_type' => $post_type,
        'posts_per_page' => -1
    ));
    
    foreach ($posts as $post) {
        foreach ($post_types as $related_type) {
            delete_post_meta($post->ID, 'course_related_' . $related_type);
        }
    }
}

// Slett systemsider
require_once dirname(__FILE__) . '/includes/options/kursinnstillinger.php';
$pages = Kursinnstillinger::get_required_pages();
foreach (array_keys($pages) as $page_key) {
    $page_id = get_option('ka_page_' . $page_key);
    if ($page_id) {
        wp_delete_post($page_id, true);
        delete_option('ka_page_' . $page_key);
    }
}

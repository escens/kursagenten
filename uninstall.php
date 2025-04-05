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
    'kursagenten_available_filters',
    'kursagenten_archive_layout',
    'kursagenten_archive_design',
    'kursagenten_archive_list_type',
    'kursagenten_taxonomy_layout',
    'kursagenten_taxonomy_list_type',
    'kursagenten_single_layout',
    'kursagenten_taxonomy_course_location_override',
    'kursagenten_taxonomy_course_location_list_type',
    'kursagenten_taxonomy_coursecategory_override',
    'kursagenten_taxonomy_coursecategory_list_type',
    'kursagenten_taxonomy_instructors_override',
    'kursagenten_taxonomy_instructors_list_type'
);

foreach ($options_to_delete as $option) {
    delete_option($option);
}

global $wpdb;

// 1. Håndter taxonomier og deres relasjoner først
$taxonomies = array('coursecategory', 'course_location', 'instructors');

// Hent alle term IDs for våre taxonomier
$term_ids = $wpdb->get_col("
    SELECT t.term_id 
    FROM {$wpdb->terms} t 
    INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id 
    WHERE tt.taxonomy IN ('" . implode("','", $taxonomies) . "')
");

if (!empty($term_ids)) {
    // Slett term meta
    $wpdb->query("DELETE FROM {$wpdb->termmeta} WHERE term_id IN (" . implode(',', $term_ids) . ")");
    
    // Slett term relationships
    $taxonomy_ids = $wpdb->get_col("
        SELECT term_taxonomy_id 
        FROM {$wpdb->term_taxonomy} 
        WHERE taxonomy IN ('" . implode("','", $taxonomies) . "')
    ");
    if (!empty($taxonomy_ids)) {
        $wpdb->query("DELETE FROM {$wpdb->term_relationships} WHERE term_taxonomy_id IN (" . implode(',', $taxonomy_ids) . ")");
    }
    
    // Slett term taxonomy entries
    $wpdb->query("DELETE FROM {$wpdb->term_taxonomy} WHERE term_id IN (" . implode(',', $term_ids) . ")");
    
    // Slett terms
    $wpdb->query("DELETE FROM {$wpdb->terms} WHERE term_id IN (" . implode(',', $term_ids) . ")");
}

// 2. Håndter courses og coursedates
$post_types = array('course', 'coursedate');

// Hent alle post IDs for courses og coursedates
$post_ids = $wpdb->get_col("
    SELECT ID 
    FROM {$wpdb->posts} 
    WHERE post_type IN ('" . implode("','", $post_types) . "')
");

if (!empty($post_ids)) {
    // Slett all postmeta for disse postene
    $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE post_id IN (" . implode(',', $post_ids) . ")");
}

// 3. Håndter tilknyttede bilder
// Finn og slett alle bilder som er knyttet til kurs
$course_images = $wpdb->get_col("
    SELECT ID 
    FROM {$wpdb->posts} 
    WHERE post_type = 'attachment' 
    AND (post_title LIKE 'kursbilde-%' OR post_name LIKE 'kursbilde-%')
");

if (!empty($course_images)) {
    // Slett metadata for bilder
    $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE post_id IN (" . implode(',', $course_images) . ")");
    
    // Slett bildene fra posts-tabellen
    $wpdb->query("DELETE FROM {$wpdb->posts} WHERE ID IN (" . implode(',', $course_images) . ")");
}

// 4. Slett selve course og coursedate postene
if (!empty($post_ids)) {
    $wpdb->query("DELETE FROM {$wpdb->posts} WHERE ID IN (" . implode(',', $post_ids) . ")");
}

// 5. Slett custom database table
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}kursdato");

// 6. Slett systemsider
require_once dirname(__FILE__) . '/includes/options/coursedesign.php';
$pages = Designmaler::get_required_pages();
foreach (array_keys($pages) as $page_key) {
    $page_id = get_option('ka_page_' . $page_key);
    if ($page_id) {
        wp_delete_post($page_id, true);
        delete_option('ka_page_' . $page_key);
    }
}

<?php
// If uninstall not called from WordPress, then exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Notify server about uninstall before cleaning options
if (function_exists('wp_remote_post')) {
    $api_key = get_option('kursagenten_api_key', '');
    $site_url = site_url();
    if (!empty($api_key)) {
        // Match server-plugin endpoints: /kursagenten-api/unregister_site
        $endpoint = 'https://admin.lanseres.no/kursagenten-api/unregister_site';
        $body = array(
            'api_key' => $api_key,
            'site_url' => $site_url,
        );
        // Best-effort; ignore response
        wp_remote_post($endpoint, array('body' => $body, 'timeout' => 5));
    }
}

// Delete plugin options
$options_to_delete = array(
    // Main option groups
    'kag_bedriftsinfo_option_name',
    'kag_kursinnst_option_name',
    'kag_seo_option_name',
    'kag_avansert_option_name',
    'design_option_name',
    'kursagenten_theme_customizations',
    
    // Design and layout options
    'kursagenten_template_style',
    'kursagenten_single_layout',
    'kursagenten_single_design',
    'kursagenten_archive_layout',
    'kursagenten_archive_design',
    'kursagenten_archive_list_type',
    'kursagenten_taxonomy_layout',
    'kursagenten_taxonomy_design',
    'kursagenten_taxonomy_list_type',
    'kursagenten_taxonomy_view_type',
    
    // Filter options
    'kursagenten_top_filters',
    'kursagenten_left_filters',
    'kursagenten_filter_types',
    'kursagenten_available_filters',
    'kursagenten_filter_default_height',
    'kursagenten_filter_no_collapse',
    
    // Color and styling options
    'kursagenten_max_width',
    'kursagenten_main_color',
    'kursagenten_advanced_colors',
    'kursagenten_button_background',
    'kursagenten_button_color',
    'kursagenten_link_color',
    'kursagenten_icon_color',
    'kursagenten_background_color',
    'kursagenten_highlight_background',
    'kursagenten_base_font',
    'kursagenten_heading_font',
    'kursagenten_main_font',
    'kursagenten_custom_css',
    
    // Grid column options
    'kursagenten_archive_grid_columns_desktop',
    'kursagenten_archive_grid_columns_tablet',
    'kursagenten_archive_grid_columns_mobile',
    'kursagenten_taxonomy_grid_columns_desktop',
    'kursagenten_taxonomy_grid_columns_tablet',
    'kursagenten_taxonomy_grid_columns_mobile',
    
    // Image display options
    'kursagenten_show_images',
    'kursagenten_show_images_taxonomy',
    
    // Taxonomy-specific override options
    'kursagenten_taxonomy_course_location_override',
    'kursagenten_taxonomy_course_location_layout',
    'kursagenten_taxonomy_course_location_design',
    'kursagenten_taxonomy_course_location_list_type',
    'kursagenten_taxonomy_course_location_show_images',
    'kursagenten_taxonomy_course_location_grid_columns_desktop',
    'kursagenten_taxonomy_course_location_grid_columns_tablet',
    'kursagenten_taxonomy_course_location_grid_columns_mobile',
    'kursagenten_taxonomy_coursecategory_override',
    'kursagenten_taxonomy_coursecategory_layout',
    'kursagenten_taxonomy_coursecategory_design',
    'kursagenten_taxonomy_coursecategory_list_type',
    'kursagenten_taxonomy_coursecategory_show_images',
    'kursagenten_taxonomy_coursecategory_grid_columns_desktop',
    'kursagenten_taxonomy_coursecategory_grid_columns_tablet',
    'kursagenten_taxonomy_coursecategory_grid_columns_mobile',
    'kursagenten_taxonomy_instructors_override',
    'kursagenten_taxonomy_instructors_layout',
    'kursagenten_taxonomy_instructors_design',
    'kursagenten_taxonomy_instructors_list_type',
    'kursagenten_taxonomy_instructors_show_images',
    'kursagenten_taxonomy_instructors_name_display',
    'kursagenten_taxonomy_instructors_grid_columns_desktop',
    'kursagenten_taxonomy_instructors_grid_columns_tablet',
    'kursagenten_taxonomy_instructors_grid_columns_mobile',
    
    // Location and region options
    'kursagenten_location_mappings',
    'kursagenten_use_regions',
    'kursagenten_region_mapping',
    
    // Course pagination options
    'kursagenten_courses_per_page',
    'ka_courses_per_page',
    
    // License-related options
    'kursagenten_api_key',
    'kursagenten_site_registered',
    'kursagenten_last_register'
);

foreach ($options_to_delete as $option) {
    delete_option($option);
}

// Delete transients used by updater/registration
delete_transient('kursagenten_secure_updater');
delete_transient('kursagenten_register_success');

global $wpdb;

// 1. Håndter taxonomier og deres relasjoner først
$taxonomies = array('ka_coursecategory', 'ka_course_location', 'ka_instructors');

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
$post_types = array('ka_course', 'ka_coursedate');

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

// 4. Delete ka_course and ka_coursedate posts
if (!empty($post_ids)) {
    $wpdb->query("DELETE FROM {$wpdb->posts} WHERE ID IN (" . implode(',', $post_ids) . ")");
}

// 5. Slett custom database table
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}kursdato");

// 6. Fjern tilknytning til systemsider (slett ikke selve sidene)
// Brukere kan ha tilpasset innhold på disse sidene, så vi beholder dem
require_once dirname(__FILE__) . '/includes/options/coursedesign.php';
$pages = Designmaler::get_required_pages();
foreach (array_keys($pages) as $page_key) {
    $page_id = get_option('ka_page_' . $page_key);
    if ($page_id) {
        // Fjern post_meta som knytter siden til Kursagenten
        $existing_keys = get_post_meta($page_id, '_ka_system_page_keys', true);
        if (is_array($existing_keys)) {
            $existing_keys = array_diff($existing_keys, [$page_key]);
            if (empty($existing_keys)) {
                delete_post_meta($page_id, '_ka_system_page_keys');
            } else {
                update_post_meta($page_id, '_ka_system_page_keys', array_values($existing_keys));
            }
        }
        // Slett option som knytter page_key til page_id
        delete_option('ka_page_' . $page_key);
    }
}

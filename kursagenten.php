<?php
/**
 * Kursagenten courses
 *
 * Plugin Name:       Kursagenten
 * Plugin URI:        https://deltagersystem.no/wp-plugin
 * Description:       Dine kurs hentet og synkronisert fra Kursagenten.
 * Version:           1.0.0
 * Author:            Tone B. Hagen
 * Author URI:        https://kursagenten.no
 * Text Domain:       kursagenten
 * Domain Path:       /lang
 * Requires PHP:      7.4
 * Requires at least: 6.0
 */

 if ( ! defined('ABSPATH')) {
    exit;
}

register_activation_hook(__FILE__, 'kursagenten_check_dependencies');

function kursagenten_check_dependencies() {
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('Kursagenten krever PHP 7.4 eller høyere.');
    }
    
    if (version_compare($GLOBALS['wp_version'], '6.0', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('Kursagenten krever WordPress 6.0 eller høyere.');
    }
}


// Gruppér relaterte konstanter
define('KURSAG_NAME',        'Kursagenten');
define('KURSAG_VERSION',     '1.0.0');
define('KURSAG_MIN_PHP',     '7.4');
define('KURSAG_MIN_WP',      '6.0');

// Filstier
define('KURSAG_PLUGIN_FILE', __FILE__);
define('KURSAG_PLUGIN_BASE', plugin_basename(KURSAG_PLUGIN_FILE));
define('KURSAG_PLUGIN_DIR',  plugin_dir_path(KURSAG_PLUGIN_FILE));
define('KURSAG_PLUGIN_URL',  plugin_dir_url(KURSAG_PLUGIN_FILE));

register_activation_hook(__FILE__, 'kursagenten_activate');
register_deactivation_hook(__FILE__, 'kursagenten_deactivate');

/**
 * Function that runs when plugin is activated
 */

function kursagenten_activate() {
    // First register the post types and taxonomies
    kursagenten_register_post_types();
    $default_seo_options = array(
        'ka_url_rewrite_kurs' => 'kurs',
        'ka_url_rewrite_instruktor' => 'instruktor'
    );
    if (!get_option('kag_seo_option_name')) {
        add_option('kag_seo_option_name', $default_seo_options);
    }
    
    // Create default admin options if they don't exist
    $default_admin_options = array(
        'ka_rename_posts' => 0,
        'ka_jquery_support' => 0,
        'ka_security' => 1,
        'ka_sitereviews' => 0
    );
    add_option('kag_avansert_option_name', $default_admin_options);
    // Then flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Function to register post types and taxonomies
 */
function kursagenten_register_post_types() {
    require_once KURSAG_PLUGIN_DIR . '/admin/post_types/register_post_types.php';
    require_once KURSAG_PLUGIN_DIR . '/admin/post_types/register_taxonomies.php';
    require_once KURSAG_PLUGIN_DIR . '/admin/post_types/register_custom_cpt_relationships.php';
    require_once KURSAG_PLUGIN_DIR . '/admin/post_types/register_custom_taxonomy_fields.php';
    require_once KURSAG_PLUGIN_DIR . '/admin/post_types/register_image_fields.php';
    require_once KURSAG_PLUGIN_DIR . '/admin/post_types/visibility_management.php';
}

/**
 * Register post types on init
 */
add_action('init', 'kursagenten_register_post_types');

/**
 * Function that runs when plugin is deactivated
 */
function kursagenten_deactivate() {
    // Clear the permalinks to remove our post type's rules
    flush_rewrite_rules();
}

require_once KURSAG_PLUGIN_DIR . '/admin/options/kursagenten-admin_options.php';  

require_once KURSAG_PLUGIN_DIR . '/includes/api/api_connection.php';
require_once KURSAG_PLUGIN_DIR . '/includes/api/api-webhook-handler.php';
require_once KURSAG_PLUGIN_DIR . '/includes/api/api_course_sync.php';
require_once KURSAG_PLUGIN_DIR . '/includes/api/api_sync_on_demand.php';
require_once KURSAG_PLUGIN_DIR . '/includes/search/search_instructors.php';

/* MISC ADMIN FUNCTIONS */
require_once KURSAG_PLUGIN_DIR . '/admin/misc/hide_course-images_in_mediafolder.php';
require_once KURSAG_PLUGIN_DIR . '/assets/dynamic-icons.php';

// if (is_admin()) {

    //require_once KURSAG_PLUGIN_DIR . '/admin/bedriftsinnstillinger.php';
    //require_once KURSAG_PLUGIN_DIR . '/includes/jquery.php';
//} else {
   // require_once KURSAG_PLUGIN_DIR . '/includes/frontend/frontend-functions.php';
    //require_once KURSAG_PLUGIN_DIR . '/includes/jquery.php';
//}


    
// Hent admin options
function kursagenten_load_admin_options() {
    $admin_options = get_option('kag_avansert_option_name');
    
    $option_files = [
        'ka_rename_posts' => '/admin/misc/change-post-to-article.php',
        'ka_jquery_support' => '/admin/misc/enable-jquery-support.php',
        'ka_security' => '/admin/misc/security_functions.php',
        'ka_sitereviews' => '/admin/misc/site-reviews-support.php'
    ];
    
    foreach ($option_files as $option => $file) {
        if (isset($admin_options[$option]) && $admin_options[$option] == 1) {
            require_once KURSAG_PLUGIN_DIR . $file;
        }
    }
}
add_action('plugins_loaded', 'kursagenten_load_admin_options');
    
    
/* ENQUEUE JS & CSS ADMIN SCRIPTS */
    function enqueue_custom_admin_script() {
        if (is_admin()) {
            $screen = get_current_screen();
            $plugin_admin_pages = array('kursagenten', 'bedriftsinformasjon', 'kursinnstillinger', 'seo', 'avansert');
            $enqueue_plugin_pages = false;
            
            // Sjekk om vi er på en Kursagenten admin-side
            foreach ($plugin_admin_pages as $slug) {
                if (strpos($screen->id, $slug) !== false) { 
                    $enqueue_plugin_pages = true; 
                    break; 
                }
            }
            
            // Sjekk om vi er på en taxonomi-redigeringsside
            if ($screen && in_array($screen->taxonomy, array('coursecategory', 'course_location', 'instructors'))) {
                $enqueue_plugin_pages = true;
            }
            
            if ($enqueue_plugin_pages) {
                wp_enqueue_media();// Enqueue media scripts for file uploads
                wp_enqueue_script( 'custom-admin-upload-script', plugin_dir_url(__FILE__) . 'admin/js/image-upload.js', array('jquery'), '1.0.3',  true  );
                wp_enqueue_style( 'custom-admin-style', plugin_dir_url(__FILE__) . 'admin/css/kursagenten-admin.css', array(), '1.0.56' );
            }
        }
    }
    add_action('admin_enqueue_scripts', 'enqueue_custom_admin_script');



 /* FRONT END */   

function kursagenten_get_single_template($single) {
    global $post;
    
    if ($post->post_type == 'course') {
        return plugin_dir_path(__FILE__) . 'templates/single-course.php';
    }
    if ($post->post_type == 'instructor') {
        return plugin_dir_path(__FILE__) . 'templates/single-instructor.php';
    }
    
    return $single;
}
add_filter('single_template', 'kursagenten_get_single_template');

function kursagenten_get_archive_template($archive) {
    if (is_post_type_archive('course')) {
        return plugin_dir_path(__FILE__) . 'templates/archive-course.php';
    }
    if (is_post_type_archive('instructor')) {
        return plugin_dir_path(__FILE__) . 'templates/archive-instructor.php';
    }
    
    return $archive;
}
add_filter('archive_template', 'kursagenten_get_archive_template');

function kursagenten_get_taxonomy_template($template) {
    if (is_tax('coursecategory') || is_tax('course_location') || is_tax('instructors')) {
        return plugin_dir_path(__FILE__) . 'templates/taxonomy.php';
    }
    return $template;
}
add_filter('taxonomy_template', 'kursagenten_get_taxonomy_template');

// Sørg for at funksjonen er inkludert
    require_once KURSAG_PLUGIN_DIR . '/templates/includes/template-functions.php';
    require_once KURSAG_PLUGIN_DIR . '/templates/includes/queries.php';
    require_once KURSAG_PLUGIN_DIR . '/templates/includes/course-ajax-filter.php';
    
    function kursagenten_enqueue_styles() {
        // Last inn base CSS for alle Kursagenten sider
        wp_enqueue_style(
            'kursagenten-course-style',
            KURSAG_PLUGIN_URL . '/frontend/css/frontend-course-style.css',
            array(),
            KURSAG_VERSION
        );

        // Last inn datepicker CSS for alle Kursagenten sider
        wp_enqueue_style(
            'kursagenten-datepicker-style',
            KURSAG_PLUGIN_URL . '/frontend/css/datepicker-caleran.min.css',
            array(),
            KURSAG_VERSION
        );

        // Last inn archive-course spesifikk CSS
        if (is_post_type_archive('course')) {
            // Hent valgt stil for archive
            $default_style = get_option('kursagenten_archive_style', 'default');
            $specific_style = get_option('kursagenten_archive_style_course', '');
            $template_style = !empty($specific_style) ? $specific_style : $default_style;

            // Last inn base archive CSS
            wp_enqueue_style(
                'kursagenten-archive-base',
                KURSAG_PLUGIN_URL . '/frontend/css/frontend-courselist-default.css',
                array(),
                KURSAG_VERSION
            );

            // Last inn spesifikk stil hvis det ikke er default
            if ($template_style !== 'default') {
                wp_enqueue_style(
                    'kursagenten-archive-' . $template_style,
                    KURSAG_PLUGIN_URL . '/frontend/css/frontend-courselist-' . $template_style . '.css',
                    array('kursagenten-archive-base'),
                    KURSAG_VERSION
                );
            }
        }

        // CSS for taxonomy templates
        if (is_tax('coursecategory') || is_tax('course_location') || is_tax('instructors')) {
            // Hent valgt stil
            $taxonomy = get_queried_object()->taxonomy;
            $default_style = get_option('kursagenten_taxonomy_style', 'default');
            $specific_style = get_option("kursagenten_taxonomy_style_{$taxonomy}", '');
            $template_style = !empty($specific_style) ? $specific_style : $default_style;

            // Last inn base CSS
            wp_enqueue_style(
                'kursagenten-taxonomy-base',
                KURSAG_PLUGIN_URL . '/assets/css/taxonomy-default.css',
                array(),
                KURSAG_VERSION
            );

            // Load specific style if it's not the default
            if ($template_style !== 'default') {
                wp_enqueue_style(
                    'kursagenten-taxonomy-' . $template_style,
                    KURSAG_PLUGIN_URL . '/assets/css/taxonomy-' . $template_style . '.css',
                    array('kursagenten-taxonomy-base'),
                    KURSAG_VERSION
                );
            }
        }

        // Instructor styling (beholdt som den er)
        if (is_singular('instructor') || is_post_type_archive('instructor')) {
            wp_enqueue_style(
                'kursagenten-instructor-style',
                KURSAG_PLUGIN_URL . '/frontend/css/instructor-style.css',
                array(),
                KURSAG_VERSION
            );
        }
    }
    add_action('wp_enqueue_scripts', 'kursagenten_enqueue_styles');


    function kursagenten_enqueue_scripts() {
        // Define valid post types and their contexts
        $valid_pages = [
            'post_types' => [
                'course' => ['singular', 'archive'],
                'instructor' => ['singular', 'archive'],
                'coursedate' => ['singular']
            ],
            'taxonomies' => [
                'coursecategory' => true,
                'course_location' => true,
                'instructors' => true
            ]
        ];
        
        $should_load = false;
        
        // Sjekk post types
        foreach ($valid_pages['post_types'] as $post_type => $contexts) {
            if (
                (in_array('singular', $contexts) && is_singular($post_type)) ||
                (in_array('archive', $contexts) && is_post_type_archive($post_type))
            ) {
                $should_load = true;
                break;
            }
        }
        
        // Sjekk taxonomier
        if (!$should_load) {
            foreach ($valid_pages['taxonomies'] as $taxonomy => $enabled) {
                if ($enabled && is_tax($taxonomy)) {
                    $should_load = true;
                    break;
                }
            }
        }
        
        if (!$should_load) {
            return;
        }
        
        // Enqueue scripts and styles
        wp_enqueue_script('kursagenten-iframe-resizer', 'https://embed.kursagenten.no/js/iframe-resizer/iframeResizer.min.js', array(), null, true);
        wp_enqueue_script('kursagenten-slidein-panel', plugins_url('frontend/js/course-slidein-panel.js', __FILE__), array('jquery', 'kursagenten-iframe-resizer'), KURSAG_VERSION, true);
        wp_enqueue_script('kursagenten-ajax-filter', plugins_url('frontend/js/course-ajax-filter.js', __FILE__), array('jquery', 'kursagenten-slidein-panel'), KURSAG_VERSION, true);

        wp_enqueue_script(
            'kursagenten-datepicker-moment',
            KURSAG_PLUGIN_URL . '/frontend/js/datepicker/moment.min.js',
            array(),
            KURSAG_VERSION
        );

        wp_enqueue_script(
            'kursagenten-datepicker-script',
            KURSAG_PLUGIN_URL . '/frontend/js/datepicker/caleran.min.js',
            ['kursagenten-datepicker-moment'],
            KURSAG_VERSION
        );

        wp_enqueue_script(
            'kursagenten-accordion_script',
            KURSAG_PLUGIN_URL . '/frontend/js/course-accordion.js',
            array(),
            KURSAG_VERSION
        );

        // Lokaliser scriptet med nødvendige data
        wp_localize_script(
            'kursagenten-ajax-filter',
            'kurskalender_data',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'filter_nonce' => wp_create_nonce('filter_nonce')
            )
        );

        
        wp_enqueue_script(
            'kursagenten-filter-mobile',
            KURSAG_PLUGIN_URL . '/frontend/js/course-filter-mobile.js',
            array(),
            KURSAG_VERSION
        );
    }
    add_action('wp_enqueue_scripts', 'kursagenten_enqueue_scripts');
   
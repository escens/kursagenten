<?php
/**
 * Kursagenten courses
 *
 * Plugin Name:       Kursagenten
 * Plugin URI:        https://deltagersystem.no/wp-plugin
 * Description:       Dine kurs hentet og synkronisert fra Kursagenten.
 * Version:           0.0.1
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

// Define constants.
define( 'KURSAG_NAME',			'Kursagenten' );
define( 'KURSAG_VERSION',		'1.0.0' );
define( 'KURSAG_PLUGIN_FILE',	__FILE__ );
define( 'KURSAG_PLUGIN_BASE',	plugin_basename( KURSAG_PLUGIN_FILE ) );
define( 'KURSAG_PLUGIN_DIR',	plugin_dir_path( KURSAG_PLUGIN_FILE ) );
define( 'KURSAG_PLUGIN_URL',	plugin_dir_url( KURSAG_PLUGIN_FILE ) );



// if (is_admin()) {
    require_once KURSAG_PLUGIN_DIR . '/includes/post_types/register_post_types.php';
    require_once KURSAG_PLUGIN_DIR . '/includes/post_types/register_taxonomies.php';
    //require_once KURSAG_PLUGIN_DIR . '/includes/post_types/register_custom_kurs_fields.php';
    require_once KURSAG_PLUGIN_DIR . '/includes/post_types/register_custom_cpt_relationships.php';
    require_once KURSAG_PLUGIN_DIR . '/includes/post_types/register_custom_taxonomy_fields.php';
    require_once KURSAG_PLUGIN_DIR . '/includes/post_types/register_image_fields.php';
    require_once KURSAG_PLUGIN_DIR . '/includes/post_types/create_kursdato_table.php';
    // Registrer aktiveringskrok for å opprette tabellen
    //register_activation_hook(__FILE__, 'create_kursdato_table');

    
    require_once KURSAG_PLUGIN_DIR . '/includes/admin_options/kursagenten-admin_options.php';

    require_once KURSAG_PLUGIN_DIR . '/includes/kurs_sync/kurs_api_connection.php';
    require_once KURSAG_PLUGIN_DIR . '/includes/kurs_sync/kurs_webhook.php';
    require_once KURSAG_PLUGIN_DIR . '/includes/kurs_sync/kurs_create_update.php';
    require_once KURSAG_PLUGIN_DIR . '/includes/kurs_sync/kurs_sync_all_courses_from_admin_settings.php';
    //require_once KURSAG_PLUGIN_DIR . '/includes/admin/bedriftsinnstillinger.php';
    //require_once KURSAG_PLUGIN_DIR . '/includes/jquery.php';
//} else {
   // require_once KURSAG_PLUGIN_DIR . '/includes/frontend/frontend-functions.php';
    //require_once KURSAG_PLUGIN_DIR . '/includes/jquery.php';
//}

/* MISC ADMIN FUNCTIONS */

    require_once KURSAG_PLUGIN_DIR . '/includes/admin_misc/hide_course-images_in_mediafolder.php';
    require_once KURSAG_PLUGIN_DIR . 'assets/dynamic-icons.php';
    
    // Hent admin options
    $admin_options = get_option('kag_avansert_option_name');
    // Hvis funksjonen "Omdøp innlegg til Artikler" er aktivert, last inn filen som inneholder funksjonen
    if (isset($admin_options['ka_rename_posts']) && $admin_options['ka_rename_posts'] == 1) {
        require_once KURSAG_PLUGIN_DIR . '/includes/admin_misc/change-post-to-article.php';
    }
    if (isset($admin_options['ka_jquery_support']) && $admin_options['ka_jquery_support'] == 1) {
        require_once KURSAG_PLUGIN_DIR . 'includes/admin_misc/enable-jquery-support.php';
    }
    if (isset($admin_options['ka_security']) && $admin_options['ka_security'] == 1) {
        require_once KURSAG_PLUGIN_DIR . 'includes/admin_misc/security_functions.php';
    }
    if (isset($admin_options['ka_sitereviews']) && $admin_options['ka_sitereviews'] == 1) {
        require_once KURSAG_PLUGIN_DIR . 'includes/admin_misc/site-reviews-support.php';
    }
    
    
/* ENQUEUE JS & CSS ADMIN SCRIPTS */
    function enqueue_custom_admin_script() {
        if (is_admin()) {
            
            $screen = get_current_screen();
            $plugin_admin_pages = array('kursagenten', 'bedriftsinformasjon', 'kursinnstillinger', 'seo', 'avansert');
            $enqueue_plugin_pages = false;
            foreach ($plugin_admin_pages as $slug) {
                if (strpos($screen->id, $slug) !== false) { $enqueue_plugin_pages = true; break; }
            }
            
            wp_enqueue_media();// Enqueue media scripts for file uploads (necessary for image upload functionality)
            
            //Remember to add class handle in bottom of image-upload.js
            wp_enqueue_script( 'custom-admin-upload-script', plugin_dir_url(__FILE__) . 'assets/js/image-upload.js', array('jquery'), '1.0.3',  true  );
            //wp_enqueue_script( 'custom-admin-sync-script', plugin_dir_url(__FILE__) . 'assets/js/kursagenten-admin-sync.js', array('jquery'), '1.0.3',  true  );
            
            if ($enqueue_plugin_pages) { // Enqueue custom CSS for admin pages
                wp_enqueue_style( 'custom-admin-style', plugin_dir_url(__FILE__) . 'assets/css/kursagenten-admin.css', array(), '1.0.56' );
            }
        }
    }
    add_action('admin_enqueue_scripts', 'enqueue_custom_admin_script');

 /* FRONT END */   

    add_filter('single_template', function($single) {
        global $post;
        if ($post->post_type == 'course') {
            return plugin_dir_path(__FILE__) . 'templates/single-course.php';
        }
        if ($post->post_type == 'instructor') {
            return plugin_dir_path(__FILE__) . 'templates/single-instructor.php';
        }
        /*if ($post->post_type == 'instructor' || $post->post_type == 'course') {
            return plugin_dir_path(__FILE__) . 'assets/dynamic-icons.php';
        }*/
        return $single;
    });
    
    add_filter('archive_template', function($archive) {
        if (is_post_type_archive('course')) {
            return plugin_dir_path(__FILE__) . 'templates/archive-course.php';
        }
        if (is_post_type_archive('instructor')) {
            return plugin_dir_path(__FILE__) . 'templates/archive-instructor.php';
        }
        return $archive;
    });

    require_once KURSAG_PLUGIN_DIR . 'templates/includes/queries.php';
    require_once KURSAG_PLUGIN_DIR . 'templates/includes/course-ajax-filter.php';
    
    function kursagenten_enqueue_styles() {
        // Legg til CSS for 'course' templates
        if (is_singular('course') || is_post_type_archive('course')) {
            wp_enqueue_style(
                'kursagenten-course-style', // Unik ID for stilen
                plugin_dir_url(__FILE__) . 'assets/css/frontend-course-style.css', // Sti til CSS-filen
                array(), // Avhenger av ingen andre stiler
                '1.0.0.7' // Versjonsnummer
            );
        }

        if (is_singular('course') || is_post_type_archive('course')) {
            wp_enqueue_style(
                'kursagenten-courselist-style-default', // Unik ID for stilen
                plugin_dir_url(__FILE__) . 'assets/css/frontend-courselist-default.css', // Sti til CSS-filen
                array(), // Avhenger av ingen andre stiler
                '1.0.0.7' // Versjonsnummer
            );
        }
    
        // Legg til CSS for 'instructor' templates
        if (is_singular('instructor') || is_post_type_archive('instructor')) {
            wp_enqueue_style(
                'kursagenten-instructor-style',
                plugin_dir_url(__FILE__) . 'assets/css/instructor-style.css',
                array(),
                '1.0.0'
            );
        }
        wp_enqueue_style(
            'kursagenten-datepicker-style',
            plugin_dir_url(__FILE__) . 'assets/css/datepicker-caleran.min.css',
            array(),
            '1.0.0'
        );
    }
    add_action('wp_enqueue_scripts', 'kursagenten_enqueue_styles');

    /*function enqueue_datepicker_scripts() {
        // Legger til jQuery UI Datepicker
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_style('jquery-ui-css', 'https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css');
    }
    add_action('wp_enqueue_scripts', 'enqueue_datepicker_scripts');
    */

    function kursagenten_enqueue_scripts() {

            wp_enqueue_script(
                'kursagenten-accordion_script',
                plugin_dir_url(__FILE__) . 'assets/js/course-accordion.js',
                array(), // Avhenger ikke av andre skript
                '1.0.0.3' // Versjonsnummer
            );
            wp_enqueue_script(
                'kursagenten-slidein-panel',
                plugin_dir_url(__FILE__) . 'assets/js/course-slidein-panel.js',
                array(), // Avhenger ikke av andre skript
                '1.0.0.1' // Versjonsnummer
            );
            wp_enqueue_script(
                'kursagenten-datepicker-moment',
                plugin_dir_url(__FILE__) . 'assets/js/datepicker/moment.min.js',
                array(), 
                '1.0.0.2'
            );
            wp_enqueue_script(
                'kursagenten-datepicker-script',
                plugin_dir_url(__FILE__) . 'assets/js/datepicker/caleran.min.js',
                ['kursagenten-datepicker-moment'], 
                '1.0.0.2'
            );
            wp_enqueue_script(
                'kursagenten-ajax-filter',
                plugin_dir_url(__FILE__) . 'assets/js/course-ajax-filter.js',
                ['jquery', 'kursagenten-datepicker-script', 'kursagenten-datepicker-moment'], 
                '1.0.0.2', 
                true
            );
            // Legg til nonce og AJAX-URL som data i skriptet
            wp_localize_script('kursagenten-ajax-filter', 'kurskalender_data', [
                'ajax_url' => admin_url('admin-ajax.php'), // AJAX-URL for WordPress
                'filter_nonce' => wp_create_nonce('filter_nonce'), // Nonce for sikkerhet
            ]);
            

    }
    add_action('wp_enqueue_scripts', 'kursagenten_enqueue_scripts');
    
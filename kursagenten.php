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
    require_once KURSAG_PLUGIN_DIR . '/admin/post_types/register_post_types.php';
    require_once KURSAG_PLUGIN_DIR . '/admin/post_types/register_taxonomies.php';
    //require_once KURSAG_PLUGIN_DIR . '/includes/post_types/register_custom_kurs_fields.php';
    require_once KURSAG_PLUGIN_DIR . '/admin/post_types/register_custom_cpt_relationships.php';
    require_once KURSAG_PLUGIN_DIR . '/admin/post_types/register_custom_taxonomy_fields.php';
    require_once KURSAG_PLUGIN_DIR . '/admin/post_types/register_image_fields.php';
    require_once KURSAG_PLUGIN_DIR . '/admin/post_types/create_kursdato_table.php';
    // Registrer aktiveringskrok for å opprette tabellen
    //register_activation_hook(__FILE__, 'create_kursdato_table');

    
    require_once KURSAG_PLUGIN_DIR . '/admin/options/kursagenten-admin_options.php';  

    require_once KURSAG_PLUGIN_DIR . '/includes/api/api_connection.php';
    require_once KURSAG_PLUGIN_DIR . '/includes/api/api-webhook-handler.php';
    require_once KURSAG_PLUGIN_DIR . '/includes/api/api_course_sync.php';
    require_once KURSAG_PLUGIN_DIR . '/includes/api/api_sync_on_demand.php';
    //require_once KURSAG_PLUGIN_DIR . '/admin/bedriftsinnstillinger.php';
    //require_once KURSAG_PLUGIN_DIR . '/includes/jquery.php';
//} else {
   // require_once KURSAG_PLUGIN_DIR . '/includes/frontend/frontend-functions.php';
    //require_once KURSAG_PLUGIN_DIR . '/includes/jquery.php';
//}

/* MISC ADMIN FUNCTIONS */

    require_once KURSAG_PLUGIN_DIR . '/admin/misc/hide_course-images_in_mediafolder.php';
    require_once KURSAG_PLUGIN_DIR . '/assets/dynamic-icons.php';
    
    // Hent admin options
    $admin_options = get_option('kag_avansert_option_name');
    // Hvis funksjonen "Omdøp innlegg til Artikler" er aktivert, last inn filen som inneholder funksjonen
    if (isset($admin_options['ka_rename_posts']) && $admin_options['ka_rename_posts'] == 1) {
        require_once KURSAG_PLUGIN_DIR . '/admin/misc/change-post-to-article.php';
    }
    if (isset($admin_options['ka_jquery_support']) && $admin_options['ka_jquery_support'] == 1) {
        require_once KURSAG_PLUGIN_DIR . '/admin/misc/enable-jquery-support.php';
    }
    if (isset($admin_options['ka_security']) && $admin_options['ka_security'] == 1) {
        require_once KURSAG_PLUGIN_DIR . '/admin/misc/security_functions.php';
    }
    if (isset($admin_options['ka_sitereviews']) && $admin_options['ka_sitereviews'] == 1) {
        require_once KURSAG_PLUGIN_DIR . '/admin/misc/site-reviews-support.php';
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
            wp_enqueue_script( 'custom-admin-upload-script', plugin_dir_url(__FILE__) . 'admin/js/image-upload.js', array('jquery'), '1.0.3',  true  );
            //wp_enqueue_script( 'custom-admin-sync-script', plugin_dir_url(__FILE__) . 'admin/js/kursagenten-admin-sync.js', array('jquery'), '1.0.3',  true  );
            
            if ($enqueue_plugin_pages) { // Enqueue custom CSS for admin pages
                wp_enqueue_style( 'custom-admin-style', plugin_dir_url(__FILE__) . 'admin/css/kursagenten-admin.css', array(), '1.0.56' );
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
// Sørg for at funksjonen er inkludert
    require_once KURSAG_PLUGIN_DIR . '/templates/includes/template-functions.php';
    require_once KURSAG_PLUGIN_DIR . '/templates/includes/queries.php';
    require_once KURSAG_PLUGIN_DIR . '/templates/includes/course-ajax-filter.php';
    
    function kursagenten_enqueue_styles() {
        // Oppdater stier for frontend CSS
        if (is_singular('course') || is_post_type_archive('course')) {
            wp_enqueue_style(
                'kursagenten-course-style',
                KURSAG_PLUGIN_URL . '/frontend/css/frontend-course-style.css',
                array(),
                '1.0.0.7'
            );

            wp_enqueue_style(
                'kursagenten-courselist-style-default',
                KURSAG_PLUGIN_URL . '/frontend/css/frontend-courselist-default.css',
                array(),
                '1.0.0.7'
            );
        }

        if (is_singular('instructor') || is_post_type_archive('instructor')) {
            wp_enqueue_style(
                'kursagenten-instructor-style',
                KURSAG_PLUGIN_URL . '/frontend/css/instructor-style.css',
                array(),
                '1.0.0'
            );
        }

        wp_enqueue_style(
            'kursagenten-datepicker-style',
            KURSAG_PLUGIN_URL . '/frontend/css/datepicker-caleran.min.css',
            array(),
            '1.0.0'
        );
    }
    add_action('wp_enqueue_scripts', 'kursagenten_enqueue_styles');


    function kursagenten_enqueue_scripts() {
        // Oppdater stier for frontend JavaScript
        wp_enqueue_script(
            'kursagenten-accordion_script',
            KURSAG_PLUGIN_URL . '/frontend/js/course-accordion.js',
            array(),
            '1.0.0.3'
        );

        wp_enqueue_script(
            'kursagenten-slidein-panel',
            KURSAG_PLUGIN_URL . '/frontend/js/course-slidein-panel.js',
            array(),
            '1.0.0.1'
        );

        wp_enqueue_script(
            'kursagenten-datepicker-moment',
            KURSAG_PLUGIN_URL . '/frontend/js/datepicker/moment.min.js',
            array(),
            '1.0.0.2'
        );

        wp_enqueue_script(
            'kursagenten-datepicker-script',
            KURSAG_PLUGIN_URL . '/frontend/js/datepicker/caleran.min.js',
            ['kursagenten-datepicker-moment'],
            '1.0.0.2'
        );

        wp_enqueue_script(
            'kursagenten-ajax-filter',
            KURSAG_PLUGIN_URL . 'frontend/js/course-ajax-filter.js',
            ['jquery', 'kursagenten-datepicker-script', 'kursagenten-datepicker-moment'],
            '1.0.0.2',
            true
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
    }
    add_action('wp_enqueue_scripts', 'kursagenten_enqueue_scripts');
    

    
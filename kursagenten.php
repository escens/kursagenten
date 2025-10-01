<?php
/**
 * Kursagenten courses
 *
 * Plugin Name:       Kursagenten
 * Plugin URI:        https://deltagersystem.no/wp-plugin
 * Description:       Komplett løsning for visning av kurs fra Kursagenten med automatisk henting av nye og oppdaterte kurs.
 * Version:           1.0.1
 * Author:            Tone B. Hagen
 * Author URI:        https://kursagenten.no
 * Text Domain:       kursagenten
 * Domain Path:       /lang
 * Requires PHP:      7.4
 * Requires at least: 6.0
 */

 define('KURSAG_VERSION', '1.0.1');
// Plugin versjon
/*
if (defined('WP_DEBUG') && WP_DEBUG) {
    define('KURSAG_VERSION', '1.0.1-dev-' . gmdate('YmdHis'));
} else {
    define('KURSAG_VERSION', '1.0.1');
}
*/
// Plugin konstanter - bruk disse overalt for konsistent informasjon
if (!defined('KURSAG_DESCRIPTION')) {
    define('KURSAG_DESCRIPTION', 'Komplett løsning for visning av kurs fra Kursagenten med automatisk henting av nye og oppdaterte kurs');
}
if (!defined('KURSAG_INSTALLATION')) {
    define('KURSAG_INSTALLATION', '1. Installer plugin<br>2. Legg inn tilsendt API-nøkkel i Innstillinger<br>3. Gå til Oversikt for å videre instruksjoner');
}
if (!defined('KURSAG_AUTHOR')) {
    define('KURSAG_AUTHOR', 'Tone B. Hagen');
}
if (!defined('KURSAG_AUTHOR_URI')) {
    define('KURSAG_AUTHOR_URI', 'https://kursagenten.no');
}
if (!defined('KURSAG_HOMEPAGE')) {
    define('KURSAG_HOMEPAGE', 'https://deltagersystem.no/wp-plugin');
}
if (!defined('KURSAG_WP_REQUIRES')) {
    define('KURSAG_WP_REQUIRES', '6.0');
}
if (!defined('KURSAG_WP_TESTED')) {
    define('KURSAG_WP_TESTED', '6.6');
}
if (!defined('KURSAG_PHP_REQUIRES')) {
    define('KURSAG_PHP_REQUIRES', '7.4');
}
if (!defined('KURSAG_BANNER_LOW')) {
    define('KURSAG_BANNER_LOW', 'https://admin.lanseres.no/plugin-updates/kursagenten-banner-772x250.webp');
}
if (!defined('KURSAG_BANNER_HIGH')) {
    define('KURSAG_BANNER_HIGH', 'https://admin.lanseres.no/plugin-updates/kursagenten-banner-1544x500.webp');
}


if (defined('WP_DEBUG') && WP_DEBUG) {
    // Bare sett cache headers under utvikling
    add_action('send_headers', function() {
        if (!headers_sent()) {
            header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
            header("Cache-Control: post-check=0, pre-check=0", false);
            header("Pragma: no-cache");
        }
    });
}

if (!defined('ABSPATH')) {
    exit;
}


register_activation_hook(__FILE__, 'kursagenten_check_dependencies');

function kursagenten_check_dependencies() {
    $min_php = defined('KURSAG_PHP_REQUIRES') ? KURSAG_PHP_REQUIRES : '7.4';
    $min_wp = defined('KURSAG_WP_REQUIRES') ? KURSAG_WP_REQUIRES : '6.0';
    
    if (version_compare(PHP_VERSION, $min_php, '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die("Kursagenten krever PHP {$min_php} eller høyere.");
    }
    
    if (version_compare($GLOBALS['wp_version'], $min_wp, '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die("Kursagenten krever WordPress {$min_wp} eller høyere.");
    }
}

// Filstier
define('KURSAG_PLUGIN_FILE', __FILE__);
define('KURSAG_PLUGIN_BASE', plugin_basename(KURSAG_PLUGIN_FILE));
define('KURSAG_PLUGIN_DIR',  plugin_dir_path(KURSAG_PLUGIN_FILE));
define('KURSAG_PLUGIN_URL',  plugin_dir_url(KURSAG_PLUGIN_FILE));
//define('KURSAGENTEN_IMAGE_BASE_URL_INSTRUCTOR', '');


register_activation_hook(__FILE__, 'kursagenten_activate');
register_deactivation_hook(__FILE__, 'kursagenten_deactivate');


/**
 * Fikser queried object for alle kursrelaterte taksonomier
 * Added 18.03.2025 due to a bug in the taxonomy template. Didn't find the root of the problem, but this fixed it. See also default.php in the templates/designs/taxonomy folder.
 */
function kursagenten_fix_all_taxonomy_queries() {
    // Bare kjør på frontend
    if (is_admin()) {
        return;
    }
    
    // Hent URL-stien direkte
    $full_path = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    $path_segments = explode('/', trim($full_path, '/'));
    
    // Sjekk om URL-stien matcher våre taksonomier
    if (count($path_segments) >= 2) {
        $taxonomy_slug = $path_segments[0];
        $term_slug = $path_segments[1];
        
        // Fjern eventuelle query-parametere fra term slug
        if (strpos($term_slug, '?') !== false) {
            $term_slug = substr($term_slug, 0, strpos($term_slug, '?'));
        }
        
        // Hent URL-innstillinger
        $url_options = get_option('kag_seo_option_name');
        
        // Bygg mapping basert på innstillinger
        $taxonomy_map = [
            !empty($url_options['ka_url_rewrite_kurskategori']) ? $url_options['ka_url_rewrite_kurskategori'] : 'kurskategori' => 'coursecategory',
            !empty($url_options['ka_url_rewrite_kurssted']) ? $url_options['ka_url_rewrite_kurssted'] : 'kurssted' => 'course_location',
            !empty($url_options['ka_url_rewrite_instruktor']) ? $url_options['ka_url_rewrite_instruktor'] : 'instruktorer' => 'instructors'
        ];
        
        // Identifiser taksonomi basert på URL-sti
        $taxonomy = isset($taxonomy_map[$taxonomy_slug]) ? $taxonomy_map[$taxonomy_slug] : '';
        
        // Sjekk om vi har gyldig taksonomi og term
        if (!empty($taxonomy) && !empty($term_slug)) {
            $term = null;
            
            // Spesiell håndtering for instruktører med navnevisning
            if ($taxonomy === 'instructors') {
                $name_display = get_option('kursagenten_taxonomy_instructors_name_display', '');
                if ($name_display === 'firstname' || $name_display === 'lastname') {
                    $meta_key = $name_display === 'firstname' ? 'instructor_firstname' : 'instructor_lastname';
                    
                    // Finn instruktør basert på fornavn/etternavn
                    $terms = get_terms(array(
                        'taxonomy' => 'instructors',
                        'meta_key' => $meta_key,
                        'meta_value' => $term_slug,
                        'hide_empty' => false
                    ));
                    
                    if (!empty($terms)) {
                        $term = $terms[0];
                    }
                }
            }
            
            // Hvis vi ikke fant term via navnevisning, prøv standard slug
            if (!$term) {
                $term = get_term_by('slug', $term_slug, $taxonomy);
            }
            
            if ($term) {
                // Oppdater globale variabler
                global $wp_query;
                $wp_query->queried_object = $term;
                $wp_query->queried_object_id = $term->term_id;
                
                // Oppdater også taksonomi-spørringen
                $wp_query->set('taxonomy', $taxonomy);
                $wp_query->set('term', $term->slug);
                
                // Sett riktig tittel
                $wp_query->set('title', $term->name);
            }
        }
    }
}
// Kjør denne funksjonen så tidlig som mulig
add_action('wp', 'kursagenten_fix_all_taxonomy_queries', 1);

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

    // Opprett systemsider ved aktivering hvis ønskelig
    $create_pages = apply_filters('kursagenten_create_system_pages_on_activation', false);
    if ($create_pages) {
        require_once KURSAG_PLUGIN_DIR . '/includes/options/kursinnstillinger.php';
        $pages = Kursinnstillinger::get_required_pages();
        foreach (array_keys($pages) as $page_key) {
            Kursinnstillinger::create_system_page($page_key);
        }
    }
}

/**
 * Function to register post types and taxonomies
 */
function kursagenten_register_post_types() {
    require_once KURSAG_PLUGIN_DIR . '/includes/post_types/register_post_types.php';
    require_once KURSAG_PLUGIN_DIR . '/includes/post_types/register_taxonomies.php';
    require_once KURSAG_PLUGIN_DIR . '/includes/post_types/register_custom_cpt_relationships.php';
    require_once KURSAG_PLUGIN_DIR . '/includes/post_types/register_custom_taxonomy_fields.php';
    require_once KURSAG_PLUGIN_DIR . '/includes/post_types/register_image_fields.php';
    require_once KURSAG_PLUGIN_DIR . '/includes/post_types/visibility_management.php';
    require_once KURSAG_PLUGIN_DIR . '/includes/post_types/course_relationships.php';
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

require_once KURSAG_PLUGIN_DIR . '/includes/options/kursagenten-admin_options.php';  

require_once KURSAG_PLUGIN_DIR . '/includes/api/api_connection.php';
require_once KURSAG_PLUGIN_DIR . '/includes/api/api_webhook_handler.php';
require_once KURSAG_PLUGIN_DIR . '/includes/api/api_course_sync.php';
require_once KURSAG_PLUGIN_DIR . '/includes/api/api_sync_on_demand.php';
require_once KURSAG_PLUGIN_DIR . '/includes/search/search_instructors.php';
require_once KURSAG_PLUGIN_DIR . '/includes/helpers/helpers.php';
require_once KURSAG_PLUGIN_DIR . '/includes/helpers/course_days_helper.php';
require_once KURSAG_PLUGIN_DIR . '/includes/admin-bar-links.php';


// Last inn hovedklassen og CSS output
require_once KURSAG_PLUGIN_DIR . '/includes/class-kursagenten.php';
require_once KURSAG_PLUGIN_DIR . '/includes/class-kursagenten-css-output.php';

// Initialiser hovedklassen
$kursagenten = new Kursagenten();

// Last inn oppdateringshåndtering og initialiser
require_once KURSAG_PLUGIN_DIR . '/includes/plugin_update/secure_updater.php';
new \KursagentenUpdater\SecureUpdater();

/* MISC ADMIN FUNCTIONS */
require_once KURSAG_PLUGIN_DIR . '/includes/misc/hide_course-images_in_mediafolder.php';
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
        'ka_rename_posts' =>    '/includes/misc/change-post-to-article.php',
        'ka_jquery_support' =>  '/includes/misc/enable-jquery-support.php',
        'ka_security' =>        '/includes/misc/security_functions.php',
        'ka_sitereviews' =>     '/includes/misc/site-reviews-support.php',
        'ka_disable_gravatar' => '/includes/misc/disable-gravatar.php'
    ];
    
    foreach ($option_files as $option => $file) {
        if (isset($admin_options[$option]) && $admin_options[$option] == 1) {
            require_once KURSAG_PLUGIN_DIR . $file;
        }
    }
}
add_action('plugins_loaded', 'kursagenten_load_admin_options');

// Global guard: redirect alle Kursagenten-undersider til Oversikt dersom API-nøkkel mangler
add_action('admin_init', function() {
    if (!is_admin()) {
        return;
    }
    if (!current_user_can('manage_options')) {
        return;
    }
    $api_key = get_option('kursagenten_api_key', '');
    if (!empty($api_key)) {
        return;
    }
    // Siden vi står på
    $page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
    if (empty($page)) {
        return;
    }
    // Alle kjente Kursagenten-sider (submenu slugs)
    $kursagenten_pages = array(
        'kursagenten',
        'kursinnstillinger',
        'design',
        'bedriftsinformasjon',
        'seo',
        'avansert',
        'documentation',
        'kursagenten-theme-customizations'
    );
    // Omdiriger alle unntatt selve oversikten
    if (in_array($page, $kursagenten_pages, true) && $page !== 'kursagenten') {
        wp_safe_redirect( admin_url('admin.php?page=kursagenten') );
        exit;
    }
});
    
    
/* ENQUEUE JS & CSS ADMIN SCRIPTS */
    function enqueue_custom_admin_script() {
        if (is_admin()) {
            $screen = get_current_screen();
            $plugin_admin_pages = array('kursagenten', 'bedriftsinformasjon', 'kursinnstillinger', 'seo', 'avansert');
            $enqueue_plugin_pages = false;
            $api_key = get_option('kursagenten_api_key', '');
            
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
            
            // Ikke last tunge admin-scripts hvis lisensnøkkel mangler
            if ($enqueue_plugin_pages && !empty($api_key)) {
                wp_enqueue_media();// Enqueue media scripts for file uploads
                wp_enqueue_script( 'custom-admin-upload-script', plugin_dir_url(__FILE__) . 'assets/js/admin/image-upload.js', array('jquery'), KURSAG_VERSION,  true  );
                wp_enqueue_script( 'custom-admin-utilities-script', plugin_dir_url(__FILE__) . 'assets/js/admin/admin-utilities.js', array('jquery'), KURSAG_VERSION,  true  );  
                wp_enqueue_style( 'custom-admin-style', plugin_dir_url(__FILE__) . 'assets/css/admin/kursagenten-admin.css', array(), KURSAG_VERSION );
            }
        }
    }
    add_action('admin_enqueue_scripts', 'enqueue_custom_admin_script');



/* FRONTEND/SHARED */
// AJAX handlers and their dependencies must be available in both admin (admin-ajax.php) and frontend contexts
require_once KURSAG_PLUGIN_DIR . '/public/templates/includes/template-functions.php';
require_once KURSAG_PLUGIN_DIR . '/public/templates/includes/template_taxonomy_functions.php';
require_once KURSAG_PLUGIN_DIR . '/public/templates/includes/queries.php';
require_once KURSAG_PLUGIN_DIR . '/public/templates/includes/course-ajax-filter.php';

/* FRONT END */
if (!is_admin()) {
    // Definer en konstant for plugin path som brukes i template-functions.php
    define('KURSAGENTEN_PATH', KURSAG_PLUGIN_DIR);

    // Sørg for at funksjonen er inkludert
    require_once KURSAG_PLUGIN_DIR . '/public/templates/includes/template-functions.php';
    require_once KURSAG_PLUGIN_DIR . '/public/templates/includes/queries.php';
    require_once KURSAG_PLUGIN_DIR . '/public/templates/includes/template_taxonomy_functions.php';

    // Shortcodes content blocks
    require_once KURSAG_PLUGIN_DIR . '/public/shortcodes/course-list-shortcode.php';
    require_once KURSAG_PLUGIN_DIR . '/public/shortcodes/includes/grid-styles.php';
    require_once KURSAG_PLUGIN_DIR . '/public/shortcodes/coursecategories-shortcode.php';
    require_once KURSAG_PLUGIN_DIR . '/public/shortcodes/instructor-shortcode.php';
    require_once KURSAG_PLUGIN_DIR . '/public/shortcodes/related-courses-shortcode.php';
    require_once KURSAG_PLUGIN_DIR . '/public/shortcodes/course-location-shortcode.php';

    // Menus
    //require_once KURSAG_PLUGIN_DIR . '/public/menus/menu-taxonomies.php';
    require_once KURSAG_PLUGIN_DIR . '/public/shortcodes/menu-taxonomy-shortcode.php';

    // General Kursagenten shortcodes
    require_once KURSAG_PLUGIN_DIR . '/includes/misc/kursagenten-shortcodes.php';

    // Blocks
    //require_once KURSAG_PLUGIN_DIR . '/public/blocks/register-blocks.php';
}

    
    function kursagenten_enqueue_styles() {
        // Last inn base CSS for alle Kursagenten sider
        wp_enqueue_style(
            'kursagenten-course-style',
            KURSAG_PLUGIN_URL . '/assets/css/public/frontend-course-style.css',
            array(),
            KURSAG_VERSION
        );

        // Last inn datepicker CSS for alle Kursagenten sider
        wp_enqueue_style(
            'kursagenten-datepicker-style',
            KURSAG_PLUGIN_URL . '/assets/css/public/datepicker-caleran.min.css',
            array(),
            KURSAG_VERSION
        );

        // Last inn archive-course spesifikk CSS
        if (is_post_type_archive('course')) {
            // Oppdater variabelnavn for å matche nye innstillinger
            $design = get_option('kursagenten_archive_design', 'default');
            $layout = get_option('kursagenten_archive_layout', 'default');
            $list_type = get_option('kursagenten_archive_list_type', 'standard');

            // Last inn list-type-spesifikk CSS
            wp_enqueue_style(
                'kursagenten-list-type-' . $list_type,
                KURSAG_PLUGIN_URL . '/assets/css/public/list-' . $list_type . '.css',
                array(),
                KURSAG_VERSION
            );

            // Last inn design-spesifikk CSS hvis ikke default
            wp_enqueue_style(
                'kursagenten-archive-design-' . $design,
                KURSAG_PLUGIN_URL . '/assets/css/public/design-archive-' . $design . '.css',
                array('kursagenten-list-type-' . $list_type),
                KURSAG_VERSION
            );
        }

        // CSS for taxonomy templates - oppdater for å bruke nye innstillinger
        if (is_tax('coursecategory') || is_tax('course_location') || is_tax('instructors')) {
            $taxonomy = get_queried_object()->taxonomy;
            $override_enabled = get_option("kursagenten_taxonomy_{$taxonomy}_override", false);
            
            if ($override_enabled) {
                $design = get_option("kursagenten_taxonomy_{$taxonomy}_design", '');
                $layout = get_option("kursagenten_taxonomy_{$taxonomy}_layout", '');
                $list_type = get_option("kursagenten_taxonomy_{$taxonomy}_list_type", '');
                
                if (empty($design)) $design = get_option('kursagenten_taxonomy_design', 'default');
                if (empty($layout)) $layout = get_option('kursagenten_taxonomy_layout', 'default');
                if (empty($list_type)) $list_type = get_option('kursagenten_taxonomy_list_type', 'standard');
            } else {
                $design = get_option('kursagenten_taxonomy_design', 'default');
                $layout = get_option('kursagenten_taxonomy_layout', 'default');
                $list_type = get_option('kursagenten_taxonomy_list_type', 'standard');
            }

            // Last inn base CSS
            wp_enqueue_style(
                'kursagenten-taxonomy-base',
                KURSAG_PLUGIN_URL . '/assets/css/public/design-taxonomy-default.css',
                array(),
                KURSAG_VERSION
            );

            // Last inn design-spesifikk CSS
            if ($design !== 'default') {
                wp_enqueue_style(
                    'kursagenten-taxonomy-design-' . $design,
                    KURSAG_PLUGIN_URL . '/assets/css/public/design-taxonomy-' . $design . '.css',
                    array('kursagenten-taxonomy-base'),
                    KURSAG_VERSION
                );
            }


            // Last inn list-type-spesifikk CSS
            wp_enqueue_style(
                'kursagenten-taxonomy-list-' . $list_type,
                KURSAG_PLUGIN_URL . '/assets/css/public/list-' . $list_type . '.css',
                array('kursagenten-taxonomy-base'),
                KURSAG_VERSION
            );
        }

        // Single course styling
        if (is_singular('course')) {
            $design = get_option('kursagenten_single_design', 'default');

            // Last inn base CSS først
            wp_enqueue_style(
                'kursagenten-single-base',
                KURSAG_PLUGIN_URL . '/assets/css/public/frontend-course-style.css',
                array(),
                KURSAG_VERSION
            );

            // Deretter last inn design-spesifikk CSS
            wp_enqueue_style(
                'kursagenten-single-design-' . $design,
                KURSAG_PLUGIN_URL . '/assets/css/public/design-single-' . $design . '.css',
                array('kursagenten-single-base'),
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
        wp_enqueue_script('kursagenten-slidein-panel', plugins_url('assets/js/public/course-slidein-panel.js', __FILE__), array('jquery', 'kursagenten-iframe-resizer'), KURSAG_VERSION, true);
        wp_enqueue_script('kursagenten-ajax-filter', plugins_url('assets/js/public/course-ajax-filter.js', __FILE__), array('jquery', 'kursagenten-slidein-panel'), KURSAG_VERSION, true);

        wp_enqueue_script(
            'kursagenten-datepicker-moment',
            KURSAG_PLUGIN_URL . '/assets/js/public/datepicker/moment.min.js',
            array(),
            KURSAG_VERSION
        );

        wp_enqueue_script(
            'kursagenten-datepicker-script',
            KURSAG_PLUGIN_URL . '/assets/js/public/datepicker/caleran.min.js',
            ['kursagenten-datepicker-moment'],
            KURSAG_VERSION
        );


        wp_enqueue_script(
            'kursagenten-expand-content',
            KURSAG_PLUGIN_URL . '/assets/js/public/course-expand-content.js',
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

        
    }
    add_action('wp_enqueue_scripts', 'kursagenten_enqueue_scripts');
   

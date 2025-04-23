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

// Versjonshåndtering
if (defined('WP_DEBUG') && WP_DEBUG) {
    // Under utvikling - bruk versjonsnummer med tidsstempel
    define('KURSAG_VERSION', '1.0.3-dev-' . date('YmdHis'));
} else {
    // I produksjon - bruk vanlig versjonsnummer
    define('KURSAG_VERSION', '1.0.3');
}
// Husk å endre tekst i versjonslogg modalen i funksjonen render_changelog_modal()

/**
 * Versjonshåndtering og rollback funksjonalitet
 */
class Kursagenten_Version_Manager {
    private static $instance = null;
    private $current_version;
    private $previous_version;
    private $backup_dir;

        /**
     * Render versjonslogg modal
     */
    public function render_changelog_modal() {
        ?>
        <div id="kursagenten-changelog-modal" class="kursagenten-modal" style="display: none;">
            <div class="kursagenten-modal-content">
                <span class="kursagenten-modal-close">&times;</span>
                <h2><?php _e('Kursagenten Versjonslogg', 'kursagenten'); ?></h2>
                <div class="kursagenten-changelog">
                    <h3>1.0.3</h3>
                    <ul>
                        <li>Små justeringer i oppdateringsstøtte</li>
                    </ul>    
                    <h3>1.0.2</h3>
                    <ul>
                        <li>Lagt til automatisk oppdateringsstøtte via GitHub</li>
                        <li>Implementert versjonshåndtering med backup</li>
                        <li>Lagt til rollback-funksjonalitet</li>
                        <li>Forbedret admin-grensesnitt med innstillingslink</li>
                    </ul>

                    <h3>1.0.1</h3>
                    <ul>
                        <li>Første offentlige versjon</li>
                        <li>Grunnleggende kurshåndtering</li>
                        <li>Integrasjon med Kursagenten API</li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }

    public function __construct() {
        $this->current_version = KURSAG_VERSION;
        $this->previous_version = get_option('kursagenten_previous_version', '');
        $this->backup_dir = WP_CONTENT_DIR . '/kursagenten-backups/';
        
        // Registrer hooks
        add_action('admin_init', array($this, 'check_version'));
        add_action('admin_notices', array($this, 'version_notice'));
        
        // Registrer link-hooks i riktig rekkefølge
        add_filter('plugin_action_links_kursagenten/kursagenten.php', array($this, 'add_settings_link'), 10);
        add_filter('plugin_action_links_kursagenten/kursagenten.php', array($this, 'add_rollback_link'), 20);
        
        add_action('admin_init', array($this, 'handle_rollback'));
        add_action('admin_footer', array($this, 'render_changelog_modal'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Sjekk versjon og lagre backup ved oppdatering
     */
    public function check_version() {
        if ($this->previous_version !== $this->current_version) {
            // Lag backup før oppdatering
            $this->create_backup();
            
            // Lagre ny versjon
            update_option('kursagenten_previous_version', $this->current_version);
            
            // Kjør oppdateringsrutiner
            $this->run_update_routines();
        }
    }

    /**
     * Lag backup av plugin-filer
     */
    private function create_backup() {
        if (!file_exists($this->backup_dir)) {
            wp_mkdir_p($this->backup_dir);
        }

        $backup_file = $this->backup_dir . 'kursagenten-' . $this->previous_version . '-' . date('Y-m-d-H-i-s') . '.zip';
        
        // Lag ZIP av plugin-mappen
        $zip = new ZipArchive();
        if ($zip->open($backup_file, ZipArchive::CREATE) === TRUE) {
            $plugin_dir = plugin_dir_path(__FILE__);
            $this->add_dir_to_zip($zip, $plugin_dir, basename($plugin_dir));
            $zip->close();
        }
    }

    /**
     * Legg til mappe i ZIP
     */
    private function add_dir_to_zip($zip, $dir, $base_name) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = $base_name . '/' . substr($filePath, strlen($dir));
                $zip->addFile($filePath, $relativePath);
            }
        }
    }

    /**
     * Kjør oppdateringsrutiner
     */
    private function run_update_routines() {
        // Implementer spesifikke oppdateringsrutiner her
        // For eksempel database-migreringer eller filendringer
    }

    /**
     * Rollback til tidligere versjon
     */
    public function rollback() {
        if (empty($this->previous_version)) {
            return false;
        }

        // Finn siste backup
        $backups = glob($this->backup_dir . 'kursagenten-' . $this->previous_version . '*.zip');
        if (empty($backups)) {
            return false;
        }

        $latest_backup = end($backups);
        
        // Pakk ut backup
        $zip = new ZipArchive();
        if ($zip->open($latest_backup) === TRUE) {
            $zip->extractTo(WP_PLUGIN_DIR);
            $zip->close();
            
            // Oppdater versjonsnummer
            update_option('kursagenten_previous_version', $this->previous_version);
            
            return true;
        }

        return false;
    }

    /**
     * Legg til innstillingslink i utvidelseslisten
     */
    public function add_settings_link($links) {
        // Lag en ny array med innstillingslink først
        $new_links = array();
        
        // Legg til innstillingslink
        $settings_url = admin_url('admin.php?page=kursagenten');
        $new_links[] = sprintf(
            '<a href="%s">%s</a>',
            esc_url($settings_url),
            __('Innstillinger', 'kursagenten')
        );

        // Legg til versjonslogg-link
        $new_links[] = sprintf(
            '<a href="#" class="kursagenten-changelog-link">%s</a>',
            __('Versjonslogg', 'kursagenten')
        );

        // Legg til sjekk oppdateringer link
        $new_links[] = sprintf(
            '<a href="%s">%s</a>',
            wp_nonce_url(admin_url('plugins.php?action=kursagenten_check_updates'), 'kursagenten_check_updates'),
            __('Sjekk oppdateringer', 'kursagenten')
        );
        
        // Legg til alle eksisterende lenker
        foreach ($links as $link) {
            $new_links[] = $link;
        }
        
        return $new_links;
    }

    /**
     * Legg til rollback-link i utvidelseslisten
     */
    public function add_rollback_link($links) {
        // Legg til rollback-link hvis det finnes tidligere versjon
        if (!empty($this->previous_version) && !strpos($this->previous_version, 'dev')) {
            // Fjern eventuelt tidsstempel fra versjonsnummeret
            $clean_version = preg_replace('/-dev-\d+$/', '', $this->previous_version);
            
            $rollback_url = wp_nonce_url(
                admin_url('plugins.php?action=kursagenten_rollback'),
                'kursagenten_rollback_' . get_current_user_id()
            );
            $links[] = sprintf(
                '<a href="%s" class="rollback-link" style="color: #dc3232;">%s</a>',
                esc_url($rollback_url),
                sprintf(__('Rollback til %s', 'kursagenten'), $clean_version)
            );
        }
        return $links;
    }

    /**
     * Håndter rollback-forespørsel
     */
    public function handle_rollback() {
        if (
            isset($_GET['action']) && 
            $_GET['action'] === 'kursagenten_rollback' && 
            check_admin_referer('kursagenten_rollback_' . get_current_user_id())
        ) {
            if ($this->rollback()) {
                wp_redirect(admin_url('plugins.php?rollback_success=1'));
                exit;
            } else {
                wp_redirect(admin_url('plugins.php?rollback_failed=1'));
                exit;
            }
        }

        // Håndter sjekk oppdateringer
        if (
            isset($_GET['action']) && 
            $_GET['action'] === 'kursagenten_check_updates' && 
            check_admin_referer('kursagenten_check_updates')
        ) {
            // Tving oppdateringssjekk
            delete_site_transient('update_plugins');
            wp_update_plugins();
            
            // Hent oppdateringsinformasjon
            $update_plugins = get_site_transient('update_plugins');
            $has_update = false;
            $update_info = '';
            $debug_info = array();
            
            // Sjekk GitHub direkte
            $github_updater = new Kursagenten_GitHub_Updater(__FILE__);
            $github_response = $github_updater->get_repository_info();
            
            if ($github_response) {
                $debug_info[] = 'GitHub versjon: ' . $github_response['tag_name'];
                $debug_info[] = 'Nåværende versjon: ' . $this->current_version;
                
                if (version_compare($github_response['tag_name'], $this->current_version, '>')) {
                    $has_update = true;
                    $update_info = sprintf(
                        'Ny versjon %s er tilgjengelig. Du kjører versjon %s.',
                        $github_response['tag_name'],
                        $this->current_version
                    );
                }
            } else {
                $debug_info[] = 'Kunne ikke hente GitHub informasjon';
            }
            
            // Sjekk WordPress oppdateringssystem
            if (!empty($update_plugins->response)) {
                foreach ($update_plugins->response as $plugin_file => $plugin_data) {
                    if ($plugin_file === $this->slug) {
                        $has_update = true;
                        $update_info = sprintf(
                            'Ny versjon %s er tilgjengelig. Du kjører versjon %s.',
                            $plugin_data->new_version,
                            $this->current_version
                        );
                        $debug_info[] = 'WordPress fant oppdatering: ' . $plugin_data->new_version;
                        break;
                    }
                }
            } else {
                $debug_info[] = 'WordPress fant ingen oppdateringer';
            }
            
            // Lagre resultatet i en midlertidig option
            update_option('kursagenten_update_check_result', array(
                'has_update' => $has_update,
                'message' => $update_info ?: 'Ingen nye oppdateringer funnet.',
                'debug' => $debug_info
            ));
            
            wp_redirect(admin_url('plugins.php?update_check=1'));
            exit;
        }
    }

    /**
     * Vis versjonsvarsel i admin
     */
    public function version_notice() {
        if (isset($_GET['rollback_success'])) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php _e('Rollback til tidligere versjon vellykket!', 'kursagenten'); ?></p>
            </div>
            <?php
        } elseif (isset($_GET['rollback_failed'])) {
            ?>
            <div class="notice notice-error is-dismissible">
                <p><?php _e('Rollback feilet. Vennligst prøv igjen eller kontakt support.', 'kursagenten'); ?></p>
            </div>
            <?php
        } elseif (isset($_GET['update_check'])) {
            $result = get_option('kursagenten_update_check_result', array());
            delete_option('kursagenten_update_check_result'); // Fjern midlertidig data
            
            if (!empty($result)) {
                $notice_class = $result['has_update'] ? 'notice-warning' : 'notice-info';
                ?>
                <div class="notice <?php echo $notice_class; ?> is-dismissible">
                    <p><?php echo esc_html($result['message']); ?></p>
                    <?php if ($result['has_update']) : ?>
                        <p>
                            <a href="<?php echo admin_url('plugins.php'); ?>" class="button button-primary">
                                <?php _e('Gå til oppdateringer', 'kursagenten'); ?>
                            </a>
                        </p>
                    <?php endif; ?>
                    <?php if (defined('WP_DEBUG') && WP_DEBUG && !empty($result['debug'])) : ?>
                        <div style="margin-top: 10px; padding: 10px; background: #f8f8f8; border: 1px solid #ddd;">
                            <strong>Debug informasjon:</strong>
                            <ul style="margin: 5px 0;">
                                <?php foreach ($result['debug'] as $debug_line) : ?>
                                    <li><?php echo esc_html($debug_line); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
                <?php
            }
        } elseif ($this->previous_version !== $this->current_version && !get_option('kursagenten_version_notice_shown')) {
            // Fjern tidsstempel fra versjonsnummerene for visning
            $clean_current = preg_replace('/-dev-\d+$/', '', $this->current_version);
            $clean_previous = preg_replace('/-dev-\d+$/', '', $this->previous_version);
            ?>
            <div class="notice notice-info is-dismissible kursagenten-version-notice">
                <p><?php printf(
                    __('Kursagenten er oppdatert fra versjon %s til %s.', 'kursagenten'),
                    $clean_previous,
                    $clean_current
                ); ?></p>
            </div>
            <?php
            // Marker at varselet er vist
            update_option('kursagenten_version_notice_shown', true);
        }
    }

    /**
     * Last inn admin scripts
     */
    public function enqueue_admin_scripts() {
        wp_enqueue_style(
            'kursagenten-admin-style',
            KURSAG_PLUGIN_URL . 'assets/css/admin/kursagenten-admin.css',
            array(),
            KURSAG_VERSION
        );

        wp_enqueue_script(
            'kursagenten-admin-script',
            KURSAG_PLUGIN_URL . 'assets/js/admin/kursagenten-admin.js',
            array('jquery'),
            KURSAG_VERSION,
            true
        );
    }


}

// Initialiser versjonshåndtering
Kursagenten_Version_Manager::get_instance();

/**
 * Handles plugin updates from GitHub
 */
class Kursagenten_GitHub_Updater {
    private $slug;
    private $plugin;
    private $username;
    private $repo;
    private $plugin_data;
    private $access_token;
    private $github_response;

    public function __construct($plugin_file) {
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_update'));
        add_filter('plugins_api', array($this, 'plugin_popup'), 10, 3);
        add_filter('upgrader_post_install', array($this, 'after_install'), 10, 3);
        
        $this->plugin = $plugin_file;
        $this->slug = plugin_basename($this->plugin);
        $this->username = 'Kursagenten'; // Organisasjonsnavn
        $this->repo = 'WP-Kursagenten-plugin'; // Repository navn
        $this->access_token = ''; // GitHub access token (må settes)
    }

    // Sjekk etter oppdateringer
    public function check_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        $this->get_repository_info();

        if (false !== $this->github_response) {
            $plugin_data = get_plugin_data($this->plugin);
            $current_version = $plugin_data['Version'];
            $github_version = $this->github_response['tag_name'];

            if (version_compare($github_version, $current_version, '>')) {
                $new_files = array(
                    'slug' => $this->slug,
                    'plugin' => $this->plugin,
                    'new_version' => $github_version,
                    'url' => $this->github_response['html_url'],
                    'package' => $this->github_response['zipball_url']
                );
                $transient->response[$this->slug] = (object) $new_files;
            }
        }

        return $transient;
    }

    // Hent repository informasjon
    public function get_repository_info() {
        if (!empty($this->github_response)) {
            return $this->github_response;
        }

        $url = sprintf('https://api.github.com/repos/%s/%s/releases/latest', 
            $this->username, 
            $this->repo
        );

        $headers = array(
            'Accept' => 'application/json',
            'User-Agent' => 'WordPress/' . get_bloginfo('version')
        );

        // Legg til token hvis det er satt
        if (!empty($this->access_token)) {
            $headers['Authorization'] = 'token ' . $this->access_token;
        }

        $response = wp_remote_get($url, array(
            'timeout' => 15,
            'headers' => $headers
        ));

        if (is_wp_error($response)) {
            error_log('GitHub API Error: ' . $response->get_error_message());
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('GitHub API JSON Error: ' . json_last_error_msg());
            return false;
        }

        if (!is_array($data) || empty($data['tag_name'])) {
            error_log('GitHub API Invalid Response: ' . print_r($data, true));
            return false;
        }

        $this->github_response = $data;
        return $data;
    }

    // Vis popup med oppdateringsinformasjon
    public function plugin_popup($result, $action, $args) {
        if ('plugin_information' !== $action ||
            $args->slug !== $this->slug) {
            return $result;
        }

        $this->get_repository_info();

        if (false !== $this->github_response) {
            $plugin_data = get_plugin_data($this->plugin);
            
            $plugin_info = array(
                'name' => $plugin_data['Name'],
                'slug' => $this->slug,
                'version' => $this->github_response['tag_name'],
                'author' => $plugin_data['Author'],
                'homepage' => $plugin_data['PluginURI'],
                'requires' => '6.0',
                'tested' => '6.4',
                'last_updated' => $this->github_response['published_at'],
                'sections' => array(
                    'description' => $plugin_data['Description'],
                    'changelog' => $this->github_response['body']
                ),
                'download_link' => $this->github_response['zipball_url']
            );
            return (object) $plugin_info;
        }

        return $result;
    }

    // Etter installasjon
    public function after_install($response, $hook_extra, $result) {
        global $wp_filesystem;

        $plugin_folder = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . dirname($this->slug);
        $wp_filesystem->move($result['destination'], $plugin_folder);
        $result['destination'] = $plugin_folder;

        return $result;
    }
}

// Initialiser oppdateringsklassen hvis vi er i admin
if (is_admin()) {
    new Kursagenten_GitHub_Updater(__FILE__);
}

if (defined('WP_DEBUG') && WP_DEBUG) {
    // Bare sett cache headers under utvikling
    add_action('init', function() {
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
    });
}

if (!defined('ABSPATH')) {
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
define('KURSAG_MIN_PHP',     '7.4');
define('KURSAG_MIN_WP',      '6.0');

// Filstier
define('KURSAG_PLUGIN_FILE', __FILE__);
define('KURSAG_PLUGIN_BASE', plugin_basename(KURSAG_PLUGIN_FILE));
define('KURSAG_PLUGIN_DIR',  plugin_dir_path(KURSAG_PLUGIN_FILE));
define('KURSAG_PLUGIN_URL',  plugin_dir_url(KURSAG_PLUGIN_FILE));
define('KURSAGENTEN_IMAGE_BASE_URL_INSTRUCTOR', 'https://www.kursagenten.no/UserImages/');


register_activation_hook(__FILE__, 'kursagenten_activate');
register_deactivation_hook(__FILE__, 'kursagenten_deactivate');

// Last inn hooks
//require_once plugin_dir_path(__FILE__) . 'includes/hooks.php';

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
        
        // Identifiser taksonomi basert på URL-sti
        $taxonomy = '';
        switch ($taxonomy_slug) {
            case 'kurskategori':
                $taxonomy = 'coursecategory';
                break;
            case 'kurssted':
                $taxonomy = 'course_location';
                break;
            case 'instruktorer':
                $taxonomy = 'instructors';
                break;
        }
        

        
        // Sjekk om vi har gyldig taksonomi og term
        if (!empty($taxonomy) && !empty($term_slug)) {
            // Hent term basert på slug og taksonomi
            $term = get_term_by('slug', $term_slug, $taxonomy);
            
            if ($term) {
                error_log('Taxonomy fix: Found term: ' . $term->name . ' (ID: ' . $term->term_id . ')');
                
                // Oppdater globale variabler
                global $wp_query;
                $wp_query->queried_object = $term;
                $wp_query->queried_object_id = $term->term_id;
                
                error_log('Taxonomy fix: Updated queried object to: ' . $wp_query->queried_object->name);
            } else {
                error_log('Taxonomy fix: Term not found with slug: ' . $term_slug . ' in taxonomy: ' . $taxonomy);
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
require_once KURSAG_PLUGIN_DIR . '/includes/admin-bar-links.php';

// Last inn hovedklassen og CSS output
require_once KURSAG_PLUGIN_DIR . '/includes/class-kursagenten.php';
require_once KURSAG_PLUGIN_DIR . '/includes/class-kursagenten-css-output.php';

// Initialiser hovedklassen
$kursagenten = new Kursagenten();

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
                wp_enqueue_script( 'custom-admin-upload-script', plugin_dir_url(__FILE__) . 'assets/js/admin/image-upload.js', array('jquery'), '1.0.3',  true  );
                wp_enqueue_script( 'custom-admin-utilities-script', plugin_dir_url(__FILE__) . 'assets/js/admin/admin-utilities.js', array('jquery'), '1.0.317',  true  );  
                wp_enqueue_style( 'custom-admin-style', plugin_dir_url(__FILE__) . 'assets/css/admin/kursagenten-admin.css', array(), '1.0.609' );
            }
        }
    }
    add_action('admin_enqueue_scripts', 'enqueue_custom_admin_script');



 /* FRONT END */   

// Definer en konstant for plugin path som brukes i template-functions.php
define('KURSAGENTEN_PATH', KURSAG_PLUGIN_DIR);

// Sørg for at funksjonen er inkludert
require_once KURSAG_PLUGIN_DIR . '/public/templates/includes/template-functions.php';
require_once KURSAG_PLUGIN_DIR . '/public/templates/includes/queries.php';
require_once KURSAG_PLUGIN_DIR . '/public/templates/includes/course-ajax-filter.php';
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
                    KURSAG_PLUGIN_URL . '/assets/css/public/design-' . $design . '.css',
                    array('kursagenten-taxonomy-base'),
                    KURSAG_VERSION
                );
            }

            // Last inn layout-spesifikk CSS
            /*
            if ($layout !== 'default') {
                wp_enqueue_style(
                    'kursagenten-taxonomy-layout-' . $layout,
                    KURSAG_PLUGIN_URL . '/assets/css/layout-' . $layout . '.css',
                    array('kursagenten-taxonomy-base'),
                    KURSAG_VERSION
                );
            }
            */

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

        // Instructor styling (beholdt som den er)
        /*
        if (is_singular('instructor') || is_post_type_archive('instructor')) {
            wp_enqueue_style(
                'kursagenten-instructor-style',
                KURSAG_PLUGIN_URL . '/assets/css/public/instructor-style.css',
                array(),
                KURSAG_VERSION
            );
        }
        */
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
            'kursagenten-accordion_script',
            KURSAG_PLUGIN_URL . '/assets/js/public/course-accordion.js',
            array(),
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

        
       /* wp_enqueue_script(
            'kursagenten-filter-mobile',
            KURSAG_PLUGIN_URL . '/assets/js/public/course-filter-mobile.js',
            array(),
            KURSAG_VERSION
        );*/
    }
    add_action('wp_enqueue_scripts', 'kursagenten_enqueue_scripts');
   

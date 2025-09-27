<?php

namespace KursagentenUpdater;

class Updater {

    public $plugin_slug;
    public $version;
    public $cache_key;
    public $cache_allowed;

    public function __construct() {

        if ( defined( 'KURSAG_DEV_MODE' ) ) {
            add_filter('https_ssl_verify', '__return_false');
            add_filter('https_local_ssl_verify', '__return_false');
            add_filter('http_request_host_is_external', '__return_true');
        }

        $this->plugin_slug   = 'kursagenten';
        $this->version       = KURSAG_VERSION;
        $this->cache_key     = 'kursagenten_updater';
        $this->cache_allowed = true;

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Kursagenten Updater konstruktør:');
            error_log('- Plugin slug: ' . $this->plugin_slug);
            error_log('- Version: ' . $this->version);
            error_log('- Cache key: ' . $this->cache_key);
        }

        add_filter( 'plugins_api', [ $this, 'info' ], 20, 3 );
        add_filter( 'site_transient_update_plugins', [ $this, 'update' ] );
        add_action( 'upgrader_process_complete', [ $this, 'purge' ], 10, 2 );
        add_filter( 'plugin_row_meta', [ $this, 'add_update_check_link' ], 10, 2 );
        add_filter( 'plugin_action_links_' . $this->plugin_slug . '/' . $this->plugin_slug . '.php', [ $this, 'add_settings_link' ] );

        // Legg til hooks for oppdateringsprosessen
        add_action('upgrader_pre_install', [$this, 'pre_install'], 10, 2);
        add_action('upgrader_post_install', [$this, 'post_install'], 10, 2);
        add_action('upgrader_process_complete', [$this, 'upgrade_complete'], 10, 2);

        // Legg til AJAX-håndtering for oppdateringssjekk
        add_action('wp_ajax_kursagenten_check_updates', [$this, 'ajax_check_updates']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);

        // Debug notice fjernet for bedre ytelse

    }

    public function request(){

        // Sjekk cache først for å unngå unødvendige HTTP-forespørsler
        $cached = get_transient($this->cache_key);
        if ($cached !== false) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Kursagenten: Bruker cached data');
            }
            return $cached;
        }

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Kursagenten: Starter oppdateringssjekk...');
        }

        $remote = wp_remote_get( 'https://admin.lanseres.no/plugin-updates/kursagenten.json', [
                'timeout' => 10,
                'headers' => [
                    'Accept' => 'application/json'
                ]
            ]
        );

        if ( is_wp_error( $remote ) ) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Kursagenten oppdateringsfeil: ' . $remote->get_error_message());
            }
            return false;
        }

        if ( 200 !== wp_remote_retrieve_response_code( $remote ) ) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Kursagenten oppdateringsfeil: HTTP ' . wp_remote_retrieve_response_code( $remote ));
            }
            return false;
        }

        if ( empty( wp_remote_retrieve_body( $remote ) ) ) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Kursagenten oppdateringsfeil: Tom respons fra server');
            }
            return false;
        }

        $remote = json_decode( wp_remote_retrieve_body( $remote ) );

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Kursagenten oppdateringssjekk:');
            error_log('Nåværende versjon: ' . $this->version);
            error_log('Tilgjengelig versjon: ' . (isset($remote->version) ? $remote->version : 'IKKE SATT'));
            error_log('Versjonssammenligning: ' . version_compare( $this->version, $remote->version, '<' ));
            error_log('JSON respons: ' . print_r($remote, true));
        }

        // Cache resultatet i 1 time for å unngå unødvendige HTTP-forespørsler
        if ($remote) {
            set_transient($this->cache_key, $remote, HOUR_IN_SECONDS);
        }

        return $remote;

    }

    function info( $response, $action, $args ) {

        // do nothing if you're not getting plugin information right now
        if ( 'plugin_information' !== $action ) {
            return $response;
        }

        // do nothing if it is not our plugin
        if ( empty( $args->slug ) || $this->plugin_slug !== $args->slug ) {
            return $response;
        }

        // get updates
        $remote = $this->request();

        if ( ! $remote ) {
            return $response;
        }

        $response = new \stdClass();

        $response->name           = $remote->name;
        $response->slug           = $remote->slug;
        $response->version        = $remote->version;
        $response->tested         = $remote->tested;
        $response->requires       = $remote->requires;
        $response->author         = $remote->author;
        $response->author_profile = $remote->author_profile;
        $response->donate_link    = $remote->donate_link;
        $response->homepage       = $remote->homepage;
        $response->download_link  = $remote->download_url;
        $response->trunk          = $remote->download_url;
        $response->requires_php   = $remote->requires_php;
        $response->last_updated   = $remote->last_updated;

        // Bygg seksjoner og overstyr changelog fra lokal fil om tilgjengelig
        $local_changelog = $this->get_local_changelog_html();
        $response->sections = [
            'description'  => isset($remote->sections->description) ? $remote->sections->description : (defined('KURSAG_DESCRIPTION') ? KURSAG_DESCRIPTION : ''),
            'installation' => isset($remote->sections->installation) ? $remote->sections->installation : (defined('KURSAG_INSTALLATION') ? KURSAG_INSTALLATION : 'Installasjonssteg kommer her.'),
            'changelog'    => !empty($local_changelog) ? $local_changelog : (isset($remote->sections->changelog) ? $remote->sections->changelog : '')
        ];
        
        // Legg til nødvendige felter for WordPress oppdateringssystem
        $response->icons = [
            '1x' => KURSAG_PLUGIN_URL . 'assets/images/placeholder-kurs.jpg',
            '2x' => KURSAG_PLUGIN_URL . 'assets/images/placeholder-kurs.jpg'
        ];

        if ( ! empty( $remote->banners ) ) {
            $response->banners = [
                'low'  => $remote->banners->low,
                'high' => $remote->banners->high
            ];
        }

        return $response;

    }

    public function update( $transient ) {

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Kursagenten: update() kalt med transient: ' . print_r($transient, true));
        }

        if ( empty($transient->checked ) ) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Kursagenten: Ingen checked plugins, returnerer transient');
            }
            return $transient;
        }

        $remote = $this->request();

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Kursagenten: Remote data: ' . print_r($remote, true));
        }

        if ( $remote && 
             $this->compare_versions($this->version, $remote->version) && 
             version_compare( $remote->requires, get_bloginfo( 'version' ), '<=' ) && 
             version_compare( $remote->requires_php, PHP_VERSION, '<' ) ) {
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Kursagenten: Oppdatering funnet! Lager response objekt...');
            }
            
            $response              = new \stdClass();
            $response->slug        = $this->plugin_slug;
            $response->plugin      = "{$this->plugin_slug}/{$this->plugin_slug}.php";
            $response->new_version = $remote->version;
            $response->tested      = $remote->tested;
            $response->package     = $remote->download_url;
            $response->id          = "{$this->plugin_slug}/{$this->plugin_slug}.php";
            $response->url         = isset($remote->homepage) ? $remote->homepage : '';
            $response->compatibility = new \stdClass();
            $response->compatibility->{$remote->tested} = new \stdClass();
            $response->compatibility->{$remote->tested}->{$remote->version} = new \stdClass();
            // Sett en kort oppgraderingsmelding
            if (!empty($remote->upgrade_notice)) {
                $response->upgrade_notice = $remote->upgrade_notice;
            } else {
                $short_notice = $this->get_local_changelog_short_notice();
                if (!empty($short_notice)) {
                    $response->upgrade_notice = $short_notice;
                }
            }

            $transient->response[ $response->plugin ] = $response;
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Kursagenten: Response lagt til transient: ' . print_r($response, true));
            }
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Kursagenten: Ingen oppdatering funnet. Sjekker betingelser:');
                error_log('- Remote eksisterer: ' . ($remote ? 'JA' : 'NEI'));
                if ($remote) {
                    error_log('- Versjonssammenligning: ' . ($this->compare_versions($this->version, $remote->version) ? 'JA' : 'NEI'));
                    error_log('- WP versjon OK: ' . (version_compare( $remote->requires, get_bloginfo( 'version' ), '<=' ) ? 'JA' : 'NEI'));
                    error_log('- PHP versjon OK: ' . (version_compare( $remote->requires_php, PHP_VERSION, '<' ) ? 'JA' : 'NEI'));
                }
            }
        }

        return $transient;

    }

    /**
     * Hent lokal CHANGELOG som HTML for visning i plugin-informasjon.
     */
    private function get_local_changelog_html() {
        $paths = [
            \KURSAG_PLUGIN_DIR . '/CHANGELOG.md',
            \KURSAG_PLUGIN_DIR . '/readme.txt'
        ];
        foreach ($paths as $path) {
            if (file_exists($path) && is_readable($path)) {
                $content = file_get_contents($path);
                if ($content !== false && !empty($content)) {
                    return $this->markdown_to_basic_html($content);
                }
            }
        }
        return '';
    }

    /**
     * Generer en kort oppgraderingsmelding fra første punkter i CHANGELOG.
     */
    private function get_local_changelog_short_notice() {
        $path = \KURSAG_PLUGIN_DIR . '/CHANGELOG.md';
        if (!file_exists($path) || !is_readable($path)) {
            return '';
        }
        $content = file_get_contents($path);
        if ($content === false || empty($content)) {
            return '';
        }
        $lines = preg_split('/\r?\n/', trim($content));
        $buffer = [];
        $started = false;
        foreach ($lines as $line) {
            if (!$started) {
                if (preg_match('/^(##\s*)?\d+\.\d+\.\d+/', trim($line))) {
                    $started = true;
                }
                continue;
            }
            if (trim($line) === '') {
                break;
            }
            $buffer[] = trim($line, "- *\t ");
            if (count($buffer) >= 2) {
                break;
            }
        }
        $notice = implode(' ', $buffer);
        $notice = trim($notice);
        if (strlen($notice) > 300) {
            $notice = substr($notice, 0, 297) . '...';
        }
        return $notice;
    }

    /**
     * Enkel Markdown→HTML konvertering for modal.
     */
    private function markdown_to_basic_html($markdown) {
        // Overskrifter
        $markdown = preg_replace('/^######\s*(.+)$/m', '<h6>$1</h6>', $markdown);
        $markdown = preg_replace('/^#####\s*(.+)$/m', '<h5>$1</h5>', $markdown);
        $markdown = preg_replace('/^####\s*(.+)$/m', '<h4>$1</h4>', $markdown);
        $markdown = preg_replace('/^###\s*(.+)$/m', '<h3>$1</h3>', $markdown);
        $markdown = preg_replace('/^##\s*(.+)$/m', '<h2>$1</h2>', $markdown);
        $markdown = preg_replace('/^#\s*(.+)$/m', '<h1>$1</h1>', $markdown);
        // Punktlister
        $markdown = preg_replace('/^\s*[-*]\s+(.+)$/m', '<li>$1</li>', $markdown);
        // Pakk første sammenhengende li-blokk i <ul>
        $markdown = preg_replace('/((?:<li>.*?<\/li>\s*)+)/s', '<ul>$1</ul>', $markdown, 1);
        // Del avsnitt
        $parts = array_map('trim', preg_split('/\n\n+/', $markdown));
        $parts = array_map(function($p){
            if (preg_match('/^<h[1-6]>|^<ul>|^<li>/', $p)) {
                return $p;
            }
            return '<p>' . nl2br($p) . '</p>';
        }, $parts);
        return implode("\n", $parts);
    }

    public function purge( $upgrader, $options ) {

        if ( $this->cache_allowed && 'update' === $options['action'] && 'plugin' === $options[ 'type' ] ) {
            // just clean the cache when new plugin version is installed
            delete_transient( $this->cache_key );
        }

    }

    /**
     * Legg til "Innstillinger" link under plugin-navnet
     */
    public function add_settings_link($links) {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url('admin.php?page=kursagenten'),
            __('Innstillinger', 'kursagenten')
        );
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Legg til "Sjekk for oppdateringer" link i plugin-listen
     */
    public function add_update_check_link($links, $file) {
        if ($file === $this->plugin_slug . '/' . $this->plugin_slug . '.php') {
            $remote = $this->request();
            $has_update = $remote && $this->compare_versions($this->version, $remote->version);
            
            // Legg til "Vis detaljer" link kun hvis det ikke er oppdateringer (WordPress core legger til sin egen)
            if (!$has_update) {
                $details_url = admin_url('plugin-install.php?tab=plugin-information&plugin=' . $this->plugin_slug . '&section=changelog');
                $details_url = add_query_arg([
                    'TB_iframe' => 'true',
                    'width' => 600,
                    'height' => 800,
                ], $details_url);
                
                $links[] = sprintf(
                    '<a href="%s" class="thickbox open-plugin-details-modal">%s</a>',
                    esc_url($details_url),
                    __('Vis detaljer', 'kursagenten')
                );
            }
            
            // Legg til "Sjekk for oppdateringer" link som bruker AJAX
            $links[] = sprintf(
                '<a href="#" onclick="kursagentenCheckUpdates(); return false;">%s</a>',
                __('Sjekk for oppdateringer', 'kursagenten')
            );
        }
        return $links;
    }

    /**
     * Hjelpefunksjon for å sammenligne versjoner
     */
    private function compare_versions($current, $remote) {
        // Fjern dev-suffix fra nåværende versjon
        $current_clean = preg_replace('/-dev.*$/', '', $current);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Kursagenten compare_versions:');
            error_log('- Current: ' . $current);
            error_log('- Current clean: ' . $current_clean);
            error_log('- Remote: ' . $remote);
            error_log('- version_compare result: ' . version_compare($current_clean, $remote, '<'));
        }
        
        return version_compare($current_clean, $remote, '<');
    }


    /**
     * Forbered oppdatering
     */
    public function pre_install($true, $args) {
        if ($args['plugin'] !== $this->plugin_slug . '/' . $this->plugin_slug . '.php') {
            return $true;
        }

        // Opprett backup-mappe hvis den ikke eksisterer
        $backup_dir = WP_CONTENT_DIR . '/upgrade-temp-backup';
        if (!file_exists($backup_dir)) {
            wp_mkdir_p($backup_dir);
        }

        // Sjekk skrivetillatelser
        if (!is_writable($backup_dir)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error">';
                echo '<p>' . __('Kunne ikke opprette backup-mappe. Sjekk filrettigheter.', 'kursagenten') . '</p>';
                echo '</div>';
            });
            return new \WP_Error('backup_failed', __('Kunne ikke opprette backup-mappe.', 'kursagenten'));
        }

        return $true;
    }

    /**
     * Håndter post-installasjon
     */
    public function post_install($true, $args) {
        if ($args['plugin'] !== $this->plugin_slug . '/' . $this->plugin_slug . '.php') {
            return $true;
        }

        // Rydd opp i backup-mappe
        $backup_dir = WP_CONTENT_DIR . '/upgrade-temp-backup';
        if (file_exists($backup_dir)) {
            $this->recursive_remove_directory($backup_dir);
        }

        return $true;
    }

    /**
     * Håndter fullført oppdatering
     */
    public function upgrade_complete($upgrader, $options) {
        if ($options['action'] === 'update' && $options['type'] === 'plugin') {
            foreach ($options['plugins'] as $plugin) {
                if ($plugin === $this->plugin_slug . '/' . $this->plugin_slug . '.php') {
                    // Oppdatering fullført
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-success">';
                        echo '<p>' . __('Kursagenten ble oppdatert.', 'kursagenten') . '</p>';
                        echo '</div>';
                    });
                }
            }
        }
    }

    /**
     * Hjelpefunksjon for å fjerne mappe rekursivt
     */
    private function recursive_remove_directory($directory) {
        if (is_dir($directory)) {
            $objects = scandir($directory);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($directory . "/" . $object)) {
                        $this->recursive_remove_directory($directory . "/" . $object);
                    } else {
                        unlink($directory . "/" . $object);
                    }
                }
            }
            rmdir($directory);
        }
    }

    /**
     * Enqueue admin scripts for oppdateringssjekk
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'plugins.php') {
            return;
        }
        
        wp_add_inline_script('jquery', '
            function kursagentenCheckUpdates() {
                var $link = jQuery("a[onclick*=\"kursagentenCheckUpdates\"]");
                $link.text("Sjekker...").prop("disabled", true);
                
                jQuery.post(ajaxurl, {
                    action: "kursagenten_check_updates",
                    nonce: "' . wp_create_nonce('kursagenten_check_updates') . '"
                }, function(response) {
                    if (response.success) {
                        alert("Oppdateringssjekk fullført!");
                        location.reload();
                    } else {
                        alert("Feil ved oppdateringssjekk: " + response.data);
                    }
                    $link.text("Sjekk for oppdateringer").prop("disabled", false);
                });
            }
        ');
    }

    /**
     * AJAX-håndtering for oppdateringssjekk
     */
    public function ajax_check_updates() {
        check_ajax_referer('kursagenten_check_updates', 'nonce');
        
        // Slett cache for å tvinge ny sjekk
        delete_transient($this->cache_key);
        
        // Kjør oppdateringssjekk
        $remote = $this->request();
        
        if ($remote) {
            wp_send_json_success('Oppdateringssjekk fullført');
        } else {
            wp_send_json_error('Kunne ikke hente oppdateringsinformasjon');
        }
    }

}
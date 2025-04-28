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

        $this->plugin_slug   = dirname ( plugin_basename( __DIR__ ) );
        $this->version       = KURSAG_VERSION;
        $this->cache_key     = 'kursagenten_updater';
        $this->cache_allowed = false;

        add_filter( 'plugins_api', [ $this, 'info' ], 20, 3 );
        add_filter( 'site_transient_update_plugins', [ $this, 'update' ] );
        add_action( 'upgrader_process_complete', [ $this, 'purge' ], 10, 2 );
        add_filter( 'plugin_row_meta', [ $this, 'add_update_check_link' ], 10, 2 );
        
        // Legg til admin-side for oppdateringer
        add_action('admin_menu', [$this, 'add_update_page']);
        
        // Legg til admin-notice for oppdateringer
        add_action('admin_notices', [$this, 'show_update_notice']);

        // Legg til hooks for oppdateringsprosessen
        add_action('upgrader_pre_install', [$this, 'pre_install'], 10, 2);
        add_action('upgrader_post_install', [$this, 'post_install'], 10, 2);
        add_action('upgrader_process_complete', [$this, 'upgrade_complete'], 10, 2);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('admin_notices', function() {
                $remote = $this->request();
                if ($remote) {
                    echo '<div class="notice notice-info">';
                    echo '<p>Kursagenten oppdateringssjekk:</p>';
                    echo '<ul>';
                    echo '<li>Nåværende versjon: ' . esc_html($this->version) . '</li>';
                    echo '<li>Tilgjengelig versjon: ' . esc_html($remote->version) . '</li>';
                    echo '</ul>';
                    echo '</div>';
                }
            });
        }

    }

    public function request(){

        delete_transient($this->cache_key);

        $remote = wp_remote_get( 'https://plugin.lanseres.no/plugin-updates/kursagenten.json', [
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
            error_log('Tilgjengelig versjon: ' . $remote->version);
            error_log('Versjonssammenligning: ' . version_compare( $this->version, $remote->version, '<' ));
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

        $response->sections = [
            'description'  => $remote->sections->description,
            'installation' => $remote->sections->installation,
            'changelog'    => $remote->sections->changelog
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

        if ( empty($transient->checked ) ) {
            return $transient;
        }

        $remote = $this->request();

        if ( $remote && 
             $this->compare_versions($this->version, $remote->version) && 
             version_compare( $remote->requires, get_bloginfo( 'version' ), '<=' ) && 
             version_compare( $remote->requires_php, PHP_VERSION, '<' ) ) {
            
            $response              = new \stdClass();
            $response->slug        = $this->plugin_slug;
            $response->plugin      = "{$this->plugin_slug}/{$this->plugin_slug}.php";
            $response->new_version = $remote->version;
            $response->tested      = $remote->tested;
            $response->package     = $remote->download_url;

            $transient->response[ $response->plugin ] = $response;
        }

        return $transient;

    }

    public function purge( $upgrader, $options ) {

        if ( $this->cache_allowed && 'update' === $options['action'] && 'plugin' === $options[ 'type' ] ) {
            // just clean the cache when new plugin version is installed
            delete_transient( $this->cache_key );
        }

    }

    /**
     * Legg til "Sjekk for oppdateringer" link i plugin-listen
     */
    public function add_update_check_link($links, $file) {
        if ($file === $this->plugin_slug . '/' . $this->plugin_slug . '.php') {
            // Legg til "Sjekk for oppdateringer" link
            $links[] = sprintf(
                '<a href="%s">%s</a>',
                wp_nonce_url(admin_url('update.php?action=force-check'), 'force-check'),
                __('Sjekk for oppdateringer', 'kursagenten')
            );

            // Legg til direkte oppdateringslink hvis ny versjon er tilgjengelig
            $remote = $this->request();
            if ($remote && $this->compare_versions($this->version, $remote->version)) {
                $update_url = wp_nonce_url(
                    admin_url('update.php?action=upgrade-plugin&plugin=' . $this->plugin_slug . '/' . $this->plugin_slug . '.php'),
                    'upgrade-plugin_' . $this->plugin_slug . '/' . $this->plugin_slug . '.php'
                );
                $links[] = sprintf(
                    '<a href="%s" class="update-link">%s</a>',
                    $update_url,
                    sprintf(__('Oppdater til versjon %s', 'kursagenten'), $remote->version)
                );
            }
        }
        return $links;
    }

    /**
     * Hjelpefunksjon for å sammenligne versjoner
     */
    private function compare_versions($current, $remote) {
        // Fjern dev-suffix fra nåværende versjon
        $current = preg_replace('/-dev.*$/', '', $current);
        return version_compare($current, $remote, '<');
    }

    /**
     * Legg til admin-side for oppdateringer
     */
    public function add_update_page() {
        add_submenu_page(
            'plugins.php',
            __('Kursagenten Oppdateringer', 'kursagenten'),
            __('Kursagenten Oppdateringer', 'kursagenten'),
            'manage_options',
            'kursagenten-updates',
            [$this, 'render_update_page']
        );
    }

    /**
     * Vis oppdateringsside
     */
    public function render_update_page() {
        $remote = $this->request();
        ?>
        <div class="wrap">
            <h1><?php _e('Kursagenten Oppdateringer', 'kursagenten'); ?></h1>
            
            <?php if ($remote && $this->compare_versions($this->version, $remote->version)): ?>
                <div class="notice notice-warning">
                    <p>
                        <?php printf(
                            __('En ny versjon av Kursagenten er tilgjengelig: %s', 'kursagenten'),
                            $remote->version
                        ); ?>
                    </p>
                    <p>
                        <a href="<?php echo esc_url(wp_nonce_url(
                            admin_url('update.php?action=upgrade-plugin&plugin=' . $this->plugin_slug . '/' . $this->plugin_slug . '.php'),
                            'upgrade-plugin_' . $this->plugin_slug . '/' . $this->plugin_slug . '.php'
                        )); ?>" class="button button-primary">
                            <?php printf(__('Oppdater til versjon %s', 'kursagenten'), $remote->version); ?>
                        </a>
                    </p>
                </div>
            <?php else: ?>
                <div class="notice notice-success">
                    <p><?php _e('Du kjører den nyeste versjonen av Kursagenten.', 'kursagenten'); ?></p>
                </div>
            <?php endif; ?>

            <h2><?php _e('Versjonsinformasjon', 'kursagenten'); ?></h2>
            <table class="widefat">
                <tr>
                    <th><?php _e('Nåværende versjon', 'kursagenten'); ?></th>
                    <td><?php echo esc_html($this->version); ?></td>
                </tr>
                <?php if ($remote): ?>
                <tr>
                    <th><?php _e('Tilgjengelig versjon', 'kursagenten'); ?></th>
                    <td><?php echo esc_html($remote->version); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Sist oppdatert', 'kursagenten'); ?></th>
                    <td><?php echo esc_html($remote->last_updated); ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
        <?php
    }

    /**
     * Vis oppdateringsvarsel i admin
     */
    public function show_update_notice() {
        $remote = $this->request();
        if ($remote && $this->compare_versions($this->version, $remote->version)) {
            ?>
            <div class="notice notice-warning">
                <p>
                    <?php printf(
                        __('En ny versjon av Kursagenten er tilgjengelig: %s', 'kursagenten'),
                        $remote->version
                    ); ?>
                    <a href="<?php echo esc_url(admin_url('plugins.php?page=kursagenten-updates')); ?>">
                        <?php _e('Klikk her for å oppdatere', 'kursagenten'); ?>
                    </a>
                </p>
            </div>
            <?php
        }
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

}
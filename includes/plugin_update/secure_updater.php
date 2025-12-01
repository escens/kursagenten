<?php
/**
 * Kursagenten Secure Updater
 * 
 * Dette erstatter den eksisterende oppdateringsklassen
 * med en sikker versjon som bruker API-nøkler
 */

namespace KursagentenUpdater;

class SecureUpdater {

    private $plugin_slug;
    private $version;
    private $api_key;
    private $api_url;
    private $cache_key;
    private $failed_request_key;
    private $request_in_progress_key;

    public function __construct() {
        $this->plugin_slug = 'kursagenten';
        $this->version = KURSAG_VERSION;
        $this->api_key = get_option('kursagenten_api_key', '');
        // Server-plugin endepunkter bruker /kursagenten-api/{action}
        // Use local server if available, otherwise fallback to external
        $this->api_url = $this->get_api_url();
        $this->cache_key = 'kursagenten_secure_updater';
        $this->failed_request_key = 'kursagenten_updater_failed';
        $this->request_in_progress_key = 'kursagenten_updater_in_progress';

        // Hooks
        add_filter('plugins_api', [$this, 'info'], 20, 3);
        add_filter('site_transient_update_plugins', [$this, 'update']);
        // Ensure update-core.php also gets proper data by updating the transient before it's saved
        add_filter('pre_set_site_transient_update_plugins', [$this, 'pre_set_update']);
        add_action('upgrader_process_complete', [$this, 'purge'], 10, 2);
        add_filter('plugin_row_meta', [$this, 'add_update_check_link'], 10, 2);
        add_filter('plugin_action_links_' . $this->plugin_slug . '/' . $this->plugin_slug . '.php', [$this, 'add_settings_link']);

        // AJAX-håndtering
        add_action('wp_ajax_kursagenten_check_updates', [$this, 'ajax_check_updates']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);

        // Registrer site ved første besøk (eller når pending registration finnes)
        add_action('init', [$this, 'maybe_register_site']);
        // Innstillinger-side og notiser
        add_action('admin_menu', [$this, 'register_license_settings_page']);
        add_action('admin_init', [$this, 'register_license_setting']);
        add_action('admin_notices', [$this, 'maybe_show_missing_key_notice']);
        // Reager når Lisensnøkkel oppdateres
        add_action('update_option_kursagenten_api_key', [$this, 'on_api_key_updated'], 10, 3);
        // AJAX: registrer site nå
        add_action('wp_ajax_kursagenten_register_site', [$this, 'ajax_register_site']);

        // Weekly cron for reliable site registration
        add_action('kursagenten_weekly_registration', [$this, 'cron_register_site']);
        // Schedule cron if not already scheduled
        if (!wp_next_scheduled('kursagenten_weekly_registration')) {
            wp_schedule_event(time(), 'weekly', 'kursagenten_weekly_registration');
        }

        // Sørg for fersk update-info når Plugins/Update-sider lastes (unngå gammel cache med feil URL)
        add_action('load-plugins.php', function() { 
            delete_transient($this->cache_key);
            delete_transient($this->failed_request_key);
        });
        add_action('load-update.php', function() { 
            delete_transient($this->cache_key);
            delete_transient($this->failed_request_key);
        });
        add_action('load-update-core.php', function() { 
            delete_transient($this->cache_key);
            delete_transient($this->failed_request_key);
        });
    }

    /**
     * Get API URL - use admin-ajax.php as proxy to bypass firewall restrictions
     */
    private function get_api_url() {
        // Determine current host
        $host = wp_parse_url(home_url(), PHP_URL_HOST);
        $is_central_server = (stripos((string) $host, 'admin.lanseres.no') !== false);
        
        // Use local endpoint only on central server instance
        if ($is_central_server && class_exists('\\KursagentenServer\\Server')) {
            return home_url('/kursagenten-api/');
        }
        
        // Use admin-ajax.php as proxy to bypass firewall restrictions
        // admin-ajax.php is typically whitelisted in firewalls/WAF
        return 'https://admin.lanseres.no/wp-admin/admin-ajax.php?action=';
    }

    /**
     * Registrer site med API-serveren
     */
    /**
     * Check if we need to register site (handles both scheduled and daily checks)
     */
    public function maybe_register_site() {
        // Check if we have a pending registration from a recent API key update
        if (get_transient('kursagenten_pending_registration')) {
            delete_transient('kursagenten_pending_registration');
            $this->register_site(true);
            return;
        }
        
        // Otherwise run normal daily check
        $this->register_site(false);
    }

    public function register_site($force = false) {
        if (empty($this->api_key) || is_admin() === false) {
            return;
        }

        // Check if we already registered this week
        $last_register = get_option('kursagenten_last_register', 0);
        if (!$force && (time() - $last_register < 604800)) { // 7 days
            return;
        }

        $data = [
            'action' => 'register_site',
            'api_key' => $this->api_key,
            'site_url' => home_url(),
            'site_name' => get_bloginfo('name'),
            'plugin_version' => $this->version,
            'wp_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'wf_bypass' => 'kursagenten_' . substr(md5('kursagenten-client-2024'), 0, 8) // Wordfence bypass token
        ];

        // Webhook approach: Send minimal data with signature
        // Server extracts all needed info from the request itself (IP, User-Agent, etc)
        $timestamp = time();
        
        // Create secure payload hash that includes all data but doesn't expose it in URL
        $payload = json_encode([
            'site_url' => $data['site_url'],
            'site_name' => $data['site_name'],
            'plugin_version' => $data['plugin_version'],
            'wp_version' => $data['wp_version'],
            'php_version' => $data['php_version'],
            'timestamp' => $timestamp
        ]);
        
        // Encode payload with API key (server can decode it)
        $encoded_payload = base64_encode($payload);
        $signature = hash_hmac('sha256', $payload, $this->api_key);
        
        // Minimal webhook URL - just key, payload and signature
        $webhook_data = [
            'k' => substr($this->api_key, 0, 12), // First 12 chars for lookup
            'p' => $encoded_payload,
            's' => $signature
        ];
        
        $endpoint = $this->api_url . 'register_site?' . http_build_query($webhook_data);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Kursagenten: Registering site with server');
            error_log('  Site URL: ' . $data['site_url']);
            error_log('  Plugin version: ' . $data['plugin_version']);
        }
        
        $response = wp_remote_get($endpoint, [
            'timeout' => 15,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'headers' => [
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
                'X-Kursagenten-Version' => $this->version
            ]
        ]);

        $is_error = is_wp_error($response);
        $code = $is_error ? 0 : (int) wp_remote_retrieve_response_code($response);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            if ($is_error) {
                error_log('Kursagenten: Registration failed - ' . $response->get_error_message());
            } else if ($code === 200) {
                error_log('Kursagenten: Site registered successfully (HTTP ' . $code . ')');
            } else {
                error_log('Kursagenten: Registration response HTTP ' . $code);
            }
        }

        if (!$is_error && $code === 200) {
            update_option('kursagenten_last_register', time());
            update_option('kursagenten_site_registered', true);
            set_transient('kursagenten_register_success', 1, 60);
            // Clear invalid flag if previously set
            delete_option('kursagenten_api_key_invalid');
        } elseif (!$is_error && $code === 401) {
            // License is invalid - delete API key to force re-entry
            $this->handle_invalid_license('invalid');
        } elseif (!$is_error && $code === 403) {
            // License limit exceeded - treat as invalid license and delete API key
            $this->handle_invalid_license('limit_exceeded');
        } else {
            // Transient/network or server error: do not invalidate key; allow retry later
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Kursagenten register_site transient/server error, keeping key for retry');
            }
        }
    }

    /**
     * Hent oppdateringsinformasjon fra API-serveren
     */
    public function request() {
        // Sjekk cache først
        $cached = get_transient($this->cache_key);
        if ($cached !== false) {
            // Normaliser til objekt for nedstrøms-kall som forventer objekt
            return (object) $cached;
        }

        // Check if a request is already in progress - prevent concurrent calls
        $in_progress = get_transient($this->request_in_progress_key);
        if ($in_progress !== false) {
            // Another request is in progress, wait a moment and check cache again
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Kursagenten: Request already in progress, skipping');
            }
            // Wait briefly and check cache again
            usleep(100000); // 0.1 second
            $cached = get_transient($this->cache_key);
            return $cached !== false ? (object) $cached : false;
        }

        // Check if we recently had a failed request - avoid repeated timeouts
        $failed_request = get_transient($this->failed_request_key);
        if ($failed_request !== false) {
            // If we failed recently, return false immediately to avoid blocking page loads
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Kursagenten: Skipping update check - recent failure detected');
            }
            return false;
        }

        // Mark request as in progress (expires in 30 seconds as safety net)
        set_transient($this->request_in_progress_key, time(), 30);

        // Never make remote calls on frontend - only in admin
        if (!is_admin()) {
            return false;
        }

        // Avoid remote calls on irrelevant admin screens (allow Plugins, Plugin-info, Update-core, cron, WP-CLI, REST, AJAX)
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        $allowed_bases = array('plugins', 'plugin-install', 'update-core');
        $is_allowed_screen = $screen && in_array($screen->base, $allowed_bases, true);
        $is_ajax = defined('DOING_AJAX') && DOING_AJAX;
        $is_our_ajax = $is_ajax && isset($_POST['action']) && $_POST['action'] === 'kursagenten_check_updates';
        // Allow during bulk updates and WordPress core update checks
        $is_bulk_update = $is_ajax && isset($_POST['action']) && in_array($_POST['action'], array('update-plugin', 'update-selected'), true);
        $is_update_check = isset($_GET['action']) && in_array($_GET['action'], array('check-plugin-updates', 'update-plugin'), true);
        $is_cron = function_exists('wp_doing_cron') && wp_doing_cron();
        $is_cli = defined('WP_CLI') && WP_CLI;
        $is_rest = defined('REST_REQUEST') && REST_REQUEST;

        if (
            !$is_allowed_screen
            && !$is_our_ajax
            && !$is_bulk_update
            && !$is_update_check
            && !$is_cron
            && !$is_cli
            && !$is_rest
            && !$is_ajax
        ) {
            return false;
        }

        try {
            // Prøv først ny API-metode hvis Lisensnøkkel er tilgjengelig
            if (!empty($this->api_key)) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Kursagenten: Prøver API-metode med lisensnøkkel');
                }
                $api_result = $this->request_api_method();
                if ($api_result !== false) {
                    // Clear failed request flag on success
                    delete_transient($this->failed_request_key);
                    delete_transient($this->request_in_progress_key);
                    return $api_result;
                }
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Kursagenten: API-metode feilet, går til fallback');
                }
            } else {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Kursagenten: Ingen lisensnøkkel, går direkte til JSON-metode');
                }
            }

            // Fallback til den gamle JSON-baserte metoden
            $json_result = $this->request_json_method();
            if ($json_result !== false) {
                // Clear failed request flag on success
                delete_transient($this->failed_request_key);
            }
            delete_transient($this->request_in_progress_key);
            return $json_result;
        } catch (\Exception $e) {
            // Ensure we clear the in-progress flag even on exception
            delete_transient($this->request_in_progress_key);
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Kursagenten: Exception in request(): ' . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * API-metode for oppdateringskontroll
     */
    private function request_api_method() {
        $data = [
            'api_key' => $this->api_key,
            'site_url' => home_url(),
            'plugin_version' => $this->version,
            'wp_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION
        ];

        // Use admin-ajax.php as proxy to bypass firewall restrictions
        // Check if we're using admin-ajax.php proxy or direct API endpoint
        $is_ajax_proxy = strpos($this->api_url, 'admin-ajax.php') !== false;
        
        if ($is_ajax_proxy) {
            // Use admin-ajax.php proxy
            $endpoint = $this->api_url . 'kursagenten_check_update';
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Kursagenten: Using admin-ajax.php proxy: ' . $endpoint);
            }
        } else {
            // Use direct API endpoint
            $endpoint = $this->api_url . 'check_update';
            $data['action'] = 'check_update';
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Kursagenten: Using direct API endpoint: ' . $endpoint);
            }
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Kursagenten: API URL base: ' . $this->api_url);
            error_log('Kursagenten: Full endpoint: ' . $endpoint);
        }
        
        $response = wp_remote_post($endpoint, [
            'body' => $data,
            'timeout' => 3, // Reduced from 5 to 3 seconds for faster failure
            'blocking' => true,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest'
            ],
            'sslverify' => true,
            'httpversion' => '1.1'
        ]);

        if (is_wp_error($response)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Kursagenten API oppdateringsfeil: ' . $response->get_error_message());
            }
            // Mark as failed request immediately to avoid repeated attempts
            set_transient($this->failed_request_key, time(), 15 * MINUTE_IN_SECONDS);
            delete_transient($this->request_in_progress_key);
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Kursagenten API HTTP feil: ' . $response_code);
                error_log('API endpoint: ' . $endpoint);
                error_log('Response: ' . wp_remote_retrieve_body($response));
            }
            
            // If license is invalid (401), delete API key to force re-entry
            if ($response_code === 401) {
                $this->handle_invalid_license('invalid');
            }
            
            return false;
        }

        $raw = wp_remote_retrieve_body($response);
        $body = json_decode($raw, true);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Kursagenten API response: ' . print_r($body, true));
        }
        
        // Cache result for en time også når update_available er false - viktig for debugging
        $cache_data = [
            'version' => $this->version,
            'update_available' => isset($body['update_available']) ? (bool)$body['update_available'] : false,
            'method' => 'api'
        ];
        if (isset($body['update_available']) && $body['update_available'] === true && isset($body['update_info'])) {
            $update_data = $body['update_info'];
            // Lagre som array for cache siden vi så skal konvertere til objekt
            $cache_data = array_merge($cache_data, $update_data);
        }
        set_transient($this->cache_key, $cache_data, HOUR_IN_SECONDS);

        // Returner plugin info hvis API-kallet lyktes
        if (isset($body['status']) && $body['status'] === 'success') {
            if (isset($body['update_available']) && $body['update_available'] === true && isset($body['update_info'])) {
                // Hvis det er oppdatering tilgjengelig, returner update_info
                $update_info = $body['update_info'];
                $update_info['update_available'] = true;
                return (object) $update_info;
            }

            // Ingen oppdatering: bygg komplett plugin-info objekt (unngå udefinert variabel)
            // Hent changelog fra server-plugin (public endpoint)
            $changelog_text = 'Changelog er ikke tilgjengelig.';
            $changelog_data = $this->get_public_changelog();
            if (is_array($changelog_data) && isset($changelog_data['changelog']) && !empty($changelog_data['changelog'])) {
                $changelog_text = $changelog_data['changelog'];
            }

            return (object) [
                'name' => 'Kursagenten',
                'slug' => 'kursagenten',
                'version' => $this->version,
                'tested' => defined('KURSAG_WP_TESTED') ? KURSAG_WP_TESTED : '6.6',
                'requires' => defined('KURSAG_WP_REQUIRES') ? KURSAG_WP_REQUIRES : '6.0',
                'author' => defined('KURSAG_AUTHOR') ? KURSAG_AUTHOR : 'Tone B. Hagen',
                'author_profile' => defined('KURSAG_AUTHOR_URI') ? KURSAG_AUTHOR_URI : 'https://kursagenten.no',
                'homepage' => defined('KURSAG_HOMEPAGE') ? KURSAG_HOMEPAGE : 'https://deltagersystem.no/wp-plugin',
                'download_url' => '',
                'requires_php' => defined('KURSAG_PHP_REQUIRES') ? KURSAG_PHP_REQUIRES : '7.4',
                'last_updated' => date('Y-m-d'),
                'sections' => [
                    'description' => defined('KURSAG_DESCRIPTION') ? KURSAG_DESCRIPTION : 'Dine kurs hentet og oppdatert fra Kursagenten.',
                    'installation' => defined('KURSAG_INSTALLATION') ? KURSAG_INSTALLATION : 'Installasjonssteg kommer her.',
                    'changelog' => $changelog_text
                ],
                'banners' => [
                    'low' => defined('KURSAG_BANNER_LOW') ? KURSAG_BANNER_LOW : 'https://admin.lanseres.no/plugin-updates/kursagenten-banner-772x250.webp',
                    'high' => defined('KURSAG_BANNER_HIGH') ? KURSAG_BANNER_HIGH : 'https://admin.lanseres.no/plugin-updates/kursagenten-banner-1544x500.webp'
                ],
                'update_available' => false
            ];
        }
        return false;
    }

    /**
     * JSON-metode for oppdateringskontroll (fallback)
     */
    private function request_json_method() {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Kursagenten: Fallback til JSON-metode');
        }

        $remote = wp_remote_get('https://admin.lanseres.no/plugin-updates/kursagenten.json', [
            'timeout' => 3, // Reduced from 10 to 3 seconds for faster failure
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'headers' => [
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest'
            ],
            'sslverify' => true,
            'httpversion' => '1.1'
        ]);

        if (is_wp_error($remote)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Kursagenten JSON oppdateringsfeil: ' . $remote->get_error_message());
            }
            // Mark as failed request immediately to avoid repeated attempts
            set_transient($this->failed_request_key, time(), 15 * MINUTE_IN_SECONDS);
            delete_transient($this->request_in_progress_key);
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($remote);
        if (200 !== $response_code) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Kursagenten JSON HTTP feil: ' . $response_code);
                error_log('JSON URL: https://admin.lanseres.no/plugin-updates/kursagenten.json');
                error_log('Response: ' . wp_remote_retrieve_body($remote));
            }
            // Mark as failed request for non-200 responses (but not for timeout which is already handled)
            set_transient($this->failed_request_key, time(), 5 * MINUTE_IN_SECONDS);
            delete_transient($this->request_in_progress_key);
            return false;
        }

        $body = wp_remote_retrieve_body($remote);
        if (empty($body)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Kursagenten JSON oppdateringsfeil: Tom respons fra server');
            }
            return false;
        }

        $remote_data = json_decode($body);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Kursagenten JSON response: ' . print_r($remote_data, true));
        }
        
        // Cache resultatet
        $cache_data = [
            'version' => $this->version,
            'update_available' => isset($remote_data->version) && version_compare($this->version, $remote_data->version, '<'),
            'method' => 'json'
        ];
        
        if ($remote_data) {
            $cache_data = array_merge($cache_data, (array) $remote_data);
        }
        set_transient($this->cache_key, $cache_data, HOUR_IN_SECONDS);

        if ($remote_data && version_compare($this->version, $remote_data->version, '<')) {
            $remote_data->update_available = true;
            return $remote_data;
        }
        
        return false;
    }

    /**
     * WordPress plugins_api filter
     */
    public function info($response, $action, $args) {
        if ($action !== 'plugin_information' || $args->slug !== $this->plugin_slug) {
            return $response;
        }

        // Always try to get changelog from server-plugin first (no license required)
        $changelog_text = 'Changelog er ikke tilgjengelig.';
        try {
            $changelog_data = $this->get_public_changelog();
            if (is_array($changelog_data) && isset($changelog_data['changelog']) && !empty($changelog_data['changelog'])) {
                $changelog_text = $changelog_data['changelog'];
            }
        } catch (Exception $e) {
            // Silently fail and use fallback text
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Kursagenten changelog error: ' . $e->getMessage());
            }
        }

        $remote = $this->request();
        if (!$remote) {
            
            // Provide fallback plugin information when API fails
            // But still include the changelog we fetched earlier
            $response = new \stdClass();
            $response->name = 'Kursagenten';
            $response->slug = $this->plugin_slug;
            $response->version = $this->version;
            $response->tested = '6.6';
            $response->requires = '6.0';
            $response->author = 'Tone B. Hagen';
            $response->author_profile = 'https://kursagenten.no';
            $response->homepage = 'https://deltagersystem.no/wp-plugin';
            $response->download_link = '';
            $response->trunk = '';
            $response->requires_php = '7.4';
            $response->last_updated = '';
            $response->sections = [
                'description' => defined('KURSAG_DESCRIPTION') ? KURSAG_DESCRIPTION : 'Dine kurs hentet og oppdatert fra Kursagenten.',
                'installation' => defined('KURSAG_INSTALLATION') ? KURSAG_INSTALLATION : 'Installasjonssteg kommer her.',
                'changelog' => $changelog_text  // Use the changelog we fetched earlier
            ];
            
            $response->banners = [
                'low' => defined('KURSAG_BANNER_LOW') ? KURSAG_BANNER_LOW : 'https://admin.lanseres.no/plugin-updates/kursagenten-banner-772x250.webp',
                'high' => defined('KURSAG_BANNER_HIGH') ? KURSAG_BANNER_HIGH : 'https://admin.lanseres.no/plugin-updates/kursagenten-banner-1544x500.webp'
            ];
            $response->icons = [
                '1x' => KURSAG_PLUGIN_URL . 'assets/images/placeholder-kurs.jpg',
                '2x' => KURSAG_PLUGIN_URL . 'assets/images/placeholder-kurs.jpg'
            ];
            return $response;
        }
        if (is_array($remote)) { $remote = (object) $remote; }
        if (empty($remote->version)) { return $response; }

        $response = new \stdClass();
        $response->name = $remote->name;
        $response->slug = $remote->slug;
        $response->version = $remote->version;
        $response->tested = $remote->tested;
        $response->requires = $remote->requires;
        $response->author = $remote->author;
        $response->author_profile = $remote->author_profile;
        $response->homepage = $remote->homepage;
        $response->download_link = $remote->download_url;
        $response->trunk = $remote->download_url;
        $response->requires_php = $remote->requires_php;
        $response->last_updated = $remote->last_updated;
        // Ensure sections are properly populated
        $response->sections = isset($remote->sections) ? (array) $remote->sections : [];
        if (empty($response->sections['description'])) {
            $response->sections['description'] = defined('KURSAG_DESCRIPTION') ? KURSAG_DESCRIPTION : 'Dine kurs hentet og oppdatert fra Kursagenten.';
        }
        if (empty($response->sections['installation'])) {
            $response->sections['installation'] = defined('KURSAG_INSTALLATION') ? KURSAG_INSTALLATION : 'Installasjonssteg kommer her.';
        }
        if (empty($response->sections['changelog'])) {
            $response->sections['changelog'] = $changelog_text;
        }
        
        
        // Ensure banners are properly populated
        $response->banners = isset($remote->banners) ? (array) $remote->banners : [];
        if (empty($response->banners)) {
            $response->banners = [
                'low' => defined('KURSAG_BANNER_LOW') ? KURSAG_BANNER_LOW : 'https://admin.lanseres.no/plugin-updates/kursagenten-banner-772x250.webp',
                'high' => defined('KURSAG_BANNER_HIGH') ? KURSAG_BANNER_HIGH : 'https://admin.lanseres.no/plugin-updates/kursagenten-banner-1544x500.webp'
            ];
        }
        $response->icons = [
            '1x' => KURSAG_PLUGIN_URL . 'assets/images/placeholder-kurs.jpg',
            '2x' => KURSAG_PLUGIN_URL . 'assets/images/placeholder-kurs.jpg'
        ];

        return $response;
    }

    /**
     * WordPress site_transient_update_plugins filter
     */
    public function update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        $remote = $this->request();
        if (!$remote) {
            return $transient;
        }
        if (is_array($remote)) { $remote = (object) $remote; }
        if (empty($remote->version)) { return $transient; }

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf('Kursagenten: Versjonssjekk - Lokal: %s, Remote: %s, Sammenligning: %s', 
                $this->version, 
                $remote->version, 
                version_compare($this->version, $remote->version, '<') ? 'OPPDATERING TILGJENGELIG' : 'Ingen oppdatering'
            ));
        }

        if (version_compare($this->version, $remote->version, '<')) {
            $response = new \stdClass();
            $response->slug = $this->plugin_slug;
            $response->plugin = "{$this->plugin_slug}/{$this->plugin_slug}.php";
            $response->new_version = $remote->version;
            $response->tested = $remote->tested;
            $response->package = $remote->download_url;
            $response->id = "{$this->plugin_slug}/{$this->plugin_slug}.php";
            $response->url = $remote->homepage;
            $response->compatibility = new \stdClass();
            $response->compatibility->{$remote->tested} = new \stdClass();
            $response->compatibility->{$remote->tested}->{$remote->version} = new \stdClass();
            $response->upgrade_notice = $remote->upgrade_notice;

            $transient->response[$response->plugin] = $response;
        } elseif (isset($remote->update_available) && $remote->update_available === false) {
            $plugin_file = "{$this->plugin_slug}/{$this->plugin_slug}.php";
            if (isset($transient->response[$plugin_file])) {
                unset($transient->response[$plugin_file]);
            }
        }

        return $transient;
    }

    /**
     * Ensure update data is present when WordPress saves the plugins update transient
     * This affects the update-core.php page which relies on the stored transient
     */
    public function pre_set_update($transient) {
        // WordPress may pass null initially
        if (!is_object($transient)) {
            $transient = new \stdClass();
        }
        if (empty($transient->checked) || !is_array($transient->checked)) {
            return $transient;
        }

        $remote = $this->request();
        if (!$remote) {
            // Don't modify transient if request failed - keep existing update info
            return $transient;
        }
        if (is_array($remote)) { $remote = (object) $remote; }
        if (empty($remote->version)) { return $transient; }

        $plugin_file = "{$this->plugin_slug}/{$this->plugin_slug}.php";

        // Set/update response for our plugin
        if (version_compare($this->version, $remote->version, '<')) {
            $response = new \stdClass();
            $response->slug = $this->plugin_slug;
            $response->plugin = $plugin_file;
            $response->new_version = $remote->version;
            $response->tested = $remote->tested ?? '';
            $response->package = $remote->download_url ?? '';
            $response->id = $plugin_file;
            $response->url = $remote->homepage ?? '';
            $response->compatibility = new \stdClass();
            if (!empty($remote->tested)) {
                $response->compatibility->{$remote->tested} = new \stdClass();
                $response->compatibility->{$remote->tested}->{$remote->version} = new \stdClass();
            }
            if (isset($remote->upgrade_notice)) {
                $response->upgrade_notice = $remote->upgrade_notice;
            }

            if (!isset($transient->response) || !is_array($transient->response)) {
                $transient->response = [];
            }
            $transient->response[$plugin_file] = $response;
        } elseif (isset($remote->update_available) && $remote->update_available === false) {
            // Only remove update entry if we explicitly know there's no update (not on API failure)
            if (isset($transient->response[$plugin_file])) {
                unset($transient->response[$plugin_file]);
            }
        }

        // Help core know when we last checked
        $transient->last_checked = time();

        return $transient;
    }

    /**
     * Legg til "Innstillinger" link
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
     * Legg til oppdateringslinker
     */
    public function add_update_check_link($links, $file) {
        if ($file === $this->plugin_slug . '/' . $this->plugin_slug . '.php') {
            // Use cached data if available to avoid blocking page load
            $cached = get_transient($this->cache_key);
            $remote = false;
            if ($cached !== false) {
                $remote = (object) $cached;
            } else {
                // Only make request if cache is empty and we're on allowed screen
                $remote = $this->request();
            }
            $has_update = $remote && isset($remote->version) && version_compare($this->version, $remote->version, '<');
            
            // Legg til "Vis detaljer" link kun hvis det ikke er oppdateringer
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
            
            // Legg til "Sjekk for oppdateringer" link
            $links[] = sprintf(
                '<a href="#" onclick="kursagentenCheckUpdates(); return false;">%s</a>',
                __('Sjekk for oppdateringer', 'kursagenten')
            );
        }
        return $links;
    }

    /**
     * AJAX oppdateringssjekk
     */
    public function ajax_check_updates() {
        check_ajax_referer('kursagenten_check_updates', 'nonce');
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Kursagenten: AJAX oppdateringssjekk startet');
        }
        
        // Clear caches to force fresh check
        delete_transient($this->cache_key);
        delete_transient($this->failed_request_key);
        delete_transient($this->request_in_progress_key);
        
        $remote = $this->request();
        
        if ($remote) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Kursagenten: AJAX oppdatering funnet - versjon ' . (isset($remote->version) ? $remote->version : 'ukjent'));
            }
            $message = isset($remote->version) 
                ? sprintf('Oppdatering tilgjengelig: versjon %s', $remote->version)
                : 'Oppdatering tilgjengelig';
            wp_send_json_success($message);
        } else {
            // Check if it's a network error (failed request flag was set again)
            $failed_request = get_transient($this->failed_request_key);
            if ($failed_request !== false) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Kursagenten: AJAX sjekk feilet - server svarer ikke (timeout)');
                }
                wp_send_json_error([
                    'message' => 'Kunne ikke koble til oppdateringsserver (admin.lanseres.no).',
                    'details' => 'Serveren svarer ikke innen timeout-tiden. Dette kan skyldes nettverksproblemer eller at serveren er nede.',
                    'suggestion' => 'Prøv igjen om noen minutter, eller kontakt support hvis problemet vedvarer.'
                ]);
            } else {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Kursagenten: AJAX ingen oppdateringer funnet');
                }
                // Behandle "ingen oppdatering" som OK (server svarte success, update_available=false)
                wp_send_json_success('Ingen oppdateringer funnet - du har den nyeste versjonen.');
            }
        }
    }

    /**
     * Enqueue admin scripts
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
                    if (response && response.success) {
                        var msg = (typeof response.data === "string" && response.data) ? response.data : "Oppdateringssjekk fullført!";
                        alert(msg);
                        // Bare refresh hvis det potensielt er endringer (ikke ved "Ingen oppdateringer")
                        if (msg.indexOf("Ingen oppdateringer") === -1 && msg.indexOf("nyeste versjonen") === -1) {
                            location.reload();
                        }
                    } else {
                        var errorMsg = "Feil ved oppdateringssjekk";
                        if (response && response.data) {
                            if (typeof response.data === "object" && response.data.message) {
                                errorMsg = response.data.message;
                                if (response.data.details) {
                                    errorMsg += "\\n\\n" + response.data.details;
                                }
                                if (response.data.suggestion) {
                                    errorMsg += "\\n\\n" + response.data.suggestion;
                                }
                            } else if (typeof response.data === "string") {
                                errorMsg = response.data;
                            }
                        }
                        alert(errorMsg);
                    }
                    $link.text("Sjekk for oppdateringer").prop("disabled", false);
                }).fail(function() {
                    alert("Kunne ikke koble til server. Sjekk nettverksforbindelsen.");
                    $link.text("Sjekk for oppdateringer").prop("disabled", false);
                });
            }
        ');
    }

    /**
     * Rydd opp cache
     */
    public function purge($upgrader, $options) {
        if ($options['action'] === 'update' && $options['type'] === 'plugin') {
            delete_transient($this->cache_key);
            delete_transient($this->failed_request_key);
        }
    }

    /**
     * Registrer innstillingsside for lisens (Lisensnøkkel)
     */
    public function register_license_settings_page() {
        add_options_page(
            __('Kursagenten lisens', 'kursagenten'),
            __('Kursagenten lisens', 'kursagenten'),
            'manage_options',
            'kursagenten-license',
            [$this, 'render_license_settings_page']
        );
    }

    /**
     * Registrer setting for Lisensnøkkel
     */
    public function register_license_setting() {
        register_setting('kursagenten_license', 'kursagenten_api_key', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ]);
        add_settings_section('kursagenten_license_section', '', function() {
            echo '';
        }, 'kursagenten-license');
        add_settings_field(
            'kursagenten_api_key_field',
            __('Lisensnøkkel', 'kursagenten'),
            function() {
                $value = get_option('kursagenten_api_key', '');
                echo '<input type="text" name="kursagenten_api_key" value="' . esc_attr($value) . '" class="regular-text" />';
                echo '<p class="description">' . esc_html__('Lim inn lisensnøkkelen du fikk tildelt.', 'kursagenten') . '</p>';
            },
            'kursagenten-license',
            'kursagenten_license_section'
        );
    }

    /**
     * Tegn innstillingssiden
     */
    public function render_license_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php _e('Kursagenten lisens', 'kursagenten'); ?></h1>
            <form method="post" action="options.php">
                <?php
                    settings_fields('kursagenten_license');
                    do_settings_sections('kursagenten-license');
                    submit_button(__('Lagre lisens', 'kursagenten'));
                ?>
            </form>
            <p>
                <button id="kag-register-now" class="button">
                    <?php _e('Registrer nå', 'kursagenten'); ?>
                </button>
                <span id="kag-register-status" style="margin-left:8px;"></span>
            </p>
            <script>
            (function(){
                const btn = document.getElementById('kag-register-now');
                const statusEl = document.getElementById('kag-register-status');
                if (!btn) return;
                btn.addEventListener('click', function(e){
                    e.preventDefault();
                    btn.disabled = true;
                    statusEl.textContent = 'Registrerer...';
                    jQuery.post(ajaxurl, {
                        action: 'kursagenten_register_site',
                        nonce: '<?php echo esc_js( wp_create_nonce('kursagenten_check_updates') ); ?>'
                    }, function(resp){
                        if (resp && resp.success) {
                            statusEl.textContent = 'Registrert!';
                        } else if (resp && resp.data === 'license_invalid') {
                            statusEl.textContent = 'Lisensen er ugyldig - sjekk innstillinger';
                        } else {
                            statusEl.textContent = 'Feil ved registrering';
                        }
                        setTimeout(function(){ statusEl.textContent=''; btn.disabled=false; }, 2000);
                    });
                });
            })();
            </script>
        </div>
        <?php
    }

    /**
     * Vis admin-notis dersom Lisensnøkkel mangler
     */
    public function maybe_show_missing_key_notice() {
        if (!current_user_can('manage_options')) {
            return;
        }
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        
        // Show success message when API key is saved
        if (get_transient('kursagenten_api_key_saved')) {
            delete_transient('kursagenten_api_key_saved');
            echo '<div class="notice notice-success is-dismissible"><p>'
                . __('Kursagenten: Lisensnøkkel lagret. Registrerer siden...', 'kursagenten')
                . '</p></div>';
        }
        
        // Show notice if license was invalidated
        if (get_transient('kursagenten_license_invalid')) {
            delete_transient('kursagenten_license_invalid');
            $url = esc_url(admin_url('admin.php?page=kursagenten'));
            echo '<div class="notice notice-error is-dismissible"><p>'
                . sprintf(
                    __('Kursagenten: Lisensen er ugyldig eller slettet. %sLegg inn ny lisens her%s.', 'kursagenten'),
                    '<a href="' . $url . '">',
                    '</a>'
                )
                . '</p></div>';
        } elseif (get_transient('kursagenten_license_limit_exceeded')) {
            delete_transient('kursagenten_license_limit_exceeded');
            $url = esc_url(admin_url('admin.php?page=kursagenten'));
            echo '<div class="notice notice-error is-dismissible"><p>'
                . sprintf(
                    __('Kursagenten: Denne lisensen er allerede i bruk på en annen side. Gi oss beskjed på post@kursagenten.no, så skal vi hjelpe deg. %sLegg inn ny lisens her%s.', 'kursagenten'),
                    '<a href="' . $url . '">',
                    '</a>'
                )
                . '</p></div>';
        } elseif (empty($this->api_key) && $screen && in_array($screen->base, ['plugins', 'options-general'])) {
            $url = esc_url(admin_url('admin.php?page=kursagenten'));
            echo '<div class="notice notice-warning"><p>'
                . sprintf(
                    __('Kursagenten: Lisensnøkkel mangler. %sLegg inn nøkkel her%s.', 'kursagenten'),
                    '<a href="' . $url . '">',
                    '</a>'
                )
                . '</p></div>';
        } elseif (get_transient('kursagenten_register_success')) {
            delete_transient('kursagenten_register_success');
            echo '<div class="notice notice-success is-dismissible"><p>'
                . __('Kursagenten: Siden har blitt registrert hos oss, og kan nå brukes. Tilgjengelige oppdateringer vil bli gjort tilgjenglig i "Utvidelser".', 'kursagenten')
                . '</p></div>';
        }
    }

    /**
     * Kalles når Lisensnøkkel oppdateres
     */
    public function on_api_key_updated($old_value, $value, $option) {
        $this->api_key = $value;
        delete_option('kursagenten_last_register');
        
        // Schedule registration to happen on next admin page load instead of during save
        // This prevents issues with the option being deleted during the save process
        set_transient('kursagenten_pending_registration', 1, 300);
        set_transient('kursagenten_api_key_saved', 1, 60);
    }

    /**
     * AJAX: tving registrering nå
     */
    public function ajax_register_site() {
        check_ajax_referer('kursagenten_check_updates', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('unauthorized');
        }
        if (empty($this->api_key)) {
            wp_send_json_error('missing_key');
        }
        
        // Store current API key to check if it gets deleted
        $original_api_key = $this->api_key;
        
        $this->register_site(true);
        
        // If API key was deleted due to invalid license, return error
        if (empty($this->api_key) && !empty($original_api_key)) {
            wp_send_json_error('license_invalid');
        }
        
        wp_send_json_success();
    }

    /**
     * Get changelog from public endpoint (no license required)
     */
    private function get_public_changelog() {
        try {
            // Use admin-ajax.php as proxy to bypass firewall restrictions
            $is_ajax_proxy = strpos($this->api_url, 'admin-ajax.php') !== false;
            
            if ($is_ajax_proxy) {
                $endpoint = $this->api_url . 'kursagenten_get_changelog';
            } else {
                $endpoint = $this->api_url . 'get_changelog';
            }
            
            $response = wp_remote_get($endpoint, [
                'timeout' => 5,
                'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'headers' => [
                    'Accept' => 'application/json',
                    'X-Requested-With' => 'XMLHttpRequest'
                ]
            ]);
            
            if (is_wp_error($response)) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Kursagenten changelog feil: ' . $response->get_error_message());
                }
                return false;
            }
            
            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code !== 200) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Kursagenten changelog HTTP feil: ' . $response_code);
                }
                return false;
            }
            
            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (is_array($body) && isset($body['status']) && $body['status'] === 'success') {
                return $body;
            }
            
            return false;
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Kursagenten changelog exception: ' . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Handle invalid license by deleting API key and showing notice
     */
    private function handle_invalid_license($reason = 'invalid') {
        // Delete the API key to force re-entry on license validation page
        delete_option('kursagenten_api_key');
        $this->api_key = '';
        
        // Clear registration status
        delete_option('kursagenten_last_register');
        delete_option('kursagenten_site_registered');
        
        // Set a transient to show notice to admin with appropriate message
        if ($reason === 'limit_exceeded') {
            set_transient('kursagenten_license_limit_exceeded', 1, 300); // 5 minutes
        } else {
            set_transient('kursagenten_license_invalid', 1, 300); // 5 minutes
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Kursagenten: API key deleted due to ' . $reason . ' license');
        }
    }

    /**
     * Cron job to register site weekly
     */
    public function cron_register_site() {
        if (empty($this->api_key)) {
            return;
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Kursagenten: Weekly cron registration triggered');
        }
        
        $this->register_site(true);
    }
}

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

    public function __construct() {
        $this->plugin_slug = 'kursagenten';
        $this->version = KURSAG_VERSION;
        $this->api_key = get_option('kursagenten_api_key', '');
        // Server-plugin endepunkter bruker /kursagenten-api/{action}
        $this->api_url = 'https://admin.lanseres.no/kursagenten-api/';
        $this->cache_key = 'kursagenten_secure_updater';

        // Hooks
        add_filter('plugins_api', [$this, 'info'], 20, 3);
        add_filter('site_transient_update_plugins', [$this, 'update']);
        add_action('upgrader_process_complete', [$this, 'purge'], 10, 2);
        add_filter('plugin_row_meta', [$this, 'add_update_check_link'], 10, 2);
        add_filter('plugin_action_links_' . $this->plugin_slug . '/' . $this->plugin_slug . '.php', [$this, 'add_settings_link']);

        // AJAX-håndtering
        add_action('wp_ajax_kursagenten_check_updates', [$this, 'ajax_check_updates']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);

        // Registrer site ved første besøk
        add_action('init', [$this, 'register_site']);
        // Innstillinger-side og notiser
        add_action('admin_menu', [$this, 'register_license_settings_page']);
        add_action('admin_init', [$this, 'register_license_setting']);
        add_action('admin_notices', [$this, 'maybe_show_missing_key_notice']);
        // Reager når API-nøkkel oppdateres
        add_action('update_option_kursagenten_api_key', [$this, 'on_api_key_updated'], 10, 3);
        // AJAX: registrer site nå
        add_action('wp_ajax_kursagenten_register_site', [$this, 'ajax_register_site']);

        // Sørg for fersk update-info når Plugins/Update-sider lastes (unngå gammel cache med feil URL)
        add_action('load-plugins.php', function() { delete_transient($this->cache_key); });
        add_action('load-update.php', function() { delete_transient($this->cache_key); });
    }

    /**
     * Registrer site med API-serveren
     */
    public function register_site($force = false) {
        if (empty($this->api_key) || is_admin() === false) {
            return;
        }

        // Sjekk om vi allerede har registrert i dag
        $last_register = get_option('kursagenten_last_register', 0);
        if (!$force && (time() - $last_register < 86400)) { // 24 timer
            return;
        }

        $data = [
            'action' => 'register_site',
            'api_key' => $this->api_key,
            'site_url' => home_url(),
            'site_name' => get_bloginfo('name'),
            'plugin_version' => $this->version,
            'wp_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION
        ];

        // Post til /kursagenten-api/register_site
        $response = wp_remote_post($this->api_url . 'register_site', [
            'body' => $data,
            'timeout' => 5
        ]);

        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            update_option('kursagenten_last_register', time());
            update_option('kursagenten_site_registered', true);
            set_transient('kursagenten_register_success', 1, 60);
        } elseif (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 401) {
            // License is invalid - delete API key to force re-entry
            $this->handle_invalid_license('invalid');
        } elseif (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 403) {
            // License limit exceeded - treat as invalid license and delete API key
            $this->handle_invalid_license('limit_exceeded');
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

        // Unngå nettverkskall på irrelevante admin-sider (tillat Plugins, Plugin-info, Update-core, eller vår egen AJAX)
        if (is_admin()) {
            $screen = function_exists('get_current_screen') ? get_current_screen() : null;
            $allowed_bases = array('plugins', 'plugin-install', 'update-core');
            $is_allowed_screen = $screen && in_array($screen->base, $allowed_bases, true);
            $is_ajax = defined('DOING_AJAX') && DOING_AJAX;
            $is_our_ajax = $is_ajax && isset($_POST['action']) && $_POST['action'] === 'kursagenten_check_updates';
            if (!$is_allowed_screen && !$is_our_ajax) {
                return false;
            }
        }

        // Prøv først ny API-metode hvis API-nøkkel er tilgjengelig
        if (!empty($this->api_key)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Kursagenten: Prøver API-metode med API-nøkkel');
            }
            $api_result = $this->request_api_method();
            if ($api_result !== false) {
                return $api_result;
            }
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Kursagenten: API-metode feilet, går til fallback');
            }
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Kursagenten: Ingen API-nøkkel, går direkte til JSON-metode');
            }
        }

        // Fallback til den gamle JSON-baserte metoden
        return $this->request_json_method();
    }

    /**
     * API-metode for oppdateringskontroll
     */
    private function request_api_method() {
        $data = [
            'action' => 'check_update',
            'api_key' => $this->api_key,
            'site_url' => home_url(),
            'plugin_version' => $this->version
        ];

        // Post til /kursagenten-api/check_update
        $endpoint = $this->api_url . 'check_update';
        $response = wp_remote_post($endpoint, [
            'body' => $data,
            'timeout' => 5
        ]);

        if (is_wp_error($response)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Kursagenten API oppdateringsfeil: ' . $response->get_error_message());
            }
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
                return (object) $body['update_info'];
            } else {
                // Hvis ingen oppdatering, returner komplett plugin info
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
                    ]
                ];
            }
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
            'timeout' => 10,
            'headers' => [
                'Accept' => 'application/json'
            ]
        ]);

        if (is_wp_error($remote)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Kursagenten JSON oppdateringsfeil: ' . $remote->get_error_message());
            }
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($remote);
        if (200 !== $response_code) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Kursagenten JSON HTTP feil: ' . $response_code);
                error_log('JSON URL: https://admin.lanseres.no/plugin-updates/kursagenten.json');
                error_log('Response: ' . wp_remote_retrieve_body($remote));
            }
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
            if ($changelog_data && isset($changelog_data['changelog'])) {
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
                'changelog' => $changelog_text
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
        }

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
            $remote = $this->request();
            $has_update = $remote && version_compare($this->version, $remote->version, '<');
            
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
        
        delete_transient($this->cache_key);
        $remote = $this->request();
        
        if ($remote) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Kursagenten: AJAX oppdatering funnet');
            }
            wp_send_json_success('Oppdatering tilgjengelig');
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Kursagenten: AJAX ingen oppdateringer funnet');
            }
            // Behandle "ingen oppdatering" som OK (server svarte success, update_available=false)
            wp_send_json_success('Ingen oppdateringer funnet');
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
                        if (msg.indexOf("Ingen oppdateringer") === -1) {
                            location.reload();
                        }
                    } else {
                        alert("Feil ved oppdateringssjekk: " + (response && response.data ? response.data : "ukjent feil"));
                    }
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
        }
    }

    /**
     * Registrer innstillingsside for lisens (API-nøkkel)
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
     * Registrer setting for API-nøkkel
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
            __('API-nøkkel', 'kursagenten'),
            function() {
                $value = get_option('kursagenten_api_key', '');
                echo '<input type="text" name="kursagenten_api_key" value="' . esc_attr($value) . '" class="regular-text" />';
                echo '<p class="description">' . esc_html__('Lim inn API-nøkkelen du fikk tildelt.', 'kursagenten') . '</p>';
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
     * Vis admin-notis dersom API-nøkkel mangler
     */
    public function maybe_show_missing_key_notice() {
        if (!current_user_can('manage_options')) {
            return;
        }
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        
        // Show notice if license was invalidated
        if (get_transient('kursagenten_license_invalid')) {
            delete_transient('kursagenten_license_invalid');
            $url = esc_url(admin_url('options-general.php?page=kursagenten-license'));
            echo '<div class="notice notice-error is-dismissible"><p>'
                . sprintf(
                    __('Kursagenten: Lisensen er ugyldig eller slettet. %sLegg inn ny lisens her%s.', 'kursagenten'),
                    '<a href="' . $url . '">',
                    '</a>'
                )
                . '</p></div>';
        } elseif (get_transient('kursagenten_license_limit_exceeded')) {
            delete_transient('kursagenten_license_limit_exceeded');
            $url = esc_url(admin_url('options-general.php?page=kursagenten-license'));
            echo '<div class="notice notice-error is-dismissible"><p>'
                . sprintf(
                    __('Kursagenten: Denne lisensen er allerede i bruk på en annen side. Gi oss beskjed på post@kursagenten.no, så skal vi hjelpe deg. %sLegg inn ny lisens her%s.', 'kursagenten'),
                    '<a href="' . $url . '">',
                    '</a>'
                )
                . '</p></div>';
        } elseif (empty($this->api_key) && $screen && in_array($screen->base, ['plugins', 'options-general'])) {
            $url = esc_url(admin_url('options-general.php?page=kursagenten-license'));
            echo '<div class="notice notice-warning"><p>'
                . sprintf(
                    __('Kursagenten: API-nøkkel mangler. %sLegg inn nøkkel her%s.', 'kursagenten'),
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
     * Kalles når API-nøkkel oppdateres
     */
    public function on_api_key_updated($old_value, $value, $option) {
        $this->api_key = $value;
        delete_option('kursagenten_last_register');
        $this->register_site(true);
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
            $endpoint = $this->api_url . 'get_changelog';
            $response = wp_remote_get($endpoint, [
                'timeout' => 5
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
        // Delete the API key
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
}

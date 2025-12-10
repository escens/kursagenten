<?php
class Kursinnstillinger {
    private $kag_kursinnst_options;

    public function __construct() {
        add_action('admin_menu', array($this, 'kag_kursinnst_add_plugin_page'));
        add_action('admin_init', array($this, 'kag_kursinnst_page_init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_kursagenten_add_location_mapping', array($this, 'ajax_add_location_mapping'));
        add_action('wp_ajax_kursagenten_remove_location_mapping', array($this, 'ajax_remove_location_mapping'));
        add_action('wp_ajax_kursagenten_update_location_mapping', array($this, 'ajax_update_location_mapping'));
        add_action('wp_ajax_kursagenten_get_location_terms', array($this, 'ajax_get_location_terms'));
    }

    // Shortcodes for settings in kursinnstillinger.php is in misc/kursagenten-shortcodes.php

    public function enqueue_admin_scripts($hook) {
        if ('kursagenten_page_kursinnstillinger' !== $hook) {
            return;
        }
        
        wp_enqueue_script(
            'kursagenten-location-mapping',
            KURSAG_PLUGIN_URL . '/assets/js/admin/location-mapping.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        wp_localize_script(
            'kursagenten-location-mapping',
            'kursagentenLocationMapping',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('kursagenten_location_mapping_nonce'),
            )
        );
    }

    public function kag_kursinnst_add_plugin_page() {
        add_submenu_page(
            'kursagenten',         // Parent slug
            'Synkronisering', // page_title
            'Synkronisering', // menu_title
            'manage_options',      // capability 
            'kursinnstillinger', // menu_slug
            array($this, 'kag_kursinnst_create_admin_page')
            //, // function
            //'dashicons-store',     // icon_url
            //2                      // position
        );
    }

    public function kag_kursinnst_create_admin_page() {
        // Global guard: redirect to Oversikt if API key is missing
        $api_key = get_option('kursagenten_api_key', '');
        if (empty($api_key)) {
            wp_safe_redirect( admin_url('admin.php?page=kursagenten') );
            exit;
        }

        $this->kag_kursinnst_options = get_option('kag_kursinnst_option_name'); 
        require_once KURSAG_PLUGIN_DIR . '/includes/api/api_sync_on_demand.php';

        ?>
        <div class="wrap options-form ka-wrap" id="toppen">
        <form method="post" action="options.php">
        <?php 
        settings_fields('kag_kursinnst_option_group');
        do_settings_sections('kursinnstillinger-admin');
        ?>
        <?php kursagenten_sticky_admin_menu(); ?>
        <h2>Synkronisering</h2>
        <p>Her finner du innstillinger for synkronisering av kurs fra Kursagenten. <br>
        - Ved å klikke på "Hent alle kurs fra Kursagenten" overfører du alle kursene dine. Merk at det kun er enveis-synkronisering.<br>
        - Vi anbefaler å <strong>synkronisere kursene automatisk</strong>. Legg inn <a href="https://kursadmin.kursagenten.no/IntegrationSettings" target="_blank">Webhooks</a> i Kursagenten, så blir kurset overført når det blir lagret/opprettet. Se url under.<br>
        - Du kan også rydde opp i kursene hvis det er utdaterte datoer eller lignende.<br><br></p>
        <?php 
        
        // Sjekk om nødvendige innstillinger er fylt ut
        $tilbyder_id = isset($this->kag_kursinnst_options['ka_tilbyderID']) ? $this->kag_kursinnst_options['ka_tilbyderID'] : '';
        $tilbyder_guid = isset($this->kag_kursinnst_options['ka_tilbyderGuid']) ? $this->kag_kursinnst_options['ka_tilbyderGuid'] : '';

        echo kursagenten_sync_courses_button(); 
        echo kursagenten_cleanup_courses_button();

        if (empty($tilbyder_id) || empty($tilbyder_guid)) : ?>
            <div class="" style="margin: 10px 0;padding: 1px 10px; background: #d63638; border-radius: 5px; color:white; width: fit-content;">
                <p><strong>OBS!</strong> Fyll inn <a href="#kursagenten-innstillinger" style="color:white; text-decoration: underline;">innstillinger fra Kursagenten</a> før du henter alle kursene dine (synkroniser alle kurs).</p>
            </div>
        <?php endif; ?>

        <!-- Kursagenten Settings Section -->
        <div class="options-card">
            <h3 id="kursagenten-innstillinger">Innstillinger fra Kursagenten</h3>
            <p>Du finner innstillingene for <strong><a href="https://kursadmin.kursagenten.no/ProviderInformation" target="_blank">Tilbyder ID og Tilbyder Guid</a></strong> i Kursagenten under <em>Bedriftsinsformasjon-> Innstillinger</em>, og <strong><a href="https://kursadmin.kursagenten.no/IframeSetting" target="_blank">Tema for kurslister</a></strong> under <em>Embedded / iframe</em><br><br>
            I Integrasjonsinnstillinger, under fanen <strong><a href="https://kursadmin.kursagenten.no/IntegrationSettings" target="_blank">Webhooks</a></strong>, skal du legge inn <span class="copytext" title="Klikk for å kopiere"><?php echo esc_url(site_url('/wp-json/kursagenten-api/v1/process-webhook')); ?></span> i feltene CourseCreated og CourseUpdated for å automatisk oppdatere kurs når det blir opprettet eller endret på Kursagenten.</p>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Tilbyder ID:</th>
                    <td>
                        <input class="regular-text" type="text" name="kag_kursinnst_option_name[ka_tilbyderID]" value="<?php echo isset($this->kag_kursinnst_options['ka_tilbyderID']) ? esc_attr($this->kag_kursinnst_options['ka_tilbyderID']) : ''; ?>">
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Tilbyder guid:</th>
                    <td>
                        <input class="regular-text" type="text" name="kag_kursinnst_option_name[ka_tilbyderGuid]" value="<?php echo isset($this->kag_kursinnst_options['ka_tilbyderGuid']) ? esc_attr($this->kag_kursinnst_options['ka_tilbyderGuid']) : ''; ?>">
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Tema for kurslister</th>
                    <td>
                        <input class="regular-text" type="text" name="kag_kursinnst_option_name[ka_temaKursliste]" value="<?php echo isset($this->kag_kursinnst_options['ka_temaKursliste']) ? esc_attr($this->kag_kursinnst_options['ka_temaKursliste']) : ''; ?>">
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Tema for enkeltkurs</th>
                    <td>
                        <input class="regular-text" type="text" name="kag_kursinnst_option_name[ka_temaKurs]" value="<?php echo isset($this->kag_kursinnst_options['ka_temaKurs']) ? esc_attr($this->kag_kursinnst_options['ka_temaKurs']) : ''; ?>">
                    </td>
                </tr>
            </table>
        </div>

        <?php submit_button(); ?>

        <h2 id="steder">Kurssteder og regioner</h2>
        <!-- Location Name Mapping Section -->
        <div class="options-card" style="margin-top: 20px;">
            <h3 id="location-name-mapping">Navnendring på kurssteder</h3>
            <p>Her kan du endre navn på kurssteder som kommer fra Kursagenten. Når du endrer navn på et sted, blir også slugs (nettadressen) på kursene som har dette stedet oppdatert.<br><br>
            1. Nytt sted blir lagret når du klikker på "Lagre", og blir oppdatert når du klikker utenfor tekstboksen.<br>
            2. Slugs på kursene som har dette stedet oppdateres umiddelbart.<br>
            3. Det gamle stedet blir ikke slettet, men blir ikke lenger synlig på nettsiden.<br>
            4. <span style="color: #d63638;"><strong>Viktig:</strong></span> For å ta i bruk navnendringene, kjør en full synk fra Kursagenten ved å klikke på "Hent alle kurs fra Kursagenten". Husk også å markere "Rydd opp i kurs" før du kjører synken.<br><br></p>
            

            <?php
            $location_mappings = get_option('kursagenten_location_mappings', array());
            
            // Initialize with default mappings if empty
            if (empty($location_mappings)) {
                $location_mappings = array(
                    'Bærum / Sandvika' => 'Bærum',
                    'Rana / Mo i Rana' => 'Mo i Rana',
                    'Lenvik / Finnsnes' => 'Finnsnes',
                    'Porsgrunn / Brevik' => 'Porsgrunn',
                    'Vågan / Svolvær' => 'Svolvær',
                );
                update_option('kursagenten_location_mappings', $location_mappings);
            }
            ?>

            <table class="wp-list-table widefat fixed striped" style="margin-top: 15px;">
                <thead>
                    <tr>
                        <th style="width: 40%;">Navn på sted</th>
                        <th style="width: 40%;">Nytt navn på sted</th>
                        <th style="width: 20%;">Handling</th>
                    </tr>
                </thead>
                <tbody id="location-mapping-tbody">
                    <?php foreach ($location_mappings as $old_name => $new_name) : ?>
                        <tr data-old-name="<?php echo esc_attr($old_name); ?>">
                            <td>
                                <input type="text" class="regular-text" value="<?php echo esc_attr($old_name); ?>" readonly style="background: #f0f0f0;">
                            </td>
                            <td>
                                <input type="text" class="regular-text location-new-name" value="<?php echo esc_attr($new_name); ?>" data-old-name="<?php echo esc_attr($old_name); ?>">
                            </td>
                            <td>
                                <button type="button" class="button button-small remove-location-mapping" data-old-name="<?php echo esc_attr($old_name); ?>">Fjern</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div style="margin-top: 15px;">
                <button type="button" class="button" id="add-location-mapping-btn">Endre navn på nytt sted</button>
            </div>

            <div id="new-location-mapping-row" style="display: none; margin-top: 15px;">
                <table class="wp-list-table widefat fixed">
                    <tr>
                        <td style="width: 40%;">
                            <select id="new-location-select" class="regular-text">
                                <option value="">Velg sted...</option>
                            </select>
                            <p style="margin: 5px 0 0 0; font-size: 12px; color: #666;">eller</p>
                            <input type="text" id="new-location-manual" class="regular-text" style="margin-top: 5px;" placeholder="Skriv inn stedsnavn manuelt">
                        </td>
                        <td style="width: 40%;">
                            <input type="text" id="new-location-name" class="regular-text" placeholder="Skriv inn nytt navn">
                        </td>
                        <td style="width: 20%;">
                            <button type="button" class="button button-primary" id="save-new-location-mapping">Lagre</button>
                            <button type="button" class="button" id="cancel-new-location-mapping">Avbryt</button>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <?php
        kursagenten_admin_footer();
    }
    
    public function kag_kursinnst_page_init() {
        register_setting(
            'kag_kursinnst_option_group',
            'kag_kursinnst_option_name',
            array($this, 'kag_kursinnst_sanitize')
        );
    }

    public function kag_kursinnst_sanitize($input) {
        $sanitary_values = array();
        
        foreach ($input as $key => $value) {
            $sanitary_values[$key] = sanitize_text_field($value);
        }
        return $sanitary_values;
    }

    /**
     * AJAX handler for adding a new location mapping
     */
    public function ajax_add_location_mapping() {
        check_ajax_referer('kursagenten_location_mapping_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Ikke tilgang'));
            return;
        }

        $old_name = isset($_POST['old_name']) ? sanitize_text_field($_POST['old_name']) : '';
        $new_name = isset($_POST['new_name']) ? sanitize_text_field($_POST['new_name']) : '';

        if (empty($old_name) || empty($new_name)) {
            wp_send_json_error(array('message' => 'Begge feltene må fylles ut'));
            return;
        }

        $mappings = get_option('kursagenten_location_mappings', array());
        $mappings[$old_name] = $new_name;
        update_option('kursagenten_location_mappings', $mappings);

        // Update slugs on existing sub-courses
        require_once KURSAG_PLUGIN_DIR . '/includes/api/api_course_sync.php';
        $updated_count = 0;
        if (function_exists('kursagenten_update_course_slugs_for_location_mapping')) {
            $updated_count = kursagenten_update_course_slugs_for_location_mapping($old_name, $new_name);
        }

        wp_send_json_success(array(
            'message' => 'Navnendring lagret' . ($updated_count > 0 ? " ($updated_count kurs oppdatert)" : ''),
            'old_name' => $old_name,
            'new_name' => $new_name,
            'updated_count' => $updated_count
        ));
    }

    /**
     * AJAX handler for removing a location mapping
     */
    public function ajax_remove_location_mapping() {
        check_ajax_referer('kursagenten_location_mapping_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Ikke tilgang'));
            return;
        }

        $old_name = isset($_POST['old_name']) ? sanitize_text_field($_POST['old_name']) : '';

        if (empty($old_name)) {
            wp_send_json_error(array('message' => 'Stedsnavn mangler'));
            return;
        }

        $mappings = get_option('kursagenten_location_mappings', array());
        unset($mappings[$old_name]);
        update_option('kursagenten_location_mappings', $mappings);

        wp_send_json_success(array('message' => 'Navnendring fjernet'));
    }

    /**
     * AJAX handler for updating an existing location mapping
     */
    public function ajax_update_location_mapping() {
        check_ajax_referer('kursagenten_location_mapping_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Ikke tilgang'));
            return;
        }

        $old_name = isset($_POST['old_name']) ? sanitize_text_field($_POST['old_name']) : '';
        $new_name = isset($_POST['new_name']) ? sanitize_text_field($_POST['new_name']) : '';

        if (empty($old_name) || empty($new_name)) {
            wp_send_json_error(array('message' => 'Begge feltene må fylles ut'));
            return;
        }

        $mappings = get_option('kursagenten_location_mappings', array());
        if (isset($mappings[$old_name])) {
            $old_mapped_value = $mappings[$old_name];
            $mappings[$old_name] = $new_name;
            update_option('kursagenten_location_mappings', $mappings);
            
            // Update slugs on existing sub-courses
            require_once KURSAG_PLUGIN_DIR . '/includes/api/api_course_sync.php';
            $updated_count = 0;
            if (function_exists('kursagenten_update_course_slugs_for_location_mapping')) {
                // Use the old mapped value (what was actually used in posts) to find courses
                $updated_count = kursagenten_update_course_slugs_for_location_mapping($old_mapped_value, $new_name);
            }
            
            wp_send_json_success(array(
                'message' => 'Navnendring oppdatert' . ($updated_count > 0 ? " ($updated_count kurs oppdatert)" : ''),
                'updated_count' => $updated_count
            ));
        } else {
            wp_send_json_error(array('message' => 'Navnendring ikke funnet'));
        }
    }

    /**
     * AJAX handler for getting available location terms
     */
    public function ajax_get_location_terms() {
        check_ajax_referer('kursagenten_location_mapping_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Ikke tilgang'));
            return;
        }

        $mappings = get_option('kursagenten_location_mappings', array());
        $mapped_old_names = array_keys($mappings);

        $terms = get_terms(array(
            'taxonomy' => 'ka_course_location',
            'hide_empty' => false,
        ));

        $available_terms = array();
        foreach ($terms as $term) {
            // Only include terms that are not already mapped
            if (!in_array($term->name, $mapped_old_names)) {
                $available_terms[] = array(
                    'id' => $term->term_id,
                    'name' => $term->name,
                );
            }
        }

        wp_send_json_success(array('terms' => $available_terms));
    }
}
?>
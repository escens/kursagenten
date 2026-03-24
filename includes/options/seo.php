<?php
class SEO {
    private $kag_seo_options;


    public function __construct() {
        add_action('admin_menu', array($this, 'kag_seo_add_plugin_page'));
        add_action('admin_init', array($this, 'kag_seo_page_init'));
        add_action('update_option_kag_seo_option_name', array($this, 'flush_rewrite_rules_on_update'), 10, 2);
        //add_action('update_option_course_slug', 'flush_rewrite_rules');
    }

    public function kag_seo_add_plugin_page() {
        add_submenu_page(
            'kursagenten',         // Parent slug
            'Url-er', // page_title
            'URL-er og SEO', // menu_title
            'manage_options',      // capability
            'seo', // menu_slug
            array($this, 'kag_seo_create_admin_page')
            //, // function
            //'dashicons-store',     // icon_url
            //2                      // position
        );
    }

    public function kag_seo_create_admin_page() {
        $this->kag_seo_options = get_option('kag_seo_option_name'); 
        
        ?>
        <div class="wrap options-form ka-wrap" id="toppen">
        <form method="post" action="options.php">
            <?php kursagenten_sticky_admin_menu(); ?>
            <h1>SEO innstillinger</h1><br><br>

                <?php
                settings_fields('kag_seo_option_group');
                do_settings_sections('seo-admin');
                ?>

                <!-- Fyll ut feltene under -->
                <div class="options-card">
                <h3 id="url">Endre url prefix</h3>
                <p><strong>Viktig info om url-er</strong><br>Her kan du endre url for kurs, instruktør, kurskategori og kurssted. <span style="color:#b74444;font-weight:bold;">OBS! Ikke rør med mindre du vet hva du gjør.</span> Det kan ødelegge nettstedet, og gjøre disse sidene utilgjengelige. Husk å lagre <a href="/wp-admin/options-permalink.php" target="_blank">permalenkeinnstillingene</a> etter du har gjort en endring.</p>

                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Kurs</th>
                        <td>
                            <input class="regular-text" type="text" name="kag_seo_option_name[ka_url_rewrite_kurs]" value="<?php echo isset($this->kag_seo_options['ka_url_rewrite_kurs']) ? esc_attr($this->kag_seo_options['ka_url_rewrite_kurs']) : ''; ?>">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Instruktør</th>
                        <td>
                            <input class="regular-text" type="text" name="kag_seo_option_name[ka_url_rewrite_instruktor]" value="<?php echo isset($this->kag_seo_options['ka_url_rewrite_instruktor']) ? esc_attr($this->kag_seo_options['ka_url_rewrite_instruktor']) : ''; ?>">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Kurskategori</th>
                        <td>
                            <input class="regular-text" type="text" name="kag_seo_option_name[ka_url_rewrite_kurskategori]" value="<?php echo isset($this->kag_seo_options['ka_url_rewrite_kurskategori']) ? esc_attr($this->kag_seo_options['ka_url_rewrite_kurskategori']) : ''; ?>">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Kurssted</th>
                        <td>
                            <input class="regular-text" type="text" name="kag_seo_option_name[ka_url_rewrite_kurssted]" value="<?php echo isset($this->kag_seo_options['ka_url_rewrite_kurssted']) ? esc_attr($this->kag_seo_options['ka_url_rewrite_kurssted']) : ''; ?>">
                        </td>
                    </tr>

                </table>
                </div>

                <!-- SEO på/av og dokumentasjon -->
                <div class="options-card">
                <h3 id="seo">SEO på kurs og taksonomisider</h3>
                <p>Kursagenten legger til meta-tagger, Open Graph, Twitter Cards og Course-schema på kurs- og taksonomisider. Når du har en SEO-utvidelse installert, tilpasser vi oss automatisk for å unngå duplikater. <br>Du kan også skru av vår SEO helt hvis du bruker andre løsninger.</p>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Skru av SEO</th>
                        <td>
                            <label>
                                <input type="checkbox" name="kag_seo_option_name[ka_seo_disable]" value="1" <?php checked(isset($this->kag_seo_options['ka_seo_disable']) && $this->kag_seo_options['ka_seo_disable']); ?>>
                                Skru av SEO på kurs og taksonomisider
                            </label>
                            <p class="description">Aktiver dette hvis du bruker andre SEO-utvidelser som ikke er listet under, eller ønsker å håndtere SEO helt selv.</p>
                        </td>
                    </tr>
                </table>

                <h4 style="margin-top: 1.5em;">Støttede SEO-utvidelser</h4>
                <p class="description">Når disse er aktive, slår vi av våre meta-tagger og overlater til utvidelsen. Course-schema leveres av oss for de som ikke har det innebygd.</p>
                <table class="widefat striped" style="max-width: 900px; margin-top: 0.5em;">
                    <thead>
                        <tr>
                            <th>Utvidelse</th>
                            <th>Vår SEO av (meta-tagger)</th>
                            <th>Vår Course-schema</th>
                            <th>Våre tilpasninger (tittel/beskrivelse)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>Yoast SEO</td><td>✓</td><td>✓</td><td>Instruktør-tittel</td></tr>
                        <tr><td>Rank Math</td><td>✓</td><td>Av (har egen)</td><td>Instruktør-tittel</td></tr>
                        <tr><td>All in One SEO</td><td>✓</td><td>✓</td><td>–</td></tr>
                        <tr><td>Slim SEO</td><td>✓</td><td>✓</td><td>Instruktør-tittel, kurs tittel/beskrivelse</td></tr>
                        <tr><td>SEOPress</td><td>✓</td><td>✓</td><td>Instruktør-tittel, kurs tittel/beskrivelse</td></tr>
                        <tr><td>The SEO Framework</td><td>✓</td><td>✓</td><td>Instruktør-tittel, kurs tittel/beskrivelse</td></tr>
                    </tbody>
                </table>

                <h4 style="margin-top: 1.5em;">Hva Kursagenten legger til (når ingen SEO-utvidelse er aktiv)</h4>
                <table class="widefat striped" style="max-width: 900px; margin-top: 0.5em;">
                    <thead>
                        <tr>
                            <th>Element</th>
                            <th>Kurs</th>
                            <th>Taksonomier (kategori, sted, instruktør)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>Canonical URL</td><td>✓</td><td>✓</td></tr>
                        <tr><td>Meta description</td><td>✓</td><td>✓</td></tr>
                        <tr><td>Open Graph (Facebook, LinkedIn)</td><td>✓</td><td>✓</td></tr>
                        <tr><td>Twitter Cards</td><td>✓</td><td>✓</td></tr>
                        <tr><td>Course-schema (JSON-LD)</td><td>✓</td><td>–</td></tr>
                    </tbody>
                </table>
                </div>

                <?php submit_button(); ?>

    <?php
    kursagenten_admin_footer();
    }

    public function kag_seo_page_init() {
        register_setting(
            'kag_seo_option_group', // option_group
            'kag_seo_option_name',  // option_name
            array($this, 'kag_seo_sanitize') // sanitize_callback
        );

        // Sections have been removed since fields are directly integrated in the form HTML
    }

    public function kag_seo_sanitize($input) {
        $sanitary_values = array();
        // Defensiv sjekk for å unngå fatale feil ved uventede typer
        if (!is_array($input)) {
            error_log('Kursagenten: kag_seo_sanitize expected array, got ' . gettype($input));
            $existing = get_option('kag_seo_option_name', array());
            return is_array($existing) ? $existing : array();
        }

        try {
            // Checkbox: when unchecked it's not in POST, so default to 0
            $sanitary_values['ka_seo_disable'] = isset($input['ka_seo_disable']) && $input['ka_seo_disable'] ? '1' : '0';

            foreach ($input as $key => $value) {
                if ($key === 'ka_seo_disable') {
                    continue; // Already handled
                }
                $sanitary_values[$key] = sanitize_text_field($value);
            }
        } catch (\Throwable $e) {
            error_log('Kursagenten: kag_seo_sanitize error: ' . $e->getMessage());
            $existing = get_option('kag_seo_option_name', array());
            return is_array($existing) ? $existing : array();
        }

        return $sanitary_values;
    }
    
    public function flush_rewrite_rules_on_update($old_value, $new_value) {
        // Sjekk om noen av URL-innstillingene har endret seg
        $url_fields = array('ka_url_rewrite_kurs', 'ka_url_rewrite_instruktor', 'ka_url_rewrite_kurskategori', 'ka_url_rewrite_kurssted');
        $has_changes = false;
        
        foreach ($url_fields as $field) {
            if (isset($old_value[$field]) && isset($new_value[$field]) && $old_value[$field] !== $new_value[$field]) {
                $has_changes = true;
                break;
            }
        }
        
        if ($has_changes) {
            flush_rewrite_rules();
            // Clear menu cache so automeny URLs use the new slugs
            if (function_exists('kursagenten_clear_all_menu_caches')) {
                kursagenten_clear_all_menu_caches();
            }
        }
    }
}

?>
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
            'Endre url-er', // menu_title
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
            <h2>Endre url-er</h2>
            <p><strong>Viktig info om url-er</strong><br>Her kan du endre url for kurs, instruktør, kurskategori og kurssted. <span style="color:#b74444;font-weight:bold;">OBS! Ikke rør med mindre du vet hva du gjør.</span> Det kan ødelegge nettstedet, og gjøre disse sidene utilgjengelige. Husk å lagre <a href="/wp-admin/options-permalink.php" target="_blank">permalenkeinnstillingene</a> etter du har gjort en endring.</p>

                <?php
                settings_fields('kag_seo_option_group');
                do_settings_sections('seo-admin');
                ?>

                <!-- Fyll ut feltene under -->
                <div class="options-card">
                <h3 id="url">Endre url prefix</h3>
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
        foreach ($input as $key => $value) {
            $sanitary_values[$key] = sanitize_text_field($value);
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
        }
    }
}

?>

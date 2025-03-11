<?php
class Avansert {
    private $kag_avansert_options;


    public function __construct() {
        add_action('admin_menu', array($this, 'kag_avansert_add_plugin_page'));
        add_action('admin_init', array($this, 'kag_avansert_page_init'));
    }

    public function kag_avansert_add_plugin_page() {
        add_submenu_page(
            'kursagenten',         // Parent slug
            'Avanserte innstillinger', // page_title
            'Avanserte innstillinger', // menu_title
            'manage_options',      // capability
            'avansert', // menu_slug
            array($this, 'kag_avansert_create_admin_page')
        );
    }

    public function kag_avansert_create_admin_page() {
        $this->kag_avansert_options = get_option('kag_avansert_option_name'); ?>

        <div class="wrap options-form">
            <h2>Avanserte innstillinger</h2>
            <p>Skru på de innstillingene du ønsker å bruke:</p>
            <?php settings_errors(); ?>

            <form method="post" action="options.php">
                <?php
                settings_fields('kag_avansert_option_group');
                do_settings_sections('avansert-admin');
                ?>

                <!-- Innlegg til artikler -->
                <h3>Omdøp innlegg til Artikler</h3>
                <table class="form-table">
                    <tr valign="top">
                        <td>
                            <label for="ka_rename_posts">
                            <?php $this->ka_rename_posts_callback(); ?>
                            Omdøp innlegg til Artikler</label>
                            <p class="description">I Wordpress finnes to typer sider: Sider og innlegg. Innlegg tilsvarer blogginnlegg eller annet som har kategorier og tagger. Aktiver for å omdøpe innlegg til "Artikler".<br><br></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <td>
                            <label for="ka_jquery_support">
                            <?php $this->ka_jquery_support_callback(); ?>
                            Aktiver støtte for jQuery</label>
                            <p class="description">Wordpress har stort sett støtte for javascript biblioteket Jquery. Hvis det ikke er støtte for dette, kan du velge å aktivere det her.<br><br></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <td>
                            <label for="ka_sitereviews">
                            <?php $this->ka_sitereviews_callback(); ?>
                            Støtte for "Site Reviews"-plugin, anmeldelser</label>
                            <p class="description">Dette legger inn rating i Course schema via Rank Math på kurssidene. Det legger også inn redirect basert på querystrings i eposter fra Kursagenten, til rating skjema på korrekt kursside.<br><br></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <td>
                            <label for="ka_security">
                            <?php $this->ka_security_callback(); ?>
                            Aktiver ekstra sikkerhetsfunksjoner</label>
                            <p class="description">Dette valget legger til:<br>
                            1) Clickjacking Protection (X-Frame-Options) i WordPress, styrker security headers.<br>
                            2) Deaktiverer tema- og plugin redigering<br>
                            3) Fjerner WP versjonsnummer
                            <br><br></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <td>
                            <label for="ka_disable_gravatar">
                            <?php $this->ka_disable_gravatar_callback(); ?>
                            Deaktiver Gravatar på frontend</label>
                            <p class="description">Dette valget deaktiverer Gravatar på frontend og bruker et lokalt standardbilde i stedet. Dette kan løse problemer med tracking prevention i enkelte nettlesere.<br><br></p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
    <?php
    }
    
    public function ka_rename_posts_callback() {
        $current_value = isset($this->kag_avansert_options['ka_rename_posts']) ? $this->kag_avansert_options['ka_rename_posts'] : 0;
        ?>
             <input type="checkbox" id="ka_rename_posts" name="kag_avansert_option_name[ka_rename_posts]" value="1" <?php checked($current_value, 1); ?> />     
        <?php
    }
    public function ka_jquery_support_callback() {
        $current_value = isset($this->kag_avansert_options['ka_jquery_support']) ? $this->kag_avansert_options['ka_jquery_support'] : 0;
        ?>
             <input type="checkbox" id="ka_jquery_support" name="kag_avansert_option_name[ka_jquery_support]" value="1" <?php checked($current_value, 1); ?> />     
        <?php
    }
    public function ka_sitereviews_callback() {
        $current_value = isset($this->kag_avansert_options['ka_sitereviews']) ? $this->kag_avansert_options['ka_sitereviews'] : 0;
        ?>
        <input type="checkbox" id="ka_sitereviews" name="kag_avansert_option_name[ka_sitereviews]" value="1" <?php checked($current_value, 1); ?> />
        <?php
    }
    public function ka_security_callback() {
        $current_value = isset($this->kag_avansert_options['ka_security']) ? $this->kag_avansert_options['ka_security'] : 0;
        ?>
             <input type="checkbox" id="ka_security" name="kag_avansert_option_name[ka_security]" value="1" <?php checked($current_value, 1); ?> />     
        <?php
    }
    public function ka_disable_gravatar_callback() {
        $current_value = isset($this->kag_avansert_options['ka_disable_gravatar']) ? $this->kag_avansert_options['ka_disable_gravatar'] : 0;
        ?>
        <input type="checkbox" id="ka_disable_gravatar" name="kag_avansert_option_name[ka_disable_gravatar]" value="1" <?php checked($current_value, 1); ?> />
        <?php
    }


    public function kag_avansert_page_init() {
        register_setting(
            'kag_avansert_option_group', // option_group
            'kag_avansert_option_name',  // option_name
            array($this, 'kag_avansert_sanitize') // sanitize_callback
        );
    }


    public function kag_avansert_sanitize($input) {
        $sanitary_values = array();

        foreach ($input as $key => $value) {
            // Sjekk om nøkkelen er en av checkbox-feltene
            if (in_array($key, array('ka_security', 'ka_sitereviews', 'ka_jquery_support', 'ka_rename_posts'))) {
                // Hvis feltet er en checkbox, sett verdien til 1 eller 0
                $sanitary_values[$key] = isset($value) ? 1 : 0;
            } else {
                // Standard sanitering for andre felter
                $sanitary_values[$key] = sanitize_text_field($value);
            }
        }
        return $sanitary_values;
    }

}

?>

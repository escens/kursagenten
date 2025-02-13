<?php
class Designmaler {
    private $design_options;


    public function __construct() {
        add_action('admin_menu', array($this, 'design_add_plugin_page'));
        add_action('admin_init', array($this, 'design_page_init'));
    }

    public function design_add_plugin_page() {
        add_submenu_page(
            'kursagenten',         // Parent slug
            'Kursdesign', // page_title
            'Kursdesign', // menu_title
            'manage_options',      // capability
            'design', // menu_slug
            array($this, 'design_create_admin_page')
            //, // function
            //'dashicons-store',     // icon_url
            //2                      // position
        );
    }

    public function design_create_admin_page() {
        $this->design_options = get_option('design_option_name'); ?>

        <div class="wrap options-form">
            <h2>design</h2>
            <p>Her kan du skrive inn informasjon som vil bli brukt ulike steder på nettsiden. Dette inkluderer navn på hovedkontakt (personvernerklæring), samt firmanavn og adresse (kontaktside og bunnfelt).</p>
            <?php settings_errors(); ?>

            <form method="post" action="options.php">
                <?php
                settings_fields('design_option_group');
                do_settings_sections('design-admin');
                ?>

                <!-- Fyll ut feltene under -->
                <h3>Fyll ut feltene under</h3>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Firmanavn</th>
                        <td>
                            <input class="regular-text" type="text" name="design_option_name[ka_firmanavn]" value="<?php echo isset($this->design_options['ka_firmanavn']) ? esc_attr($this->design_options['ka_firmanavn']) : ''; ?>">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Adresse</th>
                        <td>
                            <input class="regular-text" type="text" name="design_option_name[ka_adresse]" value="<?php echo isset($this->design_options['ka_adresse']) ? esc_attr($this->design_options['ka_adresse']) : ''; ?>">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Postnr/sted</th>
                        <td>
                            <input style="width:32%;float:left;" class="regular-text" type="text" name="design_option_name[ka_postnummer]" value="<?php echo isset($this->design_options['ka_postnummer']) ? esc_attr($this->design_options['ka_postnummer']) : ''; ?>">
                            <input style="width:65%;float:left;" class="regular-text" type="text" name="design_option_name[ka_sted]" value="<?php echo isset($this->design_options['ka_sted']) ? esc_attr($this->design_options['ka_sted']) : ''; ?>">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Daglig leder</th>
                        <td>
                            <input class="regular-text" type="text" name="design_option_name[ka_hovedkontakt_navn]" value="<?php echo isset($this->design_options['ka_hovedkontakt_navn']) ? esc_attr($this->design_options['ka_hovedkontakt_navn']) : ''; ?>">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Epost</th>
                        <td>
                            <input class="regular-text" type="text" name="design_option_name[ka_epost]" value="<?php echo isset($this->design_options['ka_epost']) ? esc_attr($this->design_options['ka_epost']) : ''; ?>">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Telefon</th>
                        <td>
                            <input class="regular-text" type="text" name="design_option_name[ka_tlf]" value="<?php echo isset($this->design_options['ka_tlf']) ? esc_attr($this->design_options['ka_tlf']) : ''; ?>">
                        </td>
                    </tr>
                </table>

                <!-- Kort info om bedriften -->
                <h3>Kort info om bedriften</h3>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Om firmaet</th>
                        <td>
                            <textarea class="large-text" rows="3" name="design_option_name[ka_infotekst]"><?php echo isset($this->design_options['ka_infotekst']) ? esc_textarea($this->design_options['ka_infotekst']) : ''; ?></textarea>
                        </td>
                    </tr>
                </table>

                <!-- URL til sosiale profiler -->
                <h3>Url til sosiale profiler</h3>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Facebook</th>
                        <td>
                            <input class="regular-text" type="text" name="design_option_name[ka_facebook]" value="<?php echo isset($this->design_options['ka_facebook']) ? esc_attr($this->design_options['ka_facebook']) : ''; ?>">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Instagram</th>
                        <td>
                            <input class="regular-text" type="text" name="design_option_name[ka_instagram]" value="<?php echo isset($this->design_options['ka_instagram']) ? esc_attr($this->design_options['ka_instagram']) : ''; ?>">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">LinkedIn</th>
                        <td>
                            <input class="regular-text" type="text" name="design_option_name[ka_linkedin]" value="<?php echo isset($this->design_options['ka_linkedin']) ? esc_attr($this->design_options['ka_linkedin']) : ''; ?>">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">YouTube</th>
                        <td>
                            <input class="regular-text" type="text" name="design_option_name[ka_youtube]" value="<?php echo isset($this->design_options['ka_youtube']) ? esc_attr($this->design_options['ka_youtube']) : ''; ?>">
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
    <?php
    }

    public function design_page_init() {
        register_setting(
            'design_option_group', // option_group
            'design_option_name',  // option_name
            array($this, 'design_sanitize') // sanitize_callback
        );

        // Sections have been removed since fields are directly integrated in the form HTML
    }

    public function design_sanitize($input) {
        $sanitary_values = array();
        foreach ($input as $key => $value) {
            $sanitary_values[$key] = sanitize_text_field($value);
        }
        return $sanitary_values;
    }
}

?>

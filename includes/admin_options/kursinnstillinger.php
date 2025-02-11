<?php
class Kursinnstillinger {
    private $kag_kursinnst_options;
    


    public function __construct() {
        add_action('admin_menu', array($this, 'kag_kursinnst_add_plugin_page'));
        add_action('admin_init', array($this, 'kag_kursinnst_page_init'));
    }

    public function kag_kursinnst_add_plugin_page() {
        add_submenu_page(
            'kursagenten',         // Parent slug
            'Kursinnstillinger', // page_title
            'Kursinnstillinger', // menu_title
            'manage_options',      // capability 
            'kursinnstillinger', // menu_slug
            array($this, 'kag_kursinnst_create_admin_page')
            //, // function
            //'dashicons-store',     // icon_url
            //2                      // position
        );
    }

    public function kag_kursinnst_create_admin_page() {
        $this->kag_kursinnst_options = get_option('kag_kursinnst_option_name'); 
        require_once plugin_dir_path(__FILE__) . '/../kurs_sync/kurs_sync_all_courses_from_admin_settings.php';
        
        ?>

        <div class="wrap options-form">
            <h2>Kursinnstillinger</h2>
            <p>Her kan du skrive inn informasjon som vil bli brukt ulike steder på nettsiden. Dette inkluderer navn på hovedkontakt (personvernerklæring), samt firmanavn og adresse (kontaktside og bunnfelt).</p>
            <?php settings_errors(); ?>
            <?php echo kursagenten_sync_courses_button(); // fra /api_sync/ ?>

            <form method="post" action="options.php">
                <?php
                settings_fields('kag_kursinnst_option_group');
                do_settings_sections('kursinnstillinger-admin');
                ?>

                <!-- Fyll ut feltene under -->
                <h3>Valg for bilder</h3>
                <p>Standarbilder brukes som en backupløsning for å hindre ødelagte design. Disse brukes som plassholdere om et bilde mangler. Velger du ingen bilder, bruker vi Kursagentens standard erstatningsikoner om nødvendig.</p>
                <table class="form-table">

                    <tr valign="top" style="padding-bottom: 2em; display: block;">
                        <th scope="row">Kategoribilder</th>
                        <td style=" padding-top: 16px;">
                        <?php $this->kategoribilder_callback(); ?>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Plassholderbilde generelt</th>
                        <td>
                            <?php $this->plassholderbilde_generelt_callback(); ?>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Plassholderbilde kurs</th>
                        <td>
                            <?php $this->plassholderbilde_kurs_callback(); ?>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Plassholderbilde instruktør</th>
                        <td>
                            <?php $this->plassholderbilde_instruktor_callback(); ?>
                        </td>
                    </tr>
                   
                </table>


                <!-- Innstillinger fra Kursagenten -> Bedriftsinformasjon -> Innstillinger -->
                <h3>Innstillinger fra Kursagenten</h3>
                <p>Du finner disse innstillingene i Kursagenten under <a href="https://kursadmin.kursagenten.no/ProviderInformation" target="_blank">Bedriftsinsformasjon-> Innstillinger</a>, og under <a href="https://kursadmin.kursagenten.no/IframeSetting" target="_blank">Embedded / iframe</a></p>
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

                <?php submit_button(); ?>
            </form>
        </div>
    <?php
    }
    
    public function kategoribilder_callback() {
        $current_value = isset($this->kag_kursinnst_options['ka_kategoribilder']) ? $this->kag_kursinnst_options['ka_kategoribilder'] : 'egne-kategoribilder';

        $choices = [
            'bruk-kursbilde' => 'Bruk første tilgjengelige kursbilde. Egne kategoribilder vil overskrive kursbilder.',
            'egne-kategoribilder' => 'Jeg vil ikke bruke kategoribilder, eller legger inn egne.'
        ];

        foreach ($choices as $value => $label) {
            printf(
                '<div style="margin-bottom: 3px;"><input class="radioknapp" type="radio" name="kag_kursinnst_option_name[ka_kategoribilder]" value="%s" %s> %s</div>',
                esc_attr($value),
                checked($current_value, $value, false),
                esc_html($label)
            );
        }
    }
    
    public function plassholderbilde_generelt_callback() {
        $image_url = isset($this->kag_kursinnst_options['ka_plassholderbilde_generelt']) ? $this->kag_kursinnst_options['ka_plassholderbilde_generelt'] : '';
        ?>
        <div class="image-upload-wrapper">
            <img id="ka_plassholderbilde_generelt_preview" src="<?php echo esc_url($image_url); ?>" style="max-width: 150px; <?php echo $image_url ? '' : 'display: none;'; ?>" />
            <input type="hidden" id="ka_plassholderbilde_generelt" name="kag_kursinnst_option_name[ka_plassholderbilde_generelt]" value="<?php echo esc_attr($image_url); ?>" />
            <button type="button" class="button upload_image_button_ka_plassholderbilde_generelt">Velg bilde</button>
            <button type="button" class="button remove_image_button_ka_plassholderbilde_generelt" style="<?php echo $image_url ? '' : 'display: none;'; ?>">Fjern bilde</button>
        </div>
    <?php
    }

    public function plassholderbilde_kurs_callback() {
        $image_url = isset($this->kag_kursinnst_options['ka_plassholderbilde_kurs']) ? $this->kag_kursinnst_options['ka_plassholderbilde_kurs'] : '';
        ?>
        <div class="image-upload-wrapper">
            <img id="ka_plassholderbilde_kurs_preview" src="<?php echo esc_url($image_url); ?>" style="max-width: 150px; <?php echo $image_url ? '' : 'display: none;'; ?>" />
            <input type="hidden" id="ka_plassholderbilde_kurs" name="kag_kursinnst_option_name[ka_plassholderbilde_kurs]" value="<?php echo esc_attr($image_url); ?>" />
            <button type="button" class="button upload_image_button_ka_plassholderbilde_kurs">Velg bilde</button>
            <button type="button" class="button remove_image_button_ka_plassholderbilde_kurs" style="<?php echo $image_url ? '' : 'display: none;'; ?>">Fjern bilde</button>
        </div>
    <?php
    }

    public function plassholderbilde_instruktor_callback() {
        $image_url = isset($this->kag_kursinnst_options['ka_plassholderbilde_instruktor']) ? $this->kag_kursinnst_options['ka_plassholderbilde_instruktor'] : '';
        ?>
        <div class="image-upload-wrapper">
            <img id="ka_plassholderbilde_instruktor_preview" src="<?php echo esc_url($image_url); ?>" style="max-width: 150px; <?php echo $image_url ? '' : 'display: none;'; ?>" />
            <input type="hidden" id="ka_plassholderbilde_instruktor" name="kag_kursinnst_option_name[ka_plassholderbilde_instruktor]" value="<?php echo esc_attr($image_url); ?>" />
            <button type="button" class="button upload_image_button_ka_plassholderbilde_instruktor">Velg bilde</button>
            <button type="button" class="button remove_image_button_ka_plassholderbilde_instruktor" style="<?php echo $image_url ? '' : 'display: none;'; ?>">Fjern bilde</button>
        </div>
    <?php
    }
    
    
    public function kag_kursinnst_page_init() {
        register_setting(
            'kag_kursinnst_option_group', // option_group
            'kag_kursinnst_option_name',  // option_name
            array($this, 'kag_kursinnst_sanitize') // sanitize_callback
        );

        // Sections have been removed since fields are directly integrated in the form HTML
    }

    public function kag_kursinnst_sanitize($input) {
        $sanitary_values = array();
        foreach ($input as $key => $value) {
            $sanitary_values[$key] = sanitize_text_field($value);
        }
        return $sanitary_values;
    }
}

?>

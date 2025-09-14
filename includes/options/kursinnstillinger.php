<?php
class Kursinnstillinger {
    private $kag_kursinnst_options;

    public function __construct() {
        add_action('admin_menu', array($this, 'kag_kursinnst_add_plugin_page'));
        add_action('admin_init', array($this, 'kag_kursinnst_page_init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    // Shortcodes for settings in kursinnstillinger.php is in misc/kursagenten-shortcodes.php

    public function enqueue_admin_scripts($hook) {
        if ('kursagenten_page_kursinnstillinger' !== $hook) {
            return;
        }
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
}
?>

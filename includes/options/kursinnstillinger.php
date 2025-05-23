<?php
class Kursinnstillinger {
    private $kag_kursinnst_options;

    public function __construct() {
        add_action('admin_menu', array($this, 'kag_kursinnst_add_plugin_page'));
        add_action('admin_init', array($this, 'kag_kursinnst_page_init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    public function enqueue_admin_scripts($hook) {
        if ('kursagenten_page_kursinnstillinger' !== $hook) {
            return;
        }

        wp_enqueue_script('jquery-ui-sortable');
        wp_add_inline_script('jquery-ui-sortable', '
            jQuery(document).ready(function($) {
                $(".sortable-list").sortable({
                    connectWith: ".sortable-list",
                    placeholder: "ui-state-highlight",
                    start: function(event, ui) {
                        ui.placeholder.css({
                            "height": ui.item.height(),
                            "background": "#f3f8ff",
                            "border": "1px dashed #1e88e5",
                            "margin": "10px 5px"
                        });
                    },
                    over: function(event, ui) {
                        $(this).addClass("ui-state-hover");
                    },
                    out: function(event, ui) {
                        $(this).removeClass("ui-state-hover");
                    },
                    update: function() {
                        let topFilters = $("#top-filters").sortable("toArray", { attribute: "data-filter" });
                        let leftFilters = $("#left-filters").sortable("toArray", { attribute: "data-filter" });

                        $("#top-filters-input").val(topFilters.join(","));
                        $("#left-filters-input").val(leftFilters.join(","));

                        // Beholder radioknappene etter flytting
                        $(".sortable-list li").each(function() {
                            let filter = $(this).attr("data-filter");
                            if (["categories", "locations", "instructors", "language", "months", "time_of_day"].includes(filter)) {
                                if ($(this).find(".filter-type-options").length === 0) {
                                    $(this).append(`
                                        <span class="filter-type-options">
                                            <label><input type="radio" name="kursagenten_filter_types[${filter}]" value="chips"> Knapper</label>
                                            <label><input type="radio" name="kursagenten_filter_types[${filter}]" value="list" checked> Liste</label>
                                        </span>
                                    `);
                                }
                            }
                        });
                    }
                }).disableSelection();
            });
        ');

        wp_add_inline_style('wp-admin', '
            .filter-containers {
                display: grid;
                grid-template-columns: 1fr 2fr;
                gap: 1em;
            }
            .sortable-list {
                list-style: none;
                padding: 10px;
                background: #f6f6f67a;
                min-height: 50px;
                border: 2px dashed #cccccc61;
                border-radius: 8px;
            }
            .sortable-list.ui-state-hover {
                background: #e8f0fe;
                border: 2px dashed #1e88e5;
                transition: all 0.2s ease;
            }
            .sortable-list li.ui-sortable-helper {
                background: #ffffff;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                transform: scale(1.02);
                transition: all 0.2s ease;
            }
            .sortable-list li {
                padding: 10px 10px;
                margin: 10px 5px;
                background: #fff;
                cursor: move;
                border: 1px solid #e5e5e5;
                border-radius: 5px;
                font-weight: bold;
                position: relative;
            }
            #available-filters.sortable-list {
                display: flex;
                border: 0;
                background: #f6f6f67a;
                padding: 1em;
                border: 2px dashed #ccc;
                border-radius: 8px;
            }
            #available-filters.sortable-list li {
                width: fit-content;
                height: fit-content;
            }
            .sortable-list li {
                padding: 10px 15px 10px 20px;
                position: relative;
            }
            .sortable-list li i.ka-icon {
                position: absolute;
                top: 12px;
                left: 2px;
            }
            #available-filters .filter-type-options {
                display: none;
            }
            .filter-type-options {
                float: right;
                margin-left: 10px;
                font-weight: normal;
                color: #777;
            }
            #left-filters .filter-type-options {
                float: none;
                clear: both;
                display: block;
                margin-top: 10px;
            }
            .disabled-filter {
                color: #616161;
                pointer-events: none;
                opacity: 0.6;
                position: relative;
            }
            li.disabled-filter::after {
                content: "kommer";
                display: block;
                position: absolute;
                right: -4px;
                bottom: -11px;
                background: #fff;
                color: #4d4d4d;
                font-size: 10px;
                font-weight: normal;
                padding: 0 4px;
                border-radius: 3px;
                border: 1px solid #eee;
            }
        ');
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
        require_once KURSAG_PLUGIN_DIR . '/includes/api/api_sync_on_demand.php';

        ?>
        <div class="wrap options-form ka-wrap" id="toppen">
        <form method="post" action="options.php">
        <?php 
        settings_fields('kag_kursinnst_option_group');
        do_settings_sections('kursinnstillinger-admin');
        ?>
        <?php kursagenten_sticky_admin_menu(); ?>
        <h2>Kursinnstillinger</h2>
        <p>Velg oppsett, design, fonter og farger på de ulike sidene.</p>
        <?php 
        
        // Add filter settings
        $available_filters = [
            'search' => [
                'label' => 'Søk',
                'placeholder' => 'Søk etter kurs'
            ],
            'categories' => [
                'label' => 'Kategorier',
                'placeholder' => 'Velg kategori'
            ],
            'locations' => [
                'label' => 'Kurssteder',
                'placeholder' => 'Velg kurssted'
            ],
            'instructors' => [
                'label' => 'Instruktører',
                'placeholder' => 'Velg instruktør'
            ],
            'language' => [
                'label' => 'Språk',
                'placeholder' => 'Velg språk'
            ],
            'time_of_day' => [
                'label' => 'Dag-/kveldskurs',
                'placeholder' => 'Velg tidspunkt'
            ],
            'price' => [
                'label' => 'Pris',
                'placeholder' => 'Velg pris'
            ],
            'date' => [
                'label' => 'Startdato',
                'placeholder' => 'Velg dato'
            ],
            'months' => [
                'label' => 'Startmåned',
                'placeholder' => 'Velg måned'
            ]
        ];
        $inactive_filters = ['time_of_day', 'price'];
        $top_filters = get_option('kursagenten_top_filters', []);
        $left_filters = get_option('kursagenten_left_filters', []);
        $filter_types = get_option('kursagenten_filter_types', []);

        // Ensure values are arrays
        $top_filters = is_array($top_filters) ? $top_filters : explode(',', $top_filters);
        $left_filters = is_array($left_filters) ? $left_filters : explode(',', $left_filters);

        // Save available_filters as an option
        update_option('kursagenten_available_filters', $available_filters);
        ?>

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

        <!-- Filter Settings -->
        
        <div class="options-card">
            <h3 id="filterinnstillinger">Filterinnstillinger</h3>
            <p>Ta tak i filteret du ønsker å bruke, og dra til enten venstre kolonne eller over kursliste. Velg om filteret skal vises som tagger eller avkrysningsliste.<br>
            For å fjerne et filter, dra det tilbake til tilgjengelige filtre. Husk å <strong>lagre</strong> endringene dine.
            </p>
            <div class="filter-selection">
                <h4>Tilgjengelige filtre:</h4>
                <ul id="available-filters" class="sortable-list">
                    <?php foreach ($available_filters as $key => $filter) : 
                        $disabled_class = in_array($key, $inactive_filters) ? 'disabled-filter' : '';
                        if (!in_array($key, $top_filters) && !in_array($key, $left_filters)) : ?>
                            <li data-filter="<?php echo esc_attr($key); ?>" class="ui-sortable-handle <?php echo $disabled_class; ?>"> 
                            <i class="ka-icon icon-grip-dots"></i> <?php echo esc_html($filter['label']); ?>
                                <?php if (in_array($key, ['categories', 'locations', 'instructors', 'language', 'months'])) : ?>
                                    <span class="filter-type-options">
                                        <label><input type="radio" name="kursagenten_filter_types[<?php echo esc_attr($key); ?>]" value="chips" <?php echo (isset($filter_types[$key]) && $filter_types[$key] === 'chips') ? 'checked' : ''; ?>> Knapper</label>
                                        <label><input type="radio" name="kursagenten_filter_types[<?php echo esc_attr($key); ?>]" value="list" <?php echo (!isset($filter_types[$key]) || $filter_types[$key] === 'list') ? 'checked' : ''; ?>> Liste</label>
                                    </span>
                                <?php endif; ?>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div class="filter-containers">
                <div class="filter-container">
                    <h4>Filtre i venstre kolonne</h4>
                    <ul id="left-filters" class="sortable-list">
                        <?php foreach ($left_filters as $filter) : ?>
                            <?php if (!empty($filter)) : ?>
                            <li data-filter="<?php echo esc_attr($filter); ?>">
                            <i class="ka-icon icon-grip-dots"></i> <?php echo esc_html($available_filters[$filter]['label']); ?>
                                <?php if (in_array($filter, ['categories', 'locations', 'instructors', 'language', 'months', 'time_of_day'])) : ?>
                                    <span class="filter-type-options">
                                        <label>
                                            <input type="radio" name="kursagenten_filter_types[<?php echo esc_attr($filter); ?>]" value="chips"
                                                <?php echo (isset($filter_types[$filter]) && $filter_types[$filter] === 'chips') ? 'checked' : ''; ?>> Knapper
                                        </label>
                                        <label>
                                            <input type="radio" name="kursagenten_filter_types[<?php echo esc_attr($filter); ?>]" value="list"
                                                <?php echo (!isset($filter_types[$filter]) || $filter_types[$filter] === 'list') ? 'checked' : ''; ?>> Liste
                                        </label>
                                    </span>
                                <?php endif; ?>
                            </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                    <input type="hidden" name="kursagenten_left_filters" id="left-filters-input" value="<?php echo esc_attr(implode(',', $left_filters)); ?>">
                </div>

                <div class="filter-container">
                    <h4>Filtre over kurslisten</h4>
                    <ul id="top-filters" class="sortable-list">
                        <?php foreach ($top_filters as $filter) : ?>
                            <?php if (!empty($filter)) : ?>
                            <li data-filter="<?php echo esc_attr($filter); ?>">
                            <i class="ka-icon icon-grip-dots"></i> <?php echo esc_html($available_filters[$filter]['label']); ?>
                                <?php if (in_array($filter, ['categories', 'locations', 'instructors', 'language', 'months', 'time_of_day'])) : ?>
                                    <span class="filter-type-options">
                                        <label>
                                            <input type="radio" name="kursagenten_filter_types[<?php echo esc_attr($filter); ?>]" value="chips"
                                                <?php echo (isset($filter_types[$filter]) && $filter_types[$filter] === 'chips') ? 'checked' : ''; ?>> Knapper
                                        </label>
                                        <label>
                                            <input type="radio" name="kursagenten_filter_types[<?php echo esc_attr($filter); ?>]" value="list"
                                                <?php echo (isset($filter_types[$filter]) && $filter_types[$filter] === 'list') ? 'checked' : ''; ?>> Liste
                                        </label>
                                    </span>
                                <?php endif; ?>
                            </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                    <input type="hidden" name="kursagenten_top_filters" id="top-filters-input" value="<?php echo esc_attr(implode(',', $top_filters)); ?>">
                </div>
            </div>
        </div>

        <div class="options-card">
            <h3 id="valg-for-bilder">Valg for bilder</h3>
            <p>Standarbilder brukes som en backupløsning for å hindre ødelagte design. Disse brukes som plassholdere om et bilde mangler. Velger du ingen bilder, bruker vi Kursagentens standard erstatningsbilder om nødvendig.</p>
            <table class="form-table">
                <tr>
                    <th scope="row">Generelt plassholderbilde</th>
                    <td><?php $this->plassholderbilde_generelt_callback(); ?></td>
                </tr>
                <tr>
                    <th scope="row">Plassholderbilde for kurs</th>
                    <td><?php $this->plassholderbilde_kurs_callback(); ?></td>
                </tr>
                <tr>
                    <th scope="row">Plassholderbilde for instruktør</th>
                    <td><?php $this->plassholderbilde_instruktor_callback(); ?></td>
                </tr>
                <tr>
                    <th scope="row">Plassholderbilde for sted</th>
                    <td><?php $this->plassholderbilde_sted_callback(); ?></td>
                </tr>
            </table>
        </div>
        <!-- Kursagenten Settings Section -->
        <div class="options-card">
            <h3 id="kursagenten-innstillinger">Innstillinger fra Kursagenten</h3>
            <p>Du finner innstillingene for <strong><a href="https://kursadmin.kursagenten.no/ProviderInformation" target="_blank">Tilbyder ID og Tilbyder Guid</a></strong> i Kursagenten under <em>Bedriftsinsformasjon-> Innstillinger</em>, og <strong><a href="https://kursadmin.kursagenten.no/IframeSetting" target="_blank">Tema for kurslister</a></strong> under <em>Embedded / iframe</em><br><br>
            I Integrasjonsinnstillinger, under fanen <strong><a href="https://kursadmin.kursagenten.no/IntegrationSettings" target="_blank">Webhooks</a></strong> skal du legge inn <span class="copytext" title="Klikk for å kopiere"><?php echo esc_url(site_url('/wp-json/kursagenten-api/v1/process-webhook')); ?></span> i feltene CourseCreated og CourseUpdated for å automatisk oppdatere kurs når det blir opprettet eller endret på Kursagenten.</p>
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

    public function plassholderbilde_generelt_callback() {
        $image_url = isset($this->kag_kursinnst_options['ka_plassholderbilde_generelt']) ? $this->kag_kursinnst_options['ka_plassholderbilde_generelt'] : '';
        $fallback_url = KURSAG_PLUGIN_URL . 'assets/images/placeholder-generell.jpg';
        ?>
        <div class="image-upload-wrapper">
            <img id="ka_plassholderbilde_generelt_preview" src="<?php echo esc_url($image_url ? $image_url : $fallback_url); ?>" style="max-width: 80px; max-height: 80px; <?php echo ($image_url || $fallback_url) ? '' : 'display: none;'; ?> border:1px solid #eee; background:#fafafa;" />
            <input type="hidden" id="ka_plassholderbilde_generelt" name="kag_kursinnst_option_name[ka_plassholderbilde_generelt]" value="<?php echo esc_attr($image_url); ?>" />
            <button type="button" class="button upload_image_button_ka_plassholderbilde_generelt">Velg bilde</button>
            <button type="button" class="button remove_image_button_ka_plassholderbilde_generelt" style="<?php echo $image_url ? '' : 'display: none;'; ?>">Fjern bilde</button>
        </div>
        <?php
    }

    public function plassholderbilde_kurs_callback() {
        $image_url = isset($this->kag_kursinnst_options['ka_plassholderbilde_kurs']) ? $this->kag_kursinnst_options['ka_plassholderbilde_kurs'] : '';
        $fallback_url = KURSAG_PLUGIN_URL . 'assets/images/placeholder-kurs.jpg';
        ?>
        <div class="image-upload-wrapper">
            <img id="ka_plassholderbilde_kurs_preview" src="<?php echo esc_url($image_url ? $image_url : $fallback_url); ?>" style="max-width: 80px; max-height: 80px; <?php echo ($image_url || $fallback_url) ? '' : 'display: none;'; ?> border:1px solid #eee; background:#fafafa;" />
            <input type="hidden" id="ka_plassholderbilde_kurs" name="kag_kursinnst_option_name[ka_plassholderbilde_kurs]" value="<?php echo esc_attr($image_url); ?>" />
            <button type="button" class="button upload_image_button_ka_plassholderbilde_kurs">Velg bilde</button>
            <button type="button" class="button remove_image_button_ka_plassholderbilde_kurs" style="<?php echo $image_url ? '' : 'display: none;'; ?>">Fjern bilde</button>
        </div>
        <?php
    }

    public function plassholderbilde_instruktor_callback() {
        $image_url = isset($this->kag_kursinnst_options['ka_plassholderbilde_instruktor']) ? $this->kag_kursinnst_options['ka_plassholderbilde_instruktor'] : '';
        $fallback_url = KURSAG_PLUGIN_URL . 'assets/images/placeholder-instruktor.jpg';
        ?>
        <div class="image-upload-wrapper">
            <img id="ka_plassholderbilde_instruktor_preview" src="<?php echo esc_url($image_url ? $image_url : $fallback_url); ?>" style="max-width: 80px; max-height: 80px; <?php echo ($image_url || $fallback_url) ? '' : 'display: none;'; ?> border:1px solid #eee; background:#fafafa;" />
            <input type="hidden" id="ka_plassholderbilde_instruktor" name="kag_kursinnst_option_name[ka_plassholderbilde_instruktor]" value="<?php echo esc_attr($image_url); ?>" />
            <button type="button" class="button upload_image_button_ka_plassholderbilde_instruktor">Velg bilde</button>
            <button type="button" class="button remove_image_button_ka_plassholderbilde_instruktor" style="<?php echo $image_url ? '' : 'display: none;'; ?>">Fjern bilde</button>
        </div>
        <?php
    }

    public function plassholderbilde_sted_callback() {
        $image_url = isset($this->kag_kursinnst_options['ka_plassholderbilde_sted']) ? $this->kag_kursinnst_options['ka_plassholderbilde_sted'] : '';
        $fallback_url = KURSAG_PLUGIN_URL . 'assets/images/placeholder-location.jpg';
        ?>
        <div class="image-upload-wrapper">
            <img id="ka_plassholderbilde_sted_preview" src="<?php echo esc_url($image_url ? $image_url : $fallback_url); ?>" style="max-width: 80px; max-height: 80px; <?php echo ($image_url || $fallback_url) ? '' : 'display: none;'; ?> border:1px solid #eee; background:#fafafa;" />
            <input type="hidden" id="ka_plassholderbilde_sted" name="kag_kursinnst_option_name[ka_plassholderbilde_sted]" value="<?php echo esc_attr($image_url); ?>" />
            <button type="button" class="button upload_image_button_ka_plassholderbilde_sted">Velg bilde</button>
            <button type="button" class="button remove_image_button_ka_plassholderbilde_sted" style="<?php echo $image_url ? '' : 'display: none;'; ?>">Fjern bilde</button>
        </div>
        <?php
    }
    
    public function kag_kursinnst_page_init() {
        register_setting(
            'kag_kursinnst_option_group',
            'kag_kursinnst_option_name',
            array($this, 'kag_kursinnst_sanitize')
        );

        // Eksisterende filter-registreringer
        register_setting('kag_kursinnst_option_group', 'kursagenten_top_filters');
        register_setting('kag_kursinnst_option_group', 'kursagenten_left_filters');
        register_setting('kag_kursinnst_option_group', 'kursagenten_filter_types');
        register_setting('kag_kursinnst_option_group', 'kursagenten_available_filters');
    }

    public function kag_kursinnst_sanitize($input) {
        $sanitary_values = array();
        
        // Legger til plassholderbilde for sted i listen
        $image_fields = [
            'ka_plassholderbilde_generelt',
            'ka_plassholderbilde_kurs',
            'ka_plassholderbilde_instruktor',
            'ka_plassholderbilde_sted'
        ];
        
        foreach ($input as $key => $value) {
            if (in_array($key, $image_fields) && empty($value)) {
                continue;
            }
            $sanitary_values[$key] = sanitize_text_field($value);
        }
        return $sanitary_values;
    }
}
?>

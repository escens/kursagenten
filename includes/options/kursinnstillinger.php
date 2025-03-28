<?php
class Kursinnstillinger {
    private $kag_kursinnst_options;
    private static $required_pages = null;
    


    public function __construct() {
        add_action('admin_menu', array($this, 'kag_kursinnst_add_plugin_page'));
        add_action('admin_init', array($this, 'kag_kursinnst_page_init'));
        
        // Legg til action for å håndtere systemside-operasjoner
        add_action('admin_post_ka_manage_system_pages', array($this, 'handle_system_pages_actions'));
        // Legg til AJAX action
        add_action('wp_ajax_ka_manage_system_pages', array($this, 'handle_system_pages_actions'));
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

        // Start page with header (includes form opening tag)
        kursagenten_admin_header('Kursinnstillinger');
        ?>
        <p>Velg oppsett, design, fonter og farger på de ulike sidene.</p>
        <?php 
        settings_fields('kag_kursinnst_option_group');
        do_settings_sections('kursinnstillinger-admin');
        
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
                'label' => 'Dato',
                'placeholder' => 'Velg dato'
            ],
            'months' => [
                'label' => 'Måned',
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

                if (empty($tilbyder_id) || empty($tilbyder_guid)) : ?>
co                    <div class="" style="margin: 10px 0;padding: 1px 10px; background: #d63638; border-radius: 5px; color:white; width: fit-content;">
                        <p><strong>OBS!</strong> Fyll inn <a href="#kursagenten-innstillinger" style="color:white;">innstillinger fra Kursagenten</a> før du henter alle kursene dine (synkroniser alle kurs).</p>
                    </div>
                <?php endif; ?>

                <!-- Fyll ut feltene under -->
                <h3 id="valg-for-bilder">Valg for bilder</h3>
                <p>Standarbilder brukes som en backupløsning for å hindre ødelagte design. Disse brukes som plassholdere om et bilde mangler. Velger du ingen bilder, bruker vi Kursagentens standard erstatningsikoner om nødvendig.</p>
                <table class="form-table options-card">

                    <tr valign="top">
                        <th scope="row" style="border-bottom: 1px solid #f3f3f3;">Kategoribilder</th>
                        <td style="padding-top: 16px; padding-bottom: 1.5em; border-bottom: 1px solid #f3f3f3; margin-bottom: 1.5em;">
                        <?php $this->kategoribilder_callback(); ?>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row" style="padding-top: 1.5em;">Plassholderbilde generelt</th>
                        <td style="padding-top: 1.5em;">
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
                    <tr valign="top">
                        <th scope="row">Plassholderbilde kurssted</th>
                        <td>
                            <?php $this->plassholderbilde_sted_callback(); ?>
                        </td>
                    </tr>
                </table>

                <!-- Filter Settings -->
                <h3 id="filterinnstillinger">Filterinnstillinger</h3>
                <p>Ta tak i filteret du ønsker å bruke, og dra til enten venstre kolonne eller over kursliste. Velg om filteret skal vises som tagger eller avkrysningsliste.<br>
                For å fjerne et filter, dra det tilbake til tilgjengelige filtre. Husk å <strong>lagre</strong> endringene dine.
                </p>
                <div class="options-card">
                    <div class="filter-selection">
                        <h4>Tilgjengelige filtre:</h4>
                        <ul id="available-filters" class="sortable-list">
                            <?php foreach ($available_filters as $key => $filter) : 
                                $disabled_class = in_array($key, $inactive_filters) ? 'disabled-filter' : '';
                                if (!in_array($key, $top_filters) && !in_array($key, $left_filters)) : ?>
                                    <li data-filter="<?php echo esc_attr($key); ?>" class="ui-sortable-handle <?php echo $disabled_class; ?>"> 
                                        <?php echo esc_html($filter['label']); ?>
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
                                        <?php echo esc_html($available_filters[$filter]['label']); ?>
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
                                        <?php echo esc_html($available_filters[$filter]['label']); ?>
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

                <!-- System Pages Section -->
                <h3 id="systemsider">Opprett sider</h3>
                <p>Administrer sider for taksonomiarkiver. Disse sidene brukes for å vise oversikter over kategorier, kurssteder og instruktører.</p>
                <?php 
                $this->render_system_pages_section();
                
                // Continue with Kursagenten settings
                ?>
                <h3 id="kursagenten-innstillinger">Innstillinger fra Kursagenten</h3>
                <p>Du finner innstillingene for Tilbyder ID og Guid i Kursagenten under <a href="https://kursadmin.kursagenten.no/ProviderInformation" target="_blank">Bedriftsinsformasjon-> Innstillinger</a>, og Temaer under <a href="https://kursadmin.kursagenten.no/IframeSetting" target="_blank">Embedded / iframe</a><br><br>
                I Integrasjonsinnstillinger -> <a href="https://kursadmin.kursagenten.no/IntegrationSettings" target="_blank">Webhooks</a> skal du legge inn <span class="copytext" title="Klikk for å kopiere"><?php echo esc_url(site_url('/wp-json/kursagenten-api/v1/process-webhook')); ?></span> i feltene CourseCreated og CourseUpdated for å få oppdatert kursliste når et kurs endres eller opprettes.</p>
                <table class="form-table options-card">
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

        <script>
            jQuery(document).ready(function($) {
                $(".sortable-list").sortable({
                    connectWith: ".sortable-list",
                    placeholder: "ui-state-highlight",
                    start: function(event, ui) {
                        ui.placeholder.css({
                            'height': ui.item.height(),
                            'background': '#f3f8ff',
                            'border': '1px dashed #1e88e5',
                            'margin': '10px 5px'
                        });
                    },
                    over: function(event, ui) {
                        $(this).addClass('ui-state-hover');
                    },
                    out: function(event, ui) {
                        $(this).removeClass('ui-state-hover');
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



        </script>
        <style>
            .filter-containers {display: grid; grid-template-columns: 1fr 2fr; gap: 1em; }
            .sortable-list { list-style: none; padding: 10px; background: #f6f6f67a; min-height: 50px; border: 2px dashed #cccccc61; border-radius: 8px; }
            /* Add styles for valid drop target */
            .sortable-list.ui-state-hover {
                background: #e8f0fe;
                border: 2px dashed #1e88e5;
                transition: all 0.2s ease;
            }
            /* Add styles for dragging state */
            .sortable-list li.ui-sortable-helper {
                background: #ffffff;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                transform: scale(1.02);
                transition: all 0.2s ease;
            }
            .sortable-list li { padding: 10px 10px; margin: 10px 5px; background: #fff; cursor: move; border: 1px solid #e5e5e5; border-radius: 5px; font-weight: bold;}
            #available-filters.sortable-list { display: flex; border: 0; background: #f6f6f67a; padding: 1em; border: 2px dashed #ccc; border-radius: 8px; }
            #available-filters.sortable-list li { width: fit-content; height: fit-content; padding: 10px 15px;}
            #available-filters .filter-type-options { display: none; }
            .filter-type-options { float: right; margin-left: 10px; font-weight: normal; color: #777; }
            #left-filters .filter-type-options { float: none; clear: both; display: block; margin-top: 10px; }
            .disabled-filter {color: #616161; pointer-events: none; opacity: 0.6; position: relative;}
            li.disabled-filter::after { content: "kommer"; display: block; position: absolute; right: -4px; bottom: -11px;background: #fff; color: #4d4d4d; font-size: 10px; font-weight: normal; padding: 0 4px; border-radius: 3px; border: 1px solid #eee;}
            
        </style>


    <?php
    kursagenten_admin_footer();
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

    public function plassholderbilde_sted_callback() {
        $image_url = isset($this->kag_kursinnst_options['ka_plassholderbilde_sted']) ? $this->kag_kursinnst_options['ka_plassholderbilde_sted'] : '';
        ?>
        <div class="image-upload-wrapper">
            <img id="ka_plassholderbilde_sted_preview" src="<?php echo esc_url($image_url); ?>" style="max-width: 150px; <?php echo $image_url ? '' : 'display: none;'; ?>" />
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

        // Legg til registrering for systemsider
        register_setting('kag_kursinnst_option_group', 'ka_page_kurskategorier');
        register_setting('kag_kursinnst_option_group', 'ka_page_kurssteder');
        register_setting('kag_kursinnst_option_group', 'ka_page_instruktorer');

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

    public static function get_required_pages() {
        if (self::$required_pages === null) {
            self::$required_pages = [
                'kurskategorier' => [
                    'title' => 'Kurskategorier',
                    'content' => '  <!-- wp:shortcode -->
                                    [kurskategorier kilde=ikon layout=stablet grid=5 gridtablet=3 gridmobil=1 radavstand=2em bildestr=130px bildeformat=4/3 fontmin="14" fontmaks="18"]
                                    <!-- /wp:shortcode -->',
                    'description' => 'Oversiktsside for alle kurskategorier',
                    'slug' => 'kurskategorier'
                ],
                'kurssteder' => [
                    'title' => 'Kurssteder',
                    'content' => '  <!-- wp:shortcode -->
                                    [kurssteder layout=rad stil=kort grid=3 gridtablet=2 gridmobil=1 radavstand=2em bildestr=100px bildeformat=1/1 fontmin="14" fontmaks="18"]
                                    <!-- /wp:shortcode -->',
                    'description' => 'Oversiktsside for alle kurssteder',
                    'slug' => 'kurssteder'
                ],
                'instruktorer' => [
                    'title' => 'Instruktører',
                    'content' => '  <!-- wp:shortcode -->
                                    [instruktorer kilde=ikon layout=rad stil=kort grid=2 gridtablet=1 gridmobil=1 radavstand=2em bildestr=250px bildeformat=1/1 fontmin="14" fontmaks="18" utdrag=ja]
                                    <!-- /wp:shortcode -->',
                    'description' => 'Oversiktsside for alle instruktører',
                    'slug' => 'instruktorer'
                ]
            ];
        }
        return self::$required_pages;
    }

    public function render_system_pages_section() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $required_pages = self::get_required_pages();
        ?>
        <div class="ka-pages-manager options-card">
            <table class="widefat light-grey-rows" style="border: 0;">
                <thead>
                    <tr>
                        <th scope="col">Side</th>
                        <th scope="col">Beskrivelse</th>
                        <th scope="col">Status</th>
                        <th scope="col">Handlinger</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($required_pages as $key => $page): 
                        $page_id = get_option('ka_page_' . $key);
                        $exists = $page_id && get_post($page_id);
                        $post_status = $exists ? get_post_status($page_id) : '';
                        ?>
                        <tr>
                            <td><?php echo esc_html($page['title']); ?></td>
                            <td><?php echo esc_html($page['description']); ?></td>
                            <td>
                                <?php if ($exists): ?>
                                    <span class="status-indicator <?php echo $post_status; ?>">
                                        <?php 
                                        switch ($post_status) {
                                            case 'publish':
                                                echo '✓ Publisert';
                                                break;
                                            case 'draft':
                                                echo '⚠ Kladd';
                                                break;
                                            default:
                                                echo '? ' . ucfirst($post_status);
                                        }
                                        ?>
                                    </span>
                                    <div class="page-links">
                                        <a href="<?php echo get_edit_post_link($page_id); ?>" target="_blank">Rediger</a>
                                        |
                                        <a href="<?php echo get_permalink($page_id); ?>" target="_blank">Vis</a>
                                    </div>
                                <?php else: ?>
                                    <span class="status-indicator not-created">✗ Ikke opprettet</span>
                                <?php endif; ?>
                            </td>
                            <td class="actions">
                                <div class="system-page-actions">
                                    <?php if (!$exists): ?>
                                        <button type="button" class="button button-primary ka-system-page-action" 
                                                data-action="create" 
                                                data-key="<?php echo esc_attr($key); ?>"
                                                data-nonce="<?php echo wp_create_nonce('ka_manage_pages'); ?>">
                                            Opprett side
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="button button-secondary ka-system-page-action"
                                                data-action="reset"
                                                data-key="<?php echo esc_attr($key); ?>"
                                                data-nonce="<?php echo wp_create_nonce('ka_manage_pages'); ?>">
                                            Tilbakestill innhold
                                        </button>
                                        <button type="button" class="button button-link-delete ka-system-page-action"
                                                data-action="delete"
                                                data-key="<?php echo esc_attr($key); ?>"
                                                data-nonce="<?php echo wp_create_nonce('ka_manage_pages'); ?>">
                                            Slett
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <style>
            .ka-pages-manager { margin-top: 20px; }
            .ka-pages-manager td { vertical-align: middle; }
            .status-indicator { display: inline-block; padding: 3px 8px; border-radius: 3px; }
            .status-indicator.publish { background: #e8f5e9; color: #2e7d32; }
            .status-indicator.draft { background: #fff3e0; color: #ef6c00; }
            .status-indicator.not-created { background: #ffebee; color: #c62828; }
            .page-links { margin-top: 5px; font-size: 0.9em; }
            .actions form { display: flex; gap: 5px; }
        </style>
        <script>
        jQuery(document).ready(function($) {
            $('.ka-system-page-action').on('click', function(e) {
                e.preventDefault();
                var button = $(this);
                
                // Sjekk om dette er en slette-handling
                if (button.data('action') === 'delete') {
                    if (!confirm('Er du sikker på at du vil slette denne siden?')) {
                        return false; // Stopp hvis brukeren klikker Avbryt
                    }
                }

                var data = {
                    action: 'ka_manage_system_pages',
                    ka_page_key: button.data('key'),
                    ka_page_action: button.data('action'),
                    _wpnonce: button.data('nonce')
                };

                $.post(ajaxurl, data, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Det oppstod en feil. Vennligst prøv igjen.');
                    }
                });
            });
        });
        </script>
        <?php
    }

    public function handle_system_pages_actions() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Ingen tillatelse');
            return;
        }

        // Sjekk om dette er en AJAX-forespørsel
        $is_ajax = defined('DOING_AJAX') && DOING_AJAX;

        if (!isset($_POST['ka_page_key'])) {
            if ($is_ajax) {
                wp_send_json_error('Mangler page_key');
            }
            wp_die('Ugyldig forespørsel');
        }

        // Verifiser nonce
        $nonce = isset($_POST['_wpnonce']) ? $_POST['_wpnonce'] : '';
        if (!wp_verify_nonce($nonce, 'ka_manage_pages')) {
            if ($is_ajax) {
                wp_send_json_error('Ugyldig sikkerhetskode');
            }
            wp_die('Ugyldig sikkerhetskode');
        }

        $page_key = sanitize_key($_POST['ka_page_key']);
        $action = isset($_POST['ka_page_action']) ? sanitize_key($_POST['ka_page_action']) : '';
        $result = false;

        switch ($action) {
            case 'create':
                $result = self::create_system_page($page_key);
                break;
            case 'delete':
                $result = self::delete_system_page($page_key);
                break;
            case 'reset':
                $result = self::reset_system_page($page_key);
                break;
        }

        if ($is_ajax) {
            if ($result) {
                wp_send_json_success(['message' => 'Handling fullført']);
            } else {
                wp_send_json_error(['message' => 'Kunne ikke utføre handlingen']);
            }
        } else {
            wp_redirect(add_query_arg('settings-updated', 'true', wp_get_referer()));
            exit;
        }
    }

    private static function add_admin_notice($message, $type = 'success') {
        add_action('admin_notices', function() use ($message, $type) {
            printf(
                '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
                esc_attr($type),
                esc_html($message)
            );
        });
    }

    public static function create_system_page($page_key) {
        $pages = self::get_required_pages();
        if (!isset($pages[$page_key])) {
            return false;
        }
        
        $page_data = $pages[$page_key];
        $page_id = wp_insert_post([
            'post_title' => $page_data['title'],
            'post_content' => $page_data['content'],
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_name' => $page_data['slug'],
            'comment_status' => 'closed'
        ]);
        
        if ($page_id) {
            update_option('ka_page_' . $page_key, $page_id);
            update_post_meta($page_id, '_ka_system_page', $page_key);
            self::add_admin_notice('Systemside ble opprettet.');
        }
        
        return $page_id;
    }

    public static function delete_system_page($page_key) {
        $page_id = get_option('ka_page_' . $page_key);
        if ($page_id) {
            wp_delete_post($page_id, true);
            delete_option('ka_page_' . $page_key);
            self::add_admin_notice('Systemside ble slettet.');
            return true;
        }
        return false;
    }

    public static function reset_system_page($page_key) {
        $pages = self::get_required_pages();
        $page_id = get_option('ka_page_' . $page_key);
        
        if ($page_id && isset($pages[$page_key])) {
            wp_update_post([
                'ID' => $page_id,
                'post_content' => $pages[$page_key]['content']
            ]);
            
            self::add_admin_notice('Sideinnhold ble tilbakestilt.');
            return true;
        }
        return false;
    }
}

?>

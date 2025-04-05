<?php
class Designmaler {
    private $design_options;

    public function __construct() {
        add_action('admin_menu', array($this, 'design_add_plugin_page'));
        add_action('admin_init', array($this, 'design_page_init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_head', array($this, 'add_custom_css'), 999);
        
        // Legg til action for å håndtere systemside-operasjoner
        add_action('admin_post_ka_manage_system_pages', array($this, 'handle_system_pages_actions'));
        // Legg til AJAX action
        add_action('wp_ajax_ka_manage_system_pages', array($this, 'handle_system_pages_actions'));
    }

    public function design_add_plugin_page() {
        add_submenu_page(
            'kursagenten',
            'Kursdesign',
            'Kursdesign',
            'manage_options',
            'design',
            array($this, 'design_create_admin_page')
        );
    }

    public function design_create_admin_page() {
        $this->design_options = get_option('design_option_name');
        
        ?>

        <div class="wrap options-form ka-wrap" id="toppen">
            <form method="post" action="options.php">
                <?php 
                settings_fields('design_option_group');
                do_settings_sections('design-admin');
                ?>
                <?php kursagenten_sticky_admin_menu(); ?>
                <h2>Kursdesign</h2>
                <!-- System-sider -->
                <div class="design-section">
                    <h3>System-sider</h3>
                    <p>Her kan du administrere de automatisk genererte sidene for kurskategorier, kurssteder og instruktører.</p>
                    <?php $this->render_system_pages_section(); ?>
                </div>

                <!-- Single kurs -->
                
                <div class="design-section">
                    <h3>Enkeltkurs</h3>
                    
                    <!-- Layoutbredde -->
                    <div class="option-row">
                        <label class="option-label">Bredde:</label>
                        <div class="option-input">
                            <label class="radio-label">
                                <input type="radio" 
                                       name="kursagenten_single_layout" 
                                       value="default" 
                                       <?php checked(get_option('kursagenten_single_layout'), 'default'); ?>>
                                Tema-standard
                            </label>
                            <label class="radio-label">
                                <input type="radio" 
                                       name="kursagenten_single_layout" 
                                       value="full-width" 
                                       <?php checked(get_option('kursagenten_single_layout'), 'full-width'); ?>>
                                Full bredde
                            </label>
                        </div>
                    </div>

                    <!-- Design -->
                    <div class="option-row">
                        <label class="option-label">Design:</label>
                        <div class="option-input">
                            <select name="kursagenten_single_design">
                                <?php
                                $current_design = get_option('kursagenten_single_design', 'default');
                                $designs = [
                                    'default' => 'Standard',
                                    'modern' => 'Moderne',
                                    'minimal' => 'Minimal'
                                ];
                                foreach ($designs as $value => $label) {
                                    printf(
                                        '<option value="%s" %s>%s</option>',
                                        esc_attr($value),
                                        selected($current_design, $value, false),
                                        esc_html($label)
                                    );
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Arkiv/Kurslister -->
                <div class="design-section">
                    <h3>Kurslister</h3>
                    
                    <!-- Layoutbredde -->
                    <div class="option-row">
                        <label class="option-label">Bredde:</label>
                        <div class="option-input">
                            <label class="radio-label">
                                <input type="radio" 
                                       name="kursagenten_archive_layout" 
                                       value="default" 
                                       <?php checked(get_option('kursagenten_archive_layout'), 'default'); ?>>
                                Tema-standard
                            </label>
                            <label class="radio-label">
                                <input type="radio" 
                                       name="kursagenten_archive_layout" 
                                       value="full-width" 
                                       <?php checked(get_option('kursagenten_archive_layout'), 'full-width'); ?>>
                                Full bredde
                            </label>
                        </div>
                    </div>

                    <!-- Design -->
                    <div class="option-row">
                        <label class="option-label">Design:</label>
                        <div class="option-input">
                            <select name="kursagenten_archive_design">
                                <?php
                                $current_design = get_option('kursagenten_archive_design', 'default');
                                $designs = [
                                    'default' => 'Standard',
                                    'modern' => 'Moderne',
                                    'minimal' => 'Minimal'
                                ];
                                foreach ($designs as $value => $label) {
                                    printf(
                                        '<option value="%s" %s>%s</option>',
                                        esc_attr($value),
                                        selected($current_design, $value, false),
                                        esc_html($label)
                                    );
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <!-- Listevisning -->
                    <div class="option-row">
                        <label class="option-label">Listevisning:</label>
                        <div class="option-input">
                            <select name="kursagenten_archive_list_type">
                                <?php
                                $current_list = get_option('kursagenten_archive_list_type', 'standard');
                                $list_types = [
                                    'standard' => 'Standard liste',
                                    'grid' => 'Rutenett',
                                    'compact' => 'Kompakt liste'
                                ];
                                foreach ($list_types as $value => $label) {
                                    printf(
                                        '<option value="%s" %s>%s</option>',
                                        esc_attr($value),
                                        selected($current_list, $value, false),
                                        esc_html($label)
                                    );
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Vis bilder -->
                    <div class="option-row">
                        <label class="option-label">Vis bilder:</label>
                        <div class="option-input">
                            <?php
                            $show_images = get_option('kursagenten_show_images', 'yes');
                            ?>
                            <label class="radio-label">
                                <input type="radio" name="kursagenten_show_images" value="yes" <?php checked($show_images, 'yes'); ?>>
                                Ja
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="kursagenten_show_images" value="no" <?php checked($show_images, 'no'); ?>>
                                Nei
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Taxonomi -->
                <div class="design-section">
                    <h3>Taksonomi-sider</h3>
                    
                    <!-- Layoutbredde -->
                    <div class="option-row">
                        <label class="option-label">Bredde:</label>
                        <div class="option-input">
                            <label class="radio-label">
                                <input type="radio" 
                                       name="kursagenten_taxonomy_layout" 
                                       value="default" 
                                       <?php checked(get_option('kursagenten_taxonomy_layout'), 'default'); ?>>
                                Tema-standard
                            </label>
                            <label class="radio-label">
                                <input type="radio" 
                                       name="kursagenten_taxonomy_layout" 
                                       value="full-width" 
                                       <?php checked(get_option('kursagenten_taxonomy_layout'), 'full-width'); ?>>
                                Full bredde
                            </label>
                        </div>
                    </div>

                    <!-- Design -->
                    <div class="option-row">
                        <label class="option-label">Design:</label>
                        <div class="option-input">
                            <select name="kursagenten_taxonomy_design">
                                <?php
                                $current_design = get_option('kursagenten_taxonomy_design', 'default');
                                $designs = [
                                    'default' => 'Standard',
                                    'modern' => 'Moderne',
                                    'minimal' => 'Minimal'
                                ];
                                foreach ($designs as $value => $label) {
                                    printf(
                                        '<option value="%s" %s>%s</option>',
                                        esc_attr($value),
                                        selected($current_design, $value, false),
                                        esc_html($label)
                                    );
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <!-- Listevisning -->
                    <div class="option-row">
                        <label class="option-label">Listevisning:</label>
                        <div class="option-input">
                            <select name="kursagenten_taxonomy_list_type">
                                <?php
                                $current_list = get_option('kursagenten_taxonomy_list_type', 'standard');
                                $list_types = [
                                    'standard' => 'Standard liste',
                                    'grid' => 'Rutenett',
                                    'compact' => 'Kompakt liste'
                                ];
                                foreach ($list_types as $value => $label) {
                                    printf(
                                        '<option value="%s" %s>%s</option>',
                                        esc_attr($value),
                                        selected($current_list, $value, false),
                                        esc_html($label)
                                    );
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Vis bilder -->
                    <div class="option-row">
                        <label class="option-label">Vis bilder:</label>
                        <div class="option-input">
                            <?php
                            $show_images_taxonomy = get_option('kursagenten_show_images_taxonomy', 'yes');
                            ?>
                            <label class="radio-label">
                                <input type="radio" name="kursagenten_show_images_taxonomy" value="yes" <?php checked($show_images_taxonomy, 'yes'); ?>>
                                Ja
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="kursagenten_show_images_taxonomy" value="no" <?php checked($show_images_taxonomy, 'no'); ?>>
                                Nei
                            </label>
                        </div>
                    </div>

                    <!-- Spesifikke innstillinger per taksonomi -->
                    <div class="taxonomy-specific-settings">
                        <h4>Overstyr innstillinger for spesifikke taksonomier</h4>
                        <?php
                        $taxonomies = [
                            'coursecategory' => 'Kurskategorier',
                            'course_location' => 'Kurssteder',
                            'instructors' => 'Instruktører'
                        ];
                        
                        foreach ($taxonomies as $tax_name => $tax_label) :
                            $override_enabled = get_option("kursagenten_taxonomy_{$tax_name}_override", false);
                            ?>
                            <div class="taxonomy-override">
                                <label class="checkbox-label">
                                    <input type="checkbox" 
                                           name="kursagenten_taxonomy_<?php echo esc_attr($tax_name); ?>_override" 
                                           value="1" 
                                           <?php checked($override_enabled, true); ?>>
                                    Egne innstillinger for <?php echo esc_html($tax_label); ?>
                                </label>
                                
                                <div class="taxonomy-override-settings" <?php if (!$override_enabled) echo 'style="display: none;"'; ?>>
                                    <!-- Layout -->
                                    <div class="option-row">
                                        <label class="option-label">Bredde:</label>
                                        <div class="option-input">
                                            <select name="kursagenten_taxonomy_<?php echo esc_attr($tax_name); ?>_layout">
                                                <option value="">Bruk standard innstilling</option>
                                                <option value="default" <?php selected(get_option("kursagenten_taxonomy_{$tax_name}_layout"), 'default'); ?>>Tema-standard</option>
                                                <option value="full-width" <?php selected(get_option("kursagenten_taxonomy_{$tax_name}_layout"), 'full-width'); ?>>Full bredde</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <!-- Design -->
                                    <div class="option-row">
                                        <label class="option-label">Design:</label>
                                        <div class="option-input">
                                            <select name="kursagenten_taxonomy_<?php echo esc_attr($tax_name); ?>_design">
                                                <option value="">Bruk standard innstilling</option>
                                                <?php foreach ($designs as $value => $label) : ?>
                                                    <option value="<?php echo esc_attr($value); ?>" 
                                                            <?php selected(get_option("kursagenten_taxonomy_{$tax_name}_design"), $value); ?>>
                                                        <?php echo esc_html($label); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <!-- List type -->
                                    <div class="option-row">
                                        <label class="option-label">Listevisning:</label>
                                        <div class="option-input">
                                            <select name="kursagenten_taxonomy_<?php echo esc_attr($tax_name); ?>_list_type">
                                                <option value="">Bruk standard innstilling</option>
                                                <?php foreach ($list_types as $value => $label) : ?>
                                                    <option value="<?php echo esc_attr($value); ?>" 
                                                            <?php selected(get_option("kursagenten_taxonomy_{$tax_name}_list_type"), $value); ?>>
                                                        <?php echo esc_html($label); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <!-- Vis bilder -->
                                    <div class="option-row">
                                        <label class="option-label">Vis bilder:</label>
                                        <div class="option-input">
                                            <?php
                                            $show_images_taxonomy_specific = get_option("kursagenten_taxonomy_{$tax_name}_show_images", '');
                                            ?>
                                            <select name="kursagenten_taxonomy_<?php echo esc_attr($tax_name); ?>_show_images">
                                                <option value="">Bruk standard innstilling</option>
                                                <option value="yes" <?php selected($show_images_taxonomy_specific, 'yes'); ?>>Ja</option>
                                                <option value="no" <?php selected($show_images_taxonomy_specific, 'no'); ?>>Nei</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="design-setion">
                    <h3>Egen CSS</h3>
                    <p>Her kan du legge til egendefinert CSS som vil bli lastet inn på alle sider som hører til utvidelsen. Denne CSS-en vil ha høyest prioritet og vil overstyre utvidelsens standard CSS.</p>
                    
                    <div class="options-card">
                        <textarea name="kursagenten_custom_css" id="kursagenten_custom_css" rows="10" style="width: 100%; font-family: monospace;"><?php echo esc_textarea(get_option('kursagenten_custom_css', '')); ?></textarea>
                        
                        <p class="description">
                            <strong>Nyttige selectorer:</strong><br>
                            <code class="copytext" title="Kopier til utklippstavle">#ka</code> - Den ytterste wrapperen på alle frontend-sider som hører til utvidelsen<br>
                            <code class="copytext" title="Kopier til utklippstavle">#ka .ka-single</code> - Enkeltkurs-sider<br>
                            <code class="copytext" title="Kopier til utklippstavle">#ka .ka-archive</code> - Kurslister<br>
                            <code class="copytext" title="Kopier til utklippstavle">#ka .ka-taxonomy</code> - Taksonomi-sider<br>
                            <code class="copytext" title="Kopier til utklippstavle">#ka .ka-course-card</code> - Kurskort i lister<br>
                            <code class="copytext" title="Kopier til utklippstavle">#ka .ka-button</code> - Standard knapper<br>
                            <code class="copytext" title="Kopier til utklippstavle">#ka .ka-section</code> - Seksjoner i kursinnhold
                        </p>
                    </div>
                </div>

                <style>
                    .design-section {
                        background: #fff;
                        padding: 20px;
                        margin: 20px 0;
                        border: 1px solid #ccd0d4;
                        border-radius: 4px;
                    }
                    .option-row {
                        display: grid;
                        grid-template-columns: 200px 1fr;
                        gap: 20px;
                        margin-bottom: 20px;
                        align-items: center;
                    }
                    .option-label {
                        font-weight: 500;
                    }
                    .radio-label {
                        margin-right: 20px;
                    }
                    select {
                        min-width: 200px;
                    }
                    .taxonomy-override {
                        margin: 1em 0;
                        padding: 1em;
                        background: #f8f8f8;
                        border-radius: 4px;
                    }
                    .taxonomy-override-settings {
                        margin-top: 1em;
                        background: #fff;
                        border: 1px solid #ddd;
                        border-radius: 4px;
                        padding: 1em;
                    }
                    .taxonomy-override .option-row {
                        margin-bottom: 15px;
                    }
                    .checkbox-label {
                        font-weight: 600;
                        margin-bottom: 1em;
                        display: block;
                    }
                </style>

                <!-- Egen CSS -->


                <?php submit_button(); ?>
            
        
        <?php
        kursagenten_admin_footer();
        
    }

    public function design_page_init() {
        // Behold eksisterende basis-registrering
        register_setting(
            'design_option_group',
            'design_option_name',
            array($this, 'design_sanitize')
        );

        // Registrer egen CSS innstilling
        register_setting(
            'design_option_group',
            'kursagenten_custom_css',
            array(
                'type' => 'string',
                'sanitize_callback' => array($this, 'sanitize_css'),
                'default' => ''
            )
        );

        // Definer alle innstillingstyper
        $base_settings = [
            'single' => ['layout', 'design'],
            'archive' => ['layout', 'design', 'list_type'],
            'taxonomy' => ['layout', 'design', 'list_type']
        ];

        // Registrer hovedinnstillinger
        foreach ($base_settings as $type => $settings) {
            foreach ($settings as $setting) {
                register_setting(
                    'design_option_group',
                    "kursagenten_{$type}_{$setting}",
                    array(
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                        'default' => 'default'
                    )
                );
            }
        }
        
        // Registrer innstilling for bildevisning
        register_setting(
            'design_option_group',
            'kursagenten_show_images',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'yes'
            )
        );
        
        // Registrer innstilling for bildevisning på taksonomi-sider
        register_setting(
            'design_option_group',
            'kursagenten_show_images_taxonomy',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'yes'
            )
        );

        // Registrer taksonomi-spesifikke innstillinger
        $taxonomies = ['coursecategory' => 'Kurskategorier', 
                      'course_location' => 'Kurssteder', 
                      'instructors' => 'Instruktører'];

        foreach ($taxonomies as $tax_name => $tax_label) {
            // Registrer override-innstilling
            register_setting(
                'design_option_group',
                "kursagenten_taxonomy_{$tax_name}_override",
                array(
                    'type' => 'boolean',
                    'sanitize_callback' => 'rest_sanitize_boolean',
                    'default' => false
                )
            );

            // Registrer spesifikke innstillinger for hver taksonomi
            foreach (['layout', 'design', 'list_type', 'show_images'] as $setting) {
                register_setting(
                    'design_option_group',
                    "kursagenten_taxonomy_{$tax_name}_{$setting}",
                    array(
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                        'default' => ''
                    )
                );
            }
        }
    }

    public function design_sanitize($input) {
        $sanitary_values = array();
        
        // Sanitize all possible input fields
        $valid_keys = [
            'template_style',
            'taxonomy_style',
            'single_layout',
            'single_design',
            'archive_layout',
            'archive_design',
            'archive_list_type',
            'taxonomy_layout',
            'taxonomy_design',
            'taxonomy_list_type',
            'show_images',
            'show_images_taxonomy',
            'custom_css'
        ];

        foreach ($valid_keys as $key) {
            if (isset($input[$key])) {
                $sanitary_values[$key] = sanitize_text_field($input[$key]);
            }
        }

        // Sanitize taxonomy-specific settings
        $taxonomies = ['coursecategory', 'course_location', 'instructors'];
        foreach ($taxonomies as $taxonomy) {
            $tax_keys = [
                "taxonomy_{$taxonomy}_override",
                "taxonomy_{$taxonomy}_layout",
                "taxonomy_{$taxonomy}_design",
                "taxonomy_{$taxonomy}_list_type",
                "taxonomy_{$taxonomy}_show_images"
            ];

            foreach ($tax_keys as $key) {
                if (isset($input[$key])) {
                    if (strpos($key, '_override') !== false) {
                        $sanitary_values[$key] = rest_sanitize_boolean($input[$key]);
                    } else {
                        $sanitary_values[$key] = sanitize_text_field($input[$key]);
                    }
                }
            }
        }
        
        return $sanitary_values;
    }

    /**
     * Sanitize CSS input
     * 
     * @param string $css The CSS to sanitize
     * @return string The sanitized CSS
     */
    public function sanitize_css($css) {
        // Allow CSS properties and values, but strip potentially dangerous content
        // This is a basic sanitization - consider using a more robust solution for production
        $css = wp_strip_all_tags($css);
        $css = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $css);
        $css = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $css);
        
        return $css;
    }

    public function enqueue_admin_scripts($hook) {
        if ('kursagenten_page_design' !== $hook) {
            return;
        }

        wp_enqueue_script('jquery');
        wp_add_inline_script('jquery', '
            (function($) {
                $(document).ready(function() {
                    // Initialiser tilstanden for alle override-innstillinger ved lasting
                    $("input[name^=\'kursagenten_taxonomy_\'][name$=\'_override\']").each(function() {
                        var $settings = $(this).closest(".taxonomy-override").find(".taxonomy-override-settings");
                        if ($(this).is(":checked")) {
                            $settings.show();
                        } else {
                            $settings.hide();
                        }
                    });

                    // Håndter endringer i override-checkboksene
                    $("input[name^=\'kursagenten_taxonomy_\'][name$=\'_override\']").on("change", function() {
                        var $settings = $(this).closest(".taxonomy-override").find(".taxonomy-override-settings");
                        if ($(this).is(":checked")) {
                            $settings.slideDown(300);
                        } else {
                            $settings.slideUp(300);
                        }
                    });
                });
            })(jQuery);
        ');
    }

    /**
     * Legg til egendefinert CSS på frontend-sider
     */
    public function add_custom_css() {
        $custom_css = get_option('kursagenten_custom_css', '');
        
        if (!empty($custom_css)) {
            // Sjekk om vi er på en side som hører til utvidelsen
            $is_kursagenten_page = false;
            
            // Sjekk om vi er på en enkeltkurs-side
            if (is_singular('course')) {
                $is_kursagenten_page = true;
            }
            
            // Sjekk om vi er på en kursarkiv-side
            if (is_post_type_archive('course')) {
                $is_kursagenten_page = true;
            }
            
            // Sjekk om vi er på en taksonomi-side
            if (is_tax('coursecategory') || is_tax('course_location') || is_tax('instructors')) {
                $is_kursagenten_page = true;
            }
            
            // Hvis vi er på en side som hører til utvidelsen, legg til CSS-en
            if ($is_kursagenten_page) {
                echo '<!-- Kursagenten Custom CSS -->' . "\n";
                echo '<style type="text/css" id="kursagenten-custom-css">' . "\n";
                echo $custom_css . "\n";
                echo '</style>' . "\n";
            }
        }
    }

    private static $required_pages = null;

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

<?php
class Designmaler {
    private $design_options;

    public function __construct() {
        add_action('admin_menu', array($this, 'design_add_plugin_page'));
        add_action('admin_init', array($this, 'design_page_init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
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
        
        // Start page with header
        kursagenten_admin_header('Design på kurslister og kurssider');
        ?>

        <div class="wrap">
            <form method="post" action="options.php">
                <?php 
                settings_fields('design_option_group');
                do_settings_sections('design-admin');
                ?>

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
                                </div>
                            </div>
                        <?php endforeach; ?>
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

                <?php submit_button(); ?>
            </form>
        </div>
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
            foreach (['layout', 'design', 'list_type'] as $setting) {
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
            'taxonomy_list_type'
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
                "taxonomy_{$taxonomy}_list_type"
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
}

?>

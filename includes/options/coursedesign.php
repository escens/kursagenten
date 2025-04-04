<?php
class Designmaler {
    private $design_options;

    public function __construct() {
        add_action('admin_menu', array($this, 'design_add_plugin_page'));
        add_action('admin_init', array($this, 'design_page_init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_head', array($this, 'add_custom_css'), 999);
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
                <!-- Single kurs -->
                 <h2>Kursdesign</h2>
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

                    <!-- Kolonner -->
                    <div class="option-row">
                        <label class="option-label">Antall kolonner:</label>
                        <div class="option-input">
                            <div class="column-settings">
                                <div class="column-setting">
                                    <label>Desktop:</label>
                                    <select name="kursagenten_archive_columns_desktop">
                                        <?php
                                        $current_columns = get_option('kursagenten_archive_columns_desktop', '3');
                                        for ($i = 1; $i <= 4; $i++) {
                                            printf(
                                                '<option value="%d" %s>%d</option>',
                                                $i,
                                                selected($current_columns, $i, false),
                                                $i
                                            );
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="column-setting">
                                    <label>Tablet:</label>
                                    <select name="kursagenten_archive_columns_tablet">
                                        <?php
                                        $current_columns = get_option('kursagenten_archive_columns_tablet', '2');
                                        for ($i = 1; $i <= 3; $i++) {
                                            printf(
                                                '<option value="%d" %s>%d</option>',
                                                $i,
                                                selected($current_columns, $i, false),
                                                $i
                                            );
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="column-setting">
                                    <label>Mobil:</label>
                                    <select name="kursagenten_archive_columns_mobile">
                                        <?php
                                        $current_columns = get_option('kursagenten_archive_columns_mobile', '1');
                                        for ($i = 1; $i <= 2; $i++) {
                                            printf(
                                                '<option value="%d" %s>%d</option>',
                                                $i,
                                                selected($current_columns, $i, false),
                                                $i
                                            );
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Antall kurs -->
                    <div class="option-row">
                        <label class="option-label">Antall kurs per side:</label>
                        <div class="option-input">
                            <input type="number" 
                                   name="kursagenten_archive_posts_per_page" 
                                   value="<?php echo esc_attr(get_option('kursagenten_archive_posts_per_page', '12')); ?>" 
                                   min="1" 
                                   max="100">
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

                                    <!-- Kolonner -->
                                    <div class="option-row">
                                        <label class="option-label">Antall kolonner:</label>
                                        <div class="option-input">
                                            <div class="column-settings">
                                                <div class="column-setting">
                                                    <label>Desktop:</label>
                                                    <select name="kursagenten_taxonomy_<?php echo esc_attr($tax_name); ?>_columns_desktop">
                                                        <option value="">Bruk standard innstilling</option>
                                                        <?php
                                                        $current_columns = get_option("kursagenten_taxonomy_{$tax_name}_columns_desktop", '');
                                                        for ($i = 1; $i <= 4; $i++) {
                                                            printf(
                                                                '<option value="%d" %s>%d</option>',
                                                                $i,
                                                                selected($current_columns, $i, false),
                                                                $i
                                                            );
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                <div class="column-setting">
                                                    <label>Tablet:</label>
                                                    <select name="kursagenten_taxonomy_<?php echo esc_attr($tax_name); ?>_columns_tablet">
                                                        <option value="">Bruk standard innstilling</option>
                                                        <?php
                                                        $current_columns = get_option("kursagenten_taxonomy_{$tax_name}_columns_tablet", '');
                                                        for ($i = 1; $i <= 3; $i++) {
                                                            printf(
                                                                '<option value="%d" %s>%d</option>',
                                                                $i,
                                                                selected($current_columns, $i, false),
                                                                $i
                                                            );
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                <div class="column-setting">
                                                    <label>Mobil:</label>
                                                    <select name="kursagenten_taxonomy_<?php echo esc_attr($tax_name); ?>_columns_mobile">
                                                        <option value="">Bruk standard innstilling</option>
                                                        <?php
                                                        $current_columns = get_option("kursagenten_taxonomy_{$tax_name}_columns_mobile", '');
                                                        for ($i = 1; $i <= 2; $i++) {
                                                            printf(
                                                                '<option value="%d" %s>%d</option>',
                                                                $i,
                                                                selected($current_columns, $i, false),
                                                                $i
                                                            );
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Antall kurs -->
                                    <div class="option-row">
                                        <label class="option-label">Antall kurs per side:</label>
                                        <div class="option-input">
                                            <input type="number" 
                                                   name="kursagenten_taxonomy_<?php echo esc_attr($tax_name); ?>_posts_per_page" 
                                                   value="<?php echo esc_attr(get_option("kursagenten_taxonomy_{$tax_name}_posts_per_page", '')); ?>" 
                                                   min="1" 
                                                   max="100">
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
                    .column-settings {
                        display: grid;
                        grid-template-columns: repeat(3, 1fr);
                        gap: 15px;
                    }
                    .column-setting {
                        display: flex;
                        flex-direction: column;
                        gap: 5px;
                    }
                    .column-setting label {
                        font-size: 0.9em;
                        color: #666;
                    }
                    .column-setting select {
                        width: 100%;
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

        // Registrer kolonneinnstillinger for arkiv
        register_setting(
            'design_option_group',
            'kursagenten_archive_columns_desktop',
            array(
                'type' => 'integer',
                'sanitize_callback' => 'absint',
                'default' => 3
            )
        );
        register_setting(
            'design_option_group',
            'kursagenten_archive_columns_tablet',
            array(
                'type' => 'integer',
                'sanitize_callback' => 'absint',
                'default' => 2
            )
        );
        register_setting(
            'design_option_group',
            'kursagenten_archive_columns_mobile',
            array(
                'type' => 'integer',
                'sanitize_callback' => 'absint',
                'default' => 1
            )
        );

        // Registrer antall kurs per side for arkiv
        register_setting(
            'design_option_group',
            'kursagenten_archive_posts_per_page',
            array(
                'type' => 'integer',
                'sanitize_callback' => 'absint',
                'default' => 12
            )
        );

        // Registrer taksonomi-spesifikke innstillinger
        $taxonomies = ['coursecategory' => 'Kurskategorier', 
                      'course_location' => 'Kurssteder', 
                      'instructors' => 'Instruktører'];

        foreach ($taxonomies as $tax_name => $tax_label) {
            // Registrer kolonneinnstillinger for taksonomi
            foreach (['desktop', 'tablet', 'mobile'] as $device) {
                register_setting(
                    'design_option_group',
                    "kursagenten_taxonomy_{$tax_name}_columns_{$device}",
                    array(
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                        'default' => ''
                    )
                );
            }

            // Registrer antall kurs per side for taksonomi
            register_setting(
                'design_option_group',
                "kursagenten_taxonomy_{$tax_name}_posts_per_page",
                array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'default' => ''
                )
            );
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
}

?>

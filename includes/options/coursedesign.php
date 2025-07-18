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
        
        // Legg til hooks for permalenk-håndtering
        add_action('init', array($this, 'register_instructor_rewrite_rules'), 10);
        add_action('update_option_kursagenten_taxonomy_instructors_name_display', array($this, 'update_instructor_permalinks'), 10, 2);
        add_filter('term_link', array($this, 'modify_instructor_term_link'), 10, 3);
        add_filter('request', array($this, 'handle_instructor_rewrite_request'));
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
                <div class="options-card">
                    <h3>System-sider</h3>
                    <p>Her kan du administrere de automatisk genererte sidene for kurskategorier, kurssteder og instruktører.</p>
                    <?php $this->render_system_pages_section(); ?>
                </div>

                <!-- Design Variabler -->
                <div class="options-card">
                    <h3>Design Variabler</h3>
                    <p>Her kan du tilpasse grunnleggende designvariabler for Kursagenten.</p>
                    
                    <!-- Maksbredde -->
                    <div class="option-row">
                        <label class="option-label">Maksbredde:</label>
                        <div class="option-input">
                            <input type="text" 
                                   name="kursagenten_max_width" 
                                   value="<?php echo esc_attr(get_option('kursagenten_max_width', '1300px')); ?>"
                                   placeholder="1300px">
                            <p class="description">Standard: 1300px</p>
                        </div>
                    </div>

                    <!-- Hovedfarge -->
                    <div class="option-row">
                        <label class="option-label">Hovedfarge:</label>
                        <div class="option-input">
                            <input type="text" 
                                   name="kursagenten_main_color" 
                                   value="<?php echo esc_attr(get_option('kursagenten_main_color', 'hsl(32, 96%, 49%)')); ?>"
                                   class="ka-color-picker"
                                   data-default-color="hsl(32, 96%, 49%)">
                        </div>
                    </div>

                    <!-- Finjuster farger toggle -->
                    <div class="option-row">
                        <label class="option-label">Finjuster farger:</label>
                        <div class="option-input">
                            <label class="checkbox-label">
                                <input type="checkbox" 
                                       name="kursagenten_advanced_colors" 
                                       value="1" 
                                       <?php checked(get_option('kursagenten_advanced_colors'), 1); ?>
                                       class="ka-advanced-colors-toggle">
                                Vis flere fargevalg
                            </label>
                        </div>
                    </div>

                    <!-- Avanserte fargevalg -->
                    <div class="advanced-colors-section" style="display: none;">
                        <!-- Knappefarge -->
                        <div class="option-row">
                            <label class="option-label">Knappefarge:</label>
                            <div class="option-input">
                                <input type="text" 
                                       name="kursagenten_button_background" 
                                       value="<?php echo esc_attr(get_option('kursagenten_button_background', '')); ?>"
                                       class="ka-color-picker"
                                       data-default-color="">
                            </div>
                        </div>

                        <!-- Knappefarge tekst -->
                        <div class="option-row">
                            <label class="option-label">Knappefarge tekst:</label>
                            <div class="option-input">
                                <input type="text" 
                                       name="kursagenten_button_color" 
                                       value="<?php echo esc_attr(get_option('kursagenten_button_color', '')); ?>"
                                       class="ka-color-picker"
                                       data-default-color="">
                            </div>
                        </div>

                        <!-- Linker -->
                        <div class="option-row">
                            <label class="option-label">Linker:</label>
                            <div class="option-input">
                                <input type="text" 
                                       name="kursagenten_link_color" 
                                       value="<?php echo esc_attr(get_option('kursagenten_link_color', '')); ?>"
                                       class="ka-color-picker"
                                       data-default-color="">
                            </div>
                        </div>

                        <!-- Ikoner -->
                        <div class="option-row">
                            <label class="option-label">Ikoner:</label>
                            <div class="option-input">
                                <input type="text" 
                                       name="kursagenten_icon_color" 
                                       value="<?php echo esc_attr(get_option('kursagenten_icon_color', '')); ?>"
                                       class="ka-color-picker"
                                       data-default-color="">
                            </div>
                        </div>

                        <!-- Sidebakgrunn -->
                        <div class="option-row">
                            <label class="option-label">Sidebakgrunn:</label>
                            <div class="option-input">
                                <input type="text" 
                                       name="kursagenten_background_color" 
                                       value="<?php echo esc_attr(get_option('kursagenten_background_color', '')); ?>"
                                       class="ka-color-picker"
                                       data-default-color="">
                            </div>
                        </div>

                        <!-- Bakgrunn fremhevede områder -->
                        <div class="option-row">
                            <label class="option-label">Bakgrunn fremhevede områder:</label>
                            <div class="option-input">
                                <input type="text" 
                                       name="kursagenten_highlight_background" 
                                       value="<?php echo esc_attr(get_option('kursagenten_highlight_background', '')); ?>"
                                       class="ka-color-picker"
                                       data-default-color="">
                            </div>
                        </div>
                    </div>

                    <!-- Base skriftstørrelse -->
                    <div class="option-row">
                        <label class="option-label">Base skriftstørrelse:</label>
                        <div class="option-input">
                            <input type="text" 
                                   name="kursagenten_base_font" 
                                   value="<?php echo esc_attr(get_option('kursagenten_base_font', '16px')); ?>"
                                   placeholder="16px">
                            <p class="description">Standard: 16px</p>
                        </div>
                    </div>

                    <!-- Hovedoverskrift font -->
                    <div class="option-row">
                        <label class="option-label">Font for hovedoverskrifter:</label>
                        <div class="option-input">
                            <input type="text" 
                                   name="kursagenten_heading_font" 
                                   value="<?php echo esc_attr(get_option('kursagenten_heading_font', 'inherit')); ?>"
                                   placeholder="inherit">
                            <p class="description">Standard: inherit</p>
                        </div>
                    </div>

                    <!-- Hovedfont -->
                    <div class="option-row">
                        <label class="option-label">Hovedfont:</label>
                        <div class="option-input">
                            <input type="text" 
                                   name="kursagenten_main_font" 
                                   value="<?php echo esc_attr(get_option('kursagenten_main_font', 'inherit')); ?>"
                                   placeholder="inherit">
                            <p class="description">Standard: inherit</p>
                        </div>
                    </div>
                </div>

                <!-- Single kurs -->
                
                <div class="options-card">
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
                <div class="options-card">
                    <h3>Kursliste med filter</h3>
                    
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

                    <!-- Antall kurs per side -->
                    <div class="option-row">
                        <label class="option-label">Antall kurs per side:</label>
                        <div class="option-input">
                            <input type="number" 
                                   name="kursagenten_courses_per_page" 
                                   value="<?php echo esc_attr(get_option('kursagenten_courses_per_page', 5)); ?>"
                                   min="1" 
                                   max="50">
                            <p class="description">Velg standard antall kurs som skal vises per side (1-50)</p>
                        </div>
                    </div>
                </div>

                <!-- Taxonomi -->
                <div class="options-card">
                    <h3>Taksonomi-sider</h3>
                    <p>Velg et felles design for kurskategorier, kurssteder og instruktører. Du kan også velge å ha egne design for hver enkelt taksonomi.</p>
                    <p>&nbsp;</p>
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
                                    'default-2' => 'Standard 2',
                                    'modern' => 'Moderne (kommer senere)'
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

                                    <?php if ($tax_name === 'instructors'): ?>
                                    <!-- Navnevisning -->
                                    <div class="option-row">
                                        <label class="option-label">Navnevisning:</label>
                                        <div class="option-input">
                                            <?php
                                            $name_display = get_option("kursagenten_taxonomy_{$tax_name}_name_display", '');
                                            ?>
                                            <select name="kursagenten_taxonomy_<?php echo esc_attr($tax_name); ?>_name_display">
                                                <option value="">Bruk standard innstilling</option>
                                                <option value="full" <?php selected($name_display, 'full'); ?>>Fullt navn</option>
                                                <option value="firstname" <?php selected($name_display, 'firstname'); ?>>Fornavn</option>
                                                <option value="lastname" <?php selected($name_display, 'lastname'); ?>>Etternavn</option>
                                            </select>
                                        </div>
                                    </div>
                                    <?php endif; ?>
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
        register_setting(
            'design_option_group',
            'design_option_name',
            array($this, 'design_sanitize')
        );

        // Registrer custom CSS innstilling
        register_setting(
            'design_option_group',
            'kursagenten_custom_css',
            array(
                'type' => 'string',
                'sanitize_callback' => array($this, 'sanitize_css'),
                'default' => ''
            )
        );

        // Registrer nye innstillinger
        register_setting('design_option_group', 'kursagenten_max_width');
        register_setting('design_option_group', 'kursagenten_main_color');
        register_setting('design_option_group', 'kursagenten_advanced_colors');
        register_setting('design_option_group', 'kursagenten_button_background');
        register_setting('design_option_group', 'kursagenten_button_color');
        register_setting('design_option_group', 'kursagenten_link_color');
        register_setting('design_option_group', 'kursagenten_icon_color');
        register_setting('design_option_group', 'kursagenten_background_color');
        register_setting('design_option_group', 'kursagenten_highlight_background');
        register_setting('design_option_group', 'kursagenten_base_font');
        register_setting('design_option_group', 'kursagenten_heading_font');
        register_setting('design_option_group', 'kursagenten_main_font');

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

        // Registrer innstilling for antall kurs per side
        register_setting(
            'design_option_group',
            'kursagenten_courses_per_page',
            array(
                'type' => 'integer',
                'sanitize_callback' => 'absint',
                'default' => 5
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

            // Registrer navnevisning for instruktører
            if ($tax_name === 'instructors') {
                register_setting(
                    'design_option_group',
                    "kursagenten_taxonomy_{$tax_name}_name_display",
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

        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        
        wp_enqueue_script(
            'ka-admin-script',
            plugins_url('/assets/js/admin-script.js', dirname(dirname(__FILE__))),
            array('jquery', 'wp-color-picker'),
            '1.0.0',
            true
        );

        wp_enqueue_style(
            'ka-admin-style',
            plugins_url('/assets/css/admin/admin-style.css', dirname(dirname(__FILE__))),
            array(),
            '1.0.0'
        );

        // Legg til inline JavaScript for fargevalg-toggle
        wp_add_inline_script('ka-admin-script', '
            jQuery(document).ready(function($) {
                // Håndter toggle av avanserte fargevalg
                function toggleAdvancedColors() {
                    var isChecked = $(".ka-advanced-colors-toggle").is(":checked");
                    $(".advanced-colors-section").toggle(isChecked);
                }

                // Initial toggle
                toggleAdvancedColors();

                // Bind toggle-funksjon til checkbox
                $(".ka-advanced-colors-toggle").on("change", toggleAdvancedColors);
            });
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
                'kurs' => [
                    'title' => 'Kurs',
                    'content' => '<!-- wp:shortcode -->
[kursliste]
<!-- /wp:shortcode -->',
                    'description' => 'Oversiktsside/kalender for alle kurs',
                    'slug' => 'kurs'
                ],
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
r                                    [kurssteder layout=rad stil=kort grid=3 gridtablet=2 gridmobil=1 radavstand=2em bildestr=100px bildeform=firkantet bildeformat=1/1 fontmin="14" fontmaks="18"]
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
        <div class="ka-pages-manager">
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

    /**
     * Hent URL til en systemside
     * 
     * @param string $page_key Nøkkelen for systemsiden (f.eks. 'kurs', 'kurskategorier')
     * @return string URL til siden, eller arkiv-URL hvis siden ikke finnes
     */
    public static function get_system_page_url($page_key) {
        $page_id = get_option('ka_page_' . $page_key);
        if ($page_id && get_post($page_id)) {
            return get_permalink($page_id);
        }
        
        // Fallback til arkiv-URL for kurs
        if ($page_key === 'kurs') {
            return get_post_type_archive_link('course');
        }
        
        return '';
    }

    /**
     * Registrer rewrite rules for instruktører
     */
    public function register_instructor_rewrite_rules() {
        // Hent URL-innstillinger
        $url_options = get_option('kag_seo_option_name');
        $instructor_slug = !empty($url_options['ka_url_rewrite_instruktor']) ? $url_options['ka_url_rewrite_instruktor'] : 'instruktorer';
        
        $name_display = get_option('kursagenten_taxonomy_instructors_name_display', '');
        if ($name_display === 'firstname' || $name_display === 'lastname') {
            add_rewrite_rule(
                $instructor_slug . '/([^/]+)/?$',
                'index.php?instructors=$matches[1]',
                'top'
            );
        }
    }

    /**
     * Modifiser term_link for instruktører
     */
    public function modify_instructor_term_link($termlink, $term, $taxonomy) {
        if ($taxonomy !== 'instructors') {
            return $termlink;
        }

        // Hent URL-innstillinger
        $url_options = get_option('kag_seo_option_name');
        $instructor_slug = !empty($url_options['ka_url_rewrite_instruktor']) ? $url_options['ka_url_rewrite_instruktor'] : 'instruktorer';

        // Hent navnevisningsinnstilling
        $name_display = get_option('kursagenten_taxonomy_instructors_name_display', '');
        
        // Hvis ingen spesifikk navnevisning er valgt, bruk standard term_link
        if (empty($name_display) || $name_display === 'full') {
            // Men fortsatt bruk riktig slug fra innstillingene
            $new_slug = $term->slug;
            return home_url('/' . $instructor_slug . '/' . $new_slug . '/');
        }

        // Hent ønsket navn basert på innstilling
        $display_name = '';
        switch ($name_display) {
            case 'firstname':
                $display_name = get_term_meta($term->term_id, 'instructor_firstname', true);
                break;
            case 'lastname':
                $display_name = get_term_meta($term->term_id, 'instructor_lastname', true);
                break;
        }

        // Hvis vi ikke fant et navn, bruk standard term_link med riktig slug
        if (empty($display_name)) {
            $new_slug = $term->slug;
            return home_url('/' . $instructor_slug . '/' . $new_slug . '/');
        }

        // Bygg ny URL med ønsket navn og riktig slug
        $new_slug = sanitize_title($display_name);
        return home_url('/' . $instructor_slug . '/' . $new_slug . '/');
    }

    /**
     * Håndter rewrite request for instruktører
     */
    public function handle_instructor_rewrite_request($query_vars) {
        if (isset($query_vars['instructors'])) {
            $requested_slug = $query_vars['instructors'];
            
            // Hent URL-innstillinger
            $url_options = get_option('kag_seo_option_name');
            $instructor_slug = !empty($url_options['ka_url_rewrite_instruktor']) ? $url_options['ka_url_rewrite_instruktor'] : 'instruktorer';
            
            // Finn instruktøren basert på fornavn eller etternavn
            $name_display = get_option('kursagenten_taxonomy_instructors_name_display', '');
            if ($name_display === 'firstname' || $name_display === 'lastname') {
                $meta_key = $name_display === 'firstname' ? 'instructor_firstname' : 'instructor_lastname';
                
                // Finn alle instruktører med dette navnet
                $terms = get_terms(array(
                    'taxonomy' => 'instructors',
                    'meta_key' => $meta_key,
                    'meta_value' => $requested_slug,
                    'hide_empty' => false
                ));

                if (!empty($terms)) {
                    // Bruk den første matchende instruktøren
                    $query_vars['instructors'] = $terms[0]->slug;
                }
            }
        }
        return $query_vars;
    }

    /**
     * Oppdater permalenker når navnevisningsinnstillingen endres
     */
    public function update_instructor_permalinks($old_value, $new_value) {
        if ($old_value !== $new_value) {
            // Flush permalenker
            flush_rewrite_rules();
            
            // Logg at permalenkene ble oppdatert
            error_log('Instructor permalinks updated due to name display setting change from ' . $old_value . ' to ' . $new_value);
        }
    }
}

?>

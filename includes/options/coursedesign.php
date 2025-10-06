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

        // Kontroller dokumenttittel (browser/SEO) for instruktør-taksonomi
        add_filter('document_title_parts', array($this, 'filter_document_title_parts'));
        // Yoast SEO støtte
        add_filter('wpseo_title', array($this, 'filter_wpseo_title'));
        add_filter('wpseo_opengraph_title', array($this, 'filter_wpseo_title'));

        // Rank Math SEO støtte
        add_filter('rank_math/frontend/title', array($this, 'filter_rank_math_title'));
        add_filter('rank_math/opengraph/facebook/title', array($this, 'filter_rank_math_title'));
        add_filter('rank_math/opengraph/twitter/title', array($this, 'filter_rank_math_title'));
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
                <div class="options-card" id="section-systemsider" data-section="systemsider">
                    <h3>Wordpress sider</h3>
                    <p>Generer sider for kurs, kurskategorier, kurssteder og instruktører. Sidene opprettes som vanlige WordPress-sider, og du kan endre tittel og innhold. En kortkode legges inn automatisk. Sidene er merket som Kursagenten-systemsider i sideoversikten.</p>
                    <?php $this->render_system_pages_section(); ?>
                </div>

                <!-- Design Variabler -->
                <div class="options-card" data-section="designvariabler">
                    <h3>Designvariabler</h3>
                    <p>Tilpass grunnleggende designvariabler som farger, skriftstørrelser, og maksbredde på plugin-sider.</p>
                    
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
                         <div style="color: #e72323; margin-bottom: 2em; font-style: italic;">* Merk: Denne delen er fortsatt under utvikling.</div>
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

                <!-- Arkiv/Kurslister -->
                <div class="options-card" data-section="kursliste">
                    <h3>Kursliste med filter</h3>
                    <p>Tilpass visningen av kurslisten. Denne vises på systemsiden "Kurs", og alle andre steder som bruker kortkoden <span class="copytext">[kursliste]</span>. Velg mellom standard liste og rutenett. Flere design kommer.</p>
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

                <!-- Filter Settings -->
                <div class="options-card" data-section="filterinnstillinger">
                    <h3 id="filterinnstillinger">Filterinnstillinger</h3>
                    <p>Velg hvilke filtre som skal vises på kurslisten. Du kan dra filteret til enten venstre kolonne eller over kursliste. Velg om filteret skal vises som tagger eller avkrysningsliste.</p>
                    <p>Ta tak i filteret du ønsker å bruke, og dra til enten venstre kolonne eller over kursliste. Velg om filteret skal vises som tagger eller avkrysningsliste.<br>
                    For å fjerne et filter, dra det tilbake til tilgjengelige filtre. Husk å <strong>lagre</strong> endringene dine.
                    </p>
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
                                                <?php if (!isset($filter_types[$filter]) || $filter_types[$filter] === 'list') : ?>
                                                    <label class="checkbox-label-small filter-list-options size-limit-checkbox">
                                                        <?php 
                                                        $no_collapse_settings = get_option('kursagenten_filter_no_collapse', array());
                                                        $is_checked = isset($no_collapse_settings[$filter]) && $no_collapse_settings[$filter];
                                                        ?>
                                                        <input type="checkbox" 
                                                               name="kursagenten_filter_no_collapse[<?php echo esc_attr($filter); ?>]" 
                                                               value="1" 
                                                               <?php checked($is_checked, true); ?>>
                                                        Ikke begrens høyde
                                                    </label>
                                                <?php endif; ?>
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
                                                        <?php echo (!isset($filter_types[$filter]) || $filter_types[$filter] === 'list') ? 'checked' : ''; ?>> Liste
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

                    <!-- Standard høyde for filterlister -->
                    <div class="list-height-container">
                        <h4>Standard høyde for filterlister:</h4>
                        <p>Listene med filtre i venstre kolonne kan bli lange. De begrenses til en standard høyde, med "Vis mer"-link. Standard høyde er 250px. Du kan velge en annen høyde for filterlister under.</p>
                        
                        <div style="display: inline-block;">Høyde: </div>
                        <div class="option-input" style="display: inline-block;">
                            <input type="number" 
                                   name="kursagenten_filter_default_height" 
                                   value="<?php echo esc_attr(get_option('kursagenten_filter_default_height', 250)); ?>"
                                   min="100" 
                                   max="1000"
                                   step="10">
                                
                            
                        </div>
                        <div class="description" style="display: inline-block;"> (100-1000px)</div>
                    </div>
                </div>

                <!-- Single kurs -->
                
                <div class="options-card" data-section="enkeltkurs">
                    <h3>Enkeltkurs</h3>
                    <p>Velg design på sider som viser kursdetaljer, både for alle lokasjoner og enkeltlokasjoner.</p>
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

                <!-- Taxonomi -->
                <div class="options-card" data-section="taksonomi">
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
                                    'default' => 'Standard - enkel kursliste',
                                    'default-courselist-shortcode' => 'Standard - komplett kursliste',
                                    'simple' => 'Uten bilde og beskrivelse - enkel kursliste',
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
                                            <p class="description">Merk: på <a href="/wp-admin/admin.php?page=design#section-systemsider">siden</a> med instruktøroversikten må du legge til vis="fornavn" eller vis="etternavn" i kortkoden for å vise kun fornavn eller etternavn. Du kan gå direkte til redigering fra <a href="/wp-admin/admin.php?page=design#section-systemsider">Systemsider</a>.</p>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Standardbilder -->
                <div class="options-card" data-section="valg-for-bilder">
                    <h3 id="valg-for-bilder">Valg for bilder</h3>
                    <p>Standarbilder brukes som en backupløsning for å hindre ødelagte design. Disse brukes som plassholdere om et bilde mangler. Velger du ingen bilder, bruker vi Kursagentens standard erstatningsbilder om nødvendig. Du kan også sette inn url til plassholderbilder via <a href="/wp-admin/admin.php?page=kursagenten#kortkoder">kortkoder</a>.</p>
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

                <div class="options-card" data-section="egen-css">
                    <h3>Egen CSS</h3>
                    <p>Her kan du legge til egendefinert CSS som vil bli lastet inn på alle sider som hører til utvidelsen. Denne CSS-en vil ha høyest prioritet og vil overstyre utvidelsens standard CSS.</p>
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
            .checkbox-label-small {
                font-weight: normal;
                font-size: 0.9em;
                margin-left: 10px;

            }
            #top-filters .size-limit-checkbox {
                display: none;
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

        // Registrer filter-innstillinger
        register_setting('design_option_group', 'kursagenten_top_filters');
        register_setting('design_option_group', 'kursagenten_left_filters');
        register_setting('design_option_group', 'kursagenten_filter_types');
        register_setting('design_option_group', 'kursagenten_available_filters');
        register_setting('design_option_group', 'kursagenten_filter_default_height');
        register_setting('design_option_group', 'kursagenten_filter_no_collapse');
        
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
            'custom_css',
            'ka_plassholderbilde_generelt',
            'ka_plassholderbilde_kurs',
            'ka_plassholderbilde_instruktor',
            'ka_plassholderbilde_sted'
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

        // Sanitize filter no-collapse settings
        if (isset($input['kursagenten_filter_no_collapse']) && is_array($input['kursagenten_filter_no_collapse'])) {
            foreach ($input['kursagenten_filter_no_collapse'] as $filter => $value) {
                $sanitary_values['kursagenten_filter_no_collapse'][$filter] = rest_sanitize_boolean($value);
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
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_media();
        
        // Enqueue the existing image upload script
        wp_enqueue_script(
            'custom-admin-upload-script',
            plugins_url('/assets/js/admin/image-upload.js', dirname(dirname(__FILE__))),
            array('jquery'),
            '1.0.3',
            true
        );
        
        wp_enqueue_script(
            'ka-admin-script',
            plugins_url('/assets/js/admin-script.js', dirname(dirname(__FILE__))),
            array('jquery', 'wp-color-picker'),
            (defined('KURSAG_VERSION') ? KURSAG_VERSION : '1.0.0'),
            true
        );

        wp_enqueue_style(
            'ka-admin-style',
            plugins_url('/assets/css/admin/kursagenten-admin.css', dirname(dirname(__FILE__))),
            array(),
            (defined('KURSAG_VERSION') ? KURSAG_VERSION : '1.0.0')
        );

        // Legg til inline JavaScript for fargevalg-toggle, seksjons-kollaps og filter-sortable
        wp_add_inline_script('ka-admin-script', <<<'JS'
            jQuery(document).ready(function($) {
                // Helpers for querystring state (ka_open)
                function getUrlParams() {
                    var params = new URLSearchParams(window.location.search);
                    return params;
                }
                function getCurrentlyOpenSections() {
                    var open = [];
                    $(".options-card").each(function(){
                        var $card = $(this);
                        var key = $card.data('section');
                        if (key && $card.attr('data-collapsed') === 'false') {
                            open.push(String(key));
                        }
                    });
                    return open;
                }
                function setReferrerParam(name, valueArray) {
                    var $ref = $('input[name="_wp_http_referer"]');
                    // Fallback: create if missing
                    if ($ref.length === 0) {
                        var current = window.location.pathname + window.location.search;
                        $ref = $('<input type="hidden" name="_wp_http_referer" />').val(current).appendTo('form[action="options.php"]');
                    }
                    try {
                        var refUrl = $ref.val() || '';
                        // Ensure it is an absolute URL for URL API
                        var absRef = refUrl.match(/^https?:\/\//) ? refUrl : (window.location.origin + refUrl);
                        var u = new URL(absRef);
                        if (!valueArray || valueArray.length === 0) {
                            u.searchParams.delete(name);
                        } else {
                            u.searchParams.set(name, valueArray.join(','));
                        }
                        var newVal = u.pathname + (u.search ? u.search : '');
                        $ref.val(newVal);
                    } catch(e) {
                        // No-op on malformed values
                    }
                }
                function setUrlParam(name, valueArray) {
                    var params = getUrlParams();
                    if (!valueArray || valueArray.length === 0) {
                        params.delete(name);
                    } else {
                        params.set(name, valueArray.join(','));
                    }
                    var newUrl = window.location.pathname + '?' + params.toString();
                    window.history.replaceState({}, '', newUrl);
                    // Keep WP referer in sync so redirect preserves ka_open
                    setReferrerParam(name, valueArray);
                }
                function getOpenSectionsFromUrl() {
                    var params = getUrlParams();
                    var val = params.get('ka_open');
                    if (!val) return [];
                    return val.split(',').filter(Boolean);
                }

                // Kollaps/utvid seksjoner: h3 fungerer som toggle
                $(".options-card").each(function() {
                    var $card = $(this);
                    var $title = $card.children("h3").first();
                    // Sett inn ikonbeholder i tittelen
                    if ($title.find('i.ka-icon').length === 0) {
                        $title.append(' <i class="ka-icon icon-chevron-right" aria-hidden="true"></i>');
                    }
                    var $children = $card.children().not($title);
                    var $firstParagraph = $children.filter("p").first();
                    // Innhold som skal toggles (ekskluder <style> og <script>)
                    var $toggleContent = $children.not($firstParagraph).not("style, script");

                    // Marker og vis kun tittel + første p, skjul resten
                    $card.addClass("ka-collapsible");
                    $toggleContent.hide();
                    $card.attr("data-collapsed", "true");

                    // Legg til liten forklarings-klasse på første p
                    if ($firstParagraph.length) {
                        $firstParagraph.addClass("ka-collapsible-intro");
                    }

                    // Klikk på tittel toggler resten
                    $title.css("cursor", "pointer").on("click", function(e) {
                        var isCollapsed = $card.attr("data-collapsed") === "true";
                        if (isCollapsed) {
                            $toggleContent.show();
                            $card.attr("data-collapsed", "false");
                            $title.find('i.ka-icon').removeClass('icon-chevron-right').addClass('icon-chevron-down');
                            // Re-evaluer visning av avanserte farger når seksjonen åpnes
                            if (typeof toggleAdvancedColors === "function") {
                                toggleAdvancedColors();
                            }
                            // Oppdater URL-state: inkluder denne i settet av åpne bokser
                            var sectionKey = $card.data('section');
                            if (sectionKey) {
                                var currentOpen = getCurrentlyOpenSections();
                                if (currentOpen.indexOf(String(sectionKey)) === -1) {
                                    currentOpen.push(String(sectionKey));
                                }
                                setUrlParam('ka_open', currentOpen);
                            }
                        } else {
                            $toggleContent.hide();
                            $card.attr("data-collapsed", "true");
                            $title.find('i.ka-icon').removeClass('icon-chevron-down').addClass('icon-chevron-right');
                            // Hvis denne boksen var i URL-state, fjern den
                            var current = getCurrentlyOpenSections();
                            var sectionKey = $card.data('section');
                            if (sectionKey) {
                                current = current.filter(function(k){ return k !== sectionKey; });
                                setUrlParam('ka_open', current);
                            }
                        }
                    });
                });

                // Åpne boks(er) fra URL ved innlasting
                (function openFromUrl(){
                    var openKeys = getOpenSectionsFromUrl();
                    if (!openKeys.length) return;
                    // Vi støtter både én (prioritert) og flere nøkler
                    $(".options-card").each(function(){
                        var $card = $(this);
                        var key = $card.data('section');
                        if (key && openKeys.indexOf(String(key)) !== -1) {
                            var $title = $card.children("h3").first();
                            // Simuler åpen tilstand
                            var $children = $card.children().not($title);
                            var $firstParagraph = $children.filter("p").first();
                            var $toggleContent = $children.not($firstParagraph).not("style, script");
                            $toggleContent.show();
                            $card.attr("data-collapsed", "false");
                            $title.find('i.ka-icon').removeClass('icon-chevron-right').addClass('icon-chevron-down');
                        }
                    });
                    // Sørg for at referer også bærer åpen seksjon ved lagring
                    setReferrerParam('ka_open', openKeys);
                })();

                // Håndter toggle av avanserte fargevalg
                function toggleAdvancedColors() {
                    var isChecked = $(".ka-advanced-colors-toggle").is(":checked");
                    $(".advanced-colors-section").each(function(){
                        var $section = $(this);
                        var $card = $section.closest(".options-card");
                        var isCollapsed = ($card.length && $card.attr("data-collapsed") === "true");
                        // Vis kun når avkrysset OG seksjonen er utvidet
                        $section.toggle(isChecked && !isCollapsed);
                    });
                }

                // Initial toggle
                toggleAdvancedColors();

                // Bind toggle-funksjon til checkbox
                $(".ka-advanced-colors-toggle").on("change", toggleAdvancedColors);

                // Håndter toggle av filter-type og visning av "Ikke begrens høyde" checkbox
                $(document).on("change", "input[name^=\"kursagenten_filter_types\"]", function() {
                    var $this = $(this);
                    var $container = $this.closest("li");
                    var $listOptions = $container.find(".checkbox-label-small.filter-list-options");
                    
                    // Vis kun checkbox i venstre kolonne (ikke i toppfilteret)
                    if ($this.val() === "list" && $container.closest("#left-filters").length > 0) {
                        $listOptions.show();
                    } else {
                        $listOptions.hide();
                    }
                });

                // Filter sortable functionality
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
                                    let $container = $(this);
                                    let isLeftFilter = $container.closest("#left-filters").length > 0;
                                    
                                    let checkboxHtml = '';
                                    if (isLeftFilter) {
                                        checkboxHtml = `<label class="checkbox-label-small filter-list-options size-limit-checkbox">
                                            <input type="checkbox" name="kursagenten_filter_no_collapse[${filter}]" value="1"> Ikke begrens høyde
                                        </label>`;
                                    }
                                    
                                    $(this).append(`
                                        <span class="filter-type-options">
                                            <label><input type="radio" name="kursagenten_filter_types[${filter}]" value="chips"> Knapper</label>
                                            <label><input type="radio" name="kursagenten_filter_types[${filter}]" value="list" checked> Liste</label>
                                            ${checkboxHtml}
                                        </span>
                                    `);
                                }
                            }
                        });
                    }
                }).disableSelection();
        });
        JS    
        );

        // Legg til filter-CSS
        wp_add_inline_style('wp-admin', '
            .filter-containers {
                display: grid;
                grid-template-columns: 1fr 2fr;
                gap: 1em;
            }
            /* Kollaps/utvid indikator */
            .ka-collapsible > h3 {
                position: relative;
                user-select: none;
            }
            /* Deaktiver gammel pseudo-pil */
            .ka-collapsible > h3::after { content: "" !important; }
            .ka-collapsible[data-collapsed="false"] > h3::after { content: "" !important; }
            /* Stil for nye ikonpiler */
            .ka-collapsible > h3 .ka-icon {
                color: #666;
                font-size: 16px;
                line-height: 1;
                padding-left: 1em;
            }
            .ka-collapsible-intro {
                margin-top: -6px;
                color: #555;
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
            .ka-collapsible[data-collapsed="false"] {
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08), 0 1px 3px rgba(0, 0, 0, 0.06);
                border-radius: 8px;     /* valgfritt */
                background: #fff;       /* valgfritt, gir renere skygge */
            }
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
                                    [kurssteder layout=rad stil=kort grid=3 gridtablet=2 gridmobil=1 radavstand=2em bildestr=100px bildeform=firkantet bildeformat=1/1 fontmin="14" fontmaks="18"]
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
                ],
                'betaling' => [
                    'title' => 'Betaling',
                    'content' => '<!-- wp:html -->
<iframe title="Betal for kurs" id="kursagentenIframe" allowfullscreen frameBorder="0" style="overflow:hidden;height:900px;width:100%;border-radius: 8px;"></iframe>

<script type="text/javascript">
var queryString = window.location.search;

if (queryString) {
    var urlParams = new URLSearchParams(queryString);
    var pid = urlParams.get("pid");
    if (pid) {
        var theme = urlParams.get("theme");
        if (theme) {
            pid += "?theme=" + theme;
        }
        var myIframe = document.getElementById("kursagentenIframe");
        if(myIframe){
            myIframe.src = "https://embed.kursagenten.no/Betaling/" + pid;
        }
        else{
            console.log("iframe with id kursagentenIframe not found");
        }
    }
}
</script>
<script type="text/javascript" src="https://embed.kursagenten.no/js/iframe-resizer/iframeResizer.min.js"></script>
<!-- /wp:html -->',
                    'description' => 'Betalingsside for kurs',
                    'slug' => 'betaling'
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
                            <td>
                                <?php echo esc_html($page['description']); ?>
                                <?php 
                                if ($key === 'betaling' && $exists) {
                                    $payment_url = get_permalink($page_id);
                                    if (!empty($payment_url)) {
                                        $payment_url_with_pid = esc_url($payment_url) . '?pid={pid}';
                                        echo '<div style="color: #939393; font-size: .95em; font-style: italic;">Legg inn betalingslenke '
                                            . '<span class="copytext" title="Klikk for å kopiere">' . esc_html($payment_url_with_pid) . '</span>'
                                            . ' i <a href="https://kursadmin.kursagenten.no/IframeSetting" target="_blank" rel="noopener">Kursagenten</a>'
                                            . '</div>';
                                    }
                                }
                                ?>
                            </td>
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
        $desired_slug = isset($page_data['slug']) ? $page_data['slug'] : sanitize_title($page_data['title']);
        $post_title = $page_data['title'];

        // If a page already exists with the desired slug and it's not our system page,
        // avoid WP auto-adding -2 by using a conflict-free slug for Betaling.
        $existing = get_page_by_path($desired_slug, OBJECT, 'page');
        if ($existing instanceof \WP_Post) {
            $existing_key = get_post_meta($existing->ID, '_ka_system_page', true);
            if ($existing_key !== $page_key) {
                if ($page_key === 'betaling') {
                    $desired_slug = 'kurs-betaling';
                    $post_title = 'Betaling for kurs';
                }
            } else {
                // Already our system page, just update option and return
                update_option('ka_page_' . $page_key, $existing->ID);
                update_post_meta($existing->ID, '_ka_system_page', $page_key);
                return (int) $existing->ID;
            }
        }

        $page_id = wp_insert_post([
            'post_title' => $post_title,
            'post_content' => $page_data['content'],
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_name' => $desired_slug,
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
     * Beregn visningsnavn for instruktør i henhold til innstilling
     *
     * @param WP_Term $term Instructors-term
     * @return string Navnet som skal vises
     */
    private function get_instructor_display_name($term) {
        if (!($term instanceof WP_Term) || $term->taxonomy !== 'instructors') {
            return '';
        }

        $name_display = get_option('kursagenten_taxonomy_instructors_name_display', '');
        if ($name_display === 'firstname') {
            $display_name = get_term_meta($term->term_id, 'instructor_firstname', true);
            return !empty($display_name) ? $display_name : $term->name;
        }
        if ($name_display === 'lastname') {
            $display_name = get_term_meta($term->term_id, 'instructor_lastname', true);
            return !empty($display_name) ? $display_name : $term->name;
        }
        return $term->name;
    }

    /**
     * Juster document title parts for instruktør-taksonomi
     *
     * @param array $title_parts
     * @return array
     */
    public function filter_document_title_parts($title_parts) {
        if (is_tax('instructors')) {
            $term = get_queried_object();
            if ($term instanceof WP_Term) {
                $display = $this->get_instructor_display_name($term);
                if (!empty($display)) {
                    $title_parts['title'] = $display;
                }
            }
        }
        return $title_parts;
    }

    /**
     * Juster Yoast SEO tittel for instruktør-taksonomi
     *
     * @param string $title
     * @return string
     */
    public function filter_wpseo_title($title) {
        if (is_tax('instructors')) {
            $term = get_queried_object();
            if ($term instanceof WP_Term) {
                $display = $this->get_instructor_display_name($term);
                if (!empty($display)) {
                    return $display;
                }
            }
        }
        return $title;
    }

    /**
     * Juster Rank Math SEO tittel for instruktør-taksonomi
     *
     * @param string $title
     * @return string
     */
    public function filter_rank_math_title($title) {
        if (is_tax('instructors')) {
            $term = get_queried_object();
            if ($term instanceof WP_Term) {
                $display = $this->get_instructor_display_name($term);
                if (!empty($display)) {
                    return $display;
                }
            }
        }
        return $title;
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

    /**
     * Callback for generelt plassholderbilde
     */
    public function plassholderbilde_generelt_callback() {
        $image_url = isset($this->design_options['ka_plassholderbilde_generelt']) ? $this->design_options['ka_plassholderbilde_generelt'] : '';
        $fallback_url = KURSAG_PLUGIN_URL . 'assets/images/placeholder-generell.jpg';
        ?>
        <div class="image-upload-wrapper">
            <img id="ka_plassholderbilde_generelt_preview" src="<?php echo esc_url($image_url ? $image_url : $fallback_url); ?>" style="max-width: 80px; max-height: 80px; <?php echo ($image_url || $fallback_url) ? '' : 'display: none;'; ?> border:1px solid #eee; background:#fafafa;" />
            <input type="hidden" id="ka_plassholderbilde_generelt" name="design_option_name[ka_plassholderbilde_generelt]" value="<?php echo esc_attr($image_url); ?>" />
            <button type="button" class="button upload_image_button_ka_plassholderbilde_generelt">Velg bilde</button>
            <button type="button" class="button remove_image_button_ka_plassholderbilde_generelt" style="<?php echo $image_url ? '' : 'display: none;'; ?>">Fjern bilde</button>
        </div>
        <?php
    }

    /**
     * Callback for kurs plassholderbilde
     */
    public function plassholderbilde_kurs_callback() {
        $image_url = isset($this->design_options['ka_plassholderbilde_kurs']) ? $this->design_options['ka_plassholderbilde_kurs'] : '';
        $fallback_url = KURSAG_PLUGIN_URL . 'assets/images/placeholder-kurs.jpg';
        ?>
        <div class="image-upload-wrapper">
            <img id="ka_plassholderbilde_kurs_preview" src="<?php echo esc_url($image_url ? $image_url : $fallback_url); ?>" style="max-width: 80px; max-height: 80px; <?php echo ($image_url || $fallback_url) ? '' : 'display: none;'; ?> border:1px solid #eee; background:#fafafa;" />
            <input type="hidden" id="ka_plassholderbilde_kurs" name="design_option_name[ka_plassholderbilde_kurs]" value="<?php echo esc_attr($image_url); ?>" />
            <button type="button" class="button upload_image_button_ka_plassholderbilde_kurs">Velg bilde</button>
            <button type="button" class="button remove_image_button_ka_plassholderbilde_kurs" style="<?php echo $image_url ? '' : 'display: none;'; ?>">Fjern bilde</button>
        </div>
        <?php
    }

    /**
     * Callback for instruktør plassholderbilde
     */
    public function plassholderbilde_instruktor_callback() {
        $image_url = isset($this->design_options['ka_plassholderbilde_instruktor']) ? $this->design_options['ka_plassholderbilde_instruktor'] : '';
        $fallback_url = KURSAG_PLUGIN_URL . 'assets/images/placeholder-instruktor.jpg';
        ?>
        <div class="image-upload-wrapper">
            <img id="ka_plassholderbilde_instruktor_preview" src="<?php echo esc_url($image_url ? $image_url : $fallback_url); ?>" style="max-width: 80px; max-height: 80px; <?php echo ($image_url || $fallback_url) ? '' : 'display: none;'; ?> border:1px solid #eee; background:#fafafa;" />
            <input type="hidden" id="ka_plassholderbilde_instruktor" name="design_option_name[ka_plassholderbilde_instruktor]" value="<?php echo esc_attr($image_url); ?>" />
            <button type="button" class="button upload_image_button_ka_plassholderbilde_instruktor">Velg bilde</button>
            <button type="button" class="button remove_image_button_ka_plassholderbilde_instruktor" style="<?php echo $image_url ? '' : 'display: none;'; ?>">Fjern bilde</button>
        </div>
        <?php
    }

    /**
     * Callback for sted plassholderbilde
     */
    public function plassholderbilde_sted_callback() {
        $image_url = isset($this->design_options['ka_plassholderbilde_sted']) ? $this->design_options['ka_plassholderbilde_sted'] : '';
        $fallback_url = KURSAG_PLUGIN_URL . 'assets/images/placeholder-location.jpg';
        ?>
        <div class="image-upload-wrapper">
            <img id="ka_plassholderbilde_sted_preview" src="<?php echo esc_url($image_url ? $image_url : $fallback_url); ?>" style="max-width: 80px; max-height: 80px; <?php echo ($image_url || $fallback_url) ? '' : 'display: none;'; ?> border:1px solid #eee; background:#fafafa;" />
            <input type="hidden" id="ka_plassholderbilde_sted" name="design_option_name[ka_plassholderbilde_sted]" value="<?php echo esc_attr($image_url); ?>" />
            <button type="button" class="button upload_image_button_ka_plassholderbilde_sted">Velg bilde</button>
            <button type="button" class="button remove_image_button_ka_plassholderbilde_sted" style="<?php echo $image_url ? '' : 'display: none;'; ?>">Fjern bilde</button>
        </div>
        <?php
    }
}

?>

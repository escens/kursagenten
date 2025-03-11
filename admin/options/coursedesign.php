<?php
class Designmaler {
    private $design_options;

    public function __construct() {
        add_action('admin_menu', array($this, 'design_add_plugin_page'));
        add_action('admin_init', array($this, 'design_page_init'));
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
        $inactive_filters = ['time_of_day', 'price']; // Filtre som ikke er i bruk
        $top_filters = get_option('kursagenten_top_filters', []);
        $left_filters = get_option('kursagenten_left_filters', []);
        $filter_types = get_option('kursagenten_filter_types', []);

        // Sørg for at verdiene er arrays
        $top_filters = is_array($top_filters) ? $top_filters : explode(',', $top_filters);
        $left_filters = is_array($left_filters) ? $left_filters : explode(',', $left_filters);

        // Lagre available_filters som en option
        update_option('kursagenten_available_filters', $available_filters);
        ?>
        <div class="wrap options-form">
            <h2>Design på kurslister og kurssider</h2>
            <p>Velg oppsett, farger og filter på kurssider og kursdesign.</p>
            <?php settings_errors(); ?>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('design_option_group');
                do_settings_sections('design-admin');
                ?>
                
                <h3>Kurslistedesign</h3>
                <p>Velg hvordan kurslisten skal vises på nettsiden.</p>
                <select name="kursagenten_template_style" id="template-style">
                    <?php
                    $current_style = get_option('kursagenten_template_style', 'default');
                    $template_styles = array(
                        'default' => 'Standard liste',
                        'grid' => 'Rutenett',
                        'compact' => 'Kompakt liste'
                    );
                    foreach ($template_styles as $value => $label) {
                        printf(
                            '<option value="%s" %s>%s</option>',
                            esc_attr($value),
                            selected($current_style, $value, false),
                            esc_html($label)
                        );
                    }
                    ?>
                </select>

                <h3>Taksonomidesign</h3>
                <p>Velg hvordan taksonomisidene skal vises.</p>
                <select name="kursagenten_taxonomy_style" id="taxonomy-style">
                    <?php
                    $current_tax_style = get_option('kursagenten_taxonomy_style', 'default');
                    $taxonomy_styles = array(
                        'default' => 'Standard visning',
                        'grid' => 'Rutenett med stort bilde',
                        'compact' => 'Kompakt liste'
                    );
                    foreach ($taxonomy_styles as $value => $label) {
                        printf(
                            '<option value="%s" %s>%s</option>',
                            esc_attr($value),
                            selected($current_tax_style, $value, false),
                            esc_html($label)
                        );
                    }
                    ?>
                </select>

                <div class="taxonomy-specific-settings">
                    <h4>Spesifikke innstillinger per taksonomi</h4>
                    <?php
                    $taxonomies = array(
                        'coursecategory' => 'Kurskategorier',
                        'course_location' => 'Kurssteder',
                        'instructors' => 'Instruktører'
                    );
                    
                    foreach ($taxonomies as $tax_name => $tax_label) :
                        $tax_style = get_option("kursagenten_taxonomy_style_{$tax_name}", '');
                    ?>
                        <div class="taxonomy-style-override">
                            <label for="taxonomy-style-<?php echo esc_attr($tax_name); ?>">
                                <?php echo esc_html($tax_label); ?>:
                            </label>
                            <select name="kursagenten_taxonomy_style_<?php echo esc_attr($tax_name); ?>" 
                                    id="taxonomy-style-<?php echo esc_attr($tax_name); ?>">
                                <option value="">Bruk standard innstilling</option>
                                <?php foreach ($taxonomy_styles as $value => $label) : ?>
                                    <option value="<?php echo esc_attr($value); ?>" 
                                            <?php selected($tax_style, $value); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endforeach; ?>
                </div>

                <h3>Filterinnstillinger</h3>
                <p>Ta tak i filteret du ønsker å bruke, og dra til enten venstre kolonne eller over kursliste.
                    <br>Velg om filteret skal vises som tagger eller avkrysningsliste.</p>
                <p>For å fjerne et filter, dra det tilbake til tilgjengelige filtre.
                    <br> Husk å <strong>lagre</strong> endringene dine.
                </p>
                
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
                
                <?php submit_button(); ?>
            </form>
        </div>

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
            .sortable-list { list-style: none; padding: 10px; background: #ffffff7a; min-height: 50px; border: 2px dashed #cccccc61; border-radius: 8px; }
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
            #available-filters.sortable-list { display: flex; border: 0; background: #ffffff7a; padding: 1em; border: 2px dashed #ccc; border-radius: 8px; }
            #available-filters.sortable-list li { width: fit-content; height: fit-content; padding: 10px 15px;}
            #available-filters .filter-type-options { display: none; }
            .filter-type-options { float: right; margin-left: 10px; font-weight: normal; color: #777; }
            #left-filters .filter-type-options { float: none; clear: both; display: block; margin-top: 10px; }
            .disabled-filter {color: #616161; pointer-events: none; opacity: 0.6; position: relative;}
            li.disabled-filter::after { content: "kommer"; display: block; position: absolute; right: -4px; bottom: -11px;background: #fff; color: #4d4d4d; font-size: 10px; font-weight: normal; padding: 0 4px; border-radius: 3px; border: 1px solid #eee;}
            .taxonomy-specific-settings {
                margin: 2em 0;
                padding: 1.5em;
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
            }
            .taxonomy-style-override {
                margin: 1em 0;
                display: grid;
                grid-template-columns: 200px 1fr;
                align-items: center;
                gap: 1em;
            }
            .taxonomy-style-override label {
                font-weight: 500;
            }
            .taxonomy-style-override select {
                max-width: 300px;
            }
        </style>
        <?php
    }

    public function design_page_init() {
        register_setting(
            'design_option_group',
            'design_option_name',
            array($this, 'design_sanitize')
        );

        // Register template style options
        register_setting(
            'design_option_group',
            'kursagenten_template_style',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'default'
            )
        );

        // Register taxonomy template style options
        register_setting(
            'design_option_group',
            'kursagenten_taxonomy_style',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'default'
            )
        );

        // Register individual taxonomy style options
        $taxonomies = array('coursecategory', 'course_location', 'instructors');
        foreach ($taxonomies as $taxonomy) {
            register_setting(
                'design_option_group',
                "kursagenten_taxonomy_style_{$taxonomy}",
                array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                )
            );
        }

        register_setting('design_option_group', 'kursagenten_top_filters');
        register_setting('design_option_group', 'kursagenten_left_filters');
        register_setting('design_option_group', 'kursagenten_filter_types');
        register_setting('design_option_group', 'kursagenten_available_filters');
    }

    public function design_sanitize($input) {
        $sanitary_values = array();
        
        if (isset($input['template_style'])) {
            $sanitary_values['template_style'] = sanitize_text_field($input['template_style']);
        }
        
        if (isset($input['taxonomy_style'])) {
            $sanitary_values['taxonomy_style'] = sanitize_text_field($input['taxonomy_style']);
        }
        
        return $sanitary_values;
    }
}

new Designmaler();
?>

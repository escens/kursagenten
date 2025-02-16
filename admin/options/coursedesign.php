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
            ]
        ];
        $inactive_filters = ['price', 'date','time_of_day']; // Filtre som ikke er i bruk
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
                                    <?php if (in_array($key, ['categories', 'locations', 'instructors', 'language'])) : ?>
                                        <span class="filter-type-options">
                                            <label><input type="radio" name="kursagenten_filter_types[<?php echo esc_attr($key); ?>]" value="chips" <?php echo (isset($filter_types[$key]) && $filter_types[$key] === 'chips') ? 'checked' : ''; ?>> Chips</label>
                                            <label><input type="radio" name="kursagenten_filter_types[<?php echo esc_attr($key); ?>]" value="list" <?php echo (isset($filter_types[$key]) && $filter_types[$key] === 'list') ? 'checked' : ''; ?>> Liste</label>
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
                                    <?php if (in_array($filter, ['categories', 'locations', 'instructors', 'language', 'time_of_day'])) : ?>
                                        <span class="filter-type-options">
                                            <label>
                                                <input type="radio" name="kursagenten_filter_types[<?php echo esc_attr($filter); ?>]" value="chips"
                                                    <?php echo (isset($filter_types[$filter]) && $filter_types[$filter] === 'chips') ? 'checked' : ''; ?>> Tagger
                                            </label>
                                            <label>
                                                <input type="radio" name="kursagenten_filter_types[<?php echo esc_attr($filter); ?>]" value="list"
                                                    <?php echo (isset($filter_types[$filter]) && $filter_types[$filter] === 'list') ? 'checked' : ''; ?>> Avkrysningsliste
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
                                    <?php if (in_array($filter, ['categories', 'locations', 'instructors', 'language', 'time_of_day'])) : ?>
                                        <span class="filter-type-options">
                                            <label>
                                                <input type="radio" name="kursagenten_filter_types[<?php echo esc_attr($filter); ?>]" value="chips"
                                                    <?php echo (isset($filter_types[$filter]) && $filter_types[$filter] === 'chips') ? 'checked' : ''; ?>> Tagger
                                            </label>
                                            <label>
                                                <input type="radio" name="kursagenten_filter_types[<?php echo esc_attr($filter); ?>]" value="list"
                                                    <?php echo (isset($filter_types[$filter]) && $filter_types[$filter] === 'list') ? 'checked' : ''; ?>> Avkrysningsliste
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
                    update: function() {
                        let topFilters = $("#top-filters").sortable("toArray", { attribute: "data-filter" });
                        let leftFilters = $("#left-filters").sortable("toArray", { attribute: "data-filter" });

                        $("#top-filters-input").val(topFilters.join(","));
                        $("#left-filters-input").val(leftFilters.join(","));

                        // Beholder radioknappene etter flytting
                        $(".sortable-list li").each(function() {
                            let filter = $(this).attr("data-filter");
                            if (["categories", "locations", "instructors", "language", "time_of_day"].includes(filter)) {
                                if ($(this).find(".filter-type-options").length === 0) {
                                    $(this).append(`
                                        <span class="filter-type-options">
                                            <label><input type="radio" name="kursagenten_filter_types[${filter}]" value="chips" checked> Tagger</label>
                                            <label><input type="radio" name="kursagenten_filter_types[${filter}]" value="list"> Avkrysningsliste</label>
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
            .sortable-list { list-style: none; padding: 10px; background: #f7f7f7; min-height: 50px; border: 1px solid #e9e9e9; }
            .sortable-list li { padding: 10px 10px; margin: 10px 5px; background: #fff; cursor: move; border: 1px solid #e5e5e5; border-radius: 5px; font-weight: bold;}
            #available-filters.sortable-list { display: flex; border: 0; background: transparent; padding: 0; }
            #available-filters.sortable-list li { width: fit-content; height: fit-content; padding: 10px 15px;}
            #available-filters .filter-type-options { display: none; }
            .filter-type-options { float: right; margin-left: 10px; font-weight: normal; color: #777; }
            #left-filters .filter-type-options { float: none; clear: both; display: block; margin-top: 10px; }
            .disabled-filter {color: #616161; pointer-events: none; opacity: 0.6; position: relative;}
            li.disabled-filter::after { content: "kommer"; display: block; position: absolute; right: -4px; bottom: -11px;background: #fff; color: #4d4d4d; font-size: 10px; font-weight: normal; padding: 0 4px; border-radius: 3px; border: 1px solid #eee;}
        </style>
        <?php
    }

    public function design_page_init() {
        register_setting(
            'design_option_group',
            'design_option_name',
            array($this, 'design_sanitize')
        );

        // Legg til registrering av template style option
        register_setting(
            'design_option_group',
            'kursagenten_template_style',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'default'
            )
        );

        register_setting('design_option_group', 'kursagenten_top_filters');
        register_setting('design_option_group', 'kursagenten_left_filters');
        register_setting('design_option_group', 'kursagenten_filter_types');
        register_setting('design_option_group', 'kursagenten_available_filters');
    }
}

new Designmaler();
?>

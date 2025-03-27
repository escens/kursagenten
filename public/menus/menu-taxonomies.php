<?php
declare(strict_types=1);

require_once ABSPATH . 'wp-admin/includes/class-walker-nav-menu-edit.php';

/**
 * Custom walker for auto menu items in admin
 */
class Kursagenten_Walker_Nav_Menu_Edit extends Walker_Nav_Menu_Edit {
    public function start_el(&$output, $data_object, $depth = 0, $args = null, $current_object_id = 0) {
        if ($data_object->object === 'kursagenten_auto_menu') {
            $item_id = esc_attr($data_object->ID);
            $taxonomy = get_post_meta($item_id, '_menu_item_taxonomy', true);
            $parent_term_id = get_post_meta($item_id, '_menu_item_parent_term', true);
            
            // Sjekk om tittelen er en av standardtitlene
            $standard_titles = [
                'Kurskategorier',
                'Instruktører',
                'Kurssteder'
            ];
            
            // Sjekk om tittelen følger standard mønster med parentes
            $standard_patterns = [
                'Kurskategorier \(.*\)',
                'Instruktører \(.*\)',
                'Kurssteder \(.*\)'
            ];
            
            $is_standard_title = in_array($data_object->title, $standard_titles);
            $has_standard_pattern = false;
            foreach ($standard_patterns as $pattern) {
                if (preg_match('/^' . $pattern . '$/', $data_object->title)) {
                    $has_standard_pattern = true;
                    break;
                }
            }
            
            // Hvis vi har en parent term
            if (!empty($parent_term_id)) {
                $term = get_term($parent_term_id, $taxonomy);
                if (!is_wp_error($term) && $term) {
                    if ($is_standard_title || $has_standard_pattern) {
                        // Hvis det er standard tittel eller følger standard mønster
                        $base_title = preg_replace('/\s*\([^)]+\)\s*$/', '', $data_object->title);
                        $data_object->title = $base_title . ' (' . $term->name . ')';
                    }
                    // Hvis ikke standard tittel, behold den tilpassede tittelen
                }
            } else {
                // Hvis ingen parent term er valgt, fjern parenteser bare hvis det følger standard mønster
                if ($has_standard_pattern) {
                    $data_object->title = preg_replace('/\s*\([^)]+\)\s*$/', '', $data_object->title);
                }
            }
            
            parent::start_el($output, $data_object, $depth, $args, $current_object_id);

            // Add our custom fields
            $item_id = esc_attr($data_object->ID);
            $taxonomy = get_post_meta($item_id, '_menu_item_taxonomy', true);
            $parent_term_id = get_post_meta($item_id, '_menu_item_parent_term', true);
            
            // Finn posisjonen til dette menyelementet i output
            $item_start = strpos($output, 'id="menu-item-settings-' . $item_id . '"');
            if ($item_start === false) {
                return;
            }
            
            // Finn slutten av dette menyelementet
            $item_end = strpos($output, 'menu-item-actions description-wide submitbox', $item_start);
            if ($item_end === false) {
                return;
            }
            
            // Isoler dette menyelementets HTML
            $item_html = substr($output, $item_start, $item_end - $item_start);
            
            // Hent hovedtermer med undertermer for denne spesifikke taksonomien
            $parent_terms = get_terms([
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
                'parent' => 0,
                'meta_query' => [
                    'relation' => 'OR',
                    [
                        'key' => 'hide_in_list',
                        'value' => 'Vis',
                    ],
                    [
                        'key' => 'hide_in_list',
                        'compare' => 'NOT EXISTS'
                    ]
                ]
            ]);

            $fields = '';
            
            // Legg til dropdown bare hvis det finnes hovedtermer med undertermer
            $has_children_terms = array_filter($parent_terms, function($term) use ($taxonomy) {
                $children = get_terms([
                    'taxonomy' => $taxonomy,
                    'hide_empty' => false,
                    'parent' => $term->term_id
                ]);
                return !empty($children) && !is_wp_error($children);
            });

            if (!empty($has_children_terms)) {
                $fields .= '<p class="description description-wide" style="border-left: 1px solid #d3e1dd; padding: 0 10px 10px;">';
                $fields .= '<label for="edit-menu-item-parent-term-' . $item_id . '">';
                $fields .= 'Bruk undermeny som toppmeny<br />';
                $fields .= '<select id="edit-menu-item-parent-term-' . $item_id . '" ';
                $fields .= 'name="menu-item-parent-term[' . $item_id . ']" class="widefat">';
                $fields .= '<option value="">-- Velg undermeny --</option>';
                
                foreach ($has_children_terms as $term) {
                    $selected = $parent_term_id == $term->term_id ? ' selected="selected"' : '';
                    $fields .= '<option value="' . esc_attr($term->term_id) . '"' . $selected . '>';
                    $fields .= esc_html($term->name);
                    $fields .= '</option>';
                }
                
                $fields .= '</select>';
                $fields .= '</label></p>';
            }

            // Legg til taxonomy felt
            $fields .= '<p class="description description-wide">';
            $fields .= '<label for="edit-menu-item-taxonomy-' . $item_id . '">';
            $fields .= 'Taksonomi for automeny:';
            $fields .= '<input type="text" id="edit-menu-item-taxonomy-' . $item_id . '" ';
            $fields .= 'class="widefat code edit-menu-item-taxonomy" ';
            $fields .= 'name="menu-item-taxonomy[' . $item_id . ']" ';
            $fields .= 'value="' . esc_attr($taxonomy) . '" readonly style="background: white; font-size: 13px; border: 0; width: fit-content;" />';
            $fields .= '</label></p>';

            // Sett inn feltene før move-combo eller actions
            if (strpos($item_html, '<div class="field-move-combo description-group">') !== false) {
                $item_html = str_replace(
                    '<div class="field-move-combo description-group">',
                    $fields . '<div class="field-move-combo description-group">',
                    $item_html
                );
            } else {
                $item_html = str_replace(
                    '<div class="menu-item-actions description-wide submitbox">',
                    $fields . '<div class="menu-item-actions description-wide submitbox">',
                    $item_html
                );
            }
            
            // Erstatt den originale HTML-en med den oppdaterte versjonen
            $output = substr_replace($output, $item_html, $item_start, $item_end - $item_start);
        } else {
            parent::start_el($output, $data_object, $depth, $args, $current_object_id);
        }
    }
}

/**
 * Custom walker for auto menu items in frontend
 */
class Kursagenten_Walker_Nav_Menu extends Walker_Nav_Menu {
    /**
     * Starter elementet
     */
    public function start_el(&$output, $data_object, $depth = 0, $args = null, $current_object_id = 0) {
        $menu_item = $data_object;

        if ($menu_item->object !== 'kursagenten_auto_menu') {
            parent::start_el($output, $menu_item, $depth, $args, $current_object_id);
            return;
        }


        // Hent taxonomy direkte fra post meta
        $taxonomy = get_post_meta($menu_item->ID, '_menu_item_taxonomy', true);
        $parent_term_id = get_post_meta($menu_item->ID, '_menu_item_parent_term', true);
        
        // Hvis vi har en parent_term_id, bruk termens navn som menytittel
        if (!empty($parent_term_id)) {
            $term = get_term($parent_term_id, $taxonomy);
            if (!is_wp_error($term) && $term) {
                $menu_item->title = $term->name;
            }
        }

        // Hvis taxonomy ikke er satt i meta, prøv å hente fra menu-type
        if (empty($taxonomy)) {
            $menu_type = get_post_meta($menu_item->ID, '_menu_item_menu_type', true);
            
            switch ($menu_type) {
                case 'course_categories':
                    $taxonomy = 'coursecategory';
                    break;
                case 'course_locations':
                    $taxonomy = 'course_location';
                    break;
                case 'course_instructors':
                    $taxonomy = 'instructors';
                    break;
            }
        }

        if (empty($taxonomy)) {
            return;
        }


        // Start parent element
        if (isset($args->item_spacing) && 'discard' === $args->item_spacing) {
            $t = '';
            $n = '';
        } else {
            $t = "\t";
            $n = "\n";
        }
        $indent = ($depth) ? str_repeat($t, $depth) : '';

        $classes = empty($menu_item->classes) ? array() : (array) $menu_item->classes;
        $classes[] = 'menu-item-' . $menu_item->ID;
        $classes[] = 'menu-item-has-children'; // Legg til denne klassen for å vise dropdown

        $args = apply_filters('nav_menu_item_args', $args, $menu_item, $depth);
        $class_names = implode(' ', apply_filters('nav_menu_css_class', array_filter($classes), $menu_item, $args, $depth));

        $id = apply_filters('nav_menu_item_id', 'menu-item-' . $menu_item->ID, $menu_item, $args, $depth);

        $li_atts = array();
        $li_atts['id'] = !empty($id) ? $id : '';
        $li_atts['class'] = !empty($class_names) ? $class_names : '';

        $li_atts = apply_filters('nav_menu_item_attributes', $li_atts, $menu_item, $args, $depth);
        $li_attributes = $this->build_atts($li_atts);

        $output .= $indent . '<li' . $li_attributes . '>';

        // Legg til hovedlenken
        $atts = array();
        $atts['href'] = '#';
        $atts['class'] = 'ct-menu-link';
        $atts['aria-expanded'] = 'false';

        $atts = apply_filters('nav_menu_link_attributes', $atts, $menu_item, $args, $depth);
        $attributes = $this->build_atts($atts);

        $title = apply_filters('the_title', $menu_item->title, $menu_item->ID);
        $title = apply_filters('nav_menu_item_title', $title, $menu_item, $args, $depth);

        $item_output = $args->before;
        $item_output .= '<a' . $attributes . '>';
        $item_output .= $args->link_before . $title;
        
        // Legg til nedtrekkspil for hovedmenypunktet
        $item_output .= '<span class="ct-toggle-dropdown-desktop"><svg class="ct-icon" width="8" height="8" viewBox="0 0 15 15"><path d="M2.1,3.2l5.4,5.4l5.4-5.4L15,4.3l-7.5,7.5L0,4.3L2.1,3.2z"></path></svg></span>';
        
        $item_output .= $args->link_after;
        $item_output .= '</a>';

        // Legg til ghost-knapp for hovedmenypunktet
        $item_output .= '<button class="ct-toggle-dropdown-desktop-ghost" aria-label="Utvid nedtrekksmenyen" aria-haspopup="true" aria-expanded="false"></button>';

        $item_output .= $args->after;

        $output .= apply_filters('walker_nav_menu_start_el', $item_output, $menu_item, $depth, $args);

        // Start undermeny
        $this->start_lvl($output, $depth, $args);

        // Hent og legg til terms som undermenypunkter
    $terms = get_terms([
            'taxonomy' => $taxonomy,
        'hide_empty' => false,
        'hierarchical' => true,
        'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => 'hide_in_list',
                    'value' => 'Vis',
                ],
                [
                    'key' => 'hide_in_list',
                    'compare' => 'NOT EXISTS'
                ]
            ],
            'parent' => 0
        ]);

        if (!is_wp_error($terms) && !empty($terms)) {
            foreach ($terms as $term) {
                $this->add_term_item($output, $term, $depth + 1, $args);
            }
        }

        // Avslutt undermeny
        $this->end_lvl($output, $depth, $args);
    }

    /**
     * Legger til term som menypunkt
     */
    private function add_term_item(&$output, $term, $depth, $args) {
        if (isset($args->item_spacing) && 'discard' === $args->item_spacing) {
            $t = '';
            $n = '';
        } else {
            $t = "\t";
            $n = "\n";
        }
        $indent = str_repeat($t, $depth);

        // Sjekk om termen har barn
        $has_children = get_terms([
            'taxonomy' => $term->taxonomy,
            'hide_empty' => false,
            'parent' => $term->term_id,
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => 'hide_in_list',
                    'value' => 'Vis',
                ],
                [
                    'key' => 'hide_in_list',
                    'compare' => 'NOT EXISTS'
                ]
            ]
        ]);

        // Opprett menypunkt for termen
        $classes = array(
            'menu-item',
            'menu-item-type-taxonomy',
            'menu-item-object-' . $term->taxonomy,
            'menu-item-' . $term->term_id
        );

        // Legg til ekstra klasser for nedtrekksmeny
        if (!is_wp_error($has_children) && !empty($has_children)) {
            $classes[] = 'menu-item-has-children';
            $classes[] = $depth === 0 ? 'animated-submenu-block' : 'animated-submenu-inline';
        }

        $class_names = implode(' ', apply_filters('nav_menu_css_class', array_filter($classes), null, $args, $depth));
        
        // Legg til data-submenu attributt for første nivå
        $data_submenu = $depth === 0 ? ' data-submenu="right"' : '';
        
        $output .= $indent . '<li class="' . esc_attr($class_names) . '"' . $data_submenu . '>';

        $atts = array(
            'href' => get_term_link($term),
            'class' => 'ct-menu-link'
        );
        
        $attributes = $this->build_atts($atts);

        $item_output = $args->before;
        $item_output .= '<a' . $attributes . '>';
        $item_output .= $args->link_before . esc_html($term->name);
        
        // Legg til nedtrekkspil hvis det finnes undermenyer
        if (!is_wp_error($has_children) && !empty($has_children)) {
            $item_output .= '<span class="ct-toggle-dropdown-desktop"><svg class="ct-icon" width="8" height="8" viewBox="0 0 15 15"><path d="M2.1,3.2l5.4,5.4l5.4-5.4L15,4.3l-7.5,7.5L0,4.3L2.1,3.2z"></path></svg></span>';
        }
        
        $item_output .= $args->link_after;
        $item_output .= '</a>';

        // Legg til ghost-knapp for nedtrekk hvis det finnes undermenyer
        if (!is_wp_error($has_children) && !empty($has_children)) {
            $item_output .= '<button class="ct-toggle-dropdown-desktop-ghost" aria-label="Utvid nedtrekksmenyen" aria-haspopup="true" aria-expanded="false"></button>';
        }

        $item_output .= $args->after;

        $output .= $item_output;

        // Sjekk etter og legg til undertermer
        $child_terms = get_terms([
            'taxonomy' => $term->taxonomy,
            'hide_empty' => false,
            'hierarchical' => true,
            'parent' => $term->term_id,
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => 'hide_in_list',
                    'value' => 'Vis',
                ],
                [
                    'key' => 'hide_in_list',
                    'compare' => 'NOT EXISTS'
            ]
        ]
    ]);
    
        if (!is_wp_error($child_terms) && !empty($child_terms)) {
            $this->start_lvl($output, $depth, $args);
            foreach ($child_terms as $child_term) {
                $this->add_term_item($output, $child_term, $depth + 1, $args);
            }
            $this->end_lvl($output, $depth, $args);
        }

        $output .= "$indent</li>{$n}";
    }
}

/**
 * Add custom menu metabox for Kursagenten auto-menus
 */
function add_custom_nav_menu_metabox(): void {
    add_meta_box(
        'add-kursagenten-auto-menus',
        'Kursagenten automenyer',
        'kursagenten_auto_menus_metabox',
        'nav-menus',
        'side',
        'default'
    );
}
add_action('admin_init', 'add_custom_nav_menu_metabox');

/**
 * Callback for Kursagenten auto-menus metabox
 */
function kursagenten_auto_menus_metabox(): void {
    $auto_menu_items = [
        'course_categories' => [
            'id' => -1001,
            'label' => 'Kurskategorier',
            'type' => 'coursecategory'
        ],
        'course_locations' => [
            'id' => -1002,
            'label' => 'Kurssteder',
            'type' => 'course_location'
        ],
        'course_instructors' => [
            'id' => -1003,
            'label' => 'Instruktører',
            'type' => 'instructors'
        ]
    ];

    ?>
    <div id="kursagenten-auto-menu" class="posttypediv">
        <div class="tabs-panel tabs-panel-active">
            <ul class="categorychecklist form-no-clear">
                <?php foreach ($auto_menu_items as $key => $item): ?>
                    <li>
                        <label class="menu-item-title">
                            <input type="checkbox" 
                                   class="menu-item-checkbox" 
                                   name="menu-item[<?php echo $item['id']; ?>][menu-item-object-id]" 
                                   value="<?php echo esc_attr($key); ?>">
                            <?php echo esc_html($item['label']); ?>
                        </label>
                        <input type="hidden" 
                               class="menu-item-type" 
                               name="menu-item[<?php echo $item['id']; ?>][menu-item-type]" 
                               value="kursagenten_auto_menu">
                        <input type="hidden" 
                               class="menu-item-object" 
                               name="menu-item[<?php echo $item['id']; ?>][menu-item-object]" 
                               value="kursagenten_auto_menu">
                        <input type="hidden" 
                               class="menu-item-title" 
                               name="menu-item[<?php echo $item['id']; ?>][menu-item-title]" 
                               value="<?php echo esc_attr($item['label']); ?>">
                        <input type="hidden" 
                               class="menu-item-url" 
                               name="menu-item[<?php echo $item['id']; ?>][menu-item-url]" 
                               value="">
                        <input type="hidden" 
                               name="menu-item[<?php echo $item['id']; ?>][menu-item-taxonomy]" 
                               value="<?php echo esc_attr($item['type']); ?>">
                        <input type="hidden" 
                               name="menu-item[<?php echo $item['id']; ?>][menu-item-menu-type]" 
                               value="<?php echo esc_attr($key); ?>">
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <p class="button-controls wp-clearfix">
            <span class="add-to-menu">
                <input type="submit" 
                       class="button-secondary submit-add-to-menu right" 
                       value="<?php esc_attr_e('Legg til i meny'); ?>" 
                       name="add-custom-menu-item" 
                       id="submit-kursagenten-auto-menu">
                <span class="spinner"></span>
            </span>
        </p>
    </div>
    <?php
}

/**
 * Register our custom walker
 */
function kursagenten_edit_nav_menu_walker($walker) {
    return 'Kursagenten_Walker_Nav_Menu_Edit';
}
add_filter('wp_edit_nav_menu_walker', 'kursagenten_edit_nav_menu_walker');

/**
 * Filter the menu items before they are displayed
 */
function kursagenten_setup_auto_menu_items($items, $menu, $args) {
    if (is_admin()) {
        return $items;
    }
    
    if (!is_array($items)) {
        error_log('KRITISK: Menu items er ikke et array i kursagenten_setup_auto_menu_items');
        return $items;
    }

    $new_items = array();
    $id_counter = -9999;
    $auto_menu_items = array();
    
    // Behold ikke-automeny elementer og samle automeny-elementer
    foreach ($items as $item) {
        if ($item->object === 'kursagenten_auto_menu') {
            $taxonomy = get_post_meta($item->ID, '_menu_item_taxonomy', true);
            $auto_menu_items[$item->ID] = array(
                'item' => $item,
                'taxonomy' => $taxonomy
            );
        } elseif ($item->object === 'coursecategory' || 
                  $item->object === 'instructors' || 
                  $item->object === 'course_location') {
            // Skip existing taxonomy items as they will be regenerated
            continue;
        } else {
            $new_items[] = $item;
        }
    }
    
    if (empty($auto_menu_items)) {
        return $items;
    }

    // Prosesser hver automeny separat
    foreach ($auto_menu_items as $auto_menu_id => $auto_menu_data) {
        $auto_menu_item = $auto_menu_data['item'];
        $taxonomy = $auto_menu_data['taxonomy'];
        $parent_term_id = get_post_meta($auto_menu_item->ID, '_menu_item_parent_term', true);
        $original_title = get_post_meta($auto_menu_item->ID, '_menu_item_original_title', true);
        
        // Sjekk om tittelen er en av standardtitlene eller følger standard mønster
        $standard_titles = ['Kurskategorier', 'Instruktører', 'Kurssteder'];
        $standard_patterns = [
            'Kurskategorier \(.*\)',
            'Instruktører \(.*\)',
            'Kurssteder \(.*\)'
        ];
        
        $is_standard_title = in_array($auto_menu_item->title, $standard_titles);
        $has_standard_pattern = false;
        foreach ($standard_patterns as $pattern) {
            if (preg_match('/^' . $pattern . '$/', $auto_menu_item->title)) {
                $has_standard_pattern = true;
                break;
            }
        }
        
        // Oppdater tittelen hvis vi har en parent term
        if (!empty($parent_term_id)) {
            $term = get_term($parent_term_id, $taxonomy);
            if (!is_wp_error($term) && $term) {
                if ($is_standard_title || $has_standard_pattern) {
                    // Hvis det er standard tittel eller følger standard mønster, bruk termnavnet
                    $auto_menu_item->title = $term->name;
                }
                // Hvis ikke standard tittel, behold den tilpassede tittelen
            }
        }

        if (empty($taxonomy)) {
            error_log('KRITISK: Ingen taxonomy funnet for auto-menu item: ' . $auto_menu_item->ID);
            continue;
        }
        
        $new_items[] = $auto_menu_item;
        
        // Modifiser spørringen basert på om vi har en parent_term_id
        $terms_args = [
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => 'hide_in_list',
                    'value' => 'Vis',
                ],
                [
                    'key' => 'hide_in_list',
                    'compare' => 'NOT EXISTS'
                ]
            ],
            'orderby' => 'name',
            'order' => 'ASC'
        ];

        if (!empty($parent_term_id)) {
            // Hvis vi har en parent_term_id, hent bare direkte undertermer
            $terms_args['parent'] = $parent_term_id;
        } else {
            // Hvis ingen parent_term_id, hent toppnivå termer
            $terms_args['parent'] = 0;
        }

        $terms = get_terms($terms_args);

        if (is_wp_error($terms)) {
            error_log('KRITISK: Feil ved henting av terms for ' . $taxonomy . ': ' . $terms->get_error_message());
            continue;
        }
       
        // Lag en mapping mellom term_id og menu item ID
        $term_to_menu_id = array();
        
        // Legg til termer
        foreach ($terms as $term) {
            
            $menu_item = (object) array(
                'ID' => $id_counter,
                'db_id' => $id_counter,
                'menu_item_parent' => $auto_menu_item->ID,
                'object_id' => $term->term_id,
                'post_parent' => 0,
                'type' => 'taxonomy',
                'object' => $taxonomy,
                'type_label' => get_taxonomy($taxonomy)->labels->singular_name,
                'title' => $term->name,
                'url' => get_term_link($term),
                'target' => '',
                'attr_title' => '',
                'description' => '',
                'classes' => array('menu-item', 'menu-item-type-taxonomy', 'menu-item-object-' . $taxonomy),
                'xfn' => '',
                'menu_order' => count($new_items) * 1000,
                'status' => 'publish'
            );
            
            $term_to_menu_id[$term->term_id] = $id_counter;
            $new_items[] = $menu_item;
            $id_counter--;

            // Legg til undertermer bare hvis vi ikke allerede viser undertermer av en hovedterm
            if (empty($parent_term_id)) {
                $child_terms = get_terms([
                    'taxonomy' => $taxonomy,
                    'hide_empty' => false,
                    'parent' => $term->term_id,
                    'meta_query' => [
                        'relation' => 'OR',
                        [
                            'key' => 'hide_in_list',
                            'value' => 'Vis',
                        ],
                        [
                            'key' => 'hide_in_list',
                            'compare' => 'NOT EXISTS'
                        ]
                    ],
                    'orderby' => 'name',
                    'order' => 'ASC'
                ]);

                if (!is_wp_error($child_terms) && !empty($child_terms)) {
                    
                    foreach ($child_terms as $child_term) {
                        
                        $child_item = (object) array(
                            'ID' => $id_counter,
                            'db_id' => $id_counter,
                            'menu_item_parent' => $term_to_menu_id[$term->term_id],
                            'object_id' => $child_term->term_id,
                            'post_parent' => 0,
                            'type' => 'taxonomy',
                            'object' => $taxonomy,
                            'type_label' => get_taxonomy($taxonomy)->labels->singular_name,
                            'title' => $child_term->name,
                            'url' => get_term_link($child_term),
                            'target' => '',
                            'attr_title' => '',
                            'description' => '',
                            'classes' => array('menu-item', 'menu-item-type-taxonomy', 'menu-item-object-' . $taxonomy),
                            'xfn' => '',
                            'menu_order' => count($new_items) * 1000,
                            'status' => 'publish'
                        );
                        
                        $term_to_menu_id[$child_term->term_id] = $id_counter;
                        $new_items[] = $child_item;
                        $id_counter--;
                    }
                }
            }
        }
    }

    return $new_items;
}
add_filter('wp_get_nav_menu_items', 'kursagenten_setup_auto_menu_items', 10, 3);

/**
 * Adds active class to current menu items
 */
add_filter('nav_menu_css_class', function($classes, $item) {
    if (in_array('current-menu-item', $classes)) {
        $classes[] = 'active';
    }
    return $classes;
}, 10, 2);

/**
 * Ensure menu item properties are set correctly when saving
 */
function kursagenten_update_nav_menu_item($menu_id, $menu_item_db_id, $args) {
    if (isset($args['menu-item-object']) && $args['menu-item-object'] === 'kursagenten_auto_menu') {
        // Lagre menu type og taxonomy
        if (isset($args['menu-item-object-id'])) {
            $menu_type = $args['menu-item-object-id'];
            update_post_meta($menu_item_db_id, '_menu_item_menu_type', $menu_type);
            
            // Lagre original tittel
            if (isset($args['menu-item-title'])) {
                update_post_meta($menu_item_db_id, '_menu_item_original_title', $args['menu-item-title']);
            }
            
            $taxonomy = '';
            switch ($menu_type) {
                case 'course_categories':
                    $taxonomy = 'coursecategory';
                    break;
                case 'course_locations':
                    $taxonomy = 'course_location';
                    break;
                case 'course_instructors':
                    $taxonomy = 'instructors';
                    break;
            }
            
            if (!empty($taxonomy)) {
                update_post_meta($menu_item_db_id, '_menu_item_taxonomy', $taxonomy);
            }
        }
    }

    // Håndter parent term oppdatering separat
    if (isset($_POST['menu-item-parent-term']) && isset($_POST['menu-item-parent-term'][$menu_item_db_id])) {
        $parent_term_id = $_POST['menu-item-parent-term'][$menu_item_db_id];
        update_post_meta($menu_item_db_id, '_menu_item_parent_term', $parent_term_id);
    }
}
add_action('wp_update_nav_menu_item', 'kursagenten_update_nav_menu_item', 10, 3);

/**
 * Copy menu item metadata when duplicating menu items
 */
function kursagenten_duplicate_menu_item_meta($new_menu_id, $old_menu_id, $args) {
    $menu_type = get_post_meta($old_menu_id, '_menu_item_menu_type', true);
    $taxonomy = get_post_meta($old_menu_id, '_menu_item_taxonomy', true);
    
    if (!empty($menu_type)) {
        update_post_meta($new_menu_id, '_menu_item_menu_type', $menu_type);
    }
    if (!empty($taxonomy)) {
        update_post_meta($new_menu_id, '_menu_item_taxonomy', $taxonomy);
    }
}
add_action('wp_update_nav_menu_item', 'kursagenten_duplicate_menu_item_meta', 10, 3);
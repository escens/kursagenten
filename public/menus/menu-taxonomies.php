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
            $main_url = get_post_meta($item_id, '_menu_item_main_url', true);
            
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
            $main_url = get_post_meta($item_id, '_menu_item_main_url', true);
            
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
          
            // Legg til URL-felt for hovedmenypunkt
            $fields .= '<p class="description description-wide" style="border-left: 1px solid #d3e1dd; padding: 0 10px 10px;">';
            $fields .= '<label for="edit-menu-item-main-url-' . $item_id . '">';
            $fields .= 'URL for hovedmenypunkt<br />';
            $fields .= '<input type="text" id="edit-menu-item-main-url-' . $item_id . '" ';
            $fields .= 'class="widefat" name="menu-item-main-url[' . $item_id . ']" ';
            $fields .= 'value="' . esc_attr($main_url) . '" placeholder="f.eks. /kurskategorier /kurssteder eller /instruktorer" />';
            $fields .= '</label></p>';
            
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
                $fields .= 'Vis kun menypunkter fra:<br />';
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
    private $current_item_depth = array();
    private $items = array();
    private $depth_cache = array();
    private $processed_parents = array();
    private $current_parent = 0;

    public function __construct() {
        error_log('=== WALKER CONSTRUCTED ===');
        error_log('Walker instance created: ' . get_class($this));
    }

    // Ny metode for å sette menyelementer
    public function set_items($items) {
        error_log('=== SETTING WALKER ITEMS ===');
        error_log('Items count: ' . count($items));
        $this->items = $items;
        $this->sort_menu_items();
    }

    private function sort_menu_items() {
        if (empty($this->items)) {
            error_log('No items to sort');
            return;
        }

        error_log('=== SORTING MENU ITEMS ===');
        error_log('Items before sort: ' . count($this->items));
        
        $sorted_items = array();
        $parent_items = array();
        
        // Først, finn alle hovedmenypunkter (parent = 0)
        foreach ($this->items as $item) {
            if ($item->menu_item_parent == 0) {
                $parent_items[] = $item;
            }
        }

        // Sorter hovedmenypunkter etter menu_order
        usort($parent_items, function($a, $b) {
            return $a->menu_order - $b->menu_order;
        });

        // For hvert hovedmenypunkt, legg til det og dets barn
        foreach ($parent_items as $parent) {
            $sorted_items[] = $parent;
            $this->add_child_items($parent->ID, $sorted_items);
        }

        $this->items = $sorted_items;
        
        error_log('Items after sort: ' . count($this->items));
        foreach ($this->items as $item) {
            error_log(sprintf(
                "Sorted item - ID: %d, Title: %s, Parent: %d, Order: %d",
                $item->ID,
                $item->title,
                $item->menu_item_parent,
                $item->menu_order
            ));
        }
    }

    private function add_child_items($parent_id, &$sorted_items) {
        foreach ($this->items as $item) {
            if ($item->menu_item_parent == $parent_id) {
                $sorted_items[] = $item;
                $this->add_child_items($item->ID, $sorted_items);
            }
        }
    }

    public function start_lvl(&$output, $depth = 0, $args = null) {
        error_log('=== WALKER START_LVL ===');
        error_log('Depth: ' . $depth);
        
        if (isset($args->item_spacing) && 'discard' === $args->item_spacing) {
            $t = '';
            $n = '';
        } else {
            $t = "\t";
            $n = "\n";
        }
        
        $indent = str_repeat($t, $depth);
        $output .= "{$n}{$indent}<ul class=\"sub-menu\">{$n}";
    }

    public function start_el(&$output, $data_object, $depth = 0, $args = null, $current_object_id = 0) {
        error_log('=== WALKER START_EL ===');
        error_log(sprintf(
            "Processing item: %s (ID: %d, Parent: %d, Object: %s)",
            $data_object->title,
            $data_object->ID,
            $data_object->menu_item_parent,
            $data_object->object
        ));
        
        if (isset($args->item_spacing) && 'discard' === $args->item_spacing) {
            $t = '';
            $n = '';
        } else {
            $t = "\t";
            $n = "\n";
        }
        
        $indent = str_repeat($t, $depth);
        
        $classes = empty($data_object->classes) ? array() : (array) $data_object->classes;
        $classes[] = 'menu-item-' . $data_object->ID;
        
        // Sjekk om elementet har undermenyer
        $has_children = false;
        foreach ($this->items as $item) {
            if ($item->menu_item_parent == $data_object->ID) {
                $has_children = true;
                $classes[] = 'menu-item-has-children';
                break;
            }
        }
        
        if ($depth === 0) {
            $classes[] = 'menu-item-level-0';
        }
        
        $class_names = implode(' ', apply_filters('nav_menu_css_class', array_filter($classes), $data_object, $args, $depth));
        $class_names = $class_names ? ' class="' . esc_attr($class_names) . '"' : '';
        
        $id = apply_filters('nav_menu_item_id', 'menu-item-' . $data_object->ID, $data_object, $args, $depth);
        $id = $id ? ' id="' . esc_attr($id) . '"' : '';
        
        $output .= $indent . '<li' . $id . $class_names . '>';
        
        $atts = array();
        $atts['title']  = !empty($data_object->attr_title) ? $data_object->attr_title : '';
        $atts['target'] = !empty($data_object->target) ? $data_object->target : '';
        $atts['rel']    = !empty($data_object->xfn) ? $data_object->xfn : '';
        $atts['href']   = !empty($data_object->url) ? $data_object->url : '#';
        $atts['class']  = 'ct-menu-link';
        
        $atts = apply_filters('nav_menu_link_attributes', $atts, $data_object, $args, $depth);
        
        $attributes = '';
        foreach ($atts as $attr => $value) {
            if (!empty($value)) {
                $value = ('href' === $attr) ? esc_url($value) : esc_attr($value);
                $attributes .= ' ' . $attr . '="' . $value . '"';
            }
        }
        
        $title = apply_filters('the_title', $data_object->title, $data_object->ID);
        $title = apply_filters('nav_menu_item_title', $title, $data_object, $args, $depth);
        
        $item_output = $args->before;
        $item_output .= '<a' . $attributes . '>';
        $item_output .= $args->link_before . $title . $args->link_after;
        $item_output .= '</a>';
        
        // Legg til submeny-indikator hvis elementet har barn
        if ($has_children) {
            $item_output .= '<button class="ct-toggle-dropdown-desktop-ghost" aria-label="Utvid ' 
                        . esc_attr($title) . '" aria-haspopup="true" aria-expanded="false"></button>';
        }
        
        $item_output .= $args->after;
        
        $output .= apply_filters('walker_nav_menu_start_el', $item_output, $data_object, $depth, $args);
        
        // Hvis dette er et hovedmenypunkt med barn, start submeny
        if ($has_children && $depth === 0) {
            $this->start_lvl($output, $depth, $args);
            
            // Prosesser barn
            foreach ($this->items as $child) {
                if ($child->menu_item_parent == $data_object->ID) {
                    $this->start_el($output, $child, $depth + 1, $args);
                    $this->end_el($output, $child, $depth + 1, $args);
                }
            }
            
            $this->end_lvl($output, $depth, $args);
        }
    }

    public function end_lvl(&$output, $depth = 0, $args = null) {
        if (isset($args->item_spacing) && 'discard' === $args->item_spacing) {
            $t = '';
            $n = '';
        } else {
            $t = "\t";
            $n = "\n";
        }
        
        $indent = str_repeat($t, $depth);
        $output .= "$indent</ul>{$n}";
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
    error_log('=== AUTO MENU ITEMS FILTER STARTING ===');
    error_log('Menu ID: ' . $menu->term_id);
    error_log('Original items count: ' . count($items));
    
    // Bruk en closure for loggefunksjonen
    $log_menu_structure = function($items) {
        $structure = array();
        $lookup = array();
        
        // Først, bygg opp en lookup-tabell
        foreach ($items as $item) {
            $lookup[$item->ID] = $item;
            if (!isset($structure[$item->menu_item_parent])) {
                $structure[$item->menu_item_parent] = array();
            }
            $structure[$item->menu_item_parent][] = $item;
        }
        
        // Bruk en closure for den rekursive funksjonen
        $print_menu_items = function($parent_id, $structure, $lookup, $depth = 0) use (&$print_menu_items) {
            if (!isset($structure[$parent_id])) {
                return;
            }
            
            foreach ($structure[$parent_id] as $item) {
                $indent = str_repeat('  ', $depth);
                error_log(sprintf(
                    "%s- [%d] %s (Parent: %d, Order: %d, Type: %s, Object: %s)",
                    $indent,
                    $item->ID,
                    $item->title,
                    $item->menu_item_parent,
                    $item->menu_order,
                    $item->type,
                    $item->object
                ));
                
                if (isset($structure[$item->ID])) {
                    $print_menu_items($item->ID, $structure, $lookup, $depth + 1);
                }
            }
        };
        
        error_log('=== MENYSTRUKTUR START ===');
        $print_menu_items(0, $structure, $lookup);
        error_log('=== MENYSTRUKTUR SLUTT ===');
    };
    
    // Logg original menystruktur
    error_log('Original menystruktur:');
    $log_menu_structure($items);

    $new_items = array();
    $base_id = 90000;
    $term_counter = 0;
    $original_order = array();
    
    // Behold alle originale items først
    foreach ($items as $item) {
        if (!in_array($item->object, ['coursecategory', 'instructors', 'course_location'])) {
            $new_items[] = $item;
        }
    }

    // Prosesser auto-menyer
    foreach ($items as $original_item) {
        if ($original_item->object === 'kursagenten_auto_menu') {
            $item_position = array_search($original_item, $new_items);
            if ($item_position === false) {
                $item_position = count($new_items);
                $new_items[] = $original_item;
            }
            
            $taxonomy = get_post_meta($original_item->ID, '_menu_item_taxonomy', true);
            $parent_term_id = get_post_meta($original_item->ID, '_menu_item_parent_term', true);
            
            // Hent terms med korrekt hierarki
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
                'parent' => !empty($parent_term_id) ? $parent_term_id : 0,
                'hierarchical' => true
            ];

            $terms = get_terms($terms_args);
            
            if (!is_wp_error($terms) && !empty($terms)) {
                $term_items = array();
                
                // Funksjon for å legge til term og dens barn rekursivt
                $add_term_and_children = function($term, $parent_menu_id) use (&$term_items, &$term_counter, &$base_id, $taxonomy, $original_item, &$add_term_and_children) {
                    $current_menu_id = $base_id + $term_counter;
                    
                    // Opprett menypunkt for gjeldende term
                    $menu_item = new stdClass();
                    foreach (get_object_vars($original_item) as $key => $value) {
                        $menu_item->$key = $value;
                    }
                    
                    $menu_item->ID = $current_menu_id;
                    $menu_item->db_id = $current_menu_id;
                    $menu_item->menu_item_parent = $parent_menu_id;
                    $menu_item->object_id = $term->term_id;
                    $menu_item->object = $taxonomy;
                    $menu_item->type = 'taxonomy';
                    $menu_item->title = $term->name;
                    $menu_item->url = get_term_link($term);
                    $menu_item->classes = array(
                        'menu-item',
                        'menu-item-type-taxonomy',
                        'menu-item-object-' . $taxonomy,
                        'menu-item-' . $current_menu_id
                    );
                    $menu_item->menu_order = $original_item->menu_order + $term_counter;
                    
                    // Hent barnetermer
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
                        ]
                    ]);
                    
                    if (!is_wp_error($child_terms) && !empty($child_terms)) {
                        $menu_item->classes[] = 'menu-item-has-children'; 
                    }
                    
                    $term_items[] = $menu_item;
                    $term_counter++;
                    
                    // Rekursivt legg til barnetermer
                    if (!is_wp_error($child_terms) && !empty($child_terms)) {
                        foreach ($child_terms as $child_term) {
                            $add_term_and_children($child_term, $current_menu_id);
                        }
                    }
                };
                
                // Start rekursiv prosess for hver hovedterm
                foreach ($terms as $term) {
                    $add_term_and_children($term, $original_item->ID);
                }
                
                // Sett inn alle term items etter hovedmenypunktet
                array_splice($new_items, $item_position + 1, 0, $term_items);
            }
        }
    }
    
    // Sorter menyen basert på menu_order og hierarki
    usort($new_items, function($a, $b) {
        if ($a->menu_order == $b->menu_order) {
            // Hvis samme menu_order, sorter på parent
            return $a->menu_item_parent - $b->menu_item_parent;
        }
        return $a->menu_order - $b->menu_order;
    });
    
    // Logg den endelige menystrukturen
    error_log('Endelig menystruktur:');
    $log_menu_structure($new_items);
    
    // Oppdater menu_item_parent for å sikre riktig hierarki
    foreach ($new_items as $item) {
        if (isset($item->menu_item_parent) && $item->menu_item_parent > 0) {
            // Sjekk om parent eksisterer i nye items
            $parent_exists = false;
            foreach ($new_items as $potential_parent) {
                if ($potential_parent->ID == $item->menu_item_parent) {
                    $parent_exists = true;
                    break;
                }
            }
            
            // Hvis parent ikke finnes, sett parent til 0
            if (!$parent_exists) {
                $item->menu_item_parent = 0;
            }
        }
    }

    error_log('=== AUTO MENU ITEMS FILTER FINISHED ===');
    error_log('Final items count: ' . count($new_items));
    foreach ($new_items as $item) {
        error_log(sprintf(
            "Final menu item - ID: %d, Title: %s, Parent: %d, Type: %s",
            $item->ID,
            $item->title,
            $item->menu_item_parent,
            $item->object
        ));
    }
    
    return $new_items;
}
add_filter('wp_get_nav_menu_items', 'kursagenten_setup_auto_menu_items', 5, 3);

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

        // Lagre hovedmenypunkt URL og parent term
        if (isset($_POST['menu-item-main-url'][$menu_item_db_id])) {
            $main_url = sanitize_text_field($_POST['menu-item-main-url'][$menu_item_db_id]);
            update_post_meta($menu_item_db_id, '_menu_item_main_url', $main_url);
        }
        
        if (isset($_POST['menu-item-parent-term'][$menu_item_db_id])) {
            $parent_term_id = absint($_POST['menu-item-parent-term'][$menu_item_db_id]);
            if ($parent_term_id > 0) {
                update_post_meta($menu_item_db_id, '_menu_item_parent_term', $parent_term_id);
            } else {
                delete_post_meta($menu_item_db_id, '_menu_item_parent_term');
            }
        }
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

add_filter('wp_nav_menu_args', function($args) {
    $args['menu_cache_key'] = time(); // Eller en annen unik verdi
    return $args;
});

// Debug: Sjekk om filteret er registrert
add_action('init', function() {
    error_log('=== SJEKKER FILTER REGISTRERING ===');
    error_log('Context: ' . (is_admin() ? 'Admin' : 'Frontend'));
    error_log('Request URI: ' . $_SERVER['REQUEST_URI']);
    error_log('Har wp_get_nav_menu_items filter: ' . (has_filter('wp_get_nav_menu_items', 'kursagenten_setup_auto_menu_items') ? 'Ja' : 'Nei'));
});

// Legg til en ekstra sjekk for å se når menyen hentes
add_action('wp', function() {
    if (!is_admin()) {
        error_log('=== FRONTEND MENY SETUP ===');
        $locations = get_nav_menu_locations();
        error_log('Meny lokasjoner: ' . print_r($locations, true));
        
        // Tøm menycache
        wp_cache_delete('wp_get_nav_menu_items', 'nav_menu_items');
        foreach ($locations as $location => $menu_id) {
            wp_cache_delete($menu_id, 'nav_menu_items');
        }
        error_log('Tømte meny cache');
    }
});

// Prøv å tvinge oppdatering av menycache
add_action('wp_loaded', function() {
    if (!is_admin()) {
        wp_cache_delete('wp_get_nav_menu_items', 'nav_menu_items');
        error_log('Tømte nav_menu_items cache');
    }
});

// Legg til en hook for å oppdatere menyen når den lagres
add_action('wp_update_nav_menu', function($menu_id) {
    error_log('=== MENY OPPDATERT ===');
    error_log('Menu ID: ' . $menu_id);
    wp_cache_delete('wp_get_nav_menu_items', 'nav_menu_items');
    wp_cache_delete($menu_id, 'nav_menu_items');
});

// Prøv å tvinge ny versjon av menyen
add_filter('wp_nav_menu_args', function($args) {
    $args['menu_cache_key'] = time();
    error_log('La til menu_cache_key: ' . $args['menu_cache_key']);
    return $args;
});

// Oppdater ensure_menu_item_taxonomy funksjonen
function ensure_menu_item_taxonomy($menu_id) {
    error_log('=== ENSURE MENU ITEM TAXONOMY ===');
    error_log('Menu ID: ' . $menu_id);
    
    // Hent alle meny items for denne menyen
    $menu_items = wp_get_nav_menu_items($menu_id);
    
    if (!is_array($menu_items)) {
        error_log('Ingen meny items funnet');
        return;
    }
    
    foreach ($menu_items as $item) {
        if ($item->object === 'kursagenten_auto_menu') {
            $menu_type = get_post_meta($item->ID, '_menu_item_menu_type', true);
            $current_taxonomy = get_post_meta($item->ID, '_menu_item_taxonomy', true);
            
            error_log("Sjekker menu item {$item->ID}: Type: {$menu_type}, Current Taxonomy: {$current_taxonomy}");
            
            if (empty($current_taxonomy)) {
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
                    update_post_meta($item->ID, '_menu_item_taxonomy', $taxonomy);
                    error_log("Oppdaterte taxonomy til {$taxonomy} for menu item {$item->ID}");
                }
            }
        }
    }
}

// Oppdater hooken til å bare bruke menu_id
add_action('wp_update_nav_menu', 'ensure_menu_item_taxonomy', 10, 1);

// Legg til en ekstra hook for når nye menypunkter legges til
function ensure_new_menu_item_taxonomy($menu_id, $menu_item_db_id, $args) {
    error_log('=== ENSURE NEW MENU ITEM TAXONOMY ===');
    error_log("Menu ID: {$menu_id}, Item ID: {$menu_item_db_id}");
    
    if (isset($args['menu-item-object']) && $args['menu-item-object'] === 'kursagenten_auto_menu') {
        $menu_type = isset($args['menu-item-object-id']) ? $args['menu-item-object-id'] : '';
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
            error_log("Satt initial taxonomy til {$taxonomy} for nytt menu item {$menu_item_db_id}");
        }
    }
}
add_action('wp_update_nav_menu_item', 'ensure_new_menu_item_taxonomy', 10, 3);

// Legg til denne testen rett etter wp_cache_flush
add_action('wp', function() {
    if (!is_admin()) {
        error_log('=== TESTING MENY FILTER ===');
        $menu_id = 115; // Din meny ID
        $original_items = wp_get_nav_menu_items($menu_id);
        error_log('Antall menypunkter: ' . ($original_items ? count($original_items) : 0));
        
        if ($original_items) {
            foreach ($original_items as $item) {
                error_log(sprintf(
                    "Menypunkt - ID: %d, Title: %s, Type: %s",
                    $item->ID,
                    $item->title,
                    $item->object
                ));
            }
        }
    }
});

// Modifiser kursagenten_set_custom_walker for å logge menyelementene
function kursagenten_set_custom_walker($args) {
    error_log('=== SETTING CUSTOM WALKER ===');
    
    // Opprett walker-instansen
    $walker = new Kursagenten_Walker_Nav_Menu();
    
    // Hent menyelementene direkte fra filteret
    if (isset($args['menu'])) {
        $menu_items = wp_get_nav_menu_items($args['menu']);
        if ($menu_items) {
            // Kjør menyelementene gjennom auto-menu filteret
            $menu_items = kursagenten_setup_auto_menu_items($menu_items, get_term($args['menu']), $args);
            // Sett de behandlede elementene i walkeren
            $walker->set_items($menu_items);
        }
    }
    
    $args['walker'] = $walker;
    return $args;
}
add_filter('wp_nav_menu_args', 'kursagenten_set_custom_walker', 10);

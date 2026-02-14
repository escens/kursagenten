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
                'Kategorier og kurs',
                'Instruktører',
                'Kurssteder'
            ];
            
            // Sjekk om tittelen følger standard mønster med parentes
            $standard_patterns = [
                'Kurskategorier \(.*\)',
                'Kategorier og kurs \(.*\)',
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
            
            // Hvis vi har en parent term (skip for 'sub' which is a special value)
            if (!empty($parent_term_id) && $parent_term_id !== 'sub' && is_numeric($parent_term_id)) {
                $term = get_term((int) $parent_term_id, $taxonomy);
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
            $menu_type = get_post_meta($item_id, '_menu_item_menu_type', true);
            $parent_term_id = get_post_meta($item_id, '_menu_item_parent_term', true);
            $st_filter = get_post_meta($item_id, '_menu_item_st_filter', true);
            $skjul_sted_chip = get_post_meta($item_id, '_menu_item_skjul_sted_chip', true);
            $vis_kun_kurs = get_post_meta($item_id, '_menu_item_vis_kun_kurs', true) === '1';
            
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
                        'key' => 'hide_in_menu',
                        'compare' => 'NOT EXISTS'
                    ],
                    [
                        'key' => 'hide_in_menu',
                        'value' => 'Vis',
                        'compare' => '='
                    ]
                ]
            ]);

            $fields = '';

            // Dropdown for ka_coursecategory: Vis hovedkategorier, Vis subkategorier, eller velg spesifikk undermeny
            if ($taxonomy === 'ka_coursecategory') {
                $hide_in_menu_meta = [
                    'relation' => 'OR',
                    ['key' => 'hide_in_menu', 'compare' => 'NOT EXISTS'],
                    ['key' => 'hide_in_menu', 'value' => 'Vis', 'compare' => '=']
                ];
                $has_children_terms = array_filter($parent_terms, function($term) use ($taxonomy, $hide_in_menu_meta) {
                    $children = get_terms([
                        'taxonomy' => $taxonomy,
                        'hide_empty' => false,
                        'parent' => $term->term_id,
                        'meta_query' => $hide_in_menu_meta
                    ]);
                    return !empty($children) && !is_wp_error($children);
                });

                $fields .= '<p class="description description-wide" style="border-left: 1px solid #d3e1dd; padding: 0 10px 10px;">';
                $fields .= '<label for="edit-menu-item-parent-term-' . $item_id . '">';
                $fields .= 'Vis menypunkter:<br />';
                $fields .= '<select id="edit-menu-item-parent-term-' . $item_id . '" ';
                $fields .= 'name="menu-item-parent-term[' . $item_id . ']" class="widefat">';
                $fields .= '<option value=""' . selected($parent_term_id, '', false) . '>Vis hovedkategorier</option>';
                $fields .= '<option value="sub"' . selected($parent_term_id, 'sub', false) . '>Vis subkategorier</option>';
                if (!empty($has_children_terms)) {
                    foreach ($has_children_terms as $term) {
                        $selected = $parent_term_id == $term->term_id ? ' selected="selected"' : '';
                        $fields .= '<option value="' . esc_attr($term->term_id) . '"' . $selected . '>';
                        $fields .= esc_html($term->name);
                        $fields .= '</option>';
                    }
                }
                $fields .= '</select>';
                $fields .= '</label></p>';
            }

            // For Kategorier og kurs: avkrysningsvalg for å vise kun kurs
            if ($menu_type === 'course_categories_and_courses') {
                $fields .= '<p class="description description-wide" style="border-left: 1px solid #d3e1dd; padding: 0 10px 10px;">';
                $fields .= '<label>';
                $fields .= '<input type="checkbox" name="menu-item-vis-kun-kurs[' . $item_id . ']" value="1" ' . checked($vis_kun_kurs, true, false) . ' /> ';
                $fields .= 'Vis kun kurs, ikke kategorier';
                $fields .= '</label>';
                $fields .= '<br><small>For kurstilbydere med få kurs og 1/ingen kategorier: viser kun en flat liste med hovedkurs uten kategorinivå.</small>';
                $fields .= '</p>';
            }

            // Kun for Kurskategorier: stedsfilter og skjul sted-chip
            if ($taxonomy === 'ka_coursecategory') {
                $fields .= '<p class="description description-wide" style="border-left: 1px solid #d3e1dd; padding: 0 10px 10px;">';
                $fields .= '<label for="edit-menu-item-st-filter-' . $item_id . '">';
                $fields .= 'Vis kun kategorier med kurs på angitt sted (valgfritt)<br />';
                $fields .= '<input type="text" id="edit-menu-item-st-filter-' . $item_id . '" ';
                $fields .= 'class="widefat" name="menu-item-st-filter[' . $item_id . ']" ';
                $fields .= 'value="' . esc_attr($st_filter) . '" placeholder="f.eks. oslo eller ikke-oslo" />';
                $fields .= '</label>';
                $fields .= '<br><small>Begrenser til termer med kurs på angitt sted. Start med "ikke-" for å ekskludere. La stå tom for alle.</small>';
                $fields .= '</p>';

                $fields .= '<p class="description description-wide" style="border-left: 1px solid #d3e1dd; padding: 0 10px 10px;">';
                $fields .= '<label>';
                $fields .= '<input type="checkbox" name="menu-item-skjul-sted-chip[' . $item_id . ']" value="1" ' . checked($skjul_sted_chip, '1', false) . ' /> ';
                $fields .= 'Skjul stedsfilter i videre visning';
                $fields .= '</label>';
                $fields .= '<br><small>Kun for positiv filter (f.eks. nettbasert): skjuler stedsfilteret på kurslistesiden. Ved "ikke-"-filter vises filteret slik at bruker kan velge annet sted.</small>';
                $fields .= '</p>';
            }

            // Hidden field so menu_type persists when saving (WordPress nav menu expects menu-item[ID][menu-item-object-id])
            if (!empty($menu_type)) {
                $fields .= '<input type="hidden" name="menu-item[' . $item_id . '][menu-item-object-id]" value="' . esc_attr($menu_type) . '" />';
            }

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
    private $items = array();

    public function set_items($items) {
        $this->items = $items;
        $this->sort_menu_items();
    }

    private function sort_menu_items() {
        if (empty($this->items)) {
            return;
        }
        
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
 * Extract custom CSS classes from a menu item (user-added in admin "CSS Classes" field).
 * WordPress standard classes (menu-item, menu-item-type-*, menu-item-object-*, menu-item-{id})
 * are filtered out so they can be replaced with correct values for virtual items.
 *
 * @param object $item Nav menu item (page, post, kursagenten_auto_menu, etc.)
 * @return array Custom class names to merge into virtual menu items
 */
function kursagenten_get_custom_menu_classes_from_original(object $item): array {
    $classes = isset($item->classes) && is_array($item->classes)
        ? $item->classes
        : [];
    $custom = [];
    foreach ($classes as $class) {
        $class = trim((string) $class);
        if ($class === '') {
            continue;
        }
        // Skip WordPress standard menu item classes (we set these ourselves for virtual items)
        if ($class === 'menu-item'
            || strpos($class, 'menu-item-type-') === 0
            || strpos($class, 'menu-item-object-') === 0
            || preg_match('/^menu-item-\d+$/', $class)
        ) {
            continue;
        }
        $custom[] = $class;
    }
    return array_unique(array_filter($custom));
}

/**
 * Get custom CSS classes to inherit for virtual menu items.
 * When inject_only: inherit from parent (e.g. page with "featured-menu").
 * Otherwise: inherit from the automeny item itself.
 *
 * @param array $new_items Current menu items (to find parent)
 * @param int $parent_menu_id Parent item ID when inject_only
 * @param bool $inject_only Whether categories are injected under a parent
 * @param object $original_item The kursagenten_auto_menu item
 * @return array Custom class names to merge into virtual menu items
 */
function kursagenten_get_custom_classes_for_virtual_items(
    array $new_items,
    int $parent_menu_id,
    bool $inject_only,
    object $original_item
): array {
    if ($inject_only && $parent_menu_id > 0) {
        foreach ($new_items as $ni) {
            if (isset($ni->ID) && (int) $ni->ID === (int) $parent_menu_id) {
                return kursagenten_get_custom_menu_classes_from_original($ni);
            }
        }
    }
    return kursagenten_get_custom_menu_classes_from_original($original_item);
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
            'type' => 'ka_coursecategory'
        ],
        'course_categories_and_courses' => [
            'id' => -1004,
            'label' => 'Kategorier og kurs',
            'type' => 'ka_coursecategory'
        ],
        'course_locations' => [
            'id' => -1002,
            'label' => 'Kurssteder',
            'type' => 'ka_course_location'
        ],
        'course_instructors' => [
            'id' => -1003,
            'label' => 'Instruktører',
            'type' => 'ka_instructors'
        ]
    ];

    ?>
    <div id="kursagenten-auto-menu" class="posttypediv">
        <p class="description" style="margin-bottom: 12px;"><?php esc_html_e('Legg automenyer under et hovedmenypunkt (f.eks. siden Kurs). Brukt som hovedmenypunkt vil toppnivået være uten link.', 'kursagenten'); ?></p>
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
 * Get main courses (hovedkurs) in a category - ka_course with ka_is_parent_course = 'yes'
 *
 * @param int $term_id Category term ID
 * @param array $term_ids_including_children All term IDs (category + descendants) for tax_query
 * @param int|null $filter_location_term_id Optional location filter
 * @param bool $exclude_location Whether to exclude (true) or include (false) the location
 * @return array WP_Post[] Main courses
 */
function kursagenten_get_main_courses_in_category(
    int $term_id,
    array $term_ids_including_children,
    ?int $filter_location_term_id = null,
    bool $exclude_location = false
): array {
    $tax_query = [
        [
            'taxonomy' => 'ka_coursecategory',
            'field' => 'term_id',
            'terms' => $term_ids_including_children,
        ]
    ];
    if ($filter_location_term_id !== null) {
        $tax_query[] = [
            'taxonomy' => 'ka_course_location',
            'field' => 'term_id',
            'terms' => [$filter_location_term_id],
            'operator' => $exclude_location ? 'NOT IN' : 'IN',
        ];
    }

    $query_args = [
        'post_type' => 'ka_course',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
        'tax_query' => $tax_query,
        'meta_query' => [
            [
                'key' => 'ka_is_parent_course',
                'value' => 'yes',
                'compare' => '='
            ]
        ],
        'no_found_rows' => true,
    ];

    $query = new WP_Query($query_args);
    return $query->posts ?: [];
}

/**
 * Inject "Kategorier og kurs" or "Vis kun kurs" menu structure
 *
 * @param array $manual_item_custom_classes Map [taxonomy][object_id] => custom classes from manually added siblings
 */
function kursagenten_inject_categories_and_courses_menu(
    array &$new_items,
    object $original_item,
    int $splice_position,
    int $parent_menu_id,
    bool $inject_only,
    $item_position,  // int|false from array_search
    $parent_term_id,
    string $main_url,
    string $st_filter,
    bool $skjul_sted_chip,
    bool $vis_kun_kurs,
    bool $vis_kun_subkategorier = false,
    array $manual_item_custom_classes = []
): void {
    $taxonomy = 'ka_coursecategory';

    // Inherit custom CSS classes from parent when inject_only (e.g. page with "featured-menu")
    $custom_classes = kursagenten_get_custom_classes_for_virtual_items(
        $new_items,
        $parent_menu_id,
        $inject_only,
        $original_item
    );

    // Parse st-filter
    $filter_location_term_id = null;
    $exclude_location = false;
    if (!empty($st_filter)) {
        $neg_prefix = 'ikke-';
        if (stripos($st_filter, $neg_prefix) === 0) {
            $exclude_location = true;
            $st_slug = sanitize_title(substr($st_filter, strlen($neg_prefix)));
        } else {
            $st_slug = sanitize_title($st_filter);
        }
        $loc_term = get_term_by('slug', $st_slug, 'ka_course_location');
        if ($loc_term && !is_wp_error($loc_term)) {
            $filter_location_term_id = (int) $loc_term->term_id;
        }
    }

    $append_st = !empty($st_filter) ? $st_filter : '';
    $append_sc = ($skjul_sted_chip && !empty($st_filter) && stripos($st_filter, 'ikke-') !== 0) ? '0' : '';

    if ($vis_kun_kurs) {
        // Flat list: main courses only, no categories
        if ($inject_only && $item_position !== false) {
            array_splice($new_items, $item_position, 1);
            if ($splice_position > $item_position) {
                $splice_position--;
            }
        }
        $hide_in_menu_meta = [
            'relation' => 'OR',
            ['key' => 'hide_in_menu', 'compare' => 'NOT EXISTS'],
            ['key' => 'hide_in_menu', 'value' => 'Vis', 'compare' => '=']
        ];
        $term_ids = [0];
        if (!empty($parent_term_id)) {
            $parent_id = (int) $parent_term_id;
            $parent_hide = get_term_meta($parent_id, 'hide_in_menu', true);
            $child_terms = get_terms([
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
                'parent' => $parent_id,
                'meta_query' => $hide_in_menu_meta,
            ]);
            $term_ids = ($parent_hide !== 'Skjul') ? [$parent_id] : [];
            $term_ids = array_merge($term_ids, wp_list_pluck($child_terms, 'term_id'));
        } else {
            $top_terms = get_terms([
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
                'parent' => 0,
                'meta_query' => $hide_in_menu_meta,
            ]);
            if (!is_wp_error($top_terms)) {
                $term_ids = wp_list_pluck($top_terms, 'term_id');
                foreach ($top_terms as $t) {
                    $sub = get_terms([
                        'taxonomy' => $taxonomy,
                        'hide_empty' => false,
                        'parent' => $t->term_id,
                        'meta_query' => $hide_in_menu_meta,
                    ]);
                    if (!is_wp_error($sub)) {
                        $term_ids = array_merge($term_ids, wp_list_pluck($sub, 'term_id'));
                    }
                }
            }
        }
        $term_ids = array_unique(array_filter($term_ids));

        $tax_query = [['taxonomy' => 'ka_coursecategory', 'field' => 'term_id', 'terms' => $term_ids]];
        if ($filter_location_term_id !== null) {
            $tax_query[] = [
                'taxonomy' => 'ka_course_location',
                'field' => 'term_id',
                'terms' => [$filter_location_term_id],
                'operator' => $exclude_location ? 'NOT IN' : 'IN',
            ];
        }

        $courses = get_posts([
            'post_type' => 'ka_course',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'tax_query' => $tax_query,
            'meta_query' => [['key' => 'ka_is_parent_course', 'value' => 'yes', 'compare' => '=']],
        ]);

        if (empty($courses)) {
            return;
        }

        if (!$inject_only) {
            $found = false;
            foreach ($new_items as $ni) {
                if (isset($ni->ID) && $ni->ID === $original_item->ID) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $new_items[] = $original_item;
            }
            if (!empty($main_url)) {
                $url = (strpos($main_url, '/') === 0 && strpos($main_url, '//') !== 0) ? home_url($main_url) : $main_url;
                $original_item->url = $url;
            }
            if (empty($original_item->title)) {
                $original_item->title = __('Kategorier og kurs', 'kursagenten');
            }
        }

        $course_items = [];
        $base_id = 90000 + ((int) $original_item->ID * 1000);
        foreach ($courses as $idx => $course) {
            $menu_item = new stdClass();
            foreach (get_object_vars($original_item) as $key => $value) {
                if ($key === 'label') continue;
                $menu_item->$key = $value;
            }
            $cid = $base_id + $idx;
            $menu_item->ID = $cid;
            $menu_item->db_id = $cid;
            $menu_item->menu_item_parent = $parent_menu_id;
            $menu_item->object_id = $course->ID;
            $menu_item->object = 'ka_course';
            $menu_item->type = 'post_type';
            $menu_item->title = $course->post_title;
            $menu_item->label = '';
            $menu_item->post_title = $course->post_title;
            $menu_item->type_label = __('Kurs', 'kursagenten');
            $permalink = get_permalink($course);
            if (!empty($append_st)) {
                $permalink = add_query_arg('st', $append_st, $permalink);
            }
            if ($append_sc !== '') {
                $permalink = add_query_arg('sc', $append_sc, $permalink);
            }
            $menu_item->url = $permalink;
            $menu_item->classes = array_merge(
                ['menu-item', 'menu-item-type-post_type', 'menu-item-object-ka_course'],
                $custom_classes
            );
            $menu_item->menu_order = 0;
            $course_items[] = $menu_item;
        }

        array_splice($new_items, $splice_position, 0, $course_items);
        return;
    }

    // Categories with main courses as children
    if ($inject_only && $item_position !== false) {
        array_splice($new_items, $item_position, 1);
        if ($splice_position > $item_position) {
            $splice_position--;
        }
    }

    $meta_query = [
        'relation' => 'OR',
        ['key' => 'hide_in_menu', 'compare' => 'NOT EXISTS'],
        ['key' => 'hide_in_menu', 'value' => 'Vis', 'compare' => '=']
    ];

    if ($vis_kun_subkategorier) {
        // Show only subcategories (skip top-level): get children of parent_term_id or all top-level terms
        if (!empty($parent_term_id)) {
            $terms = get_terms([
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
                'parent' => (int) $parent_term_id,
                'orderby' => 'name',
                'meta_query' => $meta_query,
            ]);
        } else {
            $top_terms = get_terms([
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
                'parent' => 0,
                'orderby' => 'name',
                'meta_query' => $meta_query,
            ]);
            $terms = [];
            if (!is_wp_error($top_terms)) {
                foreach ($top_terms as $top_term) {
                    $children = get_terms([
                        'taxonomy' => $taxonomy,
                        'hide_empty' => false,
                        'parent' => (int) $top_term->term_id,
                        'orderby' => 'name',
                        'meta_query' => $meta_query,
                    ]);
                    if (!is_wp_error($children)) {
                        $terms = array_merge($terms, $children);
                    }
                }
            }
        }
    } else {
        $terms = get_terms([
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'parent' => !empty($parent_term_id) ? (int) $parent_term_id : 0,
            'orderby' => 'name',
            'meta_query' => $meta_query,
        ]);
    }
    if (is_wp_error($terms) || empty($terms)) {
        return;
    }

    if (function_exists('ka_filter_terms_with_published_courses')) {
        $terms = ka_filter_terms_with_published_courses($terms, $taxonomy);
    }
    if ($filter_location_term_id !== null && function_exists('ka_filter_terms_with_published_courses_by_location')) {
        $terms = ka_filter_terms_with_published_courses_by_location($terms, $taxonomy, $filter_location_term_id, $exclude_location);
    }

    if (!$inject_only) {
        $found = false;
        foreach ($new_items as $ni) {
            if (isset($ni->ID) && $ni->ID === $original_item->ID) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $new_items[] = $original_item;
        }
        if (!empty($main_url)) {
            $url = (strpos($main_url, '/') === 0 && strpos($main_url, '//') !== 0) ? home_url($main_url) : $main_url;
            $original_item->url = $url;
        }
        if (empty($original_item->title)) {
            $original_item->title = __('Kategorier og kurs', 'kursagenten');
        }
    }

    $term_items = [];
    $term_counter = 0;
    $base_id = 90000 + ((int) $original_item->ID * 1000);

    foreach ($terms as $term) {
        $term_ids_for_courses = [$term->term_id];
        $child_terms = get_terms([
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'parent' => $term->term_id,
            'meta_query' => [
                'relation' => 'OR',
                ['key' => 'hide_in_menu', 'compare' => 'NOT EXISTS'],
                ['key' => 'hide_in_menu', 'value' => 'Vis', 'compare' => '=']
            ]
        ]);
        if (!is_wp_error($child_terms)) {
            $term_ids_for_courses = array_merge($term_ids_for_courses, wp_list_pluck($child_terms, 'term_id'));
        }

        $courses_in_cat = kursagenten_get_main_courses_in_category(
            (int) $term->term_id,
            $term_ids_for_courses,
            $filter_location_term_id,
            $exclude_location
        );

        if (empty($courses_in_cat)) {
            continue;
        }

        $current_menu_id = $base_id + $term_counter;
        $term_link = get_term_link($term);
        if (is_wp_error($term_link)) {
            $term_link = '#';
        }
        if (!empty($append_st)) {
            $term_link = add_query_arg('st', $append_st, $term_link);
        }
        if ($append_sc !== '') {
            $term_link = add_query_arg('sc', $append_sc, $term_link);
        }

        $menu_item = new stdClass();
        foreach (get_object_vars($original_item) as $key => $value) {
            if ($key === 'label') continue;
            $menu_item->$key = $value;
        }
        $menu_item->ID = $current_menu_id;
        $menu_item->db_id = $current_menu_id;
        $menu_item->menu_item_parent = $parent_menu_id;
        $menu_item->object_id = $term->term_id;
        $menu_item->object = $taxonomy;
        $menu_item->type = 'taxonomy';
        $menu_item->title = $term->name;
        $menu_item->label = '';
        $menu_item->post_title = $term->name;
        $menu_item->type_label = get_taxonomy($taxonomy)->labels->singular_name ?? $taxonomy;
        $menu_item->url = $term_link;
        $term_custom = $custom_classes;
        if (!empty($manual_item_custom_classes[$taxonomy][$term->term_id])) {
            $term_custom = array_merge($term_custom, $manual_item_custom_classes[$taxonomy][$term->term_id]);
        }
        $menu_item->classes = array_merge(
            ['menu-item', 'menu-item-type-taxonomy', 'menu-item-object-' . $taxonomy, 'menu-item-has-children'],
            $term_custom
        );
        $menu_item->menu_order = 0;
        $term_items[] = $menu_item;
        $term_counter++;

        foreach ($courses_in_cat as $idx => $course) {
            $cid = $base_id + $term_counter;
            $course_item = new stdClass();
            foreach (get_object_vars($original_item) as $key => $value) {
                if ($key === 'label') continue;
                $course_item->$key = $value;
            }
            $course_item->ID = $cid;
            $course_item->db_id = $cid;
            $course_item->menu_item_parent = $current_menu_id;
            $course_item->object_id = $course->ID;
            $course_item->object = 'ka_course';
            $course_item->type = 'post_type';
            $course_item->title = $course->post_title;
            $course_item->label = '';
            $course_item->post_title = $course->post_title;
            $course_item->type_label = __('Kurs', 'kursagenten');
            $permalink = get_permalink($course);
            if (!empty($append_st)) {
                $permalink = add_query_arg('st', $append_st, $permalink);
            }
            if ($append_sc !== '') {
                $permalink = add_query_arg('sc', $append_sc, $permalink);
            }
            $course_item->url = $permalink;
            $course_item->classes = array_merge(
                ['menu-item', 'menu-item-type-post_type', 'menu-item-object-ka_course'],
                $custom_classes
            );
            $course_item->menu_order = 0;
            $term_items[] = $course_item;
            $term_counter++;
        }
    }

    array_splice($new_items, $splice_position, 0, $term_items);
}

/**
 * Check if Max Mega Menu or similar menu plugin is active (uses its own walker)
 */
function kursagenten_is_megamenu_active(): bool {
    if (function_exists('is_plugin_active') || (defined('ABSPATH') && file_exists(ABSPATH . 'wp-admin/includes/plugin.php'))) {
        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        if (is_plugin_active('megamenu/megamenu.php')) {
            return true;
        }
    }
    return class_exists('Mega_Menu');
}

/**
 * Filter the menu items before they are displayed
 *
 * In admin: Only show the parent item (Kurskategorier etc) - avoids save errors from virtual IDs.
 * On frontend: Expand to full taxonomy tree.
 */
function kursagenten_setup_auto_menu_items($items, $menu, $args) {
    // In admin menu editor: do NOT expand - virtual items (90001, 90002) would cause
    // "The given object ID is not that of a menu item" when saving (they're not real posts).
    if (is_admin()) {
        return $items;
    }

    // Hvis menyen ikke inneholder noen Kursagenten automenyer, skal vi ikke
    // endre strukturen i det hele tatt. Dette sikrer at helt vanlige menyer
    // (bygd manuelt med sider/kurs som i standard WordPress) forblir urørt
    // og beholder parent/child‑relasjoner slik temaet forventer.
    $has_auto_menu = false;
    foreach ((array) $items as $maybe_auto_item) {
        if (isset($maybe_auto_item->object) && $maybe_auto_item->object === 'kursagenten_auto_menu') {
            $has_auto_menu = true;
            break;
        }
    }
    if (!$has_auto_menu) {
        // Fjern eventuell gammel Kursagenten‑transient for denne menyen slik
        // at vi ikke serverer et tidligere manipulert resultat.
        $menu_id = is_object($menu) ? $menu->term_id : (int) $menu;
        if ($menu_id > 0) {
            delete_transient('kursagenten_menu_items_' . $menu_id);
        }
        return $items;
    }

    $menu_id = is_object($menu) ? $menu->term_id : (int) $menu;
    $cache_key = 'kursagenten_menu_items_' . $menu_id;
    $cached = get_transient($cache_key);
    if ($cached !== false) {
        return $cached;
    }

    $new_items = array();

    // Build map of custom CSS classes from manually added taxonomy items (siblings of automeny).
    // These items are excluded below but their custom classes must be merged into generated items.
    $manual_item_custom_classes = [];
    foreach ($items as $item) {
        if (in_array($item->object, ['ka_coursecategory', 'ka_instructors', 'ka_course_location'])) {
            $custom = kursagenten_get_custom_menu_classes_from_original($item);
            if (!empty($custom)) {
                $tax = $item->object;
                $oid = (int) $item->object_id;
                if (!isset($manual_item_custom_classes[$tax])) {
                    $manual_item_custom_classes[$tax] = [];
                }
                $manual_item_custom_classes[$tax][$oid] = $custom;
            }
        }
    }
    
    // Behold alle originale items unntatt taxonomy (erstattes av automeny)
    foreach ($items as $item) {
        if (!in_array($item->object, ['ka_coursecategory', 'ka_instructors', 'ka_course_location'])) {
            $new_items[] = $item;
        }
    }

    // Prosesser auto-menyer
    foreach ($items as $original_item) {
        if ($original_item->object === 'kursagenten_auto_menu') {
            $taxonomy = get_post_meta($original_item->ID, '_menu_item_taxonomy', true);
            if (empty($taxonomy)) {
                $menu_type = get_post_meta($original_item->ID, '_menu_item_menu_type', true);
                $taxonomy_map = [
                    'course_categories' => 'ka_coursecategory',
                    'course_categories_and_courses' => 'ka_coursecategory',
                    'course_locations' => 'ka_course_location',
                    'course_instructors' => 'ka_instructors',
                ];
                $taxonomy = $taxonomy_map[$menu_type] ?? '';
            }
            $menu_type = get_post_meta($original_item->ID, '_menu_item_menu_type', true) ?: ($original_item->object_id ?? '');
            $parent_term_id = get_post_meta($original_item->ID, '_menu_item_parent_term', true);
            $main_url = get_post_meta($original_item->ID, '_menu_item_main_url', true);
            // Når elementet ligger under en side, injiser alltid kategoriene direkte (ingen egen etikett)
            $inject_only = $original_item->menu_item_parent > 0;
            $st_filter = get_post_meta($original_item->ID, '_menu_item_st_filter', true);
            $skjul_sted_chip = get_post_meta($original_item->ID, '_menu_item_skjul_sted_chip', true) === '1';
            $vis_kun_kurs = get_post_meta($original_item->ID, '_menu_item_vis_kun_kurs', true) === '1';
            $vis_kun_subkategorier = ($parent_term_id === 'sub');

            if (empty($taxonomy)) {
                continue;
            }

            // Inject-only: leg kategoriene direkte under forelder (f.eks. Kurskategorier-siden), uten eget menypunkt
            $parent_menu_id = ($inject_only && $original_item->menu_item_parent > 0)
                ? $original_item->menu_item_parent
                : $original_item->ID;

            $item_position = array_search($original_item, $new_items);
            $splice_position = 0;
            if ($inject_only) {
                if ($item_position !== false) {
                    array_splice($new_items, $item_position, 1);
                    // Insert at the automeny's original position to preserve menu order (e.g. Betaling before automeny)
                    $splice_position = $item_position;
                } else {
                    foreach ($new_items as $idx => $ni) {
                        if (isset($ni->ID) && $ni->ID == $original_item->menu_item_parent) {
                            $splice_position = $idx + 1;
                            break;
                        }
                    }
                }
            } else {
                if ($item_position === false) {
                    $item_position = count($new_items);
                    $new_items[] = $original_item;
                }
                // Sett URL for hovedmenypunktet (både toppnivå og undermeny)
                if (!empty($main_url)) {
                    $url = $main_url;
                    if (strpos($url, '/') === 0 && strpos($url, '//') !== 0) {
                        $url = home_url($url);
                    }
                    $original_item->url = $url;
                }
                // Sikre at tittelen er satt (kan mangle fra metabox)
                if (empty($original_item->title)) {
                    $tax_obj = get_taxonomy($taxonomy);
                    $original_item->title = $tax_obj && !is_wp_error($tax_obj)
                        ? $tax_obj->labels->name
                        : __('Kurskategorier', 'kursagenten');
                }
                $splice_position = $item_position + 1;
            }

            // Kategorier og kurs: special handling with categories + main courses or flat courses only
            if ($menu_type === 'course_categories_and_courses') {
                $effective_parent = ($parent_term_id === 'sub') ? '' : $parent_term_id;
                kursagenten_inject_categories_and_courses_menu(
                    $new_items,
                    $original_item,
                    (int) $splice_position,
                    (int) $parent_menu_id,
                    $inject_only,
                    $item_position,
                    $effective_parent,
                    $main_url,
                    $st_filter,
                    $skjul_sted_chip,
                    $vis_kun_kurs,
                    $vis_kun_subkategorier,
                    $manual_item_custom_classes
                );
                continue;
            }

            // Parse st-filter (sted eller ikke-sted)
            $filter_location_term_id = null;
            $exclude_location = false;
            if (!empty($st_filter)) {
                $neg_prefix = 'ikke-';
                if (stripos($st_filter, $neg_prefix) === 0) {
                    $exclude_location = true;
                    $st_slug = sanitize_title(substr($st_filter, strlen($neg_prefix)));
                } else {
                    $st_slug = sanitize_title($st_filter);
                }
                $loc_term = get_term_by('slug', $st_slug, 'ka_course_location');
                if ($loc_term && !is_wp_error($loc_term)) {
                    $filter_location_term_id = (int) $loc_term->term_id;
                }
            }
            
            // Hent ALLE termer først (filteret trenger hele treet for å bygge children_of)
            $terms_args = [
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
                'number' => 0,
                'meta_query' => [
                    'relation' => 'OR',
                    [
                        'key' => 'hide_in_menu',
                        'compare' => 'NOT EXISTS'
                    ],
                    [
                        'key' => 'hide_in_menu',
                        'value' => 'Vis',
                        'compare' => '='
                    ]
                ],
                'hierarchical' => true,
                'orderby' => 'name'
            ];

            $all_terms = get_terms($terms_args);
            if (!is_wp_error($all_terms) && !empty($all_terms)) {
                if ($taxonomy === 'ka_coursecategory' && $filter_location_term_id !== null && function_exists('ka_filter_terms_with_published_courses_by_location')) {
                    $all_terms = ka_filter_terms_with_published_courses_by_location($all_terms, $taxonomy, $filter_location_term_id, $exclude_location);
                } elseif (function_exists('ka_filter_terms_with_published_courses')) {
                    $all_terms = ka_filter_terms_with_published_courses($all_terms, $taxonomy);
                }
            }
            // Kun hovedtermer (eller undermeny fra parent_term_id, eller subkategorier) for starten
            if ($parent_term_id === 'sub') {
                $top_term_ids = array_map(function($t) { return (int) $t->term_id; }, array_filter($all_terms ?: [], function($t) { return (int) $t->parent === 0; }));
                $terms = array_values(array_filter($all_terms ?: [], function($t) use ($top_term_ids) {
                    return in_array((int) $t->parent, $top_term_ids, true);
                }));
            } else {
                $parent_filter = !empty($parent_term_id) ? (int) $parent_term_id : 0;
                $terms = array_filter($all_terms ?: [], function($t) use ($parent_filter) {
                    return (int) $t->parent === $parent_filter;
                });
                $terms = array_values($terms);
            }

            if (!is_wp_error($terms) && !empty($terms)) {
                $term_items = array();
                $term_counter = 0;
                $base_id = 90000 + ((int) $original_item->ID * 1000);

                $append_st = !empty($st_filter) ? $st_filter : '';
                // Kun sc=0 for positiv st-filter – for "ikke-xxx" vis stedfilteret slik at bruker kan velge annet sted
                $append_sc = ($skjul_sted_chip && !empty($st_filter) && stripos($st_filter, 'ikke-') !== 0) ? '0' : '';

                $custom_classes = kursagenten_get_custom_classes_for_virtual_items(
                    $new_items,
                    (int) $parent_menu_id,
                    $inject_only,
                    $original_item
                );

                $add_term_and_children = function($term, $parent_menu_id) use (&$term_items, &$term_counter, &$base_id, $taxonomy, $original_item, $append_st, $append_sc, $custom_classes, $manual_item_custom_classes, &$add_term_and_children) {
                    $current_menu_id = $base_id + $term_counter;
                    
                    // Create menu item for this term - do NOT copy label from parent (would override title in admin)
                    $menu_item = new stdClass();
                    foreach (get_object_vars($original_item) as $key => $value) {
                        if ($key === 'label') {
                            continue;
                        }
                        $menu_item->$key = $value;
                    }
                    
                    $tax_obj = get_taxonomy($taxonomy);
                    $menu_item->ID = $current_menu_id;
                    $menu_item->db_id = $current_menu_id;
                    $menu_item->menu_item_parent = $parent_menu_id;
                    $menu_item->object_id = $term->term_id;
                    $menu_item->object = $taxonomy;
                    $menu_item->type = 'taxonomy';
                    $menu_item->title = $term->name;
                    $menu_item->label = '';
                    $menu_item->post_title = $term->name;
                    $menu_item->type_label = $tax_obj && !is_wp_error($tax_obj) ? $tax_obj->labels->singular_name : $taxonomy;
                    $term_link = get_term_link($term);
                    if (!is_wp_error($term_link)) {
                        $query_args = array();
                        if ($append_st !== '') {
                            $query_args['st'] = $append_st;
                        }
                        if ($append_sc !== '') {
                            $query_args['sc'] = $append_sc;
                        }
                        if (!empty($query_args)) {
                            $term_link = add_query_arg($query_args, $term_link);
                        }
                    }
                    $menu_item->url = is_wp_error($term_link) ? '#' : $term_link;
                    $term_custom = $custom_classes;
                    if (!empty($manual_item_custom_classes[$taxonomy][$term->term_id])) {
                        $term_custom = array_merge($term_custom, $manual_item_custom_classes[$taxonomy][$term->term_id]);
                    }
                    $menu_item->classes = array_merge(
                        [
                            'menu-item',
                            'menu-item-type-taxonomy',
                            'menu-item-object-' . $taxonomy
                        ],
                        $term_custom
                    );
                    $menu_item->menu_order = 0;
                    
                    // Hent barnetermer (filtrert – kun de med publiserte kurs)
                    $child_terms_raw = get_terms([
                        'taxonomy' => $taxonomy,
                        'hide_empty' => false,
                        'number' => 0,
                        'parent' => $term->term_id,
                        'orderby' => 'name',
                        'meta_query' => [
                            'relation' => 'OR',
                            [
                                'key' => 'hide_in_menu',
                                'compare' => 'NOT EXISTS'
                            ],
                            [
                                'key' => 'hide_in_menu',
                                'value' => 'Vis',
                                'compare' => '='
                            ]
                        ]
                    ]);
                    $child_terms = [];
                    if (!is_wp_error($child_terms_raw) && !empty($child_terms_raw) && function_exists('ka_filter_terms_with_published_courses')) {
                        $child_terms = ka_filter_terms_with_published_courses($child_terms_raw, $taxonomy);
                    } elseif (!is_wp_error($child_terms_raw)) {
                        $child_terms = $child_terms_raw;
                    }

                    if (!empty($child_terms)) {
                        $menu_item->classes[] = 'menu-item-has-children';
                    }

                    $term_items[] = $menu_item;
                    $term_counter++;

                    // Rekursivt legg til barnetermer
                    foreach ($child_terms as $child_term) {
                        $add_term_and_children($child_term, $current_menu_id);
                    }
                };
                
                // Start rekursiv prosess for hver hovedterm
                foreach ($terms as $term) {
                    $add_term_and_children($term, $parent_menu_id);
                }
                
                array_splice($new_items, $splice_position, 0, $term_items);
            }
        }
    }
    
    // IKKE sorter – splice plasserer barn rett etter forelder, som Walker forventer.
    // Tildel unike heltall for menu_order – WordPress bruker det som array-nøkkel,
    // og duplikater overskriver hverandre (tap av menypunkter).
    $menu_order = 1;
    foreach ($new_items as $item) {
        $item->menu_order = $menu_order++;
    }

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

    set_transient($cache_key, $new_items, 5 * MINUTE_IN_SECONDS);
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
        // Lagre menu type og taxonomy (object-id can come from args or our hidden field in menu-item array)
        $menu_type = $args['menu-item-object-id'] ?? ($_POST['menu-item'][$menu_item_db_id]['menu-item-object-id'] ?? null);
        if (!empty($menu_type)) {
            update_post_meta($menu_item_db_id, '_menu_item_menu_type', $menu_type);
            update_post_meta($menu_item_db_id, '_menu_item_object_id', $menu_type);
            $taxonomy = '';
            switch ($menu_type) {
                case 'course_categories':
                case 'course_categories_and_courses':
                    $taxonomy = 'ka_coursecategory';
                    break;
                case 'course_locations':
                    $taxonomy = 'ka_course_location';
                    break;
                case 'course_instructors':
                    $taxonomy = 'ka_instructors';
                    break;
            }
            
            if (!empty($taxonomy)) {
                update_post_meta($menu_item_db_id, '_menu_item_taxonomy', $taxonomy);
            }
        }

        // Lagre hovedmenypunkt URL – både i vår meta og i standard _menu_item_url
        if (isset($_POST['menu-item-main-url'][$menu_item_db_id])) {
            $main_url = sanitize_text_field($_POST['menu-item-main-url'][$menu_item_db_id]);
            if (!empty($main_url) && (strpos($main_url, '/') === 0) && strpos($main_url, '//') !== 0) {
                $main_url = home_url($main_url);
            }
            update_post_meta($menu_item_db_id, '_menu_item_main_url', $main_url);
            update_post_meta($menu_item_db_id, '_menu_item_url', $main_url);
        }
        
        if (isset($_POST['menu-item-parent-term'][$menu_item_db_id])) {
            $parent_term_val = sanitize_text_field($_POST['menu-item-parent-term'][$menu_item_db_id]);
            if ($parent_term_val === 'sub' || (is_numeric($parent_term_val) && (int) $parent_term_val > 0)) {
                update_post_meta($menu_item_db_id, '_menu_item_parent_term', $parent_term_val);
            } else {
                delete_post_meta($menu_item_db_id, '_menu_item_parent_term');
            }
        }

        if (isset($_POST['menu-item-st-filter'][$menu_item_db_id])) {
            update_post_meta($menu_item_db_id, '_menu_item_st_filter', sanitize_text_field($_POST['menu-item-st-filter'][$menu_item_db_id]));
        }
        update_post_meta($menu_item_db_id, '_menu_item_skjul_sted_chip', isset($_POST['menu-item-skjul-sted-chip'][$menu_item_db_id]) ? '1' : '0');
        update_post_meta($menu_item_db_id, '_menu_item_vis_kun_kurs', isset($_POST['menu-item-vis-kun-kurs'][$menu_item_db_id]) ? '1' : '0');
    }
}
add_action('wp_update_nav_menu_item', 'kursagenten_update_nav_menu_item', 10, 3);


// Clear menu cache when menu is updated
add_action('wp_update_nav_menu', function($menu_id) {
    wp_cache_delete('wp_get_nav_menu_items', 'nav_menu_items');
    wp_cache_delete($menu_id, 'nav_menu_items');
    delete_transient('kursagenten_menu_items_' . $menu_id);
});

/**
 * Clear all Kursagenten menu transients when a taxonomy term is updated.
 * Needed because hide_in_menu (and other term meta) affects menu output,
 * but changing it does not trigger wp_update_nav_menu.
 */
function kursagenten_clear_menu_cache_on_term_edit(): void {
    $menus = wp_get_nav_menus();
    if (is_array($menus)) {
        foreach ($menus as $menu) {
            delete_transient('kursagenten_menu_items_' . $menu->term_id);
        }
    }
}
add_action('edited_ka_coursecategory', 'kursagenten_clear_menu_cache_on_term_edit');
add_action('edited_ka_course_location', 'kursagenten_clear_menu_cache_on_term_edit');
add_action('edited_ka_instructors', 'kursagenten_clear_menu_cache_on_term_edit');
add_action('created_ka_coursecategory', 'kursagenten_clear_menu_cache_on_term_edit');
add_action('created_ka_course_location', 'kursagenten_clear_menu_cache_on_term_edit');
add_action('created_ka_instructors', 'kursagenten_clear_menu_cache_on_term_edit');

function ensure_menu_item_taxonomy($menu_id) {
    
    // Hent alle meny items for denne menyen
    $menu_items = wp_get_nav_menu_items($menu_id);
    
    if (!is_array($menu_items)) {
        return;
    }
    
    foreach ($menu_items as $item) {
        if ($item->object === 'kursagenten_auto_menu') {
            $stored_menu_type = get_post_meta($item->ID, '_menu_item_menu_type', true);
            $menu_type = $stored_menu_type ?: ($item->object_id ?? '');
            $current_taxonomy = get_post_meta($item->ID, '_menu_item_taxonomy', true);
            $parent_term = get_post_meta($item->ID, '_menu_item_parent_term', true);
            if (empty($parent_term) && get_post_meta($item->ID, '_menu_item_vis_kun_subkategorier', true) === '1') {
                update_post_meta($item->ID, '_menu_item_parent_term', 'sub');
                delete_post_meta($item->ID, '_menu_item_vis_kun_subkategorier');
            }
            // Backfill _menu_item_menu_type from object_id for items added before we had the hidden field
            if (empty($stored_menu_type) && !empty($menu_type) && in_array($menu_type, ['course_categories', 'course_categories_and_courses', 'course_locations', 'course_instructors'], true)) {
                update_post_meta($item->ID, '_menu_item_menu_type', $menu_type);
            }
            if (empty($current_taxonomy)) {
                $taxonomy = '';
                switch ($menu_type) {
                    case 'course_categories':
                    case 'course_categories_and_courses':
                        $taxonomy = 'ka_coursecategory';
                        break;
                    case 'course_locations':
                        $taxonomy = 'ka_course_location';
                        break;
                    case 'course_instructors':
                        $taxonomy = 'ka_instructors';
                        break;
                }
                
                if (!empty($taxonomy)) {
                    update_post_meta($item->ID, '_menu_item_taxonomy', $taxonomy);
                }
            }
        }
    }
}

// Oppdater hooken til å bare bruke menu_id
add_action('wp_update_nav_menu', 'ensure_menu_item_taxonomy', 10, 1);

function ensure_new_menu_item_taxonomy($menu_id, $menu_item_db_id, $args) {
    if (isset($args['menu-item-object']) && $args['menu-item-object'] === 'kursagenten_auto_menu') {
        $menu_type = isset($args['menu-item-object-id']) ? $args['menu-item-object-id'] : '';
        $taxonomy = '';
        
        switch ($menu_type) {
            case 'course_categories':
            case 'course_categories_and_courses':
                $taxonomy = 'ka_coursecategory';
                break;
            case 'course_locations':
                $taxonomy = 'ka_course_location';
                break;
            case 'course_instructors':
                $taxonomy = 'ka_instructors';
                break;
        }
        
        if (!empty($taxonomy)) {
            update_post_meta($menu_item_db_id, '_menu_item_taxonomy', $taxonomy);
        }
    }
}
add_action('wp_update_nav_menu_item', 'ensure_new_menu_item_taxonomy', 10, 3);

/**
 * Use custom walker only when Megamenu is NOT active - Megamenu has its own walker
 */
function kursagenten_set_custom_walker($args) {
    if (kursagenten_is_megamenu_active()) {
        return $args;
    }
    
    $walker = new Kursagenten_Walker_Nav_Menu();
    
    if (isset($args['menu'])) {
        $menu_obj = wp_get_nav_menu_object($args['menu']);
        if (!$menu_obj || is_wp_error($menu_obj)) {
            return $args;
        }
        $menu_items = wp_get_nav_menu_items($menu_obj->term_id);
        if ($menu_items) {
            $menu_items = kursagenten_setup_auto_menu_items($menu_items, $menu_obj, $args);
            $walker->set_items($menu_items);
        }
    }
    
    $args['walker'] = $walker;
    return $args;
}
add_filter('wp_nav_menu_args', 'kursagenten_set_custom_walker', 10);

<?php
// [kurstagger_meny]
/** * Enable shortcodes for menu navigation. */
if (!has_filter("wp_nav_menu", "do_shortcode")) {
    add_filter("wp_nav_menu", "shortcode_unautop");
    add_filter("wp_nav_menu", "do_shortcode", 11);
}

// Shortcode som viser menypunkter
add_shortcode("ka-meny", "kurstagger");

//Finn all kurstagger og list dem ut som Kadence-meny.
 // [ka-meny type="kurskategorier" start="term-slug"]
//Inkluderer hierarki med to nivåer. Ekskluderer termer med ACF felt "Ikke vis i lister og menyer" (skjul_i_lister)
function kurstagger($atts){

        // Parse attributes
        $atts = shortcode_atts(array(
            'type' => '',
            'start' => ''
        ), $atts);
        
        // Get taxonomy type
    $taxonomy = '';
    switch ($atts['type']) {
        case 'kurskategorier':
            $taxonomy = 'coursecategory';
            break;
        case 'instruktorer':
        case 'instruktører':
            $taxonomy = 'instructors';
            break;
        case 'kurssteder':
            $taxonomy = 'course_location';
            break;
        default:
            return '';
    }

    $output = '';
    //$output .= '<style>#mobile-drawer .standardmeny,#main-header .mobilmeny, ul.sub-menu:has(li.automeny) li.menu-item-type-custom{display: none;}</style>';
    
    // Hent breakpoint fra innstillinger
    $options = get_option('kursagenten_theme_customizations');
    $breakpoint = isset($options['item_breakpoint']) ? $options['item_breakpoint'] : '1025px';
    $breakpoint_num = (int)str_replace('px', '', $breakpoint);
    $next_breakpoint = ($breakpoint_num + 1) . 'px';
    
    $output .= '<style> 
                    ul.sub-menu:has(li.automeny) li.menu-item-type-custom{display: none;}
                    @media screen and (max-width: ' . $breakpoint_num . 'px) {
                        li.automeny .standardmeny {
                            display: none;
                        }
                    }
                    
                    @media screen and (min-width: ' . $next_breakpoint . ') {
                        li.automeny .mobilmeny {
                            display: none;
                        }
                    }</style>';

    // Finn parent ID hvis start er satt
    $parent_id = 0;
    if (!empty($atts['start'])) {
        $parent_term = get_term_by('slug', $atts['start'], $taxonomy);
        if ($parent_term) {
            $parent_id = $parent_term->term_id;
            $parent = $parent_term->term_id;
        }
        $kurstagger = get_terms([
            'taxonomy'  => $taxonomy,
            'hide_empty' => true,
            'hierarchical' => true,
            'parent' => $parent_id,
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
    }else{
        $parent = 0;
        $kurstagger = get_terms([
            'taxonomy'  => $taxonomy,
            'hide_empty' => true,
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
            ]
        ]);
    }

    if ( ! empty( $kurstagger ) && ! is_wp_error( $kurstagger ) ) {
        $current_theme = wp_get_theme();
        $theme_name = strtolower($current_theme->get('Name'));
        
        foreach( $kurstagger as $kurstag ){
            $url = get_term_link($kurstag->slug, $taxonomy);

            if( $kurstag->parent == $parent ) {
                $term_children = get_term_children($kurstag->term_id, $taxonomy);
                $has_children = !empty($term_children) && !is_wp_error($term_children);
                
                // Legg til hovedmenypunkt med riktig tema-struktur
                $output .= get_menu_item_html($kurstag, $url, $has_children, $theme_name);
                
                if ($has_children) {
                    // Legg til undermeny-punkter
                    foreach( $kurstagger as $subkurstag ){
                        if($subkurstag->parent == $kurstag->term_id) {
                            $suburl = get_term_link($subkurstag->slug, $taxonomy);
                            $output .= get_menu_item_html($subkurstag, $suburl, false, $theme_name);
                        }
                    }//end foreach
                    
                    $output .= '</ul></li>';
                }
            }//end if level 0
        }//end foreach
    return $output;
    }//end if
}

function get_menu_item_html($term, $url, $has_children = false, $theme = 'default') {
    $variables = [
        '{{term_id}}' => (string)$term->term_id,
        '{{term_name}}' => $term->name,
        '{{term_url}}' => $url,
        '{{taxonomy}}' => $term->taxonomy
    ];

    // Hent lagrede innstillinger
    $options = get_option('kursagenten_theme_customizations');
    $structure = isset($options['menu_structure']) ? $options['menu_structure'] : [];

    // Log tema-informasjon med mer detaljer
    error_log('get_menu_item_html - Aktivt tema: ' . $theme);
    error_log('get_menu_item_html - disable_menu_styles raw value: ' . var_export(isset($options['disable_menu_styles']) ? $options['disable_menu_styles'] : 'not set', true));
    error_log('get_menu_item_html - disable_menu_styles type: ' . (isset($options['disable_menu_styles']) ? gettype($options['disable_menu_styles']) : 'not set'));

    // Hvis ingen lagrede innstillinger, bruk tema-spesifikk struktur
    if (empty($structure)) {
        global $kursagenten_theme_customizations;
        if (!$kursagenten_theme_customizations) {
            $kursagenten_theme_customizations = new Kursagenten_Theme_Customizations();
        }
        
        $theme_structure = $kursagenten_theme_customizations->get_menu_structure($theme);
        $structure = [
            'item_simple' => $theme_structure['item_simple'],
            'item_with_children' => $theme_structure['item_with_children'],
            'item_simple_mobile' => $theme_structure['item_simple_mobile'],
            'item_with_children_mobile' => $theme_structure['item_with_children_mobile'],
            'item_simple_li_class' => $theme_structure['item_simple_li_class'],
            'item_with_children_li_class' => $theme_structure['item_with_children_li_class']
        ];
    }
    
    if ($has_children) {
        $li_class = isset($structure['item_with_children_li_class']) && !empty($structure['item_with_children_li_class']) ? 
            $structure['item_with_children_li_class'] : 
            'menu-item automeny menu-item-type-taxonomy menu-item-object-category menu-item-has-children';
            
        $item_with_children = '<li id="menu-item-' . $term->term_id . '" class="' . 
            esc_attr($li_class) . ' menu-item-' . $term->term_id . '">';
            
        // Sjekk om temaet er ekskludert eller om meny-stilene er deaktivert
        $excluded_themes = ['astra', 'oceanwp'];
        $is_excluded = in_array($theme, $excluded_themes);
        $is_disabled = isset($options['disable_menu_styles']) && (
            $options['disable_menu_styles'] === true || 
            $options['disable_menu_styles'] === 'true' || 
            $options['disable_menu_styles'] === '1' ||
            $options['disable_menu_styles'] === 1
        );
        
        error_log('get_menu_item_html - Tema ekskludert: ' . ($is_excluded ? 'true' : 'false'));
        error_log('get_menu_item_html - Meny-stiler deaktivert: ' . ($is_disabled ? 'true' : 'false'));
        
        if ($is_excluded || $is_disabled) {
            error_log('get_menu_item_html - Hopper over add_menu_class for ekskludert/deaktivert tema');
            $desktop_html = strtr($structure['item_with_children'], $variables);
            $mobile_html = strtr($structure['item_with_children_mobile'], $variables);
            error_log('get_menu_item_html - Desktop HTML: ' . $desktop_html);
            error_log('get_menu_item_html - Mobile HTML: ' . $mobile_html);
            $item_with_children .= $desktop_html;
            $item_with_children .= $mobile_html;
        } else {
            error_log('get_menu_item_html - Bruker add_menu_class for aktivt tema');
            $desktop_html = add_menu_class(strtr($structure['item_with_children'], $variables), 'desktop');
            $mobile_html = add_menu_class(strtr($structure['item_with_children_mobile'], $variables), 'mobile');
            error_log('get_menu_item_html - Desktop HTML med klasser: ' . $desktop_html);
            error_log('get_menu_item_html - Mobile HTML med klasser: ' . $mobile_html);
            $item_with_children .= $desktop_html;
            $item_with_children .= $mobile_html;
        }
        
        $item_with_children .= '<ul class="sub-menu">';
        error_log('get_menu_item_html - Full HTML output: ' . $item_with_children);
        return $item_with_children;
    } else {
        $li_class = isset($structure['item_simple_li_class']) && !empty($structure['item_simple_li_class']) ? 
            $structure['item_simple_li_class'] : 
            'menu-item automeny menu-item-type-post_type menu-item-type-taxonomy menu-item-object-course';
            
        $item_simple = '<li id="menu-item-' . $term->term_id . '" class="' . 
            esc_attr($li_class) . ' menu-item-' . $term->term_id . '">';
        $item_simple .= strtr($structure['item_simple'], $variables);
        $item_simple .= '</li>';
        error_log('get_menu_item_html - Simple item HTML: ' . $item_simple);
        return $item_simple;
    }
}

function add_menu_class($html, $type = 'desktop') {
    $class = ($type === 'desktop') ? 'standardmeny' : 'mobilmeny';

    $options = get_option('kursagenten_theme_customizations');
    $current_theme = strtolower(wp_get_theme()->get('Name'));

    // Liste over temaer som håndterer meny-visning på sin egen måte
    $excluded_themes = ['astra', 'oceanwp'];
    
    // Log informasjon med mer detaljer
    error_log('add_menu_class - Aktivt tema: ' . $current_theme);
    error_log('add_menu_class - Tema ekskludert: ' . (in_array($current_theme, $excluded_themes) ? 'true' : 'false'));
    error_log('add_menu_class - disable_menu_styles raw value: ' . var_export(isset($options['disable_menu_styles']) ? $options['disable_menu_styles'] : 'not set', true));
    error_log('add_menu_class - disable_menu_styles type: ' . (isset($options['disable_menu_styles']) ? gettype($options['disable_menu_styles']) : 'not set'));
    error_log('add_menu_class - Input HTML: ' . $html);
    
    // Sjekk om temaet er ekskludert eller om meny-stilene er deaktivert
    if (in_array($current_theme, $excluded_themes) || 
        (isset($options['disable_menu_styles']) && (
            $options['disable_menu_styles'] === true || 
            $options['disable_menu_styles'] === 'true' || 
            $options['disable_menu_styles'] === '1' ||
            $options['disable_menu_styles'] === 1
        ))) {
        error_log('add_menu_class - Returnerer HTML uten endringer for ekskludert/deaktivert tema');
        return $html;
    }
    
    // Finn første HTML-tag
    if (preg_match('/<(\w+)([^>]*)>/', $html, $matches)) {
        $tag = $matches[1];          // f.eks. 'a' eller 'div'
        $attributes = $matches[2];    // eksisterende attributter
        
        // Sjekk om det allerede finnes en class-attributt
        if (strpos($attributes, 'class="') !== false) {
            // Legg til klassen i eksisterende class-attributt
            $html = preg_replace('/class="([^"]*)"/', 'class="$1 ' . $class . '"', $html, 1);
            error_log('add_menu_class - La til klasse ' . $class . ' i eksisterende class-attributt');
        } else {
            // Legg til ny class-attributt
            $html = preg_replace('/<' . $tag . '([^>]*)>/', '<' . $tag . '$1 class="' . $class . '">', $html, 1);
            error_log('add_menu_class - La til ny class-attributt med klasse ' . $class);
        }
    }
    
    error_log('add_menu_class - Output HTML: ' . $html);
    return $html;
}



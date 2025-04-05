<?php
/**
 * Enable shortcodes in menu navigation
 */
if (!has_filter("wp_nav_menu", "do_shortcode")) {
    add_filter("wp_nav_menu", "shortcode_unautop");
    add_filter("wp_nav_menu", "do_shortcode", 11);
}

/**
 * Helper function to get theme-specific menu classes
 */
/*
function kursagenten_get_theme_menu_classes() {
    $theme = wp_get_theme();
    $theme_name = strtolower($theme->get('Name'));
    $parent_theme = strtolower($theme->get('Template'));
    
    // Standard WordPress klasser som base
    $classes = [
        'menu_item' => 'menu-item',
        'menu_item_has_children' => 'menu-item-has-children',
        'sub_menu' => 'sub-menu',
        'current_menu_item' => 'current-menu-item',
        'dropdown_toggle' => 'dropdown-toggle',
        'menu_link' => 'menu-link'
    ];
    
    // Legg til tema-spesifikke klasser
    switch ($theme_name) {
        case 'astra':
        case 'astra child':
            $classes['menu_link'] = 'menu-link ast-menu-link';
            $classes['dropdown_toggle'] = 'ast-menu-toggle';
            break;
            
        case 'generatepress':
            $classes['menu_link'] = 'menu-link gp-menu-link';
            $classes['dropdown_toggle'] = 'dropdown-menu-toggle';
            break;
            
        case 'oceanwp':
            $classes['menu_link'] = 'menu-link oceanwp-menu-link';
            $classes['dropdown_toggle'] = 'oceanwp-mobile-menu-icon';
            break;
            
        case 'kadence':
            $classes['menu_link'] = 'menu-link kadence-menu-link';
            $classes['dropdown_toggle'] = 'kadence-dropdown-toggle';
            break;
    }
    
    return apply_filters('kursagenten_menu_classes', $classes);
}

/**
 * Shortcode for generating menu structure from taxonomies
 * Usage: [hent type="kurskategorier" start="term-slug"]
 */
function kursagenten_taxonomy_menu_shortcode($atts) {
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
    
    // Hent terms
    $terms = get_terms([
        'taxonomy' => $taxonomy,
        'hide_empty' => false,
        'parent' => !empty($atts['start']) ? get_term_by('slug', $atts['start'], $taxonomy)->term_id : 0,
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

    if (!empty($terms) && !is_wp_error($terms)) {
        foreach ($terms as $term) {
            if ($term->parent == 0) {
                $term_children = get_term_children($term->term_id, $taxonomy);
                $url = get_term_link($term->slug, $taxonomy);
                
                if (!empty($term_children) && !is_wp_error($term_children)) {
                    // Parent term med barn
                    $output .= sprintf(
                        '<li id="menu-item-%1$d" class="menu-item menu-item-type-taxonomy menu-item-object-%2$s menu-item-has-children menu-item-%1$d menu-item--has-toggle">',
                        $term->term_id,
                        $taxonomy
                    );
                    
                    // Desktop versjon
                    $output .= '<a href="' . esc_url($url) . '"><span class="nav-drop-title-wrap">' . 
                              esc_html($term->name) . '<span class="dropdown-nav-toggle">' .
                              '<span class="kadence-svg-iconset svg-baseline"><svg aria-hidden="true" class="kadence-svg-icon kadence-arrow-down-svg" fill="currentColor" version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><title>Expand</title><path d="M5.293 9.707l6 6c0.391 0.391 1.024 0.391 1.414 0l6-6c0.391-0.391 0.391-1.024 0-1.414s-1.024-0.391-1.414 0l-5.293 5.293-5.293-5.293c-0.391-0.391-1.024-0.391-1.414 0s-0.391 1.024 0 1.414z"></path></svg></span></span></span></a>';
                    
                    // Mobil versjon
                    $output .= '<div class="drawer-nav-drop-wrap"><a href="' . esc_url($url) . '">' . 
                              esc_html($term->name) . '</a>' .
                              '<button class="drawer-sub-toggle" data-toggle-duration="10" data-toggle-target="#mobile-menu .menu-item-' . 
                              $term->term_id . ' > .sub-menu" aria-expanded="false"><span class="screen-reader-text">Vis/skjul undermeny</span>' .
                              '<span class="kadence-svg-iconset"><svg aria-hidden="true" class="kadence-svg-icon kadence-arrow-down-svg" fill="currentColor" version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><title>Expand</title><path d="M5.293 9.707l6 6c0.391 0.391 1.024 0.391 1.414 0l6-6c0.391-0.391 0.391-1.024 0-1.414s-1.024-0.391-1.414 0l-5.293 5.293-5.293-5.293c-0.391-0.391-1.024-0.391-1.414 0s-0.391 1.024 0 1.414z"></path></svg></span></button></div>';
                    
                    $output .= '<ul class="sub-menu">';
                    
                    // Legg til barn
                    foreach ($terms as $child_term) {
                        if ($child_term->parent == $term->term_id) {
                            $child_url = get_term_link($child_term->slug, $taxonomy);
                            $output .= sprintf(
                                '<li id="menu-item-%1$d" class="menu-item menu-item-type-taxonomy menu-item-object-%2$s menu-item-%1$d"><a href="%3$s">%4$s</a></li>',
                                $child_term->term_id,
                                $taxonomy,
                                esc_url($child_url),
                                esc_html($child_term->name)
                            );
                        }
                    }
                    
                    $output .= '</ul></li>';
                } else {
                    // Term uten barn
                    $output .= sprintf(
                        '<li id="menu-item-%1$d" class="menu-item menu-item-type-taxonomy menu-item-object-%2$s menu-item-%1$d"><a href="%3$s">%4$s</a></li>',
                        $term->term_id,
                        $taxonomy,
                        esc_url($url),
                        esc_html($term->name)
                    );
                }
            }
        }
    }
    
    return $output;
}
/*
function kursagenten_is_mobile() {
    return wp_is_mobile();
}
*/

/**
 * Render menu item
 */
/*
function kursagenten_render_menu_item($item, $template = 'item', $is_mobile = false) {
    // Hent innstillingene
    $options = get_option('kursagenten_theme_customizations');
    if (!$options) {
        $options = array();
    }

    // Sjekk om vi har en gyldig template
    $current_theme = wp_get_theme();
    $theme_key = strtolower($current_theme->get('TextDomain'));
    
    // Bestem hvilken template som skal brukes
    $template_content = '';
    if ($is_mobile) {
        $template_content = isset($options['menu_structure_mobile']) ? $options['menu_structure_mobile'] : '';
    } else {
        $template_content = isset($options['menu_structure_desktop']) ? $options['menu_structure_desktop'] : '';
    }

    // Hvis ingen tilpasset template er satt, bruk standard
    if (empty($template_content)) {
        $template_content = '<li id="menu-item-{{item_id}}" class="{{item_classes}}">
            <a href="{{item_url}}" class="menu-link">{{item_title}}</a>
            {{#if has_children}}
            <button class="dropdown-toggle" aria-expanded="false">
                <span class="screen-reader-text">Undermeny</span>
            </button>
            {{submenu}}
            {{/if}}
        </li>';
    }

    // Sjekk at vi har et gyldig item-objekt
    if (!is_object($item) || !isset($item->term_id)) {
        return '';
    }

    // Forbered variablene for erstatning
    $classes = array('menu-item');
    if (isset($item->classes) && is_array($item->classes)) {
        $classes = array_merge($classes, $item->classes);
    }
    if (isset($item->has_children) && $item->has_children) {
        $classes[] = 'menu-item-has-children';
    }

    // Lag erstatningsverdier
    $replacements = array(
        '{{item_id}}' => esc_attr($item->term_id),
        '{{item_classes}}' => esc_attr(implode(' ', array_filter($classes))),
        '{{item_url}}' => esc_url(get_term_link($item)),
        '{{item_title}}' => esc_html($item->name),
        '{{submenu}}' => isset($item->submenu) ? $item->submenu : '',
    );

    // Håndter betinget innhold for undermeny
    if (isset($item->has_children) && $item->has_children) {
        $template_content = str_replace(
            array('{{#if has_children}}', '{{/if}}'),
            array('', ''),
            $template_content
        );
    } else {
        // Fjern betinget innhold hvis det ikke er noen undermeny
        $template_content = preg_replace(
            '/{{#if has_children}}.*?{{\/if}}/s',
            '',
            $template_content
        );
    }

    // Erstatt alle variabler i templaten
    return str_replace(
        array_keys($replacements),
        array_values($replacements),
        $template_content
    );
}
*/
// Registrer shortcoden
add_shortcode('hent', 'kursagenten_taxonomy_menu_shortcode'); 
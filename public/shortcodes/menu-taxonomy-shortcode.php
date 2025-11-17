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
            'start' => '',
            // Valgfritt: skjul sted-chip i videre visning (bærer sc=0)
            'skjul_sted_chip' => '',
            // Valgfritt: transport-parameter for sted (slug eller "ikke-slug"), brukes hvis satt
            'st' => '',
        ), $atts);
        
        // Get taxonomy type
    $taxonomy = '';
    switch ($atts['type']) {
        case 'kurskategorier':
            $taxonomy = 'ka_coursecategory';
            break;
        case 'instruktorer':
        case 'instruktører':
            $taxonomy = 'ka_instructors';
            break;
        case 'kurssteder':
            $taxonomy = 'ka_course_location';
            break;
        default:
            return '';
    }

    $output = '';
    //$output .= '<style>#mobile-drawer .ka-desktop-menu,#main-header .ka-mobile-menu, ul.sub-menu:has(li.automeny) li.menu-item-type-custom{display: none;}</style>';
    
    // Hent breakpoint fra innstillinger
    $options = get_option('kursagenten_theme_customizations');
    $breakpoint = isset($options['item_breakpoint']) ? $options['item_breakpoint'] : '1025px';
    $breakpoint_num = (int)str_replace('px', '', $breakpoint);
    $next_breakpoint = ($breakpoint_num + 1) . 'px';
    
    $output .= '<style> 
                    ul.sub-menu:has(li.automeny) li.menu-item-type-custom{display: none;}
                    @media screen and (max-width: ' . $breakpoint_num . 'px) {
                        li.automeny .ka-desktop-menu {
                            display: none;
                        }
                    }
                    
                    @media screen and (min-width: ' . $next_breakpoint . ') {
                        li.automeny .ka-mobile-menu {
                            display: none;
                        }
                    }</style>';

    // Finn parent ID hvis start er satt
    $parent_id = 0;
    // Forbered stedsfilter (st) for å begrense hvilke termer som vises i menyen
    $st_param = !empty($atts['st'])
        ? sanitize_text_field((string)$atts['st'])
        : (isset($_GET['st']) ? sanitize_text_field((string)$_GET['st']) : '');
    $filter_location_term_id = null;
    $exclude_location = false;
    if ($st_param !== '') {
        $neg_prefix = 'ikke-';
        if (stripos($st_param, $neg_prefix) === 0) {
            $exclude_location = true;
            $st_slug = sanitize_title(substr($st_param, strlen($neg_prefix)));
        } else {
            $st_slug = sanitize_title($st_param);
        }
        $loc_term = get_term_by('slug', $st_slug, 'ka_course_location');
        if ($loc_term && !is_wp_error($loc_term)) {
            $filter_location_term_id = (int) $loc_term->term_id;
        }
    }

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
                    'key' => 'hide_in_menu',
                    'value' => 'Vis',
                ],
                [
                    'key' => 'hide_in_menu',
                    'compare' => 'NOT EXISTS'
                ]
            ]
        ]);
        // Filter bort termer uten publiserte kurs (ta hensyn til stedsfilter hvis satt)
        if ($filter_location_term_id !== null) {
            $kurstagger = ka_filter_terms_with_published_courses_by_location($kurstagger, $taxonomy, $filter_location_term_id, $exclude_location);
        } else {
            $kurstagger = ka_filter_terms_with_published_courses($kurstagger, $taxonomy);
        }
    }else{
        $parent = 0;
        $kurstagger = get_terms([
            'taxonomy'  => $taxonomy,
            'hide_empty' => true,
            'hierarchical' => true,
            'meta_query' => [
            'relation' => 'OR',
            [
                'key' => 'hide_in_menu',
                'value' => 'Vis',
            ],
            [
                'key' => 'hide_in_menu',
                'compare' => 'NOT EXISTS'
            ]
            ]
        ]);
        // Filter bort termer uten publiserte kurs (ta hensyn til stedsfilter hvis satt)
        if ($filter_location_term_id !== null) {
            $kurstagger = ka_filter_terms_with_published_courses_by_location($kurstagger, $taxonomy, $filter_location_term_id, $exclude_location);
        } else {
            $kurstagger = ka_filter_terms_with_published_courses($kurstagger, $taxonomy);
        }
    }

    if ( ! empty( $kurstagger ) && ! is_wp_error( $kurstagger ) ) {
        $current_theme = wp_get_theme();
        $theme_name = strtolower($current_theme->get('Name'));
        
        // Les transport-parameter fra shortcode-attributt først, ellers fra nåværende URL
        $current_st = !empty($atts['st'])
            ? sanitize_text_field((string)$atts['st'])
            : (isset($_GET['st']) ? sanitize_text_field((string)$_GET['st']) : '');
        $append_sc0 = (!empty($atts['skjul_sted_chip']) && $atts['skjul_sted_chip'] === 'ja');

        foreach( $kurstagger as $kurstag ){
            $url = get_term_link($kurstag->slug, $taxonomy);
            if (!is_wp_error($url)) {
                $args = [];
                if ($current_st !== '') {
                    $args['st'] = $current_st;
                }
                if ($append_sc0) {
                    $args['sc'] = '0';
                }
                if (!empty($args)) {
                    $url = add_query_arg($args, $url);
                }
            }

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
                            if (!is_wp_error($suburl)) {
                                $args = [];
                                if ($current_st !== '') {
                                    $args['st'] = $current_st;
                                }
                                if ($append_sc0) {
                                    $args['sc'] = '0';
                                }
                                if (!empty($args)) {
                                    $suburl = add_query_arg($args, $suburl);
                                }
                            }
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

// Hjelpefunksjon: filtrer termer til de som har publiserte kurs (inkluder foreldre som har barn med publiserte kurs)
function ka_filter_terms_with_published_courses($terms, $taxonomy) {
    if (empty($terms) || is_wp_error($terms)) {
        return $terms;
    }

    // Bygg barneliste per forelder
    $children_of = [];
    foreach ($terms as $t) {
        if ($t->parent) {
            $children_of[$t->parent] = $children_of[$t->parent] ?? [];
            $children_of[$t->parent][] = (int)$t->term_id;
        }
    }

    // For hver term, sjekk om den selv har publiserte kurs
    $has_published = [];
    foreach ($terms as $t) {
        $has_published[$t->term_id] = ka_term_has_published_courses((int)$t->term_id, $taxonomy);
    }

    // Inkluder term hvis den selv har publiserte kurs, eller om noen barn har publiserte kurs
    $filtered = array_filter($terms, function($t) use ($children_of, $has_published) {
        $tid = (int)$t->term_id;
        if (!empty($has_published[$tid])) {
            return true;
        }
        if (!empty($children_of[$tid])) {
            foreach ($children_of[$tid] as $child_id) {
                if (!empty($has_published[$child_id])) {
                    return true;
                }
            }
        }
        return false;
    });

    return array_values($filtered);
}

// Hjelpefunksjon: filtrer termer til de som har publiserte kurs, med stedsfilter (IN/NOT IN)
function ka_filter_terms_with_published_courses_by_location($terms, $taxonomy, int $location_term_id, bool $exclude_location) {
    if (empty($terms) || is_wp_error($terms)) {
        return $terms;
    }

    // Bygg barneliste per forelder
    $children_of = [];
    foreach ($terms as $t) {
        if ($t->parent) {
            $children_of[$t->parent] = $children_of[$t->parent] ?? [];
            $children_of[$t->parent][] = (int)$t->term_id;
        }
    }

    // For hver term, sjekk om den selv har publiserte kurs under stedsfilter
    $has_published = [];
    foreach ($terms as $t) {
        $has_published[$t->term_id] = ka_term_has_published_courses_with_location((int)$t->term_id, $taxonomy, $location_term_id, $exclude_location);
    }

    // Inkluder term hvis den selv har publiserte kurs, eller om noen barn har publiserte kurs (under samme stedsfilter)
    $filtered = array_filter($terms, function($t) use ($children_of, $has_published) {
        $tid = (int)$t->term_id;
        if (!empty($has_published[$tid])) {
            return true;
        }
        if (!empty($children_of[$tid])) {
            foreach ($children_of[$tid] as $child_id) {
                if (!empty($has_published[$child_id])) {
                    return true;
                }
            }
        }
        return false;
    });

    return array_values($filtered);
}

// Helper: verify that the term has at least one published ka_course/ka_coursedate depending on the taxonomy
function ka_term_has_published_courses(int $term_id, string $taxonomy): bool {
    // For alle taksonomier her viser vi kurs, ikke coursedates, i meny
    $q = new WP_Query([
        'post_type' => 'ka_course',
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'tax_query' => [[
            'taxonomy' => $taxonomy,
            'field' => 'term_id',
            'terms' => $term_id,
        ]],
        'no_found_rows' => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    ]);
    return $q->have_posts();
}

// Helper: verify published courses with optional location IN/NOT IN constraint
function ka_term_has_published_courses_with_location(int $term_id, string $taxonomy, int $location_term_id, bool $exclude_location): bool {
    $tax_query = [[
        'taxonomy' => $taxonomy,
        'field' => 'term_id',
        'terms' => $term_id,
    ]];
    // Legg til lokasjonsfilter
    $tax_query[] = [
        'taxonomy' => 'ka_course_location',
        'field' => 'term_id',
        'terms' => [$location_term_id],
        'operator' => $exclude_location ? 'NOT IN' : 'IN',
    ];

    $q = new WP_Query([
        'post_type' => 'ka_course',
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'tax_query' => $tax_query,
        'no_found_rows' => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    ]);
    return $q->have_posts();
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
            'item_with_children_mobile' => $theme_structure['item_with_children_mobile'],
            'item_simple_li_class' => $theme_structure['item_simple_li_class'],
            'item_with_children_li_class' => $theme_structure['item_with_children_li_class'],
            'item_with_children_li_class_mobile' => $theme_structure['item_with_children_li_class_mobile']
        ];
    }
    
    if ($has_children) {
        $li_class = isset($structure['item_with_children_li_class']) && !empty($structure['item_with_children_li_class']) ? 
            $structure['item_with_children_li_class'] : 
            'menu-item automeny menu-item-type-taxonomy menu-item-object-category menu-item-has-children';
            
        $li_class_mobile = isset($structure['item_with_children_li_class_mobile']) && !empty($structure['item_with_children_li_class_mobile']) ? 
            $structure['item_with_children_li_class_mobile'] : 
            'menu-item menu-item-type-taxonomy menu-item-object-category menu-item-has-children';
            
        $item_with_children = '<li id="menu-item-' . $term->term_id . '" class="automeny ' . 
            esc_attr($li_class) . ' menu-item-' . $term->term_id . '" data-desktop-class="automeny ' . esc_attr($li_class) . '" data-mobile-class="automeny ' . esc_attr($li_class_mobile) . '">';
            
        // Sjekk om temaet er ekskludert eller om meny-stilene er deaktivert
        $excluded_themes = ['astra', 'oceanwp'];
        $is_excluded = in_array($theme, $excluded_themes);
        $is_disabled = isset($options['disable_menu_styles']) && (
            $options['disable_menu_styles'] === true || 
            $options['disable_menu_styles'] === 'true' || 
            $options['disable_menu_styles'] === '1' ||
            $options['disable_menu_styles'] === 1
        );
        
        if ($is_excluded || $is_disabled) {
            $desktop_html = strtr($structure['item_with_children'], $variables);
            $mobile_html = strtr($structure['item_with_children_mobile'], $variables);
            $item_with_children .= $desktop_html;
            $item_with_children .= $mobile_html;
        } else {
            $desktop_html = add_menu_class(strtr($structure['item_with_children'], $variables), 'desktop');
            $mobile_html = add_menu_class(strtr($structure['item_with_children_mobile'], $variables), 'mobile');
            $item_with_children .= $desktop_html;
            $item_with_children .= $mobile_html;
        }
        
        $item_with_children .= '<ul class="sub-menu">';
        return $item_with_children;
    } else {
        $li_class = isset($structure['item_simple_li_class']) && !empty($structure['item_simple_li_class']) ? 
            $structure['item_simple_li_class'] : 
            'menu-item automeny menu-item-type-post_type menu-item-type-taxonomy menu-item-object-course';
            
        $item_simple = '<li id="menu-item-' . $term->term_id . '" class="automeny ' . 
            esc_attr($li_class) . ' menu-item-' . $term->term_id . '">';
        $item_simple .= strtr($structure['item_simple'], $variables);
        $item_simple .= '</li>';
        return $item_simple;
    }
}

function add_menu_class($html, $type = 'desktop') {
    $class = ($type === 'desktop') ? 'ka-desktop-menu' : 'ka-mobile-menu';

    $options = get_option('kursagenten_theme_customizations');
    $current_theme = strtolower(wp_get_theme()->get('Name'));

    // Liste over temaer som håndterer meny-visning på sin egen måte
    $excluded_themes = ['astra', 'oceanwp'];
    
    // Sjekk om temaet er ekskludert eller om meny-stilene er deaktivert
    if (in_array($current_theme, $excluded_themes) || 
        (isset($options['disable_menu_styles']) && (
            $options['disable_menu_styles'] === true || 
            $options['disable_menu_styles'] === 'true' || 
            $options['disable_menu_styles'] === '1' ||
            $options['disable_menu_styles'] === 1
        ))) {
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
        } else {
            // Legg til ny class-attributt
            $html = preg_replace('/<' . $tag . '([^>]*)>/', '<' . $tag . '$1 class="' . $class . '">', $html, 1);
        }
    }
    
    return $html;
}

// Registrer og last inn jQuery
function kursagenten_enqueue_scripts_menu() {
    wp_enqueue_script('jquery');
}
add_action('wp_enqueue_scripts', 'kursagenten_enqueue_scripts_menu');

// Legg til JavaScript for å håndtere klasser basert på skjermstørrelse
function kursagenten_menu_responsive_scripts() {
    // Hent breakpoint fra innstillinger
    $options = get_option('kursagenten_theme_customizations');
    $breakpoint = isset($options['item_breakpoint']) ? intval($options['item_breakpoint']) : 1025;
    
    ?>
    <script>
    (function() {
        // Sjekk om jQuery er tilgjengelig
        if (typeof jQuery === 'undefined') {
            console.error('Kursagenten: jQuery er ikke tilgjengelig');
            return;
        }
        
        jQuery(document).ready(function($) {
            function updateMenuClasses() {
                // Bruk breakpoint fra innstillinger
                const breakpoint = <?php echo $breakpoint; ?>;
                const menuItems = $('.menu-item-has-children[data-desktop-class][data-mobile-class]');
                
                menuItems.each(function() {
                    const desktopClass = $(this).data('desktop-class');
                    const mobileClass = $(this).data('mobile-class');
                    
                    if (window.innerWidth >= breakpoint) {
                        // På desktop
                        $(this).attr('class', desktopClass + ' menu-item-' + $(this).attr('id').replace('menu-item-', ''));
                    } else {
                        // På mobil
                        $(this).attr('class', mobileClass + ' menu-item-' + $(this).attr('id').replace('menu-item-', ''));
                    }
                });
            }
            
            // Kjør ved oppstart og når vinduet endrer størrelse
            updateMenuClasses();
            $(window).on('resize', updateMenuClasses);
        });
    })();
    </script>
    <?php
}
add_action('wp_footer', 'kursagenten_menu_responsive_scripts');



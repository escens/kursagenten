<?php
/**
 * Template loading and handling functions
 */

if (!defined('ABSPATH')) exit;

// Load SEO functions
require_once(dirname(__FILE__) . '/template-seo-functions.php');

/**
 * Laster inn riktig template basert på kontekst og innstillinger
 *
 * @param string $template Original template path
 * @return string Modified template path
 */
function kursagenten_template_loader($template) {
    // Sjekk om vi er på en kursrelatert side
    if (!is_singular('course') && !is_post_type_archive('course') && 
        !is_tax(['coursecategory', 'course_location', 'instructors'])) {
        return $template;
    }

    // Fikse queried object for taksonomier
    if (is_tax(['coursecategory', 'course_location', 'instructors'])) {
        // Hent term direkte fra URL
        $requested_slug = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
        $current_taxonomy = get_query_var('taxonomy');
        
        // Sjekk om vi har en gyldig slug og taksonomi
        if ($requested_slug && $current_taxonomy) {
            $requested_term = get_term_by('slug', $requested_slug, $current_taxonomy);
            if ($requested_term) {
                // Oppdater global $wp_query
                global $wp_query;
                $wp_query->queried_object = $requested_term;
                $wp_query->queried_object_id = $requested_term->term_id;
            }
        }
    }
    
    // Bestem kontekst og layout
    $context = '';
    $layout = 'default';
    
    if (is_singular('course')) {
        $context = 'single';
        $layout = get_option('kursagenten_single_layout', 'default');
    } elseif (is_post_type_archive('course')) {
        $context = 'archive';
        $layout = get_option('kursagenten_archive_layout', 'default');
    } elseif (is_tax(['coursecategory', 'course_location', 'instructors'])) {
        $context = 'taxonomy';
        
        // Sjekk om vi har spesifikke innstillinger for denne taksonomien
        $current_tax = get_queried_object();
        if ($current_tax && isset($current_tax->taxonomy)) {
            $tax_name = $current_tax->taxonomy;
            $override_enabled = get_option("kursagenten_taxonomy_{$tax_name}_override", false);
            
            if ($override_enabled) {
                $layout = get_option("kursagenten_taxonomy_{$tax_name}_layout", '');
                if (empty($layout)) {
                    $layout = get_option('kursagenten_taxonomy_layout', 'default');
                }
            } else {
                $layout = get_option('kursagenten_taxonomy_layout', 'default');
            }
        } else {
            $layout = get_option('kursagenten_taxonomy_layout', 'default');
        }
    }

    // Last inn layout-template
    $layout_template = KURSAG_PLUGIN_DIR . 'public/templates/layouts/' . $layout . '.php';
    if (file_exists($layout_template)) {
        return $layout_template;
    }
    
    // Fallback til standard layout
    return KURSAG_PLUGIN_DIR . 'public/templates/layouts/default.php';
}
add_filter('template_include', 'kursagenten_template_loader', 99);

/**
 * Laster inn riktig design-template basert på kontekst og innstillinger
 */
function kursagenten_get_design_template() {
    $design = 'default';
    $context = '';
    
    // Bestem kontekst og hent riktig design-innstilling
    if (is_singular('course')) {
        $context = 'single';
        $design = get_option('kursagenten_single_design', 'default');
    } elseif (is_post_type_archive('course')) {
        $context = 'archive';
        $design = get_option('kursagenten_archive_design', 'default');
    } elseif (is_tax(['coursecategory', 'course_location', 'instructors'])) {
        $context = 'taxonomy';
        
        $current_tax = get_queried_object();
        if ($current_tax && isset($current_tax->taxonomy)) {
            $tax_name = $current_tax->taxonomy;
            $override_enabled = get_option("kursagenten_taxonomy_{$tax_name}_override", false);
            
            if ($override_enabled) {
                $design = get_option("kursagenten_taxonomy_{$tax_name}_design", '');
                if (empty($design)) {
                    $design = get_option('kursagenten_taxonomy_design', 'default');
                }
            } else {
                $design = get_option('kursagenten_taxonomy_design', 'default');
            }
        } else {
            $design = get_option('kursagenten_taxonomy_design', 'default');
        }
    }

    // Last inn design-template
    $design_template = KURSAG_PLUGIN_DIR . 'public/templates/designs/' . $context . '/' . $design . '.php';
    if (file_exists($design_template)) {
        include $design_template;
    } else {
        // Fallback til standard design
        include KURSAG_PLUGIN_DIR . 'public/templates/designs/' . $context . '/default.php';
    }
}

/**
 * Laster inn riktig listevisning basert på kontekst og innstillinger
 */
function kursagenten_get_list_template() {
    $list_type = 'standard';
    
    // Bestem kontekst og hent riktig listevisning
    if (is_post_type_archive('course')) {
        $list_type = get_option('kursagenten_archive_list_type', 'standard');
    } elseif (is_tax(['coursecategory', 'course_location', 'instructors'])) {
        $current_tax = get_queried_object();
        if ($current_tax && isset($current_tax->taxonomy)) {
            $tax_name = $current_tax->taxonomy;
            $override_enabled = get_option("kursagenten_taxonomy_{$tax_name}_override", false);
            
            if ($override_enabled) {
                $list_type = get_option("kursagenten_taxonomy_{$tax_name}_list_type", '');
                if (empty($list_type)) {
                    $list_type = get_option('kursagenten_taxonomy_list_type', 'standard');
                }
            } else {
                $list_type = get_option('kursagenten_taxonomy_list_type', 'standard');
            }
        }
    }

    // Last inn listevisning-template
    $list_template = KURSAG_PLUGIN_DIR . 'public/templates/list-types/' . $list_type . '.php';
    if (file_exists($list_template)) {
        include $list_template;
    } else {
        // Fallback til standard listevisning
        include KURSAG_PLUGIN_DIR . 'public/templates/list-types/standard.php';
    }
}

/**
 * Legg til CSS-klasser til body basert på layout og listevisning
 *
 * @param array $classes Eksisterende body-klasser
 * @return array Modifiserte body-klasser
 */
function kursagenten_add_body_classes($classes) {
    // Layout-klasse
    if (is_singular('course')) {
        $layout = get_option('kursagenten_single_layout', 'default');
        $design = get_option('kursagenten_single_design', 'default');
        $classes[] = 'kursagenten-single-' . $design;
    } elseif (is_post_type_archive('course')) {
        $layout = get_option('kursagenten_archive_layout', 'default');
        $list_type = get_option('kursagenten_archive_list_type', 'standard');
        $design = get_option('kursagenten_archive_design', 'default');
        $classes[] = 'kursagenten-archive-' . $design;
        $classes[] = 'kursagenten-list-' . $list_type;
    } elseif (is_tax(['coursecategory', 'course_location', 'instructors'])) {
        $current_tax = get_queried_object();
        if ($current_tax && isset($current_tax->taxonomy)) {
            $tax_name = $current_tax->taxonomy;
            $override_enabled = get_option("kursagenten_taxonomy_{$tax_name}_override", false);
            
            if ($override_enabled) {
                $layout = get_option("kursagenten_taxonomy_{$tax_name}_layout", '');
                $list_type = get_option("kursagenten_taxonomy_{$tax_name}_list_type", '');
                $design = get_option("kursagenten_taxonomy_{$tax_name}_design", '');
                
                if (empty($layout)) $layout = get_option('kursagenten_taxonomy_layout', 'default');
                if (empty($list_type)) $list_type = get_option('kursagenten_taxonomy_list_type', 'standard');
                if (empty($design)) $design = get_option('kursagenten_taxonomy_design', 'default');
            } else {
                $layout = get_option('kursagenten_taxonomy_layout', 'default');
                $list_type = get_option('kursagenten_taxonomy_list_type', 'standard');
                $design = get_option('kursagenten_taxonomy_design', 'default');
            }
            
            $classes[] = 'kursagenten-taxonomy-' . $design;
            $classes[] = 'kursagenten-list-' . $list_type;
            $classes[] = 'kursagenten-tax-' . $tax_name;
        }
    }
    
    if (isset($layout) && $layout === 'full-width') {
        $classes[] = 'kag kursagenten-full-width';
    } else {
        $classes[] = 'kag ka-default-width';
    }
    
    return $classes;
}
add_filter('body_class', 'kursagenten_add_body_classes');

/**
 * Hjelper-funksjon for å hente template-deler
 */
function get_course_template_part($args = []) {
    // Sjekk om vi skal tvinge standard visning (fra kortkode)
    $force_standard_view = isset($args['force_standard_view']) && $args['force_standard_view'] === true;
    
    // Sjekk om list_type er sendt som parameter (fra shortcode)
    // Bruk isset() og sjekk at det ikke er tom string
    if (isset($args['list_type']) && $args['list_type'] !== '' && $args['list_type'] !== null) {
        $style = $args['list_type'];
    } elseif ($force_standard_view) {
        // Tving standard visning uansett kontekst
        $style = get_option('kursagenten_archive_list_type', 'standard');
    } elseif (is_post_type_archive('course')) {
        $style = get_option('kursagenten_archive_list_type', 'standard');
    } elseif (is_tax(['coursecategory', 'course_location', 'instructors'])) {
        $current_tax = get_queried_object();
        if ($current_tax && isset($current_tax->taxonomy)) {
            $tax_name = $current_tax->taxonomy;
            $override_enabled = get_option("kursagenten_taxonomy_{$tax_name}_override", false);
            
            if ($override_enabled) {
                $style = get_option("kursagenten_taxonomy_{$tax_name}_list_type", '');
                if (empty($style)) {
                    $style = get_option('kursagenten_taxonomy_list_type', 'standard');
                }
            } else {
                $style = get_option('kursagenten_taxonomy_list_type', 'standard');
            }
        } else {
            $style = get_option('kursagenten_taxonomy_list_type', 'standard');
        }
    } else {
        // Fallback til global innstilling
        $style = get_option('kursagenten_archive_list_type', 'standard');
    }
    
    // Bygg filnavn og path
    $template_file = "{$style}.php";
    $template_path = KURSAG_PLUGIN_DIR . "public/templates/list-types/{$template_file}";
    
    // Sjekk om template eksisterer
    if (file_exists($template_path)) {
        // Gjør $args tilgjengelig for template-filen
        extract($args);
        include $template_path;
    } else {
        // Fallback til standard template
        extract($args);
        include KURSAG_PLUGIN_DIR . "public/templates/list-types/standard.php";
    }
}

/**
 * Hjelper-funksjon for å hente taksonomi-metadata
 */
function get_taxonomy_meta($term_id, $taxonomy) {
    $meta = array(
        'rich_description' => get_term_meta($term_id, 'rich_description', true),
        'image' => '',
        'icon' => ''
    );
    
    // Hent bilde basert på taksonomi
    switch ($taxonomy) {
        case 'coursecategory':
            $meta['image'] = get_term_meta($term_id, 'image_coursecategory', true);
            $meta['icon'] = get_term_meta($term_id, 'icon_coursecategory', true);
            break;
        case 'course_location':
            $meta['image'] = get_term_meta($term_id, 'image_course_location', true);
            break;
        case 'instructors':
            $meta['image'] = get_term_meta($term_id, 'instructor_image', true);
            break;
    }
    
    return $meta;
}

/**
 * Henter layout-innstilling og returnerer riktig CSS-klasse
 * 
 * @param string $context Kontekst (single, archive, taxonomy)
 * @return string CSS-klasse for layout
 */
function kursagenten_get_layout_class($context = '') {
    $layout_class = 'ka-standard-layout';
    
    if (empty($context)) {
        // Bestem kontekst automatisk
        if (is_singular('course')) {
            $context = 'single';
        } elseif (is_post_type_archive('course')) {
            $context = 'archive';
        } elseif (is_tax(['coursecategory', 'course_location', 'instructors'])) {
            $context = 'taxonomy';
        }
    }
    
    // Hent layout-innstilling basert på kontekst
    switch ($context) {
        case 'single':
            $layout = get_option('kursagenten_single_layout', 'default');
            break;
        case 'archive':
            $layout = get_option('kursagenten_archive_layout', 'default');
            break;
        case 'taxonomy':
            $current_tax = get_queried_object();
            if ($current_tax && isset($current_tax->taxonomy)) {
                $tax_name = $current_tax->taxonomy;
                $override_enabled = get_option("kursagenten_taxonomy_{$tax_name}_override", false);
                
                if ($override_enabled) {
                    $layout = get_option("kursagenten_taxonomy_{$tax_name}_layout", '');
                    if (empty($layout)) {
                        $layout = get_option('kursagenten_taxonomy_layout', 'default');
                    }
                } else {
                    $layout = get_option('kursagenten_taxonomy_layout', 'default');
                }
            } else {
                $layout = get_option('kursagenten_taxonomy_layout', 'default');
            }
            break;
        default:
            $layout = 'default';
    }
    
    // Returner riktig CSS-klasse basert på layout
    if ($layout === 'full-width') {
        $layout_class = 'ka-full-width-layout';
    } else {
        $layout_class = 'ka-default-width';
    }
    
    return $layout_class;
}

/**
 * Sjekker om sidebar skal vises basert på innstillinger
 * 
 * @param string $context Kontekst (single, archive, taxonomy)
 * @return bool True hvis sidebar skal vises
 */
function kursagenten_show_sidebar($context = '') {
    // Bestem kontekst automatisk hvis ikke angitt
    if (empty($context)) {
        if (is_singular('course')) {
            $context = 'single';
        } elseif (is_post_type_archive('course')) {
            $context = 'archive';
        } elseif (is_tax(['coursecategory', 'course_location', 'instructors'])) {
            $context = 'taxonomy';
        }
    }
    
    // Hent sidebar-innstilling basert på kontekst
    switch ($context) {
        case 'single':
            $show_sidebar = get_option('kursagenten_single_sidebar', true);
            break;
        case 'archive':
            $show_sidebar = get_option('kursagenten_archive_sidebar', true);
            break;
        case 'taxonomy':
            $current_tax = get_queried_object();
            if ($current_tax && isset($current_tax->taxonomy)) {
                $tax_name = $current_tax->taxonomy;
                $override_enabled = get_option("kursagenten_taxonomy_{$tax_name}_override", false);
                
                if ($override_enabled) {
                    $show_sidebar = get_option("kursagenten_taxonomy_{$tax_name}_sidebar", '');
                    if ($show_sidebar === '') {
                        $show_sidebar = get_option('kursagenten_taxonomy_sidebar', true);
                    }
                } else {
                    $show_sidebar = get_option('kursagenten_taxonomy_sidebar', true);
                }
            } else {
                $show_sidebar = get_option('kursagenten_taxonomy_sidebar', true);
            }
            break;
        default:
            $show_sidebar = true;
    }
    
    return $show_sidebar;
}

/**
 * Henter riktig template for AJAX-forespørsler
 * 
 * @param string $context Kontekst for forespørselen
 * @return string Template path
 */
function get_ajax_template_path($context = 'archive') {
    $style = '';
    
    switch ($context) {
        case 'archive':
            $style = get_option('kursagenten_archive_list_type', 'standard');
            break;
        case 'taxonomy':
            $current_tax = get_queried_object();
            if ($current_tax && isset($current_tax->taxonomy)) {
                $tax_name = $current_tax->taxonomy;
                $override_enabled = get_option("kursagenten_taxonomy_{$tax_name}_override", false);
                
                if ($override_enabled) {
                    $style = get_option("kursagenten_taxonomy_{$tax_name}_list_type", '');
                    if (empty($style)) {
                        $style = get_option('kursagenten_taxonomy_list_type', 'standard');
                    }
                } else {
                    $style = get_option('kursagenten_taxonomy_list_type', 'standard');
                }
            }
            break;
        default:
            $style = 'standard';
    }
    
    $template_path = KURSAG_PLUGIN_DIR . "public/templates/list-types/{$style}.php";
    return file_exists($template_path) ? $template_path : KURSAG_PLUGIN_DIR . "public/templates/list-types/standard.php";
}
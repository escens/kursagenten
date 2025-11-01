<?php
if (!defined('ABSPATH')) exit;

function get_taxonomy_data($full_path = '') {
    if (empty($full_path)) {
        $full_path = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    }
    
    $path_segments = explode('/', trim($full_path, '/'));
    
    return parse_taxonomy_path($path_segments);
}

function parse_taxonomy_path($path_segments) {
    $taxonomy = '';
    $term_slug = '';
    $taxonomy_slug = '';
    
    if (count($path_segments) >= 2) {
        $taxonomy_slug = $path_segments[0];
        $term_slug = $path_segments[1];
        
        // Fjern query-parametere
        if (strpos($term_slug, '?') !== false) {
            $term_slug = substr($term_slug, 0, strpos($term_slug, '?'));
        }
        
        $taxonomy = map_taxonomy_slug($taxonomy_slug);
    }
    
    return [
        'taxonomy' => $taxonomy,
        'term_slug' => $term_slug,
        'taxonomy_slug' => $taxonomy_slug
    ];
}

function map_taxonomy_slug($taxonomy_slug) {
    // Hent URL-innstillinger
    $url_options = get_option('kag_seo_option_name');
    
    // Bygg mapping basert på innstillinger
    $taxonomy_map = [
        !empty($url_options['ka_url_rewrite_kurskategori']) ? $url_options['ka_url_rewrite_kurskategori'] : 'kurskategori' => 'ka_coursecategory',
        !empty($url_options['ka_url_rewrite_kurssted']) ? $url_options['ka_url_rewrite_kurssted'] : 'kurssted' => 'ka_course_location',
        !empty($url_options['ka_url_rewrite_instruktor']) ? $url_options['ka_url_rewrite_instruktor'] : 'instruktorer' => 'ka_instructors'
    ];
    
    return isset($taxonomy_map[$taxonomy_slug]) ? $taxonomy_map[$taxonomy_slug] : '';
}

function get_taxonomy_term($taxonomy, $term_slug) {
    if (empty($taxonomy) || empty($term_slug)) {
        return get_queried_object();
    }
    
    $term = get_term_by('slug', $term_slug, $taxonomy);
    
    if ($term) {
        // Oppdater global query objekt
        global $wp_query;
        $wp_query->queried_object = $term;
        $wp_query->queried_object_id = $term->term_id;
        
        return $term;
    }
    
    return get_queried_object();
}

function get_taxonomy_image($term_id, $taxonomy) {
    if (function_exists('ka_map_legacy_taxonomy')) {
        $taxonomy = ka_map_legacy_taxonomy($taxonomy);
    }

    switch ($taxonomy) {
        case 'ka_coursecategory':
            return get_term_meta($term_id, 'image_coursecategory', true);
        case 'ka_course_location':
            return get_term_meta($term_id, 'image_course_location', true);
        case 'ka_instructors':
            return get_instructor_image($term_id);
        default:
            return '';
    }
}

function get_instructor_image($term_id) {
    // Sjekk først etter lokalt lagret bilde
    $image_url = get_term_meta($term_id, 'image_instructor', true);
    if (!empty($image_url)) {
        return esc_url($image_url);
    }
    
    // Hvis ikke lokalt bilde, sjekk Kursagenten-bilde
    $image_url = get_term_meta($term_id, 'image_instructor_ka', true);
    if (!empty($image_url)) {
        return esc_url($image_url);
    }
    
    // Hvis fortsatt ingen bilde, bruk placeholder fra innstillinger
    $options = get_option('design_option_name');
    $image_url = !empty($options['ka_plassholderbilde_instruktor']) ? 
        $options['ka_plassholderbilde_instruktor'] : '';
    
    // Hvis ingen placeholder i innstillinger, bruk fallback-bilde
    if (empty($image_url)) {
        $image_url = KURSAG_PLUGIN_URL . 'assets/images/placeholder-instruktor.jpg';
    }
    
    return esc_url($image_url);
}

/**
 * Get the display URL for an instructor term
 * 
 * @param WP_Term $term The instructor term object
 * @param string $taxonomy The taxonomy name
 * @return string The URL for the instructor
 */
function get_instructor_display_url($term, $taxonomy) {
    if (function_exists('ka_map_legacy_taxonomy')) {
        $taxonomy = ka_map_legacy_taxonomy($taxonomy);
    }

    if ($taxonomy !== 'ka_instructors') {
        return get_term_link($term);
    }
    
    // Hent URL-innstillinger
    $url_options = get_option('kag_seo_option_name');
    $instructor_slug = !empty($url_options['ka_url_rewrite_instruktor']) ? $url_options['ka_url_rewrite_instruktor'] : 'instruktorer';
    
    // Get name display setting
    $name_display = get_option('kursagenten_taxonomy_instructors_name_display', '');
    if (empty($name_display) || $name_display === 'full') {
        return get_term_link($term);
    }

    // Get desired name based on setting
    $display_name = '';
    switch ($name_display) {
        case 'firstname':
            $display_name = get_term_meta($term->term_id, 'instructor_firstname', true);
            break;
        case 'lastname':
            $display_name = get_term_meta($term->term_id, 'instructor_lastname', true);
            break;
    }

    if (empty($display_name)) {
        return get_term_link($term);
    }

    // Build new URL with desired name
    $new_slug = sanitize_title($display_name);
    return home_url('/' . $instructor_slug . '/' . $new_slug . '/');
}

function get_taxonomy_courses($term_id, $taxonomy) {
    // Sjekk om term_id og taxonomy er gyldige
    if (empty($term_id) || empty($taxonomy)) {
        return new WP_Query();
    }
    
    // Sjekk om termen eksisterer
    $term = get_term($term_id, $taxonomy);
    if (is_wp_error($term) || !$term) {
        return new WP_Query();
    }
    
    $args = array(
        'post_type' => 'ka_course',
        'posts_per_page' => -1,
        'tax_query' => array(
            array(
                'taxonomy' => $taxonomy,
                'field'    => 'term_id',
                'terms'    => $term_id
            )
        ),
        'orderby' => 'title',
        'order' => 'ASC'
    );
    
    $query = get_courses_for_taxonomy($args);
    
    return $query;
}

/**
 * Get taxonomy-specific setting with proper override handling
 * 
 * @param string $taxonomy The taxonomy name (ka_coursecategory, ka_course_location, ka_instructors)
 * @param string $setting The setting name (layout, design, list_type, show_images, name_display)
 * @param string $default Default value if no setting is found
 * @return string The setting value
 */
function get_taxonomy_setting($taxonomy, $setting, $default = '') {
    // Check if override is enabled for this taxonomy
    $override_enabled = get_option("kursagenten_taxonomy_{$taxonomy}_override", false);
    
    if ($override_enabled) {
        // Get taxonomy-specific setting
        $value = get_option("kursagenten_taxonomy_{$taxonomy}_{$setting}", '');
        
        // If value is empty or not set, fall back to general taxonomy setting
        if ($value === '' || $value === false) {
            // Special handling for show_images - option name has different format
            if ($setting === 'show_images') {
                $value = get_option("kursagenten_show_images_taxonomy", $default);
            } else {
                $value = get_option("kursagenten_taxonomy_{$setting}", $default);
            }
        }
        
        return $value;
    }
    
    // If override not enabled, use general taxonomy setting
    // Special handling for show_images - option name has different format
    if ($setting === 'show_images') {
        return get_option("kursagenten_show_images_taxonomy", $default);
    }
    
    return get_option("kursagenten_taxonomy_{$setting}", $default);
}
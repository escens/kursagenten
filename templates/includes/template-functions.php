<?php
// Inkluder helpers.php
require_once dirname(dirname(__DIR__)) . '/includes/helpers/helpers.php';

function get_course_template_part($args = []) {
    error_log('=== Start get_course_template_part ===');
    
    // Log innkommende argumenter
    error_log('Incoming args: ' . print_r($args, true));
    
    // Hent valgt template style
    $style = get_option('kursagenten_template_style', 'default');
    error_log('Template style: ' . $style);
    
    // Bygg filnavn og path
    $template_file = "coursedates_{$style}.php";
    $template_path = plugin_dir_path(__FILE__) . "../partials/{$template_file}";
    error_log('Looking for template at: ' . $template_path);
    
    // Sjekk om template eksisterer
    if (!file_exists($template_path)) {
        error_log('Template not found, falling back to default');
        $template_file = "coursedates_default.php";
        $template_path = plugin_dir_path(__FILE__) . "../partials/{$template_file}";
        error_log('Fallback template path: ' . $template_path);
    }
    
    // Log post data før extract
    $post_id = get_the_ID();
    error_log('Current post ID: ' . $post_id);
    
    // Log metadata med ka_format_date for datoer
    $first_date = get_post_meta($post_id, 'course_first_date', true);
    $meta_fields = [
        'course_title' => get_post_meta($post_id, 'course_title', true),
        'course_first_date' => [
            'raw' => $first_date,
            'formatted' => ka_format_date($first_date)
        ],
        'course_location' => get_post_meta($post_id, 'course_location', true),
        'course_price' => get_post_meta($post_id, 'course_price', true)
    ];
    error_log('Post meta data: ' . print_r($meta_fields, true));
    
    // Gjør argumentene tilgjengelige for template
    extract($args);
    
    error_log('About to include template: ' . $template_path);
    
    // Sjekk om filen faktisk eksisterer før inkludering
    if (file_exists($template_path)) {
        error_log('Including template file');
        include $template_path;
        error_log('Template included successfully');
    } else {
        error_log('ERROR: Template file not found at: ' . $template_path);
        // Vis en feilmelding i frontend hvis template ikke finnes
        echo '<div class="error-message">Template ikke funnet: ' . esc_html($template_file) . '</div>';
    }
    
    error_log('=== End get_course_template_part ===');
}

function get_taxonomy_template_part($style = 'default', $args = []) {
    // Debug
    error_log('get_taxonomy_template_part called with style: ' . $style);
    
    // Bygg filnavn
    $template_file = "taxonomy_{$style}.php";
    $template_path = plugin_dir_path(__FILE__) . "../partials/{$template_file}";
    
    // Debug
    error_log('Looking for template at: ' . $template_path);
    
    // Fallback til default hvis valgt template ikke finnes
    if (!file_exists($template_path)) {
        error_log('Template not found, falling back to default');
        $template_file = "taxonomy_default.php";
        $template_path = plugin_dir_path(__FILE__) . "../partials/{$template_file}";
    }
    
    // Debug
    error_log('Final template path: ' . $template_path);
    
    // Gjør argumentene tilgjengelige for template
    extract($args);
    
    // Debug
    error_log('Including template file');
    
    // Inkluder template
    include $template_path;
    
    // Debug
    error_log('Template included');
}

// Hjelpefunksjon for å hente taksonomi-metadata
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
            // Legg til instruktør-spesifikke metadata her hvis nødvendig
            break;
    }
    
    return $meta;
} 
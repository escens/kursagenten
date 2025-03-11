<?php
// Inkluder helpers.php
require_once dirname(dirname(__DIR__)) . '/includes/helpers/helpers.php';

function get_course_template_part($args = []) {
    // Hent valgt template style
    $style = get_option('kursagenten_template_style', 'default');
    
    // Bygg filnavn og path
    $template_file = "coursedates_{$style}.php";
    $template_path = plugin_dir_path(__FILE__) . "../partials/{$template_file}";
    
    // Sjekk om template eksisterer
    if (!file_exists($template_path)) {
        $template_file = "coursedates_default.php";
        $template_path = plugin_dir_path(__FILE__) . "../partials/{$template_file}";
    }
    
    // Gjør argumentene tilgjengelige for template
    extract($args);
    
    // Inkluder template hvis den finnes
    if (file_exists($template_path)) {
        include $template_path;
    } else {
        echo '<div class="error-message">Template ikke funnet: ' . esc_html($template_file) . '</div>';
    }
}

function get_taxonomy_template_part($style = 'default', $args = []) {
    // Bygg filnavn
    $template_file = "taxonomy_{$style}.php";
    $template_path = plugin_dir_path(__FILE__) . "../partials/{$template_file}";
    
    // Fallback til default hvis valgt template ikke finnes
    if (!file_exists($template_path)) {
        $template_file = "taxonomy_default.php";
        $template_path = plugin_dir_path(__FILE__) . "../partials/{$template_file}";
    }
    
    // Gjør argumentene tilgjengelige for template
    extract($args);
    
    // Inkluder template
    include $template_path;
}

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
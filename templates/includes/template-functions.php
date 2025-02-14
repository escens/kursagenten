<?php
function get_course_template_part($args = []) {
    // Hent valgt template style
    $style = get_option('kursagenten_template_style', 'default');
    
    // Bygg filnavn
    $template_file = "coursedates_{$style}.php";
    $template_path = plugin_dir_path(__FILE__) . "../partials/{$template_file}";
    
    // Fallback til default hvis valgt template ikke finnes
    if (!file_exists($template_path)) {
        $template_file = "coursedates_default.php";
        $template_path = plugin_dir_path(__FILE__) . "../partials/{$template_file}";
    }
    
    // Gjør argumentene tilgjengelige for template
    extract($args);
    
    // Inkluder template
    include $template_path;
} 
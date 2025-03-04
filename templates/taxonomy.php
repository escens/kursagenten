<?php
/**
 * The template for displaying taxonomy archives
 */

get_header();

// Get current taxonomy
$current_taxonomy = get_queried_object();
$taxonomy_name = $current_taxonomy->taxonomy;

// Debug
error_log('Taxonomy template loaded for: ' . $taxonomy_name);

// Get template style based on taxonomy settings
$default_style = get_option('kursagenten_taxonomy_style', 'default');
$specific_style = get_option("kursagenten_taxonomy_style_{$taxonomy_name}", '');
$template_style = !empty($specific_style) ? $specific_style : $default_style;

// Debug
error_log('Template style: ' . $template_style);

// Test filstier
$template_dir = plugin_dir_path(__FILE__) . 'partials/';
$template_file = "taxonomy_{$template_style}.php";
$template_path = $template_dir . $template_file;

error_log('Template directory: ' . $template_dir);
error_log('Template file: ' . $template_file);
error_log('Full template path: ' . $template_path);
error_log('File exists: ' . (file_exists($template_path) ? 'yes' : 'no'));

// Get template part based on style
get_taxonomy_template_part($template_style, array(
    'term' => $current_taxonomy,
    'taxonomy' => $taxonomy_name,
    'posts' => get_posts(array(
        'post_type' => 'course',
        'posts_per_page' => -1,
        'tax_query' => array(
            array(
                'taxonomy' => $taxonomy_name,
                'field' => 'term_id',
                'terms' => $current_taxonomy->term_id
            )
        )
    ))
));

?>
<!-- SLIDEIN Sign up form -->

<div id="slidein-overlay"></div>
<div id="slidein-panel">
    <button class="close-btn" aria-label="Close">&times;</button>
    <iframe id="kursagenten-iframe" src=""></iframe>
</div>

<?php 
get_footer(); 
?>
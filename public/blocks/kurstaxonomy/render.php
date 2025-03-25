<?php
declare(strict_types=1);

if (!defined('ABSPATH')) exit;

// Ensure GridStyles class is available
require_once KURSAG_PLUGIN_DIR . 'assets/css/public/class-blocks-shortcode-styles.php';

// Ensure BlocksShortcodeStyles class is available
require_once KURSAG_PLUGIN_DIR . 'assets/css/public/class-blocks-shortcode-styles.php';

/**
 * Render Kurstaxonomy block
 */
function render_kurstaxonomy_block($attributes) {
    // Generate unique ID for this instance
    $id = 'kurstaxonomy-' . uniqid();
    
    // Build classes based on attributes
    $classes = [];
    $classes[] = $attributes['layout'] ?? 'stablet';
    if (!empty($attributes['visningstype'])) $classes[] = $attributes['visningstype'];
    if ($attributes['skygge']) $classes[] = 'skygge';
    if ($attributes['visBeskrivelse']) $classes[] = 'utdrag';
    
    $class_string = implode(' ', $classes);
    
    // Get taxonomy type and terms
    $taxonomy = $attributes['sourceType'];
    $terms = get_terms([
        'taxonomy' => $taxonomy,
        'hide_empty' => true
    ]);

    if (is_wp_error($terms) || empty($terms)) {
        return '<p>Ingen elementer funnet.</p>';
    }

    // Generate grid styles
    $styles = BlocksShortcodeStyles::render($id, [
        'grid' => $attributes['grid'],
        'gridtablet' => $attributes['gridtablet'],
        'gridmobil' => $attributes['gridmobil'],
        'bildestr' => $attributes['bildestr'],
        'bildeformat' => $attributes['bildeformat'],
        'bildeform' => $attributes['bildeform'],
        'fontstr' => $attributes['fontstr'],
        'fontmin' => $attributes['fontmin'] ?? '0.875rem',
        'fontmax' => $attributes['fontmax'] ?? '1rem',
        'avstand' => $attributes['avstand']
    ]);

    // Start building output
    $output = $styles;
    $output .= sprintf(
        '<div id="%s" class="outer-wrapper %s">',
        esc_attr($id),
        esc_attr($class_string)
    );
    
    $output .= '<div class="wrapper">';

    foreach ($terms as $term) {
        // Get term image (assuming you have a term meta for image)
        $image_id = get_term_meta($term->term_id, 'taxonomy_image_id', true);
        $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'medium') : '';
        $default_image = plugin_dir_url(__FILE__) . '../../assets/images/default.jpg'; // Adjust path as needed
        
        $output .= '<div class="box">';
        $output .= '<div class="box-inner">';
        
        // Image section
        $output .= sprintf(
            '<a href="%s" class="image"><picture><img src="%s" alt="%s"></picture></a>',
            esc_url(get_term_link($term)),
            esc_url($image_url ?: $default_image),
            esc_attr($term->name)
        );
        
        // Text section
        $output .= '<div class="text">';
        $output .= sprintf(
            '<a href="%s" class="title"><h3 class="tittel">%s</h3></a>',
            esc_url(get_term_link($term)),
            esc_html($term->name)
        );
        
        if ($attributes['visBeskrivelse'] && !empty($term->description)) {
            $output .= sprintf(
                '<p class="description">%s</p>',
                wp_kses_post($term->description)
            );
        }
        
        $output .= '</div>'; // End .text
        $output .= '</div>'; // End .box-inner
        $output .= '</div>'; // End .box
    }

    $output .= '</div>'; // End .wrapper
    $output .= '</div>'; // End .outer-wrapper

    return $output;
}

/**
 * Register block render callback
 */
register_block_type('kursagenten/kurstaxonomy', [
    'render_callback' => 'render_kurstaxonomy_block'
]); 
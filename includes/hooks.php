<?php
/**
 * WordPress hooks for Kursagenten
 *
 * @package kursagenten
 */

/**
 * Taxonomy Hooks
 * 
 * Unified hook for taxonomy pages (instructors, coursecategory, course_location):
 * 
 * - ka_taxonomy_left_column
 *   - Placement: Left column on taxonomy templates
 *   - Parameter: $term (WP_Term object)
 *   - Example: add_action('ka_taxonomy_left_column', 'my_callback');
 * 
 * - ka_taxonomy_below_description
 *   - Placement: Below image and rich description, above course list
 *   - Parameter: $term (WP_Term object)
 *   - Example: add_action('ka_taxonomy_below_description', 'my_callback');
 * 
 * - ka_taxonomy_footer
 *   - Placement: Below the course list (taxonomy footer)
 *   - Parameter: $term (WP_Term object)
 *   - Example: add_action('ka_taxonomy_footer', 'my_callback');
 * 
 * - ka_taxonomy_header_after
 *   - Placement: Immediately after the taxonomy header/title
 *   - Parameter: $term (WP_Term object)
 *   - Example: add_action('ka_taxonomy_header_after', 'my_callback');
 * 
 * - ka_taxonomy_right_column_top
 *   - Placement: At the top of the right column in taxonomy layout
 *   - Parameter: $term (WP_Term object)
 *   - Example: add_action('ka_taxonomy_right_column_top', 'my_callback');
 * 
 * - ka_taxonomy_right_column_bottom
 *   - Placement: At the bottom of the right column in taxonomy layout
 *   - Parameter: $term (WP_Term object)
 *   - Example: add_action('ka_taxonomy_right_column_bottom', 'my_callback');
 * 
 * - ka_taxonomy_after_title
 *   - Placement: Immediately after the H1 title inside the header block
 *   - Parameter: $term (WP_Term object)
 *   - Example: add_action('ka_taxonomy_after_title', 'my_callback');
 * 
 * - ka_courselist_before
 *   - Placement: Before the course list is rendered (taxonomy templates)
 *   - Parameter: $term (WP_Term object)
 *   - Example: add_action('ka_courselist_before', 'my_callback');
 * 
 * - ka_taxonomy_pagination_after
 *   - Placement: After the pagination controls of the course list
 *   - Parameter: $term (WP_Term object)
 *   - Example: add_action('ka_taxonomy_pagination_after', 'my_callback');
 * 
 * Existing callbacks should early return if taxonomy doesn't match their target.
 */

// Example usage from other plugins:
/*
function my_plugin_taxonomy_content($term) {
    // Validate term
    if (!($term instanceof WP_Term)) {
        return;
    }
    
    // Output content
    echo '<div class="min-plugin-content">';
    echo '<h3>Min modul</h3>';
    echo '<p>' . esc_html($term->name) . '</p>';
    echo '</div>';
}

// Register once for all taxonomies
add_action('ka_taxonomy_left_column', 'my_plugin_taxonomy_content');
*/

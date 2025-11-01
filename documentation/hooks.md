## Kursagenten hooks (overview)

This document lists available hooks for taxonomy pages (ka_instructors, ka_coursecategory, ka_course_location) and how to use them safely.

### Taxonomy hooks

- ka_taxonomy_left_column
  - Placement: Left column in taxonomy templates
  - Parameter: term (WP_Term)

- ka_taxonomy_below_description
  - Placement: Below image and rich description, above the course list
  - Parameter: term (WP_Term)

- ka_taxonomy_footer
  - Placement: Below the course list (taxonomy footer)
  - Parameter: term (WP_Term)

- ka_taxonomy_header_after
  - Placement: Immediately after the taxonomy header/title
  - Parameter: term (WP_Term)

- ka_taxonomy_right_column_top
  - Placement: Top of the right column in the taxonomy layout
  - Parameter: term (WP_Term)

- ka_taxonomy_right_column_bottom
  - Placement: Bottom of the right column in the taxonomy layout
  - Parameter: term (WP_Term)

- ka_taxonomy_after_title
  - Placement: Immediately after the H1 title inside the header block
  - Parameter: term (WP_Term)

- ka_courselist_before
  - Placement: Before the course list is rendered (taxonomy templates)
  - Parameter: term (WP_Term)

- ka_taxonomy_pagination_after
  - Placement: After the pagination controls of the course list
  - Parameter: term (WP_Term)

Note: Existing callbacks should return early if the taxonomy does not match their intended target.

### Practical example

The example below demonstrates a safe callback that:
- Validates the `$term` object
- Restricts output to specific taxonomies
- Escapes output

```php
<?php
function my_plugin_taxonomy_content($term) {
    // Validate WP_Term instance
    if (!($term instanceof WP_Term)) {
        return;
    }

    // Only render for ka_coursecategory and ka_course_location
    $allowed = array('ka_coursecategory', 'ka_course_location');
    if (!in_array($term->taxonomy, $allowed, true)) {
        return;
    }

    echo '<div class="my-plugin-taxonomy-box">';
    echo '<h3>' . esc_html($term->name) . '</h3>';
    echo '<p>' . esc_html(sprintf(__('Term ID: %d', 'my-textdomain'), (int)$term->term_id)) . '</p>';
    echo '</div>';
}

// Register for a unified placement across taxonomies
add_action('ka_taxonomy_left_column', 'my_plugin_taxonomy_content');
```

<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Visibility management for courses and coursedates
 * Handles admin columns, filters and visibility logic for hidden posts
 */

// Define hidden terms globally for reuse
if (!defined('KURSAG_HIDDEN_TERMS')) {
    define('KURSAG_HIDDEN_TERMS', serialize(array('skjult', 'skjul', 'usynlig', 'inaktiv', 'ikke-aktiv')));
}

/**
 * Hide posts with specific terms in 'ka_coursecategory' from the main query
 * This needs to run FIRST to ensure posts are filtered before other operations
 */
function exclude_hidden_kurs_posts($query) {
    // Bare kjør på hovedspørringen og ikke på våre egne spørringer
    if (!is_admin() && $query->is_main_query() && !isset($query->query_vars['get_course_dates'])) { 
        // Hent gjeldende post types fra spørringen
        $post_types = $query->get('post_type');
        
        // Hvis dette er en søkespørring, må vi håndtere det spesielt
        if ($query->is_search()) {
            // Hvis post_type ikke er spesifisert i søket, eller hvis det inkluderer course/coursedate
            if (!empty($post_types)) {
                // Hvis post_types er spesifisert, sjekk om den inneholder course eller coursedate
                if (is_array($post_types) && (in_array('course', $post_types) || in_array('coursedate', $post_types)) ||
                    $post_types === 'course' || 
                    $post_types === 'coursedate') {
                    apply_course_visibility_filter($query);
                }
            }
            // Hvis ingen post_type er spesifisert, ikke gjør noe filtering
            return;
        }
        
        // For ikke-søk spørringer, apply filter bare hvis det er course eller coursedate
        if ($post_types === 'ka_course' || $post_types === 'ka_coursedate' ||
            (is_array($post_types) && (in_array('ka_course', $post_types) || in_array('ka_coursedate', $post_types)))) {
            apply_course_visibility_filter($query);
        }
    }
}

/**
 * Helper function to apply the course visibility filter
 */
function apply_course_visibility_filter($query) {
    $all_terms = get_terms([
        'taxonomy' => 'ka_coursecategory',
        'fields' => 'slugs',
        'hide_empty' => false
    ]);
    
    if (!is_wp_error($all_terms)) {
        $hidden_terms = unserialize(KURSAG_HIDDEN_TERMS);
        $visible_terms = array_diff($all_terms, $hidden_terms);
        
        if (!empty($visible_terms)) {
            // Hent eksisterende tax query
            $existing_tax_query = $query->get('tax_query');
            if (!is_array($existing_tax_query)) {
                $existing_tax_query = [];
            }
            
            // Legg til vår nye tax query
            $existing_tax_query[] = [
                'taxonomy' => 'ka_coursecategory',
                'field'    => 'slug',
                'terms'    => $visible_terms,
                'operator' => 'IN'
            ];
            
            // Sett tax query tilbake på spørringen
            $query->set('tax_query', $existing_tax_query);
        }
    }
}
// Kjør dette filteret TIDLIG
add_action('pre_get_posts', 'exclude_hidden_kurs_posts', 5);

/**
 * Filter out hidden terms from category dropdowns and lists
 * This runs AFTER the posts are filtered
 */
function exclude_hidden_terms_from_list($args, $taxonomies) {
    if (is_admin()) {
        return $args;
    }
    
    if (in_array('ka_coursecategory', (array) $taxonomies)) {
        global $wpdb;
        $hidden_terms = unserialize(KURSAG_HIDDEN_TERMS);
        $hidden_terms_string = "'" . implode("','", array_map('esc_sql', $hidden_terms)) . "'";
        
        $query = "SELECT term_id FROM {$wpdb->terms} 
                 WHERE slug IN ($hidden_terms_string)";
        
        $excluded_ids = $wpdb->get_col($query);
        
        if (!empty($excluded_ids)) {
            $args['exclude'] = $excluded_ids;
        }
    }
    return $args;
}
add_filter('get_terms_args', 'exclude_hidden_terms_from_list', 10, 2);

/**
 * Add custom column to courses and coursedates admin list
 */
function add_course_visibility_column($columns) {
    $new_columns = array();
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        if ($key === 'title') {
            $new_columns['visibility'] = 'Synlighet';
        }
    }
    return $new_columns;
}
add_filter('manage_ka_course_posts_columns', 'add_course_visibility_column');
add_filter('manage_ka_coursedate_posts_columns', 'add_course_visibility_column');

/**
 * Add content to custom column
 */
function course_visibility_column_content($column, $post_id) {
    if ($column === 'visibility') {
        // Sjekk post status først
        if (get_post_status($post_id) === 'draft') {
            echo '<span class="course-hidden-indicator" style="
                background-color:rgb(231, 111, 123);
                color: white;
                padding: 2px 8px;
                border-radius: 3px;
                display: inline-block;
                font-size: 12px;
            ">Inaktiv</span>';
            return;
        }

        $terms = wp_get_post_terms($post_id, 'ka_coursecategory');
        $hidden_terms = array('skjult', 'skjul', 'usynlig', 'inaktiv', 'ikke-aktiv');
        $is_hidden = false;
        
        foreach ($terms as $term) {
            if (in_array($term->slug, $hidden_terms)) {
                $is_hidden = true;
                break;
            }
        }

        if ($is_hidden) {
            echo '<span class="course-hidden-indicator" style="
                background-color:rgb(233, 181, 84);
                color: white;
                padding: 2px 8px;
                border-radius: 3px;
                display: inline-block;
                font-size: 12px;
            ">Skjult</span>';
        } else {
            echo '<span class="course-visible-indicator" style="
                background-color: #28a745;
                color: white;
                padding: 2px 8px;
                border-radius: 3px;
                display: inline-block;
                font-size: 12px;
            ">Synlig</span>';
        }
    }
}
add_action('manage_ka_course_posts_custom_column', 'course_visibility_column_content', 10, 2);
add_action('manage_ka_coursedate_posts_custom_column', 'course_visibility_column_content', 10, 2);

/**
 * Make the visibility column sortable
 */
function make_visibility_column_sortable($columns) {
    $columns['visibility'] = 'visibility';
    return $columns;
}
add_filter('manage_edit-ka_course_sortable_columns', 'make_visibility_column_sortable');
add_filter('manage_edit-ka_coursedate_sortable_columns', 'make_visibility_column_sortable');

/**
 * Add custom filtering for visibility status
 */
function add_visibility_filter() {
    global $typenow;
    if ($typenow === 'ka_course' || $typenow === 'ka_coursedate') {
        $current = isset($_GET['course_visibility']) ? sanitize_text_field($_GET['course_visibility']) : '';
        ?>
        <select name="course_visibility" id="course_visibility">
            <option value=""><?php esc_html_e('Vis alle synligheter', 'kursagenten'); ?></option>
            <option value="hidden" <?php selected($current, 'hidden'); ?>><?php esc_html_e('Skjulte kurs', 'kursagenten'); ?></option>
            <option value="visible" <?php selected($current, 'visible'); ?>><?php esc_html_e('Synlige kurs', 'kursagenten'); ?></option>
        </select>
        <?php
    }
}
add_action('restrict_manage_posts', 'add_visibility_filter');

/**
 * Handle visibility filter logic
 */
function handle_visibility_filter($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }

    global $typenow;
    if (($typenow === 'ka_course' || $typenow === 'ka_coursedate') && 
        isset($_GET['course_visibility'])) {
        
        // Sanitize input
        $visibility = sanitize_text_field($_GET['course_visibility']);
        if (empty($visibility)) {
            return;
        }
        
        $hidden_terms = unserialize(KURSAG_HIDDEN_TERMS);
        $tax_query = array(
            array(
                'taxonomy' => 'ka_coursecategory',
                'field'    => 'slug',
                'terms'    => $hidden_terms,
                'operator' => $visibility === 'hidden' ? 'IN' : 'NOT IN'
            )
        );

        $query->set('tax_query', $tax_query);
    }
}
add_action('pre_get_posts', 'handle_visibility_filter');

// Legg til CSS i admin
function add_admin_visibility_styles() {
    global $post_type;
    if ($post_type === 'ka_course' || $post_type === 'ka_coursedate') {
        ?>
        <style>
            .column-visibility {
                width: 100px;
            }
            .course-hidden-indicator,
            .course-visible-indicator {
                font-weight: 500;
                text-align: center;
            }
            #course_visibility {
                margin: 1px 8px;
            }
        </style>
        <?php
    }
}
add_action('admin_head', 'add_admin_visibility_styles');

/**
 * Modify course dates query to exclude hidden items
 */
function modify_course_dates_query($args) {
    
    if (!is_admin() || !isset($_GET['post_type'])) {
        $hidden_terms = unserialize(KURSAG_HIDDEN_TERMS);
        
        // Add tax query to exclude hidden terms
        $tax_query = array(
            array(
                'taxonomy' => 'ka_coursecategory',
                'field'    => 'slug',
                'terms'    => $hidden_terms,
                'operator' => 'NOT IN'
            )
        );

        // Merge with existing tax query if it exists
        if (isset($args['tax_query'])) {
            $args['tax_query']['relation'] = 'AND';
            $args['tax_query'][] = $tax_query[0];
        } else {
            $args['tax_query'] = $tax_query;
        }
        
    }
    
    return $args;
}
add_filter('get_course_dates_query_args', 'modify_course_dates_query', 10, 1); 
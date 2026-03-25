<?php
add_action('wp_ajax_filter_courses', 'filter_courses');
add_action('wp_ajax_nopriv_filter_courses', 'filter_courses');

function filter_courses() {
    check_ajax_referer('filter_nonce', 'nonce');

    $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
    $location = isset($_POST['location']) ? sanitize_text_field($_POST['location']) : '';
    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';

    $args = [
    'post_type'      => 'ka_coursedate',
        'posts_per_page' => -1,
        's'              => $search,
        'tax_query'      => [],
        'meta_query'     => [],
    ];

    if ($category) {
        $args['tax_query'][] = [
            'taxonomy' => 'ka_coursecategory',
            'field'    => 'slug',
            'terms'    => $category,
        ];
    }

    if ($location) {
        $args['meta_query'][] = [
            'key'   => 'course_location',
            'value' => $location,
            'compare' => 'LIKE',
        ];
    }

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        ob_start();
        while ($query->have_posts()) {
            $query->the_post();
            // Print course data
            echo '<div>' . get_the_title() . '</div>';
        }
        wp_reset_postdata();
        wp_send_json_success(['html' => ob_get_clean()]);
    } else {
        wp_send_json_error(['message' => 'Ingen resultater.']);
    }
}

<?php
add_action('wp_ajax_filter_courses', 'filter_courses');
add_action('wp_ajax_nopriv_filter_courses', 'filter_courses');

function filter_courses() {
    check_ajax_referer('filter_nonce', 'nonce');

    $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
    $location = isset($_POST['location']) ? sanitize_text_field($_POST['location']) : '';
    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    $paged = isset($_POST['paged']) ? intval($_POST['paged']) : 1;

    $args = [
        'post_type'      => 'coursedate',
        'posts_per_page' => 10, // Juster til antall kurs per side
        'paged'          => $paged,
        's'              => $search,
        'tax_query'      => [],
        'meta_query'     => [],
    ];

    if ($category) {
        $args['tax_query'][] = [
            'taxonomy' => 'coursecategory',
            'field'    => 'slug',
            'terms'    => $category,
        ];
    }

    if ($location) {
        $args['tax_query'][] = [
            'taxonomy' => 'course_location',
            'field'    => 'slug',
            'terms'    => $location,
        ];
    }

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        ob_start();
        while ($query->have_posts()) {
            $query->the_post();
            // Print course data
            echo '<div class="accordion-item">';
            echo '<h3>' . get_the_title() . '</h3>';
            echo '<p>' . esc_html(get_post_meta(get_the_ID(), 'course_first_date', true) ?? 'Ingen dato') . '</p>';
            echo '</div>';
        }
        wp_reset_postdata();

        wp_send_json_success([
            'html' => ob_get_clean(),
            'max_num_pages' => $query->max_num_pages, // For paginering
        ]);
    } else {
        wp_send_json_error(['message' => 'Ingen resultater.']);
    }
}

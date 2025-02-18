<?php

add_action('wp_ajax_filter_courses', 'filter_courses_handler');
add_action('wp_ajax_nopriv_filter_courses', 'filter_courses_handler');

function filter_courses_handler() {
    // Verifiser nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'filter_nonce')) {
        wp_send_json_error([
            'message' => 'Sikkerhetssjekk feilet. Vennligst oppdater siden og prøv igjen.',
            'debug' => [
                'nonce_exists' => isset($_POST['nonce']),
                'nonce_value' => $_POST['nonce'] ?? 'missing'
            ]
        ], 403);
    }

    $base_args = [
        'post_type'      => 'coursedate',
        'posts_per_page' => 10,
        'paged'          => isset($_POST['paged']) ? intval($_POST['paged']) : 1,
        'meta_query'     => [
            'relation' => 'OR',
            [
                'key'     => 'course_first_date',
                'compare' => 'EXISTS',
            ],
            [
                'key'     => 'course_first_date',
                'compare' => 'NOT EXISTS',
            ],
        ],
    ];

    // Håndter sortering
    $sort = sanitize_text_field($_POST['sort'] ?? '');
    $order = sanitize_text_field($_POST['order'] ?? '');

    if ($sort && $order) {
        switch ($sort) {
            case 'title':
                $base_args['orderby'] = 'title';
                $base_args['order'] = strtoupper($order);
                break;
            case 'price':
                $base_args['orderby'] = 'meta_value_num';
                $base_args['meta_key'] = 'course_price';
                $base_args['order'] = strtoupper($order);
                break;
            case 'date':
                // Håndter datosortering manuelt
                $query = new WP_Query($base_args);
                $posts_with_date = [];
                $posts_without_date = [];

                while ($query->have_posts()) {
                    $query->the_post();
                    $post_id = get_the_ID();
                    $date_str = get_post_meta($post_id, 'course_first_date', true);

                    if (!empty($date_str)) {
                        // Konverter fra DD.MM.YYYY til timestamp
                        $date_parts = explode('.', $date_str);
                        if (count($date_parts) === 3) {
                            $timestamp = strtotime($date_parts[2] . '-' . $date_parts[1] . '-' . $date_parts[0]);
                            $posts_with_date[$post_id] = $timestamp;
                        }
                    } else {
                        $posts_without_date[] = $post_id;
                    }
                }
                wp_reset_postdata();

                // Sorter etter timestamp
                if (strtoupper($order) === 'DESC') {
                    arsort($posts_with_date);
                } else {
                    asort($posts_with_date);
                }

                // Kombiner sorterte poster
                $sorted_posts = array_merge(array_keys($posts_with_date), $posts_without_date);
                
                // Ny spørring med sorterte poster
                $base_args['post__in'] = $sorted_posts;
                $base_args['orderby'] = 'post__in';
                break;
        }
    } else {
        // Standard datosortering når ingen sortering er valgt
        $query = new WP_Query($base_args);
        $posts_with_date = [];
        $posts_without_date = [];

        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $date_str = get_post_meta($post_id, 'course_first_date', true);

            if (!empty($date_str)) {
                // Konverter fra DD.MM.YYYY til timestamp
                $date_parts = explode('.', $date_str);
                if (count($date_parts) === 3) {
                    $timestamp = strtotime($date_parts[2] . '-' . $date_parts[1] . '-' . $date_parts[0]);
                    $posts_with_date[$post_id] = $timestamp;
                }
            } else {
                $posts_without_date[] = $post_id;
            }
        }
        wp_reset_postdata();

        // Sorter etter timestamp
        asort($posts_with_date);

        // Kombiner sorterte poster
        $sorted_posts = array_merge(array_keys($posts_with_date), $posts_without_date);
        
        // Ny spørring med sorterte poster
        $base_args['post__in'] = $sorted_posts;
        $base_args['orderby'] = 'post__in';
    }

    $query = new WP_Query($base_args);

    if ($query->have_posts()) {
        ob_start();
        while ($query->have_posts()) {
            $query->the_post();
            include __DIR__ . '/../partials/coursedates_default.php';
        }
        wp_reset_postdata();

        wp_send_json_success([
            'html' => ob_get_clean(),
            'max_num_pages' => $query->max_num_pages
        ]);
    } else {
        wp_send_json_error([
            'message' => '<div class="filter-no-results"><strong>Ingen resultater</strong> <br>Prøv å fjerne ett eller flere filtre, eller <a style="display:inline-block; padding:0;font-size: inherit;" href="#" id="reset-filters-message" class="reset-filters reset-filters-btn">nullstill alle filtre</a>.</div>',
            'debug' => [
                'query_vars' => $query->query_vars,
                'sql' => $query->request,
                'filters' => $_POST
            ]
        ]);
    }
}


add_action('wp_ajax_get_course_price_range', 'get_course_price_range');
add_action('wp_ajax_nopriv_get_course_price_range', 'get_course_price_range');

function get_course_price_range() {
    global $wpdb;

    $min_price = $wpdb->get_var("SELECT MIN(CAST(meta_value AS UNSIGNED)) FROM {$wpdb->postmeta} WHERE meta_key = 'course_price'");
    $max_price = $wpdb->get_var("SELECT MAX(CAST(meta_value AS UNSIGNED)) FROM {$wpdb->postmeta} WHERE meta_key = 'course_price'");

    wp_send_json_success([
        'min_price' => $min_price ? intval($min_price) : 0,
        'max_price' => $max_price ? intval($max_price) : 10000
    ]);
}

function handle_course_filter() {
    check_ajax_referer('kursagenten_ajax_nonce', 'security');
    
    $filters = $_POST['filters'] ?? [];
    $search = sanitize_text_field($_POST['search'] ?? '');
    $sort = sanitize_text_field($_POST['sort'] ?? '');
    $order = sanitize_text_field($_POST['order'] ?? '');
    
    $args = get_course_dates_query_args($filters, $search);
    
    // Legg til sortering
    if ($sort && $order) {
        switch ($sort) {
            case 'title':
                $args['orderby'] = 'title';
                $args['order'] = strtoupper($order);
                break;
            case 'price':
                $args['orderby'] = 'meta_value_num';
                $args['meta_key'] = 'course_price';
                $args['order'] = strtoupper($order);
                break;
            case 'date':
                $args['orderby'] = 'meta_value';
                $args['meta_key'] = 'course_start_date';
                $args['meta_type'] = 'DATE';
                $args['order'] = strtoupper($order);
                break;
        }
    }
    
    $query = new WP_Query($args);
    
} 
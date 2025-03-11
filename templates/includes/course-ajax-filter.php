<?php

add_action('wp_ajax_filter_courses', 'filter_courses_handler');
add_action('wp_ajax_nopriv_filter_courses', 'filter_courses_handler');

function filter_courses_handler() {
    error_log('Starting filter_courses_handler');
    error_log('POST data: ' . print_r($_POST, true));
    error_log('REQUEST data: ' . print_r($_REQUEST, true));

    // Verifiser nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'filter_nonce')) {
        error_log('Nonce verification failed');
        wp_send_json_error([
            'message' => 'Sikkerhetssjekk feilet. Vennligst oppdater siden og prøv igjen.',
            'debug' => [
                'nonce_exists' => isset($_POST['nonce']),
                'nonce_value' => $_POST['nonce'] ?? 'missing'
            ]
        ], 403);
    }

    try {
        // Håndter datofilteret - sjekk både POST og REQUEST
        $date_param = $_POST['dato'] ?? $_REQUEST['dato'] ?? null;
        if (!empty($date_param)) {
            error_log('Processing date filter: ' . print_r($date_param, true));
            
            if (is_string($date_param)) {
                $dates = explode('-', sanitize_text_field($date_param));
                if (count($dates) === 2) {
                    $from_date = \DateTime::createFromFormat('d.m.Y', trim($dates[0]));
                    $to_date = \DateTime::createFromFormat('d.m.Y', trim($dates[1]));
                    
                    if ($from_date && $to_date) {
                        error_log('Parsed dates - From: ' . $from_date->format('Y-m-d') . ', To: ' . $to_date->format('Y-m-d'));
                        
                        $_REQUEST['dato'] = [
                            'from' => $from_date->format('Y-m-d'),
                            'to' => $to_date->format('Y-m-d')
                        ];
                    }
                }
            }
        }

        $query = get_course_dates_query();
        error_log('Query args: ' . print_r($query->query_vars, true));

        if ($query->have_posts()) {
            ob_start();
            while ($query->have_posts()) {
                $query->the_post();
                include __DIR__ . '/../partials/coursedates_default.php';
            }
            wp_reset_postdata();

            error_log('AJAX FILTER - Debug pagination:');
            error_log('POST paged: ' . (isset($_POST['paged']) ? $_POST['paged'] : 'not set'));
            error_log('Query max_num_pages: ' . $query->max_num_pages);
            error_log('Query current page: ' . $query->get('paged'));
            error_log('Query posts per page: ' . $query->get('posts_per_page'));
            error_log('Query found posts: ' . $query->found_posts);

            $url_options = get_option('kag_seo_option_name');
            $kurs = !empty($url_options['ka_url_rewrite_kurs']) ? $url_options['ka_url_rewrite_kurs'] : 'kurs';
            $pagination = paginate_links([
                'base' => get_home_url(null, $kurs) .'?%_%',
                'current' => max(1, $query->get('paged')),
                'format' => 'side=%#%',
                'total' => $query->max_num_pages,
                'add_args' => array_map(function ($item) {
                    return is_array($item) ? join(',', $item) : $item;
                }, array_diff_key($_REQUEST, ['side' => true, 'action' => true, 'nonce' => true]))
            ]);

            wp_send_json_success([
                'html' => ob_get_clean(),
                'html_pagination' => $pagination,
                'max_num_pages' => $query->max_num_pages,
                'course-count' => sprintf(
                    '%d kurs %s',
                    $query->found_posts,
                    $query->max_num_pages > 1 ? sprintf(
                        '- side %d av %d',
                        $query->get('paged'),
                        $query->max_num_pages
                    ) : ''
                )
            ]);
        } else {
            wp_send_json_success([
                'html' => '<div class="filter-no-results"><p>Ingen kurs funnet. Fjern ett eller flere filtre, eller<a href="#" class="reset-filters"> nullstill alle filtre</a></p></div>',
                'html_pagination' => '',
                'max_num_pages' => 0,
                'course-count' => ''
            ]);
        }
    } catch (Exception $e) {
        error_log('Error in filter_courses_handler: ' . $e->getMessage());
        error_log('Stack trace: ' . $e->getTraceAsString());
        wp_send_json_error([
            'message' => 'En feil oppstod under filtreringen.',
            'debug' => $e->getMessage()
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

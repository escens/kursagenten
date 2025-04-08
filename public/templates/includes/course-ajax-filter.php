<?php
// Sjekk om template-functions.php er lastet
if (!function_exists('get_course_template_part')) {
    $template_functions_path = KURSAG_PLUGIN_DIR . 'public/templates/includes/template-functions.php';
    if (!file_exists($template_functions_path)) {
        error_log('ERROR: Could not find template functions file at: ' . $template_functions_path);
    } else {
        require_once $template_functions_path;
    }
}

add_action('wp_ajax_filter_courses', 'filter_courses_handler');
add_action('wp_ajax_nopriv_filter_courses', 'filter_courses_handler');

function filter_courses_handler() {
    // Verifiser nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'filter_nonce')) {
        error_log('Nonce verification failed in filter_courses_handler');
        wp_send_json_error([
            'message' => 'Sikkerhetssjekk feilet. Vennligst oppdater siden og prøv igjen.'
        ], 403);
    }

    try {
        // Logg innkommende parametre
        //error_log('Incoming request parameters: ' . print_r($_REQUEST, true));
        //error_log('Incoming POST parameters: ' . print_r($_POST, true));

        // Håndter datofilteret
        $date_param = $_POST['dato'] ?? $_REQUEST['dato'] ?? null;
        if (!empty($date_param) && is_string($date_param)) {
            $dates = explode('-', sanitize_text_field($date_param));
            if (count($dates) === 2) {
                $from_date = \DateTime::createFromFormat('d.m.Y', trim($dates[0]));
                $to_date = \DateTime::createFromFormat('d.m.Y', trim($dates[1]));
                
                if ($from_date && $to_date) {
                    $_REQUEST['dato'] = [
                        'from' => $from_date->format('Y-m-d'),
                        'to' => $to_date->format('Y-m-d')
                    ];
                }
            }
        }

        $query = get_course_dates_query();

        // Håndter søk
        $search_param = $_POST['sok'] ?? $_REQUEST['sok'] ?? null;
        if (!empty($search_param)) {
            $_REQUEST['s'] = sanitize_text_field($search_param);
        }
        
        // Logg query-parametere
        //error_log('Query parameters: ' . print_r($query->query_vars, true));
        //error_log('Current page: ' . $query->get('paged'));
        //error_log('Max pages: ' . $query->max_num_pages);

        if ($query->have_posts()) {
            ob_start();
            $context = is_tax() ? 'taxonomy' : 'archive';
            
            while ($query->have_posts()) {
                $query->the_post();
                try {
                    if (!function_exists('get_course_template_part')) {
                        $fallback_template = __DIR__ . '/../list-types/standard.php';
                        include $fallback_template;
                    } else {
                        $style = get_option('kursagenten_archive_list_type', 'standard');
                        $template_path = KURSAG_PLUGIN_DIR . "public/templates/list-types/{$style}.php";
                        
                        if (file_exists($template_path)) {
                            include $template_path;
                        } else {
                            include KURSAG_PLUGIN_DIR . 'public/templates/list-types/standard.php';
                        }
                    }
                } catch (Exception $e) {
                    error_log('Error loading template in filter_courses_handler: ' . $e->getMessage());
                }
            }
            wp_reset_postdata();

            // Hent gjeldende forespørsels-URL som base for paginering
            $current_url = '';

            // Prøv først å hente fra HTTP_REFERER
            if (!empty($_SERVER['HTTP_REFERER'])) {
                $referer = wp_parse_url($_SERVER['HTTP_REFERER']);
                if ($referer && isset($referer['path'])) {
                    $current_url = home_url($referer['path']);
                }
            }

            // Fallback til REQUEST_URI hvis HTTP_REFERER ikke er tilgjengelig
            if (empty($current_url)) {
                $request_uri = $_SERVER['REQUEST_URI'];
                // Fjern query-parametre
                $path = strtok($request_uri, '?');
                $current_url = home_url($path);
            }

            //error_log('Original request URL: ' . $_SERVER['HTTP_REFERER']);
            //error_log('Parsed current URL for pagination: ' . $current_url);

            // Fjern eventuelle eksisterende side-parametre fra URL-en
            $current_url = remove_query_arg('side', $current_url);
            
            //error_log('URL after removing side parameter: ' . $current_url);

            // Bygg pagineringsparametere
            $pagination_args = [
                'base' => $current_url . '%_%',
                'current' => max(1, $query->get('paged')),
                'format' => '?side=%#%',
                'total' => $query->max_num_pages,
                'add_args' => array_map(function ($item) {
                    return is_array($item) ? join(',', $item) : $item;
                }, array_diff_key($_REQUEST, ['side' => true, 'action' => true, 'nonce' => true, 'coursedate' => true, 'course' => true]))
            ];
            
            //error_log('Pagination arguments: ' . print_r($pagination_args, true));

            $pagination = paginate_links($pagination_args);
            
            //error_log('Generated pagination HTML: ' . $pagination);

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
        wp_send_json_error([
            'message' => 'En feil oppstod under filtreringen.'
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

// Legg til AJAX-handler for mobilfiltre
add_action('wp_ajax_load_mobile_filters', 'ka_load_mobile_filters');
add_action('wp_ajax_nopriv_load_mobile_filters', 'ka_load_mobile_filters');

function ka_load_mobile_filters() {
    try {
        if (!check_ajax_referer('filter_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Invalid nonce']);
            return;
        }
        
        // Last inn nødvendige filer
        if (!function_exists('get_course_languages')) {
            $queries_path = KURSAG_PLUGIN_DIR . 'public/templates/includes/queries.php';
            if (!file_exists($queries_path)) {
                error_log('Could not find queries.php at: ' . $queries_path);
                wp_send_json_error(['message' => 'Required files not found']);
                return;
            }
            require_once $queries_path;
        }
        
        // Last inn mobilfilter-malen
        $template_path = KURSAG_PLUGIN_DIR . 'public/templates/mobile-filters.php';
        
        error_log('Looking for template at: ' . $template_path);
        
        if (!file_exists($template_path)) {
            error_log('Template file not found at: ' . $template_path);
            wp_send_json_error(['message' => 'Template file not found: ' . $template_path]);
            return;
        }
        
        // Start output buffering
        ob_start();
        include $template_path;
        $html = ob_get_clean();
        
        if (empty($html)) {
            error_log('Empty template content from: ' . $template_path);
            wp_send_json_error(['message' => 'Empty template content']);
            return;
        }
        
        error_log('Successfully loaded mobile filters template. Content length: ' . strlen($html));
        wp_send_json_success(['html' => $html]);
        
    } catch (Exception $e) {
        error_log('Error in ka_load_mobile_filters: ' . $e->getMessage());
        wp_send_json_error(['message' => 'En feil oppstod: ' . $e->getMessage()]);
    }
}

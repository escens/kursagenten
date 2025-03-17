<?php
// Sjekk om template-functions.php er lastet
if (!function_exists('get_course_template_part')) {
    $template_functions_path = KURSAG_PLUGIN_DIR . 'includes/templates/template-functions.php';
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
                        $template_path = KURSAG_PLUGIN_DIR . "templates/list-types/{$style}.php";
                        
                        if (file_exists($template_path)) {
                            include $template_path;
                        } else {
                            include KURSAG_PLUGIN_DIR . 'templates/list-types/standard.php';
                        }
                    }
                } catch (Exception $e) {
                    error_log('Error loading template in filter_courses_handler: ' . $e->getMessage());
                }
            }
            wp_reset_postdata();

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

/**
 * Hjelpefunksjon for å finne riktig template path
 */
/*
function get_ajax_template_path($context = 'archive') {
    $style = get_option('kursagenten_' . $context . '_list_type', 'standard');
    error_log('Template style from get_ajax_template_path: ' . $style);
    
    $possible_paths = [
        KURSAG_PLUGIN_DIR . "templates/list-types/{$style}.php",
        dirname(__FILE__) . "/../list-types/{$style}.php",
        dirname(__FILE__) . "/../partials/coursedates_{$style}.php",
        dirname(__FILE__) . "/../partials/coursedates_default.php"
    ];
    
    foreach ($possible_paths as $path) {
        error_log('Checking template path: ' . $path);
        if (file_exists($path)) {
            error_log('Found valid template at: ' . $path);
            return $path;
        }
    }
    
    error_log('No valid template found, using default');
    return dirname(__FILE__) . "/../partials/coursedates_default.php";
}
*/
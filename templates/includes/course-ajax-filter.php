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

    $args = [
        'post_type'      => 'coursedate',
        'posts_per_page' => 10,
        'paged'          => isset($_POST['paged']) ? intval($_POST['paged']) : 1,
        'tax_query'      => ['relation' => 'AND'],
        'meta_query'     => ['relation' => 'AND'],
    ];

    // Logg mottatte filterdata
    error_log('Mottatte filterdata: ' . print_r($_POST, true));

    $filter_settings = get_option('design_option_name', []);

    // Håndtering av taxonomi-baserte filtre
    $taxonomies = [
        'k' => 'coursecategory',
        'sted' => 'course_location',
        'i' => 'instructors'
    ];

    foreach ($taxonomies as $param => $taxonomy) {
        if (!empty($_POST[$param])) {
            $filter_value = is_array($_POST[$param]) ? 
                array_map('sanitize_text_field', $_POST[$param]) : 
                explode(',', sanitize_text_field($_POST[$param]));
            
            error_log("Taxonomy filter for {$taxonomy}: " . print_r($filter_value, true));
            
            if (!empty($filter_value)) {
                $args['tax_query'][] = [
                    'taxonomy' => $taxonomy,
                    'field'    => 'slug',
                    'terms'    => $filter_value,
                    'operator' => 'IN'
                ];
            }
        }
    }

    // Håndtering av metafelter
    $meta_fields = [
        'time_of_day' => 'time_of_day',
        'sprak' => 'course_language',
        'price' => 'course_price',
        'dato' => 'course_first_date'
    ];

    error_log('Mottatt dato filter: ' . print_r($_POST['dato'], true));

    foreach ($meta_fields as $param => $meta_key) {
        if (!empty($_POST[$param])) {
            if ($param === 'price') {
                if (!empty($_POST['price_min']) && !empty($_POST['price_max'])) {
                    $args['meta_query'][] = [
                        'key'     => 'course_price',
                        'value'   => [floatval($_POST['price_min']), floatval($_POST['price_max'])],
                        'compare' => 'BETWEEN',
                        'type'    => 'NUMERIC'
                    ];
                }
            } elseif ($param === 'dato') {
                if (!empty($_POST['dato']['from']) && !empty($_POST['dato']['to'])) {
                    $from_date_raw = sanitize_text_field($_POST['dato']['from']);
                    $to_date_raw = sanitize_text_field($_POST['dato']['to']);
                
                    error_log("Datoer mottatt: $from_date_raw og $to_date_raw");
                
                    // Konverter fra 'YYYY-MM-DD' (fra frontend) til 'DD.MM.YYYY' (for sammenligning i databasen)
                    $from_date_obj = DateTime::createFromFormat('Y-m-d', $from_date_raw);
                    $to_date_obj = DateTime::createFromFormat('Y-m-d', $to_date_raw);
                
                    if ($from_date_obj !== false && $to_date_obj !== false) {
                        $from_date = $from_date_obj->format('d.m.Y'); // Matcher formatet i databasen
                        $to_date = $to_date_obj->format('d.m.Y');
                
                        error_log("Filtrerer kurs mellom: $from_date og $to_date");
                
                        // Bruker STR_TO_DATE for å konvertere datoformatet i spørringen
                        $args['meta_query'][] = [
                            'key'     => $meta_key,
                            'value'   => array('2025-01-01', '2025-03-01'),
                            'compare' => 'BETWEEN',
                            'type'    => 'date' // Må bruke CHAR siden datoene er lagret som DD.MM.YYYY
                        ];

                        /*$args['meta_query'] = [
                            'relation' => 'AND',
                            [
                                'key'     => 'course_first_date',
                                'value'   => strtotime($to_date),
                                'compare' => '<=',
                                'type'    => 'numeric'
                            ],
                            [ 
                                'key'     => 'course_first_date',
                                'value'   => strtotime($from_date),
                                'compare' => '>=',
                                'type'    => 'numeric'
                            ]
                        ];*/
                
                        // Alternativ sikkerhet: Hvis STR_TO_DATE() ikke fungerer, fallback til LIKE for spesifikke måneder
                        /*if (!empty($_POST['dato']['month_filter'])) {
                            $args['meta_query'][] = [
                                'key'     => $meta_key,
                                'value'   => "%.$from_date_obj->format('m.Y')", // F.eks. '.01.2025' for januar 2025
                                'compare' => 'LIKE'
                            ];
                        }*/
                    } else {
                        error_log("Feil: Kunne ikke konvertere datoene! Råverdier: From: $from_date_raw | To: $to_date_raw");
                    }
                }
            } elseif ($param === 'sprak') {
                // Konverter input til lowercase for konsistens
                $sprak_filter = is_array($_POST[$param]) ? array_map('strtolower', array_map('sanitize_text_field', $_POST[$param])) : [strtolower(sanitize_text_field($_POST[$param]))];

                error_log('Språkfilter verdier: ' . print_r($sprak_filter, true));
                
                // Konverter språkverdier til stor forbokstav for å matche databasen
                $language_values = array_map(function($lang) {
                    return ucfirst(strtolower($lang));
                }, $sprak_filter);
                
                error_log('Språkfilter verdier etter konvertering: ' . print_r($language_values, true));
                
                if (in_array('Norsk', $language_values)) {
                    $args['meta_query'][] = [
                        'relation' => 'OR',
                        [
                            'key' => 'course_language',
                            'value' => 'Norsk',
                            'compare' => '='
                        ],
                        [
                            'key' => 'course_language',
                            'compare' => 'NOT EXISTS'
                        ],
                        [
                            'key' => 'course_language',
                            'value' => '',
                            'compare' => '='
                        ]
                    ];
                } else {
                    $args['meta_query'][] = [
                        'key' => 'course_language',
                        'value' => $language_values,
                        'compare' => 'IN'
                    ];
                }
            } else {
                $args['meta_query'][] = [
                    'key'     => $meta_key,
                    'value'   => strtolower(sanitize_text_field($_POST[$param])),
                    'compare' => '='
                ];
            }
        }
    }

    // Filtrering basert på søk
    if (!empty($_POST['sok'])) {
        $args['s'] = sanitize_text_field($_POST['sok']);
    }

    // Legg til debugging for meta_query
    error_log('Meta query struktur: ' . print_r($args['meta_query'], true));

    // Logg den endelige spørringen
    error_log('Endelig WP_Query args: ' . print_r($args, true));

    // Utfør spørringen
    $query = new WP_Query($args);
    
    // Debugging for resultater
    error_log('Antall funnet poster: ' . $query->found_posts);
    error_log('SQL spørring: ' . $query->request);
    
    // For å sjekke faktiske verdier i databasen
    if ($query->found_posts === 0) {
        global $wpdb;
        $test_query = $wpdb->prepare(
            "SELECT p.ID, p.post_title, pm.meta_value as language, t.name as location, i.name as instructor
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'course_language'
            LEFT JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
            LEFT JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            LEFT JOIN {$wpdb->terms} t ON tt.term_id = t.term_id AND tt.taxonomy = 'course_location'
            LEFT JOIN {$wpdb->term_relationships} tr2 ON p.ID = tr2.object_id
            LEFT JOIN {$wpdb->term_taxonomy} tt2 ON tr2.term_taxonomy_id = tt2.term_taxonomy_id AND tt2.taxonomy = 'instructors'
            LEFT JOIN {$wpdb->terms} i ON tt2.term_id = i.term_id
            WHERE p.post_type = 'coursedate'
            LIMIT 5"
        );
        $sample_data = $wpdb->get_results($test_query);
        error_log('Eksempel på kursdata i databasen: ' . print_r($sample_data, true));
    }

    if ($query->have_posts()) {
        ob_start();
        while ($query->have_posts()) {
            $query->the_post();
            include __DIR__ . '/../partials/coursedates_default.php';
        }
        wp_reset_postdata();

        wp_send_json_success([
            'html' => ob_get_clean(),
            'max_num_pages' => $query->max_num_pages,
            'debug' => [
                'found_posts' => $query->found_posts,
                'query_vars' => $query->query_vars,
                'sql' => $query->request
            ]
        ]);
    } else {
        wp_send_json_error([
            'message' => '<strong>Ingen resultater</strong> <br>Prøv å fjerne ett eller flere filtre, eller <a style="display:inline-block; padding:0;font-size: inherit;" href="#" id="reset-filters-message" class="reset-filters reset-filters-btn">nullstill alle filtre</a>.',
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


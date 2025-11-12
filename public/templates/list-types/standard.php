<?php

if (!function_exists('kursagenten_normalize_bool')) {
    /**
     * Normalize truthy values from metadata to strict booleans.
     */
    function kursagenten_normalize_bool($value): bool {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        if (is_string($value)) {
            $value = strtolower(trim($value));
            return in_array($value, ['1', 'true', 'yes', 'on'], true);
        }

        return false;
    }
}

// Sjekk visningstype fra args
$view_type = isset($args['view_type']) ? $args['view_type'] : 'all_coursedates';
$is_taxonomy_page = isset($args['is_taxonomy_page']) && $args['is_taxonomy_page'];

// Sjekk om vi skal tvinge standard visning (fra kortkode)
$force_standard_view = isset($args['force_standard_view']) && $args['force_standard_view'] === true;

// Hvis visningstype er 'main_courses', vis hovedkurs med første tilgjengelige dato
if ($view_type === 'main_courses' && !$force_standard_view) {
    $course_id = get_the_ID();
    $course_title = get_the_title();
    $excerpt = get_the_excerpt();
    
    // Hent location_id (API-ID) for å finne relaterte kursdatoer
    // Kursdatoer har main_course_id som matcher kursets main_course_id (ikke location_id)
    $is_parent = get_post_meta($course_id, 'ka_is_parent_course', true);
    
    if ($is_parent === 'yes') {
        // For hovedkurs: bruk ka_location_id (som er samme som ka_main_course_id)
        $search_id = get_post_meta($course_id, 'ka_location_id', true);
    } else {
        // For underkurs: bruk ka_main_course_id
        $search_id = get_post_meta($course_id, 'ka_main_course_id', true);
    }
    
    // Check if we need to filter by location (taxonomy page)
    $taxonomy = isset($args['taxonomy']) ? $args['taxonomy'] : null;
    $current_term = isset($args['current_term']) ? $args['current_term'] : null;
    
    // Build meta query for coursedates
    $meta_query = [
        ['key' => 'ka_main_course_id', 'value' => $search_id],
    ];
    
    // If on a location taxonomy page, filter coursedates by that location
    if ($taxonomy === 'ka_course_location' && $current_term) {
        $meta_query[] = [
            'key' => 'ka_course_location',
            'value' => $current_term->name,
            'compare' => '='
        ];
    }
    
    $related_coursedates = get_posts([
        'post_type' => 'ka_coursedate',
        'posts_per_page' => -1,
        'meta_query' => $meta_query,
    ]);
    
    // Konverter til array av IDer
    $related_coursedate_ids = array_map(function($post) {
        return $post->ID;
    }, $related_coursedates);
    
    // Hent data fra første tilgjengelige kursdato
    $selected_coursedate_data = get_selected_coursedate_data($related_coursedate_ids);
    
    // Hent lokasjonsinformasjon fra den valgte kursdatoen
    $location = $selected_coursedate_data['location'] ?? '';
    $location_freetext = $selected_coursedate_data['location_freetext'] ?? '';
    $location_room = $selected_coursedate_data['course_location_room'] ?? '';
    
    // Hent bilde
    // Hent plassholderbilde fra innstillinger
    $options = get_option('design_option_name');
    $placeholder_image = !empty($options['ka_plassholderbilde_kurs']) 
        ? $options['ka_plassholderbilde_kurs']
        : rtrim(KURSAG_PLUGIN_URL, '/') . '/assets/images/placeholder-kurs.jpg';
    
    $featured_image_thumb = get_the_post_thumbnail_url($course_id, 'thumbnail') ?: $placeholder_image;
    
    // Sett opp link til kurset
    $course_link = get_permalink($course_id);
    
    // Hent data fra første tilgjengelige kursdato
    $first_course_date = $selected_coursedate_data['first_date'] ?? '';
    $last_course_date = $selected_coursedate_data['last_date'] ?? '';
    $price = $selected_coursedate_data['price'] ?? '';
    $after_price = $selected_coursedate_data['after_price'] ?? '';
    $duration = $selected_coursedate_data['duration'] ?? '';
    $coursetime = $selected_coursedate_data['time'] ?? '';
    $course_days = $selected_coursedate_data['course_days'] ?? '';
    $button_text = $selected_coursedate_data['button_text'] ?? '';
    $signup_url = $selected_coursedate_data['signup_url'] ?? '';
    $is_full = kursagenten_normalize_bool($selected_coursedate_data['is_full'] ?? false);
    $show_registration = kursagenten_normalize_bool($selected_coursedate_data['show_registration'] ?? false);
} else {
    // Original kode for coursedates
    $course_id = get_the_ID();

    $course_title =             get_post_meta($course_id, 'ka_course_title', true);
    $first_course_date =        ka_format_date(get_post_meta($course_id, 'ka_course_first_date', true));
    $last_course_date =         ka_format_date(get_post_meta($course_id, 'ka_course_last_date', true));
    $registration_deadline =    ka_format_date(get_post_meta($course_id, 'ka_course_registration_deadline', true));
    $duration =                 get_post_meta($course_id, 'ka_course_duration', true);
    $coursetime =               get_post_meta($course_id, 'ka_course_time', true);
    $course_days =              get_post_meta($course_id, 'ka_course_days', true);
    $price =                    get_post_meta($course_id, 'ka_course_price', true);
    $after_price =              get_post_meta($course_id, 'ka_course_text_after_price', true);
    $location =                 get_post_meta($course_id, 'ka_course_location', true);
    $location_freetext =        get_post_meta($course_id, 'ka_course_location_freetext', true);
    $location_room =            get_post_meta($course_id, 'ka_course_location_room', true);
    $is_full_meta =             get_post_meta($course_id, 'ka_course_isFull', true);
    $marked_as_full_meta =      get_post_meta($course_id, 'ka_course_markedAsFull', true);
    $is_full =                  kursagenten_normalize_bool($is_full_meta) || kursagenten_normalize_bool($marked_as_full_meta);
    $show_registration_meta =   get_post_meta($course_id, 'ka_course_showRegistrationForm', true);
    $show_registration =        kursagenten_normalize_bool($show_registration_meta);

    $button_text =              get_post_meta($course_id, 'ka_course_button_text', true);
    $signup_url =               get_post_meta($course_id, 'ka_course_signup_url', true);

    $related_course_id =        get_post_meta($course_id, 'ka_location_id', true);

    $related_course_info = get_course_info_by_location($related_course_id);

    // Hent plassholderbilde fra innstillinger
    $options = get_option('design_option_name');
    $placeholder_image = !empty($options['ka_plassholderbilde_kurs']) 
        ? $options['ka_plassholderbilde_kurs']
        : rtrim(KURSAG_PLUGIN_URL, '/') . '/assets/images/placeholder-kurs.jpg';

    if ($related_course_info) {
        $course_link = esc_url($related_course_info['permalink']);
        $featured_image_thumb = $related_course_info['thumbnail'] ?: $placeholder_image;
        $excerpt = $related_course_info['excerpt'];
    } else {
        // Hvis ingen relatert kursinfo, bruk plassholderbilde og fallback-data
        $featured_image_thumb = $placeholder_image;
        $course_link = false;
        $excerpt = '';
    }
}

$course_count = $course_count ?? 0;
$item_class = $course_count === 1 ? ' single-item' : '';

// Sjekk om bilder skal vises
// Prioritet: shortcode attributt > taksonomi-spesifikk innstilling > global innstilling
$shortcode_show_images = isset($args['shortcode_show_images']) ? $args['shortcode_show_images'] : null;

// If shortcode explicitly sets bilder parameter to 'yes' or 'no', use it
if ($shortcode_show_images === 'yes' || $shortcode_show_images === 'no') {
    // Bruk shortcode attributt hvis eksplisitt satt til yes eller no
    $show_images = $shortcode_show_images;
} elseif ($is_taxonomy_page && !$force_standard_view) {
    // Taksonomi-side: bruk taksonomi-innstillinger med proper override handling
    $taxonomy = get_queried_object()->taxonomy;
    $show_images = get_taxonomy_setting($taxonomy, 'show_images', 'yes');
} else {
    // Standard: bruk global innstilling
    $show_images = get_option('kursagenten_show_images', 'yes');
}

$with_image_class = $show_images === 'yes' ? ' with-image' : '';

// Hent instruktører for kurset
$instructors = get_the_terms($course_id, 'ka_instructors');
$instructor_links = [];
if (!empty($instructors) && !is_wp_error($instructors)) {
    $instructor_links = array_map(function ($term) {
        $instructor_url = get_instructor_display_url($term, 'ka_instructors');
        // Bruk samme navnevisningslogikk som i default.php
        $name_display = get_option('kursagenten_taxonomy_instructors_name_display', '');
        $display_name = $term->name;
        
        if ($name_display === 'firstname') {
            $firstname = get_term_meta($term->term_id, 'instructor_firstname', true);
            if (!empty($firstname)) {
                $display_name = $firstname;
            }
        } elseif ($name_display === 'lastname') {
            $lastname = get_term_meta($term->term_id, 'instructor_lastname', true);
            if (!empty($lastname)) {
                $display_name = $lastname;
            }
        }
        
        return '<a href="' . esc_url($instructor_url) . '">' . esc_html($display_name) . '</a>';
    }, $instructors);
}

?>
<?php
// Hent kurskategorier for data-category attributt
$course_categories = get_the_terms($course_id, 'ka_coursecategory');
$category_slugs = [];
if (!empty($course_categories) && !is_wp_error($course_categories)) {
    foreach ($course_categories as $category) {
        // Bruk kun den faktiske kategorien kurset tilhører
        $category_slugs[] = $category->slug;
    }
}
$category_slugs = array_unique($category_slugs);

// Generate view type class
$view_type_class = ' view-type-' . str_replace('_', '', $view_type);
?>
<div class="courselist-item<?php echo $item_class . $view_type_class; ?>" data-location="<?php echo esc_attr($location_freetext); ?>" data-category="<?php echo esc_attr(implode(' ', $category_slugs)); ?>">
    <div class="courselist-main<?php echo $with_image_class; ?>">
        <?php if ($show_images === 'yes') : ?>
        <!-- Image area -->
        <div class="image column" style="background-image: url(<?php echo esc_url($featured_image_thumb); ?>);">
            <a class="image-inner" href="<?php echo esc_url($course_link); ?>" title="<?php echo esc_attr($course_title); ?>" aria-label="Se kurs: <?php echo esc_attr($course_title); ?>">
                <span class="sr-only">Se kurs: <?php echo esc_html($course_title); ?></span>
            </a>
        </div>
        <?php endif; ?>
        <div class="text-area-wrapper">
            <!-- Text area -->
            <div class="text-area column">
                <!-- Title area -->
                <div class="title-area">
                    <h3 class="course-title">
                        <a href="<?php echo esc_url($course_link); ?>" class="course-link"><?php echo esc_html($course_title); ?></a>
                        <?php if ($is_full) : ?>
                            <span class="course-available full">Fullt</span>
                        <?php elseif (!$show_registration) : ?>
                            <span class="course-available on-demand">På forespørsel</span>
                        <?php else : ?>
                            <span class="course-available">Ledige plasser</span>
                        <?php endif; ?>
                    </h3>
                                    
                </div>
                <!-- Details area - date and location -->
                <div class="details-area iconlist horizontal">
                    <?php if ($view_type === 'main_courses' && !$force_standard_view) : ?>
                        <?php if (!empty($first_course_date)) : ?>
                            <div class="startdate">
                                <i class="ka-icon icon-calendar"></i> <span class="ka-next-course">Neste kurs: &nbsp;</span> <?php echo esc_html($first_course_date); ?>
                                <?php if (count($related_coursedate_ids) > 1) : ?>
                                    <a href="#" class="show-ka-modal" data-course-id="<?php echo esc_attr($course_id); ?>" style="margin-left: 8px; font-size: 0.9em;">
                                        (+<?php echo count($related_coursedate_ids) - 1; ?> flere)
                                    </a>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($coursetime)) : ?><div class="coursetime"><i class="ka-icon icon-time"></i> <?php echo esc_html($coursetime); ?></div><?php endif; ?>
                        <?php endif; ?>
                        <?php if (!empty($location)) : ?>
                            <div class="location">
                                <div class="location-text"><i class="ka-icon icon-location"></i><?php echo esc_html($location); ?></div>
                                <?php if (!empty($location_freetext)) : ?>
                                    <div class="location_freetext">
                                        &nbsp;(<?php echo esc_html($location_freetext); ?>)
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                    <?php else : ?>
                        <?php if (!empty($first_course_date)) : ?>
                            <div class="startdate"><i class="ka-icon icon-calendar"></i><?php echo esc_html($first_course_date); ?></div>
                        <?php endif; ?>
                    
                        <?php if (!empty($location)) : ?>
                            <div class="location">
                                <div class="location-text"><i class="ka-icon icon-location"></i><?php echo esc_html($location); ?></div>
                                <?php if (!empty($location_freetext)) : ?>
                                    <div class="location_freetext">
                                        &nbsp;(<?php echo esc_html($location_freetext); ?>)
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <!-- Meta area -->
                <div class="meta-area iconlist horizontal">
                    <?php if (!empty($coursetime) || !empty($course_days)) : ?>
                        <div class="coursetime">
                            <i class="ka-icon icon-time"></i>
                            <?php if (!empty($course_days)) : ?><?php echo esc_html($course_days); ?> <?php endif; ?>
                            <?php if (!empty($coursetime)) : ?><?php echo esc_html($coursetime); ?><?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($duration)) : ?>
                        <div class="duration"><i class="ka-icon icon-timer-light"></i><?php echo esc_html($duration); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($price)) : ?>
                        <div class="price"><i class="ka-icon icon-layers"></i><?php echo esc_html($price); ?> <?php echo isset($after_price) ? esc_html($after_price) : ''; ?></div>
                    <?php endif; ?>
                    <?php if (!empty($instructor_links)) : ?>
                        <div class="instructors"><i class="ka-icon icon-user"></i><?php echo implode(' ,&nbsp;', $instructor_links); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($location_room)) : ?>
                        <div class="location_room"><i class="ka-icon icon-grid"></i><?php echo esc_html($location_room); ?></div>
                    <?php endif; ?>
                    
                    <span class="accordion-icon clickopen tooltip" data-title="Se detaljer">+</span>
                </div>
                <!-- Accordion content -->
                <div class="courselist-content accordion-content">
                    <?php if (!empty($excerpt)) : ?>
                        <p><strong>Kort beskrivelse: </strong><br><?php echo wp_kses_post($excerpt); ?></p>
                    <?php endif; ?>
                    <?php if ($view_type === 'main_courses' && !$force_standard_view) : ?>
                        
                        <?php 
                        // Old location listing code removed - now using modal
                        $main_course_id = get_post_meta($course_id, 'ka_main_course_id', true);
                        
                        // If this is a main course, use course_id as main_course_id
                        if (empty($main_course_id)) {
                            $main_course_id = $course_id;
                        }
                        
                        // Get all coursedates for the main course (hidden, used for modal below)
                        $all_coursedates = get_posts([
                            'post_type' => 'ka_coursedate',
                            'posts_per_page' => -1,
                            'meta_query' => [
                                ['key' => 'ka_main_course_id', 'value' => $main_course_id],
                            ],
                        ]);
                        
                        // Collect all location data with dates
                        $locations_with_dates = [];
                        $locations_without_dates = [];
                        
                        if (!empty($all_coursedates)) {
                            foreach ($all_coursedates as $coursedate) {
                                // Get location_id (API ID) to find the related course subpage
                                $coursedate_location_id = get_post_meta($coursedate->ID, 'ka_location_id', true);
                                $coursedate_main_course_id = get_post_meta($coursedate->ID, 'ka_main_course_id', true);
                                
                                // Get location terms for this coursedate
                                $location_terms = get_the_terms($coursedate->ID, 'ka_course_location');
                                
                                if (!empty($location_terms) && !is_wp_error($location_terms) && !empty($coursedate_location_id)) {
                                    foreach ($location_terms as $location_term) {
                                        $location_slug = $location_term->slug;
                                        $location_name = $location_term->name;
                                        
                                        // Find the WordPress post (subcourse) for this location
                                        $sub_course = get_posts([
                                            'post_type' => 'ka_course',
                                            'posts_per_page' => 1,
                                            'meta_query' => [
                                                'relation' => 'AND',
                                                [
                                                    'key' => 'ka_location_id',
                                                    'value' => $coursedate_location_id,
                                                    'compare' => '='
                                                ],
                                                [
                                                    'key' => 'ka_main_course_id',
                                                    'value' => $coursedate_main_course_id,
                                                    'compare' => '='
                                                ]
                                            ]
                                        ]);
                                        
                                        // Get URL to the course subpage
                                        $location_url = !empty($sub_course) ? get_permalink($sub_course[0]->ID) : '';
                                        
                                        // Get freetext location
                                        $location_freetext = get_post_meta($coursedate->ID, 'ka_course_location_freetext', true);
                                        
                                        // Get course date
                                        $course_first_date = get_post_meta($coursedate->ID, 'ka_course_first_date', true);
                                        
                                        if (!empty($course_first_date) && !empty($location_url)) {
                                            $formatted_date = ka_format_date($course_first_date);
                                            
                                            // Store with date for sorting - use unique key with location_id
                                            $unique_key = $location_slug . '_' . $coursedate_location_id;
                                            if (!isset($locations_with_dates[$unique_key])) {
                                                $locations_with_dates[$unique_key] = [
                                                    'slug' => $location_slug,
                                                    'location_id' => $coursedate_location_id,
                                                    'location_name' => $location_name,
                                                    'location_freetext' => $location_freetext,
                                                    'url' => $location_url,
                                                    'dates' => [],
                                                ];
                                            } else {
                                                // Update freetext if we find a better one (non-empty)
                                                if (!empty($location_freetext) && empty($locations_with_dates[$unique_key]['location_freetext'])) {
                                                    $locations_with_dates[$unique_key]['location_freetext'] = $location_freetext;
                                                }
                                            }
                                            
                                            $locations_with_dates[$unique_key]['dates'][] = [
                                                'date' => $formatted_date,
                                                'date_raw' => $course_first_date,
                                            ];
                                        }
                                    }
                                }
                            }
                        }
                        
                        // Find locations without dates by checking all coursedates for this course
                        $all_location_ids = [];
                        if (!empty($all_coursedates)) {
                            foreach ($all_coursedates as $coursedate) {
                                $coursedate_location_id = get_post_meta($coursedate->ID, 'ka_location_id', true);
                                $coursedate_main_course_id = get_post_meta($coursedate->ID, 'ka_main_course_id', true);
                                $location_terms = get_the_terms($coursedate->ID, 'ka_course_location');
                                
                                if (!empty($location_terms) && !is_wp_error($location_terms) && !empty($coursedate_location_id)) {
                                    foreach ($location_terms as $location_term) {
                                        $location_slug = $location_term->slug;
                                        $unique_key = $location_slug . '_' . $coursedate_location_id;
                                        
                                        // Check if this location already has dates in our list
                                        if (!isset($locations_with_dates[$unique_key]) && !isset($all_location_ids[$coursedate_location_id])) {
                                            // Check if this location has any coursedate with a date
                                            $has_date_for_this_location = false;
                                            $location_freetext_for_no_date = '';
                                            
                                            foreach ($all_coursedates as $check_coursedate) {
                                                $check_location_id = get_post_meta($check_coursedate->ID, 'ka_location_id', true);
                                                if ($check_location_id == $coursedate_location_id) {
                                                    $check_date = get_post_meta($check_coursedate->ID, 'ka_course_first_date', true);
                                                    
                                                    // Collect freetext while checking
                                                    if (empty($location_freetext_for_no_date)) {
                                                        $location_freetext_for_no_date = get_post_meta($check_coursedate->ID, 'ka_course_location_freetext', true);
                                                    }
                                                    
                                                    if (!empty($check_date)) {
                                                        $has_date_for_this_location = true;
                                                        break;
                                                    }
                                                }
                                            }
                                            
                                            // If no dates found for this location, add to locations without dates
                                            if (!$has_date_for_this_location) {
                                                // Find the WordPress post (subcourse) for this location
                                                $sub_course = get_posts([
                                                    'post_type' => 'ka_course',
                                                    'posts_per_page' => 1,
                                                    'meta_query' => [
                                                        'relation' => 'AND',
                                                        [
                                                            'key' => 'location_id',
                                                            'value' => $coursedate_location_id,
                                                            'compare' => '='
                                                        ],
                                                        [
                                                            'key' => 'main_course_id',
                                                            'value' => $coursedate_main_course_id,
                                                            'compare' => '='
                                                        ]
                                                    ]
                                                ]);
                                                
                                                $location_url = !empty($sub_course) ? get_permalink($sub_course[0]->ID) : '';
                                                
                                                if (!empty($location_url)) {
                                                    // Build display name with freetext if available
                                                    $display_name = $location_term->name;
                                                    if (!empty($location_freetext_for_no_date)) {
                                                        $display_name .= ' (' . $location_freetext_for_no_date . ')';
                                                    }
                                                    
                                                    $locations_without_dates[] = [
                                                        'name' => $display_name,
                                                        'url' => $location_url,
                                                        'location_id' => $coursedate_location_id,
                                                    ];
                                                    $all_location_ids[$coursedate_location_id] = true;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        
                        // Sort locations with dates by first date
                        $sorted_locations_with_dates = [];
                        foreach ($locations_with_dates as $unique_key => $location_data) {
                            // Sort dates for this location
                            usort($location_data['dates'], function($a, $b) {
                                return strcmp($a['date_raw'], $b['date_raw']);
                            });
                            
                            // Build display name with freetext if available
                            $display_name = $location_data['location_name'];
                            if (!empty($location_data['location_freetext'])) {
                                $display_name .= ' (' . $location_data['location_freetext'] . ')';
                            }
                            
                            // Add to sorted array with first date for sorting
                            $sorted_locations_with_dates[] = [
                                'name' => $display_name,
                                'url' => $location_data['url'],
                                'dates' => $location_data['dates'],
                                'first_date_raw' => $location_data['dates'][0]['date_raw'],
                            ];
                        }
                        
                        // Sort by first date
                        usort($sorted_locations_with_dates, function($a, $b) {
                            return strcmp($a['first_date_raw'], $b['first_date_raw']);
                        });
                        
                        // Sort locations without dates alphabetically
                        usort($locations_without_dates, function($a, $b) {
                            return strcmp($a['name'], $b['name']);
                        });
                        
                        // Location listing removed - now shown in modal
                        ?>
                    <?php else : ?>
                    
                        <?php if (!empty($first_course_date)) : ?>
                            <p>Kurset varer fra <?php echo esc_html($first_course_date); ?><?php if (!empty($last_course_date)) : ?> til <?php echo esc_html($last_course_date); ?><?php endif; ?></p>
                        <?php else : ?>
                            <?php 
                            $is_online = has_term('nettbasert', 'ka_course_location', $course_id);
                            if ($is_online) : ?>
                                <p>Etter påmelding vil du få en e-post med mer informasjon om kurset, og hvordan det skal gjennomføres.</p>
                            <?php elseif ($show_registration) : ?>
                                <p>Du kan melde deg på kurset nå. Etter påmelding vil du få mer informasjon.</p>
                            <?php else : ?>
                                <p>Det er ikke satt opp dato for nye kurs. Meld din interesse for å få mer informasjon eller å sette deg på venteliste.</p>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                    <p><a href="<?php echo esc_url($course_link); ?>" class="course-link">Se kursdetaljer</a></p>
                </div>
            </div>
            
            <div class="links-area column">
                <?php if ($view_type === 'main_courses' && !$force_standard_view) : ?>
                    <a class="pamelding signup-link pameldingskjema" data-url="<?php echo esc_url($signup_url); ?>">
                        <?php echo esc_html($button_text ?: 'Påmelding'); ?>  <i class="ka-icon icon-arrow-right-short"></i>
                    </a>
                <?php else : ?>
                    <button class="courselist-button pamelding pameldingsknapp pameldingskjema" data-url="<?php echo esc_url($signup_url); ?>">
                        <?php echo esc_html($button_text ?: 'Påmelding'); ?>
                    </button>
                    <a href="<?php echo esc_url($course_link); ?>" class="course-link small">Mer informasjon</a>
                <?php endif; ?>
                
            </div>
        </div>
    </div>

    <?php if ($view_type === 'main_courses' && !$force_standard_view && count($related_coursedate_ids) > 1) : ?>
    <!-- Popup for alle kursdatoer -->
    <div class="ka-course-dates-modal" id="modal-<?php echo esc_attr($course_id); ?>" style="display: none;">
        <div class="ka-modal-overlay"></div>
        <div class="ka-modal-content">
            <div class="ka-modal-header">
                <h3><?php echo esc_html($course_title); ?></h3>
                <button class="ka-modal-close" aria-label="Lukk">&times;</button>
            </div>
            <div class="ka-modal-body">
                <h4>Alle tilgjengelige kurssteder og datoer</h4>
                <?php
                // Use the filtered coursedates if on a location taxonomy page
                $modal_meta_query = [
                    ['key' => 'ka_main_course_id', 'value' => $main_course_id],
                ];
                
                // If on a location taxonomy page, filter modal coursedates by that location
                if ($taxonomy === 'ka_course_location' && $current_term) {
                    $modal_meta_query[] = [
                        'key' => 'ka_course_location',
                        'value' => $current_term->name,
                        'compare' => '='
                    ];
                }
                
                // Hent alle kursdatoer (filtrert hvis på location page)
                $all_coursedates_popup = get_posts([
                    'post_type' => 'ka_coursedate',
                    'posts_per_page' => -1,
                    'meta_query' => $modal_meta_query,
                ]);
                
                // Samle lokasjonsdata
                $locations_popup = [];
                foreach ($all_coursedates_popup as $coursedate) {
                    $cd_location = get_post_meta($coursedate->ID, 'ka_course_location', true);
                    $cd_freetext = get_post_meta($coursedate->ID, 'ka_course_location_freetext', true);
                    $cd_first_date = get_post_meta($coursedate->ID, 'ka_course_first_date', true);
                    $cd_signup_url = get_post_meta($coursedate->ID, 'ka_course_signup_url', true);
                    
                    if (!empty($cd_location) && !empty($cd_first_date)) {
                        $key = $cd_location;
                        if (!isset($locations_popup[$key])) {
                            $locations_popup[$key] = [
                                'name' => $cd_location,
                                'freetext' => $cd_freetext,
                                'dates' => []
                            ];
                        }
                        $locations_popup[$key]['dates'][] = [
                            'date' => ka_format_date($cd_first_date),
                            'raw_date' => $cd_first_date,
                            'url' => $cd_signup_url
                        ];
                    }
                }
                
                // Sorter datoer innenfor hver lokasjon
                foreach ($locations_popup as &$loc_data) {
                    usort($loc_data['dates'], function($a, $b) {
                        return strcmp($a['raw_date'], $b['raw_date']);
                    });
                }
                unset($loc_data);
                
                // Vis lokasjonene med datoer
                if (!empty($locations_popup)) :
                    foreach ($locations_popup as $loc) : ?>
                        <div class="ka-location-group">
                            <h5><?php echo esc_html($loc['name']); ?><?php if (!empty($loc['freetext'])) : ?> (<?php echo esc_html($loc['freetext']); ?>)<?php endif; ?></h5>
                            <ul class="ka-dates-list">
                                <?php foreach ($loc['dates'] as $date_info) : ?>
                                    <li>
                                        <a href="#" class="pameldingskjema" data-url="<?php echo esc_url($date_info['url']); ?>">
                                            <?php echo esc_html($date_info['date']); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endforeach;
                else : ?>
                    <p>Ingen kursdatoer tilgjengelig for øyeblikket.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
</div>

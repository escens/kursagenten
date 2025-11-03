<?php

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
    
    $related_coursedates = get_posts([
        'post_type' => 'ka_coursedate',
        'posts_per_page' => -1,
        'meta_query' => [
            ['key' => 'ka_main_course_id', 'value' => $search_id],
        ],
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
    
    // Hent plassholderbilde fra innstillinger
    $options = get_option('design_option_name');
    $placeholder_image = !empty($options['ka_plassholderbilde_kurs']) 
        ? $options['ka_plassholderbilde_kurs']
        : rtrim(KURSAG_PLUGIN_URL, '/') . '/assets/images/placeholder-kurs.jpg';
    
    // Hent bilde
    $featured_image_thumb = get_the_post_thumbnail_url($course_id, 'medium') ?: $placeholder_image;
    
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
    $is_full = $selected_coursedate_data['is_full'] ?? false;
    $show_registration = $selected_coursedate_data['show_registration'] ?? false;
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
    $is_full =                  get_post_meta($course_id, 'ka_course_isFull', true);
    $show_registration =        get_post_meta($course_id, 'ka_course_showRegistrationForm', true);

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
        $featured_image_thumb = $related_course_info['thumbnail-medium'] ?: $placeholder_image;
        $excerpt = $related_course_info['excerpt'];
    } else {
        // Hvis ingen relatert kursinfo, sett fallback-verdier
        $course_link = false;
        $featured_image_thumb = $placeholder_image;
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
        return '<a href="' . esc_url($instructor_url) . '">' . esc_html($term->name) . '</a>';
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
<div class="courselist-item grid-item<?php echo $item_class . $view_type_class; ?>" data-location="<?php echo esc_attr($location_freetext); ?>" data-category="<?php echo esc_attr(implode(' ', $category_slugs)); ?>">
    <div class="courselist-card<?php echo $with_image_class; ?>">
        <?php if ($show_images === 'yes') : ?>
        <!-- Image area -->
        <div class="card-image" style="background-image: url(<?php echo esc_url($featured_image_thumb); ?>);">
            <a class="image-inner" href="<?php echo esc_url($course_link); ?>" title="<?php echo esc_attr($course_title); ?>" aria-label="Se kurs: <?php echo esc_attr($course_title); ?>">
                <span class="sr-only">Se kurs: <?php echo esc_html($course_title); ?></span>
            </a>
            <?php if ($is_full === 'true' || $is_full === 1) : ?>
                <span class="card-availability course-available full">Fullt</span>
            <?php elseif (empty($show_registration) || $show_registration === 'false') : ?>
                <span class="card-availability course-available on-demand">På forespørsel</span>
            <?php else : ?>
                <span class="card-availability course-available">Ledige plasser</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="card-content">
            <div class="card-content-upper">
                <!-- Title area -->
                <div class="title-area">
                    <h3 class="course-title">
                        <a href="<?php echo esc_url($course_link); ?>" class="course-link"><?php echo esc_html($course_title); ?></a>
                    </h3>
                    <?php if ($show_images === 'no') : ?>
                    <?php if ($is_full === 'true') : ?>
                        <div class="course-availability tooltip tooltip-left" data-title="Fullt">
                            <span class="card-availability course-available full"></span>
                        </div>
                    <?php elseif (empty($show_registration) || $show_registration === 'false') : ?>
                        <div class="course-availability tooltip tooltip-left" data-title="På forespørsel">
                            <span class="card-availability course-available on-demand"></span>
                        </div>
                    <?php else : ?>
                        <div class="course-availability tooltip tooltip-left" data-title="Ledige plasser">
                            <span class="card-availability course-available"></span>
                        </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Location -->
                <?php if (!empty($location)) : ?>
                <div class="card-location">
                    <strong><?php echo esc_html($location); ?></strong>
                </div>
                <?php endif; ?>
                
                <!-- Excerpt -->
                <?php if (!empty($excerpt)) : ?>
                <div class="card-excerpt">
                    <?php echo wp_trim_words(wp_kses_post($excerpt), 20, '...'); ?>
                </div>
                <?php endif; ?>
                
                <!-- Course details -->
                <div class="card-details">
                    <ul class="card-details-list">
                        <?php if ($view_type === 'main_courses' && !$force_standard_view) : ?>
                            <?php if (!empty($first_course_date)) : ?>
                            <li>
                                <i class="ka-icon icon-calendar"></i>
                                <span class="ka-main-color">Neste kurs: </span><?php echo esc_html($first_course_date); ?>
                                <?php if (count($related_coursedate_ids) > 1) : ?>
                                    <a href="#" class="show-ka-modal" data-course-id="<?php echo esc_attr($course_id); ?>" style="margin-left: 8px; font-size: 0.9em;">
                                        (+<?php echo count($related_coursedate_ids) - 1; ?> flere)
                                    </a>
                                <?php endif; ?>
                            </li>
                            <?php endif; ?>
                            <?php if (!empty($coursetime) || !empty($course_days)) : ?>
                            <li>
                                <i class="ka-icon icon-time"></i>
                                <?php if (!empty($course_days)) : ?><?php echo esc_html($course_days); ?> <?php endif; ?>
                                <?php if (!empty($coursetime)) : ?><?php echo esc_html($coursetime); ?><?php endif; ?>
                            </li>
                            <?php endif; ?>
                            <?php if (!empty($instructor_links)) : ?>
                            <li><i class="ka-icon icon-user"></i><?php echo implode('', $instructor_links); ?></li>
                            <?php endif; ?>
                        <?php else : ?>
                            <?php if (!empty($first_course_date)) : ?>
                            <li><i class="ka-icon icon-calendar"></i><?php echo esc_html($first_course_date); ?></li>
                            <?php endif; ?>
                            <?php if (!empty($coursetime)) : ?>
                            <li><i class="ka-icon icon-time"></i><?php echo esc_html($coursetime); ?></li>
                            <?php endif; ?>
                            <?php if (!empty($instructor_links)) : ?>
                            <li><i class="ka-icon icon-user"></i><?php echo implode(', ', $instructor_links); ?></li>
                            <?php endif; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            
            <div class="card-content-lower">
                <div class="card-separator"></div>
                
                <!-- Footer area -->
                <div class="card-footer">
                    <?php if ($view_type === 'main_courses' && !$force_standard_view) : ?>
                        <?php if (!empty($price)) : ?>
                        <div class="card-price">
                            <strong><?php echo esc_html($price); ?> <?php echo isset($after_price) ? esc_html($after_price) : ''; ?></strong>
                        </div>
                        <?php endif; ?>
                        
                        <button class="courselist-button pamelding pameldingsknapp pameldingskjema" data-url="<?php echo esc_url($signup_url); ?>">
                            <?php echo esc_html($button_text ?: 'Påmelding'); ?>
                        </button>
                    <?php else : ?>
                        <?php if (!empty($price)) : ?>
                        <div class="card-price">
                            <strong><?php echo esc_html($price); ?> <?php echo isset($after_price) ? esc_html($after_price) : ''; ?></strong>
                        </div>
                        <?php endif; ?>
                        
                        <button class="courselist-button pamelding pameldingsknapp pameldingskjema" data-url="<?php echo esc_url($signup_url); ?>">
                            <?php echo esc_html($button_text ?: 'Påmelding'); ?>
                        </button>
                    <?php endif; ?>
                </div>
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
                // Hent main_course_id for å finne alle kursdatoer
                $main_course_id = get_post_meta($course_id, 'ka_main_course_id', true);
                if (empty($main_course_id)) {
                    $main_course_id = get_post_meta($course_id, 'ka_location_id', true);
                }
                
                // Hent alle kursdatoer (samme logikk som i standard.php)
                $all_coursedates_popup = get_posts([
                    'post_type' => 'ka_coursedate',
                    'posts_per_page' => -1,
                    'meta_query' => [
                        ['key' => 'ka_main_course_id', 'value' => $main_course_id],
                    ],
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
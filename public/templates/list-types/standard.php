<?php

// Sjekk om vi er på en taksonomi-side
$is_taxonomy_page = is_tax('coursecategory') || is_tax('course_location') || is_tax('instructors');

// Hvis vi er på en taksonomi-side, hent kurs-informasjon
if ($is_taxonomy_page) {
    $course_id = get_the_ID();
    $course_title = get_the_title();
    $excerpt = get_the_excerpt();
    
    // Hent location_id for å finne relaterte kursdatoer
    $location_id = get_post_meta($course_id, 'location_id', true);
    
    // Hent kursdatoer basert på location_id
    $related_coursedates = get_posts([
        'post_type' => 'coursedate',
        'posts_per_page' => -1,
        'meta_query' => [
            ['key' => 'location_id', 'value' => $location_id],
        ],
    ]);
    
    // Konverter til array av IDer
    $related_coursedate_ids = array_map(function($post) {
        return $post->ID;
    }, $related_coursedates);
    
    // Hent data fra første tilgjengelige kursdato
    $selected_coursedate_data = get_selected_coursedate_data($related_coursedate_ids);
    
    // Hent lokasjonsinformasjon fra coursedates
    $location_freetext = get_post_meta($course_id, 'course_location_freetext', true);
    
    // Hvis location_freetext ikke er satt direkte på kurset, prøv å hente fra coursedates
    if (empty($location_freetext)) {
        foreach ($related_coursedates as $coursedate) {
            $coursedate_location = get_post_meta($coursedate->ID, 'course_location_freetext', true);
            if (!empty($coursedate_location)) {
                $location_freetext = $coursedate_location;
                break;
            }
        }
    }
    
    // Hent resten av lokasjonsinformasjonen
    $location = get_post_meta($course_id, 'course_location', true);
    
    // Hvis location ikke er satt direkte på kurset, prøv å hente fra coursedates
    if (empty($location)) {
        foreach ($related_coursedates as $coursedate) {
            $coursedate_location = get_post_meta($coursedate->ID, 'course_location', true);
            if (!empty($coursedate_location)) {
                $location = $coursedate_location;
                break;
            }
        }
    }
    
    $location_room = get_post_meta($course_id, 'course_location_room', true);
    
    // Hent bilde
    $featured_image_thumb = get_the_post_thumbnail_url($course_id, 'thumbnail') ?: KURSAG_PLUGIN_URL . '/assets/images/placeholder-kurs.jpg';
    
    // Sett opp link til kurset
    $course_link = get_permalink($course_id);
    
    // Hent data fra første tilgjengelige kursdato
    $first_course_date = $selected_coursedate_data['first_date'] ?? '';
    $last_course_date = $selected_coursedate_data['last_date'] ?? '';
    $price = $selected_coursedate_data['price'] ?? '';
    $after_price = $selected_coursedate_data['after_price'] ?? '';
    $duration = $selected_coursedate_data['duration'] ?? '';
    $coursetime = $selected_coursedate_data['time'] ?? '';
    $button_text = $selected_coursedate_data['button_text'] ?? '';
    $signup_url = $selected_coursedate_data['signup_url'] ?? '';
    $is_full = $selected_coursedate_data['is_full'] ?? false;
    $show_registration = $selected_coursedate_data['show_registration'] ?? false;
} else {
    // Original kode for coursedates
    $course_id = get_the_ID();

    $course_title =             get_post_meta($course_id, 'course_title', true);
    $first_course_date =        ka_format_date(get_post_meta($course_id, 'course_first_date', true));
    $last_course_date =         ka_format_date(get_post_meta($course_id, 'course_last_date', true));
    $registration_deadline =    ka_format_date(get_post_meta($course_id, 'course_registration_deadline', true));
    $duration =                 get_post_meta($course_id, 'course_duration', true);
    $coursetime =               get_post_meta($course_id, 'course_time', true);
    $price =                    get_post_meta($course_id, 'course_price', true);
    $after_price =              get_post_meta($course_id, 'course_text_after_price', true);
    $location =                 get_post_meta($course_id, 'course_location', true);
    $location_freetext =        get_post_meta($course_id, 'course_location_freetext', true);
    $location_room =            get_post_meta($course_id, 'course_location_room', true);
    $is_full =                  get_post_meta($course_id, 'course_isFull', true);
    $show_registration =        get_post_meta($course_id, 'course_showRegistrationForm', true);

    $button_text =              get_post_meta($course_id, 'course_button_text', true);
    $signup_url =               get_post_meta($course_id, 'course_signup_url', true);

    $related_course_id =        get_post_meta($course_id, 'location_id', true);

    $related_course_info = get_course_info_by_location($related_course_id);

    if ($related_course_info) {
        $course_link = esc_url($related_course_info['permalink']);
        $featured_image_thumb = $related_course_info['thumbnail'];
        $excerpt = $related_course_info['excerpt'];
    }

    if (!$course_link) {
        $course_link = false;
    }
}

$course_count = $course_count ?? 0;
$item_class = $course_count === 1 ? ' single-item' : '';

// Sjekk om bilder skal vises
$show_images = get_option('kursagenten_show_images', 'yes');

// Sjekk om vi er på en taksonomi-side
if (is_tax('coursecategory') || is_tax('course_location') || is_tax('instructors')) {
    $taxonomy = get_queried_object()->taxonomy;
    $taxonomy_show_images = get_option("kursagenten_taxonomy_{$taxonomy}_show_images", '');
    
    // Hvis det er satt en spesifikk innstilling for denne taksonomien, bruk den
    if (!empty($taxonomy_show_images)) {
        $show_images = $taxonomy_show_images;
    } else {
        // Ellers bruk den generelle taksonomi-innstillingen
        $show_images = get_option('kursagenten_show_images_taxonomy', 'yes');
    }
}

$with_image_class = $show_images === 'yes' ? ' with-image' : '';

// Hent instruktører for kurset
$instructors = get_the_terms($course_id, 'instructors');
$instructor_links = [];
if (!empty($instructors) && !is_wp_error($instructors)) {
    $instructor_links = array_map(function ($term) {
        $instructor_url = get_instructor_display_url($term, 'instructors');
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
<div class="courselist-item<?php echo $item_class; ?>" data-location="<?php echo esc_attr($location_freetext); ?>">
    <div class="courselist-main<?php echo $with_image_class; ?>">
        <?php if ($show_images === 'yes') : ?>
        <!-- Image area -->
        <div class="image column" style="background-image: url(<?php echo esc_url($featured_image_thumb); ?>);">
            <a class="image-inner" href="<?php echo esc_url($course_link); ?>" title="<?php echo esc_attr($course_title); ?>">
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
                        <?php if ($is_full === 'true') : ?>
                            <span class="course-available full">Fullt</span>
                        <?php else : ?>
                            <span class="course-available">Ledige plasser</span>
                        <?php endif; ?>
                    </h3>
                                    
                </div>
                <!-- Details area - date and location -->
                <div class="details-area iconlist horizontal">
                    <?php if ($is_taxonomy_page) : ?>
                        <?php if (!empty($first_course_date)) : ?>
                            <div class="startdate"><i class="ka-icon icon-calendar"></i> <strong>Neste kurs: &nbsp;</strong> <?php echo esc_html($first_course_date); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($location)) : ?>
                            <div class="location"><i class="ka-icon icon-location"></i><?php echo esc_html($location); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($location_freetext)) : ?>
                            <div class="location_room">(<?php echo esc_html($location_freetext); ?>)</div>
                        <?php endif; ?>
                    <?php else : ?>
                        <?php if (!empty($first_course_date)) : ?>
                            <div class="startdate"><i class="ka-icon icon-calendar"></i><?php echo esc_html($first_course_date); ?></div>
                        <?php endif; ?>
                    
                        <?php if (!empty($location)) : ?>
                            <div class="location"><i class="ka-icon icon-location"></i><?php echo esc_html($location); ?></div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <!-- Meta area -->
                <div class="meta-area iconlist horizontal">
                    <?php if ($is_taxonomy_page) : ?>

                        <div class="all-courses"><a href="<?php echo esc_url($course_link); ?>">Se alle tilgjengelige kurssteder og datoer</a></div>
                    <?php else : ?>
                        <?php if (!empty($coursetime)) : ?>
                            <div class="coursetime"><i class="ka-icon icon-time"></i><?php echo esc_html($coursetime); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($duration)) : ?>
                            <div class="duration"><i class="ka-icon icon-timer-light"></i><?php echo esc_html($duration); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($price)) : ?>
                            <div class="price"><i class="ka-icon icon-layers"></i><?php echo esc_html($price); ?> <?php echo isset($after_price) ? esc_html($after_price) : ''; ?></div>
                        <?php endif; ?>
                        <?php if (!empty($instructor_links)) : ?>
                            <div class="instructors"><i class="ka-icon icon-user"></i><?php echo implode(', ', $instructor_links); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($location_freetext)) : ?>
                            <div class="location_room"><i class="ka-icon icon-home"></i><?php echo esc_html($location_freetext); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($location_room)) : ?>
                            <div class="location_room"><i class="ka-icon icon-grid"></i><?php echo esc_html($location_room); ?></div>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <span class="accordion-icon clickopen tooltip" data-title="Se detaljer">+</span>
                </div>
                <!-- Accordion content -->
                <div class="courselist-content accordion-content">
                    <?php if (!empty($excerpt)) : ?>
                        <p><strong>Kort beskrivelse: </strong><br><?php echo wp_kses_post($excerpt); ?></p>
                    <?php endif; ?>
                    <?php if ($is_taxonomy_page) : ?>
                        <?php 
                        error_log('=== START Location Display Debug ===');
                        error_log('Related coursedate IDs: ' . print_r($related_coursedate_ids, true));
                        
                        $all_coursedates = get_all_sorted_coursedates($related_coursedate_ids);
                        error_log('All coursedates: ' . print_r($all_coursedates, true));
                        
                        $location_list = [];
                        foreach ($all_coursedates as $coursedate) {
                            if (!empty($coursedate['location']) && !empty($coursedate['course_location_freetext'])) {
                                $location_text = $coursedate['location'] . ' - ' . $coursedate['course_location_freetext'];
                                error_log('Found location: ' . $location_text);
                                if (!in_array($location_text, $location_list)) {
                                    $location_list[] = esc_html($location_text);
                                    error_log('Added unique location: ' . $location_text);
                                }
                            }
                        }
                        error_log('Final location list: ' . print_r($location_list, true));
                        
                        if (!empty($location_list)) : ?>
                            <p>Dette kurset er tilgjengelig på flere steder: <?php echo implode(', ', $location_list); ?></p>
                        <?php else : ?>
                            <p>Dette kurset er tilgjengelig på flere steder, men ingen spesifikke lokasjoner er satt opp ennå.</p>
                        <?php endif;
                        error_log('=== END Location Display Debug ===');
                        ?>
                    <?php else : ?>
                    
                        <?php if (!empty($first_course_date)) : ?>
                            <p>Kurset varer fra <?php echo esc_html($first_course_date); ?><?php if (!empty($last_course_date)) : ?> til <?php echo esc_html($last_course_date); ?><?php endif; ?></p>
                        <?php else : ?>
                            <?php 
                            $is_online = has_term('nettbasert', 'course_location', $course_id);
                            if ($is_online) : ?>
                                <p>Etter påmelding vil du få en e-post med mer informasjon om kurset, og hvordan det skal gjennomføres.</p>
                            <?php elseif ($show_registration === '1' || $show_registration === 1 || $show_registration === true || $show_registration === "true") : ?>
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
                <?php if ($is_taxonomy_page) : ?>
                    <button class="courselist-button pamelding pameldingsknapp pameldingskjema" data-url="<?php echo esc_url($signup_url); ?>">
                        <?php echo esc_html($button_text ?: 'Påmelding'); ?>
                    </button>
                <?php else : ?>
                    <button class="courselist-button pamelding pameldingsknapp pameldingskjema" data-url="<?php echo esc_url($signup_url); ?>">
                        <?php echo esc_html($button_text ?: 'Påmelding'); ?>
                    </button>
                <?php endif; ?>
                <a href="<?php echo esc_url($course_link); ?>" class="course-link small">Mer informasjon</a>
            </div>
        </div>
    </div>

    
</div>
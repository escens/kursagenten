<?php

// Sjekk om vi er på en taksonomi-side
$is_taxonomy_page = is_tax('coursecategory') || is_tax('course_location') || is_tax('instructors');

// Hvis vi er på en taksonomi-side, hent kurs-informasjon
if ($is_taxonomy_page) {
    $course_id = get_the_ID();
    $course_title = get_the_title();
    $excerpt = get_the_excerpt();
    
    // Hent coursedate-informasjon fra post-objektet
    $coursedate_info = $post->coursedate_info ?? [];
    
    $first_course_date = $coursedate_info['first_date'] ?? '';
    $last_course_date = $coursedate_info['last_date'] ?? '';
    $price = $coursedate_info['price'] ?? '';
    $after_price = $coursedate_info['after_price'] ?? '';
    $duration = $coursedate_info['duration'] ?? '';
    $coursetime = $coursedate_info['time'] ?? '';
    $button_text = $coursedate_info['button_text'] ?? '';
    $signup_url = $coursedate_info['signup_url'] ?? '';
    $is_full = $coursedate_info['is_full'] ?? '';
    
    // Hent lokasjonsinformasjon
    $location = get_post_meta($course_id, 'course_location', true);
    $location_freetext = get_post_meta($course_id, 'course_location_freetext', true);
    $location_room = get_post_meta($course_id, 'course_location_room', true);
    
    // Hent bilde
    $featured_image_thumb = get_the_post_thumbnail_url($course_id, 'thumbnail') ?: KURSAG_PLUGIN_URL . '/assets/images/placeholder-kurs.jpg';
    
    // Sett opp link til kurset
    $course_link = get_permalink($course_id);

    // Hent informasjon om førstkommende kurs
    $related_coursedate = get_post_meta($course_id, 'course_related_coursedate', true);
    $selected_coursedate_data = get_selected_coursedate_data($related_coursedate);
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

?>
<div class="courselist-item<?php echo $item_class; ?>">
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
                        <?php if (!empty($is_full)) : ?>
                            <span class="course-available full">Fullt</span>
                        <?php else : ?>
                            <span class="course-available">Ledige plasser</span>
                        <?php endif; ?>
                    </h3>
                                    
                </div>
                <!-- Details area - date and location -->
                <div class="details-area iconlist horizontal">
                    <?php if ($is_taxonomy_page) : ?>
                        <?php if (!empty($selected_coursedate_data['first_date'])) : ?>
                            <div class="startdate"><i class="ka-icon icon-calendar"></i> Neste kurs: <?php echo esc_html($selected_coursedate_data['first_date']); ?></div>
                        <?php endif; ?>
                    <?php else : ?>
                        <?php if (!empty($first_course_date)) : ?>
                            <div class="startdate"><i class="ka-icon icon-calendar"></i><?php echo esc_html($first_course_date); ?></div>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if (!empty($location)) : ?>
                        <div class="location"><i class="ka-icon icon-location"></i><?php echo esc_html($location); ?></div>
                    <?php endif; ?>
                </div>
                <!-- Meta area -->
                <div class="meta-area iconlist horizontal">
                    <?php if ($is_taxonomy_page) : ?>
                        <?php if (!empty($selected_coursedate_data['price'])) : ?>
                            <div class="price"><i class="ka-icon icon-layers"></i><?php echo esc_html($selected_coursedate_data['price']); ?> <?php echo !empty($selected_coursedate_data['after_price']) ? esc_html($selected_coursedate_data['after_price']) : ''; ?></div>
                        <?php endif; ?>
                        <?php if (!empty($selected_coursedate_data['duration'])) : ?>
                            <div class="duration"><i class="ka-icon icon-timer-light"></i><?php echo esc_html($selected_coursedate_data['duration']); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($selected_coursedate_data['time'])) : ?>
                            <div class="coursetime"><i class="ka-icon icon-time"></i><?php echo esc_html($selected_coursedate_data['time']); ?></div>
                        <?php endif; ?>
                    <?php else : ?>
                        <?php if (!empty($price)) : ?>
                            <div class="price"><i class="ka-icon icon-layers"></i><?php echo esc_html($price); ?> <?php echo isset($after_price) ? esc_html($after_price) : ''; ?></div>
                        <?php endif; ?>
                        <?php if (!empty($duration)) : ?>
                            <div class="duration"><i class="ka-icon icon-timer-light"></i><?php echo esc_html($duration); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($coursetime)) : ?>
                            <div class="coursetime"><i class="ka-icon icon-time"></i><?php echo esc_html($coursetime); ?></div>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if (!empty($location_freetext)) : ?>
                        <div class="location_room"><i class="ka-icon icon-home"></i><?php echo esc_html($location_freetext); ?></div>
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
                    <p><?php echo esc_html($first_course_date ? 'Kurset varer fra ' . $first_course_date . ' til ' . $last_course_date : 'Det er ikke satt opp dato for nye kurs. Meld din interesse for å få mer informasjon eller å sette deg på venteliste.'); ?></p>
                    <p><a href="<?php echo esc_url($course_link); ?>" class="course-link">Se kursdetaljer</a></p>
                </div>
            </div>
            
            <div class="links-area column">
                <?php if ($is_taxonomy_page) : ?>
                    <button class="courselist-button pamelding pameldingsknapp pameldingskjema" data-url="<?php echo esc_url($selected_coursedate_data['signup_url']); ?>">
                        <?php echo esc_html($selected_coursedate_data['button_text'] ?? 'Påmelding'); ?>
                    </button>
                <?php else : ?>
                    <button class="courselist-button pamelding pameldingsknapp pameldingskjema" data-url="<?php echo esc_url($signup_url); ?>">
                        <?php echo esc_html($button_text); ?>
                    </button>
                <?php endif; ?>
                <a href="<?php echo esc_url($course_link); ?>" class="course-link small">Mer informasjon</a>
            </div>
        </div>
    </div>

    
</div>
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
    $featured_image_thumb = get_the_post_thumbnail_url($course_id, 'medium') ?: KURSAG_PLUGIN_URL . '/assets/images/placeholder-kurs.jpg';
    
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
        $featured_image_thumb = $related_course_info['thumbnail-medium'];
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
<div class="courselist-item grid-item<?php echo $item_class; ?>">
    <div class="courselist-card<?php echo $with_image_class; ?>">
        <?php if ($show_images === 'yes') : ?>
        <!-- Image area -->
        <div class="card-image" style="background-image: url(<?php echo esc_url($featured_image_thumb); ?>);">
            <a class="image-inner" href="<?php echo esc_url($course_link); ?>" title="<?php echo esc_attr($course_title); ?>">
            </a>
            <?php if (!empty($is_full)) : ?>
                <span class="card-availability course-available full">Fullt</span>
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
                    <?php if (!empty($is_full)) : ?>
                        <div class="course-availability tooltip tooltip-left" data-title="Fullt">
                            <span class="card-availability course-available full"></span>
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
                        <?php if ($is_taxonomy_page) : ?>
                            <?php if (!empty($selected_coursedate_data['first_date'])) : ?>
                            <li><i class="ka-icon icon-calendar"></i><?php echo esc_html($selected_coursedate_data['first_date']); ?></li>
                            <?php endif; ?>
                            <?php if (!empty($selected_coursedate_data['time'])) : ?>
                            <li><i class="ka-icon icon-time"></i><?php echo esc_html($selected_coursedate_data['time']); ?></li>
                            <?php endif; ?>
                        <?php else : ?>
                            <?php if (!empty($first_course_date)) : ?>
                            <li><i class="ka-icon icon-calendar"></i><?php echo esc_html($first_course_date); ?></li>
                            <?php endif; ?>
                            <?php if (!empty($coursetime)) : ?>
                            <li><i class="ka-icon icon-time"></i><?php echo esc_html($coursetime); ?></li>
                            <?php endif; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            
            <div class="card-content-lower">
                <div class="card-separator"></div>
                
                <!-- Footer area -->
                <div class="card-footer">
                    <?php if ($is_taxonomy_page) : ?>
                        <?php if (!empty($selected_coursedate_data['price'])) : ?>
                        <div class="card-price">
                            <strong><?php echo esc_html($selected_coursedate_data['price']); ?> <?php echo !empty($selected_coursedate_data['after_price']) ? esc_html($selected_coursedate_data['after_price']) : ''; ?></strong>
                        </div>
                        <?php endif; ?>
                        
                        <button class="courselist-button pamelding pameldingsknapp pameldingskjema" data-url="<?php echo esc_url($selected_coursedate_data['signup_url']); ?>">
                            <?php echo esc_html($selected_coursedate_data['button_text'] ?? 'Påmelding'); ?>
                        </button>
                    <?php else : ?>
                        <?php if (!empty($price)) : ?>
                        <div class="card-price">
                            <strong><?php echo esc_html($price); ?> <?php echo isset($after_price) ? esc_html($after_price) : ''; ?></strong>
                        </div>
                        <?php endif; ?>
                        
                        <button class="courselist-button pamelding pameldingsknapp pameldingskjema" data-url="<?php echo esc_url($signup_url); ?>">
                            <?php echo esc_html($button_text); ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
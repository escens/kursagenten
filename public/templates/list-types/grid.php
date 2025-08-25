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
    
    // Hent lokasjonsinformasjon
    $location = get_post_meta($course_id, 'course_location', true);
    $location_freetext = get_post_meta($course_id, 'course_location_freetext', true);
    $location_room = get_post_meta($course_id, 'course_location_room', true);
    
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
    
    // Hent bilde
    $featured_image_thumb = get_the_post_thumbnail_url($course_id, 'medium') ?: KURSAG_PLUGIN_URL . '/assets/images/placeholder-kurs.jpg';
    
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

    $course_title =             get_post_meta($course_id, 'course_title', true);
    $first_course_date =        ka_format_date(get_post_meta($course_id, 'course_first_date', true));
    $last_course_date =         ka_format_date(get_post_meta($course_id, 'course_last_date', true));
    $registration_deadline =    ka_format_date(get_post_meta($course_id, 'course_registration_deadline', true));
    $duration =                 get_post_meta($course_id, 'course_duration', true);
    $coursetime =               get_post_meta($course_id, 'course_time', true);
    $course_days =              get_post_meta($course_id, 'course_days', true);
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

// Hent instruktører for kurset
$instructors = get_the_terms($course_id, 'instructors');
$instructor_links = [];
if (!empty($instructors) && !is_wp_error($instructors)) {
    $instructor_links = array_map(function ($term) {
        $instructor_url = get_instructor_display_url($term, 'instructors');
        return '<a href="' . esc_url($instructor_url) . '">' . esc_html($term->name) . '</a>';
    }, $instructors);
}

?>
<?php
// Hent kurskategorier for data-category attributt
$course_categories = get_the_terms($course_id, 'coursecategory');
$category_slugs = [];
if (!empty($course_categories) && !is_wp_error($course_categories)) {
    foreach ($course_categories as $category) {
        // Bruk kun den faktiske kategorien kurset tilhører
        $category_slugs[] = $category->slug;
    }
}
$category_slugs = array_unique($category_slugs);
?>
<div class="courselist-item grid-item<?php echo $item_class; ?>" data-location="<?php echo esc_attr($location_freetext); ?>" data-category="<?php echo esc_attr(implode(' ', $category_slugs)); ?>">
    <div class="courselist-card<?php echo $with_image_class; ?>">
        <?php if ($show_images === 'yes') : ?>
        <!-- Image area -->
        <div class="card-image" style="background-image: url(<?php echo esc_url($featured_image_thumb); ?>);">
            <a class="image-inner" href="<?php echo esc_url($course_link); ?>" title="<?php echo esc_attr($course_title); ?>">
            </a>
            <?php if ($is_full === 'true' || $is_full === 1) : ?>
                <span class="card-availability course-available full">Fullt</span>
            <?php elseif ($show_registration === 'false') : ?>
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
                    <?php elseif ($show_registration === 'false') : ?>
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
                        <?php if ($is_taxonomy_page) : ?>
                            <?php if (!empty($first_course_date)) : ?>
                            <li><i class="ka-icon icon-calendar"></i><?php echo esc_html($first_course_date); ?></li>
                            <?php endif; ?>
                            <?php if (!empty($coursetime) || !empty($course_days)) : ?>
                            <li>
                                <i class="ka-icon icon-time"></i>
                                <?php if (!empty($course_days)) : ?><?php echo esc_html($course_days); ?> <?php endif; ?>
                                <?php if (!empty($coursetime)) : ?><?php echo esc_html($coursetime); ?><?php endif; ?>
                            </li>
                            <?php endif; ?>
                            <?php if (!empty($instructor_links)) : ?>
                            <li><i class="ka-icon icon-user"></i><?php echo implode(', ', $instructor_links); ?></li>
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
                    <?php if ($is_taxonomy_page) : ?>
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
</div>
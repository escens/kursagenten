<?php

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
$course_count = $course_count ?? 0;
$item_class = $course_count === 1 ? ' single-item' : '';

// Sjekk om bilder skal vises
$show_images = get_option('kursagenten_show_images', 'yes');
$with_image_class = $show_images === 'yes' ? ' with-image' : '';

?>
<div class="courselist-item grid-item<?php echo $item_class; ?>">
    <div class="courselist-card<?php echo $with_image_class; ?>">
        <?php if ($show_images === 'yes') : ?>
        <!-- Image area -->
        <div class="card-image" style="background-image: url(<?php echo esc_url($featured_image_thumb); ?>);">
            <a class="image-inner" href="<?php echo esc_url($course_link); ?>" title="<?php echo esc_attr($course_title); ?>">
            </a>
            <span class="card-availability course-available">Ledige plasser</span>
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
                    <div class="course-availability tooltip tooltip-left" data-title="Ledige plasser">
                        <span class="card-availability course-available"></span>
                    </div>
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
                        <?php if (!empty($first_course_date)) : ?>
                        <li><i class="ka-icon icon-calendar"></i><?php echo esc_html($first_course_date); ?></li>
                        <?php endif; ?>
                        <?php if (!empty($coursetime)) : ?>
                        <li><i class="ka-icon icon-time"></i><?php echo esc_html($coursetime); ?></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            
            <div class="card-content-lower">
                <div class="card-separator"></div>
                
                <!-- Footer area -->
                <div class="card-footer">
                    <?php if (!empty($price)) : ?>
                    <div class="card-price">
                        <strong><?php echo esc_html($price); ?> <?php echo esc_html($after_price); ?></strong>
                    </div>
                    <?php endif; ?>
                    
                    <button class="courselist-button pamelding pameldingsknapp pameldingskjema" data-url="<?php echo esc_url($signup_url); ?>">
                        <?php echo esc_html($button_text) ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
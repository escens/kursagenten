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
    $featured_image_thumb = $related_course_info['thumbnail'];
    $excerpt = $related_course_info['excerpt'];
}

if (!$course_link) {
    $course_link = false;
}
$course_count = $course_count ?? 0;
$item_class = $course_count === 1 ? ' single-item' : '';

?>
<div class="courselist-item<?php echo $item_class; ?>">
    <div class="courselist-main with-image">
        <!--
        <div class="image col" style="background-image: url(<?php echo esc_url($featured_image_thumb); ?>);">
            <a class="image-inner" href="<?php echo esc_url($course_link); ?>" title="<?php echo esc_attr($course_title); ?>">
                <picture>
                    <img src="" alt="<?php echo esc_attr($course_title); ?>" class="course-image" decoding="async">
                </picture>
            </a>
        </div>
        -->
        <!-- Image area -->
        <div class="image column" style="background-image: url(<?php echo esc_url($featured_image_thumb); ?>);">
            <a class="image-inner" href="<?php echo esc_url($course_link); ?>" title="<?php echo esc_attr($course_title); ?>">
            </a>
        </div>
        <div class="text-area-wrapper">
            <!-- Text area -->
            <div class="text-area column">
                <!-- Title area -->
                <div class="title-area">
                    <h3 class="course-title">
                        <a href="<?php echo esc_url($course_link); ?>" class="course-link"><?php echo esc_html($course_title); ?></a>
                        <span class="course-available">Ledige plasser</span>
                    </h3>
                                    
                </div>
                <!-- Details area - date and location -->
                <div class="details-area iconlist horizontal">
                    <?php if (!empty($first_course_date)) : ?>
                        <div class="startdate"><i class="ka-icon icon-calendar"></i><?php echo esc_html($first_course_date); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($location)) : ?>
                        <div class="location"><i class="ka-icon icon-location"></i><?php echo esc_html($location); ?></div>
                    <?php endif; ?>
                </div>
                <!-- Meta area -->
                <div class="meta-area iconlist horizontal">
                    <?php if (!empty($price)) : ?>
                        <div class="price"><i class="ka-icon icon-layers"></i><?php echo esc_html($price); ?> <?php echo esc_html($after_price); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($duration)) : ?>
                        <div class="duration"><i class="ka-icon icon-timer-light"></i><?php echo esc_html($duration); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($location_freetext)) : ?>
                        <div class="location_room"><i class="ka-icon icon-home"></i><?php echo esc_html($location_freetext); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($location_room)) : ?>
                        <div class="location_room"><i class="ka-icon icon-grid"></i><?php echo esc_html($location_room); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($coursetime)) : ?>
                        <div class="coursetime"><i class="ka-icon icon-time"></i><?php echo esc_html($coursetime); ?></div>
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
                <button class="courselist-button pamelding pameldingsknapp pameldingskjema" data-url="<?php echo esc_url($signup_url); ?>">
                    <?php echo esc_html($button_text) ?>
                </button>
                <a href="<?php echo esc_url($course_link); ?>" class="course-link small">Mer informasjon</a>
            </div>
        </div>
    </div>

    
</div>
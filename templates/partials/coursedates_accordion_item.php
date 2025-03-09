<?php
// Hent taxonomiene
/*$instructors = get_the_terms($course_id, 'instructors');
$course_categories = get_the_terms($course_id, 'coursecategory');
$course_locations = get_the_terms($course_id, 'course_location');

// Funksjon for å formatere taxonomilisten som kommaseparert
function format_taxonomy_list($terms) {
    if (empty($terms) || is_wp_error($terms)) {
        return 'Ingen';
    }
    return implode(', ', wp_list_pluck($terms, 'name'));
}

$instructors_list = format_taxonomy_list($instructors);
$categories_list = format_taxonomy_list($course_categories);
$locations_list = format_taxonomy_list($course_locations);
*/


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

/*if ($related_course_info) {
    echo '<a href="' . esc_url($related_course_info['permalink']) . '">';
    echo '<img src="' . esc_url($related_course_info['thumbnail']) . '" alt="' . esc_attr($related_course_info['title']) . '">';
    echo '</a>';
}*/

if (!$course_link) {
    $course_link = false;
}
$item_class = $course_count === 1 ? 'course-item courselist-item single-item' : 'course-item courselist-item';
?>
<div class="<?php echo $item_class; ?>">
    <div class="courselist-main">
        <div class="text">
            <div class="title-area">
                <span class="accordion-icon clickopen tooltip" data-title="Se detaljer">+</span>
                <span class="title"><a href="<?php echo esc_url($related_course_info['permalink']); ?>" class="course-link small tooltip" data-title="Vis kurs"><h3 class="course-title"><?php echo esc_html($course_title); ?></h3></a></span>
            </div>
            <div class="details-area iconlist horizontal">
                <?php if (!empty($first_course_date)) : ?>
                    <div class="startdate"><i class="ka-icon icon-calendar"></i><?php echo esc_html($first_course_date); ?></div>
                <?php endif; ?>
                <?php if (!empty($location)) : ?>
                    <div class="location"><i class="ka-icon icon-location"></i><?php echo esc_html($location); ?></div>
                <?php endif; ?>
            </div>
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
                
            </div>
        </div>
        <div class="links">
            <a href="<?php echo esc_url($course_link); ?>" class="course-link small">Les mer</a>
            <button class="courselist-button pamelding pameldingsknapp pameldingskjema" data-url="<?php echo esc_url($signup_url); ?>">
            <?php echo esc_html($button_text) ?>
            </button>
        </div>
    </div>

    <div class="courselist-content accordion-content">
        <p><?php echo esc_html($first_course_date ? $first_course_date : 'Det er ikke satt opp dato for nye kurs. Meld din interesse for å få mer informasjon eller å sette deg på venteliste.'); ?></p>
        <p><a href="<?php echo esc_url($course_link); ?>" class="course-link">Se kursdetaljer</a></p>
    </div>
</div>
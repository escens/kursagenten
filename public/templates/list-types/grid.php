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

// Hent kontekst og taksonominavn
$context = is_post_type_archive('course') ? 'archive' : 'taxonomy';
$tax_name = '';
if ($context === 'taxonomy') {
    $current_tax = get_queried_object();
    if ($current_tax && isset($current_tax->taxonomy)) {
        $tax_name = $current_tax->taxonomy;
    }
}

// Hent kolonneinnstillinger
$column_attributes = kursagenten_get_column_attributes($context, $tax_name);

// Hent antall kurs per side
$posts_per_page = kursagenten_get_posts_per_page($context, $tax_name);

// Sett opp spørringen
$args = [
    'posts_per_page' => $posts_per_page,
    'paged' => get_query_var('paged') ? get_query_var('paged') : 1
];

if ($context === 'taxonomy') {
    $args['taxonomy'] = $tax_name;
    $args['term'] = $current_tax->slug;
    $query = get_courses_for_taxonomy($args);
} else {
    $query = get_courses_for_archive($args);
}

?>
<div class="courselist-items"<?php echo $column_attributes; ?>>
    <?php
    if ($query->have_posts()) :
        while ($query->have_posts()) : $query->the_post();
            // ... existing code for å vise kurs ...
        endwhile;
        
        // Paginering
        echo '<div class="courselist-pagination">';
        echo paginate_links([
            'total' => $query->max_num_pages,
            'current' => max(1, get_query_var('paged')),
            'prev_text' => '&laquo; Forrige',
            'next_text' => 'Neste &raquo;'
        ]);
        echo '</div>';
        
        wp_reset_postdata();
    else :
        echo '<p>Ingen kurs funnet.</p>';
    endif;
    ?>
</div>
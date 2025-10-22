<?php
/**
 * Kompakt listevisning for kurs (per-item template)
 * Dette er en per-item template som vises én gang per kurs i loopen
 */

if (!defined('ABSPATH')) exit;

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
    
    // Hent plassholderbilde fra innstillinger
    $options = get_option('design_option_name');
    $placeholder_image = !empty($options['ka_plassholderbilde_kurs']) 
        ? $options['ka_plassholderbilde_kurs']
        : rtrim(KURSAG_PLUGIN_URL, '/') . '/assets/images/placeholder-kurs.jpg';
    
    // Sett opp link til kurset
    $course_link = get_permalink($course_id);
    
    // Hent data fra første tilgjengelige kursdato
    $first_course_date = $selected_coursedate_data['first_date'] ?? '';
    $price = $selected_coursedate_data['price'] ?? '';
    $after_price = $selected_coursedate_data['after_price'] ?? '';
} else {
    // Original kode for coursedates
    $course_id = get_the_ID();

    $course_title = get_post_meta($course_id, 'course_title', true);
    $first_course_date = ka_format_date(get_post_meta($course_id, 'course_first_date', true));
    $price = get_post_meta($course_id, 'course_price', true);
    $after_price = get_post_meta($course_id, 'course_text_after_price', true);
    $location = get_post_meta($course_id, 'course_location', true);
    $location_freetext = get_post_meta($course_id, 'course_location_freetext', true);

    $related_course_id = get_post_meta($course_id, 'location_id', true);
    $related_course_info = get_course_info_by_location($related_course_id);

    if ($related_course_info) {
        $course_link = esc_url($related_course_info['permalink']);
    }

    if (!$course_link) {
        $course_link = false;
    }
}

$course_count = $course_count ?? 0;
$item_class = $course_count === 1 ? ' single-item' : '';

// Hent kurskategorier for data-category attributt
$course_categories = get_the_terms($course_id, 'coursecategory');
$category_slugs = [];
if (!empty($course_categories) && !is_wp_error($course_categories)) {
    foreach ($course_categories as $category) {
        $category_slugs[] = $category->slug;
    }
}
$category_slugs = array_unique($category_slugs);
?>

<div class="courselist-item compact-item<?php echo $item_class; ?>" data-location="<?php echo esc_attr($location_freetext); ?>" data-category="<?php echo esc_attr(implode(' ', $category_slugs)); ?>">
    <div class="compact-course-content">
        <h3 class="compact-course-title">
            <a href="<?php echo esc_url($course_link); ?>">
                <?php echo esc_html($course_title); ?>
            </a>
        </h3>
        
        <div class="compact-course-meta">
            <?php if (!empty($first_course_date)) : ?>
                <span class="compact-course-date">
                    <i class="ka-icon icon-calendar"></i> <?php echo esc_html($first_course_date); ?>
                </span>
            <?php endif; ?>
            
            <?php if (!empty($location)) : ?>
                <span class="compact-course-location">
                    <i class="ka-icon icon-location"></i> <?php echo esc_html($location); ?>
                </span>
            <?php endif; ?>
            
            <?php if (!empty($price)) : ?>
                <span class="compact-course-price">
                    <i class="ka-icon icon-layers"></i> <?php echo esc_html($price); ?> <?php echo isset($after_price) ? esc_html($after_price) : ''; ?>
                </span>
            <?php endif; ?>
        </div>
        
        <a href="<?php echo esc_url($course_link); ?>" class="compact-course-link">
            Se kurset <i class="ka-icon icon-chevron-right"></i>
        </a>
    </div>
</div>

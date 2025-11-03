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

// Hent plassholderbilde fra innstillinger (brukes av begge view types)
$options = get_option('design_option_name');
$placeholder_image = !empty($options['ka_plassholderbilde_kurs']) 
    ? $options['ka_plassholderbilde_kurs']
    : rtrim(KURSAG_PLUGIN_URL, '/') . '/assets/images/placeholder-kurs.jpg';

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
    
    // Sett opp link til kurset
    $course_link = get_permalink($course_id);
    
    // Hent bilde for hovedkurs
    $featured_image_thumb = get_the_post_thumbnail_url($course_id, 'thumbnail') ?: $placeholder_image;
    
    // Hent data fra første tilgjengelige kursdato
    $first_course_date = $selected_coursedate_data['first_date'] ?? '';
    $price = $selected_coursedate_data['price'] ?? '';
    $after_price = $selected_coursedate_data['after_price'] ?? '';
    $signup_url = $selected_coursedate_data['signup_url'] ?? '';
    $show_registration = $selected_coursedate_data['show_registration'] ?? false;
    $button_text = $selected_coursedate_data['button_text'] ?? '';
    $is_full = $selected_coursedate_data['is_full'] ?? false;
} else {
    // Original kode for coursedates
    $course_id = get_the_ID();

    $course_title = get_post_meta($course_id, 'ka_course_title', true);
    $first_course_date = ka_format_date(get_post_meta($course_id, 'ka_course_first_date', true));
    $price = get_post_meta($course_id, 'ka_course_price', true);
    $after_price = get_post_meta($course_id, 'ka_course_text_after_price', true);
    $location = get_post_meta($course_id, 'ka_course_location', true);
    $location_freetext = get_post_meta($course_id, 'ka_course_location_freetext', true);
    $signup_url = get_post_meta($course_id, 'ka_course_signup_url', true);
    $show_registration = get_post_meta($course_id, 'ka_course_showRegistrationForm', true);
    $button_text = get_post_meta($course_id, 'ka_course_button_text', true);
    $is_full = get_post_meta($course_id, 'ka_course_isFull', true);

    $related_course_id = get_post_meta($course_id, 'ka_location_id', true);
    $related_course_info = get_course_info_by_location($related_course_id);

    if ($related_course_info) {
        $course_link = esc_url($related_course_info['permalink']);
        $featured_image_thumb = $related_course_info['thumbnail'] ?: $placeholder_image;
    } else {
        // Hvis ingen relatert kursinfo, bruk plassholderbilde og fallback-data
        $featured_image_thumb = $placeholder_image;
        $course_link = false;
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

// Hent kurskategorier for data-category attributt
$course_categories = get_the_terms($course_id, 'ka_coursecategory');
$category_slugs = [];
if (!empty($course_categories) && !is_wp_error($course_categories)) {
    foreach ($course_categories as $category) {
        $category_slugs[] = $category->slug;
    }
}
$category_slugs = array_unique($category_slugs);

// Generate view type class
$view_type_class = ' view-type-' . str_replace('_', '', $view_type);
?>

<div class="courselist-item compact-item<?php echo $item_class . $view_type_class; ?>" data-location="<?php echo esc_attr($location_freetext); ?>" data-category="<?php echo esc_attr(implode(' ', $category_slugs)); ?>">
    <div class="compact-course-content">
        <div class="compact-course-info-wrapper">
            <?php if ($show_images === 'yes') : ?>
                <div class="compact-course-image">
                    <a href="<?php echo esc_url($course_link); ?>">
                        <img src="<?php echo esc_url($featured_image_thumb); ?>" alt="<?php echo esc_attr($course_title); ?>">
                    </a>
                </div>
            <?php endif; ?>
            
             <div class="compact-course-info">
                 <h3 class="compact-course-title">
                     <a href="<?php echo esc_url($course_link); ?>">
                         <?php echo esc_html($course_title); ?>
                     </a>
                     <?php if ($is_full === 'true') : ?>
                         <span class="compact-availability full">Fullt</span>
                     <?php elseif (empty($show_registration) || $show_registration === 'false') : ?>
                         <span class="compact-availability on-demand">På forespørsel</span>
                     <?php else : ?>
                         <span class="compact-availability available">Ledige plasser</span>
                     <?php endif; ?>
                 </h3>
                 
                 <div class="compact-course-meta">
                     <?php if (!empty($first_course_date)) : ?>
                         <span class="compact-course-date">
                             <i class="ka-icon icon-calendar"></i>
                             <span>
                                 <?php if ($view_type === 'main_courses' && !$force_standard_view) : ?>
                                     <strong>Neste kurs: </strong>
                                 <?php endif; ?>
                                 <?php echo esc_html($first_course_date); ?>
                             </span>
                         </span>
                     <?php endif; ?>
                    
                    <?php if (!empty($location)) : ?>
                        <span class="compact-course-location">
                            <i class="ka-icon icon-location"></i>
                            <span><?php echo esc_html($location); ?></span>
                        </span>
                    <?php endif; ?>
                    
                    <?php if (!empty($price)) : ?>
                        <span class="compact-course-price">
                            <i class="ka-icon icon-layers"></i>
                            <span><?php echo esc_html($price); ?> <?php echo isset($after_price) ? esc_html($after_price) : ''; ?></span>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="compact-course-actions">
            <a href="<?php echo esc_url($course_link); ?>" class="compact-btn compact-btn-secondary">
                Les mer
            </a>

            <?php if (!empty($signup_url)) : ?>
                <button class="compact-btn compact-btn-primary pameldingskjema" data-url="<?php echo esc_url($signup_url); ?>">
                    <?php echo esc_html($button_text ?: 'Påmelding'); ?>
                </button>
            <?php endif; ?>
            
        </div>
    </div>
</div>

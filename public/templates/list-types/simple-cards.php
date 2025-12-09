<?php
/**
 * Simple cards list type - Enkle kort
 * Displays courses as simple cards with title, excerpt, duration, and next course date
 */

if (!function_exists('kursagenten_normalize_bool')) {
    /**
     * Normalize truthy values from metadata to strict booleans.
     */
    function kursagenten_normalize_bool($value): bool {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        if (is_string($value)) {
            $value = strtolower(trim($value));
            return in_array($value, ['1', 'true', 'yes', 'on'], true);
        }

        return false;
    }
}

// Check view type from args - simple-cards always uses main_courses
$view_type = 'main_courses';
$is_taxonomy_page = isset($args['is_taxonomy_page']) && $args['is_taxonomy_page'];

// When view_type is 'main_courses', the query returns ka_coursedate posts
// We need to find the main course based on the coursedate
$coursedate_id = get_the_ID();

// Get main_course_id from coursedate
$main_course_id = get_post_meta($coursedate_id, 'ka_main_course_id', true);

// Find main course based on main_course_id
$main_courses = get_posts([
    'post_type' => 'ka_course',
    'posts_per_page' => 1,
    'meta_query' => [
        'relation' => 'AND',
        [
            'key' => 'ka_main_course_id',
            'value' => $main_course_id,
            'compare' => '='
        ],
        [
            'key' => 'ka_is_parent_course',
            'value' => 'yes',
            'compare' => '='
        ]
    ]
]);

// If we found the main course, use it as base
if (!empty($main_courses)) {
    $course_id = $main_courses[0]->ID;
    $course_title = get_the_title($course_id);
    $excerpt = get_the_excerpt($course_id);
} else {
    // Fallback if main course doesn't exist
    $course_id = 0;
    $course_title = '';
    $excerpt = '';
}

// Check if we need to filter by location (taxonomy page)
$taxonomy = isset($args['taxonomy']) ? $args['taxonomy'] : null;
$current_term = isset($args['current_term']) ? $args['current_term'] : null;

// Build meta query for coursedates
$meta_query = [
    ['key' => 'ka_main_course_id', 'value' => $main_course_id],
];

// If on a location taxonomy page, filter coursedates by that location
if ($taxonomy === 'ka_course_location' && $current_term) {
    $meta_query[] = [
        'key' => 'ka_course_location',
        'value' => $current_term->name,
        'compare' => '='
    ];
}

$related_coursedates = get_posts([
    'post_type' => 'ka_coursedate',
    'posts_per_page' => -1,
    'meta_query' => $meta_query,
]);

// Convert to array of IDs
$related_coursedate_ids = array_map(function($post) {
    return $post->ID;
}, $related_coursedates);

// Get data from first available coursedate
$selected_coursedate_data = get_selected_coursedate_data($related_coursedate_ids);

// Get location information from selected coursedate
$location = $selected_coursedate_data['location'] ?? '';
$location_freetext = $selected_coursedate_data['location_freetext'] ?? '';

// Get placeholder image from settings
$options = get_option('design_option_name');
$placeholder_image = !empty($options['ka_plassholderbilde_kurs']) 
    ? $options['ka_plassholderbilde_kurs']
    : rtrim(KURSAG_PLUGIN_URL, '/') . '/assets/images/placeholder-kurs.jpg';

// Get image
$featured_image_thumb = $course_id ? get_the_post_thumbnail_url($course_id, 'medium') : '';
$featured_image_thumb = $featured_image_thumb ?: $placeholder_image;

// Set up link to course - always use main course (parent course) for simple-cards
$course_link = $course_id ? get_permalink($course_id) : '#';

// Get data from first available coursedate
$first_course_date = $selected_coursedate_data['first_date'] ?? '';
$duration = $selected_coursedate_data['duration'] ?? '';

$course_count = $course_count ?? 0;
$item_class = $course_count === 1 ? ' single-item' : '';

// Check if images should be shown
// Priority: shortcode attribute > taxonomy-specific setting > global setting
$shortcode_show_images = isset($args['shortcode_show_images']) ? $args['shortcode_show_images'] : null;

// If shortcode explicitly sets bilder parameter to 'yes' or 'no', use it
if ($shortcode_show_images === 'yes' || $shortcode_show_images === 'no') {
    // Use shortcode attribute if explicitly set to yes or no
    $show_images = $shortcode_show_images;
} elseif ($is_taxonomy_page) {
    // Taxonomy page: use taxonomy settings with proper override handling
    $taxonomy = get_queried_object()->taxonomy;
    $show_images = get_taxonomy_setting($taxonomy, 'show_images', 'yes');
} else {
    // Standard: use global setting
    $show_images = get_option('kursagenten_show_images', 'yes');
}

// Get course categories for data-category attribute
$course_categories = get_the_terms($course_id, 'ka_coursecategory');
$category_slugs = [];
if (!empty($course_categories) && !is_wp_error($course_categories)) {
    foreach ($course_categories as $category) {
        $category_slugs[] = $category->slug;
    }
}
$category_slugs = array_unique($category_slugs);

// Generate view type class
$view_type_class = ' view-type-maincourses';
?>

<div class="courselist-item simple-card-item<?php echo $item_class . $view_type_class; ?>" data-location="<?php echo esc_attr($location_freetext); ?>" data-category="<?php echo esc_attr(implode(' ', $category_slugs)); ?>">
    <a href="<?php echo esc_url($course_link); ?>" class="simple-card-link" title="<?php echo esc_attr($course_title); ?>" aria-label="Se kurs: <?php echo esc_attr($course_title); ?>">
        <div class="simple-card<?php echo ($show_images === 'yes') ? ' with-image' : ''; ?>">
            <?php if ($show_images === 'yes') : ?>
            <!-- Image area - left side with same border-radius -->
            <div class="simple-card-image">
                <img src="<?php echo esc_url($featured_image_thumb); ?>" 
                     alt="<?php echo esc_attr($course_title); ?>" 
                     title="<?php echo esc_attr($course_title); ?>">
            </div>
            <?php endif; ?>
            
            <!-- Content area -->
            <div class="simple-card-content">
                <!-- Title -->
                <h3 class="simple-card-title">
                    <?php echo esc_html($course_title); ?>
                </h3>
                
                <!-- Excerpt -->
                <?php if (!empty($excerpt)) : ?>
                <div class="simple-card-excerpt">
                    <?php echo wp_trim_words(wp_kses_post($excerpt), 60, '...'); ?>
                </div>
                <?php endif; ?>
                
                <!-- Meta row: Duration and Next course date -->
                <div class="simple-card-meta">
                    <?php if (!empty($duration)) : ?>
                    <span class="simple-card-duration">
                        <i class="ka-icon icon-stopwatch"></i>
                        <span><?php echo esc_html($duration); ?></span>
                    </span>
                    <?php endif; ?>
                    
                    <?php if (!empty($first_course_date)) : ?>
                    <span class="simple-card-date">
                        <i class="ka-icon icon-calendar"></i>
                        <span>Neste kurs: <?php echo esc_html($first_course_date); ?></span>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Arrow indicator - right side -->
            <div class="simple-card-arrow">
                <i class="ka-icon icon-arrow-right-short"></i>
            </div>
        </div>
    </a>
</div>

<?php
/**
 * SEO Functions for Templates
 * 
 * Contains reusable SEO functions for single course templates, taxonomy archives, and more.
 * These functions handle meta tags, Open Graph, Twitter Cards, and Schema.org structured data.
 * 
 * @package kursagenten
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add meta tags to single course pages
 * 
 * Handles SEO meta tags including Open Graph and Twitter Cards.
 * Automatically detects if SEO plugins (Rank Math, Yoast, All in One SEO) are active
 * and adjusts output accordingly to avoid conflicts.
 * 
 * @return void
 */
function kursagenten_add_meta_tags() {
    global $post;
    
    // Only run on single course posts
    if (!is_singular('ka_course') || !isset($post->ID)) {
        return;
    }
    
    // Detect if SEO plugins are active - if so, let them handle meta tags
    $seo_plugins_active = (
        defined('WPSEO_VERSION') || // Yoast SEO
        class_exists('RankMath') || // Rank Math
        defined('AIOSEO_VERSION') || // All in One SEO
        class_exists('WPSEO_Frontend') // Yoast alternative check
    );
    
    // If SEO plugin is active, only add course-specific schema and exit
    if ($seo_plugins_active) {
        kursagenten_add_course_schema();
        return;
    }
    
    // Get SEO data with proper fallbacks
    $title = get_post_meta($post->ID, 'custom_title', true);
    if (empty($title)) {
        $title = get_the_title($post->ID);
    }
    
    $description = get_post_meta($post->ID, 'meta_description', true);
    if (empty($description)) {
        $excerpt = get_the_excerpt($post->ID);
        // Clean and limit description
        $description = wp_strip_all_tags(strip_shortcodes($excerpt));
        $description = wp_trim_words($description, 30, '...');
    }
    
    // Get site name for Open Graph
    $site_name = get_bloginfo('name');
    
    // Get featured image with fallback
    $placeholder_image = '';
    $kursinnst_options = get_option('design_option_name');
    if (!empty($kursinnst_options['ka_plassholderbilde_kurs'])) {
        $placeholder_image = $kursinnst_options['ka_plassholderbilde_kurs'];
    } else {
        $placeholder_image = KURSAG_PLUGIN_URL . 'assets/images/placeholder-kurs.jpg';
    }
    
    $featured_image = get_the_post_thumbnail_url($post->ID, 'full');
    if (empty($featured_image)) {
        $featured_image = $placeholder_image;
    }
    
    // Canonical URL
    echo '<link rel="canonical" href="' . esc_url(get_permalink($post->ID)) . '">' . PHP_EOL;
    
    // Standard meta tags
    echo '<meta name="description" content="' . esc_attr($description) . '">' . PHP_EOL;
    
    // Open Graph tags (Facebook, LinkedIn, etc.)
    echo '<meta property="og:type" content="website">' . PHP_EOL;
    echo '<meta property="og:title" content="' . esc_attr($title) . '">' . PHP_EOL;
    echo '<meta property="og:description" content="' . esc_attr($description) . '">' . PHP_EOL;
    echo '<meta property="og:url" content="' . esc_url(get_permalink($post->ID)) . '">' . PHP_EOL;
    echo '<meta property="og:site_name" content="' . esc_attr($site_name) . '">' . PHP_EOL;
    echo '<meta property="og:locale" content="nb_NO">' . PHP_EOL;
    
    if (!empty($featured_image)) {
        echo '<meta property="og:image" content="' . esc_url($featured_image) . '">' . PHP_EOL;
        echo '<meta property="og:image:width" content="1200">' . PHP_EOL;
        echo '<meta property="og:image:height" content="630">' . PHP_EOL;
        echo '<meta property="og:image:alt" content="' . esc_attr($title) . '">' . PHP_EOL;
    }
    
    // Twitter Card tags
    echo '<meta name="twitter:card" content="summary_large_image">' . PHP_EOL;
    echo '<meta name="twitter:title" content="' . esc_attr($title) . '">' . PHP_EOL;
    echo '<meta name="twitter:description" content="' . esc_attr($description) . '">' . PHP_EOL;
    
    if (!empty($featured_image)) {
        echo '<meta name="twitter:image" content="' . esc_url($featured_image) . '">' . PHP_EOL;
        echo '<meta name="twitter:image:alt" content="' . esc_attr($title) . '">' . PHP_EOL;
    }
    
    // Add Course Schema.org structured data
    kursagenten_add_course_schema();
}

/**
 * Add Course Schema.org structured data
 * 
 * Outputs JSON-LD structured data for Course type according to Schema.org specification.
 * This runs even when SEO plugins are active (unless Rank Math handles it),
 * as it adds course-specific data that generic SEO plugins may not include.
 * 
 * @return void
 */
function kursagenten_add_course_schema() {
    global $post;
    
    if (!isset($post->ID)) {
        echo '<!-- Kursagenten Schema: No post ID -->' . PHP_EOL;
        return;
    }
    
    // Check if Rank Math is handling the schema (don't duplicate)
    $rank_math_active = class_exists('RankMath');
    
    if ($rank_math_active) {
        echo '<!-- Kursagenten Schema: Rank Math active, schema handled by Rank Math -->' . PHP_EOL;
        return;
    }
    
    // Get course data
    $title = get_post_meta($post->ID, 'custom_title', true) ?: get_the_title($post->ID);
    $description = get_post_meta($post->ID, 'meta_description', true) ?: wp_strip_all_tags(get_the_excerpt($post->ID));
    $description = wp_trim_words($description, 30, '...');
    
    // Get featured image
    $placeholder_image = '';
    $kursinnst_options = get_option('design_option_name');
    if (!empty($kursinnst_options['ka_plassholderbilde_kurs'])) {
        $placeholder_image = $kursinnst_options['ka_plassholderbilde_kurs'];
    } else {
        $placeholder_image = KURSAG_PLUGIN_URL . 'assets/images/placeholder-kurs.jpg';
    }
    $image_url = get_the_post_thumbnail_url($post->ID, 'full') ?: $placeholder_image;
    
    // Get site/organization info
    $site_name = get_bloginfo('name');
    $site_url = home_url();
    
    // Build base schema data
    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'Course',
        'name' => $title,
        'description' => $description,
        'url' => get_permalink($post->ID),
        'provider' => array(
            '@type' => 'Organization',
            'name' => $site_name,
            'url' => $site_url
        )
    );
    
    // Add image
    if (!empty($image_url)) {
        $schema['image'] = $image_url;
    }
    
    // Add language
    $schema['inLanguage'] = 'no';
    
    // Add course categories as keywords
    $categories = wp_get_post_terms($post->ID, 'ka_coursecategory');
    if (!empty($categories) && !is_wp_error($categories)) {
        $keywords = array();
        foreach ($categories as $cat) {
            $keywords[] = $cat->name;
        }
        if (!empty($keywords)) {
            $schema['keywords'] = implode(', ', $keywords);
        }
    }
    
    // Add aggregateRating if available (from Site Reviews plugin)
    $rating_score = get_post_meta($post->ID, '_glsr_average', true);
    $rating_count = get_post_meta($post->ID, '_glsr_reviews', true);
    if (!empty($rating_score) && !empty($rating_count)) {
        $schema['aggregateRating'] = array(
            '@type' => 'AggregateRating',
            'ratingValue' => $rating_score,
            'ratingCount' => $rating_count,
            'bestRating' => '5',
            'worstRating' => '1'
        );
    }
    
    // Get next course data (same logic as used in template for .nextcourse section)
    $selected_coursedate_data = kursagenten_get_next_course_data($post->ID);
    
    // Add CourseInstance if we have a specific course date
    if (!empty($selected_coursedate_data) && !empty($selected_coursedate_data['first_date'])) {
        $course_instance = kursagenten_build_course_instance_from_data($selected_coursedate_data);
        if (!empty($course_instance)) {
            $schema['hasCourseInstance'] = array($course_instance);
        }
    } else {
        // Fallback: Add general offer if no specific date is available
        $price = get_post_meta($post->ID, 'ka_course_price', true);
        if (!empty($price) && $price !== '0') {
            $schema['offers'] = array(
                '@type' => 'Offer',
                'price' => preg_replace('/[^0-9.]/', '', $price),
                'priceCurrency' => 'NOK',
                'availability' => 'https://schema.org/InStock',
                'url' => get_permalink($post->ID)
            );
        }
    }
    
    // Output schema
    echo '<!-- Kursagenten Course Schema (using next course data) -->' . PHP_EOL;
    echo '<script type="application/ld+json">' . PHP_EOL;
    echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL;
    echo '</script>' . PHP_EOL;
}

/**
 * Get next course data for schema
 * Uses same logic as template to get the selected coursedate
 * 
 * @param int $post_id Course post ID
 * @return array Course data or empty array
 */
function kursagenten_get_next_course_data($post_id) {
    // Get related coursedates (same logic as in template)
    $course_id = get_post_meta($post_id, 'location_id', true);
    $is_parent_course = get_post_meta($post_id, 'is_parent_course', true);
    
    if ($is_parent_course === 'yes') {
        $related_coursedate = get_posts([
            'post_type' => 'ka_coursedate',
            'posts_per_page' => -1,
            'meta_query' => [
                ['key' => 'main_course_id', 'value' => $course_id],
            ],
            'fields' => 'ids'
        ]);
    } else {
        $main_course_id = get_post_meta($post_id, 'main_course_id', true);
        $course_location_terms = wp_get_post_terms($post_id, 'ka_course_location');
        
        if (!empty($course_location_terms) && !is_wp_error($course_location_terms) && !empty($main_course_id)) {
            $location_names = array_map(function($term) {
                return $term->name;
            }, $course_location_terms);
            
            $related_coursedate = get_posts([
                'post_type' => 'ka_coursedate',
                'posts_per_page' => -1,
                'meta_query' => [
                    'relation' => 'AND',
                    [
                        'relation' => 'OR',
                        ['key' => 'course_location', 'value' => $location_names, 'compare' => 'IN'],
                        ['key' => 'course_location_freetext', 'value' => $location_names, 'compare' => 'IN']
                    ],
                    ['key' => 'main_course_id', 'value' => $main_course_id, 'compare' => '=']
                ],
                'fields' => 'ids'
            ]);
        } else {
            $related_coursedate = [];
        }
    }
    
    // Use the same function as template to get selected coursedate
    if (function_exists('get_selected_coursedate_data') && !empty($related_coursedate)) {
        return get_selected_coursedate_data($related_coursedate);
    }
    
    return array();
}

/**
 * Build CourseInstance from selected coursedate data
 * 
 * @param array $coursedate_data Data from get_selected_coursedate_data()
 * @return array CourseInstance schema or empty array
 */
function kursagenten_build_course_instance_from_data($coursedate_data) {
    // Require coursedate ID to get full data
    if (empty($coursedate_data['id'])) {
        return array();
    }
    
    $coursedate_id = $coursedate_data['id'];
    
    $instance = array(
        '@type' => 'CourseInstance'
    );
    
    // Add dates in ISO 8601 format
    if (!empty($coursedate_data['first_date'])) {
        $timestamp = strtotime($coursedate_data['first_date']);
        if ($timestamp !== false) {
            $instance['startDate'] = date('c', $timestamp);
        }
    }
    
    if (!empty($coursedate_data['last_date'])) {
        $timestamp = strtotime($coursedate_data['last_date']);
        if ($timestamp !== false) {
            $instance['endDate'] = date('c', $timestamp);
        }
    }
    
    // Get location name from coursedate post meta (NOT in get_selected_coursedate_data!)
    $location_name = get_post_meta($coursedate_id, 'ka_course_location', true);
    $location_freetext = get_post_meta($coursedate_id, 'ka_course_location_freetext', true);
    
    // Add courseMode based on location
    if (!empty($location_name)) {
        $location_lower = strtolower($location_name);
        if (stripos($location_lower, 'nettbasert') !== false || 
            stripos($location_lower, 'online') !== false || 
            stripos($location_lower, 'digital') !== false ||
            stripos($location_lower, 'webinar') !== false) {
            $instance['courseMode'] = 'online';
        } else {
            $instance['courseMode'] = 'onsite';
        }
    }
    
    // Add location for physical courses
    if (isset($instance['courseMode']) && $instance['courseMode'] === 'onsite') {
        $location = array(
            '@type' => 'Place'
        );
        
        // Use location name
        if (!empty($location_name)) {
            $location['name'] = $location_name;
        }
        
        // Add address data
        $address_street = get_post_meta($coursedate_id, 'ka_course_address_street', true);
        $postal_code = get_post_meta($coursedate_id, 'ka_course_address_zipcode', true);
        $city = get_post_meta($coursedate_id, 'ka_course_address_place', true);
        
        if (!empty($address_street) || !empty($postal_code) || !empty($city)) {
            $location['address'] = array(
                '@type' => 'PostalAddress',
                'addressCountry' => 'NO'
            );
            
            if (!empty($address_street)) {
                $location['address']['streetAddress'] = $address_street;
            }
            if (!empty($postal_code)) {
                $location['address']['postalCode'] = $postal_code;
            }
            if (!empty($city)) {
                $location['address']['addressLocality'] = $city;
            }
        }
        
        $instance['location'] = $location;
    }
    
    // Add course schedule (time and days)
    $schedule_parts = array();
    if (!empty($coursedate_data['course_days'])) {
        $schedule_parts[] = $coursedate_data['course_days'];
    }
    if (!empty($coursedate_data['time'])) {
        $schedule_parts[] = $coursedate_data['time'];
    }
    if (!empty($schedule_parts)) {
        $instance['courseSchedule'] = array(
            '@type' => 'Schedule',
            'scheduleTimezone' => 'Europe/Oslo',
            'repeatFrequency' => implode(' ', $schedule_parts)
        );
    }
    
    // Add price offer
    if (!empty($coursedate_data['price'])) {
        $instance['offers'] = array(
            '@type' => 'Offer',
            'price' => preg_replace('/[^0-9.]/', '', $coursedate_data['price']),
            'priceCurrency' => 'NOK'
        );
        
        // Check availability
        if (!empty($coursedate_data['is_full'])) {
            $instance['offers']['availability'] = 'https://schema.org/SoldOut';
        } else {
            $instance['offers']['availability'] = 'https://schema.org/InStock';
        }
        
        // Add valid through date
        if (!empty($coursedate_data['last_date'])) {
            $timestamp = strtotime($coursedate_data['last_date']);
            if ($timestamp !== false) {
                $instance['offers']['validThrough'] = date('c', $timestamp);
            }
        } elseif (!empty($coursedate_data['first_date'])) {
            $timestamp = strtotime($coursedate_data['first_date'] . ' +1 day');
            if ($timestamp !== false) {
                $instance['offers']['validThrough'] = date('c', $timestamp);
            }
        }
    }
    
    // Add instructor if available
    $instructor_name = get_post_meta($coursedate_id, 'ka_course_instructor', true);
    if (!empty($instructor_name)) {
        $instance['instructor'] = array(
            '@type' => 'Person',
            'name' => $instructor_name
        );
    }
    
    // Add language
    if (!empty($coursedate_data['language'])) {
        $instance['inLanguage'] = $coursedate_data['language'];
    } else {
        $instance['inLanguage'] = 'no';
    }
    
    // Add duration/workload if available
    if (!empty($coursedate_data['duration'])) {
        $duration_clean = strtolower($coursedate_data['duration']);
        if (preg_match('/(\d+)\s*dag/i', $duration_clean, $matches)) {
            $instance['courseWorkload'] = 'P' . $matches[1] . 'D';
        } elseif (preg_match('/(\d+)\s*time/i', $duration_clean, $matches)) {
            $instance['courseWorkload'] = 'PT' . $matches[1] . 'H';
        } elseif (preg_match('/(\d+)\s*uke/i', $duration_clean, $matches)) {
            $instance['courseWorkload'] = 'P' . $matches[1] . 'W';
        } elseif (preg_match('/(\d+)\s*kveld/i', $duration_clean, $matches)) {
            $instance['courseWorkload'] = 'P' . $matches[1] . 'D';
        }
    }
    
    return $instance;
}

/**
 * Add meta tags to taxonomy archive pages
 * 
 * Handles SEO meta tags for course categories, locations, and instructor pages.
 * This function can be extended to support different taxonomies.
 * 
 * @param string $taxonomy The taxonomy name (e.g., 'ka_coursecategory', 'ka_course_location', 'ka_instructors')
 * @return void
 */
function kursagenten_add_taxonomy_meta_tags($taxonomy = '') {
    // Check if we're on a taxonomy archive
    if (!is_tax() && !is_category() && !is_tag()) {
        return;
    }
    
    // Detect if SEO plugins are active
    $seo_plugins_active = (
        defined('WPSEO_VERSION') ||
        class_exists('RankMath') ||
        defined('AIOSEO_VERSION') ||
        class_exists('WPSEO_Frontend')
    );
    
    // If SEO plugin is active, let them handle it
    if ($seo_plugins_active) {
        return;
    }
    
    // Get current term
    $term = get_queried_object();
    
    if (!$term || !isset($term->term_id)) {
        return;
    }
    
    // Get term data
    $title = $term->name;
    $description = !empty($term->description) ? wp_strip_all_tags($term->description) : '';
    $description = wp_trim_words($description, 30, '...');
    
    // Get site name
    $site_name = get_bloginfo('name');
    
    // Get term link
    $term_link = get_term_link($term);
    
    // Get taxonomy image
    $image_url = '';
    $current_taxonomy = $term->taxonomy;
    
    // Try to get image based on taxonomy type
    switch ($current_taxonomy) {
        case 'ka_coursecategory':
            $image_url = get_term_meta($term->term_id, 'image_coursecategory', true);
            break;
        case 'ka_course_location':
            $image_url = get_term_meta($term->term_id, 'image_course_location', true);
            break;
        case 'ka_instructors':
            // Try uploaded image first, then KA image
            $image_url = get_term_meta($term->term_id, 'image_instructor', true);
            if (empty($image_url)) {
                $image_url = get_term_meta($term->term_id, 'image_instructor_ka', true);
            }
            break;
    }
    
    // Fallback to placeholder if no image found
    if (empty($image_url)) {
        $options = get_option('design_option_name');
        $placeholder_key = match($current_taxonomy) {
            'ka_coursecategory' => 'ka_plassholderbilde_kurs',
            'ka_course_location' => 'ka_plassholderbilde_sted',
            'ka_instructors' => 'ka_plassholderbilde_instruktor',
            default => 'ka_plassholderbilde_kurs'
        };
        
        if (!empty($options[$placeholder_key])) {
            $image_url = $options[$placeholder_key];
        } else {
            // Final fallback
            $image_url = KURSAG_PLUGIN_URL . 'assets/images/placeholder-kurs.jpg';
        }
    }
    
    // Canonical URL
    echo '<link rel="canonical" href="' . esc_url($term_link) . '">' . PHP_EOL;
    
    // Standard meta tags
    if (!empty($description)) {
        echo '<meta name="description" content="' . esc_attr($description) . '">' . PHP_EOL;
    }
    
    // Open Graph tags
    echo '<meta property="og:type" content="website">' . PHP_EOL;
    echo '<meta property="og:title" content="' . esc_attr($title) . '">' . PHP_EOL;
    if (!empty($description)) {
        echo '<meta property="og:description" content="' . esc_attr($description) . '">' . PHP_EOL;
    }
    echo '<meta property="og:url" content="' . esc_url($term_link) . '">' . PHP_EOL;
    echo '<meta property="og:site_name" content="' . esc_attr($site_name) . '">' . PHP_EOL;
    echo '<meta property="og:locale" content="nb_NO">' . PHP_EOL;
    
    // Add Open Graph image
    if (!empty($image_url)) {
        echo '<meta property="og:image" content="' . esc_url($image_url) . '">' . PHP_EOL;
        echo '<meta property="og:image:width" content="1200">' . PHP_EOL;
        echo '<meta property="og:image:height" content="630">' . PHP_EOL;
        echo '<meta property="og:image:alt" content="' . esc_attr($title) . '">' . PHP_EOL;
    }
    
    // Twitter Card tags
    echo '<meta name="twitter:card" content="summary_large_image">' . PHP_EOL;
    echo '<meta name="twitter:title" content="' . esc_attr($title) . '">' . PHP_EOL;
    if (!empty($description)) {
        echo '<meta name="twitter:description" content="' . esc_attr($description) . '">' . PHP_EOL;
    }
    if (!empty($image_url)) {
        echo '<meta name="twitter:image" content="' . esc_url($image_url) . '">' . PHP_EOL;
        echo '<meta name="twitter:image:alt" content="' . esc_attr($title) . '">' . PHP_EOL;
    }
}

/**
 * Initialize SEO functions
 * 
 * Registers hooks for SEO meta tags on appropriate pages.
 * This is called automatically via template_redirect hook.
 * 
 * @return void
 */
function kursagenten_init_seo() {
    // Add meta tags for single course pages
    if (is_singular('ka_course')) {
        add_action('wp_head', 'kursagenten_add_meta_tags', 1);
    }
    
    // Add meta tags for taxonomy archives
    if (is_tax(array('ka_coursecategory', 'ka_course_location', 'ka_instructors'))) {
        add_action('wp_head', 'kursagenten_add_taxonomy_meta_tags', 1);
    }
}

// Auto-initialize SEO on template_redirect (runs before wp_head)
add_action('template_redirect', 'kursagenten_init_seo');


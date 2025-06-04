<?php
/**
 * Search functionality for instructors
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Modify search query to include instructor content
 */
function kursagenten_modify_search_query($query) {
    if (!is_admin() && $query->is_main_query() && $query->is_search()) {
        //error__log('Kursagenten: Starting modify_search_query');
        
        $search_term = $query->get('s');
        //error__log('Kursagenten: Search term: ' . $search_term);
        
        if (!empty($search_term)) {
            // Find instructor terms matching the search term
            $instructor_terms = get_terms(array(
                'taxonomy' => 'instructors',
                'name__like' => $search_term,
                'hide_empty' => false
            ));
            
            //error__log('Kursagenten: Found instructor terms: ' . print_r($instructor_terms, true));
            
            if (!empty($instructor_terms)) {
                // Set post type and other query parameters
                $query->set('post_type', array('post', 'page', 'course', 'coursedate'));
                $query->set('posts_per_page', -1);
                $query->set('orderby', 'title');
                $query->set('order', 'ASC');
                
                // Add tax_query to find related posts
                $tax_query = array(
                    array(
                        'taxonomy' => 'instructors',
                        'field' => 'term_id',
                        'terms' => wp_list_pluck($instructor_terms, 'term_id'),
                        'operator' => 'IN'
                    )
                );
                $query->set('tax_query', $tax_query);
                
                // Add instructors to search results
                add_filter('the_posts', function($posts) use ($instructor_terms) {
                    // Convert instructor terms to post objects
                    $instructor_posts = array_map(function($term) {
                        $post = new stdClass();
                        $post->ID = -$term->term_id; // Negative ID to avoid conflicts
                        $post->post_title = $term->name;
                        $post->post_type = 'instructor_term';
                        $post->post_status = 'publish';
                        $post->guid = get_term_link($term);
                        $post->term_id = $term->term_id;
                        $post->has_instructor_image = false; // Default value
                        
                        // Generate content with image and description
                        $content = '';
                        
                        // Get image
                        $instructor_image = get_term_meta($term->term_id, 'image_instructor', true);
                        //error__log('Kursagenten: Instructor image for term ' . $term->term_id . ': ' . print_r($instructor_image, true));
                        if ($instructor_image) {
                            // Convert URL to image ID
                            $image_id = attachment_url_to_postid($instructor_image);
                            //error__log('Kursagenten: Converted image URL to ID: ' . $image_id);
                            if ($image_id) {
                                // Get image in full size
                                $image = wp_get_attachment_image_src($image_id, 'large');
                                
                                // Get all available image sizes
                                $image_sizes = wp_get_attachment_image_sizes($image_id);
                                $srcset = wp_get_attachment_image_srcset($image_id, 'large');
                                
                                //error__log('Kursagenten: Image data: ' . print_r($image, true));
                                
                                // Mark that we have an image
                                $post->has_instructor_image = true;
                                
                                // Generate image HTML
                                $content .= sprintf(
                                    '<div class="nv-post-thumbnail-wrap img-wrap"><a href="%s" rel="bookmark" title="%s"><img width="%s" height="%s" src="%s" class="wp-post-image" alt="%s" decoding="async" srcset="%s" sizes="%s" /></a></div>',
                                    esc_url(get_term_link($term)),
                                    esc_attr($term->name),
                                    esc_attr($image[1]),
                                    esc_attr($image[2]),
                                    esc_url($image[0]),
                                    esc_attr($term->name),
                                    $srcset,
                                    $image_sizes ?: '(max-width: ' . $image[1] . 'px) 100vw, ' . $image[1] . 'px'
                                );
                                
                                //error__log('Kursagenten: Generated image HTML: ' . $content);
                            }
                        }
                        
                        // Add description
                        $content .= '<div class="excerpt-wrap entry-summary"><p>' . $term->description . '</p></div>';
                        
                        // Set content and excerpt
                        $post->post_content = $content;
                        $post->post_excerpt = wp_strip_all_tags($term->description);
                        
                        return $post;
                    }, $instructor_terms);
                    
                    // Put instructors first in results
                    return array_merge($instructor_posts, $posts);
                });
            }
        }
        //error__log('Kursagenten: Finished modify_search_query');
    }
}
add_action('pre_get_posts', 'kursagenten_modify_search_query');

/**
 * Display instructor content
 */
add_filter('the_content', function($content) {
    global $post;
    if ($post->post_type === 'instructor_term') {
        // Get term
        $term = get_term($post->term_id, 'instructors');
        if ($term) {
            $output = '';
            
            // Get image
            $instructor_image = get_term_meta($term->term_id, 'image_instructor', true);
            if ($instructor_image) {
                // Convert URL to image ID
                $image_id = attachment_url_to_postid($instructor_image);
                if ($image_id) {
                    // Get image in full size
                    $image = wp_get_attachment_image_src($image_id, 'large');
                    
                    // Get all available image sizes
                    $image_sizes = wp_get_attachment_image_sizes($image_id);
                    $srcset = wp_get_attachment_image_srcset($image_id, 'large');
                    
                    if ($image) {
                        // Generate image HTML
                        $output .= sprintf(
                            '<div class="nv-post-thumbnail-wrap img-wrap"><a href="%s" rel="bookmark" title="%s"><img width="%s" height="%s" src="%s" class="wp-post-image" alt="%s" decoding="async" srcset="%s" sizes="%s" /></a></div>',
                            esc_url(get_term_link($term)),
                            esc_attr($term->name),
                            esc_attr($image[1]),
                            esc_attr($image[2]),
                            esc_url($image[0]),
                            esc_attr($term->name),
                            $srcset,
                            $image_sizes ?: '(max-width: ' . $image[1] . 'px) 100vw, ' . $image[1] . 'px'
                        );
                    }
                }
            }
            
            // Add title
            $output .= '<h2 class="entry-title">' . $term->name . '</h2>';
            
            // Add description
            $output .= '<div class="excerpt-wrap entry-summary"><p>' . $term->description . '</p></div>';
            
            return $output;
        }
    }
    return $content;
});

// Remove old display code and replace with excerpt support
add_filter('get_the_excerpt', function($excerpt, $post) {
    if ($post->post_type === 'instructor_term') {
        $term = get_term($post->term_id, 'instructors');
        if ($term) {
            return wp_strip_all_tags($term->description);
        }
    }
    return $excerpt;
}, 10, 2);

// Change link for instructors
add_filter('the_permalink', function($permalink, $post) {
    if ($post->post_type === 'instructor_term') {
        $term = get_term($post->term_id, 'instructors');
        if ($term) {
            return get_instructor_display_url($term, 'instructors');
        }
    }
    return $permalink;
}, 10, 2);

add_filter('post_link', function($permalink, $post) {
    if ($post->post_type === 'instructor_term') {
        $term = get_term($post->term_id, 'instructors');
        if ($term) {
            return get_instructor_display_url($term, 'instructors');
        }
    }
    return $permalink;
}, 10, 2);

add_filter('post_type_link', function($permalink, $post) {
    if ($post->post_type === 'instructor_term') {
        $term = get_term($post->term_id, 'instructors');
        if ($term) {
            return get_instructor_display_url($term, 'instructors');
        }
    }
    return $permalink;
}, 10, 2);

// Remove all unnecessary filters
remove_all_filters('post_thumbnail_html');
remove_all_filters('wp_get_attachment_image');
remove_all_filters('wp_get_attachment_image_src');
remove_all_filters('post_thumbnail_id');
remove_all_filters('wp_get_attachment_url');
remove_all_filters('get_post_metadata');

// Keep only the essential filters
add_filter('has_post_thumbnail', function($has_thumbnail, $post) {
    if (is_numeric($post)) {
        $post = get_post($post);
    }
    
    if ($post && $post->post_type === 'instructor_term') {
        return isset($post->post_thumbnail_html);
    }
    return $has_thumbnail;
}, 10, 2);

// Main filter for displaying the image
add_filter('the_post_thumbnail', function($html, $post_id, $post_thumbnail_id, $size) {
    global $post;
    if ($post && $post->post_type === 'instructor_term' && isset($post->post_thumbnail_html)) {
        return $post->post_thumbnail_html;
    }
    return $html;
}, 10, 4);

// Add support for displaying title for instructor terms
add_filter('the_title', function($title, $post_id) {
    global $post;
    // Commented out logging
    // error_log('Kursagenten: the_title filter called for post_id: ' . $post_id . ', title: "' . $title . '"');
    if ($post && $post->post_type === 'instructor_term') {
        // Commented out logging
        // error_log('Kursagenten: Found instructor term post. Post ID: ' . $post->ID . ', post_title: "' . $post->post_title . '"');
        // Get term
        $term = get_term($post->term_id, 'instructors');
        if ($term) {
            // Commented out logging
            // error_log('Kursagenten: Retrieved term. Term ID: ' . $term->term_id . ', term name: "' . $term->name . '"');
            // Prioritize correct title display
            // Image logic is preserved in the code but commented out to ensure title displays correctly
            
            // Return only the title for now, but with a filter so themes can customize display
            $modified_title = apply_filters('kursagenten_instructor_title', $term->name, $term);
            // Commented out logging
            // error_log('Kursagenten: Returning title: "' . $modified_title . '"');
            return $modified_title;
        }
    }
    return $title;
}, 10, 2);

// Filter for customizing instructor title
add_filter('kursagenten_instructor_title', function($title, $term) {
    // Commented out logging
    // error_log('Kursagenten: kursagenten_instructor_title filter called: "' . $title . '"');
    // Standard return only the title
    return $title;
}, 10, 2);

/**
 * COMMENTED OUT: Code for displaying image before title
 * This is preserved for future use when image display issues are resolved
 *
 * To activate, remove this comment and comment out the simplified code above:
 *
 * add_filter('the_title', function($title, $post_id) {
 *     global $post;
 *     if ($post && $post->post_type === 'instructor_term') {
 *         // Get term
 *         $term = get_term($post->term_id, 'instructors');
 *         if ($term) {
 *             // Get image
 *             $instructor_image = get_term_meta($term->term_id, 'image_instructor', true);
 *             if ($instructor_image) {
 *                 // Convert URL to image ID
 *                 $image_id = attachment_url_to_postid($instructor_image);
 *                 if ($image_id) {
 *                     // Get image in full size
 *                     $image = wp_get_attachment_image_src($image_id, 'medium');
 *                     
 *                     // Get all available image sizes
 *                     $image_sizes = wp_get_attachment_image_sizes($image_id);
 *                     $srcset = wp_get_attachment_image_srcset($image_id, 'large');
 *                     
 *                     if ($image) {
 *                         // Generate image HTML with responsive srcset
 *                         $image_html = sprintf(
 *                             '<div class="nv-post-thumbnail-wrap img-wrap"><a href="%s" rel="bookmark" title="%s"><img width="%s" height="%s" src="%s" class="wp-post-image" alt="%s" decoding="async" srcset="%s" sizes="%s" /></a></div>',
 *                             esc_url(get_term_link($term)),
 *                             esc_attr($term->name),
 *                             esc_attr($image[1]),
 *                             esc_attr($image[2]),
 *                             esc_url($image[0]),
 *                             esc_attr($term->name),
 *                             $srcset,
 *                             $image_sizes ?: '(max-width: ' . $image[1] . 'px) 100vw, ' . $image[1] . 'px'
 *                         );
 *                         
 *                         // Allow theme to override HTML structure if desired
 *                         return apply_filters('kursagenten_instructor_image_html', $image_html . $title, $image_html, $title, $term);
 *                     }
 *                 }
 *             }
 *         }
 *     }
 *     return $title;
 * }, 10, 2);
 *
 * // Add support for overriding image HTML in theme
 * add_filter('kursagenten_instructor_image_html', function($html, $image_html, $title, $term) {
 *     // Here the theme can override the HTML structure by returning its own version
 *     return $html;
 * }, 10, 4);
 */

// Add has-post-thumbnail class for instructor terms with images
add_filter('post_class', function($classes, $class, $post_id) {
    global $post;
    if ($post && $post->post_type === 'instructor_term' && !empty($post->has_instructor_image)) {
        $classes[] = 'has-post-thumbnail';
    }
    return $classes;
}, 10, 3);
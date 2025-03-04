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
        error_log('Kursagenten: Starter modify_search_query');
        
        $search_term = $query->get('s');
        error_log('Kursagenten: Søkeord: ' . $search_term);
        
        if (!empty($search_term)) {
            // Finn instruktør-termer som matcher søkeordet
            $instructor_terms = get_terms(array(
                'taxonomy' => 'instructors',
                'name__like' => $search_term,
                'hide_empty' => false
            ));
            
            error_log('Kursagenten: Fant instruktør-termer: ' . print_r($instructor_terms, true));
            
            if (!empty($instructor_terms)) {
                // Sett post type og andre query parametre
                $query->set('post_type', array('post', 'page', 'course', 'coursedate'));
                $query->set('posts_per_page', -1);
                $query->set('orderby', 'title');
                $query->set('order', 'ASC');
                
                // Legg til tax_query for å finne relaterte innlegg
                $tax_query = array(
                    array(
                        'taxonomy' => 'instructors',
                        'field' => 'term_id',
                        'terms' => wp_list_pluck($instructor_terms, 'term_id'),
                        'operator' => 'IN'
                    )
                );
                $query->set('tax_query', $tax_query);
                
                // Legg til instruktører i søkeresultatene
                add_filter('the_posts', function($posts) use ($instructor_terms) {
                    // Konverter instruktør-termer til post-objekter
                    $instructor_posts = array_map(function($term) {
                        $post = new stdClass();
                        $post->ID = -$term->term_id; // Negativ ID for å unngå konflikter
                        $post->post_title = $term->name;
                        $post->post_content = $term->description;
                        $post->post_type = 'instructor_term';
                        $post->post_status = 'publish';
                        $post->guid = get_term_link($term);
                        $post->term_id = $term->term_id;
                        return $post;
                    }, $instructor_terms);
                    
                    // Sett instruktører først i resultatene
                    return array_merge($instructor_posts, $posts);
                });
            }
        }
        error_log('Kursagenten: Ferdig med modify_search_query');
    }
}
add_action('pre_get_posts', 'kursagenten_modify_search_query');

/**
 * Vis instruktør-term i søkeresultatene
 */
function kursagenten_display_instructor_term($post) {
    if ($post->post_type === 'instructor_term') {
        $term = get_term($post->term_id, 'instructors');
        if ($term) {
            ?>
            <article class="instructor-search-result">
                <?php if ($term->description): ?>
                    <div class="entry-content">
                        <?php echo wp_kses_post($term->description); ?>
                    </div>
                <?php endif; ?>
            </article>
            <?php
            return true;
        }
    }
    return false;
}

// Vis instruktørinnhold
add_filter('the_content', function($content) {
    global $post;
    if (kursagenten_display_instructor_term($post)) {
        return '';
    }
    return $content;
});

// Endre link for instruktører
add_filter('the_permalink', function($permalink, $post) {
    if ($post->post_type === 'instructor_term') {
        $term = get_term($post->term_id, 'instructors');
        if ($term) {
            return get_term_link($term);
        }
    }
    return $permalink;
}, 10, 2);

add_filter('post_link', function($permalink, $post) {
    if ($post->post_type === 'instructor_term') {
        $term = get_term($post->term_id, 'instructors');
        if ($term) {
            return get_term_link($term);
        }
    }
    return $permalink;
}, 10, 2);

add_filter('post_type_link', function($permalink, $post) {
    if ($post->post_type === 'instructor_term') {
        $term = get_term($post->term_id, 'instructors');
        if ($term) {
            return get_term_link($term);
        }
    }
    return $permalink;
}, 10, 2);
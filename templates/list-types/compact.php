<?php
/**
 * Kompakt listevisning for kurs
 */

if (!defined('ABSPATH')) exit;

// Hent kurs fra global query
global $query;

if (!$query->have_posts()) {
    echo '<p class="kursagenten-no-courses">Ingen kurs funnet.</p>';
    return;
}
?>

<div class="kursagenten-course-list compact-list">
    <?php 
    while ($query->have_posts()) : $query->the_post();
        $course_id = get_the_ID();
        ?>
        <div class="kursagenten-course-item">
            <div class="kursagenten-course-content">
                <h3 class="kursagenten-course-title">
                    <a href="<?php the_permalink(); ?>">
                        <?php the_title(); ?>
                    </a>
                </h3>
                
                <div class="kursagenten-course-meta">
                    <?php 
                    // Vis kursdato, sted, pris
                    $course_date = get_post_meta($course_id, '_course_date', true);
                    $course_location = get_post_meta($course_id, '_course_location', true);
                    $course_price = get_post_meta($course_id, '_course_price', true);
                    
                    if (!empty($course_date)) {
                        echo '<span class="kursagenten-course-date">' . esc_html($course_date) . '</span>';
                    }
                    
                    if (!empty($course_location)) {
                        echo '<span class="kursagenten-course-location">' . esc_html($course_location) . '</span>';
                    }
                    
                    if (!empty($course_price)) {
                        echo '<span class="kursagenten-course-price">' . esc_html($course_price) . ' kr</span>';
                    }
                    ?>
                </div>
                
                <a href="<?php the_permalink(); ?>" class="kursagenten-course-link">
                    Se kurset <i class="ka-icon icon-chevron-right"></i>
                </a>
            </div>
        </div>
    <?php 
    endwhile; 
    wp_reset_postdata(); 
    ?>
</div>
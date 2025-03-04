<?php
/**
 * Template for instructor taxonomy archive
 */

get_header();

// Get current term
$term = get_queried_object();

// Get instructor meta
$bio = get_term_meta($term->term_id, 'instructor_bio', true);
$image = get_term_meta($term->term_id, 'instructor_image', true);
$email = get_term_meta($term->term_id, 'instructor_email', true);
$phone = get_term_meta($term->term_id, 'instructor_phone', true);

// Get related courses
$courses = get_posts(array(
    'post_type' => array('course', 'coursedate'),
    'posts_per_page' => -1,
    'tax_query' => array(
        array(
            'taxonomy' => 'instructors',
            'field' => 'term_id',
            'terms' => $term->term_id
        )
    )
));
?>

<div class="instructor-archive">
    <div class="instructor-header">
        <?php if ($image): ?>
            <div class="instructor-image">
                <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($term->name); ?>">
            </div>
        <?php endif; ?>
        
        <div class="instructor-info">
            <h1><?php echo esc_html($term->name); ?></h1>
            
            <?php if ($bio): ?>
                <div class="instructor-bio">
                    <?php echo wp_kses_post($bio); ?>
                </div>
            <?php endif; ?>
            
            <div class="instructor-contact">
                <?php if ($email): ?>
                    <p class="instructor-email">
                        <strong>E-post:</strong> 
                        <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a>
                    </p>
                <?php endif; ?>
                
                <?php if ($phone): ?>
                    <p class="instructor-phone">
                        <strong>Telefon:</strong> 
                        <a href="tel:<?php echo esc_attr($phone); ?>"><?php echo esc_html($phone); ?></a>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (!empty($courses)): ?>
        <div class="instructor-courses">
            <h2>Kurs med <?php echo esc_html($term->name); ?></h2>
            
            <div class="courses-grid">
                <?php foreach ($courses as $course): ?>
                    <div class="course-card">
                        <h3>
                            <a href="<?php echo get_permalink($course->ID); ?>">
                                <?php echo esc_html($course->post_title); ?>
                            </a>
                        </h3>
                        
                        <?php if ($course->post_excerpt): ?>
                            <div class="course-excerpt">
                                <?php echo wp_kses_post($course->post_excerpt); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($course->post_type === 'coursedate'): ?>
                            <?php
                            $date = get_post_meta($course->ID, 'course_date', true);
                            if ($date): ?>
                                <div class="course-date">
                                    <strong>Dato:</strong> <?php echo esc_html($date); ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.instructor-archive {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

.instructor-header {
    display: flex;
    gap: 2rem;
    margin-bottom: 3rem;
}

.instructor-image {
    flex: 0 0 300px;
}

.instructor-image img {
    width: 100%;
    height: auto;
    border-radius: 8px;
}

.instructor-info {
    flex: 1;
}

.instructor-bio {
    margin: 1rem 0;
    line-height: 1.6;
}

.instructor-contact {
    margin-top: 1rem;
}

.instructor-contact p {
    margin: 0.5rem 0;
}

.courses-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 2rem;
    margin-top: 2rem;
}

.course-card {
    padding: 1.5rem;
    border: 1px solid #ddd;
    border-radius: 8px;
    background: #fff;
}

.course-card h3 {
    margin: 0 0 1rem;
}

.course-excerpt {
    margin-bottom: 1rem;
    color: #666;
}

.course-date {
    color: #333;
    font-weight: bold;
}
</style>

<?php get_footer(); ?> 
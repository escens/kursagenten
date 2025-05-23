<?php
/**
 * Modern enkeltvisning for kurs
 */

if (!defined('ABSPATH')) exit;

// Hent kurs-ID
$course_id = get_the_ID();

// Hent kursinformasjon
$course_title = get_the_title();
$course_content = get_the_content();
$course_excerpt = get_the_excerpt();
$course_thumbnail = get_the_post_thumbnail_url($course_id, 'large');

// Hent metadata
$first_course_date = get_post_meta($course_id, 'course_first_date', true);
$last_course_date = get_post_meta($course_id, 'course_last_date', true);
$registration_deadline = get_post_meta($course_id, 'course_registration_deadline', true);
$duration = get_post_meta($course_id, 'course_duration', true);
$coursetime = get_post_meta($course_id, 'course_time', true);
$price = get_post_meta($course_id, 'course_price', true);
$after_price = get_post_meta($course_id, 'course_text_after_price', true);
$location = get_post_meta($course_id, 'course_location', true);
$location_freetext = get_post_meta($course_id, 'course_location_freetext', true);
$location_room = get_post_meta($course_id, 'course_location_room', true);
$button_text = get_post_meta($course_id, 'course_button_text', true);
$signup_url = get_post_meta($course_id, 'course_signup_url', true);

// Hent taksonomier
$instructors = get_the_terms($course_id, 'instructors');
$categories = get_the_terms($course_id, 'coursecategory');
$locations = get_the_terms($course_id, 'course_location');

// Formater instruktører med nye URL-er
$instructor_links = [];
if (!empty($instructors) && !is_wp_error($instructors)) {
    $instructor_links = array_map(function ($term) {
        $instructor_url = get_instructor_display_url($term, 'instructors');
        return '<a href="' . esc_url($instructor_url) . '">' . esc_html($term->name) . '</a>';
    }, $instructors);
}

// Formater kategorier
$category_links = [];
if (!empty($categories) && !is_wp_error($categories)) {
    $category_links = array_map(function ($term) {
        return '<a href="' . esc_url(get_term_link($term)) . '">' . esc_html($term->name) . '</a>';
    }, $categories);
}

// Formater lokasjoner
$location_links = [];
if (!empty($locations) && !is_wp_error($locations)) {
    $location_links = array_map(function ($term) {
        return '<a href="' . esc_url(get_term_link($term)) . '">' . esc_html($term->name) . '</a>';
    }, $locations);
}
?>

<article id="post-<?php the_ID(); ?>" <?php post_class('kursagenten-single-course modern-design'); ?>>
    <div class="course-container">
        <header class="course-header">
            <h1 class="course-title"><?php echo esc_html($course_title); ?></h1>
            
            <?php if (!empty($course_excerpt)) : ?>
                <div class="course-excerpt">
                    <?php echo wp_kses_post($course_excerpt); ?>
                </div>
            <?php endif; ?>
            
            <div class="course-meta">
                <?php if (!empty($first_course_date)) : ?>
                    <div class="meta-item">
                        <i class="ka-icon icon-calendar"></i>
                        <span><?php echo esc_html(ka_format_date($first_course_date)); ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($location)) : ?>
                    <div class="meta-item">
                        <i class="ka-icon icon-location"></i>
                        <span><?php echo esc_html($location); ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($price)) : ?>
                    <div class="meta-item">
                        <i class="ka-icon icon-tag"></i>
                        <span><?php echo esc_html($price); ?> <?php echo esc_html($after_price); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </header>

        <div class="course-content-wrapper">
            <div class="course-main-content">
                <?php echo apply_filters('the_content', $course_content); ?>
            </div>
            
            <aside class="course-sidebar">
                <?php if (!empty($course_thumbnail)) : ?>
                    <div class="course-featured-image">
                        <img src="<?php echo esc_url($course_thumbnail); ?>" alt="<?php echo esc_attr($course_title); ?>">
                    </div>
                <?php endif; ?>
                
                <div class="course-info-box">
                    <h3>Kursinformasjon</h3>
                    
                    <div class="info-list">
                        <?php if (!empty($first_course_date)) : ?>
                            <div class="info-item">
                                <div class="info-label">Startdato:</div>
                                <div class="info-value"><?php echo esc_html(ka_format_date($first_course_date)); ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($last_course_date)) : ?>
                            <div class="info-item">
                                <div class="info-label">Sluttdato:</div>
                                <div class="info-value"><?php echo esc_html(ka_format_date($last_course_date)); ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($registration_deadline)) : ?>
                            <div class="info-item">
                                <div class="info-label">Påmeldingsfrist:</div>
                                <div class="info-value"><?php echo esc_html(ka_format_date($registration_deadline)); ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($duration)) : ?>
                            <div class="info-item">
                                <div class="info-label">Varighet:</div>
                                <div class="info-value"><?php echo esc_html($duration); ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($coursetime)) : ?>
                            <div class="info-item">
                                <div class="info-label">Tidspunkt:</div>
                                <div class="info-value"><?php echo esc_html($coursetime); ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($price)) : ?>
                            <div class="info-item">
                                <div class="info-label">Pris:</div>
                                <div class="info-value"><?php echo esc_html($price); ?> <?php echo esc_html($after_price); ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($location)) : ?>
                            <div class="info-item">
                                <div class="info-label">Sted:</div>
                                <div class="info-value"><?php echo esc_html($location); ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($location_freetext)) : ?>
                            <div class="info-item">
                                <div class="info-label">Lokasjon:</div>
                                <div class="info-value"><?php echo esc_html($location_freetext); ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($location_room)) : ?>
                            <div class="info-item">
                                <div class="info-label">Rom:</div>
                                <div class="info-value"><?php echo esc_html($location_room); ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($instructor_links)) : ?>
                            <div class="info-item">
                                <div class="info-label">Instruktører:</div>
                                <div class="info-value"><?php echo implode(', ', $instructor_links); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($signup_url)) : ?>
                        <div class="sidebar-actions">
                            <a href="<?php echo esc_url($signup_url); ?>" class="course-button primary full-width">
                                <?php echo esc_html($button_text ? $button_text : 'Meld deg på'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($category_links)) : ?>
                    <div class="course-categories-box">
                        <h3>Kategorier</h3>
                        <div class="categories-list">
                            <?php echo implode(', ', $category_links); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Share buttons -->
                <div class="course-share-box">
                    <h3>Del dette kurset</h3>
                    <div class="share-buttons">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(get_permalink()); ?>" target="_blank" class="share-button facebook">
                            <i class="ka-icon icon-facebook"></i>
                        </a>
                        <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(get_permalink()); ?>&text=<?php echo urlencode(get_the_title()); ?>" target="_blank" class="share-button twitter">
                            <i class="ka-icon icon-twitter"></i>
                        </a>
                        <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo urlencode(get_permalink()); ?>&title=<?php echo urlencode(get_the_title()); ?>" target="_blank" class="share-button linkedin">
                            <i class="ka-icon icon-linkedin"></i>
                        </a>
                        <a href="mailto:?subject=<?php echo urlencode(get_the_title()); ?>&body=<?php echo urlencode(get_permalink()); ?>" class="share-button email">
                            <i class="ka-icon icon-mail"></i>
                        </a>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    <?php
    // Relaterte kurs
    $related_args = [
        'post_type' => 'course',
        'posts_per_page' => 3,
        'post__not_in' => [$course_id],
        'tax_query' => []
    ];

    if (!empty($category_links)) {
        $related_args['tax_query'][] = [
            'taxonomy' => 'coursecategory',
            'field' => 'term_id',
            'terms' => array_map('term_exists', $category_links)
        ];
    }

    $related_courses = new WP_Query($related_args);

    if ($related_courses->have_posts()) : ?>
        <div class="related-courses">
            <h2>Relaterte kurs</h2>
            
            <div class="related-courses-grid">
                <?php while ($related_courses->have_posts()) : $related_courses->the_post(); 
                    $related_thumbnail = get_the_post_thumbnail_url(get_the_ID(), 'medium');
                    $related_price = get_post_meta(get_the_ID(), 'course_price', true);
                    $related_date = get_post_meta(get_the_ID(), 'course_first_date', true);
                ?>
                    <div class="related-course-card">
                        <?php if (!empty($related_thumbnail)) : ?>
                            <div class="related-course-image">
                                <a href="<?php the_permalink(); ?>">
                                    <img src="<?php echo esc_url($related_thumbnail); ?>" alt="<?php echo esc_attr(get_the_title()); ?>">
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <div class="related-course-content">
                            <h3 class="related-course-title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h3>
                            
                            <div class="related-course-meta">
                                <?php if (!empty($related_date)) : ?>
                                    <div class="meta-item">
                                        <i class="ka-icon icon-calendar"></i>
                                        <span><?php echo esc_html(ka_format_date($related_date)); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($related_price)) : ?>
                                    <div class="meta-item">
                                        <i class="ka-icon icon-tag"></i>
                                        <span><?php echo esc_html($related_price); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <a href="<?php the_permalink(); ?>" class="related-course-link">Les mer</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    <?php 
    endif;
    wp_reset_postdata();
    ?>

    <!-- CTA Section -->
    <div class="course-cta-section">
        <div class="cta-content">
            <h2>Klar til å melde deg på?</h2>
            <p>Sikre deg plass på dette kurset nå. Har du spørsmål, ikke nøl med å kontakte oss.</p>
            
            <div class="cta-buttons">
                <?php if (!empty($signup_url)) : ?>
                    <a href="<?php echo esc_url($signup_url); ?>" class="cta-button primary">
                        <?php echo esc_html($button_text ? $button_text : 'Meld deg på'); ?>
                    </a>
                <?php endif; ?>
                
                <a href="/kontakt" class="cta-button secondary">Kontakt oss</a>
            </div>
        </div>
    </div>
</article>

<script>
// Tabs functionality
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Remove active class from all buttons and contents
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Add active class to clicked button
            button.classList.add('active');
            
            // Show corresponding content
            const tabId = button.getAttribute('data-tab');
            document.getElementById('tab-' + tabId).classList.add('active');
        });
    });
    
    // Sticky CTA
    const stickyCta = document.querySelector('.course-sticky-cta');
    const heroSection = document.querySelector('.course-hero');
    
    if (stickyCta && heroSection) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > heroSection.offsetHeight) {
                stickyCta.classList.add('visible');
            } else {
                stickyCta.classList.remove('visible');
            }
        });
    }
});
</script>

<style>
/* Modern design styles */
.kursagenten-single-course.modern-design {
    --primary-color: #3498db;
    --secondary-color: #2c3e50;
    --accent-color: #e74c3c;
    --light-bg: #f8f9fa;
    --dark-bg: #2c3e50;
    --text-color: #333;
    --light-text: #fff;
    --border-radius: 8px;
    --box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    --transition: all 0.3s ease;
}

/* Hero section */
.course-hero {
    position: relative;
    background-size: cover;
    background-position: center;
    min-height: 500px;
    display: flex;
    align-items: center;
    color: var(--light-text);
    margin-bottom: 2rem;
}

.course-hero-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(to bottom, rgba(0,0,0,0.3), rgba(0,0,0,0.7));
    display: flex;
    align-items: center;
    padding: 2rem;
}

.course-hero-content {
    max-width: 800px;
    margin: 0 auto;
}

.course-hero-content .course-title {
    font-size: 3rem;
    margin-bottom: 1rem;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.course-hero-content .course-meta {
    display: flex;
    gap: 1.5rem;
    margin-top: 1.5rem;
}

.course-hero-content .meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.course-categories {
    margin-bottom: 1rem;
}

.course-category {
    display: inline-block;
    background: var(--primary-color);
    color: white;
    padding: 0.3rem 0.8rem;
    border-radius: 50px;
    font-size: 0.9rem;
    margin-right: 0.5rem;
    text-decoration: none;
    transition: var(--transition);
}

.course-category:hover {
    background: var(--secondary-color);
}

/* Sticky CTA */
.course-sticky-cta {
    position: fixed;
    bottom: -80px;
    left: 0;
    right: 0;
    background: white;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
    padding: 1rem;
    z-index: 100;
    transition: bottom 0.3s ease;
}

.course-sticky-cta.visible {
    bottom: 0;
}

.sticky-cta-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    max-width: 1200px;
    margin: 0 auto;
}

.sticky-course-title {
    font-weight: bold;
    flex: 1;
}

.sticky-course-price {
    font-weight: bold;
    color: var(--accent-color);
    margin: 0 2rem;
}

.sticky-cta-button {
    background: var(--primary-color);
    color: white;
    padding: 0.7rem 1.5rem;
    border-radius: var(--border-radius);
    text-decoration: none;
    font-weight: bold;
    transition: var(--transition);
}

.sticky-cta-button:hover {
    background: var(--secondary-color);
}

/* Content layout */
.course-content-wrapper {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
    margin-bottom: 3rem;
}

/* Tabs */
.course-tabs {
    margin-top: 2rem;
}

.tabs-nav {
    display: flex;
    border-bottom: 1px solid #ddd;
    margin-bottom: 1.5rem;
}

.tab-button {
    padding: 1rem 1.5rem;
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    cursor: pointer;
    font-weight: 600;
    transition: var(--transition);
}

.tab-button.active {
    border-bottom-color: var(--primary-color);
    color: var(--primary-color);
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

/* Course details grid */
.course-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1.5rem;
}

.detail-item {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    background: var(--light-bg);
    border-radius: var(--border-radius);
}

.detail-icon {
    font-size: 1.5rem;
    color: var(--primary-color);
}

.detail-content h4 {
    margin: 0 0 0.5rem 0;
}

.detail-content p {
    margin: 0;
}

/* Instructors grid */
.instructors-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 2rem;
}

.instructor-card {
    background: var(--light-bg);
    border-radius: var(--border-radius);
    overflow: hidden;
}

.instructor-image img {
    width: 100%;
    height: 200px;
    object-fit: cover;
}

.instructor-info {
    padding: 1.5rem;
}

.instructor-name {
    margin-top: 0;
}

/* Sidebar */
.course-sidebar {
    position: sticky;
    top: 2rem;
}

.course-info-box {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    overflow: hidden;
    margin-bottom: 2rem;
}

.course-price-box {
    background: var(--primary-color);
    color: white;
    padding: 1.5rem;
    text-align: center;
}

.price-label {
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.price-value {
    font-size: 2rem;
    font-weight: bold;
}

.sidebar-actions {
    padding: 1.5rem;
}

.course-button {
    display: inline-block;
    padding: 0.8rem 1.5rem;
    border-radius: var(--border-radius);
    text-decoration: none;
    font-weight: bold;
    transition: var(--transition);
    text-align: center;
}

.course-button.primary {
    background: var(--primary-color);
    color: white;
}

.course-button.primary:hover {
    background: var(--secondary-color);
}

.course-button.full-width {
    display: block;
    width: 100%;
}

.key-info {
    padding: 1.5rem;
}

.key-info-item {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
}

.info-icon {
    color: var(--primary-color);
}

.info-label {
    font-size: 0.8rem;
    color: #666;
}

.info-value {
    font-weight: 500;
}

/* Categories box */
.course-categories-box {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.categories-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.category-tag {
    display: inline-block;
    background: var(--light-bg);
    padding: 0.5rem 1rem;
    border-radius: 50px;
    text-decoration: none;
    color: var(--text-color);
    transition: var(--transition);
}

.category-tag:hover {
    background: var(--primary-color);
    color: white;
}

/* Share box */
.course-share-box {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    padding: 1.5rem;
}

.share-buttons {
    display: flex;
    gap: 1rem;
}

.share-button {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    color: white;
    text-decoration: none;
    transition: var(--transition);
}

.share-button.facebook {
    background: #3b5998;
}

.share-button.twitter {
    background: #1da1f2;
}

.share-button.linkedin {
    background: #0077b5;
}

.share-button.email {
    background: #666;
}

.share-button:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 10px rgba(0,0,0,0.2);
}

/* Related courses */
.related-courses {
    margin: 3rem 0;
}

.related-courses h2 {
    margin-bottom: 2rem;
    text-align: center;
}

.related-courses-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 2rem;
}

.related-course-card {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    overflow: hidden;
    transition: var(--transition);
}

.related-course-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.1);
}

.related-course-image img {
    width: 100%;
    height: 200px;
    object-fit: cover;
}

.related-course-content {
    padding: 1.5rem;
}

.related-course-title {
    margin-top: 0;
}

.related-course-title a {
    color: var(--text-color);
    text-decoration: none;
    transition: var(--transition);
}

.related-course-title a:hover {
    color: var(--primary-color);
}

.related-course-meta {
    display: flex;
    gap: 1rem;
    margin: 1rem 0;
}

.related-course-link {
    display: inline-block;
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 500;
    transition: var(--transition);
}

.related-course-link:hover {
    color: var(--secondary-color);
}

/* CTA Section */
.course-cta-section {
    background: var(--primary-color);
    color: white;
    padding: 4rem 0;
    text-align: center;
    margin-top: 3rem;
}

.cta-content {
    max-width: 800px;
    margin: 0 auto;
}

.cta-content h2 {
    font-size: 2.5rem;
    margin-bottom: 1rem;
}

.cta-content p {
    font-size: 1.2rem;
    margin-bottom: 2rem;
}

.cta-buttons {
    display: flex;
    justify-content: center;
    gap: 1rem;
}

.cta-button {
    display: inline-block;
    padding: 1rem 2rem;
    border-radius: 50px;
    text-decoration: none;
    font-weight: bold;
    transition: var(--transition);
}

.cta-button.primary {
    background: white;
    color: var(--primary-color);
}

.cta-button.primary:hover {
    background: var(--secondary-color);
    color: white;
}

.cta-button.secondary {
    background: transparent;
    border: 2px solid white;
    color: white;
}

.cta-button.secondary:hover {
    background: rgba(255,255,255,0.1);
}

/* Responsive */
@media (max-width: 768px) {
    .course-hero-content .course-title {
        font-size: 2rem;
    }
    
    .course-content-wrapper {
        grid-template-columns: 1fr;
    }
    
    .course-sidebar {
        position: static;
    }
    
    .course-hero-content .course-meta {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .cta-buttons {
        flex-direction: column;
    }
}
</style> 
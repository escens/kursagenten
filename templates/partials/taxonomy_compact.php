<?php
/**
 * Compact template for taxonomy archives
 * 
 * This template can be used for all taxonomies (coursecategory, course_location, instructors)
 * with specific customizations based on the taxonomy type.
 */

// Get current term
$term = get_queried_object();
$term_id = $term->term_id;

// Get taxonomy metadata
$meta = get_taxonomy_meta($term_id, $taxonomy);

// Get courses for this term
$courses = new WP_Query([
    'post_type' => 'course',
    'tax_query' => [
        [
            'taxonomy' => $taxonomy,
            'field'    => 'term_id',
            'terms'    => $term_id,
        ],
    ],
    'posts_per_page' => -1
]);

get_header(); ?>

<main id="main" class="site-main taxonomy-archive taxonomy-compact <?php echo esc_attr($taxonomy); ?>" role="main">
    <div class="taxonomy-header">
        <?php if (!empty($meta['image'])) : ?>
            <div class="taxonomy-image">
                <img src="<?php echo esc_url($meta['image']); ?>" alt="<?php echo esc_attr($term->name); ?>">
            </div>
        <?php endif; ?>

        <div class="taxonomy-title-wrapper">
            <?php if (!empty($meta['icon'])) : ?>
                <div class="taxonomy-icon">
                    <img src="<?php echo esc_url($meta['icon']); ?>" alt="">
                </div>
            <?php endif; ?>
            
            <h1 class="taxonomy-title">
                <?php
                // Tilpass overskriften basert på taksonomi
                switch ($taxonomy) {
                    case 'coursecategory':
                        echo esc_html($term->name) . ' kurs';
                        break;
                    case 'course_location':
                        echo 'Kurs i ' . esc_html($term->name);
                        break;
                    case 'instructors':
                        echo 'Kurs med ' . esc_html($term->name);
                        break;
                    default:
                        echo esc_html($term->name);
                }
                ?>
            </h1>
        </div>

        <?php if (!empty($meta['rich_description'])) : ?>
            <div class="taxonomy-description">
                <?php echo wp_kses_post($meta['rich_description']); ?>
            </div>
        <?php elseif (!empty($term->description)) : ?>
            <div class="taxonomy-description">
                <?php echo wp_kses_post($term->description); ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($courses->have_posts()) : ?>
        <div class="taxonomy-courses">
            <div class="course-header">
                <div class="course-count">
                    <?php 
                    $course_count = $courses->found_posts;
                    printf(
                        _n('%s kurs', '%s kurs', $course_count, 'kursagenten'),
                        number_format_i18n($course_count)
                    );
                    ?>
                </div>

                <div class="course-filters">
                    <!-- Her kan du legge til eventuelle filtre -->
                </div>
            </div>

            <div class="course-list compact-layout">
                <?php
                while ($courses->have_posts()) : $courses->the_post();
                    // Bruk samme template som for kurslister
                    get_course_template_part([
                        'course_count' => $course_count,
                        'query' => $courses
                    ]);
                endwhile;
                wp_reset_postdata();
                ?>
            </div>
        </div>
    <?php else : ?>
        <div class="no-courses">
            <p>Ingen kurs funnet i denne kategorien.</p>
        </div>
    <?php endif; ?>

    <?php
    // Vis relaterte termer hvis det finnes
    $related_terms = get_terms([
        'taxonomy' => $taxonomy,
        'exclude'  => [$term_id],
        'number'   => 6,
    ]);

    if (!empty($related_terms) && !is_wp_error($related_terms)) : ?>
        <div class="related-terms">
            <h2>
                <?php
                switch ($taxonomy) {
                    case 'coursecategory':
                        echo 'Andre kurskategorier';
                        break;
                    case 'course_location':
                        echo 'Andre kurssteder';
                        break;
                    case 'instructors':
                        echo 'Andre instruktører';
                        break;
                    default:
                        echo 'Relaterte emner';
                }
                ?>
            </h2>
            
            <div class="terms-grid">
                <?php foreach ($related_terms as $related_term) : 
                    $related_meta = get_taxonomy_meta($related_term->term_id, $taxonomy);
                ?>
                    <a href="<?php echo esc_url(get_term_link($related_term)); ?>" class="term-card">
                        <?php if (!empty($related_meta['image'])) : ?>
                            <div class="term-image">
                                <img src="<?php echo esc_url($related_meta['image']); ?>" 
                                     alt="<?php echo esc_attr($related_term->name); ?>">
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($related_meta['icon'])) : ?>
                            <div class="term-icon">
                                <img src="<?php echo esc_url($related_meta['icon']); ?>" alt="">
                            </div>
                        <?php endif; ?>
                        
                        <h3 class="term-name"><?php echo esc_html($related_term->name); ?></h3>
                        
                        <?php if (!empty($related_term->description)) : ?>
                            <div class="term-excerpt">
                                <?php echo wp_trim_words($related_term->description, 15); ?>
                            </div>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</main>

<?php get_footer(); ?> 
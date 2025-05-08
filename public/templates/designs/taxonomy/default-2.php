<?php
/**
 * Standard taksonomi-rammeverk
 * Brukes for course_location, coursecategory og instructors
 */

if (!defined('ABSPATH')) exit;

// Hent taksonomidata
$taxonomy_data = get_taxonomy_data();
$term = get_taxonomy_term($taxonomy_data['taxonomy'], $taxonomy_data['term_slug']);

// Sjekk om vi har en gyldig term
if (!isset($term->term_id) || !isset($term->taxonomy)) {
    wp_redirect(home_url());
    exit;
}

// Hent nødvendig data
$term_id = $term->term_id;
$taxonomy = $term->taxonomy;
$rich_description = get_term_meta($term_id, 'rich_description', true);
$image_url = get_taxonomy_image($term_id, $taxonomy);
$query = get_taxonomy_courses($term_id, $taxonomy);

// Logg informasjon om termen vi bruker
//error_log('Taxonomy template: Using term ID: ' . $term_id);
//error_log('Taxonomy template: Using taxonomy: ' . $taxonomy);
//error_log('Taxonomy template: Using term name: ' . $term->name);
//error_log('Taxonomy template: Using term slug: ' . $term->slug);
?>

<article class="ka-outer-container taxonomy-container">
    <header class="ka-section ka-taxonomy-header">
        <div class="ka-content-container">
            <div class="taxonomy-header-content">
                <?php 
                // Hent Kursagenten-bilde eller placeholder
                $ka_image_url = get_term_meta($term_id, 'image_instructor_ka', true);
                if (empty($ka_image_url)) {
                    $options = get_option('kag_kursinnst_option_name');
                    $ka_image_url = isset($options['ka_plassholderbilde_instruktor']) ? 
                        $options['ka_plassholderbilde_instruktor'] : '';
                }
                ?>
                <?php if (!empty($ka_image_url)): ?>
                    <div class="taxonomy-header-image">
                        <img src="<?php echo esc_url($ka_image_url); ?>" alt="<?php echo esc_attr($term->name); ?>">
                    </div>
                <?php endif; ?>
                <div class="taxonomy-header-text">
                    <h1><?php echo esc_html($term->name); ?></h1>
                    <?php if (!empty($term->description)): ?>
                        <div class="taxonomy-description">
                            <?php echo wp_kses_post($term->description); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <section class="ka-section ka-main-content">
        <div class="ka-content-container">
            <div class="taxonomy-content-grid">
                <?php 
                // Sjekk etter alternativt bilde (image_instructor)
                $alt_image_url = get_term_meta($term_id, 'image_instructor', true);
                
                if (!empty($alt_image_url)): 
                ?>
                    <div class="taxonomy-image">
                        <img src="<?php echo esc_url($alt_image_url); ?>" alt="<?php echo esc_attr($term->name); ?>">
                    </div>
                <?php endif; ?>

                <?php if (!empty($rich_description)): ?>
                    <div class="taxonomy-rich-description">
                        <?php 
                        // Bruk apply_filters for å tillate mer HTML-innhold
                        echo apply_filters('the_content', $rich_description); 
                        ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($query->have_posts()): ?>
                <div class="taxonomy-coursedates">
                    <h2>Tilgjengelige kurs</h2>
                    
                    <!-- Bruk samme system som i archive/default.php -->
                    <div class="courselist-items" id="filter-results">
                        <?php
                        $args = [
                            'course_count' => $query->found_posts,
                            'query' => $query
                        ];

                        while ($query->have_posts()) : $query->the_post();
                            get_course_template_part($args);
                        endwhile;
                        ?>
                    </div>
                    
                    <!-- Pagination Controls -->
                    <?php if ($query->max_num_pages > 1): ?>
                        <div class="pagination-wrapper">
                            <div class="pagination">
                            <?php
                            // Generate pagination links
                            echo paginate_links([
                                'base' => get_term_link($term) . '?%_%',
                                'current' => max(1, $query->get('paged')),
                                'format' => 'side=%#%',
                                'total' => $query->max_num_pages,
                                'prev_text' => '<i class="ka-icon icon-chevron-left"></i> <span>Forrige</span>',
                                'next_text' => '<span>Neste</span> <i class="ka-icon icon-chevron-right"></i>',
                                'add_args' => array_map(function ($item) {
                                    return is_array($item) ? join(',', $item) : $item;
                                }, array_diff_key($_REQUEST, ['side' => true, 'action' => true, 'nonce' => true]))
                            ]);
                            ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="no-courses-message">
                    <p>Ingen kurs tilgjengelige for øyeblikket.</p>
                </div>
            <?php endif; ?>
            <?php wp_reset_postdata(); ?>
        </div>
    </section>
</article>


<?php
/**
 * Moderne taksonomi-design
 * Brukes for course_location, coursecategory og instructors
 * 
 * Dette designet er fortsatt under utvikling.
 * Dette er et design-rammeverk som inneholder layout og struktur.
 * Selve kurslistevisningen kommer fra list-types filene (standard.php, grid.php, compact.php)
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

// Sjekk visningstype-innstilling
$view_type = get_option('kursagenten_taxonomy_view_type', 'main_courses');

// Hent kurs basert på visningstype
if ($view_type === 'all_coursedates') {
    // Vis alle kursdatoer - bruk [kursliste] kortkoden
    $shortcode_atts = [];
    
    if ($taxonomy === 'coursecategory') {
        $shortcode_atts[] = 'kategori="' . esc_attr($term->slug) . '"';
    } elseif ($taxonomy === 'course_location') {
        $shortcode_atts[] = 'sted="' . esc_attr($term->name) . '"';
    } elseif ($taxonomy === 'instructors') {
        $shortcode_atts[] = 'instruktør="' . esc_attr($term->slug) . '"';
    }
    
    $list_type = get_option('kursagenten_taxonomy_list_type', 'standard');
    $shortcode_atts[] = 'list_type="' . esc_attr($list_type) . '"';
    
    // Legg til bildevisning fra taksonomi-innstillinger
    $taxonomy_show_images = get_option("kursagenten_taxonomy_{$taxonomy}_show_images", '');
    if (!empty($taxonomy_show_images)) {
        $show_images = $taxonomy_show_images;
    } else {
        $show_images = get_option('kursagenten_show_images_taxonomy', 'yes');
    }
    $shortcode_atts[] = 'bilder="' . esc_attr($show_images) . '"';
    
    $shortcode = '[kursliste ' . implode(' ', $shortcode_atts) . ']';
    $query = null;
} else {
    // Vis hovedkurs (standard)
    $query = get_taxonomy_courses($term_id, $taxonomy);
}
?>

<article class="ka-outer-container taxonomy-container modern-design">
    <header class="ka-section ka-taxonomy-header">
        <div class="ka-content-container">
            <h1><?php echo esc_html($term->name); ?></h1>
            <?php if (!empty($term->description)): ?>
                <p><?php echo wp_kses_post($term->description); ?></p>
            <?php endif; ?>
        </div>
    </header>

    <section class="ka-section ka-main-content">
        <div class="ka-content-container">
            <?php if ($view_type === 'all_coursedates'): ?>
                <!-- Bruk [kursliste] shortcode -->
                <?php echo do_shortcode($shortcode); ?>
            <?php elseif ($query && $query->have_posts()): ?>
                <!-- Vis hovedkurs -->
                <div class="courselist-items" id="filter-results">
                    <?php
                    $args = [
                        'course_count' => $query->found_posts,
                        'query' => $query,
                        'view_type' => $view_type,
                        'is_taxonomy_page' => true,
                        'list_type' => $list_type
                    ];

                    while ($query->have_posts()) : $query->the_post();
                        get_course_template_part($args);
                    endwhile;
                    ?>
                </div>
                <?php wp_reset_postdata(); ?>
            <?php else: ?>
                <p>Ingen kurs tilgjengelige.</p>
            <?php endif; ?>
        </div>
    </section>
</article>

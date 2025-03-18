<?php
/**
 * Standard taksonomi-rammeverk
 * Brukes for course_location, coursecategory og instructors
 */

if (!defined('ABSPATH')) exit;

// Hent URL-stien direkte
$full_path = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
$path_segments = explode('/', trim($full_path, '/'));
error_log('Taxonomy template: URL Path: ' . $full_path);

// Identifiser taksonomi og term basert på URL-stien
$taxonomy = '';
$term_slug = '';

if (count($path_segments) >= 2) {
    $taxonomy_slug = $path_segments[0];
    $term_slug = $path_segments[1];
    
    // Fjern eventuelle query-parametere
    if (strpos($term_slug, '?') !== false) {
        $term_slug = substr($term_slug, 0, strpos($term_slug, '?'));
    }
    
    // Kartlegg taxonomy_slug til faktisk taksonomi
    switch ($taxonomy_slug) {
        case 'kurskategori':
            $taxonomy = 'coursecategory';
            break;
        case 'kurssted':
            $taxonomy = 'course_location';
            break;
        case 'instruktorer':
            $taxonomy = 'instructors';
            break;
    }
}

error_log('Taxonomy template: URL taxonomy slug: ' . $taxonomy_slug);
error_log('Taxonomy template: Mapped taxonomy: ' . $taxonomy);
error_log('Taxonomy template: Requested term slug: ' . $term_slug);

// Prøv å hente term basert på slug og taksonomi
$term = null;
if (!empty($taxonomy) && !empty($term_slug)) {
    $term = get_term_by('slug', $term_slug, $taxonomy);
    
    if ($term) {
        error_log('Taxonomy template: Found term: ' . $term->name . ' (ID: ' . $term->term_id . ')');
        
        // Oppdater global query objekt
        global $wp_query;
        $wp_query->queried_object = $term;
        $wp_query->queried_object_id = $term->term_id;
    } else {
        error_log('Taxonomy template: Term not found, using get_queried_object as fallback');
        $term = get_queried_object();
    }
} else {
    error_log('Taxonomy template: Invalid taxonomy or term, using get_queried_object');
    $term = get_queried_object();
}

// Sjekk om vi har en gyldig term
if (!isset($term->term_id) || !isset($term->taxonomy)) {
    error_log('Taxonomy template: No valid term found, redirecting to home');
    wp_redirect(home_url());
    exit;
}

$term_id = $term->term_id;
$taxonomy = $term->taxonomy;

// Logg informasjon om termen vi bruker
error_log('Taxonomy template: Using term ID: ' . $term_id);
error_log('Taxonomy template: Using taxonomy: ' . $taxonomy);
error_log('Taxonomy template: Using term name: ' . $term->name);
error_log('Taxonomy template: Using term slug: ' . $term->slug);

// Hent term metadata
$rich_description = get_term_meta($term_id, 'rich_description', true);
$image_url = '';

// Hent riktig bildefelt basert på taksonomi
switch ($taxonomy) {
    case 'coursecategory':
        $image_url = get_term_meta($term_id, 'image_coursecategory', true);
        break;
    case 'course_location':
        $image_url = get_term_meta($term_id, 'image_course_location', true);
        break;
    case 'instructors':
        $image_url = get_term_meta($term_id, 'image_instructor', true);
        break;
}

// Hent tilhørende kurs-datoer
$args = array(
    'post_type' => 'coursedate',
    'posts_per_page' => -1,
    'tax_query' => array(
        array(
            'taxonomy' => $taxonomy,
            'field'    => 'term_id',
            'terms'    => $term_id
        )
    ),
    'orderby' => 'course_first_date',
    'order' => 'ASC'
);

$query = get_course_dates_query($args);

get_header();
?>

<article class="ka-outer-container taxonomy-container">
    <header class="ka-section ka-taxonomy-header">
        <div class="ka-content-container">
            <div class="taxonomy-header-content">
                <h1><?php echo esc_html($term->name); ?></h1>
                <?php if (!empty($term->description)): ?>
                    <div class="taxonomy-description">
                        <?php echo wp_kses_post($term->description); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <section class="ka-section ka-main-content">
        <div class="ka-content-container">
            <div class="taxonomy-content-grid">
                <?php if (!empty($image_url)): ?>
                    <div class="taxonomy-image">
                        <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($term->name); ?>">
                    </div>
                <?php endif; ?>

                <?php if (!empty($rich_description)): ?>
                    <div class="taxonomy-rich-description">
                        <?php echo wp_kses_post($rich_description); ?>
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

<?php get_footer(); ?>
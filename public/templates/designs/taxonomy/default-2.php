<?php
/**
 * Standard 2 taksonomi-design
 * Brukes for course_location, coursecategory og instructors
 * Dette designet viser headerbildet i toppen og alternativt bilde i innholdet
 * 
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
$rich_description = get_term_meta($term_id, 'rich_description', true);
$image_url = get_taxonomy_image($term_id, $taxonomy);

// Sjekk visningstype-innstilling
$view_type = get_option('kursagenten_taxonomy_view_type', 'main_courses');

// Get list_type and show_images settings with proper override handling (used by both view types)
$list_type = get_taxonomy_setting($taxonomy, 'list_type', 'standard');
$show_images = get_taxonomy_setting($taxonomy, 'show_images', 'yes');

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
    
    $shortcode_atts[] = 'list_type="' . esc_attr($list_type) . '"';
    $shortcode_atts[] = 'bilder="' . esc_attr($show_images) . '"';
    
    $shortcode = '[kursliste ' . implode(' ', $shortcode_atts) . ']';
    $query = null;
} else {
    // Vis hovedkurs (standard)
    $query = get_taxonomy_courses($term_id, $taxonomy);
}
?>

<article class="ka-outer-container taxonomy-container">
    <header class="ka-section ka-taxonomy-header">
        <div class="ka-content-container">
            <div class="taxonomy-header-content">
                <?php 
                // Hent Kursagenten-bilde eller placeholder
                $ka_image_url = get_term_meta($term_id, 'image_instructor_ka', true);
                if (empty($ka_image_url)) {
                    $options = get_option('design_option_name');
                    $ka_image_url = isset($options['ka_plassholderbilde_instruktor']) ? 
                        $options['ka_plassholderbilde_instruktor'] : '';
                }
                ?>
                <?php if (!empty($ka_image_url)): ?>
                    <?php
                    // Get image dimensions for header image
                    $header_image_width = 300;
                    $header_image_height = 300;
                    $header_attachment_id = attachment_url_to_postid($ka_image_url);
                    if ($header_attachment_id) {
                        $header_image_data = wp_get_attachment_image_src($header_attachment_id, 'thumbnail');
                        if ($header_image_data) {
                            $header_image_width = $header_image_data[1];
                            $header_image_height = $header_image_data[2];
                        }
                    }
                    ?>
                    <div class="taxonomy-header-image">
                        <img src="<?php echo esc_url($ka_image_url); ?>" 
                             width="<?php echo esc_attr($header_image_width); ?>" 
                             height="<?php echo esc_attr($header_image_height); ?>" 
                             alt="<?php echo esc_attr($term->name); ?>"
                             title="<?php echo esc_attr($term->name); ?>">
                    </div>
                <?php endif; ?>
                <div class="taxonomy-header-text">
                    <h1><?php 
                    // Håndter navnevisning for instruktører
                    if ($taxonomy === 'instructors') {
                        $name_display = get_option('kursagenten_taxonomy_instructors_name_display', '');
                        switch ($name_display) {
                            case 'firstname':
                                $display_name = get_term_meta($term_id, 'instructor_firstname', true);
                                echo esc_html(!empty($display_name) ? $display_name : $term->name);
                                break;
                            case 'lastname':
                                $display_name = get_term_meta($term_id, 'instructor_lastname', true);
                                echo esc_html(!empty($display_name) ? $display_name : $term->name);
                                break;
                            default:
                                echo esc_html($term->name);
                        }
                    } else {
                        echo esc_html($term->name);
                    }
                    ?></h1>
                    <?php
                    // Hook immediately after the H1 title in header block
                    do_action('ka_taxonomy_after_title', $term);
                    ?>
                    <?php if (!empty($term->description)): ?>
                        <div class="taxonomy-description">
                            <?php echo wp_kses_post($term->description); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <?php
    // Hook right after the taxonomy header/title
    do_action('ka_taxonomy_header_after', $term);
    ?>

    <section class="ka-section ka-main-content">
        <div class="ka-content-container">
            <div class="taxonomy-content-grid">
                <?php 
                // Sjekk etter alternativt bilde (image_instructor)
                $alt_image_url = get_term_meta($term_id, 'image_instructor', true);
                
                if (!empty($alt_image_url)): 
                    // Get image dimensions for content image
                    $content_image_width = 500;
                    $content_image_height = 500;
                    $content_attachment_id = attachment_url_to_postid($alt_image_url);
                    if ($content_attachment_id) {
                        $content_image_data = wp_get_attachment_image_src($content_attachment_id, 'medium');
                        if ($content_image_data) {
                            $content_image_width = $content_image_data[1];
                            $content_image_height = $content_image_data[2];
                        }
                    }
                ?>
                    <div class="taxonomy-image">
                        <img src="<?php echo esc_url($alt_image_url); ?>" 
                             width="<?php echo esc_attr($content_image_width); ?>" 
                             height="<?php echo esc_attr($content_image_height); ?>" 
                             alt="<?php echo esc_attr($term->name); ?>"
                             title="<?php echo esc_attr($term->name); ?>">
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

            <?php
            // Hook below main image and extended description, before course list
            do_action('ka_taxonomy_below_description', $term);
            ?>

            <?php if ($view_type === 'all_coursedates'): ?>
                <!-- Bruk [kursliste] shortcode -->
                <div class="taxonomy-coursedates">
                    <h2>Tilgjengelige kurs</h2>
                    <?php
                    do_action('ka_courselist_before', $term);
                    echo do_shortcode($shortcode);
                    do_action('ka_courselist_after', $term);
                    ?>
                </div>
            <?php elseif ($query && $query->have_posts()): ?>
                <!-- Vis hovedkurs -->
                <div class="taxonomy-coursedates">
                    <h2>Tilgjengelige kurs</h2>
                    <?php
                    do_action('ka_courselist_before', $term);
                    ?>
                    
                    <!-- Kursliste - bruker valgt list-type -->
                    <div class="courselist-items" id="filter-results">
                        <?php
                        $args = [
                            'course_count' => $query->found_posts,
                            'query' => $query,
                            'instructor_url' => $taxonomy === 'instructors' ? get_instructor_display_url($term, $taxonomy) : null,
                            'view_type' => $view_type,
                            'is_taxonomy_page' => true,
                            'list_type' => $list_type,
                            'shortcode_show_images' => $show_images
                        ];

                        while ($query->have_posts()) : $query->the_post();
                            // Inkluder valgt list-type (standard, grid, compact)
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
                            $base_url = $taxonomy === 'instructors' ? get_instructor_display_url($term, $taxonomy) : get_term_link($term);
                            echo paginate_links([
                                'base' => $base_url . '?%_%',
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

                    <?php
                    // Hook after pagination controls
                    do_action('ka_taxonomy_pagination_after', $term);
                    ?>
                </div>
            <?php else: ?>
                <div class="no-courses-message">
                    <p>Ingen kurs tilgjengelige for øyeblikket.</p>
                </div>
            <?php endif; ?>
            <?php wp_reset_postdata(); ?>

            <?php
            // Hook below the course list (taxonomy footer)
            do_action('ka_taxonomy_footer', $term);
            ?>
        </div>
    </section>
</article>

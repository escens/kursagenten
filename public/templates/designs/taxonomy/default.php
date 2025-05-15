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
                <div class="left-column">
                    <?php if (!empty($image_url)): ?>
                        <div class="taxonomy-image">
                            <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($term->name); ?>">
                        </div>
                    <?php endif; ?>

                    <?php
                    // Hook for venstre kolonne basert på taksonomitype
                    switch ($taxonomy) {
                        case 'instructors':
                            do_action('ka_instructors_left_column', $term);
                            break;
                        case 'coursecategory':
                            do_action('ka_coursecategory_left_column', $term);
                            break;
                        case 'course_location':
                            do_action('ka_courselocation_left_column', $term);
                            break;
                    }
                    ?>
                </div>

                <div class="right-column">
                    <?php if (!empty($rich_description)): ?>
                        <div class="taxonomy-rich-description">
                            <?php 
                            // Bruk apply_filters for å tillate mer HTML-innhold
                            echo apply_filters('the_content', $rich_description); 
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($taxonomy === 'course_location'): ?>
                    <?php 
                    $specific_locations = get_term_meta($term_id, 'specific_locations', true);
                    if (!empty($specific_locations) && is_array($specific_locations)): 
                    ?>
                        <div class="specific-locations-section">
                            <h3>Spesifikke lokasjoner</h3>
                            <div class="specific-locations-grid">
                                <?php foreach ($specific_locations as $location): ?>
                                    <div class="location-card" title="Vis kurs i <?php echo esc_attr($location['description']); ?>" data-location="<?php echo esc_attr($location['description']); ?>">
                                        <div class="location-content">
                                            <h4><?php echo esc_html($location['description']); ?></h4>
                                            <?php if (!empty($location['address'])): ?>
                                                <div class="location-address">
                                                    <?php if (!empty($location['address']['street'])): ?>
                                                        <p class="street">
                                                            <?php echo esc_html($location['address']['street']); ?> 
                                                            <?php if (!empty($location['address']['number'])): ?>
                                                                <?php echo esc_html($location['address']['number']); ?>
                                                            <?php endif; ?>
                                                            <?php 
                                                            // Bygg Google Maps-lenke
                                                            $address_parts = array_filter([
                                                                $location['address']['street'],
                                                                $location['address']['number'],
                                                                $location['address']['zipcode'],
                                                                $location['address']['place']
                                                            ]);
                                                            $full_address = implode(' ', $address_parts);
                                                            if (!empty($full_address)): 
                                                                $maps_link = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($full_address);
                                                            ?>
                                                                <a href="<?php echo esc_url($maps_link); ?>" 
                                                                   target="_blank" 
                                                                   rel="noopener noreferrer" 
                                                                   class="maps-link" 
                                                                   title="Åpne i Google Maps">
                                                                    <i class="ka-icon icon-map-marker"></i>
                                                                    <span class="screen-reader-text">Åpne adresse i Google Maps</span>
                                                                </a>
                                                            <?php endif; ?>
                                                        </p>
                                                    <?php endif; ?>
                                                    <?php if (!empty($location['address']['zipcode']) || !empty($location['address']['place'])): ?>
                                                        <p class="postal">
                                                            <?php 
                                                            if (!empty($location['address']['zipcode'])) {
                                                                echo esc_html($location['address']['zipcode']);
                                                            }
                                                            if (!empty($location['address']['place'])) {
                                                                echo ' ' . esc_html($location['address']['place']);
                                                            }
                                                            ?>
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>


            <?php if ($query->have_posts()): ?>
                <div class="taxonomy-coursedates">
                    <h2>Tilgjengelige kurs</h2>
                    
                    <div class="courselist-items" id="filter-results">
                        <?php
                        $args = [
                            'course_count' => $query->found_posts,
                            'query' => $query
                        ];

                        while ($query->have_posts()) : $query->the_post();
                            // Hent course_location_freetext for kurset
                            $location_freetext = get_post_meta(get_the_ID(), 'course_location_freetext', true);
                            // Legg til data-location attributt i args
                            $args['data_location'] = $location_freetext;
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



<script>
document.addEventListener('DOMContentLoaded', function() {
    const locationCards = document.querySelectorAll('.location-card');
    const courseList = document.getElementById('filter-results');
    let currentLocation = null;

    locationCards.forEach(card => {
        card.addEventListener('click', function(e) {
            // Ikke trigger hvis man klikker på kart-ikonet
            if (e.target.closest('.maps-link')) {
                return;
            }

            const location = this.dataset.location;
            
            // Toggle active state
            if (currentLocation === location) {
                // Fjern filtrering
                currentLocation = null;
                this.classList.remove('active');
                locationCards.forEach(c => c.classList.remove('active'));
            } else {
                // Aktiver ny lokasjon
                currentLocation = location;
                locationCards.forEach(c => c.classList.remove('active'));
                this.classList.add('active');
            }

            // Filtrer kurs
            const courses = courseList.querySelectorAll('.courselist-item');
            courses.forEach(course => {
                const courseLocation = course.dataset.location;
                if (!currentLocation || courseLocation === currentLocation) {
                    course.style.display = '';
                } else {
                    course.style.display = 'none';
                }
            });

            // Oppdater URL med valgt lokasjon
            const url = new URL(window.location.href);
            if (currentLocation) {
                url.searchParams.set('location', encodeURIComponent(currentLocation));
            } else {
                url.searchParams.delete('location');
            }
            window.history.pushState({}, '', url);
        });
    });

    // Sjekk om det er en valgt lokasjon i URL
    const urlParams = new URLSearchParams(window.location.search);
    const selectedLocation = urlParams.get('location');
    if (selectedLocation) {
        const decodedLocation = decodeURIComponent(selectedLocation);
        const card = document.querySelector(`.location-card[data-location="${decodedLocation}"]`);
        if (card) {
            card.click();
        }
    }
});
</script>
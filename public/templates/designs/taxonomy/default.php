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
?>

<article class="ka-outer-container taxonomy-container">
    <header class="ka-section ka-taxonomy-header">
        <div class="ka-content-container">
            <div class="taxonomy-header-content">
                
                <h1>
                <a href="javascript:history.back()" class="back-link" title="Gå tilbake">
                    <i class="ka-icon icon-circle-left-regular page-back-link"></i>
                </a><?php 
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
                    
                    <?php
                    // Hent toppnivå kurskategorier for filtrering
                    $top_categories = get_top_level_categories_from_query($query);
                    
                    // Vis kun filteret hvis det er mer enn én kategori
                    if (count($top_categories) > 1): ?>
                        <div class="category-filter-wrapper">
                            <div class="category-filter">
                                <span class="filter-label">Filtrer på kategori:</span>
                                <div class="category-buttons">
                                    <button class="category-btn active" data-category="all">
                                        Alle (<?php echo $query->found_posts; ?>)
                                    </button>
                                    <?php foreach ($top_categories as $category): ?>
                                        <button class="category-btn" data-category="<?php echo esc_attr($category['slug']); ?>">
                                            <?php echo esc_html($category['name']); ?> (<?php echo $category['count']; ?>)
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="courselist-items" id="filter-results">
                        <?php
                        $args = [
                            'course_count' => $query->found_posts,
                            'query' => $query,
                            'instructor_url' => $taxonomy === 'instructors' ? get_instructor_display_url($term, $taxonomy) : null
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
                    
                    <!-- Melding når ingen kurs matcher filteret -->
                    <div class="no-courses-filtered-message" id="no-courses-filtered" style="display: none;">
                        <div class="message-content">
                            <i class="ka-icon icon-info"></i>
                            <h3>Ingen kurs tilgjengelige for dette filteret</h3>
                            <p>Prøv å endre dine filtervalg eller nullstill alle filtre for å se alle tilgjengelige kurs.</p>
                            <button class="ka-button reset-filters-btn" id="reset-filters-btn">
                                <i class="ka-icon icon-sync"></i>
                                Nullstill alle filtre
                            </button>
                        </div>
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
    
    // Kategori-filter funksjonalitet
    const categoryButtons = document.querySelectorAll('.category-btn');
    let currentCategory = 'all';

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
            filterCourses();

            // Oppdater URL med valgt lokasjon
            const url = new URL(window.location.href);
            if (currentLocation) {
                url.searchParams.set('location', encodeURIComponent(currentLocation));
            } else {
                url.searchParams.delete('location');
            }
            window.history.pushState({}, '', url);
            
            // Oppdater filtrering basert på både lokasjon og kategori
            filterCourses();
        });
    });
    
    // Event listeners for kategori-knapper
    categoryButtons.forEach(button => {
        button.addEventListener('click', function() {
            const category = this.dataset.category;
            
            // Oppdater active state
            categoryButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            currentCategory = category;
            
            // Oppdater URL med valgt kategori
            const url = new URL(window.location.href);
            if (category !== 'all') {
                url.searchParams.set('category', category);
            } else {
                url.searchParams.delete('category');
            }
            window.history.pushState({}, '', url);
            
            // Oppdater filtrering basert på både lokasjon og kategori
            filterCourses();
        });
    });
    
    // Event listener for nullstill filtre-knappen
    const resetFiltersBtn = document.getElementById('reset-filters-btn');
    if (resetFiltersBtn) {
        resetFiltersBtn.addEventListener('click', resetAllFilters);
    }
    
    // Funksjon for å filtrere kurs basert på både lokasjon og kategori
    function filterCourses() {
        const courses = courseList.querySelectorAll('.courselist-item');
        let visibleCount = 0;
        
        // Oppdater kategori-tellere basert på synlige kurs
        updateCategoryCounts(courses);
        
        courses.forEach(course => {
            const courseLocation = course.dataset.location;
            const courseCategories = course.dataset.category ? course.dataset.category.split(' ') : [];
            
            // Sjekk om kurset matcher både lokasjon og kategori
            const locationMatch = !currentLocation || courseLocation === currentLocation;
            const categoryMatch = currentCategory === 'all' || courseCategories.includes(currentCategory);
            
            if (locationMatch && categoryMatch) {
                course.style.display = '';
                visibleCount++;
            } else {
                course.style.display = 'none';
            }
        });
        
        // "Alle" knappen oppdateres nå av updateCategoryCounts() funksjonen
        
        // Vis eller skjul "ingen kurs" meldingen
        showNoCoursesMessage(visibleCount);
    }
    
    // Funksjon for å vise/skjule "ingen kurs" meldingen
    function showNoCoursesMessage(visibleCount) {
        const noCoursesMessage = document.getElementById('no-courses-filtered');
        const courseList = document.getElementById('filter-results');
        
        if (visibleCount === 0) {
            noCoursesMessage.style.display = 'block';
            courseList.style.display = 'none';
        } else {
            noCoursesMessage.style.display = 'none';
            courseList.style.display = 'flex';
        }
    }
    
    // Funksjon for å nullstille alle filtre
    function resetAllFilters() {
        // Nullstill lokasjon
        currentLocation = null;
        locationCards.forEach(card => card.classList.remove('active'));
        
        // Nullstill kategori
        currentCategory = 'all';
        categoryButtons.forEach(btn => btn.classList.remove('active'));
        const allButton = document.querySelector('.category-btn[data-category="all"]');
        if (allButton) {
            allButton.classList.add('active');
        }
        
        // Oppdater URL
        const url = new URL(window.location.href);
        url.searchParams.delete('location');
        url.searchParams.delete('category');
        window.history.pushState({}, '', url);
        
        // Kjør filtrering på nytt
        filterCourses();
    }
    
    // Funksjon for å oppdatere kategori-tellere basert på synlige kurs
    function updateCategoryCounts(courses) {
        const categoryCounts = {};
        let totalVisibleCount = 0;
        
        // Tell opp kurs for hver kategori basert på synlige kurs
        courses.forEach(course => {
            const courseLocation = course.dataset.location;
            const courseCategories = course.dataset.category ? course.dataset.category.split(' ') : [];
            
            // Sjekk om kurset er synlig basert på lokasjon
            const locationMatch = !currentLocation || courseLocation === currentLocation;
            
            if (locationMatch) {
                totalVisibleCount++;
                courseCategories.forEach(category => {
                    if (!categoryCounts[category]) {
                        categoryCounts[category] = 0;
                    }
                    categoryCounts[category]++;
                });
            }
        });
        
        // Oppdater alle kategori-knappene med nye tall
        categoryButtons.forEach(button => {
            const category = button.dataset.category;
            if (category !== 'all') {
                const count = categoryCounts[category] || 0;
                const originalText = button.textContent.split(' (')[0]; // Fjern eksisterende tall
                button.textContent = `${originalText} (${count})`;
            }
        });
        
        // Oppdater "Alle" knappen med totalt antall synlige kurs
        const allButton = document.querySelector('.category-btn[data-category="all"]');
        if (allButton) {
            allButton.textContent = `Alle (${totalVisibleCount})`;
        }
    }

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
    
    // Sjekk om det er en valgt kategori i URL
    const selectedCategory = urlParams.get('category');
    if (selectedCategory) {
        const categoryButton = document.querySelector(`.category-btn[data-category="${selectedCategory}"]`);
        if (categoryButton) {
            categoryButton.click();
        }
    }
    
    // Kjør initial filtrering
    filterCourses();
});
</script>
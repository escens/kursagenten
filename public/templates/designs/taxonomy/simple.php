<?php
/**
 * Simple taksonomi-design (uten bilde og beskrivelse)
 * Brukes for course_location, coursecategory og instructors
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

// Finn tilbake-lenke basert på taxonomy
$back_link_mapping = [
    'course_location' => 'kurssteder',
    'coursecategory' => 'kurskategorier',
    'instructors' => 'instruktorer'
];
$system_page_key = isset($back_link_mapping[$taxonomy]) ? $back_link_mapping[$taxonomy] : 'kurs';
$back_link_url = Designmaler::get_system_page_url($system_page_key);

// Hent sidetittel for tilbake-lenken
$page_id = get_option('ka_page_' . $system_page_key);
$back_link_title = $page_id && get_post($page_id) ? mb_strtolower(get_the_title($page_id)) : 'oversikten';

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
                
                <h1>
                <?php if (!empty($back_link_url)): ?>
                <a href="<?php echo esc_url($back_link_url); ?>" class="back-link" title="Tilbake til <?php echo esc_attr($back_link_title); ?>">
                    <i class="ka-icon icon-circle-left-regular page-back-link"></i>
                    <span class="sr-only">Tilbake til <?php echo esc_html($back_link_title); ?></span>
                </a>
                <?php endif; ?><?php 
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
    </header>

    <?php
    // Hook right after the taxonomy header/title
    do_action('ka_taxonomy_header_after', $term);
    ?>

    <section class="ka-section ka-main-content">
        <div class="ka-content-container">
        
                <!-- Uten innhold i venstre og høyre kolonne -->

            <?php
            // Hook below main image and extended description, before course list
            do_action('ka_taxonomy_below_description', $term);
            ?>
            <?php if ($taxonomy === 'course_location'): ?>
                    <?php 
                    $specific_locations = get_term_meta($term_id, 'specific_locations', true);
                    if (!empty($specific_locations) && is_array($specific_locations)): 
                    ?>
                        <div class="specific-locations-section">
                            <h3>Spesifikke lokasjoner</h3>
                            <div class="specific-locations-grid">
                                <?php foreach ($specific_locations as $location): ?>
                                    <?php
                                    // Bygg Google Maps-lenke
                                    $maps_link = '';
                                    if (!empty($location['address'])) {
                                        $address_parts = array_filter([
                                            $location['address']['street'] ?? '',
                                            $location['address']['number'] ?? '',
                                            $location['address']['zipcode'] ?? '',
                                            $location['address']['place'] ?? ''
                                        ]);
                                        $full_address = implode(' ', $address_parts);
                                        if (!empty($full_address)) {
                                            $maps_link = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($full_address);
                                        }
                                    }
                                    
                                    // Hvis "Alle kursdatoer", gjør hele boksen til Maps-link
                                    $is_map_link = ($view_type === 'all_coursedates' && !empty($maps_link));
                                    $card_tag = $is_map_link ? 'a' : 'div';
                                    $card_attrs = $is_map_link 
                                        ? 'href="' . esc_url($maps_link) . '" target="_blank" rel="noopener noreferrer" title="Åpne ' . esc_attr($location['description']) . ' i Google Maps"'
                                        : 'title="Vis kurs i ' . esc_attr($location['description']) . '" data-location="' . esc_attr($location['description']) . '"';
                                    $card_class = $is_map_link ? 'location-card location-map-link' : 'location-card';
                                    ?>
                                    <<?php echo $card_tag; ?> class="<?php echo $card_class; ?>" <?php echo $card_attrs; ?>>
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
                                                            <?php if (!$is_map_link && !empty($maps_link)): ?>
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
                                            <?php if ($is_map_link): ?>
                                                <div class="map-link-indicator">
                                                    <i class="ka-icon icon-map-marker"></i>
                                                    <span>Åpne i Google Maps</span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </<?php echo $card_tag; ?>>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>


            <?php if ($view_type === 'all_coursedates'): ?>
                <!-- Bruk [kursliste] shortcode -->
                <div class="taxonomy-coursedates">
                    <?php
                    do_action('ka_courselist_before', $term);
                    echo do_shortcode($shortcode);
                    do_action('ka_courselist_after', $term);
                    ?>
                </div>
            <?php elseif ($query && $query->have_posts()): ?>
                <!-- Vis hovedkurs -->
                <div class="taxonomy-coursedates">
                    <?php
                    do_action('ka_courselist_before', $term);
                    ?>
                    
                    <!-- Enkelt kategorifilter (for hovedkurs) -->
                    <?php
                    $top_categories = get_top_level_categories_from_query($query);
                    $top_categories = array_filter($top_categories, function($category) {
                        return $category['count'] > 0;
                    });
                    
                    if (count($top_categories) > 1): ?>
                        <div class="category-filter-wrapper">
                            <div class="category-filter">
                                <span class="filter-label">Filtrer på kategori:</span>
                                <div class="category-buttons">
                                    <button class="category-btn button-filter active" data-category="all">
                                        Alle (<?php echo $query->found_posts; ?>)
                                    </button>
                                    <?php foreach ($top_categories as $category): ?>
                                        <button class="category-btn button-filter" data-category="<?php echo esc_attr($category['slug']); ?>">
                                            <?php echo esc_html($category['name']); ?> (<?php echo $category['count']; ?>)
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
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
                            // Hent course_location_freetext for kurset
                            $location_freetext = get_post_meta(get_the_ID(), 'course_location_freetext', true);
                            // Legg til data-location attributt i args
                            $args['data_location'] = $location_freetext;
                            
                            // Inkluder valgt list-type (standard, grid, compact)
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
                            <button class="ka-button button-filter reset-filters-btn" id="reset-filters-btn">
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
    <section class="ka-section ka-footer-content">
        <div class="ka-content-container">
            <?php if ($taxonomy === 'coursecategory') : ?>
                <h2>Flere kurskategorier</h2>
                <?php echo do_shortcode('[kurskategorier layout="rad" stil="kort" grid=3 gridtablet=2 gridmobil=1 radavstand="1rem" bildestr="0px" overskrift="h4" fontmin="13px" fontmaks="16px" avstand="2em .5em"]'); ?>
            <?php elseif ($taxonomy === 'instructors') : ?>
                <h2>Flere instruktører</h2>
                <?php echo do_shortcode('[instruktorer layout="rad" stil="kort" grid=3 gridtablet=2 gridmobil=1 radavstand="1rem" bildestr="0px" overskrift="h4" fontmin="13px" fontmaks="16px" avstand="2em .5em"]'); ?>
            <?php elseif ($taxonomy === 'course_location') : ?>
                <h2>Flere kurssteder</h2>
                <?php echo do_shortcode('[kurssteder layout="rad" stil="kort" grid=5 gridtablet=2 gridmobil=1 radavstand="1rem" bildestr="0px" overskrift="h4" fontmin="13px" fontmaks="16px" avstand="2em .5em"]'); ?>
            <?php endif; ?>
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
        // Skip location-cards som er Google Maps-linker
        if (card.classList.contains('location-map-link')) {
            return;
        }
        
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
                course.classList.remove('hidden');
                visibleCount++;
            } else {
                course.classList.add('hidden');
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
            courseList.classList.add('hidden');
        } else {
            noCoursesMessage.style.display = 'none';
            courseList.classList.remove('hidden');
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
        
        // Vis alle kategoriknapper igjen
        categoryButtons.forEach(btn => {
            btn.style.display = '';
        });
        
        // Vis alle kurs igjen
        const courses = courseList.querySelectorAll('.courselist-item');
        courses.forEach(course => {
            course.classList.remove('hidden');
        });
        
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
        
        // Oppdater alle kategori-knappene med nye tall og skjul de med teller 0
        categoryButtons.forEach(button => {
            const category = button.dataset.category;
            if (category !== 'all') {
                const count = categoryCounts[category] || 0;
                const originalText = button.textContent.split(' (')[0]; // Fjern eksisterende tall
                button.textContent = `${originalText} (${count})`;
                
                // Skjul kategoriknapper med teller 0
                if (count === 0) {
                    button.style.display = 'none';
                } else {
                    button.style.display = '';
                }
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

<?php
/**
 * Standard taksonomi-rammeverk med kortkode for kursliste
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
        
            <div class="taxonomy-content-grid">
                <div class="left-column">
                    <?php if (!empty($image_url)): ?>
                        <div class="taxonomy-image">
                            <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($term->name); ?>">
                        </div>
                    <?php endif; ?>

                    <?php
                    // Felles hook for venstre kolonne på taksonomi-sider
                    do_action('ka_taxonomy_left_column', $term);
                    ?>
                </div>

                <div class="right-column">
                    <?php
                    // Hook at the top of the right column
                    do_action('ka_taxonomy_right_column_top', $term);
                    ?>
                    <?php if (!empty($rich_description)): ?>
                        <div class="taxonomy-rich-description">
                            <?php 
                            // Bruk apply_filters for å tillate mer HTML-innhold
                            echo apply_filters('the_content', $rich_description); 
                            ?>
                        </div>
                    <?php endif; ?>
                    <?php
                    // Hook at the bottom of the right column
                    do_action('ka_taxonomy_right_column_bottom', $term);
                    ?>
                </div>
            </div>

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

            <div class="taxonomy-coursedates">
                <h2>Tilgjengelige kurs</h2>
                <?php
                // Hook before the course list (above filters and pagination)
                do_action('ka_courselist_before', $term);
                ?>
                
                <?php
                // Bruk kortkode for kursliste basert på taksonomitype
                $shortcode_parameter = '';
                switch ($taxonomy) {
                    case 'coursecategory':
                        $shortcode_parameter = 'kategori="' . esc_attr($term->slug) . '"';
                        break;
                    case 'course_location':
                        // For lokasjoner, bruk term-navn i stedet for slug siden det er det som er lagret i meta-feltene
                        $shortcode_parameter = 'lokasjon="' . esc_attr($term->name) . '"';
                        break;
                    case 'instructors':
                        $shortcode_parameter = 'instruktør="' . esc_attr($term->slug) . '"';
                        break;
                }
                
                if (!empty($shortcode_parameter)) {
                    echo do_shortcode('[kursliste ' . $shortcode_parameter . ' force_standard_view="true"]');
                } else {
                    echo '<p>Ingen kurs tilgjengelige for øyeblikket.</p>';
                }
                ?>
            </div>
            <?php
            // Hook below the course list (taxonomy footer)
            do_action('ka_taxonomy_footer', $term);
            ?>
        </div>
    </section>
</article>

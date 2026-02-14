<?php
declare(strict_types=1);

require_once dirname(__FILE__) . '/includes/stable-id-generator.php';

if (!defined('ABSPATH')) exit;

/**
 * Shortcode for å vise kurssteder i grid-format
 * [kurssteder layout="grid/rad/liste" grid=3 gridtablet=2 gridmobil=1 radavstand="1rem" stil="standard/kort" bildestr="100px" bildeform="avrundet/rund/firkantet/10px" bildeformat="4/3" overskrift="h3" fontmin="13px" fontmaks="15px" avstand="2em .5em" skygge="ja" vis="standard/alta,oslo,bergen" region="østlandet" stedinfo="ja"]
 * 
 * vis-parameteren (filtrering, konsistent med kurskategorier):
 * - "standard": viser alle steder (standard)
 * - kommaseparert liste: viser kun spesifikke steder (f.eks. "alta,oslo,bergen" eller "tromso,trondheim")
 * - Kan bruke stedsnavn eller slug (case-insensitive)
 * 
 * region-parameteren:
 * - Filtrerer kurssteder basert på region (kun hvis regioner er aktivert)
 * - Gyldige verdier: "sørlandet", "østlandet", "vestlandet", "midt-norge", "nord-norge"
 * - Kan kombineres med vis-parameteren (OR-logikk: viser steder fra regionen ELLER de spesifiserte stedene)
 * 
 * stedinfo-parameteren:
 * - "ja": viser liste over spesifikke steder (fritekst sted fra Kursagenten) under hvert sted
 * - standard: viser ikke stedsinfo
 */
class CourseLocationGrid {
    private string $placeholder_image;
    
    public function __construct() {
        add_shortcode('kurssteder', [$this, 'render_locations']);
        $this->set_placeholder_image();
    }

    private function set_placeholder_image(): void {
        $options = get_option('design_option_name');
        $this->placeholder_image = !empty($options['ka_plassholderbilde_sted']) 
            ? $options['ka_plassholderbilde_sted']
            : KURSAG_PLUGIN_URL . 'assets/images/placeholder-location.jpg';
    }

    public function render_locations($atts): string {
        // Definer standardverdier
        $defaults = [
            'layout' => 'stablet',
            'stil' => 'standard',
            'grid' => '3',
            'gridtablet' => '2',
            'gridmobil' => '1',
            'radavstand' => '1rem',
            'bildestr' => '100px',
            'bildeform' => 'avrundet',
            'bildeformat' => '4/3',
            'skygge' => '',
            'utdrag' => '',
            'overskrift' => 'H3',
            'fontmin' => '13px',
            'fontmaks' => '18px',
            'avstand' => '2em .5em',
            'vis' => 'standard',
            'region' => '',
            'stedinfo' => '',
            'klasse' => ''
        ];

        // Slå sammen med brukerens attributter
        $a = shortcode_atts($defaults, $atts);
        // Track if 'radavstand' was explicitly provided in the shortcode attributes
        $a['_radavstand_provided'] = array_key_exists('radavstand', $atts) && $atts['radavstand'] !== '';
        $random_id = \StableIdGenerator::generate_id('kurssteder');
        
        // Prosesser attributter
        $a = $this->process_attributes($a);
        
        // Hent kurssteder
        $terms = $this->get_terms($a['vis'], $a['region']);
        
        if (empty($terms) || is_wp_error($terms)) {
            return '<div class="no-locations">Det er for øyeblikket ingen kurssteder å vise.</div>';
        }

        // Generer output ved å bruke ID-spesifikke grid-stiler
        $output = \GridStyles::get_grid_styles($random_id, $a);
        $output .= $this->get_location_specific_styles($random_id);
        $output .= $this->generate_html($random_id, $terms, $a);
        
        return $output;
    }

    private function process_attributes(array $atts): array {
        // Prosesser utdrag
        $atts['utdrag'] = ($atts['utdrag'] === 'ja') ? ' utdrag' : '';
        
        // Prosesser bildeform
        $atts['bildeform'] = match($atts['bildeform']) {
            'rund' => '50%',
            'firkantet' => '0px',  // Mer eksplisitt med px
            'avrundet' => '8px',
            default => $atts['bildeform']
        };
        
        // Prosesser skygge
        $atts['skygge'] = ($atts['skygge'] === 'ja') ? ' skygge' : '';
        
        // Kalkuler font størrelse
        $base_font_size = 16;
        $min_rem = floatval($atts['fontmin']) / $base_font_size;
        $max_rem = floatval($atts['fontmaks']) / $base_font_size;
        $atts['fontstr'] = "clamp({$min_rem}rem, 3.5vw - 0.219rem, {$max_rem}rem)";
        
        return $atts;
    }

    private function get_terms(string $vis = 'standard', string $region = ''): array 
    {
        $args = [
            'taxonomy' => 'ka_course_location',
            'hide_empty' => false,
            'meta_query' => [
                'relation' => 'AND',
                [
                    'relation' => 'OR',
                    [
                        'key' => 'hide_in_list',
                        'value' => 'Vis',
                    ],
                    [
                        'key' => 'hide_in_list',
                        'compare' => 'NOT EXISTS'
                    ]
                ]
            ],
            'orderby' => 'name',
            'order' => 'ASC'
        ];

        $location_term_ids = [];

        // Hvis region er spesifisert, hent alle term_ids fra den regionen
        if (!empty($region)) {
            // Convert region name to internal (ASCII) format
            require_once KURSAG_PLUGIN_DIR . '/includes/helpers/location-regions.php';
            $internal_region = kursagenten_get_region_internal_name($region);
            $valid_regions = kursagenten_get_valid_regions();
            
            // Sjekk om region er gyldig
            if (in_array($internal_region, $valid_regions, true)) {
                // Hent alle steder med denne regionen (bruk intern versjon)
                $region_terms = get_terms([
                    'taxonomy' => 'ka_course_location',
                    'hide_empty' => false,
                    'meta_query' => [
                        [
                            'key' => 'location_region',
                            'value' => $internal_region,
                            'compare' => '='
                        ]
                    ],
                    'fields' => 'ids'
                ]);
                
                if (!is_wp_error($region_terms) && !empty($region_terms)) {
                    $location_term_ids = array_merge($location_term_ids, array_map('intval', $region_terms));
                }
            }
        }

        // Hvis vis ikke er "standard", tolkes det som en kommaseparert liste av steder å filtrere på
        if ($vis !== 'standard' && !empty($vis)) {
            $location_names = array_map('trim', explode(',', $vis));
            
            foreach ($location_names as $location_name) {
                if (empty($location_name)) {
                    continue;
                }
                
                // Prøv først å finne via slug
                $location_slug = sanitize_title($location_name);
                $location_term = get_term_by('slug', $location_slug, 'ka_course_location');
                
                // Hvis ikke funnet via slug, prøv via navn (case-insensitive)
                if (!$location_term || is_wp_error($location_term)) {
                    $location_term = get_term_by('name', $location_name, 'ka_course_location');
                }
                
                if ($location_term && !is_wp_error($location_term)) {
                    $location_term_ids[] = (int) $location_term->term_id;
                }
            }
        }

        // Hvis vi har samlet term_ids (fra region og/eller vis), bruk dem med OR-logikk
        if (!empty($location_term_ids)) {
            // Fjern duplikater
            $location_term_ids = array_unique($location_term_ids);
            $args['include'] = $location_term_ids;
        } elseif (!empty($region)) {
            // Hvis kun region er spesifisert og vi ikke fant noen steder, returner tom array
            return [];
        }

        $terms = get_terms($args);
        
        // Hvis vi ikke har noen terms, returner tom array
        if (is_wp_error($terms) || empty($terms)) {
            return [];
        }

        // Filtrer bort steder uten tilknyttede kurs/coursedates og berik med spesifikke lokasjoner
        $filtered = [];
        foreach ($terms as $term) {
            if ($this->has_associated_content((int) $term->term_id)) {
                $term->specific_locations = $this->get_specific_locations_for_term((int) $term->term_id);
                $filtered[] = $term;
            }
        }
        return $filtered;
    }

    /**
     * Determine if a location term has any associated published content.
     * Checks:
     *  - published 'ka_course' posts using the 'ka_course_location' taxonomy
     *  - published 'ka_coursedate' posts referencing the location via meta 'ka_location_id'.
     */
    private function has_associated_content(int $term_id): bool
    {
        // Check for published 'ka_course' posts linked to this location
        $course_query = new \WP_Query([
            'post_type' => 'ka_course',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'tax_query' => [[
                'taxonomy' => 'ka_course_location',
                'field' => 'term_id',
                'terms' => $term_id,
            ]],
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ]);
        if ($course_query->have_posts()) {
            return true;
        }

        // Check for published 'ka_coursedate' posts pointing to this location via meta 'ka_location_id'
        $coursedate_ids = get_posts([
            'post_type' => 'ka_coursedate',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'meta_query' => [[
                'key' => 'ka_location_id',
                'value' => $term_id,
                'compare' => '=',
            ]],
        ]);

        return !empty($coursedate_ids);
    }

    private function get_specific_locations_for_term(int $term_id): array 
    {
        // Retrieve all ka_coursedate posts related to this location
        $coursedates = get_posts([
            'post_type' => 'ka_coursedate',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'ka_location_id',
                    'value' => $term_id,
                ]
            ]
        ]);

        $locations = [];
        foreach ($coursedates as $coursedate) {
            $location_freetext = get_post_meta($coursedate->ID, 'ka_course_location_freetext', true);
            if (!empty($location_freetext) && !in_array($location_freetext, $locations)) {
                $locations[] = $location_freetext;
            }
        }

        return $locations;
    }

    private function get_location_specific_styles(string $id): string {
        return "<style>

        </style>";
    }

    private function generate_html(string $id, array $terms, array $a): string {
        // Tilordne variabler med standardverdier
        $layout = $a['layout'];
        $stil = $a['stil'];
        $skygge = $a['skygge'];
        $bildeform = $a['bildeform'];
        $utdrag = $a['utdrag'];
        $overskrift = $a['overskrift'];
        $bildeformat = $a['bildeformat'];
        $custom_class = !empty($a['klasse']) ? ' ' . esc_attr($a['klasse']) : '';

        if ($bildeform == '50%') {
            $bildeformen = 'rund';
        } else {
            $bildeformen = '';
        }

        $output = "<div class='outer-wrapper {$layout} {$stil}{$skygge} {$utdrag} {$bildeformen}{$custom_class}' id='{$id}'>";
        $output .= "<div class='wrapper'>";

        foreach ($terms as $term) {
            // Hent thumbnail
            $thumbnail = get_term_meta($term->term_id, 'image_course_location', true);
            
            // Hvis ingen bilde, bruk placeholder
            if (empty($thumbnail)) {
                $options = get_option('design_option_name');
                $thumbnail = !empty($options['ka_plassholderbilde_sted']) ? 
                    $options['ka_plassholderbilde_sted'] : 
                    $this->placeholder_image;
            }

            // Sikre at URL-en er trygg
            $thumbnail = esc_url($thumbnail);

            // Fetch short and rich descriptions
            $short_description = wp_kses_post($term->description);
            $rich_description = get_term_meta($term->term_id, 'rich_description', true);
            $rich_description = wpautop(wp_kses_post($rich_description));

            // Always prefer short description for this shortcode
            $description = $short_description;

            $output .= $this->generate_location_html($term, $thumbnail, $description, $a);
        }

        $output .= "</div></div>";
        return $output;
    }

    private function generate_location_html($term, string $thumbnail, string $description, array $a): string {
        // Check if images should be displayed
        $show_image = !empty($a['bildestr']) && $a['bildestr'] !== '0' && $a['bildestr'] !== 0;
        
        $image_html = '';
        if ($show_image) {
            // Get image dimensions if it's from media library
            $width = 300;
            $height = 300;
            
            // Try to get actual dimensions if it's an attachment
            $attachment_id = attachment_url_to_postid($thumbnail);
            if ($attachment_id) {
                $image_data = wp_get_attachment_image_src($attachment_id, 'thumbnail');
                if ($image_data) {
                    $width = $image_data[1];
                    $height = $image_data[2];
                }
            }
            
            $image_html = "
                <a class='image box-inner' href='" . get_term_link($term) . "' title='{$term->name}'>
                    <picture>
                        <img src='{$thumbnail}' 
                             width='" . esc_attr($width) . "' 
                             height='" . esc_attr($height) . "' 
                             alt='Bilde av {$term->name}' 
                             class='wp-image-{$term->term_id}' 
                             decoding='async'>
                    </picture>
                </a>";
        }
        
        $output = "
            <div class='box term-{$term->term_id}'>
                {$image_html}
                <div class='text box-inner'>
                    <a class='title' href='" . get_term_link($term) . "' title='{$term->name}'>
                        <{$a['overskrift']} class='tittel'>" . ucfirst($term->name) . "</{$a['overskrift']}>
                    </a>
                    <div class='description info'>" . wp_kses_post($description) . "</div>";

                        // Hent spesifikke steder (fritekst sted fra Kursagenten) - kun hvis stedinfo="ja"
                        if ($a['stedinfo'] === 'ja') {
                            $specific_locations = get_term_meta($term->term_id, 'specific_locations', true);
                            
                            if (!empty($specific_locations) && is_array($specific_locations)) {
                                $output .= "<a class='infowrapper' href='" . get_term_link($term) . "'><div class='specific-locations info'>";
                                $output .= "<ul>";
                                
                                foreach ($specific_locations as $location) {
                                    $output .= "<li class='location-item'>";
                                    $output .= "" . esc_html($location['description']) . "";                         
                                    $output .= "</li>";
                                }
                                
                                $output .= "</ul>";
                                $output .= "</div></a>";//slutt specific-locations
                            }
                        }
                    $output .= "";//slutt info
                    //$output .= "</div>";//slutt box-inner
                    //$output .= "</div>";//slutt box
        

        $output .= "</div></div>";
        return $output;
    }
}

// Initialiser shortcode
new CourseLocationGrid(); 
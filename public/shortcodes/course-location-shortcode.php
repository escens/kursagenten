<?php
declare(strict_types=1);

if (!defined('ABSPATH')) exit;

/**
 * Shortcode for å vise kurssteder i grid-format
 * [kurssteder layout="grid/rad/liste" grid=3 gridtablet=2 gridmobil=1 radavstand="1rem" stil="standard/kort" bildestr="100px" bildeform="avrundet/rund/firkantet/10px" bildeformat="4/3" overskrift="h3" fontmin="13px" fontmaks="15px" avstand="2em .5em" skygge="ja"]
 */
class CourseLocationGrid {
    private string $placeholder_image;
    
    public function __construct() {
        add_shortcode('kurssteder', [$this, 'render_locations']);
        $this->set_placeholder_image();
    }

    private function set_placeholder_image(): void {
        $options = get_option('kag_kursinnst_option_name');
        $this->placeholder_image = !empty($options['ka_plassholderbilde_kurssted']) 
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
        ];

        // Slå sammen med brukerens attributter
        $a = shortcode_atts($defaults, $atts);
        $random_id = 'l-' . uniqid();
        
        // Prosesser attributter
        $a = $this->process_attributes($a);
        
        // Hent kurssteder
        $terms = $this->get_terms();
        
        if (empty($terms) || is_wp_error($terms)) {
            return '<div class="no-locations">Det er for øyeblikket ingen kurssteder å vise.</div>';
        }

        // Generer output
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
            'firkantet' => '0',
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

    private function get_terms(): array 
    {
        $args = [
            'taxonomy' => 'course_location',
            'hide_empty' => false,
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => 'hide_in_list',
                    'value' => 'Vis',
                ],
                [
                    'key' => 'hide_in_list',
                    'compare' => 'NOT EXISTS'
                ]
            ],
            'orderby' => 'name',
            'order' => 'ASC'
        ];

        $terms = get_terms($args);
        
        // Hvis vi ikke har noen terms, returner tom array
        if (is_wp_error($terms) || empty($terms)) {
            return [];
        }

        // Filtrer bort steder uten publiserte kurs og berik med spesifikke lokasjoner
        $filtered = [];
        foreach ($terms as $term) {
            $q = new \WP_Query([
                'post_type' => 'course',
                'post_status' => 'publish',
                'posts_per_page' => 1,
                'fields' => 'ids',
                'tax_query' => [[
                    'taxonomy' => 'course_location',
                    'field' => 'term_id',
                    'terms' => (int)$term->term_id,
                ]],
                'no_found_rows' => true,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
            ]);
            if ($q->have_posts()) {
                $term->specific_locations = $this->get_specific_locations_for_term($term->term_id);
                $filtered[] = $term;
            }
        }
        return $filtered;
    }

    private function get_specific_locations_for_term(int $term_id): array 
    {
        // Hent alle coursedates som er knyttet til dette kursstedet
        $coursedates = get_posts([
            'post_type' => 'coursedate',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'location_id',
                    'value' => $term_id,
                ]
            ]
        ]);

        $locations = [];
        foreach ($coursedates as $coursedate) {
            $location_freetext = get_post_meta($coursedate->ID, 'course_location_freetext', true);
            if (!empty($location_freetext) && !in_array($location_freetext, $locations)) {
                $locations[] = $location_freetext;
            }
        }

        return $locations;
    }

    private function get_location_specific_styles(string $id): string {
        return "<style>
            #{$id}.skygge:not(.kort) .box img {
                -webkit-box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.15);
                -moz-box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.15);
                box-shadow: 0px 2px 8px 0px rgba(53, 53, 53, 0.15);
                transition: transform ease 0.3s, box-shadow ease 0.3s;
            }
            #{$id}.rad .text {
                flex-direction: column;
                align-items: flex-start;
                justify-content: flex-start;
            


            
            #{$id} .specific-locations .address {
                font-size: 0.9em;
                color: #666;
                margin: 0.3em 0;
            }
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

        if ($bildeform == '50%') {
            $bildeformen = 'rund';
        }

        $output = "<div class='outer-wrapper {$layout} {$stil}{$skygge} {$utdrag} {$bildeformen}' id='{$id}'>";
        $output .= "<div class='wrapper'>";

        foreach ($terms as $term) {
            // Hent thumbnail
            $thumbnail = get_term_meta($term->term_id, 'image_course_location', true);
            
            // Hvis ingen bilde, bruk placeholder
            if (empty($thumbnail)) {
                $options = get_option('kag_kursinnst_option_name');
                $thumbnail = isset($options['ka_plassholderbilde_kurssted']) ? 
                    $options['ka_plassholderbilde_kurssted'] : 
                    $this->placeholder_image;
            }

            // Sikre at URL-en er trygg
            $thumbnail = esc_url($thumbnail);

            // Hent beskrivelser
            $short_description = wp_kses_post($term->description);
            $rich_description = get_term_meta($term->term_id, 'rich_description', true);
            $rich_description = wpautop(wp_kses_post($rich_description));
            
            // Bruk rich_description hvis tilgjengelig, ellers bruk standard beskrivelse
            $description = !empty($rich_description) ? $rich_description : $short_description;

            $output .= $this->generate_location_html($term, $thumbnail, $description, $a);
        }

        $output .= "</div></div>";
        return $output;
    }

    private function generate_location_html($term, string $thumbnail, string $description, array $a): string {
        $output = "
            <div class='box term-{$term->term_id}'>
                <a class='image box-inner' href='" . get_term_link($term) . "' title='{$term->name}'>
                    <picture>
                        <img src='{$thumbnail}' alt='Bilde av {$term->name}' class='wp-image-{$term->term_id}' decoding='async'>
                    </picture>
                </a>
                <div class='text box-inner'>
                    <a class='title' href='" . get_term_link($term) . "' title='{$term->name}'>
                        <{$a['overskrift']} class='tittel'>" . ucfirst($term->name) . "</{$a['overskrift']}>
                    </a>
                    <a class='info' href='" . get_term_link($term) . "'>
                        <div class='description'>" . wp_kses_post($description) . "</div>";

                        // Hent spesifikke lokasjoner
                        $specific_locations = get_term_meta($term->term_id, 'specific_locations', true);
                        
                        if (!empty($specific_locations) && is_array($specific_locations)) {
                            $output .= "<div class='specific-locations'>";
                            //output .= "<h4>Spesifikke lokasjoner:</h4>";
                            $output .= "<ul>";
                            
                            foreach ($specific_locations as $location) {
                                $output .= "<li class='location-item'>";
                                $output .= "" . esc_html($location['description']) . "";
                                
                                // Vis adresse hvis tilgjengelig
                                /*if (!empty($location['address']['street'])) {
                                    $output .= "<div class='address'>";
                                    $output .= esc_html($location['address']['street']);

                                    $output .= "<br>";
                                    if (!empty($location['address']['zipcode'])) {
                                        $output .= esc_html($location['address']['zipcode']);
                                    }
                                    if (!empty($location['address']['place'])) {
                                        $output .= " " . esc_html($location['address']['place']);
                                    }
                                    $output .= "</div>";
                                }*/
                                
                                // Vis maks antall deltakere hvis tilgjengelig
                                /*if (!empty($location['maxParticipants'])) {
                                    $output .= "<div class='max-participants'>";
                                    $output .= "Maks antall deltakere: " . esc_html($location['maxParticipants']);
                                    $output .= "</div>";
                                }*/
                                
                                $output .= "</li>";
                            }
                            
                            $output .= "</ul>";
                        $output .= "</div>";//slutt specific-locations
                    }
                    $output .= "</a>";//slutt info
                    //$output .= "</div>";//slutt box-inner
                    //$output .= "</div>";//slutt box
        

        $output .= "</div></div>";
        return $output;
    }
}

// Initialiser shortcode
new CourseLocationGrid(); 
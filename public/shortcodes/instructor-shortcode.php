<?php
declare(strict_types=1);

require_once dirname(__FILE__) . '/includes/stable-id-generator.php';

if (!defined('ABSPATH')) exit;

/**
 * Shortcode for å vise instruktører i grid-format
 * [instruktorer kilde="bilde/ikon" layout="grid/rad/liste" radavstand="1rem" bildestr="100px" bildeform="avrundet/rund/firkantet/10px" bildeformat="4/3" overskrift="h3" fontmaks="15px" avstand="2em .5em" skygge="ja" grid=3 gridtablet=2 gridmobil=1 skjul="Iris,Anna"]
 */
class InstructorGrid {
    private string $placeholder_image;
    
    public function __construct() {
        add_shortcode('instruktorer', [$this, 'render_instructors']);
        $this->set_placeholder_image();
    }

    private function set_placeholder_image(): void {
        $options = get_option('design_option_name');
        $this->placeholder_image = !empty($options['ka_plassholderbilde_instruktor']) 
            ? $options['ka_plassholderbilde_instruktor']
            : KURSAG_PLUGIN_URL . 'assets/images/placeholder-instruktor.jpg';
    }

    public function render_instructors($atts): string {
        $defaults = [
            'kilde' => 'bilde',
            'layout' => 'stablet',
            'stil' => 'standard',
            'grid' => '3',
            'gridtablet' => '2',
            'gridmobil' => '1',
            'radavstand' => '1rem',
            'bildestr' => '100px',
            'bildeform' => 'avrundet',
            'bildeformat' => '4/4',
            'skygge' => '',
            'utdrag' => '',
            'beskrivelse' => '',
            'overskrift' => 'H3',
            'fontmin' => '13px',
            'fontmaks' => '18px',
            'avstand' => '2em .5em',
            'vis' => 'standard',
            'skjul' => '',
            'klasse' => ''
        ];

        $a = shortcode_atts($defaults, $atts);
        $random_id = \StableIdGenerator::generate_id('instruktorer');
        
        // Prosesser attributter
        $a = $this->process_attributes($a);
        
        // Hent instruktører
        $terms = $this->get_terms();
        
        if (empty($terms) || is_wp_error($terms)) {
            return '<div class="no-instructors">Det er for øyeblikket ingen instruktører å vise.</div>';
        }

        // Generer output ved å bruke ID-spesifikke grid-stiler
        $output = \GridStyles::get_grid_styles($random_id, $a);
        
        // Legg til instruktør-spesifikk CSS
        $output .= $this->get_instructor_specific_styles($random_id);
        
        $output .= $this->generate_html($random_id, $terms, $a);
        
        return $output;
    }

    private function process_attributes(array $atts): array {
        // Prosesser utdrag/beskrivelse: begge viser .description, men innholdet velges senere
        $should_show_description = ($atts['utdrag'] === 'ja') || ($atts['beskrivelse'] === 'ja');
        $atts['utdrag'] = $should_show_description ? ' utdrag' : '';
        // Sett egen klasse når lang beskrivelse er aktivert
        $atts['beskrivelse'] = ($atts['beskrivelse'] === 'ja') ? ' beskrivelse' : '';
        // Internt flagg for om vi skal vise rich (lang) beskrivelse
        $atts['_show_rich'] = (trim($atts['beskrivelse']) === 'beskrivelse');
        
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

    private function get_terms(): array 
    {
        $args = [
            'taxonomy' => 'instructors',
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
        return is_wp_error($terms) ? [] : $terms;
    }

    private function get_instructor_specific_styles(string $id): string {
        return "<style>
            #{$id}.skygge:not(.kort) .box img {
                -webkit-box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.15);
                -moz-box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.15);
                box-shadow: 0px 2px 8px 0px rgba(53, 53, 53, 0.15);
                transition: transform ease 0.3s, box-shadow ease 0.3s;
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
        $custom_class = !empty($a['klasse']) ? ' ' . esc_attr($a['klasse']) : '';

        if ($bildeform == '50%') {
            $bildeformen = 'rund';
        } else {
            $bildeformen = '';
        }

        $output = "<div class='outer-wrapper {$layout} {$stil} {$skygge} {$utdrag}{$a['beskrivelse']} {$bildeformen}{$custom_class}' id='{$id}'>";
        $output .= "<div class='wrapper'>";

        foreach ($terms as $term) {
            $thumbnail = '';
            
            if ($a['kilde'] === 'ka-bilde') {
                // Hent bilde fra KA API
                $thumbnail = get_term_meta($term->term_id, 'image_instructor_ka', true);
            } else {
                // Hent opplastet bilde
                $thumbnail = get_term_meta($term->term_id, 'image_instructor', true);
            }
            
            // Hvis fortsatt ingen bilde funnet, prøv den andre kilden som fallback
            if (empty($thumbnail)) {
                if ($a['kilde'] === 'ka-bilde') {
                    // Prøv opplastet bilde som fallback
                    $thumbnail = get_term_meta($term->term_id, 'image_instructor', true);
                } else {
                    // Prøv KA-bilde som fallback
                    $thumbnail = get_term_meta($term->term_id, 'image_instructor_ka', true);
                }
            }
            
            // Hvis ingen bilde funnet, bruk placeholder
            if (empty($thumbnail)) {
                $options = get_option('design_option_name');
                $thumbnail = !empty($options['ka_plassholderbilde_instruktor']) ? 
                    $options['ka_plassholderbilde_instruktor'] : 
                    $this->placeholder_image;
            }

            // Sikre at URL-en er trygg
            $thumbnail = esc_url($thumbnail);

        // Hent kort (WP term description) og lang (rich_description) beskrivelse
        $short_description = wpautop(wp_kses_post($term->description));
        $rich_description = get_term_meta($term->term_id, 'rich_description', true);
        $rich_description = wpautop(wp_kses_post($rich_description));

        // Velg innhold basert på attributter: beskrivelse => lang, utdrag => kort
        $selected_description = $a['_show_rich'] ? $rich_description : $short_description;

        $output .= $this->generate_instructor_html($term, $thumbnail, $selected_description, $a);
        }

        $output .= "</div></div>";
        return $output;
    }

    private function generate_instructor_html($term, string $thumbnail, string $description, array $a): string {
        // Get display name based on 'vis' attribute
        $display_name = match($a['vis']) {
            'fornavn' => get_term_meta($term->term_id, 'instructor_firstname', true),
            'etternavn' => get_term_meta($term->term_id, 'instructor_lastname', true),
            default => $term->name
        };

        // If meta field is empty, fallback to term name
        $display_name = !empty($display_name) ? $display_name : $term->name;
        
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
        
        return "
            <div class='box term-{$term->term_id}'>
                {$image_html}
                <div class='text box-inner'>
                    <a class='title' href='" . get_term_link($term) . "' title='{$term->name}'>
                        <{$a['overskrift']} class='tittel'>" . ucfirst($display_name) . "</{$a['overskrift']}>
                    </a>
                    <div class='description info'>" . $description . "</div>
                </div>
            </div>";
    }
}

// Initialiser shortcode
new InstructorGrid(); 
<?php
declare(strict_types=1);

if (!defined('ABSPATH')) exit;

/**
 * Shortcode for å vise instruktører i grid-format
 * [instruktorer kilde="bilde/ikon" layout="grid/rad/liste" bildestr="100px" bildeform="avrundet/rund/firkantet/10px" bildeformat="4/3" fonttype="h3" fontmaks="15px" avstand="2em .5em" skygge="ja" grid=3 gridtablet=2 gridmobil=1 skjul="Iris,Anna"]
 */
class InstructorGrid {
    private string $placeholder_image;
    
    public function __construct() {
        add_shortcode('instruktorer', [$this, 'render_instructors']);
        $this->set_placeholder_image();
    }

    private function set_placeholder_image(): void {
        $options = get_option('kag_kursinnst_option_name');
        $this->placeholder_image = !empty($options['ka_plassholderbilde_instruktor']) 
            ? $options['ka_plassholderbilde_instruktor']
            : KURSAG_PLUGIN_URL . 'assets/images/placeholder-instruktor.jpg';
    }

    public function render_instructors($atts): string {
        $defaults = [
            'kilde' => 'bilde_instruktor',
            'layout' => 'stablet',
            'stil' => 'standard',
            'grid' => '3',
            'gridtablet' => '2',
            'gridmobil' => '1',
            'bildestr' => '100px',
            'bildeform' => 'avrundet',
            'bildeformat' => '4/4',
            'skygge' => '',
            'utdrag' => '',
            'fonttype' => 'H3',
            'fontmin' => '13px',
            'fontmaks' => '18px',
            'avstand' => '2em .5em',
            'vis' => 'standard',
            'skjul' => '',
        ];

        $a = shortcode_atts($defaults, $atts);
        $random_id = 'i-' . uniqid();
        
        // Prosesser attributter
        $a = $this->process_attributes($a);
        
        // Hent instruktører
        $terms = $this->get_instructors($a['skjul']);
        
        if (empty($terms) || is_wp_error($terms)) {
            return '<div class="no-instructors">Det er for øyeblikket ingen instruktører å vise.</div>';
        }

        // Generer output ved å bruke felles grid-stiler
        $output = \GridStyles::get_grid_styles($random_id, $a);
        
        // Legg til instruktør-spesifikk CSS
        $output .= $this->get_instructor_specific_styles($random_id);
        
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

    private function get_instructors(string $skjul): array {
        // Konverter 'skjul' attributt til array med fornavn
        $excluded_names = array_map(function ($name) {
            return mb_strtolower(trim($name), 'UTF-8');
        }, explode(',', $skjul));

        $terms = get_terms([
            'taxonomy' => 'instructors',
            'hide_empty' => false,
            'orderby' => 'menu_order',
            'order' => 'ASC',
        ]);

        if (is_wp_error($terms)) {
            return [];
        }

        // Filtrer ut instruktører som skal skjules
        return array_filter($terms, function($term) use ($excluded_names) {
            $first_word = mb_strtolower(strtok($term->name, ' '), 'UTF-8');
            return !in_array($first_word, $excluded_names);
        });
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
        $output = "<div class='outer-wrapper {$a['layout']} {$a['stil']} {$a['kilde']}{$a['skygge']} {$a['bildeform']}{$a['utdrag']}' id='{$id}'>";
        $output .= "<div class='wrapper'>";

        foreach ($terms as $term) {
            // Hent thumbnail
            $thumbnail = get_term_meta($term->term_id, 'image_instructor', true);
            if (empty($thumbnail)) {
                $thumbnail = get_term_meta($term->term_id, 'image_instructor_ka', true);
            }
            
            // Hvis fortsatt ingen bilde, bruk placeholder
            if (empty($thumbnail)) {
                $options = get_option('kag_kursinnst_option_name');
                $thumbnail = isset($options['ka_plassholderbilde_instruktor']) ? 
                    $options['ka_plassholderbilde_instruktor'] : 
                    $this->placeholder_image;
            }

            // Sikre at URL-en er trygg
            $thumbnail = esc_url($thumbnail);

            // Hent beskrivelse
            $description = get_term_meta($term->term_id, 'rich_description', true);
            $description = wp_kses_post($description);

            $output .= $this->generate_instructor_html($term, $thumbnail, $description, $a);
        }

        $output .= "</div></div>";
        return $output;
    }

    private function generate_instructor_html($term, string $thumbnail, string $description, array $a): string {
        return "
            <div class='box term-{$term->term_id}'>
                <a class='image box-inner' href='" . get_term_link($term) . "' title='{$term->name}'>
                    <picture>
                        <img src='{$thumbnail}' alt='Bilde av {$term->name}' class='wp-image-{$term->term_id}' decoding='async'>
                    </picture>
                </a>
                <div class='text box-inner'>
                    <a class='title' href='" . get_term_link($term) . "' title='{$term->name}'>
                        <{$a['fonttype']} class='tittel'>{$term->name}</{$a['fonttype']}>
                    </a>
                    <div class='description'>{$description}</div>
                </div>
            </div>";
    }
}

// Initialiser shortcode
new InstructorGrid(); 
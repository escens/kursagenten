<?php
declare(strict_types=1);


if (!defined('ABSPATH')) exit;

/**
 * Shortcode for å vise relaterte kurs i samme kategori
 * [kurs-i-samme-kategori layout=stablet/rad/liste stil=kort ...]
 */
class RelatedCourses {
    private string $placeholder_image;
    
    public function __construct() {
        add_shortcode('kurs-i-samme-kategori', [$this, 'render_related_courses']);
        $this->set_placeholder_image();
    }

    private function set_placeholder_image(): void {
        $options = get_option('bedriftsinformasjon_option_name');
        $this->placeholder_image = !empty($options['plassholderbilde_kurs']) 
            ? $options['plassholderbilde_kurs']
            : 'https://kursagenten.no/images/cmspluss/placeholder-kurs.jpg';
    }

    public function render_related_courses($atts): string {
        $defaults = [
            'kilde' => 'bilde_kurskategori',
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
            'vis' => 'standard'
        ];

        $a = shortcode_atts($defaults, $atts);
        $random_id = 'r-' . uniqid();
        
        // Prosesser attributter
        $a = $this->process_attributes($a);
        
        // Hent relaterte kurs
        $related_posts = $this->get_related_courses();
        
        if (empty($related_posts)) {
            return '<p>Ingen relaterte kurs funnet</p>';
        }

        // Generer output
        $output = \GridStyles::get_grid_styles($random_id, $a);
        $output .= $this->generate_html($random_id, $related_posts, $a);
        
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

    private function get_related_courses(): array {
        global $post;

        // Sjekk om gjeldende innlegg har terms i 'kurskategori' taksonomien
        $terms = get_the_terms($post->ID, 'coursecategory');

        if (!$terms || is_wp_error($terms)) {
            return [];
        }

        // Bruk første term (kan modifiseres for å håndtere flere terms)
        $current_term = $terms[0];

        // Hent alle innlegg i samme 'kurskategori'
        return get_posts([
            'post_type' => 'course',
            'tax_query' => [[
                'taxonomy' => 'coursecategory',
                'field' => 'term_id',
                'terms' => $current_term->term_id,
            ]],
            'posts_per_page' => -1,
            'post__not_in' => [$post->ID], // Ekskluder gjeldende innlegg
        ]);
    }

    private function generate_html(string $id, array $posts, array $a): string {
        $output = "<div class='outer-wrapper {$a['layout']} {$a['stil']} {$a['kilde']}{$a['skygge']} {$a['bildeform']}{$a['utdrag']}' id='{$id}'>";
        $output .= "<div class='wrapper'>";

        foreach ($posts as $related_post) {
            $thumbnail_data = $this->get_thumbnail_data($related_post, $a);
            $output .= $this->generate_course_html($related_post, $thumbnail_data, $a);
        }

        $output .= "</div></div>";
        return $output;
    }

    private function get_thumbnail_data($post, array $a): array {
        $thumbnail_id = get_post_thumbnail_id($post->ID);
        
        if (empty($thumbnail_id)) {
            return [
                'url' => $this->placeholder_image,
                'srcset' => '',
                'sizes' => ''
            ];
        }

        return [
            'url' => wp_get_attachment_image_url($thumbnail_id, 'thumbnail'),
            'srcset' => wp_get_attachment_image_srcset($thumbnail_id, ['thumbnail', 'medium', 'large']),
            'sizes' => "(max-width: 530px) 100vw, (max-width: 1024px) calc(100% / {$a['gridtablet']}), calc(100% / {$a['grid']})"
        ];
    }

    private function generate_course_html($post, array $thumbnail, array $a): string {
        $title = get_the_title($post->ID);
        return "
            <div class='box term-{$post->ID}'>
                <a class='image box-inner' href='" . get_permalink($post->ID) . "' title='{$title}'>
                    <picture>
                        <img src='{$thumbnail['url']}' 
                             srcset='{$thumbnail['srcset']}'
                             sizes='{$thumbnail['sizes']}'
                             alt='Bilde av kurs i {$title}'
                             class='wp-image-{$post->ID}'
                             decoding='async'>
                    </picture>
                </a>
                <div class='text box-inner'>
                    <a class='title' href='" . get_permalink($post->ID) . "' title='{$title}'>
                        <{$a['fonttype']} class='tittel'>{$title}</{$a['fonttype']}>
                    </a>
                    <div class='description'>" . get_the_excerpt($post->ID) . "</div>
                </div>
            </div>";
    }
}

// Initialiser shortcode
new RelatedCourses(); 
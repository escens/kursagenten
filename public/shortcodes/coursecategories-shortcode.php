<?php
declare(strict_types=1);

if (!defined('ABSPATH')) exit;

/**
 * Shortcode for å vise kurskategorier i grid-format
 * [kurskategorier kilde="bilde/ikon" layout="grid/rad/liste" bildestr="100px" bildeform="avrundet/rund/firkantet/10px" bildeformat="4/3" fonttype="h3" fontmin="13" fontmaks="18" avstand="2em .5em" skygge="ja" grid=3 gridtablet=2 gridmobil=1  vis="hovedkategorier/subkategorier/standard"]
 */
class CourseCategories {
    private string $placeholder_image;
    private string $kilde = 'bilde_kurskategori';
    
    public function __construct() {
        add_shortcode('kurskategorier', [$this, 'render_categories']);
        $this->set_placeholder_image();
    }

    private function set_placeholder_image(): void {
        $options = get_option('kag_kursinnst_option_name');
        $this->placeholder_image = !empty($options['ka_plassholderbilde_kurs']) 
            ? $options['ka_plassholderbilde_kurs']
            : KURSAG_PLUGIN_URL . 'assets/images/placeholder-kurs.jpg';
    }

    public function render_categories($atts): string {
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
        $this->kilde = $a['kilde'];
        $random_id = 'k-' . uniqid();
        
        // Prosesser attributter
        $a = $this->process_attributes($a);
        
        // Hent og filtrer terms
        $terms = $this->get_filtered_terms($a['vis']);
        
        // Generer output ved å bruke felles grid-stiler
        $output = \GridStyles::get_grid_styles($random_id, $a);
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

    private function get_filtered_terms(string $vis): array {
        $terms = get_terms([
            'taxonomy' => 'coursecategory',
            'hide_empty' => true,
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
            'order' => 'ASC',
        ]);

        if (is_wp_error($terms)) {
            return [];
        }

        return $this->filter_terms($terms, $vis);
    }

    private function filter_terms(array $terms, string $vis): array 
    {
        // Bygg oversikt over foreldre-barn forhold
        $parent_map = [];
        // Bygg oversikt over foreldre til kategorier med innlegg
        $active_parent_map = [];
        foreach ($terms as $term) {
            if ($term->parent !== 0) {
                $parent_map[$term->parent] = true;
                if ($term->count > 0) {
                    $active_parent_map[$term->parent] = true;
                }
            }
        }

        return array_filter($terms, function($term) use ($vis, $parent_map, $active_parent_map) {
            $has_children = isset($parent_map[$term->term_id]);
            $has_active_children = isset($active_parent_map[$term->term_id]);

            return match($vis) {
                'subkategorier' => $has_children ? $term->parent != 0 : ($term->count > 0),
                'hovedkategorier' => $term->parent == 0 && ($term->count > 0 || $has_active_children),
                default => $term->count > 0
            };
        });
    }

    private function generate_html(string $id, array $terms, array $a): string {
        $output = "<div class='outer-wrapper {$a['layout']} {$a['stil']} {$a['kilde']}{$a['skygge']} {$a['bildeform']}{$a['utdrag']}' id='{$id}'>";
        $output .= "<div class='wrapper'>";

        foreach ($terms as $term) {
            $thumbnail = $this->get_term_thumbnail($term);
            $output .= $this->generate_term_html($term, $thumbnail, $a);
        }

        $output .= "</div></div>";
        return $output;
    }

    private function get_term_thumbnail($term): string {
        // Nå vil $this->kilde alltid være definert
        $meta_key = ($this->kilde === 'ikon') ? 'icon_coursecategory' : 'image_coursecategory';
        
        // Prøv å hente bilde fra term meta
        $thumbnail = get_term_meta($term->term_id, $meta_key, true);
        if (!empty($thumbnail)) {
            return $thumbnail;
        }

        // Hvis ingen term-bilde funnet, prøv å hente fra tilknyttet kurs
        $posts = get_posts([
            'post_type' => 'course',
            'tax_query' => [[
                'taxonomy' => 'coursecategory',
                'field' => 'term_id',
                'terms' => $term->term_id,
            ]],
            'posts_per_page' => 1,
            'meta_query' => [[
                'key' => '_thumbnail_id',
                'compare' => 'EXISTS',
            ]],
        ]);

        if (!empty($posts)) {
            $thumbnail_url = get_the_post_thumbnail_url($posts[0]->ID, 'thumbnail');
            if (!empty($thumbnail_url)) {
                return $thumbnail_url;
            }
        }

        // Hvis ingen bilder funnet, returner placeholder
        return $this->placeholder_image;
    }

    private function generate_term_html($term, string $thumbnail, array $a): string {
        return "
            <div class='box term-{$term->term_id}'>
                <a class='image box-inner' href='" . get_term_link($term) . "' title='{$term->name}'>
                    <picture>
                        <img src='{$thumbnail}' alt='Bilde av kurs i {$term->name}' class='wp-image-{$term->term_id}' decoding='async'>
                    </picture>
                </a>
                <div class='text box-inner'>
                    <a class='title' href='" . get_term_link($term) . "' title='{$term->name}'>
                        <{$a['fonttype']} class='tittel'>{$term->name}</{$a['fonttype']}>
                    </a>
                </div>
            </div>";
    }
}

// Initialiser shortcode
new CourseCategories();
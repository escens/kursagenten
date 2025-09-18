<?php
declare(strict_types=1);

require_once dirname(__FILE__) . '/includes/grid-styles.php';

if (!defined('ABSPATH')) exit;

/**
 * Shortcode for å vise kurskategorier i grid-format
 * [kurskategorier kilde="bilde/ikon" layout="grid/rad/liste" radavstand="1rem" bildestr="100px" bildeform="avrundet/rund/firkantet/10px" bildeformat="4/3" overskrift="h3" fontmin="13" fontmaks="18" avstand="2em .5em" skygge="ja" grid=3 gridtablet=2 gridmobil=1  vis="hovedkategorier/subkategorier/standard/<slug>"]
 *
 * "vis" kan være:
 * - hovedkategorier: bare toppnivå-kategorier
 * - subkategorier: bare underkategorier (alle som har forelder)
 * - standard: alle kategorier som har publiserte kurs
 * - <slug>: vis underkategorier under gitt foreldreslug, f.eks. vis=dans
 */
class CourseCategories {
    private string $placeholder_image;
    private string $kilde = 'bilde_kurskategori';
    
    public function __construct() {
        add_shortcode('kurskategorier', [$this, 'render_categories']);
        $this->set_placeholder_image();
    }

    private function set_placeholder_image(): void {
        $options = get_option('design_option_name');
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
            'radavstand' => '1rem',
            'bildestr' => '100px',
            'bildeform' => 'avrundet',
            'bildeformat' => '4/4',
            'skygge' => '',
            'utdrag' => '',
            'overskrift' => 'H3',
            'fontmin' => '13px',
            'fontmaks' => '18px',
            'avstand' => '2em .5em',
            'vis' => 'standard'
        ];

        $a = shortcode_atts($defaults, $atts);
        // Track if 'radavstand' was explicitly provided in the shortcode attributes
        $a['_radavstand_provided'] = array_key_exists('radavstand', $atts) && $atts['radavstand'] !== '';
        $this->kilde = $a['kilde'];
        $random_id = 'k-' . uniqid();
        
        // Prosesser attributter
        $a = $this->process_attributes($a);
        
        // Hent og filtrer terms
        $terms = $this->get_filtered_terms($a['vis']);
        
        // Generer output ved å bruke felles grid-stiler
        $output = \GridStyles::get_grid_styles($random_id, $a);
        
        // Legg til kategori-spesifikk CSS
        $output .= $this->get_category_specific_styles($random_id);
        
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
        // If "vis" is a slug, resolve to a specific parent term id
        $parent_term_id = null;
        $known_modes = ['subkategorier', 'hovedkategorier', 'standard'];
        if (!in_array($vis, $known_modes, true)) {
            $slug = sanitize_title($vis);
            $parent_term = get_term_by('slug', $slug, 'coursecategory');
            if ($parent_term && !is_wp_error($parent_term)) {
                $parent_term_id = (int) $parent_term->term_id;
            }
        }

        // Build maps based on published posts only (avoid counting drafts)
        $has_published = [];
        $children_of = [];
        foreach ($terms as $term) {
            $has_published[$term->term_id] = $this->term_has_published_courses((int)$term->term_id);
            if ($term->parent !== 0) {
                $children_of[$term->parent] = $children_of[$term->parent] ?? [];
                $children_of[$term->parent][] = (int)$term->term_id;
            }
        }

        return array_filter($terms, function($term) use ($vis, $parent_term_id, $has_published, $children_of) {
            $term_id = (int)$term->term_id;
            $is_root = ($term->parent == 0);
            $has_children = isset($children_of[$term_id]);
            $has_active_children = false;
            if ($has_children) {
                foreach ($children_of[$term_id] as $child_id) {
                    if (!empty($has_published[$child_id])) {
                        $has_active_children = true;
                        break;
                    }
                }
            }

            // Decide visibility per mode
            // If a specific parent slug was provided, show only its direct children with published posts
            if ($parent_term_id !== null) {
                return ($term->parent == $parent_term_id) && !empty($has_published[$term_id]);
            }

            switch ($vis) {
                case 'subkategorier':
                    // Show subcategories that themselves have published posts
                    return ($term->parent != 0) && !empty($has_published[$term_id]);
                case 'hovedkategorier':
                    // Show root categories with own published posts OR at least one child with published posts
                    return $is_root && (!empty($has_published[$term_id]) || $has_active_children);
                default:
                    // Standard: show any category that has published posts
                    return !empty($has_published[$term_id]);
            }
        });
    }

    // Check if a term has at least one published course
    private function term_has_published_courses(int $term_id): bool {
        // Query only published posts to avoid drafts keeping categories visible
        $q = new \WP_Query([
            'post_type' => 'course',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'tax_query' => [[
                'taxonomy' => 'coursecategory',
                'field' => 'term_id',
                'terms' => $term_id,
            ]],
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ]);
        return $q->have_posts();
    }

    private function generate_html(string $id, array $terms, array $a): string {
        // Tilordne variabler med standardverdier
        $kilde = $a['kilde'];
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

        $output = "<div class='outer-wrapper {$layout} {$stil} {$kilde}{$skygge} {$utdrag} {$bildeformen}' id='{$id}'>";
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
        // Short description (WP term description)
        $short_description = wpautop(wp_kses_post($term->description));

        return "
            <div class='box term-{$term->term_id}'>
                <a class='image box-inner' href='" . get_term_link($term) . "' title='{$term->name}'>
                    <picture>
                        <img src='{$thumbnail}' alt='Bilde av kurs i {$term->name}' class='wp-image-{$term->term_id}' decoding='async'>
                    </picture>
                </a>
                <div class='text box-inner'>
                    <a class='title' href='" . get_term_link($term) . "' title='{$term->name}'>
                        <{$a['overskrift']} class='tittel'>" . ucfirst($term->name) . "</{$a['overskrift']}>
                    </a>
                    <div class='description'>" . $short_description . "</div>
                </div>
            </div>";
    }

    private function get_category_specific_styles(string $id): string {
        return "<style>
            #{$id}.skygge:not(.kort) .box img {
                -webkit-box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.15);
                -moz-box-shadow: 0px 2px 8px 0px rgba(0,0,0,0.15);
                box-shadow: 0px 2px 8px 0px rgba(53, 53, 53, 0.15);
                transition: transform ease 0.3s, box-shadow ease 0.3s;
            }
        </style>";
    }
}

// Initialiser shortcode
new CourseCategories();
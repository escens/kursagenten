<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

require_once KURSAG_PLUGIN_DIR . '/public/blocks/shared/shortcode-attribute-mapper.php';

/**
 * Builds a safe CSS value with fallback.
 */
function kursagenten_css_value(string $value, string $fallback): string {
    $value = trim($value);
    if ($value === '') {
        return $fallback;
    }

    $sanitized = sanitize_text_field($value);
    if ($sanitized === '') {
        return $fallback;
    }

    // Block characters/tokens that can break out of inline style context.
    if (preg_match('/[{};<>[:cntrl:]]/u', $sanitized) === 1) {
        return $fallback;
    }

    $lower = strtolower($sanitized);
    if (strpos($lower, 'url(') !== false || strpos($lower, 'expression(') !== false || strpos($lower, '@import') !== false) {
        return $fallback;
    }

    return $sanitized;
}

/**
 * Builds a safe CSS color value with fallback.
 */
function kursagenten_css_color_value(string $value, string $fallback): string {
    $value = trim($value);
    if ($value === '') {
        return $fallback;
    }

    $hex = sanitize_hex_color($value);
    if (is_string($hex) && $hex !== '') {
        return $hex;
    }

    if (preg_match('/^var\(--[a-z0-9_-]+(?:\s*,\s*[^)]+)?\)$/i', $value) === 1) {
        return $value;
    }

    if (preg_match('/^(rgba?|hsla?)\([0-9.\s,%+-]+\)$/i', $value) === 1) {
        return $value;
    }

    if (in_array(strtolower($value), ['transparent', 'inherit', 'currentcolor'], true)) {
        return $value;
    }

    return $fallback;
}

/**
 * Build a fluid clamp font size based on px values.
 */
function kursagenten_build_font_clamp(int $min_px, int $max_px): string {
    $min_ratio = $min_px / 16;
    $max_ratio = $max_px / 16;

    return sprintf(
        'clamp(calc(var(--ka-base-font, 16px) * %.4f), calc(2.2vw + var(--ka-base-font, 16px) * 0.25), calc(var(--ka-base-font, 16px) * %.4f))',
        $min_ratio,
        $max_ratio
    );
}

/**
 * Convert text alignment keyword to flex alignment value.
 */
function kursagenten_map_alignment_to_flex(string $alignment): string {
    if ($alignment === 'center') {
        return 'center';
    }
    if ($alignment === 'right') {
        return 'flex-end';
    }
    return 'flex-start';
}

/**
 * Convert vertical alignment keyword to flex justification value.
 */
function kursagenten_map_vertical_alignment_to_flex(string $alignment): string {
    if ($alignment === 'center') {
        return 'center';
    }
    if ($alignment === 'bottom') {
        return 'flex-end';
    }
    return 'flex-start';
}

/**
 * Resolve a CSS keyword against an allow-list.
 *
 * @param array<int,string> $allowed
 */
function kursagenten_css_keyword(string $value, array $allowed, string $fallback): string {
    $normalized = strtolower(trim($value));
    if ($normalized === '') {
        return $fallback;
    }

    return in_array($normalized, $allowed, true) ? $normalized : $fallback;
}

/**
 * Extract inline padding values (right/left) from CSS shorthand.
 *
 * @return array{right:string,left:string}
 */
function kursagenten_padding_inline_values(string $padding, string $fallback): array {
    $resolved = kursagenten_css_value($padding, $fallback);
    preg_match_all('/(calc\([^)]*\)|var\([^)]*\)|[^\s]+)/', trim($resolved), $matches);
    $parts = $matches[0] ?? [];

    if (empty($parts)) {
        return ['right' => '0px', 'left' => '0px'];
    }

    if (count($parts) === 1) {
        return ['right' => $parts[0], 'left' => $parts[0]];
    }

    if (count($parts) === 2 || count($parts) === 3) {
        return ['right' => $parts[1], 'left' => $parts[1]];
    }

    return ['right' => $parts[1], 'left' => $parts[3]];
}

/**
 * Get inner HTML of a DOM node.
 */
function kursagenten_dom_inner_html(DOMNode $node): string {
    $html = '';
    foreach ($node->childNodes as $child) {
        $html .= $node->ownerDocument ? $node->ownerDocument->saveHTML($child) : '';
    }
    return $html;
}

/**
 * Trim HTML to a word limit while preserving safe links.
 */
function kursagenten_trim_html_words_preserve_links(string $html, int $word_limit): string {
    $sanitized = wp_kses_post($html);
    if ($word_limit <= 0) {
        return $sanitized;
    }

    $plain = trim(wp_strip_all_tags($sanitized));
    if ($plain === '') {
        return '';
    }

    if (str_word_count($plain) <= $word_limit) {
        return $sanitized;
    }

    if (!class_exists('DOMDocument')) {
        return esc_html(wp_trim_words($plain, $word_limit));
    }

    $document = new DOMDocument('1.0', 'UTF-8');
    $wrapped_html = '<div>' . $sanitized . '</div>';
    $previous_errors = libxml_use_internal_errors(true);
    $loaded = $document->loadHTML('<?xml encoding="UTF-8">' . $wrapped_html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($previous_errors);

    if (!$loaded) {
        return esc_html(wp_trim_words($plain, $word_limit));
    }

    $root = $document->getElementsByTagName('div')->item(0);
    if (!$root instanceof DOMElement) {
        return esc_html(wp_trim_words($plain, $word_limit));
    }

    $words_used = 0;
    $is_complete = false;

    $trim_node = function (DOMNode $node) use (&$trim_node, &$words_used, &$is_complete, $word_limit): void {
        if ($is_complete) {
            if ($node->parentNode) {
                $node->parentNode->removeChild($node);
            }
            return;
        }

        if ($node->nodeType === XML_TEXT_NODE) {
            $text = trim((string) preg_replace('/\s+/u', ' ', (string) $node->nodeValue));
            if ($text === '') {
                return;
            }

            $parts = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
            if (!is_array($parts) || empty($parts)) {
                return;
            }

            $count = count($parts);
            if (($words_used + $count) <= $word_limit) {
                $words_used += $count;
                return;
            }

            $keep = max(0, $word_limit - $words_used);
            if ($keep === 0) {
                $node->nodeValue = '';
                $is_complete = true;
                return;
            }

            $node->nodeValue = implode(' ', array_slice($parts, 0, $keep)) . '...';
            $words_used = $word_limit;
            $is_complete = true;
            return;
        }

        if ($node->hasChildNodes()) {
            $children = [];
            foreach ($node->childNodes as $child) {
                $children[] = $child;
            }
            foreach ($children as $child) {
                if ($is_complete) {
                    $node->removeChild($child);
                    continue;
                }
                $trim_node($child);
            }
        }
    };

    $trim_node($root);
    return wp_kses_post(kursagenten_dom_inner_html($root));
}

/**
 * Returns box shadow values based on preset.
 *
 * @return array{normal:string,hover:string}
 */
function kursagenten_get_shadow_values(string $preset): array {
    if ($preset === 'outline') {
        return ['normal' => 'none', 'hover' => 'none'];
    }
    if ($preset === 'xsoft') {
        return ['normal' => '0px 0px 7px rgba(15, 23, 42, 0.1)', 'hover' => '0px 0px 10px rgba(15, 23, 42, 0.14)'];
    }
    if ($preset === 'soft') {
        return ['normal' => '0 2px 8px rgba(15, 23, 42, 0.12)', 'hover' => '0 4px 14px rgba(15, 23, 42, 0.18)'];
    }
    if ($preset === 'medium') {
        return ['normal' => '0 6px 16px rgba(15, 23, 42, 0.16)', 'hover' => '0 10px 24px rgba(15, 23, 42, 0.24)'];
    }
    if ($preset === 'large') {
        return ['normal' => '0 10px 24px rgba(15, 23, 42, 0.2)', 'hover' => '0 14px 34px rgba(15, 23, 42, 0.28)'];
    }
    if ($preset === 'xl') {
        return ['normal' => '0 14px 34px rgba(15, 23, 42, 0.24)', 'hover' => '0 20px 44px rgba(15, 23, 42, 0.32)'];
    }
    return ['normal' => 'none', 'hover' => 'none'];
}

/**
 * Returns taxonomy slug from source type.
 */
function kursagenten_taxonomy_from_source(string $source_type): string {
    if ($source_type === 'location') {
        return 'ka_course_location';
    }
    if ($source_type === 'instructor') {
        return 'ka_instructors';
    }
    return 'ka_coursecategory';
}

/**
 * Resolve placeholder image by source type.
 */
function kursagenten_get_placeholder_image_url(string $source_type): string {
    $options = get_option('design_option_name');

    if ($source_type === 'location') {
        $configured = !empty($options['ka_plassholderbilde_sted']) ? (string) $options['ka_plassholderbilde_sted'] : '';
        return esc_url($configured !== '' ? $configured : KURSAG_PLUGIN_URL . 'assets/images/placeholder-location.jpg');
    }

    if ($source_type === 'instructor') {
        $configured = !empty($options['ka_plassholderbilde_instruktor']) ? (string) $options['ka_plassholderbilde_instruktor'] : '';
        return esc_url($configured !== '' ? $configured : KURSAG_PLUGIN_URL . 'assets/images/placeholder-instruktor.jpg');
    }

    $configured = !empty($options['ka_plassholderbilde_kurs']) ? (string) $options['ka_plassholderbilde_kurs'] : '';
    return esc_url($configured !== '' ? $configured : KURSAG_PLUGIN_URL . 'assets/images/placeholder-kurs.jpg');
}

/**
 * Resolve requested image size from block setting.
 */
function kursagenten_get_requested_image_size(string $image_resolution): string {
    $allowed_sizes = ['thumbnail', 'medium', 'large', 'full'];
    if ($image_resolution === 'auto') {
        return 'large';
    }
    return in_array($image_resolution, $allowed_sizes, true) ? $image_resolution : 'large';
}

/**
 * Get a course featured image for category fallback.
 *
 * @return array{url:string,attachment_id:int}
 */
function kursagenten_get_course_image_for_category(int $term_id, string $image_resolution = 'auto'): array {
    $posts = get_posts([
        'post_type' => 'ka_course',
        'post_status' => 'publish',
        'tax_query' => [[
            'taxonomy' => 'ka_coursecategory',
            'field' => 'term_id',
            'terms' => $term_id,
        ]],
        'posts_per_page' => 1,
        'meta_query' => [[
            'key' => '_thumbnail_id',
            'compare' => 'EXISTS',
        ]],
        'no_found_rows' => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    ]);

    if (empty($posts)) {
        return ['url' => '', 'attachment_id' => 0];
    }

    $thumbnail_id = (int) get_post_thumbnail_id((int) $posts[0]->ID);
    if ($thumbnail_id <= 0) {
        return ['url' => '', 'attachment_id' => 0];
    }

    $size = kursagenten_get_requested_image_size($image_resolution);
    $thumbnail_url = wp_get_attachment_image_url($thumbnail_id, $size);
    if (!is_string($thumbnail_url) || $thumbnail_url === '') {
        $thumbnail_url = wp_get_attachment_url($thumbnail_id) ?: '';
    }

    return [
        'url' => $thumbnail_url ? esc_url($thumbnail_url) : '',
        'attachment_id' => $thumbnail_id,
    ];
}

/**
 * @param array<string,mixed> $settings
 * @return array<int,WP_Term>
 */
function kursagenten_get_taxonomy_terms(array $settings): array {
    $taxonomy = kursagenten_taxonomy_from_source((string) $settings['sourceType']);

    $args = [
        'taxonomy' => $taxonomy,
        'hide_empty' => true,
        'orderby' => 'name',
        'order' => 'ASC',
        'meta_query' => [
            'relation' => 'OR',
            [
                'key' => 'hide_in_list',
                'value' => 'Vis',
            ],
            [
                'key' => 'hide_in_list',
                'compare' => 'NOT EXISTS',
            ],
        ],
    ];

    if ($taxonomy === 'ka_course_location') {
        $location_term_ids = [];
        $region = sanitize_text_field((string) $settings['region']);

        if ($region !== '') {
            $region_terms = get_terms([
                'taxonomy' => 'ka_course_location',
                'hide_empty' => false,
                'meta_query' => [
                    [
                        'key' => 'location_region',
                        'value' => $region,
                        'compare' => '=',
                    ],
                ],
                'fields' => 'ids',
            ]);
            if (!is_wp_error($region_terms) && !empty($region_terms)) {
                $location_term_ids = array_merge($location_term_ids, array_map('intval', $region_terms));
            }
        }

        if (!empty($settings['locationInclude']) && is_array($settings['locationInclude'])) {
            foreach ($settings['locationInclude'] as $location_slug) {
                $location_slug = sanitize_title((string) $location_slug);
                if ($location_slug === '') {
                    continue;
                }
                $location_term = get_term_by('slug', $location_slug, 'ka_course_location');
                if ($location_term instanceof WP_Term) {
                    $location_term_ids[] = (int) $location_term->term_id;
                }
            }
        }

        if (!empty($location_term_ids)) {
            $args['include'] = array_values(array_unique($location_term_ids));
        } elseif ($region !== '') {
            return [];
        }
    }

    $terms = get_terms($args);
    if (is_wp_error($terms) || empty($terms)) {
        return [];
    }

    if ($taxonomy === 'ka_coursecategory') {
        $filter_mode = (string) $settings['filterMode'];
        if ($filter_mode === 'hovedkategorier') {
            $terms = array_filter($terms, static function ($term): bool {
                return (int) $term->parent === 0;
            });
        } elseif ($filter_mode === 'subkategorier') {
            $terms = array_filter($terms, static function ($term): bool {
                return (int) $term->parent !== 0;
            });
        } elseif ($filter_mode === 'standard') {
            $parent_slugs = [];
            if (!empty($settings['categoryParentSlugs']) && is_array($settings['categoryParentSlugs'])) {
                $parent_slugs = array_values(array_filter(array_map('sanitize_title', $settings['categoryParentSlugs'])));
            } elseif (!empty($settings['categoryParentSlug'])) {
                $parent_slugs = [sanitize_title((string) $settings['categoryParentSlug'])];
            }

            if (!empty($parent_slugs)) {
                $parent_ids = [];
                foreach ($parent_slugs as $parent_slug) {
                    $parent_term = get_term_by('slug', $parent_slug, 'ka_coursecategory');
                    if ($parent_term instanceof WP_Term) {
                        $parent_ids[] = (int) $parent_term->term_id;
                    }
                }
                $parent_ids = array_values(array_unique($parent_ids));
                if (!empty($parent_ids)) {
                    $terms = array_filter($terms, static function ($term) use ($parent_ids): bool {
                        return in_array((int) $term->parent, $parent_ids, true);
                    });
                } else {
                    $terms = [];
                }
            }
        }
    }

    if ($taxonomy === 'ka_coursecategory' && !empty($settings['categoryLocationFilter'])) {
        $location_filter = sanitize_title((string) $settings['categoryLocationFilter']);
        $exclude_location = false;

        if (strpos($location_filter, 'ikke-') === 0) {
            $exclude_location = true;
            $location_filter = sanitize_title(substr($location_filter, strlen('ikke-')));
        }

        if ($location_filter !== '') {
            $location_term = get_term_by('slug', $location_filter, 'ka_course_location');
            if ($location_term instanceof WP_Term) {
                $location_term_id = (int) $location_term->term_id;
                $terms = array_filter($terms, static function ($term) use ($location_term_id, $exclude_location): bool {
                    return kursagenten_term_has_published_courses_for_location((int) $term->term_id, $location_term_id, $exclude_location);
                });
            } elseif (!$exclude_location) {
                $terms = [];
            }
        }
    }

    if ($taxonomy === 'ka_instructors' && !empty($settings['instructorExclude']) && is_array($settings['instructorExclude'])) {
        $excluded_slugs = array_values(array_filter(array_map('sanitize_title', $settings['instructorExclude'])));
        if (!empty($excluded_slugs)) {
            $terms = array_filter($terms, static function ($term) use ($excluded_slugs): bool {
                return !in_array(sanitize_title((string) $term->slug), $excluded_slugs, true);
            });
        }
    }

    return array_values($terms);
}

/**
 * Check if a category term has published courses for a location filter.
 */
function kursagenten_term_has_published_courses_for_location(int $category_term_id, int $location_term_id, bool $exclude_location): bool {
    $location_query = [
        'taxonomy' => 'ka_course_location',
        'field' => 'term_id',
        'terms' => [$location_term_id],
        'operator' => $exclude_location ? 'NOT IN' : 'IN',
    ];

    $query = new WP_Query([
        'post_type' => 'ka_course',
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'tax_query' => [
            'relation' => 'AND',
            [
                'taxonomy' => 'ka_coursecategory',
                'field' => 'term_id',
                'terms' => [$category_term_id],
            ],
            $location_query,
        ],
        'no_found_rows' => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    ]);

    return $query->have_posts();
}

/**
 * Resolve image url for term by source type.
 */
function kursagenten_get_term_image_url(
    WP_Term $term,
    string $source_type,
    string $category_image_source = 'main',
    string $instructor_image_source = 'standard',
    string $image_resolution = 'auto',
    int &$attachment_id = 0
): string {
    $attachment_id = 0;

    if ($source_type === 'location') {
        $url = (string) get_term_meta($term->term_id, 'image_course_location', true);
        if ($url !== '') {
            $attachment_id = attachment_url_to_postid($url);
            return esc_url($url);
        }
        return kursagenten_get_placeholder_image_url($source_type);
    }

    if ($source_type === 'instructor') {
        $profile_image = (string) get_term_meta($term->term_id, 'image_instructor_ka', true);
        $alternative_image = (string) get_term_meta($term->term_id, 'image_instructor', true);

        if ($instructor_image_source === 'alternative') {
            if ($alternative_image !== '') {
                $attachment_id = attachment_url_to_postid($alternative_image);
                return esc_url($alternative_image);
            }
            if ($profile_image !== '') {
                $attachment_id = attachment_url_to_postid($profile_image);
                return esc_url($profile_image);
            }
        } else {
            if ($profile_image !== '') {
                $attachment_id = attachment_url_to_postid($profile_image);
                return esc_url($profile_image);
            }
            if ($alternative_image !== '') {
                $attachment_id = attachment_url_to_postid($alternative_image);
                return esc_url($alternative_image);
            }
        }
        return kursagenten_get_placeholder_image_url($source_type);
    }

    // Category fallback order:
    // - If "Hovedbilde" is selected: Hovedbilde -> Kursbilde -> Plassholder
    // - If "Profilbilde (ikon-bilde)" is selected: Profilbilde (ikon-bilde) -> Hovedbilde -> Kursbilde -> Plassholder
    $main_image = (string) get_term_meta($term->term_id, 'image_coursecategory', true);
    $icon_image = (string) get_term_meta($term->term_id, 'icon_coursecategory', true);

    if ($category_image_source === 'icon') {
        if ($icon_image !== '') {
            $attachment_id = attachment_url_to_postid($icon_image);
            return esc_url($icon_image);
        }
        if ($main_image !== '') {
            $attachment_id = attachment_url_to_postid($main_image);
            return esc_url($main_image);
        }
    } else {
        // "Hovedbilde" selected: ignore icon image completely unless main image is missing.
        if ($main_image !== '') {
            $attachment_id = attachment_url_to_postid($main_image);
            return esc_url($main_image);
        }
    }

    $course_image = kursagenten_get_course_image_for_category((int) $term->term_id, $image_resolution);
    if ($course_image['url'] !== '') {
        $attachment_id = (int) $course_image['attachment_id'];
        return $course_image['url'];
    }

    return kursagenten_get_placeholder_image_url($source_type);
}

/**
 * Build image markup with responsive sources when possible.
 */
function kursagenten_get_term_image_markup(
    string $image_url,
    string $alt_text,
    string $image_resolution = 'auto',
    int $attachment_id = 0
): string {
    if ($image_url === '') {
        return '';
    }

    $allowed_sizes = ['auto', 'thumbnail', 'medium', 'large', 'full'];
    if (!in_array($image_resolution, $allowed_sizes, true)) {
        $image_resolution = 'auto';
    }

    $requested_size = kursagenten_get_requested_image_size($image_resolution);
    if ($attachment_id <= 0) {
        $attachment_id = attachment_url_to_postid($image_url);
    }

    if ($attachment_id > 0) {
        $attr = [
            'class' => 'k-image',
            'alt' => $alt_text,
            'loading' => 'lazy',
            'decoding' => 'async',
        ];

        if ($image_resolution === 'auto') {
            $attr['sizes'] = '(max-width: 600px) 92vw, (max-width: 1024px) 46vw, 32vw';
        }

        $html = wp_get_attachment_image($attachment_id, $requested_size, false, $attr);
        if (is_string($html) && $html !== '') {
            return $html;
        }
    }

    return sprintf(
        '<img class="k-image" src="%s" alt="%s" loading="lazy" decoding="async" />',
        esc_url($image_url),
        esc_attr($alt_text)
    );
}

/**
 * Get display title for each term.
 */
function kursagenten_get_term_title(WP_Term $term, string $source_type, string $name_mode): string {
    if ($source_type !== 'instructor') {
        return (string) $term->name;
    }

    if ($name_mode === 'fornavn') {
        $firstname = (string) get_term_meta($term->term_id, 'instructor_firstname', true);
        if ($firstname !== '') {
            return $firstname;
        }
    }

    if ($name_mode === 'etternavn') {
        $lastname = (string) get_term_meta($term->term_id, 'instructor_lastname', true);
        if ($lastname !== '') {
            return $lastname;
        }
    }

    return (string) $term->name;
}

/**
 * Enqueue all style files for taxonomy grid block.
 */
function kursagenten_enqueue_taxonomy_grid_styles(): void {
    $handles = [
        'kursagenten-taxonomy-grid-base',
        'kursagenten-taxonomy-grid-stablet',
        'kursagenten-taxonomy-grid-rad',
        'kursagenten-taxonomy-grid-liste',
        'kursagenten-taxonomy-grid-kort',
        'kursagenten-taxonomy-grid-kort-bg',
    ];
    foreach ($handles as $handle) {
        if (wp_style_is($handle, 'registered')) {
            wp_enqueue_style($handle);
        }
    }
}

/**
 * @param array<string,mixed> $attributes
 */
function kursagenten_render_taxonomy_grid_block(array $attributes): string {
    $settings = Kursagenten_Block_Attribute_Mapper::map_taxonomy_grid($attributes);
    $terms = kursagenten_get_taxonomy_terms($settings);

    if (empty($terms)) {
        return '<div class="k-taxonomy-grid-empty">Ingen elementer funnet.</div>';
    }

    $source_type = (string) $settings['sourceType'];
    $instance_id = 'k-taxonomy-grid-' . wp_generate_uuid4();
    $font_clamp = kursagenten_build_font_clamp((int) $settings['fontMin'], (int) $settings['fontMax']);
    $description_font_clamp = kursagenten_build_font_clamp(
        (int) $settings['descriptionFontMin'],
        (int) $settings['descriptionFontMax']
    );
    $fallback_text_color = kursagenten_css_color_value((string) $settings['textColor'], 'inherit');
    $title_color = kursagenten_css_color_value(
        (string) ($settings['titleColor'] ?: $settings['textColor']),
        'inherit'
    );
    $description_color = kursagenten_css_color_value(
        (string) ($settings['descriptionColor'] ?: $settings['textColor']),
        'inherit'
    );
    $overlay_opacity = number_format(((int) $settings['overlayStrength']) / 100, 2, '.', '');
    $style_preset = sanitize_html_class((string) $settings['stylePreset']);
    $shadow = kursagenten_get_shadow_values((string) $settings['shadowPreset']);
    $text_align_desktop = kursagenten_css_keyword((string) $settings['textAlignDesktop'], ['left', 'center', 'right'], 'left');
    $text_align_tablet = kursagenten_css_keyword((string) $settings['textAlignTablet'], ['left', 'center', 'right'], 'left');
    $text_align_mobile = kursagenten_css_keyword((string) $settings['textAlignMobile'], ['left', 'center', 'right'], 'left');
    $vertical_align_desktop = kursagenten_css_keyword((string) $settings['verticalAlignDesktop'], ['top', 'center', 'bottom'], 'top');
    $vertical_align_tablet = kursagenten_css_keyword((string) $settings['verticalAlignTablet'], ['top', 'center', 'bottom'], 'top');
    $vertical_align_mobile = kursagenten_css_keyword((string) $settings['verticalAlignMobile'], ['top', 'center', 'bottom'], 'top');
    $image_radius_top = kursagenten_css_value((string) $settings['imageRadiusTop'], (string) $settings['imageRadius']);
    $image_radius_right = kursagenten_css_value((string) $settings['imageRadiusRight'], (string) $settings['imageRadius']);
    $image_radius_bottom = kursagenten_css_value((string) $settings['imageRadiusBottom'], (string) $settings['imageRadius']);
    $image_radius_left = kursagenten_css_value((string) $settings['imageRadiusLeft'], (string) $settings['imageRadius']);
    $image_radius_combined = $image_radius_top . ' ' . $image_radius_right . ' ' . $image_radius_bottom . ' ' . $image_radius_left;
    $card_padding_desktop = kursagenten_css_value((string) $settings['cardPaddingDesktop'], '16px');
    $card_padding_tablet = kursagenten_css_value((string) $settings['cardPaddingTablet'], '14px');
    $card_padding_mobile = kursagenten_css_value((string) $settings['cardPaddingMobile'], '12px');
    $card_padding_inline_desktop = kursagenten_padding_inline_values((string) $settings['cardPaddingDesktop'], '16px');
    $card_padding_inline_tablet = kursagenten_padding_inline_values((string) $settings['cardPaddingTablet'], '14px');
    $card_padding_inline_mobile = kursagenten_padding_inline_values((string) $settings['cardPaddingMobile'], '12px');
    $title_tag = strtolower((string) $settings['titleTag']);
    $allowed_tags = ['h2', 'h3', 'h4', 'h5', 'h6', 'p', 'div', 'span'];
    if (!in_array($title_tag, $allowed_tags, true)) {
        $title_tag = 'h3';
    }

    $wrapper_style = sprintf(
        '--k-cols-desktop:%d;--k-cols-tablet:%d;--k-cols-mobile:%d;--k-row-gap-desktop:%s;--k-row-gap-tablet:%s;--k-row-gap-mobile:%s;--k-wrapper-padding-desktop:%s;--k-wrapper-padding-tablet:%s;--k-wrapper-padding-mobile:%s;--k-card-padding-desktop:%s;--k-card-padding-tablet:%s;--k-card-padding-mobile:%s;--k-card-padding-inline-desktop-left:%s;--k-card-padding-inline-desktop-right:%s;--k-card-padding-inline-tablet-left:%s;--k-card-padding-inline-tablet-right:%s;--k-card-padding-inline-mobile-left:%s;--k-card-padding-inline-mobile-right:%s;--k-card-margin-desktop:%s;--k-card-margin-tablet:%s;--k-card-margin-mobile:%s;--k-image-size:%s;--k-image-aspect:%s;--k-image-radius:%s;--k-image-radius-top:%s;--k-image-radius-right:%s;--k-image-radius-bottom:%s;--k-image-radius-left:%s;--k-image-bw:%s;--k-image-bs:%s;--k-image-bc:%s;--k-image-bw-hover:%s;--k-image-bs-hover:%s;--k-image-bc-hover:%s;--k-image-bg:%s;--k-font-size:%s;--k-text-color:%s;--k-title-color:%s;--k-description-color:%s;--k-description-font-size:%s;--k-title-font-weight:%s;--k-description-font-weight:%s;--k-card-bg:%s;--k-card-bg-hover:%s;--k-card-radius:%s;--k-card-bw:%s;--k-card-bs:%s;--k-card-bc:%s;--k-overlay-opacity:%s;--k-shadow-normal:%s;--k-shadow-hover:%s;--k-text-align-desktop:%s;--k-text-align-tablet:%s;--k-text-align-mobile:%s;--k-content-align-desktop:%s;--k-content-align-tablet:%s;--k-content-align-mobile:%s;--k-content-justify-desktop:%s;--k-content-justify-tablet:%s;--k-content-justify-mobile:%s;',
        (int) $settings['columnsDesktop'],
        (int) $settings['columnsTablet'],
        (int) $settings['columnsMobile'],
        kursagenten_css_value((string) $settings['rowGapDesktop'], '24px'),
        kursagenten_css_value((string) $settings['rowGapTablet'], '20px'),
        kursagenten_css_value((string) $settings['rowGapMobile'], '16px'),
        kursagenten_css_value((string) $settings['wrapperPaddingDesktop'], '0px'),
        kursagenten_css_value((string) $settings['wrapperPaddingTablet'], '0px'),
        kursagenten_css_value((string) $settings['wrapperPaddingMobile'], '0px'),
        $card_padding_desktop,
        $card_padding_tablet,
        $card_padding_mobile,
        $card_padding_inline_desktop['left'],
        $card_padding_inline_desktop['right'],
        $card_padding_inline_tablet['left'],
        $card_padding_inline_tablet['right'],
        $card_padding_inline_mobile['left'],
        $card_padding_inline_mobile['right'],
        kursagenten_css_value((string) $settings['cardMarginDesktop'], '0px'),
        kursagenten_css_value((string) $settings['cardMarginTablet'], '0px'),
        kursagenten_css_value((string) $settings['cardMarginMobile'], '0px'),
        kursagenten_css_value((string) $settings['imageSize'], '240px'),
        kursagenten_css_value((string) $settings['imageAspect'], '4/3'),
        $image_radius_combined,
        $image_radius_top,
        $image_radius_right,
        $image_radius_bottom,
        $image_radius_left,
        kursagenten_css_value((string) $settings['imageBorderWidth'], '0px'),
        kursagenten_css_keyword((string) $settings['imageBorderStyle'], ['none', 'solid', 'dashed', 'dotted', 'double'], 'solid'),
        kursagenten_css_color_value((string) $settings['imageBorderColor'], 'transparent'),
        kursagenten_css_value((string) $settings['imageBorderWidthHover'], kursagenten_css_value((string) $settings['imageBorderWidth'], '0px')),
        kursagenten_css_keyword((string) $settings['imageBorderStyleHover'], ['none', 'solid', 'dashed', 'dotted', 'double'], 'solid'),
        kursagenten_css_color_value(
            (string) $settings['imageBorderColorHover'],
            kursagenten_css_color_value((string) $settings['imageBorderColor'], 'transparent')
        ),
        kursagenten_css_color_value((string) $settings['imageBackgroundColor'], 'transparent'),
        $font_clamp,
        $fallback_text_color,
        $title_color,
        $description_color,
        $description_font_clamp,
        (string) $settings['fontWeightTitle'],
        (string) $settings['fontWeightDescription'],
        kursagenten_css_color_value((string) $settings['cardBackgroundColor'], '#ffffff'),
        kursagenten_css_color_value(
            (string) $settings['cardBackgroundColorHover'],
            kursagenten_css_color_value((string) $settings['cardBackgroundColor'], '#ffffff')
        ),
        kursagenten_css_value((string) $settings['cardRadius'], '12px'),
        kursagenten_css_value((string) $settings['cardBorderWidth'], '1px'),
        kursagenten_css_keyword((string) $settings['cardBorderStyle'], ['none', 'solid', 'dashed', 'dotted', 'double'], 'solid'),
        kursagenten_css_color_value((string) $settings['cardBorderColor'], '#d0d7de'),
        $overlay_opacity,
        $shadow['normal'],
        $shadow['hover'],
        $text_align_desktop,
        $text_align_tablet,
        $text_align_mobile,
        kursagenten_map_alignment_to_flex($text_align_desktop),
        kursagenten_map_alignment_to_flex($text_align_tablet),
        kursagenten_map_alignment_to_flex($text_align_mobile),
        kursagenten_map_vertical_alignment_to_flex($vertical_align_desktop),
        kursagenten_map_vertical_alignment_to_flex($vertical_align_tablet),
        kursagenten_map_vertical_alignment_to_flex($vertical_align_mobile)
    );

    $classes = [
        'k-taxonomy-grid',
        'k-preset-' . $style_preset,
        'k-source-' . sanitize_html_class($source_type),
    ];

    if (!empty($settings['showDescription'])) {
        $classes[] = 'k-show-description';
    }
    if (empty($settings['showImage'])) {
        $classes[] = 'k-hide-image';
    }
    if ((string) $settings['backgroundMode'] === 'taxonomyImage') {
        $classes[] = 'k-background-taxonomy-image';
    }
    if (!empty($settings['useCardDesign'])) {
        $classes[] = 'k-use-card';
    } else {
        $classes[] = 'k-no-card';
    }
    if ((string) $settings['shadowPreset'] !== 'none') {
        $classes[] = 'k-shadow-enabled';
    }
    if ((string) $settings['shadowPreset'] === 'outline') {
        $classes[] = 'k-frame-enabled';
    }

    $output = '<section id="' . esc_attr($instance_id) . '" class="' . esc_attr(implode(' ', $classes)) . '" style="' . esc_attr($wrapper_style) . '">';
    $output .= '<div class="k-wrapper">';
    $link_rel = is_admin() ? ' rel="nofollow"' : '';

    foreach ($terms as $term) {
        $title = kursagenten_get_term_title($term, $source_type, (string) $settings['instructorNameMode']);
        $term_link = get_term_link($term);
        if (is_wp_error($term_link)) {
            continue;
        }

        $image_attachment_id = 0;
        $image_url = kursagenten_get_term_image_url(
            $term,
            $source_type,
            (string) $settings['categoryImageSource'],
            (string) ($settings['instructorImageSource'] ?? 'standard'),
            (string) ($settings['imageResolution'] ?? 'auto'),
            $image_attachment_id
        );
        $image_markup = kursagenten_get_term_image_markup(
            $image_url,
            $title,
            (string) ($settings['imageResolution'] ?? 'auto'),
            (int) ($image_attachment_id ?? 0)
        );
        $description_word_limit = max(0, (int) $settings['descriptionWordLimit']);
        $extended_description_word_limit = max(0, (int) $settings['descriptionWordLimitExtended']);
        $raw_description = (string) $term->description;
        $clean_description = wp_strip_all_tags($raw_description);
        $short_description = $description_word_limit > 0
            ? wp_trim_words($clean_description, $description_word_limit)
            : $clean_description;
        $rich_description_meta = (string) get_term_meta($term->term_id, 'rich_description', true);
        $long_description_source = trim($rich_description_meta) !== '' ? $rich_description_meta : $raw_description;
        $long_description = kursagenten_trim_html_words_preserve_links($long_description_source, $extended_description_word_limit);
        $is_rad_detalj = $style_preset === 'rad-detalj';
        $has_instructor_contact_links = $source_type === 'instructor' && (!empty($settings['showInstructorPhone']) || !empty($settings['showInstructorEmail']));
        $instructor_email = '';
        $instructor_phone = '';
        if ($source_type === 'instructor') {
            $instructor_email = sanitize_email((string) get_term_meta($term->term_id, 'instructor_email', true));
            $instructor_phone = trim((string) get_term_meta($term->term_id, 'instructor_phone', true));
            $instructor_phone = preg_replace('/[^\d+\s().-]/', '', $instructor_phone) ?: '';
        }

        $card_style = '';
        if ((string) $settings['backgroundMode'] === 'taxonomyImage' && $image_url !== '') {
            $card_style = 'background-image:url(' . esc_url($image_url) . ');';
        }

        $output .= '<article class="k-card" style="' . esc_attr($card_style) . '">';

        if ($is_rad_detalj) {
            $output .= '<div class="k-card-link">';
            $output .= '<span class="k-card-overlay"></span>';

            if (!empty($settings['showImage']) && $image_url !== '' && (string) $settings['backgroundMode'] !== 'taxonomyImage') {
                $output .= '<a class="k-image-link" href="' . esc_url((string) $term_link) . '" aria-label="' . esc_attr($title) . '"' . $link_rel . '>';
                $output .= '<span class="k-image-wrap">' . $image_markup . '</span>';
                $output .= '</a>';
            }

            $output .= '<div class="k-content">';
            $output .= '<' . $title_tag . ' class="k-title"><a class="k-title-link" href="' . esc_url((string) $term_link) . '"' . $link_rel . '>' . esc_html($title) . '</a></' . $title_tag . '>';

            if (!empty($settings['showDescription']) && trim(wp_strip_all_tags($long_description_source)) !== '') {
                $output .= '<div class="k-description k-description-long">' . $long_description . '</div>';
            }
            if ($source_type === 'instructor' && (!empty($settings['showInstructorPhone']) || !empty($settings['showInstructorEmail']))) {
                $output .= '<div class="k-instructor-contact">';
                if (!empty($settings['showInstructorPhone']) && $instructor_phone !== '') {
                    $phone_href = preg_replace('/[^\d+]/', '', $instructor_phone) ?: $instructor_phone;
                    $output .= '<a class="k-contact-item k-contact-phone" href="tel:' . esc_attr($phone_href) . '"' . $link_rel . '>';
                    $output .= '<span class="k-contact-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M6.62 10.79a15.054 15.054 0 0 0 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1C10.3 21 3 13.7 3 4c0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.24.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2Z" fill="currentColor"/></svg></span>';
                    $output .= '<span class="k-contact-text">' . esc_html($instructor_phone) . '</span>';
                    $output .= '</a>';
                }
                if (!empty($settings['showInstructorEmail']) && $instructor_email !== '') {
                    $output .= '<a class="k-contact-item k-contact-email" href="mailto:' . esc_attr($instructor_email) . '"' . $link_rel . '>';
                    $output .= '<span class="k-contact-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2Zm0 4.25-8 5-8-5V6l8 5 8-5v2.25Z" fill="currentColor"/></svg></span>';
                    $output .= '<span class="k-contact-text">' . esc_html($instructor_email) . '</span>';
                    $output .= '</a>';
                }
                $output .= '</div>';
            }
            if ($source_type === 'location' && !empty($settings['locationShowInfo'])) {
                $specific_locations = get_term_meta((int) $term->term_id, 'specific_locations', true);
                if (is_array($specific_locations) && !empty($specific_locations)) {
                    $output .= '<div class="k-location-info"><ul class="k-location-info-list">';
                    foreach ($specific_locations as $location_entry) {
                        $description = '';
                        if (is_array($location_entry) && isset($location_entry['description'])) {
                            $description = trim((string) $location_entry['description']);
                        }
                        if ($description === '') {
                            continue;
                        }
                        $output .= '<li class="k-location-info-item">' . esc_html($description) . '</li>';
                    }
                    $output .= '</ul></div>';
                }
            }
            $output .= '</div>';
            $output .= '</div>';
            $output .= '</article>';
            continue;
        }

        if ($has_instructor_contact_links) {
            $output .= '<div class="k-card-link">';
        } else {
            $output .= '<a class="k-card-link" href="' . esc_url((string) $term_link) . '" aria-label="' . esc_attr($title) . '"' . $link_rel . '>';
        }
        $output .= '<span class="k-card-overlay"></span>';

        if (!empty($settings['showImage']) && $image_url !== '' && (string) $settings['backgroundMode'] !== 'taxonomyImage') {
            if ($has_instructor_contact_links) {
                $output .= '<a class="k-image-link" href="' . esc_url((string) $term_link) . '" aria-label="' . esc_attr($title) . '"' . $link_rel . '>';
                $output .= '<span class="k-image-wrap">' . $image_markup . '</span>';
                $output .= '</a>';
            } else {
                $output .= '<span class="k-image-wrap">' . $image_markup . '</span>';
            }
        }

        $output .= '<div class="k-content">';
        if ($has_instructor_contact_links) {
            $output .= '<' . $title_tag . ' class="k-title"><a class="k-title-link" href="' . esc_url((string) $term_link) . '"' . $link_rel . '>' . esc_html($title) . '</a></' . $title_tag . '>';
        } else {
            $output .= '<' . $title_tag . ' class="k-title">' . esc_html($title) . '</' . $title_tag . '>';
        }

        if (!empty($settings['showDescription']) && $short_description !== '') {
            $output .= '<div class="k-description">' . esc_html($short_description) . '</div>';
        }
        if ($has_instructor_contact_links) {
            $output .= '<div class="k-instructor-contact">';
            if (!empty($settings['showInstructorPhone']) && $instructor_phone !== '') {
                $phone_href = preg_replace('/[^\d+]/', '', $instructor_phone) ?: $instructor_phone;
                $output .= '<a class="k-contact-item k-contact-phone" href="tel:' . esc_attr($phone_href) . '"' . $link_rel . '>';
                $output .= '<span class="k-contact-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M6.62 10.79a15.054 15.054 0 0 0 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1C10.3 21 3 13.7 3 4c0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.24.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2Z" fill="currentColor"/></svg></span>';
                $output .= '<span class="k-contact-text">' . esc_html($instructor_phone) . '</span>';
                $output .= '</a>';
            }
            if (!empty($settings['showInstructorEmail']) && $instructor_email !== '') {
                $output .= '<a class="k-contact-item k-contact-email" href="mailto:' . esc_attr($instructor_email) . '"' . $link_rel . '>';
                $output .= '<span class="k-contact-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2Zm0 4.25-8 5-8-5V6l8 5 8-5v2.25Z" fill="currentColor"/></svg></span>';
                $output .= '<span class="k-contact-text">' . esc_html($instructor_email) . '</span>';
                $output .= '</a>';
            }
            $output .= '</div>';
        }
        if ($source_type === 'location' && !empty($settings['locationShowInfo'])) {
            $specific_locations = get_term_meta((int) $term->term_id, 'specific_locations', true);
            if (is_array($specific_locations) && !empty($specific_locations)) {
                $output .= '<div class="k-location-info"><ul class="k-location-info-list">';
                foreach ($specific_locations as $location_entry) {
                    $description = '';
                    if (is_array($location_entry) && isset($location_entry['description'])) {
                        $description = trim((string) $location_entry['description']);
                    }
                    if ($description === '') {
                        continue;
                    }
                    $output .= '<li class="k-location-info-item">' . esc_html($description) . '</li>';
                }
                $output .= '</ul></div>';
            }
        }
        $output .= '</div>';
        $output .= $has_instructor_contact_links ? '</div>' : '</a>';
        $output .= '</article>';
    }

    $output .= '</div></section>';

    return $output;
}

<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

require_once KURSAG_PLUGIN_DIR . '/public/blocks/shared/shortcode-attribute-mapper.php';
require_once KURSAG_PLUGIN_DIR . '/public/templates/includes/queries.php';
require_once KURSAG_PLUGIN_DIR . '/public/templates/includes/template-functions.php';

/**
 * Builds a safe CSS value with fallback.
 */
function kursagenten_single_css_value(string $value, string $fallback = ''): string {
    $value = trim($value);
    if ($value === '') {
        return $fallback;
    }

    $sanitized = sanitize_text_field($value);
    if ($sanitized === '') {
        return $fallback;
    }

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
function kursagenten_single_css_color_value(string $value, string $fallback = ''): string {
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
 * @param array<string,mixed> $attributes
 */
function kursagenten_single_build_style_vars(array $attributes, array $extra = []): string {
    $vars = [
        '--ka-text-color' => kursagenten_single_css_color_value((string) ($attributes['textColor'] ?? ''), ''),
        '--ka-heading-color' => kursagenten_single_css_color_value((string) ($attributes['headingColor'] ?? ''), ''),
        '--ka-link-color' => kursagenten_single_css_color_value((string) ($attributes['linkColor'] ?? ''), ''),
        '--ka-icon-color' => kursagenten_single_css_color_value((string) ($attributes['iconColor'] ?? ''), ''),
        '--ka-font-family' => kursagenten_single_css_value((string) ($attributes['fontFamily'] ?? ''), ''),
        '--ka-heading-font-family' => kursagenten_single_css_value((string) ($attributes['headingFontFamily'] ?? ''), ''),
        '--ka-font-size' => kursagenten_single_css_value((string) ($attributes['fontSize'] ?? ''), ''),
        '--ka-heading-size' => kursagenten_single_css_value((string) ($attributes['headingSize'] ?? ''), ''),
    ];

    foreach ($extra as $key => $value) {
        $vars[(string) $key] = (string) $value;
    }

    $parts = [];
    foreach ($vars as $key => $value) {
        $value = trim((string) $value);
        if ($value === '') {
            continue;
        }
        $parts[] = $key . ':' . $value;
    }

    return implode(';', $parts);
}

/**
 * @return int[]
 */
function kursagenten_single_get_related_coursedate_ids(int $post_id): array {
    $main_course_id = (int) get_post_meta($post_id, 'main_course_id', true);
    $is_parent_course = (int) get_post_meta($post_id, 'ka_course_isParentCourse', true) === 1;

    // Same logic as the default single template: parent courses fetch coursedates for all locations.
    if ($is_parent_course) {
        $query = get_posts([
            'post_type' => 'ka_coursedate',
            'post_status' => 'publish',
            'numberposts' => -1,
            'fields' => 'ids',
            'meta_query' => [
                [
                    'key' => 'main_course_id',
                    'value' => (string) $main_course_id,
                    'compare' => '=',
                ],
            ],
        ]);
        return array_values(array_map('intval', is_array($query) ? $query : []));
    }

    // Location pages: match by main_course_id + course_location term.
    $term_ids = wp_get_post_terms($post_id, 'ka_course_location', ['fields' => 'ids']);
    $location_term_id = !is_wp_error($term_ids) && !empty($term_ids) ? (int) $term_ids[0] : 0;

    $meta_query = [
        'relation' => 'AND',
        [
            'key' => 'main_course_id',
            'value' => (string) $main_course_id,
            'compare' => '=',
        ],
    ];

    if ($location_term_id > 0) {
        $meta_query[] = [
            'key' => 'course_location_term_id',
            'value' => (string) $location_term_id,
            'compare' => '=',
        ];
    }

    $query = get_posts([
        'post_type' => 'ka_coursedate',
        'post_status' => 'publish',
        'numberposts' => -1,
        'fields' => 'ids',
        'meta_query' => $meta_query,
    ]);

    return array_values(array_map('intval', is_array($query) ? $query : []));
}

function kursagenten_single_block_message(string $message): string {
    return '<div class="ka-single-block ka-single-block-message">' . esc_html($message) . '</div>';
}

/**
 * @param array<string,mixed> $attributes
 */
function kursagenten_render_single_title_block(array $attributes): string {
    if (!is_singular('ka_course')) {
        return kursagenten_single_block_message('Denne blokken fungerer kun på enkeltkurs.');
    }

    $post_id = (int) get_the_ID();
    $main_title = (string) get_post_meta($post_id, 'main_course_title', true);
    if ($main_title === '') {
        $main_title = (string) get_the_title($post_id);
    }
    $location_title = (string) get_post_meta($post_id, 'sub_course_location', true);
    $is_parent_course = (int) get_post_meta($post_id, 'ka_course_isParentCourse', true) === 1;

    $show_location = (string) ($attributes['showLocation'] ?? 'auto');
    $should_show_location = $show_location === 'yes' || ($show_location === 'auto' && !$is_parent_course && trim($location_title) !== '');

    $tag = strtolower((string) ($attributes['headingTag'] ?? 'h1'));
    if (!in_array($tag, ['h1', 'h2', 'h3'], true)) {
        $tag = 'h1';
    }

    $layout = (string) ($attributes['layout'] ?? 'stacked');
    if (!in_array($layout, ['stacked', 'inline'], true)) {
        $layout = 'stacked';
    }

    $extra_vars = [
        '--ka-location-color' => kursagenten_single_css_color_value((string) ($attributes['locationColor'] ?? ''), ''),
        '--ka-location-size' => kursagenten_single_css_value((string) ($attributes['locationSize'] ?? ''), ''),
    ];
    $style = kursagenten_single_build_style_vars($attributes, $extra_vars);
    $classes = 'ka-single-block ka-single-title ka-layout-' . sanitize_html_class($layout);

    $html = '<' . $tag . ' class="' . esc_attr($classes) . '"' . ($style !== '' ? ' style="' . esc_attr($style) . '"' : '') . '>';
    $html .= esc_html($main_title);

    if ($should_show_location) {
        if ($layout === 'inline') {
            $html .= ' <span class="ka-single-location">- <span class="notranslate">' . esc_html($location_title) . '</span></span>';
        } else {
            $html .= '<span class="ka-single-location" style="margin-top:.2em;display:block;font-size:0.75em;font-weight:500;">- <span class="notranslate">' . esc_html($location_title) . '</span></span>';
        }
    }

    $html .= '</' . $tag . '>';
    return $html;
}

/**
 * @param array<string,mixed> $attributes
 */
function kursagenten_render_single_course_link_block(array $attributes): string {
    if (!is_singular('ka_course')) {
        return kursagenten_single_block_message('Denne blokken fungerer kun på enkeltkurs.');
    }

    $label = trim((string) ($attributes['label'] ?? ''));
    if ($label === '') {
        $label = 'Alle kurs';
    }

    $url = '';
    if (class_exists('Designmaler') && method_exists('Designmaler', 'get_system_page_url')) {
        $url = (string) Designmaler::get_system_page_url('kurs', true);
    }

    if ($url === '') {
        return '';
    }

    $show_icon = !empty($attributes['showIcon']);
    $style = kursagenten_single_build_style_vars($attributes);

    $html = '<div class="ka-single-block ka-single-course-link"' . ($style !== '' ? ' style="' . esc_attr($style) . '"' : '') . '>';
    $html .= '<a href="' . esc_url($url) . '" class="ka-course-page-link">';
    if ($show_icon) {
        $html .= '<i class="ka-icon icon-vertical-bars"></i> ';
    }
    $html .= esc_html($label) . '</a>';
    $html .= '</div>';
    return $html;
}

/**
 * @param array<string,mixed> $attributes
 */
function kursagenten_render_single_signup_button_block(array $attributes): string {
    if (!is_singular('ka_course')) {
        return kursagenten_single_block_message('Denne blokken fungerer kun på enkeltkurs.');
    }

    $post_id = (int) get_the_ID();
    $related = kursagenten_single_get_related_coursedate_ids($post_id);
    $selected = get_selected_coursedate_data($related);
    $signup_url = !empty($selected['signup_url']) ? (string) $selected['signup_url'] : '';

    if ($signup_url === '') {
        return '';
    }

    $button_text = !empty($selected['button_text']) ? (string) $selected['button_text'] : '';
    $fallback_text = trim((string) ($attributes['fallbackText'] ?? ''));
    if ($button_text === '') {
        $button_text = $fallback_text !== '' ? $fallback_text : 'Påmelding';
    }

    $variant = (string) ($attributes['styleVariant'] ?? 'primary');
    if (!in_array($variant, ['primary', 'secondary', 'link'], true)) {
        $variant = 'primary';
    }

    $classes = [
        'ka-single-block',
        'ka-single-signup-button',
        'ka-variant-' . sanitize_html_class($variant),
    ];
    if (!empty($attributes['fullWidth'])) {
        $classes[] = 'ka-full-width';
    }
    $button_style_source = (string) ($attributes['buttonStyleSource'] ?? 'theme');
    $use_override = $button_style_source === 'override';
    if ($use_override) {
        $classes[] = 'ka-btn-override';
    }

    $extra = [];
    if ($use_override) {
        $extra['--ka-button-bg'] = kursagenten_single_css_color_value((string) ($attributes['buttonBg'] ?? ''), '');
        $extra['--ka-button-color'] = kursagenten_single_css_color_value((string) ($attributes['buttonColor'] ?? ''), '');
        $extra['--ka-button-radius'] = kursagenten_single_css_value((string) ($attributes['buttonRadius'] ?? ''), '');
        $extra['--ka-button-padding'] = kursagenten_single_css_value((string) ($attributes['buttonPadding'] ?? ''), '');
    }

    $style = kursagenten_single_build_style_vars($attributes, $extra);

    $html = '<div class="' . esc_attr(implode(' ', $classes)) . '"' . ($style !== '' ? ' style="' . esc_attr($style) . '"' : '') . '>';
    $html .= '<a href="#" class="button pameldingskjema clickelement" data-url="' . esc_attr($signup_url) . '">' . esc_html($button_text) . '</a>';
    $html .= '</div>';
    return $html;
}

/**
 * @param array<string,mixed> $attributes
 */
function kursagenten_render_single_schedule_list_block(array $attributes): string {
    if (!is_singular('ka_course')) {
        return kursagenten_single_block_message('Denne blokken fungerer kun på enkeltkurs.');
    }

    $post_id = (int) get_the_ID();
    $related = kursagenten_single_get_related_coursedate_ids($post_id);
    $coursedates = get_all_sorted_coursedates($related);

    if (empty($coursedates)) {
        return '';
    }

    $heading_tag = strtolower((string) ($attributes['headingTag'] ?? 'h3'));
    if (!in_array($heading_tag, ['h3', 'h4', 'none'], true)) {
        $heading_tag = 'h3';
    }
    $show_location_links = !empty($attributes['showLocationLinks']);

    $style = kursagenten_single_build_style_vars($attributes);
    $html = '<div class="ka-single-block ka-single-schedule-list"' . ($style !== '' ? ' style="' . esc_attr($style) . '"' : '') . '>';

    if ($heading_tag !== 'none') {
        $html .= '<' . $heading_tag . ' class="ka-coursedate-heading">Kurstider og steder</' . $heading_tag . '>';
    }

    $html .= '<div class="ka-coursedate-list">';
    foreach ($coursedates as $item) {
        $is_full = !empty($item['course_isFull']);
        $signup_url = (string) ($item['signup_url'] ?? '');
        $title = (string) ($item['title'] ?? '');
        $date_from = (string) ($item['first_date'] ?? '');
        $date_to = (string) ($item['last_date'] ?? '');
        $time = (string) ($item['time'] ?? '');
        $location = (string) ($item['location'] ?? '');
        $location_freetext = (string) ($item['course_location_freetext'] ?? '');

        $html .= '<div class="ka-coursedate-item' . ($is_full ? ' is-full' : '') . '">';
        $html .= '<div class="ka-coursedate-item-inner">';
        $html .= '<div class="ka-coursedate-title">' . esc_html($title) . '</div>';

        $html .= '<div class="ka-coursedate-meta">';
        if ($date_from !== '') {
            $html .= '<span class="ka-coursedate-date">' . esc_html($date_from);
            if ($date_to !== '' && $date_to !== $date_from) {
                $html .= ' - ' . esc_html($date_to);
            }
            $html .= '</span>';
        }
        if ($time !== '') {
            $html .= '<span class="ka-coursedate-time">' . esc_html($time) . '</span>';
        }
        $location_text = trim($location_freetext) !== '' ? $location_freetext : $location;
        if ($location_text !== '') {
            $html .= '<span class="ka-coursedate-location">';
            if ($show_location_links) {
                $html .= wp_kses_post(display_course_locations((int) ($item['id'] ?? 0)));
            } else {
                $html .= esc_html($location_text);
            }
            $html .= '</span>';
        }
        $html .= '</div>';

        if ($signup_url !== '') {
            $link_label = $is_full ? 'Fullt' : 'Påmelding';
            $html .= '<div class="ka-coursedate-cta">';
            $html .= '<a href="#" class="ka-coursedate-signup-link clickelement" data-url="' . esc_attr($signup_url) . '">' . esc_html($link_label) . '</a>';
            $html .= '</div>';
        }

        $html .= '</div></div>';
    }
    $html .= '</div></div>';
    return $html;
}

/**
 * @param array<string,mixed> $attributes
 */
function kursagenten_render_single_next_course_info_block(array $attributes): string {
    if (!is_singular('ka_course')) {
        return kursagenten_single_block_message('Denne blokken fungerer kun på enkeltkurs.');
    }

    $post_id = (int) get_the_ID();
    $related = kursagenten_single_get_related_coursedate_ids($post_id);
    $selected = get_selected_coursedate_data($related);

    if (empty($selected) || (!empty($selected['coursedatemissing']) && empty($selected['id']))) {
        return '';
    }

    $show_heading = !isset($attributes['showHeading']) || !empty($attributes['showHeading']);
    $show_icons = !empty($attributes['showIcons']);
    $show_signup_link = !isset($attributes['showSignupLink']) || !empty($attributes['showSignupLink']);
    $show_price = !empty($attributes['showPrice']);
    $show_duration = !empty($attributes['showDuration']);
    $show_language = !empty($attributes['showLanguage']);

    $is_full = !empty($selected['is_full']);
    $signup_url = (string) ($selected['signup_url'] ?? '');
    $first_date = (string) ($selected['first_date'] ?? '');
    $last_date = (string) ($selected['last_date'] ?? '');
    $time = (string) ($selected['time'] ?? '');
    $price = (string) ($selected['price'] ?? '');
    $after_price = (string) ($selected['after_price'] ?? '');
    $duration = (string) ($selected['duration'] ?? '');
    $language = (string) ($selected['language'] ?? '');
    $location = (string) ($selected['location'] ?? '');
    $location_freetext = (string) ($selected['location_freetext'] ?? '');

    $heading = $is_full ? 'Neste kurs (fullt)' : 'Neste kurs';
    $style = kursagenten_single_build_style_vars($attributes);

    $html = '<div class="ka-single-block ka-single-next-course-info"' . ($style !== '' ? ' style="' . esc_attr($style) . '"' : '') . '>';
    if ($show_heading) {
        $html .= '<h3 class="ka-next-course-heading">' . esc_html($heading) . '</h3>';
    }

    $html .= '<ul class="ka-next-course-list">';
    $date_text = $first_date !== '' ? $first_date : '';
    if ($date_text !== '' && $last_date !== '' && $last_date !== $first_date) {
        $date_text .= ' - ' . $last_date;
    }
    if ($date_text !== '') {
        $html .= '<li class="ka-next-course-item ka-next-course-date">';
        if ($show_icons) {
            $html .= '<i class="ka-icon icon-calendar"></i> ';
        }
        $html .= esc_html($date_text) . '</li>';
    }
    if ($time !== '') {
        $html .= '<li class="ka-next-course-item ka-next-course-time">';
        if ($show_icons) {
            $html .= '<i class="ka-icon icon-clock"></i> ';
        }
        $html .= esc_html($time) . '</li>';
    }

    $location_text = trim($location_freetext) !== '' ? $location_freetext : $location;
    if ($location_text !== '') {
        $html .= '<li class="ka-next-course-item ka-next-course-location">';
        if ($show_icons) {
            $html .= '<i class="ka-icon icon-location"></i> ';
        }
        $html .= esc_html($location_text) . '</li>';
    }

    if ($show_duration && $duration !== '') {
        $html .= '<li class="ka-next-course-item ka-next-course-duration">';
        if ($show_icons) {
            $html .= '<i class="ka-icon icon-hourglass"></i> ';
        }
        $html .= esc_html($duration) . '</li>';
    }

    if ($show_language && $language !== '') {
        $html .= '<li class="ka-next-course-item ka-next-course-language">';
        if ($show_icons) {
            $html .= '<i class="ka-icon icon-chat"></i> ';
        }
        $html .= esc_html($language) . '</li>';
    }

    if ($show_price && $price !== '') {
        $price_text = $price;
        if ($after_price !== '') {
            $price_text .= ' ' . $after_price;
        }
        $html .= '<li class="ka-next-course-item ka-next-course-price">';
        if ($show_icons) {
            $html .= '<i class="ka-icon icon-tag"></i> ';
        }
        $html .= esc_html($price_text) . '</li>';
    }
    $html .= '</ul>';

    if ($show_signup_link && $signup_url !== '') {
        $label = $is_full ? 'Se neste dato' : 'Påmelding';
        $html .= '<div class="ka-next-course-cta">';
        $html .= '<a href="#" class="ka-next-course-signup-link clickelement" data-url="' . esc_attr($signup_url) . '">' . esc_html($label) . '</a>';
        $html .= '</div>';
    }

    $html .= '</div>';
    return $html;
}

/**
 * @param array<string,mixed> $attributes
 */
function kursagenten_render_single_ka_content_block(array $attributes): string {
    if (!is_singular('ka_course')) {
        return kursagenten_single_block_message('Denne blokken fungerer kun på enkeltkurs.');
    }

    $post_id = (int) get_the_ID();
    $content = (string) get_post_meta($post_id, 'ka_course_content', true);
    $content = trim($content);
    if ($content === '') {
        return '';
    }

    $style = kursagenten_single_build_style_vars($attributes);
    return '<div class="ka-single-block ka-single-ka-content"' . ($style !== '' ? ' style="' . esc_attr($style) . '"' : '') . '>' . wp_kses_post(wpautop($content)) . '</div>';
}

/**
 * @param array<string,mixed> $attributes
 */
function kursagenten_render_single_contact_block(array $attributes): string {
    if (!is_singular('ka_course')) {
        return kursagenten_single_block_message('Denne blokken fungerer kun på enkeltkurs.');
    }

    $post_id = (int) get_the_ID();
    $contact_name = trim((string) get_post_meta($post_id, 'ka_course_contact_name', true));
    $contact_phone = trim((string) get_post_meta($post_id, 'ka_course_contact_phone', true));
    $contact_email = sanitize_email((string) get_post_meta($post_id, 'ka_course_contact_email', true));

    $has_any = $contact_name !== '' || $contact_phone !== '' || $contact_email !== '';
    $hide_if_empty = !isset($attributes['hideIfEmpty']) || !empty($attributes['hideIfEmpty']);
    if (!$has_any && $hide_if_empty) {
        return '';
    }

    $show_wrapper = !isset($attributes['showWrapper']) || !empty($attributes['showWrapper']);
    $show_title = !isset($attributes['showTitle']) || !empty($attributes['showTitle']);
    $style = kursagenten_single_build_style_vars($attributes);

    $inner = '<div class="ka-contact-info">';
    if ($show_title) {
        $inner .= '<h3 class="ka-contact-heading">Kontakt</h3>';
    }
    $inner .= '<ul class="ka-contact-list">';
    if ($contact_name !== '') {
        $inner .= '<li class="ka-contact-item ka-contact-name">' . esc_html($contact_name) . '</li>';
    }
    if ($contact_phone !== '') {
        $phone_href = preg_replace('/[^\d+]/', '', $contact_phone) ?: $contact_phone;
        $inner .= '<li class="ka-contact-item ka-contact-phone"><a href="tel:' . esc_attr($phone_href) . '">' . esc_html($contact_phone) . '</a></li>';
    }
    if ($contact_email !== '') {
        $inner .= '<li class="ka-contact-item ka-contact-email"><a href="mailto:' . esc_attr($contact_email) . '">' . esc_html($contact_email) . '</a></li>';
    }
    $inner .= '</ul></div>';

    if (!$show_wrapper) {
        return '<div class="ka-single-block ka-single-contact"' . ($style !== '' ? ' style="' . esc_attr($style) . '"' : '') . '>' . $inner . '</div>';
    }

    return '<div class="ka-single-block ka-single-contact ka-contact-wrapper"' . ($style !== '' ? ' style="' . esc_attr($style) . '"' : '') . '>' . $inner . '</div>';
}

/**
 * @param array<string,mixed> $attributes
 */
function kursagenten_render_single_related_courses_block(array $attributes): string {
    if (!is_singular('ka_course')) {
        return kursagenten_single_block_message('Denne blokken fungerer kun på enkeltkurs.');
    }

    $layout = (string) ($attributes['layout'] ?? 'list');
    if (!in_array($layout, ['list', 'grid', 'cards'], true)) {
        $layout = 'list';
    }

    $columns = max(1, min(6, (int) ($attributes['columns'] ?? 3)));
    $limit = max(1, min(30, (int) ($attributes['limit'] ?? 6)));
    $show_image = !isset($attributes['showImage']) || !empty($attributes['showImage']);

    // Map to existing shortcode for consistent markup and CSS.
    $shortcode_atts = [
        'layout' => $layout === 'list' ? 'liste' : ($layout === 'grid' ? 'stablet' : 'kort'),
        'grid' => (string) $columns,
        'bildestr' => $show_image ? 'standard' : 'ingen',
        'limit' => (string) $limit,
    ];

    $style = kursagenten_single_build_style_vars($attributes);
    $html = '<div class="ka-single-block ka-single-related-courses"' . ($style !== '' ? ' style="' . esc_attr($style) . '"' : '') . '>';
    $html .= do_shortcode('[kurs-i-samme-kategori ' . kursagenten_build_shortcode_attr_string($shortcode_atts) . ']');
    $html .= '</div>';
    return $html;
}

/**
 * Build a safe shortcode attribute string.
 *
 * @param array<string,string> $atts
 */
function kursagenten_build_shortcode_attr_string(array $atts): string {
    $parts = [];
    foreach ($atts as $key => $value) {
        $key = sanitize_key($key);
        $value = trim((string) $value);
        if ($key === '' || $value === '') {
            continue;
        }
        $parts[] = $key . '="' . esc_attr($value) . '"';
    }
    return implode(' ', $parts);
}


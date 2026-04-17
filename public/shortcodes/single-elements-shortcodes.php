<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

require_once KURSAG_PLUGIN_DIR . '/public/blocks/single-elements/render.php';

/**
 * Enqueue shared styles for single element blocks/shortcodes.
 */
function kursagenten_enqueue_single_elements_style(): void {
    if (wp_style_is('kursagenten-single-elements', 'registered') && !wp_style_is('kursagenten-single-elements', 'enqueued')) {
        wp_enqueue_style('kursagenten-single-elements');
    }
}

add_shortcode('ka_single_title', static function ($atts): string {
    kursagenten_enqueue_single_elements_style();
    $atts = shortcode_atts([
        'headingTag' => 'h1',
        'showLocation' => 'auto',
        'layout' => 'stacked',
        'locationColor' => '',
        'locationSize' => '',
        'textColor' => '',
        'headingColor' => '',
        'linkColor' => '',
        'iconColor' => '',
        'fontFamily' => '',
        'headingFontFamily' => '',
        'fontSize' => '',
        'headingSize' => '',
    ], $atts, 'ka_single_title');
    return kursagenten_render_single_title_block((array) $atts);
});

add_shortcode('ka_single_course_link', static function ($atts): string {
    kursagenten_enqueue_single_elements_style();
    $atts = shortcode_atts([
        'label' => '',
        'showIcon' => true,
        'textColor' => '',
        'headingColor' => '',
        'linkColor' => '',
        'iconColor' => '',
        'fontFamily' => '',
        'headingFontFamily' => '',
        'fontSize' => '',
        'headingSize' => '',
    ], $atts, 'ka_single_course_link');
    $atts['showIcon'] = !empty($atts['showIcon']) && $atts['showIcon'] !== 'false' && $atts['showIcon'] !== '0';
    return kursagenten_render_single_course_link_block((array) $atts);
});

add_shortcode('ka_single_signup_button', static function ($atts): string {
    kursagenten_enqueue_single_elements_style();
    $atts = shortcode_atts([
        'fallbackText' => '',
        'styleVariant' => 'primary',
        'fullWidth' => false,
        'buttonStyleSource' => 'theme',
        'buttonBg' => '',
        'buttonColor' => '',
        'buttonRadius' => '',
        'buttonPadding' => '',
    ], $atts, 'ka_single_signup_button');
    $atts['fullWidth'] = !empty($atts['fullWidth']) && $atts['fullWidth'] !== 'false' && $atts['fullWidth'] !== '0';
    return kursagenten_render_single_signup_button_block((array) $atts);
});

add_shortcode('ka_single_schedule_list', static function ($atts): string {
    kursagenten_enqueue_single_elements_style();
    $atts = shortcode_atts([
        'headingTag' => 'h3',
        'showLocationLinks' => true,
        'textColor' => '',
        'headingColor' => '',
        'linkColor' => '',
        'iconColor' => '',
        'fontFamily' => '',
        'headingFontFamily' => '',
        'fontSize' => '',
        'headingSize' => '',
    ], $atts, 'ka_single_schedule_list');
    $atts['showLocationLinks'] = !empty($atts['showLocationLinks']) && $atts['showLocationLinks'] !== 'false' && $atts['showLocationLinks'] !== '0';
    return kursagenten_render_single_schedule_list_block((array) $atts);
});

add_shortcode('ka_single_next_course_info', static function ($atts): string {
    kursagenten_enqueue_single_elements_style();
    $atts = shortcode_atts([
        'showHeading' => true,
        'showIcons' => true,
        'showSignupLink' => true,
        'showPrice' => true,
        'showDuration' => true,
        'showLanguage' => true,
        'textColor' => '',
        'headingColor' => '',
        'linkColor' => '',
        'iconColor' => '',
        'fontFamily' => '',
        'headingFontFamily' => '',
        'fontSize' => '',
        'headingSize' => '',
    ], $atts, 'ka_single_next_course_info');
    foreach (['showHeading', 'showIcons', 'showSignupLink', 'showPrice', 'showDuration', 'showLanguage'] as $bool_key) {
        $atts[$bool_key] = !empty($atts[$bool_key]) && $atts[$bool_key] !== 'false' && $atts[$bool_key] !== '0';
    }
    return kursagenten_render_single_next_course_info_block((array) $atts);
});

add_shortcode('ka_single_ka_content', static function ($atts): string {
    kursagenten_enqueue_single_elements_style();
    $atts = shortcode_atts([
        'textColor' => '',
        'headingColor' => '',
        'linkColor' => '',
        'iconColor' => '',
        'fontFamily' => '',
        'headingFontFamily' => '',
        'fontSize' => '',
        'headingSize' => '',
    ], $atts, 'ka_single_ka_content');
    return kursagenten_render_single_ka_content_block((array) $atts);
});

add_shortcode('ka_single_contact', static function ($atts): string {
    kursagenten_enqueue_single_elements_style();
    $atts = shortcode_atts([
        'showTitle' => true,
        'showWrapper' => true,
        'hideIfEmpty' => true,
        'textColor' => '',
        'headingColor' => '',
        'linkColor' => '',
        'iconColor' => '',
        'fontFamily' => '',
        'headingFontFamily' => '',
        'fontSize' => '',
        'headingSize' => '',
    ], $atts, 'ka_single_contact');
    foreach (['showTitle', 'showWrapper', 'hideIfEmpty'] as $bool_key) {
        $atts[$bool_key] = !empty($atts[$bool_key]) && $atts[$bool_key] !== 'false' && $atts[$bool_key] !== '0';
    }
    return kursagenten_render_single_contact_block((array) $atts);
});

add_shortcode('ka_single_related_courses', static function ($atts): string {
    kursagenten_enqueue_single_elements_style();
    $atts = shortcode_atts([
        'layout' => 'list',
        'columns' => 3,
        'limit' => 6,
        'showImage' => true,
        'textColor' => '',
        'headingColor' => '',
        'linkColor' => '',
        'iconColor' => '',
        'fontFamily' => '',
        'headingFontFamily' => '',
        'fontSize' => '',
        'headingSize' => '',
    ], $atts, 'ka_single_related_courses');
    $atts['columns'] = (int) $atts['columns'];
    $atts['limit'] = (int) $atts['limit'];
    $atts['showImage'] = !empty($atts['showImage']) && $atts['showImage'] !== 'false' && $atts['showImage'] !== '0';
    return kursagenten_render_single_related_courses_block((array) $atts);
});


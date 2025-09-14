<?php
/**
 * Shortcodes for displaying company information
 * These shortcodes pull data from the bedriftsinfo settings
 */

// Base URL shortcode
add_shortcode('side-url', function($atts) {
    return get_site_url();
});

// Company information shortcodes
add_shortcode('firmanavn', function($atts) {
    $options = get_option('kag_bedriftsinfo_option_name');
    return isset($options['ka_firmanavn']) ? esc_html($options['ka_firmanavn']) : '';
});

add_shortcode('adresse', function($atts) {
    $options = get_option('kag_bedriftsinfo_option_name');
    return isset($options['ka_adresse']) ? esc_html($options['ka_adresse']) : '';
});

add_shortcode('postnummer', function($atts) {
    $options = get_option('kag_bedriftsinfo_option_name');
    return isset($options['ka_postnummer']) ? esc_html($options['ka_postnummer']) : '';
});

add_shortcode('poststed', function($atts) {
    $options = get_option('kag_bedriftsinfo_option_name');
    return isset($options['ka_sted']) ? esc_html($options['ka_sted']) : '';
});

add_shortcode('hovedkontakt', function($atts) {
    $options = get_option('kag_bedriftsinfo_option_name');
    return isset($options['ka_hovedkontakt_navn']) ? esc_html($options['ka_hovedkontakt_navn']) : '';
});

add_shortcode('epost', function($atts) {
    $options = get_option('kag_bedriftsinfo_option_name');
    return isset($options['ka_epost']) ? esc_html($options['ka_epost']) : '';
});

add_shortcode('telefon', function($atts) {
    $options = get_option('kag_bedriftsinfo_option_name');
    return isset($options['ka_tlf']) ? esc_html($options['ka_tlf']) : '';
});

add_shortcode('infotekst', function($atts) {
    $options = get_option('kag_bedriftsinfo_option_name');
    return isset($options['ka_infotekst']) ? wpautop(esc_html($options['ka_infotekst'])) : '';
});

// Social media shortcodes
add_shortcode('facebook', function($atts) {
    $options = get_option('kag_bedriftsinfo_option_name');
    return isset($options['ka_facebook']) ? esc_url($options['ka_facebook']) : '';
});

add_shortcode('instagram', function($atts) {
    $options = get_option('kag_bedriftsinfo_option_name');
    return isset($options['ka_instagram']) ? esc_url($options['ka_instagram']) : '';
});

add_shortcode('linkedin', function($atts) {
    $options = get_option('kag_bedriftsinfo_option_name');
    return isset($options['ka_linkedin']) ? esc_url($options['ka_linkedin']) : '';
});

add_shortcode('youtube', function($atts) {
    $options = get_option('kag_bedriftsinfo_option_name');
    return isset($options['ka_youtube']) ? esc_url($options['ka_youtube']) : '';
});

// Placeholder image shortcodes
add_shortcode('plassholderbilde-generelt', function($atts) {
    $options = get_option('design_option_name');
    return isset($options['ka_plassholderbilde_generelt']) ? esc_url($options['ka_plassholderbilde_generelt']) : '';
});

add_shortcode('plassholderbilde-kurs', function($atts) {
    $options = get_option('design_option_name');
    return isset($options['ka_plassholderbilde_kurs']) ? esc_url($options['ka_plassholderbilde_kurs']) : '';
});

add_shortcode('plassholderbilde-instruktor', function($atts) {
    $options = get_option('design_option_name');
    return isset($options['ka_plassholderbilde_instruktor']) ? esc_url($options['ka_plassholderbilde_instruktor']) : '';
});

add_shortcode('plassholderbilde-sted', function($atts) {
    $options = get_option('design_option_name');
    return isset($options['ka_plassholderbilde_sted']) ? esc_url($options['ka_plassholderbilde_sted']) : '';
}); 
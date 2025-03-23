<?php
/**
 * Moderne taksonomi-rammeverk
 */

if (!defined('ABSPATH')) exit;

// Hent gjeldende term
$term = get_queried_object();
$term_id = $term->term_id;
$taxonomy = $term->taxonomy;

// Hent taksonomi-metadata
$meta = get_taxonomy_meta($term_id, $taxonomy);

// Hent kurs for denne termen
$args = [
    'post_type' => 'course',
    'tax_query' => [
        [
            'taxonomy' => $taxonomy,
            'field'    => 'term_id',
            'terms'    => $term_id,
        ],
    ],
    'posts_per_page' => -1
];

$query = new WP_Query($args);

// Last inn layout-wrapper
kursagenten_get_layout_template(); 
<?php
/**
 * Standard taksonomi-rammeverk
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

// Hent term-metadata
$term_meta = get_taxonomy_meta($term_id, $taxonomy);

// Initialiser hovedspørring og filterinnstillinger
$query = get_courses_by_taxonomy_query($taxonomy, $term_id);
?>

<!-- Header-område -->
<header class="ka-header">
    <div class="ka-header-content">
        <h1><?php echo esc_html($term->name); ?></h1>
        <?php if (!empty($term->description)): ?>
            <div class="term-description"><?php echo wp_kses_post($term->description); ?></div>
        <?php endif; ?>
    </div>
</header>

<!-- Resten av koden følger samme mønster som archive/default.php --> 
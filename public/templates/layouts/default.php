<?php
/**
 * Standard layout-wrapper
 */

if (!defined('ABSPATH')) exit;

get_header();

// Hent variabler fra rammeverket
global $query, $top_filters, $left_filters, $filter_types, $available_filters, 
       $has_left_filters, $left_column_class, $is_search_only, $search_class, 
       $taxonomy_data, $filter_display_info;
?>

<div class="kursagenten-wrapper ka-default-width">
    <main id="ka-main" class="kursagenten-main" role="main">
        <div class="ka-container">
                                    <?php
            // Last inn riktig design-template basert på kontekst
            kursagenten_get_design_template();
                                ?>
                                </div>
    </main>
    <div id="slidein-overlay"></div>
    <div id="slidein-panel">
        <button class="close-btn" aria-label="Close">&times;</button>
        <iframe id="kursagenten-iframe" src=""></iframe>
    </div>
</div>

<?php get_footer(); ?>  
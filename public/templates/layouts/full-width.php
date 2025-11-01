<?php
/**
 * Full-bredde layout-wrapper
 */

if (!defined('ABSPATH')) exit;

get_header();
?>

<div id="ka" class="kursagenten-wrapper ka-full-width">
    <main id="ka-main" class="kursagenten-main" role="main">
        <?php 
        // Last inn riktig design-template basert på kontekst
        kursagenten_get_design_template();
        ?>
    </main>
    <div id="slidein-overlay"></div>
    <div id="slidein-panel">
        <button class="close-btn" aria-label="Close">&times;</button>
        <iframe id="kursagenten-iframe" src=""></iframe>
    </div>
</div>

<?php get_footer(); ?>
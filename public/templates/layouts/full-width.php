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
</div>

<?php get_footer(); ?>
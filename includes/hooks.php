<?php
/**
 * WordPress hooks for Kursagenten
 *
 * @package kursagenten
 */

/**
 * Taksonomi Hooks
 * 
 * Disse hooks lar andre plugins legge til innhold i taksonomi-sidene:
 * 
 * 1. ka_instructors_left_column
 *    - Plassering: Venstre kolonne på instruktør-sider
 *    - Parameter: $term (WP_Term objekt)
 *    - Eksempel: add_action('ka_instructors_left_column', 'min_funksjon');
 * 
 * 2. ka_coursecategory_left_column
 *    - Plassering: Venstre kolonne på kurskategori-sider
 *    - Parameter: $term (WP_Term objekt)
 *    - Eksempel: add_action('ka_coursecategory_left_column', 'min_funksjon');
 * 
 * 3. ka_courselocation_left_column
 *    - Plassering: Venstre kolonne på kurssted-sider
 *    - Parameter: $term (WP_Term objekt)
 *    - Eksempel: add_action('ka_courselocation_left_column', 'min_funksjon');
 */

// Eksempel på hvordan man kan bruke hooks i andre plugins:
/*
function min_plugin_taxonomy_content($term) {
    // Sjekk om vi har riktig term
    if (!($term instanceof WP_Term)) {
        return;
    }
    
    // Legg til innhold
    echo '<div class="min-plugin-innhold">';
    echo '<h3>Min Plugin Tittel</h3>';
    echo '<p>Innhold for ' . esc_html($term->name) . '</p>';
    echo '</div>';
}

// Registrer for alle taksonomier
add_action('ka_instructors_left_column', 'min_plugin_taxonomy_content');
add_action('ka_coursecategory_left_column', 'min_plugin_taxonomy_content');
add_action('ka_courselocation_left_column', 'min_plugin_taxonomy_content');
*/

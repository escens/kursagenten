<?php
// Avslutt hvis WordPress ikke kjører
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// 1. Slette alle post_meta (meta fields) knyttet til pluginen
global $wpdb;
$wpdb->query(
    "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE 'kag_%'"
);

// 2. Slette alternativer i wp_options-tabellen
delete_option( 'kag_seo_option_name' );       
delete_option( 'kag_avansert_option_name' ); 
delete_option( 'kag_kursinnst_option_name' ); 
delete_option( 'kag_bedriftsinfo_option_name' ); 
delete_option( 'kag_designmaler_option_name' ); 

// Slett nettverksalternativer (multisite)
/* if ( is_multisite() ) {
    delete_site_option( 'din_plugin_option_name' );
} */

// 3. Ekstra: Slette eventuelle egne tabeller (hvis du har opprettet noen)
/* $table_name = $wpdb->prefix . 'din_egen_tabel';
$wpdb->query( "DROP TABLE IF EXISTS $table_name" ); */

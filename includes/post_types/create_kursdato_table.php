<?php

function create_kursdato_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'kursdato';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        kurs_id BIGINT(20) UNSIGNED NOT NULL, -- Referanse til kurs CPT
        kursdato_id BIGINT(20) UNSIGNED NOT NULL, -- Unik ID for kursdato
        location_id BIGINT(20) UNSIGNED DEFAULT NULL, -- Referanse til lokasjon
        parent_course_id BIGINT(20) UNSIGNED DEFAULT NULL, -- Referanse til parent kurs
        schedule_id BIGINT(20) UNSIGNED NOT NULL, -- Referanse til schedule ID fra API
        first_date DATETIME NOT NULL, -- Startdato for kurset
        last_date DATETIME DEFAULT NULL, -- Sluttdato for kurset
        coursetime VARCHAR(255) DEFAULT NULL, -- Tidspunkter (f.eks. kl. 10-12)
        duration VARCHAR(100) DEFAULT NULL, -- Varighet (f.eks. 3 dager)
        language VARCHAR(50) DEFAULT NULL, -- Språk
        price DECIMAL(10,2) DEFAULT NULL, -- Pris
        max_participants INT(11) DEFAULT NULL, -- Maks deltakere
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP, -- Når raden ble opprettet
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Når raden sist ble oppdatert
        PRIMARY KEY  (id),
        KEY kurs_id (kurs_id),
        KEY kursdato_id (kursdato_id),
        KEY location_id (location_id),
        KEY parent_course_id (parent_course_id),
        KEY schedule_id (schedule_id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

register_activation_hook(__FILE__, 'create_kursdato_table');

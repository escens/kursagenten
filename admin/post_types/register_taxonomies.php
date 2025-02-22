<?php
if (!defined('ABSPATH')) {
    exit;
}

// Hent bedriftsvariabler for tilpassede URL-slugs
$url_options = get_option('seo_option_name');
$kurskategori = !empty($url_options['ka_url_rewrite_kurskategori']) ? $url_options['ka_url_rewrite_kurskategori'] : 'kurskategori';
$kurssted = !empty($url_options['ka_url_rewrite_kurssted']) ? $url_options['ka_url_rewrite_kurssted'] : 'kurssted';

function capitalize_first_letter($string) {
    return ucfirst($string);
}

// Registrering av taksonomien 'kurskategori'
register_taxonomy('coursecategory', array('course', 'coursedate', 'instructor'), array(
    'labels' => array(
        'name' => 'Kurskategorier',
        'singular_name' => capitalize_first_letter($kurskategori),
        'menu_name' => 'Kurskategorier',
        'all_items' => 'Alle kurskategorier',
        'edit_item' => 'Rediger kurskategori',
        'view_item' => 'Vis kurskategori',
        'update_item' => 'Oppdater kurskategori',
        'add_new_item' => 'Legg til kurskategori',
        'new_item_name' => 'Nytt navn for kurskategori',
        'parent_item' => 'Foreldrekategori',
        'parent_item_colon' => 'Foreldrekategori:',
        'search_items' => 'Søk etter kurskategori',
        'most_used' => 'Mest brukt',
        'not_found' => 'Ingen kurskategorier funnet',
        'no_terms' => 'Ingen kurskategorier',
        'name_field_description' => 'Navnet er det som vises på siden',
        'slug_field_description' => '"Slug" er den SEO-vennlige versjonen av url-en. Eksempel /mitt-kurs',
        'parent_field_description' => 'Velg en forelder for å lage et hierarki, og la dette bli en subkategori.',
        'filter_by_item' => 'Filtrer på kurskategori',
        'items_list_navigation' => 'Kurskategorier listenavigasjon',
        'items_list' => 'Kurskategorier liste',
        'back_to_items' => '← Tilbake til kurskategorier',
        'item_link' => 'Kurskategori link',
        'item_link_description' => 'Link til en kurskategori',
        'archives'  => capitalize_first_letter($kurskategori),
    ),
    'public' => true,
    'hierarchical' => true,
    'show_in_menu' => true,
    'show_in_rest' => true,
    'show_admin_column' => true,
    'rewrite' => array(
        'slug' => $kurskategori,
    ),
));

// Registrering av taksonomien 'kurssted'
register_taxonomy('course_location', array('course', 'coursedate', 'instructor'), array(
    'labels' => array(
        'name' => 'Kurssteder',
        'singular_name' => capitalize_first_letter($kurssted),
        'menu_name' => 'Kurssteder',
        'all_items' => 'Alle kurssteder',
        'edit_item' => 'Rediger kurssted',
        'view_item' => 'Vis kurssted',
        'update_item' => 'Oppdater kurssted',
        'add_new_item' => 'Legg til kurssted',
        'new_item_name' => 'Navn på nytt kurssted',
        'parent_item' => 'Overordnet kurssted',
        'parent_item_colon' => 'Overordnet kurssted:',
        'search_items' => 'Søk i kurssteder',
        'most_used' => 'Mest brukt',
        'not_found' => 'Ingen kurssteder funnet',
        'no_terms' => 'Ingen kurssteder',
        'filter_by_item' => 'Filtrer på kurssted',
        'items_list_navigation' => 'Kurssteder listenavigasjon',
        'items_list' => 'Kurssteder liste',
        'back_to_items' => '← Tilbake til kurssteder',
        'item_link' => 'Kurssted link',
        'item_link_description' => 'Link til et kurssted',
        'archives'  => capitalize_first_letter($kurssted),
    ),
    'public' => true,
    'hierarchical' => true,
    'show_in_menu' => true,
    'show_admin_column' => true,
    'show_in_rest' => true,
    'rewrite' => array(
        'slug' => $kurssted,
    ),
));

// Registrering av taksonomien 'instruktorer'
register_taxonomy('instructors', array('course', 'coursedate', 'instructor'), array(
    'labels' => array(
        'name' => 'Instruktører',
        'singular_name' => 'Instruktør',
        'menu_name' => 'Instruktører',
        'all_items' => 'Alle instruktører',
        'edit_item' => 'Rediger instruktør',
        'view_item' => 'Vis instruktør',
        'update_item' => 'Oppdater instruktør',
        'add_new_item' => 'Legg til instruktør',
        'new_item_name' => 'Navn på nytt instruktør',
        'parent_item' => 'Overordnet instruktør',
        'parent_item_colon' => 'Overordnet instruktør:',
        'search_items' => 'Søk i instruktører',
        'most_used' => 'Mest brukt',
        'not_found' => 'Ingen instruktører funnet',
        'no_terms' => 'Ingen instruktører',
        'filter_by_item' => 'Filtrer på instruktør',
        'items_list_navigation' => 'Instruktører listenavigasjon',
        'items_list' => 'Instruktører liste',
        'back_to_items' => '← Tilbake til instruktører',
        'item_link' => 'Instruktørlink',
        'item_link_description' => 'Link til et instruktør',
        'archives'  => 'Instruktører',
    ),
    'public' => true,
    'hierarchical' => false,
    'show_in_menu' => true,
    'show_admin_column' => true,
    'show_in_rest' => true,
    'rewrite' => array(
        'slug' => 'instruktorer',
    ),
));

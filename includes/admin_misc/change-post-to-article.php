<?php 

// Endre "Innlegg" til "Artikler" i WP dashboard
add_action( 'admin_menu', 'pilau_change_post_menu_label' );
add_action( 'init', 'pilau_change_post_object_label' );
function pilau_change_post_menu_label() {
    global $menu;
    global $submenu;
    $menu[5][0] = 'Artikler';
    $submenu['edit.php'][5][0] = 'Artikler';
   // $submenu['edit.php'][10][0] = 'Ny artikkel';
    //$submenu['edit.php'][16][0] = 'Tagger';
    echo '';
}
function pilau_change_post_object_label() {
    global $wp_post_types;
    $labels = &$wp_post_types['post']->labels;
    $labels->name = 'Artikler';
    $labels->singular_name = 'Artikkel';
    $labels->add_new = 'Ny artikkel';
    $labels->add_new_item = 'Ny artikkel';
    $labels->edit_item = 'Rediger artikkel';
    $labels->new_item = 'Artikkel';
    $labels->view_item = 'Vis artikkel';
    $labels->search_items = 'Søk i artikler';
    $labels->not_found = 'Ingen artikler funnet';
    $labels->not_found_in_trash = 'Ingen artikler i papirkurven';
    $labels->name_admin_bar = 'Artikler';
}   
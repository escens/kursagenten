<?php

// Lager et custom field på bilder for å merke om de har blitt hentet fra Kursagenten.
// Videre sjekkes dette feltet i scriptet ....... for å skjule disse bildene i Media, så de ikke blir brukt i innlegg og på sider.

add_action('admin_init', 'add_custom_image_meta_box');
add_action('attachment_submitbox_misc_actions', 'custom_attachment_meta_box_callback');
add_action('save_post', 'save_custom_image_meta_box_data');
add_action('edit_attachment', 'save_custom_image_meta_box_data');


function add_custom_image_meta_box() {
    $post = get_post();

    if (isset($post) && $post->post_type == 'attachment' && strpos($post->post_mime_type, 'image/') === 0) {
        add_meta_box(
            'kursbilde_meta_box',
            __('Kursbilde', 'textdomain'),
            'custom_attachment_meta_box_callback',
            'attachment',
            'side',  // Endrer fra 'side' til 'normal' for å plassere boksen nederst
            'low'      // Setter prioritet til 'low' slik at boksen vises nederst i listen
        );
    }
}


function custom_attachment_meta_box_callback($post) {
    $kursbilde = get_post_meta($post->ID, 'er_kursbilde', true);
    
    $status = ($kursbilde) ? __('Ja', 'textdomain') : __('Nei', 'textdomain');
    echo '<div class="misc-pub-section misc-pub-kursagenten"  style="pointer-events: none; opacity: 0.8;">';
    echo '' . __('Bilde hentet fra Kursagenten:', 'textdomain') . ' ' . $status;
    echo '</div>';
}

function save_custom_image_meta_box_data($post_id) {
    // Sjekk om det er et vedlegg
    if (get_post_type($post_id) !== 'attachment') {
        return;
    }

    // Sjekk nonce
    if (!isset($_POST['custom_image_meta_box_nonce']) || !wp_verify_nonce($_POST['custom_image_meta_box_nonce'], 'save_custom_image_meta_box')) {
        return;
    }

    // Sjekk brukertillatelser
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Sjekk om feltet er til stede og oppdater meta
    if (isset($_POST['kursbilde'])) {
        update_post_meta($post_id, 'er_kursbilde', 1);
    } else {
        delete_post_meta($post_id, 'er_kursbilde');
    }
}


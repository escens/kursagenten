<?php
if (!defined('ABSPATH')) exit;

function kursagenten_register_blocks() {
    // Registrer kun i admin-kontekst
    if (!is_admin()) {
        return;
    }

    $block_path = plugins_url('/build/kurstaxonomy.js', dirname(dirname(__FILE__)));
    $file_path = plugin_dir_path(dirname(dirname(__FILE__))) . 'build/kurstaxonomy.js';
    
    if (!file_exists($file_path)) {
        error_log('[Kursagenten Critical] Block file missing: ' . $file_path);
        return;
    }

    // Registrer scriptet
    if (!wp_script_is('kursagenten-blocks-editor', 'registered')) {
        wp_register_script(
            'kursagenten-blocks-editor',
            $block_path,
            array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n'),
            filemtime($file_path)
        );
    }

    // Registrer blokk-typen
    register_block_type('kursagenten/kurstaxonomy', array(
        'editor_script' => 'kursagenten-blocks-editor',
        'render_callback' => 'render_kurstaxonomy_block'
    ));
    
    error_log('[Kursagenten] Block registered during plugin activation');
}

// Registrer blokken på init, men kun i admin
add_action('init', 'kursagenten_register_blocks');

// Registrer blokk-kategorien
function kursagenten_block_category($categories) {
    return array_merge(
        $categories,
        array(
            array(
                'slug'  => 'kursagenten',
                'title' => 'Kursagenten',
                'icon'  => 'calendar-alt'
            )
        )
    );
}
add_filter('block_categories_all', 'kursagenten_block_category', 10, 2);

// Debug: Legg til en admin notice hvis noe går galt
function kursagenten_admin_notices() {
    if (is_admin()) {
        $file_path = plugin_dir_path(dirname(dirname(__FILE__))) . 'build/kurstaxonomy.js';
        if (!file_exists($file_path)) {
            echo '<div class="error"><p>Kursagenten block file not found at: ' . esc_html($file_path) . '</p></div>';
        }
    }
}
add_action('admin_notices', 'kursagenten_admin_notices');

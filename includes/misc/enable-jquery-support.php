<?php 
// if jQuery doesn't load for some reason, force it to load frontend
function force_load_jquery() {
    if (!is_admin()) {
        wp_enqueue_script('jquery');
    }
}
add_action('wp_enqueue_scripts', 'force_load_jquery');
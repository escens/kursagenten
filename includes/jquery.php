<?php 

//In special cases that jQuery is not loaded, you can force the file to be loaded
function force_load_jquery() {
    if (!is_admin()) {
        wp_enqueue_script('jquery');
    }
}
add_action('wp_enqueue_scripts', 'force_load_jquery');
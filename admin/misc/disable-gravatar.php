<?php
// Deaktiver Gravatar på frontend
add_filter('get_avatar_url', function($url) {
    if (!is_admin()) {
        return '';
    }
    return $url;
}, 10, 1);

// Bruk lokalt standardbilde i stedet
add_filter('pre_get_avatar_data', function($args, $id_or_email) {
    if (!is_admin()) {
        $args['default'] = KURSAG_PLUGIN_URL . '/assets/images/gravatar_replacement.png';
        $args['url'] = $args['default'];
    }
    return $args;
}, 10, 2);

// Add custom CSS for avatar styling in admin bar
add_action('admin_init', function() {
    $custom_css = "
        #wpadminbar #wp-admin-bar-my-account.with-avatar>.ab-empty-item img, 
        #wpadminbar #wp-admin-bar-my-account.with-avatar>a img {
            border: none;
            border-radius: 50px;
        }
    ";
    wp_add_inline_style('admin-bar', $custom_css);
}); 
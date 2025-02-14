<?php
// Include option pages
require_once plugin_dir_path(__FILE__) . 'bedriftsinnstillinger.php';
require_once plugin_dir_path(__FILE__) . 'kursinnstillinger.php';
require_once plugin_dir_path(__FILE__) . 'coursedesign.php';
require_once plugin_dir_path(__FILE__) . 'seo.php';
require_once plugin_dir_path(__FILE__) . 'avansert.php';

add_action('admin_menu', 'kursagenten_register_admin_menu');


// Instantiate the classes to add them as submenus
if (is_admin()) {
    $kursinnstillinger = new Kursinnstillinger();
    $bedriftsinformasjon = new Bedriftsinformasjon();
    $designmaler = new Designmaler();
    $seo = new SEO();
    $avansert = new Avansert();
}

// Add the main admin menu
function kursagenten_register_admin_menu() {

    // Add main menu page
    add_menu_page(
        'Kursagenten',                         // Page title
        'Kursagenten',                         // Menu title
        'manage_options',                      // Capability
        'kursagenten',                         // Menu slug
        'kursagenten_admin_landing_page',      // Callback function
        'dashicons-welcome-learn-more',        // Icon
        2                                    // Position
    );

    // Add a landing page callback
    function kursagenten_admin_landing_page() {
        ?>

        <div class="wrap">
            <h2>Kursagenten Landing Page</h2>
            <p class="options-form">Welcome to the Kursagenten admin section!</p>
                <div class="options-card">
                    Kortkoder, info og annetd
                    <?php echo kursagenten_icon_overview_shortcode(); ?>
                </div>
             
        </div>

        <?php
    }
}

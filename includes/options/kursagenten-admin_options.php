<?php
// Include option pages
require_once plugin_dir_path(__FILE__) . 'bedriftsinnstillinger.php';
require_once plugin_dir_path(__FILE__) . 'kursinnstillinger.php';
require_once plugin_dir_path(__FILE__) . 'coursedesign.php';
require_once plugin_dir_path(__FILE__) . 'theme_specific_customizations.php';
require_once plugin_dir_path(__FILE__) . 'seo.php';
require_once plugin_dir_path(__FILE__) . 'avansert.php';
require_once KURSAG_PLUGIN_DIR . '/includes/options/options_menu_top.php'; 

// Instantiate the classes to add them as submenus
if (is_admin()) {
    $kursinnstillinger = new Kursinnstillinger();
    $designmaler = new Designmaler();
    $theme_specific_customizations = new Kursagenten_Theme_Customizations();
    $seo = new SEO();
    $bedriftsinformasjon = new Bedriftsinformasjon();
    $avansert = new Avansert();
}

// Add the main admin menu
function kursagenten_register_admin_menu() {
    // Registrer hovedmenyen først
    add_menu_page(
        'Kursagenten',                         // Page title
        'Kursagenten',                         // Menu title
        'manage_options',                      // Capability
        'kursagenten',                         // Menu slug
        'kursagenten_admin_landing_page',      // Callback function
        'dashicons-welcome-learn-more',        // Icon
        2                                      // Position
    );

    // Legg til hovedsiden som submeny også for å unngå at første submeny blir standard
    add_submenu_page(
        'kursagenten',                         // Parent slug
        'Oversikt',                           // Page title
        'Oversikt',                           // Menu title
        'manage_options',                      // Capability
        'kursagenten',                         // Menu slug (samme som parent)
        'kursagenten_admin_landing_page'       // Callback function
    );
}

// Registrer hovedmenyen
add_action('admin_menu', 'kursagenten_register_admin_menu', 9);

// Landing page function remains the same
function kursagenten_admin_landing_page() {
    ?>
    <div class="wrap">
        <h1>Velkommen til Kursagenten</h1>
        
        <div class="welcome-panel">
            <div class="welcome-panel-content">
                <h2>Administrer dine kursinnstillinger</h2>
                <p class="about-description">Her kan du administrere alle innstillinger for Kursagenten.</p>
                
                <div class="welcome-panel-column-container">
                    <div class="welcome-panel-column">
                        <h3>Kom i gang</h3>
                        <ul>
                            <li><a href="admin.php?page=kursagenten-bedrift">Bedriftsinnstillinger</a></li>
                            <li><a href="admin.php?page=kursagenten-kurs">Kursinnstillinger</a></li>
                            <li><a href="admin.php?page=kursagenten-design">Design</a></li>
                        </ul>
                    </div>
                    
                    <div class="welcome-panel-column">
                        <h3>Kortkoder</h3>
                        <ul>
                            <li><code>[kursagenten_courses]</code> - Vis kursliste</li>
                            <li><code>[kursagenten_calendar]</code> - Vis kurskalender</li>
                            <li><code>[kursagenten_filter]</code> - Vis kursfilter</li>
                        </ul>
                    </div>
                    
                    <div class="welcome-panel-column">
                        <h3>Hjelp & støtte</h3>
                        <ul>
                            <li><a href="#">Dokumentasjon</a></li>
                            <li><a href="#">Support</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (function_exists('kursagenten_icon_overview_shortcode')): ?>
            <div class="card">
                <h2>Tilgjengelige ikoner</h2>
                <?php echo kursagenten_icon_overview_shortcode(); ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

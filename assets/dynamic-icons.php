<?php
function kursagenten_dynamic_icons_css() {

    // Hent ikonfiler fra en mappe
    $icon_folder = plugin_dir_path(__FILE__) . 'icons/';
    $icon_url = plugin_dir_url(__FILE__) . 'icons/';

    if (!is_dir($icon_folder)) {
        error_log("Ikonmappen finnes ikke: $icon_folder");
    }



    // Les alle SVG-filer i mappen
    $icons = glob($icon_folder . '*.svg');

    // Generer CSS for hver SVG
    if (!empty($icons)) {
        echo "<style type='text/css'>\n";
        foreach ($icons as $icon) {
            $icon_name = basename($icon, '.svg'); // Få filnavn uten .svg
            $icon_path = $icon_url . $icon_name . '.svg';
            echo ".icon-$icon_name {\n";
            echo "    mask: url('$icon_path') no-repeat center;\n";
            echo "    -webkit-mask: url('$icon_path') no-repeat center;\n";
            echo "}\n";
        }
        echo "</style>\n";

    }
}
add_action('wp_head', 'kursagenten_dynamic_icons_css');
add_action('admin_head', 'kursagenten_dynamic_icons_css');


// Funksjon for å generere ikonoversikten
function kursagenten_icon_overview_shortcode() {
    // Definer mappen som inneholder ikonene
    $icon_folder = plugin_dir_path(__FILE__) . 'icons/';
    $icon_url = plugin_dir_url(__FILE__) . 'icons/';

    // Kontroller om mappen finnes
    if (!is_dir($icon_folder)) {
        return "Ikonmappen finnes ikke: $icon_folder";
    }

    // Hent alle SVG-filene i mappen
    $icons = glob($icon_folder . '*.svg');

    // Sjekk om det finnes noen ikoner
    if (empty($icons)) {
        return "Ingen ikoner funnet i mappen.";
    }

    // Start HTML-utgangen
    $output = "<div class='col2'>";

    // Generer HTML for hvert ikon
    foreach ($icons as $icon) {
        $icon_name = basename($icon, '.svg'); // Fjern filtypen
        $icon_path = $icon_url . $icon_name . '.svg';
        $output .= "<div class='icon-preview'>";
        $output .= "<i class='ka-icon icon-$icon_name' style='mask: url($icon_path) no-repeat center;\n -webkit-mask: url($icon_path) no-repeat center;\n mask-size: contain;\n -webkit-mask-size: contain;\n'></i> $icon_name";
        $output .= "</div>";
    }

    $output .= "</div>";

    // Legg til CSS-stiler
    $output .= "<style>
    .col2 {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-start;
        gap: 10px;
    }
    .icon-preview {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        flex-basis: 200px;
        padding: 10px;
    }
    i.ka-icon {
        display: block;
        width: 38px;
        height: 26px;
        background-color: rgb(245, 133, 5);
        mask-size: contain;
        -webkit-mask-size: contain;
        background-size: contain;
        background-repeat: no-repeat;
        padding-right: 8px;
    }
    </style>";

    return $output;
}

// Registrer kortkoden
add_shortcode('ikonoversikt', 'kursagenten_icon_overview_shortcode');

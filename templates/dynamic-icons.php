<?php
header("Content-Type: text/css");

// Hent ikonfiler fra en mappe
$icon_folder = plugin_dir_path(__FILE__) . 'assets/icons/';
$icon_url = plugin_dir_url(__FILE__) . 'assets/icons/';

echo $icon_folder;
echo "test";



// Les alle SVG-filer i mappen
$icons = glob($icon_folder . '*.svg');

// Generer CSS for hver SVG
if (!empty($icons)) {
    foreach ($icons as $icon) {
        $icon_name = basename($icon, '.svg'); // Få filnavn uten .svg
        $icon_path = $icon_url . $icon_name . '.svg';
        echo ".icon-$icon_name {\n";
        echo "    background-image: url('$icon_path');\n";
        echo "    background-size: contain;\n";
        echo "    background-repeat: no-repeat;\n";
        echo "    display: inline-block;\n";
        echo "    width: 24px;\n"; // Standardstørrelse, juster etter behov
        echo "    height: 24px;\n"; // Standardstørrelse, juster etter behov
        echo "}\n";
    }
}
?>

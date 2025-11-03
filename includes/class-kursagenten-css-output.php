<?php
/**
 * Class for handling CSS output based on theme settings
 */
class Kursagenten_CSS_Output {
    public function __construct() {
        add_action('wp_head', array($this, 'output_custom_css'), 999);
    }

    public function output_custom_css() {
        $css = ':root {';
        
        // Maksbredde
        $max_width = get_option('kursagenten_max_width', '1300px');
        $css .= '--ka-max-width: ' . esc_attr($max_width) . ';';
        
        // Hovedfarge og avledede farger
        $main_color = get_option('kursagenten_main_color', 'hsl(32, 96%, 49%)');
        
        // Konverter til HSL hvis det er hex
        if (strpos($main_color, '#') === 0) {
            list($h, $s, $l) = $this->hex_to_hsl($main_color);
        } else {
            $hsl = str_replace(['hsl(', ')', '%'], '', $main_color);
            $values = explode(',', $hsl);
            $h = trim($values[0]);
            $s = trim($values[1]);
            $l = trim($values[2]);
        }

        $css .= '--ka-color: ' . esc_attr($main_color) . ';';
        
        // Juster metningsgrad og lyshet basert på utgangspunktet
        $base_l = floatval($l);
        
        // For mørkere farger (under 50% lyshet), reduser metningen for lysere varianter
        if ($base_l < 50) {
            $light_s = max(floatval($s) * 0.7, 30); // Reduser metning med 30%, men ikke under 30%
            $css .= '--ka-color-darker: ' . "hsl($h, {$s}%, " . max(0, $base_l - 10) . "%);";
            $css .= '--ka-color-lighter: ' . "hsl($h, {$light_s}%, " . min(100, $base_l + 11) . "%);";
            $css .= '--ka-color-light: ' . "hsl($h, {$light_s}%, " . min(100, $base_l + 30) . "%);";
            $css .= '--ka-color-lightest: ' . "hsl($h, {$light_s}%, " . min(100, $base_l + 47) . "%);";
            $css .= '--ka-color-light-hover: ' . "hsl($h, {$light_s}%, " . min(100, $base_l + 35) . "%);";
            $css .= '--ka-color-light-active: ' . "hsl($h, {$light_s}%, " . min(100, $base_l + 40) . "%);";
        } 
        // For lysere farger, behold mer av metningen
        else {
            $css .= '--ka-color-darker: ' . "hsl($h, {$s}%, " . max(0, $base_l - 10) . "%);";
            $css .= '--ka-color-lighter: ' . "hsl($h, {$s}%, " . min(100, $base_l + 11) . "%);";
            $css .= '--ka-color-light: ' . "hsl($h, {$s}%, " . min(100, $base_l + 30) . "%);";
            $css .= '--ka-color-lightest: ' . "hsl($h, {$s}%, " . min(100, $base_l + 47) . "%);";
            $css .= '--ka-color-light-hover: ' . "hsl($h, {$s}%, " . min(100, $base_l + 35) . "%);";
            $css .= '--ka-color-light-active: ' . "hsl($h, {$s}%, " . min(100, $base_l + 40) . "%);";
        }
        
        // Aksentfarge og avledede farger (samme logikk som over)
        $accent_color = get_option('kursagenten_accent_color', 'hsl(310, 45%, 52%)');
        $css .= '--ka-color-accent: ' . esc_attr($accent_color) . ';';
        
        // Konverter aksentfarge til HSL hvis det er hex
        if (strpos($accent_color, '#') === 0) {
            list($accent_h, $accent_s, $accent_l) = $this->hex_to_hsl($accent_color);
        } else {
            $accent_hsl = str_replace(['hsl(', ')', '%'], '', $accent_color);
            $accent_values = explode(',', $accent_hsl);
            $accent_h = trim($accent_values[0]);
            $accent_s = trim($accent_values[1]);
            $accent_l = trim($accent_values[2]);
        }
        
        $base_accent_l = floatval($accent_l);
        
        // Juster aksentfarger basert på utgangspunktet
        if ($base_accent_l < 50) {
            $light_accent_s = max(floatval($accent_s) * 0.8, 30);
            $css .= '--ka-color-accent-hover: ' . "hsl($accent_h, {$light_accent_s}%, " . min(100, $base_accent_l + 10) . "%);";
            $css .= '--ka-color-accent-active: ' . "hsl($accent_h, {$light_accent_s}%, " . min(100, $base_accent_l + 15) . "%);";
            $css .= '--ka-color-accent-disabled: ' . "hsl($accent_h, {$light_accent_s}%, " . min(100, $base_accent_l + 25) . "%);";
            $css .= '--ka-color-accent-light: ' . "hsl($accent_h, {$light_accent_s}%, " . min(100, $base_accent_l + 35) . "%);";
            $css .= '--ka-color-accent-dark: ' . "hsl($accent_h, {$accent_s}%, " . max(0, $base_accent_l - 15) . "%);";
        } else {
            $css .= '--ka-color-accent-hover: ' . "hsl($accent_h, {$accent_s}%, " . min(100, $base_accent_l + 6) . "%);";
            $css .= '--ka-color-accent-active: ' . "hsl($accent_h, {$accent_s}%, " . min(100, $base_accent_l + 12) . "%);";
            $css .= '--ka-color-accent-disabled: ' . "hsl($accent_h, {$accent_s}%, " . min(100, $base_accent_l + 18) . "%);";
            $css .= '--ka-color-accent-light: ' . "hsl($accent_h, {$accent_s}%, " . min(100, $base_accent_l + 25) . "%);";
            $css .= '--ka-color-accent-dark: ' . "hsl($accent_h, {$accent_s}%, " . max(0, $base_accent_l - 8) . "%);";
        }
        

        
        // Base skriftstørrelse
        $base_font = get_option('kursagenten_base_font', '16px');
        $css .= '--ka-base-font: ' . esc_attr($base_font) . ';';
        
        // Font size levels (calculated based on base font)
        $css .= '--ka-font-xxs: calc(var(--ka-base-font) * 0.68);';
        $css .= '--ka-font-xs: calc(var(--ka-base-font) * 0.75);';
        $css .= '--ka-font-s: calc(var(--ka-base-font) * 0.875);';
        $css .= '--ka-font-s-plus: calc(var(--ka-base-font) * 0.9375);';
        $css .= '--ka-font-base: var(--ka-base-font);';
        $css .= '--ka-font-md: calc(var(--ka-base-font) * 1.125);';
        $css .= '--ka-font-lg: calc(var(--ka-base-font) * 1.375);';
        $css .= '--ka-font-xl: calc(var(--ka-base-font) * 1.625);';
        $css .= '--ka-font-xxl: calc(var(--ka-base-font) * 2);';
        
        // Line heights
        $css .= '--ka-line-height-tight: 1.2;';
        $css .= '--ka-line-height-normal: 1.5;';
        $css .= '--ka-line-height-relaxed: 1.75;';
        
        // Additional CSS variables
        $css .= '--ka-alt-background: rgba(0, 0, 0, 0.02);';
        $css .= '--ka-box-background: rgba(0, 0, 0, 0.02);';
        $css .= '--ka-color-filter: #494949;';
        $css .= '--ka-filter-font-size: 14px;';
        $css .= '--ka-font-size-small: 0.775rem;';
        $css .= '--ka-font-size-medium: 1rem;';
        
        // Hovedoverskrift font
        $heading_font = get_option('kursagenten_heading_font', 'inherit');
        if ($heading_font !== 'inherit') {
            $css .= '--ka-font-family-main-headings: ' . esc_attr($heading_font) . ';';
        }
        
        // Hovedfont
        $main_font = get_option('kursagenten_main_font', 'inherit');
        if ($main_font !== 'inherit') {
            $css .= '--ka-font-family: ' . esc_attr($main_font) . ';';
        }
        
        // Sjekk om avanserte farger er aktivert
        $advanced_colors = get_option('kursagenten_advanced_colors', 0);
        
        if ($advanced_colors) {
            // Knappefarger
            $button_background = get_option('kursagenten_button_background', '');
            $button_color = get_option('kursagenten_button_color', '');
            if ($button_background) {
                $css .= '--ka-button-background: ' . esc_attr($button_background) . ';';
                $css .= '--ka-button-background-lighter: ' . $this->adjust_lightness($button_background, 10) . ';';
                $css .= '--ka-button-background-darker: ' . $this->adjust_lightness($button_background, -10) . ';';
                
                // Endre farger på knapper
                $css .= '#ka .pagination .current {border-color: var(--ka-button-background); background: var(--ka-button-background); color: var(--ka-button-color);}';
                $css .= '#ka .pagination a:hover, #ka .pagination a:focus, #ka .pagination a:active { border-color: var(--ka-button-background);}';
                $css .= '#ka button { background: var(--ka-button-background); color: var(--ka-button-color); border: none; }';
                $css .= '#ka .courselist-button { background: var(--ka-button-background); color: var(--ka-button-color); border: none; }';
                $css .= '#ka .ka-button { background: var(--ka-button-background); color: var(--ka-button-color); border: none; }';
                $css .= '#ka .pamelding { background: var(--ka-button-background); color: var(--ka-button-color); border: none; }';
                $css .= '#ka .button { background: var(--ka-button-background); color: var(--ka-button-color); border: none; }';
                
                // Hover-effekter for knapper
                $css .= '#ka button:hover, #ka .courselist-button:hover, #ka .ka-button:hover, #ka .pamelding:hover, #ka .button:hover { background: var(--ka-button-background-darker); }';
                $css .= '#ka button:focus, #ka .courselist-button:focus, #ka .ka-button:focus, #ka .pamelding:focus, #ka .button:focus { background: var(--ka-button-background-darker); }';
                $css .= '#ka button:active, #ka .courselist-button:active, #ka .ka-button:active, #ka .pamelding:active, #ka .button:active { background: var(--ka-button-background-darker); }';
            }
            if ($button_color) {
                $css .= '--ka-button-color: ' . esc_attr($button_color) . ';';
            }
            
            // Hvis kun bakgrunnsfarge er satt, bruk standard tekstfarge
            if ($button_background && !$button_color) {
                $css .= '#ka button, #ka .courselist-button, #ka .ka-button, #ka .pamelding, #ka .button { color: #ffffff; }';
            }
            
            // Hvis kun tekstfarge er satt, bruk standard bakgrunnsfarge
            if ($button_color && !$button_background) {
                $css .= '#ka button, #ka .courselist-button, #ka .ka-button, #ka .pamelding, #ka .button { background: var(--ka-color); }';
            }

            // Linker
            $link_color = get_option('kursagenten_link_color', '');
            if ($link_color) {
                $css .= '--ka-link-color: ' . esc_attr($link_color) . ';';
                $css .= '--ka-link-color-lighter: ' . $this->adjust_lightness($link_color, 10) . ';';
                $css .= '--ka-link-color-darker: ' . $this->adjust_lightness($link_color, -10) . ';';
                
                // Endre farger på linker
                $css .= '#ka a:not(.courselist-button):not(.course-linkk):not(.ka-button):not(.button):not(.header-links a):not(.button-filter) { color: var(--ka-link-color); }';
                $css .= '#ka a:not(.courselist-button):not(.course-linkk):not(.ka-button):not(.button):not(.header-links a):not(.button-filter):hover { color: var(--ka-link-color-darker); }';
                $css .= '#ka a:not(.courselist-button):not(.course-linkk):not(.ka-button):not(.button):not(.header-links a):not(.button-filter):focus { color: var(--ka-link-color-darker); }';
                $css .= '#ka a:not(.courselist-button):not(.course-linkk):not(.ka-button):not(.button):not(.header-links a):not(.button-filter):active { color: var(--ka-link-color-darker); }';
            }
            
            // Hvis kun linkfarge er satt, bruk standard hover-farge
            if ($link_color) {
                $css .= '#ka a:not(.courselist-button):not(.course-linkk):not(.ka-button):not(.button):not(.header-links a):not(.button-filter):hover { color: var(--ka-link-color-darker); }';
            }

            // Ikoner
            $icon_color = get_option('kursagenten_icon_color', '');
            if ($icon_color) {
                $css .= '--ka-icon-color: ' . esc_attr($icon_color) . ';';
                $css .= '--ka-icon-color-lighter: ' . $this->adjust_lightness($icon_color, 10) . ';';
                $css .= '--ka-icon-color-darker: ' . $this->adjust_lightness($icon_color, -10) . ';';
                
                // Endre farger på ikoner
                $css .= '#ka .ka-icon { background-color: var(--ka-icon-color); }';
                $css .= '#ka .ka-icon:hover { background-color: var(--ka-icon-color-darker); }';
                $css .= '#ka .iconlist i { background-color: var(--ka-icon-color); }';
                $css .= '#ka .iconlist i:hover { background-color: var(--ka-icon-color-darker); }';
                $css .= '#ka .iconlist .ka-icon { background-color: var(--ka-icon-color); }';
                $css .= '#ka .iconlist .ka-icon:hover { background-color: var(--ka-icon-color-darker); }';
                $css .= '#ka .header-links .ka-icon { background-color: var(--ka-icon-color); }';
                $css .= '#ka .header-links .ka-icon:hover { background-color: var(--ka-icon-color-darker); }';
                $css .= '#ka .maps-link .ka-icon { background-color: var(--ka-icon-color); }';
                $css .= '#ka .maps-link .ka-icon:hover { background-color: var(--ka-icon-color-darker); }';
                $css .= '#ka .taxonomy-list .ka-icon { background-color: var(--ka-icon-color); }';
                $css .= '#ka .taxonomy-list .ka-icon:hover { background-color: var(--ka-icon-color-darker); }';
                $css .= '#ka .course-container .header-content i.ka-icon { background-color: var(--ka-icon-color-lighter); }';
                $css .= '#ka .course-container .header-content i.ka-icon:hover { background-color: var(--ka-icon-color-darker); }';
                
                // Generell regel for alle ikoner som ikke er knapper eller linker
                $css .= '#ka i.ka-icon:not(.courselist-button i):not(.course-link i):not(.ka-button i):not(.button i):not(.course-container .header-content i) { background-color: var(--ka-icon-color); }';
                $css .= '#ka i.ka-icon:not(.courselist-button i):not(.course-link i):not(.ka-button i):not(.button i):not(.course-container .header-content i):hover { background-color: var(--ka-icon-color-darker); }';
            }
            
            // Hvis kun ikonfarge er satt, bruk standard hover-farge
            if ($icon_color) {
                $css .= '#ka .ka-icon:hover, #ka .iconlist i:hover, #ka .iconlist .ka-icon:hover, #ka .header-links .ka-icon:hover, #ka .maps-link .ka-icon:hover, #ka .taxonomy-list .ka-icon:hover, #ka .course-container .header-content i.ka-icon:hover { background-color: var(--ka-icon-color-darker); }';
            }

            // Sidebakgrunn
            $background_color = get_option('kursagenten_background_color', '');
            if ($background_color) {
                $css .= '--ka-background-color: ' . esc_attr($background_color) . ';';
                $css .= '--ka-background-color-lighter: ' . $this->adjust_lightness($background_color, 10) . ';';
                $css .= '--ka-background-color-darker: ' . $this->adjust_lightness($background_color, -10) . ';';
                
                // Endre sidebakgrunn
                $css .= '#ka { background-color: var(--ka-background-color); }';
                //$css .= '#ka .ka-section { background-color: var(--ka-background-color); }';
            }
            
            // Hvis kun bakgrunnsfarge er satt, bruk standard tekstfarge
            if ($background_color) {
                $css .= '#ka { color: inherit; }';
            }

            // Bakgrunn fremhevede områder - overskriver --ka-box-background
            $highlight_background = get_option('kursagenten_highlight_background', '');
            if ($highlight_background) {
                // Override the default --ka-alt-background with custom color
                $css .= '--ka-box-background: ' . esc_attr($highlight_background) . ';';
                $css .= '--ka-box-background-lighter: ' . $this->adjust_lightness($highlight_background, 5) . ';';
                $css .= '--ka-box-background-darker: ' . $this->adjust_lightness($highlight_background, -10) . ';';
            }
            
            // Hvis kun fremhevet bakgrunnsfarge er satt, bruk standard tekstfarge
            if ($highlight_background) {
                $css .= '#ka .ka-section, #ka .options-card, #ka .courselist-item { color: inherit; }';
            }
        }
        
        $css .= '}';

        // Output CSS
        echo '<style type="text/css" id="kursagenten-custom-css">' . $css . '</style>';
    }

    /**
     * Konverterer hex-farge til HSL-verdier
     * 
     * @param string $hex Hex fargekode (f.eks. '#8e0063')
     * @return array Array med [hue, saturation, lightness]
     */
    private function hex_to_hsl($hex) {
        // Fjern # hvis den finnes
        $hex = ltrim($hex, '#');
        
        // Konverter til RGB
        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;
        
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        
        // Beregn luminance først
        $l = ($max + $min) / 2;
        
        // Hvis max og min er like, er det en gråtone
        if ($max == $min) {
            $h = $s = 0;
        } else {
            $d = $max - $min;
            
            // Beregn saturation
            $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
            
            // Beregn hue
            switch ($max) {
                case $r:
                    $h = ($g - $b) / $d + ($g < $b ? 6 : 0);
                    break;
                case $g:
                    $h = ($b - $r) / $d + 2;
                    break;
                case $b:
                    $h = ($r - $g) / $d + 4;
                    break;
            }
            
            $h = $h / 6;
        }
        
        // Konverter til HSL-verdier
        $h = round($h * 360);
        $s = round($s * 100);
        $l = round($l * 100);
        
        return [$h, $s, $l];
    }

    /**
     * Justerer lysheten på en farge
     * 
     * @param string $color Farge i hex eller hsl format
     * @param int $amount Mengde å justere lysheten med (-100 til 100)
     * @return string Justert farge i samme format som input
     */
    private function adjust_lightness($color, $amount) {
        if (strpos($color, '#') === 0) {
            list($h, $s, $l) = $this->hex_to_hsl($color);
            $l = max(0, min(100, $l + $amount));
            return "hsl($h, {$s}%, {$l}%)";
        } else {
            $hsl = str_replace(['hsl(', ')', '%'], '', $color);
            list($h, $s, $l) = explode(',', $hsl);
            $l = max(0, min(100, floatval($l) + $amount));
            return "hsl($h, {$s}%, {$l}%)";
        }
    }
} 
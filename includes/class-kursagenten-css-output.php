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
            $css .= '--ka-color-lighter: ' . "hsl($h, {$light_s}%, " . min(100, $base_l + 30) . "%);";
            $css .= '--ka-color-light: ' . "hsl($h, {$light_s}%, " . min(100, $base_l + 55) . "%);";
            $css .= '--ka-color-light-hover: ' . "hsl($h, {$light_s}%, " . min(100, $base_l + 45) . "%);";
            $css .= '--ka-color-light-active: ' . "hsl($h, {$light_s}%, " . min(100, $base_l + 48) . "%);";
        } 
        // For lysere farger, behold mer av metningen
        else {
            $css .= '--ka-color-darker: ' . "hsl($h, {$s}%, " . max(0, $base_l - 10) . "%);";
            $css .= '--ka-color-lighter: ' . "hsl($h, {$s}%, " . min(100, $base_l + 16) . "%);";
            $css .= '--ka-color-light: ' . "hsl($h, {$s}%, " . min(100, $base_l + 25) . "%);";
            $css .= '--ka-color-light-hover: ' . "hsl($h, {$s}%, " . min(100, $base_l + 30) . "%);";
            $css .= '--ka-color-light-active: ' . "hsl($h, {$s}%, " . min(100, $base_l + 35) . "%);";
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
        
        // Alt bakgrunn med color-mix
        $css .= '--ka-alt-background: color-mix(in hsl, var(--ka-color), white 92%);';
        $css .= '--ka-alt-background-darker: color-mix(in hsl, var(--ka-color-darker), white 90%);';
        $css .= '--ka-alt-background-lighter: color-mix(in hsl, var(--ka-color-lighter), white 93%);';
        
        // Base skriftstørrelse
        $base_font = get_option('kursagenten_base_font', '16px');
        $css .= '--ka-base-font: ' . esc_attr($base_font) . ';';
        
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
                $css .= 'button{  background: var(--ka-button-background);  color: var(--ka-button-color);  border: none;}';
            }
            if ($button_color) {
                $css .= '--ka-button-color: ' . esc_attr($button_color) . ';';
            }

            // Linker
            $link_color = get_option('kursagenten_link_color', '');
            if ($link_color) {
                $css .= '--ka-link-color: ' . esc_attr($link_color) . ';';
                $css .= '--ka-link-color-lighter: ' . $this->adjust_lightness($link_color, 10) . ';';
                $css .= '--ka-link-color-darker: ' . $this->adjust_lightness($link_color, -10) . ';';
            }

            // Ikoner
            $icon_color = get_option('kursagenten_icon_color', '');
            if ($icon_color) {
                $css .= '--ka-icon-color: ' . esc_attr($icon_color) . ';';
                $css .= '--ka-icon-color-lighter: ' . $this->adjust_lightness($icon_color, 10) . ';';
                $css .= '--ka-icon-color-darker: ' . $this->adjust_lightness($icon_color, -10) . ';';
            }

            // Sidebakgrunn
            $background_color = get_option('kursagenten_background_color', '');
            if ($background_color) {
                $css .= '--ka-background-color: ' . esc_attr($background_color) . ';';
                $css .= '--ka-background-color-lighter: ' . $this->adjust_lightness($background_color, 10) . ';';
                $css .= '--ka-background-color-darker: ' . $this->adjust_lightness($background_color, -10) . ';';
            }

            // Bakgrunn fremhevede områder
            $highlight_background = get_option('kursagenten_highlight_background', '');
            if ($highlight_background) {
                $css .= '--ka-highlight-background: ' . esc_attr($highlight_background) . ';';
                $css .= '--ka-highlight-background-lighter: ' . $this->adjust_lightness($highlight_background, 10) . ';';
                $css .= '--ka-highlight-background-darker: ' . $this->adjust_lightness($highlight_background, -10) . ';';
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
<?php
/**
 * Hovedklasse for Kursagenten plugin
 */
class Kursagenten {
    private $css_output;

    public function __construct() {
        // Initialiser CSS output
        $this->css_output = new Kursagenten_CSS_Output();

        // Legg til andre initialiseringer her etter behov
        add_action('init', array($this, 'init'));
    }

    public function init() {
        // Initialiseringskode som skal kjøres på init hook
        // Dette kan være registrering av post types, taxonomies, etc.
    }
} 
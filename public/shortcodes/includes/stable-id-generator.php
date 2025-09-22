<?php
declare(strict_types=1);

if (!defined('ABSPATH')) exit;

/**
 * Klasse for å generere stabile ID-er for kortkoder
 * ID-ene teller opp fra 1 for hver kortkode som blir rendret på siden
 */
class StableIdGenerator {
    private static int $counter = 0;
    private static array $shortcode_types = [
        'instruktorer' => 'kag',
        'kurssteder' => 'kag', 
        'kurskategorier' => 'kag',
        'related-courses' => 'kag'
    ];

    /**
     * Generer en stabil ID for en kortkode
     * 
     * @param string $shortcode_name Navnet på kortkoden (f.eks. 'instruktorer')
     * @return string Stabil ID (f.eks. 'kag1', 'kag2', osv.)
     */
    public static function generate_id(string $shortcode_name): string {
        self::$counter++;
        
        // Bruk standard prefix 'kag' for alle kortkoder
        $prefix = self::$shortcode_types[$shortcode_name] ?? 'kag';
        
        return $prefix . self::$counter;
    }

    /**
     * Reset telleren (nyttig for testing eller hvis siden blir reloadet)
     */
    public static function reset(): void {
        self::$counter = 0;
    }

    /**
     * Hent nåværende teller-verdi
     */
    public static function get_current_count(): int {
        return self::$counter;
    }
}

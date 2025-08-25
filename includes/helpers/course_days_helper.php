<?php
/**
 * Helper functions for course days functionality
 * 
 * @package Kursagenten
 * @since 1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Get course days based on coursetime and firstCourseDate
 * 
 * @param string $coursetime The coursetime string from API
 * @param string $firstCourseDate The first course date from API
 * @return string The weekday name in Norwegian or empty string
 */
function get_course_days_from_coursetime($coursetime, $firstCourseDate) {
    // Check if coursetime starts with "Kl" and has time format
    if (empty($coursetime) || !preg_match('/^Kl\s+\d{1,2}:\d{2}\s*-\s*\d{1,2}:\d{2}$/', trim($coursetime))) {
        return '';
    }
    
    // Check if we have a valid firstCourseDate
    if (empty($firstCourseDate)) {
        return '';
    }
    
    try {
        // Parse the date string
        $date = new DateTime($firstCourseDate);
        
        // Get weekday number (0 = Sunday, 1 = Monday, etc.)
        $weekday_number = (int) $date->format('w');
        
        // Convert to Norwegian weekday names
        $norwegian_weekdays = [
            0 => 'Søndag',
            1 => 'Mandag', 
            2 => 'Tirsdag',
            3 => 'Onsdag',
            4 => 'Torsdag',
            5 => 'Fredag',
            6 => 'Lørdag'
        ];
        
        return $norwegian_weekdays[$weekday_number] ?? '';
        
    } catch (Exception $e) {
        // Log error if date parsing fails
        error_log("Kursagenten: Feil ved parsing av kursdato: " . $e->getMessage());
        return '';
    }
}

/**
 * Test function to validate coursetime format
 * 
 * @param string $coursetime The coursetime string to test
 * @return bool True if format is valid
 */
function is_valid_coursetime_format($coursetime) {
    if (empty($coursetime)) {
        return false;
    }
    
    // Check if it starts with "Kl" and has time format HH:MM - HH:MM
    return preg_match('/^Kl\s+\d{1,2}:\d{2}\s*-\s*\d{1,2}:\d{2}$/', trim($coursetime));
}

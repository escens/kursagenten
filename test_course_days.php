<?php
/**
 * Test file for course days functionality
 * 
 * @package Kursagenten
 * @since 1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Test function to verify course days functionality
function test_course_days_functionality() {
    echo "<h2>Test av Course Days Funksjonalitet</h2>";
    
    // Test 1: Valid coursetime format starting with "Kl"
    $test_coursetime1 = "Kl 08:08 - 11:11";
    $test_date1 = "2025-10-10T00:00:00"; // Friday
    $result1 = get_course_days_from_coursetime($test_coursetime1, $test_date1);
    echo "<p><strong>Test 1:</strong> coursetime: '$test_coursetime1', date: '$test_date1'</p>";
    echo "<p>Resultat: " . ($result1 ?: 'Tom') . " (Forventet: Fredag)</p>";
    
    // Test 2: Invalid coursetime format (doesn't start with "Kl")
    $test_coursetime2 = "Fredager 10.00-12.00";
    $test_date2 = "2025-10-10T00:00:00";
    $result2 = get_course_days_from_coursetime($test_coursetime2, $test_date2);
    echo "<p><strong>Test 2:</strong> coursetime: '$test_coursetime2', date: '$test_date2'</p>";
    echo "<p>Resultat: " . ($result2 ?: 'Tom') . " (Forventet: Tom)</p>";
    
    // Test 3: Valid coursetime format with Monday date
    $test_coursetime3 = "Kl 09:00 - 16:00";
    $test_date3 = "2025-10-13T00:00:00"; // Monday
    $result3 = get_course_days_from_coursetime($test_coursetime3, $test_date3);
    echo "<p><strong>Test 3:</strong> coursetime: '$test_coursetime3', date: '$test_date3'</p>";
    echo "<p>Resultat: " . ($result3 ?: 'Tom') . " (Forventet: Mandag)</p>";
    
    // Test 4: Empty coursetime
    $test_coursetime4 = "";
    $test_date4 = "2025-10-10T00:00:00";
    $result4 = get_course_days_from_coursetime($test_coursetime4, $test_date4);
    echo "<p><strong>Test 4:</strong> coursetime: '$test_coursetime4', date: '$test_date4'</p>";
    echo "<p>Resultat: " . ($result4 ?: 'Tom') . " (Forventet: Tom)</p>";
    
    // Test 5: Invalid date format
    $test_coursetime5 = "Kl 08:00 - 12:00";
    $test_date5 = "invalid-date";
    $result5 = get_course_days_from_coursetime($test_coursetime5, $test_date5);
    echo "<p><strong>Test 5:</strong> coursetime: '$test_coursetime5', date: '$test_date5'</p>";
    echo "<p>Resultat: " . ($result5 ?: 'Tom') . " (Forventet: Tom pga. ugyldig dato)</p>";
    
    echo "<hr>";
    echo "<h3>Format-validering</h3>";
    
    // Test format validation
    $valid_formats = [
        "Kl 08:08 - 11:11" => true,
        "Kl 09:00 - 16:00" => true,
        "Kl 10:30 - 15:45" => true,
        "Fredager 10.00-12.00" => false,
        "Mandager 09:00-17:00" => false,
        "Kl 8:8 - 11:11" => true, // Single digits
        "Kl 08:08-11:11" => true, // No spaces around dash
        "kl 08:08 - 11:11" => false, // Lowercase "kl"
        "Kl08:08 - 11:11" => false, // No space after "Kl"
    ];
    
    foreach ($valid_formats as $format => $expected) {
        $is_valid = is_valid_coursetime_format($format);
        $status = $is_valid ? "✓ Gyldig" : "✗ Ugyldig";
        $expected_status = $expected ? "✓ Forventet gyldig" : "✗ Forventet ugyldig";
        echo "<p><strong>$format:</strong> $status ($expected_status)</p>";
    }
}

// Only run test if user is admin
if (current_user_can('manage_options')) {
    add_action('admin_notices', 'test_course_days_functionality');
}

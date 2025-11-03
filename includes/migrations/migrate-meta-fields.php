<?php
/**
 * Migration Script: Update all meta fields to use ka_ prefix
 * 
 * This script migrates all meta fields from their old names to new names with ka_ prefix
 * to avoid conflicts with other plugins and follow WordPress best practices.
 * 
 * IMPORTANT: Run this script once after updating the plugin code.
 * The script is idempotent - safe to run multiple times.
 * 
 * @package kursagenten
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main migration function
 */
function ka_migrate_meta_fields_to_prefix() {
    global $wpdb;
    
    error_log('=== START: Meta Fields Migration to ka_ prefix ===');
    
    // Define all meta field mappings: old_name => new_name
    $meta_field_mappings = [
        // Fields without prefix
        'location_id' => 'ka_location_id',
        'button-text' => 'ka_button_text',
        'main_course_id' => 'ka_main_course_id',
        'is_parent_course' => 'ka_is_parent_course',
        'main_course_title' => 'ka_main_course_title',
        'sub_course_location' => 'ka_sub_course_location',
        'schedule_id' => 'ka_schedule_id',
        'meta_description' => 'ka_meta_description',
        'is_active' => 'ka_is_active',
        
        // Fields with course_ prefix
        'course_content' => 'ka_course_content',
        'course_price' => 'ka_course_price',
        'course_text_before_price' => 'ka_course_text_before_price',
        'course_text_after_price' => 'ka_course_text_after_price',
        'course_difficulty_level' => 'ka_course_difficulty_level',
        'course_type' => 'ka_course_type',
        'course_is_online' => 'ka_course_is_online',
        'course_municipality' => 'ka_course_municipality',
        'course_county' => 'ka_course_county',
        'course_language' => 'ka_course_language',
        'course_external_sign_on' => 'ka_course_external_sign_on',
        'course_contactperson_name' => 'ka_course_contactperson_name',
        'course_contactperson_phone' => 'ka_course_contactperson_phone',
        'course_contactperson_email' => 'ka_course_contactperson_email',
        'course_title' => 'ka_course_title',
        'course_first_date' => 'ka_course_first_date',
        'course_last_date' => 'ka_course_last_date',
        'course_registration_deadline' => 'ka_course_registration_deadline',
        'course_duration' => 'ka_course_duration',
        'course_time' => 'ka_course_time',
        'course_time_type' => 'ka_course_time_type',
        'course_start_time' => 'ka_course_start_time',
        'course_end_time' => 'ka_course_end_time',
        'course_code' => 'ka_course_code',
        'course_button_text' => 'ka_course_button_text',
        'course_maxParticipants' => 'ka_course_maxParticipants',
        'course_showRegistrationForm' => 'ka_course_showRegistrationForm',
        'course_markedAsFull' => 'ka_course_markedAsFull',
        'course_isFull' => 'ka_course_isFull',
        'course_signup_url' => 'ka_course_signup_url',
        'course_location' => 'ka_course_location',
        'course_location_freetext' => 'ka_course_location_freetext',
        'course_location_room' => 'ka_course_location_room',
        'course_address_street' => 'ka_course_address_street',
        'course_address_street_number' => 'ka_course_address_street_number',
        'course_address_zipcode' => 'ka_course_address_zipcode',
        'course_address_place' => 'ka_course_address_place',
        'course_days' => 'ka_course_days',
        'course_month' => 'ka_course_month',
        'course_related_coursedate' => 'ka_course_related_coursedate',
        'course_related_course' => 'ka_course_related_course',
        'course_image_name' => 'ka_course_image_name',
    ];
    
    $total_updated = 0;
    $total_already_migrated = 0;
    
    foreach ($meta_field_mappings as $old_key => $new_key) {
        error_log("Migrating: $old_key => $new_key");
        
        // Check if new meta key already exists
        $existing_new = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s",
            $new_key
        ));
        
        if ($existing_new > 0) {
            error_log("  ✓ New key already exists with {$existing_new} entries - skipping to avoid duplicates");
            $total_already_migrated++;
            continue;
        }
        
        // Update all meta entries from old key to new key
        $updated = $wpdb->update(
            $wpdb->postmeta,
            ['meta_key' => $new_key],  // New value
            ['meta_key' => $old_key],  // Where condition
            ['%s'],                     // Format for new value
            ['%s']                      // Format for where condition
        );
        
        if ($updated !== false) {
            error_log("  ✓ Updated {$updated} entries");
            $total_updated += $updated;
        } else {
            error_log("  ✗ Error updating: " . $wpdb->last_error);
        }
    }
    
    error_log("=== MIGRATION SUMMARY ===");
    error_log("Total meta fields updated: {$total_updated}");
    error_log("Meta fields already migrated: {$total_already_migrated}");
    error_log("=== END: Meta Fields Migration ===");
    
    return [
        'success' => true,
        'total_updated' => $total_updated,
        'already_migrated' => $total_already_migrated,
        'message' => "Migrering fullført! {$total_updated} metafelter oppdatert, {$total_already_migrated} allerede migrert."
    ];
}

/**
 * Admin page for running migration
 */
function ka_migration_admin_page() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Handle migration request
    $migration_result = null;
    if (isset($_POST['run_migration']) && check_admin_referer('ka_run_migration')) {
        $migration_result = ka_migrate_meta_fields_to_prefix();
    }
    
    ?>
    <div class="wrap">
        <h1>Kursagenten - Meta Fields Migration</h1>
        
        <?php if ($migration_result): ?>
            <div class="notice notice-<?php echo $migration_result['success'] ? 'success' : 'error'; ?>">
                <p><?php echo esc_html($migration_result['message']); ?></p>
                <ul>
                    <li>Oppdaterte metafelter: <?php echo esc_html($migration_result['total_updated']); ?></li>
                    <li>Allerede migrert: <?php echo esc_html($migration_result['already_migrated']); ?></li>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Migrer metafelter til ka_ prefix</h2>
            <p>Denne migreringen oppdaterer alle metafelter til å bruke <code>ka_</code> prefix for å unngå konflikter med andre plugins.</p>
            
            <h3>Hva migreringen gjør:</h3>
            <ul>
                <li>Oppdaterer 48 metafelter på alle kurs og kursdatoer</li>
                <li>Endrer f.eks. <code>location_id</code> til <code>ka_location_id</code></li>
                <li>Endrer f.eks. <code>course_price</code> til <code>ka_course_price</code></li>
                <li>Sikker å kjøre flere ganger (idempotent)</li>
            </ul>
            
            <h3>Viktig:</h3>
            <ul style="color: #d63638;">
                <li><strong>Ta backup av databasen før du kjører migreringen!</strong></li>
                <li>Migreringen kan ta litt tid hvis du har mange kurs</li>
                <li>Ikke avbryt prosessen mens den kjører</li>
            </ul>
            
            <form method="post" action="" onsubmit="return confirm('Er du sikker på at du har tatt backup av databasen?');">
                <?php wp_nonce_field('ka_run_migration'); ?>
                <p>
                    <button type="submit" name="run_migration" class="button button-primary button-large">
                        Kjør migrering
                    </button>
                </p>
            </form>
        </div>
        
        <div class="card" style="margin-top: 20px;">
            <h2>Teknisk informasjon</h2>
            <p>Migreringen kjøres direkte på <code>wp_postmeta</code> tabellen med følgende logikk:</p>
            <pre style="background: #f0f0f0; padding: 10px; overflow-x: auto;">
UPDATE wp_postmeta 
SET meta_key = 'ka_[old_key]' 
WHERE meta_key = '[old_key]'
            </pre>
            <p>Logg fra migreringen kan ses i WordPress debug log.</p>
        </div>
    </div>
    <?php
}

/**
 * Add migration page to admin menu
 */
function ka_migration_add_admin_menu() {
    add_submenu_page(
        'tools.php',                      // Parent slug (under Tools)
        'Kursagenten Migration',          // Page title
        'Kursagenten Migration',          // Menu title
        'manage_options',                 // Capability
        'kursagenten-migration',          // Menu slug
        'ka_migration_admin_page'         // Callback function
    );
}
add_action('admin_menu', 'ka_migration_add_admin_menu');


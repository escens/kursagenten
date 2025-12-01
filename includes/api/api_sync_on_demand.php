<?php
// Function to display "Sync All Courses" button in admin options page
function kursagenten_sync_courses_button() {
    if (!current_user_can('manage_options')) {
        wp_die(__('Du har ikke tilgang til denne funksjonen.', 'kursagenten'));
    }

    ob_start();
    ?>
    <div style="margin-bottom: 15px;">
        <a href="#" class="button sync-api-to-posts" id="sync-all-courses">Hent alle kurs fra Kursagenten</a>
        
        <div style="margin-top: 10px; padding: 10px; background: #f0f0f0; border-left: 4px solid #2271b1; max-width: 650px;">
            <label style="display: flex; align-items: center; cursor: pointer;">
                <input type="checkbox" id="run-cleanup-checkbox" style="margin-right: 8px;">
                <span style="font-weight: 500;">Rydd opp i kurs etter synkronisering</span>
            </label>
            <p style="margin: 8px 0 0 24px; font-size: 12px; color: #666; line-height: 1.5;">
                Kryss av på "Rydd opp i kurs" om det er mange utløpte kursdatoer som vises på websiden. 
                Det blir kjørt en nattlig opprydning, så det kan være unødvendig å gjøre dette nå. 
                <strong>NB:</strong> Opprydding tar 3-5 minutter ekstra.
            </p>
        </div>
    </div>
    <div id="sync-status-message" style="margin: 2em 0;"></div>
    <?php
    return ob_get_clean();
}

// Function to display "Cleanup Courses" button in admin options page
function kursagenten_cleanup_courses_button() {
    ob_start();
    ?>
    <a href="#" class="button" id="cleanup-courses">Rydd opp i kurs</a><span style="font-size: 12px; color: #666; line-height: 2.5; padding-left: 1em; font-style: italic;"> Rydd vekk utløpte kursdatoer, og slettede kurs som ikke finnes i Kursagenten</span>
    <div id="cleanup-status-message" style="margin-top: 10px;"></div>
    <?php
    return ob_get_clean();
}

function kursagenten_get_course_ids() {
    check_ajax_referer('sync_kurs_nonce', 'nonce');

    // Increase PHP limits for large sync operations
    @set_time_limit(120); // 2 minutes to check sync status
    @ini_set('memory_limit', '256M');

    $courses = kursagenten_get_course_list();
    if (empty($courses)) {
        error_log("FEIL: Kunne ikke hente kursliste fra API");
        wp_send_json_error(['message' => 'Failed to fetch course data.']);
    }

    // Cleanup specific_locations before sync
    cleanup_all_specific_locations();

    error_log("=== START: Bygger kursliste ===");
    
    $course_data = [];
    $index = 0;
    $valid_location_ids = []; // Store valid location IDs to prevent internal courses from being synced
    
    foreach ($courses as $course) {
        // Skip internal courses: courses with mainCategory.id === 0 are internal courses
        $main_category_id = isset($course['mainCategory']['id']) ? (int) $course['mainCategory']['id'] : null;
        if ($main_category_id === 0) {
            error_log("Hopper over internkurs (mainCategory.id === 0): " . ($course['name'] ?? 'Ukjent navn') . " (ID: " . ($course['id'] ?? 'Ukjent') . ")");
            continue; // Skip this entire course (all its locations)
        }
        
        foreach ($course['locations'] as $location) {
            $is_active = false;
            if (isset($location['active'])) {
                $is_active = filter_var($location['active'], FILTER_VALIDATE_BOOLEAN);
            } elseif (isset($course['active'])) {
                $is_active = filter_var($course['active'], FILTER_VALIDATE_BOOLEAN);
            } else {
                $is_active = false;
            }
            
            $location_id = (int) $location['courseId'];
            $valid_location_ids[] = $location_id; // Add to valid list
            
            // Only send basic info - detailed data will be fetched per batch
            $course_data[] = [
                'index' => $index,
                'location_id' => $location_id,
                'main_course_id' => $course['id'],
                'course_name' => $location['courseName'],
                'municipality' => $location['municipality'],
                'county' => $location['county'],
                'language' => $course['language'],
                'is_active' => $is_active,
                'image_url_cms' => $location['cmsLogo'] ?? null,
            ];
            
            $index++;
        }
    }
    
    // Store valid location IDs in transient for use during sync (expires in 1 hour)
    set_transient('kursagenten_valid_location_ids', $valid_location_ids, HOUR_IN_SECONDS);
    
    $total_courses = count($course_data);
    
    error_log("=== SLUTT: Bygget liste med $total_courses kurs ===");
    error_log("=== Lagret " . count($valid_location_ids) . " gyldige location_ids for å forhindre synk av internkurs ===");

    wp_send_json_success([
        'courses' => $course_data,
        'stats' => [
            'total' => $total_courses,
        ]
    ]);
}
add_action('wp_ajax_get_course_ids', 'kursagenten_get_course_ids');

function kursagenten_run_sync_kurs() {
    check_ajax_referer('sync_kurs_nonce', 'nonce');
    
    // Increase PHP limits for large sync operations
    // Some courses have images that take 30+ seconds to download
    @set_time_limit(600); // 10 minutes per batch (to handle slow image downloads)
    @ini_set('memory_limit', '512M');
    
    error_log("================================================");
    error_log("=== START: Synkronisering av kurs ===");

    if (!isset($_POST['courses']) || !is_array($_POST['courses'])) {
        error_log("FEIL: Ugyldig kursdata mottatt");
        wp_send_json_error(['message' => 'Invalid course data.']);
    }

    error_log("--Antall kurs å synkronisere: " . count($_POST['courses']));
    $success_count = 0;
    $error_count = 0;
    $failed_courses = []; // Track failed courses with details

    // Get valid location IDs from transient to prevent syncing internal courses
    $valid_location_ids = get_transient('kursagenten_valid_location_ids');
    if ($valid_location_ids === false || empty($valid_location_ids)) {
        // If transient expired or empty, rebuild the list from CourseList API
        error_log("Transient utløpt eller tom, henter gyldige location_ids på nytt");
        $course_list = kursagenten_get_course_list();
        $valid_location_ids = [];
        if (!empty($course_list)) {
            foreach ($course_list as $course_item) {
                // Skip internal courses: courses with mainCategory.id === 0 are internal courses
                $main_category_id = isset($course_item['mainCategory']['id']) ? (int) $course_item['mainCategory']['id'] : null;
                if ($main_category_id === 0) {
                    continue; // Skip this entire course (all its locations)
                }
                
                foreach ($course_item['locations'] as $location) {
                    $valid_location_ids[] = (int) $location['courseId'];
                }
            }
        }
        set_transient('kursagenten_valid_location_ids', $valid_location_ids, HOUR_IN_SECONDS);
        error_log("Oppdatert transient med " . count($valid_location_ids) . " gyldige location_ids");
    }
    
    foreach ($_POST['courses'] as $course) {
        try {
            $location_id = (int) ($course['location_id'] ?? 0);
            error_log("--Synkroniserer kurs: " . ($course['course_name'] ?? 'Ukjent navn') . " (ID: " . $location_id . ")");
            
            // CRITICAL: Check if this location_id is in the valid list (prevents syncing internal courses)
            // This check MUST pass before fetching detailed course data
            if (!in_array($location_id, $valid_location_ids)) {
                $error_msg = "Kurset er et internkurs og skal ikke synkroniseres (finnes ikke i CourseList API)";
                error_log("ADVARSEL: $error_msg for location_id: " . $location_id);
                $error_count++;
                $failed_courses[] = [
                    'location_id' => $location_id,
                    'course_name' => $course['course_name'] ?? 'Ukjent navn',
                    'error_type' => 'internal_course',
                    'error_message' => $error_msg
                ];
                continue;
            }
            
            // Fetch detailed course data for this location
            $single_course = kursagenten_get_course_details($location_id);
            
            if (empty($single_course)) {
                $error_msg = "Kunne ikke hente detaljert kursdata fra API";
                error_log("FEIL: $error_msg for location_id: " . $location_id);
                $error_count++;
                $failed_courses[] = [
                    'location_id' => $location_id,
                    'course_name' => $course['course_name'] ?? 'Ukjent navn',
                    'error_type' => 'api_fetch_failed',
                    'error_message' => $error_msg
                ];
                continue;
            }
            
            // Additional check: Verify course is not an internal course by checking mainCategory.id
            // Internal courses often have mainCategory.id === 0
            if (isset($single_course['mainCategory']['id']) && (int)$single_course['mainCategory']['id'] === 0) {
                // Double-check: Even if mainCategory.id is 0, verify it's not in CourseList
                if (!in_array($location_id, $valid_location_ids)) {
                    $error_msg = "Kurset er et internkurs (mainCategory.id === 0 og finnes ikke i CourseList API)";
                    error_log("ADVARSEL: $error_msg for location_id: " . $location_id);
                    $error_count++;
                    $failed_courses[] = [
                        'location_id' => $location_id,
                        'course_name' => $course['course_name'] ?? 'Ukjent navn',
                        'error_type' => 'internal_course',
                        'error_message' => $error_msg
                    ];
                    continue;
                }
            }
            
            // Add the detailed course data to the course array
            $course['single_course_data'] = $single_course;
            $course['is_active'] = filter_var($course['is_active'], FILTER_VALIDATE_BOOLEAN) ? '1' : '';
            
            $sync_result = create_or_update_course_and_schedule($course);
            
            // Function returns post_id (integer) on success, false on failure
            if ($sync_result !== false && $sync_result !== null) {
                $success_count++;
            } else {
                $error_count++;
                $error_msg = is_string($sync_result) ? $sync_result : "Ukjent feil under synkronisering";
                error_log("FEIL: $error_msg - kurs: " . ($course['course_name'] ?? 'Ukjent navn'));
                $lower_error = strtolower($error_msg);
                $error_type = 'sync_failed';
                if (strpos($lower_error, 'bilde er for stort') !== false) {
                    $error_type = 'image_too_large';
                } elseif (strpos($lower_error, 'bilde-nedlasting timeout') !== false || strpos($lower_error, 'timeout') !== false) {
                    $error_type = 'image_timeout';
                }
                $failed_courses[] = [
                    'location_id' => $course['location_id'],
                    'course_name' => $course['course_name'] ?? 'Ukjent navn',
                    'error_type' => $error_type,
                    'error_message' => $error_msg
                ];
            }
        } catch (Exception $e) {
            $error_count++;
            $error_msg = $e->getMessage();
            error_log("FEIL under synkronisering (Exception): $error_msg");
            $lower_error = strtolower($error_msg);
            $error_type = 'exception';
            if (strpos($lower_error, 'bilde er for stort') !== false) {
                $error_type = 'image_too_large';
            } elseif (strpos($lower_error, 'bilde-nedlasting timeout') !== false || strpos($lower_error, 'timeout') !== false) {
                $error_type = 'image_timeout';
            }
            $failed_courses[] = [
                'location_id' => $course['location_id'] ?? 'Ukjent ID',
                'course_name' => $course['course_name'] ?? 'Ukjent navn',
                'error_type' => $error_type,
                'error_message' => $error_msg
            ];
        }
    }

    error_log("Synkronisering fullført. Suksess: $success_count, Feil: $error_count");
    
    // Log failed courses summary
    if (!empty($failed_courses)) {
        error_log("❌ FEILEDE KURS I DENNE BATCHEN:");
        foreach ($failed_courses as $failed) {
            error_log("  - {$failed['course_name']} (kursID: {$failed['location_id']}) - {$failed['error_message']}");
        }
    }
    
    error_log("=== SLUTT: Synkronisering av kurs ===");

    error_log("Oppdaterer hovedkurs statuser etter fullført synkronisering");
    kursagenten_update_main_course_status();

    wp_send_json_success([
        'success_count' => $success_count,
        'error_count' => $error_count,
        'failed_courses' => $failed_courses // Include detailed failure info
    ]);
}
add_action('wp_ajax_run_sync_kurs', 'kursagenten_run_sync_kurs');

// Enqueue admin JavaScript for handling sync button functionality
function kursagenten_enqueue_admin_scripts($hook) {
    if ('toplevel_page_kursinnstillinger' !== $hook) {
        //return;
    }
    wp_enqueue_script(
        'kursagenten-admin-sync', 
        KURSAG_PLUGIN_URL . '/assets/js/admin/kursagenten-admin-sync.js', 
        array('jquery'), 
        '1.3.3', // Simplified stats - just shows total courses found
        true
    );
   
    wp_localize_script('kursagenten-admin-sync', 'sync_kurs', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('sync_kurs_nonce'),
    ]);
}
add_action( 'admin_enqueue_scripts', 'kursagenten_enqueue_admin_scripts' );

/**
 * Function to handle nightly course synchronization
 * This function is called by WordPress cron
 */
function kursagenten_nightly_sync() {
    error_log("=== START: Nattlig synkronisering av kurs ===");
    
    // Increase PHP limits for large sync operations
    @set_time_limit(3600); // 1 hour for nightly sync
    @ini_set('memory_limit', '512M');
    
    // Get course data
    $courses = kursagenten_get_course_list();
    if (empty($courses)) {
        error_log("FEIL: Kunne ikke hente kursliste fra API under nattlig synkronisering");
        return;
    }

    // Build list of valid location IDs to prevent syncing internal courses
    $valid_location_ids = [];
    foreach ($courses as $course) {
        // Skip internal courses: courses with mainCategory.id === 0 are internal courses
        $main_category_id = isset($course['mainCategory']['id']) ? (int) $course['mainCategory']['id'] : null;
        if ($main_category_id === 0) {
            error_log("Nattlig synk: Hopper over internkurs (mainCategory.id === 0): " . ($course['name'] ?? 'Ukjent navn') . " (ID: " . ($course['id'] ?? 'Ukjent') . ")");
            continue; // Skip this entire course (all its locations)
        }
        
        foreach ($course['locations'] as $location) {
            $valid_location_ids[] = (int) $location['courseId'];
        }
    }
    
    // Update transient with valid location IDs for future use
    set_transient('kursagenten_valid_location_ids', $valid_location_ids, HOUR_IN_SECONDS);
    error_log("Nattlig synkronisering: Lagret " . count($valid_location_ids) . " gyldige location_ids i transient.");

    $success_count = 0;
    $error_count = 0;

    foreach ($courses as $course) {
        // Skip internal courses: courses with mainCategory.id === 0 are internal courses
        $main_category_id = isset($course['mainCategory']['id']) ? (int) $course['mainCategory']['id'] : null;
        if ($main_category_id === 0) {
            error_log("Nattlig synk: Hopper over internkurs (mainCategory.id === 0): " . ($course['name'] ?? 'Ukjent navn') . " (ID: " . ($course['id'] ?? 'Ukjent') . ")");
            $error_count++; // Count as error for tracking
            continue; // Skip this entire course (all its locations)
        }
        
        foreach ($course['locations'] as $location) {
            $location_id = (int) $location['courseId'];
            
            // CRITICAL: Skip if location_id is not in valid list (prevents syncing internal courses)
            if (!in_array($location_id, $valid_location_ids)) {
                error_log("ADVARSEL: Hopper over internkurs med location_id: $location_id (finnes ikke i gyldig liste)");
                $error_count++; // Count as error for tracking
                continue;
            }
            $is_active = false;
            if (isset($location['active'])) {
                $is_active = filter_var($location['active'], FILTER_VALIDATE_BOOLEAN);
            } elseif (isset($course['active'])) {
                $is_active = filter_var($course['active'], FILTER_VALIDATE_BOOLEAN);
            } else {
                // Hvis ingen active-verdi er satt, anta at kurset er inaktivt
                // Dette forhindrer at inaktive kurs blir aktivert som standard
                $is_active = false;
            }

            // Hent enkeltkursdata for denne lokasjonen
            $single_course = kursagenten_get_course_details($location_id);
            
            if (empty($single_course)) {
                error_log("ADVARSEL: Kunne ikke hente enkeltkursdata for location_id: " . $location_id);
                continue;
            }
            
            $course_data = [
                'location_id' => $location_id,
                'main_course_id' => $course['id'],
                'course_name' => $location['courseName'],
                'municipality' => $location['municipality'],
                'county' => $location['county'],
                'language' => $course['language'],
                'is_active' => $is_active,
                'image_url_cms' => $location['cmsLogo'] ?? null,
                'single_course_data' => $single_course // Send med hele single_course data
            ];

            try {
                if (create_or_update_course_and_schedule($course_data)) {
                    $success_count++;
                } else {
                    $error_count++;
                    error_log("FEIL: Kunne ikke synkronisere kurs: " . $course_data['course_name']);
                }
            } catch (Exception $e) {
                $error_count++;
                error_log("FEIL under nattlig synkronisering: " . $e->getMessage());
            }
        }
    }

    error_log("Nattlig synkronisering av kurs fullført. Suksess: $success_count, Feil: $error_count");

    // Note: sync_term_locations() was removed as it was not implemented
    // Location data is already synced via update_course_taxonomies() during course sync
    // No additional location sync is needed here

    // Update main course statuses
    error_log("Oppdaterer hovedkurs statuser");
    kursagenten_update_main_course_status();

    error_log("=== SLUTT: Nattlig synkronisering av kurs ===");
}

/**
 * Register the nightly sync schedule
 */
function kursagenten_register_nightly_sync() {
    if (!wp_next_scheduled('kursagenten_nightly_sync_event')) {
        // Schedule the event to run at 2 AM every day
        wp_schedule_event(strtotime('tomorrow 2:00:00'), 'daily', 'kursagenten_nightly_sync_event');
    }
}
add_action('wp', 'kursagenten_register_nightly_sync');

/**
 * Hook the sync function to the scheduled event
 */
add_action('kursagenten_nightly_sync_event', 'kursagenten_nightly_sync');

/**
 * Clean up the scheduled event when plugin is deactivated
 */
function kursagenten_deactivate_nightly_sync() {
    wp_clear_scheduled_hook('kursagenten_nightly_sync_event');
}
register_deactivation_hook(KURSAG_PLUGIN_FILE, 'kursagenten_deactivate_nightly_sync');

/**
 * Function to handle course cleanup
 * This function can be called manually or via cron
 */
function cleanup_courses_on_demand() {
    // Increase PHP limits for cleanup operations
    @set_time_limit(300); // 5 minutes for cleanup
    @ini_set('memory_limit', '512M');
    
    error_log("=== START: Opprydding av kurs og kursdatoer ===");
    
    // Hent alle kurs fra API
    $courses = kursagenten_get_course_list();
    if (empty($courses)) {
        error_log("FEIL: Kunne ikke hente kursliste fra API under opprydding");
        return false;
    }

    // Samle alle gyldige location_ids og schedule_ids fra API
    $valid_location_ids = [];
    $valid_schedule_ids = [];
    $api_course_details = [];
    
    foreach ($courses as $course) {
        // Skip internal courses: courses with mainCategory.id === 0 are internal courses
        $main_category_id = isset($course['mainCategory']['id']) ? (int) $course['mainCategory']['id'] : null;
        if ($main_category_id === 0) {
            error_log("Cleanup: Hopper over internkurs (mainCategory.id === 0): " . ($course['name'] ?? 'Ukjent navn') . " (ID: " . ($course['id'] ?? 'Ukjent') . ")");
            continue; // Skip this entire course (all its locations)
        }
        
        foreach ($course['locations'] as $location) {
            $location_id_int = (int) $location['courseId'];
            $valid_location_ids[] = $location_id_int;
            
            // Hent detaljer for hvert kurs
            $course_details = kursagenten_get_course_details($location_id_int);
            if (!empty($course_details)) {
                $api_course_details[$location_id_int] = [
                    'name' => $course_details['name'],
                    'first_course_date' => null,
                    'schedule_ids' => [],
                    'has_scheduled_dates' => false,
                    'is_online' => ($location['county'] === 'Nettbasert' || $location['place'] === 'Nettbasert')
                ];
                
                // Finn første kursdato og schedule_ids
                foreach ($course_details['locations'] as $loc) {
                    if ($loc['courseId'] == $location_id_int && !empty($loc['schedules'])) {
                        foreach ($loc['schedules'] as $schedule) {
                            if (!empty($schedule['id'])) {
                                $valid_schedule_ids[] = $schedule['id'];
                                $api_course_details[$location_id_int]['schedule_ids'][] = $schedule['id'];
                                $api_course_details[$location_id_int]['has_scheduled_dates'] = true;
                            }
                            if (!empty($schedule['firstCourseDate'])) {
                                $api_course_details[$location_id_int]['first_course_date'] = $schedule['firstCourseDate'];
                            }
                        }
                    }
                }
            }
        }
    }
    
    // Update transient with valid location IDs for future use
    set_transient('kursagenten_valid_location_ids', $valid_location_ids, HOUR_IN_SECONDS);
    
    error_log("=== API DATA ===");
    error_log("Gyldige location_ids fra API: " . implode(', ', $valid_location_ids));
    error_log("Antall gyldige location_ids fra API: " . count($valid_location_ids));
    error_log("Gyldige schedule_ids fra API: " . implode(', ', $valid_schedule_ids));
    error_log("Antall gyldige schedule_ids fra API: " . count($valid_schedule_ids));
    
    // Finn alle kurs i WordPress
    $wp_courses = get_posts([
        'post_type' => 'ka_course',
        'posts_per_page' => -1,
        'post_status' => ['publish', 'draft'],
    ]);
    
    error_log("=== WORDPRESS DATA ===");
    error_log("Antall kurs i WordPress: " . count($wp_courses));
    
    $deleted_courses = 0;
    $deleted_dates = 0;
    
    // Sjekk hvert kurs i WordPress
    foreach ($wp_courses as $wp_course) {
        $location_id = get_post_meta($wp_course->ID, 'ka_location_id', true);
        $location_id_int = (int) $location_id;
        
        // CRITICAL: Check if course exists in CourseList API
        // Courses not in CourseList are considered internal courses and should be deleted
        $exists_in_course_list = in_array($location_id_int, $valid_location_ids);
        
        if (!$exists_in_course_list) {
            // Course is not in CourseList API - it's either deleted or an internal course
            // Verify it's an internal course by checking single course API
            $single_course_check = kursagenten_get_course_details($location_id_int);
            if (!empty($single_course_check)) {
                // Course exists in single course API but not in CourseList = internal course
                error_log("=== SLETTING AV INTERNKURS ===");
                error_log("Kurs med location_id $location_id_int finnes i enkeltkurs API men ikke i CourseList API");
                error_log("Post ID: " . $wp_course->ID);
                error_log("Tittel: " . $wp_course->post_title);
                error_log("Status: " . $wp_course->post_status);
                
                // Check if mainCategory.id === 0 (additional indicator for internal course)
                if (isset($single_course_check['mainCategory']['id']) && (int)$single_course_check['mainCategory']['id'] === 0) {
                    error_log("Bekreftet internkurs: mainCategory.id === 0");
                }
            } else {
                // Course doesn't exist in either API - it's been deleted
                error_log("=== SLETTING AV SLETTET KURS ===");
                error_log("Kurs med location_id $location_id_int finnes ikke lenger i API");
                error_log("Post ID: " . $wp_course->ID);
                error_log("Tittel: " . $wp_course->post_title);
                error_log("Status: " . $wp_course->post_status);
            }
            //error_log("=== SLETTING AV KURS ===");
            //error_log("Kurs med location_id $location_id finnes ikke lenger i API");
            //error_log("Post ID: " . $wp_course->ID);
            //error_log("Tittel: " . $wp_course->post_title);
            //error_log("Status: " . $wp_course->post_status);
            
            // Finn og slett tilknyttede kursdatoer basert på location_id
            $related_dates = get_posts([
                'post_type' => 'ka_coursedate',
                'posts_per_page' => -1,
                'meta_query' => [
                    ['key' => 'ka_location_id', 'value' => $location_id_int],
                ],
            ]);
            
            foreach ($related_dates as $date) {
                wp_delete_post($date->ID, true);
                $deleted_dates++;
                error_log("Slettet kursdato ID: $date->ID");
            }
            
            // Slett selve kurset
            if (wp_delete_post($wp_course->ID, true)) {
                $deleted_courses++;
                error_log("Slettet kurs ID: {$wp_course->ID}");
            }
        } else {
            // Course exists in CourseList - check its course dates
            // Sjekk kursdatoer for dette kurset basert på location_id
            $related_dates = get_posts([
                'post_type' => 'ka_coursedate',
                'posts_per_page' => -1,
                'meta_query' => [
                    ['key' => 'ka_location_id', 'value' => $location_id_int],
                ],
            ]);
            
            // Track unique schedule_id + location_id combinations to detect duplicates
            $seen_combinations = [];
            
            foreach ($related_dates as $date) {
                $schedule_id = get_post_meta($date->ID, 'ka_schedule_id', true);
                
                // Create unique key for this combination
                $unique_key = $location_id_int . '_' . $schedule_id;
                
                // Check for duplicates - if we've seen this combination before, delete this one
                if (isset($seen_combinations[$unique_key])) {
                    error_log("=== SLETTING AV DUPLIKAT KURSDATO ===");
                    error_log("Kursdato ID: " . $date->ID);
                    error_log("Tittel: " . $date->post_title);
                    error_log("Location ID: " . $location_id_int);
                    error_log("Schedule ID: " . $schedule_id);
                    error_log("Dette er duplikat nummer " . ($seen_combinations[$unique_key] + 1));
                    
                    if (wp_delete_post($date->ID, true)) {
                        $deleted_dates++;
                        error_log("Slettet duplikat kursdato ID: " . $date->ID);
                    }
                    $seen_combinations[$unique_key]++;
                    continue; // Skip further checks for this duplicate
                }
                
                // Mark this combination as seen
                $seen_combinations[$unique_key] = 1;
                
                // Sjekk om vi skal slette kursdatoen
                $should_delete = false;
                
                if ($schedule_id === '0' || $schedule_id === 0) {
                    // For nettbaserte kurs, behold kursdatoen med schedule_id 0
                    if (isset($api_course_details[$location_id_int]) && !$api_course_details[$location_id_int]['is_online']) {
                        // For vanlige kurs, slett kun hvis API-et har minst én plan med en faktisk schedule_id
                        if ($api_course_details[$location_id_int]['has_scheduled_dates']) {
                            $should_delete = true;
                            error_log("Kursdato med schedule_id 0 skal slettes fordi API har minst én plan med en faktisk schedule_id");
                        }
                    } else {
                        error_log("Beholder kursdato med schedule_id 0 fordi det er et nettbasert kurs");
                    }
                } else if (!in_array($schedule_id, $valid_schedule_ids)) {
                    $should_delete = true;
                    error_log("Kursdato med schedule_id $schedule_id finnes ikke lenger i API");
                }
                
                if ($should_delete) {
                    error_log("=== SLETTING AV KURSDATO ===");
                    error_log("Kursdato ID: " . $date->ID);
                    error_log("Tittel: " . $date->post_title);
                    error_log("Status: " . $date->post_status);
                    error_log("Location ID: " . $location_id_int);
                    error_log("Schedule ID: " . $schedule_id);
                    
                    if (wp_delete_post($date->ID, true)) {
                        $deleted_dates++;
                        error_log("Slettet kursdato ID: " . $date->ID);
                    }
                }
            }
        }
    }
    
    error_log("=== OPPRYDDING FULLFØRT ===");
    error_log("Antall slettede kurs: $deleted_courses");
    error_log("Antall slettede kursdatoer: $deleted_dates");
    error_log("=== SLUTT: Opprydding av kurs og kursdatoer ===");
    
    return [
        'deleted_courses' => $deleted_courses,
        'deleted_dates' => $deleted_dates
    ];
}

// Legg til denne nye funksjonen først
function cleanup_all_specific_locations() {
    error_log("=== START: Rydder opp i alle specific_locations ===");
    
    $terms = get_terms([
        'taxonomy' => 'ka_course_location',
        'hide_empty' => false,
    ]);

    if (is_wp_error($terms)) {
        error_log("Feil ved henting av kurssteder: " . $terms->get_error_message());
        return false;
    }

    $cleaned_count = 0;
    foreach ($terms as $term) {
        if (delete_term_meta($term->term_id, 'specific_locations')) {
            $cleaned_count++;
           // error_log("Slettet specific_locations for term_id: " . $term->term_id);
        }
    }

    //error_log("Slettet specific_locations for $cleaned_count kurssteder");
    //error_log("=== SLUTT: Rydder opp i alle specific_locations ===");
    return true;
}

// Oppdater AJAX handler for å inkludere cleanup
function kursagenten_ajax_cleanup_courses() {
    check_ajax_referer('sync_kurs_nonce', 'nonce');
    
    $result = cleanup_courses_on_demand();
    
    if ($result === false) {
        wp_send_json_error(['message' => 'Kunne ikke utføre opprydding.']);
    } else {
        wp_send_json_success([
            'message' => sprintf(
                'Opprydding fullført. Slettet %d kurs og %d kursdatoer.',
                $result['deleted_courses'],
                $result['deleted_dates']
            )
        ]);
    }
}
add_action('wp_ajax_cleanup_courses', 'kursagenten_ajax_cleanup_courses');

// Registrer nattlig opprydding
function kursagenten_register_nightly_cleanup() {
    if (!wp_next_scheduled('kursagenten_nightly_cleanup')) {
        wp_schedule_event(strtotime('tomorrow 03:00:00'), 'daily', 'kursagenten_nightly_cleanup');
    }
}
add_action('wp', 'kursagenten_register_nightly_cleanup');

// Nattlig opprydding
function kursagenten_nightly_cleanup() {
    cleanup_courses_on_demand();
}
add_action('kursagenten_nightly_cleanup', 'kursagenten_nightly_cleanup');

// Deaktiver nattlig opprydding ved deaktivering av plugin
function kursagenten_deactivate_nightly_cleanup() {
    wp_clear_scheduled_hook('kursagenten_nightly_cleanup');
}
register_deactivation_hook(__FILE__, 'kursagenten_deactivate_nightly_cleanup');


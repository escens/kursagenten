<?php
// Function to display "Sync All Courses" button in admin options page
function kursagenten_sync_courses_button() {
    if (!current_user_can('manage_options')) {
        wp_die(__('Du har ikke tilgang til denne funksjonen.', 'kursagenten'));
    }

    ob_start();
    ?>
    <a href="#" class="button sync-api-to-posts" id="sync-all-courses">Synkroniser alle kurs</a>
    <div id="sync-status-message" style="margin-top: 10px;"></div>
    <?php
    return ob_get_clean();
}

// Function to display "Cleanup Courses" button in admin options page
function kursagenten_cleanup_courses_button() {
    ob_start();
    ?>
    <a href="#" class="button" id="cleanup-courses">Rydd opp i kurs</a>
    <div id="cleanup-status-message" style="margin-top: 10px;"></div>
    <?php
    return ob_get_clean();
}

function kursagenten_get_course_ids() {
    check_ajax_referer('sync_kurs_nonce', 'nonce');

    $courses = kursagenten_get_course_list();
    if (empty($courses)) {
        error_log("FEIL: Kunne ikke hente kursliste fra API");
        wp_send_json_error(['message' => 'Failed to fetch course data.']);
    }
        // Først rydd opp i alle specific_locations
        cleanup_all_specific_locations();

    error_log("=== START: Prosessering av kursliste ===");
    $course_data = [];
    foreach ($courses as $course) {
        error_log("Prosesserer kurs: " . $course['name']);
        
        foreach ($course['locations'] as $location) {
            //error_log("Prosesserer lokasjon: " . $location['municipality'] . " - " . $location['courseId']);
            $is_active = false;
            if (isset($location['active'])) {
                $is_active = filter_var($location['active'], FILTER_VALIDATE_BOOLEAN);
            } elseif (isset($course['active'])) {
                $is_active = filter_var($course['active'], FILTER_VALIDATE_BOOLEAN);
            } else {
                $is_active = true;
            }
            
            // Hent enkeltkursdata for denne lokasjonen
            $single_course = kursagenten_get_course_details($location['courseId']);
            
            if (empty($single_course)) {
                error_log("ADVARSEL: Kunne ikke hente enkeltkursdata for location_id: " . $location['courseId']);
                continue;
            }
            
            // Bruk single_course data direkte
            $course_data[] = [
                'location_id' => $location['courseId'],
                'main_course_id' => $course['id'],
                'course_name' => $location['courseName'],
                'municipality' => $location['municipality'],
                'county' => $location['county'],
                'language' => $course['language'],
                'is_active' => $is_active,
                'image_url_cms' => $location['cmsLogo'] ?? null,
                'single_course_data' => $single_course // Send med hele single_course data
            ];
        }
    }
    error_log("=== SLUTT: Prosessering av kursliste ===");

    kursagenten_update_main_course_status();

    wp_send_json_success(['courses' => $course_data]);
}
add_action('wp_ajax_get_course_ids', 'kursagenten_get_course_ids');

function kursagenten_run_sync_kurs() {
    check_ajax_referer('sync_kurs_nonce', 'nonce');
    error_log("================================================");
    error_log("=== START: Synkronisering av kurs ===");



    if (!isset($_POST['courses']) || !is_array($_POST['courses'])) {
        error_log("FEIL: Ugyldig kursdata mottatt");
        wp_send_json_error(['message' => 'Invalid course data.']);
    }

    error_log("--Antall kurs å synkronisere: " . count($_POST['courses']));
    $success_count = 0;
    $error_count = 0;

    foreach ($_POST['courses'] as $course) {
        try {
            error_log("--Synkroniserer kurs: " . ($course['course_name'] ?? 'Ukjent navn'));
            $course['is_active'] = filter_var($course['is_active'], FILTER_VALIDATE_BOOLEAN) ? '1' : '';
            
            if (create_or_update_course_and_schedule($course)) {
                $success_count++;
            } else {
                $error_count++;
                error_log("FEIL: Kunne ikke synkronisere kurs: " . ($course['course_name'] ?? 'Ukjent navn'));
            }
        } catch (Exception $e) {
            $error_count++;
            error_log("FEIL under synkronisering: " . $e->getMessage());
        }
    }

    error_log("Synkronisering fullført. Suksess: $success_count, Feil: $error_count");
    error_log("=== SLUTT: Synkronisering av kurs ===");

    error_log("Oppdaterer hovedkurs statuser etter fullført synkronisering");
    kursagenten_update_main_course_status();

    wp_send_json_success([
        'success_count' => $success_count,
        'error_count' => $error_count
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
        '1.0.1', 
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
    
    // Get course data
    $courses = kursagenten_get_course_list();
    if (empty($courses)) {
        error_log("FEIL: Kunne ikke hente kursliste fra API under nattlig synkronisering");
        return;
    }

    $success_count = 0;
    $error_count = 0;

    foreach ($courses as $course) {
        foreach ($course['locations'] as $location) {
            $is_active = false;
            if (isset($location['active'])) {
                $is_active = filter_var($location['active'], FILTER_VALIDATE_BOOLEAN);
            } elseif (isset($course['active'])) {
                $is_active = filter_var($course['active'], FILTER_VALIDATE_BOOLEAN);
            } else {
                $is_active = true;
            }

            // Hent enkeltkursdata for denne lokasjonen
            $single_course = kursagenten_get_course_details($location['courseId']);
            
            if (empty($single_course)) {
                error_log("ADVARSEL: Kunne ikke hente enkeltkursdata for location_id: " . $location['courseId']);
                continue;
            }
            
            $course_data = [
                'location_id' => $location['courseId'],
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

    // Synkroniser lokasjoner for alle kurssteder
    error_log("Starter synkronisering av lokasjoner");
    $terms = get_terms([
        'taxonomy' => 'course_location',
        'hide_empty' => false,
    ]);

    if (!is_wp_error($terms)) {
        foreach ($terms as $term) {
            sync_term_locations($term->term_id);
        }
    }

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
        foreach ($course['locations'] as $location) {
            $valid_location_ids[] = $location['courseId'];
            
            // Hent detaljer for hvert kurs
            $course_details = kursagenten_get_course_details($location['courseId']);
            if (!empty($course_details)) {
                $api_course_details[$location['courseId']] = [
                    'name' => $course_details['name'],
                    'first_course_date' => null,
                    'schedule_ids' => [],
                    'has_scheduled_dates' => false,
                    'is_online' => ($location['county'] === 'Nettbasert' || $location['place'] === 'Nettbasert')
                ];
                
                // Finn første kursdato og schedule_ids
                foreach ($course_details['locations'] as $loc) {
                    if ($loc['courseId'] == $location['courseId'] && !empty($loc['schedules'])) {
                        foreach ($loc['schedules'] as $schedule) {
                            if (!empty($schedule['id'])) {
                                $valid_schedule_ids[] = $schedule['id'];
                                $api_course_details[$location['courseId']]['schedule_ids'][] = $schedule['id'];
                                $api_course_details[$location['courseId']]['has_scheduled_dates'] = true;
                            }
                            if (!empty($schedule['firstCourseDate'])) {
                                $api_course_details[$location['courseId']]['first_course_date'] = $schedule['firstCourseDate'];
                            }
                        }
                    }
                }
            }
        }
    }
    
    error_log("=== API DATA ===");
    error_log("Gyldige location_ids fra API: " . implode(', ', $valid_location_ids));
    error_log("Antall gyldige location_ids fra API: " . count($valid_location_ids));
    error_log("Gyldige schedule_ids fra API: " . implode(', ', $valid_schedule_ids));
    error_log("Antall gyldige schedule_ids fra API: " . count($valid_schedule_ids));
    
    // Finn alle kurs i WordPress
    $wp_courses = get_posts([
        'post_type' => 'course',
        'posts_per_page' => -1,
        'post_status' => ['publish', 'draft'],
    ]);
    
    error_log("=== WORDPRESS DATA ===");
    error_log("Antall kurs i WordPress: " . count($wp_courses));
    
    $deleted_courses = 0;
    $deleted_dates = 0;
    
    // Sjekk hvert kurs i WordPress
    foreach ($wp_courses as $wp_course) {
        $location_id = get_post_meta($wp_course->ID, 'location_id', true);
        
        if (!in_array($location_id, $valid_location_ids)) {
            //error_log("=== SLETTING AV KURS ===");
            //error_log("Kurs med location_id $location_id finnes ikke lenger i API");
            //error_log("Post ID: " . $wp_course->ID);
            //error_log("Tittel: " . $wp_course->post_title);
            //error_log("Status: " . $wp_course->post_status);
            
            // Finn og slett tilknyttede kursdatoer basert på location_id
            $related_dates = get_posts([
                'post_type' => 'coursedate',
                'posts_per_page' => -1,
                'meta_query' => [
                    ['key' => 'location_id', 'value' => $location_id],
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
            // Sjekk kursdatoer for dette kurset basert på location_id
            $related_dates = get_posts([
                'post_type' => 'coursedate',
                'posts_per_page' => -1,
                'meta_query' => [
                    ['key' => 'location_id', 'value' => $location_id],
                ],
            ]);
            
            foreach ($related_dates as $date) {
                $schedule_id = get_post_meta($date->ID, 'schedule_id', true);
                
                // Sjekk om vi skal slette kursdatoen
                $should_delete = false;
                
                if ($schedule_id === '0' || $schedule_id === 0) {
                    // For nettbaserte kurs, behold kursdatoen med schedule_id 0
                    if (!$api_course_details[$location_id]['is_online']) {
                        // For vanlige kurs, slett kun hvis API-et har minst én plan med en faktisk schedule_id
                        if ($api_course_details[$location_id]['has_scheduled_dates']) {
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
                    error_log("Location ID: " . $location_id);
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
        'taxonomy' => 'course_location',
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


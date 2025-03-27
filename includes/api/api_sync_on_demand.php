<?php
// Function to display "Sync All Courses" button in admin options page
function kursagenten_sync_courses_button() {
    ob_start();
    ?>
    <a href="#" class="button sync-api-to-posts" id="sync-all-courses">Synkroniser alle kurs</a>
    <div id="sync-status-message" style="margin-top: 10px;"></div>
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

    error_log("=== START: Prosessering av kursliste ===");
    $course_data = [];
    foreach ($courses as $course) {
        error_log("Prosesserer kurs: " . $course['name']);
        
        foreach ($course['locations'] as $location) {
            error_log("Prosesserer lokasjon: " . $location['municipality'] . " - " . $location['courseId']);
            $is_active = false;
            if (isset($location['active'])) {
                $raw_value = $location['active'];
                //error_log("--Raw active verdi før konvertering for: " . print_r($raw_value, true));
                $is_active = filter_var($raw_value, FILTER_VALIDATE_BOOLEAN);
                //error_log("--Konvertert active verdi: " . ($is_active ? 'true' : 'false'));
            } elseif (isset($course['active'])) {
                $is_active = filter_var($course['active'], FILTER_VALIDATE_BOOLEAN);
                //error_log("--Fant active status i hovedkurs for kurs {$location['courseName']}: " . ($is_active ? 'true' : 'false'));
            } else {
                $is_active = true; // Default til true hvis ingen verdi er satt
                //error_log("--Ingen active status funnet for kurs {$location['courseName']}, setter default: true");
            }
            
            // Logg bildedata fra kursliste API
            if (!empty($location['cmsLogo'])) {
                //error_log("--Kursliste API - Fant cmsLogo for kurs '{$location['courseName']}': " . $location['cmsLogo']);
            }
            
            // Hent enkeltkursdata for å sjekke bannerImage
            $single_course = kursagenten_get_course_details($location['courseId']);
            if (!empty($single_course)) {
                if (!empty($single_course['bannerImage'])) {
                    //error_log("--Enkeltkurs API - Fant bannerImage for kurs '{$location['courseName']}': " . $single_course['bannerImage']);
                }
                
                // Sjekk også etter bilder i locations array fra enkeltkurs API
                foreach ($single_course['locations'] as $loc) {
                    if ($loc['courseId'] == $location['courseId'] && !empty($loc['bannerImage'])) {
                        //error_log("--Enkeltkurs API - Fant lokasjonsspesifikt bannerImage for kurs '{$location['courseName']}': " . $loc['bannerImage']);
                    }
                }
            }
            
            $course_data[] = [
                'location_id' => $location['courseId'],
                'main_course_id' => $course['id'],
                'course_name' => $location['courseName'],
                'municipality' => $location['municipality'],
                'county' => $location['county'],
                'language' => $course['language'],
                'is_active' => $is_active,
                'image_url_cms' => $location['cmsLogo'] ?? null,  // Legg til cmsLogo
            ];
        }
    }
    error_log("=== SLUTT: Prosessering av kursliste ===");

    //error_log("Oppdaterer hovedkurs statuser etter fullført synkronisering");
    kursagenten_update_main_course_status();

    wp_send_json_success(['courses' => $course_data]);
}
add_action('wp_ajax_get_course_ids', 'kursagenten_get_course_ids');

function kursagenten_run_sync_kurs() {
    check_ajax_referer('sync_kurs_nonce', 'nonce');
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


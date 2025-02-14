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

    $courses = kursagenten_get_course_list(); // Hent liste fra kursliste API
    if (empty($courses)) {
        wp_send_json_error(['message' => 'Failed to fetch course data.']);
    }

    // Pakk nødvendig data for hvert kurs i en struktur
    $course_data = [];
    foreach ($courses as $course) {
        foreach ($course['locations'] as $location) {
            $course_data[] = [
                'location_id' => $location['courseId'],  // Unik ID for lokasjon
                'main_course_id' => $course['id'],      // ID for hovedkurset
                'course_name' => $location['courseName'],  // Navn på kurset (fra lokasjon)
                'municipality' => $location['municipality'],
                'county' => $location['county'], 
                'language' => $course['language'], 
            ];
        }
    }
    

    wp_send_json_success(['courses' => $course_data]);
}
add_action('wp_ajax_get_course_ids', 'kursagenten_get_course_ids');

function kursagenten_run_sync_kurs() {
    check_ajax_referer('sync_kurs_nonce', 'nonce');

    if (!isset($_POST['courses']) || !is_array($_POST['courses'])) {
        wp_send_json_error(['message' => 'Invalid course data.']);
    }

    foreach ($_POST['courses'] as $course) {
        try {
            create_or_update_course_and_schedule($course);
            error_log("Kurs synkronisert: " . print_r($course, true));
        } catch (Exception $e) {
            error_log("Feil under synkronisering: " . $e->getMessage());
        }
    }

    wp_send_json_success();
}
add_action('wp_ajax_run_sync_kurs', 'kursagenten_run_sync_kurs');






// Enqueue admin JavaScript for handling sync button functionality

function kursagenten_enqueue_admin_scripts($hook) {
    if ('toplevel_page_kursinnstillinger' !== $hook) {
        //return;
    }
    wp_enqueue_script(
        'kursagenten-admin-sync', 
        KURSAG_PLUGIN_URL . '/admin/js/kursagenten-admin-sync.js', 
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


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
        wp_send_json_error(['message' => 'Failed to fetch course data.']);
    }

    $course_data = [];
    foreach ($courses as $course) {
        foreach ($course['locations'] as $location) {
            $is_active = false;
            if (isset($location['active'])) {
                $is_active = filter_var($location['active'], FILTER_VALIDATE_BOOLEAN);
            } else {
                $is_active = true; // Default til true hvis ingen verdi er satt
            }
            
            $course_data[] = [
                'location_id' => $location['courseId'],
                'main_course_id' => $course['id'],
                'course_name' => $location['courseName'],
                'municipality' => $location['municipality'],
                'county' => $location['county'],
                'language' => $course['language'],
                'is_active' => $is_active,
                //'image_url_alt' => $location['cmsLogo'],
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
            // Konverter is_active til 1 eller tom streng for å matche webhook-formatet
            $course['is_active'] = filter_var($course['is_active'], FILTER_VALIDATE_BOOLEAN) ? '1' : '';
            create_or_update_course_and_schedule($course);
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


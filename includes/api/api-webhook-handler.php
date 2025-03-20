<?php

// Registrer REST API-endepunkt
function register_custom_webhook_endpoint() {
    register_rest_route('kursagenten-api/v1', '/process-webhook', array(
        'methods' => 'POST',
        'callback' => 'process_webhook_data',
        'permission_callback' => '__return_true',
    ));
}
add_action('rest_api_init', 'register_custom_webhook_endpoint');

function process_webhook_data($request) {
    $body = json_decode($request->get_body(), true);

    if (!isset($body['CourseId']) || !is_numeric($body['CourseId'])) {
        return new WP_REST_Response('Invalid CourseId provided.', 400);
    }

    $location_id = (int) $body['CourseId'];

    /*if (get_transient('webhook_processed_' . $location_id)) {
        error_log("Skipping duplicate webhook for CourseId: $location_id");
        return new WP_REST_Response('Duplicate webhook skipped.', 200);
    }
    set_transient('webhook_processed_' . $location_id, true, 10);*/

    // Hent main_course data basert på location_id
    $course_data = get_main_course_id_by_location_id($location_id);

    if (!$course_data) {
        error_log("DEBUG: location_id {$location_id} finnes ikke i API-et.");
        return new WP_REST_Response('Location ID not found in API.', 404);
    }

    // Legg til location_id i course_data for fullstendig datastruktur
    $course_data['location_id'] = $location_id;

    try {
        $result = create_or_update_course_and_schedule($course_data, true);

        if ($result) {
            return new WP_REST_Response('Webhook processed successfully.', 200);
        } else {
            return new WP_REST_Response('Failed to process course.', 500);
        }
    } catch (Exception $e) {
        error_log("Error processing webhook: " . $e->getMessage());
        return new WP_REST_Response('Error processing webhook.', 500);
    }
}


function get_main_course_id_by_location_id($location_id) {
    $courses = kursagenten_get_course_list();
    if (empty($courses)) {
        return false;
    }

    foreach ($courses as $course) {
        foreach ($course['locations'] as $location) {
            if ((int) $location['courseId'] === (int) $location_id) {
                return [
                    'main_course_id' => $course['id'],
                    'course_name' => $location['courseName'],
                    'municipality' => $location['municipality'],
                    'county' => $location['county'],
                    'language' => $course['language'],
                    'is_active' => $location['active'] ?? true,
                ];
            }
        }
    }

    return false;
}

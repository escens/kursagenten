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
    $count_key = 'webhook_count_' . $location_id;
    $webhook_data_key = 'webhook_data_' . $location_id;
    
    // Hent eller initialiser teller
    $count = (int)get_transient($count_key) ?: 0;
    $count++;
    
    error_log("Webhook received for CourseId: $location_id (Count: $count)");
    //error_log("Webhook data: " . json_encode($body));
    
    // Lagre webhook data og øk telleren
    set_transient($count_key, $count, 3); // Hold telleren i 3 sekunder
    
    // Lagre webhook data
    $stored_webhooks = get_transient($webhook_data_key) ?: [];
    $stored_webhooks[] = [
        'body' => $body,
        'time' => time()
    ];
    set_transient($webhook_data_key, $stored_webhooks, 3);
    error_log("Stored webhooks count: " . count($stored_webhooks));
    
    // Vent med prosessering hvis dette er første webhook og den ikke har Enabled
    if ($count === 1 && !isset($body['Enabled'])) {
        //error_log("First webhook received without Enabled parameter - waiting for potential second webhook");
        return new WP_REST_Response('Waiting for additional webhooks.', 200);
    }
    
    // Hvis dette er andre webhook, eller hvis det er første OG har Enabled
    if ($count === 2 || isset($body['Enabled'])) {
        //error_log("Processing webhook(s) for CourseId: $location_id");
        
        // Bruk webhook med Enabled hvis den finnes
        $webhook_to_process = $body;
        if ($count === 2) {
            $stored_webhooks = get_transient($webhook_data_key) ?: [];
            foreach ($stored_webhooks as $stored_webhook) {
                if (isset($stored_webhook['body']['Enabled'])) {
                    $webhook_to_process = $stored_webhook['body'];
                    //error_log("Found webhook with Enabled parameter - using this for processing");
                    break;
                }
            }
        }
        
        //error_log("Final webhook being processed: " . json_encode($webhook_to_process));
        
        // Slett transients
        delete_transient($count_key);
        delete_transient($webhook_data_key);
        //error_log("Cleared temporary webhook data");
        
        // Fortsett med prosessering
        $course_data = get_main_course_id_by_location_id($location_id);
        
        if (!$course_data) {
            error_log("DEBUG: location_id {$location_id} finnes ikke i API-et.");
            return new WP_REST_Response('Location ID not found in API.', 404);
        }

        $course_data['location_id'] = $location_id;
        
        try {
            error_log("Starting course processing for CourseId: $location_id");
            $result = create_or_update_course_and_schedule($course_data, true);
            if ($result) {
                error_log("Successfully processed course update for CourseId: $location_id");
                
                // Oppdater hovedkurs status etter vellykket oppdatering
                $main_course_id = $course_data['main_course_id'] ?? null;
                error_log("Oppdaterer hovedkurs status for main_course_id: $main_course_id");
                kursagenten_update_main_course_status($main_course_id);
                
                return new WP_REST_Response('Webhook processed successfully.', 200);
            } else {
                error_log("Failed to process course update for CourseId: $location_id");
                return new WP_REST_Response('Failed to process course.', 500);
            }
        } catch (Exception $e) {
            error_log("Error processing webhook: " . $e->getMessage());
            return new WP_REST_Response('Error processing webhook.', 500);
        }
    }
    
    error_log("Webhook received and stored for CourseId: $location_id - awaiting processing");
    return new WP_REST_Response('Webhook received.', 200);
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

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
    $last_webhook_time_key = 'last_webhook_time_' . $location_id;
    
    // Sjekk om vi har mottatt en webhook for dette kurset nylig
    $last_webhook_time = get_transient($last_webhook_time_key);
    $current_time = time();
    
    if ($last_webhook_time && ($current_time - $last_webhook_time) < 3) {
        // Hvis vi har mottatt en webhook for dette kurset innen 3 sekunder,
        // og denne webhooken ikke har Enabled parameter, ignorer den
        if (!isset($body['Enabled'])) {
            error_log("Ignoring duplicate webhook for CourseId: $location_id (within 3 seconds)");
            return new WP_REST_Response('Duplicate webhook ignored.', 200);
        }
    }
    
    // Oppdater siste webhook tid
    set_transient($last_webhook_time_key, $current_time, 3);
    
    // Hent eller initialiser teller
    $count = (int)get_transient($count_key) ?: 0;
    $count++;
    
    error_log("Webhook received for CourseId: $location_id (Count: $count)");
    
    // Lagre webhook data og øk telleren
    set_transient($count_key, $count, 3);
    
    // Lagre webhook data
    $stored_webhooks = get_transient($webhook_data_key) ?: [];
    $stored_webhooks[] = [
        'body' => $body,
        'time' => $current_time
    ];
    set_transient($webhook_data_key, $stored_webhooks, 3);
    
    // Slett kurs umiddelbart hvis Deleted=true
    if (isset($body['Deleted']) && $body['Deleted'] === true) {
        error_log("Webhook indicates deletion for CourseId: $location_id");
        if (function_exists('kursagenten_delete_course_by_location_id')) {
            $deleted = kursagenten_delete_course_by_location_id($location_id);
            if ($deleted) {
                // Oppdater hovedkurs etter sletting
                // Finn main_course_id via eksisterende liste, hvis mulig
                $course_data = get_main_course_id_by_location_id($location_id);
                $main_course_id = $course_data['main_course_id'] ?? null;
                if ($main_course_id && function_exists('kursagenten_update_main_course_status')) {
                    kursagenten_update_main_course_status($main_course_id);
                }
                return new WP_REST_Response('Course deleted successfully.', 200);
            }
        }
        return new WP_REST_Response('Course not found or already deleted.', 200);
    }

    // Prosesser webhook hvis:
    // 1. Den har Enabled parameter (kurs-oppdatering)
    // 2. Det er første webhook for dette kurset (instruktør-oppdatering)
    if (isset($body['Enabled']) || $count === 1) {
        error_log("Processing webhook for CourseId: $location_id" . (isset($body['Enabled']) ? " (with Enabled)" : " (first webhook)"));
        
        // Bruk webhook med Enabled hvis den finnes
        $webhook_to_process = $body;
        if (isset($body['Enabled'])) {
            // Slett transients siden vi prosesserer nå
            delete_transient($count_key);
            delete_transient($webhook_data_key);
            delete_transient($last_webhook_time_key);
        }
        
        // Fortsett med prosessering
        $course_data = get_main_course_id_by_location_id($location_id);
        
        if (!$course_data) {
            error_log("DEBUG: location_id {$location_id} finnes ikke i API-et.");
            return new WP_REST_Response('Location ID not found in API.', 404);
        }

        $course_data['location_id'] = $location_id;
        // If webhook includes Enabled, override is_active from API list to avoid race conditions
        if (isset($body['Enabled'])) {
            $course_data['is_active'] = (bool) $body['Enabled'];
        }
        
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

    $main_course_id = null;
    $course_data = null;
    $all_locations = [];
    $target_municipality = null;
    $target_county = null;

    // Finn først hovedkurset og target location
    foreach ($courses as $course) {
        foreach ($course['locations'] as $location) {
            if ((int) $location['courseId'] === (int) $location_id) {
                $main_course_id = $course['id'];
                $target_municipality = $location['municipality'] ?? null;
                $target_county = $location['county'] ?? null;
                $course_data = [
                    'main_course_id' => $course['id'],
                    'course_name' => $location['courseName'],
                    'municipality' => $target_municipality,
                    'county' => $target_county,
                    'language' => $course['language'],
                    'is_active' => $location['active'] ?? false,
                    'all_locations' => []
                ];
                break 2; // Bryt ut av begge løkkene
            }
        }
    }

    // Hvis vi fant kurset, samle lokasjoner fra samme område
    if ($course_data && ($target_municipality || $target_county)) {
        foreach ($courses as $course) {
            foreach ($course['locations'] as $location) {
                // Sjekk om lokasjonen er i samme område
                $location_matches = false;
                
                if ($target_municipality && !empty($location['municipality'])) {
                    $location_matches = ($location['municipality'] === $target_municipality);
                } elseif ($target_county && !empty($location['county'])) {
                    $location_matches = ($location['county'] === $target_county);
                }

                if ($location_matches) {
                    $description = $location['placeFreeText'] ?? '';
                    if (!empty($description)) {
                        // Behold original adressestruktur
                        $location_info = [
                            'description' => $description,
                            'address' => [
                                'streetAddress' => $location['address']['streetAddress'] ?? '',
                                'streetAddressNumber' => $location['address']['streetAddressNumber'] ?? '',
                                'zipCode' => $location['address']['zipCode'] ?? '',
                                'place' => $location['address']['place'] ?? ''
                            ]
                        ];
                        
                        // Unngå duplikater
                        $exists = false;
                        foreach ($all_locations as $existing) {
                            if ($existing['description'] === $description) {
                                $exists = true;
                                break;
                            }
                        }
                        
                        if (!$exists) {
                            $all_locations[] = $location_info;
                        }
                    }
                }
            }
        }
        
        $course_data['all_locations'] = $all_locations;
        $area_type = $target_municipality ? 'kommune' : 'fylke';
        $area_name = $target_municipality ?: $target_county;
        //error_log("Fant " . count($all_locations) . " lokasjoner for " . $area_type . ": " . $area_name);
        return $course_data;
    }

    return false;
}

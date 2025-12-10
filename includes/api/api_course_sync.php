<?php

function create_or_update_course_and_schedule($course_data, $is_webhook = false) {
    error_log("=== START: create_or_update_course_and_schedule function ===");
    
    // 1. Initialiser grunnleggende variabler
    $location_id = isset($course_data['location_id']) ? (int) $course_data['location_id'] : 0;
    $main_course_id = isset($course_data['main_course_id']) ? (int) $course_data['main_course_id'] : 0;
    $language = sanitize_text_field($course_data['language'] ?? null); 
    $is_active = $course_data['is_active'] ?? true;
    
    // Check if this location_id is in the valid CourseList (prevents syncing internal courses)
    // For webhooks: We still check CourseList, but webhooks might be legitimate updates
    // However, if course is not in CourseList at all, it's an internal course and should be skipped
    $valid_location_ids = get_transient('kursagenten_valid_location_ids');
    if ($valid_location_ids === false || empty($valid_location_ids)) {
        // Transient expired or empty, rebuild from CourseList API
        error_log("Transient utløpt eller tom i create_or_update_course_and_schedule, henter CourseList på nytt");
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
    }
    
    // CRITICAL: Must check if location_id is in valid list before proceeding
    // This applies to both regular sync and webhooks to prevent internal courses
    if (!in_array($location_id, $valid_location_ids)) {
        if ($is_webhook) {
            error_log("ADVARSEL: Webhook hopper over internkurs med location_id: $location_id (finnes ikke i CourseList API)");
        } else {
            error_log("ADVARSEL: Hopper over internkurs med location_id: $location_id (finnes ikke i CourseList API)");
        }
        return false;
    }
    
    // Hvis vi har single_course_data, bruk det direkte
    $individual_course_data = $course_data['single_course_data'] ?? null;
    
    // Hvis vi ikke har single_course_data (f.eks. fra webhook), hent det
    if (empty($individual_course_data)) {
        error_log("Henter enkeltkursdata for location_id: $location_id");
        $individual_course_data = kursagenten_get_course_details($location_id);
        if (empty($individual_course_data)) {
            error_log("FEIL: Kunne ikke hente kursdetaljer for location_id: $location_id");
            return false;
        }
    }
    
    // Additional safety check: Verify course is still in CourseList after fetching details
    // This prevents internal courses that might have been fetched via single course API
    if (!$is_webhook) {
        $valid_location_ids_check = get_transient('kursagenten_valid_location_ids');
        if ($valid_location_ids_check === false || empty($valid_location_ids_check)) {
            // Rebuild transient if needed
            $course_list_check = kursagenten_get_course_list();
            $valid_location_ids_check = [];
            if (!empty($course_list_check)) {
                foreach ($course_list_check as $course_item) {
                    foreach ($course_item['locations'] as $location) {
                        $valid_location_ids_check[] = (int) $location['courseId'];
                    }
                }
            }
            set_transient('kursagenten_valid_location_ids', $valid_location_ids_check, HOUR_IN_SECONDS);
        }
        
        if (!in_array($location_id, $valid_location_ids_check)) {
            error_log("ADVARSEL: Kurs med location_id: $location_id ble funnet i enkeltkurs API men ikke i CourseList - hopper over (internkurs)");
            return false;
        }
    }
    
    // Håndter all_locations kun for webhook-kall
    if ($is_webhook && !empty($course_data['all_locations'])) {
        error_log("Bruker all_locations fra webhook-data");
        $individual_course_data['all_locations'] = $course_data['all_locations'];
    }
    
    error_log("Location ID: $location_id, Main Course ID: $main_course_id");

    // 3. Forbered location data
    $location_data = prepare_location_data($individual_course_data, $location_id);
    $total_locations = count($individual_course_data['locations'] ?? []);

    // 4. Finn eksisterende hovedkurs
    $main_course = get_existing_main_course($main_course_id);
    $is_main_course_location = ((int)$location_id === (int)$main_course_id);

    // 5. Hovedlogikk
    if ($main_course) {
        // Hovedkurset eksisterer
        
        if ($is_main_course_location) {
            // Oppdater hovedkurset
            update_existing_course($main_course[0]->ID, $location_data, $main_course_id, $location_id, $language, $is_active, $is_webhook);
            
            // Finn og oppdater subkurs for hovedkursets lokasjon
            $main_course_sub_course = get_existing_sub_course($location_id, $main_course_id);
            if ($main_course_sub_course) {
                // Oppdater eksisterende subkurs
                update_existing_course($main_course_sub_course[0]->ID, $location_data, $main_course_id, $location_id, $language, $is_active, $is_webhook);
            } else {
                // Opprett subkurs hvis det ikke eksisterer
                create_new_sub_course($location_data, $main_course_id, $location_id, $language, $is_active, $is_webhook);
            }
            
            return $main_course[0]->ID;
        } else {
            // Finn og oppdater/opprett subkurs
            $existing_sub_course = get_existing_sub_course($location_id, $main_course_id);
            
            if ($existing_sub_course) {
                // Oppdater eksisterende subkurs
                update_existing_course($existing_sub_course[0]->ID, $location_data, $main_course_id, $location_id, $language, $is_active, $is_webhook);
                return $existing_sub_course[0]->ID;
            } else {
                // Opprett nytt subkurs
                return create_new_sub_course($location_data, $main_course_id, $location_id, $language, $is_active, $is_webhook);
            }
        }
    } else {
        // Hovedkurset eksisterer ikke
        if ($is_main_course_location) {
            // Opprett hovedkurs
            $main_post_id = create_new_course($location_data, $main_course_id, $location_id, $language, $is_active, $is_webhook);
            
            // Opprett subkurs for hovedkursets lokasjon
            $main_sub_location_data = prepare_location_data($individual_course_data, $location_id);
            create_new_sub_course($main_sub_location_data, $main_course_id, $location_id, $language, $is_active, $is_webhook);
            
            return $main_post_id;
        } else {
            // Opprett hovedkurs først, deretter subkurs
            $main_course_data = kursagenten_get_course_details($main_course_id);
            if (!empty($main_course_data)) {
                $main_location_data = prepare_location_data($main_course_data, $main_course_id);
                $main_post_id = create_new_course($main_location_data, $main_course_id, $main_course_id, $language, $is_active, $is_webhook);
                
                // Opprett subkurs for hovedkursets lokasjon
                create_new_sub_course($main_location_data, $main_course_id, $main_course_id, $language, $is_active, $is_webhook);
                
                // Opprett subkurs for denne lokasjonen
                return create_new_sub_course($location_data, $main_course_id, $location_id, $language, $is_active, $is_webhook);
            }
        }
    }

    //error_log("=== SLUTT: create_or_update_course_and_schedule ===");
    return false;
}

// Hjelpefunksjoner
function prepare_location_data($course_data, $location_id) {
    $location_data = $course_data;
    foreach ($course_data['locations'] as $loc) {
        if ($loc['courseId'] == $location_id) {
            $location_data['locations'] = [$loc];
            if (isset($loc['bannerImage'])) {
                $location_data['bannerImage'] = $loc['bannerImage'];
            }
            break;
        }
    }
    return $location_data;
}

function get_existing_main_course($main_course_id) {
    return get_posts([
        'post_type' => 'ka_course',
        'meta_query' => [
            'relation' => 'AND',
            [
                'key' => 'ka_main_course_id',
                'value' => $main_course_id,
                'compare' => '='
            ],
            [
                'key' => 'ka_is_parent_course',
                'value' => 'yes',
                'compare' => '='
            ]
        ],
        'post_status' => ['publish', 'draft'],
        'posts_per_page' => 1
    ]);
}

function get_existing_sub_course($location_id, $main_course_id) {
    //error_log("Søker etter subkurs med location_id: $location_id og main_course_id: $main_course_id");
    
    return get_posts([
        'post_type' => 'ka_course',
        'meta_query' => [
            'relation' => 'AND',
            [
                'key' => 'ka_location_id',
                'value' => $location_id,
                'compare' => '='
            ],
            [
                'key' => 'ka_main_course_id',
                'value' => $main_course_id,
                'compare' => '='
            ],
            // Sjekk at is_parent_course IKKE eksisterer
            [
                'key' => 'ka_is_parent_course',
                'compare' => 'NOT EXISTS'
            ]
        ],
        'post_status' => ['publish', 'draft'],
        'posts_per_page' => 1
    ]);
}

function create_new_course($data, $main_course_id, $location_id, $language, $is_active, $is_webhook = false) {
    //error_log("=== Start create_new_course with location_id: $location_id and is_active: $is_active");
    $post_status = $is_active ? 'publish' : 'draft';

    $post_id = wp_insert_post([
        'post_title'   => sanitize_text_field($data['name']),
        'post_type'    => 'ka_course',
        'post_status'  => $post_status,
        'post_excerpt' => sanitize_text_field($data['introText']),
    ]);

    if (!is_wp_error($post_id)) {
        // Update shared metadata
        $common_meta_fields = get_common_meta_fields($data, $language);
        foreach ($common_meta_fields as $key => $value) {
            update_post_meta($post_id, $key, $value);
        }
        update_post_meta($post_id, 'ka_main_course_id', (int) $data['id']);
        update_post_meta($post_id, 'ka_is_parent_course', 'yes');
        update_post_meta($post_id, 'ka_meta_description', sanitize_text_field($data['introText']));
        // Foreldrekurs har ikke is_active - statusen baseres på underkurs
        update_post_meta($post_id, 'ka_location_id', (int) $data['id']);

        update_course_taxonomies($post_id, $location_id, $data, $is_webhook);
                    
        // Associate instructor taxonomy
        $instructors = get_instructors_in_courselist($data, $location_id);
        $location_instructors = $instructors['instructors_location'];
        update_instructor_taxonomies($post_id, $location_instructors);

        sync_main_course_data($main_course_id);

        set_featured_image_from_url($data, $post_id, $main_course_id, $location_id, get_course_location($data));

        // Use the correct location id when creating/updating coursedates
        create_or_update_course_date($data, $post_id, $main_course_id, $location_id, $is_active);
    }

    return $post_id;
}

function create_new_sub_course($data, $main_course_id, $location_id, $language, $is_active, $is_webhook = false) {
    //error_log("=== START: create_new_sub_course ===");
    //error_log("Main Course ID: $main_course_id");
    //error_log("Location ID: $location_id");
    //error_log("Is Active: $is_active");
    // Check if parent course exists
    $parent_course = get_posts([
        'post_type' => 'ka_course',
        'post_status' => ['publish', 'draft'],
        'meta_query' => [
            [
                'key' => 'ka_location_id',
                'value' => $main_course_id,
                'compare' => '='
            ],
            [
                'key' => 'ka_is_parent_course',
                'value' => 'yes',
                'compare' => '='
            ]
        ],
        'posts_per_page' => 1,
    ]);

    if (empty($parent_course)) {
        //error_log("ADVARSEL: Fant ikke hovedkurs for main_course_id: $main_course_id");
        //error_log("Prøver å opprette hovedkurs først...");
        
        // Hent data for hovedkurset og opprett det
        $main_course_data = kursagenten_get_course_details($main_course_id);
        if (!empty($main_course_data)) {
            $parent_id = create_new_course($main_course_data, $main_course_id, $main_course_id, $language, $is_active, $is_webhook);
            //error_log("Opprettet hovedkurs med ID: $parent_id");
        } else {
            //error_log("FEIL: Kunne ikke hente data for hovedkurset");
            return false;
        }
    } else {
        $parent_id = $parent_course[0]->ID;
        //error_log("Fant eksisterende hovedkurs med ID: $parent_id");
    }
    $post_status = $is_active ? 'publish' : 'draft';
    // Create sub-course
    $post_id = wp_insert_post([
        'post_title'   => sanitize_text_field($data['name'] . ' - ' . get_course_location($data)),
        'post_type'    => 'ka_course',
        'post_status'  => $post_status,
        'post_parent'  => (int) $parent_id,
        'post_excerpt' => sanitize_text_field($data['introText']),
        'post_name'    => get_course_location($data),
    ]);

    if (!is_wp_error($post_id)) {
        // Update shared metadata
        $common_meta_fields = get_common_meta_fields($data, $language);
        foreach ($common_meta_fields as $key => $value) {
            update_post_meta($post_id, $key, $value);
        }
        update_post_meta($post_id, 'ka_main_course_id', (int) $main_course_id);
        update_post_meta($post_id, 'ka_main_course_title', sanitize_text_field($data['name']));
        update_post_meta($post_id, 'ka_sub_course_location', sanitize_text_field(get_course_location($data)));
        update_post_meta($post_id, 'ka_meta_description', sanitize_text_field($data['introText']));
        update_post_meta($post_id, 'ka_is_active', $is_active ? '1' : '0');
        update_post_meta($post_id, 'ka_location_id', (int) $location_id);
        
        // Sett course_location_freetext basert på lokasjonsdata - men ikke for nettkurs
        //$location_name = get_course_location($data);
        $is_online = !empty($data['isOnlineCourse']) && ($data['isOnlineCourse'] === true || $data['isOnlineCourse'] === 'true' || $data['isOnlineCourse'] === 1 || $data['isOnlineCourse'] === '1');
        if (!$is_online && !empty($data['locations'][0]['description'])) {
            update_post_meta($post_id, 'ka_course_location_freetext', sanitize_text_field($data['locations'][0]['description']));
        } else {
            // Remove location_freetext if course is online
            delete_post_meta($post_id, 'ka_course_location_freetext');
        }

        // Pass the actual location id, not the main course id
        update_course_taxonomies($post_id, $location_id, $data, $is_webhook);

        // Fetch instructor data
        // Use the correct location id for instructor extraction
        $instructors = get_instructors_in_courselist($data, $location_id);
        $location_instructors = $instructors['instructors_location'];
        update_instructor_taxonomies($post_id, $location_instructors);

        sync_main_course_data($main_course_id);

        set_featured_image_from_url($data, $post_id, $main_course_id, $location_id, get_course_location($data));

        // Use the correct location id when creating/updating coursedates
        create_or_update_course_date($data, $post_id, $main_course_id, $location_id, $is_active);
    }

    //error_log("=== SLUTT: create_new_sub_course ===");
    return $post_id;
}

function update_existing_course($post_id, $data, $main_course_id, $location_id, $language, $is_active, $is_webhook = false) {
    //error_log("=== Start update_existing_course with post_id: $post_id/location_id: $location_id/main_course_id: $main_course_id and is_active: $is_active");
    $is_parent_course = get_post_meta($post_id, 'ka_is_parent_course', true);
    $total_locations = count($data['locations'] ?? []);

    if ($is_parent_course === 'yes') {
        // Hovedkurs skal også respektere is_active statusen
        $updated_title = $data['name'];
        $post_status = $is_active ? 'publish' : 'draft';
        error_log("Updating main course with multiple locations {$post_id}/location {$location_id}/main_course_id {$main_course_id} status to: {$post_status} (is_active: " . ($is_active ? 'true' : 'false') . ")");
    } else {
        $updated_title = $is_parent_course === 'yes' ? $data['name'] : $data['name'] . ' - ' . get_course_location($data);
        $post_status = $is_active ? 'publish' : 'draft';
        error_log("Updating sub-course {$post_id}/location {$location_id}/main_course_id {$main_course_id} status to: {$post_status} (is_active: " . ($is_active ? 'true' : 'false') . ")");
    }
    error_log("Updating course {$post_id}/location {$location_id}/main_course_id {$main_course_id} status to: {$post_status} (is_active: " . ($is_active ? 'true' : 'false') . ")");

    // Update post data including slug for sub-courses
    $update_data = [
        'ID'           => $post_id,
        'post_title'   => sanitize_text_field($updated_title),
        'description'  => 'Updated',
        'post_excerpt' => sanitize_text_field($data['introText']),
        'post_status'  => $post_status,
    ];
    
    // Update slug (post_name) for sub-courses when location name changes
    if ($is_parent_course !== 'yes') {
        $new_location_name = get_course_location($data);
        $current_post = get_post($post_id);
        if ($current_post && $current_post->post_name !== $new_location_name) {
            $update_data['post_name'] = sanitize_title($new_location_name);
        }
    }

    wp_update_post($update_data);

    if (!is_wp_error($post_id)) {
        // Update shared metadata
        $common_meta_fields = get_common_meta_fields($data, $language);
        foreach ($common_meta_fields as $key => $value) {
            update_post_meta($post_id, $key, $value);
        }
        // Oppdater is_active meta-verdi kun for underkurs (ikke foreldrekurs)
        if ($is_parent_course !== 'yes') {
            update_post_meta($post_id, 'ka_is_active', $is_active ? '1' : '0');
        }
        // Oppdater location_id meta-verdi
        update_post_meta($post_id, 'ka_location_id', (int) $location_id);
        if ($is_parent_course !== 'yes') {
            update_post_meta($post_id, 'ka_main_course_title', sanitize_text_field($data['name']));
            update_post_meta($post_id, 'ka_sub_course_location', sanitize_text_field(get_course_location($data)));
            // Sett course_location_freetext basert på lokasjonsdata - men ikke for nettkurs
            //$location_name = get_course_location($data);
            $is_online = !empty($data['isOnlineCourse']) && ($data['isOnlineCourse'] === true || $data['isOnlineCourse'] === 'true' || $data['isOnlineCourse'] === 1 || $data['isOnlineCourse'] === '1');
            if (!$is_online && !empty($data['locations'][0]['description'])) {
                update_post_meta($post_id, 'ka_course_location_freetext', sanitize_text_field($data['locations'][0]['description']));
            } else {
                // Remove location_freetext if course is online
                delete_post_meta($post_id, 'ka_course_location_freetext');
            }
        }

        update_course_taxonomies($post_id, $location_id, $data, $is_webhook);

        // Associate instructor taxonomy
        $instructors = get_instructors_in_courselist($data, $location_id);
        $location_instructors = $instructors['instructors_location'];
        update_instructor_taxonomies($post_id, $location_instructors);

        if ($main_course_id) {
            sync_main_course_data($main_course_id);
        }

        set_featured_image_from_url($data, $post_id, $main_course_id, $location_id, get_course_location($data));
        // Use the correct location id when creating/updating coursedates
        create_or_update_course_date($data, $post_id, $main_course_id, $location_id, $is_active);
    }
}

function create_or_update_course_date($data, $post_id, $main_course_id, $location_id, $is_active) {
    //error_log("**Starting create_or_update_course_date for post_id: $post_id, location_id: $location_id");
    
    if (!$is_active) {
        //error_log("Course is not active, deleting existing dates for location_id: $location_id");
    $existing_dates = get_posts([
        'post_type' => 'ka_coursedate',
            'post_status' => ['publish', 'draft'],
            'meta_query' => [
                ['key' => 'ka_location_id', 'value' => $location_id],
            ],
            'numberposts' => -1,
        ]);

        foreach ($existing_dates as $date) {
            // Bruk den nye remove_course_coursedate_relationship funksjonen
            remove_course_coursedate_relationship($post_id, $date->ID);
            wp_delete_post($date->ID, true);
        }
        return;
    }

    if (!isset($data['locations'])) {
        //error_log("No locations data found in course data for location_id: $location_id");
        return;
    }

    // Find correct location based on location_id
    $location = array_filter($data['locations'], function ($loc) use ($location_id) {
        return $loc['courseId'] === $location_id;
    });

    if (empty($location)) {
        //error_log("Location not found in data for location_id: $location_id");
        return;
    }

    $location = reset($location);

    // Loop through all schedules and create/update course dates
    foreach ($location['schedules'] as $schedule) {
        $schedule_id = $schedule['id'] ?? 0;

        // Check if course date already exists based on schedule_id and location_id
        $existing_posts = get_posts([
            'post_type' => 'ka_coursedate',
            'post_status' => ['publish', 'draft'],
            'meta_query' => [
                ['key' => 'ka_schedule_id', 'value' => $schedule_id],
                ['key' => 'ka_location_id', 'value' => $location_id],
            ],
            'numberposts' => -1, // Get ALL matches to detect duplicates
        ]);

        // If we have duplicates, delete all but the first one
        if (count($existing_posts) > 1) {
            error_log("⚠️ Fant " . count($existing_posts) . " duplikater av coursedate med schedule_id: $schedule_id og location_id: $location_id - sletter duplikater");
            for ($i = 1; $i < count($existing_posts); $i++) {
                wp_delete_post($existing_posts[$i]->ID, true);
                error_log("🗑️ Slettet duplikat coursedate ID: " . $existing_posts[$i]->ID);
            }
        }

        $coursedate_id = $existing_posts[0]->ID ?? null;

        // Get provider data
        // Create signup form url
        $courseprovider = get_option('kag_kursinnst_option_name');
        $provider_id = !empty($courseprovider['ka_tilbyderID']) ? $courseprovider['ka_tilbyderID'] : '';
        $provider_theme = !empty($courseprovider['ka_temaKurs']) ? $courseprovider['ka_temaKurs'] : 'standard';
        $course_signup_url = "https://embed.kursagenten.no/$provider_id/skjema/$location_id/$schedule_id?theme=$provider_theme&gtmevent=add_to_cart";

        // Set up meta fields for course date
        $meta_input = [];

        if (isset($main_course_id)) {   $meta_input['ka_main_course_id'] = $main_course_id;}
        if (isset($location_id)) {      $meta_input['ka_location_id'] = $location_id;}
        if (isset($schedule_id)) {      $meta_input['ka_schedule_id'] = $schedule_id;}
        if (!empty($data['name'])) {    $meta_input['ka_course_title'] = $data['name'];}

        if (!empty($schedule['firstCourseDate'])) {     $meta_input['ka_course_first_date'] = format_date_for_db($schedule['firstCourseDate']);}
        if (!empty($schedule['firstCourseDate'])) {     $meta_input['ka_course_month'] = format_date_get_month($schedule['firstCourseDate']);}
        if (!empty($schedule['lastCourseDate'])) {      $meta_input['ka_course_last_date'] = format_date_for_db($schedule['lastCourseDate']);}
        if (!empty($schedule['registrationDeadline'])) {$meta_input['ka_course_registration_deadline'] = format_date_for_db($schedule['registrationDeadline']);}
        if (!empty($schedule['duration'])) {            $meta_input['ka_course_duration'] = $schedule['duration'];}
        if (!empty($schedule['coursetime'])) {          $meta_input['ka_course_time'] = format_coursetime($schedule['coursetime']);}
        if (!empty($schedule['coursetimeType'])) {      $meta_input['ka_course_time_type'] = $schedule['coursetimeType'];}
        
                if (!empty($schedule['startTime'])) {           $meta_input['ka_course_start_time'] = $schedule['startTime'];}
        if (!empty($schedule['endTime'])) {             $meta_input['ka_course_end_time'] = $schedule['endTime'];}
        if (!empty($schedule['price'])) {               $meta_input['ka_course_price'] = (int) $schedule['price'];}
        if (!empty($schedule['textBeforeAmount'])) {    $meta_input['ka_course_text_before_price'] = sanitize_text_field($schedule['textBeforeAmount']);}
        if (!empty($schedule['textAfterAmount'])) {     $meta_input['ka_course_text_after_price'] = sanitize_text_field($schedule['textAfterAmount']);}
        if (!empty($schedule['courseCode'])) {          $meta_input['ka_course_code'] = $schedule['courseCode'];}
        if (!empty($schedule['formButtonText'])) {      $meta_input['ka_course_button_text'] = $schedule['formButtonText'];}
        if (!empty($schedule['language'])) {            $meta_input['ka_course_language'] = $schedule['language'];}
        
        if (!empty($schedule['maxParticipants'])) {     $meta_input['ka_course_maxParticipants'] = $schedule['maxParticipants'];}
        if (isset($schedule['showRegistrationForm'])) { $meta_input['ka_course_showRegistrationForm'] = $schedule['showRegistrationForm'];}
        if (isset($schedule['markedAsFull'])) {         $meta_input['ka_course_markedAsFull'] = $schedule['markedAsFull'];}
        if (isset($schedule['isFull'])) {               $meta_input['ka_course_isFull'] = $schedule['isFull'];}
        if (!empty($course_signup_url)) {               $meta_input['ka_course_signup_url'] = $course_signup_url;}
        if (!empty($location['county'])) {              $meta_input['ka_course_location'] = get_course_location($data);} 
        // Only set location_freetext if course is not online
        $is_online = !empty($data['isOnlineCourse']) && ($data['isOnlineCourse'] === true || $data['isOnlineCourse'] === 'true' || $data['isOnlineCourse'] === 1 || $data['isOnlineCourse'] === '1');
        if (!$is_online && !empty($location['description'])) {
            $meta_input['ka_course_location_freetext'] = $location['description'];
        } else {
            // Ensure location_freetext is removed for online courses
            $meta_input['ka_course_location_freetext'] = '';
        }

        // Only set address fields if course is not online
        if (!$is_online) {
            if (!empty($location['address']['streetAddress'])) {        $meta_input['ka_course_address_street'] = $location['address']['streetAddress'];}
            if (!empty($location['address']['streetAddressNumber'])) {  $meta_input['ka_course_address_street_number'] = $location['address']['streetAddressNumber'];}
            if (!empty($location['address']['zipCode'])) {              $meta_input['ka_course_address_zipcode'] = $location['address']['zipCode'];}
            if (!empty($location['address']['place'])) {                $meta_input['ka_course_address_place'] = $location['address']['place'];}
        } else {
            // Remove address fields for online courses
            $meta_input['ka_course_address_street'] = '';
            $meta_input['ka_course_address_street_number'] = '';
            $meta_input['ka_course_address_zipcode'] = '';
            $meta_input['ka_course_address_place'] = '';
        }

        if (!empty($schedule['locationRooms']) && is_array($schedule['locationRooms'])) {
            $room_names = array();
            foreach ($schedule['locationRooms'] as $room) {
                if (!empty($room['name'])) {
                    $room_names[] = $room['name'];
                }
            }
            if (!empty($room_names)) {
                $meta_input['ka_course_location_room'] = implode(', ', $room_names);
            }
        }
        // Add course_days based on coursetime format and firstCourseDate
        if (!empty($schedule['coursetime']) && !empty($schedule['firstCourseDate'])) {
            $course_days = get_course_days_from_coursetime($schedule['coursetime'], $schedule['firstCourseDate']);
            if (!empty($course_days)) {
                $meta_input['ka_course_days'] = $course_days;
            }
        }
        //****/

        // Create or update course date
        if ($coursedate_id) {
            // Update existing course date
            wp_update_post([
                'ID' => $coursedate_id,
                'post_title' => $data['name'] . ' - ' . $location['county'],
                'post_status' => 'publish',
                'meta_input' => $meta_input
            ]);
        } else {
            // Create new course date
            $coursedate_id = wp_insert_post([
                'post_title' => $data['name'] . ' - ' . $location['county'],
                'post_type' => 'ka_coursedate',
                'post_status' => 'publish',
                'meta_input' => $meta_input
            ]);
        }

        if (!is_wp_error($coursedate_id)) {
            // Oppdater location_id
            update_post_meta($coursedate_id, 'ka_location_id', $location_id);
            
            // Bruk den nye create_or_update_course_coursedate_relationship funksjonen
            if (!empty($post_id)) {
                create_or_update_course_coursedate_relationship($post_id, $coursedate_id);
            }

            // Oppdater taxonomier basert på kurset
            update_course_taxonomies($coursedate_id, $location_id, $data, false);
            
            // Oppdater instruktører
            $schedule_data = [
                'locations' => [
                    [
                        'courseId' => $location_id,
                        'schedules' => [$schedule]
                    ]
                ]
            ];
            
            $instructors = get_instructors_in_courselist($schedule_data, $location_id);
            $location_instructors = $instructors['instructors_location'];
            update_instructor_taxonomies($coursedate_id, $location_instructors);
        }
    }
    
    // Clean up old coursedates that no longer exist in the API
    if (!empty($location['schedules'])) {
        cleanup_coursedates($location_id, $location['schedules']);
    }
}

function cleanup_coursedates($location_id, $schedules_from_api) {
    //error_log("=== START: cleanup_coursedates for location_id: $location_id ===");
    
    // This function removes coursedates that no longer exist in the API
    // It compares schedule_id from existing coursedates with valid schedule_ids from API
    
    // Hent alle kursdatoer for denne lokasjonen
    $coursedates = get_posts([
        'post_type' => 'ka_coursedate',
        'posts_per_page' => -1,
        'meta_query' => [
            ['key' => 'ka_location_id', 'value' => $location_id],
        ],
        'numberposts' => -1,
    ]);

    // Hent gyldige schedule_id-er fra API
    $valid_schedule_ids = array_map(function($schedule) {
        return $schedule['id'] ?? 0;
    }, $schedules_from_api);

    // Track unique schedule_id combinations to detect duplicates
    $seen_schedule_ids = [];

    // Sjekk hver kursdato
    foreach ($coursedates as $coursedate) {
        $schedule_id = get_post_meta($coursedate->ID, 'ka_schedule_id', true);
        $related_post_id = get_post_meta($coursedate->ID, 'ka_course_related_course', true);

        // Check for duplicates - if we've seen this schedule_id before, delete this one
        if (isset($seen_schedule_ids[$schedule_id])) {
            error_log("⚠️ Fant duplikat coursedate med schedule_id: $schedule_id og location_id: $location_id - sletter duplikat ID: " . $coursedate->ID);
            wp_delete_post($coursedate->ID, true);
            continue; // Skip further checks for this duplicate
        }
        
        // Mark this schedule_id as seen
        $seen_schedule_ids[$schedule_id] = true;

        // Hvis schedule_id er 0, sjekk om det er en gyldig verdi
        if ($schedule_id === '0' || $schedule_id === 0) {
            if (!in_array(0, $valid_schedule_ids)) {
                // Slett kursdatoen hvis 0 ikke er en gyldig schedule_id
                wp_delete_post($coursedate->ID, true);
            }
        } else if (!in_array($schedule_id, $valid_schedule_ids)) {
            // Slett kursdatoen hvis schedule_id ikke lenger er gyldig
            wp_delete_post($coursedate->ID, true);
        }
    }
}

function remove_coursedate_from_related_course($coursedate_id, $post_id) {
    // Fjern kursdato fra kursets relasjoner
    if (!empty($post_id)) {
        $related_coursedates = get_post_meta($post_id, 'ka_course_related_coursedate', true);
        
        if (!empty($related_coursedates) && is_array($related_coursedates)) {
            $related_coursedates = array_diff($related_coursedates, [$coursedate_id]);
            update_post_meta($post_id, 'ka_course_related_coursedate', array_values($related_coursedates));
        }
    }

    // Fjern kurs fra kursdatos relasjoner
    if (!empty($coursedate_id)) {
        $related_courses = get_post_meta($coursedate_id, 'ka_course_related_course', true);
        
        if (!empty($related_courses) && is_array($related_courses)) {
            $related_courses = array_diff($related_courses, [$post_id]);
            update_post_meta($coursedate_id, 'ka_course_related_course', array_values($related_courses));
        }
    }
}

// Felles funksjoner/ helper funkcions and data

// Get data fra enkeltkurs API for meta fields
function get_common_meta_fields($data, $language) {
    // Ensure we have valid data structure
    $first_location = $data['locations'][0] ?? [];
    $first_course_type = $data['courseTypes'][0] ?? ['description' => ''];
    
    return [ 
        'ka_location_id' => (int) ($data['id'] ?? 0),
        'ka_course_content' => wp_kses_post($data['description'] ?? ''),
        'ka_course_price' => (int) ($first_location['price'] ?? 0),
        'ka_course_text_before_price' => sanitize_text_field($first_location['textBeforeAmount'] ?? ''),
        'ka_course_text_after_price' => sanitize_text_field($first_location['textAfterAmount'] ?? ''),
        'ka_course_difficulty_level' => sanitize_text_field($data['difficultyLevel'] ?? ''),
        'ka_course_type' => sanitize_text_field($first_course_type['description'] ?? ''),
        'ka_course_is_online' => sanitize_text_field($data['isOnlineCourse'] ?? ''),
        'ka_course_municipality' => sanitize_text_field($first_location['municipality'] ?? ''),
        'ka_course_county' => sanitize_text_field($first_location['county'] ?? ''),
        'ka_course_language' => sanitize_text_field($language ?? ''),
        'ka_course_external_sign_on' => sanitize_text_field($data['signOnPage'] ?? ''),
        'ka_course_contactperson_name' => sanitize_text_field($data['contactPerson']['name'] ?? ''),
        'ka_course_contactperson_phone' => sanitize_text_field($data['contactPerson']['phoneNumber'] ?? ''),
        'ka_course_contactperson_email' => sanitize_email($data['contactPerson']['email'] ?? ''),
    ];
}

function get_course_location($data) {
    if (!empty($data['locations'][0]['municipality'])) {
        // Get municipality name mapping from options
        $municipality_mapping = get_option('kursagenten_location_mappings', array());
        
        // If no mappings exist, initialize with default values
        if (empty($municipality_mapping)) {
        $municipality_mapping = [
            'Bærum / Sandvika' => 'Bærum',
            'Rana / Mo i Rana' => 'Mo i Rana',
            'Lenvik / Finnsnes' => 'Finnsnes',
            'Porsgrunn / Brevik' => 'Porsgrunn',
            'Vågan / Svolvær' => 'Svolvær',
        ];
            update_option('kursagenten_location_mappings', $municipality_mapping);
        }
        
        $municipality = $data['locations'][0]['municipality'];
        if (isset($municipality_mapping[$municipality])) {
            return $municipality_mapping[$municipality];
        }
        
        return $municipality;
    } elseif (!empty($data['locations'][0]['county'])) {
        return $data['locations'][0]['county'];
    }
}

function format_date($date_string) {
    if ($date_string === null) {
        return '';
    }
    $date = DateTime::createFromFormat('Y-m-d\TH:i:s', $date_string);
    return $date ? $date->format('d.m.Y') : $date_string;
}

function format_coursetime($coursetime) {
    if (empty($coursetime)) {
        return '';
    }
    
    // Convert "Kl" to "kl" if it matches the valid pattern
    if (is_valid_coursetime_format($coursetime)) {
        return str_replace('Kl ', 'kl ', $coursetime);
    }
    
    // Return original coursetime if it doesn't match the pattern
    return $coursetime;
}

function format_date_for_db($date_string) {
    if ($date_string === null) {
        return '';
    }
    $date = DateTime::createFromFormat('Y-m-d\TH:i:s', $date_string);
    return $date ? $date->format('Y-m-d H:i:s') : $date_string;
}

function format_date_get_month($date_string) {
    if ($date_string === null) {
        return '';
    }
    $date = DateTime::createFromFormat('Y-m-d\TH:i:s', $date_string);
    return $date ? $date->format('m') : $date_string;
}

function update_course_taxonomies($post_id, $location_id, $data, $is_webhook = false) {
    static $updated_terms = [];
    
    // Koble kurs til course_location taxonomi
    $course_location = get_course_location($data);

    if ($course_location) {
        // Sjekk om taxonomien finnes eller opprett den
        $course_location_term = term_exists($course_location, 'ka_course_location');

        if (!$course_location_term) {
            $course_location_term = wp_insert_term($course_location, 'ka_course_location');
        }

        if (!is_wp_error($course_location_term)) {
            $term_id = (int)$course_location_term['term_id'];
            
            // Sett course_location taxonomien for kurset
            wp_set_object_terms($post_id, $term_id, 'ka_course_location', false);
            
            // Oppdater spesifikke lokasjoner kun hvis denne term_id ikke allerede er oppdatert
            if (!isset($updated_terms[$term_id])) {
                //error_log("update_course_taxonomies: Oppdaterer specific_locations for term_id: " . $term_id);
                update_specific_locations($term_id, $data, $is_webhook);
                $updated_terms[$term_id] = true;
            } else {
                //error_log("update_course_taxonomies: Skipper oppdatering av specific_locations for term_id: " . $term_id . " (allerede oppdatert)");
            }
        }
    }

    // Koble kurs til coursecategory taxonomier
    if (!empty($data['tags']) && is_array($data['tags'])) {
        $course_categories = [];

        foreach ($data['tags'] as $tag) {
            if (!empty($tag['title'])) {
                $course_category = sanitize_text_field($tag['title']);

                // Sjekk om taxonomien finnes eller opprett den
                $course_category_term = term_exists($course_category, 'ka_coursecategory');

                if (!$course_category_term) {
                    $course_category_term = wp_insert_term($course_category, 'ka_coursecategory');
                }

                if (!is_wp_error($course_category_term)) {
                    $course_categories[] = (int)$course_category_term['term_id'];
                }
            }
        }

        if (!empty($course_categories)) {
            // Sett coursecategory taxonomier for kurset
            wp_set_object_terms($post_id, $course_categories, 'ka_coursecategory', false);
        }
    }
}

// Legg til denne hjelpefunksjonen før update_specific_locations
function build_address($location) {
    $address = [
        'street' => '',
        'zipcode' => '',
        'place' => ''
    ];

    // Hvis vi har direkte adressedata
    if (isset($location['address'])) {
        // Håndter streetAddress og streetAddressNumber
        $street = $location['address']['streetAddress'] ?? '';
        $number = $location['address']['streetAddressNumber'] ?? '';
        
        // Fjern nummer fra street hvis det allerede er inkludert
        if (!empty($number) && !empty($street)) {
            // Fjern nummer fra slutten av street hvis det matcher
            $street = preg_replace('/\s*' . preg_quote($number, '/') . '$/', '', $street);
        }
        
        // Slå sammen street og number
        $address['street'] = trim($street . ' ' . $number);
        $address['zipcode'] = $location['address']['zipCode'] ?? '';
        $address['place'] = $location['address']['place'] ?? '';
    }
    
    return $address;
}

function update_specific_locations($term_id, $data, $is_webhook = false) {
    //error_log("=== START: update_specific_locations for term_id: $term_id ===");
    
    // Hvis dette er et webhook-kall, bruk webhook-logikk
    if ($is_webhook) {
        //error_log("Bruker webhook-logikk");
        $valid_locations = [];
        
        // For webhook bruker vi all_locations fra API-data
        if (!empty($data['all_locations'])) {
            foreach ($data['all_locations'] as $location) {
                $description = $location['description'] ?? '';
                // Rens bort uønsket prefiks som "::marker"
                $description = sanitize_specific_location_description($description);
                if (empty($description)) {
                    //error_log("Ingen description funnet, hopper over lokasjon");
                    continue;
                }
                
                //error_log("Prosesserer lokasjon: " . $description);
                
                // Bygg adresse
                $address = build_address($location);
                
                // Legg til lokasjonen i valid_locations
                $valid_locations[$description] = [
                    'description' => $description,
                    'address' => $address
                ];
            }
        }
        
        // Konverter til array med numeriske indekser
        $updated_locations = array_values($valid_locations);
        
        // For webhook-kall, slett eksisterende data først
        delete_term_meta($term_id, 'specific_locations');
        
        // Lagre oppdaterte spesifikke lokasjoner
        $result = add_term_meta($term_id, 'specific_locations', $updated_locations, true);
        
        if (!$result) {
            //error_log("Kunne ikke lagre lokasjoner med add_term_meta, prøver update_term_meta");
            $result = update_term_meta($term_id, 'specific_locations', $updated_locations);
        }
        
        //error_log("Oppdaterte lokasjoner lagret: " . ($result ? 'true' : 'false'));
        //error_log("Antall lokasjoner lagret: " . count($updated_locations));
        
        //error_log("=== SLUTT: update_specific_locations (webhook) ===");
        return;
    }
    
    // Sync on demand logikk - forenklet versjon
    if (empty($data['locations'])) {
        //error_log("Ingen locations data funnet, kan ikke oppdatere spesifikke lokasjoner");
        return;
    }

    //error_log("Bruker sync on demand logikk");
    
    // Hent eksisterende lokasjoner
    $existing_locations = get_term_meta($term_id, 'specific_locations', true) ?: [];
    //error_log("Eksisterende lokasjoner: " . print_r($existing_locations, true));
    
    // Konverter eksisterende lokasjoner til et map for enklere søk
    $existing_locations_map = [];
    foreach ($existing_locations as $loc) {
        if (!empty($loc['description'])) {
            $existing_locations_map[$loc['description']] = $loc;
        }
    }
    
    // Legg til nye lokasjoner fra API-data
    foreach ($data['locations'] as $location) {
        $description = $location['placeFreeText'] ?? $location['description'] ?? '';
        // Rens bort uønsket prefiks som "::marker"
        $description = sanitize_specific_location_description($description);
        if (empty($description)) {
            //error_log("Ingen description for denne lokasjonen, hopper over");
            continue;
        }

        // Bygg adresse
        $address = build_address($location);

        $location_data = [
            'description' => $description,
            'address' => $address
        ];

        // Legg til lokasjonen hvis den ikke allerede finnes
        if (!isset($existing_locations_map[$description])) {
            $existing_locations_map[$description] = $location_data;
            //error_log("La til ny lokasjon: " . $description);
        } else {
            //error_log("Lokasjon eksisterer allerede: " . $description);
        }
    }

    // Konverter tilbake til array og lagre
    $updated_locations = array_values($existing_locations_map);
    //error_log("Data som skal lagres: " . print_r($updated_locations, true));
    
    $result = update_term_meta($term_id, 'specific_locations', $updated_locations);
    //error_log("Resultat av update_term_meta: " . ($result ? 'true' : 'false'));
    
    // Verifiser at dataene ble lagret korrekt
    $verified_data = get_term_meta($term_id, 'specific_locations', true);
    //error_log("Verifisert lagret data: " . print_r($verified_data, true));
    
    //error_log("Ferdig med å prosessere lokasjoner. Totalt antall: " . count($updated_locations));
    //error_log("=== SLUTT: update_specific_locations ===");
}

/**
 * Sanitize description for specific_locations by removing list marker artifacts
 * like the leading "::marker" that sometimes appears when pasting from external sources.
 * Returns a trimmed string or empty string if input is not usable.
 */
function sanitize_specific_location_description($description) {
    if (!is_string($description)) {
        return '';
    }

    $clean = trim($description);

    // Fjern alle usynlige tegn og kontrolltegn i starten
    $clean = preg_replace('/^[\x{FEFF}\x{200B}-\x{200D}\x{00}-\x{1F}\x{7F}-\x{9F}]+/u', '', $clean);

    // Fjern prefiks '::marker' (case-insensitive) og påfølgende whitespace
    $clean = preg_replace('/^::marker\s*/i', '', $clean);

    // Fjern alle mulige liste-markører og punkt-tegn i starten
    // Inkluderer: : :: • - – — * · · · ◦ ‣ ⁃ ▪ ▫ ○ ● ◯ ◉ ◐ ◑ ◒ ◓ ◔ ◕ ◖ ◗ ◘ ◙ ◚ ◛ ◜ ◝ ◞ ◟ ◠ ◡ ◢ ◣ ◤ ◥ ◦ ◧ ◨ ◩ ◪ ◫ ◬ ◭ ◮ ◯ ◰ ◱ ◲ ◳ ◴ ◵ ◶ ◷ ◸ ◹ ◺ ◻ ◼ ◽ ◾ ◿
    $clean = preg_replace('/^(?:(?:[:\.*\-•–—··◦‣⁃▪▫○●◯◉◐◑◒◓◔◕◖◗◘◙◚◛◜◝◞◟◠◡◢◣◤◥◦◧◨◩◪◫◬◭◮◯◰◱◲◳◴◵◶◷◸◹◺◻◼◽◾◿]+)\s*)+/u', '', $clean);

    // Fjern eventuelle gjenværende kontrolltegn
    $clean = preg_replace('/[\x{00}-\x{1F}\x{7F}-\x{9F}]/u', '', $clean);

    // Normaliser mellomrom
    $clean = preg_replace('/\s+/u', ' ', $clean);

    return trim((string) $clean);
}

function sync_main_course_data($main_course_id) {
    // Finn hovedkursets post-ID basert på $main_course_id som meta-verdi
    $main_course_post = get_posts([
        'post_type' => 'ka_course',
        'post_status' => ['publish', 'draft'],
        'meta_query' => [
            [
                'key' => 'ka_main_course_id',
                'value' => $main_course_id,
                'compare' => '='
            ],
            [
                'key' => 'ka_is_parent_course',
                'value' => 'yes',
                'compare' => '='
            ]
        ],
        'numberposts' => 1,
    ]);

    if (empty($main_course_post)) {
        return;
    }

    $post_id = $main_course_post[0]->ID; // Faktisk post-ID for hovedkurset

    // Hent alle child_course knyttet til hovedkurset
    $child_courses = get_posts([
        'post_type' => 'ka_course',
        'post_status' => ['publish', 'draft'],
        'meta_query' => [
            'relation' => 'AND',
            [
                'key' => 'ka_main_course_id',
                'value' => $main_course_id,
                'compare' => '='
            ],
            [
                'relation' => 'OR',
                [
                    'key' => 'ka_is_parent_course',
                    'value' => 'yes',
                    'compare' => '!='
                ],
                [
                    'key' => 'ka_is_parent_course',
                    'compare' => 'NOT EXISTS'
                ]
            ]
        ],
        'numberposts' => -1,
    ]);

    if (empty($child_courses)) {
        return;
    }

    $course_locations = [];
    $course_categories = [];
    $instructors = [];
    $related_course_dates = [];

    foreach ($child_courses as $course) {
        // Hent taxonomier
        $course_location_terms = wp_get_object_terms($course->ID, 'ka_course_location', ['fields' => 'ids']);
        $course_category_terms = wp_get_object_terms($course->ID, 'ka_coursecategory', ['fields' => 'ids']);
        $instructor_terms = wp_get_object_terms($course->ID, 'ka_instructors', ['fields' => 'ids']);

        // Hent relaterte kursdatoer
        $course_dates = get_post_meta($course->ID, 'ka_course_related_coursedate', true);
        if (!empty($course_dates)) {
            $related_course_dates = array_merge($related_course_dates, (array) $course_dates);
        }

        if (is_wp_error($course_location_terms) || is_wp_error($course_category_terms) || is_wp_error($instructor_terms)) {
            continue;
        }

        $course_locations = array_merge($course_locations, $course_location_terms);
        $course_categories = array_merge($course_categories, $course_category_terms);
        $instructors = array_merge($instructors, $instructor_terms);
    }

    // Fjern duplikater
    $course_locations = array_unique($course_locations);
    $course_categories = array_unique($course_categories);
    $instructors = array_unique($instructors);
    $related_course_dates = array_unique($related_course_dates);

    // Sett taxonomier på hovedkurset
    if (!empty($course_locations)) {
        wp_set_object_terms($post_id, $course_locations, 'ka_course_location', false);
    }

    if (!empty($course_categories)) {
        wp_set_object_terms($post_id, $course_categories, 'ka_coursecategory', false);
    }

    if (!empty($instructors)) {
        wp_set_object_terms($post_id, $instructors, 'ka_instructors', false);
    }

    // Oppdater relaterte kursdatoer på hovedkurset
    if (!empty($related_course_dates)) {
        update_post_meta($post_id, 'ka_course_related_coursedate', $related_course_dates);
    }
}

function update_instructor_taxonomies($post_id, $data_instructors) {
    if (!empty($data_instructors) && is_array($data_instructors)) {
        $instructors = [];

        foreach ($data_instructors as $instructor) {
            if (empty($instructor['fullname'])) {
                continue;
            }

            error_log("Prosesserer instruktør: {$instructor['fullname']} (ID: {$instructor['userId']})");
            $term_id = null;

            // 1. Søk etter eksisterende instruktør med samme ID
            if (!empty($instructor['userId'])) {
                $existing_terms = get_terms([
                    'taxonomy' => 'ka_instructors',
                    'meta_key' => 'instructor_id',
                    'meta_value' => $instructor['userId'],
                    'hide_empty' => false,
                    'number' => 1
                ]);

                if (!empty($existing_terms) && !is_wp_error($existing_terms)) {
                    $term_id = $existing_terms[0]->term_id;
                    error_log("Fant eksisterende instruktør på ID: {$instructor['userId']} (term_id: $term_id)");
                    
                    // Oppdater navn hvis det er endret
                    if ($existing_terms[0]->name !== $instructor['fullname']) {
                        wp_update_term($term_id, 'ka_instructors', [
                            'name' => $instructor['fullname']
                        ]);
                        error_log("Oppdaterte navn på instruktør {$instructor['userId']} fra '{$existing_terms[0]->name}' til '{$instructor['fullname']}'");
                    }
                }
            }

            // 2. Hvis vi ikke fant på ID, søk på navn
            if (!$term_id) {
                // Bruk get_terms i stedet for term_exists for mer presis søking
                $name_search = get_terms([
                    'taxonomy' => 'ka_instructors',
                    'name' => $instructor['fullname'],
                    'hide_empty' => false,
                    'number' => 1
                ]);

                if (!empty($name_search) && !is_wp_error($name_search)) {
                    $term_id = $name_search[0]->term_id;
                    $existing_id = get_term_meta($term_id, 'instructor_id', true);
                    
                    if (!empty($existing_id)) {
                        // Hvis vi fant på navn og denne har en annen ID, oppdater ID-en
                        update_term_meta($term_id, 'instructor_id', sanitize_text_field($instructor['userId']));
                        error_log("Oppdaterte instructor_id på instruktør '{$instructor['fullname']}' fra {$existing_id} til {$instructor['userId']}");
                    } else {
                        // Hvis ingen ID er satt, oppdater med ny ID
                        update_term_meta($term_id, 'instructor_id', sanitize_text_field($instructor['userId']));
                        error_log("Satte instructor_id på instruktør '{$instructor['fullname']}' til {$instructor['userId']}");
                    }
                }
            }

            // 3. Opprett ny instruktør KUN hvis vi ikke fant noen eksisterende
            if (!$term_id) {
                error_log("Ingen eksisterende instruktør funnet, oppretter ny for: {$instructor['fullname']}");
                $new_term = wp_insert_term($instructor['fullname'], 'ka_instructors');
                
                if (!is_wp_error($new_term)) {
                    $term_id = $new_term['term_id'];
                    if (!empty($instructor['userId'])) {
                        update_term_meta($term_id, 'instructor_id', sanitize_text_field($instructor['userId']));
                    }
                    error_log("Opprettet ny instruktør: {$instructor['fullname']} (ID: {$instructor['userId']}, term_id: $term_id)");
                } else {
                    error_log("FEIL ved opprettelse av ny instruktør: " . $new_term->get_error_message());
                }
            }

            if ($term_id) {
                $instructors[] = $term_id;

                // Sjekk om feltene er manuelt redigert før oppdatering
                $manually_edited = [
                    'email' => get_term_meta($term_id, 'instructor_email_edited', true) === 'yes',
                    'phone' => get_term_meta($term_id, 'instructor_phone_edited', true) === 'yes',
                    'firstname' => get_term_meta($term_id, 'instructor_firstname_edited', true) === 'yes',
                    'lastname' => get_term_meta($term_id, 'instructor_lastname_edited', true) === 'yes',
                    'image' => get_term_meta($term_id, 'instructor_image_edited', true) === 'yes'
                ];

                // Oppdater kun felt som ikke er manuelt redigert
                if (isset($instructor['image']) && !$manually_edited['image']) {
                    $image_url = esc_url_raw($instructor['image']);
                    update_term_meta($term_id, 'image_instructor_ka', $image_url);
                }

                if (isset($instructor['email']) && !$manually_edited['email']) {
                    update_term_meta($term_id, 'instructor_email', sanitize_email($instructor['email']));
                }

                if (isset($instructor['phone']) && !$manually_edited['phone']) {
                    update_term_meta($term_id, 'instructor_phone', sanitize_text_field($instructor['phone']));
                }

                if (isset($instructor['firstname']) && !$manually_edited['firstname']) {
                    update_term_meta($term_id, 'instructor_firstname', sanitize_text_field($instructor['firstname']));
                }

                if (isset($instructor['lastname']) && !$manually_edited['lastname']) {
                    update_term_meta($term_id, 'instructor_lastname', sanitize_text_field($instructor['lastname']));
                }
            }
        }

        if (!empty($instructors)) {
            wp_set_object_terms($post_id, $instructors, 'ka_instructors', false);
        }
    }
}

function get_instructors_in_courselist($data, $location_id) {
    $instructors_location = [];

    // Debug logging
    //error_log("DEBUG: Raw instructor data: " . print_r($data['locations'], true));

    foreach ($data['locations'] as $location) {
        if (!isset($location['courseId']) || $location['courseId'] != $location_id) {
            continue;
        }

        foreach ($location['schedules'] as $schedule) {
            if (!isset($schedule['instructors']) || !is_array($schedule['instructors'])) {
                continue;
            }

            foreach ($schedule['instructors'] as $instructor) {
                if (isset($instructor['id'], $instructor['fullname'])) {
                    // Konstruer full bilde-URL hvis image eksisterer
                    $image_url = !empty($instructor['image']) ? 
                    ltrim($instructor['image'], '/') : '';

                    $instructors_location[$instructor['id']] = [
                        'id' => $instructor['id'],
                        'fullname' => sanitize_text_field($instructor['fullname']),
                        'firstname' => sanitize_text_field($instructor['firstname'] ?? ''),
                        'lastname' => sanitize_text_field($instructor['lastname'] ?? ''),
                        'email' => sanitize_email($instructor['email'] ?? ''),
                        'phone' => sanitize_text_field($instructor['phone'] ?? ''),
                        'userId' => sanitize_text_field($instructor['userId'] ?? ''),
                        'image' => esc_url_raw($image_url)
                    ];
                    //error_log("DEBUG: Prosesserer instruktør {$instructor['fullname']}, bilde: {$image_url}");
                }
            }
        }
    }

    //error_log("DEBUG: Behandlet instruktørdata: " . print_r($instructors_location, true));
    return ['instructors_location' => array_values($instructors_location)];
}

function set_featured_image_from_url($data, $post_id, $main_course_id, $location_id, $location_name) {
    //error_log("** START: set_featured_image_from_url, post_id: $post_id, location_id: $location_id");
    //error_log("Post ID: $post_id, Location ID: $location_id");
    
    $existing_thumbnail_id = get_post_thumbnail_id($post_id);
    $image_url = $data['bannerImage'] ?? null;
    
    //error_log("Eksisterende thumbnail ID: $existing_thumbnail_id - Bilde URL: " . ($image_url ?: 'Ingen URL'));
    //error_log("Bilde URL: " . ($image_url ?: 'Ingen URL'));

    if (empty($image_url)) {
        error_log("ADVARSEL: Ingen bilde-URL tilgjengelig");
        if ($existing_thumbnail_id) {
            wp_delete_attachment($existing_thumbnail_id, true);
            update_post_meta($post_id, 'course_image_name', '');
            //error_log("Slettet eksisterende bilde for post_id: $post_id");
        }
        return false;
    }

    // Legg til ekstra validering av bilde-URL
    $image_url = urldecode($image_url); // Dekod URL for å håndtere spesialtegn
    
    // Valider URL-format med en mer robust metode
    $parsed_url = parse_url($image_url);
    if ($parsed_url === false || !isset($parsed_url['scheme']) || !isset($parsed_url['host'])) {
        error_log("FEIL: Ugyldig bilde-URL format: $image_url");
        return false;
    }

    // Sjekk at URL-en starter med http eller https
    if (!in_array($parsed_url['scheme'], ['http', 'https'])) {
        error_log("FEIL: URL må starte med http eller https: $image_url");
        return false;
    }

    $filename = basename($parsed_url['path']);
    $filename_original = $filename;
    
    // Standardiser kjente bildeformater
    $allowed_types = array(
        'jpeg' => 'jpg',
        'jpg' => 'jpg',
        'png' => 'png',
        'gif' => 'gif',
        'webp' => 'webp'
    );
    
    // Hent filendelse
    $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    // Standardiser filendelsen hvis den er i listen over tillatte typer
    if (array_key_exists($file_ext, $allowed_types)) {
        $new_ext = $allowed_types[$file_ext];
        $filename = substr($filename, 0, -(strlen($file_ext))) . $new_ext;
    }
    
    $stored_image_name = get_post_meta($post_id, 'ka_course_image_name', true);
    
    // Check if the image already exists and is the same to avoid re-downloading
    if ($existing_thumbnail_id && $stored_image_name === $filename) {
        error_log("⏩ Bilde allerede opplastet for kurs ID $post_id (kursID: $location_id), hopper over nedlasting");
        return false; // Image unchanged, no need to re-download
    }

    // Log when we need to download a new image
    error_log("📥 Starter nedlasting av bilde for kurs ID $post_id (kursID: $location_id)");
    $download_start = microtime(true);

    $upload_dir = wp_upload_dir();
    $file_path = $upload_dir['path'] . '/' . $filename;

    // Download the image from the URL using WordPress HTTP API (safer and respects WP config)
    $response = wp_remote_get($image_url, array(
        'timeout'   => 90, // Increased timeout for very large images
        'redirection' => 5,
        'sslverify' => true,
        'stream' => false, // Don't stream to memory for now
    ));

    if (is_wp_error($response)) {
        $error_msg = 'Nedlasting av bilde feilet: ' . $response->get_error_message();
        error_log("❌ $error_msg for kurs ID $post_id (kursID: $location_id) - URL: $image_url");
        
        // Check if it's a timeout error
        if (strpos($response->get_error_message(), 'timed out') !== false || 
            strpos($response->get_error_message(), 'timeout') !== false) {
            throw new Exception("Bilde-nedlasting timeout (bilde for stort eller treg forbindelse)");
        }
        
        throw new Exception($error_msg);
    }

    $http_code = wp_remote_retrieve_response_code($response);
    if ((int) $http_code !== 200) {
        $error_msg = "HTTP status $http_code ved nedlasting av bilde";
        error_log("❌ $error_msg for kurs ID $post_id (kursID: $location_id) - URL: $image_url");
        throw new Exception($error_msg);
    }

    // Enforce maximum image size (999 KB) using header if available
    $max_image_size_kb = 999;
    $max_image_size_bytes = (int) ($max_image_size_kb * 1024);
    $content_length_header = wp_remote_retrieve_header($response, 'content-length');
    if (!empty($content_length_header) && (int) $content_length_header > $max_image_size_bytes) {
        $reported_size_mb = round(((int) $content_length_header) / (1024 * 1024), 2);
        $course_name = sanitize_text_field($data['name'] ?? '');
        $too_large_message = sprintf(
            'Bildet er for stort (%.2f MB) for kurs "%s" (kursID: %d). Bytt til et bilde under %.0f KB i Kursagenten og kjør synkronisering på nytt. Hvis du har lagt inn webhook, holder det at du lagrer kurset i Kursagenten etter å ha byttet bildet.',
            $reported_size_mb,
            $course_name ?: 'Ukjent kurs',
            $location_id,
            $max_image_size_kb
        );
        error_log('❌ ' . $too_large_message);
        throw new Exception($too_large_message);
    }

    $image_data = wp_remote_retrieve_body($response);
    if ($image_data === '' || $image_data === null || $image_data === false) {
        $error_msg = "Tomt eller ugyldig bildeinnhold mottatt";
        error_log("❌ $error_msg for kurs ID $post_id (kursID: $location_id) - URL: $image_url");
        throw new Exception($error_msg);
    }
    
    // Check actual image size after download and enforce limit
    $image_size_bytes = strlen($image_data);
    if ($image_size_bytes > $max_image_size_bytes) {
        $image_size_mb = round($image_size_bytes / (1024 * 1024), 2);
        $course_name = sanitize_text_field($data['name'] ?? '');
        $too_large_message = sprintf(
            'Bildet er for stort (%.2f MB) for kurs "%s" (kursID: %d). Bytt til et bilde under %.0f KB i Kursagenten og kjør synkronisering på nytt. Hvis du har lagt inn webhook, holder det at du lagrer kurset i Kursagenten etter å ha byttet bildet.',
            $image_size_mb,
            $course_name ?: 'Ukjent kurs',
            $location_id,
            $max_image_size_kb
        );
        error_log('❌ ' . $too_large_message);
        throw new Exception($too_large_message);
    }

    if ($image_size_bytes > (10 * 1024 * 1024)) {
        $image_size_mb = round($image_size_bytes / (1024 * 1024), 2);
        error_log("⚠️ Stort bilde lastet ned: {$image_size_mb}MB for kurs ID $post_id (kursID: $location_id)");
    }

    // Delete the existing image now that the new one is validated
    if ($existing_thumbnail_id) {
        wp_delete_attachment($existing_thumbnail_id, true);
        error_log("🗑️ Slettet gammelt bilde for kurs ID $post_id (kursID: $location_id)");
    }

    // Ensure the upload directory exists
    if (!file_exists($upload_dir['path'])) {
        wp_mkdir_p($upload_dir['path']);
    }

    // Save the image to the uploads directory
    if (file_put_contents($file_path, $image_data) === false) {
        error_log("** Failed to save image to: $file_path");
        return false;
    }

    // Check the file type and ensure it's valid
    $wp_filetype = wp_check_filetype($filename, null);
    if (!$wp_filetype['type']) {
        // Logg ukjent filtype
        error_log("Unknown or invalid file type for image: $filename");
        return false;
    }

    $formatted_date = date('Y-m-d H:i:s');

    $new_filename = "kursbilde-" . sanitize_file_name($data['name']) . '-' . $location_name;
    $attachment = array(
        'post_mime_type' => $wp_filetype['type'],
        'post_title'     => sanitize_file_name($new_filename),
        'post_content'   => $data['introText'],
        'post_excerpt'   => $data['introText'],
        'post_status'    => 'inherit',
        'post_date'    => $formatted_date,
        'post_date_gmt' => get_gmt_from_date($formatted_date),
    );

    // Insert the attachment into the media library
    $attach_id = wp_insert_attachment($attachment, $file_path, $post_id);
    if (is_wp_error($attach_id)) {
        error_log("** Failed to insert attachment for image: $filename");
        return false;
    }

    // Generate attachment metadata and set the featured image
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
    wp_update_attachment_metadata($attach_id, $attach_data);
    set_post_thumbnail($post_id, $attach_id);

    // Update the image name in custom fields (optional if you use ACF)
    update_post_meta($attach_id, '_wp_attachment_image_alt', $data['introText']);
    update_post_meta($post_id, 'ka_course_image_name', $filename_original);
    update_post_meta($attach_id, 'is_course_image', true);

    // Log download time
    $download_time = round(microtime(true) - $download_start, 2);
    error_log("✅ Bilde ferdig lastet ned for kurs ID $post_id (kursID: $location_id) - Tid: {$download_time}s");

    error_log("=== SLUTT: set_featured_image_from_url ===");
    return true;
}

/**
 * Oppdaterer status for hovedkurs basert på status til tilhørende subkurs
 * 
 * @param int|null $main_course_id Spesifikk hovedkurs-ID å sjekke, eller null for å sjekke alle
 * @return void
 */
function kursagenten_update_main_course_status($main_course_id = null) {
   // error_log("=== START: Oppdaterer hovedkurs status ===");
    
    // Hent alle hovedkurs eller et spesifikt hovedkurs
    $args = array(
        'post_type' => 'ka_course',
        'posts_per_page' => -1,
        'post_status' => array('publish', 'draft'),
        'meta_query' => array(
            array(
                'key' => 'ka_is_parent_course',
                'value' => 'yes'
            )
        )
    );

    if ($main_course_id) {
        $args['meta_query'][] = array(
            'key' => 'ka_main_course_id',
            'value' => $main_course_id
        );
    }

    $main_courses = get_posts($args);

    foreach ($main_courses as $main_course) {
        //error_log("Sjekker hovedkurs: {$main_course->ID}");
        
        // Hent alle subkurs for dette hovedkurset
        $sub_courses = get_posts(array(
            'post_type' => 'ka_course',
            'posts_per_page' => -1,
            'post_status' => array('publish', 'draft'),
            'meta_query' => array(
                array(
                    'key' => 'ka_main_course_id',
                    'value' => get_post_meta($main_course->ID, 'ka_main_course_id', true)
                ),
                array(
                    'key' => 'ka_is_parent_course',
                    'compare' => 'NOT EXISTS'
                )
            )
        ));

        //error_log("Fant " . count($sub_courses) . " subkurs for hovedkurs {$main_course->ID}");
        
        // Tell antall publiserte subkurs
        $published_count = 0;
        foreach ($sub_courses as $sub_course) {
            //error_log("Subkurs {$sub_course->ID} status: {$sub_course->post_status}");
            if ($sub_course->post_status === 'publish') {
                $published_count++;
            }
        }
        
        //error_log("Antall publiserte subkurs: $published_count av totalt " . count($sub_courses));

        // Foreldrekursets status skal kun baseres på statusen til underkursene
        // Hvis alle underkurs er inaktive, skal foreldrekurset være inaktivt
        // Hvis minst ett underkurs er aktivt, skal foreldrekurset være aktivt
        if ($published_count === 0 && $main_course->post_status !== 'draft') {
            error_log("Setter hovedkurs {$main_course->ID} til kladd - ingen publiserte subkurs");
            wp_update_post(array(
                'ID' => $main_course->ID,
                'post_status' => 'draft'
            ));
        } elseif ($published_count > 0 && $main_course->post_status !== 'publish') {
            error_log("Setter hovedkurs {$main_course->ID} til publisert - har $published_count publiserte subkurs");
            wp_update_post(array(
                'ID' => $main_course->ID,
                'post_status' => 'publish'
            ));
        }
    }

    //error_log("=== SLUTT: Oppdaterer hovedkurs status ===");
}

function update_all_course_locations() {
    error_log("=== START: update_all_course_locations ===");
    
    // Hent alle kurssteder
    $terms = get_terms([
        'taxonomy' => 'ka_course_location',
        'hide_empty' => false,
    ]);

    if (is_wp_error($terms)) {
        error_log("Feil ved henting av kurssteder: " . $terms->get_error_message());
        return;
    }

    error_log("Fant " . count($terms) . " kurssteder");

    foreach ($terms as $term) {
        error_log("Prosesserer kurssted: " . $term->name);
        
        // Hent alle kurs for dette stedet
        $courses = get_posts([
            'post_type' => 'ka_course',
            'posts_per_page' => -1,
            'tax_query' => [
                [
                    'taxonomy' => 'ka_course_location',
                    'field' => 'term_id',
                    'terms' => $term->term_id,
                ]
            ]
        ]);

        if (empty($courses)) {
            error_log("Ingen kurs funnet for " . $term->name);
            continue;
        }

        error_log("Fant " . count($courses) . " kurs for " . $term->name);

        // Samle all lokasjonsdata fra kursene
        $locations_data = [];
        foreach ($courses as $course) {
            $location_id = get_post_meta($course->ID, 'ka_location_id', true);
            if ($location_id) {
                $course_data = kursagenten_get_course_details($location_id);
                if (!empty($course_data) && !empty($course_data['locations'])) {
                    $locations_data = array_merge($locations_data, $course_data['locations']);
                }
            }
        }

        if (!empty($locations_data)) {
            error_log("Oppdaterer spesifikke lokasjoner for " . $term->name);
            update_specific_locations($term->term_id, ['locations' => $locations_data], false);
        }
    }

    error_log("=== SLUTT: update_all_course_locations ===");
}

if (defined('WP_CLI') && WP_CLI) {
    class Kursagenten_CLI {
        public function update_locations() {
            WP_CLI::log('Starter oppdatering av kurssteder...');
            update_all_course_locations();
            WP_CLI::success('Oppdatering av kurssteder fullført!');
        }
    }

    WP_CLI::add_command('kursagenten', 'Kursagenten_CLI');
}

// Legg til denne nye funksjonen
function get_locations_for_area($location_id, $municipality, $county) {
    error_log("=== START: get_locations_for_area for location_id: $location_id ===");
    
    $courses = kursagenten_get_course_list();
    if (empty($courses)) {
        error_log("FEIL: Kunne ikke hente kursliste for å finne lokasjoner i samme område");
        return [];
    }

    $all_locations = [];
    foreach ($courses as $course) {
        foreach ($course['locations'] as $location) {
            // Sjekk om lokasjonen er i samme område
            $location_matches = false;
            
            if ($municipality && !empty($location['municipality'])) {
                $location_matches = ($location['municipality'] === $municipality);
            } elseif ($county && !empty($location['county'])) {
                $location_matches = ($location['county'] === $county);
            }

            if ($location_matches) {
                $description = $location['placeFreeText'] ?? $location['description'] ?? '';
                if (!empty($description)) {
                    // Slå sammen streetAddress og streetAddressNumber
                    $street_address = trim(($location['address']['streetAddress'] ?? '') . ' ' . ($location['address']['streetAddressNumber'] ?? ''));
                    
                    $location_info = [
                        'description' => $description,
                        'address' => [
                            'street' => $street_address,
                            'zipcode' => $location['address']['zipCode'] ?? '',
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
    
    error_log("Fant " . count($all_locations) . " lokasjoner i samme område");
    error_log("=== SLUTT: get_locations_for_area ===");
    return $all_locations;
}

/**
 * Delete course and coursedates by location_id
 * Deletes:
 * - All coursedates with meta location_id
 * - All course posts (parent/sub) with meta location_id
 * - If no sub-courses remain for the main_course_id, delete the parent course
 */
function kursagenten_delete_course_by_location_id($location_id) {
    error_log("=== START: kursagenten_delete_course_by_location_id for location_id: $location_id ===");

    $location_id = (int) $location_id;
    if (!$location_id) {
        return false;
    }

    // Find course posts with this location_id (could be parent or sub)
    $courses = get_posts([
        'post_type' => 'ka_course',
        'posts_per_page' => -1,
        'post_status' => ['publish', 'draft'],
        'meta_query' => [
            [
                'key' => 'ka_location_id',
                'value' => $location_id,
                'compare' => '='
            ]
        ]
    ]);

    // Always delete coursedates for this location_id first
    $coursedates = get_posts([
        'post_type' => 'ka_coursedate',
        'posts_per_page' => -1,
        'post_status' => ['publish', 'draft'],
        'meta_query' => [
            [ 'key' => 'ka_location_id', 'value' => $location_id ]
        ]
    ]);

    foreach ($coursedates as $date) {
        // Try to clean relationships if helper exists
        $related_courses = get_post_meta($date->ID, 'ka_course_related_course', true);
        if (!empty($related_courses) && is_array($related_courses)) {
            foreach ($related_courses as $related_course_id) {
                if (function_exists('remove_coursedate_from_related_course')) {
                    remove_coursedate_from_related_course($date->ID, (int) $related_course_id);
                }
            }
        }
        wp_delete_post($date->ID, true);
        error_log("Slettet kursdato ID: {$date->ID} for location_id: {$location_id}");
    }

    $deleted_any_course = false;
    $affected_main_course_ids = [];

    foreach ($courses as $course_post) {
        $main_course_id = get_post_meta($course_post->ID, 'ka_main_course_id', true);
        if (!empty($main_course_id)) {
            $affected_main_course_ids[] = (int) $main_course_id;
        }

        wp_delete_post($course_post->ID, true);
        $deleted_any_course = true;
        error_log("Slettet kurs ID: {$course_post->ID} (location_id: {$location_id})");
    }

    // If we deleted a sub-course, check if the parent should be deleted too
    $affected_main_course_ids = array_unique(array_filter($affected_main_course_ids));
    foreach ($affected_main_course_ids as $main_course_id) {
        // Remaining sub-courses for this main_course_id
        $remaining_sub = get_posts([
            'post_type' => 'ka_course',
            'posts_per_page' => 1,
            'post_status' => ['publish', 'draft'],
            'meta_query' => [
                [ 'key' => 'ka_main_course_id', 'value' => $main_course_id, 'compare' => '=' ],
                [ 'key' => 'ka_is_parent_course', 'compare' => 'NOT EXISTS' ]
            ]
        ]);

        if (empty($remaining_sub)) {
            // Delete parent course (is_parent_course = yes)
            $parent_course = get_posts([
                'post_type' => 'ka_course',
                'posts_per_page' => 1,
                'post_status' => ['publish', 'draft'],
                'meta_query' => [
                    [ 'key' => 'ka_main_course_id', 'value' => $main_course_id, 'compare' => '=' ],
                    [ 'key' => 'ka_is_parent_course', 'value' => 'yes', 'compare' => '=' ]
                ]
            ]);

            if (!empty($parent_course)) {
                // Also remove any coursedates that might be tied to the main_course_id (if any)
                $parent_location_id = (int) get_post_meta($parent_course[0]->ID, 'ka_location_id', true);
                if ($parent_location_id) {
                    $parent_dates = get_posts([
                        'post_type' => 'ka_coursedate',
                        'posts_per_page' => -1,
                        'post_status' => ['publish', 'draft'],
                        'meta_query' => [
                            [ 'key' => 'ka_location_id', 'value' => $parent_location_id ]
                        ]
                    ]);
                    foreach ($parent_dates as $pd) {
                        wp_delete_post($pd->ID, true);
                        error_log("Slettet hovedkurs-dato ID: {$pd->ID} for main_course_id: {$main_course_id}");
                    }
                }

                wp_delete_post($parent_course[0]->ID, true);
                error_log("Slettet hovedkurs ID: {$parent_course[0]->ID} (main_course_id: {$main_course_id}) fordi ingen subkurs gjenstår");
            }
        } else {
            // Sync parent status after deletion
            if (function_exists('kursagenten_update_main_course_status')) {
                kursagenten_update_main_course_status((int) $main_course_id);
            }
        }
    }

    error_log("=== SLUTT: kursagenten_delete_course_by_location_id ===");
    return $deleted_any_course;
}

/**
 * Update slugs on sub-courses when location name mapping changes
 * 
 * @param string $old_name The old location name (before mapping)
 * @param string $new_name The new location name (after mapping)
 * @return int Number of posts updated
 */
function kursagenten_update_course_slugs_for_location_mapping($old_name, $new_name) {
    if (empty($old_name) || empty($new_name) || $old_name === $new_name) {
        return 0;
    }

    // Sanitize slugs
    $old_slug = sanitize_title($old_name);
    $new_slug = sanitize_title($new_name);
    
    if ($old_slug === $new_slug) {
        return 0; // No change needed if slugs are the same
    }

    // Find all sub-courses (not parent courses) with the old slug or matching meta field
    // We check both post_name (slug) and ka_sub_course_location meta field
    global $wpdb;
    $post_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT p.ID 
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = 'ka_sub_course_location'
        LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = 'ka_is_parent_course'
        WHERE p.post_type = 'ka_course' 
        AND p.post_status != 'trash'
        AND (pm2.meta_value != 'yes' OR pm2.meta_id IS NULL)
        AND (
            p.post_name = %s 
            OR (pm1.meta_value = %s)
        )",
        $old_slug,
        $old_name
    ));

    if (empty($post_ids)) {
        return 0;
    }

    $updated_count = 0;

    foreach ($post_ids as $post_id) {
        $final_new_slug = $new_slug;
        
        // Check if new slug already exists (avoid conflicts)
        $existing_post = get_page_by_path($final_new_slug, OBJECT, 'ka_course');
        
        if ($existing_post && $existing_post->ID != $post_id) {
            // Slug conflict - append number
            $counter = 1;
            do {
                $final_new_slug = $new_slug . '-' . $counter;
                $existing_post = get_page_by_path($final_new_slug, OBJECT, 'ka_course');
                $counter++;
            } while ($existing_post && $existing_post->ID != $post_id && $counter < 100); // Safety limit
        }

        // Update the post slug
        $result = wp_update_post(array(
            'ID' => $post_id,
            'post_name' => $final_new_slug,
        ), true);

        if (!is_wp_error($result)) {
            // Also update the metadata
            update_post_meta($post_id, 'ka_sub_course_location', sanitize_text_field($new_name));
            
            // Update post title if it contains the old location name
            $post = get_post($post_id);
            if ($post && strpos($post->post_title, ' - ' . $old_name) !== false) {
                $new_title = str_replace(' - ' . $old_name, ' - ' . $new_name, $post->post_title);
                wp_update_post(array(
                    'ID' => $post_id,
                    'post_title' => $new_title,
                ));
            }
            
            $updated_count++;
        }
    }

    return $updated_count;
}

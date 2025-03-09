<?php

function create_or_update_course_and_schedule($course_data, $is_webhook = false) {
    // Retrieve data from API course list
    $location_id = isset($course_data['location_id']) ? (int) $course_data['location_id'] : 0;
    $main_course_id = isset($course_data['main_course_id']) ? (int) $course_data['main_course_id'] : 0;
    $language = sanitize_text_field($course_data['language'] ?? null); 

    // Retrieve data from API for individual courses
    $individual_course_data = kursagenten_get_course_details($location_id);
    if (empty($individual_course_data)) {
        return false;
    }

    // Check if the course already exists
    $existing_courses = get_posts([
        'post_type' => 'course',
        'meta_key' => 'location_id',
        'meta_value' => $location_id,
        'posts_per_page' => -1,
    ]);

    if (!$existing_courses) {
        // Course does not exist: create a new course
        if ((int)$location_id === (int)$main_course_id) {
            // Create as main course
            error_log("DEBUG: Opprettet nytt hovedkurs med location_id: $location_id og main_course_id: $main_course_id");
            return create_new_course($individual_course_data, $main_course_id, $location_id, $language);
        } else {
            // Create as sub-course
            $post_id = create_new_sub_course($individual_course_data, $main_course_id, $location_id, $language);
            error_log("DEBUG: Opprettet nytt underkurs med location_id: $location_id");

            // Check if there are additional locations tied to this main course. Copy the main course if it does not exist.
            $sibling_courses = get_posts([
                'post_type' => 'course',
                'meta_key' => 'main_course_id',
                'meta_value' => $main_course_id,
                'posts_per_page' => -1,
                'meta_query' => [
                    [
                        'key' => 'location_id',
                        'value' => $main_course_id,
                        'compare' => '!=', // Exclude the main course
                    ],
                ],
            ]);

            if (count($sibling_courses) <= 1) { 
                if (!empty($individual_course_data)) {
                    // Use data directly from API to create a new sub-course
                    $individual_course_data = kursagenten_get_course_details($main_course_id);
                    create_new_sub_course($individual_course_data, $main_course_id, $location_id, $language);
                    error_log('DEBUG: Opprettet nytt søskenkurs for hovedkurs med location_id: ' . $main_course_id);
                }
            }
            return $post_id;
        }
    } else {
        // Course exists: update existing course
        foreach ($existing_courses as $course) {
            update_existing_course($course->ID, $individual_course_data, $main_course_id, $location_id, $language);
            error_log('DEBUG: Oppdaterte eksisterende kurs med location_id: ' . $location_id);
        }
        
        return true;
    }
}

function create_new_course($data, $main_course_id, $location_id, $language) {
    $post_id = wp_insert_post([
        'post_title'   => sanitize_text_field($data['name']),
        'post_type'    => 'course',
        'post_status'  => 'publish',
        'post_excerpt' => sanitize_text_field($data['introText']),
    ]);

    if (!is_wp_error($post_id)) {
        // Update shared metadata
        $common_meta_fields = get_common_meta_fields($data, $language);
        foreach ($common_meta_fields as $key => $value) {
            update_post_meta($post_id, $key, $value);
        }
        update_post_meta($post_id, 'main_course_id', (int) $data['id']);
        update_post_meta($post_id, 'is_parent_course', 'yes');
        update_post_meta($post_id, 'meta_description', sanitize_text_field($data['introText']));

        update_course_taxonomies($post_id, $location_id, $data);
                    
        // Associate instructor taxonomy
        $instructors = get_instructors_in_courselist($data, $location_id);
        $location_instructors = $instructors['instructors_location'];
        $data_instructors = array_column($location_instructors, 'fullname');
        update_instructor_taxonomies($post_id, $data_instructors);

        sync_main_course_data($main_course_id);

        set_featured_image_from_url($data, $post_id, $main_course_id, $location_id);

        create_or_update_course_date($data, $post_id, $main_course_id, $data['id']);
    }

    return $post_id;
}

function create_new_sub_course($data, $main_course_id, $location_id, $language) {
    // Check if parent course exists
    $parent_course = get_posts([
        'post_type' => 'course',
        'meta_key' => 'location_id',
        'meta_value' => $main_course_id,
        'posts_per_page' => 1,
    ]);

    if (empty($parent_course)) {
        // Parent course creation logic (if needed)
    } else {
        $parent_id = $parent_course[0]->ID;
    }

    // Create sub-course
    $post_id = wp_insert_post([
        'post_title'   => sanitize_text_field($data['name'] . ' - ' . get_course_location($data)),
        'post_type'    => 'course',
        'post_status'  => 'publish',
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
        update_post_meta($post_id, 'main_course_id', (int) $main_course_id);
        update_post_meta($post_id, 'meta_description', sanitize_text_field($data['introText']));

        update_course_taxonomies($post_id, $data['id'], $data);

        // Fetch instructor data
        $instructors = get_instructors_in_courselist($data, $data['id']);
        $location_instructors = $instructors['instructors_location'];
        $instructor_fullnames = array_column($location_instructors, 'fullname');
        
        update_instructor_taxonomies($post_id, $instructor_fullnames);

        sync_main_course_data($main_course_id);

        set_featured_image_from_url($data, $post_id, $main_course_id, $data['id']);

        create_or_update_course_date($data, $post_id, $main_course_id, $data['id']);
    }

    return $post_id;
}

function update_existing_course($post_id, $data, $main_course_id, $location_id, $language) {
    $is_parent_course = get_post_meta($post_id, 'is_parent_course', true);

    if ($is_parent_course === 'yes') {
        $updated_title = $data['name'];
    } else {
        $updated_title = $data['name'] . ' - ' . get_course_location($data);
    }

    wp_update_post([
        'ID'           => $post_id,
        'post_title'   => sanitize_text_field($updated_title),
        'description'  => 'Updated',
        'post_excerpt' => sanitize_text_field($data['introText']),
        'post_status'  => 'publish',
    ]);

    if (!is_wp_error($post_id)) {
        // Update shared metadata
        $common_meta_fields = get_common_meta_fields($data, $language);
        foreach ($common_meta_fields as $key => $value) {
            update_post_meta($post_id, $key, $value);
        }

        update_course_taxonomies($post_id, $location_id, $data);

        // Associate instructor taxonomy
        $instructors = get_instructors_in_courselist($data, $location_id);
        $location_instructors = $instructors['instructors_location'];
        $data_instructors = array_column($location_instructors, 'fullname');
        update_instructor_taxonomies($post_id, $data_instructors);

        if ($main_course_id) {
            sync_main_course_data($main_course_id);
        }

        set_featured_image_from_url($data, $post_id, $main_course_id, $data['id']);
        create_or_update_course_date($data, $post_id, $main_course_id, $data['id']);
    }
}

function create_or_update_course_date($data, $post_id, $main_course_id, $location_id) { 
    if (!isset($data['locations'])) {
        //error_log('Ingen locations funnet i API-data.');
        return;
    }
    

    // Find correct location based on location_id
    $location = array_filter($data['locations'], function ($loc) use ($location_id) {
        return $loc['courseId'] === $location_id;
    });

    if (empty($location)) {
        //error_log("Lokasjon med ID {$location_id} ble ikke funnet.");
        return;
    }

    $location = reset($location); // Hent første matchende lokasjon

    if (!isset($location['schedules']) || empty($location['schedules'])) {
        //error_log("Ingen schedules funnet for lokasjon ID {$location_id}.");
        return;
    }

    // Loop through all schedules and create/update course dates
    foreach ($location['schedules'] as $schedule) {
        $schedule_id = $schedule['id'] ?? 0;

        // Check if course date already exists based on schedule_id and location_id
        $existing_post = get_posts([
            'post_type' => 'coursedate',
            'meta_query' => [
                ['key' => 'schedule_id', 'value' => $schedule_id],
                ['key' => 'location_id', 'value' => $location_id],
            ],
            'numberposts' => 1,
        ]);

        $coursedate_id = $existing_post[0]->ID ?? null;

        // Get provider data
        // Create signup form url
        $courseprovider = get_option('kag_kursinnst_option_name');
        $provider_id = !empty($courseprovider['ka_tilbyderID']) ? $courseprovider['ka_tilbyderID'] : '';
        $provider_theme = !empty($courseprovider['ka_temaKurs']) ? $courseprovider['ka_temaKurs'] : 'standard';
        $course_signup_url = "https://embed.kursagenten.no/$provider_id/skjema/$location_id/$schedule_id?theme=$provider_theme&gtmevent=add_to_cart";


        // Set up meta fields for course date
        $meta_input = [];

        if (isset($main_course_id)) {   $meta_input['main_course_id'] = $main_course_id;}
        if (isset($location_id)) {      $meta_input['location_id'] = $location_id;}
        if (isset($schedule_id)) {      $meta_input['schedule_id'] = $schedule_id;}
        if (!empty($data['name'])) {    $meta_input['course_title'] = $data['name'];}

        if (!empty($schedule['firstCourseDate'])) {     $meta_input['course_first_date'] = format_date_for_db($schedule['firstCourseDate']);}
        if (!empty($schedule['lastCourseDate'])) {      $meta_input['course_last_date'] = format_date_for_db($schedule['lastCourseDate']);}
        if (!empty($schedule['registrationDeadline'])) {$meta_input['course_registration_deadline'] = format_date_for_db($schedule['registrationDeadline']);}
        if (!empty($schedule['duration'])) {            $meta_input['course_duration'] = $schedule['duration'];}
        if (!empty($schedule['coursetime'])) {          $meta_input['course_time'] = $schedule['coursetime'];}
        if (!empty($schedule['coursetimeType'])) {      $meta_input['course_time_type'] = $schedule['coursetimeType'];}
        if (!empty($schedule['startTime'])) {           $meta_input['course_start_time'] = $schedule['startTime'];}
        if (!empty($schedule['endTime'])) {             $meta_input['course_end_time'] = $schedule['endTime'];}
        if (!empty($schedule['price'])) {               $meta_input['course_price'] = (int) $schedule['price'];}
        if (!empty($schedule['textBeforeAmount'])) {    $meta_input['course_text_before_price'] = sanitize_text_field($schedule['textBeforeAmount']);}
        if (!empty($schedule['textAfterAmount'])) {     $meta_input['course_text_after_price'] = sanitize_text_field($schedule['textAfterAmount']);}
        if (!empty($schedule['courseCode'])) {          $meta_input['course_code'] = $schedule['courseCode'];}
        if (!empty($schedule['formButtonText'])) {      $meta_input['course_button_text'] = $schedule['formButtonText'];}
        if (!empty($schedule['language'])) {            $meta_input['course_language'] = $schedule['language'];}
        if (!empty($schedule['locationRooms']['name'])) {$meta_input['course_location_room'] = $schedule['locationRooms']['name'];}
        if (!empty($schedule['maxParticipants'])) {     $meta_input['course_maxParticipants'] = $schedule['maxParticipants'];}
        if (isset($schedule['showRegistrationForm'])) { $meta_input['course_showRegistrationForm'] = $schedule['showRegistrationForm'];}
        if (isset($schedule['markedAsFull'])) {         $meta_input['course_markedAsFull'] = $schedule['markedAsFull'];}
        if (isset($schedule['isFull'])) {               $meta_input['course_isFull'] = $schedule['isFull'];}
        if (!empty($course_signup_url)) {               $meta_input['course_signup_url'] = $course_signup_url;}
        if (!empty($location['county'])) {              $meta_input['course_location'] = get_course_location($data);} 
        if (!empty($location['description'])) {         $meta_input['course_location_freetext'] = $location['description'];}

        if (!empty($location['address']['streetAddress'])) {        $meta_input['course_address_street'] = $location['address']['streetAddress'];}
        if (!empty($location['address']['streetAddressNumber'])) {  $meta_input['course_address_street_number'] = $location['address']['streetAddressNumber'];}
        if (!empty($location['address']['zipCode'])) {              $meta_input['course_address_zipcode'] = $location['address']['zipCode'];}
        if (!empty($location['address']['place'])) {                $meta_input['course_address_place'] = $location['address']['place'];}


        // Create or update course date
         $post_data = [
             'ID' => $coursedate_id,
             'post_type' => 'coursedate',
             'post_status' => 'publish',
             'post_title' => $data['name'] . ' - ' . get_course_location($data) . ' ' . 
                 (isset($schedule['firstCourseDate']) ? format_date($schedule['firstCourseDate']) : "-uten kursdato"),
             'meta_input'  => $meta_input,
        ];
        

        $instructor_name_array = [];
        if (!empty($schedule['instructors']) && is_array($schedule['instructors'])) {
            foreach ($schedule['instructors'] as $instructor) {
                $instructor_name_array[] = sanitize_text_field($instructor['fullname']);
            }
        }

        $coursedate_post_id = wp_insert_post($post_data);

        if (is_wp_error($coursedate_post_id)) {
            //error_log("FEIL: wp_insert_post mislyktes for schedule ID: " . ($schedule['id'] ?? 'ukjent') . ". Feil: " . $coursedate_post_id->get_error_message());
        } else {
            update_course_taxonomies($coursedate_post_id, $location_id, $data);
            update_instructor_taxonomies($coursedate_post_id, $instructor_name_array);
        }
        

        // Oppdater relasjonen til kurs (post_id)
        if (!empty($post_id)) {
            $current_related_course = get_post_meta($coursedate_post_id, 'course_related_course', true) ?: [];
            if (!is_array($current_related_course)) {
                $current_related_course = (array) $current_related_course; // Sikre array-format
            }

            if (!in_array($post_id, $current_related_course)) {
                $current_related_course[] = $post_id;
                update_post_meta($coursedate_post_id, 'course_related_course', array_unique($current_related_course));
            }
        }

        // Oppdater relasjonen fra kurs til kursdato
        if (!empty($coursedate_post_id)) {
            $related_coursedates = get_post_meta($post_id, 'course_related_coursedate', true) ?: [];
            if (!is_array($related_coursedates)) {
                $related_coursedates = (array) $related_coursedates; // Sikre array-format
            }

            if (!in_array($coursedate_post_id, $related_coursedates)) {
                $related_coursedates[] = $coursedate_post_id;
                update_post_meta($post_id, 'course_related_coursedate', array_unique($related_coursedates));
            }
        }


        //error_log("DEBUG: Sjekker etter eksisterende kursdato for schedule ID: " . ($schedule['id'] ?? 'ukjent') . " og lokasjon ID: $location_id");
        //error_log("DEBUG: Forespørsel returnerte: " . print_r($existing_post, true));

    }
    // Kall opprydningsfunksjonen etter at alle kursdatoer er opprettet/oppdatert
    cleanup_coursedates($location_id, $location['schedules']);
}




function cleanup_coursedates($location_id, $schedules_from_api) {
    // Hent alle kursdatoer for denne lokasjonen
    $coursedates = get_posts([
        'post_type' => 'coursedate',
        'meta_query' => [
            ['key' => 'location_id', 'value' => $location_id],
        ],
        'numberposts' => -1,
    ]);

    // Lag en liste over schedule_id-er fra API-et
    $valid_schedule_ids = array_map(function ($schedule) {
        return $schedule['id'] ?? 0; // Standardverdi for ID-løse schedules
    }, $schedules_from_api);

    //error_log("DEBUG: Gyldige schedule_id-er fra API for lokasjon {$location_id}: " . print_r($valid_schedule_ids, true));

    foreach ($coursedates as $coursedate) {
        $coursedate_id = $coursedate->ID;
        $schedule_id = get_post_meta($coursedate_id, 'schedule_id', true);
        $related_post_id = get_post_meta($coursedate_id, 'course_related_course', true);

        // Håndter kursdatoer med schedule_id = 0
        if ($schedule_id == 0) {
            // Slett kun hvis 0 IKKE er en gyldig schedule_id i API-et
            if (!in_array(0, $valid_schedule_ids)) {
                remove_coursedate_from_related_course($coursedate_id, $related_post_id);
                wp_delete_post($coursedate_id, true);
                //error_log("INFO: Slettet kursdato med schedule_id = 0 for lokasjon {$location_id}");
            }
            continue; // Hopp over til neste kursdato
        }

        // Slett kursdato med ugyldig schedule_id
        if (!in_array($schedule_id, $valid_schedule_ids)) {
            remove_coursedate_from_related_course($coursedate_id, $related_post_id);
            wp_delete_post($coursedate_id, true);
            //error_log("INFO: Slettet kursdato med ugyldig schedule_id {$schedule_id} for lokasjon {$location_id}");
        }
    }
}

function remove_coursedate_from_related_course($coursedate_id, $related_post_ids) {
    if (empty($related_post_ids) || !is_array($related_post_ids)) {
        return; // Ingen relaterte kurs å oppdatere
    }

    foreach ($related_post_ids as $related_post_id) {
        $related_courses = get_post_meta($related_post_id, 'course_related_coursedate', true);

        if (is_array($related_courses)) {
            // Fjern kursdato ID fra array
            $updated_kurs = array_filter($related_courses, function ($id) use ($coursedate_id) {
                return $id != $coursedate_id;
            });

            // Oppdater meta-feltet for kurs
            update_post_meta($related_post_id, 'course_related_coursedate', $updated_kurs);
        }
    }
}





// Felles funksjoner/ helper funkcions and data

// Get data fra enkeltkurs API for meta fields
function get_common_meta_fields($data, $language) {
    return [ 
        'location_id' => (int) $data['id'],
        'course_content' => wp_kses_post($data['description']),
        'course_price' => (int) $data['locations'][0]['price'],
        'course_text_before_price' => sanitize_text_field($data['locations'][0]['textBeforeAmount']),
        'course_text_after_price' => sanitize_text_field($data['locations'][0]['textAfterAmount']),
        'course_difficulty_level' => sanitize_text_field($data['difficultyLevel']),
        'course_type' => sanitize_text_field($data['courseTypes'][0]['description']),//ARRAY
        'course_is_online' => sanitize_text_field($data['isOnlineCourse']),
        'course_municipality' => sanitize_text_field($data['locations'][0]['municipality']),
        'course_county' => sanitize_text_field($data['locations'][0]['county']),
        'course_language' => sanitize_text_field($language),
        'course_external_sign_on' => sanitize_text_field($data['signOnPage']),
        'course_contactperson_name' => sanitize_text_field($data['contactPerson']['name'] ?? ''),
        'course_contactperson_phone' => sanitize_text_field($data['contactPerson']['phoneNumber'] ?? ''),
        'course_contactperson_email' => sanitize_email($data['contactPerson']['email'] ?? ''),
    ];
}

function get_course_location($data) {
    if (!empty($data['locations'][0]['municipality'])) {
        return $data['locations'][0]['municipality'];
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

function format_date_for_db($date_string) {
    if ($date_string === null) {
        return '';
    }
    $date = DateTime::createFromFormat('Y-m-d\TH:i:s', $date_string);
    return $date ? $date->format('Y-m-d H:i:s') : $date_string;
}

function update_course_taxonomies($post_id, $location_id, $data) {
    // Koble kurs til course_location taxonomi
    $course_location = get_course_location($data); // Henter course_location basert på dataen

    if ($course_location) {
        // Sjekk om taxonomien finnes eller opprett den
        $course_location_term = term_exists($course_location, 'course_location');

        if (!$course_location_term) {
            $course_location_term = wp_insert_term($course_location, 'course_location');
        }

        if (!is_wp_error($course_location_term)) {
            // Sett course_location taxonomien for kurset
            wp_set_object_terms($post_id, (int)$course_location_term['term_id'], 'course_location', false);
        }
    }

    // Koble kurs til coursecategory taxonomier
    if (!empty($data['tags']) && is_array($data['tags'])) {
        $course_categories = [];

        foreach ($data['tags'] as $tag) {
            if (!empty($tag['title'])) {
                $course_category = sanitize_text_field($tag['title']);

                // Sjekk om taxonomien finnes eller opprett den
                $course_category_term = term_exists($course_category, 'coursecategory');

                if (!$course_category_term) {
                    $course_category_term = wp_insert_term($course_category, 'coursecategory');
                }

                if (!is_wp_error($course_category_term)) {
                    $course_categories[] = (int)$course_category_term['term_id'];
                }
            }
        }

        if (!empty($course_categories)) {
            // Sett coursecategory taxonomier for kurset
            wp_set_object_terms($post_id, $course_categories, 'coursecategory', false);
        }
    }

}

function sync_main_course_data($main_course_id) {
    // Finn hovedkursets post-ID basert på $main_course_id som meta-verdi
    $main_course_post = get_posts([
        'post_type' => 'course',
        'meta_query' => [
            [
                'key' => 'main_course_id',
                'value' => $main_course_id,
                'compare' => '='
            ],
            [
                'key' => 'is_parent_course',
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
        'post_type' => 'course',
        'meta_query' => [
            'relation' => 'AND',
            [
                'key' => 'main_course_id',
                'value' => $main_course_id,
                'compare' => '='
            ],
            [
                'relation' => 'OR',
                [
                    'key' => 'is_parent_course',
                    'value' => 'yes',
                    'compare' => '!='
                ],
                [
                    'key' => 'is_parent_course',
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
        $course_location_terms = wp_get_object_terms($course->ID, 'course_location', ['fields' => 'ids']);
        $course_category_terms = wp_get_object_terms($course->ID, 'coursecategory', ['fields' => 'ids']);
        $instructor_terms = wp_get_object_terms($course->ID, 'instructors', ['fields' => 'ids']);

        // Hent relaterte kursdatoer
        $course_dates = get_post_meta($course->ID, 'course_related_coursedate', true);
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
        wp_set_object_terms($post_id, $course_locations, 'course_location', false);
    }

    if (!empty($course_categories)) {
        wp_set_object_terms($post_id, $course_categories, 'coursecategory', false);
    }

    if (!empty($instructors)) {
        wp_set_object_terms($post_id, $instructors, 'instructors', false);
    }

    // Oppdater relaterte kursdatoer på hovedkurset
    if (!empty($related_course_dates)) {
        update_post_meta($post_id, 'course_related_coursedate', $related_course_dates);
    }
}



function update_instructor_taxonomies($post_id, $data_instructors) {
    if (!empty($data_instructors) && is_array($data_instructors)) {
        $instructors = [];

        // Behandle instruktørene i dataen
        foreach ($data_instructors as $instructor) {
            $instructor_term = term_exists($instructor, 'instructors'); // Sjekk om taxonomien allerede finnes
            if (!$instructor_term) {
                $instructor_term = wp_insert_term($instructor, 'instructors'); // Opprett taxonomien hvis den ikke finnes
            }
            if (!is_wp_error($instructor_term)) {
                $instructors[] = (int)$instructor_term['term_id']; // Legg til term ID
                
                // Oppdater metafelter for instruktøren
                if (isset($instructor['userId'])) {
                    update_term_meta($instructor_term['term_id'], 'instructor_id', sanitize_text_field($instructor['userId']));
                }
                if (isset($instructor['email'])) {
                    update_term_meta($instructor_term['term_id'], 'instructor_email', sanitize_email($instructor['email']));
                }
                if (isset($instructor['phone'])) {
                    update_term_meta($instructor_term['term_id'], 'instructor_phone', sanitize_text_field($instructor['phone']));
                }
            }
        }

        // Oppdater taxonomien på innlegget
        if (!empty($instructors)) {
            wp_set_object_terms($post_id, $instructors, 'instructors', false);
            error_log("DEBUG: Oppdatert taxonomi 'instructors' på post ID $post_id med term IDs: " . implode(', ', $instructors));
        }
    } else {
        error_log("DEBUG: Ingen instruktørdata funnet for post ID $post_id.");
    }
}



function get_instructors_in_courselist($data, $location_id) {
    $instructors_location = [];

    // Sjekk om 'locations' eksisterer og er en array
    if (!isset($data['locations']) || !is_array($data['locations'])) {
        error_log("Feil: 'locations' mangler eller er ikke en array i kurs: " . $data['id']);
        return ['instructors_location' => []];
    }

    foreach ($data['locations'] as $location) {
        // Sjekk om lokasjonens courseId samsvarer med $location_id
        if (!isset($location['courseId']) || $location['courseId'] != $location_id) {
            continue;
        }

        // Sjekk om 'schedules' eksisterer og er en array
        if (!isset($location['schedules']) || !is_array($location['schedules'])) {
            error_log("Feil: 'schedules' mangler eller er ikke en array for lokasjon: " . $location['courseId']);
            continue;
        }

        foreach ($location['schedules'] as $schedule) {
            // Sjekk om 'instructors' eksisterer og er en array
            if (!isset($schedule['instructors']) || !is_array($schedule['instructors'])) {
                continue;
            }

            foreach ($schedule['instructors'] as $instructor) {
                if (isset($instructor['id'], $instructor['fullname'])) {
                    $instructors_location[$instructor['id']] = [
                        'id' => $instructor['id'],
                        'fullname' => sanitize_text_field($instructor['fullname']),
                        'firstname' => sanitize_text_field($instructor['firstname'] ?? ''),
                        'lastname' => sanitize_text_field($instructor['lastname'] ?? ''),
                        'email' => sanitize_email($instructor['email'] ?? ''),
                        'phone' => sanitize_text_field($instructor['phone'] ?? ''),
                    ];
                }
            }
        }
    }

    // Returner instruktører uten duplikater
    return [
        'instructors_location' => array_values($instructors_location),
    ];
}







function set_featured_image_from_url($data, $post_id, $main_course_id, $location_id) {
    // Check if the current post already has a featured image
    $existing_thumbnail_id = get_post_thumbnail_id($post_id);
    $image_url = $data['bannerImage'] ?? null;
    /*error_log('Eksternt bilde: ' . $image_url);
    error_log('Image URL: ' . var_export($image_url, true));
    error_log('Nåværende bilde: '. $existing_thumbnail_id);

    error_log("DEBUG: Starter funksjon med post_id: $post_id, image_url: " . var_export($image_url, true));
    */
    if ($image_url !== null && $image_url !== '') {
        //error_log("DEBUG: Image URL er gyldig, fortsetter med import.");
        $filename = basename($image_url);
        $stored_image_name = get_post_meta($post_id, 'course_image_name', true);
        /*
        error_log("**** kursID: $post_id, Lokasjon: $location_id,  Course id: " . $data['id']);
        error_log("**** stored_image_name: $stored_image_name");
        error_log("**** filename: $filename");
        */
        // Check if the image already exists and is the same to avoid re-downloading
        if ($existing_thumbnail_id && $stored_image_name === $filename) {
            error_log("** Image already exists for post ID: $post_id, skipping new import.");
            return false;
        }

        // Delete the existing image if it's different
        if ($existing_thumbnail_id) {
            wp_delete_attachment($existing_thumbnail_id, true);
        }

        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['path'] . '/' . $filename;

        // Download the image from the URL
        $ch = curl_init($image_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $image_data = curl_exec($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($image_data === false) {
            error_log("** Failed to fetch image from URL: $image_url. Error: $curl_error");
            return false;
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
            error_log("** Invalid file type for image: $filename");
            return false;
        }

        $formatted_date = date('Y-m-d H:i:s');

        $new_filename = 'kursbilde-zrs_' . $data['name'] . '-' . $filename;
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
        update_post_meta($post_id, 'course_image_name', $filename);
        update_post_meta($attach_id, 'is_course_image', true);

        error_log("** Image successfully downloaded and set as featured image for post ID: $post_id - kurs: " . $data['name']);

        return true;
    } else {
        error_log("DEBUG: Image URL er tom eller null, avslutter tidlig.");
        if ($existing_thumbnail_id) {
            error_log("eksisterende bilde $existing_thumbnail_id, slettes for post_id: $post_id");
            wp_delete_attachment($existing_thumbnail_id, true);
            update_post_meta($post_id, 'course_image_name', '');

        }
        return false;
    }

    // If neither image_url nor parent image is available and post already has an image, do nothing
    if ($existing_thumbnail_id) {
        error_log("** Image already exists for post ID: $post_id, no changes needed.");
        return false;
    }

    // If neither image_url nor parent image is available, return false
    //error_log("** No image available for post ID: " . $post_id);
    //return false;
}

// If the location_id is missing in the API response, set all related courses to draft and delete all related course dates
function handle_missing_location_in_api($location_id) {
    error_log("DEBUG: Starter håndtering av manglende location_id: {$location_id}");

    // Finn alle kurs med denne location_id og sett status til kladd
    $courses = get_posts([
        'post_type' => 'course',
        'meta_key' => 'location_id',
        'meta_value' => $location_id,
        'posts_per_page' => -1,
    ]);

    foreach ($courses as $course) {
        wp_update_post([
            'ID' => $course->ID,
            'post_status' => 'draft',
        ]);
        error_log("DEBUG: Kurs med ID {$course->ID} satt til kladd.");
    }

    // Slett alle kursdatoer med denne location_id
    $coursedates = get_posts([
        'post_type' => 'coursedate',
        'meta_key' => 'location_id',
        'meta_value' => $location_id,
        'posts_per_page' => -1,
    ]);

    foreach ($coursedates as $coursedate) {
        wp_delete_post($coursedate->ID, true);
        error_log("DEBUG: Kursdato med ID {$coursedate->ID} slettet.");
    }

    error_log("DEBUG: Ferdig med håndtering av manglende location_id: {$location_id}");
}

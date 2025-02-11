<?php

// Call this where you want to trigger the update for a specific courseId
//create_or_update_course_and_schedule(224284);

// Functions:
// process_webhook_data
// create_or_update_course_and_schedule - main function
// find_earliest_schedule_in_api_list
// find_parent_kurs
// create_kurs_post
// update_kurs_post
// create_or_update_enkeltkurs_post
// set_featured_image_from_url
// handle_kurskategori_taxonomy
// handle_kurssted_taxonomy
// handle_instruktor_taxonomy
// handle_kurs_deletion - remove kurs no longer in api
// handle_enkeltkurs_deletion - remove kurs no longer in api
// format_date
// cleanup_duplicate_enkeltkurs_posts
// update_kurskategori_image_if_empty - get from kurs image if bedriftsinformsjon settings are "bruk-kursbilde"
// set_user_uploaded_image_flag_for_taxonomy - kurskategori


function process_webhook_data( $request ) {
    // Retrieve the JSON body from the POST request
    $body = json_decode( $request->get_body(), true );
    
    // Check if the necessary 'CourseId' field exists
    if ( isset( $body['CourseId'] ) && is_numeric( $body['CourseId'] ) ) {
        $course_id = $body['CourseId'];

        // Check if this webhook has been processed recently (e.g., within 10 seconds)
        if ( get_transient( 'webhook_processed_' . $course_id ) ) {
            error_log('Skipping duplicate webhook for CourseId: ' . $course_id);
            return new WP_REST_Response( 'Duplicate webhook skipped.', 200 );
        }

        // Set a transient to mark this webhook as processed (for 10 seconds)
        set_transient( 'webhook_processed_' . $course_id, true, 10 );

        // Call your function with the course ID
        create_or_update_course_and_schedule( $course_id );
        
        // Return a success response
        return new WP_REST_Response( 'Webhook processed successfully.', 200 );
    } else {
        // Return an error if the CourseId is missing or invalid
        return new WP_REST_Response( 'Invalid CourseId provided.', 400 );
    }
}


function create_or_update_course_and_schedule($courseId) {

    // Fetch full course list for checking parent kurs
    $full_course_list = wpgetapi_endpoint('kurs_api', 'liste', array('debug' => false));

    // Fetch API data for the given course ID
    $api_data = wpgetapi_endpoint('kurs_api', 'enkeltkurs', array(
        'debug' => false,
        'endpoint_variables' => array($courseId)
    ));

    if (!is_array($api_data)) {
        error_log('API returned a non-array response: ' . print_r($api_data, true));
        return; // or handle the error appropriately
    }

    // Get existing kurs and enkeltkurs posts IDs
    $existing_kurs_posts = get_posts(array('post_type' => 'kurs', 'numberposts' => -1));
    $existing_enkeltkurs_posts = get_posts(array('post_type' => 'enkeltkurs', 'numberposts' => -1));

    $existing_kurs_ids = array_map(function($post) { return $post->ID; }, $existing_kurs_posts);

    $existing_enkeltkurs_ids = [];

    foreach ($existing_enkeltkurs_posts as $post) {
        $existing_enkeltkurs_ids[$post->ID] = get_field('id', $post->ID);
    }

    // Fetch all course IDs from the full course list (from 'kurs_api -> liste')
    $full_course_list_ids = array_column($full_course_list, 'id');

    // Find all enkeltkurs IDs from the full course list
    $full_enkeltkurs_ids = [];

    foreach ($full_course_list as $course) {
        if (isset($course['locations']) && is_array($course['locations'])) {
            foreach ($course['locations'] as $location) {
                if (isset($location['courseId'])) {
                    $full_enkeltkurs_ids[] = $location['courseId'];
                }

                // Collect schedule IDs from location schedules
                if (isset($location['schedules']) && is_array($location['schedules'])) {
                    foreach ($location['schedules'] as $schedule) {
                        if (isset($schedule['id'])) {
                            $full_enkeltkurs_ids[] = $schedule['id'];
                        }
                    }
                }
            }
        }
    }


    // Remove any duplicate IDs
    $full_enkeltkurs_ids = array_unique($full_enkeltkurs_ids);

    // Perform course creation or updates
    if ($api_data && is_array($api_data)) {
        $course = $api_data;
        $locations = $course['locations'] ?? [];

        $find_kurs_id = find_parent_kurs($course['id'], $full_course_list) ?? $course['id'];
        error_log("+ Find parent post ID: $find_kurs_id for ");

        // Try to find the parent kurs by the course ID (or another unique identifier)
        $existing_parent_post = get_posts(array(
            'post_type' => 'kurs',
            'meta_key' => 'id', // Assuming the 'id' field stores the course ID
            'meta_value' => $find_kurs_id,
            'numberposts' => 1,
        ));
        
        // Create or update kurs
		// Check if the parent kurs post exists
		if (!empty($existing_parent_post)) {
			$parent_post_id = isset($existing_parent_post[0]) ? (int) $existing_parent_post[0]->ID : null;
			error_log("+ Found parent post ID: $parent_post_id");
			update_kurs_post($course, $parent_post_id, $full_course_list);
		} else {
			error_log("+ Parent post ID not found, creating kurs");
			$parent_post_id = create_kurs_post($course, $full_course_list);
		}
        if (!$parent_post_id) {
            error_log("+ Error: Parent kurs could not be created.");
            return; // Exit the function if the parent kurs could not be created
        }

        if ($parent_post_id && in_array($parent_post_id, $existing_kurs_ids)) {
            unset($existing_kurs_ids[array_search($parent_post_id, $existing_kurs_ids)]);
        }

        // First, delete any enkeltkurs not in the full course list
        foreach ($existing_enkeltkurs_ids as $post_id => $enkeltkurs_id) {
            if (!in_array($enkeltkurs_id, $full_enkeltkurs_ids)) {
                handle_enkeltkurs_deletion($post_id);
            }
        }

        // Enkeltkurs (process this after the parent kurs updates)
        if (!empty($course['locations']) && is_array($course['locations'])) {
            foreach ($course['locations'] as $location) {
                if (isset($location['schedules']) && is_array($location['schedules'])) {
                    foreach ($location['schedules'] as $schedule) {
                        $enkeltkurs_id = $schedule['id'] ?? $location['courseId'];
                        error_log("+ Enkeltkurs ID: $enkeltkurs_id - run create or update function for this ID ");
                        $enkeltkurs_post_id = create_or_update_enkeltkurs_post($course, $location, $schedule, $enkeltkurs_id, $parent_post_id, $full_course_list);

                        if ($enkeltkurs_post_id && isset($existing_enkeltkurs_ids[$enkeltkurs_post_id])) {
                            unset($existing_enkeltkurs_ids[$enkeltkurs_post_id]);
                        }

                        if (isset($location)){
                            handle_kurssted_taxonomy($location);
                        }
                        if (isset($schedule['instructors'])){
                            handle_instruktor_taxonomy($schedule['instructors']);
                        }
                    }
                }
            }
        }
    } else {
        error_log('API did not return valid course data for courseId: ' . $courseId);
        return; // Exit the function early if data is invalid
    }

    // Delete any kurs not in the full course list
    foreach ($existing_kurs_ids as $post_id) {
        $kurs_id = get_field('id', $post_id); // Assuming the ID is stored in a custom field
        if (!in_array($kurs_id, $full_course_list_ids)) {
            handle_kurs_deletion($post_id);
        }
    }

    cleanup_duplicate_enkeltkurs_posts();

}

// Function to find the earliest date and associated information in the API list based on the kurs ID
function find_earliest_schedule_in_api_list($course_id, $full_course_list) {
    $earliest_schedule = null;
    $earliest_date = null;

    if (!is_array($full_course_list) || empty($full_course_list)) {
        error_log('Full course list is empty or invalid.');
        return;
    }

    foreach ($full_course_list as $course) {
        if (isset($course['id']) && $course['id'] === $course_id) {
            if (isset($course['locations']) && is_array($course['locations'])) {
                foreach ($course['locations'] as $location) {
                    if (isset($location['schedules']) && is_array($location['schedules'])) {
                        foreach ($location['schedules'] as $schedule) {
                            if (!empty($schedule['firstCourseDate'])) {
                                $schedule_date = DateTime::createFromFormat('Y-m-d\TH:i:s', $schedule['firstCourseDate']);
                                if ($schedule_date && (!$earliest_date || $schedule_date < $earliest_date)) {
                                    $earliest_date = $schedule_date;
                                    // Capture the entire schedule associated with the earliest date
                                    $earliest_schedule = [
                                        'date' => $schedule_date->format('d.m.Y'),
                                        'lastCourseDate' => $schedule['lastCourseDate'] ?? null,
                                        'registrationDeadline' => $schedule['registrationDeadline'] ?? null,
                                        'duration' => $schedule['duration'] ?? null,
                                        'coursetime' => $schedule['coursetime'] ?? null,
                                        'coursetimeType' => $schedule['coursetimeType'] ?? null,
                                        'price' => $schedule['price'] ?? null,
                                        'textBeforeAmount' => $schedule['textBeforeAmount'] ?? null,
                                        'textAfterAmount' => $schedule['textAfterAmount'] ?? null,
                                        'maxParticipants' => $schedule['maxParticipants'] ?? null,
                                        'showRegistrationForm' => $schedule['showRegistrationForm'] ?? null,
                                        'formButtonText' => $schedule['formButtonText'] ?? null,
                                        'markedAsFull' => $schedule['markedAsFull'] ?? null,
                                        'isFull' => $schedule['isFull'] ?? null,
                                        'innhold' => $course['description'] ?? '',
                                        'language' => $schedule['language'] ?? null,
                                        'address' => $location['address'] ?? null,
                                        'municipality' => $location['municipality'] ?? null,
                                        'county' => $location['county'] ?? null,
                                        'nettkurs' => $course['isOnlineCourse'] ?? null,
                                        'sted_fritekst' => $location['placeFreeText'] ?? null,
                                        'kurslokale' => $schedule['locationRooms'][0]['name'] ?? null,
                                        'vansklighetsgrad' => $course['difficultyLevel'] ?? null,
                                        'kurstype' => $course['courseTypes']['description'] ?? null,
                                        'kontaktperson_navn' => $course['contactPerson']['name'] ?? null,
                                        'kontaktperson_tlf' => $course['contactPerson']['phoneNumber'] ?? null,
                                        'kontaktperson_epost' => $course['contactPerson']['email'] ?? null,
                                        'lokasjon_id' => $location['courseId'] ?? null // Add location ID
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    // If no earliest date was found, use any available schedule data without the date
    if (!$earliest_schedule) {
        // If no valid schedule with date was found, fall back to generic information
        foreach ($full_course_list as $course) {
            // Check if course ID exists and matches the course_id
            if (isset($course['id']) && $course['id'] === $course_id) {
                // Check if 'locations' exists and is an array
                if (isset($course['locations']) && is_array($course['locations'])) {
                    foreach ($course['locations'] as $location) {
                        // Check if 'schedules' exists and is an array
                        if (isset($location['schedules']) && is_array($location['schedules'])) {
                            foreach ($location['schedules'] as $schedule) {
                                // Return the first available schedule data (without requiring a date)
                                $earliest_schedule = [
                                    'date' => null, // No date found
                                    'lastCourseDate' => $schedule['lastCourseDate'] ?? null,
                                    'registrationDeadline' => $schedule['registrationDeadline'] ?? null,
                                    'duration' => $schedule['duration'] ?? null,
                                    'coursetime' => $schedule['coursetime'] ?? null,
                                    'coursetimeType' => $schedule['coursetimeType'] ?? null,
                                    'price' => $schedule['price'] ?? null,
                                    'textBeforeAmount' => $schedule['textBeforeAmount'] ?? null,
                                    'textAfterAmount' => $schedule['textAfterAmount'] ?? null,
                                    'maxParticipants' => $schedule['maxParticipants'] ?? null,
                                    'showRegistrationForm' => $schedule['showRegistrationForm'] ?? null,
                                    'formButtonText' => $schedule['formButtonText'] ?? null,
                                    'markedAsFull' => $schedule['markedAsFull'] ?? null,
                                    'isFull' => $schedule['isFull'] ?? null,
                                    'innhold' => $course['description'] ?? '',
                                    'language' => $schedule['language'] ?? null,
                                    'address' => $location['address'] ?? null,
                                    'municipality' => $location['municipality'] ?? null,
                                    'county' => $location['county'] ?? null,
                                    'nettkurs' => $course['isOnlineCourse'] ?? null,
                                    'sted_fritekst' => $location['placeFreeText'] ?? null,
                                    'kurslokale' => $schedule['locationRooms'][0]['name'] ?? null,
                                    'vansklighetsgrad' => $course['difficultyLevel'] ?? null,
                                    'kurstype' => $course['courseTypes']['description'] ?? null,
                                    'kontaktperson_navn' => $course['contactPerson']['name'] ?? null,
                                    'kontaktperson_tlf' => $course['contactPerson']['phoneNumber'] ?? null,
                                    'kontaktperson_epost' => $course['contactPerson']['email'] ?? null,
                                    'lokasjon_id' => $location['courseId'] ?? null // Add location ID
                                ];
                                break 3; // Exit all loops once data is found
                            }
                        }
                    }
                }
            }
        }
    }

    return $earliest_schedule;
}


function find_parent_kurs($course_id, $full_course_list) {
    foreach ($full_course_list as $course) {
        // Check if the 'locations' key exists and is an array
        if (isset($course['locations']) && is_array($course['locations'])) {
            foreach ($course['locations'] as $location) {
                if (isset($location['courseId']) && $location['courseId'] == $course_id) {
                    return $course['id']; // Return the parent kurs id
                }
            }
        }
    }
    return null; // No parent found
}


function create_kurs_post($course, $full_course_list) {
    
    $name = $course['name'];
    $ingress = $course['introText'];
    $locations = $course['locations'];
	
	if (empty($course['name'])) {
		error_log("createKurs: Error: Missing course name.");
		return false;
	}

    // Find the earliest schedule for this kurs
    $earliest_schedule = find_earliest_schedule_in_api_list($course['id'], $full_course_list);

    // Check if a date was found in the earliest schedule
    if (!empty($earliest_schedule['date'])) {
        $formatted_first_course_date = $earliest_schedule['date'];
        $post_date = DateTime::createFromFormat('d.m.Y', $formatted_first_course_date)
            ->modify('-2192 days')->format('Y-m-d H:i:s');
    } else {
        // If no valid date is found, use the current date and modify it as a fallback
        $post_date = (new DateTime())->modify('-1 days')->format('Y-m-d H:i:s');
    }


    // Prepare post data for creation
    $post_data = array(
        'post_title' => sanitize_text_field($course['name']),
        'post_type' => 'kurs',
        'post_status' => 'publish',
		'post_date' => $post_date,
		'post_excerpt' => sanitize_text_field($ingress),
        'meta_input' => array(
            'id' => sanitize_text_field($course['id']),
        ),
    );
	
	// Insert the new post and get its ID
    $post_id = wp_insert_post($post_data);

    if (is_wp_error($post_id) || !$post_id) {
        error_log("createKurs: Error: Failed to create kurs.");
        return false;
    }

    //error_log("createKurs: Successfully created kurs with ID: $post_id");

	$acf_fields = [
		'forste_kursdato' => $earliest_schedule['date'] ?? '',
		'siste_kursdato' => format_date($earliest_schedule['lastCourseDate'] ?? ''),
		'frist' => format_date($earliest_schedule['registrationDeadline'] ?? ''),
		'varighet' => $earliest_schedule['duration'] ?? '',
		'tidspunkt' => $earliest_schedule['coursetime'] ?? '',
		'tidspunkt_type' => $earliest_schedule['coursetimeType'] ?? '',
		'pris' => $earliest_schedule['price'] ?? '',
		'pris_tekst_for_belop' => $earliest_schedule['textBeforeAmount'] ?? '',
		'pris_tekst_etter_belop' => $earliest_schedule['textAfterAmount'] ?? '',
		'maks_deltagere' => $earliest_schedule['maxParticipants'] ?? '',
		'vis_registreringsskjema' => $earliest_schedule['showRegistrationForm'] ?? '',
		'knappetekst_pamelding' => $earliest_schedule['formButtonText'] ?? '',
		'markert_fullt' => $earliest_schedule['markedAsFull'] ?? '',
        'innhold' => $earliest_schedule['innhold'] ?? '',
		'er_fullt' => $earliest_schedule['isFull'] ?? '',
		'sprak' => $earliest_schedule['language'] ?? '',
		'kurssted_adresse' => $earliest_schedule['address']['streetAddress'] ?? '',
		'kurssted_adresse_nr' => $earliest_schedule['address']['streetAddressNumber'] ?? '',
		'kurssted_adresse_postnr' => $earliest_schedule['address']['zipCode'] ?? '',
		'kurssted_adresse_sted' => $earliest_schedule['address']['place'] ?? '',
		'kommune' => $earliest_schedule['municipality'] ?? '',
		'fylke' => $earliest_schedule['county'] ?? '',
        'innhold' => $earliest_schedule['innhold'] ?? '',
        'nettkurs' => $earliest_schedule['nettkurs'] ?? '',
        'sted_fritekst' => $earliest_schedule['sted_fritekst'] ?? '',
        'kurslokale' => $earliest_schedule['kurslokale'] ?? '',
        'vansklighetsgrad' => $earliest_schedule['vansklighetsgrad'] ?? '',
        'kurstype' => $earliest_schedule['kurstype'] ?? '',
        'kontaktperson_navn' => $earliest_schedule['kontaktperson_navn'] ?? '',
        'kontaktperson_tlf' => $earliest_schedule['kontaktperson_tlf'] ?? '',
        'kontaktperson_epost' => $earliest_schedule['kontaktperson_epost'] ?? '',
        'lokasjon_id' => $earliest_schedule['lokasjon_id'] ?? '' // Add location ID to ACF fields

	];

	foreach ($acf_fields as $key => $value) {
		if (!empty($value)) {
			update_field($key, $value, $post_id);
		}
	}

	// Handle Featured image
	$lokasjon_id = get_field('lokasjon_id', $post_id);
	$parent_image_name = '';
	if (!empty($course['bannerImage'])) {
		set_featured_image_from_url($course['bannerImage'], $post_id, $course['name'], $course['introText'], $lokasjon_id, $course['id'], $parent_image_name );
	} else {
		// Keep the image if it's already set
		$existing_thumbnail_id = get_post_thumbnail_id($post_id);
		if ($existing_thumbnail_id) {
			error_log("createKurs: No image available for post ID: $post_id, clearing the existing image.");
			wp_delete_attachment($existing_thumbnail_id, true);
			update_field('kursbilde_navn', '', $post_id);
		}
	}


	// Get business information options
	$bedriftsinformasjon_options = get_option('bedriftsinformasjon_option_name');
	$firma = $bedriftsinformasjon_options['kursagentID'] ?? ''; // Ensure fallback
	$temaKursliste = $bedriftsinformasjon_options['temaKursliste'] ?? '';
	$temaKurs = $bedriftsinformasjon_options['temaKurs'] ?? '';

	//$url_kursliste = 'https://embed.kursagenten.no/'. $firma .'/Kurs/'. $course['id'] .'?theme='. $temaKurs;
	$url_kursliste = 'https://embed.kursagenten.no/'. $firma .'/Kurs/'. $earliest_schedule['lokasjon_id'] .'?theme='. $temaKurs;
	update_field('kursliste', $url_kursliste, $post_id);

    // Construct link to open modal (Kadence Conversions) with correct classes and data-url
    $url_kursliste_modal = '<a href="#modal--signup" class="pamelding pameldingsknapp" data-url="' . $url_kursliste . '">' .$earliest_schedule['formButtonText'] . '</a>';
    update_field('kurspamelding_skjema_modal', $url_kursliste_modal, $post_id);

	// Taxonomies

	// Step into the full course list and collect all instructors, tags, and kurssteder
	$all_instructors = [];
	$all_tags = [];
	$all_municipalities = [];

	// Loop through each location to gather instructors, tags, and municipalities
	foreach ($full_course_list as $course_item) {
		if ($course_item['id'] === $course['id']) {
			// Collect tags
			$course_tags = wp_list_pluck($course_item['tags'] ?? [], 'title');
			if (!empty($course_tags)) {
				$all_tags = array_merge($all_tags, $course_tags);
			}

			foreach ($course_item['locations'] as $location) {
				// Collect municipalities or counties
				$municipality = !empty($location['municipality']) ? $location['municipality'] : ($location['county'] ?? '');
				if (!empty($municipality)) {
					$all_municipalities[] = $municipality;
				}

				// Collect instructors from each schedule
				foreach ($location['schedules'] as $schedule) {
					$schedule_instructors = wp_list_pluck($schedule['instructors'] ?? [], 'fullname');
					if (!empty($schedule_instructors)) {
						$all_instructors = array_merge($all_instructors, $schedule_instructors);
					}
				}
			}
		}
	}

	// Remove duplicates from instructors, tags, and municipalities
	$all_instructors = array_unique($all_instructors);
	$all_tags = array_unique($all_tags);
	$all_municipalities = array_unique($all_municipalities);

	// Update taxonomies with collected data
	if (!empty($all_instructors)) {
		wp_set_object_terms($post_id, $all_instructors, 'instruktor');  // Changed to $post_id
	}

	if (!empty($all_tags)) {
        error_log("createKurs: Kaller opp wp_set_object_terms.");
		wp_set_object_terms($post_id, $all_tags, 'kurskategori');  // Changed to $post_id
        error_log("createKurs: Kaller opp handle_kurskategori_taxonomy.");
		handle_kurskategori_taxonomy($all_tags, $firma, $temaKursliste); // Assuming this function handles taxonomy setup
	}

	if (!empty($all_municipalities)) {
		wp_set_object_terms($post_id, $all_municipalities, 'kurssted');  // Changed to $post_id
	}


    return $post_id;
}


function update_kurs_post($course, $parent_post_id, $full_course_list) {
	if (!$parent_post_id) {
        error_log("updateKurs: Error: Parent post ID is missing.");
        return false;
    }
    
    $name = $course['name'];
    $kurs_id = get_field('id', $parent_post_id);
    $ingress = $course['introText'];
    $locations = $course['locations'];

    error_log("updateKurs: Updating parent kurs for course ID: " . $course['id'] . "- Kurs id: $kurs_id - Parent_post_id: $parent_post_id");


    // Find the earliest schedule for this kurs
    $earliest_schedule = find_earliest_schedule_in_api_list($course['id'], $full_course_list);

    // Check if a date was found in the earliest schedule
    if (!empty($earliest_schedule['date'])) {
        $formatted_first_course_date = $earliest_schedule['date'];
        $post_date = DateTime::createFromFormat('d.m.Y', $formatted_first_course_date)
            ->modify('-2192 days')->format('Y-m-d H:i:s');
    } else {
        // If no valid date is found, use the current date and modify it as a fallback
        $post_date = (new DateTime())->modify('-1 days')->format('Y-m-d H:i:s');
    }


    // If is parent course
    if ($course['id'] == $kurs_id) {
        wp_update_post([
            'post_title' => sanitize_text_field($course['name']),
            'ID' => (int) $parent_post_id,
            'post_date' => $post_date,
            'post_excerpt' => $ingress,
        ]);
        update_field('innhold', $course['description'], $parent_post_id);
    } else {
        wp_update_post([
            'ID' => (int) $parent_post_id,
            'post_date' => $post_date,
        ]);
    }

    if ($parent_post_id) {
        $acf_fields = [
            'forste_kursdato' => $earliest_schedule['date'] ?? '',
            'siste_kursdato' => format_date($earliest_schedule['lastCourseDate'] ?? ''),
            'frist' => format_date($earliest_schedule['registrationDeadline'] ?? ''),
            'varighet' => $earliest_schedule['duration'] ?? '',
            'tidspunkt' => $earliest_schedule['coursetime'] ?? '',
            'tidspunkt_type' => $earliest_schedule['coursetimeType'] ?? '',
            'pris' => $earliest_schedule['price'] ?? '',
            'pris_tekst_for_belop' => $earliest_schedule['textBeforeAmount'] ?? '',
            'pris_tekst_etter_belop' => $earliest_schedule['textAfterAmount'] ?? '',
            'maks_deltagere' => $earliest_schedule['maxParticipants'] ?? '',
            'vis_registreringsskjema' => $earliest_schedule['showRegistrationForm'] ?? '',
            'knappetekst_pamelding' => $earliest_schedule['formButtonText'] ?? '',
            'markert_fullt' => $earliest_schedule['markedAsFull'] ?? '',
            'innhold' => $earliest_schedule['innhold'] ?? '',
            'er_fullt' => $earliest_schedule['isFull'] ?? '',
            'sprak' => $earliest_schedule['language'] ?? '',
            'kurssted_adresse' => $earliest_schedule['address']['streetAddress'] ?? '',
            'kurssted_adresse_nr' => $earliest_schedule['address']['streetAddressNumber'] ?? '',
            'kurssted_adresse_postnr' => $earliest_schedule['address']['zipCode'] ?? '',
            'kurssted_adresse_sted' => $earliest_schedule['address']['place'] ?? '',
            'kommune' => $earliest_schedule['municipality'] ?? '',
            'fylke' => $earliest_schedule['county'] ?? '',
            'innhold' => $course['description'] ?? '',        
            'nettkurs' => $earliest_schedule['nettkurs'] ?? '',
            'sted_fritekst' => $earliest_schedule['sted_fritekst'] ?? '',
            'kurslokale' => $earliest_schedule['kurslokale'] ?? '',
            'vansklighetsgrad' => $earliest_schedule['vansklighetsgrad'] ?? '',
            'kurstype' => $earliest_schedule['kurstype'] ?? '',
            'kontaktperson_navn' => $earliest_schedule['kontaktperson_navn'] ?? '',
            'kontaktperson_tlf' => $earliest_schedule['kontaktperson_tlf'] ?? '',
            'kontaktperson_epost' => $earliest_schedule['kontaktperson_epost'] ?? '',
            'lokasjon_id' => $earliest_schedule['lokasjon_id'] ?? '' // Add location ID to ACF fields

        ];

        foreach ($acf_fields as $key => $value) {
            if (!empty($value)) {
                update_field($key, $value, $parent_post_id);
            }
        }

        // Handle Featured image
        $lokasjon_id = get_field('lokasjon_id', $parent_post_id);
        $parent_image_name = '';
        if (!empty($course['bannerImage'])) {
            set_featured_image_from_url($course['bannerImage'], $parent_post_id, $course['name'], $course['introText'], $lokasjon_id, $course['id'], $parent_image_name );
        } else {
            // Keep the image if it's already set
            $existing_thumbnail_id = get_post_thumbnail_id($parent_post_id);
            if ($existing_thumbnail_id) {
                error_log("createKurs: No image available for post ID: $parent_post_id, clearing the existing image.");
                wp_delete_attachment($existing_thumbnail_id, true);
                update_field('kursbilde_navn', '', $parent_post_id);
            }
        }


        // Get business information options
        $bedriftsinformasjon_options = get_option('bedriftsinformasjon_option_name');
        $firma = $bedriftsinformasjon_options['kursagentID'] ?? ''; // Ensure fallback
        $temaKursliste = $bedriftsinformasjon_options['temaKursliste'] ?? '';
        $temaKurs = $bedriftsinformasjon_options['temaKurs'] ?? '';

        //$url_kursliste = 'https://embed.kursagenten.no/'. $firma .'/Kurs/'. $course['id'] .'?theme='. $temaKurs;
        $url_kursliste = 'https://embed.kursagenten.no/'. $firma .'/Kurs/'. $earliest_schedule['lokasjon_id'] .'?theme='. $temaKurs;
        update_field('kursliste', $url_kursliste, $parent_post_id);

        // Construct link to open modal (Kadence Conversions) with correct classes and data-url
        $url_kursliste_modal = '<a href="#modal--signup" class="pamelding pameldingsknapp" data-url="' . $url_kursliste . '">' .$earliest_schedule['formButtonText'] . '</a>';
        update_field('kurspamelding_skjema_modal', $url_kursliste_modal, $parent_post_id);

        //Taxonomier

        // Step into the full course list and collect all instructors, tags, and kurssteder
        $all_instructors = [];
        $all_tags = [];
        $all_municipalities = [];

        // Loop through each location to gather instructors, tags, and municipalities
        foreach ($full_course_list as $course_item) {
            if ($course_item['id'] === $course['id']) {
                // Collect tags
                $course_tags = wp_list_pluck($course_item['tags'] ?? [], 'title');
                if (!empty($course_tags)) {
                    $all_tags = array_merge($all_tags, $course_tags);
                }

                foreach ($course_item['locations'] as $location) {
                    // Collect municipalities or counties
                    $municipality = !empty($location['municipality']) ? $location['municipality'] : ($location['county'] ?? '');
                    if (!empty($municipality)) {
                        $all_municipalities[] = $municipality;
                    }

                    // Collect instructors from each schedule
                    foreach ($location['schedules'] as $schedule) {
                        $schedule_instructors = wp_list_pluck($schedule['instructors'] ?? [], 'fullname');
                        if (!empty($schedule_instructors)) {
                            $all_instructors = array_merge($all_instructors, $schedule_instructors);
                        }
                    }
                }
            }
        }

        // Remove duplicates from instructors, tags, and municipalities
        $all_instructors = array_unique($all_instructors);
        $all_tags = array_unique($all_tags);
        $all_municipalities = array_unique($all_municipalities);

        // Update taxonomies with collected data
        if (!empty($all_instructors)) {
            wp_set_object_terms($parent_post_id, $all_instructors, 'instruktor');
        }

        if (!empty($all_tags)) {
            wp_set_object_terms($parent_post_id, $all_tags, 'kurskategori');
            handle_kurskategori_taxonomy($all_tags, $firma, $temaKursliste); // Assuming this function handles taxonomy setup
        }

        if (!empty($all_municipalities)) {
            wp_set_object_terms($parent_post_id, $all_municipalities, 'kurssted');
        }

        //////

    } else {
        error_log("createKurs: Error: Parent kurs could not be created.");
    }

    return $parent_post_id;
}

function create_or_update_enkeltkurs_post($course, $location, $schedule, $enkeltkurs_id, $parent_post_id, $full_course_list) {
    // Use municipality or county for the title
    $location_name = !empty($location['municipality']) ? $location['municipality'] : $location['county'] ?? '';

    $enkeltkurs_title = sprintf('%s - %s %s', $course['name'], $location_name, format_date($schedule['firstCourseDate'] ?? ''));

    $existing_post = get_posts(array(
        'post_type' => 'enkeltkurs',
        'meta_key' => 'id',
        'meta_value' => $enkeltkurs_id,
        'numberposts' => 1,
    ));
    error_log("-- Finn existing posts med enkeltkurs_id $enkeltkurs_id: " . print_r($existing_post, true));
    // Find the parent kurs using the full course list
    $parent_kurs_id = find_parent_kurs($course['id'], $full_course_list) ?? $parent_post_id;

    $post_date = !empty($schedule['firstCourseDate'])
        ? (DateTime::createFromFormat('d.m.Y', format_date($schedule['firstCourseDate'])))->modify('-2192 days')->format('Y-m-d H:i:s')
        : (new DateTime())->modify('-1 days')->format('Y-m-d H:i:s');

    if (!empty($existing_post)) {
    $existing_post = $existing_post[0]; // Access the first post in the array
        //error_log("enkeltKurs: Updating enkeltkurs for course ID: " . $course['id'] . ", postID: $existing_post->ID - enkeltkurs id: $enkeltkurs_id - schedule[id]: " . $schedule['id'] . " -  title: $enkeltkurs_title - excerpt: " . $course['introText']);
        $enkeltkurs_post_id = wp_update_post([
            'post_title' => $enkeltkurs_title,
            'ID' => $existing_post->ID,
            'post_date' => $post_date,
            'post_excerpt' => $course['introText'],
        ]);
    } else {
        //error_log("enkeltKurs: Creating enkeltkurs for course ID: " . $course['id'] . ", postID: $existing_post->ID - enkeltkurs id: $enkeltkurs_id - schedule[id]: " . $schedule['id'] . " -   title: $enkeltkurs_title");
        $enkeltkurs_post_id = wp_insert_post([
            'post_title' => $enkeltkurs_title,
            'post_excerpt' => $course['introText'],
            'post_type' => 'enkeltkurs',
            'post_status' => 'publish',
            'post_date' => $post_date,
        ]);
    }


    if ($enkeltkurs_post_id) {
        // Update ACF fields for enkeltkurs
        $acf_fields = [
            'kursnavn' => $course['name'],
            'id' => $enkeltkurs_id ?? '',
            'kursid_hovedkurs' => $parent_kurs_id, // Store the correct parent kurs ID
            'lokasjon_id' => $location['courseId'] ?? '',
            'nettkurs' => $course['isOnlineCourse'] ?? '',
            'sted_fritekst' => $location['description'] ?? '',
            'kommune' => $location['municipality'] ?? '',
            'fylke' => $location['county'] ?? '',
            'forste_kursdato' => format_date($schedule['firstCourseDate'] ?? ''),
            'siste_kursdato' => format_date($schedule['lastCourseDate'] ?? ''),
            'frist' => format_date($schedule['registrationDeadline'] ?? ''),
            'varighet' => $schedule['duration'] ?? '',
            'tidspunkt' => $schedule['coursetime'] ?? '',
            'tidspunkt_type' => $schedule['coursetimeType'] ?? '',
            'pris' => $schedule['price'] ?? '',
            'pris_tekst_for_belop' => $schedule['textBeforeAmount'] ?? '',
            'pris_tekst_etter_belop' => $schedule['textAfterAmount'] ?? '',
            'maks_deltagere' => $schedule['maxParticipants'] ?? '',
            'vis_registreringsskjema' => $schedule['showRegistrationForm'] ?? '',
            'knappetekst_pamelding' => $schedule['formButtonText'] ?? '',
            'markert_fullt' => $schedule['markedAsFull'] ?? '',
            'er_fullt' => $schedule['isFull'] ?? '',
            'sprak' => $schedule['language'] ?? '',
            'kurslokale' => $schedule['locationRooms'][0]['name'] ?? '',
            'innhold' => $course['description'] ?? '',
            'vansklighetsgrad' => $course['difficultyLevel'] ?? '',
            'kurstype' => $course['courseTypes']['description'] ?? '',
            'kontaktperson_navn' => $course['contactPerson']['name'] ?? '',
            'kontaktperson_tlf' => $course['contactPerson']['phoneNumber'] ?? '',
            'kontaktperson_epost' => $course['contactPerson']['email'] ?? '',
        ];

        foreach ($acf_fields as $key => $value) {
            if (!empty($value)) {
                update_field($key, $value, $enkeltkurs_post_id);
            }
        }

        // Update relationship field to link enkeltkurs to parent kurs
        update_field('foreldrekurs_id', $parent_kurs_id, $enkeltkurs_post_id);

        // Update the parent kurs to include the newly added enkeltkurs
        $existing_enkeltkurs_ids = get_field('enkeltkurs_id', $parent_kurs_id);
        if (!$existing_enkeltkurs_ids) {
            $existing_enkeltkurs_ids = [];
        }

        // Ensure that only post IDs are stored in $existing_enkeltkurs_ids
        $existing_enkeltkurs_ids = array_map(function($post) {
            return is_object($post) ? $post->ID : (int) $post; // Extract ID if it's a WP_Post object, or cast to int if not
        }, $existing_enkeltkurs_ids);

        if (!in_array($enkeltkurs_post_id, $existing_enkeltkurs_ids)) {
            $existing_enkeltkurs_ids[] = $enkeltkurs_post_id;
            update_field('enkeltkurs_id', $existing_enkeltkurs_ids, $parent_kurs_id);
        }


        $instructors = wp_list_pluck($schedule['instructors'] ?? [], 'fullname');
        if (!empty($instructors)) {
            wp_set_object_terms($enkeltkurs_post_id, array_unique($instructors), 'instruktor');
        }


        // Handle Featured image
        $lokasjon_id = get_field('lokasjon_id', $enkeltkurs_post_id);
        $kursbilde_navn = get_field('kursbilde_navn', $enkeltkurs_post_id);
        $parent_image_name = get_field('kursbilde_navn', $parent_post_id);

        if (!empty($course['bannerImage'])) {
            set_featured_image_from_url($course['bannerImage'], $enkeltkurs_post_id, $course['name'], $course['introText'], $lokasjon_id, $course['id'], $parent_image_name,  $parent_post_id);
        } else {
            // Reuse the parent image if available
            set_featured_image_from_url(null, $enkeltkurs_post_id, $course['name'], $course['introText'], $lokasjon_id, $course['id'], $parent_image_name, $parent_post_id);
        }



        // Get business information options
        $bedriftsinformasjon_options = get_option('bedriftsinformasjon_option_name');
        $firma = $bedriftsinformasjon_options['kursagentID'] ?? ''; // Ensure fallback
        $temaKursliste = $bedriftsinformasjon_options['temaKursliste'] ?? '';
        $temaKurs = $bedriftsinformasjon_options['temaKurs'] ?? '';

        $url_hovedkurs = 'https://embed.kursagenten.no/'. $firma .'/Kurs/'. $course['id'] .'?theme='. $temaKursliste;
        update_field('kursliste', $url_hovedkurs, $enkeltkurs_post_id);

        $url_enkeltkurs = 'https://embed.kursagenten.no/'. $firma .'/skjema/'. $lokasjon_id .'/' . $enkeltkurs_id . '?theme='. $temaKurs;
        update_field('kurspamelding_skjema', $url_enkeltkurs, $enkeltkurs_post_id);

        // Construct link to open modal (Kadence Conversions) with correct classes and data-url
        $url_enkeltkurs_modal = '<a href="#modal--signup" class="pamelding pameldingsknapp" data-url="' . $url_enkeltkurs . '">' . $schedule['formButtonText'] . '</a>';
        update_field('kurspamelding_skjema_modal', $url_enkeltkurs_modal, $enkeltkurs_post_id);

        $tags = wp_list_pluck($course['tags'] ?? [], 'title');
        if (!empty($tags)) {
            wp_set_object_terms($enkeltkurs_post_id, $tags, 'kurskategori');
            handle_kurskategori_taxonomy($tags, $firma, $temaKursliste);
        }

        if (!empty($location_name)) {
            wp_set_object_terms($enkeltkurs_post_id, $location_name, 'kurssted');
        }

        $existing_enkeltkurs = get_field('enkeltkurs_id', $parent_post_id);
        if (!$existing_enkeltkurs) {
            $existing_enkeltkurs = [];
        }
        // Ensure that only post IDs are stored in $existing_enkeltkurs_ids
        $existing_enkeltkurs = array_map(function($post) {
            return is_object($post) ? $post->ID : (int) $post; // Extract ID if it's a WP_Post object, or cast to int if not
        }, $existing_enkeltkurs);

        if (!in_array($enkeltkurs_post_id, $existing_enkeltkurs)) {
            $existing_enkeltkurs[] = $enkeltkurs_post_id;
            update_field('enkeltkurs_id', $existing_enkeltkurs, $parent_post_id);
        }

        // Update relationship field to link enkeltkurs to parent kurs
        update_field('foreldrekurs_id', $parent_post_id, $enkeltkurs_post_id);

    }

    return $enkeltkurs_post_id;
}

//////////////////////////

function set_featured_image_from_url($image_url, $post_id, $course_name, $course_ingress, $lokasjon_id, $course_id, $parent_image_name, $parent_post_id = null) {
    // Check if the current post already has a featured image
    $existing_thumbnail_id = get_post_thumbnail_id($post_id);

    //$existing_thumbnail_id
    //$parent_post_id
    //$post_id
    //$lokasjon_id
    /*
    error_log("* post_id: $post_id");
    error_log("* course_id: $course_id");
    error_log("* lokasjon_id: $lokasjon_id");
    error_log("* parent_post_id: $parent_post_id");
    error_log("* existing_thumbnail_id: $existing_thumbnail_id");
    error_log("* image_url: $image_url");
    error_log("* parent_image_name: $parent_image_name");
    error_log("* course_name: $course_name");*/

     // Enkeltkurs: If there's no image URL and we have an image, and its the same as parent post, delete it
    if (empty($image_url) && $existing_thumbnail_id && $parent_post_id) {
        error_log("* No parent image: Empty image url, thumb exists, is enkeltkurs post ID: $post_id , kursID: $course_id");
        
        $enkeltkurs_id = get_field('id', $post_id);
        /*error_log("* No parent image: Empty image url, thumb exists, course_id: $course_id, lokasjon_id: $lokasjon_id");
        error_log("course_id type: " . gettype($course_id));
        error_log("lokasjon_id type: " . gettype($lokasjon_id));*/

        //if ($parent_image_name === $child_image_name) {
        if ((string)$course_id === (string)$lokasjon_id) {
            update_field('kursbilde_navn', '', $post_id);
            wp_delete_attachment($existing_thumbnail_id, true);
            error_log("* No parent image: Image deleted");
            return true;
        }
    }
    // Parent kurs: If there's no image URL and we have an image, and its a parent post, delete it
    if (empty($image_url) && $existing_thumbnail_id && !$parent_post_id) {
        error_log("* No image for parent: Empty image url, thumb exists, is kurs -post ID: $post_id , kursID: $course_id");
        wp_delete_attachment($existing_thumbnail_id, true);
        update_field('kursbilde_navn', '', $post_id);
        error_log("* No image for parent: Image deleted");
        return true;
    }
    
    // Enkeltkurs: If there's no image URL and we have a parent post, use the parent image
    if (empty($image_url) && !$existing_thumbnail_id && $parent_post_id) {
        error_log("* Use parent image: Empty image url, thumb doesnt exists, has parent -post ID: $post_id , kursID: $course_id");
        $parent_thumbnail_id = get_post_thumbnail_id($parent_post_id);
        if ($parent_thumbnail_id) {
            // Set the parent kurs image as the featured image for this post
            $parent_image_name = get_field('kursbilde_navn', $parent_post_id);
            set_post_thumbnail($post_id, $parent_thumbnail_id);
            update_field('kursbilde_navn', $parent_image_name, $post_id);
            error_log("** Reusing parent kurs image for post ID: $post_id");
            return true; // Reused parent image, no further processing needed
        } else {
            error_log("** No image found for parent kurs or current post ID: $post_id");
            return false;
        }
    }

    // If an image URL is provided, proceed to download the image
    if (!empty($image_url)) {
        $filename = basename($image_url);
        $stored_image_name = get_field('kursbilde_navn', $post_id);

        error_log("**** kursID: $post_id, Lokasjon: $lokasjon_id,  Course id: $course_id");
        error_log("**** stored_image_name: $stored_image_name");
        error_log("**** filename: $filename");
        // Check if it's enkeltkurs and has it's own image. Skip import.
        if ($existing_thumbnail_id && $lokasjon_id != $course_id && $stored_image_name != $filename) {//ulikt bildenavn forskjellig kursid
            error_log("* Ulikt bildenavn og ulik kursID: $post_id, skipping new import. Lokasjon: $lokasjon_id Course id: $course_id");
            error_log("* Lagret bildenavn: $stored_image_name Filnavn: $filename");
            return false;
        }

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

        $formatted_date = date('Y-m-d H:i:s', strtotime('2015-01-01'));

        $new_filename = 'kursbilde-zrs_' . $course_name . '-' . $filename;
        $attachment = array(
            'post_mime_type' => $wp_filetype['type'],
            'post_title'     => sanitize_file_name($new_filename),
            'post_content'   => $course_ingress,
            'post_excerpt'   => $course_ingress,
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
        update_post_meta($attach_id, '_wp_attachment_image_alt', $course_name);
        update_field('kursbilde_navn', $filename, $post_id);
        update_post_meta($attach_id, 'kursbilde', true);

        error_log("** Image successfully downloaded and set as featured image for post ID: $post_id - kurs: $course_name");

        return true;
    }

    // If neither image_url nor parent image is available and post already has an image, do nothing
    if ($existing_thumbnail_id) {
        error_log("** Image already exists for post ID: $post_id, no changes needed.");
        return false;
    }

    // If neither image_url nor parent image is available, return false
    error_log("** No image available for post ID: $post_id");
    return false;
}



//////////////////////////////////

function handle_kurskategori_taxonomy($tags, $firma, $temaKursliste) {
    global $full_course_list, $bedriftsinformasjon_options;

    $firma = $firma ?? ''; 
    $temaKursliste = $temaKursliste ?? '';
    //error_log("Term firma: $firma ");
    //error_log("Term temaKursliste: $temaKursliste ");
    foreach ($tags as $tagg) {
        $term = get_term_by('name', $tagg, 'kurskategori');
        if ($term) {
            //error_log("Term funnet i handle_kurskategori: $term ");
            $term_custom_id = 'kurskategori_' . $term->term_id;
            $url_kategori = 'https://embed.kursagenten.no/' . $firma . '/Kursliste?theme=' . $temaKursliste . '&tags=' . $tagg;
            update_field('kursliste', $url_kategori, $term_custom_id);
            
            // Check if 'skjul_i_lister' field is defined and handle it

            $vis = get_field('skjul_i_lister', $term_custom_id);
            if ($vis !== "Skjul") {
                update_field('skjul_i_lister', "Vis", $term_custom_id);
                error_log("Field 'skjul_i_lister' is undefined or empty for term_custom_id: " . $term_custom_id);
            }
            
            $skjema = get_field('pameldingskjema_kurstagg', $term_custom_id);
            if ($skjema === null || $skjema != "Nei") {
                update_field('pameldingskjema_kurstagg', "Ja", $term_custom_id);
            }

            // Call the function to handle image update if necessary
            //error_log('Update kurskategori image if empty for: $term ');
            update_kurskategori_image_if_empty( $term->term_id, $full_course_list, $bedriftsinformasjon_options );
        }
    }
}

function handle_kurssted_taxonomy($location) {
    // Use municipality if it exists, otherwise fallback to county
    $location_name = !empty($location['municipality']) ? $location['municipality'] : $location['county'];

    if ($location_name) {
        // Insert or get the term by name (municipality or county)
        $term = wp_insert_term($location_name, 'kurssted');
        
        if (!is_wp_error($term)) {
            $term_id = $term['term_id'];
        } else {
            $term = get_term_by('name', $location_name, 'kurssted');
            $term_id = $term->term_id;
        }

        // If the term is valid, update its metadata
        if ($term_id) {
            $address_fields = [
                'kurssted_adresse' => $location['address']['streetAddress'] ?? '',
                'kurssted_adresse_nr' => $location['address']['streetAddressNumber'] ?? '',
                'kurssted_adresse_postnr' => $location['address']['zipCode'] ?? '',
                'kurssted_adresse_sted' => $location['address']['place'] ?? '',
            ];

            foreach ($address_fields as $key => $value) {
                if (!empty($value)) {
                    update_field($key, $value, 'kurssted_' . $term_id);
                }
            }
        }
    }
}

function handle_instruktor_taxonomy($instructors) {
    if (isset($instructors) && is_array($instructors)) {
        foreach ($instructors as $instructor) {
            $term = wp_insert_term($instructor['fullname'], 'instruktor');

            if (!is_wp_error($term)) {
                $term_id = $term['term_id'];
            } else {
                $term = get_term_by('name', $instructor['fullname'], 'instruktor');
                $term_id = $term->term_id;
            }

            if ($term_id) {
                $fields = [
                    'instruktor_fornavn' => $instructor['firstname'] ?? '',
                    'instruktor_etternavn' => $instructor['lastname'] ?? '',
                    'instruktor_epost' => $instructor['email'] ?? '',
                    'instruktor_telefon' => $instructor['phone'] ?? '',
                ];

                foreach ($fields as $key => $value) {
                    if (!empty($value)) {
                        update_term_meta($term_id, $key, $value);
                    }
                }
            }
        }
    } else {
        error_log("@ No instructors found for this schedule.");
    }
}



function handle_kurs_deletion($post_id) {
    wp_delete_post($post_id, true);
}

function handle_enkeltkurs_deletion($post_id) {
    wp_delete_post($post_id, true);
}

function format_date($date_string) {
    if ($date_string === null) {
        return '';
    }

    $date = DateTime::createFromFormat('Y-m-d\TH:i:s', $date_string);
    return $date ? $date->format('d.m.Y') : $date_string;
}

function cleanup_duplicate_enkeltkurs_posts() {
    // Fetch all existing 'enkeltkurs' posts
    $existing_enkeltkurs_posts = get_posts(array(
        'post_type' => 'enkeltkurs',
        'numberposts' => -1,
    ));

    $processed_ids = array();

    foreach ($existing_enkeltkurs_posts as $post) {
        // Get the 'enkeltkurs' id from the meta field
        $enkeltkurs_id = get_post_meta($post->ID, 'id', true);
        
        // Get the 'foreldrekurs_id' (relationship field) for the parent kurs
        $parent_kurs_id = get_field('foreldrekurs_id', $post->ID);
        
        if (in_array($enkeltkurs_id, $processed_ids)) {
            // Check if the post is connected to a parent kurs (foreldrekurs_id)
            if (empty($parent_kurs_id)) {
                // If there's no parent kurs, delete this post as a duplicate
                wp_delete_post($post->ID, true);
                error_log("Duplicate enkeltkurs removed: Post ID $post->ID with enkeltkurs ID $enkeltkurs_id and no parent kurs");
            } else {
                error_log("Skipping post ID $post->ID because it is connected to a parent kurs with ID $parent_kurs_id");
            }
        } else {
            // Mark this enkeltkurs_id as processed
            $processed_ids[] = $enkeltkurs_id;
        }
    }
}

// Bruk bilde fra kurs hvis valgt i bedriftsinnstillinger, og kurs har bilde, og ikke har lastet opp sitt eget
function update_kurskategori_image_if_empty( $term_id, $full_course_list, $bedriftsinformasjon_options ) {
    // Check if the admin option allows setting a kursbilde
    $bedriftsinformasjon_options = get_option('bedriftsinformasjon_option_name');
    if ( $bedriftsinformasjon_options['kursvalg_bilder'] === 'bruk-kursbilde' ) {
        
        // Get the current value of the 'bilde_kurskategori' field for the term
        $image_field = get_field('bilde_kurskategori', 'kurskategori_' . $term_id);
        $user_uploaded_image = get_field('user_uploaded_image', 'kurskategori_' . $term_id);
        //error_log("Bruk kursbilde for: $term_id - $image_field - $user_uploaded_image");

        // Only proceed if the field is empty and not user-uploaded
        if ( empty( $image_field ) && !$user_uploaded_image ) {
            error_log("Bildefelt tomt for: $term_id");
            // 1. Try pulling image from connected 'kurs' or 'enkeltkurs' posts' featured image
            $connected_posts = get_posts( array(
                'post_type' => array('kurs', 'enkeltkurs'),
                'tax_query' => array(
                    array(
                        'taxonomy' => 'kurskategori',
                        'field'    => 'term_id',
                        'terms'    => $term_id,
                    ),
                ),
                'posts_per_page' => 1, // Limit to one post
            ) );

            if ( $connected_posts ) {
                // Get the featured image ID from the connected post
                $image_id = get_post_thumbnail_id( $connected_posts[0]->ID );
                if ( $image_id ) {
                    // Update the ACF field for the taxonomy term
                    update_field('bilde_kurskategori', $image_id, 'kurskategori_' . $term_id);
                    return;
                }
            }

           // 2. If no connected post image, pull from API data ($full_course_list)
            if (!empty($full_course_list) && is_array($full_course_list)) {
                foreach ($full_course_list as $course) {
                    // Check if tags are present and iterate through them
                    if (isset($course['tags']) && is_array($course['tags'])) {
                        foreach ($course['tags'] as $tag) {
                            if ($tag['title'] === $term_name) { // Assuming $term_name is the current kurskategori term name
                                // Loop through locations to find cmsLogo
                                if (isset($course['locations']) && is_array($course['locations'])) {
                                    foreach ($course['locations'] as $location) {
                                        $image_url = $location['cmsLogo'] ?? null; // Use cmsLogo as the image
                                        if ($image_url) {
                                            // Upload the image from URL and set it in ACF field
                                            $image_id = set_featured_image_from_url($image_url);
                                            if ($image_id) {
                                                update_field('bilde_kurskategori', $image_id, 'kurskategori_' . $term_id);
                                            }
                                        }
                                        break; // Break after finding the first match
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                error_log('Error: $full_course_list is empty or not an array');
            }


        }
    }
}

// Merk hvis bruker laster opp eget kategoribilde
function set_user_uploaded_image_flag_for_taxonomy( $term_id, $tt_id, $taxonomy ) {
    if ( $taxonomy === 'kurskategori' ) {
        //error_log('Tax er kurskategori');
        // Check if the image field is set for the term
        $image_field = get_field('bilde_kurskategori', 'kurskategori_' . $term_id);
        if ( !empty($image_field) ) {
            error_log('Bildefelt er ikke tomt, true');
            update_field('user_uploaded_image', true, 'kurskategori_' . $term_id); // Mark as user uploaded
        } else {
            error_log('Bildefelt tomt, false');
            update_field('user_uploaded_image', false, 'kurskategori_' . $term_id); // Reset flag
        }
    }
}
add_action('acf/save_term', 'set_user_uploaded_image_flag_for_taxonomy', 0, 4);
add_action('saved_term', 'set_user_uploaded_image_flag_for_taxonomy', 0, 4);

// Merk hvis bruker oppdaterer via Admin Columns Pro i listevisning
function acp_set_user_uploaded_image_flag_for_taxonomy(AC\Column $column, $id, $value) {
        // Get the term by its ID
        $term = get_term($id, 'kurskategori');
        
        // Make sure the term exists
        if ($term && !is_wp_error($term)) {
            error_log('AC: Tax er kurskategori');
            
            // Check if the image field is set for the term
            $image_field = get_field('bilde_kurskategori', 'kurskategori_' . $term->term_id);
            if (!empty($image_field)) {
                error_log('AC: Bildefelt er ikke tomt, true');
                update_field('user_uploaded_image', true, 'kurskategori_' . $term->term_id); // Mark as user uploaded
            } else {
               error_log('AC: Bildefelt tomt, false');
                update_field('user_uploaded_image', false, 'kurskategori_' . $term->term_id); // Reset flag
            }
        }
    //}
}

add_action('acp/editing/saved', 'acp_set_user_uploaded_image_flag_for_taxonomy', 10, 3);



// Justerer felter på enkeltkurs
function copy_ekstern_link_to_enkeltkurs($post_id) {
    if (get_post_type($post_id) !== 'kurs') {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    $ekstern_link = get_post_meta($post_id, 'ekstern_link', true);
    $ekstern_link_tekst = get_post_meta($post_id, 'ekstern_link_tekst', true);

    if (!empty($ekstern_link)) {
        if (empty($ekstern_link_tekst)) {
        $ekstern_link_tekst = 'Meld deg på';
        update_post_meta($post_id, 'ekstern_link_tekst', $ekstern_link_tekst);
        }
    }

    
    $enkeltkurs_ids = get_field('enkeltkurs_id', $post_id);

    if (!empty($enkeltkurs_ids)) {
        if (!empty($ekstern_link)) {
            foreach ($enkeltkurs_ids as $enkeltkurs_id) {
                // Oppdater feltet 'ekstern_link' i hver enkeltkurs-post
                update_post_meta($enkeltkurs_id, 'ekstern_link', $ekstern_link);
                update_post_meta($enkeltkurs_id, 'ekstern_link_tekst', $ekstern_link_tekst);
            }
        }else{
            foreach ($enkeltkurs_ids as $enkeltkurs_id) {
                delete_post_meta($enkeltkurs_id, 'ekstern_link');
                delete_post_meta($enkeltkurs_id, 'ekstern_link_tekst');
            }
        }
    }
}

add_action('save_post', 'copy_ekstern_link_to_enkeltkurs');
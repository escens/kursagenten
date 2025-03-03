<?php

function create_or_update_course_and_schedule($course_data, $is_webhook = false) {
    // Hent data fra API kursliste
    $lokasjon_id = isset($course_data['lokasjon_id']) ? (int) $course_data['lokasjon_id'] : 0;
    $hovedkurs_id = isset($course_data['hovedkurs_id']) ? (int) $course_data['hovedkurs_id'] : 0;
    $sprak = sanitize_text_field($course_data['sprak'] ?? null); 

    // Hent data fra API for enkeltkurs
    $enkeltkurs_data = kursagenten_get_course_details($lokasjon_id);
    if (empty($enkeltkurs_data)) {
        //error_log("Feil: API returnerte ingen data for lokasjon_id: $lokasjon_id.");
        return false;
    }

    // Sjekk om kurset allerede finnes 
    $existing_courses = get_posts([
        'post_type' => 'kurs',
        'meta_key' => 'lokasjon_id',
        'meta_value' => $lokasjon_id,
        'posts_per_page' => -1,
    ]);
    ////error_log("Antall eksisterende kurs funnet for lokasjon_id $lokasjon_id: " . count($existing_courses)); 


    if (!$existing_courses) {
        // Kurset finnes ikke: opprett nytt kurs
        if ((int)$lokasjon_id === (int)$hovedkurs_id) {
            // Opprett som hovedkurs
            return create_new_kurs($enkeltkurs_data, $hovedkurs_id, $lokasjon_id, $sprak);
        } else {
            // Opprett som underkurs
            $post_id = create_new_kurs_under_hovedkurs($enkeltkurs_data, $hovedkurs_id, $lokasjon_id, $sprak);

            // Sjekk om det er flere lokasjoner tilknyttet dette hovedkurset. Kopier hovedkurset hvis det ikke finnes.
            $sibling_courses = get_posts([
                'post_type' => 'kurs',
                'meta_key' => 'hovedkurs_id',
                'meta_value' => $hovedkurs_id,
                'posts_per_page' => -1,
                'meta_query' => [
                    [
                        'key' => 'lokasjon_id',
                        'value' => $hovedkurs_id,
                        'compare' => '!=', // Ekskluder hovedkurset
                    ],
                ],
            ]);
            
            if (count($sibling_courses) <= 1) { 
                if (!empty($enkeltkurs_data)) {
                    // Bruk data direkte fra API for å opprette nytt underkurs 
                    $enkeltkurs_data = kursagenten_get_course_details($hovedkurs_id);
                    create_new_kurs_under_hovedkurs($enkeltkurs_data, $hovedkurs_id, $lokasjon_id, $sprak);
                } else {
                }
            }
            return $post_id;
        }
    } else {
        // Kurset finnes: oppdater eksisterende kurs 
        foreach ($existing_courses as $course) {
            update_existing_kurs($course->ID, $enkeltkurs_data, $hovedkurs_id, $lokasjon_id, $sprak);
        }

        return true;
    }
}

function create_new_kurs($data, $hovedkurs_id, $lokasjon_id, $sprak) {
    $post_id = wp_insert_post([
        'post_title'   => sanitize_text_field($data['name']),
        'post_type'    => 'kurs',
        'post_status'  => 'publish',
        'post_excerpt' => sanitize_text_field($data['introText']),
    ]);

    if (!is_wp_error($post_id)) {
        // Oppdater felles metadata
        $common_meta_fields = get_common_meta_fields($data, $sprak);
        foreach ($common_meta_fields as $key => $value) {
            update_post_meta($post_id, $key, $value);
        }
        update_post_meta($post_id, 'hovedkurs_id', (int) $data['id']);
        update_post_meta($post_id, 'er_foreldrekurs', 'ja');
        update_post_meta($post_id, 'meta_beskrivelse', sanitize_text_field($data['introText']));

        update_kurs_taxonomies($post_id, $lokasjon_id, $data);
                    
        // Koble instruktor-taxonomien
        /*$instructors = get_instructors_in_courselist($hovedkurs_id, $lokasjon_id);
        $instructors_lokasjon = $instructors['instructors_lokasjon'];
        $data_instruktorer = array_column($instructors_lokasjon, 'fullname');
        error_log("DEBUG create_new_course: Liste med instruktører på lokasjon_id for post_id: $post_id: " . implode(', ', $data_instruktorer));
        update_instruktor_taxonomies($post_id, $data_instruktorer);
        */
        sync_hovedkurs_taxonomies($hovedkurs_id);

        create_or_update_kursdato($data, $post_id, $hovedkurs_id, $data['id']);
    }

    return $post_id;
}

function create_new_kurs_under_hovedkurs($data, $hovedkurs_id, $lokasjon_id, $sprak) {
    // Sjekk om foreldrekurs eksisterer
     $parent_kurs = get_posts([
        'post_type' => 'kurs',
        'meta_key' => 'lokasjon_id',
        'meta_value' => $hovedkurs_id,
        'posts_per_page' => 1,
    ]);

    if (empty($parent_kurs)) {
        // Opprett foreldrekurs hvis det ikke finnes
        // EDIT: Kommentert ut å opprette foreldrekurs
        /*$parent_id = create_new_kurs([
            'kursnavn' => sanitize_text_field($data['name'] . ' - Hovedkurs'),
            'lokasjon_id' => (int) $hovedkurs_id,
            'hovedkurs_id' => (int) $hovedkurs_id,
        ]);*/
    } else {
        $parent_id = $parent_kurs[0]->ID;
    }

    // Opprett underkurs
    $post_id = wp_insert_post([
        'post_title'   => sanitize_text_field($data['name'] . ' - ' . get_kurssted($data)),
        'post_type'    => 'kurs',
        'post_status'  => 'publish',
        'post_parent'  => (int) $parent_id,
        'post_excerpt' => sanitize_text_field($data['introText']),
    ]);

    if (!is_wp_error($post_id)) {
        // Oppdater felles metadata
        $common_meta_fields = get_common_meta_fields($data, $sprak);
        foreach ($common_meta_fields as $key => $value) {
            update_post_meta($post_id, $key, $value);
        }
        update_post_meta($post_id, 'hovedkurs_id', (int) $hovedkurs_id);
        update_post_meta($post_id, 'meta_beskrivelse', sanitize_text_field($data['introText']));

        update_kurs_taxonomies($post_id, $lokasjon_id, $data);
        // Koble instruktor-taxonomien
        $instructors = get_instructors_in_courselist($hovedkurs_id, $lokasjon_id);
        $instructors_lokasjon = $instructors['instructors_lokasjon'];
        $data_instruktorer = array_column($instructors_lokasjon, 'fullname');
        error_log("DEBUG create_new_course_under_hovedkurs: Liste med instruktører på lokasjon_id for post_id: $post_id: " . implode(', ', $data_instruktorer));
        update_instruktor_taxonomies($post_id, $data_instruktorer);
        
        sync_hovedkurs_taxonomies($hovedkurs_id);

        create_or_update_kursdato($data, $post_id, $hovedkurs_id, $data['id']);
    }

    return $post_id;
}


function update_existing_kurs($post_id, $data, $hovedkurs_id, $lokasjon_id, $sprak) {
    $is_foreldrekurs = get_post_meta($post_id, 'er_foreldrekurs', true);
    
    if ($is_foreldrekurs === 'ja') {
        $oppdatert_tittel = $data['name'];
    }else{
        $oppdatert_tittel = $data['name'] . ' - ' . get_kurssted($data);
    }
    wp_update_post([
        'ID'           => $post_id,
        'post_title'   => sanitize_text_field($oppdatert_tittel),
        'description'   => 'Oppdatert',
        'post_excerpt' => sanitize_text_field($data['introText']),
    ]);

    if (!is_wp_error($post_id)) {
        // Oppdater felles metadata
        $common_meta_fields = get_common_meta_fields($data, $sprak);
        foreach ($common_meta_fields as $key => $value) {
            update_post_meta($post_id, $key, $value);
        }

        update_kurs_taxonomies($post_id, $lokasjon_id, $data);
        // Koble instruktor-taxonomien
        $instructors = get_instructors_in_courselist($hovedkurs_id, $lokasjon_id);
        $instructors_lokasjon = $instructors['instructors_lokasjon'];
        $data_instruktorer = array_column($instructors_lokasjon, 'fullname');
        error_log("DEBUG update_existing_course: Liste med instruktører på lokasjon_id for post_id: $post_id: " . implode(', ', $data_instruktorer));
        update_instruktor_taxonomies($post_id, $data_instruktorer);
        
        //$hovedkurs_id = get_post_meta($post_id, 'hovedkurs_id', true);
        if ($hovedkurs_id) {
            sync_hovedkurs_taxonomies($hovedkurs_id);
        }

        create_or_update_kursdato($data, $post_id, $hovedkurs_id, $data['id']);
    }
    //error_log("Oppdaterer kurset med lokasjon_id: " . $data['id']);
}

function create_or_update_kursdato($data, $post_id, $hovedkurs_id, $lokasjon_id) { 
    if (!isset($data['locations'])) {
        //error_log('Ingen locations funnet i API-data.');
        return;
    }
    /* //error_log('DEBUG: Innholdet i $data: ' . print_r($data, true));
    //error_log('kursdato: Lokasjon_id sendt inn i funksjon: ' . $lokasjon_id);
    //error_log('kursdato: Hovedkurs_id sendt inn i funksjon: ' . $hovedkurs_id); */

    // Finn riktig lokasjon basert på lokasjon_id
    $location = array_filter($data['locations'], function ($loc) use ($lokasjon_id) {
        return $loc['courseId'] === $lokasjon_id;
    });

    if (empty($location)) {
        //error_log("Lokasjon med ID {$lokasjon_id} ble ikke funnet.");
        return;
    }

    $location = reset($location); // Hent første matchende lokasjon

    if (!isset($location['schedules']) || empty($location['schedules'])) {
        //error_log("Ingen schedules funnet for lokasjon ID {$lokasjon_id}.");
        return;
    }

    // Loop gjennom alle schedules og opprett/oppdater kursdato
    foreach ($location['schedules'] as $schedule) {
        $schedule_id = $schedule['id'] ?? 0;

        // Sjekk om kursdato allerede finnes basert på schedule_id og lokasjon_id
        $existing_post = get_posts([
            'post_type' => 'kursdato',
            'meta_query' => [
                ['key' => 'schedule_id', 'value' => $schedule_id],
                ['key' => 'lokasjon_id', 'value' => $lokasjon_id],
            ],
            'numberposts' => 1,
        ]);

        $kursdato_id = $existing_post[0]->ID ?? null;

        // Lag eller oppdater kursdato
        $post_data = [
            'ID' => $kursdato_id,
            'post_type' => 'kursdato',
            'post_status' => 'publish',
            'post_title' => $data['name'] . ' - ' . get_kurssted($data) . ' ' . 
                (isset($schedule['firstCourseDate']) ? format_date($schedule['firstCourseDate']) : "-uten kursdato"),
            'meta_input' => [
                'hovedkurs_id' => $hovedkurs_id,
                'lokasjon_id' => $lokasjon_id,
                'schedule_id' => $schedule_id,
                'forste_kursdato' => isset($schedule['firstCourseDate']) ? format_date($schedule['firstCourseDate']) : null,
                'siste_kursdato' => isset($schedule['lastCourseDate']) ? format_date($schedule['lastCourseDate']) : null,
                'varighet' => $schedule['duration'] ?? null,
                'tidspunkt' => $schedule['coursetime'] ?? null,
                'tidspunkt_type' => $schedule['coursetimeType'] ?? null,
                'kurskode' => $schedule['courseCode'] ?? null,
                'knappetekst' => $schedule['formButtonText'] ?? null,
                'sprak' => $schedule['language'] ?? null,
                'starttid' => $schedule['startTime'] ?? null,
                'kurslokale' => isset($schedule['locationRooms'][0]['name']) ? $schedule['locationRooms'][0]['name'] : null,
                'slutttid' => $schedule['endTime'] ?? null,
            ],
        ];
        

        $instruktornavn_array = [];
        if (!empty($schedule['instructors']) && is_array($schedule['instructors'])) {
            foreach ($schedule['instructors'] as $instructor) {
                $instruktornavn_array[] = sanitize_text_field($instructor['fullname']);
            }
        }

        $kursdato_post_id = wp_insert_post($post_data);

        if (is_wp_error($kursdato_post_id)) {
            //error_log("FEIL: wp_insert_post mislyktes for schedule ID: " . ($schedule['id'] ?? 'ukjent') . ". Feil: " . $kursdato_post_id->get_error_message());
        } else {
            update_kurs_taxonomies($kursdato_post_id, $lokasjon_id, $data);
            update_instruktor_taxonomies($kursdato_post_id, $instruktornavn_array);
            
            // Koble instruktor-taxonomien
            // Hent instruktørlister fra API
            /*$instructors = get_instructors_in_courselist($hovedkurs_id, $lokasjon_id);
            $instructors_lokasjon = $instructors['instructors_lokasjon'];
            // Hent kun fullname for instruktørene
            $data_instruktorer = array_column($instructors_lokasjon, 'fullname');
            // Oppdater instruktør-taxonomien
            update_instruktor_taxonomies($post_id, $data_instruktorer);
    */
            //error_log("DEBUG: Kursdato opprettet/oppdatert med ID: $kursdato_post_id");
        }
        

        // Oppdater relasjonen til kurs (post_id)
        if (!empty($post_id)) {
            $current_related_kurs = get_post_meta($kursdato_post_id, 'related_kurs', true) ?: [];
            if (!is_array($current_related_kurs)) {
                $current_related_kurs = (array) $current_related_kurs; // Sikre array-format
            }

            if (!in_array($post_id, $current_related_kurs)) {
                $current_related_kurs[] = $post_id;
                update_post_meta($kursdato_post_id, 'related_kurs', array_unique($current_related_kurs));
            }
        }

        // Oppdater relasjonen fra kurs til kursdato
        if (!empty($kursdato_post_id)) {
            $related_kursdatoer = get_post_meta($post_id, 'related_kursdato', true) ?: [];
            if (!is_array($related_kursdatoer)) {
                $related_kursdatoer = (array) $related_kursdatoer; // Sikre array-format
            }

            if (!in_array($kursdato_post_id, $related_kursdatoer)) {
                $related_kursdatoer[] = $kursdato_post_id;
                update_post_meta($post_id, 'related_kursdato', array_unique($related_kursdatoer));
            }
        }


        //error_log("DEBUG: Sjekker etter eksisterende kursdato for schedule ID: " . ($schedule['id'] ?? 'ukjent') . " og lokasjon ID: $lokasjon_id");
        //error_log("DEBUG: Forespørsel returnerte: " . print_r($existing_post, true));

    }
    // Kall opprydningsfunksjonen etter at alle kursdatoer er opprettet/oppdatert
    cleanup_kursdatoer($lokasjon_id, $location['schedules']);
}




function cleanup_kursdatoer($lokasjon_id, $schedules_from_api) {
    // Hent alle kursdatoer for denne lokasjonen
    $kursdatoer = get_posts([
        'post_type' => 'kursdato',
        'meta_query' => [
            ['key' => 'lokasjon_id', 'value' => $lokasjon_id],
        ],
        'numberposts' => -1,
    ]);

    // Lag en liste over schedule_id-er fra API-et
    $valid_schedule_ids = array_map(function ($schedule) {
        return $schedule['id'] ?? 0; // Standardverdi for ID-løse schedules
    }, $schedules_from_api);

    //error_log("DEBUG: Gyldige schedule_id-er fra API for lokasjon {$lokasjon_id}: " . print_r($valid_schedule_ids, true));

    foreach ($kursdatoer as $kursdato) {
        $kursdato_id = $kursdato->ID;
        $schedule_id = get_post_meta($kursdato_id, 'schedule_id', true);

        // Håndter kursdatoer med schedule_id = 0
        if ($schedule_id == 0) {
            // Slett kun hvis 0 IKKE er en gyldig schedule_id i API-et
            if (!in_array(0, $valid_schedule_ids)) {
                wp_delete_post($kursdato_id, true);
                //error_log("INFO: Slettet kursdato med schedule_id = 0 for lokasjon {$lokasjon_id}");
            }
            continue; // Hopp over til neste kursdato
        }

        // Slett kursdato med ugyldig schedule_id
        if (!in_array($schedule_id, $valid_schedule_ids)) {
            wp_delete_post($kursdato_id, true);
            //error_log("INFO: Slettet kursdato med ugyldig schedule_id {$schedule_id} for lokasjon {$lokasjon_id}");
        }
    }
}







// Felles funksjoner/ helper funkcions and data

// Get data fra enkeltkurs API for meta fields
function get_common_meta_fields($data, $sprak) {
    return [ 
        'lokasjon_id' => (int) $data['id'],
        'innhold' => wp_kses_post($data['description']),
        'pris' => (int) $data['locations'][0]['price'],
        'pris_tekst_for_belop' => sanitize_text_field($data['locations'][0]['textBeforeAmount']),
        'pris_tekst_etter_belop' => sanitize_text_field($data['locations'][0]['textAfterAmount']),
        'vansklighetsgrad' => sanitize_text_field($data['difficultyLevel']),
        'kurstype' => sanitize_text_field($data['courseTypes'][0]['description']),//ARRAY
        'nettkurs' => sanitize_text_field($data['isOnlineCourse']),
        'kommune' => sanitize_text_field($data['locations'][0]['municipality']),
        'fylke' => sanitize_text_field($data['locations'][0]['county']),
        'sprak' => sanitize_text_field($sprak)
    ];
}

function get_kurssted($data) {
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

function update_kurs_taxonomies($post_id, $lokasjon_id, $data) {
    // Koble kurs til kurssted taxonomi
    $kurssted = get_kurssted($data); // Henter kurssted basert på dataen

    if ($kurssted) {
        // Sjekk om taxonomien finnes eller opprett den
        $kurssted_term = term_exists($kurssted, 'kurssted');

        if (!$kurssted_term) {
            $kurssted_term = wp_insert_term($kurssted, 'kurssted');
        }

        if (!is_wp_error($kurssted_term)) {
            // Sett kurssted taxonomien for kurset
            wp_set_object_terms($post_id, (int)$kurssted_term['term_id'], 'kurssted', false);
        }
    }

    // Koble kurs til kurskategori taxonomier
    if (!empty($data['tags']) && is_array($data['tags'])) {
        $kurskategorier = [];

        foreach ($data['tags'] as $tag) {
            if (!empty($tag['title'])) {
                $kurskategori = sanitize_text_field($tag['title']);

                // Sjekk om taxonomien finnes eller opprett den
                $kurskategori_term = term_exists($kurskategori, 'kurskategori');

                if (!$kurskategori_term) {
                    $kurskategori_term = wp_insert_term($kurskategori, 'kurskategori');
                }

                if (!is_wp_error($kurskategori_term)) {
                    $kurskategorier[] = (int)$kurskategori_term['term_id'];
                }
            }
        }

        if (!empty($kurskategorier)) {
            // Sett kurskategori taxonomier for kurset
            wp_set_object_terms($post_id, $kurskategorier, 'kurskategori', false);
        }
    }

    // Koble kurs til instruktorer taxonomi
    /*$instruktorer = [];

    if (empty($data['locations'])) {
        error_log("Ingen lokasjoner funnet i data.");
    } else {
        foreach ($data['locations'] as $location) {
            error_log("Sjekker lokasjon med courseId: " . $location['courseId']);
            if ($location['courseId'] === $lokasjon_id && !empty($location['schedules'])) {
                foreach ($location['schedules'] as $schedule) {
                    if (isset($schedule['id'])) {
                        error_log("Fant schedule med ID: " . $schedule['id']);
                    } else {
                        error_log("Schedule mangler ID.");
                        continue;
                    }

                    if (!empty($schedule['instructors']) && is_array($schedule['instructors'])) {
                        foreach ($schedule['instructors'] as $instructor) {
                            if (!empty($instructor['fullname'])) {
                                $instruktor_navn = sanitize_text_field($instructor['fullname']);
                                error_log("Behandler instruktør: " . $instruktor_navn);

                                // Sjekk om taxonomien finnes eller opprett den
                                $instruktor_term = term_exists($instruktor_navn, 'instruktorer');

                                if (!$instruktor_term) {
                                    $instruktor_term = wp_insert_term($instruktor_navn, 'instruktorer');
                                    if (is_wp_error($instruktor_term)) {
                                        error_log("Feil ved oppretting av term $instruktor_navn: " . $instruktor_term->get_error_message());
                                    } else {
                                        error_log("Opprettet ny instruktør-term $instruktor_navn: " . $instruktor_navn);
                                    }
                                } else {
                                    error_log("Instruktør-term eksisterer allerede: " . $instruktor_navn);
                                }

                                if (!is_wp_error($instruktor_term)) {
                                    $instruktorer[] = (int)$instruktor_term['term_id'];
                                }
                            } else {
                                error_log("Instruktør mangler fullname.");
                            }
                        }
                    } else {
                        error_log("Ingen instruktører funnet i schedule med ID: " . $schedule['id']);
                    }
                }
            } else {
                if ($location['courseId'] !== $lokasjon_id) {
                    error_log("Lokasjon courseId matcher ikke lokasjon_id: " . $lokasjon_id);
                }
                if (empty($location['schedules'])) {
                    error_log("Ingen schedules funnet for lokasjon med courseId: " . $location['courseId']);
                }
            }
        }
    }

    if (!empty($instruktorer)) {
        error_log("Setter instruktører: " . implode(", ", $instruktorer));
        wp_set_object_terms($post_id, $instruktorer, 'instruktorer', false); // Rettet til riktig taksonomi
    } else {
        error_log("Ingen instruktører å sette for kurs med post_id: " . $post_id);
    }
    */

}

function sync_hovedkurs_taxonomies($hovedkurs_id) {
    // Finn hovedkursets post-ID basert på $hovedkurs_id som meta-verdi
    $hovedkurs_post = get_posts([
        'post_type' => 'kurs',
        'meta_query' => [
            [
                'key' => 'hovedkurs_id',
                'value' => $hovedkurs_id,
                'compare' => '='
            ],
            [
                'key' => 'er_foreldrekurs',
                'value' => 'ja',
                'compare' => '='
            ]
        ],
        'numberposts' => 1,
    ]);

    if (empty($hovedkurs_post)) {
        return;
    }

    $post_id = $hovedkurs_post[0]->ID; // Faktisk post-ID for hovedkurset 

    // Hent alle underkurs knyttet til hovedkurset
    $underkurs = get_posts([
        'post_type' => 'kurs',
        'meta_query' => [
            'relation' => 'AND',
            [
                'key' => 'hovedkurs_id',
                'value' => $hovedkurs_id,
                'compare' => '='
            ],
            [
                'relation' => 'OR',
                [
                    'key' => 'er_foreldrekurs',
                    'value' => 'ja',
                    'compare' => '!='
                ],
                [
                    'key' => 'er_foreldrekurs',
                    'compare' => 'NOT EXISTS'
                ]
            ]
        ],
        'numberposts' => -1,
    ]);

    if (empty($underkurs)) {
        return;
    }

    $kurssteder = [];
    $kurskategorier = [];
    $instruktorer = [];

    foreach ($underkurs as $kurs) {
        $kurssted_terms = wp_get_object_terms($kurs->ID, 'kurssted', ['fields' => 'ids']);
        $kurskategori_terms = wp_get_object_terms($kurs->ID, 'kurskategori', ['fields' => 'ids']);
        $instruktor_terms = wp_get_object_terms($kurs->ID, 'instruktorer', ['fields' => 'ids']);

        if (is_wp_error($kurssted_terms) || is_wp_error($kurskategori_terms) || is_wp_error($instruktor_terms)) {
            continue;
        }

        $kurssteder = array_merge($kurssteder, $kurssted_terms);
        $kurskategorier = array_merge($kurskategorier, $kurskategori_terms);
        $instruktorer = array_merge($instruktorer, $instruktor_terms);
    }

    // Fjern duplikater
    $kurssteder = array_unique($kurssteder);
    $kurskategorier = array_unique($kurskategorier);
    $instruktorer = array_unique($instruktorer);

    // Sett taxonomier på hovedkurset
    if (!empty($kurssteder)) {
        wp_set_object_terms($post_id, $kurssteder, 'kurssted', false);
    }

    if (!empty($kurskategorier)) {
        wp_set_object_terms($post_id, $kurskategorier, 'kurskategori', false);
    }

    if (!empty($instruktorer)) {
        wp_set_object_terms($post_id, $instruktorer, 'instruktorer', false);
    }

    //error_log("DEBUG: Synkronisert taxonomier for hovedkurs ID: $hovedkurs_id.");
}


function update_instruktor_taxonomies($post_id, $data_instruktorer) {
    if (!empty($data_instruktorer) && is_array($data_instruktorer)) {
        $instruktorer = [];

        // Behandle instruktørene i dataen
        foreach ($data_instruktorer as $instructor) {
            $instruktor_term = term_exists($instructor, 'instruktorer'); // Sjekk om taxonomien allerede finnes
            if (!$instruktor_term) {
                $instruktor_term = wp_insert_term($instructor, 'instruktorer'); // Opprett taxonomien hvis den ikke finnes
            }
            if (!is_wp_error($instruktor_term)) {
                $instruktorer[] = (int)$instruktor_term['term_id']; // Legg til term ID
            }
        }

        // Oppdater taxonomien på innlegget
        if (!empty($instruktorer)) {
            wp_set_object_terms($post_id, $instruktorer, 'instruktorer', false);
            error_log("DEBUG: Oppdatert taxonomi 'instruktorer' på post ID $post_id med term IDs: " . implode(', ', $instruktorer));
        }
    } else {
        error_log("DEBUG: Ingen instruktørdata funnet for post ID $post_id.");
    }
}



function create_or_update_instruktor_cpt($instruktornavn_array, $kurs_post_id, $kursdato_post_id) {
    foreach ($instruktornavn_array as $fullname) {
        if (empty($fullname)) {
            continue;
        }

        // Sjekk om instruktøren allerede finnes
        $existing_instruktor = get_posts([
            'post_type' => 'instruktor',
            'title' => $fullname,
            'post_status' => 'publish',
            'numberposts' => 1,
        ]);

        $instruktor_post_id = $existing_instruktor[0]->ID ?? null;

        if (!$instruktor_post_id) {
            // Opprett ny instruktør
            $instruktor_post_id = wp_insert_post([
                'post_title' => $fullname,
                'post_type' => 'instruktor',
                'post_status' => 'publish',
            ]);
        }

        if (!is_wp_error($instruktor_post_id)) {
            // Oppdater instruktørens relasjoner
            $related_kurs = get_post_meta($instruktor_post_id, 'related_kurs', true) ?: [];
            $related_kursdato = get_post_meta($instruktor_post_id, 'related_kursdato', true) ?: [];

            if (!in_array($kurs_post_id, $related_kurs)) {
                $related_kurs[] = $kurs_post_id;
                update_post_meta($instruktor_post_id, 'related_kurs', $related_kurs);
            }

            if (!in_array($kursdato_post_id, $related_kursdato)) {
                $related_kursdato[] = $kursdato_post_id;
                update_post_meta($instruktor_post_id, 'related_kursdato', $related_kursdato);
            }

            // Oppdater relasjoner i kurs
            $related_instruktorer = get_post_meta($kurs_post_id, 'related_instruktor', true) ?: [];
            if (!in_array($instruktor_post_id, $related_instruktorer)) {
                $related_instruktorer[] = $instruktor_post_id;
                update_post_meta($kurs_post_id, 'related_instruktor', $related_instruktorer);
            }

            // Oppdater relasjoner i kursdato
            $related_instruktorer_kursdato = get_post_meta($kursdato_post_id, 'related_instruktor', true) ?: [];
            if (!in_array($instruktor_post_id, $related_instruktorer_kursdato)) {
                $related_instruktorer_kursdato[] = $instruktor_post_id;
                update_post_meta($kursdato_post_id, 'related_instruktor', $related_instruktorer_kursdato);
            }
        }
    }
}

function get_instructors_in_courselist($hovedkurs_id, $lokasjon_id) {
    // Hent hele kurslisten fra API
    $all_courses = kursagenten_get_course_list();
    error_log("DEBUG get_instructors: hovedkurs_id sendt inn i funksjon: $hovedkurs_id");
    error_log("DEBUG get_instructors: lokasjon_id sendt inn i funksjon: $lokasjon_id");

    $instructors_hovedkurs = [];
    $instructors_lokasjon = [];

    // Loop gjennom kursene
    foreach ($all_courses as $course) {
        if ($course['id'] == $hovedkurs_id) {
            error_log("DEBUG get_instructors: Match found for hovedkurs_id: $hovedkurs_id");
            // Loop gjennom lokasjoner for hovedkurs
            foreach ($course['locations'] as $location) {
                // Hent instruktører for alle schedules
                foreach ($location['schedules'] as $schedule) {
                    if (isset($schedule['instructors'])) {
                        foreach ($schedule['instructors'] as $instructor) {
                            // Legg til i hovedkurslisten
                            $instructors_hovedkurs[$instructor['id']] = [
                                'id' => $instructor['id'],
                                'fullname' => $instructor['fullname'],
                                'firstname' => $instructor['firstname'],
                                'lastname' => $instructor['lastname'],
                                'email' => $instructor['email'],
                                'phone' => $instructor['phone'],
                            ];
                        }
                    }
                }

                // Hvis lokasjon matcher lokasjon_id, legg til instruktører for denne lokasjonen
                if ($location['courseId'] == $lokasjon_id) {
                    error_log("DEBUG get_instructors: Match found for lokasjon_id: $lokasjon_id");
                    foreach ($location['schedules'] as $schedule) {
                        if (isset($schedule['instructors'])) {
                            foreach ($schedule['instructors'] as $instructor) {
                                $instructors_lokasjon[$instructor['id']] = [
                                    'id' => $instructor['id'],
                                    'fullname' => $instructor['fullname'],
                                    'firstname' => $instructor['firstname'],
                                    'lastname' => $instructor['lastname'],
                                    'email' => $instructor['email'],
                                    'phone' => $instructor['phone'],
                                ];
                            }
                        }
                    }
                }
            }
        }
    }

    // Fjern duplikater og returner lister
    return [
        'instructors_hovedkurs' => array_values($instructors_hovedkurs),
        'instructors_lokasjon' => array_values($instructors_lokasjon),
    ];
}






<?php
// API Call Helper

function kursagenten_api_request($endpoint, $method = 'GET', $body = []) {

    $courseprovider = get_option('kag_kursinnst_option_name');
    $provider_id = !empty($courseprovider['ka_tilbyderGuid']) ? $courseprovider['ka_tilbyderGuid'] : 'A19A6462CA18404AAA973D2BD1414E62'; //Account Demokurs (6201) at kursadmin.kursagenten.no

    $args = [
        'method'  => $method,
        'headers' => [
            'x-Provider'    => $provider_id, // Dette kan senere erstattes med en admin option
            'Content-Type'  => 'application/json',
        ],
    ];

    if (!empty($body)) {
        $args['body'] = json_encode($body);
    }

    $response = wp_remote_request($endpoint, $args);

    if (is_wp_error($response)) {
        error_log('API request failed: ' . $response->get_error_message());
        return false;
    }

    $status_code = wp_remote_retrieve_response_code($response);
    if ($status_code !== 200) {
        error_log('API request returned status ' . $status_code);
        return false;
    }

    return json_decode(wp_remote_retrieve_body($response), true);
}

function kursagenten_get_course_list() {
    $endpoint = 'https://developer.kursagenten.no/api/Course/WP/CourseList?includeParticipantCounts=true&includeFullSchedules=true';
    $courses = kursagenten_api_request($endpoint);

    if (!$courses) {
        error_log('Failed to fetch course list');
        return [];
    }

    return $courses;
}

function kursagenten_get_course_details($enkeltkurs_id) {
    $endpoint = 'https://developer.kursagenten.no/api/Course/WP/' . $enkeltkurs_id . '?includeFullSchedules=true';
    $course_details = kursagenten_api_request($endpoint);

    if (!$course_details) {
        error_log('Failed to fetch course details for ID: ' . $enkeltkurs_id);
        return [];
    }

    return $course_details;
}

// Kortkode som viser data
function test_kursagenten_api() {
    // Hente listen over kurs
    $courses = kursagenten_get_course_list();

    if (empty($courses)) {
        echo 'Ingen kurs ble hentet eller en feil oppsto.';
        return;
    }

    echo '<pre>';
    print_r($courses);
    echo '</pre>';

    // Test å hente detaljer for et spesifikt kurs (bruk et faktisk enkeltkurs-ID fra listen over)
    if (isset($courses[0]['id'])) {
        //$enkeltkurs_id = $courses[0]['id'];
        $enkeltkurs_id = '224264';
        $course_details = kursagenten_get_course_details($enkeltkurs_id);

        echo '<pre>';
        print_r($course_details);
        echo '</pre>';
    } else {
        echo 'Ingen enkeltkurs-ID funnet for å teste detaljkallet.';
    }
    if (isset($courses[0]['id'])) {
        //$enkeltkurs_id = $courses[0]['id'];
        $enkeltkurs_id = '224284';
        $course_details = kursagenten_get_course_details($enkeltkurs_id);

        echo '<pre>';
        print_r($course_details);
        echo '</pre>';
    } else {
        echo 'Ingen enkeltkurs-ID funnet for å teste detaljkallet.';
    }
}

// Kall funksjonen via en shortcode for enkel testing i WordPress
add_shortcode('test_kursagenten_api', 'test_kursagenten_api');


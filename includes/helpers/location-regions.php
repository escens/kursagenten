<?php
/**
 * Location Regions and Custom Names Helper
 * 
 * Handles region mapping for counties and municipalities,
 * and custom location names management.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get default region mapping for counties
 * Only counties are used for region mapping, not municipalities
 * 
 * @return array Array with counties mapped to regions
 */
function kursagenten_get_default_region_mapping() {
    return [
        'sørlandet' => [
            'counties' => ['Agder', 'Vest-Agder', 'Aust-Agder'],
            'municipalities' => []
        ],
        'østlandet' => [
            'counties' => ['Oslo', 'Akershus', 'Buskerud', 'Østfold', 'Innlandet', 'Vestfold', 'Telemark', 'Hedmark', 'Oppland'],
            'municipalities' => []
        ],
        'nord-norge' => [
            'counties' => ['Nordland', 'Troms', 'Finnmark', 'Troms og Finnmark'],
            'municipalities' => []
        ],
        'vestlandet' => [
            'counties' => ['Vestland', 'Rogaland', 'Møre og Romsdal', 'Hordaland', 'Sogn og Fjordane'],
            'municipalities' => []
        ],
        'midt-norge' => [
            'counties' => ['Trøndelag', 'Sør-Trøndelag', 'Nord-Trøndelag'],
            'municipalities' => []
        ]
    ];
}

/**
 * Get saved region mapping from options
 * 
 * @return array Array with counties and municipalities mapped to regions
 */
function kursagenten_get_region_mapping() {
    $default = kursagenten_get_default_region_mapping();
    $saved = get_option('kursagenten_region_mapping', []);
    
    // Merge saved with default, prioritizing saved values
    // Support both old region names (sør, øst, nord, vest) and new names (sørlandet, østlandet, etc.)
    $region_names = ['sørlandet', 'østlandet', 'nord-norge', 'vestlandet', 'midt-norge'];
    $old_to_new_mapping = [
        'sør' => 'sørlandet',
        'øst' => 'østlandet',
        'nord' => 'nord-norge',
        'vest' => 'vestlandet'
    ];
    
    $mapping = [];
    foreach ($region_names as $region) {
        // Check if saved data exists for this region
        $saved_data = null;
        if (isset($saved[$region]) && is_array($saved[$region])) {
            $saved_data = $saved[$region];
        }
        
        // Also check old region names and migrate them
        foreach ($old_to_new_mapping as $old => $new) {
            if ($new === $region && isset($saved[$old]) && is_array($saved[$old])) {
                $saved_data = $saved[$old];
                // Migrate old region name to new
                unset($saved[$old]);
                $saved[$region] = $saved_data;
                update_option('kursagenten_region_mapping', $saved);
                break;
            }
        }
        
        // Use saved data if available, otherwise use default
        if ($saved_data !== null && isset($saved_data['counties']) && is_array($saved_data['counties'])) {
            $mapping[$region] = [
                'counties' => $saved_data['counties'],
                'municipalities' => [] // Always empty - we only use counties
            ];
        } else {
            $mapping[$region] = [
                'counties' => $default[$region]['counties'] ?? [],
                'municipalities' => [] // Always empty - we only use counties
            ];
        }
    }
    
    return $mapping;
}

/**
 * Get region for a county or municipality
 * 
 * @param string $county County name
 * @param string $municipality Municipality name (optional)
 * @return string|false Region name or false if not found
 */
function kursagenten_get_region_for_location($county = '', $municipality = '') {
    // Check if regions are enabled
    if (!get_option('kursagenten_use_regions', false)) {
        return false;
    }
    
    $mapping = kursagenten_get_region_mapping();
    
    // Only check county (municipalities are not used for region mapping)
    if (!empty($county)) {
        foreach ($mapping as $region => $data) {
            if (in_array($county, $data['counties'])) {
                return $region;
            }
        }
    }
    
    return false;
}

/**
 * Assign regions to all existing location terms based on reference list
 * Called when regions are first activated
 * Also migrates old region names (sør, øst, nord, vest) to new names
 * 
 * @return int Number of terms updated
 */
function kursagenten_assign_regions_to_existing_terms() {
    if (!get_option('kursagenten_use_regions', false)) {
        return 0;
    }
    
    $all_terms = get_terms(array(
        'taxonomy' => 'ka_course_location',
        'hide_empty' => false,
    ));
    
    if (empty($all_terms) || is_wp_error($all_terms)) {
        return 0;
    }
    
    $reference_list = kursagenten_get_location_reference_list();
    $location_mappings = get_option('kursagenten_location_mappings', array());
    $updated_count = 0;
    
    // Old to new region name mapping
    $old_to_new_mapping = [
        'sør' => 'sørlandet',
        'øst' => 'østlandet',
        'nord' => 'nord-norge',
        'vest' => 'vestlandet'
    ];
    
    foreach ($all_terms as $term) {
        $existing_region = get_term_meta($term->term_id, 'location_region', true);
        
        // First, migrate old region names to new names
        if (!empty($existing_region) && isset($old_to_new_mapping[$existing_region])) {
            $new_region = $old_to_new_mapping[$existing_region];
            update_term_meta($term->term_id, 'location_region', $new_region);
            $updated_count++;
            continue; // Already has region (now migrated), skip
        }
        
        // Skip if region already set (and not an old name)
        if (!empty($existing_region) && !isset($old_to_new_mapping[$existing_region])) {
            continue;
        }
        
        // Get identifier (current name)
        $identifier = get_term_meta($term->term_id, 'location_custom_name', true);
        if (empty($identifier)) {
            $identifier = $term->name;
        }
        
        // Check if this identifier is a mapped name (new name)
        // If so, find the original name from location mappings
        $original_identifier = $identifier;
        foreach ($location_mappings as $old_name => $new_name) {
            if ($new_name === $identifier) {
                $original_identifier = $old_name;
                break;
            }
        }
        
        // First, check if original identifier is a county name directly
        // This handles cases like "Oslo" -> "Oslobyen" where Oslo is a county
        $region_mapping = kursagenten_get_region_mapping();
        $found_region = false;
        $county = null;
        
        foreach ($region_mapping as $region_key => $region_data) {
            if (isset($region_data['counties']) && is_array($region_data['counties']) && in_array($original_identifier, $region_data['counties'], true)) {
                $county = $original_identifier;
                update_term_meta($term->term_id, 'location_county', $county);
                $region = kursagenten_get_region_for_location($county, '');
                if ($region) {
                    update_term_meta($term->term_id, 'location_region', $region);
                    $updated_count++;
                    $found_region = true;
                    break;
                }
            }
        }
        
        // If not found as county, try to find matching location in reference list
        // First try with original name (in case it's been renamed)
        if (!$found_region) {
            foreach ($reference_list as $loc) {
                if ($loc['municipality'] === $original_identifier || $loc['county'] === $original_identifier) {
                    $county = $loc['county'];
                    // Store county in meta for future use
                    update_term_meta($term->term_id, 'location_county', $county);
                    if (!empty($loc['municipality'])) {
                        update_term_meta($term->term_id, 'location_municipality', $loc['municipality']);
                    }
                    // Only use county for region mapping (municipalities not used)
                    $region = kursagenten_get_region_for_location($county, '');
                    if ($region) {
                        update_term_meta($term->term_id, 'location_region', $region);
                        $updated_count++;
                        $found_region = true;
                        break;
                    }
                }
            }
        }
        
        // If not found with original name, try with current name
        if (!$found_region) {
            foreach ($reference_list as $loc) {
                if ($loc['municipality'] === $identifier || $loc['county'] === $identifier) {
                    $county = $loc['county'];
                    // Store county in meta for future use
                    update_term_meta($term->term_id, 'location_county', $county);
                    if (!empty($loc['municipality'])) {
                        update_term_meta($term->term_id, 'location_municipality', $loc['municipality']);
                    }
                    // Only use county for region mapping (municipalities not used)
                    $region = kursagenten_get_region_for_location($county, '');
                    if ($region) {
                        update_term_meta($term->term_id, 'location_region', $region);
                        $updated_count++;
                        $found_region = true;
                        break;
                    }
                }
            }
        }
        
        // If not found in reference list, try direct lookup by identifier as county name
        if (!$found_region) {
            // Try original identifier as county name first
            $region = kursagenten_get_region_for_location($original_identifier, '');
            if ($region) {
                // Store county in meta
                update_term_meta($term->term_id, 'location_county', $original_identifier);
                update_term_meta($term->term_id, 'location_region', $region);
                $updated_count++;
            } else {
                // Try current identifier as county name
                $region = kursagenten_get_region_for_location($identifier, '');
                if ($region) {
                    // Store county in meta
                    update_term_meta($term->term_id, 'location_county', $identifier);
                    update_term_meta($term->term_id, 'location_region', $region);
                    $updated_count++;
                }
            }
        }
    }
    
    return $updated_count;
}

/**
 * Update all location terms based on current region mapping
 * This function updates ALL terms, even if they already have a region set
 * 
 * @return int Number of terms updated
 */
function kursagenten_update_all_terms_with_region_mapping() {
    if (!get_option('kursagenten_use_regions', false)) {
        return 0;
    }
    
    $all_terms = get_terms(array(
        'taxonomy' => 'ka_course_location',
        'hide_empty' => false,
    ));
    
    if (empty($all_terms) || is_wp_error($all_terms)) {
        return 0;
    }
    
    $region_mapping = kursagenten_get_region_mapping();
    $reference_list = kursagenten_get_location_reference_list();
    $location_mappings = get_option('kursagenten_location_mappings', array());
    $updated_count = 0;
    
    foreach ($all_terms as $term) {
        // Get identifier (current name)
        $identifier = get_term_meta($term->term_id, 'location_custom_name', true);
        if (empty($identifier)) {
            $identifier = $term->name;
        }
        
        // Check if this identifier is a mapped name (new name)
        // If so, find the original name from location mappings
        $original_identifier = $identifier;
        foreach ($location_mappings as $old_name => $new_name) {
            if ($new_name === $identifier) {
                $original_identifier = $old_name;
                break;
            }
        }
        
        $county = null;
        $region = null;
        
        // First, check if original identifier is a county name directly in region mapping
        // This handles cases like "Oslo" -> "Oslobyen" where Oslo is a county
        foreach ($region_mapping as $region_key => $region_data) {
            if (isset($region_data['counties']) && is_array($region_data['counties']) && in_array($original_identifier, $region_data['counties'], true)) {
                $county = $original_identifier;
                update_term_meta($term->term_id, 'location_county', $county);
                break;
            }
        }
        
        // If not found as county, try to find matching location in reference list
        // First try with original name (in case it's been renamed)
        if (!$county) {
            foreach ($reference_list as $loc) {
                if ($loc['municipality'] === $original_identifier || $loc['county'] === $original_identifier) {
                    $county = $loc['county'];
                    // Store county in meta for future use
                    update_term_meta($term->term_id, 'location_county', $county);
                    if (!empty($loc['municipality'])) {
                        update_term_meta($term->term_id, 'location_municipality', $loc['municipality']);
                    }
                    break;
                }
            }
        }
        
        // If not found with original name, try with current name
        if (!$county) {
            foreach ($reference_list as $loc) {
                if ($loc['municipality'] === $identifier || $loc['county'] === $identifier) {
                    $county = $loc['county'];
                    // Store county in meta for future use
                    update_term_meta($term->term_id, 'location_county', $county);
                    if (!empty($loc['municipality'])) {
                        update_term_meta($term->term_id, 'location_municipality', $loc['municipality']);
                    }
                    break;
                }
            }
        }
        
        // If still not found, check if current identifier is a county name in region mapping
        if (!$county) {
            foreach ($region_mapping as $region_key => $region_data) {
                if (isset($region_data['counties']) && is_array($region_data['counties']) && in_array($identifier, $region_data['counties'], true)) {
                    $county = $identifier;
                    update_term_meta($term->term_id, 'location_county', $county);
                    break;
                }
            }
        }
        
        // Last resort: use identifier as county name
        if (!$county) {
            $county = $identifier;
            update_term_meta($term->term_id, 'location_county', $county);
        }
        
        // Find region for this county based on current mapping
        $region = null;
        foreach ($region_mapping as $region_key => $region_data) {
            if (isset($region_data['counties']) && is_array($region_data['counties']) && in_array($county, $region_data['counties'], true)) {
                $region = $region_key;
                break;
            }
        }
        
        // Update or remove region meta
        $existing_region = get_term_meta($term->term_id, 'location_region', true);
        if ($region) {
            if ($existing_region !== $region) {
                update_term_meta($term->term_id, 'location_region', $region);
                $updated_count++;
            }
        } else {
            // Remove region if county is not in any region
            if (!empty($existing_region)) {
                delete_term_meta($term->term_id, 'location_region');
                $updated_count++;
            }
        }
    }
    
    return $updated_count;
}

/**
 * Get Kursagenten location reference list
 * This is the official list from Kursagenten with their county/municipality naming
 * Format: id;fylke;kommune
 * 
 * @return array Array of locations with id, county, municipality
 */
function kursagenten_get_location_reference_list() {
    return [
        ['id' => 1, 'county' => 'E-læring og hjemmestudier', 'municipality' => ''],
        ['id' => 4, 'county' => 'Akershus', 'municipality' => 'Asker'],
        ['id' => 5, 'county' => 'Akershus', 'municipality' => 'Bærum / Sandvika'],
        ['id' => 6, 'county' => 'Akershus', 'municipality' => 'Eidsvoll'],
        ['id' => 7, 'county' => 'Akershus', 'municipality' => 'Enebakk'],
        ['id' => 8, 'county' => 'Akershus', 'municipality' => 'Fet'],
        ['id' => 9, 'county' => 'Akershus', 'municipality' => 'Frogn / Drøbak'],
        ['id' => 10, 'county' => 'Akershus', 'municipality' => 'Gjerdrum'],
        ['id' => 11, 'county' => 'Akershus', 'municipality' => 'Hurdal'],
        ['id' => 12, 'county' => 'Akershus', 'municipality' => 'Lørenskog'],
        ['id' => 13, 'county' => 'Akershus', 'municipality' => 'Nannestad'],
        ['id' => 14, 'county' => 'Akershus', 'municipality' => 'Nes'],
        ['id' => 15, 'county' => 'Akershus', 'municipality' => 'Nesodden'],
        ['id' => 16, 'county' => 'Akershus', 'municipality' => 'Nittedal'],
        ['id' => 17, 'county' => 'Akershus', 'municipality' => 'Oppegård'],
        ['id' => 18, 'county' => 'Akershus', 'municipality' => 'Rælingen'],
        ['id' => 19, 'county' => 'Akershus', 'municipality' => 'Skedsmo / Lillestrøm'],
        ['id' => 20, 'county' => 'Akershus', 'municipality' => 'Ski'],
        ['id' => 21, 'county' => 'Akershus', 'municipality' => 'Sørum'],
        ['id' => 22, 'county' => 'Akershus', 'municipality' => 'Ullensaker'],
        ['id' => 23, 'county' => 'Akershus', 'municipality' => 'Vestby'],
        ['id' => 24, 'county' => 'Akershus', 'municipality' => 'Ås'],
        ['id' => 26, 'county' => 'Aust-Agder', 'municipality' => 'Arendal'],
        ['id' => 27, 'county' => 'Aust-Agder', 'municipality' => 'Birkenes'],
        ['id' => 28, 'county' => 'Aust-Agder', 'municipality' => 'Bygland'],
        ['id' => 29, 'county' => 'Aust-Agder', 'municipality' => 'Bykle'],
        ['id' => 30, 'county' => 'Aust-Agder', 'municipality' => 'Evje og Hornnes'],
        ['id' => 31, 'county' => 'Aust-Agder', 'municipality' => 'Froland'],
        ['id' => 32, 'county' => 'Aust-Agder', 'municipality' => 'Gjerstad'],
        ['id' => 33, 'county' => 'Aust-Agder', 'municipality' => 'Grimstad'],
        ['id' => 34, 'county' => 'Aust-Agder', 'municipality' => 'Iveland'],
        ['id' => 35, 'county' => 'Aust-Agder', 'municipality' => 'Lillesand'],
        ['id' => 36, 'county' => 'Aust-Agder', 'municipality' => 'Risør'],
        ['id' => 37, 'county' => 'Aust-Agder', 'municipality' => 'Tvedestrand'],
        ['id' => 38, 'county' => 'Aust-Agder', 'municipality' => 'Valle'],
        ['id' => 39, 'county' => 'Aust-Agder', 'municipality' => 'Vegårshei'],
        ['id' => 40, 'county' => 'Aust-Agder', 'municipality' => 'Åmli'],
        ['id' => 42, 'county' => 'Buskerud', 'municipality' => 'Drammen'],
        ['id' => 43, 'county' => 'Buskerud', 'municipality' => 'Flesberg'],
        ['id' => 44, 'county' => 'Buskerud', 'municipality' => 'Flå'],
        ['id' => 45, 'county' => 'Buskerud', 'municipality' => 'Gol'],
        ['id' => 46, 'county' => 'Buskerud', 'municipality' => 'Hemsedal'],
        ['id' => 47, 'county' => 'Buskerud', 'municipality' => 'Hol'],
        ['id' => 48, 'county' => 'Buskerud', 'municipality' => 'Hole'],
        ['id' => 49, 'county' => 'Buskerud', 'municipality' => 'Hurum'],
        ['id' => 50, 'county' => 'Buskerud', 'municipality' => 'Kongsberg'],
        ['id' => 51, 'county' => 'Buskerud', 'municipality' => 'Krødsherad'],
        ['id' => 52, 'county' => 'Buskerud', 'municipality' => 'Lier'],
        ['id' => 53, 'county' => 'Buskerud', 'municipality' => 'Modum'],
        ['id' => 54, 'county' => 'Buskerud', 'municipality' => 'Nedre Eiker'],
        ['id' => 55, 'county' => 'Buskerud', 'municipality' => 'Nes'],
        ['id' => 56, 'county' => 'Buskerud', 'municipality' => 'Nore og Uvdal'],
        ['id' => 57, 'county' => 'Buskerud', 'municipality' => 'Ringerike / Hønefoss'],
        ['id' => 58, 'county' => 'Buskerud', 'municipality' => 'Rollag'],
        ['id' => 59, 'county' => 'Buskerud', 'municipality' => 'Røyken'],
        ['id' => 60, 'county' => 'Buskerud', 'municipality' => 'Sigdal'],
        ['id' => 61, 'county' => 'Buskerud', 'municipality' => 'Øvre Eiker / Hokksund'],
        ['id' => 62, 'county' => 'Buskerud', 'municipality' => 'Ål'],
        ['id' => 64, 'county' => 'Finnmark', 'municipality' => 'Alta'],
        ['id' => 65, 'county' => 'Finnmark', 'municipality' => 'Hammerfest'],
        ['id' => 66, 'county' => 'Finnmark', 'municipality' => 'Hasvik'],
        ['id' => 67, 'county' => 'Finnmark', 'municipality' => 'Karasjok'],
        ['id' => 68, 'county' => 'Finnmark', 'municipality' => 'Kautokeino'],
        ['id' => 69, 'county' => 'Finnmark', 'municipality' => 'Kvalsund'],
        ['id' => 70, 'county' => 'Finnmark', 'municipality' => 'Loppa'],
        ['id' => 71, 'county' => 'Finnmark', 'municipality' => 'Nordkapp / Honningsvåg'],
        ['id' => 72, 'county' => 'Finnmark', 'municipality' => 'Porsanger'],
        ['id' => 73, 'county' => 'Finnmark', 'municipality' => 'Sør-Varanger / Kirkenes'],
        ['id' => 74, 'county' => 'Finnmark', 'municipality' => 'Vadsø'],
        ['id' => 76, 'county' => 'Hedmark', 'municipality' => 'Alvdal'],
        ['id' => 77, 'county' => 'Hedmark', 'municipality' => 'Eidskog'],
        ['id' => 78, 'county' => 'Hedmark', 'municipality' => 'Elverum'],
        ['id' => 79, 'county' => 'Hedmark', 'municipality' => 'Grue'],
        ['id' => 80, 'county' => 'Hedmark', 'municipality' => 'Hamar'],
        ['id' => 81, 'county' => 'Hedmark', 'municipality' => 'Kongsvinger'],
        ['id' => 82, 'county' => 'Hedmark', 'municipality' => 'Løten'],
        ['id' => 83, 'county' => 'Hedmark', 'municipality' => 'Nord-Odal'],
        ['id' => 84, 'county' => 'Hedmark', 'municipality' => 'Os'],
        ['id' => 85, 'county' => 'Hedmark', 'municipality' => 'Rendalen'],
        ['id' => 86, 'county' => 'Hedmark', 'municipality' => 'Ringsaker'],
        ['id' => 87, 'county' => 'Hedmark', 'municipality' => 'Stange'],
        ['id' => 88, 'county' => 'Hedmark', 'municipality' => 'Stor-Elvdal'],
        ['id' => 89, 'county' => 'Hedmark', 'municipality' => 'Sør-Odal'],
        ['id' => 90, 'county' => 'Hedmark', 'municipality' => 'Tolga'],
        ['id' => 91, 'county' => 'Hedmark', 'municipality' => 'Trysil'],
        ['id' => 92, 'county' => 'Hedmark', 'municipality' => 'Tynset'],
        ['id' => 93, 'county' => 'Hedmark', 'municipality' => 'Våler'],
        ['id' => 94, 'county' => 'Hedmark', 'municipality' => 'Åmot'],
        ['id' => 95, 'county' => 'Hedmark', 'municipality' => 'Åsnes'],
        ['id' => 98, 'county' => 'Hordaland', 'municipality' => 'Askøy'],
        ['id' => 99, 'county' => 'Hordaland', 'municipality' => 'Austevoll'],
        ['id' => 100, 'county' => 'Hordaland', 'municipality' => 'Bergen'],
        ['id' => 103, 'county' => 'Hordaland', 'municipality' => 'Bømlo'],
        ['id' => 104, 'county' => 'Hordaland', 'municipality' => 'Etne'],
        ['id' => 106, 'county' => 'Hordaland', 'municipality' => 'Fedje'],
        ['id' => 107, 'county' => 'Hordaland', 'municipality' => 'Fitjar'],
        ['id' => 108, 'county' => 'Hordaland', 'municipality' => 'Fjell'],
        ['id' => 109, 'county' => 'Hordaland', 'municipality' => 'Fusa'],
        ['id' => 111, 'county' => 'Hordaland', 'municipality' => 'Granvin'],
        ['id' => 112, 'county' => 'Hordaland', 'municipality' => 'Jondal'],
        ['id' => 113, 'county' => 'Hordaland', 'municipality' => 'Kvam'],
        ['id' => 114, 'county' => 'Hordaland', 'municipality' => 'Kvinnherad'],
        ['id' => 116, 'county' => 'Hordaland', 'municipality' => 'Lindås'],
        ['id' => 117, 'county' => 'Hordaland', 'municipality' => 'Masfjorden'],
        ['id' => 118, 'county' => 'Hordaland', 'municipality' => 'Meland'],
        ['id' => 119, 'county' => 'Hordaland', 'municipality' => 'Odda'],
        ['id' => 120, 'county' => 'Hordaland', 'municipality' => 'Os'],
        ['id' => 121, 'county' => 'Hordaland', 'municipality' => 'Osterøy'],
        ['id' => 122, 'county' => 'Hordaland', 'municipality' => 'Radøy'],
        ['id' => 123, 'county' => 'Hordaland', 'municipality' => 'Stord'],
        ['id' => 124, 'county' => 'Hordaland', 'municipality' => 'Sund'],
        ['id' => 125, 'county' => 'Hordaland', 'municipality' => 'Sveio'],
        ['id' => 126, 'county' => 'Hordaland', 'municipality' => 'Tysnes'],
        ['id' => 127, 'county' => 'Hordaland', 'municipality' => 'Ulvik'],
        ['id' => 128, 'county' => 'Hordaland', 'municipality' => 'Vaksdal'],
        ['id' => 129, 'county' => 'Hordaland', 'municipality' => 'Voss'],
        ['id' => 130, 'county' => 'Hordaland', 'municipality' => 'Øygarden'],
        ['id' => 133, 'county' => 'Møre og Romsdal', 'municipality' => 'Aukra'],
        ['id' => 134, 'county' => 'Møre og Romsdal', 'municipality' => 'Aure'],
        ['id' => 135, 'county' => 'Møre og Romsdal', 'municipality' => 'Averøy'],
        ['id' => 136, 'county' => 'Møre og Romsdal', 'municipality' => 'Eide'],
        ['id' => 137, 'county' => 'Møre og Romsdal', 'municipality' => 'Frei'],
        ['id' => 138, 'county' => 'Møre og Romsdal', 'municipality' => 'Fræna'],
        ['id' => 139, 'county' => 'Møre og Romsdal', 'municipality' => 'Giske'],
        ['id' => 140, 'county' => 'Møre og Romsdal', 'municipality' => 'Gjemnes'],
        ['id' => 141, 'county' => 'Møre og Romsdal', 'municipality' => 'Halsa'],
        ['id' => 142, 'county' => 'Møre og Romsdal', 'municipality' => 'Haram'],
        ['id' => 143, 'county' => 'Møre og Romsdal', 'municipality' => 'Hareid'],
        ['id' => 144, 'county' => 'Møre og Romsdal', 'municipality' => 'Herøy / Fosnavåg'],
        ['id' => 145, 'county' => 'Møre og Romsdal', 'municipality' => 'Kristiansund'],
        ['id' => 146, 'county' => 'Møre og Romsdal', 'municipality' => 'Midsund'],
        ['id' => 147, 'county' => 'Møre og Romsdal', 'municipality' => 'Molde'],
        ['id' => 148, 'county' => 'Møre og Romsdal', 'municipality' => 'Nesset'],
        ['id' => 149, 'county' => 'Møre og Romsdal', 'municipality' => 'Norddal'],
        ['id' => 150, 'county' => 'Møre og Romsdal', 'municipality' => 'Rauma / Åndalsnes'],
        ['id' => 151, 'county' => 'Møre og Romsdal', 'municipality' => 'Sande'],
        ['id' => 152, 'county' => 'Møre og Romsdal', 'municipality' => 'Sandøy'],
        ['id' => 153, 'county' => 'Møre og Romsdal', 'municipality' => 'Skodje'],
        ['id' => 154, 'county' => 'Møre og Romsdal', 'municipality' => 'Smøla'],
        ['id' => 155, 'county' => 'Møre og Romsdal', 'municipality' => 'Stordal'],
        ['id' => 156, 'county' => 'Møre og Romsdal', 'municipality' => 'Stranda'],
        ['id' => 157, 'county' => 'Møre og Romsdal', 'municipality' => 'Sula'],
        ['id' => 158, 'county' => 'Møre og Romsdal', 'municipality' => 'Sunndal'],
        ['id' => 159, 'county' => 'Møre og Romsdal', 'municipality' => 'Surnadal'],
        ['id' => 160, 'county' => 'Møre og Romsdal', 'municipality' => 'Sykkylven'],
        ['id' => 161, 'county' => 'Møre og Romsdal', 'municipality' => 'Tingvoll'],
        ['id' => 163, 'county' => 'Møre og Romsdal', 'municipality' => 'Ulstein / Ulsteinvik'],
        ['id' => 164, 'county' => 'Møre og Romsdal', 'municipality' => 'Vanylven'],
        ['id' => 165, 'county' => 'Møre og Romsdal', 'municipality' => 'Vestnes'],
        ['id' => 166, 'county' => 'Møre og Romsdal', 'municipality' => 'Volda'],
        ['id' => 167, 'county' => 'Møre og Romsdal', 'municipality' => 'Ørskog'],
        ['id' => 168, 'county' => 'Møre og Romsdal', 'municipality' => 'Ørsta'],
        ['id' => 169, 'county' => 'Møre og Romsdal', 'municipality' => 'Ålesund'],
        ['id' => 171, 'county' => 'Nordland', 'municipality' => 'Alstahaug / Sandnessjøen'],
        ['id' => 172, 'county' => 'Nordland', 'municipality' => 'Andøy'],
        ['id' => 173, 'county' => 'Nordland', 'municipality' => 'Ballangen'],
        ['id' => 174, 'county' => 'Nordland', 'municipality' => 'Beiarn'],
        ['id' => 175, 'county' => 'Nordland', 'municipality' => 'Bodø'],
        ['id' => 176, 'county' => 'Nordland', 'municipality' => 'Brønnøy / Brønnøysund'],
        ['id' => 177, 'county' => 'Nordland', 'municipality' => 'Bø'],
        ['id' => 178, 'county' => 'Nordland', 'municipality' => 'Dønna'],
        ['id' => 179, 'county' => 'Nordland', 'municipality' => 'Evenes'],
        ['id' => 180, 'county' => 'Nordland', 'municipality' => 'Fauske'],
        ['id' => 181, 'county' => 'Nordland', 'municipality' => 'Flakstad'],
        ['id' => 182, 'county' => 'Nordland', 'municipality' => 'Gildeskål'],
        ['id' => 183, 'county' => 'Nordland', 'municipality' => 'Grane'],
        ['id' => 184, 'county' => 'Nordland', 'municipality' => 'Hadsel / Stokmarknes'],
        ['id' => 185, 'county' => 'Nordland', 'municipality' => 'Hemnes'],
        ['id' => 186, 'county' => 'Nordland', 'municipality' => 'Lurøy'],
        ['id' => 187, 'county' => 'Nordland', 'municipality' => 'Lødingen'],
        ['id' => 188, 'county' => 'Nordland', 'municipality' => 'Meløy'],
        ['id' => 189, 'county' => 'Nordland', 'municipality' => 'Moskenes'],
        ['id' => 190, 'county' => 'Nordland', 'municipality' => 'Narvik'],
        ['id' => 191, 'county' => 'Nordland', 'municipality' => 'Nesna'],
        ['id' => 192, 'county' => 'Nordland', 'municipality' => 'Rana / Mo i Rana'],
        ['id' => 193, 'county' => 'Nordland', 'municipality' => 'Rødøy'],
        ['id' => 194, 'county' => 'Nordland', 'municipality' => 'Saltdal'],
        ['id' => 196, 'county' => 'Nordland', 'municipality' => 'Sortland'],
        ['id' => 197, 'county' => 'Nordland', 'municipality' => 'Steigen'],
        ['id' => 198, 'county' => 'Nordland', 'municipality' => 'Sømna'],
        ['id' => 199, 'county' => 'Nordland', 'municipality' => 'Sørfold'],
        ['id' => 200, 'county' => 'Nordland', 'municipality' => 'Tjeldsund'],
        ['id' => 201, 'county' => 'Nordland', 'municipality' => 'Træna'],
        ['id' => 202, 'county' => 'Nordland', 'municipality' => 'Vefsn / Mosjøen'],
        ['id' => 203, 'county' => 'Nordland', 'municipality' => 'Vega'],
        ['id' => 204, 'county' => 'Nordland', 'municipality' => 'Vestvågøy / Leknes'],
        ['id' => 205, 'county' => 'Nordland', 'municipality' => 'Vågan / Svolvær'],
        ['id' => 206, 'county' => 'Nordland', 'municipality' => 'Øksnes'],
        ['id' => 208, 'county' => 'Nord-Trøndelag', 'municipality' => 'Flatanger'],
        ['id' => 209, 'county' => 'Nord-Trøndelag', 'municipality' => 'Frosta'],
        ['id' => 210, 'county' => 'Nord-Trøndelag', 'municipality' => 'Grong'],
        ['id' => 211, 'county' => 'Nord-Trøndelag', 'municipality' => 'Høylandet'],
        ['id' => 212, 'county' => 'Nord-Trøndelag', 'municipality' => 'Inderøy'],
        ['id' => 213, 'county' => 'Nord-Trøndelag', 'municipality' => 'Leksvik'],
        ['id' => 214, 'county' => 'Nord-Trøndelag', 'municipality' => 'Levanger'],
        ['id' => 215, 'county' => 'Nord-Trøndelag', 'municipality' => 'Lierne'],
        ['id' => 216, 'county' => 'Nord-Trøndelag', 'municipality' => 'Mosvik'],
        ['id' => 217, 'county' => 'Nord-Trøndelag', 'municipality' => 'Namdalseid'],
        ['id' => 218, 'county' => 'Nord-Trøndelag', 'municipality' => 'Namsos'],
        ['id' => 219, 'county' => 'Nord-Trøndelag', 'municipality' => 'Namsskogan'],
        ['id' => 220, 'county' => 'Nord-Trøndelag', 'municipality' => 'Nærøy / Kolvereid'],
        ['id' => 221, 'county' => 'Nord-Trøndelag', 'municipality' => 'Overhalla'],
        ['id' => 222, 'county' => 'Nord-Trøndelag', 'municipality' => 'Snåsa'],
        ['id' => 223, 'county' => 'Nord-Trøndelag', 'municipality' => 'Steinkjer'],
        ['id' => 224, 'county' => 'Nord-Trøndelag', 'municipality' => 'Stjørdal'],
        ['id' => 225, 'county' => 'Nord-Trøndelag', 'municipality' => 'Verdal'],
        ['id' => 226, 'county' => 'Nord-Trøndelag', 'municipality' => 'Verran'],
        ['id' => 227, 'county' => 'Nord-Trøndelag', 'municipality' => 'Vikna'],
        ['id' => 229, 'county' => 'Oppland', 'municipality' => 'Dovre'],
        ['id' => 230, 'county' => 'Oppland', 'municipality' => 'Gausdal'],
        ['id' => 231, 'county' => 'Oppland', 'municipality' => 'Gjøvik'],
        ['id' => 232, 'county' => 'Oppland', 'municipality' => 'Gran'],
        ['id' => 233, 'county' => 'Oppland', 'municipality' => 'Jevnaker'],
        ['id' => 234, 'county' => 'Oppland', 'municipality' => 'Lesja'],
        ['id' => 235, 'county' => 'Oppland', 'municipality' => 'Lillehammer'],
        ['id' => 236, 'county' => 'Oppland', 'municipality' => 'Lunner'],
        ['id' => 237, 'county' => 'Oppland', 'municipality' => 'Nord-Aurdal / Fagernes'],
        ['id' => 238, 'county' => 'Oppland', 'municipality' => 'Nord-Fron'],
        ['id' => 239, 'county' => 'Oppland', 'municipality' => 'Nordre Land'],
        ['id' => 240, 'county' => 'Oppland', 'municipality' => 'Ringebu'],
        ['id' => 241, 'county' => 'Oppland', 'municipality' => 'Sel / Otta'],
        ['id' => 242, 'county' => 'Oppland', 'municipality' => 'Søndre Land'],
        ['id' => 243, 'county' => 'Oppland', 'municipality' => 'Sør-Fron'],
        ['id' => 244, 'county' => 'Oppland', 'municipality' => 'Vang'],
        ['id' => 245, 'county' => 'Oppland', 'municipality' => 'Vestre Slidre'],
        ['id' => 246, 'county' => 'Oppland', 'municipality' => 'Vestre Toten'],
        ['id' => 247, 'county' => 'Oppland', 'municipality' => 'Vågå'],
        ['id' => 248, 'county' => 'Oppland', 'municipality' => 'Østre Toten'],
        ['id' => 249, 'county' => 'Oppland', 'municipality' => 'Øyer'],
        ['id' => 250, 'county' => 'Oppland', 'municipality' => 'Øystre Slidre'],
        ['id' => 285, 'county' => 'Rogaland', 'municipality' => 'Eigersund / Egersund'],
        ['id' => 286, 'county' => 'Rogaland', 'municipality' => 'Sandnes'],
        ['id' => 287, 'county' => 'Rogaland', 'municipality' => 'Stavanger'],
        ['id' => 288, 'county' => 'Rogaland', 'municipality' => 'Haugesund'],
        ['id' => 289, 'county' => 'Rogaland', 'municipality' => 'Sokndal'],
        ['id' => 290, 'county' => 'Rogaland', 'municipality' => 'Lund'],
        ['id' => 291, 'county' => 'Rogaland', 'municipality' => 'Bjerkreim'],
        ['id' => 292, 'county' => 'Rogaland', 'municipality' => 'Hå'],
        ['id' => 293, 'county' => 'Rogaland', 'municipality' => 'Klepp'],
        ['id' => 294, 'county' => 'Rogaland', 'municipality' => 'Time / Bryne'],
        ['id' => 295, 'county' => 'Rogaland', 'municipality' => 'Gjesdal'],
        ['id' => 296, 'county' => 'Rogaland', 'municipality' => 'Sola'],
        ['id' => 297, 'county' => 'Rogaland', 'municipality' => 'Randaberg'],
        ['id' => 298, 'county' => 'Rogaland', 'municipality' => 'Forsand'],
        ['id' => 299, 'county' => 'Rogaland', 'municipality' => 'Strand / Jørpeland'],
        ['id' => 300, 'county' => 'Rogaland', 'municipality' => 'Hjelmeland'],
        ['id' => 301, 'county' => 'Rogaland', 'municipality' => 'Suldal'],
        ['id' => 302, 'county' => 'Rogaland', 'municipality' => 'Sauda'],
        ['id' => 303, 'county' => 'Rogaland', 'municipality' => 'Finnøy'],
        ['id' => 304, 'county' => 'Rogaland', 'municipality' => 'Rennesøy'],
        ['id' => 305, 'county' => 'Rogaland', 'municipality' => 'Kvitsøy'],
        ['id' => 306, 'county' => 'Rogaland', 'municipality' => 'Bokn'],
        ['id' => 307, 'county' => 'Rogaland', 'municipality' => 'Tysvær'],
        ['id' => 308, 'county' => 'Rogaland', 'municipality' => 'Karmøy'],
        ['id' => 309, 'county' => 'Rogaland', 'municipality' => 'Utsira'],
        ['id' => 310, 'county' => 'Rogaland', 'municipality' => 'Vindafjord'],
        ['id' => 312, 'county' => 'Sogn og Fjordane', 'municipality' => 'Askvoll'],
        ['id' => 313, 'county' => 'Sogn og Fjordane', 'municipality' => 'Aurland'],
        ['id' => 314, 'county' => 'Sogn og Fjordane', 'municipality' => 'Balestrand'],
        ['id' => 315, 'county' => 'Sogn og Fjordane', 'municipality' => 'Bremanger'],
        ['id' => 316, 'county' => 'Sogn og Fjordane', 'municipality' => 'Eid'],
        ['id' => 317, 'county' => 'Sogn og Fjordane', 'municipality' => 'Fjaler'],
        ['id' => 318, 'county' => 'Sogn og Fjordane', 'municipality' => 'Flora / Florø'],
        ['id' => 319, 'county' => 'Sogn og Fjordane', 'municipality' => 'Førde'],
        ['id' => 320, 'county' => 'Sogn og Fjordane', 'municipality' => 'Gaular'],
        ['id' => 321, 'county' => 'Sogn og Fjordane', 'municipality' => 'Gloppen'],
        ['id' => 322, 'county' => 'Sogn og Fjordane', 'municipality' => 'Gulen'],
        ['id' => 323, 'county' => 'Sogn og Fjordane', 'municipality' => 'Hyllestad'],
        ['id' => 324, 'county' => 'Sogn og Fjordane', 'municipality' => 'Høyanger'],
        ['id' => 325, 'county' => 'Sogn og Fjordane', 'municipality' => 'Jølster'],
        ['id' => 326, 'county' => 'Sogn og Fjordane', 'municipality' => 'Leikanger'],
        ['id' => 327, 'county' => 'Sogn og Fjordane', 'municipality' => 'Luster'],
        ['id' => 328, 'county' => 'Sogn og Fjordane', 'municipality' => 'Selje'],
        ['id' => 329, 'county' => 'Sogn og Fjordane', 'municipality' => 'Sogndal'],
        ['id' => 330, 'county' => 'Sogn og Fjordane', 'municipality' => 'Solund'],
        ['id' => 331, 'county' => 'Sogn og Fjordane', 'municipality' => 'Stryn'],
        ['id' => 332, 'county' => 'Sogn og Fjordane', 'municipality' => 'Vik'],
        ['id' => 333, 'county' => 'Sogn og Fjordane', 'municipality' => 'Vågsøy / Måløy'],
        ['id' => 334, 'county' => 'Sogn og Fjordane', 'municipality' => 'Årdal'],
        ['id' => 336, 'county' => 'Sør-Trøndelag', 'municipality' => 'Agdenes'],
        ['id' => 337, 'county' => 'Sør-Trøndelag', 'municipality' => 'Bjugn'],
        ['id' => 338, 'county' => 'Sør-Trøndelag', 'municipality' => 'Frøya'],
        ['id' => 339, 'county' => 'Sør-Trøndelag', 'municipality' => 'Hemne'],
        ['id' => 340, 'county' => 'Sør-Trøndelag', 'municipality' => 'Hitra'],
        ['id' => 341, 'county' => 'Sør-Trøndelag', 'municipality' => 'Holtålen'],
        ['id' => 342, 'county' => 'Sør-Trøndelag', 'municipality' => 'Klæbu'],
        ['id' => 343, 'county' => 'Sør-Trøndelag', 'municipality' => 'Malvik'],
        ['id' => 344, 'county' => 'Sør-Trøndelag', 'municipality' => 'Meldal'],
        ['id' => 345, 'county' => 'Sør-Trøndelag', 'municipality' => 'Melhus'],
        ['id' => 346, 'county' => 'Sør-Trøndelag', 'municipality' => 'Midtre Gauldal'],
        ['id' => 347, 'county' => 'Sør-Trøndelag', 'municipality' => 'Oppdal'],
        ['id' => 348, 'county' => 'Sør-Trøndelag', 'municipality' => 'Orkdal'],
        ['id' => 349, 'county' => 'Sør-Trøndelag', 'municipality' => 'Osen'],
        ['id' => 350, 'county' => 'Sør-Trøndelag', 'municipality' => 'Rennebu'],
        ['id' => 351, 'county' => 'Sør-Trøndelag', 'municipality' => 'Rissa'],
        ['id' => 352, 'county' => 'Sør-Trøndelag', 'municipality' => 'Roan'],
        ['id' => 353, 'county' => 'Sør-Trøndelag', 'municipality' => 'Røros'],
        ['id' => 354, 'county' => 'Sør-Trøndelag', 'municipality' => 'Selbu'],
        ['id' => 355, 'county' => 'Sør-Trøndelag', 'municipality' => 'Skaun'],
        ['id' => 356, 'county' => 'Sør-Trøndelag', 'municipality' => 'Trondheim'],
        ['id' => 357, 'county' => 'Sør-Trøndelag', 'municipality' => 'Tydal'],
        ['id' => 358, 'county' => 'Sør-Trøndelag', 'municipality' => 'Ørland / Brekstad'],
        ['id' => 359, 'county' => 'Sør-Trøndelag', 'municipality' => 'Åfjord'],
        ['id' => 361, 'county' => 'Telemark', 'municipality' => 'Bamble'],
        ['id' => 362, 'county' => 'Telemark', 'municipality' => 'Bø'],
        ['id' => 363, 'county' => 'Telemark', 'municipality' => 'Drangedal'],
        ['id' => 364, 'county' => 'Telemark', 'municipality' => 'Kragerø'],
        ['id' => 365, 'county' => 'Telemark', 'municipality' => 'Kviteseid'],
        ['id' => 366, 'county' => 'Telemark', 'municipality' => 'Nissedal'],
        ['id' => 367, 'county' => 'Telemark', 'municipality' => 'Nome'],
        ['id' => 368, 'county' => 'Telemark', 'municipality' => 'Notodden'],
        ['id' => 369, 'county' => 'Telemark', 'municipality' => 'Porsgrunn / Brevik'],
        ['id' => 370, 'county' => 'Telemark', 'municipality' => 'Sauherad'],
        ['id' => 371, 'county' => 'Telemark', 'municipality' => 'Seljord'],
        ['id' => 372, 'county' => 'Telemark', 'municipality' => 'Siljan'],
        ['id' => 373, 'county' => 'Telemark', 'municipality' => 'Skien'],
        ['id' => 374, 'county' => 'Telemark', 'municipality' => 'Tinn / Rjukan'],
        ['id' => 375, 'county' => 'Telemark', 'municipality' => 'Tokke'],
        ['id' => 376, 'county' => 'Telemark', 'municipality' => 'Vinje'],
        ['id' => 378, 'county' => 'Troms', 'municipality' => 'Balsfjord'],
        ['id' => 379, 'county' => 'Troms', 'municipality' => 'Bardu'],
        ['id' => 380, 'county' => 'Troms', 'municipality' => 'Berg'],
        ['id' => 381, 'county' => 'Troms', 'municipality' => 'Dyrøy'],
        ['id' => 382, 'county' => 'Troms', 'municipality' => 'Gratangen'],
        ['id' => 383, 'county' => 'Troms', 'municipality' => 'Harstad'],
        ['id' => 384, 'county' => 'Troms', 'municipality' => 'Ibestad'],
        ['id' => 385, 'county' => 'Troms', 'municipality' => 'Karlsøy'],
        ['id' => 388, 'county' => 'Troms', 'municipality' => 'Kvæfjord'],
        ['id' => 389, 'county' => 'Troms', 'municipality' => 'Kvænangen'],
        ['id' => 390, 'county' => 'Troms', 'municipality' => 'Kåfjord'],
        ['id' => 391, 'county' => 'Troms', 'municipality' => 'Lavangen'],
        ['id' => 392, 'county' => 'Troms', 'municipality' => 'Lenvik / Finnsnes'],
        ['id' => 393, 'county' => 'Troms', 'municipality' => 'Lyngen'],
        ['id' => 394, 'county' => 'Troms', 'municipality' => 'Målselv'],
        ['id' => 395, 'county' => 'Troms', 'municipality' => 'Nordreisa'],
        ['id' => 396, 'county' => 'Troms', 'municipality' => 'Salangen'],
        ['id' => 397, 'county' => 'Troms', 'municipality' => 'Skjervøy'],
        ['id' => 398, 'county' => 'Troms', 'municipality' => 'Skånland'],
        ['id' => 399, 'county' => 'Troms', 'municipality' => 'Storfjord'],
        ['id' => 400, 'county' => 'Troms', 'municipality' => 'Sørreisa'],
        ['id' => 401, 'county' => 'Troms', 'municipality' => 'Tomasjord'],
        ['id' => 402, 'county' => 'Troms', 'municipality' => 'Torsken'],
        ['id' => 403, 'county' => 'Troms', 'municipality' => 'Tranøy'],
        ['id' => 405, 'county' => 'Troms', 'municipality' => 'Tromsø'],
        ['id' => 408, 'county' => 'Vest-Agder', 'municipality' => 'Audnedal'],
        ['id' => 409, 'county' => 'Vest-Agder', 'municipality' => 'Farsund'],
        ['id' => 410, 'county' => 'Vest-Agder', 'municipality' => 'Flekkefjord'],
        ['id' => 411, 'county' => 'Vest-Agder', 'municipality' => 'Hægebostad'],
        ['id' => 412, 'county' => 'Vest-Agder', 'municipality' => 'Kristiansand'],
        ['id' => 413, 'county' => 'Vest-Agder', 'municipality' => 'Kvinesdal'],
        ['id' => 414, 'county' => 'Vest-Agder', 'municipality' => 'Lindesnes'],
        ['id' => 415, 'county' => 'Vest-Agder', 'municipality' => 'Lyngdal'],
        ['id' => 416, 'county' => 'Vest-Agder', 'municipality' => 'Mandal'],
        ['id' => 417, 'county' => 'Vest-Agder', 'municipality' => 'Marnardal'],
        ['id' => 418, 'county' => 'Vest-Agder', 'municipality' => 'Sirdal'],
        ['id' => 419, 'county' => 'Vest-Agder', 'municipality' => 'Songdalen'],
        ['id' => 420, 'county' => 'Vest-Agder', 'municipality' => 'Søgne'],
        ['id' => 421, 'county' => 'Vest-Agder', 'municipality' => 'Vennesla'],
        ['id' => 422, 'county' => 'Vest-Agder', 'municipality' => 'Åseral'],
        ['id' => 424, 'county' => 'Vestfold', 'municipality' => 'Andebu'],
        ['id' => 425, 'county' => 'Vestfold', 'municipality' => 'Hof'],
        ['id' => 426, 'county' => 'Vestfold', 'municipality' => 'Holmestrand'],
        ['id' => 427, 'county' => 'Vestfold', 'municipality' => 'Horten'],
        ['id' => 428, 'county' => 'Vestfold', 'municipality' => 'Lardal'],
        ['id' => 429, 'county' => 'Vestfold', 'municipality' => 'Larvik'],
        ['id' => 430, 'county' => 'Vestfold', 'municipality' => 'Nøtterøy'],
        ['id' => 431, 'county' => 'Vestfold', 'municipality' => 'Re'],
        ['id' => 432, 'county' => 'Vestfold', 'municipality' => 'Sande'],
        ['id' => 433, 'county' => 'Vestfold', 'municipality' => 'Sandefjord'],
        ['id' => 434, 'county' => 'Vestfold', 'municipality' => 'Stokke'],
        ['id' => 435, 'county' => 'Vestfold', 'municipality' => 'Svelvik'],
        ['id' => 436, 'county' => 'Vestfold', 'municipality' => 'Tjøme'],
        ['id' => 437, 'county' => 'Vestfold', 'municipality' => 'Tønsberg'],
        ['id' => 439, 'county' => 'Østfold', 'municipality' => 'Aremark'],
        ['id' => 440, 'county' => 'Østfold', 'municipality' => 'Askim'],
        ['id' => 441, 'county' => 'Østfold', 'municipality' => 'Eidsberg / Mysen'],
        ['id' => 442, 'county' => 'Østfold', 'municipality' => 'Fredrikstad'],
        ['id' => 443, 'county' => 'Østfold', 'municipality' => 'Halden'],
        ['id' => 444, 'county' => 'Østfold', 'municipality' => 'Hobøl'],
        ['id' => 445, 'county' => 'Østfold', 'municipality' => 'Hvaler'],
        ['id' => 446, 'county' => 'Østfold', 'municipality' => 'Marker'],
        ['id' => 447, 'county' => 'Østfold', 'municipality' => 'Moss'],
        ['id' => 448, 'county' => 'Østfold', 'municipality' => 'Rakkestad'],
        ['id' => 449, 'county' => 'Østfold', 'municipality' => 'Rygge'],
        ['id' => 450, 'county' => 'Østfold', 'municipality' => 'Rømskog'],
        ['id' => 451, 'county' => 'Østfold', 'municipality' => 'Råde'],
        ['id' => 452, 'county' => 'Østfold', 'municipality' => 'Sarpsborg'],
        ['id' => 453, 'county' => 'Østfold', 'municipality' => 'Skiptvet'],
        ['id' => 454, 'county' => 'Østfold', 'municipality' => 'Spydeberg'],
        ['id' => 455, 'county' => 'Østfold', 'municipality' => 'Trøgstad'],
        ['id' => 456, 'county' => 'Østfold', 'municipality' => 'Våler'],
        ['id' => 457, 'county' => 'Svalbard', 'municipality' => 'Svalbard'],
        ['id' => 461, 'county' => 'Akershus', 'municipality' => 'Aurskog-Høland'],
        ['id' => 462, 'county' => 'Hordaland', 'municipality' => 'Austrheim'],
        ['id' => 463, 'county' => 'Hordaland', 'municipality' => 'Eidfjord'],
        ['id' => 464, 'county' => 'Finnmark', 'municipality' => 'Berlevåg'],
        ['id' => 465, 'county' => 'Finnmark', 'municipality' => 'Båtsfjord'],
        ['id' => 466, 'county' => 'Hordaland', 'municipality' => 'Modalen'],
        ['id' => 467, 'county' => 'Møre og Romsdal', 'municipality' => 'Rindal'],
        ['id' => 468, 'county' => 'Nordland', 'municipality' => 'Røst'],
        ['id' => 469, 'county' => 'Nordland', 'municipality' => 'Bindal'],
        ['id' => 470, 'county' => 'Nord-Trøndelag', 'municipality' => 'Meråker'],
        ['id' => 471, 'county' => 'Finnmark', 'municipality' => 'Lebesby'],
        ['id' => 472, 'county' => 'Nord-Trøndelag', 'municipality' => 'Røyrvik'],
        ['id' => 473, 'county' => 'Finnmark', 'municipality' => 'Måsøy'],
        ['id' => 474, 'county' => 'Finnmark', 'municipality' => 'Nesseby'],
        ['id' => 475, 'county' => 'Nordland', 'municipality' => 'Hamarøy'],
        ['id' => 476, 'county' => 'Nordland', 'municipality' => 'Hattfjelldal'],
        ['id' => 477, 'county' => 'Nord-Trøndelag', 'municipality' => 'Leka'],
        ['id' => 478, 'county' => 'Finnmark', 'municipality' => 'Tana'],
        ['id' => 479, 'county' => 'Nord-Trøndelag', 'municipality' => 'Fosnes'],
        ['id' => 480, 'county' => 'Finnmark', 'municipality' => 'Vardø'],
        ['id' => 481, 'county' => 'Nordland', 'municipality' => 'Herøy / Fosnavåg'],
        ['id' => 482, 'county' => 'Nordland', 'municipality' => 'Leirfjord'],
        ['id' => 483, 'county' => 'Oppland', 'municipality' => 'Skjåk'],
        ['id' => 484, 'county' => 'Oppland', 'municipality' => 'Lom'],
        ['id' => 485, 'county' => 'Hedmark', 'municipality' => 'Engerdal'],
        ['id' => 486, 'county' => 'Hedmark', 'municipality' => 'Folldal'],
        ['id' => 487, 'county' => 'Nordland', 'municipality' => 'Vevelstad'],
        ['id' => 488, 'county' => 'Oppland', 'municipality' => 'Etnedal'],
        ['id' => 489, 'county' => 'Nordland', 'municipality' => 'Verøy'],
        ['id' => 494, 'county' => 'Sogn og Fjordane', 'municipality' => 'Hornindal'],
        ['id' => 495, 'county' => 'Sogn og Fjordane', 'municipality' => 'Lærdal'],
        ['id' => 496, 'county' => 'Sogn og Fjordane', 'municipality' => 'Naustsdal'],
        ['id' => 497, 'county' => 'Sør-Trøndelag', 'municipality' => 'Snillfjord'],
        ['id' => 498, 'county' => 'Telemark', 'municipality' => 'Fyresdal'],
        ['id' => 499, 'county' => 'Telemark', 'municipality' => 'Hjartdal'],
        ['id' => 500, 'county' => 'Troms', 'municipality' => 'Bjarkøy'],
        ['id' => 501, 'county' => 'Svalbard', 'municipality' => 'Bjørnøya'],
        ['id' => 502, 'county' => 'Svalbard', 'municipality' => 'Hopen'],
        ['id' => 503, 'county' => 'Svalbard', 'municipality' => 'Longyearbyen'],
        ['id' => 504, 'county' => 'Svalbard', 'municipality' => 'Spitsbergen'],
        ['id' => 747, 'county' => 'Utlandet', 'municipality' => 'Afghanistan'],
        ['id' => 748, 'county' => 'Utlandet', 'municipality' => 'Albania'],
        ['id' => 749, 'county' => 'Utlandet', 'municipality' => 'Algerie'],
        ['id' => 750, 'county' => 'Utlandet', 'municipality' => 'Amerikansk Samoa'],
        ['id' => 751, 'county' => 'Utlandet', 'municipality' => 'Andorra'],
        ['id' => 752, 'county' => 'Utlandet', 'municipality' => 'Angola'],
        ['id' => 753, 'county' => 'Utlandet', 'municipality' => 'Anguilla'],
        ['id' => 754, 'county' => 'Utlandet', 'municipality' => 'Antigua og Barbuda'],
        ['id' => 755, 'county' => 'Utlandet', 'municipality' => 'Argentina'],
        ['id' => 756, 'county' => 'Utlandet', 'municipality' => 'Armenia'],
        ['id' => 757, 'county' => 'Utlandet', 'municipality' => 'Aruba'],
        ['id' => 758, 'county' => 'Utlandet', 'municipality' => 'Aserbajdsjan'],
        ['id' => 759, 'county' => 'Utlandet', 'municipality' => 'Australia'],
        ['id' => 760, 'county' => 'Utlandet', 'municipality' => 'Bahamas'],
        ['id' => 761, 'county' => 'Utlandet', 'municipality' => 'Bahrain'],
        ['id' => 762, 'county' => 'Utlandet', 'municipality' => 'Bangladesh'],
        ['id' => 763, 'county' => 'Utlandet', 'municipality' => 'Barbados'],
        ['id' => 764, 'county' => 'Utlandet', 'municipality' => 'Belgia'],
        ['id' => 765, 'county' => 'Utlandet', 'municipality' => 'Belize'],
        ['id' => 766, 'county' => 'Utlandet', 'municipality' => 'Benin'],
        ['id' => 767, 'county' => 'Utlandet', 'municipality' => 'Bermuda'],
        ['id' => 768, 'county' => 'Utlandet', 'municipality' => 'Bhutan'],
        ['id' => 769, 'county' => 'Utlandet', 'municipality' => 'Bolivia'],
        ['id' => 770, 'county' => 'Utlandet', 'municipality' => 'Bosnia-Hercegovina'],
        ['id' => 771, 'county' => 'Utlandet', 'municipality' => 'Botswana'],
        ['id' => 772, 'county' => 'Utlandet', 'municipality' => 'Brasil'],
        ['id' => 773, 'county' => 'Utlandet', 'municipality' => 'Brunei'],
        ['id' => 774, 'county' => 'Utlandet', 'municipality' => 'Bulgaria'],
        ['id' => 775, 'county' => 'Utlandet', 'municipality' => 'Burkina Faso'],
        ['id' => 776, 'county' => 'Utlandet', 'municipality' => 'Burma'],
        ['id' => 777, 'county' => 'Utlandet', 'municipality' => 'Burundi'],
        ['id' => 778, 'county' => 'Utlandet', 'municipality' => 'Canada'],
        ['id' => 779, 'county' => 'Utlandet', 'municipality' => 'Caymanøyene'],
        ['id' => 780, 'county' => 'Utlandet', 'municipality' => 'Chile'],
        ['id' => 781, 'county' => 'Utlandet', 'municipality' => 'Colombia'],
        ['id' => 782, 'county' => 'Utlandet', 'municipality' => 'Cookøyene'],
        ['id' => 783, 'county' => 'Utlandet', 'municipality' => 'Costa Rica'],
        ['id' => 784, 'county' => 'Utlandet', 'municipality' => 'Cuba'],
        ['id' => 785, 'county' => 'Utlandet', 'municipality' => 'Danmark'],
        ['id' => 786, 'county' => 'Utlandet', 'municipality' => 'De amerikanske jomfruøyene'],
        ['id' => 787, 'county' => 'Utlandet', 'municipality' => 'De britiske jomfruøyene'],
        ['id' => 788, 'county' => 'Utlandet', 'municipality' => 'De forente arabiske emirater'],
        ['id' => 789, 'county' => 'Utlandet', 'municipality' => 'De nederlandske Antillene'],
        ['id' => 790, 'county' => 'Utlandet', 'municipality' => 'Den demokratiske republikken Kongo'],
        ['id' => 791, 'county' => 'Utlandet', 'municipality' => 'Den dominikanske republikk'],
        ['id' => 792, 'county' => 'Utlandet', 'municipality' => 'Den europeiske union'],
        ['id' => 793, 'county' => 'Utlandet', 'municipality' => 'Den sentralafrikanske republikk'],
        ['id' => 794, 'county' => 'Utlandet', 'municipality' => 'Djibouti'],
        ['id' => 795, 'county' => 'Utlandet', 'municipality' => 'Dominica'],
        ['id' => 796, 'county' => 'Utlandet', 'municipality' => 'Ecuador'],
        ['id' => 797, 'county' => 'Utlandet', 'municipality' => 'Egypt'],
        ['id' => 798, 'county' => 'Utlandet', 'municipality' => 'Ekvatorial-Guinea'],
        ['id' => 799, 'county' => 'Utlandet', 'municipality' => 'El Salvador'],
        ['id' => 800, 'county' => 'Utlandet', 'municipality' => 'Elfenbenskysten'],
        ['id' => 801, 'county' => 'Utlandet', 'municipality' => 'England'],
        ['id' => 802, 'county' => 'Utlandet', 'municipality' => 'Eritrea'],
        ['id' => 803, 'county' => 'Utlandet', 'municipality' => 'Estland'],
        ['id' => 804, 'county' => 'Utlandet', 'municipality' => 'Etiopia'],
        ['id' => 805, 'county' => 'Utlandet', 'municipality' => 'Falklandsøyene'],
        ['id' => 806, 'county' => 'Utlandet', 'municipality' => 'Fiji'],
        ['id' => 807, 'county' => 'Utlandet', 'municipality' => 'Filippinene'],
        ['id' => 808, 'county' => 'Utlandet', 'municipality' => 'Finland'],
        ['id' => 809, 'county' => 'Utlandet', 'municipality' => 'Kina'],
        ['id' => 810, 'county' => 'Utlandet', 'municipality' => 'Frankrike'],
        ['id' => 811, 'county' => 'Utlandet', 'municipality' => 'Fransk Polynesia'],
        ['id' => 812, 'county' => 'Utlandet', 'municipality' => 'Færøyene'],
        ['id' => 813, 'county' => 'Utlandet', 'municipality' => 'Gabon'],
        ['id' => 814, 'county' => 'Utlandet', 'municipality' => 'Gambia'],
        ['id' => 815, 'county' => 'Utlandet', 'municipality' => 'Gaza'],
        ['id' => 816, 'county' => 'Utlandet', 'municipality' => 'Georgia'],
        ['id' => 817, 'county' => 'Utlandet', 'municipality' => 'Ghana'],
        ['id' => 818, 'county' => 'Utlandet', 'municipality' => 'Gibraltar'],
        ['id' => 819, 'county' => 'Utlandet', 'municipality' => 'Grenada'],
        ['id' => 821, 'county' => 'Utlandet', 'municipality' => 'Guam'],
        ['id' => 822, 'county' => 'Utlandet', 'municipality' => 'Guatemala'],
        ['id' => 823, 'county' => 'Utlandet', 'municipality' => 'Guernsey'],
        ['id' => 824, 'county' => 'Utlandet', 'municipality' => 'Guinea'],
        ['id' => 825, 'county' => 'Utlandet', 'municipality' => 'Guinea-Bissau'],
        ['id' => 826, 'county' => 'Utlandet', 'municipality' => 'Guyana'],
        ['id' => 827, 'county' => 'Utlandet', 'municipality' => 'Haiti'],
        ['id' => 828, 'county' => 'Utlandet', 'municipality' => 'Hellas'],
        ['id' => 829, 'county' => 'Utlandet', 'municipality' => 'Honduras'],
        ['id' => 830, 'county' => 'Utlandet', 'municipality' => 'Hongkong'],
        ['id' => 831, 'county' => 'Utlandet', 'municipality' => 'Hviterussland'],
        ['id' => 832, 'county' => 'Utlandet', 'municipality' => 'India'],
        ['id' => 833, 'county' => 'Utlandet', 'municipality' => 'Indonesia'],
        ['id' => 834, 'county' => 'Utlandet', 'municipality' => 'Irak'],
        ['id' => 835, 'county' => 'Utlandet', 'municipality' => 'Iran'],
        ['id' => 836, 'county' => 'Utlandet', 'municipality' => 'Island'],
        ['id' => 837, 'county' => 'Utlandet', 'municipality' => 'Israel'],
        ['id' => 838, 'county' => 'Utlandet', 'municipality' => 'Italia'],
        ['id' => 839, 'county' => 'Utlandet', 'municipality' => 'Jamaica'],
        ['id' => 840, 'county' => 'Utlandet', 'municipality' => 'Japan'],
        ['id' => 841, 'county' => 'Utlandet', 'municipality' => 'Jemen'],
        ['id' => 842, 'county' => 'Utlandet', 'municipality' => 'Jersey'],
        ['id' => 843, 'county' => 'Utlandet', 'municipality' => 'Jordan'],
        ['id' => 844, 'county' => 'Utlandet', 'municipality' => 'Kambodsja'],
        ['id' => 845, 'county' => 'Utlandet', 'municipality' => 'Kamerun'],
        ['id' => 846, 'county' => 'Utlandet', 'municipality' => 'Kapp Verde'],
        ['id' => 847, 'county' => 'Utlandet', 'municipality' => 'Kasakhstan'],
        ['id' => 848, 'county' => 'Utlandet', 'municipality' => 'Kenya'],
        ['id' => 849, 'county' => 'Utlandet', 'municipality' => 'Kirgisistan'],
        ['id' => 850, 'county' => 'Utlandet', 'municipality' => 'Kiribati'],
        ['id' => 851, 'county' => 'Utlandet', 'municipality' => 'Komorene'],
        ['id' => 852, 'county' => 'Utlandet', 'municipality' => 'Kosovo'],
        ['id' => 853, 'county' => 'Utlandet', 'municipality' => 'Kroatia'],
        ['id' => 854, 'county' => 'Utlandet', 'municipality' => 'Kuwait'],
        ['id' => 855, 'county' => 'Utlandet', 'municipality' => 'Kypros'],
        ['id' => 856, 'county' => 'Utlandet', 'municipality' => 'Laos'],
        ['id' => 857, 'county' => 'Utlandet', 'municipality' => 'Latvia'],
        ['id' => 858, 'county' => 'Utlandet', 'municipality' => 'Lesotho'],
        ['id' => 859, 'county' => 'Utlandet', 'municipality' => 'Libanon'],
        ['id' => 860, 'county' => 'Utlandet', 'municipality' => 'Liberia'],
        ['id' => 861, 'county' => 'Utlandet', 'municipality' => 'Libya'],
        ['id' => 862, 'county' => 'Utlandet', 'municipality' => 'Liechtenstein'],
        ['id' => 863, 'county' => 'Utlandet', 'municipality' => 'Litauen'],
        ['id' => 864, 'county' => 'Utlandet', 'municipality' => 'Luxembourg'],
        ['id' => 865, 'county' => 'Utlandet', 'municipality' => 'Macao'],
        ['id' => 866, 'county' => 'Utlandet', 'municipality' => 'Madagaskar'],
        ['id' => 867, 'county' => 'Utlandet', 'municipality' => 'Makedonia'],
        ['id' => 868, 'county' => 'Utlandet', 'municipality' => 'Malawi'],
        ['id' => 869, 'county' => 'Utlandet', 'municipality' => 'Malaysia'],
        ['id' => 870, 'county' => 'Utlandet', 'municipality' => 'Maldivene'],
        ['id' => 871, 'county' => 'Utlandet', 'municipality' => 'Mali'],
        ['id' => 872, 'county' => 'Utlandet', 'municipality' => 'Malta'],
        ['id' => 873, 'county' => 'Utlandet', 'municipality' => 'Man'],
        ['id' => 874, 'county' => 'Utlandet', 'municipality' => 'Marokko'],
        ['id' => 875, 'county' => 'Utlandet', 'municipality' => 'Marshalløyene'],
        ['id' => 876, 'county' => 'Utlandet', 'municipality' => 'Mauritania'],
        ['id' => 877, 'county' => 'Utlandet', 'municipality' => 'Mauritius'],
        ['id' => 878, 'county' => 'Utlandet', 'municipality' => 'Mayotte'],
        ['id' => 879, 'county' => 'Utlandet', 'municipality' => 'Mexico'],
        ['id' => 880, 'county' => 'Utlandet', 'municipality' => 'Mikronesiaføderasjonen'],
        ['id' => 881, 'county' => 'Utlandet', 'municipality' => 'Moldova'],
        ['id' => 882, 'county' => 'Utlandet', 'municipality' => 'Monaco'],
        ['id' => 883, 'county' => 'Utlandet', 'municipality' => 'Mongolia'],
        ['id' => 884, 'county' => 'Utlandet', 'municipality' => 'Montenegro'],
        ['id' => 885, 'county' => 'Utlandet', 'municipality' => 'Montserrat'],
        ['id' => 886, 'county' => 'Utlandet', 'municipality' => 'Mosambik'],
        ['id' => 887, 'county' => 'Utlandet', 'municipality' => 'Namibia'],
        ['id' => 888, 'county' => 'Utlandet', 'municipality' => 'Nauru'],
        ['id' => 889, 'county' => 'Utlandet', 'municipality' => 'Nederland'],
        ['id' => 890, 'county' => 'Utlandet', 'municipality' => 'Nepal'],
        ['id' => 891, 'county' => 'Utlandet', 'municipality' => 'New Zealand'],
        ['id' => 892, 'county' => 'Utlandet', 'municipality' => 'Nicaragua'],
        ['id' => 893, 'county' => 'Utlandet', 'municipality' => 'Niger'],
        ['id' => 894, 'county' => 'Utlandet', 'municipality' => 'Nigeria'],
        ['id' => 895, 'county' => 'Utlandet', 'municipality' => 'Niue'],
        ['id' => 896, 'county' => 'Utlandet', 'municipality' => 'Nord-Irland'],
        ['id' => 897, 'county' => 'Utlandet', 'municipality' => 'Nord-Korea'],
        ['id' => 898, 'county' => 'Utlandet', 'municipality' => 'Nord-Marianene'],
        ['id' => 899, 'county' => 'Utlandet', 'municipality' => 'Norge'],
        ['id' => 900, 'county' => 'Utlandet', 'municipality' => 'Ny-Caledonia'],
        ['id' => 901, 'county' => 'Utlandet', 'municipality' => 'Oman'],
        ['id' => 902, 'county' => 'Utlandet', 'municipality' => 'Pakistan'],
        ['id' => 903, 'county' => 'Utlandet', 'municipality' => 'Palau'],
        ['id' => 904, 'county' => 'Utlandet', 'municipality' => 'Palestina'],
        ['id' => 905, 'county' => 'Utlandet', 'municipality' => 'Panama'],
        ['id' => 906, 'county' => 'Utlandet', 'municipality' => 'Papua Ny-Guinea'],
        ['id' => 907, 'county' => 'Utlandet', 'municipality' => 'Paraguay'],
        ['id' => 908, 'county' => 'Utlandet', 'municipality' => 'Peru'],
        ['id' => 909, 'county' => 'Utlandet', 'municipality' => 'Polen'],
        ['id' => 910, 'county' => 'Utlandet', 'municipality' => 'Portugal'],
        ['id' => 911, 'county' => 'Utlandet', 'municipality' => 'Puerto Rico'],
        ['id' => 912, 'county' => 'Utlandet', 'municipality' => 'Qatar'],
        ['id' => 913, 'county' => 'Utlandet', 'municipality' => 'Republikken Irland'],
        ['id' => 914, 'county' => 'Utlandet', 'municipality' => 'Republikken Kongo'],
        ['id' => 915, 'county' => 'Utlandet', 'municipality' => 'Romania'],
        ['id' => 916, 'county' => 'Utlandet', 'municipality' => 'Russland'],
        ['id' => 917, 'county' => 'Utlandet', 'municipality' => 'Rwanda'],
        ['id' => 918, 'county' => 'Utlandet', 'municipality' => 'Saint Helena'],
        ['id' => 919, 'county' => 'Utlandet', 'municipality' => 'Saint Kitts og Nevis'],
        ['id' => 920, 'county' => 'Utlandet', 'municipality' => 'Saint Lucia'],
        ['id' => 921, 'county' => 'Utlandet', 'municipality' => 'Saint Pierre og Miquelon'],
        ['id' => 922, 'county' => 'Utlandet', 'municipality' => 'Saint Vincent og Grenadinene'],
        ['id' => 923, 'county' => 'Utlandet', 'municipality' => 'Salomonøyene'],
        ['id' => 924, 'county' => 'Utlandet', 'municipality' => 'Samoa'],
        ['id' => 925, 'county' => 'Utlandet', 'municipality' => 'San Marino'],
        ['id' => 926, 'county' => 'Utlandet', 'municipality' => 'São Tomé og Príncipe'],
        ['id' => 927, 'county' => 'Utlandet', 'municipality' => 'Saudi-Arabia'],
        ['id' => 928, 'county' => 'Utlandet', 'municipality' => 'Senegal'],
        ['id' => 929, 'county' => 'Utlandet', 'municipality' => 'Serbia'],
        ['id' => 930, 'county' => 'Utlandet', 'municipality' => 'Seychellene'],
        ['id' => 931, 'county' => 'Utlandet', 'municipality' => 'Sierra Leone'],
        ['id' => 932, 'county' => 'Utlandet', 'municipality' => 'Singapore'],
        ['id' => 933, 'county' => 'Utlandet', 'municipality' => 'Skottland'],
        ['id' => 934, 'county' => 'Utlandet', 'municipality' => 'Slovakia'],
        ['id' => 935, 'county' => 'Utlandet', 'municipality' => 'Slovenia'],
        ['id' => 936, 'county' => 'Utlandet', 'municipality' => 'Somalia'],
        ['id' => 937, 'county' => 'Utlandet', 'municipality' => 'Spania'],
        ['id' => 938, 'county' => 'Utlandet', 'municipality' => 'Sri Lanka'],
        ['id' => 939, 'county' => 'Utlandet', 'municipality' => 'Sudan'],
        ['id' => 940, 'county' => 'Utlandet', 'municipality' => 'Surinam'],
        ['id' => 941, 'county' => 'Utlandet', 'municipality' => 'Sveits'],
        ['id' => 942, 'county' => 'Utlandet', 'municipality' => 'Sverige'],
        ['id' => 943, 'county' => 'Utlandet', 'municipality' => 'Swaziland'],
        ['id' => 944, 'county' => 'Utlandet', 'municipality' => 'Syria'],
        ['id' => 945, 'county' => 'Utlandet', 'municipality' => 'Sør-Afrika'],
        ['id' => 946, 'county' => 'Utlandet', 'municipality' => 'Sør-Korea'],
        ['id' => 947, 'county' => 'Utlandet', 'municipality' => 'Tadsjikistan'],
        ['id' => 948, 'county' => 'Utlandet', 'municipality' => 'Taiwan'],
        ['id' => 949, 'county' => 'Utlandet', 'municipality' => 'Tanzania'],
        ['id' => 950, 'county' => 'Utlandet', 'municipality' => 'Thailand'],
        ['id' => 951, 'county' => 'Utlandet', 'municipality' => 'Togo'],
        ['id' => 952, 'county' => 'Utlandet', 'municipality' => 'Tokelau'],
        ['id' => 953, 'county' => 'Utlandet', 'municipality' => 'Tonga'],
        ['id' => 954, 'county' => 'Utlandet', 'municipality' => 'Trinidad og Tobago'],
        ['id' => 955, 'county' => 'Utlandet', 'municipality' => 'Tsjad'],
        ['id' => 956, 'county' => 'Utlandet', 'municipality' => 'Tsjekkia'],
        ['id' => 957, 'county' => 'Utlandet', 'municipality' => 'Tunisia'],
        ['id' => 958, 'county' => 'Utlandet', 'municipality' => 'Turkmenistan'],
        ['id' => 959, 'county' => 'Utlandet', 'municipality' => 'Turks- og Caicosøyene'],
        ['id' => 960, 'county' => 'Utlandet', 'municipality' => 'Tuvalu'],
        ['id' => 961, 'county' => 'Utlandet', 'municipality' => 'Tyrkia'],
        ['id' => 962, 'county' => 'Utlandet', 'municipality' => 'Tyskland'],
        ['id' => 963, 'county' => 'Utlandet', 'municipality' => 'Uganda'],
        ['id' => 964, 'county' => 'Utlandet', 'municipality' => 'Ukraina'],
        ['id' => 965, 'county' => 'Utlandet', 'municipality' => 'Ungarn'],
        ['id' => 966, 'county' => 'Utlandet', 'municipality' => 'Uruguay'],
        ['id' => 967, 'county' => 'Utlandet', 'municipality' => 'USA'],
        ['id' => 968, 'county' => 'Utlandet', 'municipality' => 'Usbekistan'],
        ['id' => 969, 'county' => 'Utlandet', 'municipality' => 'Vanuatu'],
        ['id' => 970, 'county' => 'Utlandet', 'municipality' => 'Venezuela'],
        ['id' => 971, 'county' => 'Utlandet', 'municipality' => 'Vietnam'],
        ['id' => 972, 'county' => 'Utlandet', 'municipality' => 'Wales'],
        ['id' => 973, 'county' => 'Utlandet', 'municipality' => 'Wallis og Futuna'],
        ['id' => 974, 'county' => 'Utlandet', 'municipality' => 'Zambia'],
        ['id' => 975, 'county' => 'Utlandet', 'municipality' => 'Zimbabwe'],
        ['id' => 976, 'county' => 'Utlandet', 'municipality' => 'Østerrike'],
        ['id' => 977, 'county' => 'Utlandet', 'municipality' => 'Øst-Timor'],
        ['id' => 978, 'county' => 'Utlandet', 'municipality' => 'Irland'],
        ['id' => 979, 'county' => 'Nordland', 'municipality' => 'Tysfjord'],
        ['id' => 980, 'county' => 'Akershus', 'municipality' => 'Nordre Follo'],
    ];
}


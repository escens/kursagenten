<?php
if (!defined('ABSPATH')) {
    exit;
}

// Hent bedriftsvariabler for tilpassede URL-slugs
$url_options = get_option('kag_seo_option_name');
$kurskategori = !empty($url_options['ka_url_rewrite_kurskategori']) ? $url_options['ka_url_rewrite_kurskategori'] : 'kurskategori';
$kurssted = !empty($url_options['ka_url_rewrite_kurssted']) ? $url_options['ka_url_rewrite_kurssted'] : 'kurssted';
$instruktor = !empty($url_options['ka_url_rewrite_instruktor']) ? $url_options['ka_url_rewrite_instruktor'] : 'instruktorer';

function capitalize_first_letter($string) {
    return ucfirst($string);
}

// Registrering av taksonomien 'kurskategori'
register_taxonomy('ka_coursecategory', array('ka_course', 'ka_coursedate', 'instructor'), array(
    'labels' => array(
        'name' => 'Kurskategorier',
        'singular_name' => capitalize_first_letter($kurskategori),
        'menu_name' => 'Kurskategorier',
        'all_items' => 'Alle kurskategorier',
        'edit_item' => 'Rediger kurskategori',
        'view_item' => 'Vis kurskategori',
        'update_item' => 'Oppdater kurskategori',
        'add_new_item' => 'Legg til kurskategori',
        'new_item_name' => 'Nytt navn for kurskategori',
        'parent_item' => 'Foreldrekategori',
        'parent_item_colon' => 'Foreldrekategori:',
        'search_items' => 'Søk etter kurskategori',
        'most_used' => 'Mest brukt',
        'not_found' => 'Ingen kurskategorier funnet',
        'no_terms' => 'Ingen kurskategorier',
        'name_field_description' => 'Navnet er det som vises på siden',
        'slug_field_description' => '"Slug" er den SEO-vennlige versjonen av url-en. Eksempel /mitt-kurs',
        'parent_field_description' => 'Velg en forelder for å lage et hierarki, og la dette bli en subkategori.',
        'desc_field_description' => 'Kort beskrivelse brukes i oversikter og som innledende tekst på detaljside',
        'filter_by_item' => 'Filtrer på kurskategori',
        'items_list_navigation' => 'Kurskategorier listenavigasjon',
        'items_list' => 'Kurskategorier liste',
        'back_to_items' => '← Tilbake til kurskategorier',
        'item_link' => 'Kurskategori link',
        'item_link_description' => 'Link til en kurskategori',
        'archives'  => capitalize_first_letter($kurskategori),
    ),
    'public' => true,
    'hierarchical' => true,
    'show_ui' => true,
    'show_in_menu' => false,
    'show_in_rest' => true,
    'show_admin_column' => true,
    'rewrite' => array(
        'slug' => $kurskategori,
    ),
));

// Registrering av taksonomien 'kurssted'
register_taxonomy('ka_course_location', array('ka_course', 'ka_coursedate', 'instructor'), array(
    'labels' => array(
        'name' => 'Kurssteder',
        'singular_name' => capitalize_first_letter($kurssted),
        'menu_name' => 'Kurssteder',
        'all_items' => 'Alle kurssteder',
        'edit_item' => 'Rediger kurssted',
        'view_item' => 'Vis kurssted',
        'update_item' => 'Oppdater kurssted',
        'add_new_item' => 'Legg til kurssted',
        'new_item_name' => 'Navn på nytt kurssted',
        'parent_item' => 'Overordnet kurssted',
        'parent_item_colon' => 'Overordnet kurssted:',
        'search_items' => 'Søk i kurssteder',
        'most_used' => 'Mest brukt',
        'not_found' => 'Ingen kurssteder funnet',
        'no_terms' => 'Ingen kurssteder',
        'filter_by_item' => 'Filtrer på kurssted',
        'items_list_navigation' => 'Kurssteder listenavigasjon',
        'items_list' => 'Kurssteder liste',
        'back_to_items' => '← Tilbake til kurssteder',
        'item_link' => 'Kurssted link',
        'item_link_description' => 'Link til et kurssted',
        'archives'  => capitalize_first_letter($kurssted),
        'name_field_description' => 'Navnet slik det som vises på siden. Kan endres under <a href="' . admin_url('admin.php?page=kursinnstillinger#places') . '">Synkronisering</a>.',
        'slug_field_description' => '"Slug" er den SEO-vennlige versjonen av url-en. Eksempel /oslo',
        'parent_field_description' => 'Velg en forelder for å lage et hierarki, og la dette bli en subkategori.',
        'desc_field_description' => 'Kort beskrivelse brukes i oversikter og som innledende tekst på detaljside',
    ),
    'public' => true,
    'hierarchical' => true,
    'show_ui' => true,
    'show_in_menu' => false,
    'show_admin_column' => true,
    'show_in_rest' => true,
    'rewrite' => array(
        'slug' => $kurssted,
    ),
));

// Registrering av taksonomien 'instruktorer'
register_taxonomy('ka_instructors', array('ka_course', 'ka_coursedate', 'instructor'), array(
    'labels' => array(
        'name' => 'Instruktører',
        'singular_name' => 'Instruktør',
        'menu_name' => 'Instruktører',
        'all_items' => 'Alle instruktører',
        'edit_item' => 'Rediger instruktør',
        'view_item' => 'Vis instruktør',
        'update_item' => 'Oppdater instruktør',
        'add_new_item' => 'Legg til instruktør',
        'new_item_name' => 'Navn på nytt instruktør',
        'parent_item' => 'Overordnet instruktør',
        'parent_item_colon' => 'Overordnet instruktør:',
        'search_items' => 'Søk i instruktører',
        'most_used' => 'Mest brukt',
        'not_found' => 'Ingen instruktører funnet',
        'no_terms' => 'Ingen instruktører',
        'filter_by_item' => 'Filtrer på instruktør',
        'items_list_navigation' => 'Instruktører listenavigasjon',
        'items_list' => 'Instruktører liste',
        'back_to_items' => '← Tilbake til instruktører',
        'item_link' => 'Instruktørlink',
        'item_link_description' => 'Link til et instruktør',
        'archives'  => 'Instruktører',
        'name_field_description' => 'Navnet slik det som vises på siden. Bør kun endres på instruktørens brukerprofil på Kursagenten.',
        'slug_field_description' => '"Slug" er den SEO-vennlige versjonen av url-en. Eksempel /kari-norman',
        'parent_field_description' => 'Velg en forelder for å lage et hierarki, og la dette bli en subkategori.',
        'desc_field_description' => 'Kort beskrivelse brukes i oversikter og som innledende tekst på detaljside',
    ),
    'public' => true,
    'hierarchical' => false,
    'show_ui' => true,
    'show_in_menu' => false,
    'show_admin_column' => true,
    'show_in_rest' => true,
    'rewrite' => array(
        'slug' => $instruktor,
    ),
));

// Add taxonomies as submenus under Kursagenten main menu and reorganize menu order
add_action('admin_menu', function() {
    global $submenu;
    
    // Add taxonomy submenus under Kursagenten menu (parent 'kursagenten')
    add_submenu_page(
        'kursagenten',                                      // Parent slug
        'Kurskategorier',                                   // Page title
        'Kurskategorier',                                   // Menu title
        'manage_categories',                                // Capability
        'edit-tags.php?taxonomy=ka_coursecategory&post_type=ka_course', // Menu slug
        ''                                                  // Callback (empty for taxonomy links)
    );
    
    add_submenu_page(
        'kursagenten',
        'Kurssteder',
        'Kurssteder',
        'manage_categories',
        'edit-tags.php?taxonomy=ka_course_location&post_type=ka_course',
        ''
    );
    
    add_submenu_page(
        'kursagenten',
        'Instruktører',
        'Instruktører',
        'manage_categories',
        'edit-tags.php?taxonomy=ka_instructors&post_type=ka_course',
        ''
    );
}, 11); // Priority 11 to run after main menu (priority 9) but before reorganization

// Reorganize submenu order and add separators
add_action('admin_menu', function() {
    global $submenu;
    
    if (!isset($submenu['kursagenten'])) {
        return;
    }
    
    // Create a map of menu items by their slug for easy lookup
    $menu_items = [];
    foreach ($submenu['kursagenten'] as $item) {
        $menu_items[$item[2]] = $item;
    }
    
    // Define the desired order with separators
    $desired_order = [
        'kursagenten',                                                      // Oversikt
        'separator_1',                                                      // First separator
        'edit.php?post_type=ka_course',                                    // Alle kurs
        'edit-tags.php?taxonomy=ka_coursecategory&post_type=ka_course',   // Kurskategorier
        'edit-tags.php?taxonomy=ka_course_location&post_type=ka_course',  // Kurssteder
        'edit-tags.php?taxonomy=ka_instructors&post_type=ka_course',      // Instruktører
        'separator_2',                                                      // Second separator
        'design',                                                           // Kursdesign
        'kursinnstillinger',                                                // Synkronisering
        'bedriftsinformasjon',                                              // Bedriftsinformasjon
        'kursagenten-theme-customizations',                                 // Tematilpasninger
        'seo',                                                              // Endre url-er
        'avansert',                                                         // Avanserte innstillinger
        'ka_documentation',                                                 // Dokumentasjon
    ];
    
    // Build new submenu array in desired order
    $new_submenu = [];
    foreach ($desired_order as $slug) {
        if ($slug === 'separator_1' || $slug === 'separator_2') {
            // Add separator marker - we'll style the next item
            continue;
        }
        
        if (isset($menu_items[$slug])) {
            $item = $menu_items[$slug];
            
            // Add separator class to items after separators
            if ($slug === 'edit.php?post_type=ka_course') {
                $item[4] = 'kag-menu-separator-before';
            } elseif ($slug === 'design') {
                $item[4] = 'kag-menu-separator-before';
            }
            
            $new_submenu[] = $item;
        }
    }
    
    // Replace the submenu with our reorganized version
    $submenu['kursagenten'] = $new_submenu;
}, 999); // Very high priority to run after all other menu registrations

// Add link to Kursdatoer at the top of "Alle kurs" admin page
add_action('all_admin_notices', function() {
    $screen = get_current_screen();
    
    // Check if we're on the ka_course edit page
    if ($screen && $screen->post_type === 'ka_course' && $screen->base === 'edit') {
        $kursdatoer_url = admin_url('edit.php?post_type=ka_coursedate');
        ?>
        <div style="margin-top: 0px; padding: 12px 0; border-left-color: #2271b1;">
            <p style="margin: 0;">
                <strong>Kursdatoer:</strong> 
                <a href="<?php echo esc_url($kursdatoer_url); ?>">Se alle kursdatoer</a> 
                <span style="color: #666; margin-left: 10px;">– Brukes for feilsøking og oversikt</span>
            </p>
        </div>
        <?php
    }
});

// Keep Kursagenten menu open when on taxonomy pages
add_filter('parent_file', function($parent_file) {
    global $current_screen;
    
    // Check if we're on one of our taxonomy edit pages
    if ($current_screen && in_array($current_screen->taxonomy, ['ka_coursecategory', 'ka_course_location', 'ka_instructors'])) {
        return 'kursagenten';
    }
    
    return $parent_file;
});

// Highlight the correct submenu item when on taxonomy pages
add_filter('submenu_file', function($submenu_file, $parent_file) {
    global $current_screen;
    
    // Only apply to our taxonomies under Kursagenten menu
    if ($parent_file === 'kursagenten' && $current_screen) {
        if ($current_screen->taxonomy === 'ka_coursecategory') {
            return 'edit-tags.php?taxonomy=ka_coursecategory&post_type=ka_course';
        } elseif ($current_screen->taxonomy === 'ka_course_location') {
            return 'edit-tags.php?taxonomy=ka_course_location&post_type=ka_course';
        } elseif ($current_screen->taxonomy === 'ka_instructors') {
            return 'edit-tags.php?taxonomy=ka_instructors&post_type=ka_course';
        }
    }
    
    return $submenu_file;
}, 10, 2);

// Make name field readonly for ka_course_location taxonomy and update description
add_action('ka_course_location_edit_form_fields', function($term) {
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Make name field readonly
        $('#name').prop('readonly', true).css('background-color', '#f0f0f0');
        
        // Make slug field readonly
        $('#slug').prop('readonly', true).css('background-color', '#f0f0f0');
        
        // Update description text
        var $desc = $('#name-description');
        if ($desc.length) {
            var syncUrl = '<?php echo esc_js(admin_url('admin.php?page=kursinnstillinger#places')); ?>';
            $desc.html('Navnet slik det som vises på siden. Kan endres under <a href="' + syncUrl + '">Synkronisering</a>.');
        }
    });
    </script>
    <?php
}, 10, 1);

// Replace the add form with information message
add_action('ka_course_location_pre_add_form', function($taxonomy) {
    $sync_url = admin_url('admin.php?page=kursinnstillinger#places');
    $regions_url = admin_url('admin.php?page=kursinnstillinger#regions');
    $use_regions = get_option('kursagenten_use_regions', false);
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Create the information message
        var infoHtml = '<div class="form-wrap" style="margin-top: 0;">' +
            '<h2>Informasjon om kurssteder</h2>' +
            '<div class="notice notice-info" style="padding: 15px; margin: 15px 0;">' +
            '<p style="margin-bottom: 10px;">' +
            '<strong>Kurssteder opprettes automatisk</strong> når du synkroniserer kurs fra Kursagenten. Du kan ikke legge til kurssteder manuelt her.' +
            '</p>' +
            '<p style="margin-bottom: 10px;">' +
            '<strong>Navnendring på kurssteder:</strong><br>' +
            'Du kan endre navn på kurssteder under <a href="<?php echo esc_js($sync_url); ?>">Synkronisering → Navnendring på kurssteder</a>. ' +
            'Når du endrer navn på et sted, blir også slugs (nettadressen) på kursene som har dette stedet oppdatert.<br> Det gamle stedet blir ikke slettet, men blir ikke lenger synlig på nettsiden.' +
            '</p>' +
            <?php if ($use_regions) : ?>
            '<p style="margin-bottom: 0;">' +
            '<strong>Regioner:</strong><br>' +
            'Regioner er aktivert. Du kan administrere regioninndelingen under <a href="<?php echo esc_js($regions_url); ?>">Synkronisering → Regioner</a>. Tilhørighet til en region kan endres under hvert kurssted.' +
            '</p>' +
            <?php else : ?>
            '<p style="margin-bottom: 0;">' +
            '<strong>Regioner:</strong><br>' +
            'Du kan aktivere og administrere regioner under <a href="<?php echo esc_js($regions_url); ?>">Synkronisering → Regioner</a>.' +
            '</p>' +
            <?php endif; ?>
            '</div>' +
            '</div>';
        
        // Wait for the form to be rendered, then replace it
        setTimeout(function() {
            var $formWrap = $('#col-left .form-wrap');
            if ($formWrap.length) {
                // Hide the form but keep the structure
                $formWrap.find('form#addtag').hide();
                $formWrap.find('h2').text('Informasjon om kurssteder');
                
                // Insert info message after h2
                $formWrap.find('h2').after('<div class="notice notice-info" style="padding: 15px; margin: 15px 0;">' +
                    '<p style="margin-bottom: 10px;">' +
                    '<strong>Kurssteder opprettes automatisk</strong> når du synkroniserer kurs fra Kursagenten. Du kan ikke legge til kurssteder manuelt her.' +
                    '</p>' +
                    '<p style="margin-bottom: 10px;">' +
                    '<strong>Navnendring på kurssteder:</strong><br>' +
                    'Du kan endre navn på kurssteder under <a href="<?php echo esc_js($sync_url); ?>">Synkronisering → Navnendring på kurssteder</a>. ' +
                    'Når du endrer navn på et sted, blir også slugs (nettadressen) på kursene som har dette stedet oppdatert.<br> Det gamle stedet blir ikke slettet, men blir ikke lenger synlig på nettsiden.' +
                    '</p>' +
                    <?php if ($use_regions) : ?>
                    '<p style="margin-bottom: 0;">' +
                    '<strong>Regioner:</strong><br>' +
                    'Regioner er aktivert. Du kan administrere regioninndelingen under <a href="<?php echo esc_js($regions_url); ?>">Synkronisering → Regioner</a>. Tilhørighet til en region kan endres under hvert kurssted.' +
                    '</p>' +
                    <?php else : ?>
                    '<p style="margin-bottom: 0;">' +
                    '<strong>Regioner:</strong><br>' +
                    'Du kan aktivere og administrere regioner under <a href="<?php echo esc_js($regions_url); ?>">Synkronisering → Regioner</a>.' +
                    '</p>' +
                    <?php endif; ?>
                    '</div>');
            }
        }, 100);
    });
    </script>
    <?php
});

// Store original term name and slug before update to prevent changes
add_action('load-edit-tags.php', function() {
    if (isset($_GET['taxonomy']) && $_GET['taxonomy'] === 'ka_course_location' && isset($_GET['tag_ID'])) {
        $term_id = (int) $_GET['tag_ID'];
        $term = get_term($term_id, 'ka_course_location');
        if ($term && !is_wp_error($term)) {
            // Store original name and slug in transient
            set_transient('ka_location_original_name_' . $term_id, $term->name, 300);
            set_transient('ka_location_original_slug_' . $term_id, $term->slug, 300);
        }
    }
});

// Prevent name and slug changes via form submission
add_action('edit_term', function($term_id, $tt_id, $taxonomy) {
    static $preventing_loop = false;
    
    // Only apply to ka_course_location taxonomy
    if ($taxonomy !== 'ka_course_location' || $preventing_loop) {
        return;
    }
    
    // Get original name and slug from transient
    $original_name = get_transient('ka_location_original_name_' . $term_id);
    $original_slug = get_transient('ka_location_original_slug_' . $term_id);
    
    $needs_revert = false;
    $update_data = array();
    
    // Check if name was changed
    if ($original_name && isset($_POST['name']) && $_POST['name'] !== $original_name) {
        $update_data['name'] = $original_name;
        $needs_revert = true;
    }
    
    // Check if slug was changed
    if ($original_slug && isset($_POST['slug']) && $_POST['slug'] !== $original_slug) {
        $update_data['slug'] = $original_slug;
        $needs_revert = true;
    }
    
    if ($needs_revert) {
        // Revert the changes
        $preventing_loop = true;
        wp_update_term($term_id, $taxonomy, $update_data);
        $preventing_loop = false;
        
        // Delete transients
        delete_transient('ka_location_original_name_' . $term_id);
        delete_transient('ka_location_original_slug_' . $term_id);
        
        // Show admin notice
        add_action('admin_notices', function() {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p><strong>Advarsel:</strong> Navn og slug på kurssteder kan ikke endres her. Du kan endre navnet under <a href="<?php echo esc_url(admin_url('admin.php?page=kursinnstillinger#places')); ?>">Synkronisering</a>.</p>
            </div>
            <?php
        });
    } else {
        // Clean up transients
        delete_transient('ka_location_original_name_' . $term_id);
        delete_transient('ka_location_original_slug_' . $term_id);
    }
}, 5, 3); // Priority 5 to run early

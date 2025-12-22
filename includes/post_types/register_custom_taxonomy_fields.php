<?php
if (!defined('ABSPATH')) {
    exit;
}

// Custom fields for CPT kurs and taxonomies
//****************************************** */


// Taxonomy Rich text metabox
// -----------------------------
function custom_taxonomy_rich_text_editor($term) {
    if (!isset($term->term_id)) {
        return;
    }
    
    $rich_description = get_term_meta($term->term_id, 'rich_description', true);
    ?>
    <tr class="form-field">
        <th scope="row" valign="top"><label for="rich_description">Utvidet beskrivelse</label></th>
        <td>
            <?php
            wp_editor($rich_description, 'rich_description', array(
                'textarea_name' => 'rich_description',
                'textarea_rows' => 10,
                'media_buttons' => true
            ));
            ?>
        </td>
    </tr>
    <?php
}



// Taxonomy Image upload metabox
// -----------------------------
function add_taxonomy_image_field($term, $taxonomy, $field_name, $label_text, $button_type_label, $description = '') {
    if (!isset($term->term_id)) {
        return;
    }
    
    $image_url = get_term_meta($term->term_id, $field_name, true);
    ?>
    <tr class="form-field image-field">
        <th scope="row" valign="top"><label for="<?php echo esc_attr($field_name); ?>"><?php echo esc_html($label_text); ?></label></th>
        <td>
            <div class="image-upload-container">
            <img id="<?php echo esc_attr($field_name); ?>_preview" src="<?php echo esc_url($image_url); ?>" style="max-height:250px; margin-top:10px; display:<?php echo $image_url ? 'block' : 'none'; ?>" />
            <input type="button" class="button button-secondary upload_image_button_<?php echo esc_attr($field_name); ?>" value="Last opp <?php echo esc_attr($button_type_label); ?>" />
            <input type="button" class="button button-secondary remove_image_button_<?php echo esc_attr($field_name); ?>" value="Fjern <?php echo esc_attr($button_type_label); ?>" style="<?php echo $image_url ? 'display:inline-block;' : 'display:none;'; ?>" />
            <input type="hidden" id="<?php echo esc_attr($field_name); ?>" name="<?php echo esc_attr($field_name); ?>" value="<?php echo esc_attr($image_url); ?>" />
            <?php if ($description): ?><p class="description" style="margin-top:.9em"><?php echo esc_html($description); ?></p><?php endif; ?>
            </div>
        </td>
    </tr>
    <?php
}


// Add region field for location taxonomy
function add_location_region_field($term) {
    if (!isset($term->term_id)) {
        return;
    }
    
    // Check if regions are enabled
    $use_regions = get_option('kursagenten_use_regions', false);
    if (!$use_regions) {
        return;
    }
    
    require_once KURSAG_PLUGIN_DIR . '/includes/helpers/location-regions.php';
    
    $current_region = get_term_meta($term->term_id, 'location_region', true);
    $regions = kursagenten_get_region_mapping();
    ?>
    <tr class="form-field">
        <th scope="row"><label for="location_region">Region</label></th>
        <td>
            <select name="location_region" id="location_region">
                <option value="">Ingen region</option>
                <?php foreach ($regions as $region_key => $region_data) : 
                    $region_label = kursagenten_get_region_display_name($region_key);
                ?>
                    <option value="<?php echo esc_attr($region_key); ?>" <?php selected($current_region, $region_key); ?>>
                        <?php echo esc_html($region_label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="description">Velg hvilken region denne lokasjonen tilhører.</p>
        </td>
    </tr>
    <?php
}
add_action('ka_course_location_edit_form_fields', 'add_location_region_field');

// Callback functions for each taxonomy, passing the correct parameters
function add_coursecategory_image_field($term) {
    add_taxonomy_image_field($term, 'ka_coursecategory', 'image_coursecategory', 'Hovedbilde', 'bilde', 'Hovedbilde som brukes på kategorisiden og i kategorioversikter');
}
add_action('ka_coursecategory_edit_form_fields', 'add_coursecategory_image_field');

function add_coursecategory_icon_field($term) {
    add_taxonomy_image_field($term, 'ka_coursecategory', 'icon_coursecategory', 'Ikon', 'bilde av ikon', 'Bruk en .png bildefil. Du kan laste ned ikoner på feks. https://thenounproject.com/');
}
add_action('ka_coursecategory_edit_form_fields', 'add_coursecategory_icon_field');

function add_course_location_image_field($term) {
    add_taxonomy_image_field($term, 'ka_course_location', 'image_course_location', 'Bilde av kurssted', 'bilde');
}
add_action('ka_course_location_edit_form_fields', 'add_course_location_image_field');

// Legg til instruktør-felt
function add_instructor_image_field($term) {
    add_taxonomy_image_field($term, 'ka_instructors', 'image_instructor', 'Alternativt bilde', 'bilde', 
        'Dette bildet kan brukes som et alternativt bilde på instruktørprofilen.');
}
add_action('ka_instructors_edit_form_fields', 'add_instructor_image_field');

// Legg til instruktør-felt
function add_instructor_profile_image_field($term) {
    add_taxonomy_image_field($term, 'ka_instructors', 'image_instructor_ka', 'Profilbilde', 'bilde', 
        'Dette bildet brukes som hovedbilde på instruktørprofilen.');
}
add_action('ka_instructors_edit_form_fields', 'add_instructor_profile_image_field');



// Taxonomy Visibility field
// -----------------------------
function add_taxonomy_visibility_field($term) {
    if (!isset($term->term_id)) {
        return;
    }
    
    $taxonomy = $term->taxonomy;
    $visibility = get_term_meta($term->term_id, 'hide_in_list', true);
    $menu_visibility = get_term_meta($term->term_id, 'hide_in_menu', true);
    $course_list_visibility = get_term_meta($term->term_id, 'hide_in_course_list', true);
    
    if (empty($visibility)) {
        $visibility = 'Vis'; // Standard verdi
    }
    if (empty($menu_visibility)) {
        $menu_visibility = 'Vis'; // Standard verdi
    }
    if (empty($course_list_visibility)) {
        $course_list_visibility = 'Vis'; // Standard verdi
    }
    ?>
    <tr class="form-field">
        <th scope="row"><label for="hide_in_list">Synlighet</label></th>
        <td>
            <label style="margin-right: 15px;">
                <input type="radio" name="hide_in_list" value="Vis" <?php checked($visibility, 'Vis'); ?>>
                Vis
            </label>
            <label>
                <input type="radio" name="hide_in_list" value="Skjul" <?php checked($visibility, 'Skjul'); ?>>
                Skjul i oversiktslister
            </label>
            <p class="description">Velg om denne skal vises i kategorilister og oversikter.</p>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row"><label for="hide_in_menu">Menyer</label></th>
        <td>
            <label style="margin-right: 15px;">
                <input type="radio" name="hide_in_menu" value="Vis" <?php checked($menu_visibility, 'Vis'); ?>>
                Vis
            </label>
            <label>
                <input type="radio" name="hide_in_menu" value="Skjul" <?php checked($menu_visibility, 'Skjul'); ?>>
                Skjul i automenyer
            </label>
            <p class="description">Velg om denne skal vises i autogenererte menyer.</p>
        </td>
    </tr>
    <?php if ($taxonomy === 'ka_coursecategory'): ?>
    <tr class="form-field">
        <th scope="row"><label for="hide_in_course_list">Kursliste</label></th>
        <td>
            <label style="margin-right: 15px;">
                <input type="radio" name="hide_in_course_list" value="Vis" <?php checked($course_list_visibility, 'Vis'); ?>>
                Vis
            </label>
            <label>
                <input type="radio" name="hide_in_course_list" value="Skjul" <?php checked($course_list_visibility, 'Skjul'); ?>>
                Skjul tilhørende kurs, og i kategorifilter
            </label>
            <p class="description">Velg om denne kategorien og tilhørende kurs skal vises i kurslisten.</p>
        </td>
    </tr>
    <?php endif; ?>
    <?php
}

// Legg til feltet i hurtigredigering
function add_quick_edit_visibility_field($column_name, $taxonomy) {
    // Only add the field once per taxonomy
    static $added_fields = array();
    
    if (isset($added_fields[$taxonomy])) {
        return;
    }
    
    $added_fields[$taxonomy] = true;
    ?>
    <fieldset>
        <div class="inline-edit-col">
            <label>
                <span class="title">Synlighet</span>
                <span class="input-text-wrap">
                    <label class="alignleft" style="margin-right: 15px;">
                        <input type="radio" name="quick_edit_hide_in_list" value="Vis">
                        <span class="checkbox-title">Vis</span>
                    </label>
                    <label class="alignleft">
                        <input type="radio" name="quick_edit_hide_in_list" value="Skjul">
                        <span class="checkbox-title">Skjul i oversiktslister</span>
                    </label>
                </span>
            </label>
        </div>
    </fieldset>
    <fieldset>
        <div class="inline-edit-col">
            <label>
                <span class="title">Menyer</span>
                <span class="input-text-wrap">
                    <label class="alignleft" style="margin-right: 15px;">
                        <input type="radio" name="quick_edit_hide_in_menu" value="Vis">
                        <span class="checkbox-title">Vis</span>
                    </label>
                    <label class="alignleft">
                        <input type="radio" name="quick_edit_hide_in_menu" value="Skjul">
                        <span class="checkbox-title">Skjul i automenyer</span>
                    </label>
                </span>
            </label>
        </div>
    </fieldset>
    <fieldset class="course-list-visibility" style="display:none">
        <div class="inline-edit-col">
            <label>
                <span class="title">Kursliste</span>
                <span class="input-text-wrap">
                    <label class="alignleft" style="margin-right: 15px;">
                        <input type="radio" name="quick_edit_hide_in_course_list" value="Vis">
                        <span class="checkbox-title">Vis</span>
                    </label>
                    <label class="alignleft">
                        <input type="radio" name="quick_edit_hide_in_course_list" value="Skjul">
                        <span class="checkbox-title">Skjul tilhørende kurs, og i kategorifilter</span>
                    </label>
                </span>
            </label>
        </div>
    </fieldset>
    <?php
}

// Legg til kolonne i taksonomi-tabellen
function add_taxonomy_visibility_column($columns) {
    $columns['visibility'] = 'Synlighet';
    
    // Add region column for location taxonomy if regions are enabled
    global $current_screen;
    if (isset($current_screen->taxonomy) && $current_screen->taxonomy === 'ka_course_location') {
        $use_regions = get_option('kursagenten_use_regions', false);
        if ($use_regions) {
            $columns['region'] = 'Region';
        }
    }
    
    return $columns;
}

// Vis innhold i kolonnen
function manage_taxonomy_visibility_column($content, $column_name, $term_id) {
    if ($column_name === 'visibility') {
        $visibility = get_term_meta($term_id, 'hide_in_list', true);
        $menu_visibility = get_term_meta($term_id, 'hide_in_menu', true);
        $course_list_visibility = get_term_meta($term_id, 'hide_in_course_list', true);
        
        $output = '';
        
        if ($visibility === 'Skjul') {
            $output .= '<span class="visibility-tag" style="color: rgb(226, 91, 102);">Skjult i lister</span>';
        }
        if ($menu_visibility === 'Skjul') {
            $output .= '<span class="visibility-tag" style="color: rgb(226, 91, 102);">Skjult i menyer</span>';
        }
        if ($course_list_visibility === 'Skjul') {
            $output .= '<span class="visibility-tag" style="color: rgb(226, 91, 102);">Skjult i kursliste</span>';
        }
        
        return $output;
    }
    
    // Handle region column
    if ($column_name === 'region') {
        require_once KURSAG_PLUGIN_DIR . '/includes/helpers/location-regions.php';
        $region = get_term_meta($term_id, 'location_region', true);
        
        if (!empty($region)) {
            $region_label = kursagenten_get_region_display_name($region);
            return '<span style="padding: 3px 8px; background: #f0f0f0; border-radius: 3px; font-size: 12px;">' . esc_html($region_label) . '</span>';
        }
        
        return '<span style="color: #999; font-style: italic;">Ingen region</span>';
    }
    
    return $content;
}

// Taxonomy save function med forbedret sikkerhet
// -----------------------------
function save_taxonomy_field($term_id) {
    if (!current_user_can('manage_categories')) {
        return;
    }
    
    $fields = [
        'image_coursecategory' => 'esc_url',
        'icon_coursecategory' => 'esc_url',
        'image_course_location' => 'esc_url',
        'image_instructor' => 'esc_url',
        'rich_description' => function($content) {
            $allowed_html = wp_kses_allowed_html('post');
            $allowed_html['iframe'] = array(
                'src' => true,
                'width' => true,
                'height' => true,
                'frameborder' => true,
                'allowfullscreen' => true,
                'style' => true,
                'class' => true
            );
            $allowed_html['video'] = array(
                'src' => true,
                'width' => true,
                'height' => true,
                'controls' => true,
                'autoplay' => true,
                'loop' => true,
                'muted' => true,
                'poster' => true,
                'style' => true,
                'class' => true
            );
            $allowed_html['source'] = array(
                'src' => true,
                'type' => true
            );
            return wp_kses($content, $allowed_html);
        },
        'instructor_email' => 'sanitize_email',
        'instructor_phone' => 'sanitize_text_field',
        'instructor_firstname' => 'sanitize_text_field',
        'instructor_lastname' => 'sanitize_text_field',
        'hide_in_list' => 'sanitize_text_field',
        'hide_in_menu' => 'sanitize_text_field',
        'hide_in_course_list' => 'sanitize_text_field',
        'location_region' => function($value) {
            // Convert to internal (ASCII) format and validate
            require_once KURSAG_PLUGIN_DIR . '/includes/helpers/location-regions.php';
            $value = trim($value);
            $internal_region = kursagenten_get_region_internal_name($value);
            $valid_regions = kursagenten_get_valid_regions();
            if (in_array($internal_region, $valid_regions, true)) {
                return $internal_region;
            }
            return '';
        },
    ];
    
    // Sjekk om dette er en hurtigredigering
    $is_quick_edit = isset($_POST['action']) && $_POST['action'] === 'inline-save-tax';
    
    // Sjekk om dette er en manuell instruktør (uten instructor_id)
    $is_manual_instructor = empty(get_term_meta($term_id, 'instructor_id', true));
    
    // Hvis dette er en hurtigredigering, ikke sett edited-flaggene
    if (!$is_quick_edit) {
        if ($is_manual_instructor) {
            // For manuelle instruktører, sett alltid edited-flaggene
            if (isset($_POST['instructor_email'])) {
                update_term_meta($term_id, 'instructor_email_edited', 'yes');
            }
            if (isset($_POST['instructor_phone'])) {
                update_term_meta($term_id, 'instructor_phone_edited', 'yes');
            }
            if (isset($_POST['instructor_firstname'])) {
                update_term_meta($term_id, 'instructor_firstname_edited', 'yes');
            }
            if (isset($_POST['instructor_lastname'])) {
                update_term_meta($term_id, 'instructor_lastname_edited', 'yes');
            }
            if (isset($_POST['image_instructor_ka'])) {
                update_term_meta($term_id, 'instructor_image_edited', 'yes');
            }
        } else {
            // For Kursagenten-instruktører, sett flaggene basert på toggle-status
            $image_override = isset($_POST['instructor_image_override_toggle']) && $_POST['instructor_image_override_toggle'] === 'on';
            $profile_override = isset($_POST['instructor_override_toggle']) && $_POST['instructor_override_toggle'] === 'on';
            
            // Sett edited-flaggene basert på toggle-status og om feltene er endret
            if ($image_override) {
                if (isset($_POST['image_instructor_ka'])) {
                    update_term_meta($term_id, 'instructor_image_edited', 'yes');
                }
            } else {
                delete_term_meta($term_id, 'instructor_image_edited');
            }
            
            if ($profile_override) {
                if (isset($_POST['instructor_email'])) {
                    update_term_meta($term_id, 'instructor_email_edited', 'yes');
                }
                if (isset($_POST['instructor_phone'])) {
                    update_term_meta($term_id, 'instructor_phone_edited', 'yes');
                }
                if (isset($_POST['instructor_firstname'])) {
                    update_term_meta($term_id, 'instructor_firstname_edited', 'yes');
                }
                if (isset($_POST['instructor_lastname'])) {
                    update_term_meta($term_id, 'instructor_lastname_edited', 'yes');
                }
            } else {
                delete_term_meta($term_id, 'instructor_email_edited');
                delete_term_meta($term_id, 'instructor_phone_edited');
                delete_term_meta($term_id, 'instructor_firstname_edited');
                delete_term_meta($term_id, 'instructor_lastname_edited');
            }
        }
    }
    
    // Check if regions are enabled before saving region field
    $use_regions = get_option('kursagenten_use_regions', false);
    
    // Oppdater feltene
    foreach ($fields as $field => $sanitize_callback) {
        // Skip location_region if regions are disabled
        if ($field === 'location_region' && !$use_regions) {
            // Remove region data if regions are disabled
            delete_term_meta($term_id, 'location_region');
            continue;
        }
        
        if (isset($_POST[$field])) {
            $value = $_POST[$field];
            if (is_callable($sanitize_callback)) {
                $value = call_user_func($sanitize_callback, $value);
            }
            
            // For location_region, only save if regions are enabled and value is not empty
            if ($field === 'location_region') {
                if ($use_regions && !empty($value)) {
                    // Lagre valgt region og marker at denne er satt manuelt
                    update_term_meta($term_id, $field, $value);
                    update_term_meta($term_id, 'location_region_manual', 'yes');
                } else {
                    // Tøm region og fjern manuell-flagget
                    delete_term_meta($term_id, $field);
                    delete_term_meta($term_id, 'location_region_manual');
                }
            } else {
                update_term_meta($term_id, $field, $value);
            }
        } elseif ($field === 'location_region' && $use_regions) {
            // Hvis feltet ikke er sendt inn, men regioner er aktive, fjern region og manuell-flagget
            delete_term_meta($term_id, 'location_region');
            delete_term_meta($term_id, 'location_region_manual');
        }
    }
    
    // Håndter image_instructor_ka separat siden det er spesialtilfelle
    if (isset($_POST['image_instructor_ka'])) {
        $image_edited = get_term_meta($term_id, 'instructor_image_edited', true) === 'yes';
        if ($image_edited || $is_manual_instructor) {
            $value = esc_url_raw($_POST['image_instructor_ka']);
            update_term_meta($term_id, 'image_instructor_ka', $value);
        }
    }
    
    // Oppdater term name hvis det er endret
    if (isset($_POST['instructor_name'])) {
        $new_name = sanitize_text_field($_POST['instructor_name']);
        if ($new_name !== get_term($term_id)->name) {
            wp_update_term($term_id, 'ka_instructors', array('name' => $new_name));
        }
    }
}

// Register hooks
add_action('ka_coursecategory_edit_form_fields', 'custom_taxonomy_rich_text_editor');
add_action('ka_course_location_edit_form_fields', 'custom_taxonomy_rich_text_editor');
add_action('ka_instructors_edit_form_fields', 'custom_taxonomy_rich_text_editor');

add_action('ka_coursecategory_edit_form_fields', 'add_coursecategory_image_field');
add_action('ka_coursecategory_edit_form_fields', 'add_coursecategory_icon_field');
add_action('ka_course_location_edit_form_fields', 'add_course_location_image_field');
add_action('ka_instructors_edit_form_fields', 'add_instructor_image_field');
add_action('ka_instructors_edit_form_fields', 'add_instructor_profile_image_field');
add_action('ka_coursecategory_edit_form_fields', 'add_taxonomy_visibility_field');
add_action('ka_course_location_edit_form_fields', 'add_taxonomy_visibility_field');
add_action('ka_instructors_edit_form_fields', 'add_taxonomy_visibility_field');

add_action('edited_ka_coursecategory', 'save_taxonomy_field');
add_action('edited_ka_course_location', 'save_taxonomy_field');
add_action('edited_ka_instructors', 'save_taxonomy_field');

// Registrer hooks for alle aktuelle taksonomier
$taxonomies = ['ka_coursecategory', 'ka_instructors', 'ka_course_location'];

foreach ($taxonomies as $taxonomy) {
    // Legg til kolonne
    add_filter("manage_edit-{$taxonomy}_columns", 'add_taxonomy_visibility_column');
    
    // Håndter kolonneinnhold
    add_filter("manage_{$taxonomy}_custom_column", 'manage_taxonomy_visibility_column', 10, 3);
    
    // Lagre hurtigredigering
    add_action("edited_{$taxonomy}", 'save_quick_edit_visibility');
}

// Legg til quick edit
add_action('quick_edit_custom_box', 'add_quick_edit_visibility_field', 10, 2);

// JavaScript for å håndtere hurtigredigering
function add_quick_edit_javascript() {
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            var wp_inline_edit = inlineEditTax.edit;
            
            inlineEditTax.edit = function(id) {
                wp_inline_edit.apply(this, arguments);
                var tag_id = 0;
                if (typeof(id) == 'object') {
                    tag_id = parseInt(this.getId(id));
                }
                
                if (tag_id > 0) {
                    var $row = $('#tag-' + tag_id);
                    var $visibilityCell = $row.find('td.column-visibility');
                    var visibilityText = $visibilityCell.text();
                    var $table = $row.closest('table');
                    var isCourseCategory = $table.length > 0 && $table.attr('id') && $table.attr('id').indexOf('ka_coursecategory') !== -1;
                    
                    // Hent verdiene fra meta-feltene
                    var list_visibility = visibilityText.includes('Skjult i lister') ? 'Skjul' : 'Vis';
                    var menu_visibility = visibilityText.includes('Skjult i menyer') ? 'Skjul' : 'Vis';
                    var course_list_visibility = visibilityText.includes('Skjult i kursliste') ? 'Skjul' : 'Vis';
                    
                    // Sett radio-knappene
                    $('input[name="quick_edit_hide_in_list"][value="' + list_visibility + '"]').prop('checked', true);
                    $('input[name="quick_edit_hide_in_menu"][value="' + menu_visibility + '"]').prop('checked', true);
                    $('input[name="quick_edit_hide_in_course_list"][value="' + course_list_visibility + '"]').prop('checked', true);
                    
                    // Vis/skjul kursliste-feltet basert på taksonomi
                    if (isCourseCategory) {
                        $('.course-list-visibility').show();
                    } else {
                        $('.course-list-visibility').hide();
                    }
                }
            };
        });
    </script>
    <?php
}
add_action('admin_footer-edit-tags.php', 'add_quick_edit_javascript');

// Endre "Beskrivelse" til "Kort beskrivelse" for alle taksonomier
function kursagenten_change_description_label($translated_text, $text, $domain) {
    if ($domain === 'default' && $text === 'Description') {
        return 'Kort beskrivelse';
    }
    return $translated_text;
}
add_filter('gettext', 'kursagenten_change_description_label', 10, 3);

// Skjul originale felter og stil instruktørskjemaet
function kursagenten_add_instructor_styles() {
    ?>
    <style type="text/css">
        @media (min-width: 950px) {
        .taxonomy-ka_coursecategory .edit-tag-actions,
        .taxonomy-ka_instructors .edit-tag-actions,
        .taxonomy-ka_course_location .edit-tag-actions {
                position: fixed;
                bottom: 40px;
                left: 900px;
                background: white;
                padding: 1em;
                border-radius: 6px;
                box-shadow: rgba(0, 0, 0, 0.15) 0px 4px 12px;
                z-index: 10000;
            }
        }
        #delete-link {
            margin-left: 20px;
        }
        /* Skjul originale felter med en gang */
        .taxonomy-ka_instructors:has(.instructor-contact-card) .term-name-wrap,
        .taxonomy-ka_instructors:has(.instructor-contact-card) .term-slug-wrap {
            display: none !important;
        }
        
        /* Stil instruktørskjemaet */
        .instructor-contact-card {
            padding: 2em;
            background: #f8f9fa;
            border-radius: 4px;
            margin: 2em 0;
            min-height: 100px;
            display: flex;
            align-items: center;
            gap: 2em;
        }
        
        .instructor-image-container {
            flex: 0 0 200px; /* Fast bredde på bildecontainer */
            width: 200px;
            height: 200px;
            background: #f0f0f1;
            border-radius: 100%;
        }
        
        .instructor-image {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            object-fit: cover; /* Klipper bildet og beholder aspect ratio */
            object-position: center; /* Midtstiller bildet */
            border: 3px solid #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .instructor-content {
            flex: 1;
        }
        
        .instructor-name {
            font-size: 2em;
            font-weight: bold;
            margin-bottom: 1em;
            color: #23282d;
        }
        
        .instructor-details {
            line-height: 1.6;
        }
        
        .instructor-details p {
            margin: .3em 0;
        }
        .instructor-details strong {
            color: #666;
            display: inline-block;
            width: 80px;
        }
        
        .instructor-details a {
            display: inline-block;
            margin-top: 1em;
            color: #0073aa;
            text-decoration: none;
            padding: 5px 0;
        }
        
        .instructor-details a:hover {
            color: #00a0d2;
        }
        
        .content-section {
            margin-top: 2em;
        }
        
        .content-section h3 {
            margin-bottom: 1em;
        }
        .term-description-wrap th{
            vertical-align: top;
        }
        tr.form-field {

        }
        .form-field th {
            min-width: 150px;
        }
        .form-field th, .form-field td {
            padding-top: 2em;
        }
        .image-field .image-upload-container {
            border: 3px #e7e7e7 dashed;
            border-radius: 7px;
            padding: 1em 2em;
            background: white;
            
        }
        .form-table td p.description {
            font-size: .9em;
            font-style: italic;
        }
        
        /* Synlighetstagger */
        #visibility{
            width: 110px;
        }
        .visibility-tag {
            display: block;
            font-size: 12px;
        }
        
        .visibility-tag[data-visibility="Skjul"] {
            color: rgb(226, 91, 102);
        }
        
        .visibility-tag[data-visibility="Vis"] {
            color: #4CAF50;
        }

        /* Hurtigredigering synlighet */
        .course-list-visibility {
            display: none;
        }
        
        body.taxonomy-ka_coursecategory .course-list-visibility {
            display: block !important;
        }
    </style>
    <?php
}
add_action('admin_head', 'kursagenten_add_instructor_styles');

// Gjør Navn og Identifikator skrivebeskyttede
function kursagenten_make_fields_readonly($term) {
    if (!isset($term->term_id)) {
        return;
    }
    
    $email = get_term_meta($term->term_id, 'instructor_email', true);
    $phone = get_term_meta($term->term_id, 'instructor_phone', true);
    $firstname = get_term_meta($term->term_id, 'instructor_firstname', true);
    $lastname = get_term_meta($term->term_id, 'instructor_lastname', true);
    $id = get_term_meta($term->term_id, 'instructor_id', true);
    $image_ka = get_term_meta($term->term_id, 'image_instructor_ka', true);
    
    // Sjekk om dette er en manuelt opprettet instruktør
    $is_manual_instructor = empty($id);
    
    // Sjekk om feltene er manuelt redigert
    $email_edited = get_term_meta($term->term_id, 'instructor_email_edited', true) === 'yes';
    $phone_edited = get_term_meta($term->term_id, 'instructor_phone_edited', true) === 'yes';
    $firstname_edited = get_term_meta($term->term_id, 'instructor_firstname_edited', true) === 'yes';
    $lastname_edited = get_term_meta($term->term_id, 'instructor_lastname_edited', true) === 'yes';
    
    // Sjekk om noen felter er overskrevet
    $has_edited_fields = $email_edited || $phone_edited || $firstname_edited || $lastname_edited;
    
    // I kursagenten_make_fields_readonly funksjonen, etter den eksisterende toggle-knappen
    $image_edited = get_term_meta($term->term_id, 'instructor_image_edited', true) === 'yes';
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            var $form = $('#edittag');
            
            // Finn feltene vi vil beholde
            var $imageField = $form.find('tr:has(#image_instructor)');
            var $profileImageField = $form.find('tr:has(#image_instructor_ka)');
            var $descriptionField = $form.find('tr:has(#description)');
            var $richDescriptionField = $form.find('tr:has(#rich_description)');
            
            // Legg til klassen for å kontrollere visning
            $profileImageField.addClass('instructor-image-override-fields');
            
            // Fjern feltene fra sin nåværende posisjon
            $imageField.remove();
            $profileImageField.remove();
            $descriptionField.remove();
            $richDescriptionField.remove();
            
            // Opprett HTML for redigerbare felter
            var editableFieldsHtml = `
                <tr class="form-field instructor-override-fields" style="display:none;">
                    <th scope="row"><label for="instructor_name">Navn</label></th>
                    <td>
                        <input type="text" name="instructor_name" id="instructor_name" value="<?php echo esc_attr($term->name); ?>" class="regular-text" />
                        <p class="description">Endre navnet på instruktøren</p>
                    </td>
                </tr>
                <tr class="form-field instructor-override-fields" style="display:none;">
                    <th scope="row"><label for="instructor_phone">Telefon</label></th>
                    <td>
                        <input type="text" name="instructor_phone" id="instructor_phone" value="<?php echo esc_attr($phone); ?>" class="regular-text" />
                        <?php if (!$is_manual_instructor): ?>
                        <p class="description"><?php echo $phone_edited ? '<span style="color:#d63638;">Dette feltet er overskrevet fra Kursagenten</span>' : ''; ?></p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr class="form-field instructor-override-fields" style="display:none;">
                    <th scope="row"><label for="instructor_email">E-post</label></th>
                    <td>
                        <input type="email" name="instructor_email" id="instructor_email" value="<?php echo esc_attr($email); ?>" class="regular-text" />
                        <?php if (!$is_manual_instructor): ?>
                        <p class="description"><?php echo $email_edited ? '<span style="color:#d63638;">Dette feltet er overskrevet fra Kursagenten</span>' : ''; ?></p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr class="form-field instructor-override-fields" style="display:none;">
                    <th scope="row"><label for="instructor_firstname">Fornavn</label></th>
                    <td>
                        <input type="text" name="instructor_firstname" id="instructor_firstname" value="<?php echo esc_attr($firstname); ?>" class="regular-text" />
                        <?php if (!$is_manual_instructor): ?>
                        <p class="description"><?php echo $firstname_edited ? '<span style="color:#d63638;">Dette feltet er overskrevet fra Kursagenten</span>' : ''; ?></p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr class="form-field instructor-override-fields" style="display:none;">
                    <th scope="row"><label for="instructor_lastname">Etternavn</label></th>
                    <td>
                        <input type="text" name="instructor_lastname" id="instructor_lastname" value="<?php echo esc_attr($lastname); ?>" class="regular-text" />
                        <?php if (!$is_manual_instructor): ?>
                        <p class="description"><?php echo $lastname_edited ? '<span style="color:#d63638;">Dette feltet er overskrevet fra Kursagenten</span>' : ''; ?></p>
                        <?php endif; ?>
                    </td>
                </tr>
            `;
            
            // Opprett HTML for informasjonsboks
            var infoBoxHtml = `
                <tr class="form-field instructor-override-fields" style="display:none;">
                    <td colspan="2">
                        <div class="revert-to-ka-data">
                            <?php if ($is_manual_instructor): ?>
                            <p><strong>Bruk Kursagenten instruktørprofil i stedet:</strong></p>
                            <ol>
                                <li>Slett denne instruktøren</li>
                                <li>Lagre et kurs i Kursagenten hvor instruktøren er lagt inn</li>
                            </ol>
                            <?php else: ?>
                            <p><strong>Gå tilbake til Kursagenten-data:</strong></p>
                            <ol>
                                <li>Deaktiver "Overstyr profil fra Kursagenten" knappen</li>
                                <li>Oppdater instruktørprofilen i Kursagenten</li>
                                <li>Profilen oppdateres her. Merk: instruktøren må være knyttet til et kurs for at dataene skal bli hentet over.</li>
                            </ol>
                            <p>- Bruker du data fra Kursagenten, slipper du å holde dem oppdatert to steder.<br>
                            - En instruktør som er opprettet i Kursagenten med det samme navnet, vil automatisk bli slått sammen med denne instruktøren.</p>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            `;
            
            // Legg til feltene i ny rekkefølge
            $form.prepend(
                $('<tr><td colspan="2"><div class="instructor-contact-card">' +
                    '<div class="instructor-image-container">' +
                        '<img src="<?php echo esc_js(esc_url($image_ka)); ?>" ' +
                        'class="instructor-image" ' +
                        'style="display:<?php echo $image_ka ? "block" : "none"; ?>" />' +
                    '</div>' +
                    '<div class="instructor-content">' +
                        '<div class="instructor-name"><?php echo esc_js(esc_html($term->name)); ?></div>' +
                        '<div class="instructor-details">' +
                            '<p><strong>Slug:</strong> /<?php echo esc_js(esc_attr($term->slug)); ?></p>' +
                            '<p class="instructor-phone"><strong>Telefon:</strong> <span class="instructor-value"><?php echo esc_js(esc_html($phone)); ?></span></p>' +
                            '<p class="instructor-email"><strong>E-post:</strong> <span class="instructor-value"><?php echo esc_js(esc_html($email)); ?></span></p>' +
                            '<p class="instructor-firstname"><strong>Fornavn:</strong> <span class="instructor-value"><?php echo esc_js(esc_html($firstname)); ?></span></p>' +
                            '<p class="instructor-lastname"><strong>Etternavn:</strong> <span class="instructor-value"><?php echo esc_js(esc_html($lastname)); ?></span></p>' +
                            //'<p class="instructor-image-url"><strong>Bildeurl:</strong> <span class="instructor-value"><?php echo esc_js(esc_html($image_ka)); ?></span></p>' +
                            <?php if (!$is_manual_instructor): ?>
                            '<a href="https://kursadmin.kursagenten.no/Profile/<?php echo esc_js(esc_html($id)); ?>" target="_blank">' +
                                'Rediger i Kursadmin' +
                            '</a>' +
                            '<br><span class="instructor-ka-note" style="font-size:12px; color:#888; font-style:italic;"> Merk: instruktøren må være knyttet til et kurs for at dataene skal bli hentet over.</span>' +
                            <?php endif; ?>
                        '</div>' +
                    '</div>' +
                '</div></td></tr>'),

                // Profilbilde seksjon
                $('<tr><td colspan="2"><div class="content-section"><h3>Profilbilde</h3></div></td></tr>'),
                
                // Toggle-knapp for profilbilde
                $('<tr><td colspan="2"><div class="instructor-override-toggle">' +
                    '<label class="switch">' +
                        '<input type="checkbox" id="instructor_image_override_toggle" ' +
                        '<?php echo ($image_edited || $is_manual_instructor) ? "checked" : ""; ?> ' +
                        '<?php echo $is_manual_instructor ? "disabled" : ""; ?> />' +
                        '<span class="slider round"></span>' +
                    '</label>' +
                    '<span class="toggle-label"><?php echo $is_manual_instructor ? "Profilbilde" : "Overstyr profilbilde"; ?></span>' +
                '</div></td></tr>'),
                
                // Profilbilde felt
                $profileImageField,
                
                // Innhold seksjon
                $('<tr><td colspan="2"><div class="content-section"><h3>Innhold</h3></div></td></tr>'),
                
                // Toggle-knapp for andre felter
                $('<tr><td colspan="2"><div class="instructor-override-toggle">' +
                    '<label class="switch">' +
                        '<input type="checkbox" id="instructor_override_toggle" ' +
                        '<?php echo ($has_edited_fields || $is_manual_instructor) ? "checked" : ""; ?> ' +
                        '<?php echo $is_manual_instructor ? "disabled" : ""; ?> />' +
                        '<span class="slider round"></span>' +
                    '</label>' +
                    '<span class="toggle-label"><?php echo $is_manual_instructor ? "Lagt inn direkte via WordPress" : "Overstyr profil fra Kursagenten"; ?></span>' +
                '</div></td></tr>'),
                
                // Redigerbare felter
                $(editableFieldsHtml),
                
                // Informasjonsboks
                $(infoBoxHtml),
                
                // Alternativt bilde
                $imageField,
                
                // Beskrivelser
                $descriptionField,
                $richDescriptionField
            );
            
            // Legg til skjulte felt for å lagre toggle-status
            $form.append(
                '<input type="hidden" name="instructor_image_override_toggle" id="instructor_image_override_toggle_hidden" value="<?php echo ($image_edited || $is_manual_instructor) ? "on" : ""; ?>" />',
                '<input type="hidden" name="instructor_override_toggle" id="instructor_override_toggle_hidden" value="<?php echo ($has_edited_fields || $is_manual_instructor) ? "on" : ""; ?>" />'
            );
            
            // Håndter toggle for profilbilde
            $('#instructor_image_override_toggle').on('change', function() {
                var isChecked = this.checked;
                $('.instructor-image-override-fields').toggle(isChecked);
                $('#instructor_image_override_toggle_hidden').val(isChecked ? 'on' : '');
                
                if (!isChecked && !<?php echo $is_manual_instructor ? 'true' : 'false'; ?>) {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'remove_instructor_image_edited',
                            term_id: <?php echo $term->term_id; ?>,
                            nonce: '<?php echo wp_create_nonce("remove_instructor_image_edited"); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                $('.instructor-image-override-fields .description span').remove();
                                $.ajax({
                                    url: ajaxurl,
                                    type: 'POST',
                                    data: {
                                        action: 'get_instructor_image_ajax',
                                        term_id: <?php echo $term->term_id; ?>,
                                        nonce: '<?php echo wp_create_nonce("get_instructor_image_ajax"); ?>'
                                    },
                                    success: function(data) {
                                        if (data.success) {
                                            var $image = $('.instructor-image');
                                            if (data.data.image_url) {
                                                $image.attr('src', data.data.image_url).show();
                                            } else {
                                                $image.hide();
                                            }
                                            $('.instructor-details p:nth-child(6) strong').next().text(data.data.image_url || '');
                                        }
                                    }
                                });
                            }
                        }
                    });
                }
            });
            
            // Håndter toggle for andre felter
            $('#instructor_override_toggle').on('change', function() {
                var isChecked = this.checked;
                $('.instructor-override-fields').toggle(isChecked);
                $('#instructor_override_toggle_hidden').val(isChecked ? 'on' : '');
                
                if (!isChecked && !<?php echo $is_manual_instructor ? 'true' : 'false'; ?>) {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'remove_instructor_profile_edited',
                            term_id: <?php echo $term->term_id; ?>,
                            nonce: '<?php echo wp_create_nonce("remove_instructor_profile_edited"); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                $.ajax({
                                    url: ajaxurl,
                                    type: 'POST',
                                    data: {
                                        action: 'get_instructor_data',
                                        term_id: <?php echo $term->term_id; ?>,
                                        nonce: '<?php echo wp_create_nonce("get_instructor_data"); ?>'
                                    },
                                    success: function(data) {
                                        if (data.success) {
                                            $('.instructor-phone .instructor-value').text(data.data.phone || '');
                                            $('.instructor-email .instructor-value').text(data.data.email || '');
                                            $('.instructor-firstname .instructor-value').text(data.data.firstname || '');
                                            $('.instructor-lastname .instructor-value').text(data.data.lastname || '');
                                            
                                            if ($('.instructor-override-fields').is(':visible')) {
                                                $('#instructor_phone').val(data.data.phone || '');
                                                $('#instructor_email').val(data.data.email || '');
                                                $('#instructor_firstname').val(data.data.firstname || '');
                                                $('#instructor_lastname').val(data.data.lastname || '');
                                            }
                                        }
                                    }
                                });
                            }
                        }
                    });
                }
            });
            
            // Oppdater skjulte felt når siden lastes
            $('#instructor_image_override_toggle_hidden').val($('#instructor_image_override_toggle').prop('checked') ? 'on' : '');
            $('#instructor_override_toggle_hidden').val($('#instructor_override_toggle').prop('checked') ? 'on' : '');
            
            // Vis feltene basert på deres respektive tilstander
            if (<?php echo ($image_edited || $is_manual_instructor) ? 'true' : 'false'; ?>) {
                $('.instructor-image-override-fields').show();
            } else {
                $('.instructor-image-override-fields').hide();
            }
            
            if (<?php echo ($has_edited_fields || $is_manual_instructor) ? 'true' : 'false'; ?>) {
                $('.instructor-override-fields').show();
            } else {
                $('.instructor-override-fields').hide();
            }
        });
    </script>
    <style>
        /* Stil for toggle-knappen */
        .instructor-override-toggle {
            margin: 1em 0;
            padding: 1em;
            background: #f8f9fa;
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 1em;
        }
        
        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }
        
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
        }
        
        input:checked + .slider {
            background-color: #2271b1;
        }
        
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        
        .slider.round {
            border-radius: 34px;
        }
        
        .slider.round:before {
            border-radius: 50%;
        }
        
        .toggle-label {
            font-weight: 600;
            color: #1d2327;
        }
        
        /* Stil for redigerbare felter */
        .instructor-override-fields td {
            padding: 1em 0;
        }
        
        .instructor-override-fields input[type="text"],
        .instructor-override-fields input[type="email"] {
            width: 100%;
            max-width: 400px;
        }
        
        .instructor-override-fields .description {
            margin-top: 0.5em;
            font-style: italic;
        }
        
        /* Stil for notice */
        .revert-to-ka-data {
            margin: 1em 0;
            padding: 1em;
            background: rgb(247, 247, 247);
            border-radius: 7px;
        }
        
        .revert-to-ka-data ol {
            margin: 0.5em 0 0.5em 1.5em;
        }
        
        .revert-to-ka-data li {
            margin-bottom: 2px;
        }
        
        .revert-to-ka-data strong {
            color: #1d2327;
        }
        
        /* Forbedret stil for inaktiv toggle */
        .switch input:disabled + .slider {
            background-color: #c3c8cb;
            cursor: not-allowed;
        }
        
        .switch input:disabled:checked + .slider {
            background-color: #c3c8cb;
        }
        
        .switch input:disabled + .slider:before {
            cursor: not-allowed;
            background-color: #f0f0f0;
        }

    </style>
    <?php
}
add_action('instructors_pre_edit_form', 'kursagenten_make_fields_readonly', 10);

// Legg til denne nye funksjonen
function sync_instructor_data_from_ka($term_id, $sync_image = true) {
    // Hent instructor_id fra term meta
    $instructor_id = get_term_meta($term_id, 'instructor_id', true);
    if (empty($instructor_id)) {
        return false;
    }
    
    // Sjekk edited-flagg
    $image_edited = get_term_meta($term_id, 'instructor_image_edited', true) === 'yes';
    $email_edited = get_term_meta($term_id, 'instructor_email_edited', true) === 'yes';
    $phone_edited = get_term_meta($term_id, 'instructor_phone_edited', true) === 'yes';
    $firstname_edited = get_term_meta($term_id, 'instructor_firstname_edited', true) === 'yes';
    $lastname_edited = get_term_meta($term_id, 'instructor_lastname_edited', true) === 'yes';
    
    // Hent kursliste for å finne instruktøren
    $courses = kursagenten_get_course_list();
    if (empty($courses)) {
        return false;
    }
    
    // Finn instruktøren i kurslisten
    $instructor_data = null;
    foreach ($courses as $course) {
        foreach ($course['locations'] as $location) {
            foreach ($location['schedules'] as $schedule) {
                if (!empty($schedule['instructors'])) {
                    foreach ($schedule['instructors'] as $instructor) {
                        if ($instructor['userId'] == $instructor_id) {
                            $instructor_data = $instructor;
                            break 3; // Bryt ut av alle løkker
                        }
                    }
                }
            }
        }
    }
    
    if (empty($instructor_data)) {
        return false;
    }
    
    // Oppdater metadata med data fra Kursagenten, men respekter edited-flagg
    if (!$email_edited && isset($instructor_data['email'])) {
        update_term_meta($term_id, 'instructor_email', sanitize_email($instructor_data['email']));
    }
    if (!$phone_edited && isset($instructor_data['phone'])) {
        update_term_meta($term_id, 'instructor_phone', sanitize_text_field($instructor_data['phone']));
    }
    if (!$firstname_edited && isset($instructor_data['firstname'])) {
        update_term_meta($term_id, 'instructor_firstname', sanitize_text_field($instructor_data['firstname']));
    }
    if (!$lastname_edited && isset($instructor_data['lastname'])) {
        update_term_meta($term_id, 'instructor_lastname', sanitize_text_field($instructor_data['lastname']));
    }
    if ($sync_image && !$image_edited && isset($instructor_data['image'])) {
        $image_url = ltrim($instructor_data['image'], '/');
        update_term_meta($term_id, 'image_instructor_ka', esc_url_raw($image_url));
    }
    
    return true;
}

// Registrer AJAX-handlere
add_action('wp_ajax_remove_instructor_edited_fields', 'remove_instructor_edited_fields');
add_action('wp_ajax_remove_instructor_image_edited', 'remove_instructor_image_edited');

// Oppdater remove_instructor_edited_fields funksjonen
function remove_instructor_edited_fields() {
    // Verifiser nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'remove_instructor_edited_fields')) {
        wp_send_json_error('Sikkerhetsverifisering feilet');
        return;
    }
    
    if (!current_user_can('manage_categories')) {
        wp_send_json_error('Ingen tilgang');
        return;
    }
    
    if (!isset($_POST['term_id'])) {
        wp_send_json_error('Manglende term_id');
        return;
    }
    
    $term_id = intval($_POST['term_id']);
    
    // Fjern alle edited-felter
    delete_term_meta($term_id, 'instructor_email_edited');
    delete_term_meta($term_id, 'instructor_phone_edited');
    delete_term_meta($term_id, 'instructor_firstname_edited');
    delete_term_meta($term_id, 'instructor_lastname_edited');
    
    // Synkroniser data fra Kursagenten
    sync_instructor_data_from_ka($term_id);
    
    wp_send_json_success();
}

// Oppdater remove_instructor_image_edited for å kun synkronisere bilde
function remove_instructor_image_edited() {
    // Verifiser nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'remove_instructor_image_edited')) {
        wp_send_json_error('Sikkerhetsverifisering feilet');
        return;
    }
    
    if (!current_user_can('manage_categories')) {
        wp_send_json_error('Ingen tilgang');
        return;
    }
    
    if (!isset($_POST['term_id'])) {
        wp_send_json_error('Manglende term_id');
        return;
    }
    
    $term_id = intval($_POST['term_id']);
    
    delete_term_meta($term_id, 'instructor_image_edited');
    
    // Synkroniser kun bilde fra Kursagenten
    sync_instructor_data_from_ka($term_id, true);
    
    wp_send_json_success();
}

// Legg til AJAX-håndtering for å hente instruktørdata
function get_instructor_data() {
    check_ajax_referer('get_instructor_data', 'nonce');
    
    if (!current_user_can('manage_categories')) {
        wp_send_json_error('Ingen tilgang');
        return;
    }
    
    $term_id = intval($_POST['term_id']);
    
    $data = array(
        'phone' => get_term_meta($term_id, 'instructor_phone', true),
        'email' => get_term_meta($term_id, 'instructor_email', true),
        'firstname' => get_term_meta($term_id, 'instructor_firstname', true),
        'lastname' => get_term_meta($term_id, 'instructor_lastname', true)
    );
    
    wp_send_json_success($data);
}
add_action('wp_ajax_get_instructor_data', 'get_instructor_data');

// Legg til AJAX-håndtering for å hente instruktørbilde
function get_instructor_image_ajax() {
    check_ajax_referer('get_instructor_image_ajax', 'nonce');
    
    if (!current_user_can('manage_categories')) {
        wp_send_json_error('Ingen tilgang');
        return;
    }
    
    $term_id = intval($_POST['term_id']);
    $image_url = get_term_meta($term_id, 'image_instructor_ka', true);
    
    wp_send_json_success(array('image_url' => $image_url));
}
add_action('wp_ajax_get_instructor_image_ajax', 'get_instructor_image_ajax');

// Legg til AJAX-håndtering for bildeopplasting
function handle_instructor_image_upload() {
    // Verifiser nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'upload_instructor_image')) {
        wp_send_json_error('Sikkerhetsverifisering feilet');
        return;
    }
    
    if (!current_user_can('manage_categories')) {
        wp_send_json_error('Ingen tilgang');
        return;
    }
    
    if (!isset($_POST['term_id'])) {
        wp_send_json_error('Manglende term_id');
        return;
    }
    
    $term_id = intval($_POST['term_id']);
    
    // Sjekk om det er lastet opp et bilde
    if (!isset($_FILES['instructor_image']) || $_FILES['instructor_image']['error'] !== UPLOAD_ERR_OK) {
        wp_send_json_error('Ingen bildefil lastet opp eller feil ved opplasting');
        return;
    }
    
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    
    // Last opp bildet til WordPress media library
    $attachment_id = media_handle_upload('instructor_image', 0);
    
    if (is_wp_error($attachment_id)) {
        wp_send_json_error('Feil ved opplasting av bilde: ' . $attachment_id->get_error_message());
        return;
    }
    
    // Hent bilde-URL
    $image_url = wp_get_attachment_url($attachment_id);
    
    if (!$image_url) {
        wp_send_json_error('Kunne ikke hente bilde-URL etter opplasting');
        return;
    }
    
    // Oppdater term meta
    update_term_meta($term_id, 'image_instructor_ka', $image_url);
    update_term_meta($term_id, 'instructor_image_edited', 'yes');
    
    wp_send_json_success(array('image_url' => $image_url));
}
add_action('wp_ajax_handle_instructor_image_upload', 'handle_instructor_image_upload');

// Legg til AJAX-håndtering for fjerning av bilde
function remove_instructor_image() {
    // Verifiser nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'remove_instructor_image')) {
        wp_send_json_error('Sikkerhetsverifisering feilet');
        return;
    }
    
    if (!current_user_can('manage_categories')) {
        wp_send_json_error('Ingen tilgang');
        return;
    }
    
    if (!isset($_POST['term_id'])) {
        wp_send_json_error('Manglende term_id');
        return;
    }
    
    $term_id = intval($_POST['term_id']);
    
    // Fjern bilde fra term meta
    delete_term_meta($term_id, 'image_instructor_ka');
    delete_term_meta($term_id, 'instructor_image_edited');
    
    wp_send_json_success();
}
add_action('wp_ajax_remove_instructor_image', 'remove_instructor_image');

// Legg til hurtigredigering lagring
function save_quick_edit_visibility($term_id) {
    if (!current_user_can('manage_categories')) {
        return;
    }
    
    // Sjekk om hurtigredigering er aktiv
    if (!isset($_POST['action']) || $_POST['action'] !== 'inline-save-tax') {
        return;
    }
    
    // Hent og valider verdier
    $hide_in_list = isset($_POST['quick_edit_hide_in_list']) ? sanitize_text_field($_POST['quick_edit_hide_in_list']) : 'Vis';
    $hide_in_menu = isset($_POST['quick_edit_hide_in_menu']) ? sanitize_text_field($_POST['quick_edit_hide_in_menu']) : 'Vis';
    $hide_in_course_list = isset($_POST['quick_edit_hide_in_course_list']) ? sanitize_text_field($_POST['quick_edit_hide_in_course_list']) : 'Vis';
    
    // Oppdater term meta
    update_term_meta($term_id, 'hide_in_list', $hide_in_list);
    update_term_meta($term_id, 'hide_in_menu', $hide_in_menu);
    
    // Sjekk om dette er en kurskategori før vi oppdaterer kursliste-visning
    $term = get_term($term_id);
    if ($term && $term->taxonomy === 'ka_coursecategory') {
        update_term_meta($term_id, 'hide_in_course_list', $hide_in_course_list);
    }
}

// Legg til ny AJAX-handler for å fjerne kun profil-edited-felter
function remove_instructor_profile_edited() {
    // Verifiser nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'remove_instructor_profile_edited')) {
        wp_send_json_error('Sikkerhetsverifisering feilet');
        return;
    }
    
    if (!current_user_can('manage_categories')) {
        wp_send_json_error('Ingen tilgang');
        return;
    }
    
    if (!isset($_POST['term_id'])) {
        wp_send_json_error('Manglende term_id');
        return;
    }
    
    $term_id = intval($_POST['term_id']);
    
    // Fjern KUN profil-edited-felter
    delete_term_meta($term_id, 'instructor_email_edited');
    delete_term_meta($term_id, 'instructor_phone_edited');
    delete_term_meta($term_id, 'instructor_firstname_edited');
    delete_term_meta($term_id, 'instructor_lastname_edited');
    
    // Synkroniser data fra Kursagenten, men ikke bilde
    sync_instructor_data_from_ka($term_id, false);
    
    wp_send_json_success();
}
add_action('wp_ajax_remove_instructor_profile_edited', 'remove_instructor_profile_edited');



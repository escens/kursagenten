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


// Callback functions for each taxonomy, passing the correct parameters
function add_coursecategory_image_field($term) {
    add_taxonomy_image_field($term, 'coursecategory', 'image_coursecategory', 'Hovedbilde', 'bilde', 'Hovedbilde som brukes på kategorisiden og i kategorioversikter');
}
add_action('coursecategory_edit_form_fields', 'add_coursecategory_image_field');

function add_coursecategory_icon_field($term) {
    add_taxonomy_image_field($term, 'coursecategory', 'icon_coursecategory', 'Ikon', 'bilde av ikon', 'Bruk en .png bildefil. Du kan laste ned ikoner på feks. https://thenounproject.com/');
}
add_action('coursecategory_edit_form_fields', 'add_coursecategory_icon_field');

function add_course_location_image_field($term) {
    add_taxonomy_image_field($term, 'course_location', 'image_course_location', 'Bilde av kurssted', 'bilde');
}
add_action('course_location_edit_form_fields', 'add_course_location_image_field');

// Legg til instruktør-felt
function add_instructor_image_field($term) {
    add_taxonomy_image_field($term, 'instructors', 'image_instructor', 'Instruktørbilde', 'bilde', 'Bruk et annet bilde enn det som er lagt inn i Kursagenten. Dette bilde blir synlig på instruktørprofilen her på nettsiden.');
}
add_action('instructors_edit_form_fields', 'add_instructor_image_field');


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
    <?php if ($taxonomy === 'coursecategory'): ?>
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
    return $content;
}

// Taxonomy save function med forbedret sikkerhet
// -----------------------------
function save_taxonomy_field($term_id) {
    // Verify nonce would be good to add here
    if (!current_user_can('manage_categories')) {
        return;
    }
    
    $fields = [
        'image_coursecategory' => 'esc_url',
        'icon_coursecategory' => 'esc_url',
        'image_course_location' => 'esc_url',
        'image_instructor' => 'esc_url',
        'rich_description' => 'wp_kses_post',
        'instructor_email' => 'sanitize_email',
        'instructor_phone' => 'sanitize_text_field',
        'hide_in_list' => 'sanitize_text_field',
        'hide_in_menu' => 'sanitize_text_field',
        'hide_in_course_list' => 'sanitize_text_field'
    ];
    
    foreach ($fields as $field => $sanitize_callback) {
        if (isset($_POST[$field])) {
            $value = $_POST[$field];
            if (is_callable($sanitize_callback)) {
                $value = call_user_func($sanitize_callback, $value);
            }
            update_term_meta($term_id, $field, $value);
        }
    }
}

// Register hooks
add_action('coursecategory_edit_form_fields', 'custom_taxonomy_rich_text_editor');
add_action('course_location_edit_form_fields', 'custom_taxonomy_rich_text_editor');
add_action('instructors_edit_form_fields', 'custom_taxonomy_rich_text_editor');

add_action('coursecategory_edit_form_fields', 'add_coursecategory_image_field');
add_action('coursecategory_edit_form_fields', 'add_coursecategory_icon_field');
add_action('course_location_edit_form_fields', 'add_course_location_image_field');
add_action('instructors_edit_form_fields', 'add_instructor_image_field');
add_action('coursecategory_edit_form_fields', 'add_taxonomy_visibility_field');
add_action('course_location_edit_form_fields', 'add_taxonomy_visibility_field');
add_action('instructors_edit_form_fields', 'add_taxonomy_visibility_field');

add_action('edited_coursecategory', 'save_taxonomy_field');
add_action('edited_course_location', 'save_taxonomy_field');
add_action('edited_instructors', 'save_taxonomy_field');

// Registrer hooks for alle aktuelle taksonomier
$taxonomies = ['coursecategory', 'instructors', 'course_location'];

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
                    var isCourseCategory = $table.length > 0 && $table.attr('id') && $table.attr('id').indexOf('coursecategory') !== -1;
                    
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
        /* Skjul originale felter med en gang */
        .taxonomy-instructors:has(.instructor-contact-card) .term-name-wrap,
        .taxonomy-instructors:has(.instructor-contact-card) .term-slug-wrap {
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
        
        body.taxonomy-coursecategory .course-list-visibility {
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
    $id = get_term_meta($term->term_id, 'instructor_id', true);
    $image_ka = get_term_meta($term->term_id, 'image_instructor_ka', true); // image_instructor_ka is the image from Kursagenten. Override is the image_instructor field.
    error_log("DEBUG: Instructor ID: " . $id);
    error_log("DEBUG: Instructor image_ka: " . $image_ka);
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            var $form = $('#edittag');
            
            // Finn feltene vi vil beholde
            var $imageField = $form.find('tr:has(#image_instructor)');
            var $descriptionField = $form.find('tr:has(#description)');
            var $richDescriptionField = $form.find('tr:has(#rich_description)');
            
            // Fjern feltene fra sin nåværende posisjon
            $imageField.remove();
            $descriptionField.remove();
            $richDescriptionField.remove();
            
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
                            '<p><strong>Telefon:</strong> <?php echo esc_js(esc_html($phone)); ?></p>' +
                            '<p><strong>E-post:</strong> <?php echo esc_js(esc_html($email)); ?></p>' +
                            '<a href="https://kursadmin.kursagenten.no/Profile/<?php echo esc_js(esc_html($id)); ?>" target="_blank">' +
                                'Rediger i Kursadmin' +
                            '</a>' +
                        '</div>' +
                    '</div>' +
                '</div></td></tr>'),
                
                // Innhold seksjon
                $('<tr><td colspan="2"><div class="content-section"><h3>Legg til innhold</h3></div></td></tr>'),
                $imageField,
                $descriptionField,
                $richDescriptionField
            );
        });
    </script>
    <?php
}
add_action('instructors_pre_edit_form', 'kursagenten_make_fields_readonly', 10);

// Legg til AJAX-håndtering for lagring av synlighet
function save_taxonomy_visibility_ajax() {
    check_ajax_referer('save_taxonomy_visibility', 'nonce');
    
    if (!current_user_can('manage_categories')) {
        wp_send_json_error('Ingen tilgang');
        return;
    }
    
    $tag_id = intval($_POST['tag_id']);
    $hide_in_list = sanitize_text_field($_POST['hide_in_list']);
    $hide_in_menu = sanitize_text_field($_POST['hide_in_menu']);
    $hide_in_course_list = sanitize_text_field($_POST['hide_in_course_list']);
    
    update_term_meta($tag_id, 'hide_in_list', $hide_in_list);
    update_term_meta($tag_id, 'hide_in_menu', $hide_in_menu);
    update_term_meta($tag_id, 'hide_in_course_list', $hide_in_course_list);
    
    wp_send_json_success();
}
add_action('wp_ajax_save_taxonomy_visibility', 'save_taxonomy_visibility_ajax');

// Lagre hurtigredigering
function save_quick_edit_visibility($term_id) {
    if (isset($_POST['quick_edit_hide_in_list'])) {
        update_term_meta($term_id, 'hide_in_list', sanitize_text_field($_POST['quick_edit_hide_in_list']));
    }
    if (isset($_POST['quick_edit_hide_in_menu'])) {
        update_term_meta($term_id, 'hide_in_menu', sanitize_text_field($_POST['quick_edit_hide_in_menu']));
    }
    if (isset($_POST['quick_edit_hide_in_course_list'])) {
        update_term_meta($term_id, 'hide_in_course_list', sanitize_text_field($_POST['quick_edit_hide_in_course_list']));
    }
}



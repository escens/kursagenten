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
    add_taxonomy_image_field($term, 'instructors', 'image_instructor', 'Instruktørbilde', 'bilde', 'Bilde av instruktøren som vises på instruktørsiden');
}
add_action('instructors_edit_form_fields', 'add_instructor_image_field');

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
        'instructor_phone' => 'sanitize_text_field'
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

add_action('edited_coursecategory', 'save_taxonomy_field');
add_action('edited_course_location', 'save_taxonomy_field');
add_action('edited_instructors', 'save_taxonomy_field');


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
        }
        
        .instructor-name {
            font-size: 2em;
            font-weight: bold;
            margin-bottom: 1em;
        }
        
        .instructor-details {
            line-height: 1.6;
        }
        
        .instructor-details strong {
            color: #666;
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
                // Kontaktkort seksjon
                $('<tr><td colspan="2"><div class="instructor-contact-card"><div class="instructor-name"><?php echo esc_js($term->name); ?></div><div class="instructor-details"><strong>Slug:</strong> /<?php echo esc_js($term->slug); ?><br><strong>Telefon:</strong> <?php echo esc_js($phone); ?><br><strong>E-post:</strong> <?php echo esc_js($email); ?></div></div></td></tr>'),
                
                // Innhold seksjon
                $('<tr><td colspan="2"><div class="content-section"><h3>Endre innhold</h3></div></td></tr>'),
                $imageField,
                $descriptionField,
                $richDescriptionField
            );
        });
    </script>
    <?php
}
add_action('instructors_pre_edit_form', 'kursagenten_make_fields_readonly', 10);



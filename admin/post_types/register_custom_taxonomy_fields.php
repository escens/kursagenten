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
    <tr class="form-field">
        <th scope="row" valign="top"><label for="<?php echo esc_attr($field_name); ?>"><?php echo esc_html($label_text); ?></label></th>
        <td>
            <input type="button" class="button button-secondary upload_image_button_<?php echo esc_attr($field_name); ?>" value="Last opp <?php echo esc_attr($button_type_label); ?>" />
            <input type="button" class="button button-secondary remove_image_button_<?php echo esc_attr($field_name); ?>" value="Fjern <?php echo esc_attr($button_type_label); ?>" style="<?php echo $image_url ? 'display:inline-block;' : 'display:none;'; ?>" />
            <input type="hidden" id="<?php echo esc_attr($field_name); ?>" name="<?php echo esc_attr($field_name); ?>" value="<?php echo esc_attr($image_url); ?>" />
            <img id="<?php echo esc_attr($field_name); ?>_preview" src="<?php echo esc_url($image_url); ?>" style="max-height:250px; margin-top:10px; display:<?php echo $image_url ? 'block' : 'none'; ?>" />
            <?php if ($description): ?><p class="description"><?php echo esc_html($description); ?></p><?php endif; ?>
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
        'rich_description' => 'wp_kses_post'
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
add_action('coursecategory_edit_form_fields', 'add_coursecategory_image_field');
add_action('coursecategory_edit_form_fields', 'add_coursecategory_icon_field');
add_action('course_location_edit_form_fields', 'add_course_location_image_field');
add_action('edited_coursecategory', 'save_taxonomy_field');
add_action('edited_course_location', 'save_taxonomy_field');



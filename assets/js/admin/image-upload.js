jQuery(document).ready(function($) {
    // Function to handle image upload for each field
    function handleImageUpload(field) {
        var file_frame;
        var uploadButton = $('.upload_image_button_' + field);
        var removeButton = $('.remove_image_button_' + field);
        var previewImage = $('#' + field + '_preview');
        var hiddenField = $('#' + field);

        uploadButton.on('click', function(e) {
            e.preventDefault();

            // If the media frame already exists, reopen it.
            if (file_frame) {
                file_frame.open();
                return;
            }

            // Create the media frame.
            file_frame = wp.media.frames.file_frame = wp.media({
                title: 'Velg eller last opp bilde',
                button: {
                    text: 'Bruk dette bildet',
                },
                multiple: false // Set to true to allow multiple files to be selected
            });

            // When an image is selected, run a callback.
            file_frame.on('select', function() {
                var attachment = file_frame.state().get('selection').first().toJSON();
                hiddenField.val(attachment.url);
                previewImage.attr('src', attachment.url).show();
                removeButton.show();
            });

            // Finally, open the modal
            file_frame.open();
        });

        removeButton.on('click', function(e) {
            e.preventDefault();
            hiddenField.val(null);
            previewImage.hide();
            $(this).hide();
        });
    }

    // Initialize handlers for each field
    handleImageUpload('ka_plassholderbilde_generelt');
    handleImageUpload('ka_plassholderbilde_kurs');
    handleImageUpload('ka_plassholderbilde_instruktor');
    handleImageUpload('ka_plassholderbilde_sted');
    handleImageUpload('image_coursecategory');
    handleImageUpload('icon_coursecategory');
    handleImageUpload('image_course_location');
    handleImageUpload('image_instructor');
});

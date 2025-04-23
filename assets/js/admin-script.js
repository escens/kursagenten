(function($) {
    'use strict';

    $(document).ready(function() {
        // Initialiser color picker
        $('.ka-color-picker').wpColorPicker();

        // Initialiser tilstanden for alle override-innstillinger ved lasting
        $("input[name^='kursagenten_taxonomy_'][name$='_override']").each(function() {
            var $settings = $(this).closest(".taxonomy-override").find(".taxonomy-override-settings");
            if ($(this).is(":checked")) {
                $settings.show();
            } else {
                $settings.hide();
            }
        });

        // Håndter endringer i override-checkboksene
        $("input[name^='kursagenten_taxonomy_'][name$='_override']").on("change", function() {
            var $settings = $(this).closest(".taxonomy-override").find(".taxonomy-override-settings");
            if ($(this).is(":checked")) {
                $settings.slideDown(300);
            } else {
                $settings.slideUp(300);
            }
        });
    });
})(jQuery); 
jQuery(document).ready(function($) {
    // Kopier tekst funksjonalitet
    $('.copytext').on('click', function() {
        // Hent teksten inni span
        var textToCopy = $(this).text();
        
        // Opprett midlertidig textarea for kopiering
        var tempTextarea = $('<textarea>');
        $('body').append(tempTextarea);
        tempTextarea.val(textToCopy).select();
        document.execCommand('copy');
        tempTextarea.remove();

        // Opprett og vis tooltip
        var tooltip = $('<span class="tooltip-copytext">Kopiert</span>');
        $('body').append(tooltip);

        // Hent posisjon til klikket element
        var offset = $(this).offset();
        
        // Posisjoner tooltip
        tooltip.css({
            'position': 'absolute',
            'top': offset.top - tooltip.outerHeight() - 10,
            'left': offset.left + ($(this).width() / 2),
            'transform': 'translateX(-50%)',
            'z-index': 1000,
            'background-color': '#333',
            'color': '#fff',
            'padding': '5px 10px',
            'border-radius': '5px',
            'font-size': '12px',
            'text-align': 'center'
        });

        // Fjern tooltip etter 1.5 sekunder
        setTimeout(function() {
            tooltip.fadeOut(500, function() {
                $(this).remove();
            });
        }, 1500);
    });

    // Håndter versjonslogg modal
    $('.kursagenten-changelog-link').on('click', function(e) {
        e.preventDefault();
        $('#kursagenten-changelog-modal').show();
    });

    // Lukk modal når man klikker på X
    $('.kursagenten-modal-close').on('click', function() {
        $('#kursagenten-changelog-modal').hide();
    });

    // Lukk modal når man klikker utenfor
    $(window).on('click', function(e) {
        if ($(e.target).is('#kursagenten-changelog-modal')) {
            $('#kursagenten-changelog-modal').hide();
        }
    });

    // Lukk modal med Escape-tasten
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $('#kursagenten-changelog-modal').is(':visible')) {
            $('#kursagenten-changelog-modal').hide();
        }
    });

    // Håndter lukking av versjonsvarsel
    $('.kursagenten-version-notice').on('click', '.notice-dismiss', function() {
        // Lagre i localStorage at varselet er lukket
        localStorage.setItem('kursagenten_version_notice_dismissed', 'true');
    });

    // Sjekk om varselet skal vises
    if (localStorage.getItem('kursagenten_version_notice_dismissed') === 'true') {
        $('.kursagenten-version-notice').hide();
    }
}); 
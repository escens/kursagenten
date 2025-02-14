jQuery(document).ready(function($) {
    const defaultIframeURL = '{$defaultIframeURL}';
    console.log(defaultIframeURL);

    // Handle clicks for links with the class 'pamelding'
    $('.pamelding').on('click', function(event) {
        event.preventDefault(); // Prevent default link behavior
        const iframeURL = $(this).data('url') || defaultIframeURL;
        console.log('Iframe URL from link:', iframeURL); // Debugging output
        $('#kursagenten-iframe').attr('src', iframeURL);
    });

    // Handle click on the primary button and reset iframe to default URL
    $('#pameldingsknapp').on('click', function() {
        console.log('Iframe URL reset to default:', defaultIframeURL); // Debugging output
        $('#kursagenten-iframe').attr('src', defaultIframeURL);
    });
    // Handle click on the history back button above kursliste2 on enkeltkurs
    $('#tilbaketilkurs').on('click', function() {
        console.log('Klikket Tilbakeknapp');
        parent.history.back();
        return false;
    });
});
";

// New script for kurskalender page link override
$override_script = "
    jQuery(document).ready(function($) {
        const currentSlug = scriptData.page_slug;
        console.log(currentSlug);

        // Check if we are on the page with slug 'kurskalender'
        if (currentSlug === 'kurskalender' || currentSlug === 'vare-kurs' || currentSlug === 'kurs' || currentSlug === 'timeplan') {
            console.log('On kurskalender page');

            // Function to override links if there is exactly one '.wp-block-kadence-query' element in the div
            function overrideLinksIfOneQueryBlock() {
                const classList = $('.kadence-query-init').attr('class'); // Get the class attribute as a string
                if (classList) {
                    const classArray = classList.split(/\s+/); // Split by whitespace
                    const classCount = classArray.filter(function(className) {
                        return className === 'wp-block-kadence-query';
                    }).length;

                    if (classCount < 2) {
                        console.log('There are less than two .wp-block-kadence-query classes in this div. Hide modalknapp');
                        $('.kurslisten').addClass('skjultmodalknapp');
                        $('.kurslisten').removeClass('skjultvideresending');
                    
                    } else {
                        console.log('There are two or more .wp-block-kadence-query classes in this div');
                        $('.kurslisten').addClass('skjultvideresending');
                        $('.kurslisten').removeClass('skjultmodalknapp');


                    }
                }
            }
            function overrideLinksIfSearchFieldFocus() {

                console.log('Input search field focus');
                $('.kurslisten').addClass('skjultmodalknapp');
                $('.kurslisten').removeClass('skjultvideresending');
                /*
                console.log('There are two or more .wp-block-kadence-query classes in this div');
                $('.kurslisten').addClass('skjultvideresending');
                $('.kurslisten').removeClass('skjultmodalknapp');
                */                       
            }

            // Function to handle both click and 'Enter' keypress
            function handleInteraction(event) {
                if (event.type === 'click' || (event.type === 'keydown') || (event.type === 'keypress')) {
                    overrideLinksIfSearchFieldFocus(); // Call the function
                }
            }
            

            // Detect filter changes to trigger the override
            $(document).on('click', '.kadence-query-filter', function() {
                overrideLinksIfOneQueryBlock(); // Call the override function on filter button click
            });
            // Detect filter changes via searchfield
            $(document).on('keydown keypress', '.kb-filter-search', function(event) {
                handleInteraction(event); // Call the handler function
            });

            // Run the override function if the page is already loaded
            overrideLinksIfOneQueryBlock();
        }
    });
<?php 

function enqueue_custom_scripts() {
    global $post;
    
    // Get the current page's slug
    $page_slug = $post ? $post->post_name : '';

    // Existing modal behavior script
    $defaultIframeURL = esc_js(get_field('kurspamelding_skjema'));
    if (empty($defaultIframeURL)) {
        $defaultIframeURL = esc_js(get_field('kursliste'));
    }
    $defaultIframeURL = $defaultIframeURL . '&gtmevent=add_to_cart';

    // Sjekk for query-parameter 'fra' og legg til parameter hvis nødvendig
    //if (isset($_GET['fra']) && $_GET['fra'] === 'kurskalender') {
     //   $fromCourselist = 'ja';
    //}

    // Existing modal behavior script with jQuery
    $modal_script = "
        jQuery(document).ready(function($) {
            const defaultIframeURL = '{$defaultIframeURL}';
            const fromCourselist = '{$fromCourselist}';
            console.log('Default iframe url: ' . defaultIframeURL);
            console.log('From course list: ' . fromCourselist);

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
            // Dynamicly change iframe url if query parameter fra=kursliste
            /*if (fromCourselist === 'ja') {
                console.log('Iframe URL from courselist:', defaultIframeURL); // Debugging output
                $('#kursagenten-iframe').attr('src', defaultIframeURL);
            }*/
            // Handle click on the history back button above kursliste2 on enkeltkurs
            $('#tilbaketilkurs').on('click', function() {
                console.log('Klikket Tilbakeknapp');
                parent.history.back();
		        return false;
            });
        });
    ";


    // Enqueue both scripts in the footer
    wp_add_inline_script('jquery', $modal_script); // Enqueue the modal script

    // Localize the page slug for JavaScript
    wp_localize_script('jquery', 'scriptData', array('page_slug' => $page_slug));
}
add_action('wp_enqueue_scripts', 'enqueue_custom_scripts');

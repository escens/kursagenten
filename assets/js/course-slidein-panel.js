/* slidein panel for signup form */
function initSlideInPanel() {
    const panel = document.getElementById('slidein-panel');
    const overlay = document.getElementById('slidein-overlay');
    const iframe = document.getElementById('kursagenten-iframe');

    const defaultIframeURL = '<?php echo esc_js(get_post_meta(get_the_ID(), "course_signup_url", true) ?: get_post_meta(get_the_ID(), "kursliste", true)); ?>' + '&gtmevent=add_to_cart';

    // Open panel on link or button click
    document.querySelectorAll('.pameldingskjema').forEach(element => {
        element.removeEventListener('click', handleSlideInClick); // Fjern eksisterende for å unngå duplikater
        element.addEventListener('click', handleSlideInClick);
    });

    function handleSlideInClick(e) {
        e.preventDefault();
        e.stopPropagation(); // Hindrer bubbling, spesielt viktig for knapper i accordion
        const url = this.dataset.url || defaultIframeURL;
        iframe.src = url;
        panel.classList.add('active');
        overlay.classList.add('active');
    }

    // Close panel on overlay or button click
    overlay.addEventListener('click', function () {
        panel.classList.remove('active');
        overlay.classList.remove('active');
        iframe.src = ''; // Reset iframe URL
    });
    document.querySelector('#slidein-panel .close-btn').addEventListener('click', function () {
        panel.classList.remove('active');
        overlay.classList.remove('active');
        iframe.src = ''; // Reset iframe URL
    });
        // Initialize iFrame resizing
        document.getElementById('kursagenten-iframe').addEventListener('load', function () {
            iFrameResize({ log: false }, '#kursagenten-iframe');
        });
}

// Initialiser ved DOMContentLoaded
document.addEventListener('DOMContentLoaded', initSlideInPanel);


/* HTML code for slidein panel for use in templates
 * iFrameResizer script is included in code above

    <div id="slidein-overlay"></div>
    <div id="slidein-panel">
        <button class="close-btn" aria-label="Close">&times;</button>
        <iframe id="kursagenten-iframe" src=""></iframe>
        <script type="text/javascript" src="https://embed.kursagenten.no/js/iframe-resizer/iframeResizer.min.js"></script>
    </div>
*/
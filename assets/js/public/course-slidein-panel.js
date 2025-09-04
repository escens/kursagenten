/* slidein panel for signup form */
window.initSlideInPanel = function() {
    const panel = document.getElementById('slidein-panel');
    const overlay = document.getElementById('slidein-overlay');
    const iframe = document.getElementById('kursagenten-iframe');

    const defaultIframeURL = '<?php echo esc_js(get_post_meta(get_the_ID(), "course_signup_url", true) ?: get_post_meta(get_the_ID(), "kursliste", true)); ?>' + '&gtmevent=add_to_cart';

    if (!panel || !overlay || !iframe) {
        console.log('Required slidein panel elements not found');
        return;
    }

    // Open panel on link or button click
    document.querySelectorAll('.pameldingskjema').forEach(element => {
        element.removeEventListener('click', handleSlideInClick); // Remove existing to prevent duplicates
        element.addEventListener('click', handleSlideInClick);
    });

    function handleSlideInClick(e) {
        e.preventDefault();
        e.stopPropagation(); // Prevent bubbling, especially important for buttons in accordion
        const url = this.dataset.url || defaultIframeURL;
        iframe.src = url;
        panel.classList.add('active');
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    // Close panel on overlay or button click
    overlay.addEventListener('click', function () {
        panel.classList.remove('active');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
        iframe.src = ''; // Reset iframe URL
    });

    const closeBtn = panel.querySelector('.close-btn');
    if (closeBtn) {
        closeBtn.addEventListener('click', function () {
            panel.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
            iframe.src = ''; // Reset iframe URL
        });
    }

    // Initialize iFrame resizing
    iframe.addEventListener('load', function () {
        if (typeof iFrameResize === 'function') {
            iFrameResize({ 
                log: false,
                checkOrigin: false,
                trustedDomains: ['https://embed.kursagenten.no']
            }, '#kursagenten-iframe');
        }
    });
};

// Initialize on DOMContentLoaded
document.addEventListener('DOMContentLoaded', function() {
    window.initSlideInPanel();
});

/* HTML code for slidein panel for use in templates
    <div id="slidein-overlay"></div>
    <div id="slidein-panel">
        <button class="close-btn" aria-label="Close">&times;</button>
        <iframe id="kursagenten-iframe" src=""></iframe>
    </div>
*/
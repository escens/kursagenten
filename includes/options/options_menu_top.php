<?php
function kursagenten_admin_header($title) {
    ?>
    <div class="wrap options-form ka-wrap">
       
        
        <form method="post" action="options.php">

            <div class="sticky-header">
                <div class="section-nav">
                    <ul>
                        <li><a href="#toppen"><img src="https://login.kursagenten.no/kursagenten-nettstedsikon.png" alt="Kursagenten logo" style="width: 30px; height: 30px;"></a></li> 
                        <li><a href="#toppen">Til toppen</a></li>    
                        <?php
                        // Denne delen vil bli fylt dynamisk med JavaScript
                        ?>
                    </ul>
                </div>
                <div class="sticky-save">
                    <?php submit_button(null, 'primary', 'submit', false); ?>
                </div>
            </div>
            <div class="wrap-inner">
            <?php settings_errors(); ?>
            <h2 id="toppen"><?php echo esc_html($title); ?></h2> 
    <?php
}
function kursagenten_sticky_admin_menu() {
    ?>

            <div class="sticky-header">
                <div class="section-nav">
                    <ul>
                        <li><a href="#toppen"><img src="https://login.kursagenten.no/kursagenten-nettstedsikon.png" alt="Kursagenten logo" style="width: 30px; height: 30px;"></a></li> 
                        <li><a href="#toppen">Til toppen</a></li>    
                        <?php
                        // Denne delen vil bli fylt dynamisk med JavaScript
                        ?>
                    </ul>
                </div>
                <div class="sticky-save">
                    <?php submit_button(null, 'primary', 'submit', false); ?>
                </div>
            </div>
            <div class="wrap-inner">
            <?php settings_errors(); ?>
    <?php
}

function kursagenten_admin_footer() {
    ?>
        </form>
    </div>
    </div>
    <script>
    jQuery(document).ready(function($) {
        // Throttle scroll event for better performance
        let scrollTimeout;
        $(window).on('scroll', function() {
            if (!scrollTimeout) {
                scrollTimeout = setTimeout(function() {
                    // Scroll spy code here
                    const scrollPosition = $(window).scrollTop();
                    $('h3[id]').each(function() {
                        const target = $(this);
                        const id = target.attr('id');
                        const offset = target.offset().top - 100;

                        if (scrollPosition >= offset) {
                            $('.section-nav a').removeClass('active');
                            $('.section-nav a[href="#' + id + '"]').addClass('active');
                        }
                    });
                    scrollTimeout = null;
                }, 100); // Run max every 100ms
            }
        });

        // Finn alle h3-elementer og legg til i menyen
        const $nav = $('.section-nav ul');
        $('h3').each(function() {
            const $heading = $(this);
            const id = $heading.attr('id') || 'section-' + $heading.text().toLowerCase().replace(/\s+/g, '-');
            $heading.attr('id', id);
            
            if ($heading.is(':visible')) {
                $nav.append(`<li><a href="#${id}">${$heading.text()}</a></li>`);
            }
        });

        // Smooth scroll for navigation links
        $('.section-nav a').on('click', function(e) {
            e.preventDefault();
            const target = $($(this).attr('href'));
            $('html, body').animate({
                scrollTop: target.offset().top - 80
            }, 500);
        });
    });
    </script>

    <style>
    .sticky-header {
        position: sticky;
        top: 32px;
        background: white;
        margin: 0 0 0 -20px;
        padding: 15px 20px;
        z-index: 100;
        border-bottom: 1px solid #ccd0d4;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .section-nav ul {
        display: flex;
        align-items: center;
        gap: 20px;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .section-nav a {
        text-decoration: none;
        color: #1d2327;
        padding: 5px 0;
        position: relative;
    }

    .section-nav a:hover {
        color: #2271b1;
    }

    .section-nav a::after {
        content: '';
        position: absolute;
        width: 100%;
        height: 2px;
        bottom: -2px;
        left: 0;
        background-color: #2271b1;
        transform: scaleX(0);
        transition: transform 0.2s ease-in-out;
    }

    .section-nav a:hover::after,
    .section-nav a.active::after {
        transform: scaleX(1);
    }

    .wrap.options-form {
        padding-top: 0;
    }

    .wrap.options-form > h2 {
        margin-bottom: 0;
    }

    .options-form h3 {
        padding-top: 30px;
        margin-top: 20px;
        border-top: 1px solid #eee;
    }
    </style>
    <?php
} 
<?php
/**
 * The template for displaying single course posts.
 *
 * @package kursagenten
 */
    // Add meta tags to single course pages    
    function kursagenten_add_meta_tags() {
        global $post;
        $title = get_post_meta($post->ID, 'custom_title', true) ?: get_the_title($post->ID);
        $description = get_post_meta($post->ID, 'meta_description', true) ?: get_the_excerpt($post->ID);

        // Meta-tags
        echo '<meta name="title" content="' . esc_attr($title) . '">' . PHP_EOL;
        echo '<meta name="description" content="' . esc_attr($description) . '">' . PHP_EOL;

        // Open Graph
        echo '<meta property="og:title" content="' . esc_attr($title) . '">' . PHP_EOL;
        echo '<meta property="og:description" content="' . esc_attr($description) . '">' . PHP_EOL;
        echo '<meta property="og:type" content="website">' . PHP_EOL;
        echo '<meta property="og:url" content="' . esc_url(get_permalink($post->ID)) . '">' . PHP_EOL;
        echo '<meta property="og:image" content="' . esc_url(get_the_post_thumbnail_url($post->ID, 'full')) . '">' . PHP_EOL;
    }
    add_action('wp_head', 'kursagenten_add_meta_tags');

    if (current_user_can('editor') || current_user_can('administrator')) {
        $admin_view_class = ' admin-view';
        $admin_view = 'true';
    }

?>


<?php
    // Get post meta data
    $course_id = get_post_meta(get_the_ID(), 'location_id', true);
    $content = htmlspecialchars_decode(get_post_meta(get_the_ID(), 'course_content', true));
    $price = get_post_meta(get_the_ID(), 'course_price', true);
    $price_posttext = get_post_meta(get_the_ID(), 'course_text_after_price', true);
    $difficulty = get_post_meta(get_the_ID(), 'course_difficulty_level', true);
    $button_text = get_post_meta(get_the_ID(), 'button-text', true);
    $main_course_id = get_post_meta(get_the_ID(), 'main_course_id', true); // Added this line
    
    // Hent kursdatoer basert på om det er et foreldrekurs eller underkurs
    $is_parent_course = get_post_meta(get_the_ID(), 'is_parent_course', true);
    
    if ($is_parent_course === 'yes') {
        // For foreldrekurs: Hent alle kursdatoer som har main_course_id lik dette kursets location_id
        $related_coursedate = get_posts([
            'post_type' => 'coursedate',
            'posts_per_page' => -1,
            'meta_query' => [
                ['key' => 'main_course_id', 'value' => $course_id],
            ],
            'fields' => 'ids'
        ]);
    } else {
        // For underkurs: Hent kursdatoer basert på course_location taksonomien
        // i stedet for location_id
        $course_location_terms = wp_get_post_terms(get_the_ID(), 'course_location');
        
        if (!empty($course_location_terms) && !is_wp_error($course_location_terms)) {
            // Hent alle kursdatoer som tilhører samme course_location taksonomi
            // OG som tilhører samme kurs (samme main_course_id)
            $location_names = array_map(function($term) {
                return $term->name;
            }, $course_location_terms);

            // Bruk main_course_id for å filtrere siden vi nå har den definert
            if (!empty($main_course_id)) {
                $meta_query_main = [
                    'relation' => 'AND',
                    [
                        'relation' => 'OR',
                        ['key' => 'course_location', 'value' => $location_names, 'compare' => 'IN'],
                        ['key' => 'course_location_freetext', 'value' => $location_names, 'compare' => 'IN']
                    ],
                    ['key' => 'main_course_id', 'value' => $main_course_id, 'compare' => '=']
                ];
                
                $related_coursedate = get_posts([
                    'post_type' => 'coursedate',
                    'posts_per_page' => -1,
                    'meta_query' => $meta_query_main,
                    'fields' => 'ids'
                ]);
            } else {
                // Fallback: bruk course_title hvis main_course_id ikke er tilgjengelig
                $course_title = get_post_meta(get_the_ID(), 'course_title', true);
                
                if (!empty($course_title)) {
                    $meta_query_title = [
                        'relation' => 'AND',
                        [
                            'relation' => 'OR',
                            ['key' => 'course_location', 'value' => $location_names, 'compare' => 'IN'],
                            ['key' => 'course_location_freetext', 'value' => $location_names, 'compare' => 'IN']
                        ],
                        ['key' => 'course_title', 'value' => $course_title, 'compare' => '=']
                    ];
                    
                    $related_coursedate = get_posts([
                        'post_type' => 'coursedate',
                        'posts_per_page' => -1,
                        'meta_query' => $meta_query_title,
                        'fields' => 'ids'
                    ]);
                } else {
                    // Fallback: bruk kun lokasjon hvis begge er tomme
                    $related_coursedate = get_posts([
                        'post_type' => 'coursedate',
                        'posts_per_page' => -1,
                        'meta_query' => [
                            'relation' => 'OR',
                            ['key' => 'course_location', 'value' => $location_names, 'compare' => 'IN'],
                            ['key' => 'course_location_freetext', 'value' => $location_names, 'compare' => 'IN']
                        ],
                        'fields' => 'ids'
                    ]);
                }
            }
        } else {
            // Fallback til original logikk hvis ingen course_location taksonomi er satt
            $related_coursedate = get_posts([
                'post_type' => 'coursedate',
                'posts_per_page' => -1,
                'meta_query' => [
                    ['key' => 'location_id', 'value' => $course_id],
                ],
                'fields' => 'ids'
            ]);
        }
    }
    
    if (empty($related_coursedate) || !is_array($related_coursedate)) {
        $related_coursedate = [];
    }
    $main_course_title = get_post_meta(get_the_ID(), 'main_course_title', true);
    $sub_course_location = get_post_meta(get_the_ID(), 'sub_course_location', true);
    $is_full = get_post_meta(get_the_ID(), 'course_isFull', true);
    $contact_name = get_post_meta(get_the_ID(), 'course_contactperson_name', true);
    $contact_phone = get_post_meta(get_the_ID(), 'course_contactperson_phone', true);
    $contact_email = get_post_meta(get_the_ID(), 'course_contactperson_email', true);

    // Get placeholder image from settings
    $kursinnst_options = get_option('kag_kursinnst_option_name');
    $placeholder_image = !empty($kursinnst_options['ka_plassholderbilde_kurs'])
        ? $kursinnst_options['ka_plassholderbilde_kurs']
        : KURSAG_PLUGIN_URL . 'assets/images/placeholder-kurs.jpg';

    $featured_image_full = get_the_post_thumbnail_url(get_the_ID(), 'full') ?: $placeholder_image;
    $featured_image_thumb = get_the_post_thumbnail_url(get_the_ID(), 'thumbnail') ?: $placeholder_image;
    $featured_image_medium = get_the_post_thumbnail_url(get_the_ID(), 'medium') ?: $placeholder_image;
    $featured_image_large = get_the_post_thumbnail_url(get_the_ID(), 'large') ?: $placeholder_image;

    $wp_content = get_the_content();

    // Get coursecategories - finner kategorier som brukes som lenker i header
    $excluded_terms = ['skjult', 'skjul', 'usynlig', 'inaktiv', 'ikke-aktiv'];
    $coursecategories = wp_get_post_terms(get_the_ID(), 'coursecategory', [
        'exclude' => array_map(function ($term_slug) {
            $term = get_term_by('slug', $term_slug, 'coursecategory');
            return $term ? $term->term_id : null;
        }, $excluded_terms)
    ]);

    // Forbedret håndtering av coursecategories - viser kategorier som lenker i header
    $coursecategory_links = [];
    if (!empty($coursecategories) && !is_wp_error($coursecategories)) {
        $coursecategory_links = array_map(function ($term) {
            return '<a href="' . esc_url(get_term_link($term)) . '">' . esc_html($term->name) . '</a>';
        }, $coursecategories);
    }

    // Get instructors
    $instructors = wp_get_post_terms(get_the_ID(), 'instructors');
    if (!empty($instructors) && !is_wp_error($instructors)) {
        $instructor_links = array_map(function ($term) {
            $instructor_url = get_instructor_display_url($term, 'instructors');
            return '<a href="' . esc_url($instructor_url) . '">' . esc_html($term->name) . '</a>';
        }, $instructors);
    }

    // Sjekk selected_coursedate_data - finner første ledige kursdato
    $selected_coursedate_data = get_selected_coursedate_data($related_coursedate);
    
    // Sjekk all_coursedates - finner alle kursdatoer
    $all_coursedates = get_all_sorted_coursedates($related_coursedate);
 ?>

<style>
    .background-blur { background-image: url('<?php echo esc_url($featured_image_full); ?>'); }
    @media (max-width: 1600px) { .background-blur { background-image: url('<?php echo esc_url($featured_image_large); ?>'); } }
    @media (max-width: 1024px) { .background-blur { background-image: url('<?php echo esc_url($featured_image_medium); ?>'); } }
    @media (max-width: 768px) { .background-blur { background-image: url('<?php echo esc_url($featured_image_thumb); ?>'); } }
</style>

<article class="ka-outer-container course-container">
    <?php if ($admin_view === 'true') : ?>
    <div class="edit-course edit-link"><a href="<?php echo "https://www.kursagenten.no/User.aspx?page=regKurs&id=" . $course_id; ?>" target="_blank"><span class="ka-icon-button"><i class="ka-icon icon-edit"></i></span><span class="edit-text">Rediger kurs</span></a></div>
    <?php endif; ?>
    <!-- HEADER -->
    <header class="ka-section ka-header">
        <div class="ka-content-container">
            <div class="background-blur"></div>
            <div class="overlay"></div>
            <div class="ka-content-container header-content">
                <?php if ($is_parent_course === 'yes') : ?>
                    <h1><?php the_title(); ?></h1>
                <?php else : ?>
                    <h1><?php echo esc_html($main_course_title); ?></h1>
                    <h2 style="margin-top: -.6em;"><?php echo esc_html($sub_course_location); ?></h2>
                <?php endif; ?>
                <div class="header-links iconlist horizontal uppercase small">
                    <div><a href="<?php echo esc_url(Designmaler::get_system_page_url('kurs')); ?>"><i class="ka-icon icon-vertical-bars"></i> Alle kurs</a></div> 
                    <div class="taxonomy-list horizontal">
                        <?php if (!empty($coursecategory_links)) : ?>
                            <i class="ka-icon icon-vertical-bars"></i><?php echo implode('<span class="separator">|</span>', $coursecategory_links); ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php do_action('ka_singel_header_links_after'); ?>
                <div class="course-buttons">
                    <?php if (!empty($selected_coursedate_data) && isset($selected_coursedate_data['signup_url'])) : ?>
                        <a href="#" class="button pameldingskjema clickelement" data-url="<?php echo esc_url($selected_coursedate_data['signup_url']); ?>">
                            <?php echo esc_html($selected_coursedate_data['button_text'] ?? 'Påmelding'); ?>
                        </a>
                    <?php endif; ?>
                    <!--<a href="#" class="button">Legg til i ønskeliste</a>-->
                </div>
            </div>
        </div>
    </header>

    <?php do_action('ka_singel_header_after'); ?>

    <!-- DETAILS -->
    <section class="ka-section details">
        <div class="ka-content-container">
            <div class="course-grid col-3-1">
                <!-- Course list -->
                <div class="courselist">                          
                    <?php if (!empty($all_coursedates)) : ?>
                    <div class="all-coursedates">
                        <h2 class="small">Kurstider og steder</h2>
                        <p><?php echo display_course_locations(get_the_ID()); ?></p>
                        <div class="accordion courselist-items-wrapper expand-content" data-size="220px">
                            <?php 
                            $totalCourses = count($all_coursedates);
                            foreach ($all_coursedates as $index => $coursedate) : 
                                $item_class = $totalCourses === 1 ? 'courselist-item single-item' : 'courselist-item';
                                if (isset($coursedate['course_isFull']) && $coursedate['course_isFull'] === 'true') {
                                    $item_class .= ' full';
                                    $available_text = 'Kurset er fullt';
                                    $available_class = 'full';  
                                }else{
                                    $item_class .= ' available';
                                    $available_text = 'Ledige plasser';
                                    $available_class = 'available';
                                }
                            ?>
                                <div class="<?php echo $item_class; ?>">
                                    <div class="courselist-main" onclick="toggleAccordion(this)">
                                        <div class="text-area">
                                            <div class="title-area">
                                                
                                                <?php if (isset($coursedate['course_isFull']) && $coursedate['course_isFull'] === 'true') : ?>
                                                    <span class="course-available <?php echo $available_class; ?> accordion-icon" title="<?php echo $available_text; ?>"></span>
                                                    <span class="courselist-title <?php echo $available_class; ?>" title="<?php echo $available_text; ?>">
                                                </span>
                                                <?php else : ?>
                                                    <span class="course-available <?php echo $available_class; ?> accordion-icon" title="<?php echo $available_text; ?>"></span>
                                                    <span class="courselist-title <?php echo $available_class; ?>" title="<?php echo $available_text; ?>">
                                                </span>
                                                <?php endif; ?>
                                                <strong class="<?php echo $available_class; ?>" title="<?php echo $available_text; ?>"><?php echo esc_html($coursedate['location']) ?></strong>
                                                <span class="courselist-details">
                                                    <?php echo esc_html($coursedate['first_date']) ?>
                                                </span>
                                            </div>
                                            <div class="content-area">
                                            <?php echo esc_html($coursedate['course_title']) /* KLADD  */ ?> 
                                                <?php if (!empty($coursedate['time'])) : ?>
                                                <span class="courselist-details">
                                                    <?php echo esc_html($coursedate['time']) ?> 
                                                </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="links-area">
                                            <span class="more-info ka-text-xs">Mer info</span>
                                            <a class="courselist-button pameldingskjema clickelement"  data-url="<?php echo esc_url($coursedate['signup_url']); ?>">
                                            <?php echo esc_html($coursedate['button_text']) ?>
                                            </a>   
                                        </div>
                                    </div>
                                    <div class="accordion-content courselist-content">
                                        <?php if ($coursedate['missing_first_date']) : ?>
                                            <?php 
                                            $is_online = has_term('nettbasert', 'course_location', $coursedate['id']);
                                            $show_registration = get_post_meta($coursedate['id'], 'course_showRegistrationForm', true);
                                            if ($is_online) : ?>
                                                <p>Etter påmelding vil du få en e-post med mer informasjon om kurset.</p>
                                            <?php elseif ($show_registration == '1' || $show_registration === 1 || $show_registration === "true" || $show_registration === true) : ?>
                                                <p>Du kan melde deg på kurset nå. Etter påmelding vil du få mer informasjon.</p>
                                            <?php else : ?>
                                                <p>Det er ikke satt opp dato for nye kurs. Meld din interesse for å få mer informasjon eller å sette deg på venteliste.</p>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <?php if (isset($coursedate['course_isFull']) && ($coursedate['course_isFull'] === 'true' || $coursedate['course_isFull'] === 1)) : ?>
                                            <p>Kurset er fullt. Du kan melde din interesse for å få mer informasjon eller å sette deg på venteliste.</p>
                                        <?php endif ?>
                                        <div class="course-grid col-1-1" style="padding-left: 2vw; padding-right: 2vw;">
                                            <div class="content">
                                                <p>
                                                <?php if (!empty($coursedate['first_date'])): ?>
                                                        <span style="font-weight: bold;">Starter:</span>
                                                        <span><?php echo esc_html($coursedate['first_date']) ?></span><br>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (!empty($coursedate['last_date'])): ?>
                                                        <span style="font-weight: bold;">Slutter:</span>
                                                        <span><?php echo esc_html($coursedate['last_date']) ?></span><br>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (!empty($coursedate['price'])): ?>
                                                        <span style="font-weight: bold;">Pris:</span>
                                                        <span><?php echo esc_html($coursedate['price']) ?> <?php echo esc_html($price_posttext); ?></span><br>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (!empty($coursedate['location'])): ?>
                                                        <span style="font-weight: bold;">Sted:</span>
                                                        <span><?php echo esc_html($coursedate['location']) ?></span><br>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (!empty($coursedate['duration'])): ?>
                                                        <span style="font-weight: bold;">Varighet:</span>
                                                        <span><?php echo esc_html($coursedate['duration']) ?></span><br>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (!empty($coursedate['language'])): ?>
                                                        <span style="font-weight: bold;">Språk:</span>
                                                        <span><?php echo esc_html($coursedate['language']) ?></span>
                                                    <?php endif; ?>
                                        </p>
                                            </div>
                                            <div class="aside">
                                                <?php if (!empty($coursedate['address_street'])) : ?>
                                                <p><strong>Adresse</strong></p>
                                                <p> <?php echo esc_html($coursedate['course_location_freetext']) ?><br>
                                                    <?php echo esc_html($coursedate['address_street']) ?><br>
                                                    <?php echo esc_html($coursedate['postal_code']) ?> <?php echo esc_html($coursedate['city']) ?><br>
                                                    <a style="display: block; padding-top: .4em;" href="https://www.google.com/maps/search/?api=1&query=<?php echo esc_attr($coursedate['address_street']) ?>,+<?php echo esc_attr($coursedate['postal_code']) ?>+<?php echo esc_attr($coursedate['city']) ?>" target="_blank">Vis i Google Maps</a>
                                                </p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php do_action('ka_singel_courselist_after'); ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Next course information -->
                <div class="nextcourse">
                    <?php if (!empty($selected_coursedate_data['coursedatemissing'])) : ?>
                        <h2 class="small">Informasjon</h2>
                    <?php else : ?>
                        <h2 class="small">Neste kurs</h2>
                    <?php endif; ?>
                    <div class="iconlist medium">
                        <?php if (!empty($selected_coursedate_data['first_date'])) : ?>
                            <div><i class="ka-icon icon-calendar"></i>Starter: <?php echo esc_html($selected_coursedate_data['first_date']) ;?></div>
                        <?php endif; ?>
                        <?php if (!empty($selected_coursedate_data['last_date'])) : ?>
                            <div><i class="ka-icon icon-calendar"></i>Slutter: <?php echo esc_html($selected_coursedate_data['last_date']) ;?></div>
                        <?php endif; ?>
                        <?php if (!empty($selected_coursedate_data['time'])) : ?>
                            <div><i class="ka-icon icon-time"></i>Kurstider: <?php echo esc_html($selected_coursedate_data['time']) ;?></div>
                        <?php endif; ?>
                        <?php if (!empty($selected_coursedate_data['duration'])) : ?>
                            <div><i class="ka-icon icon-stopwatch"></i>Varighet: <?php echo esc_html($selected_coursedate_data['duration']) ;?></div>
                        <?php endif; ?>
                        <?php if (!empty($selected_coursedate_data['language'])) : ?>
                            <div><i class="ka-icon icon-chat-bubble"></i>Språk: <?php echo esc_html($selected_coursedate_data['language']) ;?></div>
                        <?php endif; ?>
                        <?php if (!empty($selected_coursedate_data['price'])) : ?>
                            <div><i class="ka-icon icon-bag"></i>Pris: <?php echo esc_html($selected_coursedate_data['price']) ;?> <?php echo esc_html($price_posttext) ;?></div>
                        <?php endif; ?>
                    </div>
                    <?php do_action('ka_singel_nextcourse_after'); ?>
                </div>
                

            </div>
        </div>
    </section>

    <!-- COURSE INFORMATION -->
    <section class="ka-section course-information">
        <div class="ka-content-container">
            <div class="course-grid">
                <!-- Content -->
                <div class="content">
                    
                    <h2>Om kurset</h2>
                    <?php do_action('ka_singel_content_intro_before'); ?>
                    <p><?php the_excerpt(); ?></p>
                    <?php do_action('ka_singel_content_intro_after'); ?>
                    <?php do_action('ka_singel_content_before'); ?>
                    <!-- WP content -->
                    <?php if (!empty($wp_content)) : ?>
                        <?php if ($admin_view === 'true') : ?>
                        <div class="edit-link"><a href="<?php echo get_edit_post_link(); ?>"><i class="ka-icon icon-edit"></i><span class="edit-text">Rediger Wordpress innhold</span></a></div>
                        <?php endif; ?>
                        <div class="content-text<?php echo $admin_view_class; ?>"><?php echo wp_kses_post($wp_content); ?></div>
                    <?php else : ?>
                        <?php if ($admin_view === 'true') : ?>
                        <div class="edit-link"><a href="<?php echo get_edit_post_link(); ?>"><i class="ka-icon icon-plus"></i><span class="edit-text">Legg til ekstra Wordpress innhold</span></a></div>
                        <?php endif; ?>
                        <div class="content-text<?php echo $admin_view_class; ?>"></div>
                    <?php endif; ?>
                    <p><?php echo wpautop(wp_kses_post($content)); ?></p>
                    <?php do_action('ka_singel_content_after'); ?>
                </div>
                <!-- Course image -->
                <?php if (has_post_thumbnail()): ?>
                <picture class="course-image">
                    <img src="<?php echo esc_url(get_the_post_thumbnail_url(get_the_ID(), 'large')); ?>" alt="Bilde for kurs i <?php the_title(); ?>" decoding="async">
                </picture>
                <?php endif; ?>
                <!-- Sidebar -->
                <div class="aside">
                    <?php do_action('ka_singel_aside_before'); ?>
                    <?php if (!empty($contact_name)) : ?>
                        <div class="contact-info ka-box">
                            <h3>Kontaktinformasjon</h3>
                            <p>
                            <?php if (!empty($contact_name)) : ?><?php echo esc_html($contact_name); ?><br><?php endif; ?>
                            <?php if (!empty($contact_phone)) : ?><?php echo esc_html($contact_phone); ?><br><?php endif; ?>
                            <?php if (!empty($contact_email)) : ?><?php echo esc_html($contact_email); ?><?php endif; ?>
                            </p>
                        </div>
                        <div class="similar-courses"></div>
                    <?php endif; ?>
                    <?php do_action('ka_singel_aside_after'); ?>
                </div>


            </div>
        </div>
    </section>
    <?php do_action('ka_singel_footer_before'); ?>
    <section class="ka-section ka-footer">
        <div class="ka-content-container title-section">
            <h3>Kurs i samme kategori</h3>
        <?php echo do_shortcode('[kurs-i-samme-kategori stil="kort" overskrift="h4" layout="rad" bildestr="100px" bildeformat="4/3" bildeform=firkantet fontmin="13px" fontmaks="15px" avstand="2em .5em"]'); ?>
        </div>
    </section>
    <?php do_action('ka_singel_footer_after'); ?>
</article>





<?php
// Debug-utskrift
/*
add_action('wp_head', function() {
    if (is_single() && get_post_type() === 'course') {
        //error_log('Debug Course Data:');
        //error_log('Post ID: ' . get_the_ID());
        //error_log('Course ID: ' . get_post_meta(get_the_ID(), 'location_id', true));
        //error_log('Related Coursedate: ' . get_post_meta(get_the_ID(), 'course_related_coursedate', true));
        
        // Sjekk coursecategories
        $coursecategories = wp_get_post_terms(get_the_ID(), 'coursecategory');
        error_log('Coursecategories: ' . print_r($coursecategories, true));
        
        // Sjekk selected_coursedate_data
        $related_coursedate = get_post_meta(get_the_ID(), 'course_related_coursedate', true);
        $selected_coursedate_data = get_selected_coursedate_data($related_coursedate);
    }
});
*/

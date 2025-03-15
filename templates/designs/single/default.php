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
    $related_coursedate = get_post_meta(get_the_ID(), 'course_related_coursedate', true);
    $contact_name = get_post_meta(get_the_ID(), 'course_contactperson_name', true);
    $contact_phone = get_post_meta(get_the_ID(), 'course_contactperson_phone', true);
    $contact_email = get_post_meta(get_the_ID(), 'course_contactperson_email', true);
    error_log('course_related_coursedate: ' . $related_coursedate);

    // Get WP data
    $featured_image_full = get_the_post_thumbnail_url(get_the_ID(), 'full') ?: 'path/to/default-image.jpg';
    $featured_image_thumb = get_the_post_thumbnail_url(get_the_ID(), 'thumbnail') ?: 'path/to/default-image.jpg';
    $featured_image_medium = get_the_post_thumbnail_url(get_the_ID(), 'medium') ?: 'path/to/default-image.jpg';
    $featured_image_large = get_the_post_thumbnail_url(get_the_ID(), 'large') ?: 'path/to/default-image.jpg';

    $wp_content = get_the_content();

    // Get coursecategories
    $excluded_terms = ['skjult', 'skjul', 'usynlig', 'inaktiv', 'ikke-aktiv'];
    $coursecategories = wp_get_post_terms(get_the_ID(), 'coursecategory', [
        'exclude' => array_map(function ($term_slug) {
            $term = get_term_by('slug', $term_slug, 'coursecategory');
            return $term ? $term->term_id : null;
        }, $excluded_terms)
    ]);

    // Forbedret håndtering av coursecategories
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
            return '<a href="' . esc_url(get_term_link($term)) . '">' . esc_html($term->name) . '</a>';
        }, $instructors);
    }

    // Get selected coursedate data (first available date)
    $selected_coursedate_data = get_selected_coursedate_data($related_coursedate);
    // Get all coursedates
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
                <h1><?php the_title(); ?></h1>
                <div class="header-links iconlist horizontal uppercase small">
                    <div><a href="<?php echo get_post_type_archive_link('course'); ?>"><i class="ka-icon icon-vertical-bars"></i> Alle kurs</a></div> 
                    <div class="taxonomy-list horizontal">
                        <?php if (!empty($coursecategory_links)) : ?>
                            <i class="ka-icon icon-tag"></i><?php echo implode('<span class="separator">|</span>', $coursecategory_links); ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="course-buttons">
                    <?php if (!empty($selected_coursedate_data) && isset($selected_coursedate_data['signup_url'])) : ?>
                        <a href="#" class="button pameldingskjema clickelement" data-url="<?php echo esc_url($selected_coursedate_data['signup_url']); ?>">
                            <?php echo esc_html($selected_coursedate_data['button_text'] ?? 'Påmelding'); ?>
                        </a>
                    <?php endif; ?>
                    <a href="#" class="button">Legg til i ønskeliste</a>
                </div>
            </div>
        </div>
    </header>
    <!-- DETAILS -->
    <section class="ka-section details">
        <div class="ka-content-container">
            <div class="course-grid col-1-3">
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
                    </div>
                    <!-- Course list -->
                    <div class="courselist">                          
                        <?php if (!empty($all_coursedates)) : ?>
                        <div class="all-coursedates">
                            <h2 class="small">Kurstider og steder</h2>
                            <div class="accordion courselist-items-wrapper expand-content" data-size="180px">
                                <?php 
                                $totalCourses = count($all_coursedates);
                                foreach ($all_coursedates as $index => $coursedate) : 
                                    $item_class = $totalCourses === 1 ? 'courselist-item single-item' : 'courselist-item';
                                ?>
                                    <div class="<?php echo $item_class; ?>">
                                        <div class="courselist-main" onclick="toggleAccordion(this)">
                                            <div class="text-area">
                                                <div class="title-area">
                                                    <span class="accordion-icon">+</span>
                                                    <span class="courselist-title">
                                                        <strong><?php echo esc_html($coursedate['location']) ?></strong>
                                                        <?php echo esc_html($coursedate['first_date']) ?>
                                                        <?php echo esc_html($coursedate['time']) ?> 
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="links-area">
                                                <button class="courselist-button pameldingskjema clickelement"  data-url="<?php echo esc_url($coursedate['signup_url']); ?>">
                                                <?php echo esc_html($coursedate['button_text']) ?>
                                                </button>   
                                            </div>
                                        </div>
                                        <div class="accordion-content courselist-content">
                                            <?php if ($coursedate['missing_first_date']) : ?>
                                                <p>Det er ikke satt opp dato for nye kurs. Meld din interesse for å få mer informasjon eller å sette deg på venteliste.</p>   
                                            <?php endif; ?>
                                            <ul>
                                                <li>Starts: <?php echo esc_html($coursedate['first_date']) ?></li>
                                                <li>Price: <?php echo esc_html($coursedate['price']) ?> <?php echo esc_html($price_posttext); ?></li>
                                                <li>Location: <?php echo esc_html($coursedate['location'] ?? 'N/A') ?></li>
                                                <li>Duration: <?php echo esc_html($coursedate['duration']) ?></li>
                                            </ul>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
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
                    <p><?php the_excerpt(); ?></p>
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
                </div>
                <!-- Course image -->
                <picture class="course-image">
                        <img src="<?php echo esc_url($featured_image_large); ?>" alt="Bilde for kurs i <?php the_title(); ?>" decoding="async">
                </picture>
                <!-- Sidebar -->
                <div class="aside">
                    
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
                </div>


            </div>
        </div>
    </section>
    <section class="ka-section ka-footer">
        <div class="ka-content-container title-section">
            <h4>Footer</h4>
        </div>
    </section>
</article>





<?php
// Debug-utskrift
add_action('wp_head', function() {
    if (is_single() && get_post_type() === 'course') {
        error_log('Debug Course Data:');
        error_log('Post ID: ' . get_the_ID());
        error_log('Course ID: ' . get_post_meta(get_the_ID(), 'location_id', true));
        error_log('Related Coursedate: ' . get_post_meta(get_the_ID(), 'course_related_coursedate', true));
        
        // Sjekk coursecategories
        $coursecategories = wp_get_post_terms(get_the_ID(), 'coursecategory');
        error_log('Coursecategories: ' . print_r($coursecategories, true));
        
        // Sjekk selected_coursedate_data
        $related_coursedate = get_post_meta(get_the_ID(), 'course_related_coursedate', true);
        $selected_coursedate_data = get_selected_coursedate_data($related_coursedate);
        error_log('Selected Coursedate Data: ' . print_r($selected_coursedate_data, true));
    }
});

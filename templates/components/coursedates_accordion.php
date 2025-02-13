

<div class="<?php echo $item_class; ?>">
    <div class="accordion-header" onclick="toggleAccordion(this)">
        <div class="accordion-header-text">
            <span class="accordion-icon">+</span>
            <span class="accordion-title"><a href="<?php echo esc_url($course_link); ?>" class="course-link clickelement small"><h3 class="course-title"><?php the_title(); ?></h3></a></span>
        </div>
        <p><a href="<?php echo esc_url($course_link); ?>" class="course-link clickelement small">Les mer</a>
        <button class="accordion-button pamelding pameldingsknapp pameldingskjema clickelement" data-url="<?php echo esc_url($signup_url); ?>">
        <?php echo esc_html($button_text) ?>
        </button>
    </div>

    <div class="course-content accordion-content">
        <p><?php echo esc_html($first_course_date ? $first_course_date : 'Det er ikke satt opp dato for nye kurs. Meld din interesse for å få mer informasjon eller å sette deg på venteliste.'); ?></p>
        <p><a href="<?php echo esc_url($course_link); ?>" class="course-link clickelement">Se kursdetaljer</a></p>
    </div>
</div>
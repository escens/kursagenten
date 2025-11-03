# 📋 Course Dates Modal - Bruksguide

## Oversikt
En gjenbrukbar popup-modal for å vise alle tilgjengelige kursdatoer og kurssteder.

## ✅ Hva er gjort

### Filer opprettet/oppdatert:
1. **JavaScript:** `assets/js/public/course-dates-modal.js` (gjenbrukbar modul)
2. **CSS:** `assets/css/public/frontend-course-style.css` (ny seksjon: COURSE DATES MODAL)
3. **Registrering:** `kursagenten.php` (linje 606 - globalt lastet)
4. **Eksempel:** `grid.php` (ferdig implementert)

## 🎯 Hvordan bruke i andre templates

### Steg 1: Legg til trigger-link
```php
<?php if (count($related_coursedate_ids) > 1) : ?>
    <a href="#" class="show-ka-modal" data-course-id="<?php echo esc_attr($course_id); ?>">
        (+<?php echo count($related_coursedate_ids) - 1; ?> flere)
    </a>
<?php endif; ?>
```

**Viktig:** 
- Klasse: `show-ka-modal`
- Data-attributt: `data-course-id="{course_id}"`

### Steg 2: Legg til modal-struktur
```php
<?php if ($view_type === 'main_courses' && count($related_coursedate_ids) > 1) : ?>
<div class="ka-course-dates-modal" id="modal-<?php echo esc_attr($course_id); ?>" style="display: none;">
    <div class="ka-modal-overlay"></div>
    <div class="ka-modal-content">
        <div class="ka-modal-header">
            <h3><?php echo esc_html($course_title); ?></h3>
            <button class="ka-modal-close" aria-label="Lukk">&times;</button>
        </div>
        <div class="ka-modal-body">
            <h4>Alle tilgjengelige kurssteder og datoer</h4>
            <?php
            // Hent main_course_id
            $main_course_id = get_post_meta($course_id, 'ka_main_course_id', true);
            if (empty($main_course_id)) {
                $main_course_id = get_post_meta($course_id, 'ka_location_id', true);
            }
            
            // Hent alle kursdatoer
            $all_coursedates_popup = get_posts([
                'post_type' => 'ka_coursedate',
                'posts_per_page' => -1,
                'meta_query' => [
                    ['key' => 'ka_main_course_id', 'value' => $main_course_id],
                ],
            ]);
            
            // Samle lokasjonsdata
            $locations_popup = [];
            foreach ($all_coursedates_popup as $coursedate) {
                $cd_location = get_post_meta($coursedate->ID, 'ka_course_location', true);
                $cd_freetext = get_post_meta($coursedate->ID, 'ka_course_location_freetext', true);
                $cd_first_date = get_post_meta($coursedate->ID, 'ka_course_first_date', true);
                $cd_signup_url = get_post_meta($coursedate->ID, 'ka_course_signup_url', true);
                
                if (!empty($cd_location) && !empty($cd_first_date)) {
                    $key = $cd_location;
                    if (!isset($locations_popup[$key])) {
                        $locations_popup[$key] = [
                            'name' => $cd_location,
                            'freetext' => $cd_freetext,
                            'dates' => []
                        ];
                    }
                    $locations_popup[$key]['dates'][] = [
                        'date' => ka_format_date($cd_first_date),
                        'raw_date' => $cd_first_date,
                        'url' => $cd_signup_url
                    ];
                }
            }
            
            // Sorter datoer
            foreach ($locations_popup as &$loc_data) {
                usort($loc_data['dates'], function($a, $b) {
                    return strcmp($a['raw_date'], $b['raw_date']);
                });
            }
            unset($loc_data);
            
            // Vis lokasjoner med datoer
            if (!empty($locations_popup)) :
                foreach ($locations_popup as $loc) : ?>
                    <div class="ka-location-group">
                        <h5><?php echo esc_html($loc['name']); ?><?php if (!empty($loc['freetext'])) : ?> (<?php echo esc_html($loc['freetext']); ?>)<?php endif; ?></h5>
                        <ul class="ka-dates-list">
                            <?php foreach ($loc['dates'] as $date_info) : ?>
                                <li>
                                    <a href="#" class="pameldingskjema" data-url="<?php echo esc_url($date_info['url']); ?>">
                                        <?php echo esc_html($date_info['date']); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach;
            else : ?>
                <p>Ingen kursdatoer tilgjengelig for øyeblikket.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>
```

## 📖 API Dokumentasjon

### JavaScript Events

Modal-modulen trigger custom events du kan lytte på:

```javascript
// Når modal åpnes
jQuery(document).on('ka:modal:opened', function(e, data) {
    console.log('Modal åpnet for kurs:', data.courseId);
    // Legg til tracking/analytics her
});

// Når modal lukkes
jQuery(document).on('ka:modal:closed', function(e, data) {
    console.log('Modal lukket for kurs:', data.courseId);
});
```

### CSS Variabler

Modal bruker CSS-variabler for enkel tilpasning:

```css
:root {
    --ka-primary-color: #2271b1; /* Hovedfarge for header */
    --ka-color: #2271b1;         /* Fallback farge */
}
```

## 🎨 Styling

All styling er i `assets/css/public/frontend-course-style.css` under seksjonen:
```css
/* COURSE DATES MODAL */
```

### Klasser du kan style:
- `.ka-course-dates-modal` - Hoved-container
- `.ka-modal-overlay` - Bakgrunns-overlay
- `.ka-modal-content` - Modal-boksen
- `.ka-modal-header` - Header med tittel og lukk-knapp
- `.ka-modal-body` - Innhold
- `.ka-location-group` - Lokasjonsgruppe
- `.ka-dates-list` - Liste med datoer
- `.show-ka-modal` - Trigger-link

## 📱 Responsivitet

Modal er fullt responsiv:
- **Desktop:** 600px bred, 80% av viewport høyde
- **Mobil:** 95% bred, 90% av viewport høyde
- **Scrollbar:** Automatisk hvis mange datoer
- **Touch-vennlig:** Fungerer perfekt på touchskjermer

## 🔧 Eksempel: Implementering i compact.php

```php
// I compact.php, rett etter første dato-visning:
<?php if ($view_type === 'main_courses' && !empty($first_course_date)) : ?>
    <div class="course-date">
        Neste kurs: <?php echo esc_html($first_course_date); ?>
        <?php if (count($related_coursedate_ids) > 1) : ?>
            <a href="#" class="show-ka-modal" data-course-id="<?php echo esc_attr($course_id); ?>">
                (+<?php echo count($related_coursedate_ids) - 1; ?> flere)
            </a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Legg til modal-strukturen nederst i template -->
<!-- Se Steg 2 over -->
```

## ⚡ Performance

- **Lazy loading:** Modal-innhold lastes ikke før den åpnes
- **Static flag:** CSS og JS lastes kun én gang per side
- **Event delegation:** Effektiv håndtering av flere modaler
- **Caching:** Browser cacher scripts (versjonert med KURSAG_VERSION)

## 🐛 Troubleshooting

### Modal åpner ikke
- Sjekk at jQuery er lastet
- Sjekk at `course-dates-modal.js` er enqueued
- Sjekk console for JavaScript-feil
- Verifiser at modal har riktig ID: `modal-{course_id}`

### Modal vises alltid (ikke skjult)
- Sjekk at `style="display: none;"` er på `.ka-course-dates-modal`
- Fjern eventuell CSS som overstyrer display

### Påmeldingsknapper fungerer ikke i modal
- Sjekk at `class="pameldingskjema"` er på linkene
- Sjekk at `data-url` er satt korrekt
- Verifiser at `course-slidein-panel.js` er lastet

## 📝 Notater

- Modal bruker `position: fixed` med høy z-index (10000)
- Bakgrunns-scrolling hindres når modal er åpen
- Modal kan enkelt tilpasses med CSS-variabler
- Fullstendig tilgjengelig med keyboard (ESC lukker)

---

**Implementert i:** `grid.php` (linje 225-374)  
**Opprettet:** 2024-11-02  
**Versjon:** 1.0.0


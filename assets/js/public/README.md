# Kursagenten Public JavaScript Modules

## Overview
Dette er gjenbrukbare JavaScript-moduler for Kursagenten frontend-funksjonalitet.

## Moduler

### 1. `course-expand-content.js`
**Hva:** Håndterer ekspanderbart innhold og accordion-funksjonalitet.

**Bruk:**
```html
<div class="expand-content" data-size="220px">
    <!-- Content here -->
</div>
```

**Funksjoner:**
- Automatisk "Vis mer" knapp hvis innhold er høyere enn `data-size`
- Smooth expand/collapse animasjon
- Accordion-funksjonalitet for kurslister

**Dependency:** Vanilla JavaScript (ingen avhengigheter)

---

### 2. `course-dates-modal.js` 
**Hva:** Popup-modal for visning av alle kursdatoer og kurssteder.

**Bruk:**
```html
<!-- Trigger link -->
<a href="#" class="show-all-dates-link" data-course-id="123">
    (+5 flere datoer)
</a>

<!-- Modal structure -->
<div class="ka-course-dates-modal" id="modal-123" style="display: none;">
    <div class="ka-modal-overlay"></div>
    <div class="ka-modal-content">
        <div class="ka-modal-header">
            <h3>Kurstittel</h3>
            <button class="ka-modal-close" aria-label="Lukk">&times;</button>
        </div>
        <div class="ka-modal-body">
            <!-- Content here -->
        </div>
    </div>
</div>
```

**Funksjoner:**
- Åpner modal ved klikk på `.show-all-dates-link`
- Lukker modal med X-knapp, klikk utenfor, eller ESC-tast
- Hindrer scrolling av bakgrunn når modal er åpen
- Custom events: `ka:modal:opened` og `ka:modal:closed`

**Dependency:** jQuery

**Styling:** Se `frontend-course-style.css` (COURSE DATES MODAL seksjon)

---

### 3. `course-slidein-panel.js`
**Hva:** Slide-in panel for påmeldingsskjema (iframe fra Kursagenten).

**Dependency:** jQuery, iframeResizer

---

### 4. `course-ajax-filter.js`
**Hva:** AJAX-baserte filtre for kurslister (lokasjon, kategori, språk, måned, osv).

**Dependency:** jQuery

---

## Registrering

Alle public scripts er registrert i `kursagenten.php` under `kursagenten_enqueue_scripts()` funksjonen.

```php
wp_enqueue_script('kursagenten-expand-content', ...);
wp_enqueue_script('kursagenten-dates-modal', ...);
wp_enqueue_script('kursagenten-ajax-filter', ...);
```

Scripts lastes kun på relevante sider (kurs-sider, taksonomi-sider, og sider med kortkoder).

## Development

### Testing
Test alle moduler på:
- Single course pages
- Archive/taxonomy pages  
- Pages med `[kursliste]` kortkode
- Mobil og desktop

### Debugging
Aktiver browser console for å se events og feilmeldinger:
```javascript
$(document).on('ka:modal:opened', function(e, data) {
    console.log('Modal opened for course:', data.courseId);
});
```

## Best Practices

1. ✅ **Gjenbrukbar kode** - Bygg modulære funksjoner
2. ✅ **Event delegation** - Bruk `$(document).on()` for dynamisk innhold
3. ✅ **Namespacing** - Bruk prefixes (`ka-`, `kursagenten-`)
4. ✅ **Accessibility** - Legg til ARIA-labels og keyboard support
5. ✅ **Custom events** - Trigger events for tracking/analytics

## Changelog

### 2024-11-02
- Opprettet `course-dates-modal.js` - Gjenbrukbar modal for kursdatoer
- Flyttet CSS til `frontend-course-style.css`
- Registrert globalt i `kursagenten.php`


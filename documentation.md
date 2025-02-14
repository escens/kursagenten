# Kursagenten WordPress Plugin

## Innholdsfortegnelse
1. [Oversikt](#oversikt)
2. [Installasjon](#installasjon)
3. [Struktur](#struktur)
4. [Funksjonalitet](#funksjonalitet)
5. [API Integrasjon](#api-integrasjon)
6. [Custom Post Types](#custom-post-types)
7. [Hooks og Filters](#hooks-og-filters)
8. [Frontend](#frontend)
9. [Administrasjon](#administrasjon)

## Oversikt
Kursagenten er en WordPress-plugin som integrerer kursdata fra Kursagenten API og gir mulighet for å vise og administrere kurs på WordPress-nettsteder. Pluginen håndterer automatisk synkronisering av kursdata, visning av kurs på frontend, og gir administrative verktøy for å administrere kursinnhold.

## Installasjon
1. Last opp plugin-mappen til `/wp-content/plugins/`
2. Aktiver pluginen i WordPress admin
3. Konfigurer API-nøkler under Innstillinger > Kursagenten
4. Kjør initial synkronisering av kursdata

## Struktur
```
kursagenten/
├── assets/
│   ├── css/
│   │   ├── components/
│   │   ├── layouts/
│   │   └── modules/
│   ├── js/
│   │   ├── admin/
│   │   │   ├── components/
│   │   │   └── modules/
│   │   └── frontend/
│   │       ├── components/ 
│   │       └── modules/
│   ├── icons/
│   │   ├── admin/
│   │   └── frontend/
│   ├── includes/
│   │   ├── api/
│   │   │   ├── endpoints/
│   │   │   ├── controllers/
│   │   │   └── models/

│   │   ├── admin/
│   │   │   ├── views/
│   │   │   ├── controllers/
│   │   │   └── helpers/
│   │   └── frontend/
│   │       ├── widgets/
│   │       ├── shortcodes/
│   │       └── blocks/
│   ├── templates/
│   │   ├── partials/
│   │   │   ├── course/
│   │   │   └── forms/
│   │   ├── admin/
│   │   └── emails/
│   └── languages/
│       ├── nb_NO/
│       ├── nn_NO/
│       └── en_US/

## Funksjonalitet

### Hovedfunksjoner
- Automatisk synkronisering av kursdata fra Kursagenten API
- Custom post type for kurs med tilpassede felter
- Avansert søk og filtrering av kurs
- Påmeldingssystem integrert med Kursagenten
- Webhook-håndtering for oppdateringer
- Flerspråklig støtte
- Tilpassbar frontend-visning

### Kursadministrasjon
- Manuell og automatisk synkronisering av kurs
- Redigering av kursinformasjon
- Administrasjon av kurskategorier og tags
- Håndtering av kursdatoer og påmeldinger

## API Integrasjon

### Kursagenten API
- REST API-endepunkter for kursdata
- Autentisering med API-nøkler
- Automatisk synkronisering via cron-jobber
- Feilhåndtering og logging

### Webhooks
- Mottak av sanntidsoppdateringer
- Validering av webhook-signaturer
- Håndtering av ulike webhook-hendelser:
  - Nye kurs
  - Oppdateringer
  - Sletting
  - Påmeldinger

## Custom Post Types

### Kurs (course)
```php
'supports' => [
    'title',
    'editor',
    'thumbnail',
    'excerpt',
    'custom-fields'
]
```

### Metadata-felter
- course_id: Unik ID fra Kursagenten
- start_date: Kursets startdato
- end_date: Kursets sluttdato
- location: Kurssted
- price: Kurspris
- available_seats: Tilgjengelige plasser
- course_type: Kurstype
- instructor: Kursholder

## Hooks og Filters

### Actions
```php
// Før kurssynkronisering
do_action('ka_before_course_sync');

// Etter kurssynkronisering
do_action('ka_after_course_sync');

// Ved påmelding
do_action('ka_course_registration', $course_id, $user_data);
```

### Filters
```php
// Modifiser kursdata før lagring
apply_filters('ka_course_data', $course_data);

// Tilpass søkeresultater
apply_filters('ka_search_results', $results);
```

## Frontend

### Shortcodes
```php
[ka_course_list] // Viser kursliste
[ka_course_calendar] // Viser kurskalender
[ka_course_search] // Viser søkeskjema
```

### Templates
- archive-course.php: Kursoversikt
- single-course.php: Enkelt kurs
- course-calendar.php: Kurskalender
- course-search.php: Søkeside

### Tilpasning
- Støtte for tilpassede temaer
- CSS-variabler for styling
- Responsivt design
- Tilpassbare maler

## Administrasjon

### Innstillinger
- API-konfigurasjon
- Synkroniseringsinnstillinger
- Visningsalternativer
- Påmeldingsinnstillinger

### Verktøy
- Manuell synkronisering
- Feilsøkingslogg
- Cache-administrasjon
- Dataeksport/import

### Sikkerhet
- API-nøkkel validering
- Webhook-signaturverifisering
- Brukerrettigheter
- GDPR-samsvar

## Feilsøking

### Vanlige problemer
1. API-tilkoblingsfeil
2. Synkroniseringsproblemer
3. Webhook-feil
4. Visningsproblemer

### Logging
- Feillogging i wp-content/debug.log
- API-respons logging
- Synkroniseringslogg
- Påmeldingslogg

## Ytelse

### Caching
- Transient API for kursdata
- Objektcaching for søkeresultater
- Sidecaching-kompatibilitet

### Optimalisering
- Lazy loading av bilder
- Minifierte ressurser
- Database-indeksering
- API-resultat caching
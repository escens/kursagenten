# Kursagenten - Meta Fields Migration til ka_ prefix

## Oversikt

Alle metafelter i Kursagenten er nå oppdatert til å bruke `ka_` prefix for å unngå konflikter med andre plugins og følge WordPress best practices.

## Endringer

### 📊 Totalt 48 metafelter oppdatert

#### Felter uten prefix → `ka_` prefix:
- `location_id` → `ka_location_id`
- `button-text` → `ka_button_text`
- `main_course_id` → `ka_main_course_id`
- `is_parent_course` → `ka_is_parent_course`
- `main_course_title` → `ka_main_course_title`
- `sub_course_location` → `ka_sub_course_location`
- `schedule_id` → `ka_schedule_id`
- `meta_description` → `ka_meta_description`
- `is_active` → `ka_is_active`

#### Felter med `course_` prefix → `ka_course_` prefix:
- Alle 39 `course_*` felter har nå `ka_course_*` prefix
- Eksempler: `course_price` → `ka_course_price`, `course_title` → `ka_course_title`

Se `META_FIELDS_MAPPING.md` for komplett liste.

## Oppdaterte filer

### ✅ Kernefiler (API & Data):
- `includes/api/api_course_sync.php` - API synkronisering
- `public/templates/includes/queries.php` - Alle søk og filter-funksjoner

### ✅ Template-filer:
- `public/templates/designs/single/default.php` - Single course visning
- `public/templates/list-types/standard.php` - Standard liste
- `public/templates/list-types/grid.php` - Grid liste
- `public/templates/list-types/compact.php` - Kompakt liste  
- `public/templates/list-types/plain.php` - Plain liste

### ✅ Migreringsscript:
- `includes/migrations/migrate-meta-fields.php` - Database migreringsscript

## Hvordan migrere eksisterende data

### Steg 1: Backup
**VIKTIG:** Ta backup av databasen din før du kjører migreringen!

```sql
-- Eksempel backup kommando:
mysqldump -u bruker -p databasenavn > backup.sql
```

### Steg 2: Kjør migrering
1. Logg inn i WordPress admin
2. Gå til **Verktøy → Kursagenten Migration**
3. Klikk på "Kjør migrering"

Migreringen vil:
- Oppdatere alle metafelter på alle kurs og kursdatoer
- Være sikker å kjøre flere ganger (idempotent)
- Logge alle endringer i WordPress debug log
- Vise en oppsummering når ferdig

### Steg 3: Verifiser
Etter migreringen, sjekk at:
- Kurs vises korrekt på nettsiden
- Kursdatoer vises korrekt
- Filtre og søk fungerer
- API-synkronisering fungerer

## Teknisk informasjon

### Database oppdatering
Migreringen kjører følgende SQL for hvert metafelt:

```sql
UPDATE wp_postmeta 
SET meta_key = 'ka_[old_key]' 
WHERE meta_key = '[old_key]'
```

### Ytelse
- Migreringen kan ta 1-5 minutter avhengig av antall kurs
- Cirka 48 SQL-oppdateringer per kurs/kursdato
- Ikke avbryt prosessen mens den kjører

### Logging
Alle migreringer logges til WordPress debug log hvis aktivert:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Feilsøking

### Problem: Migreringen henger
**Løsning:** Øk PHP memory limit og max execution time:
```php
ini_set('memory_limit', '512M');
ini_set('max_execution_time', '300');
```

### Problem: Noen felter ikke oppdatert
**Løsning:** Sjekk debug log for feilmeldinger. Kjør migreringen på nytt - den er idempotent.

### Problem: Kurs vises ikke etter migrering
**Løsning:** 
1. Kjør API-synkronisering på nytt
2. Tøm WordPress cache
3. Sjekk at alle template-filer er oppdatert

## Kompatibilitet

### Bakoverkompatibilitet
⚠️ **Ikke bakoverkompatibel** - Gamle metafeltnavn vil ikke lenger fungere etter migreringen.

### Fremoverkompatibilitet
✅ Nye kurs fra API vil automatisk bruke de nye metafeltnavnene.

## Support

Ved problemer eller spørsmål:
1. Sjekk debug log
2. Kjør migrering på nytt
3. Kontakt utvikler

## Changelog

### 2024-11-02
- Migrert alle 48 metafelter til ka_ prefix
- Laget migreringsscript for eksisterende data
- Oppdatert alle template-filer
- Oppdatert API sync og queries

## Vedlikeholdernotater

Ved fremtidige oppdateringer, sørg for at:
- Alle nye metafelter bruker `ka_` prefix
- Metafeltnavn er dokumentert i `META_FIELDS_MAPPING.md`
- Migreringsscriptet oppdateres hvis nye felter legges til


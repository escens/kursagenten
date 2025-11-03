# Course Days Funksjonalitet

## Oversikt

Denne funksjonaliteten legger til et nytt metafelt `course_days` på post type `ka_coursedate` som automatisk fylles med informasjon om hvilken ukedag første kursdato er på.

## Hvordan det fungerer

### 1. Automatisk oppdatering
- Når kursdata synkroniseres fra API, sjekkes `coursetime` og `firstCourseDate` feltene
- Hvis `coursetime` starter med "Kl" og har formatet "Kl HH:MM - HH:MM", beregnes ukedagen
- Ukedagen lagres i `course_days` metafeltet på norsk (Mandag, Tirsdag, Onsdag, etc.)

### 2. Format-validering
Funksjonen validerer at `coursetime` har riktig format:
- ✅ `Kl 08:08 - 11:11` (gyldig)
- ✅ `Kl 09:00 - 16:00` (gyldig)
- ❌ `Fredager 10.00-12.00` (ugyldig - starter ikke med "Kl")
- ❌ `Mandager 09:00-17:00` (ugyldig - starter ikke med "Kl")

### 3. Dato-parsing
- `firstCourseDate` må være i ISO 8601 format (f.eks. "2025-10-10T00:00:00")
- Ukedagen beregnes basert på denne datoen
- Hvis datoen er ugyldig, settes `course_days` til tom

## Teknisk implementering

### Filer
- `includes/helpers/course_days_helper.php` - Hovedfunksjonalitet
- `includes/api/api_course_sync.php` - Integrering i API-synkronisering

### Hovedfunksjoner
- `get_course_days_from_coursetime($coursetime, $firstCourseDate)` - Hovedfunksjon
- `is_valid_coursetime_format($coursetime)` - Format-validering

### Database
- Metafeltet `course_days` lagres automatisk i WordPress sin `wp_postmeta` tabell
- Ingen endringer i database-skjema kreves

## Eksempler

### Eksempel 1: Gyldig format
```json
{
  "coursetime": "Kl 08:08 - 11:11",
  "firstCourseDate": "2025-10-10T00:00:00"
}
```
**Resultat:** `course_days` = "Fredag" (10. oktober 2025 er en fredag)

### Eksempel 2: Ugyldig format
```json
{
  "coursetime": "Fredager 10.00-12.00",
  "firstCourseDate": "2025-10-10T00:00:00"
}
```
**Resultat:** `course_days` = tom (starter ikke med "Kl")

### Eksempel 3: Ugyldig dato
```json
{
  "coursetime": "Kl 08:08 - 11:11",
  "firstCourseDate": "invalid-date"
}
```
**Resultat:** `course_days` = tom (ugyldig dato-format)

## Testing

For å teste funksjonaliteten:
1. Sett `WP_DEBUG = true` i `wp-config.php`
2. Gå til WordPress admin
3. Test-resultatene vises som admin-notices

## Bruk i templates

```php
$course_days = get_post_meta($coursedate_id, 'course_days', true);
if (!empty($course_days)) {
    echo '<div class="course-days">Kursdag: ' . esc_html($course_days) . '</div>';
}
```

## Vedlikehold

- Test-filen bør fjernes i produksjon
- Funksjonaliteten kjøres automatisk ved hver API-synkronisering
- Ingen manuell vedlikehold kreves

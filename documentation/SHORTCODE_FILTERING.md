# Kursliste Shortcode - Filtrering

## Oversikt

Kursliste-kortkoden `[kursliste]` har nå støtte for direkte filtrering via kortkode-parametere. Dette gjør det mulig å vise spesifikke kurs basert på kategorier, steder, instruktører, språk og måneder uten å vise filter-grensesnittet.

## Bruk

### Grunnleggende syntaks

```php
[kursliste]
```

### Filtrering med parametere

```php
[kursliste kategori="dans"]
[kursliste sted="bærum"]
[kursliste kategori="dans" sted="oslo"]
[kursliste språk="norsk"]
[kursliste måned="01"]
[kursliste instruktør="john-doe"]
```

**Merk:** Du kan bruke både med og uten anførselstegn:
```php
[kursliste måned=9]        // Uten anførselstegn
[kursliste måned="9"]      // Med anførselstegn
[kursliste språk=norsk]    // Uten anførselstegn
[kursliste språk="norsk"]  // Med anførselstegn
[kursliste kategori=web]   // Uten anførselstegn
[kursliste kategori="web"] // Med anførselstegn
```

## Tilgjengelige parametere

| Parameter | Beskrivelse | Eksempel |
|-----------|-------------|----------|
| `kategori` | Filtrer på kurskategori (ka_coursecategory taxonomy) | `kategori="dans"` |
| `sted` | Filtrer på kurssted (ka_course_location taxonomy) | `sted="bærum"` |
| `instruktør` | Filtrer på instruktør (ka_instructors taxonomy) | `instruktør="john-doe"` |
| `språk` | Filtrer på kursets språk (course_language meta) | `språk="norsk"` |
| `måned` | Filtrer på måned (course_month meta) | `måned="9"` |

## Eksempler på bruk

### 1. Vis alle kurs
```php
[kursliste]
```

### 2. Vis kun dansekurs
```php
[kursliste kategori="dans"]
```

### 3. Vis kun kurs i Bærum
```php
[kursliste sted="bærum"]
```

### 4. Vis dansekurs i Oslo
```php
[kursliste kategori="dans" sted="oslo"]
```

### 5. Vis kun norske kurs
```php
[kursliste språk="norsk"]
```

### 6. Vis kun kurs i september
```php
[kursliste måned="9"]
```

### 7. Kombinere flere filtre
```php
[kursliste kategori="dans" sted="oslo" språk="norsk"]
```

### 8. Vis kurs i spesifikke måneder
```php
[kursliste måned="9"]     // September
[kursliste måned="10"]    // Oktober
[kursliste måned="12"]    // Desember
```

## Teknisk informasjon

### Hvordan det fungerer

1. Kortkoden parser attributtene og setter dem som `$_REQUEST` og `$_GET` parametere
2. Den eksisterende `get_course_dates_query()` funksjonen håndterer filtreringen
3. Filter-grensesnittet skjules når kortkode-parametere er satt
4. En informasjonsboks vises med aktive filtre
5. Kortkode-parametrene sendes til JavaScript og bevares ved paginering og sortering
6. URL-parametrene oppdateres automatisk med kortkode-parametrene

### Verdier

- **Kategorier**: Bruk taxonomy slug (f.eks. "dans", "musikk", "kunst")
- **Steder**: Bruk taxonomy slug eller navn (f.eks. "bærum", "oslo")
- **Instruktører**: Bruk taxonomy slug (f.eks. "john-doe", "jane-smith")
- **Språk**: Bruk språknavn (f.eks. "norsk", "engelsk") - konverteres automatisk til lowercase
- **Måneder**: Bruk månedsnummer (f.eks. "9" for september, "12" for desember) - konverteres automatisk til padded format

### Kompatibilitet

- Fungerer med eksisterende filter-system
- Støtter paginering med bevaring av filtre
- Støtter sortering med bevaring av filtre
- Fungerer med AJAX-oppdateringer
- Kompatibel med alle eksisterende temaer
- Kortkode-parametere bevares ved navigasjon og sortering

## CSS-styling

Kortkoden inkluderer CSS-styling for filter-informasjonen:

```css
.shortcode-filters-info {
    background-color: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.shortcode-active-filters li {
    display: inline-block;
    background-color: #007cba;
    color: white;
    padding: 5px 12px;
    border-radius: 20px;
    margin: 0 10px 10px 0;
    font-size: 14px;
}
```

## Feilsøking

### Vanlige problemer

1. **Ingen kurs vises**: Sjekk at verdiene matcher faktiske taxonomy slugs eller meta verdier
2. **Filtre vises ikke**: Sjekk at parameterne er riktig skrevet (inkludert æ, ø, å)
3. **Feil encoding**: Bruk UTF-8 encoding for norske tegn

### Debugging

Aktiver WordPress debug logging for å se eventuelle feil:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Oppdateringer

- **v1.0**: Grunnleggende filtrering implementert
- **v1.1**: Paginering og sortering med bevaring av filtre
- Støtte for alle hovedfiltre
- Automatisk skjuling av filter-grensesnitt
- Informasjonsboks for aktive filtre
- Kortkode-parametere bevares ved navigasjon og sortering
- URL-parametrene oppdateres automatisk

# 🔍 Debug: "Neste kurs" vises ikke for noen kurs

## Problem
Noen kurs viser "På forespørsel" i stedet for "Neste kurs: [dato]", selv om det finnes mange kursdatoer.

**Eksempel:**
- Kurs: G11 Stroppe- og anhukerkurs – helg – Moss
- Status: "På forespørsel"
- Faktisk: 29 kursdatoer tilgjengelig (vises i lokasjonslisten)

## Årsaksanalyse

### Logikk for "Neste kurs" (main_courses view)

I `standard.php` linje 18-34:
```php
$location_id = get_post_meta($course_id, 'ka_location_id', true);

$related_coursedates = get_posts([
    'post_type' => 'ka_coursedate',
    'posts_per_page' => -1,
    'meta_query' => [
        ['key' => 'ka_main_course_id', 'value' => $location_id],
    ],
]);

$related_coursedate_ids = array_map(function($post) {
    return $post->ID;
}, $related_coursedates);

$selected_coursedate_data = get_selected_coursedate_data($related_coursedate_ids);
$first_course_date = $selected_coursedate_data['first_date'] ?? '';
```

### Potensielle problemer

#### 1. Hovedkurs vs Underkurs mismatch
**Problem:** Du viser sannsynligvis **hovedkurs** (is_parent_course = 'yes') på taksonomi-sider, men:
- Hovedkursets `ka_location_id` = hovedkurs sitt API-ID (f.eks. 12345)
- Kursdatoene har `ka_main_course_id` = hovedkurs sitt API-ID
- Dette skal matche, MEN...

**Hvis dette er et UNDERKURS som vises:**
- Underkursets `ka_location_id` = underkurs sitt API-ID (f.eks. 12346 - forskjellig fra 12345)
- Kursdatoene har fortsatt `ka_main_course_id` = 12345 (hovedkursets ID)
- De matcher IKKE → ingen kursdatoer funnet → "På forespørsel"

#### 2. Skjulte kursdatoer
`get_selected_coursedate_data()` filtrerer ut kursdatoer med skjulte termer.

**Mulig scenario:**
- 29 kursdatoer eksisterer
- Alle har skjult term (f.eks. "skjult" kategori)
- `get_selected_coursedate_data()` returnerer tomt
- "På forespørsel" vises

#### 3. Delvis synkronisering
Hvis synkroniseringen stoppet midtveis (pga syntaksfeil):
- Noen kurs er opprettet
- Men deres kursdatoer er IKKE opprettet
- Eller kursdatoer mangler riktig `ka_main_course_id`

## Løsning

### Test 1: Er dette et hovedkurs eller underkurs?
```sql
-- Finn dette kurset i databasen (søk på tittel)
SELECT p.ID, p.post_title, 
       pm1.meta_value as location_id,
       pm2.meta_value as main_course_id,
       pm3.meta_value as is_parent_course
FROM wp_posts p
LEFT JOIN wp_postmeta pm1 ON (p.ID = pm1.post_id AND pm1.meta_key = 'ka_location_id')
LEFT JOIN wp_postmeta pm2 ON (p.ID = pm2.post_id AND pm2.meta_key = 'ka_main_course_id')
LEFT JOIN wp_postmeta pm3 ON (p.ID = pm3.post_id AND pm3.meta_key = 'ka_is_parent_course')
WHERE p.post_type = 'ka_course' 
AND p.post_title LIKE '%Stroppe%'
LIMIT 5;
```

### Test 2: Finn kursdatoer for dette kurset
```sql
-- Bruk location_id fra Test 1
SELECT COUNT(*) FROM wp_postmeta 
WHERE meta_key = 'ka_main_course_id' 
AND meta_value = '[location_id fra Test 1]';

-- Se alle kursdatoer for dette kurset
SELECT p.ID, p.post_title,
       pm1.meta_value as location_id,
       pm2.meta_value as main_course_id,
       pm3.meta_value as first_date
FROM wp_posts p
LEFT JOIN wp_postmeta pm1 ON (p.ID = pm1.post_id AND pm1.meta_key = 'ka_location_id')
LEFT JOIN wp_postmeta pm2 ON (p.ID = pm2.post_id AND pm2.meta_key = 'ka_main_course_id')
LEFT JOIN wp_postmeta pm3 ON (p.ID = pm3.post_id AND pm3.meta_key = 'ka_course_first_date')
WHERE p.post_type = 'ka_coursedate'
AND pm2.meta_value = '[location_id fra Test 1]'
LIMIT 10;
```

### Test 3: Sjekk skjulte kategorier
```sql
-- Finn skjulte kategorier
SELECT t.name, t.slug 
FROM wp_terms t
JOIN wp_term_taxonomy tt ON t.term_id = tt.term_id
WHERE tt.taxonomy = 'ka_coursecategory'
AND t.slug IN ('skjult', 'skjul', 'usynlig', 'inaktiv', 'ikke-aktiv');

-- Sjekk om kursdatoene har skjulte kategorier
SELECT p.ID, p.post_title, GROUP_CONCAT(t.name) as categories
FROM wp_posts p
JOIN wp_term_relationships tr ON p.ID = tr.object_id
JOIN wp_term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
JOIN wp_terms t ON tt.term_id = t.term_id
WHERE p.post_type = 'ka_coursedate'
AND tt.taxonomy = 'ka_coursecategory'
AND t.slug IN ('skjult', 'skjul', 'usynlig')
GROUP BY p.ID;
```

## Rask fix: Kjør synkronisering på nytt

Den enkleste løsningen er å **kjøre en fullstendig synkronisering på nytt**:

1. Gå til **Oversikt** eller **Kursinnstillinger**
2. Klikk "Hent alle kurs fra Kursagenten"
3. Vent til den er 100% ferdig
4. Dette vil re-synkronisere alle kurs og sikre at:
   - Alle metafelter er korrekte
   - Alle relasjoner er satt
   - Alle kursdatoer er koblet til riktig kurs

## Alternativ: Debug spesifikt kurs

Legg til denne koden midlertidig i `standard.php` rett etter linje 31:

```php
// DEBUG: Vis hva som skjer
if (strpos($course_title, 'Stroppe') !== false) {
    error_log("=== DEBUG: $course_title ===");
    error_log("Course ID: $course_id");
    error_log("Location ID: $location_id");
    error_log("Related coursedates found: " . count($related_coursedates));
    error_log("Related coursedate IDs: " . print_r($related_coursedate_ids, true));
    error_log("Selected coursedate data: " . print_r($selected_coursedate_data, true));
}
```

Dette vil logge detaljert info for akkurat dette kurset til `debug.log`.

## Forventet årsak

Mest sannsynlig er dette et av disse scenariene:

1. **Delvis synkronisering:** Kurset ble opprettet før syntaksfeilen, men kursdatoer ble ikke koblet riktig
2. **Underkurs vises:** Det er et underkurs som vises, men logikken forventer hovedkurs
3. **Skjulte kursdatoer:** Alle kursdatoer har skjult kategori

**Løsning for alle:** Kjør fullstendig synkronisering på nytt! 🔄

---

**TL;DR:** Kjør synkronisering på nytt for å sikre at alle relasjoner mellom kurs og kursdatoer er korrekte.


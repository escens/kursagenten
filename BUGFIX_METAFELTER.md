# 🐛 Bugfix: Manglende metafelt-oppdateringer

## Problem
Etter metafelt-migrering til `ka_` prefix viste kurs-sider og taksonomi-sider: 
**"Ingen kurs tilgjengelige for øyeblikket"**

## Årsaker funnet og fikset

### 1. 🔴 KRITISK: Syntaksfeil i api_course_sync.php
- **Linje 1:** `is <?php` → `<?php`
- Dette gjorde at hele filen feilet å laste
- **Status:** ✅ FIKSET

### 2. 🔴 Meta_key i meta_query arrays ikke oppdatert (queries.php)
Mange `meta_query` arrays i `queries.php` brukte fortsatt gamle metafeltnavn:

**Fikset følgende meta_key referanser:**
- `'key' => 'course_location'` → `'key' => 'ka_course_location'`
- `'key' => 'course_language'` → `'key' => 'ka_course_language'`
- `'key' => 'course_first_date'` → `'key' => 'ka_course_first_date'`
- `'key' => 'course_month'` → `'key' => 'ka_course_month'`
- `'key' => 'course_price'` → `'key' => 'ka_course_price'`
- `'key' => 'course_title'` → `'key' => 'ka_course_title'`
- `'key' => 'course_description'` → `'key' => 'ka_course_description'`
- `'key' => 'location_id'` → `'key' => 'ka_location_id'`
- `'key' => 'main_course_id'` → `'key' => 'ka_main_course_id'`
- `'key' => 'is_parent_course'` → `'key' => 'ka_is_parent_course'`
- `'key' => 'course_location_freetext'` → `'key' => 'ka_course_location_freetext'`

**I SQL JOIN statements:**
- `meta_key = 'course_first_date'` → `meta_key = 'ka_course_first_date'`
- `meta_key = 'course_price'` → `meta_key = 'ka_course_price'`

### 3. 🔴 course_relationships.php (manglende oppdatering)
- **Status:** ✅ FIKSET
- Alle relasjons-metafelter oppdatert

### 4. 🔴 api_sync_on_demand.php (cleanup-funksjoner)
- **Status:** ✅ FIKSET
- Cleanup-queries oppdatert til nye metafeltnavn

## Funksjoner oppdatert i queries.php

1. `get_course_dates_query()` - Hoved query-funksjon
2. `get_course_info_by_location()` - Finn kurs basert på location_id
3. `get_courses_for_taxonomy()` - Taksonomi-sider
4. `display_course_locations()` - Vis lokasjoner på enkelt-kurs
5. `get_course_dates_query_for_count()` - Telling for filtre

## Testing

### Før fix:
```
Resultat: "Ingen kurs tilgjengelige for øyeblikket"
Debug log: Stopper etter "Bygget liste med 155 kurs"
```

### Etter fix:
```
Forventet resultat:
- Kurs vises på taksonomi-sider
- Kurs vises på kortkode-sider  
- Filtre fungerer
- Søk fungerer
```

## Verifisering

Etter oppdatering, sjekk:
- [ ] Kurs vises på `/kurs/`
- [ ] Taksonomi-sider viser kurs (f.eks. `/kurskategori/dans/`)
- [ ] Kortkoder viser kurs (`[kursliste]`)
- [ ] Filtre fungerer (lokasjon, kategori, språk, måned)
- [ ] Søk fungerer
- [ ] Enkelt-kurs sider viser korrekt info

## Læring

**Problem:** Når du søker og erstatter metafeltnavn, må du også oppdatere:
1. ✅ `get_post_meta()` kall
2. ✅ `update_post_meta()` kall
3. ✅ **Meta_query arrays** (`'key' => '...'`)
4. ✅ **SQL JOIN statements** (`meta_key = '...'`)
5. ✅ **Orderby meta_key** statements

Disse var lett å glemme fordi de ikke alltid bruker `get_post_meta()` funksjonen direkte.

## Oppsummering

**Totalt 24+ meta_key referanser** i `queries.php` som var glemt og nå er fikset.

Disse påvirket:
- Filtrering av kurs (lokasjon, språk, måned, pris)
- Sortering av kurs (dato, pris, tittel)
- Søk i kurs
- Taksonomi-sider
- Relasjoner mellom hovedkurs og underkurs

**Status:** ✅ ALT FIKSET!


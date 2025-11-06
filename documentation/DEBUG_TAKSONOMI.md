# 🔍 Debug: Taksonomi-sider viser "Ingen kurs"

## Problem
Taksonomi-sider og kortkode-sider viser: **"Ingen kurs tilgjengelige for øyeblikket"**

## Diagnose

### Mulig årsak 1: Kurs ikke synkronisert riktig
Siden du resatt databasen og fikk syntaksfeil under første synk, kan det hende:
- Kurs ble delvis opprettet
- Metafelter mangler
- Relasjoner mellom kurs og kursdatoer mangler

**Test:**
```sql
-- Sjekk om det finnes kurs i databasen
SELECT COUNT(*) FROM wp_posts WHERE post_type = 'ka_course' AND post_status = 'publish';

-- Sjekk om det finnes kursdatoer
SELECT COUNT(*) FROM wp_posts WHERE post_type = 'ka_coursedate' AND post_status = 'publish';

-- Sjekk om metafelter eksisterer
SELECT COUNT(*) FROM wp_postmeta WHERE meta_key LIKE 'ka_%';

-- Sjekk om location_id eksisterer
SELECT COUNT(*) FROM wp_postmeta WHERE meta_key = 'ka_location_id';
```

### Mulig årsak 2: Taksonomi-termer ikke koblet til kurs
Kurs må være koblet til taksonomiene for å vises på taksonomi-sider.

**Test:**
```sql
-- Sjekk taksonomi-relasjoner
SELECT COUNT(*) FROM wp_term_relationships 
WHERE object_id IN (SELECT ID FROM wp_posts WHERE post_type = 'ka_course');

-- Sjekk spesifikke taksonomier
SELECT t.name, tt.taxonomy, COUNT(*) as count
FROM wp_term_relationships tr
JOIN wp_term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
JOIN wp_terms t ON tt.term_id = t.term_id
WHERE tr.object_id IN (SELECT ID FROM wp_posts WHERE post_type = 'ka_course')
GROUP BY tt.taxonomy;
```

### Mulig årsak 3: is_parent_course filter
Taksonomi-sider filtrerer på `is_parent_course = 'yes'` for å vise bare hovedkurs.

Hvis hovedkurs ikke er opprettet korrekt, vil ingen kurs vises.

**Test:**
```sql
-- Sjekk hvor mange hovedkurs som finnes
SELECT COUNT(*) FROM wp_postmeta 
WHERE meta_key = 'ka_is_parent_course' AND meta_value = 'yes';

-- Sjekk hvor mange underkurs som finnes
SELECT COUNT(*) FROM wp_posts p
WHERE p.post_type = 'ka_course' 
AND p.ID NOT IN (
    SELECT post_id FROM wp_postmeta 
    WHERE meta_key = 'ka_is_parent_course' AND meta_value = 'yes'
);
```

## Løsning

### Steg 1: Slett alle kurs og start på nytt
```sql
-- ADVARSEL: Dette sletter ALT! Kjør kun hvis du vil starte helt på nytt

-- Slett alle kurs
DELETE FROM wp_posts WHERE post_type IN ('ka_course', 'ka_coursedate');
DELETE FROM wp_postmeta WHERE post_id NOT IN (SELECT ID FROM wp_posts);
DELETE FROM wp_term_relationships WHERE object_id NOT IN (SELECT ID FROM wp_posts);

-- Eller via WP Admin: Deaktiver plugin, reaktiver plugin, kjør synk på nytt
```

### Steg 2: Kjør FULL synkronisering på nytt
1. Sørg for at det ikke er noen PHP-feil
2. Sjekk at API-nøkkel er satt
3. Kjør "Hent alle kurs fra Kursagenten"
4. Vent til den er 100% ferdig
5. Sjekk debug.log for feil

### Steg 3: Verifiser i debug.log
Du skal se:
```
=== START: create_or_update_course_and_schedule function ===
Location ID: [nummer], Main Course ID: [nummer]
...
=== SLUTT: create_or_update_course_and_schedule ===
```

For hvert kurs (155 ganger).

### Steg 4: Verifiser i database
```sql
-- Skal returnere > 0
SELECT COUNT(*) FROM wp_posts WHERE post_type = 'ka_course' AND post_status = 'publish';

-- Skal returnere > 0  
SELECT COUNT(*) FROM wp_postmeta WHERE meta_key = 'ka_location_id';

-- Skal returnere > 0
SELECT COUNT(*) FROM wp_postmeta WHERE meta_key = 'ka_is_parent_course' AND meta_value = 'yes';
```

## Debug checklist

Hvis kurs fortsatt ikke vises:

- [ ] Kjør syntaks-sjekk på alle PHP-filer (se om det er flere syntaksfeil)
- [ ] Sjekk at alle metafelter er opprettet (SQL query over)
- [ ] Sjekk at taksonomi-relasjoner eksisterer
- [ ] Sjekk at is_parent_course er satt korrekt
- [ ] Send meg komplett debug.log fra en synkronisering

## Neste steg

1. **Kjør synkronisering på nytt** (siden første gang feilet pga syntaksfeil)
2. **Vent til den er 100% ferdig**
3. **Sjekk debug.log** - se om den faktisk oppretter kurs
4. **Test taksonomi-sider** igjen

---

**Viktig:** Den første synkroniseringen feilet pga syntaksfeil, så kurs ble **ikke** opprettet korrekt. Du må kjøre en ny, fullstendig synkronisering! 🔄


# ✅ Verifisering etter database-reset og metafelt-oppdatering

## Problem du opplevde
- Feilmelding: "Kunne ikke hente kursdata. Timeout eller nettverksfeil."
- Dette skjedde etter database-reset og plugin-reaktivering

## Mulige årsaker (og løsninger)

### 1. ❗ Lisensnøkkel mangler (MEST SANNSYNLIG)
Siden du resatt databasen, er alle innstillinger borte, inkludert Lisensnøkkelen.

**Løsning:**
1. Gå til: **Innstillinger → Kursinnstillinger**
2. Under "API-innstillinger":
   - Legg inn **Tilbyder-GUID** (API-nøkkel)
   - Legg inn **Tilbyder-ID**
3. Lagre innstillingene
4. Prøv synkronisering på nytt

### 2. 🔌 Nettverkstimeout
API-kallet kan timeout hvis serveren er treg eller har streng firewall.

**Løsning:**
- Øk PHP timeout i `wp-config.php`:
```php
define('WP_HTTP_BLOCK_EXTERNAL', false);
set_time_limit(300);
ini_set('max_execution_time', '300');
```

### 3. 🐛 WordPress Debug Log
For å se nøyaktig hva som feiler, aktiver debug-logging:

**I `wp-config.php`:**
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

**Sjekk deretter:**
`/wp-content/debug.log` - se etter feilmeldinger fra Kursagenten

### 4. ✅ Test API-tilkobling direkte
Legg til denne kortkoden på en side for å teste API-tilkobling:

```
[test_kursagenten_api]
```

Dette vil vise om API-et svarer eller ikke.

## Verifisering etter at API-nøkkel er satt

### Steg 1: Test API-tilkobling
- [ ] API-nøkkel er satt i innstillinger
- [ ] API svarer (bruk `[test_kursagenten_api]` kortkode)
- [ ] Ingen feilmeldinger i debug.log

### Steg 2: Kjør synkronisering
- [ ] Gå til **Oversikt** eller **Kursinnstillinger**
- [ ] Klikk "Hent alle kurs fra Kursagenten"
- [ ] Vent på statistikk (15 sekunder)
- [ ] Synkronisering starter automatisk

### Steg 3: Verifiser at nye metafelter brukes
Etter vellykket synkronisering, sjekk i databasen:

```sql
-- Sjekk at nye metafelter brukes (skal returnere > 0)
SELECT COUNT(*) FROM wp_postmeta 
WHERE meta_key LIKE 'ka_%';

-- Sjekk spesifikke metafelter
SELECT meta_key, COUNT(*) as count 
FROM wp_postmeta 
WHERE meta_key IN ('ka_location_id', 'ka_course_price', 'ka_main_course_id')
GROUP BY meta_key;
```

### Steg 4: Test at kurs vises
- [ ] Gå til frontend: `/kurs/`
- [ ] Kurs lister vises
- [ ] Single course pages fungerer
- [ ] Filtre fungerer (lokasjon, kategori, språk)
- [ ] Søk fungerer

## Feilsøking

### Problem: "Timeout eller nettverksfeil"
**Mulige årsaker:**
1. ❌ API-nøkkel mangler eller er feil
2. ❌ Serveren blokkerer utgående HTTP-forespørsler
3. ❌ PHP timeout er for lav
4. ❌ Memory limit er for lav

**Løsning:**
1. Verifiser API-nøkkel i innstillinger
2. Sjekk debug.log for detaljert feilmelding
3. Kontakt hosting-support hvis server blokkerer API-kall

### Problem: Kurs vises ikke etter synkronisering
**Løsning:**
1. Sjekk at kurs er publisert (ikke draft)
2. Tøm WordPress cache
3. Sjekk at permalinks er flushed (Settings → Permalinks → Save)

### Problem: Gamle metafeltnavn brukes fortsatt
**Dette skal IKKE skje** - alle filer er oppdatert.

**Hvis det likevel skjer:**
1. Sjekk at du har lastet opp alle oppdaterte filer
2. Tøm opcode cache (OPCache)
3. Sjekk at ingen filer er cached

## Viktige notater

### 🔴 Ingen migrering nødvendig
Siden du resatt databasen, er det **ingen gamle data å migrere**. 
Alle nye kurs fra API vil automatisk bruke de nye `ka_` metafeltnavnene.

### ✅ Plugin-filer er klare
Alle filer er oppdatert og klare til bruk:
- ✅ API sync - oppdatert
- ✅ Queries - oppdatert  
- ✅ Templates - oppdatert
- ✅ Shortcodes - oppdatert
- ✅ Relationships - oppdatert

### ⚠️ Migreringsscript ikke nødvendig
Migreringsscriptet (`includes/migrations/migrate-meta-fields.php`) er kun nødvendig hvis du har **eksisterende data** i databasen. Siden du resatt alt, kan du ignorere migreringsscriptet.

## Neste steg

1. **Sett API-nøkkel** (viktigst!)
2. **Aktiver debug logging**
3. **Test API-tilkobling** med `[test_kursagenten_api]`
4. **Kjør synkronisering** når API fungerer
5. **Verifiser** at kurs vises korrekt

## Support

Hvis problemet vedvarer etter å ha satt API-nøkkel:
1. Sjekk `debug.log` for feilmeldinger
2. Test API-tilkobling direkte
3. Kontakt hosting-support hvis serveren blokkerer API-kall
4. Send meg `debug.log` for analyse

---

**TL;DR:** Mest sannsynlig mangler API-nøkkelen etter database-reset. Sett den i innstillinger og prøv på nytt! 🔑


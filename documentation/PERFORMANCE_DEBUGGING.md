# Performance Debugging Guide for Kursagenten

## Hvordan finne ut hvorfor admin er tregt

### Metode 1: Bruk Performance Debugger (Anbefalt)

1. **Aktiver debug i wp-config.php:**
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   define('SAVEQUERIES', true); // Spor database-spørringer
   define('KURSAG_PERF_DEBUG', true); // Aktiver Kursagenten performance debugging
   ```

2. **Inkluder debug-filen i kursagenten.php:**
   Legg til denne linjen etter linje 296 (etter class-kursagenten-css-output.php):
   ```php
   require_once KURSAG_PLUGIN_DIR . '/includes/performance-debug.php';
   ```

3. **Last inn admin-sider:**
   - Gå til forskjellige admin-sider i WordPress
   - Sjekk error log (wp-content/debug.log) for detaljerte rapporter

4. **Sjekk rapporten:**
   Rapporten viser:
   - Total lastetid
   - HTTP-forespørsler (inkludert varighet)
   - Database-spørringer (inkludert langsomme spørringer)
   - Hooks som kjøres
   - Filer som inkluderes

### Metode 2: Bruk WordPress Query Monitor Plugin

1. Installer Query Monitor plugin
2. Aktiver plugin
3. Sjekk Query Monitor-widget i admin for:
   - Langsomme database-spørringer
   - HTTP-forespørsler
   - Hooks som kjøres

### Metode 3: Manuell debugging

#### A. Sjekk for blokkerende HTTP-forespørsler

Se etter `wp_remote_get` og `wp_remote_post` i:
- `includes/plugin_update/secure_updater.php` - `maybe_register_site()` kjører på admin_init
- `includes/api/api_connection.php` - API-kall
- `includes/api/api_course_sync.php` - Synkronisering

**Problem:** `maybe_register_site()` kan gjøre HTTP-kall som blokkerer admin-sider.

**Løsning:** Sjekk om funksjonen kjører oftere enn nødvendig:
```php
// I secure_updater.php, linje 116-136
// Sjekk at den ikke kjøres på hver admin-side
```

#### B. Sjekk for tunge database-spørringer

Se etter:
- `get_option()` kall i løkker
- `WP_Query` uten paginering
- Manglende caching av ofte brukte verdier

**Problem:** `coursedesign.php` har mange `get_option()` kall som kan caches.

#### C. Sjekk for hooks som kjøres for ofte

Vanlige problemer:
- Hooks på `admin_init` som gjør tungt arbeid
- Hooks på `admin_menu` som laster filer
- Hooks uten sjekk av current screen

### Vanlige flaskehalser i Kursagenten

1. **secure_updater.php - `maybe_register_site()`**
   - Kjører på `admin_init`
   - Kan gjøre HTTP-kall til ekstern server
   - Burde kun kjøres maks én gang per dag/uke

2. **secure_updater.php - `request()`**
   - Kjører når WordPress sjekker for oppdateringer
   - Kan blokkere hvis timeout er høy eller server er treg
   - Allerede optimert med caching og timeout på 3 sekunder

3. **coursedesign.php - Mange get_option() kall**
   - Henter innstillinger flere ganger
   - Burde cache resultater

4. **Admin menu registrering**
   - Mange admin menu hooks kan påvirke ytelse
   - Men burde ikke være et stort problem

### Quick Fixes

#### 1. Sjekk om maybe_register_site() kjøres for ofte

Legg til denne debug-koden midlertidig i `secure_updater.php`:

```php
public function maybe_register_site() {
    // Debug logging
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Kursagenten: maybe_register_site() called');
        error_log('  Last register: ' . get_option('kursagenten_last_register', 0));
        error_log('  Time since last: ' . (time() - get_option('kursagenten_last_register', 0)) . ' seconds');
    }
    
    // ... resten av koden
}
```

#### 2. Deaktiver registrering midlertidig

For å teste om registrering er problemet, kommenter ut denne linjen i `secure_updater.php` linje 49:
```php
// add_action('admin_init', [$this, 'maybe_register_site']);
```

#### 3. Sjekk timeout-verdier

Se etter HTTP-kall med høye timeout-verdier:
- `secure_updater.php`: timeout på 15 sekunder for registrering (linje 199)
- Dette kan blokkere hvis serveren er treg

### Rapportering

Når du har identifisert problemet, dokumenter:
1. Hvilken funksjon/hook som er problemet
2. Hvor ofte den kjøres
3. Hvor lang tid den tar
4. Hva som trigger den

Dette gjør det lettere å fikse problemet permanent.

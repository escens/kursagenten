# Stabile ID-er for Kortkoder

## Oversikt
Alle kortkoder i Kursagenten-pluginen genererer nå stabile ID-er som ikke endrer seg mellom sideinnlastinger. Dette gjør det mulig å bruke ekstern CSS for å style spesifikke kortkoder på siden.

## ID-format
- **Format**: `kag1`, `kag2`, `kag3`, osv.
- **Teller**: Øker med 1 for hver kortkode som blir rendret på siden
- **Rekkefølge**: Basert på rekkefølgen kortkodene vises i innholdet

## Støttede kortkoder
- `[instruktorer]` → `kag1`, `kag2`, osv.
- `[kurssteder]` → `kag1`, `kag2`, osv.
- `[kurskategorier]` → `kag1`, `kag2`, osv.
- `[kurs-i-samme-kategori]` → `kag1`, `kag2`, osv.

## Eksempel på bruk
Kortkodene har både `id` og `class` med samme verdi (f.eks. `id="kag1"` og `class="kag1 ..."`).
Klasser brukes i plugin-CSS for å tillate brukerens egne klasser (via `klasse="min-klasse"`) å overstyre med lik spesifisitet.

Alle kortkode-elementer bruker prefikset `k-` for å unngå konflikter med tema og andre plugins.

**Bakoverkompatibilitet:** Elementene har også de gamle klassene (`wrapper`, `box`, `text`, `tittel`, `title`, `image`, `description`, `infowrapper`, `specific-locations`, `location-item`) ved siden av de nye. Eksisterende custom CSS som bruker de gamle klassene vil fortsatt fungere.

### Tilgjengelige k-klasser
- `.k-wrapper` – grid-wrapper
- `.k-box` – enkelt kort/box
- `.k-box-inner` – innhold i box
- `.k-text` – tekstområde
- `.k-tittel` – overskrift (h3)
- `a.k-title` – lenke rundt tittel
- `a.k-image` – bilde-lenke
- `.k-description` – beskrivelse
- `.k-infowrapper` – info-wrapper (kurssteder)
- `.k-specific-locations` – stedsliste
- `.k-location-item` – enkelt sted

```css
/* Style den første kortkoden på siden */
.kag1 .k-box {
    border: 2px solid #007cba;
}

/* Style den andre kortkoden på siden */
.kag2 .k-box {
    background-color: #f0f0f0;
}

/* Overstyr med egen klasse fra kortkoden */
.min-klasse.kort .k-box {
    border-radius: 12px;
}

/* Style alle kortkoder */
[class^="kag"] .k-box {
    margin-bottom: 2rem;
}
```

## Teknisk implementasjon
- `StableIdGenerator`-klassen håndterer ID-generering
- Statisk teller som øker for hver kortkode
- Alle kortkoder bruker samme prefix (`kag`) for konsistens
- ID-ene er stabile mellom sideinnlastinger

## Testing
For å teste at ID-ene er stabile:
1. Last inn siden og inspiser HTML
2. Noter ID-ene som brukes
3. Last inn siden på nytt
4. Verifiser at samme ID-er brukes i samme rekkefølge

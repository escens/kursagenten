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
```css
/* Style den første kortkoden på siden */
#kag1 .box {
    border: 2px solid #007cba;
}

/* Style den andre kortkoden på siden */
#kag2 .box {
    background-color: #f0f0f0;
}

/* Style alle kortkoder */
[id^="kag"] .box {
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

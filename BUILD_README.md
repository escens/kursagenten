# Kursagenten Release Builder

Dette scriptet automatiserer bygging av nye plugin-versjoner.

## Bruk

### Metode 1: Med versjonsnummer som parameter
```powershell
.\build-release.ps1 -Version "1.0.4"
```

### Metode 2: Bruk det interaktive scriptet
```powershell
.\build.ps1
```

## Hva gjør scriptet?

1. ✅ **Oppdaterer versjonsnummer** i `kursagenten.php` (både i header og KURSAG_VERSION konstant)
2. ⚠️ **Minner deg om** å oppdatere `CHANGELOG.md` (og venter på bekreftelse)
3. ✅ **Bygger ZIP-fil** med riktig mappestruktur (`kursagenten/` som rotnavn)
4. ✅ **Lagrer til** `C:\Users\ToneBHagen\Websider\Pluginversjoner\kursagenten-X.X.X.zip`
5. ✅ **Verifiserer** at ZIP-strukturen er korrekt

## Viktig!

- Scriptet stopper og venter på at du oppdaterer CHANGELOG.md før det bygger ZIP-filen
- ZIP-filen vil alltid ha `kursagenten/` som rotmappe (ikke `kursagenten-1.0.4/`)
- Dette sikrer at WordPress ikke deaktiverer pluginen ved oppdatering

## Filer som ekskluderes fra ZIP

- `*.ps1` - PowerShell-scripts
- `node_modules/` - Node modules
- `.git/` - Git repository
- `.vscode/` - VS Code settings
- `*.log` - Log-filer
- `slettes/` - Utviklermappe

## Etter bygging

1. Last opp ZIP-filen til Kursagenten Server plugin
2. Test oppdateringen på en testside
3. Commit endringene til Git når alt fungerer


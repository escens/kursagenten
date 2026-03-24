# Blokk-oppsett for Kursagenten

Denne guiden viser den enkleste måten å bygge og bruke Gutenberg-blokkene.

## 1) Installer avhengigheter

Kjør i pluginmappen:

```powershell
npm install
```

## 2) Bygg blokkfilene

```powershell
npm run build
```

Dette lager:

- `build/taxonomy-grid.js`
- `build/taxonomy-grid.asset.php`
- `build/taxonomy-grid.css`

## 3) Bruk i WordPress-editor

I blokk-innsetter finner du:

- `Kurskategorier`
- `Kurssteder`
- `Instruktører`
- `Preset-stiler`

Alle er varianter av samme blokk-motor.

## 4) Vanlige feil

### Blokken vises ikke i editor

Sjekk at disse filene finnes etter build:

- `build/taxonomy-grid.js`
- `build/taxonomy-grid.asset.php`

Hvis de mangler: kjør `npm run build` på nytt.

### Endringer i JS/CSS vises ikke

1. Kjør `npm run build` på nytt
2. Oppdater nettleser med hard refresh
3. Tøm eventuelt cache-plugin/CDN cache

## 5) CSS-strategi

Hver preset har egen CSS-fil i:

- `public/blocks/taxonomy-grid/style-*.css`

Dette gjør det enklere å teste en stil uten bivirkninger i andre stiler.

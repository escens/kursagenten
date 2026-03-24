# Kursagenten-blokker - oppsett og videre arbeid

Dette dokumentet beskriver hvordan blokkene under `public/blocks/` er bygget opp, hvordan de henger sammen, og en løpende oppsummering av viktige endringer.

## 1) Oversikt

Blokk-systemet er bygget rundt én hovedblokk:

- `kursagenten/taxonomy-grid`

Denne brukes med flere varianter i inserteren:

- `Kurskategorier`
- `Kurssteder`
- `Instruktører`
- `Preset-stiler`

Alle varianter peker til samme editor/render-logikk, men med ulike standardinnstillinger.

## 2) Viktige filer

### Editor (Gutenberg-UI)

- `src/blocks/taxonomy-grid/index.js`
  - Blokkregistrering
  - Varianter (`registerBlockVariation`)
  - Inspector-kontroller (paneler, faner, presets, betinget visning)
  - Preset-standarder (`PRESETS`)

- `src/blocks/taxonomy-grid/editor.css`
  - Styling av Inspector-UI
  - Egne komponentstiler for preset-kort, faner, fargerader osv.

### Render (frontend + editor-forhåndsvisning)

- `public/blocks/taxonomy-grid/render.php`
  - Server-side rendering
  - Henter taksonomi-termer og bygger HTML
  - Setter CSS-variabler
  - Bilde-fallbacks og sanitering

- `public/blocks/shared/shortcode-attribute-mapper.php`
  - Mapper/saniterer blokk-attributter til trygg struktur brukt av render

### CSS (frontend/layout)

- `public/blocks/taxonomy-grid/style-base.css`
- `public/blocks/taxonomy-grid/style-stablet.css`
- `public/blocks/taxonomy-grid/style-rad.css`
- `public/blocks/taxonomy-grid/style-liste.css`
- `public/blocks/taxonomy-grid/style-kort.css`
- `public/blocks/taxonomy-grid/style-kort-bg.css`

### Metadata og build

- `public/blocks/taxonomy-grid/block.json` (attributter/defaults)
- `build/taxonomy-grid.js` + `build/index.css` + `build/index-rtl.css` + `build/taxonomy-grid.asset.php` (bygde filer)

## 3) Bygging og deploy

Når du endrer `src/blocks/...`:

1. Kjør:

```bash
npm run build
```

2. Last opp oppdaterte `build/`-filer.

Når du kun endrer filer i `public/blocks/...` (PHP/CSS):

- Ingen build nødvendig.
- Last opp endrede filer direkte.

## 4) Hvordan presets og varianter fungerer

- `PRESETS` i `src/.../index.js` har alle defaults per stil.
- Ved klikk på preset i editor kjøres `applyPreset()`.
- For blokkvarianter i inserteren brukes også `getPresetDefaults(...)`, slik at relevante preset-verdier blir riktige med en gang ved innsetting.

## 5) Viktige conditional-regler i editor

Per nå:

- `Velg bildetype` vises kun når:
  - `Kildetype = Kurskategorier`
  - `Vis bilde = på`

- `Bilde`-panelet vises kun når:
  - `Vis bilde = på`

- `Maks ord i beskrivelse` vises kun når:
  - `Vis beskrivelse = på`
  - preset **ikke** er `Rad utvidet beskrivelse`

- `Maks ord i utvidet beskrivelse` vises kun når:
  - `Vis beskrivelse = på`
  - preset er `Rad utvidet beskrivelse`

- For bakgrunns-presets:
  - `Kort bakgrunn` viser `Mørk overlay (%)`
  - `Kort bakgrunnsfarge` viser fargekontroller for `Tekst`, `Bakgrunn` og `Bakgrunn hover`

- `Kolonner` vises nederst i `Layout og stil` for alle presets
  - unntak: `Liste enkel` og `Rad utvidet beskrivelse`
  - for disse to vises `Antall kolonner` i `Justeringer`

## 6) Oppsummering av denne chatten

Gjennomførte endringer (høydepunkter):

- Ny inspector-struktur med faner:
  - `Generelt`
  - `Justeringer`
- Flyttet kontroller for bedre flyt:
  - `Datakilde` og `Layout og stil` i `Generelt`
  - øvrige paneler i `Justeringer`
- Hurtigvalg lagt inn/forbedret:
  - ikonbaserte knapper for `Bildestørrelse`
  - `Egendefinert`-lenke i label-linje
  - `Bildeformer` med skjult radius-kontroll til `Egendefinert`
- Oppdatert navngivning i UI:
  - `Border ...` -> `Ramme...` / `Kantstil`
  - `Tekstjustering` -> `Element-justering`
  - skyggenivå: `Svak`, `Normal`, `Medium`, `Kraftig`, `Ekstra kraftig`
- Forbedret innstillings-UX:
  - `Rammeinnstillinger`-lenke under `Skygge/kantstil = Ingen, men bruk ramme`
  - `Rediger i Element-kort`-lenke som hopper til panel i `Justeringer`
  - `Element-kort` vises alltid
- Preset-oppdateringer:
  - `Rad detalj` er omdøpt til `Rad utvidet beskrivelse`
  - oppdaterte bilde-/skygge-defaults (inkl. `xsoft` som standard der skygge brukes)
  - `Stablet kort overlapp` har hvit bildebakgrunn som default
- Ny bildekontroll:
  - `Bakgrunnsfarge bak bilde` (under `Bilde`-panelet)
- Ny hover-kontroll for kortbakgrunn:
  - `Bakgrunn hover` i `Element-kort > Farger`
  - `Bakgrunn hover` i `Kort bakgrunnsfarge > Farger`
  - styrer hover via CSS-variabel `--k-card-bg-hover`
- Frontend-justering:
  - bildejustering følger `Element-justering` via CSS-variabler
  - side-luft rundt bilde følger `Kort padding` (desktop/tablet/mobil)
- Render for `Rad utvidet beskrivelse`:
  - bruker term-meta `rich_description` (fallback til vanlig beskrivelse)
  - kun bilde og tittel er lenker
  - lenker i beskrivelsen fungerer
- Ny ordgrense-logikk:
  - `Maks ord i beskrivelse` (kort beskrivelse)
  - `Maks ord i utvidet beskrivelse` (kun `Rad utvidet beskrivelse`)
  - HTML-trimming for utvidet beskrivelse bevarer lenker

## 7) Detaljert: siste steg i chatten

Siste implementerte steg:

- Ny innstilling `Bakgrunn hover` for kort med hover-stil i frontend.

Teknisk:

- Filer:
  - `public/blocks/taxonomy-grid/style-base.css`
  - `public/blocks/taxonomy-grid/render.php`
  - `src/blocks/taxonomy-grid/index.js`
  - `public/blocks/shared/shortcode-attribute-mapper.php`
  - `public/blocks/taxonomy-grid/block.json`
- Endring:
  - Ny attributt: `cardBackgroundColorHover`.
  - Ny CSS-variabel i wrapper: `--k-card-bg-hover`.
  - Hover-stil for kort: bakgrunn endres på `.k-card:hover`.
  - Ny editor-rad `Bakgrunn hover` i begge relevante fargepaneler.

Forrige større steg (fortsatt gjeldende):

- `Rad utvidet beskrivelse` bruker `rich_description` med fallback.
- Egne ordgrenser for kort og utvidet beskrivelse.
- Utvidet beskrivelse trimmes med HTML-bevaring (inkl. lenker).

Konsekvens:

- Mer forutsigbar redigering av lang tekst i `Rad utvidet beskrivelse`.
- Redaktør kan begrense tekst uten å miste lenker i beskrivelsen.
- Redaktør kan styre separat hover-bakgrunn på kort i relevante presets/paneler.

## 8) Anbefalt oppstartstekst for ny chat

Kopier gjerne dette som start i neste chat:

> Vi jobber i `kursagenten` med blokken `taxonomy-grid`.  
> Se `public/blocks/blocks.md` for oppsett og status.  
> Siste relevante tema: editor-modernisering, tema-hardening i CSS, attributt-validering i mapper/render, og `Stablet kort overlapp` (se §9).  
> Nå ønsker jeg å jobbe videre med [sett inn neste oppgave].

## 9) Senere oppdateringer: ytelse, editor, robusthet og tema

### Render og PHP

- **`kursagenten_css_keyword()`** i `render.php`: whitelist + fallback for `textAlignDesktop/Tablet/Mobile` (`left` / `center` / `right`), `verticalAlignDesktop/Tablet/Mobile` (`top` / `center` / `bottom`) og for ramme-`border-style` på bilde og kort (`none`, `solid`, `dashed`, `dotted`, `double`).
- **Semantikk**: kort beskrivelse (standard layout) bruker `<div class="k-description">`, ikke `<span>`, slik at innholdet matcher blokknivå og tema-CSS forutsigbart.
- **CSS-verdier**: `kursagenten_css_value` / farger som tidligere, med sanitering som blokkerer farlige tegn og `url()` / `expression()` / `@import` i inline-verdier.
- **Vertikaljustering i output**: render setter `--k-content-justify-desktop/tablet/mobile` som brukes i CSS for faktisk vertikal plassering av innhold.

### Attributt-mapper (`shortcode-attribute-mapper.php`)

- Etter `wp_parse_args` valideres bl.a.:
  - `sourceType`, `stylePreset`, `backgroundMode`, `filterMode`, `instructorNameMode`
- Eksisterende validering for kolonner, ordgrenser, tekstjustering, skygge, ramme-stil og bildekilde er uendret i intensjon; nye whitelist-er hindrer korrupte attributter fra å slippe gjennom.
- **Nytt**: `verticalAlignDesktop/Tablet/Mobile` valideres mot `top|center|bottom`.

### Editor (JavaScript)

- **Forhåndsvisning**: `useServerSideRender` med debouncet `previewAttributes` (unngår unødvendige server-kall under dragging av kontroller).
- **Responsive-faner og viewport**: Når du bytter tab (Desktop/Nettbrett/Mobil) i Responsive-innstillinger (f.eks. Kolonner, Element-justering, Spacing), synkes editorens forhåndsvisning automatisk til tilsvarende skjermstørrelse via `syncEditorViewportToDevice()`. Bruker WP sin innebygde API: `core/editor` `setDeviceType` (WP 6.5+), med fallback til `core/edit-post` / `core/edit-site` `__experimentalSetPreviewDeviceType`.
- **Element-justering**: nye SVG-ikoner (Kadence-lignende) for horisontal justering + ekstra rad for vertikal justering (topp/senter/bunn).
- **Preset-defaults for vertikaljustering**: lagt inn per preset, slik at nye blokker starter med samme vertikale oppsett som tidligere CSS ga visuelt.
- **Fargepalett**: `useSettings('color.palette.theme', 'color.palette.default')` i stedet for `__experimentalUseMultipleOriginColorsAndGradients`; fallback til intern palett om tema ikke leverer farger.
- **Blokkikon**: `public/blocks/shared/icon.svg` som blokkikon i inserter.
- **Kategori**: kun `block_categories_all` i PHP (Dashicon `welcome-learn-more`); `registerBlockCollection` er fjernet for å unngå duplikat «Kursagenten»-kategori.
- **Deprecated API**: ved behov bruk `wp.editor.PluginSidebar` / `PluginSidebarMoreMenuItem` i stedet for `wp.editPost.*` (WP 6.6+).

### Tekst og tittel (font, farge, vekt)
- **Tittel**: `fontMin`/`fontMax` (clamp), `titleColor`, `fontWeightTitle` (100, 400, 600, 700, 800).
- **Beskrivelse/tekst**: `descriptionFontMin`/`descriptionFontMax`, `descriptionColor`, `fontWeightDescription`.
- `textColor` brukes som fallback når `titleColor`/`descriptionColor` er tom.
- Editor: FontSizeMinMaxControl (min input venstre, sliders, max input høyre), FontWeightButtons, separate fargerader for Tittel og Tekst.

### Frontend-CSS (tema-hardening)

- **`style-base.css`**: `min-width: 0` / `max-width: 100%` på direkte barn av `.k-content`, reset av `article.k-card` margin, normalisering av rich text i `.k-description` (overskrifter, lister, lenker, innebygd media), og tydelig `:focus-visible` på kort-/bilde-/tittel-lenker.
- **Lenkestyring i beskrivelser**:
  - Kort beskrivelse: `.k-description:not(.k-description-long) a` låses til arvet farge uten underline.
  - Utvidet beskrivelse (`.k-description-long`): lar temaets vanlige linkstil slå gjennom.
- **`style-rad.css`**: `grid-template-columns: … minmax(0, 1fr)`, `min-width: 0` på `.k-content`, og vertikaljustering flyttet til variabler (`--k-content-justify-*`) i stedet for hardkodet `center`.
- **`style-stablet.css`**: `min-width: 0` på stablet-`.k-card-link`.
- **`style-liste.css`**: `min-width: 0` på `.k-content` i liste-preset.
- **`style-kort-bg.css`**: vertikal plassering i bakgrunns-presets styres via variabler (`--k-content-justify-*`) i stedet for `margin-top:auto`/`margin-bottom:auto`.

### Preset «Stablet kort overlapp»

- **Overlap på bildet**: `margin-top: calc(var(--k-image-size, 120px) / -2.5)` gir omtrent **40 %** av bildehøyden som negativ forskyvning (klassisk «løftet» sirkel/ bilde).
- **Rad-avstand**: `.k-card` i dette preset har `margin-bottom: calc(var(--k-image-size, 120px) / 2.5)` slik at løftet bilde ikke kolliderer visuelt med raden over (tilpass om du endrer overlap-formelen).
- En tidligere variant brukte `max(-44px, …)` for å begrense løftet ved store bilder; den er fjernet der designet skal følge full `calc` uten tak.

### Diverse

- **N+1**: bilde-fallback for kategorier henter kursbilde kun når hovedbilde (og ev. profilbilde (ikon-bilde) etter valg) mangler.
- **Stil-last**: taxonomy-grid-stiler lastes via registrerte handles uten dobbel enqueue der det er sjekket med `wp_style_is`.
- **Admin-meny**: toppnivå bruker `public/blocks/shared/icon-black.svg` (konfigurert i `kursagenten-admin_options.php`).

---

Når du endrer `public/blocks/taxonomy-grid/*.css` eller `render.php`, trenger du **ikke** `npm run build`. Kjør build etter endringer i `src/blocks/...`.

## 10) Dekning mot taksonomi-kortkoder (fra `documentation.php`)

Denne sjekken sammenligner attributtene i dokumentasjonen for:

- `[kurskategorier]`
- `[kurssteder]`
- `[instruktorer]`

mot det som faktisk finnes i `taxonomy-grid`-blokken (`block.json` + mapper/render).

### Dekket i blokk (helt eller funksjonelt tilsvarende)

- `kilde` (kategori): dekket via `categoryImageSource` (`main`/`icon`).
- `kilde` (instruktører): dekket via `instructorImageSource` (`standard`/`alternative`) med prioritert fallback.
- `layout`: dekket via presets (`stablet`, `rad`, `liste`-varianter).
- `stil=kort`: dekket via kort-presets / `useCardDesign`.
- `grid`, `gridtablet`, `gridmobil`: dekket via `columnsDesktop/Tablet/Mobile`.
- `bildestr`: dekket via `imageSize`.
- `radavstand`: dekket via `rowGapDesktop/Tablet/Mobile`.
- `avstand`: dekket via wrapper/card spacing (`wrapperPadding*`, `cardPadding*`, `cardMargin*`).
- `bildeform`: dekket via radius-kontroller (`imageRadius*`).
- `bildeformat`: dekket via `imageAspect`.
- `skygge`: dekket via `shadowPreset`.
- `overskrift`: dekket via `titleTag`.
- `fontmin`, `fontmaks`: dekket via `fontMin`, `fontMax`.
- `utdrag`: dekket via `showDescription` (+ ordgrenser).
- `beskrivelse` (instruktører): dekket funksjonelt via `showDescription` + langtekst-preset (`rad utvidet beskrivelse`).
- `region` (kurssteder): dekket via `region`.
- `vis` (kurssteder): dekket via `locationInclude` (dynamisk flervalg i editor), kombinert med `region` (OR-logikk).
- `vis` (instruktører: fornavn/etternavn): dekket via `instructorNameMode`.
- `skjul` (instruktører): dekket via `instructorExclude` (dynamisk flervalg i editor).
- `stedinfo` (kurssteder): dekket via `locationShowInfo` med visning av stedsbeskrivelser under hvert sted.
- `vis` (kurskategorier): dekket inkl. foreldreslug via `categoryParentSlugs` (dynamisk flervalg).
- `st` (kurskategorier): dekket via `categoryLocationFilter` (`<slug>` / `ikke-<slug>`).

### Mangler i blokk (ikke dekket per nå)

- `klasse`: blokken har ikke egen shortcode-lignende `klasse`-attributt i mapperen (men Gutenberg sin "Ekstra CSS-klasse" kan brukes på blokk-nivå).

### Forslag til rekkefølge videre

1. Vurdere om `klasse` skal inn som eksplisitt blokk-attributt (for 1:1 mot shortcode-oppsett).
2. Oppdatere intern dokumentasjon/screenshots slik at nye datakilde-kontroller beskrives (region, stedvalg, foreldrekategori, stedsfilter, instruktørbilde, kontaktfelt).
3. Eventuelt legge inn migrering/hjelpetekst for mapping mellom shortcode-navn og blokk-attributter der navngivning avviker.

## 11) Seneste integreringer i denne runden

- **Kurssteder**
  - Dynamisk avkrysningsliste for `vis` (`locationInclude`) med `Tøm`.
  - Regionstyring koblet til admin-innstillingen `kursagenten_use_regions`; region felt er disablet når funksjonen ikke er aktiv.
  - Region vises som dynamisk dropdown i editor (samme kildedata som øvrige region-funksjoner).
  - `stedinfo` støttes i blokk (`locationShowInfo`) med frontend-styling uten punktliste.
- **Kurskategorier**
  - `Filtrering` omdøpt til `Velg kategorinivå`.
  - Foreldrekategori er utvidet til flervalg (`categoryParentSlugs`) med samme UX som stedvalg.
  - `st` er implementert som stedsfilter (`categoryLocationFilter`) med støtte for både `slug` og `ikke-slug`.
- **Instruktører**
  - Ny bildetype-kontroll (`instructorImageSource`) med fallback-rekkefølge:
    - `standard`: profilbilde -> alternativt bilde -> placeholder
    - `alternative`: alternativt bilde -> profilbilde -> placeholder
  - Nye toggles for kontaktfelt (`showInstructorPhone`, `showInstructorEmail`) med `tel:` / `mailto:`.
  - Kontaktvisning bruker egne ikoner (SVG) og egen kontakt-rad-styling.
- **Layout/robusthet**
  - Rettet ugyldig nested-link-struktur i instruktørkort.
  - Forbedret bildejustering i stablet-presets når `k-image-link` brukes.
  - Oppdaterte preset-defaults for `Stablet kort innfelt` og `Stablet kort overlapp` (kort-padding desktop/tablet/mobil), og synket disse med `block.json`-defaults.


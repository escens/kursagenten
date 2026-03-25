# Kursagenten WordPress-plugin

WordPress-utvidelse som gjør det mulig å vise kurs fra Kursagenten på nettsiden din – med automatisk import av nye/oppdaterte kurs via API og webhooks.

Pluginet oppretter egne posttyper/taksonomier, genererer kurs- og taksonomissider basert på malvalg, og tilbyr ferdige komponenter (shortcodes + Gutenberg-blokk) for lister, filtre og visning i flere layoutvarianter.

---

## Innhold (kort oppsummert)

- Synkroniserer kurs fra Kursagenten (kurs, datoer, lokasjoner og instruktører)
- Oppretter:
  - Posttyper: `ka_course` og `ka_coursedate`
  - Taksonomier: `ka_coursecategory`, `ka_course_location`, `ka_instructors`
- Automatisk oppdatering via webhook (`CourseCreated`/`CourseUpdated` i Kursagenten)
- Kursliste med filtre og paginering (AJAX-basert)
- Taksonomilister i grid/rad/liste via shortcodes
- Gutenberg-blokk for “taxonomy grid”
- Kursdesign og URL-/SEO-tilpasning via admin-innstillinger
- Vedlikehold:
  - Manuell opprydding
  - Nattlig opprydding (kurs/kursdatoer som ikke lenger finnes eller er utløpt)

---

## Krav

- WordPress: minst 6.0
- PHP: minst 7.4

---

## Installasjon

1. Last opp pluginet til `wp-content/plugins/` og aktiver det.
2. Lim inn lisensnøkkel i Kursagenten-oppsettet (vises i admin når nøkkel mangler).
3. Gå til **Kursagenten → Synkronisering**:
   - Koble mot Kursagenten-kontoen
   - Legg inn webhooks i Kursagenten (CourseCreated og CourseUpdated)
4. Klikk **Hent alle kurs fra Kursagenten** (anbefalt første gang, og ved større endringer).

---

## Webhook (anbefalt metode)

Når webhooks er aktivert i Kursagenten, oppdateres kursene automatisk når de lagres eller oppdateres der.

**Webhook-URL:**
`{ditt-nettsted}/wp-json/kursagenten-api/v1/process-webhook`

**Slik finner du riktig URL:**  
Pluginets dokumentasjon i admin viser url-en som passer din installasjon.

---

## Oppsett av sider (Kursdesign)

For å vise kursoversikter og lister på nettsiden, bruker du WordPress-sider med kortkoder.

Gå til **Kursagenten → Kursdesign** og opprett/velg sider for:
- **Kurs** (inneholder `[kursliste]`)
- **Kurskategorier** (inneholder `[kurskategorier]`)
- **Kurssteder** (inneholder `[kurssteder]`)
- **Instruktører** (inneholder `[instruktorer]`)

Pluginet oppretter også nødvendige systemsider automatisk (forenklet: enkeltkurs og taksonomier).

---

## Bruk: Kortkoder

### Kursliste (filtrert kursoversikt)
- `kursliste`

Eksempel:
- `[kursliste]`
- `[kursliste kategori="dans" sted="oslo" språk="norsk" måned="09" list_type="standard" bilder="yes"]`

Vanlige attributter:
- `kategori` (kurskategori)
- `sted` / `lokasjon`
- `språk`
- `måned` (måned som tekst/nummer, f.eks. `01` / `9`)
- `list_type` (`standard`, `grid`, `compact`)
- `bilder` (`yes`/`no`)
- `antall` (begrens antall kurs)

### Lister/grid for taksonomier
- `kurskategorier`
- `kurssteder`
- `instruktorer`

Eksempler:
- `[kurskategorier]`
- `[kurssteder region="østlandet" stedinfo="ja"]`
- `[instruktorer skjul="Iris,Anna"]`

Disse støtter flere visuelle valg (layout, gridstørrelse, kort/tekst, osv.). Se admin-siden **Dokumentasjon** for full oversikt og alle parametere.

### Dynamiske menyer (autogenerering)
- `ka-meny`

Eksempel:
- `[ka-meny type="kurskategorier" start="mitt-kategorislug"]`
- `[ka-meny type="kurssteder" st="ikke-oslo"]`
- `[ka-meny type="instruktører"]`

---

## Gutenberg-blokk: Taxonomy Grid

Pluginet tilbyr en Gutenberg-blokk for å bygge grid av taksonomier (kurskategorier/kurssteder/instruktører) med kortdesign og styling.

Bruk blokken i stedet for kortkoder hvis du bygger sidene i blokkeditoren (Gutenberg).

---

## Kursdesign, URL-er og visning

I admin kan du styre:
- Designmal for enkeltsider og taksonomisider
- Layout og liste-/gridvalg
- Tema-farger og CSS-variable
- URL-rewrite/tilpasning (kan være nødvendig hvis dere ønsker alternative slugs, f.eks. flytte fra `/kurs/...` til en annen struktur)

---

## Vedlikehold og opprydding

Pluginet kan rydde bort kurs/kursdatoer som ikke lenger finnes i Kursagenten eller er utløpt.

Du kan:
- Krysse av for opprydding ved synkronisering (“Rydd opp i kurs …”)
- Kjøre opprydding manuelt via adminknapp
- Dra nytte av automatisk nattlig opprydding

---

## Lisens og oppdateringer

Kursagenten bruker en lisensnøkkel for å:
- begrense funksjonalitet uten lisens
- hente plugin-oppdateringer på en kontrollert måte

---

## Dokumentasjon i admin

Pluginet har en omfattende “Dokumentasjon”-side i WordPress (A–Å) som beskriver:
- nøyaktig oppsett av webhooks
- forslag til sider og kortkoder
- menyløsninger
- designvalg og vanlige feilsøkingstips

---

## Feilsøking (kort)

- Hvis nye kurs ikke dukker opp: sjekk at webhooks er korrekt satt i Kursagenten, og at synkronisering er kjørt.
- Ved endringer i URL-er/design: oppdater permalinker og evt. tøm cache.
- Hvis bilder mangler: kursene importerer featured image fra Kursagenten og bruker placeholder hvis bilde ikke finnes.

---

## Support

Kontakt gjerne via prosjektets supportkanal/e-post som er oppgitt i pluginet og dokumentasjonen i admin.

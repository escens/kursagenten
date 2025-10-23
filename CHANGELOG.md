## 1.0.6 - 2025-10-22
- Forbedret: Moderne design for kompakt listevisning med forbedret layout og visuelt hierarki
- Lagt til: Påmeldingsknapp i kompakt listevisning
- Lagt til: Thumbnail-bilde i kompakt listevisning (respekterer bildeinnstillinger)
- Lagt til: "Neste kurs: " tekst i hovedkurs-visning på taksonomi-sider
- Lagt til: Tilgjengelighetsmerker (Ledige plasser, Fullt, På forespørsel) i kompakt listevisning
- Lagt til: `bilder` attributt i [kursliste] shortcode for å overstyre bildeinnstillinger (bilder="yes" eller bilder="no")
- Forbedret: Responsivt design for kompakt listevisning på mobile enheter
- Forbedret: Hover-effekter og animasjoner for bedre brukeropplevelse
- Forbedret: Taksonomi-bildeinnstillinger sendes nå automatisk til [kursliste] shortcode ved "Vis alle kursdatoer"
- Forbedret: Konsistent bildeinnstillings-logikk på tvers av alle listetyper (standard, grid, compact, plain)
- Fix: CSS-fil for kompakt listevisning lastes nå inn korrekt i shortcodes
- Fix: Kursbilder vises nå korrekt i shortcodes (bruker bilde fra hovedkurs, ikke coursedate)
- Fix: Taksonomi-bildeinnstillinger respekteres nå på alle visningstyper (ikke kun hovedkurs)
- Fix: Plain listevisning respekterer nå taksonomi-bildeinnstillinger og shortcode-attributter

## 1.0.5 - 2025-10-16
- Endret: Lagt inn mulighet for å legge inn egen klasse på kortkoder (lister) for custom styling
- Endret: I kortkoder for lister vil ikke bilder lenger lastes inn om attributt bildestr blir satt til 0
- Fix: Feil i taksonomi-maler. I malene var det ikke mulig å velge listetype (grid/standard). Nå er det mulig å velge listetype, samt å velge å vise kun hovedkurs i listen (med Neste kursdato) eller alle kursdatoer
- Lagt til: Ny kursliste-mal - "Kompakt". Enkelt og kompakt, med mulighet for å vise kursbilde
- Lagt til: Ny kursliste-mal - "Enkel". Enkelt og ren, med mulighet for å vise kursbilde
- Lagt til: Attributter i [kursliste]. Nå kan det manuelt legges inn bilder=yes/no og list_type=grid/plain/compact/standard

## 1.0.4 - 2025-10-16
- Stabilisert henting av filer fra Kursagenten. Ved mange kurs har det hendt at synk feiler.

## 1.0.3 - 2025-10-08
- Endret: Navn på designmaler for taksonomisider
- Lagt til: Flere designmaler for taksonomi (Enkel mal, uten bilde og tekst)
- Lagt til: Legg inn betalingsside automatisk, og som valg i Kursdesign -> Wordpress-sider
- Forbedret: SEO for enkeltkurs-sider og taksonomisider

## 1.0.2 - 2025-10-01
- Fix: Rettet designfeil i kursliste på mobil
- Fix: Ikke kollaps hovedkategori om det er kun én aktiv hovedkategori i filter

## 1.0.1 - 2025-10-01
- Lagt til: Mange nye hooks tilgjengelig for taksonomi-sider
- Fix: Kurs på Kursagenten som blir slettet, blir nå også slettet på nettsiden
- Fix: Retter feil i oppdateringsfunksjon

## 1.0.0 - 2025-09-22
- Første versjon


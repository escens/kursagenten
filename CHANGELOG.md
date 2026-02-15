## 1.1.14 - 2026-02-02
- Fix: Skjult kursbilder i Mediebibliotek. De skal ikke kunne brukes på sider, da de kan slettes via Kursagenten og kan skape ødelagte linker.
- Lagt til: Mulighet for overstyre filterinnstillinger på kurskategorier, med listetype *Vis alle kursdatoer*. Normalt vises kun underkategorier på en gitt hovedkategori. Kryss av på "Vis kursfilter på kateogoriside" for å vise kategorier som kursene deler. 
- Fix: Lagt inn mulighet for å legge til klasser på menypunkter, slik standard funksjonalitet i WP er.
- Lagt til: Ny designmal for enkeltkurs: Bokser. Dette er en ny stil, og det kan senere komme nye varianter. 

## 1.1.13 - 2026-02-02
- Fix: Justert design på standardmal for taksonomier. Visning blir nå fin både med bilde og tekst, kun bilde eller tekst, eller uten tekst og bilde.
- Lagt til: Mulighet til å skru av Spesifikke lokasjoner på kurssteder. Ny innstilling under *Kursdesign -> Taksonomisider*
- Lagt til: Ny menytype - automenyer. Generer lister med instruktører, kurssteder og kurskategorier. Dette betyr at du ikke trenger å legge til/fjerne punkter i menyen som er lagt til manuelt.
- Lagt til: Ny menytype *Kategorier og kurs* i automenyer. Viser kategorier med hovedkurs som undermenypunkter. Legges som de andre under et menypunkt for best resultat. Nedtrekksmeny med valg: Vis hovedkategorier, Vis subkategorier, eller velg spesifikk undermeny. Avkrysning *Vis kun kurs, ikke kategorier* for kurstilbydere med få kurs.

## 1.1.12 - 2026-01-22
- Fix: Endret oppdateringsserver.

## 1.1.11 - 2026-01-15
- Fix: Slide-in panel er flyttet så det alltid vises over header-rad og meny.
- Fix: Lagt inn støtte for plugi.\buildn Avada Builder, så stilene i utvidelsen ikke blir overskrevet.

## 1.1.10 - 2026-01-05
- Fix: Link til lokasjoner på enkeltkurs er nå skjult om de er deaktivert i Kursagenten.
- Fix: Nettbasert kurs uten Fritekst sted-tekst ble skjult i lister. Nå rettet opp så de synes.

## 1.1.09 - 2025-12-22
- Fix: Region på kurssted ble overskrevet ved flytting av regioner under Synkronisering. Nå beholder kurssted manuelt valgt region.

## 1.1.08 - 2025-12-15
- Fix: Navngivning av Regioner ved lagring på engelsk Wordpress er nå korrekt
- Endret: Lagt til alt nytt så all informasjon relatert til utvidelsen slettes fra WP ved avinstallering

## 1.1.07 - 2025-12-12
- Lagt til: Widget i kontrollpanel så du får se de nyeste endringene vi har gjort 🎉
- Lagt til: Mulighet til å endre navn på kurssted fra Kursagenten -> Synkronisering. Navn blir endret ved henting/synkronisering av kurs.
- Lagt til: Regioner. Kan aktiveres ved behov, og brukes i kortkode [kurssteder] med region="sørlandet/østlandet/vestlandet/midt-norge/nord-norge". Det er også mulig å legge til ekstra steder: [kurssteder region="østlandet" vis="bergen"]
- Endret: Forbedret dokumentasjon
- Intert: lagt inn performance-debug.php og dokumentasjon for feilsøking hvis utvidelsen blir treg
- Internt: gjort endringer til secure updater, gjorde siden treg igjen

## 1.1.06 - 2025-12-05
- Lagt til: Kortkode-attributt *vis* steder, som gir mulighet til å filtrere kurssteder-kortkode til kun å vise ønskede lokasjoner. Fungerer både med slug og stedsnavn. Eksempel: vis=oslo,drammen,bergen
- Endret: Tidlligere attributt *vis* for kurssteder har blitt omdøpt til *stedinfo*. Gir mulighet til å vise spesifikke lokasjoner under stedsnavn i listen (fra feltet Fritekst sted i Kursagenten).
- Fix: Hvis kurslokasjon blir endret fra fysisk sted til nettkurs, blir nå fritekst sted og adresse fjernet fra oppføring 
- Fix: Feil i kode på [kurssteder], nå rettet. Viste steder som ikke lenger skulle være synlige.

## 1.1.05 - 2025-12-01
- Forbedring: Under "Kursdesign" kan du nå velge eksisterende sider for kurs, kurskategorier, kurssteder og instruktører i stedet for å måtte opprette nye. Du kan også bruke samme side for flere funksjoner (f.eks. én side som viser både kurs og kurskategorier). Lenker i malene oppdateres automatisk basert på hva du velger. Kun Betaling-siden opprettes automatisk ved installering.
- Fix: Gitt korrekt navn til "Lisensnøkkel", omdøpt fra "API-nøkkel"

## 1.1.04 - 2025-11-28
- Fix: Internkurs blir nå hoppet over i synken. Webhook er også fjernet i Kursagenten, så disse kursene ikke blir overført ved opprettelse/lagring. For å fjerne internkurs: Gå til Synkronisering og klikk på *Rydd opp i kurs*

## 1.1.03 - 2025-11-27
- Endret: Navn på følgende steder blir endret - Rana / Mo i Rana → Mo i Rana, Lenvik / Finnsnes → Finnsnes, Porsgrunn / Brevik → Porsgrunn og Vågan / Svolvær → Svolvær
- Fix: På taksonomisider har sted som aktivt filter på kurssteder, og kategori på kurskategorier, blitt fjernet. Dette gjelder for visningstypen "Vis alle kursdatoer".

## 1.1.02 - 2025-11-26
- Lagt til: Støtte for CPT tema-maler: single-ka_course.php, taxonomy-ka_course_location.php, taxonomy-ka_coursecategory.php og taxonomy-ka_instructors.php. Bruk støttefunksjon kursagenten_get_content(); mellom get_header og get_footer for å generere innholdet.

## 1.1.01 - 2025-11-23
- Fix: Endret hvordan *the content* behandles når det skal vises på en kursside. Alle Gutenberg-blokker skal nå fungere.
- Fix: Oppdateringslogikken gjorde enkelte sider trege. Det skal nå være reparert.

## 1.0.9 - 2025-11-17
- Fix: Lagt inn fallback api-adresse
- Lagt til: Attributt st=sted/st=ikke-sted som kan brukes i kortkoder for kurskategorier, kursliste og automenyer. Begrenser visning av kategorier og menyer til de som hører til/ikke hører til spesifikke steder, feks nettkurs/ikke-nettkurs.
- Fix: Enkelte linker til enkeltkurs fra kursliste gikk til hovedkurset. Nå går alle linker til korrekt lokasjon.
- Lagt til nye hook-plasseringer: ka_singel_header_before, ka_singel_after, ka_taxonomy_header_before og ka_taxonomy_after


## 1.0.8 - 2025-11-07
- Fix: Kurstatus vistes med ledige plasser uavhengig av status i kursliste. Rettet så det nå også viser fullt/på forspørsel.
- Lagt til: Ny designmal for taksonomi: *Profil*. Rundt hovedbilde, tittel under. Kolonne under med kort og lang beskrivelse.
- Lagt til: Mulighet for å begrense antall kurs i kortkode for kursliste: [kursliste antall=10]
- Lagt til: Ny kursliste-mal med kun status, dato, kurstittel og påmeldingsknapp. Foreløpig kun til bruk i kursliste kortkode: [kursliste list_type="date-and-title"]

## 1.0.7 - 2025-11-07
- Justering på strukturelle endringer
- Endring av admin meny. Nå er alle Kursagenten sider samlet.
- Oppdatering av innstillings-siden "Oversikt"

## 1.0.6 - 2025-11-02
- Strukturelle endringer for unngå konflikt med andre kurs-utvidelser

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


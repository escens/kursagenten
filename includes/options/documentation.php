<?php
// Documentation admin page for Kursagenten
// Comments in English; UI text in Norwegian

class KA_Documentation_Page {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_plugin_page'));
    }

    public function add_plugin_page() {
        add_submenu_page(
            'kursagenten',
            'Dokumentasjon',
            'Dokumentasjon',
            'manage_options',
            'ka_documentation',
            array($this, 'render_admin_page')
        );
    }

    public function render_admin_page() {
        ?>
        <div class="wrap options-form ka-wrap" id="toppen">
            <form method="post" action="options.php">
                <?php kursagenten_sticky_admin_menu(false); ?>
                <h1>Dokumentasjon</h1>

                <div class="options-card">
                    <h3>Kom i gang – A–Å</h3>
                    <p>Denne siden hjelper deg raskt i gang med å sette opp Kursagenten-pluginen fra A til Å. Følg stegene i rekkefølge, og bruk venstremenyen for å hoppe mellom seksjoner.</p>
                    <ol>
                        <li>Gå til <a href="admin.php?page=bedriftsinformasjon">Bedriftsinformasjon</a> og fyll ut grunnleggende firmaopplysninger.</li>
                        <li>Åpne <a href="admin.php?page=kursinnstillinger">Synkronisering</a> og koble mot Kursagenten-kontoen din. Legg inn <a href="https://kursadmin.kursagenten.no/IntegrationSettings" target="_blank">Webhooks</a> i Kursagenten, så blir kurset overført automatisk når det blir lagret/opprettet. <br>Første gang bør du klikke på "Hent alle kurs fra Kursagenten". Da henter du alle kursene samtidig.</li>
                        <li><a href="admin.php?page=design">Kursdesign</a> – Opprett ønskede sider for kurs, kategorier, steder og instruktører via "Wordpress sider". Kortkoder legges inn automatisk.</li>
                        <li>Velg filtre på kursliste i <a href="admin.php?page=design">Kursdesign</a>, og også andre designvalg (liste/grid, detaljer, og malvalg).</li>
                        <li>Sjekk <a href="admin.php?page=seo">Endre url-er</a> om du må tilpasse url-strukturer. Her kan du endre fra feks. "/kurs/ditt-kurs" til "/undervisning/ditt-kurs".</li>
                        <li>Du kan legge inn menypunkter som automatisk generer kurskategorier, kurssteder og instruktører. Se "Meny" i seksjonen "Innholdsblokker".</li>
                        <li>Kurskategorier, kurssteder og instruktører kan berikes med tekst og bilder om ønsket. Mer informasjon.</li>
                        <li>Test frontend: kursliste, enkeltkurs, kategorier, kurssteder og instruktører, og juster filtrene/design ved behov.</li>
                    </ol>
                </div>

                <h2>Kursdata fra Kursagenten</h2>

                <div id="synkronisering" class="options-card">
                    <h3>Synkronisering av kurs</h3>
                    
                    <h4>Henting av kurs fra Kursagenten</h4>
                    <p>Kursene dine hentes fra Kursagenten på to måter:</p>
                    <ul>
                        <li><strong>Manuell synkronisering:</strong> Gå til <a href="admin.php?page=kursinnstillinger">Synkronisering</a> og klikk på "Hent alle kurs fra Kursagenten". Dette bør gjøres første gang du setter opp pluginen, og ved behov for å oppdatere alle kursene samtidig.</li>
                        <li><strong>Automatisk synkronisering via webhooks:</strong> Når webhooks er konfigurert, oppdateres kursene automatisk når de endres i Kursagenten. Dette er den anbefalte metoden for løpende oppdateringer.</li>
                    </ul>
                    
                    <h4>Opprydding av kurs</h4>
                    <p>Opprydding fjerner kurs og kursdatoer som ikke lenger finnes i Kursagenten, samt utløpte kursdatoer. Dette holder nettsiden ryddig og oppdatert.</p>
                    <p>Det finnes to måter å rydde opp på:</p>
                    <ul>
                        <li><strong>Avkrysning før synkronisering:</strong> Når du klikker på "Hent alle kurs fra Kursagenten", kan du kryss av for "Rydd opp i kurs etter synkronisering". Dette kjører opprydding automatisk etter at synkroniseringen er fullført. <strong>NB:</strong> Opprydding tar 3-5 minutter ekstra.</li>
                        <li><strong>Egen opprydding:</strong> Du kan også kjøre opprydding separat ved å klikke på knappen "Rydd opp i kurs" på <a href="admin.php?page=kursinnstillinger">Synkronisering</a>-siden. Dette er nyttig hvis du bare ønsker å rydde opp uten å synkronisere alle kursene på nytt.</li>
                    </ul>
                    <p><strong>Hva ryddes opp:</strong></p>
                    <ul>
                        <li>Kurs som er slettet i Kursagenten (finnes ikke lenger i API-et)</li>
                        <li>Kursdatoer som er slettet eller utløpt</li>
                    </ul>
                    <p><strong>Automatisk opprydding:</strong> Det kjøres også en automatisk nattlig opprydding klokken 03:00 hver natt, så det er ikke alltid nødvendig å kjøre opprydding manuelt. Bruk manuell opprydding hvis du har mange utløpte kursdatoer som vises på nettsiden og ønsker å rydde opp umiddelbart.</p>
                    
                    <h4>Webhooks</h4>
                    <p>Webhooks sikrer at kursene dine alltid er oppdatert uten manuell innsats. For å aktivere webhooks:</p>
                    <ol>
                        <li>Gå til <a href="admin.php?page=kursinnstillinger">Synkronisering</a> i WordPress-administrasjonen.</li>
                        <li>Kopier webhook-URL-en som vises på siden: <code class="copytext"><?php echo esc_url(site_url('/wp-json/kursagenten-api/v1/process-webhook')); ?></code></li>
                        <li>Logg inn på <a href="https://kursadmin.kursagenten.no/IntegrationSettings" target="_blank">Kursagenten</a> og gå til <strong>Integrasjonsinnstillinger → Webhooks</strong>.</li>
                        <li>Lim inn webhook-URL-en i feltene <strong>CourseCreated</strong> og <strong>CourseUpdated</strong>.</li>
                    </ol>
                    <p>Når webhooks er aktivert, vil kursene automatisk oppdateres i WordPress når de endres eller opprettes i Kursagenten. Dette gjelder både kursdata, datoer og lokasjoner.</p>
                </div>

                <div id="stedsnavn-og-regioner" class="options-card">
                    <h3>Stedsnavn og regioner</h3>
                    <h4>Endring av stedsnavn</h4>
                    <p>Du kan endre navn på kurssteder som kommer fra Kursagenten. Dette er nyttig hvis du ønsker å bruke kortere eller mer beskrivende navn på nettsiden.</p>
                    <ul>
                        <li>Gå til <a href="admin.php?page=kursinnstillinger#location-name-mapping">Synkronisering → Navnendring på kurssteder</a>.</li>
                        <li>Klikk på "Endre navn på nytt sted" for å legge til en ny navnendring.</li>
                        <li>Velg stedet fra listen eller skriv inn navnet manuelt, og angi det nye navnet.</li>
                        <li>Klikk "Lagre" for å lagre navnendringen.</li>
                    </ul>
                    <p><strong>Viktig:</strong> Når du endrer navn på et sted:</p>
                    <ul>
                        <li>Det gamle stedet blir ikke slettet, men blir ikke lenger synlig på nettsiden.</li>
                        <li>Slugs (nettadresser) på kursene som har dette stedet oppdateres ved neste synkronisering.</li>
                        <li>Du må kjøre en full synkronisering ("Hent alle kurs fra Kursagenten") for å ta i bruk navnendringene.</li>
                        <li>Merk også "Rydd opp i kurs" før du kjører synken for å sikre at gamle kursdatoer fjernes.</li>
                    </ul>
                    
                    <h4>Regioner</h4>
                    <p>Regioner lar deg organisere kurssteder i geografiske områder (Sørlandet, Østlandet, Vestlandet, Midt-Norge, Nord-Norge). Dette er nyttig for filtrering og organisering av kurssteder.</p>
                    <ul>
                        <li>Gå til <a href="admin.php?page=kursinnstillinger#regions">Synkronisering → Regioner</a>.</li>
                        <li>Kryss av for "Aktiver regioninndeling" for å aktivere funksjonen.</li>
                        <li>Dra fylker mellom regionene for å organisere dem etter dine behov.</li>
                        <li>Endringene lagres automatisk når du flytter fylker.</li>
                        <li>Bruk "Resett til standard" for å tilbakestille regioninndelingen til standardverdiene.</li>
                    </ul>
                    <p><strong>Bruk i kortkoder:</strong> Du kan filtrere kurssteder basert på region i kortkoden <code class="copytext">[kurssteder]</code>:</p>
                    <ul>
                        <li><code class="copytext">[kurssteder region="østlandet"]</code> – viser kun kurssteder i Østlandet</li>
                        <li><code class="copytext">[kurssteder region="østlandet" vis="bergen"]</code> – viser alle steder i Østlandet eller Bergen (OR-logikk)</li>
                    </ul>
                    <p>Gyldige regioner: <code>sørlandet</code>, <code>østlandet</code>, <code>vestlandet</code>, <code>midt-norge</code>, <code>nord-norge</code></p>
                    <p><strong>Merk:</strong> Regioner må være aktivert for at filtreringen skal fungere. Du kan også endre region for individuelle kurssteder ved å redigere kursstedet i <a href="edit-tags.php?taxonomy=ka_course_location&post_type=ka_course">Kurssteder</a>-oversikten.</p>
                </div>

                <h2>Sider og systemsider</h2>

                <div id="anbefalte-sider" class="options-card">
                    <h3>Sider som opprettes automatisk</h3>
                    <p>Pluginen oppretter automatisk systemsider for enkeltsider som håndteres av pluginen. Disse sidene genereres dynamisk og trenger ikke å opprettes manuelt:</p>
                    <h4>Enkeltkurs</h4>
                    <p>Hvert kurs har sin egen side som genereres automatisk basert på valgt designmal. Innholdet hentes fra Kursagenten, inkludert bildet. Du kan legge til eget innhold mellom introtekst og hovedinnhold ved å redigere kurset. Du ser en markering for dette området når du besøker siden som administrator, samt en link for å gå til redigering. Det er også snarvei for å redigere kurset i Kursagenten.  Se etter et blyant-ikon du kan klikke på. Dette åpner kurset i Kursagenten, i en ny fane. Gjør endringene du ønsker der, lagre, og så ser du umiddelbart endringene på nettsiden.</p>                    <ul>
                    
                    <h4>Taksonomisider</h4>
                    <p>Hver kategori, hvert sted og hver instruktør får automatisk sin egen side. Disse genereres fra maler som velges i <a href="admin.php?page=design">Kursdesign</a>. Du kan berike disse sidene med ekstra innhold, bilder og tekst ved å redigere taksonomiene.</p>
                    
                    <p>Alle systemsider følger WordPress sin standard template-hierarki, så hvis temaet ditt har egne templates (f.eks. <code>single-ka_course.php</code> eller <code>taxonomy-ka_coursecategory.php</code>), vil disse brukes i stedet for pluginen sin standardmal.</p>
                </div>

                <div id="sider-som-ma-opprettes" class="options-card">
                    <h3>Sider som må opprettes</h3>
                    <p>For å vise oversikter og lister med kurs, kategorier, steder og instruktører, må du opprette vanlige WordPress-sider med kortkoder. Disse sidene bør opprettes fra <a href="admin.php?page=design#section-systemsider">Kursdesign → Wordpress sider</a>. Har du allerede sider du ønsker å bruke, velger du dem fra dropdown-menyen.</p>
                    
                    <h4>Oversiktssider med kortkoder</h4>
                    <p>Under <a href="admin.php?page=design#section-systemsider">Kursdesign → Wordpress sider</a> kan du opprette følgende sider:</p>
                    <ul>
                        <li><strong>Kurs</strong> – Inneholder <code class="copytext">[kursliste]</code> for å vise alle kurs med filtre.</li>
                        <li><strong>Kurskategorier</strong> – Inneholder <code class="copytext">[kurskategorier]</code> for å vise alle kategorier.</li>
                        <li><strong>Kurssteder</strong> – Inneholder <code class="copytext">[kurssteder]</code> for å vise alle kurssteder.</li>
                        <li><strong>Instruktører</strong> – Inneholder <code class="copytext">[instruktorer]</code> for å vise alle instruktører.</li>
                    </ul>
                    
                    <h4>Betalingsside</h4>
                    <p>Det opprettes også automatisk en side for betaling når pluginen aktiveres. Denne siden inneholder kode som genererer betalingsboksen og er nødvendig for at betalingsfunksjonaliteten skal fungere. Betalingssiden publiseres automatisk, mens de andre oversiktssidene opprettes som kladd (draft) slik at du kan redigere dem før publisering.</p>
                    
                    <h4>Hvordan opprette sidene</h4>
                    <p>Du har flere alternativer:</p>
                    <ul>
                        <li><strong>Automatisk opprettelse:</strong> Gå til <a href="admin.php?page=design#section-systemsider">Kursdesign → Wordpress sider</a> og klikk på "Opprett side" for hver side du trenger. Kortkoden legges inn automatisk.</li>
                        <li><strong>Velge eksisterende side:</strong> Du kan også velge en eksisterende WordPress-side fra dropdown-menyen og tilordne den til en funksjon. Lim inn kortkoden manuelt. Koden kan du kopiere fra ikonet <i class="ka-icon icon-code-simple-solid-full" style="height: 14px;"></i> ved siden av navnet.</li>
                    </ul>
                    
                    <p><strong>Viktig:</strong></p>
                    <ul>
                        <li>Sidene blir merket med "Kursagenten" i sideoversikten for enkel identifikasjon.</li>
                        <li>Du kan fritt endre tittel, slug og innhold på sidene. Det eneste som ikke bør fjernes er kortkoden.</li>
                        <li>Du kan legge til ekstra tekst over eller under kortkoden for introduksjon, SEO-tekst eller annen informasjon.</li>
                        <li>Designet på listene kan endres med attributter i kortkoden. Se <a href="#lister">oversikt</a> for å se hvilke valg du kan gjøre.</li>
                    </ul>
                </div>

                <div id="utseende-lister" class="options-card">
                    <h3>Styre utseende på kurslister</h3>
                    <p>Under <a href="admin.php?page=design">Kursdesign</a> styrer du listedesign på systemsidene. Det finnes innstillinger for bildebruk, antall kolonner og paginering.</p>
                    <h4>Kursliste med filter</h4>
                    <ul>
                        <li><strong>Listedesign:</strong> Velg listedesign (standard, rutenett, kompakt, enkel liste, enkle kort).</li>
                        <li><strong>Vis bilder:</strong> Skru av/på bilder i listen. Best egnet om du har lastet opp bilder til kursene i Kursagenten.</li>
                        <li><strong>Antall per side:</strong> Velg antall kurs som skal vises per side.</li>
                    </ul>
                    <h4>Taksonomisider</h4>
                    <ul>
                        <li><strong>Bredde:</strong> Velg om du vil bruke temaets standardbredde eller full bredde. Stort sett vil standardbredde fungerer som tiltenkt.</li>
                        <li><strong>Layout:</strong> Velg designet på siden. Dette styrer hvor/hvordan tittel, tekst, hovedbilde og kursliste vises.</li>
                        
                        <li><strong>Listedesign:</strong> Velg designet på kurslisten (standard, rutenett, kompakt, enkle kort...)</li>
                        <li><strong>Visningstype:</strong> Velg om du vil vise hovedkurs (ett kurs per rad/boks) eller alle kursdatoer (tilsvarende "Kursliste med filter").</li>
                        <li><strong>Vis bilder:</strong> Skru av/på bilder i listen. Best egnet om du har lastet opp bilder til kursene i Kursagenten.</li>
                    </ul>
                    <p>Disse innstillingene fungerer som standardvalg for kortkodene. Du kan også overstyre dem direkte i kortkoden med attributter. Se <a href="#lister">Kortkoder for lister og grid</a> for mer informasjon.</p>
                </div>

                <div class="options-card">
                    <h3>Filtre – slik fungerer de</h3>
                    <p>Kurslisten kan filtreres på kategori, sted, startmåned, språk m.m. Filtrene vises som del av kortkoden <code class="copytext">[kursliste]</code>. Har du opprettet sider via <a href="admin.php?page=design">Kursdesign</a> → Wordpress sider, gjelder dette siden "Kurs".</p>
                    <ul>
                        <li><strong>Plassering:</strong> Filtrene velges ved å dra dem til korrekt plassering. Du kan velge å vise dem til venstre for kurslisten og/eller over kurslisten.</li>
                        <li><strong>Tagger eller lister:</strong> Filtrene kan velges som tagger eller avkrysningsliste. Tagger er knapper som velger ett filter av gangen, og avkrysningsliste er en liste som kan velge flere filter samtidig.</li>
                        <li><strong>Rekkefølge:</strong> Du kan også dra i filtrene for å endre rekkefølgen de vises i.</li>
                        <li><strong>Visning av liste:</strong> I venstre kolonne vil listene vises som avkrysningsliste, mens over kursliten vises lister som dropdown-menyer.</li>
                        <li><strong>Test:</strong> Besøk frontend og se at det ser ut som det skal.</li>
                    </ul>
                </div>

                <div id="kortkoder" class="options-card">
                    <h3>Kortkoder</h3>
                    <p><strong>Hva er kortkoder?</strong> Kortkoder er små koder du plasserer i innhold (sider/innlegg) for å vise dynamiske lister og komponenter fra pluginen.</p>
                    <p><strong>Hvor brukes de?</strong> Vanligvis på egne sider eller innlegg (f.eks. «Kurs», «Kurskategorier», «Kurssteder», «Instruktører»). Du kan også bruke dem i menyen eller widgets.</p>
                    <p><strong>Eget innhold over/under:</strong> Du kan legge inn vanlig innhold i editoren over og under kortkoden for å skrive en introduksjon, legge til bilder eller annen informasjon.</p>
                    <div class="ka-grid ka-grid-3">
                    
                        <div class="kort">
                            <h4>Design: Lister og grid <span class="small"><a href="#lister">mer info</a></span></h4>
                            <p><span class="copytext">[kurskategorier]</span><br><span class="copytext">[kurssteder]</span><br><span class="copytext">[instruktorer]</span></p>
                        
                            <h4>Kursliste med filter <span class="small"><a href="#lister">mer info</a></span></h4>
                            <p><span class="copytext">[kursliste]</span></p>
                            
                            <h4>Bilder</h4>
                            <p><span class="copytext">[plassholderbilde-kurs]</span><br><span class="copytext">[plassholderbilde-generelt]</span><br><span class="copytext">[plassholderbilde-instruktor]</span><br><span class="copytext">[plassholderbilde-sted]</span></p>
                            
                        </div>
                        <div class="kort">
                            <h4>Bedriftsinformasjon</h4>
                            <p><span class="copytext">[firmanavn]</span><br><span class="copytext">[adresse]</span><br><span class="copytext">[postnummer]</span><br><span class="copytext">[poststed]</span><br><span class="copytext">[hovedkontakt]</span><br><span class="copytext">[epost]</span><br><span class="copytext">[telefon]</span><br><span class="copytext">[infotekst]</span><br><span class="copytext">[facebook]</span><br><span class="copytext">[instagram]</span><br><span class="copytext">[linkedin]</span><br><span class="copytext">[youtube]</span></p>
                        </div>
                        <div class="kort">
                        <h4>Meny</h4>
                        <p><span class="copytext">[ka-meny type="kurskategorier"]</span><br><span class="copytext">[ka-meny type="kurskategorier" start="din-hovedterm" st="sted/st=ikke-sted"]</span><br><span class="copytext">[ka-meny type="instruktorer"]</span><br><span class="copytext">[ka-meny type="kurssteder"]</span></p>
                        <p title="Legg inn kortkoden i tekstfeltet i en egendefinert meny. I url-feltet skriver du #. For å få menyen som en undermeny, dra dette menypunktet til høyre innunder et annet menypunkt. For å lage meny av en bestemt kategori (med underkategorier), skriv inn kategori-slug etter start=""."><img src="<?php echo esc_url(plugins_url('assets/images/admin-menu-illustration.jpg', KURSAG_PLUGIN_FILE)); ?>" alt="Kursagenten admin" style="width: 100%; max-width: 400px;"></p>
                        </div>
                        
                        
                    </div>
                </div>

                <div class="options-card">
                    <h3 id="lister">Kortkoder for lister og grid</h3>
                    <p>Kortkoder kan legges inn i teksten på sider og blogginnlegg. Du kan legge inn hele kurslisten, eller lister med enten alle kurskategorier, kurs i samme kategori (brukes på kurssider), eller instruktører.<br>Det er mange ulike valg. Du finner full kortkode under, med samtlige valg, samt en liste som forklarer alle valgene.<br>Kortkoden kopieres, og limes inn der du ønsker å vise den. <br>Merk at du må fjerne eventuelle valg du ikke trenger, og deler der flere valg er listet opp (feks som stablet/rad/liste).</p>
                    <div class="kort" style="background: #fbfbfb; padding: 1em; border-radius: 10px;">
                        <p><strong>Kursliste med filter </strong><span class="smal"><span class="copytext">[kursliste]</span></span><br><span class="copytext small">[kursliste kategori="web" sted="oslo" måned="9" språk="norsk" st=sted/st=ikke-sted klasse="min-klasse"]</span></p>
                        <p><strong>Liste med kurskategorier </strong><span class="smal"><span class="copytext">[kurskategorier]</span></span><br><span class="copytext small" style="color:#666">[kurskategorier kilde="bilde/ikon" layout="stablet/rad/liste" grid=3 gridtablet=2 gridmobil=1  radavstand="1rem" stil="standard/kort" bildestr="100px" bildeform="avrundet/rund/firkantet/10px" bildeformat="4/3" overskrift="h3" fontmin="13" fontmaks="18" avstand="2em .5em" skygge="ja" vis="hovedkategorier/subkategorier/slug/standard" st=sted/st=ikke-sted utdrag="ja" klasse="min-klasse"]</span></p>
                        <p><strong>Liste med kurssteder </strong><span class="smal"><span class="copytext">[kurssteder]</span></span><br><span class="copytext small" style="color:#666">[kurssteder layout="stablet/rad/liste" grid=3 gridtablet=2 gridmobil=1 radavstand="1rem" stil="standard/kort" bildestr="100px" bildeform="avrundet/rund/firkantet/10px" bildeformat="4/3" overskrift="h3" fontmin="13px" fontmaks="15px" avstand="2em .5em" skygge="ja" utdrag="ja" vis="standard/alta,oslo,bergen" region="østlandet" stedinfo="ja" klasse="min-klasse"]</span></p>
                        <p><strong>Liste med instruktører </strong><span class="smal"><span class="copytext">[instruktorer]</span></span><br><span class="copytext small" style="color:#666">[instruktorer layout="stablet/rad/liste" grid=3 gridtablet=2 gridmobil=1 radavstand="1rem" stil="standard/kort" bildestr="100px" bildeform="avrundet/rund/firkantet/10px" bildeformat="4/3" overskrift="h3" fontmin="13px" fontmaks="15px" avstand="2em .5em" skygge="ja" skjul="Iris,Anna" utdrag="ja" beskrivelse="ja" klasse="min-klasse"]</span></p>
                    </div>
                    <p>&nbsp;</p>
                    
                    <div class="">
                        <h3>Valg for lister og grid</h3>
                        <p>Skriver du kun kortkodene <span class="copytext">[kurskategorier]</span>, <span class="copytext">[kurssteder]</span> eller <span class="copytext">[instruktorer]</span> brukes standardvalgene.<br>Bruk eventuelt de fulle kodene over, og fjern de valgene du ikke trenger.</p>
                    
                        <table class="widefat light-grey-rows" style="border: 0; background: #fbfbfb; padding: 1em; border-radius: 10px;">
                            <colgroup>
                                <col style="width:10%;">
                                <col style="width:40%;">
                                <col style="width:20%;">
                                <col style="width:11%;">
                                <col style="width:19%;">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Valg</th>
                                    <th>Beskrivelse</th>
                                    <th>Variant</th>
                                    <th>Kan brukes på</th>
                                    <th>Eksempel</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Kilde (k)</td>
                                    <td>Du kan velge om du vil bruke hovedbilde, eller laste opp egne ikoner for lister. Velg kilde=ikon hvis du vil bruke disse.</td>
                                    <td>bilde, ikon<br><strong>Standard:</strong> bilde</td>
                                    <td>Kurskategorier</td>
                                    <td><span class="copytext">[kurskategorier kilde=ikon]</span></td>
                                </tr>
                                <tr>
                                    <td>Kilde (i)</td>
                                    <td>Du kan velge om du vil bruke et bilde du laster opp selv, eller bruke bildet hentet fra Kursagenten. Velg kilde=ka-bilde hvis du vil bruke bilde fra Kursagenten.</td>
                                    <td>bilde, ka-bilde<br><strong>Standard:</strong> bilde</td>
                                    <td>Instruktører</td>
                                    <td><span class="copytext">[instruktorer kilde=ka-bilde]</span></td>
                                </tr>
                                <tr>
                                    <td>Layout</td>
                                    <td>Ulike layout. Stablet viser bilde over kurs-/kategorinavn, rad viser bilde til venstre. Liste viser alle navn under hverandre. Liste har lavere mellomrom mellom punktene, og passer bedre med små bilder/uten bilder.</td>
                                    <td>stablet, rad, liste<br><strong>Standard:</strong> stablet</td>
                                    <td>Alle</td>
                                    <td><span class="copytext">[kurskategorier layout=rad]</span></td>
                                </tr>
                                <tr>
                                    <td>Stil</td>
                                    <td>Vis som kort, med hvit bakgrunn og skygge bak hele kortet.</td>
                                    <td>kort<br><strong>Standard</strong>: ikke kort</td>
                                    <td>Alle</td>
                                    <td><span class="copytext">[kurskategorier stil=kort]</span></td>
                                </tr>
                                <tr>
                                    <td>Grid</td>
                                    <td>Antall kolonner på desktop.</td>
                                    <td><strong>Standard:</strong> 3 kolonner</td>
                                    <td>Alle</td>
                                    <td><span class="copytext">[kurskategorier grid=4]</span></td>
                                </tr>
                                <tr>
                                    <td>Gridtablet</td>
                                    <td>Antall kolonner på tablet.&nbsp;</td>
                                    <td><strong>Standard:</strong> 2 kolonner</td>
                                    <td>Alle</td>
                                    <td><span class="copytext">[kurskategorier gridtablet=2]</span></td>
                                </tr>
                                <tr>
                                    <td>Gridmobil</td>
                                    <td>Antall kolonner på mobil.&nbsp;</td>
                                    <td><strong>Standard:</strong> 1 kolonne</td>
                                    <td>Alle</td>
                                    <td><span class="copytext">[kurskategorier gridmobil=2]</span></td>
                                </tr>
                                <tr>
                                    <td>Bildestr</td>
                                    <td>Skriv inn størrelse på bildet du ønsker, i pixler. Ønsker du ikke bilde, skriv 0. Når bildestr settes til 0, lastes ikke bildene inn i det hele tatt.</td>
                                    <td><strong>Standard:</strong> 100px</td>
                                    <td>Alle</td>
                                    <td><span class="copytext">[kurskategorier bildestr=80px]</span><br><span class="copytext">[kurskategorier bildestr=0]</span></td>
                                </tr>
                                <tr>
                                    <td>Radavstand</td>
                                    <td>Skriv inn avstanden mellom radene, i pixler, em eller rem. Ønsker du ingen avstand, skriv 0.</td>
                                    <td><strong>Standard:</strong> 1rem</td>
                                    <td>Alle</td>
                                    <td><span class="copytext">[kurskategorier radavstand=10px]</span></td>
                                </tr>
                                <tr>
                                    <td>Avstand</td>
                                    <td>Avstand rundt alle elementene, første verdi er topp og bunn, andre verdi er venstre og høyre.</td>
                                    <td><strong>Standard:</strong> 2em .5em</td>
                                    <td>Alle</td>
                                    <td><span class="copytext">[kurskategorier avstand="1em 0"]</span></td>
                                </tr>
                                <tr>
                                    <td>Bildeform</td>
                                    <td>Velg helt firkantede bilder, litt avrundet i kantene, eller runde bilder.</td>
                                    <td>avrundet, rund, firkantet<br><strong>Standard</strong>: avrundet</td>
                                    <td>Alle</td>
                                    <td><span class="copytext">[kurskategorier bildeform=rund]</span></td>
                                </tr>
                                <tr>
                                    <td>Bildeformat</td>
                                    <td>Hvorvidt bildet skal være liggende, stående eller kvadratisk.</td>
                                    <td>feks 4/3, 16/9, 1/1<br><strong>Standard</strong>: 4/3</td>
                                    <td>Alle</td>
                                    <td><span class="copytext">[kurskategorier bildeformat=16/9]</span></td>
                                </tr>
                                <tr>
                                    <td>Skygge</td>
                                    <td>Skygge ved musepeker over kurs/kurskategori.</td>
                                    <td>ja<br><strong>Standard</strong>: uten skygge</td>
                                    <td>Alle</td>
                                    <td><span class="copytext">[kurskategorier skygge=ja]</span></td>
                                </tr>
                                <tr>
                                    <td>Overskrift</td>
                                    <td>Velg hvilken overskrift du vil bruke for navnene.</td>
                                    <td>feks h3, h4, h5, p, span, div<br><strong>Standard</strong>: h3</td>
                                    <td>Alle</td>
                                    <td><span class="copytext">[kurskategorier overskrift=h4]</span></td>
                                </tr>
                                <tr>
                                    <td>Fontmin</td>
                                    <td>Teksten justerer seg etter skjermstørrelse. Dette er den minste fontstørrelsen du vil bruke for tekst og overskrifter.</td>
                                    <td>feks 13px, 15px, 18px<br><strong>Standard</strong>: 13px</td>
                                    <td>Alle</td>
                                    <td><span class="copytext">[kurskategorier fontmin=15px]</span></td>
                                </tr>
                                <tr>
                                    <td>Fontmaks</td>
                                    <td>Dette er den største fontstørrelsen du vil bruke for tekst og overskrifter.</td>
                                    <td>feks 15px, 18px, 26px<br><strong>Standard</strong>: 18px</td>
                                    <td>Alle</td>
                                    <td><span class="copytext">[kurskategorier fontmaks=18px]</span></td>
                                </tr>
                                <tr>
                                    <td>Vis (k)</td>
                                    <td>For de kategoriene som har flere nivåer, er det mulighet til å vise kun toppnivåene, kun underkategoriene, kun underkategorier under gitt foreldreslug, eller alle.</td>
                                    <td>hovedkategorier, subkategorier, foreldreslug (feks dans eller truck)<br><strong>Standard</strong>: viser alle</td>
                                    <td>Kurskategorier</td>
                                    <td><span class="copytext">[kurskategorier vis=hovedkategorier/subkategorier/slug]</span></td>
                                </tr>
                                <tr>
                                    <td>Vis (i)</td>
                                    <td>Velg å vise fornavn, etternavn eller begge.</td>
                                    <td>fornavn, etternavn<br><strong>Standard</strong>: viser fullt navn</td>
                                    <td>Instruktører</td>
                                    <td><span class="copytext">[instruktorer vis=fornavn]</span></td>
                                </tr>
                                <tr>
                                    <td>Vis (s)</td>
                                    <td>Filtrer stedslisten til kun vise spesifikke steder. Kan bruke stedsnavn eller slug (case-insensitive).</td>
                                    <td>standard, kommaseparert liste (feks alta,oslo,bergen eller "Oslo,Mo i Rana")<br><strong>Standard</strong>: viser alle steder</td>
                                    <td>Kurssteder</td>
                                    <td><span class="copytext">[kurssteder vis=alta,oslo,bergen]</span></td>
                                </tr>
                                <tr>
                                    <td>Region</td>
                                    <td>Filtrer kurssteder basert på region (kun hvis regioner er aktivert). Kan kombineres med vis-parameteren (OR-logikk: viser steder fra regionen ELLER de spesifiserte stedene).</td>
                                    <td>sørlandet, østlandet, vestlandet, midt-norge, nord-norge<br><strong>Standard</strong>: ingen filtrering</td>
                                    <td>Kurssteder</td>
                                    <td><span class="copytext">[kurssteder region="østlandet"]</span><br><span class="copytext">[kurssteder region="østlandet" vis="bergen"]</span></td>
                                </tr>
                                <tr>
                                    <td>Stedinfo</td>
                                    <td>Vis liste med spesifikke lokasjoner under hvert stedsnavn. Dette kommer fra feltet "Fritekst sted" i Kursagenten. </td>
                                    <td>ja<br><strong>Standard</strong>: vises ikke</td>
                                    <td>Kurssteder</td>
                                    <td><span class="copytext">[kurssteder stedinfo=ja]</span></td>
                                </tr>
                                <tr>
                                    <td>Skjul</td>
                                    <td>Skjul instruktør ved å skrive en kommaseparert liste med fornavn til instruktør slik det er skrevet i feltet "fornavn"</td>
                                    <td>kommasepartert liste<br><strong>Standard</strong>: viser alle</td>
                                    <td>Instruktører</td>
                                    <td><span class="copytext">[instruktor skjul=Anna,Per]</span></td>
                                </tr>
                                <tr>
                                    <td>St</td>
                                    <td>Velg å vise/skjule kurskategorier/menypunkter som hører til spesifikke steder. Eksempel: vis alle kurs som ikke er nettkurs: st="ikke-nettbasert"</td>
                                    <td>sted, ikke-sted<br><strong>Standard</strong>: viser alle</td>
                                    <td>Kurskategorier, automenyer</td>
                                    <td><span class="copytext">[kurskategorier st=sted]<br>[ka-meny type="kurskategorier" st=sted]</span></td>
                                </tr>
                                <tr>
                                    <td>Utdrag</td>
                                    <td>Vis tekst fra feltet "Kort beskrivelse".</td>
                                    <td>ja<br><strong>Standard</strong>: viser ikke</td>
                                    <td>Alle</td>
                                    <td><span class="copytext">[kurssted utdrag=ja]</span></td>
                                </tr>
                                <tr>
                                    <td>Beskrivelse</td>
                                    <td>Vis tekst fra feltet "Utvidet beskrivelse". Merk at dette vil overskrive utdrag.</td>
                                    <td>ja<br><strong>Standard</strong>: viser ikke</td>
                                    <td>Instruktører</td>
                                    <td><span class="copytext">[instruktorer beskrivelse=ja]</span></td>
                                </tr>
                                <tr>
                                    <td>Klasse</td>
                                    <td>Legg til egendefinert CSS-klasse til wrapper-elementet. Nyttig for custom styling eller tema-spesifikke behov.</td>
                                    <td>tekst<br><strong>Standard</strong>: tom (ingen klasse)</td>
                                    <td>Alle</td>
                                    <td><span class="copytext">[kurskategorier klasse="min-egen-klasse"]</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <h2>Ekstra innhold</h2>

                <div id="berike-taksonomisider" class="options-card">
                    <h3>Berike taksonomisider</h3>
                    <p>Alle taksonomisider (kategorier, kurssteder og instruktører) kan berikes med ekstra innhold som bilder, tekst og beskrivelser. Dette gjør at du kan tilpasse hver enkelt side med unikt innhold utover det som kommer fra Kursagenten.</p>
                    <p>Designmalene viser frem innholdet på forskjellige måter.</p>
                    
                    <h4>Hvordan legge til innhold</h4>
                    <p>Gå til oversikten over taksonomiene (f.eks. <a href="edit-tags.php?taxonomy=ka_coursecategory&post_type=ka_course">Kurskategorier</a>, <a href="edit-tags.php?taxonomy=ka_course_location&post_type=ka_course">Kurssteder</a> eller <a href="edit-tags.php?taxonomy=ka_instructors&post_type=ka_course">Instruktører</a>) og klikk på "Rediger" på den taksonomien du vil berike. Her kan du legge til:</p>
                    <ul>
                        <li><strong>Bilde:</strong> Last opp et hovedbilde som vises på taksonomisiden og i lister.</li>
                        <li><strong>Kort beskrivelse:</strong> En kort tekst som kan vises i lister og oversikter. Vises ofte direkte under overskriften.</li>
                        <li><strong>Utvidet beskrivelse:</strong> En lengre tekst med rik tekstformatering (HTML) som vises på taksonomisiden. Her kan du beskrive kategorien, stedet eller instruktøren med tekst, bilder og annen informasjon.</li>
                        <li><strong>Ekstra felter:</strong> Spesifikke felter avhengig av taksonomitypen (se nedenfor).</li>
                    </ul>
                </div>

                <div id="kurskategorier-innhold" class="options-card">
                    <h3>Kurskategorier</h3>
                    <p>Kurskategorier kan berikes med bilder, ikoner og beskrivelser. Du kan også kontrollere hvor kategoriene vises.</p>
                    
                    <h4>Tilgjengelige felter</h4>
                    <ul>
                        <li><strong>Navn:</strong> Kategorinavnet (kommer fra Kursagenten, bør ikke endres).</li>
                        <li><strong>Slug:</strong> URL-vennlig versjon av navnet (bør ikke endres da det kan føre til ødelagte lenker).</li>
                        <li><strong>Hovedbilde:</strong> Et bilde som vises på kategorisiden og i lister.</li>
                        <li><strong>Ikon:</strong> Et alternativt bilde som kan brukes i stedet for hovedbilde i lister. Her er det fint å laste opp png-ikoner (bruk <code>kilde=ikon</code> i kortkoden).</li>
                        <li><strong>Kort beskrivelse:</strong> En kort tekst som kan vises i lister og oversikter. Vises ofte direkte under overskriften.</li>
                        <li><strong>Utvidet beskrivelse:</strong> En lengre tekst med HTML-formatering som vises på kategorisiden.</li>
                    </ul>
                    
                    <h4>Synlighet og skjuling</h4>
                    <p>Du kan kontrollere hvor kategoriene vises med tre synlighetsinnstillinger:</p>
                    <ul>
                        <li><strong>Skjul i oversiktslister:</strong> Når aktivert, skjules kategorien i kortkoder som <code>[kurskategorier]</code> og lignende lister. Kategorien vil fortsatt være tilgjengelig direkte via URL.</li>
                        <li><strong>Skjul i automenyer:</strong> Når aktivert, skjules kategorien i autogenererte menyer som bruker kortkoden <code>[ka-meny]</code>. Dette er nyttig hvis du har kategorier som ikke skal vises i hovedmenyen.</li>
                        <li><strong>Skjul i kursliste:</strong> (Kun for kurskategorier) Når aktivert, skjules både kategorien i kategorifilteret og alle kurs som tilhører denne kategorien i kurslisten. Dette er nyttig for interne kategorier eller kategorier som ikke skal være synlige for besøkende.</li>
                    </ul>
                    <p>Disse innstillingene kan settes både ved redigering av kategorien og via hurtigredigering i oversikten.</p>
                </div>

                <div id="kurssteder-innhold" class="options-card">
                    <h3>Kurssteder</h3>
                    <p>Kurssteder kan berikes med bilder og beskrivelser. Du kan også endre navn på steder og organisere dem i regioner.</p>
                    
                    <h4>Tilgjengelige felter</h4>
                    <ul>
                        <li><strong>Navn:</strong> Stedsnavnet (kommer fra Kursagenten, men kan endres via navnendring-funksjonen).</li>
                        <li><strong>Slug:</strong> URL-vennlig versjon av navnet (oppdateres automatisk ved navnendring).</li>
                        <li><strong>Bilde:</strong> Et bilde som vises på stedssiden og i lister.</li>
                        <li><strong>Kort beskrivelse:</strong> En kort tekst som kan vises i lister. Vises ofte direkte under overskriften.</li>
                        <li><strong>Utvidet beskrivelse:</strong> En lengre tekst med HTML-formatering som vises på stedssiden.</li>
                        <li><strong>Region:</strong> (Hvis regioner er aktivert) Velg hvilken region stedet tilhører.</li>
                    </ul>
                    
                    <h4>Navnendring</h4>
                    <p>Du kan endre navn på kurssteder som kommer fra Kursagenten. Dette gjøres via <a href="admin.php?page=kursinnstillinger#location-name-mapping">Synkronisering → Navnendring på kurssteder</a>. Se <a href="#stedsnavn-og-regioner">Stedsnavn og regioner</a> for mer informasjon.</p>
                    <p><strong>Viktig:</strong> Navnet på kursstedet i WordPress-administrasjonen er skrivebeskyttet når det kommer fra Kursagenten. For å endre navnet som vises på nettsiden, bruk navnendring-funksjonen i stedet for å redigere taksonomien direkte.</p>
                    
                    <h4>Regioner</h4>
                    <p>Hvis regioner er aktivert, kan du tilordne hvert kurssted til en region (Sørlandet, Østlandet, Vestlandet, Midt-Norge, Nord-Norge). Dette kan gjøres på to måter:</p>
                    <ul>
                        <li><strong>Automatisk:</strong> Steder blir automatisk tilordnet til regioner basert på fylke når regioner er aktivert.</li>
                        <li><strong>Manuelt:</strong> Du kan endre region for individuelle steder ved å redigere kursstedet i <a href="edit-tags.php?taxonomy=ka_course_location&post_type=ka_course">Kurssteder</a>-oversikten.</li>
                    </ul>
                    <p>Se <a href="#stedsnavn-og-regioner">Stedsnavn og regioner</a> for mer informasjon om hvordan regioner fungerer.</p>
                    
                    <h4>Synlighet</h4>
                    <p>Kurssteder har samme synlighetsinnstillinger som kurskategorier (skjul i oversiktslister og i automenyer).</p>
                </div>

                <div id="instruktorer-innhold" class="options-card">
                    <h3>Instruktører</h3>
                    <p>Instruktører kan berikes med bilder, kontaktinformasjon og beskrivelser. Du kan også opprette egne instruktører som ikke kommer fra Kursagenten, og overskrive data fra Kursagenten.</p>
                    
                    <h4>Tilgjengelige felter</h4>
                    <ul>
                        <li><strong>Navn:</strong> Instruktørens navn (kan deles opp i fornavn og etternavn).</li>
                        <li><strong>Profilbilde:</strong> Et hovedbilde som vises på instruktørsiden og i lister. Dette kan være fra Kursagenten eller et eget opplastet bilde.</li>
                        <li><strong>Alternativt bilde:</strong> Et ekstra bilde som kan brukes på instruktørsiden.</li>
                        <li><strong>E-post:</strong> Instruktørens e-postadresse.</li>
                        <li><strong>Telefon:</strong> Instruktørens telefonnummer.</li>
                        <li><strong>Kort beskrivelse:</strong> En kort tekst som kan vises i lister.</li>
                        <li><strong>Utvidet beskrivelse:</strong> En lengre tekst med HTML-formatering som vises på instruktørsiden.</li>
                    </ul>
                    
                    <h4>Overskrive data fra Kursagenten</h4>
                    <p>For instruktører som kommer fra Kursagenten, kan du overskrive dataene med egne verdier:</p>
                    <ul>
                        <li><strong>Overstyr profilbilde:</strong> Aktiver denne for å bruke et eget opplastet bilde i stedet for bildet fra Kursagenten.</li>
                        <li><strong>Overstyr profil fra Kursagenten:</strong> Aktiver denne for å kunne redigere navn, e-post og telefon. Når aktivert, vil ikke disse feltene oppdateres automatisk fra Kursagenten ved synkronisering.</li>
                    </ul>
                    <p><strong>Viktig:</strong> Når du overskriver felter, vil de ikke lenger oppdateres automatisk fra Kursagenten. Du må manuelt oppdatere dem hvis det er endringer i Kursagenten.</p>
                    
                    <h4>Opprette egne instruktører</h4>
                    <p>Du kan opprette instruktører direkte i WordPress som ikke kommer fra Kursagenten:</p>
                    <ol>
                        <li>Gå til <a href="edit-tags.php?taxonomy=ka_instructors&post_type=ka_course">Instruktører</a>-oversikten.</li>
                        <li>Klikk på "Legg til ny instruktør".</li>
                        <li>Fyll ut navn og eventuelt slug.</li>
                        <li>Klikk "Legg til ny instruktør".</li>
                        <li>Rediger instruktøren for å legge til bilder, kontaktinformasjon og beskrivelser.</li>
                    </ol>
                    <p>Egne instruktører fungerer på samme måte som instruktører fra Kursagenten, men de vil ikke oppdateres automatisk og kan fritt redigeres uten å aktivere "overstyr"-funksjoner.</p>
                    <h4>Vise fullt navn, fornavn eller etternavn</h4>
                    <p>Du kan velge å vise fullt navn, fornavn eller etternavn på instruktørsiden og i listen med instruktører. Du må både endre innstillinger i Kursdesign og i Wordpress-siden som viser instruktørene.</p>
                        <ol>
                            <li>Gå til <a href="admin.php?page=design#design-taksonomi">Kursdesign → Taksonomisider</a></li>
                            <li>Klikk på "Egne innstillinger for instruktører"</li>
                            <li>Velg "Fullt navn", "Fornavn" eller "Etternavn" i feltet "Navnevisning".</li>
                            <li>Klikk "Lagre".</li>
                            <li>Gå til <a href="admin.php?page=design#section-systemsider">Wordpress sider</a>. Rediger siden med instruktøroversikten og legg til vis="fornavn" eller vis="etternavn" i kortkoden for å vise kun fornavn eller etternavn. Du kan gå direkte til redigering fra <a href="admin.php?page=design#section-systemsider">Wordpress sider</a>.</li>
                        </ol>
                    <h4>Synlighet</h4>
                    <p>Instruktører har samme synlighetsinnstillinger som kurskategorier (skjul i oversiktslister og i automenyer). Du kan også skjule spesifikke instruktører i kortkoder ved å bruke <code>skjul</code>-parameteren i kortkoden <code>[instruktorer]</code>.</p>
                </div>

                <h2>Design</h2>

                <div id="designmaler-kurs" class="options-card">
                    <h3>Designmaler for kurs</h3>
                    <p>I <a href="admin.php?page=design#section-enkeltkurs">Kursdesign → Enkeltkurs</a> velger du designmal for enkeltsider for kurs. Designmalen påvirker hele oppsettet av kursdetaljsiden:</p>
                    <ul>
                        <li><strong>Layout og struktur:</strong> Hvor elementene plasseres på siden (header, innhold, sidekolonne, footer).</li>
                        <li><strong>Rekkefølge på elementer:</strong> Hvilken rekkefølge informasjonen vises i (introtekst, hovedinnhold, kursdatoer, instruktører, osv.).</li>
                        <li><strong>Visuelle detaljer:</strong> Styling, spacing og annet visuelt design.</li>
                    </ul>
                    <p>Ved å bytte designmal endres hele presentasjonen av kurset. Dette kan påvirke hvordan besøkende opplever kursinformasjonen.</p>
                    <p>Det er foreløpig ikke mange designmaler tilgjengelig for enkeltkurs. Vi planlegger å utvide med flere designmaler snart.</p>
                    <p><strong>Viktig:</strong> Endringer i designmal kan kreve oppdatering/refresh av cache og permalenker, spesielt ved utstrakte URL-tilpasninger.</p>
                </div>

                <div id="design-taksonomi" class="options-card">
                    <h3>Design på taksonomi</h3>
                    <p>I <a href="admin.php?page=design">Kursdesign</a> kan du velge designmaler og innstillinger for taksonomisider (kategorier, kurssteder og instruktører).</p>
                    
                    <h4>Designmaler</h4>
                    <p>Du kan velge en felles designmal for alle taksonomier, eller aktivere separate designmaler for hver taksonomitype:</p>
                    <ul>
                        <li><strong>Felles designmal:</strong> Alle taksonomier bruker samme designmal.</li>
                        <li><strong>Separate designmaler:</strong> Aktiver dette for å kunne velge forskjellige designmaler for kategorier, steder og instruktører.</li>
                    </ul>
                    
                    <h4>Listetype</h4>
                    <p>Velg hvordan kurslistene på taksonomisidene skal vises:</p>
                    <ul>
                        <li><strong>Standard:</strong> En tradisjonell listevisning med kurskort, ett kurs per rad/boks.</li>
                        <li><strong>Grid:</strong> Et rutenett med kurskort i flere kolonner. Mulig å velge antall kolonner for desktop, tablet og mobil.</li>
                        <li><strong>Kompakt:</strong> En mer kompakt listevisning med mindre mellomrom. Uten bakgrunn, og med færre kurselementer</li>
                        <li><strong>Ren og enkel liste:</strong> Basert på Standard liste, men uten bakgrunn, og med færre kurselementer</li>
                        <li><strong>Enkle kort:</strong> Hvite kort med overskrift og tekst. Viser neste tilgjengelige dato for kursene. Mulig å velge antall kolonner for desktop, tablet og mobil.</li>
                    </ul>
                    
                    <h4>Layout</h4>
                    <p>Velg designet på siden. Dette styrer hvor/hvordan tittel, tekst, hovedbilde og kursliste vises.</p>
                    <ul>
                        <li><strong>Standard - med bilde og beskrivelse:</strong> Tittel og beskrivelse, deretter bilde og beskrivelse, deretter kursliste</li>
                        <li><strong>Enkel - kun tittel og kort beskrivelse:</strong> Tittel og kort beskrivelse, deretter kursliste. Egnet når du ikke har bilder eller utvidet beskrivelse.</li>
                        <li><strong>Profil - rundt bilde og tittel:</strong> Rundt bilde, deretter tittel og beskrivelse, deretter kursliste</li>
                    </ul>
                    
                    <h4>Innstillinger for kursliste</h4>
                    <p>Du kan også konfigurere hvordan kurslistene på taksonomisidene skal vises. Se <a href="#section-styre-utseende-på-kurslister">Styre utseende på kurslister</a> for mer informasjon.</p>
                </div>

                <div id="hooks" class="options-card">
                    <h3>Hooks</h3>
                    <div class="ka-grid ka-grid-3">
                        <div class="kort">
                                <h4>Taksonomi-sider</h4>
                                <p style="line-height: 1.8;">
                                    <strong>Header før</strong> <span class="copytext">ka_taxonomy_header_before</span><br><span style="color:#777;font-style:italic"> – Før hele header-seksjonen (før &lt;article&gt;).</span><br>
                                    <strong>Header etter tittel</strong> <span class="copytext">ka_taxonomy_after_title</span><br><span style="color:#777;font-style:italic"> – Vises rett etter H1 i toppseksjonen.</span><br>
                                    <strong>Header etter seksjon</strong> <span class="copytext">ka_taxonomy_header_after</span><br><span style="color:#777;font-style:italic"> – Vises rett under hele header-blokken.</span><br>
                                    <strong>Venstre kolonne</strong> <span class="copytext">ka_taxonomy_left_column</span><br><span style="color:#777;font-style:italic"> – Plassering for innhold i venstre kolonne.</span><br>
                                    <strong>Høyre kolonne topp</strong> <span class="copytext">ka_taxonomy_right_column_top</span><br><span style="color:#777;font-style:italic"> – Øverst i høyre kolonne.</span><br>
                                    <strong>Høyre kolonne bunn</strong> <span class="copytext">ka_taxonomy_right_column_bottom</span><br><span style="color:#777;font-style:italic"> – Nederst i høyre kolonne.</span><br>
                                    <strong>Under bilde og beskrivelse</strong> <span class="copytext">ka_taxonomy_below_description</span><br><span style="color:#777;font-style:italic"> – Like under hovedbilde/utvidet beskrivelse, før kurslisten.</span><br>
                                    <strong>Før kursliste</strong> <span class="copytext">ka_courselist_before</span><br><span style="color:#777;font-style:italic"> – Under overskrift, over filter/paginering og liste.</span><br>
                                    <strong>Etter paginering</strong> <span class="copytext">ka_taxonomy_pagination_after</span><br><span style="color:#777;font-style:italic"> – Rett under pagineringskontroller i mal "Standard".</span><br>
                                    <strong>Footer</strong> <span class="copytext">ka_taxonomy_footer</span><br><span style="color:#777;font-style:italic"> – Helt nederst, etter kurslisten (bunnseksjon).</span><br>
                                    <strong>Etter hele siden</strong> <span class="copytext">ka_taxonomy_after</span><br><span style="color:#777;font-style:italic"> – Etter hele footer-seksjonen (etter &lt;/article&gt;).</span>
                                </p>
                        </div>
                        <div class="kort">
                            <h4>Enkeltkurs</h4>
                            <p style="line-height: 1.8;">
                            <strong>Header før</strong> <span class="copytext">ka_singel_header_before</span><br><span style="color:#777;font-style:italic"> – Før hele header-seksjonen (før &lt;article&gt;).</span><br>
                            <strong>Header etter tittel</strong> <span class="copytext">ka_singel_header_links_after</span><br><span style="color:#777;font-style:italic"> – Etter lenkene i header-seksjonen.</span><br>
                            <strong>Header etter</strong> <span class="copytext">ka_singel_header_after</span><br><span style="color:#777;font-style:italic"> – Rett under hele header-blokken.</span><br>
                            <strong>Kursliste etter</strong> <span class="copytext">ka_singel_courselist_after</span><br><span style="color:#777;font-style:italic"> – Etter eventuell kursliste-seksjon på detaljsiden.</span><br>
                            <strong>Neste kurs</strong> <span class="copytext">ka_singel_nextcourse_after</span><br><span style="color:#777;font-style:italic"> – Etter modulen "Neste kurs".</span><br>
                            <strong>Introtekst før</strong> <span class="copytext">ka_singel_content_intro_before</span><br><span style="color:#777;font-style:italic"> – Før introtekst.</span><br>
                            <strong>Introtekst etter</strong> <span class="copytext">ka_singel_content_intro_after</span><br><span style="color:#777;font-style:italic"> – Etter introtekst.</span><br>
                            <strong>Hovedinnhold før</strong> <span class="copytext">ka_singel_content_before</span><br><span style="color:#777;font-style:italic"> – Før hovedinnholdet.</span><br>
                            <strong>Hovedinnhold etter</strong> <span class="copytext">ka_singel_content_after</span><br><span style="color:#777;font-style:italic"> – Etter hovedinnholdet.</span><br>
                            <strong>Sidekolonne før</strong> <span class="copytext">ka_singel_aside_before</span><br><span style="color:#777;font-style:italic"> – Før sidekolonne/aside.</span><br>
                            <strong>Sidekolonne etter</strong> <span class="copytext">ka_singel_aside_after</span><br><span style="color:#777;font-style:italic"> – Etter sidekolonne/aside.</span><br>
                            <strong>Footer før</strong> <span class="copytext">ka_singel_footer_before</span><br><span style="color:#777;font-style:italic"> – Rett før footer.</span><br>
                            <strong>Footer etter</strong> <span class="copytext">ka_singel_footer_after</span><br><span style="color:#777;font-style:italic"> – Rett etter footer-seksjonen.</span><br>
                            <strong>Etter hele siden</strong> <span class="copytext">ka_singel_after</span><br><span style="color:#777;font-style:italic"> – Etter hele footer-seksjonen (etter &lt;/article&gt;).</span><br>
                            </p>
                        </div>

                        <div class="kort">
                            <h4>Annet</h4>
                            <p>Hooks kommer...<br><span class="copytext"></span></p>
                         </div>
                        
                        
                    </div>
                </div>

                <!-- Tilgjengelige ikoner -->
                <?php if (function_exists('kursagenten_icon_overview_shortcode')): ?>
                    <div id="tilgjengelige-ikoner" class="options-card">
                    <h3>Tilgjengelige ikoner</h3>
                    <p>Ikoner er tilgjengelige som html med css-klasser. Du kan bruke dem direkte i HTML-kode. Styr størrelse og farge med width, height og background-color på i.ka-icon. Husk å legge til <i>icon-</i> før navnet på ikonet. F.eks. icon-calendar. Eksempel:</p>
                    <pre><code class="copytext">&lt;i class="ka-icon icon-calendar"&gt;&lt;/i&gt;</code></pre> 
                    <style>
                        .ka-wrap i.ka-icon {
                            height: 20px;
                        }
                    </style>

                        <?php echo kursagenten_icon_overview_shortcode(); ?>
                    </div>
                <?php endif; ?>

                <h2>Annet</h2>

                <div class="options-card">
                    <h3>Tips og feilsøking</h3>
                    <ul>
                        <li><strong>Permalenker:</strong> Ved endring av URL-innstillinger, lagre «Permalenker» på nytt.</li>
                        <li><strong>Cache:</strong> Tøm cache hvis du ikke ser endringer umiddelbart.</li>
                        <li><strong>Bilder:</strong> Bruk plassholder-bilder via kortkodene om du mangler bilder.</li>
                        <li><strong>Synkronisering:</strong> Hvis kurs ikke oppdateres automatisk, sjekk at webhooks er konfigurert korrekt i Kursagenten.</li>
                        <li><strong>Designendringer:</strong> Hvis designendringer ikke vises, kan det være nødvendig å tømme cache og oppdatere permalenker.</li>
                    </ul>
                </div>

                    <?php kursagenten_admin_footer(); ?>
        <?php
    }
}

if (is_admin()) {
    new KA_Documentation_Page();
}

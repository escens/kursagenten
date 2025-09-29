
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
                <h2>Dokumentasjon</h2>

                <div class="options-card">
                    <h3>Kom i gang- A–Å</h3>
                    <p>Denne siden hjelper deg raskt i gang med å sette opp Kursagenten-pluginen fra A til Å. Følg stegene i rekkefølge, og bruk venstremenyen for å hoppe mellom seksjoner.</p>
                    <ol>
                        <li>Gå til <a href="admin.php?page=bedriftsinformasjon">Bedriftsinformasjon</a> og fyll ut grunnleggende firmaopplysninger.</li>
                        <li>Åpne <a href="admin.php?page=kursinnstillinger">Synkronisering</a> og koble mot Kursagenten-kontoen din. Legg inn <a href="https://kursadmin.kursagenten.no/IntegrationSettings" target="_blank">Webhooks</a> i Kursagenten, så blir kurset overført automatisk når det blir lagret/opprettet. <br>Første gang bør du klikke på "Hent alle kurs fra Kursagenten". Da henter du alle kursene samtidig.</li>
                        <li><a href="admin.php?page=design">Kursdesign</a> - Opprett ønskede sider for kurs, kategorier, steder og instruktører via "Wordpress sider". Kortkoder legges inn automatisk.</li>
                        <li>Velg filtre på kursliste i <a href="admin.php?page=design">Kursdesign</a>, og også andre designvalg (liste/grid, detaljer, og malvalg).</li>
                        <li>Sjekk <a href="admin.php?page=seo">Endre url-er</a> om du må tilpasse url-strukturer. Her kan du endre fra feks. "/kurs/ditt-kurs" til "/undervisning/ditt-kurs".</li>
                        <li>Du kan legge inn menypunkter som automatisk generer kurskategorier, kurssteder og instruktører. Se "Meny" i seksjonen "Innholdsblokker".</li>
                        <li>Kurskategorier, kurssteder og instruktører kan berikes med tekst og bilder om ønsket. Mer informasjon.</li>
                        <li>Test frontend: kursliste, enkeltkurs, kategorier, kurssteder og instruktører, og juster filtrene/design ved behov.</li>
                    </ol>
                </div>

                <div id="anbefalte-sider" class="options-card">
                    <h3>Anbefalte sider som bør opprettes</h3>
                    <p>Under "Kursdesign" kan du opprette anbefalte sider med lister for kurs, kategorier, steder og instruktører. Disse inneholder kortkoder som blir generert automatisk. Anbefalte sider:</p>
                    <ul>
                        <li><strong>Kurs</strong> – Inneholder <code class="copytext">[kursliste]</code>.</li>
                        <li><strong>Kurskategorier</strong> – Inneholder <code class="copytext">[kurskategorier]</code>.</li>
                        <li><strong>Kurssteder</strong> – Inneholder <code class="copytext">[kurssteder]</code>.</li>
                        <li><strong>Instruktører</strong> – Inneholder <code class="copytext">[instruktorer]</code>.</li>
                    </ul>
                    <p>Klikk på "Opprett sider" for å opprette sider automatisk. Fra oversikten kan du slette sidene, gå direkte til redigering, eller tilbakestille innholdet. 
                        Sidene vil bli merket med "Kursagenten" i sideoversikten. 
                        <br>Du kan gi sidene valgfrie tittler og innhold. Det eneste som ikke bør fjernes er kortkoden. Du kan også justere visningen av innholdet som blir generert med kortkoden. Se oversikt for å se hvilke valg du kan gjøre.
                        <br>Oppretter du sidene manuelt, må du lime inn riktig kortkode. Du kan fritt legge til ekstra tekst over/under.</p>
                </div>

                <div id="sider-systemsider" class="options-card">
                    <h3>Vanlige sider og systemsider- hva er automatisk og hva er malstyrt</h3>
                    <p>Systemsider er enkeltsider som pluginen håndterer for deg – for eksempel taksonomi-sider (kategorier, kurssteder, instruktører) og enkeltsider for kurs.</p>
                    <ul>
                        
                        <li><strong>Kurs enkeltsider</strong>: Enkeltkurs blir generert fra standardmaler som blir valgt i «Kursdesign». Innholdet hentes fra Kursagenten. Det er mulig å legge inn eget innhold som vises mellom "Introtekst" fra Kursagenten og "Innhold". Du finner redigeringslink når du besøker enkeltkurset.</li>
                        <li><strong>Taksonomi enkeltsider</strong> Hver enkeltkategori, hvert sted, og hver enkeltside for instruktør blir generert fra maler. Det er mulighet for å legge inn ekstra innhold, som feks bilder og tekst. Malen som blir brukt velger du i «Kursdesign».</li>
                        <li><strong>Oversiktsider</strong>: Oversiktsider/lister med kurs, kategorier, steder og instruktører opprettes i vanlige Wordpress-sider med kortkoder. Kortkodene kan justeres for å oppnå ulike design, som feks kort, lister og annet.</li>
                    </ul>
                </div>

                <div id="kortkoder-oversikt" class="options-card">
                    <h3>Kortkoder- rask oversikt</h3>
                    <p><strong>Hva er kortkoder?</strong> Kortkoder er små koder du plasserer i innhold (sider/innlegg) for å vise dynamiske lister og komponenter fra pluginen.</p>
                    <ul>
                        <li><code class="copytext">[kursliste]</code> – Viser kursliste med filter.</li>
                        <li><code class="copytext">[kurskategorier]</code> – Viser kategorier som liste eller grid.</li>
                        <li><code class="copytext">[kurssteder]</code> – Viser kurssteder.</li>
                        <li><code class="copytext">[instruktorer]</code> – Viser instruktører.</li>
                    </ul>
                    <p><strong>Hvor brukes de?</strong> Vanligvis på egne sider i menyen (f.eks. «Kurs», «Kurskategorier», «Kurssteder», «Instruktører»). Du kan også bruke dem i innlegg eller widgets.</p>
                    <p><strong>Eget innhold over/under:</strong> Du kan legge inn vanlig innhold i editoren over og under kortkoden for å gi introduksjon, SEO-tekst eller annen informasjon.</p>
                </div>


                <div id="designmaler" class="options-card">
                    <h3>Designmaler for kurs og taksonomi</h3>
                    <p>I <a href="admin.php?page=design">Kursdesign</a> velger du mal for kursdetaljer og for taksonomier. Å bytte mal påvirker oppsett, rekkefølge på elementer og visuelle detaljer.</p>
                    <ul>
                        <li><strong>Enkeltkurs</strong>: velg dedikert mal for kursdetaljsiden.</li>
                        <li><strong>Kategorier/steder/instruktører</strong>: velg mal for lister og enkeltsider.</li>
                    </ul>
                    <p>Endringer kan kreve oppdatering/refresh av cache og permalenker ved utstakte URL-tilpasninger.</p>
                </div>

                <div id="utseende-lister" class="options-card">
                    <h3>Styre utseende på lister</h3>
                    <p>Under <a href="admin.php?page=design">Kursdesign</a> styrer du listeutseende (grid, kort, feltvisning). Det finnes innstillinger for bildebruk, metainformasjon, antall kolonner og paginering.</p>
                    <ul>
                        <li>Velg listetype (liste eller grid) for «Kursliste», «Kategorier», «Steder», «Instruktører».</li>
                        <li>Skru av/på elementer som bilde, beskrivelse, metadata, knapper.</li>
                        <li>Angi antall per side og paginering.</li>
                    </ul>
                </div>

                <div class="options-card">
                    <h3>Filtre- slik fungerer de</h3>
                    <p>Kurslisten kan filtreres på kategori, sted, tidspunkt m.m. Filtrene vises som del av kortkoden <code class="copytext">[kursliste]</code>. Du kan typisk konfigurere standardvalg og felt i «Kursdesign».</p>
                    <ul>
                        <li>Standardvalg for filtre settes i designinnstillinger.</li>
                        <li>Besøk frontend og test ulike kombinasjoner for å verifisere at dataene som kommer fra Kursagenten vises korrekt.</li>
                    </ul>
                </div>

                <div id="kortkoder" class="options-card">
                <h3>Kortkoder</h3>
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
                    <p><span class="copytext">[ka-meny type="kurskategorier" start="din-hovedterm"]</span><br><span class="copytext">[ka-meny type="instruktorer"]</span><br><span class="copytext">[ka-meny type="kurssteder"]</span></p>
                    <p title="Legg inn kortkoden i tekstfeltet i en egendefinert meny. I url-feltet skriver du #. For å få menyen som en undermeny, dra dette menypunktet til høyre innunder et annet menypunkt. For å lage meny av en bestemt kategori (med underkategorier), skriv inn kategori-slug etter start=""."><img src="<?php echo esc_url(plugins_url('assets/images/admin-menu-illustration.jpg', KURSAG_PLUGIN_FILE)); ?>" alt="Kursagenten admin" style="width: 100%; max-width: 400px;"></p>
                    </div>
                    
                    
                </div>
            </div>

            <div class="options-card">
                
                <h3  id="lister">Kortkoder for lister og grid</h3>
                <p>Kortkoder kan legges inn i teksten på sider og blogginnlegg. Du kan legge inn hele kurslisten, eller lister med enten alle kurskategorier, kurs i samme kategori (brukes på kurssider), eller instruktører.<br>Det er mange ulike valg. Du finner full kortkode under, samt en liste som forklarer alle valgene.<br>Kortkoden kopieres, og limes inn der du ønsker å vise den.</p>
                <div class="kort" style="background: #fbfbfb; padding: 1em; border-radius: 10px;">
                    <p><strong>Kursliste med filter </strong><span class="smal"><span class="copytext">[kursliste]</span></span><br><span class="copytext small">[kursliste kategori="web" sted="oslo" måned="9" språk="norsk"]</span></p>
                    <p><strong>Liste med kurskategorier </strong><span class="smal"><span class="copytext">[kurskategorier]</span></span><br><span class="copytext small" style="color:#666">[kurskategorier kilde="bilde/ikon" layout="stablet/rad/liste" grid=3 gridtablet=2 gridmobil=1  radavstand="1rem" stil="standard/kort" bildestr="100px" bildeform="avrundet/rund/firkantet/10px" bildeformat="4/3" overskrift="h3" fontmin="13" fontmaks="18" avstand="2em .5em" skygge="ja" vis="hovedkategorier/subkategorier/slug/standard" utdrag="ja"]</span></p>
                    <p><strong>Liste med kurssteder </strong><span class="smal"><span class="copytext">[kurssteder]</span></span><br><span class="copytext small" style="color:#666">[kurssteder layout="stablet/rad/liste" grid=3 gridtablet=2 gridmobil=1 radavstand="1rem" stil="standard/kort" bildestr="100px" bildeform="avrundet/rund/firkantet/10px" bildeformat="4/3" overskrift="h3" fontmin="13px" fontmaks="15px" avstand="2em .5em" skygge="ja" utdrag="ja" vis="spesifikke-lokasjoner"]</span></p>
                    <p><strong>Liste med instruktører </strong><span class="smal"><span class="copytext">[instruktorer]</span></span><br><span class="copytext small" style="color:#666">[instruktorer layout="stablet/rad/liste" grid=3 gridtablet=2 gridmobil=1 radavstand="1rem" stil="standard/kort" bildestr="100px" bildeform="avrundet/rund/firkantet/10px" bildeformat="4/3" overskrift="h3" fontmin="13px" fontmaks="15px" avstand="2em .5em" skygge="ja" skjul="Iris,Anna" utdrag="ja" beskrivelse="ja"]</span></p>
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
                                <td>Skriv inn størrelse på bildet du ønsker, i pixler. Ønsker du ikke bilde, skriv 0.</td>
                                <td><strong>Standard:</strong> 100px</td>
                                <td>Alle</td>
                                <td><span class="copytext">[kurskategorier bildestr=80px]</span></td>
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
                                <td>Velg å vise spesifikke lokasjoner.</td>
                                <td>spesifikke-lokasjoner<br><strong>Standard</strong>: vises ikke</td>
                                <td>Kurssteder</td>
                                <td><span class="copytext">[kurssteder vis=spesifikke-lokasjoner]</span></td>
                            </tr>
                            <tr>
                                <td>Skjul</td>
                                <td>Skjul instruktør ved å skrive en kommaseparert liste med fornavn til instruktør slik det er skrevet i feltet "fornavn"</td>
                                <td>kommasepartert liste<br><strong>Standard</strong>: viser alle</td>
                                <td>Instruktører</td>
                                <td><span class="copytext">[instruktor skjul=Anna,Per]</span></td>
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
                        </tbody>
                    </table>
                </div>
            </div>
            <div id="hooks" class="options-card">
                <h3>Hooks</h3>
                <div class="ka-grid ka-grid-3">
                    <div class="kort">
                            <h4>Taksonomi-sider</h4>
                            <p style="line-height: 1.8;">
                                <strong>Header etter tittel</strong> <span class="copytext">ka_taxonomy_after_title</span><br><span style="color:#777;font-style:italic"> – Vises rett etter H1 i toppseksjonen.</span><br>
                                <strong>Header etter seksjon</strong> <span class="copytext">ka_taxonomy_header_after</span><br><span style="color:#777;font-style:italic"> – Vises rett under hele header-blokken.</span><br>
                                <strong>Venstre kolonne</strong> <span class="copytext">ka_taxonomy_left_column</span><br><span style="color:#777;font-style:italic"> – Plassering for innhold i venstre kolonne.</span><br>
                                <strong>Høyre kolonne topp</strong> <span class="copytext">ka_taxonomy_right_column_top</span><br><span style="color:#777;font-style:italic"> – Øverst i høyre kolonne.</span><br>
                                <strong>Høyre kolonne bunn</strong> <span class="copytext">ka_taxonomy_right_column_bottom</span><br><span style="color:#777;font-style:italic"> – Nederst i høyre kolonne.</span><br>
                                <strong>Under bilde og beskrivelse</strong> <span class="copytext">ka_taxonomy_below_description</span><br><span style="color:#777;font-style:italic"> – Like under hovedbilde/utvidet beskrivelse, før kurslisten.</span><br>
                                <strong>Før kursliste</strong> <span class="copytext">ka_courselist_before</span><br><span style="color:#777;font-style:italic"> – Under overskrift, over filter/paginering og liste.</span><br>
                                <strong>Etter paginering</strong> <span class="copytext">ka_taxonomy_pagination_after</span><br><span style="color:#777;font-style:italic"> – Rett under pagineringskontroller i mal "Standard".</span><br>
                                <strong>Footer</strong> <span class="copytext">ka_taxonomy_footer</span><br><span style="color:#777;font-style:italic"> – Helt nederst, etter kurslisten (bunnseksjon).</span>
                            </p>
                    </div>
                    <div class="kort">
                        <h4>Enkeltkurs</h4>
                        <p style="line-height: 1.8;">
                        <strong>Header etter tittel</strong> <span class="copytext">ka_singel_header_links_after</span><br><span style="color:#777;font-style:italic"> – Etter lenkene i header-seksjonen.</span><br>
                        <strong>Header etter</strong> <span class="copytext">ka_singel_header_after</span><br><span style="color:#777;font-style:italic"> – Rett under hele header-blokken.</span><br>
                        <strong>Kursliste etter</strong> <span class="copytext">ka_singel_courselist_after</span><br><span style="color:#777;font-style:italic"> – Etter eventuell kursliste-seksjon på detaljsiden.</span><br>
                        <strong>Neste kurs</strong> <span class="copytext">ka_singel_nextcourse_after</span><br><span style="color:#777;font-style:italic"> – Etter modulen “Neste kurs”.</span><br>
                        <strong>Introtekst før</strong> <span class="copytext">ka_singel_content_intro_before</span><br><span style="color:#777;font-style:italic"> – Før introtekst.</span><br>
                        <strong>Introtekst etter</strong> <span class="copytext">ka_singel_content_intro_after</span><br><span style="color:#777;font-style:italic"> – Etter introtekst.</span><br>
                        <strong>Hovedinnhold før</strong> <span class="copytext">ka_singel_content_before</span><br><span style="color:#777;font-style:italic"> – Før hovedinnholdet.</span><br>
                        <strong>Hovedinnhold etter</strong> <span class="copytext">ka_singel_content_after</span><br><span style="color:#777;font-style:italic"> – Etter hovedinnholdet.</span><br>
                        <strong>Sidekolonne før</strong> <span class="copytext">ka_singel_aside_before</span><br><span style="color:#777;font-style:italic"> – Før sidekolonne/aside.</span><br>
                        <strong>Sidekolonne etter</strong> <span class="copytext">ka_singel_aside_after</span><br><span style="color:#777;font-style:italic"> – Etter sidekolonne/aside.</span><br>
                        <strong>Footer før</strong> <span class="copytext">ka_singel_footer_before</span><br><span style="color:#777;font-style:italic"> – Rett før footer.</span><br>
                        <strong>Footer etter</strong> <span class="copytext">ka_singel_footer_after</span><br><span style="color:#777;font-style:italic"> – Rett etter footer-seksjonen.</span><br>
                        </p>
                    </div>

                    <div class="kort">
                        <h4>Annet</h4>
                        <p>Hooks kommer...<br><span class="copytext"></span></p>
                     </div>
                    
                    
                </div>
            </div>

            <div class="options-card">
                <h3>Tips og feilsøking</h3>
                <ul>
                    <li><strong>Permalenker</strong>: Ved endring av URL-innstillinger, lagre «Permalenker» på nytt.</li>
                    <li><strong>Cache</strong>: Tøm cache hvis du ikke ser endringer umiddelbart.</li>
                    <li><strong>Bilder</strong>: Bruk plassholder-bilder via kortkodene om du mangler bilder.</li>
                </ul>
            </div>

            <!-- Tilgjengelige ikoner -->
            <?php if (function_exists('kursagenten_icon_overview_shortcode')): ?>
                <div id="tilgjengelige-ikoner" class="options-card">
                <h3>Tilgjengelige ikoner</h3>
                <p>Ikoner er tilgjengelige som html med css-klasser. Du kan bruke dem direkte i HTML-kode. Styr størrelse og farge med width, height og background-color på i.ka-icon. Eksempel:</p>
                <pre><code class="copytext">&lt;i class="ka-icon icon-calendar"&gt;&lt;/i&gt;</code></pre> 
                <style>
                    .ka-wrap i.ka-icon {
                        height: 20px;
                    }
                </style>

                    <?php echo kursagenten_icon_overview_shortcode(); ?>
                </div>
            <?php endif; ?>

                <?php kursagenten_admin_footer(); ?>
        <?php
    }
}

if (is_admin()) {
    new KA_Documentation_Page();
}



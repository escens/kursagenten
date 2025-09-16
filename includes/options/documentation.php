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
                    <h3>Kom i gang: A–Å</h3>
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

                <div class="options-card">
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

                <div class="options-card">
                    <h3>Vanlige sider og systemsider: hva er automatisk og hva er malstyrt</h3>
                    <p>Systemsider er enkeltsider som pluginen håndterer for deg – for eksempel taksonomi-sider (kategorier, kurssteder, instruktører) og enkeltsider for kurs.</p>
                    <ul>
                        
                        <li><strong>Kurs enkeltsider</strong>: Enkeltkurs blir generert fra standardmaler som blir valgt i «Kursdesign». Innholdet hentes fra Kursagenten. Det er mulig å legge inn eget innhold som vises mellom "Introtekst" fra Kursagenten og "Innhold". Du finner redigeringslink når du besøker enkeltkurset.</li>
                        <li><strong>Taksonomi enkeltsider</strong> Hver enkeltkategori, hvert sted, og hver enkeltside for instruktør blir generert fra maler. Det er mulighet for å legge inn ekstra innhold, som feks bilder og tekst. Malen som blir brukt velger du i «Kursdesign».</li>
                        <li><strong>Oversiktsider</strong>: Oversiktsider/lister med kurs, kategorier, steder og instruktører opprettes i vanlige Wordpress-sider med kortkoder. Kortkodene kan justeres for å oppnå ulike design, som feks kort, lister og annet.</li>
                    </ul>
                </div>

                <div class="options-card">
                    <h3>Kortkoder: hva, hvor og hvordan</h3>
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


                <div class="options-card">
                    <h3>Designmaler for kurs og taksonomi</h3>
                    <p>I <a href="admin.php?page=design">Kursdesign</a> velger du mal for kursdetaljer og for taksonomier. Å bytte mal påvirker oppsett, rekkefølge på elementer og visuelle detaljer.</p>
                    <ul>
                        <li><strong>Enkeltkurs</strong>: velg dedikert mal for kursdetaljsiden.</li>
                        <li><strong>Kategorier/steder/instruktører</strong>: velg mal for lister og enkeltsider.</li>
                    </ul>
                    <p>Endringer kan kreve oppdatering/refresh av cache og permalenker ved utstakte URL-tilpasninger.</p>
                </div>

                <div class="options-card">
                    <h3>Styre utseende på lister</h3>
                    <p>Under <a href="admin.php?page=design">Kursdesign</a> styrer du listeutseende (grid, kort, feltvisning). Det finnes innstillinger for bildebruk, metainformasjon, antall kolonner og paginering.</p>
                    <ul>
                        <li>Velg listetype (liste eller grid) for «Kursliste», «Kategorier», «Steder», «Instruktører».</li>
                        <li>Skru av/på elementer som bilde, beskrivelse, metadata, knapper.</li>
                        <li>Angi antall per side og paginering.</li>
                    </ul>
                </div>

                <div class="options-card">
                    <h3>Filtre: slik fungerer de</h3>
                    <p>Kurslisten kan filtreres på kategori, sted, tidspunkt m.m. Filtrene vises som del av kortkoden <code class="copytext">[kursliste]</code>. Du kan typisk konfigurere standardvalg og felt i «Kursdesign».</p>
                    <ul>
                        <li>Standardvalg for filtre settes i designinnstillinger.</li>
                        <li>Besøk frontend og test ulike kombinasjoner for å verifisere at dataene som kommer fra Kursagenten vises korrekt.</li>
                    </ul>
                </div>

                <div class="options-card">
                    <h3>Tips og feilsøking</h3>
                    <ul>
                        <li><strong>Permalenker</strong>: Ved endring av URL-innstillinger, lagre «Permalenker» på nytt.</li>
                        <li><strong>Cache</strong>: Tøm cache hvis du ikke ser endringer umiddelbart.</li>
                        <li><strong>Bilder</strong>: Bruk plassholder-bilder via kortkodene om du mangler bilder.</li>
                    </ul>
                </div>

                <?php kursagenten_admin_footer(); ?>
        <?php
    }
}

if (is_admin()) {
    new KA_Documentation_Page();
}



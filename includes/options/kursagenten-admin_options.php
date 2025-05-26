<?php
// Include option pages
require_once plugin_dir_path(__FILE__) . 'bedriftsinnstillinger.php';
require_once plugin_dir_path(__FILE__) . 'kursinnstillinger.php';
require_once plugin_dir_path(__FILE__) . 'coursedesign.php';
require_once plugin_dir_path(__FILE__) . 'theme_specific_customizations.php';
require_once plugin_dir_path(__FILE__) . 'seo.php';
require_once plugin_dir_path(__FILE__) . 'avansert.php';
require_once KURSAG_PLUGIN_DIR . '/includes/options/options_menu_top.php'; 

// Instantiate the classes to add them as submenus
if (is_admin()) {
    $kursinnstillinger = new Kursinnstillinger();
}
// Instansier Designmaler-klassen både i admin og frontend
$designmaler = new Designmaler();

if (is_admin()) {
    $theme_specific_customizations = new Kursagenten_Theme_Customizations();
    $seo = new SEO();
    $bedriftsinformasjon = new Bedriftsinformasjon();
    $avansert = new Avansert();
}

// Add the main admin menu
function kursagenten_register_admin_menu() {
    // Registrer hovedmenyen først
    add_menu_page(
        'Kursagenten',                         // Page title
        'Kursagenten',                         // Menu title
        'manage_options',                      // Capability
        'kursagenten',                         // Menu slug
        'kursagenten_admin_landing_page',      // Callback function
        'dashicons-welcome-learn-more',        // Icon
        2                                      // Position
    );

    // Legg til hovedsiden som submeny også for å unngå at første submeny blir standard
    add_submenu_page(
        'kursagenten',                         // Parent slug
        'Oversikt',                           // Page title
        'Oversikt',                           // Menu title
        'manage_options',                      // Capability
        'kursagenten',                         // Menu slug (samme som parent)
        'kursagenten_admin_landing_page'       // Callback function
    );
}

// Registrer hovedmenyen
add_action('admin_menu', 'kursagenten_register_admin_menu', 9);

// Landing page function remains the same
function kursagenten_admin_landing_page() {
    ?>
    <div class="wrap options-form ka-wrap" id="toppen">
        <form method="post" action="options.php">
            <?php kursagenten_sticky_admin_menu(); ?>
            <h1>Velkommen til Kursagenten</h1>
            
            <div class="welcome-panel">
                <div class="welcome-panel-content">
                
                    <div class="welcome-panel-column-container">
                        <div class="welcome-panel-column">
                            <h4 class="welcome-panel-title">Kom i gang</h4>
                            <p>Legg inn innstillinger for å automatisk hente kurs fra Kursagenten.</p>
                            <ul>
                                <li><a href="admin.php?page=kursinnstillinger">Kursinnstillinger</a></li>
                                <li><a href="admin.php?page=design">Designvalg</a></li>
                                <li><a href="admin.php?page=bedriftsinformasjon">Bedriftsinformasjon</a></li>
                            </ul>
                        </div>
                        
                        <div class="welcome-panel-column">
                            <h4 class="welcome-panel-title">Kortkoder</h4>
                            <p>Kortkoder kan brukes på valgfritt sted, f.eks. på blogginnlegg og sider.</p>
                            <ul>
                                <li><code class="copytext" title="Kopier til utklippstavle">[kursliste]</code> - Vis kursliste med filter</li>
                                <li><code class="copytext" title="Kopier til utklippstavle">[kurskategorier]</code> - Vis kurskategorier</li>
                                <li><code class="copytext" title="Kopier til utklippstavle">[instruktorer]</code> - Vis instruktører</li>
                            </ul>
                            <p class="small"><a href="#kortkoder">Se alle kortkoder</a>.</p>
                        </div>
                        
                        <div class="welcome-panel-column">
                            <h4 class="welcome-panel-title">Hjelp & støtte</h4>
                            <ul>
                                <li><a href="#">Dokumentasjon</a></li>
                                <li><a href="#">Support</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div id="kortkoder" class="options-card">
                <h3>Kortkoder</h3>
                <div class="ka-grid ka-grid-3">
                    <div class="kort">
                        <h4>Design: Lister og grid <span class="small"><a href="#lister">mer info</a></span></h4>
                        <p><span class="copytext">[kurskategorier]</span><br><span class="copytext">[kurssteder]</span><br><span class="copytext">[instruktorer]</span></p>
                        
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
                
                <h3>Innholdsblokker</h3>
                <p>Kortkode med lister av enten alle kurskategorier, kurs i samme kategori (brukes på kurssider), eller instruktører.<br>Det er mange ulike valg. Du finner full kortkode under, og så en liste som forklarer alle valgene.<br>Kortkoden kopieres, og limes inn der du ønsker å vise den.</p>
                <div class="kort" id="lister" style="background: #fbfbfb; padding: 1em; border-radius: 10px;">
                    <p><strong>Liste med kurskategorier </strong><span class="smal"><span class="copytext">[kurskategorier]</span></span><br><span class="copytext small">[kurskategorier kilde="bilde/ikon" layout="stablet/rad/liste" grid=3 gridtablet=2 gridmobil=1  radavstand="1rem" stil="standard/kort" bildestr="100px" bildeform="avrundet/rund/firkantet/10px" bildeformat="4/3" overskrift="h3" fontmin="13" fontmaks="18" avstand="2em .5em" skygge="ja" vis="hovedkategorier/subkategorier/standard"]</span></p>
                    <p><strong>Liste med kurssteder </strong><span class="smal"><span class="copytext">[kurssted]</span></span><br><span class="copytext small">[kurssteder layout="stablet/rad/liste" grid=3 gridtablet=2 gridmobil=1 radavstand="1rem" stil="standard/kort" bildestr="100px" bildeform="avrundet/rund/firkantet/10px" bildeformat="4/3" overskrift="h3" fontmin="13px" fontmaks="15px" avstand="2em .5em" skygge="ja"]</span></p>
                    <p><strong>Liste med instruktører </strong><span class="smal"><span class="copytext">[instruktorer]</span></span><br><span class="copytext small">[instruktorer layout="stablet/rad/liste" grid=3 gridtablet=2 gridmobil=1 radavstand="1rem" stil="standard/kort" bildestr="100px" bildeform="avrundet/rund/firkantet/10px" bildeformat="4/3" overskrift="h3" fontmin="13px" fontmaks="15px" avstand="2em .5em" skygge="ja" skjul="Iris,Anna"]</span></p>
                </div>
                <p>&nbsp;</p>
                
                <div class="">
                    <h3>Valg for lister og grid</h3>
                    <p>Skriver du kun kortkodene <span class="copytext">[kurskategorier]</span> eller <span class="copytext">[kurssted]</span> brukes standardvalgene.<br>Bruk eventuelt de fulle kodene over, og fjern de valgene du ikke trenger.</p>
                
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
                                <td>Du kan velge om du vil bruke et bildet du laster opp selv, eller bruke bildet hentet fra Kursagenten. Velg kilde=ka-bilde hvis du vil bruke dette.</td>
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
                                <td>4/3, 16/9, 1/1<br><strong>Standard</strong>: 4/3</td>
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
                                <td>h3, h4, h5, h6<br><strong>Standard</strong>: h3</td>
                                <td>Alle</td>
                                <td><span class="copytext">[kurskategorier overskrift=h4]</span></td>
                            </tr>
                            <tr>
                                <td>Fontmin</td>
                                <td>Teksten justerer seg etter skjermstørrelse. Dette er den minste fontstørrelsen du vil bruke for tekst og overskrifter.</td>
                                <td>13px, 15px, 18px<br><strong>Standard</strong>: 13px</td>
                                <td>Alle</td>
                                <td><span class="copytext">[kurskategorier fontmin=15px]</span></td>
                            </tr>
                            <tr>
                                <td>Fontmaks</td>
                                <td>Dette er den største fontstørrelsen du vil bruke for tekst og overskrifter.</td>
                                <td>15px, 18px, 26px<br><strong>Standard</strong>: 18px</td>
                                <td>Alle</td>
                                <td><span class="copytext">[kurskategorier fontmaks=18px]</span></td>
                            </tr>
                            <tr>
                                <td>Vis (k)</td>
                                <td>For de kategoriene som har flere nivåer, er det mulighet til å vise kun toppnivåene, kun underkategoriene, eller alle.</td>
                                <td>hovedkategorier, subkategorier<br><strong>Standard</strong>: viser alle</td>
                                <td>Kurskategorier</td>
                                <td><span class="copytext">[kurskategorier vis=hovedkategorier]</span></td>
                            </tr>
                            <tr>
                                <td>Vis (i)</td>
                                <td>Velg å vise fornavn, etternavn eller begge.</td>
                                <td>fornavn, etternavn<br><strong>Standard</strong>: viser fullt navn</td>
                                <td>Instruktører</td>
                                <td><span class="copytext">[instruktorer vis=fornavn]</span></td>
                            </tr>
                            <tr>
                                <td>Skjul</td>
                                <td>Skjul instruktør ved å skrive en kommaseparert liste med fornavn til instruktør slik det er skrevet i feltet "fornavn"</td>
                                <td>kommasepartert liste<br><strong>Standard</strong>: viser alle</td>
                                <td>Instruktører</td>
                                <td><span class="copytext">[instruktor skjul=Anna,Per]</span></td>
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
                            <p><strong>Instruktører</strong><br><span class="copytext">ka_instructors_left_column</span></p>
                            <p><strong>Kurskategorier</strong><br><span class="copytext">ka_coursecategory_left_column</span></p>
                            <p><strong>Kurssted</strong><br><span class="copytext">ka_courselocation_left_column</span></p>
                    </div>
                    <div class="kort">
                        <h4>Enkeltkurs</h4>
                        <p style="line-height: 1.8;">
                            <span class="copytext">ka_singel_header_links_after</span><br>
                            <span class="copytext">ka_singel_header_after</span><br>
                            <span class="copytext">ka_singel_courselist_after</span><br>
                            <span class="copytext">ka_singel_nextcourse_after</span><br>
                            <span class="copytext">ka_singel_content_intro_before</span><br>
                            <span class="copytext">ka_singel_content_intro_after</span><br>
                            <span class="copytext">ka_singel_content_before</span><br>
                            <span class="copytext">ka_singel_content_after</span><br>
                            <span class="copytext">ka_singel_aside_before</span><br>
                            <span class="copytext">ka_singel_aside_after</span><br>
                            <span class="copytext">ka_singel_footer_before</span><br>
                            <span class="copytext">ka_singel_footer_after</span><br>
                        </p>
                    </div>

                    <div class="kort">
                        <h4>Annet</h4>
                        <p>Hooks kommer...<br><span class="copytext"></span></p>
                     </div>
                    
                    
                </div>
            </div>
        
            <?php if (function_exists('kursagenten_icon_overview_shortcode')): ?>
                <div class="card">
                    <h2>Tilgjengelige ikoner</h2>
                    <?php echo kursagenten_icon_overview_shortcode(); ?>
                </div>
            <?php endif; ?>
        <?php
        kursagenten_admin_footer();
}

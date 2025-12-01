<?php
// Include option pages
require_once plugin_dir_path(__FILE__) . 'bedriftsinnstillinger.php';
require_once plugin_dir_path(__FILE__) . 'kursinnstillinger.php';
require_once plugin_dir_path(__FILE__) . 'coursedesign.php';
require_once plugin_dir_path(__FILE__) . 'theme_specific_customizations.php';
require_once plugin_dir_path(__FILE__) . 'seo.php';
require_once plugin_dir_path(__FILE__) . 'avansert.php';
require_once plugin_dir_path(__FILE__) . 'documentation.php';
require_once KURSAG_PLUGIN_DIR . '/includes/options/options_menu_top.php'; 

// Shortcodes for settings in kursinnstillinger.php is in misc/kursagenten-shortcodes.php

// Instantiate submenu classes kun når lisens (Lisensnøkkel) finnes for å redusere last
$__kag_api_key_present = get_option('kursagenten_api_key', '');
if (!empty($__kag_api_key_present)) {
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
    // Vis kun lisensboks dersom Lisensnøkkel mangler
    $current_key = get_option('kursagenten_api_key', '');
    if (empty($current_key)) {
        ?>
        <div class="wrap options-form ka-wrap" id="toppen" style="max-width: 720px; margin: 0 auto; margin-top: 10vh;">
            <h1>Velkommen til Kursagenten</h1>
            <div style="padding:12px; margin-top:12px; background:#fff3cd; border-left:4px solid #dba617; border-radius:4px; color:#533f03;">
                <p style="margin:0;"><?php echo esc_html__('Du må legge inn lisensnøkkel før du kan bruke innstillingene.', 'kursagenten'); ?></p>
            </div>
            <div class="options-card" style="max-width:720px; margin-top: 1em;">
                <h3><?php echo esc_html__('Legg inn lisensnøkkel', 'kursagenten'); ?></h3>
                <form method="post" action="options.php">
                    <?php 
                        if (function_exists('settings_fields')) {
                            settings_fields('kursagenten_license');
                        }
                        // Ensure proper redirect back to this page
                        if (empty($_POST)) {
                            echo '<input type="hidden" name="_wp_http_referer" value="' . esc_attr(admin_url('admin.php?page=kursagenten')) . '" />';
                        }
                    ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php echo esc_html__('Lisensnøkkel', 'kursagenten'); ?></th>
                            <td>
                                <input type="text" name="kursagenten_api_key" class="regular-text" value="" />
                                <p class="description"><?php echo esc_html__('Lim inn lisensnøkkelen du fikk tildelt.', 'kursagenten'); ?></p>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button( esc_html__('Lagre lisens', 'kursagenten') ); ?>
                </form>
            </div>
        </div>
        <?php
        return;
    }
    ?>
    <div class="wrap options-form ka-wrap" id="toppen">
        <form method="post" action="options.php">
            <?php kursagenten_sticky_admin_menu(); ?>
            <h1>Velkommen til Kursagenten</h1>
            

                
            <div id="kom-igang" class="options-card">
                <h3>Kom i gang</h3>
                    <div class="ka-grid ka-grid-3">
                        <div class="kort">
                            <h4 class="welcome-panel-title">Hent kursene</h4>
                            <p>Legg inn innstillinger for å automatisk hente kurs fra Kursagenten. Når du har lagt inn innstillinger, kan du klikke på "Hent alle kurs fra Kursagenten" så blir alt hentet ved første gangs installering.</p>
                            <ul>
                                <li><a href="admin.php?page=kursinnstillinger"><strong>Synkronisering</strong></a></li>
                            </ul>
                        </div>
                        
                        <div class="kort">
                            <h4 class="welcome-panel-title">Legg inn bedriftsinformasjon</h4>
                            <p>Her kan du skrive inn informasjon som vil bli brukt ulike steder på nettsiden. Dette inkluderer navn på hovedkontakt (personvernerklæring), samt firmanavn og adresse (kontaktside og bunnfelt).</p>
                            <ul>
                                <li><a href="admin.php?page=bedriftsinformasjon"><strong>Bedriftsinformasjon</strong></a></li>
                            </ul>
                        </div>
                        
                        <div class="kort">
                            <h4 class="welcome-panel-title">Velg sider og design</h4>
                            <p>Opprett eller velg nødvendige sider for kurs, kurskategorier, kurssteder og instruktører. Velg design på kursliste, enkeltkurs, kategorier, steder og instruktører. Velg hovedfarger.</p>
                            <ul>
                                <li><a href="admin.php?page=design"><strong>Designvalg</strong></a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div id="berike-innhold" class="options-card">
                    <h3>Berik innholdet ditt</h3>
                    <div class="ka-grid ka-grid-2">
                        <div class="kort">
                            <h4 class="welcome-panel-title">Kurskategorier, kurssteder og instruktører</h4>
                            <p>Kursene dine blir automatisk overført fra Kursagenten sammen med kurskategorier, kurssteder og instruktører. Men for å gi nettsiden din et profesjonelt uttrykk, kan du berike disse med bilder og tekst direkte på nettsiden.</p>
                            <ul>
                                <li><a href="edit-tags.php?taxonomy=ka_coursecategory&post_type=ka_course"><strong>Kurskategorier</strong></a> – Legg til bilder, beskrivelser og organiser i hovedkategorier</li>
                                <li><a href="edit-tags.php?taxonomy=ka_course_location&post_type=ka_course"><strong>Kurssteder</strong></a> – Legg til bilder og stedsbeskrivelser</li>
                                <li><a href="edit-tags.php?taxonomy=ka_instructors&post_type=ka_course"><strong>Instruktører</strong></a> – Legg til profilbilder og utvidet informasjon</li>
                            </ul>
                            <p><em>Tips: Hvis du har mange kurskategorier, kan du velge ut noen som hovedkategorier eller opprette egne. Dette gjør kategoriene mer oversiktlige for besøkende.</em></p>
                        </div>
                        
                        <div class="kort">
                            <h4 class="welcome-panel-title">Om kursredigering</h4>
                            <p><strong>Viktig:</strong> Kursene skal i hovedsak <strong>ikke redigeres på nettsiden</strong> – all redigering bør gjøres i Kursagenten. Dette sikrer at informasjonen er oppdatert og riktig synkronisert.</p>
                            <p>Når du besøker et kurs på frontend, finner du et redigeringsikon som tar deg direkte til kursredigering i Kursagenten. Dette gjør det enkelt å oppdatere kursinformasjon uten å gå veien om nettsiden.</p>
                            <ul>
                                <li><a href="edit.php?post_type=ka_course"><strong>Alle kurs</strong></a> – Se oversikt over alle importerte kurs</li>
                            </ul>
                            <p><em>Unntak: Du kan legge til ekstra innhold mellom "Introtekst" og "Innhold" på enkeltkurs hvis du ønsker å berike kursene med nettstedsspesifikk informasjon.</em></p>
                        </div>
                    </div>
                </div>

                <div id="meld-feil" class="options-card">
                <h3>Meld feil og forbedringer</h3>
                        <div class="kort">
                            <p>Har du funnet feil eller har du forbedringsforslag? Vi hører gjerne fra deg. Vi jobber for å gjøre Kursagenten bedre for deg.<br>
                                Send en epost rett til inboksen i Trello:</p>
                            <p><a href="mailto:tonebhagen+p3gzydq7w8klqykuvdjs@app.trello.com?subject=WP%20Plugin%20tilbakemelding"><strong>Send til Trello</strong></a></p>
                        </div>
                    
                </div>

            
        

        <?php
        kursagenten_admin_footer();
}

// Fjern " - privatundervisning" fra tittelen
add_filter('the_title', function($title, $post_id = null) {
    // Sjekk om vi er i admin eller frontend
    if (is_admin()) {
        return $title;
    }
    
    // Fjern " - privatundervisning" fra tittelen (case-insensitive)
    return preg_replace('/\s*-\s*privatundervisning/i', '', $title);
}, 10, 2);

// Filter for å vise kun foreldrekurs i Kadence Query Loop og fjerne "privatundervisning" fra tittelen
add_filter('kadence_blocks_pro_query_loop_query_vars', function($query, $ql_query_meta, $ql_id) {
    if ($ql_id == 3610) {
        // Fjern offset og paged for testing
        unset($query['offset']);
        unset($query['paged']);
        
        // Kombinert meta_query som sjekker både sub_course_location og tittel
        $query['meta_query'] = array(
            'relation' => 'AND',
            array(
                'key' => 'sub_course_location',
                'compare' => 'NOT EXISTS'
            )
        );
        
        // Legg til tittelfilter
        $query['s'] = 'Privatundervisning';
        
        // Gjenopprett paginering for den faktiske queryen
        $query['paged'] = 1;
        $query['offset'] = 0;
        
        // Legg til filter for å modifisere resultatene
        add_filter('posts_results', function($posts) use ($ql_id) {
            if ($ql_id == 3610) {
                foreach ($posts as $post) {
                    // Bare fjern ordet "privatundervisning"
                    $post->post_title = str_ireplace('privatundervisning', '', $post->post_title);
                    // Fjern eventuelle doble mellomrom som kan oppstå
                    $post->post_title = preg_replace('/\s+/', ' ', $post->post_title);
                    // Fjern eventuelle mellomrom før og etter bindestrek
                    $post->post_title = preg_replace('/\s*–\s*/', ' – ', $post->post_title);
                    // Trim whitespace
                    $post->post_title = trim($post->post_title);
                }
            }
            return $posts;
        }, 10, 1);
    }
    
    return $query;
}, 10, 3);

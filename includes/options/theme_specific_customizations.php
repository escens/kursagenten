<?php
class Kursagenten_Theme_Customizations {
    private $option_name = 'kursagenten_theme_customizations';
    
    public function __construct() {
        global $kursagenten_theme_customizations;
        $kursagenten_theme_customizations = $this;
        
        add_action('admin_menu', array($this, 'add_menu_page'));
        add_action('admin_init', array($this, 'register_settings'));
        //add_action('wp_head', array($this, 'add_theme_specific_styles'));
        
        // Legg til filter for custom HTML attributter
        add_filter('wp_kses_allowed_html', array($this, 'allow_custom_html_attributes'));
        
        // Legg til AJAX-handler for å tilbakestille innstillinger
        add_action('wp_ajax_reset_theme_customizations', array($this, 'ajax_reset_theme_customizations'));
    }
    
    public function add_menu_page() {
        add_submenu_page(
            'kursagenten',
            'Tematilpasninger',
            'Tematilpasninger',
            'manage_options',
            'kursagenten-theme-customizations',
            array($this, 'render_settings_page')
        );
    }
    
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Du har ikke tillatelse til å åpne denne siden.'));
        }
        
        $options = get_option($this->option_name);
        $current_theme = wp_get_theme();
        
        // Endre form action til vår custom handler
        ?>
        <div class="wrap options-form ka-wrap" id="toppen">
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <?php kursagenten_sticky_admin_menu('Tematilpasninger'); ?>
            <input type="hidden" name="action" value="save_theme_customizations">
            <?php wp_nonce_field('save_theme_customizations', 'theme_customizations_nonce'); ?>
            <h2>Tilpasninger for <?php echo esc_html($current_theme->get('Name')); ?> tema</h2>
            
            <div class="options-card">
            <h3>Menytilpasninger</h3>
                <?php
                // Sjekk om det finnes egendefinerte innstillinger
                $has_custom_settings = !empty($options) && isset($options['menu_structure']) && 
                    (!empty($options['menu_structure']['item_simple']) || 
                     !empty($options['menu_structure']['item_with_children']) || 
                     !empty($options['menu_structure']['item_with_children_mobile']));

                ?>
                
                
                <h4>Menytema</h4>
                        <?php
                        if ($has_custom_settings) {
                            echo '<p><span class="theme-status custom">Egendefinert kode</span> &nbsp;Klikk "Reset" under for å bruke automatisk valgt kode basert på aktivt tema</p>';
                        } else {
                            echo '<p><span class="theme-status auto">Automatisk</span> Menytemaet er valgt basert på aktivt tema</p>';
                        }
                        ?>
                <h4 style="font-size: 1.2em; margin-top: 5em;">Egendefinerte innstillinger</h4>
                <div class="ka-grid ka-grid-2">
                    <div class="ka-col ka-col-light">
                        <?php //TODO: Legg til flere temaer ?>
                            <h4>Juster html-koden for menyen</h4>
                            <p>Velg et tema som utgangspunkt:</p>
                            <div class="theme-selector-wrapper">
                                <select id="theme-template-selector"> 
                                    <option value="">Velg tema</option>
                                    <option value="kadence">Kadence</option>
                                    <option value="astra">Astra</option>
                                    <option value="generatepress">GeneratePress</option>
                                    <option value="blocksy">Blocksy</option>
                                </select>
                                <button type="button" id="reset-to-theme" class="button" title="Tilbakestill til tema-standard">
                                    <span class="dashicons dashicons-image-rotate"></span>
                                    Reset
                                </button>
                            </div>
                    </div>
                    <div class="ka-col ka-col-light">
                        <h4>Variabler for menyelement</h4>
                        <ul class="variables-list">
                                <li><code class="copytext">{{term_id}}</code> - Term ID</li>
                                <li><code class="copytext">{{term_name}}</code> - Term navn</li>
                                <li><code class="copytext">{{term_url}}</code> - Term URL</li>
                                <li><code class="copytext">{{taxonomy}}</code> - Taxonomi navn</li>
                            </ul>
                    </div>
                </div>

                <div class="" style="margin-bottom: 2em;">
                        <p>Se <a href="#oppsett-for-generert-kode">eksempel oppsett</a> for generert kode - Menyelement med underpunkter nedenfor.
                        </p>
                </div>
                
                


                
                <table class="form-table">
                    <tr>
                        <th scope="row">Enkelt menyelement</th>
                        <td>
                            <pre><code>&lt;li id="menu-item-{{term_id}}" class="<span class="li-class-preview">menu-item automeny menu-item-type-post_type menu-item-type-taxonomy menu-item-object-course</span> menu-item-{{term_id}}"&gt;</code></pre>
                            <textarea name="<?php echo esc_attr($this->option_name); ?>[menu_structure][item_simple]" rows="2" class="large-text code"><?php 
                                echo esc_textarea(isset($options['menu_structure']['item_simple']) ? $options['menu_structure']['item_simple'] : ''); 
                            ?></textarea>
                            <pre><code>&lt;/li&gt;</code></pre>
                            <div class="li-class-input">
                                <label>Ekstra klasser for &lt;li&gt;:</label>
                                <input type="text" name="<?php echo esc_attr($this->option_name); ?>[menu_structure][item_simple_li_class]" 
                                       value="<?php echo esc_attr(isset($options['menu_structure']['item_simple_li_class']) ? $options['menu_structure']['item_simple_li_class'] : 'menu-item automeny menu-item-type-post_type menu-item-type-taxonomy menu-item-object-course'); ?>" 
                                       class="regular-text li-class-field">
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Menyelement med undermeny (desktop)</th>
                        <td>
                            
                            <pre><code>&lt;li id="menu-item-{{term_id}}" class="<span class="li-class-preview">menu-item automeny menu-item-type-taxonomy menu-item-object-category menu-item-has-children</span> menu-item-{{term_id}}"&gt;</code></pre>
                            <textarea name="<?php echo esc_attr($this->option_name); ?>[menu_structure][item_with_children]" rows="4" class="large-text code"><?php 
                                echo esc_textarea(isset($options['menu_structure']['item_with_children']) ? $options['menu_structure']['item_with_children'] : ''); 
                            ?></textarea>
                            <pre><code>&lt;ul class="sub-menu"&gt;  ... innhold ...
&lt;/ul&gt;
&lt;/li&gt;</code></pre>
                            <div class="li-class-input">
                                <label>Ekstra klasser for &lt;li&gt;:</label>
                                <input type="text" name="<?php echo esc_attr($this->option_name); ?>[menu_structure][item_with_children_li_class]" 
                                       value="<?php echo esc_attr(isset($options['menu_structure']['item_with_children_li_class']) ? $options['menu_structure']['item_with_children_li_class'] : 'menu-item automeny menu-item-type-taxonomy menu-item-object-category menu-item-has-children'); ?>" 
                                       class="regular-text li-class-field">
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Menyelement med undermeny (mobil)</th>
                        <td>
                            <pre><code>&lt;li id="menu-item-{{term_id}}" class="<span class="li-class-preview-mobile">menu-item menu-item-type-taxonomy menu-item-object-category menu-item-has-children</span> menu-item-{{term_id}}"&gt;</code></pre>
                            <textarea name="<?php echo esc_attr($this->option_name); ?>[menu_structure][item_with_children_mobile]" rows="4" class="large-text code"><?php 
                                echo esc_textarea(isset($options['menu_structure']['item_with_children_mobile']) ? $options['menu_structure']['item_with_children_mobile'] : ''); 
                            ?></textarea>
                            <pre><code>&lt;ul class="sub-menu"&gt;  ... innhold ...
&lt;/ul&gt;
&lt;/li&gt;</code></pre>
                            <div class="li-class-input">
                                <label>Ekstra klasser for &lt;li&gt; (mobil):</label>
                                <input type="text" name="<?php echo esc_attr($this->option_name); ?>[menu_structure][item_with_children_li_class_mobile]" 
                                       value="<?php echo esc_attr(isset($options['menu_structure']['item_with_children_li_class_mobile']) ? $options['menu_structure']['item_with_children_li_class_mobile'] : 'menu-item menu-item-type-taxonomy menu-item-object-category menu-item-has-children'); ?>" 
                                       class="regular-text li-class-field-mobile">
                            </div>
                        </td>
                    </tr>
                </table>

                <div class="ka-grid ka-grid-2" style="margin-top: 2em;">
                    <div class="ka-col ka-col-light">
                        <h4>Deaktiver klasser for visning/skjuling</h4> 
                        <label>
                            <input type="checkbox" 
                                    name="<?php echo esc_attr($this->option_name); ?>[disable_menu_styles]" 
                                    value="1" 
                                    <?php checked(!empty($options['disable_menu_styles'])); ?>>
                            Deaktiver klasser for meny-visning/skjuling
                        </label>
                        <p class="description">Vi legger til klasser for visning/skjuling av menyen på desktop og mobil. Se forklaring nederst på siden.</p>
                    </div>
                    <div class="ka-col ka-col-light">
                        <h4>Breakpoint for responsiv meny</h4>
                        <label>
                            <input type="text" 
                                   name="<?php echo esc_attr($this->option_name); ?>[item_breakpoint]" 
                                   value="<?php echo esc_attr(isset($options['item_breakpoint']) ? $options['item_breakpoint'] : '1025px'); ?>" 
                                   class="regular-text">
                            <p class="description">Angi breakpoint for når menyen skal bytte mellom mobil og desktop visning (f.eks. 1025px).</p>

                    </div>
                </div>

                <div class="" style="margin: 3em 1em;" id="oppsett-for-generert-kode">
                    <strong>Oppsett for generert kode - Menyelement med underpunkter</strong> <br>
                        <p>
                            <pre><code>&lt;li id="menu-item-{{term_id}}" class="menu-item automeny menu-item-type-taxonomy menu-item-object-category menu-item-has-children menu-item-{{term_id}}"&gt;</code></pre>
                            - Menyelement med undermeny (desktop) <br>
                            - Menyelement med undermeny (mobil) <em><br>(Legges til rett etter desktop-versjonen. De to elementene vises/skjules med css. Korrekte css-klasser er lagt til automatisk.)</em>
                            <pre><code>&lt;ul class="sub-menu"&gt;  ... innhold ... &lt;/ul&gt;
&lt;/li&gt;</code></pre>
                        </p>
                </div>
                
                
                <?php submit_button(); ?>
            </div>
        </form>

        <script>
        jQuery(document).ready(function($) {
            // Hent alle tema-strukturer fra PHP
            const templates = <?php 
                $all_templates = array();
                $themes = array('kadence', 'astra', 'generatepress', 'blocksy');
                foreach ($themes as $theme) {
                    $all_templates[$theme] = $this->get_theme_structure($theme, 'desktop');
                }
                echo json_encode($all_templates);
            ?>;
            
            const currentTheme = '<?php echo esc_js(strtolower(wp_get_theme()->get('Name'))); ?>';
            
            // Funksjon for å fylle inn tema-struktur
            function fillThemeStructure(theme) {
                if (templates[theme]) {
                    // Sett verdiene i textarea
                    $('[name="<?php echo $this->option_name; ?>[menu_structure][item_simple]"]')
                        .val(templates[theme].item_simple);
                    $('[name="<?php echo $this->option_name; ?>[menu_structure][item_with_children]"]')
                        .val(templates[theme].item_with_children);
                    $('[name="<?php echo $this->option_name; ?>[menu_structure][item_with_children_mobile]"]')
                        .val(templates[theme].item_with_children_mobile);
                    
                    // Sett li-class feltene
                    $('[name="<?php echo $this->option_name; ?>[menu_structure][item_simple_li_class]"]')
                        .val(templates[theme].item_simple_li_class);
                    $('[name="<?php echo $this->option_name; ?>[menu_structure][item_with_children_li_class]"]')
                        .val(templates[theme].item_with_children_li_class);
                    $('[name="<?php echo $this->option_name; ?>[menu_structure][item_with_children_li_class_mobile]"]')
                        .val(templates[theme].item_with_children_li_class_mobile || templates[theme].item_with_children_li_class);
                    
                    // Sett breakpoint-feltet
                    $('[name="<?php echo $this->option_name; ?>[item_breakpoint]"]')
                        .val(templates[theme].item_breakpoint);
                    
                    // Oppdater previews
                    $('.li-class-preview').each(function() {
                        const input = $(this).closest('td').find('.li-class-field');
                        $(this).text(input.val() || 'Standard tema-klasser');
                    });
                    
                    $('.li-class-preview-mobile').each(function() {
                        const input = $(this).closest('td').find('.li-class-field-mobile');
                        $(this).text(input.val() || 'Standard tema-klasser');
                    });
                }
            }
            
            // Håndter tema-endring
            $('#theme-template-selector').on('change', function() {
                const theme = $(this).val();
                if (theme && templates[theme]) {
                    if (confirm('Dette vil overskrive nåværende menystruktur. Vil du fortsette?')) {
                        fillThemeStructure(theme);
                    }
                }
            });
            
            // Reset-knapp
            $('#reset-to-theme').on('click', function() {
                if (confirm('Dette vil tilbakestille menystrukturen til standard for aktivt tema.\n\nHusk å LAGRE endringene!')) {
                    // Fjern alle lagrede innstillinger fra skjemaet
                    $('[name="<?php echo $this->option_name; ?>[menu_structure][item_simple]"]').val('');
                    $('[name="<?php echo $this->option_name; ?>[menu_structure][item_with_children]"]').val('');
                    $('[name="<?php echo $this->option_name; ?>[menu_structure][item_with_children_mobile]"]').val('');
                    $('[name="<?php echo $this->option_name; ?>[menu_structure][item_simple_li_class]"]').val('');
                    $('[name="<?php echo $this->option_name; ?>[menu_structure][item_with_children_li_class]"]').val('');
                    
                    // Fjern disable_menu_styles
                    $('[name="<?php echo $this->option_name; ?>[disable_menu_styles]"]').prop('checked', false);
                    
                    // Hent standardverdier fra get_theme_structure()
                    $('#theme-template-selector').val(currentTheme);
                    fillThemeStructure(currentTheme);
                    
                    // Oppdater breakpoint-feltet
                    $('[name="<?php echo $this->option_name; ?>[item_breakpoint]"]').val(templates[currentTheme].item_breakpoint);
                    
                    // Oppdater previews
                    $('.li-class-preview').each(function() {
                        const input = $(this).closest('td').find('.li-class-field');
                        $(this).text(input.val() || 'Standard tema-klasser');
                    });
                    
                    $('.li-class-preview-mobile').each(function() {
                        const input = $(this).closest('td').find('.li-class-field-mobile');
                        $(this).text(input.val() || 'Standard tema-klasser');
                    });
                    
                    // Send AJAX-forespørsel for å fjerne innstillingene fra databasen
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'reset_theme_customizations',
                            nonce: '<?php echo wp_create_nonce('reset_theme_customizations'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                // Redirect tilbake til innstillingssiden med reset=true i URL-en
                                window.location.href = '<?php echo admin_url('admin.php'); ?>?page=kursagenten-theme-customizations&updated=true&reset=true';
                            } else {
                                alert('Det oppstod en feil ved tilbakestilling av innstillingene.');
                            }
                        },
                        error: function() {
                            alert('Det oppstod en feil ved tilbakestilling av innstillingene.');
                        }
                    });
                }
            });

            // Håndter li-class-felt endringer
            $('.li-class-field').on('input', function() {
                const preview = $(this).closest('td').find('.li-class-preview');
                preview.text($(this).val() || 'Standard tema-klasser');
            });
            
            $('.li-class-field-mobile').on('input', function() {
                const preview = $(this).closest('td').find('.li-class-preview-mobile');
                preview.text($(this).val() || 'Standard tema-klasser');
            });
        });
        </script>
        
        <style>

        .variables-list code {
            background: #fff;
            padding: 2px 5px;
        }
        #theme-template-selector {
            margin-bottom: 0;
        }
        .li-class-input {
            margin-bottom: 10px;
            background: #fafafa;
            padding: 10px;
            border-radius: 4px;
            font-size: 0.9em;
        }
        .li-class-input label {
            display: block;
            margin-bottom: 5px;
        }
        .li-class-input input {
            width: 100%;
            font-size: 1em;
            padding: 2px 5px;
        }
        .theme-selector-wrapper {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 20px;
        }
        #reset-to-theme {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        #reset-to-theme .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
        }
        .theme-status {
            padding: 8px 12px;
            border-radius: 4px;
            margin-top: 5px;
            margin-bottom: 15px;
        }
        .theme-status.custom {
            background-color: #f8f8d8;
            border-left: 4px solid #dba617;
        }
        .theme-status.auto {
            background-color: #e8f5e9;
            border-left: 4px solid #46b450;
        }
        </style>
        
        <?php
        kursagenten_admin_footer();
    }
    
    private function get_default_structure($type) {
        $current_theme = wp_get_theme();
        $theme_name = strtolower($current_theme->get('Name'));
        
        // Prøv å hente tema-spesifikk struktur
        $structure = $this->get_theme_structure($theme_name, $type);
        
        // Hvis ingen tema-spesifikk struktur finnes, bruk standard
        if (empty($structure)) {
            return $this->get_theme_structure('default', $type);
        }
        
        return $structure;
    }
    
    // TODO: Legg til flere temaer
    public function get_theme_structure($theme, $type) {
        $structures = [
            'default' => [
                'desktop' => [
                    'item_breakpoint' => '1025px',
                    'item_simple' => '<a href="{{term_url}}">{{term_name}}</a>',
                    'item_with_children' => '<li id="menu-item-{{term_id}}" class="menu-item automeny menu-item-type-taxonomy menu-item-object-category menu-item-has-children menu-item-{{term_id}}">
                <a class="ka-desktop-menu" href="{{term_url}}">{{term_name}}<span class="dropdown-menu-toggle icon-arrow-right">
                    <svg viewBox="0 0 192 512" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414">
                        <path d="M178.425 256.001c0 2.266-1.133 4.815-2.832 6.515L43.599 394.509c-1.7 1.7-4.248 2.833-6.514 2.833s-4.816-1.133-6.515-2.833l-14.163-14.162c-1.699-1.7-2.832-3.966-2.832-6.515 0-2.266 1.133-4.815 2.832-6.515l111.317-111.316L16.407 144.685c-1.699-1.7-2.832-4.249-2.832-6.515s1.133-4.815 2.832-6.515l14.163-14.162c1.7-1.7 4.249-2.833 6.515-2.833s4.815 1.133 6.514 2.833l131.994 131.993c1.7 1.7 2.832 4.249 2.832 6.515z" fill-rule="nonzero"></path>
                    </svg>
                </span></a>',
                    'item_with_children_mobile' => '<div class="drawer-nav-drop-wrap">
                        <a href="{{term_url}}">{{term_name}}</a>
                        <button class="drawer-sub-toggle" data-toggle-duration="10" aria-expanded="false">
                            <span class="screen-reader-text">Vis/skjul undermeny</span>
                        </button>
                    </div>',
                    'item_simple_li_class' => 'menu-item menu-item-type-post_type menu-item-type-taxonomy menu-item-object-course',
                    'item_with_children_li_class' => 'menu-item menu-item-type-taxonomy menu-item-object-category menu-item-has-children',
                    'item_with_children_li_class_mobile' => 'menu-item menu-item-type-taxonomy menu-item-object-category menu-item-has-children'
                ]
            ],
            'kadence' => [
                'desktop' => [
                    'item_breakpoint' => '1025px',
                    'item_simple' => '<a href="{{term_url}}">{{term_name}}</a>',

'item_with_children' => '<a href="{{term_url}}"><span class="nav-drop-title-wrap">{{term_name}}<span class="dropdown-nav-toggle"><span class="kadence-svg-iconset svg-baseline">
<svg aria-hidden="true" class="kadence-svg-icon kadence-arrow-down-svg" fill="currentColor" version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><title>Expand</title>
<path d="M5.293 9.707l6 6c0.391 0.391 1.024 0.391 1.414 0l6-6c0.391-0.391 0.391-1.024 0-1.414s-1.024-0.391-1.414 0l-5.293 5.293-5.293-5.293c-0.391-0.391-1.024-0.391-1.414 0s-0.391 1.024 0 1.414z"></path></svg></span>
</span></span></a>',

'item_with_children_mobile' => '<div class="drawer-nav-drop-wrap">
<a href="{{term_url}}">{{term_name}}</a>
<button class="drawer-sub-toggle" data-toggle-duration="10" data-toggle-target="#mobile-menu .menu-item-{{term_id}} > .sub-menu" aria-expanded="false">
<span class="screen-reader-text">Vis/skjul undermeny</span>
<span class="kadence-svg-iconset"><svg aria-hidden="true" class="kadence-svg-icon kadence-arrow-down-svg" fill="currentColor" version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><title>Expand</title>
<path d="M5.293 9.707l6 6c0.391 0.391 1.024 0.391 1.414 0l6-6c0.391-0.391 0.391-1.024 0-1.414s-1.024-0.391-1.414 0l-5.293 5.293-5.293-5.293c-0.391-0.391-1.024-0.391-1.414 0s-0.391 1.024 0 1.414z"></path></svg></span>
</button>
</div>',

'item_simple_li_class' => 'menu-item menu-item-type-post_type menu-item-type-taxonomy menu-item-object-course',
'item_with_children_li_class' => 'menu-item menu-item-type-taxonomy menu-item-object-category menu-item-has-children',
'item_with_children_li_class_mobile' => 'menu-item menu-item-type-taxonomy menu-item-object-category menu-item-has-children'
                ]
            ],
            'astra' => [
                'desktop' => [
                    'item_breakpoint' => '921px',
'item_simple' => '<a href="{{term_url}}" class="menu-link"><span class="ast-icon icon-arrow">
<svg class="ast-arrow-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" width="26px" height="16.043px" viewBox="57 35.171 26 16.043" enable-background="new 57 35.171 26 16.043" xml:space="preserve">
<path d="M57.5,38.193l12.5,12.5l12.5-12.5l-2.5-2.5l-10,10l-10-10L57.5,38.193z"></path>
</svg></span>{{term_name}}</a>',

'item_with_children' => '<a aria-expanded="false" href="{{term_url}}" class="menu-link">
<span class="ast-icon icon-arrow">
<svg class="ast-arrow-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" width="26px" height="16.043px" viewBox="57 35.171 26 16.043" enable-background="new 57 35.171 26 16.043" xml:space="preserve">
<path d="M57.5,38.193l12.5,12.5l12.5-12.5l-2.5-2.5l-10,10l-10-10L57.5,38.193z"></path>
</svg></span>
{{term_name}}
<span role="application" class="dropdown-menu-toggle ast-header-navigation-arrow"><span class="ast-icon icon-arrow">
<svg class="ast-arrow-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" width="26px" height="16.043px" viewBox="57 35.171 26 16.043" enable-background="new 57 35.171 26 16.043" xml:space="preserve">
<path d="M57.5,38.193l12.5,12.5l12.5-12.5l-2.5-2.5l-10,10l-10-10L57.5,38.193z"></path>
</svg></span></span></a>',

'item_with_children_mobile' => '<button class="ast-menu-toggle"><span class="screen-reader-text">Menyveksler</span><span class="ast-icon icon-arrow">
<svg class="ast-arrow-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" width="26px" height="16.043px" viewBox="57 35.171 26 16.043" enable-background="new 57 35.171 26 16.043" xml:space="preserve">
<path d="M57.5,38.193l12.5,12.5l12.5-12.5l-2.5-2.5l-10,10l-10-10L57.5,38.193z"></path>
</svg></span></button>',

'item_simple_li_class' => 'menu-item menu-item-type-post_type menu-item-type-taxonomy menu-item-object-course',
'item_with_children_li_class' => 'menu-item menu-item-type-taxonomy menu-item-object-category menu-item-has-children',
'item_with_children_li_class_mobile' => 'menu-item menu-item-type-taxonomy menu-item-object-category menu-item-has-children'
                ]
            ],
            'generatepress' => [
                'desktop' => [
                    'item_breakpoint' => '768px',
'item_simple' => '<a href="{{term_url}}">{{term_name}}</a>',

'item_with_children' => '<a href="{{term_url}}">{{term_name}}
<span role="presentation" class="dropdown-menu-toggle">
<span class="dropdown-menu-toggle icon-arrow-right">
<svg viewBox="0 0 192 512" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414">
<path d="M178.425 256.001c0 2.266-1.133 4.815-2.832 6.515L43.599 394.509c-1.7 1.7-4.248 2.833-6.514 2.833s-4.816-1.133-6.515-2.833l-14.163-14.162c-1.699-1.7-2.832-3.966-2.832-6.515 0-2.266 1.133-4.815 2.832-6.515l111.317-111.316L16.407 144.685c-1.699-1.7-2.832-4.249-2.832-6.515s1.133-4.815 2.832-6.515l14.163-14.162c1.7-1.7 4.249-2.833 6.515-2.833s4.815 1.133 6.514 2.833l131.994 131.993c1.7 1.7 2.832 4.249 2.832 6.515z" fill-rule="nonzero"></path>
</svg></span></span>
</a>',

'item_with_children_mobile' => '<a href="{{term_url}}">{{term_name}}
<span role="button" class="dropdown-menu-toggle" tabindex="0" aria-expanded="true" aria-controls="menu-item-{{term_id}}-sub-menu" aria-label="Close Sub-Menu">
<span class="gp-icon icon-arrow"><svg viewBox="0 0 330 512" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="1em" height="1em">
<path d="M305.913 197.085c0 2.266-1.133 4.815-2.833 6.514L171.087 335.593c-1.7 1.7-4.249 2.832-6.515 2.832s-4.815-1.133-6.515-2.832L26.064 203.599c-1.7-1.7-2.832-4.248-2.832-6.514s1.132-4.816 2.832-6.515l14.162-14.163c1.7-1.699 3.966-2.832 6.515-2.832 2.266 0 4.815 1.133 6.515 2.832l111.316 111.317 111.316-111.317c1.7-1.699 4.249-2.832 6.515-2.832s4.815 1.133 6.515 2.832l14.162 14.163c1.7 1.7 2.833 4.249 2.833 6.515z"></path>
</svg></span></span></a>',
'item_simple_li_class' => 'menu-item menu-item-type-post_type menu-item-type-taxonomy menu-item-object-course',
'item_with_children_li_class' => 'menu-item menu-item-type-taxonomy menu-item-object-category menu-item-has-children',
'item_with_children_li_class_mobile' => 'menu-item menu-item-type-taxonomy menu-item-object-category menu-item-has-children'
                ]
            ],
            'blocksy' => [
                'desktop' => [
                    'item_breakpoint' => '1025px',
'item_simple' => '<a href="{{term_url}}" class="ct-menu-link">{{term_name}}</a>',

'item_with_children' => '<a href="{{term_url}}" class="ct-menu-link animated-submenu-inline">{{term_name}}
<span class="ct-toggle-dropdown-desktop">
<svg class="ct-icon" width="8" height="8" viewBox="0 0 15 15">
<path d="M2.1,3.2l5.4,5.4l5.4-5.4L15,4.3l-7.5,7.5L0,4.3L2.1,3.2z"></path>
</svg>
</span></a>
<button class="ct-toggle-dropdown-desktop-ghost ka-desktop-menu" aria-label="Utvid nedtrekksmenyen" aria-haspopup="true" aria-expanded="false"></button>',

'item_with_children_mobile' => '<span class="ct-sub-menu-parent">
<a href="{{term_url}}" class="ct-menu-link">{{term_name}}</a>
<button class="ct-toggle-dropdown-mobile" aria-label="Utvid nedtrekksmenyen" aria-haspopup="true" aria-expanded="false">
<svg class="ct-icon toggle-icon-1" width="15" height="15" viewBox="0 0 15 15">
<path d="M3.9,5.1l3.6,3.6l3.6-3.6l1.4,0.7l-5,5l-5-5L3.9,5.1z"></path>
</svg>
</button>
</span>',

'item_simple_li_class' => 'menu-item menu-item-type-post_type menu-item-type-taxonomy menu-item-object-course ct-menu-item',
'item_with_children_li_class' => 'menu-item menu-item-type-taxonomy menu-item-object-category menu-item-has-children ct-menu-item animated-submenu-inline',
'item_with_children_li_class_mobile' => 'menu-item menu-item-type-taxonomy menu-item-object-category menu-item-has-children ct-menu-item'
                ]
            ]
        ];
        
        return isset($structures[$theme][$type]) ? $structures[$theme][$type] : $structures['default'][$type];
    }
    
    public function get_menu_structure($theme = '') {
        if (empty($theme)) {
            $current_theme = wp_get_theme();
            $theme = strtolower($current_theme->get('Name'));
        }
        
        $structure = $this->get_theme_structure($theme, 'desktop');
        return $structure;
    }
    
    public function register_settings() {
        // I stedet for å bruke register_setting, håndterer vi alt manuelt
        add_action('admin_post_save_theme_customizations', array($this, 'save_theme_customizations'));
    }
    
    public function allow_custom_html_attributes($tags) {
        // Legg til eller oppdater button element
        if (!isset($tags['button'])) {
            $tags['button'] = array();
        }
        
        // Legg til alle attributtene vi trenger
        $tags['button'] = array_merge($tags['button'], array(
            'class'               => true,
            'data-toggle-duration'=> true,
            'data-toggle-target'  => true,
            'aria-expanded'      => true,
            'aria-label'         => true,
            'aria-haspopup'      => true,
            'type'               => true,
            'role'               => true
        ));
        
        // Legg til støtte for span elementer
        if (!isset($tags['span'])) {
            $tags['span'] = array();
        }
        $tags['span'] = array_merge($tags['span'], array(
            'class'          => true,
            'role'           => true,
            'aria-hidden'    => true,
            'application'    => true
        ));
        
        // Legg til støtte for svg elementer og deres attributter
        $tags['svg'] = array(
            'class'           => true,
            'aria-hidden'     => true,
            'fill'            => true,
            'version'         => true,
            'xmlns'           => true,
            'viewBox'         => true,
            'viewbox'         => true,
            'width'           => true,
            'height'          => true,
            'x'              => true,
            'y'              => true,
            'enable-background' => true,
            'xml:space'      => true,
            'xmlns:xlink'    => true,
            'preserveAspectRatio' => true
        );
        
        // Legg til støtte for path element
        $tags['path'] = array(
            'd'              => true,
            'fill-rule'      => true,
            'clip-rule'      => true,
            'stroke-linejoin' => true,
            'stroke-miterlimit' => true,
            'fill'           => true,
            'stroke'         => true
        );
        
        // Legg til støtte for title element
        $tags['title'] = array();
        
        // Legg til støtte for a element
        if (!isset($tags['a'])) {
            $tags['a'] = array();
        }
        $tags['a'] = array_merge($tags['a'], array(
            'href'           => true,
            'class'          => true,
            'aria-expanded'  => true,
            'aria-haspopup'  => true,
            'role'           => true,
            'target'         => true,
            'rel'            => true
        ));
        
        return $tags;
    }
    
    public function save_theme_customizations() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        if (!isset($_POST['theme_customizations_nonce']) || 
            !wp_verify_nonce($_POST['theme_customizations_nonce'], 'save_theme_customizations')) {
            wp_die('Invalid nonce');
        }

        $input = $_POST[$this->option_name];
        
        // Sjekk om vi skal tilbakestille til standard
        if (isset($input['reset_to_default']) && $input['reset_to_default'] == '1') {
            // Fjern alle innstillinger fra databasen
            delete_option($this->option_name);
            
            // Redirect tilbake til innstillingssiden
            wp_redirect(add_query_arg(
                array('page' => 'kursagenten-theme-customizations', 'updated' => 'true', 'reset' => 'true'),
                admin_url('admin.php')
            ));
            exit;
        }
        
        $sanitized = array();
        
        // Håndter breakpoint-innstilling
        if (isset($input['item_breakpoint'])) {
            // Fjern eventuelle ikke-numeriske tegn og legg til 'px'
            $breakpoint = preg_replace('/[^0-9]/', '', $input['item_breakpoint']);
            $sanitized['item_breakpoint'] = $breakpoint . 'px';
        }
        
        if (isset($input['menu_structure'])) {
            $sanitized['menu_structure'] = array();
            
            // Håndter CSS klasser
            if (isset($input['menu_structure']['item_simple_li_class'])) {
                $sanitized['menu_structure']['item_simple_li_class'] = 
                    sanitize_text_field($input['menu_structure']['item_simple_li_class']);
            }
            if (isset($input['menu_structure']['item_with_children_li_class'])) {
                $sanitized['menu_structure']['item_with_children_li_class'] = 
                    sanitize_text_field($input['menu_structure']['item_with_children_li_class']);
            }
            
            // Håndter HTML-strukturer
            $html_fields = array(
                'item_simple',
                'item_with_children',
                'item_with_children_mobile'
            );
            
            foreach ($html_fields as $field) {
                if (isset($input['menu_structure'][$field])) {
                    // Fjern potensielt skadelig kode og ekstra anførselstegn
                    $value = $input['menu_structure'][$field];
                    $value = str_replace(
                        array('javascript:', 'onclick=', 'onerror=', 'onload=', '<script', '</script>'),
                        '',
                        $value
                    );
                    // Fjern ekstra anførselstegn rundt SVG-attributter
                    $value = preg_replace('/\\\"/', '"', $value);
                    $sanitized['menu_structure'][$field] = $value;
                }
            }
        }
        
        $sanitized['disable_menu_styles'] = isset($input['disable_menu_styles']) ? 1 : 0;
        
        // Lagre direkte i databasen
        update_option($this->option_name, $sanitized);
        
        // Redirect tilbake til innstillingssiden
        wp_redirect(add_query_arg(
            array('page' => 'kursagenten-theme-customizations', 'updated' => 'true'),
            admin_url('admin.php')
        ));
        exit;
    }

    public function ajax_reset_theme_customizations() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'reset_theme_customizations')) {
            wp_send_json_error('Invalid nonce');
        }

        // Fjern alle innstillinger fra databasen
        delete_option($this->option_name);
        
        // Send suksess-respons
        wp_send_json_success('Innstillingene ble tilbakestilt');
    }
}



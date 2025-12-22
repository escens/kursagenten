<?php
/**
 * Dashboard widget for displaying Kursagenten changelog and plugin information
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register the dashboard widget
 */
function kursagenten_register_dashboard_widget() {
    // Only show to users who can manage options
    if (!current_user_can('manage_options')) {
        return;
    }
    
    wp_add_dashboard_widget(
        'kursagenten_changelog_widget',
        __('Kursagenten plugin - Versjonshistorikk', 'kursagenten'),
        'kursagenten_dashboard_widget_content'
    );
}
add_action('wp_dashboard_setup', 'kursagenten_register_dashboard_widget');

/**
 * Dashboard widget content
 */
function kursagenten_dashboard_widget_content() {
    $changelog_path = KURSAG_PLUGIN_DIR . 'CHANGELOG.md';
    $changelog_content = '';
    
    // Read changelog file
    if (file_exists($changelog_path)) {
        $changelog_content = file_get_contents($changelog_path);
    }
    
    // Get current plugin version
    $current_version = defined('KURSAG_VERSION') ? KURSAG_VERSION : '1.1.07';
    
    // Parse changelog to extract latest entries
    $parsed_changelog = kursagenten_parse_changelog($changelog_content);
    
    ?>
    <div class="kursagenten-dashboard-widget">
        <div class="kursagenten-widget-header">
            <p>
                <strong><?php echo esc_html__('Nåværende versjon:', 'kursagenten'); ?></strong> 
                <span style="color: #2271b1; font-weight: bold;"><?php echo esc_html($current_version); ?></span>
            </p>
            <p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=ka_documentation')); ?>" class="button button-primary">
                    <?php echo esc_html__('Se dokumentasjon', 'kursagenten'); ?>
                </a>
                <a href="https://trello.com/b/2N0VmyYc/kursagenten-wp-plugin" target="_blank" class="button">
                    <?php echo esc_html__('Trello changelog', 'kursagenten'); ?>
                </a>
                <a href="<?php echo esc_url(KURSAG_HOMEPAGE); ?>" target="_blank" class="button">
                    <?php echo esc_html__('Plugin-side', 'kursagenten'); ?>
                </a>
            </p>
        </div>
        
        <div class="kursagenten-widget-changelog">
            <?php if (!empty($parsed_changelog)): ?>
                <h3 style="margin-top: 20px; margin-bottom: 10px;">
                    <?php echo esc_html__('Siste endringer', 'kursagenten'); ?>
                </h3>
                <div class="kursagenten-changelog-entries">
                    <?php echo wp_kses_post($parsed_changelog); ?>
                </div>
            <?php else: ?>
                <p><?php echo esc_html__('Kunne ikke laste versjonshistorikk.', 'kursagenten'); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="kursagenten-widget-footer" style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #ddd;">
            <p style="margin: 0; font-size: 12px; color: #666;">
                <?php 
                printf(
                    esc_html__('Utviklet av %s', 'kursagenten'),
                    '<a href="' . esc_url(KURSAG_AUTHOR_URI) . '" target="_blank">' . esc_html(KURSAG_AUTHOR) . '</a>'
                );
                ?>
            </p>
        </div>
    </div>
    
    <style>
        .kursagenten-dashboard-widget {
            line-height: 1.6;
        }
        .kursagenten-widget-header {
            margin-bottom: 15px;
        }
        .kursagenten-widget-header p {
            margin: 8px 0;
        }
        #kursagenten_changelog_widget  .button, #kursagenten_changelog_widget .button-secondary {
            border-color: #0000000f;
        }
        .kursagenten-changelog-entries {
            max-height: 400px;
            overflow-y: auto;
            padding-right: 5px;
        }
        .kursagenten-changelog-entries h4 {
            margin: 15px 0 8px 0;
            color: #2271b1;
            font-size: 14px;
            font-weight: 600;
        }
        .kursagenten-changelog-entries ul {
            margin: 8px 0 15px 20px;
            padding-left: 0;
        }
        .kursagenten-changelog-entries li {
            margin: 5px 0;
            list-style-type: disc;
        }
        .kursagenten-changelog-entries .changelog-version {
            padding: 8px 12px;
            border-top: 1px solid #2271b130;
            margin-bottom: 10px;
        }
        .kursagenten-changelog-entries .changelog-date {
            color: #666;
            font-size: 12px;
            margin-left: 8px;
        }
        .kursagenten-changelog-entries .changelog-section {
            margin-bottom: 15px;
        }
        .kursagenten-changelog-entries .changelog-section:last-child {
            margin-bottom: 0;
        }
        
    </style>
    <?php
}

/**
 * Parse changelog markdown content and convert to HTML
 * Shows latest 3-5 versions
 */
function kursagenten_parse_changelog($content) {
    if (empty($content)) {
        return '';
    }
    
    // Split by version headers (##)
    $versions = preg_split('/^##\s+/m', $content, -1, PREG_SPLIT_NO_EMPTY);
    
    if (empty($versions)) {
        return '';
    }
    
    $html = '';
    $version_count = 0;
    $max_versions = 5; // Show latest 5 versions
    
    foreach ($versions as $version_block) {
        if ($version_count >= $max_versions) {
            break;
        }
        
        // Extract version number and date
        $lines = explode("\n", trim($version_block));
        $header_line = array_shift($lines);
        
        // Parse version header: "1.1.07 - 2025-12-12"
        if (preg_match('/^(\d+\.\d+\.\d+)\s*-\s*(.+)$/', $header_line, $matches)) {
            $version = $matches[1];
            $date = $matches[2];
            
            $html .= '<div class="changelog-section">';
            $html .= '<div class="changelog-version">';
            $html .= '<strong>' . esc_html($version) . '</strong>';
            $html .= '<span class="changelog-date">' . esc_html($date) . '</span>';
            $html .= '</div>';
            
            // Process changelog items
            $current_section = '';
            $items = array();
            
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) {
                    continue;
                }
                
                // Check if it's a changelog item (starts with -)
                if (preg_match('/^-\s*(.+)$/', $line, $item_matches)) {
                    $item_text = $item_matches[1];
                    
                    // Categorize items
                    $category = '';
                    if (stripos($item_text, 'lagt til:') !== false) {
                        $category = 'lagt-til';
                        $item_text = str_ireplace('lagt til:', '', $item_text);
                    } elseif (stripos($item_text, 'endret:') !== false || stripos($item_text, 'endring:') !== false) {
                        $category = 'endret';
                        $item_text = preg_replace('/^(endret|endring):\s*/i', '', $item_text);
                    } elseif (stripos($item_text, 'fix:') !== false || stripos($item_text, 'rettet:') !== false) {
                        $category = 'fix';
                        $item_text = preg_replace('/^(fix|rettet):\s*/i', '', $item_text);
                    } elseif (stripos($item_text, 'forbedring:') !== false) {
                        $category = 'forbedring';
                        $item_text = str_ireplace('forbedring:', '', $item_text);
                    } elseif (stripos($item_text, 'internt:') !== false || stripos($item_text, 'intert:') !== false) {
                        $category = 'internt';
                        $item_text = preg_replace('/^(internt|intert):\s*/i', '', $item_text);
                    }
                    
                    $item_text = trim($item_text);
                    if (!empty($item_text)) {
                        $items[] = array(
                            'text' => $item_text,
                            'category' => $category
                        );
                    }
                }
            }
            
            // Output items grouped by category
            if (!empty($items)) {
                $html .= '<ul>';
                foreach ($items as $item) {
                    $prefix = '';
                    switch ($item['category']) {
                        case 'lagt-til':
                            $prefix = '<strong style="color: #42a05a;">Lagt til:</strong> ';
                            break;
                        case 'endret':
                            $prefix = '<strong style="color: #2271b1;">Endret:</strong> ';
                            break;
                        case 'fix':
                            $prefix = '<strong style="color:#ca4d4f;">Fix:</strong> ';
                            break;
                        case 'forbedring':
                            $prefix = '<strong style="color: #826eb4;">Forbedring:</strong> ';
                            break;
                        case 'internt':
                            $prefix = '<strong style="color: #666;">Internt:</strong> ';
                            break;
                    }
                    $html .= '<li>' . $prefix . esc_html($item['text']) . '</li>';
                }
                $html .= '</ul>';
            }
            
            $html .= '</div>';
            $version_count++;
        }
    }
    
    return $html;
}

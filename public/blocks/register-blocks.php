<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Kursagenten Gutenberg blocks.
 */
function kursagenten_register_blocks(): void {
    $block_dir = KURSAG_PLUGIN_DIR . '/public/blocks/taxonomy-grid';
    $metadata_path = $block_dir . '/block.json';
    $render_path = $block_dir . '/render.php';

    if (!file_exists($metadata_path) || !file_exists($render_path)) {
        // Fail gracefully if block files are missing in this deployment.
        return;
    }

    require_once $render_path;

    $asset_path = KURSAG_PLUGIN_DIR . '/build/taxonomy-grid.asset.php';
    $editor_js_path = KURSAG_PLUGIN_DIR . '/build/taxonomy-grid.js';
    $editor_css_path = KURSAG_PLUGIN_DIR . '/build/index.css';

    if (!file_exists($editor_js_path)) {
        return;
    }

    $asset = [
        'dependencies' => ['wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-server-side-render'],
        'version' => filemtime($editor_js_path),
    ];

    if (file_exists($asset_path)) {
        $loaded_asset = require $asset_path;
        if (is_array($loaded_asset)) {
            $asset = wp_parse_args($loaded_asset, $asset);
        }
    }

    wp_register_script(
        'kursagenten-taxonomy-grid-editor',
        KURSAG_PLUGIN_URL . '/build/taxonomy-grid.js',
        $asset['dependencies'],
        (string) $asset['version'],
        true
    );

    $taxonomy_grid_editor_data = [
        'useRegions' => (bool) get_option('kursagenten_use_regions', false),
        'regionOptions' => [],
    ];
    if ($taxonomy_grid_editor_data['useRegions']) {
        require_once KURSAG_PLUGIN_DIR . '/includes/helpers/location-regions.php';
        foreach (kursagenten_get_valid_regions() as $region_key) {
            $taxonomy_grid_editor_data['regionOptions'][] = [
                'label' => (string) kursagenten_get_region_display_name($region_key),
                'value' => (string) $region_key,
            ];
        }
    }
    wp_add_inline_script(
        'kursagenten-taxonomy-grid-editor',
        'window.kursagentenTaxonomyGridData = ' . wp_json_encode($taxonomy_grid_editor_data) . ';',
        'before'
    );

    if (file_exists($editor_css_path)) {
        wp_register_style(
            'kursagenten-taxonomy-grid-editor',
            KURSAG_PLUGIN_URL . '/build/index.css',
            [],
            (string) filemtime($editor_css_path)
        );
    }

    $style_files = [
        'kursagenten-taxonomy-grid-base' => '/public/blocks/taxonomy-grid/style-base.css',
        'kursagenten-taxonomy-grid-stablet' => '/public/blocks/taxonomy-grid/style-stablet.css',
        'kursagenten-taxonomy-grid-rad' => '/public/blocks/taxonomy-grid/style-rad.css',
        'kursagenten-taxonomy-grid-liste' => '/public/blocks/taxonomy-grid/style-liste.css',
        'kursagenten-taxonomy-grid-kort' => '/public/blocks/taxonomy-grid/style-kort.css',
        'kursagenten-taxonomy-grid-kort-bg' => '/public/blocks/taxonomy-grid/style-kort-bg.css',
    ];

    foreach ($style_files as $handle => $relative_path) {
        $absolute = KURSAG_PLUGIN_DIR . $relative_path;
        if (!file_exists($absolute)) {
            continue;
        }
        wp_register_style(
            $handle,
            KURSAG_PLUGIN_URL . $relative_path,
            [],
            (string) filemtime($absolute)
        );
    }

    if (!function_exists('kursagenten_render_taxonomy_grid_block')) {
        return;
    }

    register_block_type_from_metadata(
        $block_dir,
        [
            'editor_script' => 'kursagenten-taxonomy-grid-editor',
            'editor_style' => 'kursagenten-taxonomy-grid-editor',
            'render_callback' => 'kursagenten_render_taxonomy_grid_block',
        ]
    );
}
add_action('init', 'kursagenten_register_blocks');

/**
 * Enqueue taxonomy-grid styles where block markup is rendered.
 */
function kursagenten_enqueue_taxonomy_grid_styles_for_assets(): void {
    $handles = [
        'kursagenten-taxonomy-grid-base',
        'kursagenten-taxonomy-grid-stablet',
        'kursagenten-taxonomy-grid-rad',
        'kursagenten-taxonomy-grid-liste',
        'kursagenten-taxonomy-grid-kort',
        'kursagenten-taxonomy-grid-kort-bg',
    ];

    foreach ($handles as $handle) {
        if (wp_style_is($handle, 'registered') && !wp_style_is($handle, 'enqueued')) {
            wp_enqueue_style($handle);
        }
    }
}
// enqueue_block_assets runs for frontend and for the editor content iframe.
add_action('enqueue_block_assets', 'kursagenten_enqueue_taxonomy_grid_styles_for_assets');
// Keep this hook too for non-iframe editor contexts.
add_action('enqueue_block_editor_assets', 'kursagenten_enqueue_taxonomy_grid_styles_for_assets');

/**
 * Register custom Kursagenten block category.
 *
 * @param array<int,array<string,mixed>> $categories Existing categories.
 * @return array<int,array<string,mixed>>
 */
function kursagenten_block_category(array $categories): array {
    $categories[] = [
        'slug' => 'kursagenten',
        'title' => 'Kursagenten',
        'icon' => 'welcome-learn-more',
    ];

    return $categories;
}
add_filter('block_categories_all', 'kursagenten_block_category', 10, 1);

<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Kursagenten Gutenberg blocks.
 */
function kursagenten_register_blocks(): void {
    /*
     * Single course building blocks – PAUSED.
     *
     * Disabled on 2026-04-16: the single-* blocks and their shortcodes are
     * intentionally not registered in the published plugin while the feature
     * is on hold. All supporting code (render.php, block.json files, editor
     * JS, stylesheets, shortcode file) remains in the repository so the
     * feature can be re-enabled quickly by uncommenting this array and the
     * registration block further down (search for "Single course building
     * blocks – PAUSED" in this file).
     */
    /*
    $single_blocks = [
        'single-title' => 'kursagenten_render_single_title_block',
        'single-course-link' => 'kursagenten_render_single_course_link_block',
        'single-signup-button' => 'kursagenten_render_single_signup_button_block',
        'single-schedule-list' => 'kursagenten_render_single_schedule_list_block',
        'single-next-course-info' => 'kursagenten_render_single_next_course_info_block',
        'single-ka-content' => 'kursagenten_render_single_ka_content_block',
        'single-contact' => 'kursagenten_render_single_contact_block',
        'single-related-courses' => 'kursagenten_render_single_related_courses_block',
    ];
    */

    // Taxonomy grid block (existing).
    $block_dir = KURSAG_PLUGIN_DIR . '/public/blocks/taxonomy-grid';
    $metadata_path = $block_dir . '/block.json';
    $render_path = $block_dir . '/render.php';

    if (file_exists($metadata_path) && file_exists($render_path)) {
        require_once $render_path;

        $asset_path = KURSAG_PLUGIN_DIR . '/build/taxonomy-grid.asset.php';
        $editor_js_path = KURSAG_PLUGIN_DIR . '/build/taxonomy-grid.js';
        $editor_css_path = KURSAG_PLUGIN_DIR . '/build/index.css';

        if (file_exists($editor_js_path)) {
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

        if (function_exists('kursagenten_render_taxonomy_grid_block')) {
            register_block_type_from_metadata(
                $block_dir,
                [
                    'editor_script' => wp_script_is('kursagenten-taxonomy-grid-editor', 'registered') ? 'kursagenten-taxonomy-grid-editor' : null,
                    'editor_style' => wp_style_is('kursagenten-taxonomy-grid-editor', 'registered') ? 'kursagenten-taxonomy-grid-editor' : null,
                    'render_callback' => 'kursagenten_render_taxonomy_grid_block',
                ]
            );
        }
    }

    // Single course building blocks – PAUSED.
    // See note at top of kursagenten_register_blocks(). To re-enable the
    // single-* blocks, uncomment the block below together with the
    // $single_blocks array at the top of this function.
    /*
    $single_render_path = KURSAG_PLUGIN_DIR . '/public/blocks/single-elements/render.php';
    if (file_exists($single_render_path)) {
        require_once $single_render_path;
    }

    // Register the base design-tokens stylesheet (`:root` variables from
    // frontend-course-style.css) so it can be used as a dependency of the
    // block stylesheets below. This guarantees the tokens are defined both
    // on the frontend and in the Gutenberg/Kadence Elements editor.
    $base_tokens_path = KURSAG_PLUGIN_DIR . '/assets/css/public/frontend-course-style.css';
    if (file_exists($base_tokens_path) && !wp_style_is('kursagenten-single-base', 'registered')) {
        wp_register_style(
            'kursagenten-single-base',
            KURSAG_PLUGIN_URL . '/assets/css/public/frontend-course-style.css',
            [],
            (string) filemtime($base_tokens_path)
        );
    }

    $single_asset_path = KURSAG_PLUGIN_DIR . '/build/single-elements.asset.php';
    $single_editor_js_path = KURSAG_PLUGIN_DIR . '/build/single-elements.js';
    $single_editor_css_path = KURSAG_PLUGIN_DIR . '/public/blocks/single-elements/editor.css';

    if (file_exists($single_editor_js_path)) {
        $single_asset = [
            'dependencies' => ['wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-server-side-render'],
            'version' => filemtime($single_editor_js_path),
        ];
        if (file_exists($single_asset_path)) {
            $loaded_asset = require $single_asset_path;
            if (is_array($loaded_asset)) {
                $single_asset = wp_parse_args($loaded_asset, $single_asset);
            }
        }

        wp_register_script(
            'kursagenten-single-elements-editor',
            KURSAG_PLUGIN_URL . '/build/single-elements.js',
            $single_asset['dependencies'],
            (string) $single_asset['version'],
            true
        );

        if (file_exists($single_editor_css_path)) {
            $editor_style_deps = wp_style_is('kursagenten-single-base', 'registered')
                ? ['kursagenten-single-base']
                : [];
            wp_register_style(
                'kursagenten-single-elements-editor',
                KURSAG_PLUGIN_URL . '/public/blocks/single-elements/editor.css',
                $editor_style_deps,
                (string) filemtime($single_editor_css_path)
            );
        }
    }

    $single_style_path = KURSAG_PLUGIN_DIR . '/public/blocks/single-elements/style.css';
    if (file_exists($single_style_path)) {
        $single_style_deps = wp_style_is('kursagenten-single-base', 'registered')
            ? ['kursagenten-single-base']
            : [];
        wp_register_style(
            'kursagenten-single-elements',
            KURSAG_PLUGIN_URL . '/public/blocks/single-elements/style.css',
            $single_style_deps,
            (string) filemtime($single_style_path)
        );
    }

    foreach ($single_blocks as $dir_name => $render_callback) {
        $dir = KURSAG_PLUGIN_DIR . '/public/blocks/' . $dir_name;
        $meta = $dir . '/block.json';
        if (!file_exists($meta) || !is_string($render_callback) || !function_exists($render_callback)) {
            continue;
        }
        $args = [
            'render_callback' => $render_callback,
        ];
        if (wp_script_is('kursagenten-single-elements-editor', 'registered')) {
            $args['editor_script'] = 'kursagenten-single-elements-editor';
        }
        if (wp_style_is('kursagenten-single-elements-editor', 'registered')) {
            $args['editor_style'] = 'kursagenten-single-elements-editor';
        }
        if (wp_style_is('kursagenten-single-elements', 'registered')) {
            $args['style'] = 'kursagenten-single-elements';
        }
        register_block_type_from_metadata($dir, $args);
    }
    */
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

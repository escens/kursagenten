<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Maps block attributes to normalized render data.
 */
final class Kursagenten_Block_Attribute_Mapper {
    /**
     * @param array<string,mixed> $attributes
     * @return array<string,mixed>
     */
    public static function map_taxonomy_grid(array $attributes): array {
        $defaults = [
            'sourceType' => 'category',
            'stylePreset' => 'stablet-standard',
            'columnsDesktop' => 3,
            'columnsTablet' => 2,
            'columnsMobile' => 1,
            'showImage' => true,
            'showDescription' => false,
            'descriptionWordLimit' => 22,
            'descriptionWordLimitExtended' => 0,
            'useCardDesign' => true,
            'shadowPreset' => 'none',
            'imageSize' => '240px',
            'imageAspect' => '4/3',
            'imageRadius' => '8px',
            'imageRadiusTop' => '8px',
            'imageRadiusRight' => '8px',
            'imageRadiusBottom' => '8px',
            'imageRadiusLeft' => '8px',
            'imageBorderWidth' => '0px',
            'imageBorderWidthHover' => '0px',
            'imageBorderStyle' => 'solid',
            'imageBorderStyleHover' => 'solid',
            'imageBorderColor' => '',
            'imageBorderColorHover' => '',
            'imageBackgroundColor' => '',
            'fontMin' => 15,
            'fontMax' => 20,
            'descriptionFontMin' => 12,
            'descriptionFontMax' => 16,
            'titleColor' => '',
            'descriptionColor' => '',
            'fontWeightTitle' => '600',
            'fontWeightDescription' => '400',
            'textColor' => '',
            'textAlignDesktop' => 'left',
            'textAlignTablet' => 'left',
            'textAlignMobile' => 'left',
            'verticalAlignDesktop' => 'top',
            'verticalAlignTablet' => 'top',
            'verticalAlignMobile' => 'top',
            'titleTag' => 'h3',
            'cardBackgroundColor' => '',
            'cardBackgroundColorHover' => '',
            'cardRadius' => '12px',
            'cardBorderWidth' => '1px',
            'cardBorderStyle' => 'solid',
            'cardBorderColor' => '#d0d7de',
            'backgroundMode' => 'color',
            'overlayStrength' => 40,
            'wrapperPaddingDesktop' => '0px',
            'wrapperPaddingTablet' => '0px',
            'wrapperPaddingMobile' => '0px',
            'cardPaddingDesktop' => '16px',
            'cardPaddingTablet' => '14px',
            'cardPaddingMobile' => '12px',
            'cardMarginDesktop' => '0px',
            'cardMarginTablet' => '0px',
            'cardMarginMobile' => '0px',
            'rowGapDesktop' => '24px',
            'rowGapTablet' => '20px',
            'rowGapMobile' => '16px',
            'filterMode' => 'standard',
            'categoryImageSource' => 'main',
            'region' => '',
            'locationInclude' => [],
            'locationShowInfo' => false,
            'instructorExclude' => [],
            'categoryParentSlug' => '',
            'categoryParentSlugs' => [],
            'categoryLocationFilter' => '',
            'instructorNameMode' => 'standard',
            'instructorImageSource' => 'standard',
            'showInstructorPhone' => false,
            'showInstructorEmail' => false,
        ];

        $mapped = wp_parse_args($attributes, $defaults);
        $mapped['columnsDesktop'] = max(1, min(6, (int) $mapped['columnsDesktop']));
        $mapped['columnsTablet'] = max(1, min(4, (int) $mapped['columnsTablet']));
        $mapped['columnsMobile'] = max(1, min(2, (int) $mapped['columnsMobile']));
        $mapped['descriptionWordLimit'] = max(0, min(120, (int) $mapped['descriptionWordLimit']));
        $mapped['descriptionWordLimitExtended'] = max(0, min(200, (int) $mapped['descriptionWordLimitExtended']));
        $mapped['fontMin'] = max(11, min(28, (int) $mapped['fontMin']));
        $mapped['fontMax'] = max($mapped['fontMin'], min(36, (int) $mapped['fontMax']));
        $mapped['descriptionFontMin'] = max(10, min(24, (int) $mapped['descriptionFontMin']));
        $mapped['descriptionFontMax'] = max($mapped['descriptionFontMin'], min(28, (int) $mapped['descriptionFontMax']));
        $mapped['overlayStrength'] = max(0, min(85, (int) $mapped['overlayStrength']));
        $allowed_source_types = ['category', 'location', 'instructor'];
        if (!in_array($mapped['sourceType'], $allowed_source_types, true)) {
            $mapped['sourceType'] = 'category';
        }
        $allowed_style_presets = [
            'stablet-standard',
            'stablet-kort',
            'stablet-kort-innfelt',
            'stablet-kort-overlapp',
            'rad-standard',
            'rad-kort',
            'rad-detalj',
            'rad-kompakt',
            'liste-enkel',
            'kort-bakgrunn',
            'kort-bakgrunnsfarge',
        ];
        if (!in_array($mapped['stylePreset'], $allowed_style_presets, true)) {
            $mapped['stylePreset'] = 'stablet-standard';
        }
        $allowed_background_modes = ['color', 'taxonomyImage'];
        if (!in_array($mapped['backgroundMode'], $allowed_background_modes, true)) {
            $mapped['backgroundMode'] = 'color';
        }
        $allowed_filter_modes = ['standard', 'hovedkategorier', 'subkategorier'];
        if (!in_array($mapped['filterMode'], $allowed_filter_modes, true)) {
            $mapped['filterMode'] = 'standard';
        }
        $allowed_instructor_name_modes = ['standard', 'fornavn', 'etternavn'];
        if (!in_array($mapped['instructorNameMode'], $allowed_instructor_name_modes, true)) {
            $mapped['instructorNameMode'] = 'standard';
        }
        $allowed_instructor_image_sources = ['standard', 'alternative'];
        if (!in_array($mapped['instructorImageSource'], $allowed_instructor_image_sources, true)) {
            $mapped['instructorImageSource'] = 'standard';
        }
        $mapped['showInstructorPhone'] = !empty($mapped['showInstructorPhone']);
        $mapped['showInstructorEmail'] = !empty($mapped['showInstructorEmail']);
        $mapped['categoryParentSlug'] = sanitize_title((string) $mapped['categoryParentSlug']);
        $mapped['categoryLocationFilter'] = sanitize_title((string) $mapped['categoryLocationFilter']);
        if (!is_array($mapped['categoryParentSlugs'])) {
            $mapped['categoryParentSlugs'] = [];
        }
        $mapped['categoryParentSlugs'] = array_values(array_unique(array_filter(array_map(
            static function ($item): string {
                return sanitize_title((string) $item);
            },
            $mapped['categoryParentSlugs']
        ))));
        if (empty($mapped['categoryParentSlugs']) && $mapped['categoryParentSlug'] !== '') {
            $mapped['categoryParentSlugs'] = [$mapped['categoryParentSlug']];
        }

        if (!is_array($mapped['locationInclude'])) {
            $mapped['locationInclude'] = [];
        }
        $mapped['locationInclude'] = array_values(array_unique(array_filter(array_map(
            static function ($item): string {
                return sanitize_title((string) $item);
            },
            $mapped['locationInclude']
        ))));

        if (!is_array($mapped['instructorExclude'])) {
            $mapped['instructorExclude'] = [];
        }
        $mapped['instructorExclude'] = array_values(array_unique(array_filter(array_map(
            static function ($item): string {
                return sanitize_title((string) $item);
            },
            $mapped['instructorExclude']
        ))));

        $mapped['locationShowInfo'] = !empty($mapped['locationShowInfo']);
        $allowed_alignments = ['left', 'center', 'right'];
        if (!in_array($mapped['textAlignDesktop'], $allowed_alignments, true)) {
            $mapped['textAlignDesktop'] = 'left';
        }
        if (!in_array($mapped['textAlignTablet'], $allowed_alignments, true)) {
            $mapped['textAlignTablet'] = 'left';
        }
        if (!in_array($mapped['textAlignMobile'], $allowed_alignments, true)) {
            $mapped['textAlignMobile'] = 'left';
        }
        $allowed_vertical_alignments = ['top', 'center', 'bottom'];
        if (!in_array($mapped['verticalAlignDesktop'], $allowed_vertical_alignments, true)) {
            $mapped['verticalAlignDesktop'] = 'top';
        }
        if (!in_array($mapped['verticalAlignTablet'], $allowed_vertical_alignments, true)) {
            $mapped['verticalAlignTablet'] = 'top';
        }
        if (!in_array($mapped['verticalAlignMobile'], $allowed_vertical_alignments, true)) {
            $mapped['verticalAlignMobile'] = 'top';
        }
        $allowed_title_tags = ['h2', 'h3', 'h4', 'h5', 'h6', 'p', 'div', 'span'];
        if (!in_array($mapped['titleTag'], $allowed_title_tags, true)) {
            $mapped['titleTag'] = 'h3';
        }
        $allowed_shadow = ['none', 'outline', 'xsoft', 'soft', 'medium', 'large', 'xl'];
        if (!in_array($mapped['shadowPreset'], $allowed_shadow, true)) {
            $mapped['shadowPreset'] = 'none';
        }
        $allowed_border_styles = ['none', 'solid', 'dashed', 'dotted', 'double'];
        if (!in_array($mapped['imageBorderStyle'], $allowed_border_styles, true)) {
            $mapped['imageBorderStyle'] = 'solid';
        }
        if (!in_array($mapped['imageBorderStyleHover'], $allowed_border_styles, true)) {
            $mapped['imageBorderStyleHover'] = $mapped['imageBorderStyle'];
        }
        if (!in_array($mapped['cardBorderStyle'], $allowed_border_styles, true)) {
            $mapped['cardBorderStyle'] = 'solid';
        }
        $allowed_category_image_source = ['main', 'icon'];
        if (!in_array($mapped['categoryImageSource'], $allowed_category_image_source, true)) {
            $mapped['categoryImageSource'] = 'main';
        }
        $allowed_font_weights = ['100', '400', '600', '700', '800'];
        if (!in_array((string) $mapped['fontWeightTitle'], $allowed_font_weights, true)) {
            $mapped['fontWeightTitle'] = '600';
        }
        if (!in_array((string) $mapped['fontWeightDescription'], $allowed_font_weights, true)) {
            $mapped['fontWeightDescription'] = '400';
        }
        if ($mapped['stylePreset'] === 'rad-kompakt') {
            $mapped['stylePreset'] = 'rad-kort';
        }

        return $mapped;
    }
}

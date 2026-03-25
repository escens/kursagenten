import { registerBlockType, registerBlockVariation } from '@wordpress/blocks';
import { InspectorControls, useBlockProps, useSettings } from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	SelectControl,
	ToggleControl,
	CheckboxControl,
	TextControl,
	ColorPalette,
	BaseControl,
	Button,
	ButtonGroup,
	TabPanel,
	Dropdown,
	ColorIndicator,
	Spinner,
	Tooltip,
	Modal,
} from '@wordpress/components';
import { useServerSideRender } from '@wordpress/server-side-render';
import { RawHTML, useEffect, useState } from '@wordpress/element';
import { dispatch, useSelect } from '@wordpress/data';
import metadata from '../../../public/blocks/taxonomy-grid/block.json';
import taxonomyGridIconUrl from '../../../public/blocks/shared/icon.svg';
import './editor.css';

const taxonomyGridBlockIcon = (
	<img
		src={ taxonomyGridIconUrl }
		alt=""
		style={ { width: 24, height: 24, display: 'block' } }
	/>
);

const PRESET_ICON_COLORS = {
	image: '#cbd5e1',
	text: '#64748b',
	frame: '#6b7686',
};

const PRESET_ICON_FRAME_STROKE = 1.1;

function PresetLayoutIcon( { presetKey } ) {
	const { image, text, frame } = PRESET_ICON_COLORS;
	const commonSvgProps = {
		viewBox: '0 0 24 24',
		width: 28,
		height: 28,
		role: 'img',
		'aria-hidden': true,
	};

	if ( presetKey === 'stablet-kort-overlapp' ) {
		return (
			<svg { ...commonSvgProps }>
				<rect x="2.5" y="6.5" width="19" height="15" rx="1" fill="none" stroke={ frame } strokeWidth={ PRESET_ICON_FRAME_STROKE } />
				<circle cx="12" cy="5.5" r="3.5" fill={ image } />
				<rect x="6.5" y="14.2" width="11" height="2" rx="0.5" fill={ text } />
			</svg>
		);
	}

	if ( presetKey === 'rad-kort' ) {
		return (
			<svg { ...commonSvgProps }>
				<rect x="1.8" y="6.5" width="20.4" height="11" rx="1" fill="none" stroke={ frame } strokeWidth={ PRESET_ICON_FRAME_STROKE } />
				<rect x="2.4" y="7.1" width="6.5" height="9.8" fill={ image } />
				<rect x="10.5" y="10.8" width="9.8" height="2" rx="0.5" fill={ text } />
			</svg>
		);
	}

	if ( presetKey === 'rad-standard' ) {
		return (
			<svg { ...commonSvgProps }>
				<rect x="2.8" y="7.2" width="5.8" height="9.6" rx="0.8" fill={ image } />
				<rect x="10.2" y="10.8" width="11" height="2" rx="0.5" fill={ text } />
			</svg>
		);
	}

	if ( presetKey === 'kort-bakgrunn' ) {
		return (
			<svg { ...commonSvgProps }>
				<rect x="2.3" y="3.2" width="19.4" height="18.3" rx="1" fill={ image } stroke={ frame } strokeWidth={ PRESET_ICON_FRAME_STROKE } />
				<rect x="6.2" y="15.8" width="11.6" height="2" rx="0.5" fill={ text } />
			</svg>
		);
	}

	if ( presetKey === 'stablet-kort' ) {
		return (
			<svg { ...commonSvgProps }>
				<rect x="2.5" y="3.2" width="19" height="18.3" rx="1" fill="none" stroke={ frame } strokeWidth={ PRESET_ICON_FRAME_STROKE } />
				<rect x="3.1" y="3.8" width="17.8" height="10.4" fill={ image } />
				<rect x="7" y="16.8" width="10" height="2" rx="0.5" fill={ text } />
			</svg>
		);
	}

	if ( presetKey === 'stablet-kort-innfelt' ) {
		return (
			<svg { ...commonSvgProps }>
				<rect x="2.5" y="4.5" width="19" height="17" rx="1" fill="none" stroke={ frame } strokeWidth={ PRESET_ICON_FRAME_STROKE } />
				<circle cx="12" cy="8.8" r="2.8" fill={ image } />
				<rect x="7" y="16.6" width="10" height="2" rx="0.5" fill={ text } />
			</svg>
		);
	}

	if ( presetKey === 'rad-detalj' ) {
		return (
			<svg { ...commonSvgProps }>
				<rect x="2.6" y="6.8" width="5.8" height="10.4" rx="0.8" fill={ image } />
				<rect x="10.1" y="9.1" width="11.4" height="1.8" rx="0.4" fill={ text } />
				<rect x="10.1" y="12.3" width="9.2" height="1.6" rx="0.4" fill={ text } />
				<rect x="10.1" y="15.2" width="10.3" height="1.6" rx="0.4" fill={ text } />
			</svg>
		);
	}

	if ( presetKey === 'liste-enkel' ) {
		return (
			<svg { ...commonSvgProps }>
				<rect x="5" y="8" width="14" height="1.7" rx="0.4" fill={ text } />
				<rect x="5" y="11.2" width="14" height="1.7" rx="0.4" fill={ text } />
				<rect x="5" y="14.4" width="14" height="1.7" rx="0.4" fill={ text } />
			</svg>
		);
	}

	if ( presetKey === 'kort-bakgrunnsfarge' ) {
		return (
			<svg { ...commonSvgProps }>
				<rect x="2.3" y="3.2" width="19.4" height="18.3" rx="1" fill={ image } stroke={ frame } strokeWidth={ PRESET_ICON_FRAME_STROKE } />
				<rect x="6.2" y="15.8" width="11.6" height="2" rx="0.5" fill={ text } />
			</svg>
		);
	}

	// stablet-standard fallback
	return (
		<svg { ...commonSvgProps }>
			<rect x="5.4" y="5.2" width="13.2" height="10.6" rx="0.9" fill={ image } />
			<rect x="7" y="17.1" width="10" height="2" rx="0.5" fill={ text } />
		</svg>
	);
}

const PRESETS = [
	{ key: 'stablet-standard', label: 'Stablet standard', icon: '▦', defaults: { columnsDesktop: 3, columnsTablet: 2, columnsMobile: 1, showDescription: false, showImage: true, useCardDesign: false, backgroundMode: 'color', textAlignDesktop: 'center', textAlignTablet: 'center', textAlignMobile: 'center', verticalAlignDesktop: 'top', verticalAlignTablet: 'top', verticalAlignMobile: 'top' } },
	{ key: 'stablet-kort', label: 'Stablet kort', icon: '▣', defaults: { columnsDesktop: 3, columnsTablet: 2, columnsMobile: 1, showDescription: false, showImage: true, useCardDesign: true, shadowPreset: 'xsoft', backgroundMode: 'color', cardPaddingDesktop: '24px 18px', cardPaddingTablet: '24px 14px', cardPaddingMobile: '24px 12px', textAlignDesktop: 'center', textAlignTablet: 'center', textAlignMobile: 'center', verticalAlignDesktop: 'top', verticalAlignTablet: 'top', verticalAlignMobile: 'top' } },
	{ key: 'stablet-kort-innfelt', label: 'Stablet kort innfelt', icon: '◫', defaults: { columnsDesktop: 3, columnsTablet: 2, columnsMobile: 1, showDescription: false, showImage: true, useCardDesign: true, shadowPreset: 'xsoft', imageSize: '120px', imageAspect: '1/1', imageRadiusTop: '100%', imageRadiusRight: '100%', imageRadiusBottom: '100%', imageRadiusLeft: '100%', cardPaddingDesktop: '16px 40px 40px 40px', cardPaddingTablet: '16px 34px 40px 34px', cardPaddingMobile: '16px 34px 30px 34px', cardMarginDesktop: '5px', cardMarginTablet: '5px', cardMarginMobile: '0px', rowGapDesktop: '25px', rowGapTablet: '25px', rowGapMobile: '30px', backgroundMode: 'color', textAlignDesktop: 'center', textAlignTablet: 'center', textAlignMobile: 'center', verticalAlignDesktop: 'top', verticalAlignTablet: 'top', verticalAlignMobile: 'top' } },
	{ key: 'stablet-kort-overlapp', label: 'Stablet kort overlapp', icon: '◬', defaults: { columnsDesktop: 3, columnsTablet: 2, columnsMobile: 1, showDescription: false, showImage: true, useCardDesign: true, shadowPreset: 'xsoft', imageSize: '120px', imageAspect: '1/1', imageRadiusTop: '100%', imageRadiusRight: '100%', imageRadiusBottom: '100%', imageRadiusLeft: '100%', imageBackgroundColor: '#ffffff', cardPaddingDesktop: '16px 40px 40px 40px', cardPaddingTablet: '16px 34px 40px 34px', cardPaddingMobile: '16px 34px 30px 34px', cardMarginDesktop: '60px 5px 0 5px', cardMarginTablet: '60px 5px 0 5px', cardMarginMobile: '60px 0 0 0', rowGapDesktop: '24px', rowGapTablet: '24px', rowGapMobile: '24px', backgroundMode: 'color', textAlignDesktop: 'center', textAlignTablet: 'center', textAlignMobile: 'center', verticalAlignDesktop: 'top', verticalAlignTablet: 'top', verticalAlignMobile: 'top' } },
	{ key: 'rad-standard', label: 'Rad standard', icon: '☰', defaults: { columnsDesktop: 2, columnsTablet: 2, columnsMobile: 1, showDescription: false, showImage: true, useCardDesign: false, imageSize: '120px', imageAspect: '1/1', imageRadiusTop: '8px', imageRadiusRight: '8px', imageRadiusBottom: '8px', imageRadiusLeft: '8px', backgroundMode: 'color', textAlignDesktop: 'left', textAlignTablet: 'left', textAlignMobile: 'left', verticalAlignDesktop: 'center', verticalAlignTablet: 'center', verticalAlignMobile: 'center' } },
	{ key: 'rad-kort', label: 'Rad kort', icon: '☷', defaults: { columnsDesktop: 2, columnsTablet: 2, columnsMobile: 1, showDescription: false, showImage: true, useCardDesign: true, shadowPreset: 'xsoft', backgroundMode: 'color', textAlignDesktop: 'left', textAlignTablet: 'left', textAlignMobile: 'left', verticalAlignDesktop: 'center', verticalAlignTablet: 'center', verticalAlignMobile: 'center' } },
	{ key: 'rad-detalj', label: 'Rad utvidet beskrivelse', icon: '☷', defaults: { columnsDesktop: 1, columnsTablet: 1, columnsMobile: 1, showDescription: true, showImage: true, useCardDesign: false, imageSize: '240px', imageAspect: '1/1', imageRadiusTop: '8px', imageRadiusRight: '8px', imageRadiusBottom: '8px', imageRadiusLeft: '8px', cardPaddingDesktop: '30px', cardPaddingTablet: '24px', cardPaddingMobile: '18px', backgroundMode: 'color', textAlignDesktop: 'left', textAlignTablet: 'left', textAlignMobile: 'left', verticalAlignDesktop: 'top', verticalAlignTablet: 'top', verticalAlignMobile: 'top' } },
	{ key: 'liste-enkel', label: 'Liste enkel', icon: '≡', defaults: { columnsDesktop: 1, columnsTablet: 1, columnsMobile: 1, showDescription: false, showImage: false, useCardDesign: false, imageBorderWidth: '0px', imageBorderWidthHover: '0px', cardPaddingDesktop: '2px', cardPaddingTablet: '2px', cardPaddingMobile: '2px', cardMarginDesktop: '0px', cardMarginTablet: '0px', cardMarginMobile: '0px', rowGapDesktop: '4px', rowGapTablet: '4px', rowGapMobile: '4px', backgroundMode: 'color', textAlignDesktop: 'left', textAlignTablet: 'left', textAlignMobile: 'left', verticalAlignDesktop: 'top', verticalAlignTablet: 'top', verticalAlignMobile: 'top' } },
	{ key: 'kort-bakgrunn', label: 'Bakgrunnsbilde', icon: '◧', defaults: { columnsDesktop: 3, columnsTablet: 2, columnsMobile: 1, showDescription: true, showImage: true, useCardDesign: true, shadowPreset: 'xsoft', backgroundMode: 'taxonomyImage', cardPaddingDesktop: '26px', cardPaddingTablet: '26px', cardPaddingMobile: '26px', textColor: '#ffffff', textAlignDesktop: 'left', textAlignTablet: 'left', textAlignMobile: 'left', verticalAlignDesktop: 'bottom', verticalAlignTablet: 'bottom', verticalAlignMobile: 'bottom' } },
	{ key: 'kort-bakgrunnsfarge', label: 'Bakgrunnsfarge', icon: '◩', defaults: { columnsDesktop: 3, columnsTablet: 2, columnsMobile: 1, showDescription: true, showImage: false, useCardDesign: true, shadowPreset: 'xsoft', backgroundMode: 'color', cardPaddingDesktop: '26px', cardPaddingTablet: '26px', cardPaddingMobile: '26px', textColor: '#333333', textAlignDesktop: 'center', textAlignTablet: 'center', textAlignMobile: 'center', verticalAlignDesktop: 'center', verticalAlignTablet: 'center', verticalAlignMobile: 'center' } },
];

const SOURCE_OPTIONS = [
	{ label: 'Kurskategorier', value: 'category' },
	{ label: 'Kurssteder', value: 'location' },
	{ label: 'Instruktører', value: 'instructor' },
];

const FILTER_OPTIONS = [
	{ label: 'Standard', value: 'standard' },
	{ label: 'Hovedkategorier', value: 'hovedkategorier' },
	{ label: 'Subkategorier', value: 'subkategorier' },
];

const CATEGORY_IMAGE_SOURCE_OPTIONS = [
	{ label: 'Hovedbilde', value: 'main' },
	{ label: 'Profilbilde', value: 'icon' },
];

const NAME_MODE_OPTIONS = [
	{ label: 'Standard', value: 'standard' },
	{ label: 'Fornavn', value: 'fornavn' },
	{ label: 'Etternavn', value: 'etternavn' },
];

const INSTRUCTOR_IMAGE_SOURCE_OPTIONS = [
	{ label: 'Standard profilbilde', value: 'standard' },
	{ label: 'Alternativt bilde', value: 'alternative' },
];

const IMAGE_ASPECT_OPTIONS = [
	{ label: 'Landskap 4:3', value: '4/3' },
	{ label: 'Landskap 3:2', value: '3/2' },
	{ label: 'Landskap 16:9', value: '16/9' },
	{ label: 'Landskap 2:1', value: '2/1' },
	{ label: 'Landskap 3:1', value: '3/1' },
	{ label: 'Landskap 4:1', value: '4/1' },
	{ label: 'Portrett 3:4', value: '3/4' },
	{ label: 'Portrett 2:3', value: '2/3' },
	{ label: 'Kvadrat 1:1', value: '1/1' },
	{ label: 'Egendefinert', value: 'custom' },
];

const SHADOW_OPTIONS = [
	{ label: 'Ingen', value: 'none' },
	{ label: 'Ingen, men bruk ramme', value: 'outline' },
	{ label: 'Svak', value: 'xsoft' },
	{ label: 'Normal', value: 'soft' },
	{ label: 'Medium', value: 'medium' },
	{ label: 'Kraftig', value: 'large' },
	{ label: 'Ekstra kraftig', value: 'xl' },
];

const TITLE_TAG_OPTIONS = [
	{ label: 'H2', value: 'h2' },
	{ label: 'H3', value: 'h3' },
	{ label: 'H4', value: 'h4' },
	{ label: 'H5', value: 'h5' },
	{ label: 'H6', value: 'h6' },
	{ label: 'P', value: 'p' },
	{ label: 'Div', value: 'div' },
	{ label: 'Span', value: 'span' },
];

const TAXONOMY_GRID_EDITOR_DATA = window?.kursagentenTaxonomyGridData || {};
const REGION_OPTIONS = [
	{ label: 'Ingen region', value: '' },
	...( Array.isArray( TAXONOMY_GRID_EDITOR_DATA.regionOptions ) ? TAXONOMY_GRID_EDITOR_DATA.regionOptions : [] ),
];
const REGIONS_ENABLED = !! TAXONOMY_GRID_EDITOR_DATA.useRegions;

const FONT_WEIGHT_OPTIONS = [
	{ label: 'Tynn', value: '100' },
	{ label: 'Vanlig', value: '400' },
	{ label: 'Halvfet', value: '600' },
	{ label: 'Fet', value: '700' },
	{ label: 'Ekstra fet', value: '800' },
];

const IMAGE_BORDER_STYLES = [
	{ label: 'Solid', value: 'solid' },
	{ label: 'Stiplet', value: 'dashed' },
	{ label: 'Prikket', value: 'dotted' },
	{ label: 'Dobbel', value: 'double' },
];

const COLORS = [
	{ color: '#ffffff', name: 'Hvit' },
	{ color: '#d0d7de', name: 'Lys grå' },
	{ color: '#f4f7fb', name: 'Lys blå' },
	{ color: '#111827', name: 'Mørk' },
	{ color: '#14532d', name: 'Grønn' },
	{ color: '#7f1d1d', name: 'Rød' },
];

const DEFAULT_ATTRIBUTES = Object.fromEntries(
	Object.entries( metadata.attributes ).map( ( [ key, config ] ) => [ key, config.default ] )
);

const getPresetDefaults = ( presetKey ) => PRESETS.find( ( preset ) => preset.key === presetKey )?.defaults || {};

const RESPONSIVE_TABS = [
	{ name: 'desktop', title: 'Desktop', className: 'k-device-tab-desktop' },
	{ name: 'tablet', title: 'Nettbrett', className: 'k-device-tab-tablet' },
	{ name: 'mobile', title: 'Mobil', className: 'k-device-tab-mobile' },
];

/**
 * Syncs the editor viewport to the selected device type when switching responsive tabs.
 * Uses core/editor store (WP 6.5+) with fallback to core/edit-post for older versions.
 */
function syncEditorViewportToDevice( tabName ) {
	const deviceType = tabName.charAt( 0 ).toUpperCase() + tabName.slice( 1 );
	try {
		const editorDispatch = dispatch( 'core/editor' );
		if ( editorDispatch?.setDeviceType ) {
			editorDispatch.setDeviceType( deviceType );
			return;
		}
	} catch ( e ) {
		// core/editor may not be available
	}
	try {
		const editPostDispatch = dispatch( 'core/edit-post' );
		if ( editPostDispatch?.__experimentalSetPreviewDeviceType ) {
			editPostDispatch.__experimentalSetPreviewDeviceType( deviceType );
		}
	} catch ( e ) {
		// core/edit-post may not be available (e.g. site editor)
	}
	try {
		const editSiteDispatch = dispatch( 'core/edit-site' );
		if ( editSiteDispatch?.__experimentalSetPreviewDeviceType ) {
			editSiteDispatch.__experimentalSetPreviewDeviceType( deviceType );
		}
	} catch ( e ) {
		// core/edit-site may not be available
	}
}

const BORDER_STATE_TABS = [
	{ name: 'normal', title: 'Normal' },
	{ name: 'hover', title: 'Hover' },
];

function PresetPicker( { attributes, onSelectPreset } ) {
	return (
		<BaseControl label="Velg stil">
			<div className="k-editor-preset-grid">
				{ PRESETS.map( ( preset ) => {
					const isActive = attributes.stylePreset === preset.key;
					return (
						<button
							key={ preset.key }
							type="button"
							className={ `k-editor-preset-card ${ isActive ? 'is-active' : '' }` }
							onClick={ () => onSelectPreset( preset.key ) }
						>
							<span className="k-editor-preset-icon"><PresetLayoutIcon presetKey={ preset.key } /></span>
							<span className="k-editor-preset-label">{ preset.label }</span>
						</button>
					);
				} ) }
			</div>
		</BaseControl>
	);
}

function HorizontalAlignIcon( { type } ) {
	if ( type === 'center' ) {
		return (
			<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false">
				<path d="M12.5 15v5H11v-5H4V9h7V4h1.5v5h7v6h-7Z" />
			</svg>
		);
	}
	if ( type === 'right' ) {
		return (
			<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false">
				<path d="M4 15h11V9H4v6zM18.5 4v16H20V4h-1.5z" />
			</svg>
		);
	}
	return (
		<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false">
			<path d="M9 9v6h11V9H9zM4 20h1.5V4H4v16z" />
		</svg>
	);
}

function VerticalAlignIcon( { type } ) {
	if ( type === 'center' ) {
		return (
			<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false">
				<path d="M20 11h-5V4H9v7H4v1.5h5V20h6v-7.5h5z" />
			</svg>
		);
	}
	if ( type === 'bottom' ) {
		return (
			<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false">
				<path d="M15 4H9v11h6V4zM4 18.5V20h16v-1.5H4z" />
			</svg>
		);
	}
	return (
		<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false">
			<path d="M9 20h6V9H9v11zM4 4v1.5h16V4H4z" />
		</svg>
	);
}

function AlignmentButtons( { label, horizontalValue, verticalValue, onHorizontalChange, onVerticalChange } ) {
	return (
		<BaseControl label={ label }>
			<ButtonGroup className="k-editor-align-group">
				<Button
					variant={ horizontalValue === 'left' ? 'primary' : 'secondary' }
					onClick={ () => onHorizontalChange( 'left' ) }
					icon={ <HorizontalAlignIcon type="left" /> }
					label="Venstre"
				/>
				<Button
					variant={ horizontalValue === 'center' ? 'primary' : 'secondary' }
					onClick={ () => onHorizontalChange( 'center' ) }
					icon={ <HorizontalAlignIcon type="center" /> }
					label="Sentrert"
				/>
				<Button
					variant={ horizontalValue === 'right' ? 'primary' : 'secondary' }
					onClick={ () => onHorizontalChange( 'right' ) }
					icon={ <HorizontalAlignIcon type="right" /> }
					label="Høyre"
				/>
			</ButtonGroup>
			<ButtonGroup className="k-editor-align-group k-editor-align-group-vertical">
				<Button
					variant={ verticalValue === 'top' ? 'primary' : 'secondary' }
					onClick={ () => onVerticalChange( 'top' ) }
					icon={ <VerticalAlignIcon type="top" /> }
					label="Topp"
				/>
				<Button
					variant={ verticalValue === 'center' ? 'primary' : 'secondary' }
					onClick={ () => onVerticalChange( 'center' ) }
					icon={ <VerticalAlignIcon type="center" /> }
					label="Sentrert"
				/>
				<Button
					variant={ verticalValue === 'bottom' ? 'primary' : 'secondary' }
					onClick={ () => onVerticalChange( 'bottom' ) }
					icon={ <VerticalAlignIcon type="bottom" /> }
					label="Bunn"
				/>
			</ButtonGroup>
		</BaseControl>
	);
}

function FontWeightButtons( { label, value, onChange } ) {
	return (
		<BaseControl label={ label }>
			<ButtonGroup className="k-editor-font-weight-group">
				{ FONT_WEIGHT_OPTIONS.map( ( opt ) => (
					<Tooltip key={ opt.value } text={ opt.label }>
						<Button
							variant={ value === opt.value ? 'primary' : 'secondary' }
							onClick={ () => onChange( opt.value ) }
							style={ { fontWeight: opt.value } }
							aria-label={ `${ label }: ${ opt.label }` }
							className="k-editor-font-weight-button"
						>
							a
						</Button>
					</Tooltip>
				) ) }
			</ButtonGroup>
		</BaseControl>
	);
}

function FontSizeMinMaxControl( { label, help, minValue, maxValue, minLimit, maxLimit, onMinChange, onMaxChange } ) {
	const normalizedMin = Number.isFinite( minValue ) ? minValue : minLimit;
	const normalizedMax = Number.isFinite( maxValue ) ? maxValue : Math.max( normalizedMin, minLimit );

	const handleMinChange = ( nextValue ) => {
		const safeValue = Math.max( minLimit, Math.min( normalizedMax, nextValue || minLimit ) );
		onMinChange( safeValue );
	};

	const handleMaxChange = ( nextValue ) => {
		const safeValue = Math.min( maxLimit, Math.max( normalizedMin, nextValue || normalizedMin ) );
		onMaxChange( safeValue );
	};

	return (
		<BaseControl label={ label } help={ help } className="k-font-size-min-max-control">
			<div className="k-font-size-min-max-row">
				<input
					type="number"
					className="k-font-size-input k-font-size-min"
					value={ normalizedMin }
					min={ minLimit }
					max={ maxLimit }
					onChange={ ( e ) => handleMinChange( parseInt( e.target.value, 10 ) ) }
					aria-label={ `${ label } minimum` }
				/>
				<div className="k-font-size-range-track">
					<RangeControl
						value={ normalizedMin }
						onChange={ handleMinChange }
						min={ minLimit }
						max={ maxLimit }
						className="k-font-size-range-min"
						withInputField={ false }
						__nextHasNoMarginBottom
					/>
					<RangeControl
						value={ normalizedMax }
						onChange={ handleMaxChange }
						min={ minLimit }
						max={ maxLimit }
						className="k-font-size-range-max"
						withInputField={ false }
						__nextHasNoMarginBottom
					/>
				</div>
				<input
					type="number"
					className="k-font-size-input k-font-size-max"
					value={ normalizedMax }
					min={ minLimit }
					max={ maxLimit }
					onChange={ ( e ) => handleMaxChange( parseInt( e.target.value, 10 ) ) }
					aria-label={ `${ label } maksimum` }
				/>
			</div>
		</BaseControl>
	);
}

function RadiusCornerInput( { label, value, onChange, cornerClass } ) {
	return (
		<div className={ `k-radius-cell ${ cornerClass }` }>
			<span className="k-radius-label">{ label }</span>
			<div className="k-radius-input-wrap">
				<input
					type="text"
					value={ value }
					onChange={ ( event ) => onChange( event.target.value ) }
					className="k-radius-input"
				/>
				<span className="k-radius-corner-mark" aria-hidden="true"></span>
			</div>
		</div>
	);
}

function RadiusCornersControl( { values, onChange } ) {
	return (
		<BaseControl label="Kantlinjeradius">
			<div className="k-radius-grid">
				<RadiusCornerInput
					label="TOPP"
					value={ values.top }
					onChange={ ( next ) => onChange( { ...values, top: next } ) }
					cornerClass="k-corner-top-left"
				/>
				<RadiusCornerInput
					label="HØYRE"
					value={ values.right }
					onChange={ ( next ) => onChange( { ...values, right: next } ) }
					cornerClass="k-corner-top-right"
				/>
				<RadiusCornerInput
					label="BUNN"
					value={ values.bottom }
					onChange={ ( next ) => onChange( { ...values, bottom: next } ) }
					cornerClass="k-corner-bottom-right"
				/>
				<RadiusCornerInput
					label="VENSTRE"
					value={ values.left }
					onChange={ ( next ) => onChange( { ...values, left: next } ) }
					cornerClass="k-corner-bottom-left"
				/>
			</div>
		</BaseControl>
	);
}

function SpacingDiagram() {
	return (
		<div className="k-spacing-diagram" aria-hidden="true">
			<div className="k-spacing-wrapper">
				<span className="k-spacing-label k-spacing-label-wrapper">Wrapper padding</span>
				<div className="k-spacing-card">
					<span className="k-spacing-label k-spacing-label-margin">Kort margin</span>
					<div className="k-spacing-card-inner">
						<span className="k-spacing-label k-spacing-label-padding">Kort padding</span>
						<div className="k-spacing-content"></div>
					</div>
				</div>
			</div>
		</div>
	);
}

function BorderStylesControl( { attributes, setAttributes, colors } ) {
	return (
		<div className="k-border-styles-panel k-panel-border">
			<h4 className="k-border-styles-title">Kantstil</h4>
			<TabPanel className="k-border-state-tabs k-responsive-tabs" tabs={ BORDER_STATE_TABS }>
				{ ( tab ) => {
					const isHover = tab.name === 'hover';
					const widthValue = isHover ? attributes.imageBorderWidthHover : attributes.imageBorderWidth;
					const styleValue = isHover ? attributes.imageBorderStyleHover : attributes.imageBorderStyle;
					const colorValue = isHover ? attributes.imageBorderColorHover : attributes.imageBorderColor;

					return (
						<div className="k-border-state-content">
							<TextControl
								label="Rammetykkelse"
								value={ widthValue }
								onChange={ ( value ) =>
									setAttributes(
										isHover
											? { imageBorderWidthHover: value }
											: { imageBorderWidth: value }
									)
								}
							/>
							<SelectControl
								label="Rammestil"
								value={ styleValue }
								options={ IMAGE_BORDER_STYLES }
								onChange={ ( value ) =>
									setAttributes(
										isHover
											? { imageBorderStyleHover: value }
											: { imageBorderStyle: value }
									)
								}
							/>
							<ColorSettingRow
								label="Rammefarge"
								value={ colorValue || '' }
								colors={ colors }
								onChange={ ( value ) =>
									setAttributes(
										isHover
											? { imageBorderColorHover: value || '' }
											: { imageBorderColor: value || '' }
									)
								}
							/>
						</div>
					);
				} }
			</TabPanel>
		</div>
	);
}

function ImageShapePresetPicker( { attributes, setAttributes } ) {
	const presets = [
		{ key: 'rounded', label: 'Avrundet', aspect: null, radius: '8px' },
		{ key: 'rounded-large', label: 'Mer avrundet', aspect: null, radius: '20px' },
		{ key: 'round', label: 'Rund', aspect: '1/1', radius: '100%' },
		{ key: 'square', label: 'Firkantet', aspect: null, radius: '0px' },
	];

	const currentRadiusSignature = `${ attributes.imageRadiusTop }|${ attributes.imageRadiusRight }|${ attributes.imageRadiusBottom }|${ attributes.imageRadiusLeft }`;
	const activePreset = presets.find( ( preset ) => {
		const expected = `${ preset.radius }|${ preset.radius }|${ preset.radius }|${ preset.radius }`;
		if ( preset.key === 'round' ) {
			return currentRadiusSignature === expected && attributes.imageAspect === '1/1';
		}
		return currentRadiusSignature === expected;
	} )?.key;

	const [ isCustomOpen, setIsCustomOpen ] = useState( false );
	const showCustomControls = isCustomOpen || ! activePreset;
	const label = (
		<span className="k-option-label">
			<span>Bildeformer</span>
			<button
				type="button"
				className="k-option-custom-link"
				onClick={ () => setIsCustomOpen( ( previous ) => ! previous ) }
			>
				Egendefinert
			</button>
		</span>
	);

	return (
		<BaseControl label={ label }>
			<div className="k-image-shape-preset-grid">
				{ presets.map( ( preset ) => (
					<button
						key={ preset.key }
						type="button"
						className={ `k-image-shape-preset ${ activePreset === preset.key ? 'is-active' : '' }` }
						onClick={ () => {
							const next = {
								imageRadiusTop: preset.radius,
								imageRadiusRight: preset.radius,
								imageRadiusBottom: preset.radius,
								imageRadiusLeft: preset.radius,
								imageRadius: preset.radius,
							};
							if ( preset.aspect ) {
								next.imageAspect = preset.aspect;
							}
							setIsCustomOpen( false );
							setAttributes( next );
						} }
					>
						<span className={ `k-image-shape-preview k-shape-${ preset.key }` } aria-hidden="true"></span>
						<span className="k-image-shape-label">{ preset.label }</span>
					</button>
				) ) }
			</div>
			{ showCustomControls && (
				<>
					<RadiusCornersControl
						values={ {
							top: attributes.imageRadiusTop,
							right: attributes.imageRadiusRight,
							bottom: attributes.imageRadiusBottom,
							left: attributes.imageRadiusLeft,
						} }
						onChange={ ( next ) =>
							setAttributes( {
								imageRadiusTop: next.top,
								imageRadiusRight: next.right,
								imageRadiusBottom: next.bottom,
								imageRadiusLeft: next.left,
							} )
						}
					/>
				</>
			) }
		</BaseControl>
	);
}

function ImageSizePresetPicker( { attributes, setAttributes } ) {
	const presets = [
		{ key: 'small', label: 'Liten', value: '80px', previewClass: 'k-size-small' },
		{ key: 'medium', label: 'Medium', value: '120px', previewClass: 'k-size-medium' },
		{ key: 'large', label: 'Stor', value: '240px', previewClass: 'k-size-large' },
		{ key: 'xlarge', label: 'Ekstra stor', value: '400px', previewClass: 'k-size-xlarge' },
	];

	const activePreset = presets.find( ( preset ) => preset.value === attributes.imageSize )?.key;
	const [ isCustomOpen, setIsCustomOpen ] = useState( false );
	const showCustomInput = isCustomOpen || ! activePreset;
	const label = (
		<span className="k-option-label">
			<span>Bildestørrelse</span>
			<button
				type="button"
				className="k-option-custom-link"
				onClick={ () => setIsCustomOpen( ( previous ) => ! previous ) }
			>
				Egendefinert
			</button>
		</span>
	);

	return (
		<BaseControl label={ label }>
			<div className="k-image-shape-preset-grid">
				{ presets.map( ( preset ) => (
					<button
						key={ preset.key }
						type="button"
						className={ `k-image-shape-preset ${ activePreset === preset.key ? 'is-active' : '' }` }
						onClick={ () => {
							setIsCustomOpen( false );
							setAttributes( { imageSize: preset.value } );
						} }
					>
						<span className={ `k-image-shape-preview ${ preset.previewClass }` } aria-hidden="true"></span>
						<span className="k-image-shape-label">{ preset.label }</span>
					</button>
				) ) }
			</div>
			{ showCustomInput && (
				<TextControl
					label="Egendefinert størrelse (px)"
					value={ ( attributes.imageSize || '' ).replace( 'px', '' ) }
					onChange={ ( value ) => {
						const numericValue = value.replace( /[^0-9]/g, '' );
						if ( ! numericValue ) {
							setAttributes( { imageSize: '' } );
							return;
						}
						setAttributes( { imageSize: `${ numericValue }px` } );
					} }
				/>
			) }
		</BaseControl>
	);
}

function ColorSettingRow( { label, value, onChange, colors } ) {
	return (
		<Dropdown
			className="k-color-setting-dropdown"
			contentClassName="k-color-setting-popover"
			popoverProps={ { placement: 'left', flip: false } }
			renderToggle={ ( { isOpen, onToggle } ) => (
				<button
					type="button"
					className={ `k-color-setting-row ${ isOpen ? 'is-open' : '' }` }
					onClick={ onToggle }
					aria-expanded={ isOpen }
				>
					<ColorIndicator colorValue={ value || 'transparent' } />
					<span className="k-color-setting-label">{ label }</span>
				</button>
			) }
			renderContent={ () => (
				<ColorPalette
					value={ value || '' }
					onChange={ ( next ) => onChange( next || '' ) }
					colors={ colors }
					clearable
					disableCustomColors={ false }
				/>
			) }
		/>
	);
}

function getAspectControlValue( imageAspect ) {
	const knownValues = IMAGE_ASPECT_OPTIONS
		.map( ( item ) => item.value )
		.filter( ( value ) => value !== 'custom' );

	if ( knownValues.includes( imageAspect ) ) {
		return imageAspect;
	}

	return 'custom';
}

function TermChecklistDropdown( {
	label,
	items,
	selectedValues,
	onToggleValue,
	buttonLabel = 'Velg elementer',
	clearLabel = 'Tøm',
	onClearSelection,
	emptyText = 'Ingen tilgjengelige valg.',
} ) {
	const selectedCount = Array.isArray( selectedValues ) ? selectedValues.length : 0;
	return (
		<BaseControl label={ label }>
			<div style={ { display: 'flex', alignItems: 'center', gap: '8px' } }>
				<Dropdown
					contentClassName="k-term-checklist-dropdown"
					popoverProps={ { placement: 'bottom-start', flip: true } }
					renderToggle={ ( { onToggle, isOpen } ) => (
						<Button variant="secondary" onClick={ onToggle } aria-expanded={ isOpen }>
							{ selectedCount > 0 ? `${ buttonLabel } (${ selectedCount })` : buttonLabel }
						</Button>
					) }
					renderContent={ () => (
						<div style={ { minWidth: 260, maxHeight: 280, overflowY: 'auto', padding: '8px 10px' } }>
							{ items.length === 0 && <p style={ { margin: 0 } }>{ emptyText }</p> }
							{ items.map( ( item ) => (
								<CheckboxControl
									key={ item.value }
									label={ item.label }
									checked={ selectedValues.includes( item.value ) }
									onChange={ () => onToggleValue( item.value ) }
								/>
							) ) }
						</div>
					) }
				/>
				{ selectedCount > 0 && typeof onClearSelection === 'function' && (
					<Button variant="tertiary" onClick={ onClearSelection }>
						{ clearLabel }
					</Button>
				) }
			</div>
		</BaseControl>
	);
}

registerBlockType( metadata.name, {
	...metadata,
	icon: taxonomyGridBlockIcon,
	edit( { attributes, setAttributes } ) {
		const blockProps = useBlockProps();
		const [ themePalette = [], defaultPalette = [] ] = useSettings( 'color.palette.theme', 'color.palette.default' );
		const availableColors = [ ...( themePalette || [] ), ...( defaultPalette || [] ) ];
		const resolvedColors = availableColors.length > 0 ? availableColors : COLORS;
		const isCategorySource = attributes.sourceType === 'category';
		const isLocationSource = attributes.sourceType === 'location';
		const isInstructorSource = attributes.sourceType === 'instructor';
		const categoryTerms = useSelect(
			( select ) =>
				select( 'core' ).getEntityRecords( 'taxonomy', 'ka_coursecategory', {
					per_page: 100,
					hide_empty: false,
					orderby: 'name',
					order: 'asc',
				} ) || [],
			[]
		);
		const locationTerms = useSelect(
			( select ) =>
				select( 'core' ).getEntityRecords( 'taxonomy', 'ka_course_location', {
					per_page: 100,
					hide_empty: false,
					orderby: 'name',
					order: 'asc',
				} ) || [],
			[]
		);
		const instructorTerms = useSelect(
			( select ) =>
				select( 'core' ).getEntityRecords( 'taxonomy', 'ka_instructors', {
					per_page: 100,
					hide_empty: false,
					orderby: 'name',
					order: 'asc',
				} ) || [],
			[]
		);
		const locationChecklistItems = locationTerms
			.filter( ( term ) => Number( term?.count || 0 ) > 0 )
			.map( ( term ) => ( {
				label: term?.name || '',
				value: term?.slug || '',
			} ) )
			.filter( ( item ) => item.value );
		const instructorChecklistItems = instructorTerms.map( ( term ) => ( {
			label: term?.name || '',
			value: term?.slug || '',
		} ) ).filter( ( item ) => item.value );
		const categoryParentItems = categoryTerms
			.filter( ( term ) => Number( term?.parent || 0 ) === 0 )
			.map( ( term ) => ( {
				label: term?.name || '',
				value: term?.slug || '',
			} ) )
			.filter( ( item ) => item.value );
		const categoryLocationFilterOptions = [
			{ label: 'Alle steder', value: '' },
			...locationChecklistItems.flatMap( ( item ) => [
				{ label: `Vis kun ${ item.label }`, value: item.value },
				{ label: `Skjul ${ item.label }`, value: `ikke-${ item.value }` },
			] ),
		];
		const isBackgroundPreset = attributes.stylePreset === 'kort-bakgrunn' || attributes.stylePreset === 'kort-bakgrunnsfarge';
		const isKortBakgrunnStyle = attributes.stylePreset === 'kort-bakgrunn';
		const isKortBakgrunnFargeStyle = attributes.stylePreset === 'kort-bakgrunnsfarge';
		const isRadExtendedPreset = attributes.stylePreset === 'rad-detalj';
		const imageSizeEnabledPresets = [
			'stablet-standard',
			'stablet-kort-innfelt',
			'stablet-kort-overlapp',
			'rad-standard',
			'rad-kort',
			'rad-detalj',
		];
		const imageAspectEnabledPresets = [
			'stablet-standard',
			'stablet-kort',
			'stablet-kort-innfelt',
			'stablet-kort-overlapp',
			'rad-standard',
			'rad-kort',
			'rad-detalj',
			'kort-bakgrunn',
			'kort-bakgrunnsfarge',
		];
		const showImageSizeQuickControls = imageSizeEnabledPresets.includes( attributes.stylePreset );
		const showImageAspectControl = imageAspectEnabledPresets.includes( attributes.stylePreset );
		const imageShapeEnabledPresets = [
			'stablet-standard',
			'stablet-kort-innfelt',
			'stablet-kort-overlapp',
			'rad-standard',
			'rad-detalj',
		];
		const textAlignEnabledPresets = [
			'stablet-standard',
			'stablet-kort',
			'stablet-kort-innfelt',
			'stablet-kort-overlapp',
			'kort-bakgrunn',
			'kort-bakgrunnsfarge',
		];
		const showImageShapeControl = imageShapeEnabledPresets.includes( attributes.stylePreset );
		const showLayoutTextAlignControl = textAlignEnabledPresets.includes( attributes.stylePreset );
		const showLayoutImageAspectControl = showImageAspectControl && ( attributes.showImage || isKortBakgrunnFargeStyle );
		const showLayoutColumnsControl = attributes.stylePreset !== 'liste-enkel' && !isRadExtendedPreset;
		const imageAspectControlValue = getAspectControlValue( attributes.imageAspect );
		const [ activeSettingsTab, setActiveSettingsTab ] = useState( 'general' );
		const [ elementCardAutoOpen, setElementCardAutoOpen ] = useState( false );
		const [ showInlineBorderSettings, setShowInlineBorderSettings ] = useState( false );
		const [ showSourceInfoModal, setShowSourceInfoModal ] = useState( false );
		const [ previewAttributes, setPreviewAttributes ] = useState( attributes );
		const { content: serverRenderedContent, status: serverRenderStatus } = useServerSideRender( {
			block: metadata.name,
			attributes: previewAttributes,
		} );
		const SETTINGS_TABS = [
			{ name: 'general', title: 'Generelt' },
			{ name: 'adjustments', title: 'Justeringer' },
		];

		useEffect( () => {
			const timeoutId = setTimeout( () => {
				setPreviewAttributes( attributes );
			}, 180 );

			return () => clearTimeout( timeoutId );
		}, [ attributes ] );

		const applyPreset = ( presetKey ) => {
			const preset = PRESETS.find( ( item ) => item.key === presetKey );
			if ( ! preset ) {
				return;
			}

			const preserved = {
				sourceType: attributes.sourceType,
				filterMode: attributes.filterMode,
				categoryImageSource: attributes.categoryImageSource,
				region: attributes.region,
				locationInclude: attributes.locationInclude,
				locationShowInfo: attributes.locationShowInfo,
				instructorExclude: attributes.instructorExclude,
				categoryParentSlug: attributes.categoryParentSlug,
				categoryParentSlugs: attributes.categoryParentSlugs,
				categoryLocationFilter: attributes.categoryLocationFilter,
				instructorNameMode: attributes.instructorNameMode,
				instructorImageSource: attributes.instructorImageSource,
				showInstructorPhone: attributes.showInstructorPhone,
				showInstructorEmail: attributes.showInstructorEmail,
			};

			setAttributes( {
				...DEFAULT_ATTRIBUTES,
				...preserved,
				stylePreset: presetKey,
				...preset.defaults,
			} );
		};

		const getDeviceFields = ( device ) => {
			if ( device === 'desktop' ) {
				return {
					columns: attributes.columnsDesktop,
				};
			}
			if ( device === 'tablet' ) {
				return {
					columns: attributes.columnsTablet,
				};
			}
			return {
				columns: attributes.columnsMobile,
			};
		};

		const setDeviceColumns = ( device, value ) => {
			if ( device === 'desktop' ) {
				setAttributes( { columnsDesktop: value || 1 } );
				return;
			}
			if ( device === 'tablet' ) {
				setAttributes( { columnsTablet: value || 1 } );
				return;
			}
			setAttributes( { columnsMobile: value || 1 } );
		};

		const setDeviceTextAlign = ( device, value ) => {
			if ( device === 'desktop' ) {
				setAttributes( { textAlignDesktop: value } );
				return;
			}
			if ( device === 'tablet' ) {
				setAttributes( { textAlignTablet: value } );
				return;
			}
			setAttributes( { textAlignMobile: value } );
		};

		const getDeviceVerticalAlign = ( device ) => {
			if ( device === 'desktop' ) {
				return attributes.verticalAlignDesktop;
			}
			if ( device === 'tablet' ) {
				return attributes.verticalAlignTablet;
			}
			return attributes.verticalAlignMobile;
		};

		const setDeviceVerticalAlign = ( device, value ) => {
			if ( device === 'desktop' ) {
				setAttributes( { verticalAlignDesktop: value } );
				return;
			}
			if ( device === 'tablet' ) {
				setAttributes( { verticalAlignTablet: value } );
				return;
			}
			setAttributes( { verticalAlignMobile: value } );
		};

		const getSpacingFields = ( device ) => {
			if ( device === 'desktop' ) {
				return {
					wrapperPadding: attributes.wrapperPaddingDesktop,
					cardPadding: attributes.cardPaddingDesktop,
					cardMargin: attributes.cardMarginDesktop,
					rowGap: attributes.rowGapDesktop,
				};
			}
			if ( device === 'tablet' ) {
				return {
					wrapperPadding: attributes.wrapperPaddingTablet,
					cardPadding: attributes.cardPaddingTablet,
					cardMargin: attributes.cardMarginTablet,
					rowGap: attributes.rowGapTablet,
				};
			}
			return {
				wrapperPadding: attributes.wrapperPaddingMobile,
				cardPadding: attributes.cardPaddingMobile,
				cardMargin: attributes.cardMarginMobile,
				rowGap: attributes.rowGapMobile,
			};
		};

		const setSpacingField = ( device, field, value ) => {
			const keyMap = {
				desktop: {
					wrapperPadding: 'wrapperPaddingDesktop',
					cardPadding: 'cardPaddingDesktop',
					cardMargin: 'cardMarginDesktop',
					rowGap: 'rowGapDesktop',
				},
				tablet: {
					wrapperPadding: 'wrapperPaddingTablet',
					cardPadding: 'cardPaddingTablet',
					cardMargin: 'cardMarginTablet',
					rowGap: 'rowGapTablet',
				},
				mobile: {
					wrapperPadding: 'wrapperPaddingMobile',
					cardPadding: 'cardPaddingMobile',
					cardMargin: 'cardMarginMobile',
					rowGap: 'rowGapMobile',
				},
			};

			const attributeKey = keyMap[ device ]?.[ field ];
			if ( attributeKey ) {
				setAttributes( { [ attributeKey ]: value } );
			}
		};

		return (
			<div { ...blockProps }>
				<InspectorControls>
					<div className="k-settings-help-text k-source-info-link-wrapper">
						<button
							type="button"
							className="k-option-custom-link"
							onClick={ () => setShowSourceInfoModal( true ) }
						>
							mer informasjon
						</button>
					</div>
					<TabPanel
						key={ activeSettingsTab }
						className="k-settings-tabs"
						tabs={ SETTINGS_TABS }
						initialTabName={ activeSettingsTab }
						onSelect={ ( tabName ) => setActiveSettingsTab( tabName ) }
					>
						{ ( tab ) => (
							<>
								{ tab.name === 'general' && (
									<>
										<PanelBody title="Datakilde" initialOpen={ false }>
											<SelectControl
												label="Kildetype"
												value={ attributes.sourceType }
												options={ SOURCE_OPTIONS }
												onChange={ ( value ) => setAttributes( { sourceType: value } ) }
											/>
											{ isCategorySource && (
												<>
													<SelectControl
														label="Velg kategorinivå"
														value={ attributes.filterMode }
														options={ FILTER_OPTIONS }
														onChange={ ( value ) => setAttributes( { filterMode: value } ) }
													/>
													{ attributes.filterMode === 'standard' && (
														<TermChecklistDropdown
															label="Foreldrekategori (valgfritt)"
															items={ categoryParentItems }
															selectedValues={ attributes.categoryParentSlugs || [] }
															buttonLabel="Velg foreldrekategori"
															onToggleValue={ ( value ) => {
																const selected = Array.isArray( attributes.categoryParentSlugs ) ? attributes.categoryParentSlugs : [];
																const next = selected.includes( value )
																	? selected.filter( ( item ) => item !== value )
																	: [ ...selected, value ];
																setAttributes( { categoryParentSlugs: next, categoryParentSlug: '' } );
															} }
															onClearSelection={ () => setAttributes( { categoryParentSlugs: [], categoryParentSlug: '' } ) }
															emptyText="Fant ingen foreldrekategorier."
														/>
													) }
													<SelectControl
														label="Stedsfilter (st)"
														help="Vis eller skjul kategorier knyttet til valgt sted."
														value={ attributes.categoryLocationFilter || '' }
														options={ categoryLocationFilterOptions }
														onChange={ ( value ) => setAttributes( { categoryLocationFilter: value } ) }
													/>
													{ attributes.showImage && (
														<SelectControl
															label="Velg bildetype"
															value={ attributes.categoryImageSource || 'main' }
															options={ CATEGORY_IMAGE_SOURCE_OPTIONS }
															onChange={ ( value ) => setAttributes( { categoryImageSource: value } ) }
														/>
													) }
												</>
											) }
											{ isLocationSource && (
												<>
													<SelectControl
														label="Region"
														help={ REGIONS_ENABLED
															? "Velg region for filtrering. Kan kombineres med stedvalg ('x eller y'-logikk)."
															: 'Region er deaktivert i Synkronisering → Regioner.'
														}
														value={ attributes.region || '' }
														options={ REGION_OPTIONS }
														onChange={ ( value ) => setAttributes( { region: value } ) }
														disabled={ ! REGIONS_ENABLED }
													/>
													<TermChecklistDropdown
														label="Vis kun følgende steder"
														items={ locationChecklistItems }
														selectedValues={ attributes.locationInclude || [] }
														buttonLabel="Velg steder"
														onToggleValue={ ( value ) => {
															const selected = Array.isArray( attributes.locationInclude ) ? attributes.locationInclude : [];
															const next = selected.includes( value )
																? selected.filter( ( item ) => item !== value )
																: [ ...selected, value ];
															setAttributes( { locationInclude: next } );
														} }
														onClearSelection={ () => setAttributes( { locationInclude: [] } ) }
														emptyText="Fant ingen kurssteder."
													/>
													<ToggleControl
														label="Vis stedinfo"
														help="Stedsbeskrivelser fra feltet 'Sted' i Kursagenten."
														checked={ !! attributes.locationShowInfo }
														onChange={ ( value ) => setAttributes( { locationShowInfo: value } ) }
													/>
												</>
											) }
											{ isInstructorSource && (
												<>
													<SelectControl
														label="Vis instruktørnavn som"
														value={ attributes.instructorNameMode }
														options={ NAME_MODE_OPTIONS }
														onChange={ ( value ) => setAttributes( { instructorNameMode: value } ) }
													/>
													{ attributes.showImage && (
														<SelectControl
															label="Velg bildetype"
															value={ attributes.instructorImageSource || 'standard' }
															options={ INSTRUCTOR_IMAGE_SOURCE_OPTIONS }
															onChange={ ( value ) => setAttributes( { instructorImageSource: value } ) }
														/>
													) }
													<ToggleControl
														label="Vis telefon"
														checked={ !! attributes.showInstructorPhone }
														onChange={ ( value ) => setAttributes( { showInstructorPhone: value } ) }
													/>
													<ToggleControl
														label="Vis e-post"
														checked={ !! attributes.showInstructorEmail }
														onChange={ ( value ) => setAttributes( { showInstructorEmail: value } ) }
													/>
													<TermChecklistDropdown
														label="Skjul instruktører"
														items={ instructorChecklistItems }
														selectedValues={ attributes.instructorExclude || [] }
														onToggleValue={ ( value ) => {
															const selected = Array.isArray( attributes.instructorExclude ) ? attributes.instructorExclude : [];
															const next = selected.includes( value )
																? selected.filter( ( item ) => item !== value )
																: [ ...selected, value ];
															setAttributes( { instructorExclude: next } );
														} }
														emptyText="Fant ingen instruktører."
													/>
												</>
											) }
											<ToggleControl
												label="Vis bilde"
												checked={ attributes.showImage }
												onChange={ ( value ) => setAttributes( { showImage: value } ) }
											/>
											<ToggleControl
												label="Vis beskrivelse"
												checked={ attributes.showDescription }
												onChange={ ( value ) => setAttributes( { showDescription: value } ) }
											/>
										</PanelBody>

										<PanelBody title="Layout og stil" initialOpen={ true }>
											<PresetPicker attributes={ attributes } onSelectPreset={ applyPreset } />
											<ToggleControl
												label="Bruk card-design"
												checked={ attributes.useCardDesign }
												onChange={ ( value ) => {
													if ( value && attributes.shadowPreset === 'none' ) {
														setAttributes( { useCardDesign: value, shadowPreset: 'xsoft' } );
														return;
													}
													setAttributes( { useCardDesign: value } );
												} }
											/>
											{ attributes.showDescription && !isRadExtendedPreset && (
												<RangeControl
													className="k-description-limit-control"
													label="Maks ord i beskrivelse"
													help="0 = ingen begrensning"
													value={ attributes.descriptionWordLimit }
													onChange={ ( value ) => setAttributes( { descriptionWordLimit: value ?? 22 } ) }
													min={ 0 }
													max={ 120 }
												/>
											) }
											{ isRadExtendedPreset && attributes.showDescription && (
												<RangeControl
													className="k-description-limit-control"
													label="Maks ord i utvidet beskrivelse"
													help="0 = ingen begrensning"
													value={ attributes.descriptionWordLimitExtended }
													onChange={ ( value ) => setAttributes( { descriptionWordLimitExtended: value ?? 0 } ) }
													min={ 0 }
													max={ 200 }
												/>
											) }
											{ attributes.useCardDesign && (
												<SelectControl
													label="Skygge/kantstil"
													value={ attributes.shadowPreset }
													options={ SHADOW_OPTIONS }
													onChange={ ( value ) => setAttributes( { shadowPreset: value } ) }
												/>
											) }
											{ attributes.useCardDesign && attributes.shadowPreset === 'outline' && (
												<>
													<div className="k-inline-link-row">
														<a
															href="#"
															className="k-option-custom-link"
															onClick={ ( event ) => {
																event.preventDefault();
																setShowInlineBorderSettings( ( previous ) => ! previous );
															} }
														>
															Rammeinnstillinger
														</a>
														<button
															type="button"
															className="k-inline-muted-link"
															onClick={ () => {
																setElementCardAutoOpen( true );
																setActiveSettingsTab( 'adjustments' );
															} }
														>
															Rediger i Element-kort
														</button>
													</div>
													{ showInlineBorderSettings && (
														<div className="k-panel-border">
															<TextControl
																label="Rammetykkelse"
																value={ attributes.cardBorderWidth }
																onChange={ ( value ) => setAttributes( { cardBorderWidth: value } ) }
															/>
															<SelectControl
																label="Rammestil"
																value={ attributes.cardBorderStyle }
																options={ IMAGE_BORDER_STYLES }
																onChange={ ( value ) => setAttributes( { cardBorderStyle: value } ) }
															/>
															<ColorSettingRow
																label="Rammefarge"
																value={ attributes.cardBorderColor || '' }
																colors={ resolvedColors }
																onChange={ ( value ) => setAttributes( { cardBorderColor: value || '' } ) }
															/>
														</div>
													) }
												</>
											) }
											{ showLayoutImageAspectControl && (
												<SelectControl
													label="Bildeformat"
													value={ imageAspectControlValue }
													options={ IMAGE_ASPECT_OPTIONS }
													onChange={ ( value ) => {
														if ( value === 'custom' ) {
															if ( imageAspectControlValue !== 'custom' ) {
																setAttributes( { imageAspect: '' } );
															}
															return;
														}
														setAttributes( { imageAspect: value } );
													} }
												/>
											) }
											{ showLayoutImageAspectControl && imageAspectControlValue === 'custom' && (
												<TextControl
													label="Egendefinert format"
													help="Eks: 5/4, 7/5"
													value={ attributes.imageAspect }
													onChange={ ( value ) => setAttributes( { imageAspect: value } ) }
												/>
											) }
											{ attributes.showImage && showImageSizeQuickControls && (
												<ImageSizePresetPicker attributes={ attributes } setAttributes={ setAttributes } />
											) }
											{ attributes.showImage && showImageShapeControl && (
												<ImageShapePresetPicker attributes={ attributes } setAttributes={ setAttributes } />
											) }
											{ isBackgroundPreset && !isKortBakgrunnStyle && !isKortBakgrunnFargeStyle && (
												<SelectControl
													label="Bakgrunnskilde"
													value={ attributes.backgroundMode }
													options={ [
														{ label: 'Bakgrunnsfarge', value: 'color' },
														{ label: 'Taksonomibilde', value: 'taxonomyImage' },
													] }
													onChange={ ( value ) => setAttributes( { backgroundMode: value } ) }
												/>
											) }
											{ isBackgroundPreset && (
												<>
													{ isKortBakgrunnStyle && (
														<RangeControl
															label="Mørk overlay (%)"
															value={ attributes.overlayStrength }
															onChange={ ( value ) => setAttributes( { overlayStrength: value || 0 } ) }
															min={ 0 }
															max={ 85 }
														/>
													) }

													{ isKortBakgrunnFargeStyle && (
														<BaseControl label="Farger">
															<ColorSettingRow
																label="Tekst"
																value={ attributes.textColor || '' }
																onChange={ ( value ) => setAttributes( { textColor: value } ) }
																colors={ resolvedColors }
															/>
															<ColorSettingRow
																label="Bakgrunn"
																value={ attributes.cardBackgroundColor || '' }
																onChange={ ( value ) => setAttributes( { cardBackgroundColor: value } ) }
																colors={ resolvedColors }
															/>
													<ColorSettingRow
														label="Bakgrunn hover"
														value={ attributes.cardBackgroundColorHover || '' }
														onChange={ ( value ) => setAttributes( { cardBackgroundColorHover: value } ) }
														colors={ resolvedColors }
													/>
														</BaseControl>
													) }
												</>
											) }
											{ showLayoutTextAlignControl && (
												<AlignmentButtons
													label="Element-justering"
													horizontalValue={ attributes.textAlignDesktop }
													verticalValue={ attributes.verticalAlignDesktop }
													onHorizontalChange={ ( value ) =>
														setAttributes( {
															textAlignDesktop: value,
															textAlignTablet: value,
															textAlignMobile: value,
														} )
													}
													onVerticalChange={ ( value ) =>
														setAttributes( {
															verticalAlignDesktop: value,
															verticalAlignTablet: value,
															verticalAlignMobile: value,
														} )
													}
												/>
											) }
											{ showLayoutColumnsControl && (
												<div className="k-text-align-panel k-panel-border">
													<h4 className="k-text-align-title">Kolonner</h4>
													<TabPanel className="k-responsive-tabs" tabs={ RESPONSIVE_TABS } onSelect={ syncEditorViewportToDevice }>
														{ ( innerTab ) => {
															const fields = getDeviceFields( innerTab.name );
															const maxColumns = innerTab.name === 'desktop' ? 6 : innerTab.name === 'tablet' ? 4 : 2;

															return (
																<div className="k-responsive-tab-content">
																	<RangeControl
																		label="Kolonner"
																		value={ fields.columns }
																		onChange={ ( value ) => setDeviceColumns( innerTab.name, value ) }
																		min={ 1 }
																		max={ maxColumns }
																	/>
																</div>
															);
														} }
													</TabPanel>
												</div>
											) }
										</PanelBody>
									</>
								) }

								{ tab.name === 'adjustments' && (
									<>
										<PanelBody title="Element-kort" initialOpen={ elementCardAutoOpen }>
											<div className="k-text-align-panel k-panel-border">
												<h4 className="k-text-align-title">Element-justering</h4>
												<TabPanel className="k-text-align-tabs k-responsive-tabs" tabs={ RESPONSIVE_TABS } onSelect={ syncEditorViewportToDevice }>
													{ ( innerTab ) => {
														const horizontalValue =
															innerTab.name === 'desktop'
																? attributes.textAlignDesktop
																: innerTab.name === 'tablet'
																	? attributes.textAlignTablet
																	: attributes.textAlignMobile;

														return (
															<AlignmentButtons
																label={ `Element-justering ${ innerTab.title.toLowerCase() }` }
																horizontalValue={ horizontalValue }
																verticalValue={ getDeviceVerticalAlign( innerTab.name ) }
																onHorizontalChange={ ( next ) => setDeviceTextAlign( innerTab.name, next ) }
																onVerticalChange={ ( next ) => setDeviceVerticalAlign( innerTab.name, next ) }
															/>
														);
													} }
												</TabPanel>
											</div>
											<BaseControl label="Farger">
												<ColorSettingRow
													label="Bakgrunn"
													value={ attributes.cardBackgroundColor || '' }
													colors={ resolvedColors }
													onChange={ ( value ) => setAttributes( { cardBackgroundColor: value || '' } ) }
												/>
												<ColorSettingRow
													label="Bakgrunn hover"
													value={ attributes.cardBackgroundColorHover || '' }
													colors={ resolvedColors }
													onChange={ ( value ) => setAttributes( { cardBackgroundColorHover: value || '' } ) }
												/>
											</BaseControl>
											<TextControl
												label="Kantlinjeradius"
												value={ attributes.cardRadius }
												onChange={ ( value ) => setAttributes( { cardRadius: value } ) }
											/>
											<div className="k-settings-divider" aria-hidden="true"></div>
											<p className="k-settings-help-text"><strong>Ramme:</strong> Brukes når Skygge/kantstil = Ingen, men bruk ramme.</p>
											<TextControl
												label="Rammetykkelse"
												value={ attributes.cardBorderWidth }
												onChange={ ( value ) => setAttributes( { cardBorderWidth: value } ) }
											/>
											<SelectControl
												label="Rammestil"
												value={ attributes.cardBorderStyle }
												options={ IMAGE_BORDER_STYLES }
												onChange={ ( value ) => setAttributes( { cardBorderStyle: value } ) }
											/>
											<ColorSettingRow
												label="Rammefarge"
												value={ attributes.cardBorderColor || '' }
												colors={ resolvedColors }
												onChange={ ( value ) => setAttributes( { cardBorderColor: value || '' } ) }
											/>
										</PanelBody>

										<PanelBody title="Kolonner" initialOpen={ false }>
											<TabPanel className="k-responsive-tabs" tabs={ RESPONSIVE_TABS } onSelect={ syncEditorViewportToDevice }>
												{ ( innerTab ) => {
													const fields = getDeviceFields( innerTab.name );
													const maxColumns = innerTab.name === 'desktop' ? 6 : innerTab.name === 'tablet' ? 4 : 2;

													return (
														<div className="k-responsive-tab-content">
															<RangeControl
																label="Kolonner"
																value={ fields.columns }
																onChange={ ( value ) => setDeviceColumns( innerTab.name, value ) }
																min={ 1 }
																max={ maxColumns }
															/>
														</div>
													);
												} }
											</TabPanel>
										</PanelBody>

										<PanelBody title="Spacing" initialOpen={ false }>
											<TabPanel className="k-responsive-tabs" tabs={ RESPONSIVE_TABS } onSelect={ syncEditorViewportToDevice }>
												{ ( innerTab ) => {
													const fields = getSpacingFields( innerTab.name );
													return (
														<div className="k-responsive-tab-content">
															<TextControl
																label="Wrapper padding"
																value={ fields.wrapperPadding }
																onChange={ ( value ) => setSpacingField( innerTab.name, 'wrapperPadding', value ) }
															/>
															<TextControl
																label="Kort padding"
																value={ fields.cardPadding }
																onChange={ ( value ) => setSpacingField( innerTab.name, 'cardPadding', value ) }
															/>
															<TextControl
																label="Kort margin"
																value={ fields.cardMargin }
																onChange={ ( value ) => setSpacingField( innerTab.name, 'cardMargin', value ) }
															/>
															<TextControl
																label="Radavstand"
																value={ fields.rowGap }
																onChange={ ( value ) => setSpacingField( innerTab.name, 'rowGap', value ) }
															/>
														</div>
													);
												} }
											</TabPanel>
											<SpacingDiagram />
										</PanelBody>

										{ attributes.showImage && (
											<PanelBody title="Bilde" initialOpen={ false }>
												<ImageShapePresetPicker attributes={ attributes } setAttributes={ setAttributes } />
												<BorderStylesControl attributes={ attributes } setAttributes={ setAttributes } colors={ resolvedColors } />
												<BaseControl label="Bakgrunnsfarge bak bilde">
													<ColorSettingRow
														label="Bakgrunn"
														value={ attributes.imageBackgroundColor || '' }
														colors={ resolvedColors }
														onChange={ ( value ) => setAttributes( { imageBackgroundColor: value || '' } ) }
													/>
												</BaseControl>
											</PanelBody>
										) }

										<PanelBody title="Tekst" initialOpen={ false }>
											<div className="k-text-align-panel k-panel-border">
												<h4 className="k-text-align-title">Element-justering</h4>
												<TabPanel className="k-text-align-tabs k-responsive-tabs" tabs={ RESPONSIVE_TABS } onSelect={ syncEditorViewportToDevice }>
													{ ( innerTab ) => {
														const horizontalValue =
															innerTab.name === 'desktop'
																? attributes.textAlignDesktop
																: innerTab.name === 'tablet'
																	? attributes.textAlignTablet
																	: attributes.textAlignMobile;

														return (
															<AlignmentButtons
																label={ `Element-justering ${ innerTab.title.toLowerCase() }` }
																horizontalValue={ horizontalValue }
																verticalValue={ getDeviceVerticalAlign( innerTab.name ) }
																onHorizontalChange={ ( next ) => setDeviceTextAlign( innerTab.name, next ) }
																onVerticalChange={ ( next ) => setDeviceVerticalAlign( innerTab.name, next ) }
															/>
														);
													} }
												</TabPanel>
											</div>
											<p className="k-settings-help-text">Minste font (mobil) til største font (stor desktop). Gir en gradvis økning i fontstørrelse.</p>
											<FontSizeMinMaxControl
												label="Tittel minst til størst (px)"
												help="Fontstørrelse for tittel"
												minValue={ attributes.fontMin ?? 15 }
												maxValue={ attributes.fontMax ?? 20 }
												minLimit={ 11 }
												maxLimit={ 36 }
												onMinChange={ ( value ) => setAttributes( { fontMin: value || 11 } ) }
												onMaxChange={ ( value ) => setAttributes( { fontMax: value || attributes.fontMin } ) }
											/>
											{ ( attributes.showDescription || attributes.stylePreset === 'rad-detalj' ) && (
												<FontSizeMinMaxControl
													label="Tekst minst til størst (px)"
													help="Fontstørrelse for beskrivelse"
													minValue={ attributes.descriptionFontMin ?? 12 }
													maxValue={ attributes.descriptionFontMax ?? 16 }
													minLimit={ 10 }
													maxLimit={ 28 }
													onMinChange={ ( value ) => setAttributes( { descriptionFontMin: value || 10 } ) }
													onMaxChange={ ( value ) => setAttributes( { descriptionFontMax: value || attributes.descriptionFontMin } ) }
												/>
											) }
											<BaseControl label="Farge">
												<ColorSettingRow
													label="Tittel"
													value={ attributes.titleColor || attributes.textColor || '' }
													colors={ resolvedColors }
													onChange={ ( value ) => setAttributes( { titleColor: value || '' } ) }
												/>
												{ ( attributes.showDescription || attributes.stylePreset === 'rad-detalj' ) && (
													<ColorSettingRow
														label="Tekst"
														value={ attributes.descriptionColor || attributes.textColor || '' }
														colors={ resolvedColors }
														onChange={ ( value ) => setAttributes( { descriptionColor: value || '' } ) }
													/>
												) }
											</BaseControl>
											<FontWeightButtons
												label="Fontvekt tittel"
												value={ attributes.fontWeightTitle || '600' }
												onChange={ ( value ) => setAttributes( { fontWeightTitle: value } ) }
											/>
											{ ( attributes.showDescription || attributes.stylePreset === 'rad-detalj' ) && (
												<FontWeightButtons
													label="Fontvekt tekst"
													value={ attributes.fontWeightDescription || '400' }
													onChange={ ( value ) => setAttributes( { fontWeightDescription: value } ) }
												/>
											) }
											<SelectControl
												label="Tittel-element"
												help="Behold H3 som standard med mindre du har en tydelig semantisk grunn til å endre."
												value={ attributes.titleTag }
												options={ TITLE_TAG_OPTIONS }
												onChange={ ( value ) => setAttributes( { titleTag: value } ) }
											/>
										</PanelBody>
									</>
								) }
							</>
						) }
					</TabPanel>
					{ showSourceInfoModal && (
						<Modal
							title="Mer informasjon om dataflyt fra Kursagenten"
							onRequestClose={ () => setShowSourceInfoModal( false ) }
						>
							<div className="k-source-info-modal-content">
								<p>
									Det meste av data skal styres fra Kursagenten. Det er enkelte deler vi ikke har
									felter for, og for disse delene kan du berike kategori/sted/instruktør direkte
									her på nettsiden via Admin → Kursagenten → Kurssteder/Instruktører/Kurssteder
								</p>

								<h2>Bilder</h2>
								<p>
									God skikk for opplasting av bilder er å ha en maks størrelse på 500kb. I
									overføring fra Kursagenten er det en maksgrense for overføring på 1MB.
								</p>
								<p><strong>Plassholderbilde</strong><br />
									Det er mulig å legge inn plassholderbilder under Admin → Kursagenten → Kursdesign.
								</p>

								<h2>Enkeltkurs</h2>
								<p><strong>Tekst</strong><br />
									Tekst blir hentet fra introtekst og kursbeskrivelse i Kursagenten. Det er
									mulig å legge inn egen tekst i tillegg her på websiden. Naviger til kurset
									som innlogget admin, og klikk deretter på «Legg til ekstra Wordpress innhold»
									mellom introtekst og kursbeskrivelse.
								</p>
								<p><strong>Bilde</strong><br />
									I Kursagenten kan det legges inn bilder på hvert enkelt kurs. Om ikke bilde
									har blitt lastet opp, kan det brukes et plassholderbilde.
								</p>

								<h2>Kurskategorier</h2>
								<p><strong>Tekst</strong><br />
									Kategorinavnet blir hentet fra taggene i Kursagenten. Disse kan du strukturere
									i ønsket hierarki her på nettsiden, i maks to nivåer. Hvis du ønsker å endre
									kategorinavnet, bør du endre taggen i Kursagenten. Merk: en ny kurskategori blir
									opprettet, men den gamle blir ikke slettet. Overfør eventuell tekst og bilder til den nye kategorien.
								</p>
								<p>
									<strong>Kategoritekst</strong>: her har vi ingen felter i Kursagenten, og alt må legges
									inn på nettsiden. Du har mulighet til å legge inn både Kort beskrivelse og Lang
									beskrivelse. Dette dukker opp på de enkelte kurskategori-sidene på nettsiden.
								</p>
								<p><strong>Bilde</strong><br />
									Du kan laste opp bilder for hver kategori. Det er også mulig å laste opp et
									«profilbilde». Dette kan brukes i blokker/kortkoder, som et alternativ til hovedbildet.
									Har det ikke blitt lastet opp et kategoribilde, vil den prøve å bruke et bilde fra et
									tilknyttet kurs. Om dette ikke finnes, vil den bruke plassholderbilde.
								</p>

								<h2>Instruktører</h2>
								<p><strong>Tekst</strong><br />
									Navn, telefonnummer og epost blir overført fra Kursagenten. Dette kan overstyres
									på nettsiden om ønskelig.
								</p>
								<p><strong>Bilde</strong><br />
									Bilde hentes fra instruktørprofil i Kursagenten. Dette bildet kan overstyres her inne på
									nettsiden. Det er også mulig å bruke et alternativt bilde om du ønsker en annen stil/annet
									bilde enn det som er brukt i Kursagenten.
								</p>

								<h2>Kurssteder</h2>
								<p><strong>Tekst</strong><br />
									Stedsnavn hentes fra Kursagenten. Det er mulig å endre stedsnavnet. For at dette
									skal bli korrekt i alle titler og url-er, bør dette gjøres under Admin → Kursagenten → Synkronisering.
									Endre navn, og hent deretter alle kurs på nytt. Da vil alle forekomster av stedsnavn blir vist korrekt.
								</p>
								<p>
									<strong>Stedstekst</strong>: her har vi ingen felter i Kursagenten, og alt må legges inn
									på nettsiden. Du har mulighet til å legge inn både Kort beskrivelse og Lang beskrivelse.
									Dette dukker opp på de enkelte kurssted-sidene på nettsiden.
								</p>
								<p><strong>Bilde</strong><br />
									Om du velger å vise bilde, vil det først bli sett etter hovedbilde fra kurssted. Hvis det ikke blir funnet, brukes plassholderbilde.
								</p>
							</div>
						</Modal>
					) }
				</InspectorControls>

				<div className="k-ssr-preview">
					{ serverRenderStatus === 'loading' && <Spinner /> }
					{ serverRenderedContent && <RawHTML>{ serverRenderedContent }</RawHTML> }
				</div>
			</div>
		);
	},
	save() {
		return null;
	},
} );

registerBlockVariation( metadata.name, {
	name: 'kurskategorier',
	title: 'Kurskategorier',
	description: 'Vis kurskategorier med preset-stiler',
	attributes: {
		sourceType: 'category',
		stylePreset: 'stablet-standard',
		...getPresetDefaults( 'stablet-standard' ),
	},
	isDefault: true,
} );

registerBlockVariation( metadata.name, {
	name: 'kurssteder',
	title: 'Kurssteder',
	description: 'Vis kurssteder med preset-stiler',
	attributes: {
		sourceType: 'location',
		stylePreset: 'stablet-kort-overlapp',
		...getPresetDefaults( 'stablet-kort-overlapp' ),
	},
} );

registerBlockVariation( metadata.name, {
	name: 'instruktorer',
	title: 'Instruktører',
	description: 'Vis instruktører med preset-stiler',
	attributes: {
		sourceType: 'instructor',
		stylePreset: 'stablet-kort-overlapp',
		...getPresetDefaults( 'stablet-kort-overlapp' ),
		imageBorderWidth: '1px',
		imageBorderStyle: 'solid',
		imageBorderColor: '#eeeeee',
	},
} );

registerBlockVariation( metadata.name, {
	name: 'preset-stiler',
	title: 'Preset-stiler',
	description: 'Start direkte med visuelle stilvalg',
	attributes: {
		sourceType: 'category',
		stylePreset: 'kort-bakgrunn',
		...getPresetDefaults( 'kort-bakgrunn' ),
	},
} );

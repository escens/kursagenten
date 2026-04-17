import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps, useSettings } from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	ToggleControl,
	TextControl,
	BaseControl,
	ColorPalette,
	Dropdown,
	ColorIndicator,
	Spinner,
} from '@wordpress/components';
import { useServerSideRender } from '@wordpress/server-side-render';
import { RawHTML, useEffect, useMemo, useState } from '@wordpress/element';

import iconUrl from '../../../public/blocks/shared/icon.svg';

import titleMetadata from '../../../public/blocks/single-title/block.json';
import courseLinkMetadata from '../../../public/blocks/single-course-link/block.json';
import signupButtonMetadata from '../../../public/blocks/single-signup-button/block.json';
import scheduleListMetadata from '../../../public/blocks/single-schedule-list/block.json';
import nextCourseMetadata from '../../../public/blocks/single-next-course-info/block.json';
import kaContentMetadata from '../../../public/blocks/single-ka-content/block.json';
import contactMetadata from '../../../public/blocks/single-contact/block.json';
import relatedCoursesMetadata from '../../../public/blocks/single-related-courses/block.json';

const blockIcon = (
	<img src={ iconUrl } alt="" style={ { width: 24, height: 24, display: 'block' } } />
);

const FALLBACK_COLORS = [
	{ color: '#ffffff', name: 'Hvit' },
	{ color: '#d0d7de', name: 'Lys grå' },
	{ color: '#111827', name: 'Mørk' },
	{ color: '#2271b1', name: 'Blå' },
	{ color: '#14532d', name: 'Grønn' },
	{ color: '#7f1d1d', name: 'Rød' },
];

function ColorRow( { label, value, onChange, colors } ) {
	return (
		<Dropdown
			className="ka-color-setting-dropdown"
			popoverProps={ { placement: 'left', flip: false } }
			renderToggle={ ( { onToggle, isOpen } ) => (
				<button
					type="button"
					className={ `ka-color-setting-row ${ isOpen ? 'is-open' : '' }` }
					onClick={ onToggle }
					aria-expanded={ isOpen }
				>
					<ColorIndicator colorValue={ value || 'transparent' } />
					<span className="ka-color-setting-label">{ label }</span>
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

function CommonTypographyControls( { attributes, setAttributes, colors } ) {
	return (
		<>
			<PanelBody title="Stil: Typografi" initialOpen={ false }>
				<TextControl
					label="Fontfamilie (tekst)"
					help="Tom = bruk tema/Kursdesign."
					value={ attributes.fontFamily || '' }
					onChange={ ( value ) => setAttributes( { fontFamily: value } ) }
				/>
				<TextControl
					label="Fontfamilie (overskrifter)"
					help="Tom = bruk tema/Kursdesign."
					value={ attributes.headingFontFamily || '' }
					onChange={ ( value ) => setAttributes( { headingFontFamily: value } ) }
				/>
				<TextControl
					label="Fontstørrelse (tekst)"
					help='Eks: "16px", "1rem", "clamp(16px, 2vw, 20px)". Tom = standard.'
					value={ attributes.fontSize || '' }
					onChange={ ( value ) => setAttributes( { fontSize: value } ) }
				/>
				<TextControl
					label="Fontstørrelse (overskrift)"
					help='Eks: "32px", "2rem", "clamp(28px, 3vw, 44px)". Tom = standard.'
					value={ attributes.headingSize || '' }
					onChange={ ( value ) => setAttributes( { headingSize: value } ) }
				/>
			</PanelBody>
			<PanelBody title="Stil: Farger" initialOpen={ false }>
				<ColorRow
					label="Tekst"
					value={ attributes.textColor || '' }
					onChange={ ( value ) => setAttributes( { textColor: value } ) }
					colors={ colors }
				/>
				<ColorRow
					label="Overskrift"
					value={ attributes.headingColor || '' }
					onChange={ ( value ) => setAttributes( { headingColor: value } ) }
					colors={ colors }
				/>
				<ColorRow
					label="Lenker"
					value={ attributes.linkColor || '' }
					onChange={ ( value ) => setAttributes( { linkColor: value } ) }
					colors={ colors }
				/>
				<ColorRow
					label="Ikoner"
					value={ attributes.iconColor || '' }
					onChange={ ( value ) => setAttributes( { iconColor: value } ) }
					colors={ colors }
				/>
			</PanelBody>
		</>
	);
}

function SsrPreview( { blockName, attributes } ) {
	const [ previewAttributes, setPreviewAttributes ] = useState( attributes );
	const { content, status } = useServerSideRender( { block: blockName, attributes: previewAttributes } );

	useEffect( () => {
		const t = setTimeout( () => setPreviewAttributes( attributes ), 150 );
		return () => clearTimeout( t );
	}, [ attributes ] );

	return (
		<div className="ka-ssr-preview">
			{ status === 'loading' && <Spinner /> }
			{ content && <RawHTML>{ content }</RawHTML> }
		</div>
	);
}

function makeEdit( metadata, renderControls ) {
	return function Edit( { attributes, setAttributes } ) {
		const blockProps = useBlockProps();
		const [ themePalette = [], defaultPalette = [] ] = useSettings(
			'color.palette.theme',
			'color.palette.default'
		);
		const colors = useMemo( () => {
			const available = [ ...( themePalette || [] ), ...( defaultPalette || [] ) ];
			return available.length ? available : FALLBACK_COLORS;
		}, [ themePalette, defaultPalette ] );

		return (
			<div { ...blockProps }>
				<InspectorControls>
					{ renderControls( { attributes, setAttributes, colors } ) }
				</InspectorControls>
				<SsrPreview blockName={ metadata.name } attributes={ attributes } />
			</div>
		);
	};
}

function registerSingleBlock( metadata, controlsRenderer ) {
	registerBlockType( metadata.name, {
		...metadata,
		icon: blockIcon,
		edit: makeEdit( metadata, controlsRenderer ),
		save() {
			return null;
		},
	} );
}

registerSingleBlock( titleMetadata, ( { attributes, setAttributes, colors } ) => (
	<>
		<PanelBody title="Innhold" initialOpen={ true }>
			<SelectControl
				label="Overskrift-element"
				value={ attributes.headingTag || 'h1' }
				options={ [
					{ label: 'H1', value: 'h1' },
					{ label: 'H2', value: 'h2' },
					{ label: 'H3', value: 'h3' },
				] }
				onChange={ ( value ) => setAttributes( { headingTag: value } ) }
			/>
			<SelectControl
				label="Vis sted"
				value={ attributes.showLocation || 'auto' }
				options={ [
					{ label: 'Auto', value: 'auto' },
					{ label: 'Ja', value: 'yes' },
					{ label: 'Nei', value: 'no' },
				] }
				onChange={ ( value ) => setAttributes( { showLocation: value } ) }
			/>
			<SelectControl
				label="Layout"
				value={ attributes.layout || 'stacked' }
				options={ [
					{ label: 'Stablet', value: 'stacked' },
					{ label: 'På linje', value: 'inline' },
				] }
				onChange={ ( value ) => setAttributes( { layout: value } ) }
			/>
		</PanelBody>
		<PanelBody title="Stil: Tittel og sted" initialOpen={ false }>
			<TextControl
				label="Fontstørrelse (sted)"
				help='Eks: "18px", "1.1rem". Tom = standard.'
				value={ attributes.locationSize || '' }
				onChange={ ( value ) => setAttributes( { locationSize: value } ) }
			/>
			<ColorRow
				label="Farge (sted)"
				value={ attributes.locationColor || '' }
				onChange={ ( value ) => setAttributes( { locationColor: value } ) }
				colors={ colors }
			/>
		</PanelBody>
		<CommonTypographyControls attributes={ attributes } setAttributes={ setAttributes } colors={ colors } />
	</>
) );

registerSingleBlock( courseLinkMetadata, ( { attributes, setAttributes, colors } ) => (
	<>
		<PanelBody title="Innhold" initialOpen={ true }>
			<TextControl
				label="Tekst"
				value={ attributes.label || '' }
				onChange={ ( value ) => setAttributes( { label: value } ) }
				placeholder="Alle kurs"
			/>
			<ToggleControl
				label="Vis ikon"
				checked={ !! attributes.showIcon }
				onChange={ ( value ) => setAttributes( { showIcon: value } ) }
			/>
		</PanelBody>
		<CommonTypographyControls attributes={ attributes } setAttributes={ setAttributes } colors={ colors } />
	</>
) );

registerSingleBlock( signupButtonMetadata, ( { attributes, setAttributes, colors } ) => (
	<>
		<PanelBody title="Innhold" initialOpen={ true }>
			<TextControl
				label="Fallback-tekst"
				help="Brukes hvis kursdato ikke har button_text."
				value={ attributes.fallbackText || '' }
				onChange={ ( value ) => setAttributes( { fallbackText: value } ) }
				placeholder="Påmelding"
			/>
			<SelectControl
				label="Variant"
				value={ attributes.styleVariant || 'primary' }
				options={ [
					{ label: 'Primær', value: 'primary' },
					{ label: 'Sekundær', value: 'secondary' },
					{ label: 'Lenke', value: 'link' },
				] }
				onChange={ ( value ) => setAttributes( { styleVariant: value } ) }
			/>
			<ToggleControl
				label="Full bredde"
				checked={ !! attributes.fullWidth }
				onChange={ ( value ) => setAttributes( { fullWidth: value } ) }
			/>
		</PanelBody>
		<PanelBody title="Stil: Knapp" initialOpen={ false }>
			<SelectControl
				label="Kildestil"
				value={ attributes.buttonStyleSource || 'theme' }
				options={ [
					{ label: 'Tema (standard)', value: 'theme' },
					{ label: 'Overstyr (Kursagenten)', value: 'override' },
				] }
				onChange={ ( value ) => setAttributes( { buttonStyleSource: value } ) }
			/>
			{ attributes.buttonStyleSource === 'override' && (
				<>
					<ColorRow
						label="Bakgrunn"
						value={ attributes.buttonBg || '' }
						onChange={ ( value ) => setAttributes( { buttonBg: value } ) }
						colors={ colors }
					/>
					<ColorRow
						label="Tekst"
						value={ attributes.buttonColor || '' }
						onChange={ ( value ) => setAttributes( { buttonColor: value } ) }
						colors={ colors }
					/>
					<TextControl
						label="Radius"
						value={ attributes.buttonRadius || '' }
						onChange={ ( value ) => setAttributes( { buttonRadius: value } ) }
						placeholder="8px"
					/>
					<TextControl
						label="Padding"
						value={ attributes.buttonPadding || '' }
						onChange={ ( value ) => setAttributes( { buttonPadding: value } ) }
						placeholder="12px 18px"
					/>
				</>
			) }
		</PanelBody>
	</>
) );

registerSingleBlock( scheduleListMetadata, ( { attributes, setAttributes, colors } ) => (
	<>
		<PanelBody title="Innhold" initialOpen={ true }>
			<SelectControl
				label="Overskrift-element"
				value={ attributes.headingTag || 'h3' }
				options={ [
					{ label: 'H3', value: 'h3' },
					{ label: 'H4', value: 'h4' },
					{ label: 'Ingen', value: 'none' },
				] }
				onChange={ ( value ) => setAttributes( { headingTag: value } ) }
			/>
			<ToggleControl
				label="Vis lenker til kurssteder"
				checked={ !! attributes.showLocationLinks }
				onChange={ ( value ) => setAttributes( { showLocationLinks: value } ) }
			/>
		</PanelBody>
		<CommonTypographyControls attributes={ attributes } setAttributes={ setAttributes } colors={ colors } />
	</>
) );

registerSingleBlock( nextCourseMetadata, ( { attributes, setAttributes, colors } ) => (
	<>
		<PanelBody title="Innhold" initialOpen={ true }>
			<ToggleControl
				label="Vis overskrift"
				checked={ attributes.showHeading !== false }
				onChange={ ( value ) => setAttributes( { showHeading: value } ) }
			/>
			<ToggleControl
				label="Vis ikoner"
				checked={ !! attributes.showIcons }
				onChange={ ( value ) => setAttributes( { showIcons: value } ) }
			/>
			<ToggleControl
				label="Vis påmeldingslenke"
				checked={ attributes.showSignupLink !== false }
				onChange={ ( value ) => setAttributes( { showSignupLink: value } ) }
			/>
			<ToggleControl
				label="Vis pris"
				checked={ !! attributes.showPrice }
				onChange={ ( value ) => setAttributes( { showPrice: value } ) }
			/>
			<ToggleControl
				label="Vis varighet"
				checked={ !! attributes.showDuration }
				onChange={ ( value ) => setAttributes( { showDuration: value } ) }
			/>
			<ToggleControl
				label="Vis språk"
				checked={ !! attributes.showLanguage }
				onChange={ ( value ) => setAttributes( { showLanguage: value } ) }
			/>
		</PanelBody>
		<CommonTypographyControls attributes={ attributes } setAttributes={ setAttributes } colors={ colors } />
	</>
) );

registerSingleBlock( kaContentMetadata, ( { attributes, setAttributes, colors } ) => (
	<CommonTypographyControls attributes={ attributes } setAttributes={ setAttributes } colors={ colors } />
) );

registerSingleBlock( contactMetadata, ( { attributes, setAttributes, colors } ) => (
	<>
		<PanelBody title="Innhold" initialOpen={ true }>
			<ToggleControl
				label="Vis tittel"
				checked={ attributes.showTitle !== false }
				onChange={ ( value ) => setAttributes( { showTitle: value } ) }
			/>
			<ToggleControl
				label="Vis wrapper/boks"
				checked={ attributes.showWrapper !== false }
				onChange={ ( value ) => setAttributes( { showWrapper: value } ) }
			/>
			<ToggleControl
				label="Skjul hvis tomt"
				checked={ attributes.hideIfEmpty !== false }
				onChange={ ( value ) => setAttributes( { hideIfEmpty: value } ) }
			/>
		</PanelBody>
		<CommonTypographyControls attributes={ attributes } setAttributes={ setAttributes } colors={ colors } />
	</>
) );

registerSingleBlock( relatedCoursesMetadata, ( { attributes, setAttributes, colors } ) => (
	<>
		<PanelBody title="Innhold" initialOpen={ true }>
			<SelectControl
				label="Layout"
				value={ attributes.layout || 'list' }
				options={ [
					{ label: 'Liste', value: 'list' },
					{ label: 'Grid', value: 'grid' },
					{ label: 'Kort', value: 'cards' },
				] }
				onChange={ ( value ) => setAttributes( { layout: value } ) }
			/>
			<TextControl
				label="Kolonner"
				value={ String( attributes.columns ?? 3 ) }
				onChange={ ( value ) => setAttributes( { columns: parseInt( value || '3', 10 ) } ) }
			/>
			<TextControl
				label="Antall"
				value={ String( attributes.limit ?? 6 ) }
				onChange={ ( value ) => setAttributes( { limit: parseInt( value || '6', 10 ) } ) }
			/>
			<ToggleControl
				label="Vis bilde"
				checked={ attributes.showImage !== false }
				onChange={ ( value ) => setAttributes( { showImage: value } ) }
			/>
		</PanelBody>
		<CommonTypographyControls attributes={ attributes } setAttributes={ setAttributes } colors={ colors } />
	</>
) );


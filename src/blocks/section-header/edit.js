/**
 * Section Header Block — Editor
 */

import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	InspectorControls,
	RichText,
} from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const { kicker, title, lead, alignment, numberPosition, theme } =
		attributes;

	const showKickerBefore = !! kicker && numberPosition === 'before';
	const showKickerAfter = !! kicker && numberPosition === 'after';
	const hasKicker = showKickerBefore || showKickerAfter;

	const blockProps = useBlockProps( {
		className: [
			'section-header',
			`section-header--${ theme }`,
			`is-aligned-${ alignment }`,
			hasKicker ? 'has-kicker' : '',
			showKickerAfter ? 'has-kicker--after' : '',
		]
			.filter( Boolean )
			.join( ' ' ),
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Layout', 'goodblocks' ) }>
					<SelectControl
						label={ __( 'Alignment', 'goodblocks' ) }
						value={ alignment }
						options={ [
							{
								label: __( 'Left', 'goodblocks' ),
								value: 'left',
							},
							{
								label: __( 'Center', 'goodblocks' ),
								value: 'center',
							},
						] }
						onChange={ ( v ) => setAttributes( { alignment: v } ) }
					/>
					<SelectControl
						label={ __( 'Kicker position', 'goodblocks' ) }
						value={ numberPosition }
						options={ [
							{
								label: __( 'None (hide kicker)', 'goodblocks' ),
								value: 'none',
							},
							{
								label: __( 'Before title', 'goodblocks' ),
								value: 'before',
							},
							{
								label: __( 'After title', 'goodblocks' ),
								value: 'after',
							},
						] }
						onChange={ ( v ) =>
							setAttributes( { numberPosition: v } )
						}
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'Style', 'goodblocks' ) }
					initialOpen={ false }
				>
					<SelectControl
						label={ __( 'Theme', 'goodblocks' ) }
						value={ theme }
						options={ [
							{
								label: __( 'Light', 'goodblocks' ),
								value: 'light',
							},
							{
								label: __( 'Dark', 'goodblocks' ),
								value: 'dark',
							},
							{
								label: __( 'Accent', 'goodblocks' ),
								value: 'accent',
							},
						] }
						onChange={ ( v ) => setAttributes( { theme: v } ) }
					/>
				</PanelBody>
			</InspectorControls>

			<section { ...blockProps }>
				<div className="section-header__inner">
					{ showKickerBefore && (
						<RichText
							tagName="span"
							className="section-header__kicker"
							value={ kicker }
							onChange={ ( v ) => setAttributes( { kicker: v } ) }
							placeholder={ __(
								'Kicker / number…',
								'goodblocks'
							) }
							allowedFormats={ [] }
						/>
					) }

					<RichText
						tagName="h2"
						className="section-header__title"
						value={ title }
						onChange={ ( v ) => setAttributes( { title: v } ) }
						placeholder={ __( 'Section title…', 'goodblocks' ) }
					/>

					{ showKickerAfter && (
						<RichText
							tagName="span"
							className="section-header__kicker section-header__kicker--after"
							value={ kicker }
							onChange={ ( v ) => setAttributes( { kicker: v } ) }
							placeholder={ __(
								'Kicker / number…',
								'goodblocks'
							) }
							allowedFormats={ [] }
						/>
					) }

					{ numberPosition === 'none' && ! kicker && (
						<RichText
							tagName="span"
							className="section-header__kicker section-header__kicker--placeholder"
							value={ kicker }
							onChange={ ( v ) => setAttributes( { kicker: v } ) }
							placeholder={ __(
								'Optional kicker (set position in sidebar)…',
								'goodblocks'
							) }
							allowedFormats={ [] }
						/>
					) }

					<RichText
						tagName="p"
						className="section-header__lead"
						value={ lead }
						onChange={ ( v ) => setAttributes( { lead: v } ) }
						placeholder={ __(
							'Optional lead paragraph…',
							'goodblocks'
						) }
					/>
				</div>
			</section>
		</>
	);
}

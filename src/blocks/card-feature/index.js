/**
 * Feature Card Block
 */

import { registerBlockType } from '@wordpress/blocks';
import {
	InnerBlocks,
	useBlockProps,
	InspectorControls,
	MediaUpload,
	MediaUploadCheck,
} from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	ToggleControl,
	Button,
	TextControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { link as linkIcon } from '@wordpress/icons';

import metadata from './block.json';
import './style.scss';
import './editor.scss';

const ALLOWED_BLOCKS = [ 'core/heading', 'core/paragraph', 'core/buttons' ];

const TEMPLATE = [
	[
		'core/heading',
		{
			level: 3,
			placeholder: 'Korttitel...',
			fontSize: 'lg',
		},
	],
	[
		'core/paragraph',
		{
			placeholder: 'Kort beskrivning...',
			fontSize: 'sm',
			textColor: 'text-muted',
		},
	],
];

const Edit = ( { attributes, setAttributes } ) => {
	const {
		layout,
		mediaUrl,
		mediaId,
		imageRatio,
		hoverEffect,
		linkUrl,
		linkTarget,
		showMeta,
		metaText,
		animation,
	} = attributes;

	const blockProps = useBlockProps( {
		className: `card-feature card-feature--${ layout } card-feature--hover-${ hoverEffect } card-feature--ratio-${ imageRatio }`,
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Layout', 'goodblocks' ) }>
					<SelectControl
						label={ __( 'Kortlayout', 'goodblocks' ) }
						value={ layout }
						options={ [
							{
								label: 'Vertikal (bild ovanför)',
								value: 'vertical',
							},
							{
								label: 'Horisontell (bild vänster)',
								value: 'horizontal',
							},
							{
								label: 'Overlay (text över bild)',
								value: 'overlay',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { layout: value } )
						}
					/>
					<SelectControl
						label={ __( 'Bildformat', 'goodblocks' ) }
						value={ imageRatio }
						options={ [
							{ label: '16:9 (Landskap)', value: '16-9' },
							{ label: '4:3', value: '4-3' },
							{ label: '1:1 (Kvadrat)', value: '1-1' },
							{ label: '2:3 (Porträtt)', value: '2-3' },
							{ label: '3:4', value: '3-4' },
						] }
						onChange={ ( value ) =>
							setAttributes( { imageRatio: value } )
						}
					/>
				</PanelBody>

				<PanelBody title={ __( 'Bild', 'goodblocks' ) }>
					<MediaUploadCheck>
						<MediaUpload
							onSelect={ ( media ) =>
								setAttributes( {
									mediaUrl: media.url,
									mediaId: media.id,
								} )
							}
							allowedTypes={ [ 'image' ] }
							value={ mediaId }
							render={ ( { open } ) => (
								<div>
									{ mediaUrl ? (
										<>
											<img
												src={ mediaUrl }
												alt=""
												style={ {
													width: '100%',
													marginBottom: '8px',
												} }
											/>
											<Button
												isSecondary
												onClick={ open }
												style={ { marginRight: '8px' } }
											>
												{ __(
													'Byt bild',
													'goodblocks'
												) }
											</Button>
											<Button
												isDestructive
												onClick={ () =>
													setAttributes( {
														mediaUrl: '',
														mediaId: undefined,
													} )
												}
											>
												{ __(
													'Ta bort',
													'goodblocks'
												) }
											</Button>
										</>
									) : (
										<Button isPrimary onClick={ open }>
											{ __( 'Välj bild', 'goodblocks' ) }
										</Button>
									) }
								</div>
							) }
						/>
					</MediaUploadCheck>
				</PanelBody>

				<PanelBody title={ __( 'Länk', 'goodblocks' ) }>
					<TextControl
						label={ __( 'URL', 'goodblocks' ) }
						value={ linkUrl }
						onChange={ ( value ) =>
							setAttributes( { linkUrl: value } )
						}
						placeholder="https://"
					/>
					<SelectControl
						label={ __( 'Öppna i', 'goodblocks' ) }
						value={ linkTarget }
						options={ [
							{ label: 'Samma fönster', value: '_self' },
							{ label: 'Nytt fönster', value: '_blank' },
						] }
						onChange={ ( value ) =>
							setAttributes( { linkTarget: value } )
						}
					/>
				</PanelBody>

				<PanelBody title={ __( 'Effekter', 'goodblocks' ) }>
					<SelectControl
						label={ __( 'Hover-effekt', 'goodblocks' ) }
						value={ hoverEffect }
						options={ [
							{ label: 'Ingen', value: 'none' },
							{ label: 'Lyft (shadow)', value: 'lift' },
							{ label: 'Skala', value: 'scale' },
							{ label: 'Bild-zoom', value: 'zoom' },
							{ label: 'Lyft + Zoom', value: 'lift-zoom' },
						] }
						onChange={ ( value ) =>
							setAttributes( { hoverEffect: value } )
						}
					/>
					<SelectControl
						label={ __( 'Entrance-animation', 'goodblocks' ) }
						value={ animation }
						options={ [
							{ label: 'Ingen', value: 'none' },
							{ label: 'Fade Up', value: 'fade-up' },
							{ label: 'Fade In', value: 'fade-in' },
							{ label: 'Scale In', value: 'scale-in' },
						] }
						onChange={ ( value ) =>
							setAttributes( { animation: value } )
						}
					/>
				</PanelBody>

				<PanelBody title={ __( 'Metadata', 'goodblocks' ) }>
					<ToggleControl
						label={ __( 'Visa metadata', 'goodblocks' ) }
						checked={ showMeta }
						onChange={ ( value ) =>
							setAttributes( { showMeta: value } )
						}
					/>
					{ showMeta && (
						<TextControl
							label={ __( 'Metadata-text', 'goodblocks' ) }
							value={ metaText }
							onChange={ ( value ) =>
								setAttributes( { metaText: value } )
							}
							placeholder="Kategori, datum..."
						/>
					) }
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				{ mediaUrl && (
					<div className="card-feature__image-wrapper">
						<img
							className="card-feature__image"
							src={ mediaUrl }
							alt=""
						/>
					</div>
				) }
				<div className="card-feature__content">
					{ showMeta && metaText && (
						<span className="card-feature__meta">{ metaText }</span>
					) }
					<InnerBlocks
						allowedBlocks={ ALLOWED_BLOCKS }
						template={ TEMPLATE }
						templateLock={ false }
					/>
				</div>
				{ linkUrl && (
					<span className="card-feature__link-indicator">
						{ linkIcon }
					</span>
				) }
			</div>
		</>
	);
};

const Save = ( { attributes } ) => {
	const {
		layout,
		mediaUrl,
		imageRatio,
		hoverEffect,
		linkUrl,
		linkTarget,
		showMeta,
		metaText,
		animation,
	} = attributes;

	const blockProps = useBlockProps.save( {
		className: `card-feature card-feature--${ layout } card-feature--hover-${ hoverEffect } card-feature--ratio-${ imageRatio }`,
		'data-animate': animation !== 'none' ? animation : undefined,
	} );

	const CardWrapper = linkUrl ? 'a' : 'div';
	const wrapperProps = linkUrl
		? {
				href: linkUrl,
				target: linkTarget,
				rel:
					linkTarget === '_blank' ? 'noopener noreferrer' : undefined,
		  }
		: {};

	return (
		<CardWrapper { ...blockProps } { ...wrapperProps }>
			{ mediaUrl && (
				<div className="card-feature__image-wrapper">
					<img
						className="card-feature__image"
						src={ mediaUrl }
						alt=""
						loading="lazy"
					/>
				</div>
			) }
			<div className="card-feature__content">
				{ showMeta && metaText && (
					<span className="card-feature__meta">{ metaText }</span>
				) }
				<InnerBlocks.Content />
			</div>
		</CardWrapper>
	);
};

registerBlockType( metadata.name, {
	edit: Edit,
	save: Save,
} );

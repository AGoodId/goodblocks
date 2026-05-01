/**
 * Story Card Block — Editor
 *
 * Etapp 1: default-layout med full attribut-UI och InnerBlocks-template.
 * Andra layouts (reverse, split-*, bg-full) tillkommer i Etapp 2.
 */

import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	InspectorControls,
	RichText,
	MediaUpload,
	MediaUploadCheck,
	InnerBlocks,
} from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	TextControl,
	ToggleControl,
	Button,
} from '@wordpress/components';

const ALLOWED_BLOCKS = [
	'core/heading',
	'core/paragraph',
	'core/list',
	'core/image',
	'core/quote',
	'goodblocks/kpi-grid',
];

// Inga tema-presets (fontSize, textColor) — locked decision
const TEMPLATE = [
	[ 'core/heading', { level: 3, placeholder: 'Body heading…' } ],
	[ 'core/paragraph', { placeholder: 'Body content goes here…' } ],
];

export default function Edit( { attributes, setAttributes } ) {
	const {
		layout,
		theme,
		kicker,
		title,
		excerpt,
		mediaId,
		mediaUrl,
		mediaAlt,
		mediaType,
		actionUrl,
		actionLabel,
		actionTarget,
		labels,
		summaryLabel,
		openByDefault,
	} = attributes;

	const blockProps = useBlockProps( {
		className: [
			'story-card',
			`story-card--${ layout }`,
			`story-card--${ theme }`,
		].join( ' ' ),
	} );

	const updateLabel = ( index, value ) => {
		const next = [ ...labels ];
		next[ index ] = value;
		setAttributes( { labels: next } );
	};

	const addLabel = () => {
		setAttributes( { labels: [ ...labels, '' ] } );
	};

	const removeLabel = ( index ) => {
		setAttributes( {
			labels: labels.filter( ( _, i ) => i !== index ),
		} );
	};

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Layout', 'goodblocks' ) }>
					<SelectControl
						label={ __( 'Layout', 'goodblocks' ) }
						value={ layout }
						options={ [
							{
								label: __(
									'Default (text + media)',
									'goodblocks'
								),
								value: 'default',
							},
							{
								label: __(
									'Reverse (media + text)',
									'goodblocks'
								),
								value: 'reverse',
							},
							{
								label: __( 'Split — media left', 'goodblocks' ),
								value: 'split-left',
							},
							{
								label: __(
									'Split — media right',
									'goodblocks'
								),
								value: 'split-right',
							},
							{
								label: __( 'Background full', 'goodblocks' ),
								value: 'bg-full',
							},
						] }
						onChange={ ( v ) => setAttributes( { layout: v } ) }
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

				<PanelBody
					title={ __( 'Media', 'goodblocks' ) }
					initialOpen={ false }
				>
					<MediaUploadCheck>
						<MediaUpload
							onSelect={ ( media ) =>
								setAttributes( {
									mediaId: media.id,
									mediaUrl: media.url,
									mediaAlt: media.alt || '',
									mediaType:
										media.type === 'video'
											? 'video'
											: 'image',
								} )
							}
							allowedTypes={ [ 'image', 'video' ] }
							value={ mediaId }
							render={ ( { open } ) => (
								<div>
									{ mediaUrl && (
										<>
											{ mediaType === 'video' ? (
												<video
													src={ mediaUrl }
													style={ {
														width: '100%',
														marginBottom: '8px',
													} }
													muted
												/>
											) : (
												<img
													src={ mediaUrl }
													alt={ mediaAlt }
													style={ {
														width: '100%',
														marginBottom: '8px',
													} }
												/>
											) }
										</>
									) }
									<Button
										variant="secondary"
										onClick={ open }
									>
										{ mediaUrl
											? __(
													'Replace media',
													'goodblocks'
											  )
											: __(
													'Select media',
													'goodblocks'
											  ) }
									</Button>
									{ mediaUrl && (
										<Button
											isDestructive
											variant="link"
											onClick={ () =>
												setAttributes( {
													mediaId: 0,
													mediaUrl: '',
													mediaAlt: '',
												} )
											}
											style={ { marginLeft: '8px' } }
										>
											{ __( 'Remove', 'goodblocks' ) }
										</Button>
									) }
								</div>
							) }
						/>
					</MediaUploadCheck>
					{ mediaUrl && (
						<TextControl
							label={ __( 'Alt text', 'goodblocks' ) }
							value={ mediaAlt }
							onChange={ ( v ) =>
								setAttributes( { mediaAlt: v } )
							}
							help={ __(
								'Beskrivande alt-text. För video används som aria-label.',
								'goodblocks'
							) }
						/>
					) }
				</PanelBody>

				<PanelBody
					title={ __( 'Action', 'goodblocks' ) }
					initialOpen={ false }
				>
					<TextControl
						label={ __( 'URL', 'goodblocks' ) }
						value={ actionUrl }
						onChange={ ( v ) => setAttributes( { actionUrl: v } ) }
						placeholder="https://"
						help={ __(
							'Båda URL och text krävs för att knappen ska renderas.',
							'goodblocks'
						) }
					/>
					<TextControl
						label={ __( 'Button text', 'goodblocks' ) }
						value={ actionLabel }
						onChange={ ( v ) =>
							setAttributes( { actionLabel: v } )
						}
					/>
					<SelectControl
						label={ __( 'Open in', 'goodblocks' ) }
						value={ actionTarget }
						options={ [
							{
								label: __( 'Same window', 'goodblocks' ),
								value: '_self',
							},
							{
								label: __( 'New window', 'goodblocks' ),
								value: '_blank',
							},
						] }
						onChange={ ( v ) =>
							setAttributes( { actionTarget: v } )
						}
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'Labels', 'goodblocks' ) }
					initialOpen={ false }
				>
					<p
						style={ {
							marginTop: 0,
							fontSize: '12px',
							color: '#757575',
						} }
					>
						{ __(
							'Generic chips. Story-card äger ingen filter-state — labels är text-list, inte buttons.',
							'goodblocks'
						) }
					</p>
					{ labels.map( ( label, index ) => (
						<div
							key={ index }
							style={ {
								display: 'flex',
								gap: '8px',
								marginBottom: '8px',
								alignItems: 'flex-end',
							} }
						>
							<TextControl
								label={ `${ __( 'Label', 'goodblocks' ) } ${
									index + 1
								}` }
								value={ label }
								onChange={ ( v ) => updateLabel( index, v ) }
								style={ { flex: 1 } }
							/>
							<Button
								isDestructive
								variant="link"
								onClick={ () => removeLabel( index ) }
							>
								{ __( 'Remove', 'goodblocks' ) }
							</Button>
						</div>
					) ) }
					<Button variant="secondary" onClick={ addLabel }>
						{ __( '+ Add label', 'goodblocks' ) }
					</Button>
				</PanelBody>

				<PanelBody
					title={ __( 'Disclosure', 'goodblocks' ) }
					initialOpen={ false }
				>
					<TextControl
						label={ __( 'Summary label', 'goodblocks' ) }
						value={ summaryLabel }
						onChange={ ( v ) =>
							setAttributes( { summaryLabel: v } )
						}
						placeholder={ __( 'Read more', 'goodblocks' ) }
						help={ __(
							'Texten på "Read more"-knappen. Tom = "Read more".',
							'goodblocks'
						) }
					/>
					<ToggleControl
						label={ __( 'Open by default', 'goodblocks' ) }
						checked={ !! openByDefault }
						onChange={ ( v ) =>
							setAttributes( { openByDefault: v } )
						}
						help={ __(
							'Använd sparsamt — annars förlorar disclosure sin poäng.',
							'goodblocks'
						) }
					/>
				</PanelBody>
			</InspectorControls>

			<article { ...blockProps }>
				<div className="story-card__inner">
					<div className="story-card__text">
						<header className="story-card__header">
							<RichText
								tagName="span"
								className="story-card__kicker"
								value={ kicker }
								onChange={ ( v ) =>
									setAttributes( { kicker: v } )
								}
								placeholder={ __(
									'Optional kicker / number…',
									'goodblocks'
								) }
								allowedFormats={ [] }
							/>
							<RichText
								tagName="h3"
								className="story-card__title"
								value={ title }
								onChange={ ( v ) =>
									setAttributes( { title: v } )
								}
								placeholder={ __(
									'Story title…',
									'goodblocks'
								) }
							/>
							<RichText
								tagName="p"
								className="story-card__excerpt"
								value={ excerpt }
								onChange={ ( v ) =>
									setAttributes( { excerpt: v } )
								}
								placeholder={ __(
									'Story ingress / lead…',
									'goodblocks'
								) }
							/>
						</header>

						{ labels.length > 0 && (
							<ul className="story-card__labels">
								{ labels.map( ( label, i ) => (
									<li key={ i } className="story-card__label">
										{ label || (
											<em
												style={ {
													opacity: 0.5,
												} }
											>
												{ __(
													'Empty label',
													'goodblocks'
												) }
											</em>
										) }
									</li>
								) ) }
							</ul>
						) }

						{ actionUrl && actionLabel && (
							<div className="story-card__actions">
								<span className="story-card__action">
									{ actionLabel }
								</span>
							</div>
						) }

						{ /* Editor-preview: ingen <details> — InnerBlocks ska aldrig kunna toggleas bort under redigering. Frontend renderar med native <details>/<summary> via render.php. */ }
						<div
							className="story-card__disclosure-editor"
							aria-label={ __(
								'Disclosure body (visible in editor, collapsed by default on frontend)',
								'goodblocks'
							) }
						>
							<div
								className="story-card__summary-preview"
								aria-hidden="true"
							>
								<span className="story-card__summary-preview-icon">
									▸
								</span>
								<span className="story-card__summary-label">
									{ summaryLabel ||
										__( 'Read more', 'goodblocks' ) }
								</span>
							</div>
							<div className="story-card__body">
								<InnerBlocks
									allowedBlocks={ ALLOWED_BLOCKS }
									template={ TEMPLATE }
									templateLock={ false }
								/>
							</div>
						</div>
					</div>

					{ mediaUrl && layout !== 'bg-full' && (
						<figure className="story-card__media">
							{ mediaType === 'video' ? (
								<video
									className="story-card__media-element"
									src={ mediaUrl }
									autoPlay
									muted
									loop
									playsInline
								/>
							) : (
								<img
									className="story-card__media-element"
									src={ mediaUrl }
									alt={ mediaAlt }
								/>
							) }
						</figure>
					) }
				</div>
			</article>
		</>
	);
}

import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	RichText,
	BlockControls,
	MediaUpload,
	MediaUploadCheck,
	LinkControl,
	InspectorControls,
} from '@wordpress/block-editor';
import {
	ToolbarGroup,
	ToolbarButton,
	Popover,
	RangeControl,
	PanelBody,
	TextControl,
	SelectControl,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { link as linkIcon, image, video } from '@wordpress/icons';

import './editor.scss';

export default function Edit( { attributes, setAttributes } ) {
	const {
		title,
		text,
		backgroundMedia,
		overlayOpacity,
		type,
		imageType,
		textType,
		link: itemLink,
		gridColumn,
		gridRow,
		textPosition,
		titleSize,
	} = attributes;

	const [ isLinkPickerOpen, setIsLinkPickerOpen ] = useState( false );

	const blockProps = useBlockProps( {
		className: 'media-grid-item',
		'data-image-type': imageType || type || 'overlayOnHover',
	} );

	return (
		<>
			<BlockControls>
				<ToolbarGroup>
					<MediaUploadCheck>
						<MediaUpload
							onSelect={ ( media ) => {
								setAttributes( {
									backgroundMedia: {
										type:
											media.type === 'video'
												? 'video'
												: 'image',
										url: media.url,
										id: media.id,
									},
								} );
							} }
							allowedTypes={ [ 'image', 'video' ] }
							value={ backgroundMedia?.id }
							render={ ( { open } ) => (
								<>
									<ToolbarButton
										icon={
											backgroundMedia?.type === 'video'
												? video
												: image
										}
										onClick={ open }
										label={
											backgroundMedia?.url
												? __(
														'Change Media',
														'goodblocks'
												  )
												: __(
														'Add Media',
														'goodblocks'
												  )
										}
									/>
									{ backgroundMedia?.url && (
										<ToolbarButton
											onClick={ () =>
												setAttributes( {
													backgroundMedia: {
														type: 'image',
														url: '',
														id: null,
													},
												} )
											}
											label={ __(
												'Remove Media',
												'goodblocks'
											) }
										>
											{ __( 'Remove', 'goodblocks' ) }
										</ToolbarButton>
									) }
								</>
							) }
						/>
					</MediaUploadCheck>
					<ToolbarButton
						icon={ linkIcon }
						onClick={ () => setIsLinkPickerOpen( true ) }
						isActive={ !! itemLink }
						label={
							itemLink
								? __( 'Edit Link', 'goodblocks' )
								: __( 'Add Link', 'goodblocks' )
						}
					/>
				</ToolbarGroup>
				{ isLinkPickerOpen && (
					<Popover
						position="bottom center"
						onClose={ () => setIsLinkPickerOpen( false ) }
					>
						<LinkControl
							value={ { url: itemLink || '' } }
							onChange={ ( { url } ) =>
								setAttributes( { link: url } )
							}
							onRemove={ () => {
								setAttributes( { link: '' } );
								setIsLinkPickerOpen( false );
							} }
							forceIsEditingLink
						/>
					</Popover>
				) }
			</BlockControls>

			<InspectorControls>
				<PanelBody title={ __( 'Item settings', 'goodblocks' ) }>
					<RangeControl
						label={ __( 'Overlay Opacity', 'goodblocks' ) }
						value={ overlayOpacity }
						onChange={ ( value ) =>
							setAttributes( { overlayOpacity: value } )
						}
						min={ 0 }
						max={ 1 }
						step={ 0.1 }
					/>
					<SelectControl
						label={ __( 'Image type', 'goodblocks' ) }
						value={ imageType || type || 'none' }
						options={ [
							{
								label: __( 'Overlay on Hover', 'goodblocks' ),
								value: 'overlayOnHover',
							},
							{
								label: __( 'Image on hover', 'goodblocks' ),
								value: 'imageOnHover',
							},
							{
								label: __( 'Always Overlay', 'goodblocks' ),
								value: 'alwaysOverlay',
							},
							{
								label: __( 'None', 'goodblocks' ),
								value: 'none',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { imageType: value } )
						}
					/>
					<SelectControl
						label={ __( 'Text type', 'goodblocks' ) }
						value={ textType }
						options={ [
							{
								label: __( 'Title on Top', 'goodblocks' ),
								value: 'titleOnTop',
							},
							{
								label: __( 'Title at Bottom', 'goodblocks' ),
								value: 'titleAtBottom',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { textType: value } )
						}
					/>
					<SelectControl
						label={ __( 'Text position', 'goodblocks' ) }
						value={ textPosition }
						options={ [
							{
								label: __( 'Top', 'goodblocks' ),
								value: 'flex-start',
							},
							{
								label: __( 'Center', 'goodblocks' ),
								value: 'center',
							},
							{
								label: __( 'Bottom', 'goodblocks' ),
								value: 'flex-end',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { textPosition: value } )
						}
					/>
					<SelectControl
						label={ __( 'Title size', 'goodblocks' ) }
						value={ titleSize }
						options={ [
							{
								label: __( 'Normal', 'goodblocks' ),
								value: 'normal',
							},
							{
								label: __( 'Large', 'goodblocks' ),
								value: 'large',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { titleSize: value } )
						}
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'Custom grid position', 'goodblocks' ) }
					initialOpen={ false }
				>
					<TextControl
						label={ __(
							'Grid column (e.g. 1 / 3 or span 2)',
							'goodblocks'
						) }
						value={ gridColumn }
						onChange={ ( value ) =>
							setAttributes( { gridColumn: value } )
						}
						placeholder={ __(
							'Leave empty for auto',
							'goodblocks'
						) }
						help={ __(
							'CSS grid-column value for custom layouts',
							'goodblocks'
						) }
					/>
					<TextControl
						label={ __(
							'Grid row (e.g. 1 / 3 or span 2)',
							'goodblocks'
						) }
						value={ gridRow }
						onChange={ ( value ) =>
							setAttributes( { gridRow: value } )
						}
						placeholder={ __(
							'Leave empty for auto',
							'goodblocks'
						) }
						help={ __(
							'CSS grid-row value for custom layouts',
							'goodblocks'
						) }
					/>
				</PanelBody>
			</InspectorControls>

			<div
				{ ...blockProps }
				style={ {
					'--overlay-opacity': overlayOpacity,
					...( gridColumn && { gridColumn } ),
					...( gridRow && { gridRow } ),
				} }
			>
				{ backgroundMedia.url && (
					<div className="media-background">
						{ backgroundMedia.type === 'video' ? (
							<video
								src={ backgroundMedia.url }
								muted
								loop
								playsInline
							/>
						) : (
							<img src={ backgroundMedia.url } alt="" />
						) }
					</div>
				) }
				<div
					className="content-overlay"
					style={ { justifyContent: textPosition } }
				>
					<RichText
						tagName="h3"
						value={ title }
						onChange={ ( value ) =>
							setAttributes( { title: value } )
						}
						placeholder={ __( 'Enter title…', 'goodblocks' ) }
						className={ `item-title ${
							titleSize === 'large' ? 'h2' : ''
						}` }
						allowedFormats={ [ 'core/bold', 'core/italic' ] }
						__unstableAllowHTML
						style={ { order: textType === 'titleOnTop' ? 1 : 2 } }
					/>
					<RichText
						tagName="p"
						value={ text }
						onChange={ ( value ) =>
							setAttributes( { text: value } )
						}
						placeholder={ __( 'Enter text…', 'goodblocks' ) }
						className="item-text"
						allowedFormats={ [
							'core/bold',
							'core/italic',
							'core/link',
						] }
						__unstableAllowHTML
						style={ { order: textType === 'titleOnTop' ? 2 : 1 } }
					/>
				</div>
			</div>
		</>
	);
}

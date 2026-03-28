import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	RichText,
	BlockControls,
	InspectorControls,
	MediaUpload,
	MediaUploadCheck,
	__experimentalBlockAlignmentMatrixControl as BlockAlignmentMatrixControl,
	__experimentalLinkControl as LinkControl,
} from '@wordpress/block-editor';
import {
	ToolbarGroup,
	ToolbarButton,
	PanelBody,
	SelectControl,
	ToggleControl,
	RangeControl,
	Popover,
} from '@wordpress/components';
import { image as imageIcon, link as linkIcon, linkOff } from '@wordpress/icons';
import { useState, useRef } from '@wordpress/element';
import './editor.scss';

export default function Edit( { attributes, setAttributes, context } ) {
	const {
		mediaId,
		mediaUrl,
		mediaType,
		caption,
		heading,
		text,
		contentPosition,
		objectFit,
		showOverlay,
		overlayColor,
		overlayOpacity,
		linkUrl,
		linkTarget,
	} = attributes;
	const [ isEditingLink, setIsEditingLink ] = useState( false );
	const linkRef = useRef();
	const layout = context[ 'goodblocks/layout' ] || 'overlay';

	const onSelectMedia = ( media ) => {
		setAttributes( {
			mediaId: media.id,
			mediaUrl: media.url,
			mediaType: media.type,
			caption: media.caption || '',
		} );
	};

	const positionClass = contentPosition.replace( ' ', '-' );

	const blockProps = useBlockProps( {
		className: `slide-item position-${ positionClass } layout-${ layout }`,
		style: { '--object-fit': objectFit },
	} );

	return (
		<>
			<BlockControls>
				<ToolbarGroup>
					<MediaUploadCheck>
						<MediaUpload
							onSelect={ onSelectMedia }
							allowedTypes={ [ 'image', 'video' ] }
							value={ mediaId }
							render={ ( { open } ) => (
								<ToolbarButton
									icon={ imageIcon }
									label={ __( 'Select Media', 'goodblocks' ) }
									onClick={ open }
								/>
							) }
						/>
					</MediaUploadCheck>
				</ToolbarGroup>
				<ToolbarGroup>
					<ToolbarButton
						icon={ linkIcon }
						label={ __( 'Link', 'goodblocks' ) }
						onClick={ () => setIsEditingLink( true ) }
						isActive={ !! linkUrl }
						ref={ linkRef }
					/>
					{ linkUrl && (
						<ToolbarButton
							icon={ linkOff }
							label={ __( 'Unlink', 'goodblocks' ) }
							onClick={ () => {
								setAttributes( { linkUrl: '', linkTarget: false } );
								setIsEditingLink( false );
							} }
						/>
					) }
				</ToolbarGroup>
				{ layout === 'overlay' && (
					<ToolbarGroup>
						<BlockAlignmentMatrixControl
							label={ __( 'Change content position', 'goodblocks' ) }
							value={ contentPosition }
							onChange={ ( v ) => setAttributes( { contentPosition: v } ) }
						/>
					</ToolbarGroup>
				) }
			</BlockControls>

			{ isEditingLink && (
				<Popover
					placement="bottom"
					onClose={ () => setIsEditingLink( false ) }
					anchor={ linkRef.current }
					focusOnMount="firstElement"
				>
					<LinkControl
						value={ { url: linkUrl, opensInNewTab: linkTarget } }
						onChange={ ( v ) =>
							setAttributes( {
								linkUrl: v?.url || '',
								linkTarget: v?.opensInNewTab || false,
							} )
						}
						settings={ [
							{ id: 'opensInNewTab', title: __( 'Open in new tab', 'goodblocks' ) },
						] }
					/>
				</Popover>
			) }

			<InspectorControls>
				<PanelBody title={ __( 'Image Settings', 'goodblocks' ) }>
					<SelectControl
						label={ __( 'Object Fit', 'goodblocks' ) }
						value={ objectFit }
						options={ [
							{ label: 'Cover', value: 'cover' },
							{ label: 'Contain', value: 'contain' },
							{ label: 'Fill', value: 'fill' },
							{ label: 'None', value: 'none' },
						] }
						onChange={ ( v ) => setAttributes( { objectFit: v } ) }
					/>
				</PanelBody>
				{ layout === 'overlay' && (
					<PanelBody title={ __( 'Overlay', 'goodblocks' ) }>
						<ToggleControl
							label={ __( 'Show Overlay', 'goodblocks' ) }
							checked={ showOverlay }
							onChange={ ( v ) => setAttributes( { showOverlay: v } ) }
						/>
						{ showOverlay && (
							<>
								<div style={ { marginBottom: '16px' } }>
									<label style={ { display: 'block', marginBottom: '8px', fontWeight: '500' } }>
										{ __( 'Overlay Color', 'goodblocks' ) }
									</label>
									<input
										type="color"
										value={ overlayColor }
										onChange={ ( e ) =>
											setAttributes( { overlayColor: e.target.value } )
										}
										style={ { width: '100%', height: '40px', cursor: 'pointer' } }
									/>
								</div>
								<RangeControl
									label={ __( 'Overlay Opacity', 'goodblocks' ) }
									value={ overlayOpacity }
									onChange={ ( v ) =>
										setAttributes( { overlayOpacity: v } )
									}
									min={ 0 }
									max={ 1 }
									step={ 0.05 }
								/>
							</>
						) }
					</PanelBody>
				) }
			</InspectorControls>

			{ layout === 'below' ? (
				<figure { ...blockProps }>
					{ mediaUrl ? (
						<div className="slide-media">
							{ mediaType === 'image' ? (
								<img src={ mediaUrl } alt="" />
							) : (
								<video src={ mediaUrl } autoPlay loop muted playsInline />
							) }
						</div>
					) : (
						<div
							className="slide-media"
							style={ {
								background: '#f0f0f0',
								display: 'flex',
								alignItems: 'center',
								justifyContent: 'center',
								minHeight: '400px',
							} }
						>
							<p style={ { color: '#999' } }>
								{ __( 'Select an image or video', 'goodblocks' ) }
							</p>
						</div>
					) }
					<figcaption className="slide-caption-content">
						{ caption ? (
							<p className="slide-caption">{ caption }</p>
						) : (
							<p className="slide-caption" style={ { color: '#999' } }>
								{ __( 'Caption from media library', 'goodblocks' ) }
							</p>
						) }
					</figcaption>
				</figure>
			) : (
				<div { ...blockProps }>
					{ mediaUrl && (
						<div className="slide-media">
							{ mediaType === 'image' ? (
								<img src={ mediaUrl } alt="" />
							) : (
								<video src={ mediaUrl } autoPlay loop muted playsInline />
							) }
						</div>
					) }
					{ showOverlay && (
						<div
							className="slide-overlay"
							style={ {
								backgroundColor: overlayColor,
								opacity: overlayOpacity,
							} }
						></div>
					) }
					<div className={ `slide-content position-${ positionClass }` }>
						<RichText
							tagName="h2"
							className="slide-heading"
							value={ heading }
							onChange={ ( v ) => setAttributes( { heading: v } ) }
							placeholder={ __( 'Slide heading…', 'goodblocks' ) }
						/>
						<RichText
							tagName="p"
							className="slide-text"
							value={ text }
							onChange={ ( v ) => setAttributes( { text: v } ) }
							placeholder={ __( 'Slide text…', 'goodblocks' ) }
						/>
					</div>
				</div>
			) }
		</>
	);
}

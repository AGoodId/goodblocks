import { useBlockProps, RichText } from '@wordpress/block-editor';

export default function save( { attributes } ) {
	const {
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

	const positionClass = contentPosition.replace( ' ', '-' );

	const blockProps = useBlockProps.save( {
		className: `slide-item position-${ positionClass }`,
		'data-caption': caption || '',
		'data-object-fit': objectFit,
		'data-link-url': linkUrl || '',
		'data-link-target': linkTarget ? '_blank' : '_self',
		style: { '--object-fit': objectFit },
	} );

	const slideContent = (
		<>
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
				<RichText.Content tagName="h2" className="slide-heading" value={ heading } />
				<RichText.Content tagName="p" className="slide-text" value={ text } />
			</div>
		</>
	);

	return (
		<div { ...blockProps }>
			{ linkUrl && (
				<a
					href={ linkUrl }
					target={ linkTarget ? '_blank' : '_self' }
					rel={ linkTarget ? 'noopener noreferrer' : undefined }
					className="slide-link"
					style={ {
						position: 'absolute',
						top: 0,
						left: 0,
						width: '100%',
						height: '100%',
						zIndex: 10,
						textDecoration: 'none',
						color: 'inherit',
					} }
				>
					<span className="screen-reader-text">
						{ heading || 'Slide link' }
					</span>
				</a>
			) }
			{ slideContent }
		</div>
	);
}

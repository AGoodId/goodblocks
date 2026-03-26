/**
 * Image Compare Block - Editor
 */
import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	InspectorControls,
	MediaUpload,
	MediaUploadCheck,
} from '@wordpress/block-editor';
import {
	PanelBody,
	ToggleControl,
	TextControl,
	RangeControl,
	Button,
	Placeholder,
	SelectControl,
	FocalPointPicker,
} from '@wordpress/components';

const ICON = (
	<svg
		xmlns="http://www.w3.org/2000/svg"
		viewBox="0 0 24 24"
		width="24"
		height="24"
	>
		<path
			d="M3 3h18v18H3V3zm1 1v16h7V4H4zm9 0v16h7V4h-7zm-1 7h2v2h-2v-2z"
			fill="currentColor"
		/>
	</svg>
);

const ASPECT_RATIOS = [
	{ label: __( 'Original', 'goodblocks' ), value: 'original' },
	{ label: '16:9', value: '16:9' },
	{ label: '4:3', value: '4:3' },
	{ label: '3:2', value: '3:2' },
	{ label: '1:1', value: '1:1' },
	{ label: '2:3', value: '2:3' },
	{ label: '3:4', value: '3:4' },
	{ label: '9:16', value: '9:16' },
];

function getAspectRatioCSS( ratio ) {
	if ( ! ratio || ratio === 'original' ) {
		return undefined;
	}
	return ratio.replace( ':', ' / ' );
}

export default function Edit( { attributes, setAttributes } ) {
	const {
		beforeId,
		beforeUrl,
		beforeAlt,
		afterId,
		afterUrl,
		afterAlt,
		beforeLabel,
		afterLabel,
		showLabels,
		startPosition,
		enableTease,
		teaseSpeed,
		teaseOnce,
		orientation,
		aspectRatio,
		beforeFocalPoint,
		afterFocalPoint,
	} = attributes;

	const isVertical = orientation === 'vertical';
	const arCSS = getAspectRatioCSS( aspectRatio );
	const hasAR = !! arCSS;

	const blockProps = useBlockProps( {
		className: `image-compare-editor${ isVertical ? ' is-vertical' : '' }`,
	} );

	const onSelectBefore = ( media ) => {
		setAttributes( {
			beforeId: media.id,
			beforeUrl: media.url,
			beforeAlt: media.alt || '',
		} );
	};

	const onSelectAfter = ( media ) => {
		setAttributes( {
			afterId: media.id,
			afterUrl: media.url,
			afterAlt: media.alt || '',
		} );
	};

	const bfp = beforeFocalPoint || { x: 0.5, y: 0.5 };
	const afp = afterFocalPoint || { x: 0.5, y: 0.5 };

	const inspector = (
		<InspectorControls>
			<PanelBody title={ __( 'Image', 'goodblocks' ) }>
				<SelectControl
					label={ __( 'Aspect ratio', 'goodblocks' ) }
					value={ aspectRatio || '16:9' }
					options={ ASPECT_RATIOS }
					onChange={ ( v ) => setAttributes( { aspectRatio: v } ) }
					help={ __(
						'Controls the height of the comparison. Use "Original" to follow the image aspect ratio.',
						'goodblocks'
					) }
				/>
				{ beforeUrl && (
					<FocalPointPicker
						label={ __( 'Before image focal point', 'goodblocks' ) }
						url={ beforeUrl }
						value={ bfp }
						onChange={ ( v ) =>
							setAttributes( { beforeFocalPoint: v } )
						}
					/>
				) }
				{ afterUrl && (
					<FocalPointPicker
						label={ __( 'After image focal point', 'goodblocks' ) }
						url={ afterUrl }
						value={ afp }
						onChange={ ( v ) =>
							setAttributes( { afterFocalPoint: v } )
						}
					/>
				) }
			</PanelBody>
			<PanelBody title={ __( 'Slider', 'goodblocks' ) } initialOpen={ false }>
				<SelectControl
					label={ __( 'Orientation', 'goodblocks' ) }
					value={ orientation || 'horizontal' }
					options={ [
						{ label: __( 'Horizontal', 'goodblocks' ), value: 'horizontal' },
						{ label: __( 'Vertical', 'goodblocks' ), value: 'vertical' },
					] }
					onChange={ ( v ) => setAttributes( { orientation: v } ) }
				/>
				<RangeControl
					label={ __( 'Start position (%)', 'goodblocks' ) }
					value={ startPosition }
					min={ 10 }
					max={ 90 }
					onChange={ ( v ) => setAttributes( { startPosition: v } ) }
				/>
			</PanelBody>
			<PanelBody title={ __( 'Auto-tease', 'goodblocks' ) } initialOpen={ false }>
				<ToggleControl
					label={ __( 'Enable auto-tease animation', 'goodblocks' ) }
					help={ __(
						'Automatically slides back and forth to attract attention. Stops when the user interacts.',
						'goodblocks'
					) }
					checked={ enableTease }
					onChange={ ( v ) => setAttributes( { enableTease: v } ) }
				/>
				{ enableTease && (
					<>
						<RangeControl
							label={ __( 'Speed (seconds per cycle)', 'goodblocks' ) }
							value={ teaseSpeed }
							min={ 1 }
							max={ 8 }
							step={ 0.5 }
							onChange={ ( v ) =>
								setAttributes( { teaseSpeed: v } )
							}
						/>
						<ToggleControl
							label={ __( 'Tease only once', 'goodblocks' ) }
							help={ __(
								'Run the animation once and then stop.',
								'goodblocks'
							) }
							checked={ teaseOnce }
							onChange={ ( v ) =>
								setAttributes( { teaseOnce: v } )
							}
						/>
					</>
				) }
			</PanelBody>
			<PanelBody
				title={ __( 'Labels', 'goodblocks' ) }
				initialOpen={ false }
			>
				<ToggleControl
					label={ __( 'Show labels', 'goodblocks' ) }
					checked={ showLabels }
					onChange={ ( v ) => setAttributes( { showLabels: v } ) }
				/>
				{ showLabels && (
					<TextControl
						label={ __( 'Before label', 'goodblocks' ) }
						value={ beforeLabel }
						onChange={ ( v ) =>
							setAttributes( { beforeLabel: v } )
						}
					/>
				) }
				{ showLabels && (
					<TextControl
						label={ __( 'After label', 'goodblocks' ) }
						value={ afterLabel }
						onChange={ ( v ) =>
							setAttributes( { afterLabel: v } )
						}
					/>
				) }
			</PanelBody>
		</InspectorControls>
	);

	// Preview mode — both images selected.
	if ( beforeUrl && afterUrl ) {
		const pos = startPosition || 50;
		const clipBefore = isVertical
			? `inset(0 0 ${ 100 - pos }% 0)`
			: `inset(0 ${ 100 - pos }% 0 0)`;
		const handleStyle = isVertical
			? {
					position: 'absolute',
					left: 0,
					right: 0,
					top: `${ pos }%`,
					height: '3px',
					width: '100%',
					background: '#fff',
					boxShadow: '0 0 6px rgba(0,0,0,0.4)',
					transform: 'translateY(-1.5px)',
					pointerEvents: 'none',
			  }
			: {
					position: 'absolute',
					top: 0,
					bottom: 0,
					left: `${ pos }%`,
					width: '2px',
					background: '#fff',
					boxShadow: '0 0 6px rgba(0,0,0,0.4)',
					transform: 'translateX(-1px)',
					pointerEvents: 'none',
			  };

		const containerStyle = {
			position: 'relative',
		};
		if ( hasAR ) {
			containerStyle.aspectRatio = arCSS;
			containerStyle.overflow = 'hidden';
		}

		const imgStyle = hasAR
			? {
					display: 'block',
					width: '100%',
					height: '100%',
					objectFit: 'cover',
			  }
			: {
					display: 'block',
					width: '100%',
					height: 'auto',
			  };

		return (
			<div { ...blockProps }>
				{ inspector }
				<div
					className="image-compare-editor__preview"
					style={ containerStyle }
				>
					<img
						src={ afterUrl }
						alt={ afterAlt }
						style={ {
							...imgStyle,
							objectPosition: `${ afp.x * 100 }% ${ afp.y * 100 }%`,
						} }
					/>
					<div
						className="image-compare-editor__before"
						style={ {
							position: 'absolute',
							top: 0,
							left: 0,
							width: '100%',
							height: '100%',
							clipPath: clipBefore,
						} }
					>
						<img
							src={ beforeUrl }
							alt={ beforeAlt }
							style={ {
								display: 'block',
								width: '100%',
								height: '100%',
								objectFit: 'cover',
								objectPosition: `${ bfp.x * 100 }% ${ bfp.y * 100 }%`,
							} }
						/>
					</div>
					<div
						className="image-compare-editor__handle"
						style={ handleStyle }
					>
						<div className={ `image-compare-editor__knob${ isVertical ? ' is-vertical' : '' }` } />
					</div>
					{ showLabels && (
						<span className="image-compare-editor__label image-compare-editor__label--before">
							{ beforeLabel }
						</span>
					) }
					{ showLabels && (
						<span className="image-compare-editor__label image-compare-editor__label--after">
							{ afterLabel }
						</span>
					) }
					{ enableTease && (
						<span className="image-compare-editor__tease-badge">
							{ __( 'Auto-tease ON', 'goodblocks' ) }
						</span>
					) }
				</div>
				<div className="image-compare-editor__buttons">
					<MediaUploadCheck>
						<MediaUpload
							onSelect={ onSelectBefore }
							allowedTypes={ [ 'image' ] }
							value={ beforeId }
							render={ ( { open } ) => (
								<Button
									onClick={ open }
									variant="secondary"
									size="small"
								>
									{ __(
										'Replace before image',
										'goodblocks'
									) }
								</Button>
							) }
						/>
					</MediaUploadCheck>
					<MediaUploadCheck>
						<MediaUpload
							onSelect={ onSelectAfter }
							allowedTypes={ [ 'image' ] }
							value={ afterId }
							render={ ( { open } ) => (
								<Button
									onClick={ open }
									variant="secondary"
									size="small"
								>
									{ __(
										'Replace after image',
										'goodblocks'
									) }
								</Button>
							) }
						/>
					</MediaUploadCheck>
				</div>
			</div>
		);
	}

	// Placeholder — no images yet.
	return (
		<div { ...blockProps }>
			{ inspector }
			<Placeholder
				icon={ ICON }
				label={ __( 'Image Compare', 'goodblocks' ) }
				instructions={ __(
					'Select a before and after image to create a comparison slider.',
					'goodblocks'
				) }
			>
				<div className="image-compare-editor__selectors">
					<MediaUploadCheck>
						<MediaUpload
							onSelect={ onSelectBefore }
							allowedTypes={ [ 'image' ] }
							value={ beforeId }
							render={ ( { open } ) => (
								<Button
									onClick={ open }
									variant={
										beforeUrl ? 'secondary' : 'primary'
									}
								>
									{ beforeUrl
										? __(
												'Before image selected',
												'goodblocks'
										  )
										: __(
												'Select before image',
												'goodblocks'
										  ) }
								</Button>
							) }
						/>
					</MediaUploadCheck>
					<MediaUploadCheck>
						<MediaUpload
							onSelect={ onSelectAfter }
							allowedTypes={ [ 'image' ] }
							value={ afterId }
							render={ ( { open } ) => (
								<Button
									onClick={ open }
									variant={
										afterUrl ? 'secondary' : 'primary'
									}
								>
									{ afterUrl
										? __(
												'After image selected',
												'goodblocks'
										  )
										: __(
												'Select after image',
												'goodblocks'
										  ) }
								</Button>
							) }
						/>
					</MediaUploadCheck>
				</div>
			</Placeholder>
		</div>
	);
}

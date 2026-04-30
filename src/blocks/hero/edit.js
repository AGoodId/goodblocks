import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	InspectorControls,
	MediaUpload,
	MediaUploadCheck,
	RichText,
	BlockControls,
	BlockAlignmentMatrixControl,
} from '@wordpress/block-editor';
import {
	Panel,
	PanelBody,
	SelectControl,
	Button,
	UnitControl,
	useCustomUnits,
	ToggleControl,
	RangeControl,
	ColorPalette,
} from '@wordpress/components';
import './editor.scss';
import { getPositionClassName } from '../../shared';

export default function Edit( { attributes, setAttributes } ) {
	const {
		animation,
		rubrik,
		text,
		backgroundMedia,
		height,
		contentPosition,
		button,
		reverseFlow,
		overlayColor,
		dimRatio,
		scrollArrow,
	} = attributes;

	const backgroundStyle = { height };
	if ( backgroundMedia?.type === 'image' && backgroundMedia?.url ) {
		backgroundStyle.backgroundImage = `url(${ backgroundMedia.url })`;
	}

	const blockProps = useBlockProps( { style: backgroundStyle } );

	const units = useCustomUnits( {
		availableUnits: [ 'svh' ],
		defaultValues: { svh: 100 },
	} );

	return (
		<div { ...blockProps }>
			<InspectorControls key="setting">
				<BlockControls>
					<BlockAlignmentMatrixControl
						label={ __( 'Change content position' ) }
						value={ contentPosition }
						onChange={ ( v ) =>
							setAttributes( { contentPosition: v } )
						}
					/>
				</BlockControls>
				<Panel>
					<PanelBody title={ __( 'Settings', 'goodblocks' ) }>
						<SelectControl
							label={ __( 'Animation', 'goodblocks' ) }
							value={ animation }
							options={ [
								{
									label: __( 'None', 'goodblocks' ),
									value: 'none',
								},
								{
									label: __( 'Fade up', 'goodblocks' ),
									value: 'fade-up',
								},
								{
									label: __( 'Split words', 'goodblocks' ),
									value: 'split-words',
								},
							] }
							onChange={ ( v ) =>
								setAttributes( { animation: v } )
							}
						/>
						<UnitControl
							min={ 0 }
							max={ 100 }
							label={ __( 'Height', 'goodblocks' ) }
							value={ height }
							onChange={ ( v ) => setAttributes( { height: v } ) }
							units={ units }
						/>
						<ToggleControl
							label={ __( 'Reverse text order', 'goodblocks' ) }
							checked={ !! reverseFlow }
							onChange={ ( v ) =>
								setAttributes( { reverseFlow: v } )
							}
						/>
						<ToggleControl
							label={ __( 'Show scroll arrow', 'goodblocks' ) }
							checked={ !! scrollArrow }
							onChange={ ( v ) =>
								setAttributes( { scrollArrow: v } )
							}
						/>
						<MediaUploadCheck>
							<MediaUpload
								onSelect={ ( media ) =>
									setAttributes( {
										backgroundMedia: media,
									} )
								}
								allowedTypes={ [ 'image', 'video' ] }
								render={ ( { open } ) => (
									<div>
										{ !! backgroundMedia &&
											backgroundMedia.type ===
												'image' && (
												<>
													<img
														src={
															backgroundMedia.url
														}
														alt={
															backgroundMedia.alt
														}
													/>
													<Button
														onClick={ () =>
															setAttributes( {
																backgroundMedia:
																	null,
															} )
														}
														isDestructive
														isSecondary
													>
														{ __(
															'Remove image',
															'goodblocks'
														) }
													</Button>
												</>
											) }
										{ !! backgroundMedia &&
											backgroundMedia.type ===
												'video' && (
												<>
													<video
														src={
															backgroundMedia.url
														}
														controls
													/>
													<Button
														onClick={ () =>
															setAttributes( {
																backgroundMedia:
																	null,
															} )
														}
														isDestructive
														isSecondary
													>
														{ __(
															'Remove video',
															'goodblocks'
														) }
													</Button>
												</>
											) }
										<Button onClick={ open } isSecondary>
											{ !! backgroundMedia
												? __(
														'Replace media',
														'goodblocks'
												  )
												: __(
														'Add media',
														'goodblocks'
												  ) }
										</Button>
									</div>
								) }
							/>
						</MediaUploadCheck>
					</PanelBody>
				</Panel>
			</InspectorControls>
			<InspectorControls group="color">
				<div style={ { gridColumn: '1 / -1' } }>
					<RangeControl
						label={ __( 'Overlay opacity' ) }
						value={ dimRatio }
						onChange={ ( v ) => setAttributes( { dimRatio: v } ) }
						min={ 0 }
						max={ 100 }
						step={ 10 }
						required
					/>
				</div>
				<div style={ { gridColumn: '1 / -1' } }>
					<ColorPalette
						value={ overlayColor }
						onChange={ ( v ) =>
							setAttributes( { overlayColor: v } )
						}
					/>
				</div>
			</InspectorControls>

			<div className="hero-block">
				{ backgroundMedia?.type === 'video' && (
					<video
						autoPlay
						muted
						loop
						playsInline
						className="hero-block__video"
						src={ backgroundMedia.url }
						type={ backgroundMedia.mime }
					/>
				) }
				<div
					className="hero-block__overlay"
					style={ {
						backgroundColor: overlayColor,
						opacity: dimRatio / 100,
					} }
				></div>
				<div
					className={ `hero-block__content ${ getPositionClassName(
						contentPosition
					) }` }
				>
					<div>
						<div
							className={ `hero-block__text${
								reverseFlow ? ' reverse-flow' : ''
							}` }
						>
							<RichText
								tagName="h2"
								value={ rubrik }
								onChange={ ( v ) =>
									setAttributes( { rubrik: v } )
								}
								placeholder={ __( 'Heading…', 'goodblocks' ) }
							/>
							<RichText
								tagName="p"
								value={ text }
								onChange={ ( v ) =>
									setAttributes( { text: v } )
								}
								placeholder={ __(
									'Subheading…',
									'goodblocks'
								) }
							/>
						</div>
						<button type="button" className="btn btn-large">
							<RichText
								tagName="span"
								value={ button }
								onChange={ ( v ) =>
									setAttributes( { button: v } )
								}
								placeholder={ __(
									'Button text',
									'goodblocks'
								) }
							/>
						</button>
					</div>
				</div>
				{ !! scrollArrow && (
					<span
						className="hero-block__scroll-arrow"
						aria-hidden="true"
					>
						<svg
							xmlns="http://www.w3.org/2000/svg"
							height="24px"
							viewBox="0 -960 960 960"
							width="24px"
							fill="currentColor"
						>
							<path d="M440-800v487L216-537l-56 57 320 320 320-320-56-57-224 224v-487h-80Z" />
						</svg>
					</span>
				) }
			</div>
		</div>
	);
}

import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	useInnerBlocksProps,
	BlockControls,
	InspectorControls,
	MediaUpload,
	MediaUploadCheck,
} from '@wordpress/block-editor';
import {
	ToolbarGroup,
	ToolbarButton,
	PanelBody,
	ToggleControl,
	SelectControl,
	RangeControl,
	Button,
} from '@wordpress/components';
import {
	plus,
	chevronLeft,
	chevronRight,
	image as imageIcon,
} from '@wordpress/icons';
import { useSelect, useDispatch } from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element';
import { createBlock } from '@wordpress/blocks';
import './editor.scss';
import { mobileTextPaddingStyle } from './mobile-text-padding';
import { desktopTextPaddingStyle } from './desktop-text-padding';

export default function Edit( { clientId, attributes, setAttributes } ) {
	const {
		showNavigation,
		showPagination,
		showThumbnails,
		effect,
		autoplay,
		autoplayDelay,
		layout,
		sliderHeight,
		maxImageHeight,
		mobileTextPadding,
		desktopTextPadding,
		aspectRatio = '16-9',
	} = attributes;
	const maxHeightStyle =
		layout === 'overlay' &&
		aspectRatio !== 'none' &&
		Number.isInteger( maxImageHeight ) &&
		maxImageHeight >= 100 &&
		maxImageHeight <= 1600
			? { '--slider-max-image-height': `${ maxImageHeight }px` }
			: {};
	const [ currentSlide, setCurrentSlide ] = useState( 0 );
	const mobilePaddingDefaults = { top: 88, right: 20, bottom: 80, left: 20 };
	const mobilePaddingStyle = mobileTextPaddingStyle(
		layout,
		mobileTextPadding
	);
	const desktopPaddingDefaults = { top: 32, right: 32, bottom: 32, left: 32 };
	const desktopPaddingStyle = desktopTextPaddingStyle(
		layout,
		desktopTextPadding
	);

	const { innerBlocks, selectedBlockId } = useSelect(
		( select ) => {
			const selected =
				select( 'core/block-editor' ).getSelectedBlockClientId();
			return {
				innerBlocks:
					select( 'core/block-editor' ).getBlock( clientId )
						?.innerBlocks || [],
				selectedBlockId: selected,
			};
		},
		[ clientId ]
	);

	const { insertBlock, insertBlocks } = useDispatch( 'core/block-editor' );

	const blockProps = useBlockProps( { className: 'slider-container' } );
	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		allowedBlocks: [ 'goodblocks/slide' ],
		template: [],
		renderAppender: false,
	} );

	const slideCount = innerBlocks.length;

	useEffect( () => {
		if ( selectedBlockId ) {
			const idx = innerBlocks.findIndex(
				( b ) => b.clientId === selectedBlockId
			);
			if ( idx !== -1 && idx !== currentSlide ) {
				setCurrentSlide( idx );
			}
		}
	}, [ selectedBlockId, innerBlocks, currentSlide ] );

	useEffect( () => {
		if ( currentSlide >= slideCount && slideCount > 0 ) {
			setCurrentSlide( slideCount - 1 );
		}
	}, [ slideCount, currentSlide ] );

	const nextSlide = () => {
		if ( slideCount < 2 ) {
			return;
		}
		setCurrentSlide( ( currentSlide + 1 ) % slideCount );
	};

	const prevSlide = () => {
		if ( slideCount < 2 ) {
			return;
		}
		setCurrentSlide( ( currentSlide - 1 + slideCount ) % slideCount );
	};

	const addSlide = () => {
		const newSlide = createBlock( 'goodblocks/slide' );
		insertBlock( newSlide, innerBlocks.length, clientId );
		setCurrentSlide( innerBlocks.length );
	};

	const addSlidesFromMedia = ( media ) => {
		if ( ! media ) {
			return;
		}
		const mediaArray = Array.isArray( media ) ? media : [ media ];
		const valid = mediaArray.filter( ( item ) => item && item.id );
		if ( ! valid.length ) {
			return;
		}

		const newSlides = valid.map( ( item ) =>
			createBlock( 'goodblocks/slide', {
				mediaId: item.id,
				mediaUrl: item.url,
				mediaAlt: item.alt || item.alt_text || '',
				mediaType: item.type,
				caption: item.caption || '',
			} )
		);
		insertBlocks( newSlides, innerBlocks.length, clientId );
		setCurrentSlide( innerBlocks.length );
	};

	return (
		<>
			<BlockControls>
				<ToolbarGroup>
					<ToolbarButton
						icon={ chevronLeft }
						label={ __( 'Previous Slide', 'goodblocks' ) }
						onClick={ prevSlide }
						disabled={ slideCount < 2 }
					/>
					<ToolbarButton
						icon={ chevronRight }
						label={ __( 'Next Slide', 'goodblocks' ) }
						onClick={ nextSlide }
						disabled={ slideCount < 2 }
					/>
				</ToolbarGroup>
				<ToolbarGroup>
					<MediaUploadCheck>
						<MediaUpload
							onSelect={ addSlidesFromMedia }
							allowedTypes={ [ 'image', 'video' ] }
							multiple={ true }
							value={ [] }
							render={ ( { open } ) => (
								<ToolbarButton
									icon={ imageIcon }
									label={ __( 'Add Images', 'goodblocks' ) }
									onClick={ open }
								/>
							) }
						/>
					</MediaUploadCheck>
					<ToolbarButton
						icon={ plus }
						label={ __( 'Add Slide', 'goodblocks' ) }
						onClick={ addSlide }
					/>
				</ToolbarGroup>
			</BlockControls>

			<InspectorControls>
				<PanelBody
					title={ __( 'Slider Settings', 'goodblocks' ) }
					initialOpen={ true }
				>
					<SelectControl
						label={ __( 'Layout', 'goodblocks' ) }
						value={ layout }
						options={ [
							{
								label: __( 'Text Overlay', 'goodblocks' ),
								value: 'overlay',
							},
							{
								label: __(
									'Text Below (with Caption)',
									'goodblocks'
								),
								value: 'below',
							},
						] }
						onChange={ ( v ) => setAttributes( { layout: v } ) }
					/>
					<SelectControl
						label={ __( 'Transition Effect', 'goodblocks' ) }
						value={ effect }
						options={ [
							{ label: 'Fade', value: 'fade' },
							{ label: 'Slide', value: 'slide' },
							{ label: 'Cube', value: 'cube' },
							{ label: 'Flip', value: 'flip' },
							{ label: 'Coverflow', value: 'coverflow' },
						] }
						onChange={ ( v ) => setAttributes( { effect: v } ) }
					/>
					<ToggleControl
						label={ __( 'Show Navigation Arrows', 'goodblocks' ) }
						checked={ showNavigation }
						onChange={ ( v ) =>
							setAttributes( { showNavigation: v } )
						}
					/>
					<ToggleControl
						label={ __( 'Show Pagination Dots', 'goodblocks' ) }
						checked={ showPagination }
						onChange={ ( v ) =>
							setAttributes( { showPagination: v } )
						}
					/>
					{ layout === 'below' && (
						<ToggleControl
							label={ __(
								'Show image thumbnails',
								'goodblocks'
							) }
							help={ __(
								'Thumbnails replace dots on large screens and are hidden on mobile. Swipe or use arrows to change images.',
								'goodblocks'
							) }
							checked={ showThumbnails === true }
							onChange={ ( v ) =>
								setAttributes( { showThumbnails: v } )
							}
						/>
					) }
					<ToggleControl
						label={ __( 'Enable Autoplay', 'goodblocks' ) }
						checked={ autoplay }
						onChange={ ( v ) => setAttributes( { autoplay: v } ) }
					/>
					{ autoplay && (
						<RangeControl
							label={ __(
								'Autoplay Delay (seconds)',
								'goodblocks'
							) }
							value={ autoplayDelay / 1000 }
							onChange={ ( v ) =>
								setAttributes( { autoplayDelay: v * 1000 } )
							}
							min={ 1 }
							max={ 10 }
							step={ 0.5 }
						/>
					) }
					<SelectControl
						label={ __( 'Aspect Ratio', 'goodblocks' ) }
						value={ aspectRatio || '16-9' }
						options={ [
							{
								label: __(
									'None (use slider height)',
									'goodblocks'
								),
								value: 'none',
							},
							{ label: '16:9', value: '16-9' },
							{ label: '4:3', value: '4-3' },
							{ label: '3:2', value: '3-2' },
							{ label: '2:1', value: '2-1' },
							{ label: '3:1', value: '3-1' },
						] }
						onChange={ ( v ) =>
							setAttributes( { aspectRatio: v } )
						}
					/>
					{ ( aspectRatio === 'none' || ! aspectRatio ) && (
						<RangeControl
							label={ __( 'Slider Height (vh)', 'goodblocks' ) }
							value={ sliderHeight }
							onChange={ ( v ) =>
								setAttributes( { sliderHeight: v } )
							}
							min={ 30 }
							max={ 100 }
							step={ 5 }
						/>
					) }
					{ layout === 'overlay' && aspectRatio !== 'none' && (
						<RangeControl
							label={ __(
								'Maximum image height (px)',
								'goodblocks'
							) }
							help={ __(
								'0 means no limit. Text can expand the slide.',
								'goodblocks'
							) }
							value={ maxImageHeight || 0 }
							onChange={ ( value ) =>
								setAttributes( { maxImageHeight: value } )
							}
							min={ 0 }
							max={ 1600 }
							step={ 100 }
						/>
					) }
				</PanelBody>
				{ layout === 'overlay' && (
					<PanelBody
						title={ __( 'Desktop text padding', 'goodblocks' ) }
						initialOpen={ false }
					>
						<p>
							{ __(
								'Sets text spacing on large screens; the slide grows to fit the text.',
								'goodblocks'
							) }
						</p>
						{ [
							[ 'top', __( 'Above text (px)', 'goodblocks' ) ],
							[
								'right',
								__( 'Right of text (px)', 'goodblocks' ),
							],
							[ 'bottom', __( 'Below text (px)', 'goodblocks' ) ],
							[ 'left', __( 'Left of text (px)', 'goodblocks' ) ],
						].map( ( [ side, label ] ) => (
							<RangeControl
								key={ side }
								label={ label }
								min={ 0 }
								max={ 800 }
								step={ 1 }
								value={
									desktopTextPadding?.[ side ] ??
									desktopPaddingDefaults[ side ]
								}
								onChange={ ( value ) =>
									setAttributes( {
										desktopTextPadding: {
											...desktopPaddingDefaults,
											...desktopTextPadding,
											[ side ]: value,
										},
									} )
								}
							/>
						) ) }
						<Button
							variant="secondary"
							onClick={ () =>
								setAttributes( {
									desktopTextPadding: undefined,
								} )
							}
						>
							{ __( 'Reset desktop text padding', 'goodblocks' ) }
						</Button>
					</PanelBody>
				) }
				{ layout === 'overlay' && (
					<PanelBody
						title={ __( 'Mobile text padding', 'goodblocks' ) }
						initialOpen={ false }
					>
						{ [
							[ 'top', __( 'Above text (px)', 'goodblocks' ) ],
							[
								'right',
								__( 'Right of text (px)', 'goodblocks' ),
							],
							[ 'bottom', __( 'Below text (px)', 'goodblocks' ) ],
							[ 'left', __( 'Left of text (px)', 'goodblocks' ) ],
						].map( ( [ side, label ] ) => (
							<RangeControl
								key={ side }
								label={ label }
								min={ side === 'bottom' ? 80 : 0 }
								max={ 800 }
								step={ 1 }
								value={
									mobileTextPadding?.[ side ] ??
									mobilePaddingDefaults[ side ]
								}
								onChange={ ( value ) =>
									setAttributes( {
										mobileTextPadding: {
											...mobilePaddingDefaults,
											...mobileTextPadding,
											[ side ]: value,
										},
									} )
								}
							/>
						) ) }
						<Button
							variant="secondary"
							onClick={ () =>
								setAttributes( {
									mobileTextPadding: undefined,
								} )
							}
						>
							{ __( 'Reset text padding', 'goodblocks' ) }
						</Button>
					</PanelBody>
				) }
			</InspectorControls>

			<div
				{ ...innerBlocksProps }
				data-current-slide={ currentSlide }
				data-layout={ layout }
				data-aspect-ratio={ aspectRatio || '16-9' }
				data-mobile-text-padding={
					Object.keys( mobilePaddingStyle ).length ? true : undefined
				}
				data-desktop-text-padding={
					Object.keys( desktopPaddingStyle ).length ? true : undefined
				}
				className={ `${ innerBlocksProps.className } ${
					( aspectRatio && aspectRatio !== 'none' ) || ! aspectRatio
						? `aspect-${ aspectRatio || '16-9' }`
						: ''
				}` }
				style={ {
					...( aspectRatio === 'none'
						? {
								'--slider-height': `${ sliderHeight }vh`,
								minHeight: `${ sliderHeight }vh`,
						  }
						: {} ),
					...maxHeightStyle,
					...mobilePaddingStyle,
					...desktopPaddingStyle,
				} }
			/>
		</>
	);
}

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

export default function Edit( { clientId, attributes, setAttributes } ) {
	const {
		showNavigation,
		showPagination,
		effect,
		autoplay,
		autoplayDelay,
		layout,
		sliderHeight,
		aspectRatio = '16-9',
	} = attributes;
	const [ currentSlide, setCurrentSlide ] = useState( 0 );

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
		if ( slideCount < 2 ) return;
		setCurrentSlide( ( currentSlide + 1 ) % slideCount );
	};

	const prevSlide = () => {
		if ( slideCount < 2 ) return;
		setCurrentSlide(
			( currentSlide - 1 + slideCount ) % slideCount
		);
	};

	const addSlide = () => {
		const newSlide = createBlock( 'goodblocks/slide' );
		insertBlock( newSlide, innerBlocks.length, clientId );
		setCurrentSlide( innerBlocks.length );
	};

	const addSlidesFromMedia = ( media ) => {
		if ( ! media ) return;
		const mediaArray = Array.isArray( media ) ? media : [ media ];
		const valid = mediaArray.filter( ( item ) => item && item.id );
		if ( ! valid.length ) return;

		const newSlides = valid.map( ( item ) =>
			createBlock( 'goodblocks/slide', {
				mediaId: item.id,
				mediaUrl: item.url,
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
									label={ __(
										'Add Images',
										'goodblocks'
									) }
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
							{ label: __( 'Text Overlay', 'goodblocks' ), value: 'overlay' },
							{ label: __( 'Text Below (with Caption)', 'goodblocks' ), value: 'below' },
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
					<ToggleControl
						label={ __( 'Enable Autoplay', 'goodblocks' ) }
						checked={ autoplay }
						onChange={ ( v ) =>
							setAttributes( { autoplay: v } )
						}
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
							{ label: __( 'None (use slider height)', 'goodblocks' ), value: 'none' },
							{ label: '16:9', value: '16-9' },
							{ label: '4:3', value: '4-3' },
							{ label: '3:1', value: '3-1' },
						] }
						onChange={ ( v ) =>
							setAttributes( { aspectRatio: v } )
						}
					/>
					{ ( aspectRatio === 'none' || ! aspectRatio ) && (
						<RangeControl
							label={ __(
								'Slider Height (vh)',
								'goodblocks'
							) }
							value={ sliderHeight }
							onChange={ ( v ) =>
								setAttributes( { sliderHeight: v } )
							}
							min={ 30 }
							max={ 100 }
							step={ 5 }
						/>
					) }
				</PanelBody>
			</InspectorControls>

			<div
				{ ...innerBlocksProps }
				data-current-slide={ currentSlide }
				data-layout={ layout }
				data-aspect-ratio={ aspectRatio || '16-9' }
				className={ `${ innerBlocksProps.className } ${
					( aspectRatio && aspectRatio !== 'none' ) || ! aspectRatio
						? `aspect-${ aspectRatio || '16-9' }`
						: ''
				}` }
				style={
					aspectRatio === 'none'
						? {
								'--slider-height': `${ sliderHeight }vh`,
								minHeight: `${ sliderHeight }vh`,
						  }
						: {}
				}
			/>
		</>
	);
}

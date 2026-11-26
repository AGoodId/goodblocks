import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';

export default function save( { attributes } ) {
	const {
		showNavigation,
		showPagination,
		effect,
		autoplay,
		autoplayDelay,
		layout,
		sliderHeight,
		aspectRatio = '16-9',
		maxImageHeight,
	} = attributes;
	const maxHeightStyle =
		layout === 'overlay' &&
		aspectRatio !== 'none' &&
		Number.isInteger( maxImageHeight ) &&
		maxImageHeight >= 100 &&
		maxImageHeight <= 1600
			? { '--slider-max-image-height': `${ maxImageHeight }px` }
			: {};

	const blockProps = useBlockProps.save( {
		className: `slider-container ${
			( aspectRatio && aspectRatio !== 'none' ) || ! aspectRatio
				? `aspect-${ aspectRatio || '16-9' }`
				: ''
		}`,
		'data-show-navigation': showNavigation,
		'data-show-pagination': showPagination,
		'data-effect': effect,
		'data-autoplay': autoplay,
		'data-autoplay-delay': autoplayDelay,
		'data-layout': layout,
		'data-slider-height': sliderHeight,
		'data-aspect-ratio': aspectRatio || '16-9',
		style: {
			...( aspectRatio === 'none'
				? {
						'--slider-height': `${ sliderHeight }vh`,
						minHeight: `${ sliderHeight }vh`,
				  }
				: {} ),
			...maxHeightStyle,
		},
	} );

	return (
		<div { ...blockProps }>
			<InnerBlocks.Content />
		</div>
	);
}

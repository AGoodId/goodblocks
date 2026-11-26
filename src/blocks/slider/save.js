import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
import { mobileTextPaddingStyle } from './mobile-text-padding';

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
		mobileTextPadding,
	} = attributes;
	const maxHeightStyle =
		layout === 'overlay' &&
		aspectRatio !== 'none' &&
		Number.isInteger( maxImageHeight ) &&
		maxImageHeight >= 100 &&
		maxImageHeight <= 1600
			? { '--slider-max-image-height': `${ maxImageHeight }px` }
			: {};

	const mobilePaddingStyle = mobileTextPaddingStyle(
		layout,
		mobileTextPadding
	);
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
		'data-mobile-text-padding': Object.keys( mobilePaddingStyle ).length
			? true
			: undefined,
		style: {
			...( aspectRatio === 'none'
				? {
						'--slider-height': `${ sliderHeight }vh`,
						minHeight: `${ sliderHeight }vh`,
				  }
				: {} ),
			...maxHeightStyle,
			...mobilePaddingStyle,
		},
	} );

	return (
		<div { ...blockProps }>
			<InnerBlocks.Content />
		</div>
	);
}

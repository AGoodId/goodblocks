import { __ } from '@wordpress/i18n';
import {
	useInnerBlocksProps,
	InspectorControls,
	useBlockProps,
} from '@wordpress/block-editor';
import { PanelBody, RangeControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const { stickyOffset, cardGap } = attributes;
	const template = [ 'Discover', 'Shape', 'Launch' ].map(
		( title, index ) => [
			'core/group',
			{},
			[
				[ 'core/paragraph', { content: `0${ index + 1 }` } ],
				[ 'core/heading', { level: 3, content: title } ],
				[
					'core/paragraph',
					{
						content: __(
							'An editable card. Replace this copy with your process, services or next big idea.',
							'goodblocks'
						),
					},
				],
			],
		]
	);
	const innerBlocksProps = useInnerBlocksProps(
		useBlockProps( {
			className: 'gb-sticky-cards',
			style: {
				'--gb-card-offset': `${ stickyOffset }px`,
				'--gb-card-gap': `${ cardGap }px`,
			},
		} ),
		{ allowedBlocks: [ 'core/group' ], template }
	);
	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Card stack', 'goodblocks' ) }>
					<RangeControl
						label={ __( 'Sticky offset (px)', 'goodblocks' ) }
						value={ stickyOffset }
						min={ 0 }
						max={ 180 }
						onChange={ ( value ) =>
							setAttributes( { stickyOffset: value } )
						}
					/>
					<RangeControl
						label={ __( 'Stack spacing (px)', 'goodblocks' ) }
						value={ cardGap }
						min={ 0 }
						max={ 48 }
						onChange={ ( value ) =>
							setAttributes( { cardGap: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...innerBlocksProps } />
		</>
	);
}

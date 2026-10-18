import { __ } from '@wordpress/i18n';
import {
	InnerBlocks,
	InspectorControls,
	useBlockProps,
} from '@wordpress/block-editor';
import { PanelBody, RangeControl, SelectControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const { visualSide, pace, stickyOffset } = attributes;
	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Story layout', 'goodblocks' ) }>
					<SelectControl
						label={ __( 'Visual position', 'goodblocks' ) }
						value={ visualSide }
						options={ [
							{
								label: __( 'Left', 'goodblocks' ),
								value: 'left',
							},
							{
								label: __( 'Right', 'goodblocks' ),
								value: 'right',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { visualSide: value } )
						}
					/>
					<SelectControl
						label={ __( 'Reading pace', 'goodblocks' ) }
						value={ pace }
						options={ [
							{
								label: __( 'Relaxed', 'goodblocks' ),
								value: 'relaxed',
							},
							{
								label: __( 'Compact', 'goodblocks' ),
								value: 'compact',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { pace: value } )
						}
					/>
					<RangeControl
						label={ __( 'Sticky offset (px)', 'goodblocks' ) }
						value={ stickyOffset }
						min={ 0 }
						max={ 180 }
						onChange={ ( value ) =>
							setAttributes( { stickyOffset: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div
				{ ...useBlockProps( {
					className: `gb-scrollytelling gb-scrollytelling--${ visualSide } gb-scrollytelling--${ pace }`,
					style: { '--gb-story-offset': `${ stickyOffset }px` },
				} ) }
			>
				<InnerBlocks
					allowedBlocks={ [ 'goodblocks/story-step' ] }
					template={ [
						[ 'goodblocks/story-step', { label: '01' } ],
						[ 'goodblocks/story-step', { label: '02' } ],
						[ 'goodblocks/story-step', { label: '03' } ],
					] }
				/>
			</div>
		</>
	);
}

import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	SelectControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { __ } from '@wordpress/i18n';

const Edit = ( { attributes, setAttributes } ) => {
	const {
		parentPostId,
		depth,
		orderby,
		order,
		showRoot,
		accordion,
		markupPreset,
		configId,
	} = attributes;

	return (
		<div { ...useBlockProps() }>
			<InspectorControls>
				<PanelBody title={ __( 'Navigation', 'goodblocks' ) }>
					<TextControl
						label={ __( 'Parent page ID', 'goodblocks' ) }
						value={ parentPostId || '' }
						onChange={ ( value ) => {
							if (
								value === '' ||
								! Number.isNaN( Number( value ) )
							) {
								setAttributes( {
									parentPostId:
										value === '' ? 0 : Number( value ),
								} );
							}
						} }
						help={ __(
							'Leave empty to use the current top-level page.',
							'goodblocks'
						) }
					/>
					<RangeControl
						label={ __( 'Depth', 'goodblocks' ) }
						value={ depth }
						onChange={ ( value ) =>
							setAttributes( { depth: value || 0 } )
						}
						min={ 0 }
						max={ 8 }
						help={ __( '0 means unlimited depth.', 'goodblocks' ) }
					/>
					<SelectControl
						label={ __( 'Order by', 'goodblocks' ) }
						value={ orderby }
						options={ [
							{
								label: __(
									'Menu order, then title',
									'goodblocks'
								),
								value: 'menu_order,post_title',
							},
							{
								label: __( 'Menu order', 'goodblocks' ),
								value: 'menu_order',
							},
							{
								label: __( 'Title', 'goodblocks' ),
								value: 'post_title',
							},
							{
								label: __( 'Published date', 'goodblocks' ),
								value: 'post_date',
							},
							{
								label: __( 'Modified date', 'goodblocks' ),
								value: 'post_modified',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { orderby: value } )
						}
					/>
					<SelectControl
						label={ __( 'Order', 'goodblocks' ) }
						value={ order }
						options={ [
							{
								label: __( 'Ascending', 'goodblocks' ),
								value: 'ASC',
							},
							{
								label: __( 'Descending', 'goodblocks' ),
								value: 'DESC',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { order: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show root page title', 'goodblocks' ) }
						checked={ !! showRoot }
						onChange={ ( value ) =>
							setAttributes( { showRoot: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Accordion toggles', 'goodblocks' ) }
						checked={ !! accordion }
						onChange={ ( value ) =>
							setAttributes( { accordion: value } )
						}
					/>
				</PanelBody>
				<PanelBody
					title={ __( 'Compatibility', 'goodblocks' ) }
					initialOpen={ false }
				>
					<SelectControl
						label={ __( 'Markup preset', 'goodblocks' ) }
						value={ markupPreset }
						options={ [
							{
								label: __( 'GoodBlocks', 'goodblocks' ),
								value: 'goodblocks',
							},
							{
								label: __( 'Bellows compatible', 'goodblocks' ),
								value: 'bellows',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { markupPreset: value } )
						}
					/>
					<TextControl
						label={ __( 'Bellows config ID', 'goodblocks' ) }
						value={ configId }
						onChange={ ( value ) =>
							setAttributes( { configId: value } )
						}
						help={ __( 'Example: main or om.', 'goodblocks' ) }
						disabled={ markupPreset !== 'bellows' }
					/>
				</PanelBody>
			</InspectorControls>
			<ServerSideRender
				block="goodblocks/local-navigation"
				attributes={ attributes }
			/>
		</div>
	);
};

export default Edit;

import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	useInnerBlocksProps,
	InspectorControls,
} from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	SelectControl,
	Button,
	Placeholder,
	TextControl,
} from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import { createBlock } from '@wordpress/blocks';

import './editor.scss';

const LAYOUT_OPTIONS = [
	{ label: __( 'Grid', 'goodblocks' ), value: 'grid' },
	{
		label: __( 'Bento - Large Left', 'goodblocks' ),
		value: 'bento-large-left',
	},
	{
		label: __( 'Bento - Large Right', 'goodblocks' ),
		value: 'bento-large-right',
	},
	{
		label: __( 'Bento - Large Top', 'goodblocks' ),
		value: 'bento-large-top',
	},
	{ label: __( 'Mosaic', 'goodblocks' ), value: 'mosaic' },
	{ label: __( 'Custom', 'goodblocks' ), value: 'custom' },
];

export default function Edit( { attributes, setAttributes, clientId } ) {
	const { columns, gap, layout, height, borderRadius } = attributes;

	const [ showTemplateSelector, setShowTemplateSelector ] = useState( false );
	const { replaceBlocks, updateBlockAttributes } =
		useDispatch( 'core/block-editor' );
	const innerBlocksClientIds = useSelect(
		( select ) => select( 'core/block-editor' ).getBlockOrder( clientId ),
		[ clientId ]
	);
	const getLargeItemSpan = ( cols ) => {
		return Math.ceil( ( cols * 2 ) / 3 ) || 2;
	};

	// eslint-disable-next-line @wordpress/no-unused-vars-before-return
	const getLayoutAttributes = ( layoutType, cols = 3 ) => {
		// eslint-disable-next-line @wordpress/no-unused-vars-before-return
		const largeSpan = getLargeItemSpan( cols );
		const clearAttrs = { gridColumn: undefined, gridRow: undefined };
		switch ( layoutType ) {
			case 'grid':
			case 'mosaic':
				return [
					clearAttrs,
					clearAttrs,
					clearAttrs,
					clearAttrs,
					clearAttrs,
					clearAttrs,
				];
			case 'bento-large-left':
				return [
					{
						gridColumn: `span ${ largeSpan }`,
						gridRow: `span ${ largeSpan }`,
					},
					clearAttrs,
					clearAttrs,
					clearAttrs,
					clearAttrs,
					clearAttrs,
				];
			case 'bento-large-right':
				return [
					clearAttrs,
					clearAttrs,
					{
						gridColumn: `${ cols - largeSpan + 1 } / ${ cols + 1 }`,
						gridRow: '1 / 3',
					},
					clearAttrs,
					clearAttrs,
					clearAttrs,
				];
			case 'bento-large-top':
				return [
					{ gridColumn: '1 / -1' },
					clearAttrs,
					clearAttrs,
					clearAttrs,
				];
			case 'custom':
				return [];
			default:
				return [];
		}
	};

	// eslint-disable-next-line @wordpress/no-unused-vars-before-return
	const getTemplate = ( layoutType, cols = 3 ) => {
		// eslint-disable-next-line @wordpress/no-unused-vars-before-return
		const largeSpan = getLargeItemSpan( cols );
		switch ( layoutType ) {
			case 'grid':
				return [
					[ 'goodblocks/media-grid-item', { title: 'Item 1' } ],
					[ 'goodblocks/media-grid-item', { title: 'Item 2' } ],
					[ 'goodblocks/media-grid-item', { title: 'Item 3' } ],
					[ 'goodblocks/media-grid-item', { title: 'Item 4' } ],
					[ 'goodblocks/media-grid-item', { title: 'Item 5' } ],
					[ 'goodblocks/media-grid-item', { title: 'Item 6' } ],
				];
			case 'bento-large-left':
				return [
					[
						'goodblocks/media-grid-item',
						{
							title: 'Large Item',
							gridColumn: `span ${ largeSpan }`,
							gridRow: `span ${ largeSpan }`,
						},
					],
					[ 'goodblocks/media-grid-item', { title: 'Item 2' } ],
					[ 'goodblocks/media-grid-item', { title: 'Item 3' } ],
					[ 'goodblocks/media-grid-item', { title: 'Item 4' } ],
					[ 'goodblocks/media-grid-item', { title: 'Item 5' } ],
					[ 'goodblocks/media-grid-item', { title: 'Item 6' } ],
				];
			case 'bento-large-right':
				return [
					[ 'goodblocks/media-grid-item', { title: 'Item 1' } ],
					[ 'goodblocks/media-grid-item', { title: 'Item 2' } ],
					[
						'goodblocks/media-grid-item',
						{
							title: 'Large Item',
							gridColumn: `${ cols - largeSpan + 1 } / ${
								cols + 1
							}`,
							gridRow: '1 / 3',
						},
					],
					[ 'goodblocks/media-grid-item', { title: 'Item 4' } ],
					[ 'goodblocks/media-grid-item', { title: 'Item 5' } ],
					[ 'goodblocks/media-grid-item', { title: 'Item 6' } ],
				];
			case 'bento-large-top':
				return [
					[
						'goodblocks/media-grid-item',
						{ title: 'Large Item', gridColumn: '1 / -1' },
					],
					[ 'goodblocks/media-grid-item', { title: 'Item 2' } ],
					[ 'goodblocks/media-grid-item', { title: 'Item 3' } ],
					[ 'goodblocks/media-grid-item', { title: 'Item 4' } ],
					[ 'goodblocks/media-grid-item', { title: 'Item 5' } ],
					[ 'goodblocks/media-grid-item', { title: 'Item 6' } ],
				];
			case 'mosaic':
				return [
					[ 'goodblocks/media-grid-item', { title: 'Item 1' } ],
					[ 'goodblocks/media-grid-item', { title: 'Item 2' } ],
					[ 'goodblocks/media-grid-item', { title: 'Item 3' } ],
					[ 'goodblocks/media-grid-item', { title: 'Item 4' } ],
					[ 'goodblocks/media-grid-item', { title: 'Item 5' } ],
					[ 'goodblocks/media-grid-item', { title: 'Item 6' } ],
				];
			case 'custom':
				return [];
			default:
				return [];
		}
	};

	useEffect( () => {
		if ( ! layout ) {
			setShowTemplateSelector( true );
		}
	}, [ layout ] );

	useEffect( () => {
		if ( layout === 'custom' ) {
			return;
		}

		if ( layout && innerBlocksClientIds.length > 0 ) {
			const template = getTemplate( layout, columns );

			if ( innerBlocksClientIds.length !== template.length ) {
				const newBlocks = template.map( ( [ blockName, blockAttrs ] ) =>
					createBlock( blockName, blockAttrs )
				);
				replaceBlocks( innerBlocksClientIds, newBlocks );
			} else {
				const layoutAttrs = getLayoutAttributes( layout, columns );
				innerBlocksClientIds.forEach( ( blockId, index ) => {
					if ( layoutAttrs[ index ] ) {
						updateBlockAttributes( blockId, layoutAttrs[ index ] );
					}
				} );
			}
		}
	}, [
		layout,
		columns,
		innerBlocksClientIds,
		replaceBlocks,
		updateBlockAttributes,
	] );

	const blockProps = useBlockProps( {
		style: {
			'--columns': columns.toString(),
			'--gap': `${ gap }px`,
			'--item-height': `${ height ?? 300 }px`,
			'--border-radius': borderRadius || '0px',
		},
		...( layout && { 'data-layout': layout } ),
	} );

	const ALLOWED_BLOCKS = [ 'goodblocks/media-grid-item' ];
	const TEMPLATE = layout ? getTemplate( layout, columns ) : [];

	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		allowedBlocks: ALLOWED_BLOCKS,
		template: TEMPLATE,
		templateLock: false,
	} );

	const handleLayoutSelect = ( selectedLayout ) => {
		setAttributes( { layout: selectedLayout } );
		setShowTemplateSelector( false );
	};

	return (
		<>
			{ showTemplateSelector && (
				<Placeholder
					label={ __( 'Media Grid', 'goodblocks' ) }
					instructions={ __(
						'Select a layout to get started',
						'goodblocks'
					) }
				>
					<div
						style={ {
							display: 'grid',
							gridTemplateColumns: 'repeat(2, 1fr)',
							gap: '10px',
							minWidth: '400px',
						} }
					>
						{ LAYOUT_OPTIONS.map( ( option ) => (
							<Button
								key={ option.value }
								onClick={ () =>
									handleLayoutSelect( option.value )
								}
								variant="secondary"
								style={ {
									padding: '20px',
									textAlign: 'center',
								} }
							>
								{ option.label }
							</Button>
						) ) }
					</div>
				</Placeholder>
			) }

			{ layout && (
				<InspectorControls>
					<PanelBody title={ __( 'Grid Settings', 'goodblocks' ) }>
						<SelectControl
							label={ __( 'Layout Type', 'goodblocks' ) }
							value={ layout }
							options={ LAYOUT_OPTIONS }
							onChange={ ( value ) =>
								setAttributes( { layout: value } )
							}
							help={ __(
								'Choose how items are arranged in the grid. Beware! Changing the layout here can remove existing media grid items.',
								'goodblocks'
							) }
						/>

						<RangeControl
							label={ __( 'Columns', 'goodblocks' ) }
							value={ columns }
							onChange={ ( value ) =>
								setAttributes( { columns: value } )
							}
							min={ 1 }
							max={ 6 }
							help={ __(
								'Number of columns in the grid',
								'goodblocks'
							) }
						/>

						<RangeControl
							label={ __( 'Gap (px)', 'goodblocks' ) }
							value={ gap }
							onChange={ ( value ) =>
								setAttributes( { gap: value } )
							}
							min={ 0 }
							max={ 50 }
							help={ __(
								'Space between grid items',
								'goodblocks'
							) }
						/>

						<RangeControl
							label={ __( 'Item Height (px)', 'goodblocks' ) }
							value={ height }
							onChange={ ( value ) =>
								setAttributes( { height: value } )
							}
							min={ 100 }
							max={ 800 }
							step={ 10 }
							help={ __(
								'Minimum height of all grid items',
								'goodblocks'
							) }
						/>

						<TextControl
							label={ __( 'Border Radius', 'goodblocks' ) }
							value={ borderRadius }
							onChange={ ( value ) =>
								setAttributes( { borderRadius: value } )
							}
							placeholder="0px"
							help={ __(
								'E.g., 8px, 0.5rem, 50%',
								'goodblocks'
							) }
						/>
					</PanelBody>
				</InspectorControls>
			) }

			<div { ...innerBlocksProps } />
		</>
	);
}

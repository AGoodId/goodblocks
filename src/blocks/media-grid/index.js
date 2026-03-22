import { registerBlockType } from '@wordpress/blocks';
import './style.scss';
import Edit from './edit';
import metadata from './block.json';
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

registerBlockType( metadata.name, {
	edit: Edit,
	save: ( { attributes } ) => {
		const { columns, gap, layout, height, borderRadius } = attributes;

		const blockProps = useBlockProps.save( {
			'data-layout': layout,
			style: {
				'--columns': columns.toString(),
				'--gap': `${ gap }px`,
				'--item-height': `${ height ?? 300 }px`,
				'--border-radius': borderRadius ? `${ borderRadius }` : '0px',
			},
		} );

		const innerBlocksProps = useInnerBlocksProps.save( blockProps );

		return <div { ...innerBlocksProps } />;
	},
} );

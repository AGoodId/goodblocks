import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, RangeControl, TextControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

export default function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps();

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Matterport tour', 'goodblocks' ) }>
					<TextControl
						label={ __( 'Model ID', 'goodblocks' ) }
						value={ attributes.modelId }
						onChange={ ( modelId ) => setAttributes( { modelId } ) }
						help={ __(
							'The value after “m=” in the Matterport URL.',
							'goodblocks'
						) }
					/>
					<TextControl
						label={ __( 'Eyebrow', 'goodblocks' ) }
						value={ attributes.eyebrow }
						onChange={ ( eyebrow ) => setAttributes( { eyebrow } ) }
					/>
					<TextControl
						label={ __( 'Heading', 'goodblocks' ) }
						value={ attributes.title }
						onChange={ ( title ) => setAttributes( { title } ) }
					/>
					<TextControl
						label={ __( 'Description', 'goodblocks' ) }
						value={ attributes.description }
						onChange={ ( description ) =>
							setAttributes( { description } )
						}
					/>
					<RangeControl
						label={ __( 'Height', 'goodblocks' ) }
						value={ attributes.height }
						min={ 320 }
						max={ 1000 }
						step={ 20 }
						onChange={ ( height ) => setAttributes( { height } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				{ attributes.modelId ? (
					<ServerSideRender
						block="goodblocks/matterport"
						attributes={ attributes }
					/>
				) : (
					<p>
						{ __(
							'Enter a Matterport model ID in the block settings.',
							'goodblocks'
						) }
					</p>
				) }
			</div>
		</>
	);
}

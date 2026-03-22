import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, ToggleControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { __ } from '@wordpress/i18n';

import './editor.scss';

const Edit = ( { attributes, setAttributes } ) => {
	const { parentPostId, showParent } = attributes;

	return (
		<div { ...useBlockProps() }>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'goodblocks' ) }>
					<ToggleControl
						label={ __( 'Show parent', 'goodblocks' ) }
						checked={ !! showParent }
						onChange={ ( newShowParent ) =>
							setAttributes( { showParent: newShowParent } )
						}
						value={ showParent }
					/>
					<TextControl
						label={ __( 'Parent post ID', 'goodblocks' ) }
						value={ parentPostId }
						onChange={ ( value ) => {
							if ( ! isNaN( value ) ) {
								setAttributes( { parentPostId: value } );
							}
						} }
						help={ __(
							'Leave empty (0) to show parent of current page.',
							'goodblocks'
						) }
					/>
				</PanelBody>
			</InspectorControls>
			<ServerSideRender
				block="goodblocks/page-list"
				attributes={ attributes }
			/>
		</div>
	);
};

export default Edit;

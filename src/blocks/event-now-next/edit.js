import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, RangeControl, TextControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import './editor.scss';

export default function Edit( { attributes, setAttributes } ) {
	const { itemsToShow, heading, emptyText } = attributes;
	const blockProps = useBlockProps();

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Now / Next', 'goodblocks' ) }>
					<TextControl
						label={ __( 'Heading', 'goodblocks' ) }
						value={ heading }
						onChange={ ( value ) =>
							setAttributes( { heading: value } )
						}
					/>
					<RangeControl
						label={ __( 'Items to show', 'goodblocks' ) }
						value={ itemsToShow }
						min={ 1 }
						max={ 8 }
						onChange={ ( value ) =>
							setAttributes( { itemsToShow: value } )
						}
					/>
					<TextControl
						label={ __( 'Empty text', 'goodblocks' ) }
						value={ emptyText }
						onChange={ ( value ) =>
							setAttributes( { emptyText: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<ServerSideRender
					block="goodblocks/event-now-next"
					attributes={ attributes }
				/>
			</div>
		</>
	);
}

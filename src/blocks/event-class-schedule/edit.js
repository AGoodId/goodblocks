import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, RangeControl, TextControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import './editor.scss';

export default function Edit( { attributes, setAttributes } ) {
	const { eventsToShow, heading, placeholder, emptyText, noResultsText } =
		attributes;
	const blockProps = useBlockProps();

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Class schedule', 'goodblocks' ) }>
					<TextControl
						label={ __( 'Heading', 'goodblocks' ) }
						value={ heading }
						onChange={ ( value ) =>
							setAttributes( { heading: value } )
						}
					/>
					<TextControl
						label={ __( 'Placeholder', 'goodblocks' ) }
						value={ placeholder }
						onChange={ ( value ) =>
							setAttributes( { placeholder: value } )
						}
					/>
					<RangeControl
						label={ __( 'Maximum rows', 'goodblocks' ) }
						value={ eventsToShow }
						min={ 10 }
						max={ 500 }
						onChange={ ( value ) =>
							setAttributes( { eventsToShow: value } )
						}
					/>
					<TextControl
						label={ __( 'Empty text', 'goodblocks' ) }
						value={ emptyText }
						onChange={ ( value ) =>
							setAttributes( { emptyText: value } )
						}
					/>
					<TextControl
						label={ __( 'No results text', 'goodblocks' ) }
						value={ noResultsText }
						onChange={ ( value ) =>
							setAttributes( { noResultsText: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<ServerSideRender
					block="goodblocks/event-class-schedule"
					attributes={ attributes }
				/>
			</div>
		</>
	);
}

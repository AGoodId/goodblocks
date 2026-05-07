import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import './editor.scss';

export default function Edit( { attributes, setAttributes } ) {
	const { eventsToShow, showPast, showSearch, showTypeFilter, emptyText } =
		attributes;
	const blockProps = useBlockProps();

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Schedule', 'goodblocks' ) }>
					<RangeControl
						label={ __( 'Maximum rows', 'goodblocks' ) }
						value={ eventsToShow }
						min={ 10 }
						max={ 500 }
						onChange={ ( value ) =>
							setAttributes( { eventsToShow: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Include past rows', 'goodblocks' ) }
						checked={ showPast }
						onChange={ ( value ) =>
							setAttributes( { showPast: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show class search', 'goodblocks' ) }
						checked={ showSearch }
						onChange={ ( value ) =>
							setAttributes( { showSearch: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show type filter', 'goodblocks' ) }
						checked={ showTypeFilter }
						onChange={ ( value ) =>
							setAttributes( { showTypeFilter: value } )
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
					block="goodblocks/event-schedule"
					attributes={ attributes }
				/>
			</div>
		</>
	);
}

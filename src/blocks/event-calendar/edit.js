import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	SelectControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import './editor.scss';

export default function Edit( { attributes, setAttributes } ) {
	const {
		defaultView,
		eventsToShow,
		showPast,
		showFilters,
		showViewToggle,
		categorySlug,
		eventType,
		emptyText,
	} = attributes;
	const blockProps = useBlockProps();

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Calendar', 'goodblocks' ) }>
					<SelectControl
						label={ __( 'Default view', 'goodblocks' ) }
						value={ defaultView }
						options={ [
							{
								label: __( 'Month', 'goodblocks' ),
								value: 'month',
							},
							{
								label: __( 'Agenda', 'goodblocks' ),
								value: 'agenda',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { defaultView: value } )
						}
					/>
					<RangeControl
						label={ __( 'Maximum occurrences', 'goodblocks' ) }
						value={ eventsToShow }
						min={ 10 }
						max={ 1000 }
						onChange={ ( value ) =>
							setAttributes( { eventsToShow: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Include past occurrences', 'goodblocks' ) }
						checked={ showPast }
						onChange={ ( value ) =>
							setAttributes( { showPast: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show view toggle', 'goodblocks' ) }
						checked={ showViewToggle }
						onChange={ ( value ) =>
							setAttributes( { showViewToggle: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show filters', 'goodblocks' ) }
						checked={ showFilters }
						onChange={ ( value ) =>
							setAttributes( { showFilters: value } )
						}
					/>
					<TextControl
						label={ __( 'Category slug', 'goodblocks' ) }
						value={ categorySlug }
						onChange={ ( value ) =>
							setAttributes( { categorySlug: value } )
						}
					/>
					<TextControl
						label={ __( 'Event type', 'goodblocks' ) }
						value={ eventType }
						onChange={ ( value ) =>
							setAttributes( { eventType: value } )
						}
						help={ __(
							'Use qualification, semifinal, final, award, training, or other. Leave empty to show all.',
							'goodblocks'
						) }
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
					block="goodblocks/event-calendar"
					attributes={ attributes }
				/>
			</div>
		</>
	);
}

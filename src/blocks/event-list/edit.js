import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	RangeControl,
	ToggleControl,
	TextControl,
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import './editor.scss';

const Edit = ( { attributes, setAttributes } ) => {
	const {
		viewMode,
		eventsToShow,
		eventsPerRow,
		showPast,
		categorySlug,
		showFeaturedImage,
		showExcerpt,
		excerptLength,
		noEventsText,
	} = attributes;

	const blockProps = useBlockProps();

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Layout', 'goodblocks' ) }>
					<SelectControl
						label={ __( 'View mode', 'goodblocks' ) }
						value={ viewMode }
						options={ [
							{ label: __( 'Grid', 'goodblocks' ), value: 'grid' },
							{ label: __( 'List', 'goodblocks' ), value: 'list' },
						] }
						onChange={ ( val ) => setAttributes( { viewMode: val } ) }
					/>
					{ viewMode === 'grid' && (
						<RangeControl
							label={ __( 'Columns', 'goodblocks' ) }
							value={ eventsPerRow }
							min={ 1 }
							max={ 6 }
							onChange={ ( val ) => setAttributes( { eventsPerRow: val } ) }
						/>
					) }
					<RangeControl
						label={ __( 'Events to show', 'goodblocks' ) }
						value={ eventsToShow }
						min={ 1 }
						max={ 48 }
						onChange={ ( val ) => setAttributes( { eventsToShow: val } ) }
					/>
				</PanelBody>
				<PanelBody title={ __( 'Content', 'goodblocks' ) } initialOpen={ false }>
					<ToggleControl
						label={ __( 'Show featured image', 'goodblocks' ) }
						checked={ showFeaturedImage }
						onChange={ ( val ) => setAttributes( { showFeaturedImage: val } ) }
					/>
					<ToggleControl
						label={ __( 'Show excerpt', 'goodblocks' ) }
						checked={ showExcerpt }
						onChange={ ( val ) => setAttributes( { showExcerpt: val } ) }
					/>
					{ showExcerpt && (
						<RangeControl
							label={ __( 'Excerpt length', 'goodblocks' ) }
							value={ excerptLength }
							min={ 20 }
							max={ 300 }
							onChange={ ( val ) => setAttributes( { excerptLength: val } ) }
						/>
					) }
				</PanelBody>
				<PanelBody title={ __( 'Filter', 'goodblocks' ) } initialOpen={ false }>
					<ToggleControl
						label={ __( 'Show past events', 'goodblocks' ) }
						checked={ showPast }
						onChange={ ( val ) => setAttributes( { showPast: val } ) }
					/>
					<TextControl
						label={ __( 'Category slug', 'goodblocks' ) }
						value={ categorySlug }
						onChange={ ( val ) => setAttributes( { categorySlug: val } ) }
						help={ __( 'Filter by event_category slug. Leave empty to show all.', 'goodblocks' ) }
					/>
					<TextControl
						label={ __( 'No events text', 'goodblocks' ) }
						value={ noEventsText }
						onChange={ ( val ) => setAttributes( { noEventsText: val } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<ServerSideRender
					block="goodblocks/event-list"
					attributes={ attributes }
				/>
			</div>
		</>
	);
};

export default Edit;

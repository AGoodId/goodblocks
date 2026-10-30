import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	CheckboxControl,
	Notice,
	PanelBody,
	RangeControl,
	SelectControl,
	Spinner,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import ServerSideRender from '@wordpress/server-side-render';

export default function Edit( { attributes, setAttributes } ) {
	const {
		sourceIds,
		postsToShow,
		layout,
		columns,
		gap,
		aspectRatio,
		showImage,
		showExcerpt,
		showDate,
		showSource,
		sourceLabel,
		dateLabel,
		openInNewTab,
		fallbackText,
	} = attributes;
	const [ sources, setSources ] = useState( [] );
	const [ loadingSources, setLoadingSources ] = useState( true );
	const [ sourceError, setSourceError ] = useState( false );
	const blockProps = useBlockProps();

	useEffect( () => {
		let active = true;

		apiFetch( { path: '/goodblocks/v1/rss-sources' } )
			.then( ( response ) => {
				if ( active ) {
					setSources( Array.isArray( response ) ? response : [] );
					setSourceError( false );
				}
			} )
			.catch( () => {
				if ( active ) {
					setSourceError( true );
				}
			} )
			.finally( () => {
				if ( active ) {
					setLoadingSources( false );
				}
			} );

		return () => {
			active = false;
		};
	}, [] );

	const toggleSource = ( sourceId, enabled ) => {
		const selected = new Set( sourceIds );
		if ( enabled ) {
			selected.add( sourceId );
		} else {
			selected.delete( sourceId );
		}
		setAttributes( { sourceIds: Array.from( selected ) } );
	};

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Sources', 'goodblocks' ) }>
					<p>
						{ __(
							'Leave every source unchecked to show all configured sources.',
							'goodblocks'
						) }
					</p>
					{ loadingSources && <Spinner /> }
					{ sourceError && (
						<Notice status="error" isDismissible={ false }>
							{ __(
								'Could not load the configured RSS sources.',
								'goodblocks'
							) }
						</Notice>
					) }
					{ ! loadingSources &&
						! sourceError &&
						sources.length === 0 && (
							<Notice status="info" isDismissible={ false }>
								{ __(
									'Add RSS sources under Settings → RSS Feeds, then refresh the feed cache.',
									'goodblocks'
								) }
							</Notice>
						) }
					{ sources.map( ( source ) => (
						<CheckboxControl
							key={ source.id }
							label={ source.name }
							help={ `${ source.item_count } ${ __(
								'cached items',
								'goodblocks'
							) }` }
							checked={ sourceIds.includes( source.id ) }
							onChange={ ( enabled ) =>
								toggleSource( source.id, enabled )
							}
						/>
					) ) }
				</PanelBody>

				<PanelBody title={ __( 'Layout', 'goodblocks' ) }>
					<RangeControl
						label={ __( 'Number of items', 'goodblocks' ) }
						value={ postsToShow }
						min={ 1 }
						max={ 24 }
						onChange={ ( value ) =>
							setAttributes( { postsToShow: value } )
						}
					/>
					<SelectControl
						label={ __( 'Layout', 'goodblocks' ) }
						value={ layout }
						options={ [
							{
								label: __( 'Grid', 'goodblocks' ),
								value: 'grid',
							},
							{
								label: __( 'List', 'goodblocks' ),
								value: 'list',
							},
							{
								label: __( 'Compact list', 'goodblocks' ),
								value: 'compact',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { layout: value } )
						}
					/>
					{ layout === 'grid' && (
						<RangeControl
							label={ __( 'Columns', 'goodblocks' ) }
							value={ columns }
							min={ 1 }
							max={ 4 }
							onChange={ ( value ) =>
								setAttributes( { columns: value } )
							}
						/>
					) }
					<RangeControl
						label={ __( 'Gap', 'goodblocks' ) }
						value={ gap }
						min={ 0 }
						max={ 64 }
						onChange={ ( value ) =>
							setAttributes( { gap: value } )
						}
					/>
					<SelectControl
						label={ __( 'Image format', 'goodblocks' ) }
						value={ aspectRatio }
						options={ [
							{ label: '16:9', value: '16/9' },
							{ label: '4:3', value: '4/3' },
							{ label: '1:1', value: '1/1' },
						] }
						onChange={ ( value ) =>
							setAttributes( { aspectRatio: value } )
						}
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'Content', 'goodblocks' ) }
					initialOpen={ false }
				>
					<ToggleControl
						label={ __( 'Show images', 'goodblocks' ) }
						checked={ showImage }
						onChange={ ( value ) =>
							setAttributes( { showImage: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show excerpts', 'goodblocks' ) }
						checked={ showExcerpt }
						onChange={ ( value ) =>
							setAttributes( { showExcerpt: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show dates', 'goodblocks' ) }
						checked={ showDate }
						onChange={ ( value ) =>
							setAttributes( { showDate: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show source names', 'goodblocks' ) }
						checked={ showSource }
						onChange={ ( value ) =>
							setAttributes( { showSource: value } )
						}
					/>
					{ layout === 'compact' && showSource && (
						<TextControl
							label={ __( 'Source label', 'goodblocks' ) }
							value={ sourceLabel }
							onChange={ ( value ) =>
								setAttributes( { sourceLabel: value } )
							}
						/>
					) }
					{ layout === 'compact' && showDate && (
						<TextControl
							label={ __( 'Date label', 'goodblocks' ) }
							value={ dateLabel }
							onChange={ ( value ) =>
								setAttributes( { dateLabel: value } )
							}
						/>
					) }
					<ToggleControl
						label={ __( 'Open links in a new tab', 'goodblocks' ) }
						checked={ openInNewTab }
						onChange={ ( value ) =>
							setAttributes( { openInNewTab: value } )
						}
					/>
					<TextControl
						label={ __( 'Fallback text', 'goodblocks' ) }
						value={ fallbackText }
						onChange={ ( value ) =>
							setAttributes( { fallbackText: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<ServerSideRender
					block="goodblocks/rss-feed"
					attributes={ attributes }
				/>
			</div>
		</>
	);
}

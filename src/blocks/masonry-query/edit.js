/**
 * Masonry Query Block - Editor Component
 */

import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls, useSettings } from '@wordpress/block-editor';
import {
	PanelBody,
	PanelRow,
	SelectControl,
	RangeControl,
	ToggleControl,
	CheckboxControl,
	TextControl,
	ButtonGroup,
	Button,
	__experimentalNumberControl as NumberControl,
	Spinner,
	Placeholder,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';
import { useInstanceId } from '@wordpress/compose';
import ServerSideRender from '@wordpress/server-side-render';
import { grid } from '@wordpress/icons';

export default function Edit( { attributes, setAttributes, clientId } ) {
	// Theme.json custom.masonry = single source of truth for visual defaults.
	// Block attributes override per-instance; undefined falls through to theme.json.
	const [ masonryDefaults ] = useSettings( 'custom.masonry' );
	const defaults = masonryDefaults || {};
	const d = ( key, fallback ) => defaults[ key ] ?? fallback;

	const {
		// Data source (per-instance, keep block.json defaults)
		queryType,
		postType,
		postTypes,
		postsPerPage,
		offset,
		orderBy,
		order,
		// Visual / layout (inherit from theme.json → hardcoded fallback)
		columns = d( 'columns', { desktop: 3, tablet: 2, mobile: 1 } ),
		gap = d( 'gap', 4 ),
		imageRatio = d( 'imageRatio', 'original' ),
		imageFit = d( 'imageFit', 'cover' ),
		imageSource = d( 'imageSource', 'featured' ),
		showTitle = d( 'showTitle', true ),
		showExcerpt = d( 'showExcerpt', false ),
		showCategory = d( 'showCategory', false ),
		showDate = d( 'showDate', false ),
		overlayStyle = d( 'overlayStyle', 'solid' ),
		overlayPosition = d( 'overlayPosition', 'full' ),
		overlayVisibility = d( 'overlayVisibility', 'hover' ),
		overlayFontFamily = d( 'overlayFontFamily', 'body' ),
		hoverEffect = d( 'hoverEffect', 'zoom' ),
		borderRadius = d( 'borderRadius', 'none' ),
		clickAction = d( 'clickAction', 'link' ),
		linkTarget = d( 'linkTarget', '_self' ),
		// Lightbox (per-instance)
		enableLightbox,
		lightboxAnimation,
		// Category display
		categoryTaxonomy,
		// Filtering (per-instance toggles; visual settings inherit)
		enableFiltering,
		filterTaxonomy,
		filterStyle = d( 'filterStyle', 'buttons' ),
		filterAllText = d( 'filterAllText', 'Alla' ),
		// Pagination (per-instance)
		enablePagination,
		paginationType,
		loadMoreText,
		// Animation (inherit)
		enableAnimation = d( 'enableAnimation', true ),
		animationType = d( 'animationType', 'fade-up' ),
		animationStagger = d( 'animationStagger', 50 ),
	} = attributes;

	const instanceId = useInstanceId( Edit );

	// Set unique block ID + migrate old string gap to number
	const presetToPx = { xs: 4, sm: 8, md: 16, lg: 24, xl: 40 };
	useEffect( () => {
		if ( ! attributes.blockId ) {
			setAttributes( { blockId: `masonry-${ instanceId }` } );
		}
		if ( typeof attributes.gap === 'string' ) {
			setAttributes( { gap: presetToPx[ attributes.gap ] ?? 16 } );
		}
	}, [ instanceId ] );

	// Get available post types
	const availablePostTypes = useSelect( ( select ) => {
		const types = select( 'core' ).getPostTypes( { per_page: -1 } );
		if ( ! types ) return [];
		return types
			.filter( ( type ) => type.viewable && type.slug !== 'attachment' )
			.map( ( type ) => ( {
				label: type.labels.singular_name,
				value: type.slug,
			} ) );
	}, [] );

	// Get taxonomies for selected post type(s)
	const taxonomies = useSelect(
		( select ) => {
			const allTaxonomies = select( 'core' ).getTaxonomies( { per_page: -1 } );
			if ( ! allTaxonomies ) return [];

			const typesToCheck = queryType === 'mixed'
				? ( attributes.postTypes || [] )
				: [ postType ];

			return allTaxonomies
				.filter( ( tax ) => tax.visibility?.show_ui && typesToCheck.some( ( t ) => tax.types.includes( t ) ) )
				.map( ( tax ) => ( {
					label: tax.labels.singular_name,
					value: tax.slug,
				} ) );
		},
		[ postType, queryType, attributes.postTypes ]
	);

	const blockProps = useBlockProps( {
		className: 'masonry-query-editor',
	} );

	// (gap is now a number in pixels — no preset options needed)

	// Border radius options
	const radiusOptions = [
		{ label: __( 'Ingen', 'goodblocks' ), value: 'none' },
		{ label: 'SM', value: 'sm' },
		{ label: 'MD', value: 'md' },
		{ label: 'LG', value: 'lg' },
		{ label: 'Full', value: 'full' },
	];

	// Image ratio options
	const ratioOptions = [
		{ label: __( 'Original', 'goodblocks' ), value: 'original' },
		{ label: '1:1', value: '1:1' },
		{ label: '4:5', value: '4:5' },
		{ label: '3:4', value: '3:4' },
		{ label: '2:3', value: '2:3' },
		{ label: '4:3', value: '4:3' },
		{ label: '3:2', value: '3:2' },
		{ label: '16:9', value: '16:9' },
		{ label: '9:16', value: '9:16' },
	];

	return (
		<>
			<InspectorControls>
				{ /* DATA SOURCE */ }
				<PanelBody title={ __( 'Datakälla', 'goodblocks' ) } initialOpen={ true }>
					<SelectControl
						label={ __( 'Innehållstyp', 'goodblocks' ) }
						value={ queryType }
						options={ [
							{ label: __( 'Inlägg', 'goodblocks' ), value: 'posts' },
							{ label: __( 'Sidor', 'goodblocks' ), value: 'pages' },
							{ label: __( 'Media', 'goodblocks' ), value: 'media' },
							{ label: __( 'Anpassad posttyp', 'goodblocks' ), value: 'custom' },
							{ label: __( 'Blandad (flera typer)', 'goodblocks' ), value: 'mixed' },
						] }
						onChange={ ( value ) => setAttributes( { queryType: value } ) }
					/>

					{ queryType === 'custom' && availablePostTypes.length > 0 && (
						<SelectControl
							label={ __( 'Posttyp', 'goodblocks' ) }
							value={ postType }
							options={ availablePostTypes }
							onChange={ ( value ) => setAttributes( { postType: value } ) }
						/>
					) }

					{ queryType === 'mixed' && availablePostTypes.length > 0 && (
						<div>
							<p className="components-base-control__label">
								{ __( 'Välj posttyper', 'goodblocks' ) }
							</p>
							{ availablePostTypes.map( ( pt ) => (
								<CheckboxControl
									key={ pt.value }
									label={ pt.label }
									checked={ ( attributes.postTypes || [] ).includes( pt.value ) }
									onChange={ ( checked ) => {
										const current = [ ...( attributes.postTypes || [] ) ];
										if ( checked && ! current.includes( pt.value ) ) {
											current.push( pt.value );
										} else if ( ! checked ) {
											const idx = current.indexOf( pt.value );
											if ( idx > -1 ) current.splice( idx, 1 );
										}
										setAttributes( { postTypes: current } );
									} }
								/>
							) ) }
						</div>
					) }

					<RangeControl
						label={ __( 'Antal', 'goodblocks' ) }
						value={ postsPerPage }
						onChange={ ( value ) => setAttributes( { postsPerPage: value } ) }
						min={ 1 }
						max={ 50 }
					/>

					<RangeControl
						label={ __( 'Hoppa över (offset)', 'goodblocks' ) }
						value={ offset }
						onChange={ ( value ) => setAttributes( { offset: value } ) }
						min={ 0 }
						max={ 20 }
					/>

					<SelectControl
						label={ __( 'Sortera efter', 'goodblocks' ) }
						value={ orderBy }
						options={ [
							{ label: __( 'Datum', 'goodblocks' ), value: 'date' },
							{ label: __( 'Titel', 'goodblocks' ), value: 'title' },
							{ label: __( 'Senast ändrad', 'goodblocks' ), value: 'modified' },
							{ label: __( 'Slumpmässig', 'goodblocks' ), value: 'rand' },
							{ label: __( 'Menyordning', 'goodblocks' ), value: 'menu_order' },
						] }
						onChange={ ( value ) => setAttributes( { orderBy: value } ) }
					/>

					<SelectControl
						label={ __( 'Ordning', 'goodblocks' ) }
						value={ order }
						options={ [
							{ label: __( 'Fallande (nyast först)', 'goodblocks' ), value: 'DESC' },
							{ label: __( 'Stigande (äldst först)', 'goodblocks' ), value: 'ASC' },
						] }
						onChange={ ( value ) => setAttributes( { order: value } ) }
					/>
				</PanelBody>

				{ /* LAYOUT */ }
				<PanelBody title={ __( 'Layout', 'goodblocks' ) } initialOpen={ false }>
					<p className="components-base-control__label">{ __( 'Kolumner', 'goodblocks' ) }</p>

					<RangeControl
						label={ __( 'Desktop', 'goodblocks' ) }
						value={ columns.desktop }
						onChange={ ( value ) =>
							setAttributes( { columns: { ...columns, desktop: value } } )
						}
						min={ 1 }
						max={ 6 }
					/>

					<RangeControl
						label={ __( 'Tablet', 'goodblocks' ) }
						value={ columns.tablet }
						onChange={ ( value ) =>
							setAttributes( { columns: { ...columns, tablet: value } } )
						}
						min={ 1 }
						max={ 4 }
					/>

					<RangeControl
						label={ __( 'Mobil', 'goodblocks' ) }
						value={ columns.mobile }
						onChange={ ( value ) =>
							setAttributes( { columns: { ...columns, mobile: value } } )
						}
						min={ 1 }
						max={ 3 }
					/>

					<hr style={ { margin: '16px 0', borderTop: '1px solid #e0e0e0', borderBottom: 'none' } } />

					<NumberControl
						label={ __( 'Mellanrum (px)', 'goodblocks' ) }
						value={ gap }
						onChange={ ( value ) => setAttributes( { gap: parseInt( value, 10 ) || 0 } ) }
						min={ 0 }
						max={ 200 }
						suffix="px"
					/>

					<SelectControl
						label={ __( 'Hörnradie', 'goodblocks' ) }
						value={ borderRadius }
						options={ radiusOptions }
						onChange={ ( value ) => setAttributes( { borderRadius: value } ) }
					/>
				</PanelBody>

				{ /* IMAGE SETTINGS */ }
				<PanelBody title={ __( 'Bildinställningar', 'goodblocks' ) } initialOpen={ false }>
					<SelectControl
						label={ __( 'Bildproportioner', 'goodblocks' ) }
						value={ imageRatio }
						options={ ratioOptions }
						onChange={ ( value ) => setAttributes( { imageRatio: value } ) }
						help={
							imageRatio === 'original'
								? __( 'Masonry-layout med varierade höjder', 'goodblocks' )
								: __( 'Alla bilder får samma proportioner', 'goodblocks' )
						}
					/>

					{ imageRatio !== 'original' && (
						<SelectControl
							label={ __( 'Bildfyllning', 'goodblocks' ) }
							value={ imageFit }
							options={ [
								{ label: __( 'Fyll (beskär)', 'goodblocks' ), value: 'cover' },
								{ label: __( 'Anpassa (visa hela)', 'goodblocks' ), value: 'contain' },
							] }
							onChange={ ( value ) => setAttributes( { imageFit: value } ) }
						/>
					) }

					<SelectControl
						label={ __( 'Bildkälla', 'goodblocks' ) }
						value={ imageSource }
						options={ [
							{ label: __( 'Utvald bild', 'goodblocks' ), value: 'featured' },
							{ label: __( 'Första bilden i innehåll', 'goodblocks' ), value: 'first' },
							{ label: __( 'ACF-fält', 'goodblocks' ), value: 'acf' },
						] }
						onChange={ ( value ) => setAttributes( { imageSource: value } ) }
					/>

					{ imageSource === 'acf' && (
						<TextControl
							label={ __( 'ACF-fältnamn', 'goodblocks' ) }
							value={ attributes.acfImageField }
							onChange={ ( value ) => setAttributes( { acfImageField: value } ) }
							placeholder="hero_image"
						/>
					) }
				</PanelBody>

				{ /* DISPLAY */ }
				<PanelBody title={ __( 'Visa information', 'goodblocks' ) } initialOpen={ false }>
					<ToggleControl
						label={ __( 'Visa titel', 'goodblocks' ) }
						checked={ showTitle }
						onChange={ ( value ) => setAttributes( { showTitle: value } ) }
					/>

					<ToggleControl
						label={ __( 'Visa kategori', 'goodblocks' ) }
						checked={ showCategory }
						onChange={ ( value ) => setAttributes( { showCategory: value } ) }
					/>

					{ showCategory && (
						<TextControl
							label={ __( 'Taxonomi för kategori-etikett', 'goodblocks' ) }
							help={ __( 'T.ex. portfolio_client, category. Lämna tomt = filtertaxonomi.', 'goodblocks' ) }
							value={ categoryTaxonomy || '' }
							onChange={ ( value ) => setAttributes( { categoryTaxonomy: value } ) }
						/>
					) }

					<ToggleControl
						label={ __( 'Visa utdrag', 'goodblocks' ) }
						checked={ showExcerpt }
						onChange={ ( value ) => setAttributes( { showExcerpt: value } ) }
					/>

					<ToggleControl
						label={ __( 'Visa datum', 'goodblocks' ) }
						checked={ showDate }
						onChange={ ( value ) => setAttributes( { showDate: value } ) }
					/>

					<hr style={ { margin: '16px 0', borderTop: '1px solid #e0e0e0', borderBottom: 'none' } } />

					<SelectControl
						label={ __( 'Overlay-stil', 'goodblocks' ) }
						value={ overlayStyle }
						options={ [
							{ label: __( 'Ingen', 'goodblocks' ), value: 'none' },
							{ label: __( 'Gradient', 'goodblocks' ), value: 'gradient' },
							{ label: __( 'Solid', 'goodblocks' ), value: 'solid' },
							{ label: __( 'Blur', 'goodblocks' ), value: 'blur' },
						] }
						onChange={ ( value ) => setAttributes( { overlayStyle: value } ) }
					/>

					{ overlayStyle !== 'none' && (
						<>
							<SelectControl
								label={ __( 'Overlay-position', 'goodblocks' ) }
								value={ overlayPosition }
								options={ [
									{ label: __( 'Nederkant', 'goodblocks' ), value: 'bottom' },
									{ label: __( 'Hela bilden', 'goodblocks' ), value: 'full' },
									{ label: __( 'Centrerad', 'goodblocks' ), value: 'center' },
									{ label: __( 'Under bilden', 'goodblocks' ), value: 'below' },
								] }
								onChange={ ( value ) => setAttributes( { overlayPosition: value } ) }
							/>

							<SelectControl
								label={ __( 'Visa overlay', 'goodblocks' ) }
								value={ overlayVisibility }
								options={ [
									{ label: __( 'Alltid', 'goodblocks' ), value: 'always' },
									{ label: __( 'Vid hover', 'goodblocks' ), value: 'hover' },
								] }
								onChange={ ( value ) => setAttributes( { overlayVisibility: value } ) }
							/>

							<SelectControl
								label={ __( 'Typsnitt (overlay)', 'goodblocks' ) }
								value={ overlayFontFamily }
								options={ [
									{ label: __( 'Standard (ärv)', 'goodblocks' ), value: '' },
									{ label: 'Tiempos Headline', value: 'heading' },
									{ label: 'Tiempos Text', value: 'serif' },
									{ label: 'Untitled Sans', value: 'body' },
									{ label: 'Söhne', value: 'soehne' },
								] }
								onChange={ ( value ) => setAttributes( { overlayFontFamily: value } ) }
							/>
						</>
					) }
				</PanelBody>

				{ /* HOVER EFFECTS */ }
				<PanelBody title={ __( 'Hover-effekter', 'goodblocks' ) } initialOpen={ false }>
					<SelectControl
						label={ __( 'Bildeffekt', 'goodblocks' ) }
						value={ hoverEffect }
						options={ [
							{ label: __( 'Ingen', 'goodblocks' ), value: 'none' },
							{ label: __( 'Zooma', 'goodblocks' ), value: 'zoom' },
							{ label: __( 'Lyft', 'goodblocks' ), value: 'lift' },
							{ label: __( 'Gråskala', 'goodblocks' ), value: 'grayscale' },
							{ label: __( 'Ljusare', 'goodblocks' ), value: 'brightness' },
						] }
						onChange={ ( value ) => setAttributes( { hoverEffect: value } ) }
					/>
				</PanelBody>

				{ /* CLICK ACTION */ }
				<PanelBody title={ __( 'Klickbeteende', 'goodblocks' ) } initialOpen={ false }>
					<SelectControl
						label={ __( 'Vid klick', 'goodblocks' ) }
						value={ clickAction }
						options={ [
							{ label: __( 'Öppna lightbox', 'goodblocks' ), value: 'lightbox' },
							{ label: __( 'Gå till inlägg', 'goodblocks' ), value: 'link' },
							{ label: __( 'Ingen', 'goodblocks' ), value: 'none' },
						] }
						onChange={ ( value ) => setAttributes( { clickAction: value } ) }
					/>

					{ clickAction === 'lightbox' && (
						<>
							<SelectControl
								label={ __( 'Lightbox-animation', 'goodblocks' ) }
								value={ lightboxAnimation }
								options={ [
									{ label: __( 'Fade', 'goodblocks' ), value: 'fade' },
									{ label: __( 'Zoom', 'goodblocks' ), value: 'zoom' },
									{ label: __( 'Slide', 'goodblocks' ), value: 'slide' },
								] }
								onChange={ ( value ) => setAttributes( { lightboxAnimation: value } ) }
							/>

							<ToggleControl
								label={ __( 'Visa info i lightbox', 'goodblocks' ) }
								checked={ attributes.lightboxShowInfo }
								onChange={ ( value ) => setAttributes( { lightboxShowInfo: value } ) }
							/>

							<ToggleControl
								label={ __( 'Visa länk till inlägg', 'goodblocks' ) }
								checked={ attributes.lightboxShowLink }
								onChange={ ( value ) => setAttributes( { lightboxShowLink: value } ) }
							/>
						</>
					) }

					{ clickAction === 'link' && (
						<SelectControl
							label={ __( 'Öppna i', 'goodblocks' ) }
							value={ linkTarget }
							options={ [
								{ label: __( 'Samma fönster', 'goodblocks' ), value: '_self' },
								{ label: __( 'Nytt fönster', 'goodblocks' ), value: '_blank' },
							] }
							onChange={ ( value ) => setAttributes( { linkTarget: value } ) }
						/>
					) }
				</PanelBody>

				{ /* FILTERING */ }
				<PanelBody title={ __( 'Filtrering', 'goodblocks' ) } initialOpen={ false }>
					<ToggleControl
						label={ __( 'Aktivera filternavigering', 'goodblocks' ) }
						checked={ enableFiltering }
						onChange={ ( value ) => setAttributes( { enableFiltering: value } ) }
					/>

					{ enableFiltering && (
						<>
							{ taxonomies.length > 0 && (
								<SelectControl
									label={ __( 'Filtrera på', 'goodblocks' ) }
									value={ filterTaxonomy }
									options={ taxonomies }
									onChange={ ( value ) => setAttributes( { filterTaxonomy: value } ) }
								/>
							) }

							<SelectControl
								label={ __( 'Filterstil', 'goodblocks' ) }
								value={ filterStyle }
								options={ [
									{ label: __( 'Tabs', 'goodblocks' ), value: 'tabs' },
									{ label: __( 'Knappar', 'goodblocks' ), value: 'buttons' },
									{ label: __( 'Dropdown', 'goodblocks' ), value: 'dropdown' },
								] }
								onChange={ ( value ) => setAttributes( { filterStyle: value } ) }
							/>

							<TextControl
								label={ __( 'Text för "Alla"', 'goodblocks' ) }
								value={ filterAllText }
								onChange={ ( value ) => setAttributes( { filterAllText: value } ) }
							/>
						</>
					) }
				</PanelBody>

				{ /* PAGINATION */ }
				<PanelBody title={ __( 'Pagination', 'goodblocks' ) } initialOpen={ false }>
					<ToggleControl
						label={ __( 'Aktivera pagination', 'goodblocks' ) }
						checked={ enablePagination }
						onChange={ ( value ) => setAttributes( { enablePagination: value } ) }
					/>

					{ enablePagination && (
						<>
							<SelectControl
								label={ __( 'Paginationstyp', 'goodblocks' ) }
								value={ paginationType }
								options={ [
									{ label: __( 'Ladda fler-knapp', 'goodblocks' ), value: 'load-more' },
									{ label: __( 'Infinite scroll', 'goodblocks' ), value: 'infinite' },
									{ label: __( 'Numrerade sidor', 'goodblocks' ), value: 'numbered' },
								] }
								onChange={ ( value ) => setAttributes( { paginationType: value } ) }
							/>

							{ paginationType === 'load-more' && (
								<TextControl
									label={ __( 'Knapptext', 'goodblocks' ) }
									value={ loadMoreText }
									onChange={ ( value ) => setAttributes( { loadMoreText: value } ) }
								/>
							) }
						</>
					) }
				</PanelBody>

				{ /* ANIMATION */ }
				<PanelBody title={ __( 'Animation', 'goodblocks' ) } initialOpen={ false }>
					<ToggleControl
						label={ __( 'Aktivera inladdningsanimation', 'goodblocks' ) }
						checked={ enableAnimation }
						onChange={ ( value ) => setAttributes( { enableAnimation: value } ) }
					/>

					{ enableAnimation && (
						<>
							<SelectControl
								label={ __( 'Animationstyp', 'goodblocks' ) }
								value={ animationType }
								options={ [
									{ label: __( 'Fade up', 'goodblocks' ), value: 'fade-up' },
									{ label: __( 'Fade', 'goodblocks' ), value: 'fade' },
									{ label: __( 'Scale', 'goodblocks' ), value: 'scale' },
									{ label: __( 'Slide up', 'goodblocks' ), value: 'slide-up' },
								] }
								onChange={ ( value ) => setAttributes( { animationType: value } ) }
							/>

							<RangeControl
								label={ __( 'Stagger-delay (ms)', 'goodblocks' ) }
								value={ animationStagger }
								onChange={ ( value ) => setAttributes( { animationStagger: value } ) }
								min={ 0 }
								max={ 200 }
								step={ 10 }
							/>
						</>
					) }
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<ServerSideRender
					block="goodblocks/masonry-query"
					attributes={ attributes }
					EmptyResponsePlaceholder={ () => (
						<Placeholder
							icon={ grid }
							label={ __( 'Masonry Query', 'goodblocks' ) }
							instructions={ __( 'Inga resultat hittades. Justera dina inställningar.', 'goodblocks' ) }
						/>
					) }
					LoadingResponsePlaceholder={ () => (
						<Placeholder
							icon={ grid }
							label={ __( 'Masonry Query', 'goodblocks' ) }
						>
							<Spinner />
						</Placeholder>
					) }
				/>
			</div>
		</>
	);
}

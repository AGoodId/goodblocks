import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	RangeControl,
	SelectControl,
	ComboboxControl,
	ToggleControl,
	FormTokenField,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import ServerSideRender from '@wordpress/server-side-render';
import { useState, useEffect } from '@wordpress/element';

function decodeEntities( str ) {
	const txt = document.createElement( 'textarea' );
	txt.innerHTML = str;
	return txt.value;
}

export default function Edit( { attributes, setAttributes } ) {
	const {
		rubrik,
		text,
		productsToShow,
		category,
		tag,
		formgivare,
		orderBy,
		manualMode = false,
		manualProducts = [],
	} = attributes;

	const [ categorySearch, setCategorySearch ] = useState( '' );
	const [ tagSearch, setTagSearch ] = useState( '' );
	const [ formgivareSearch, setFormgivareSearch ] = useState( '' );
	const [ productSearch, setProductSearch ] = useState( '' );
	const [ productSuggestions, setProductSuggestions ] = useState( [] );
	const [ manualProductObjects, setManualProductObjects ] = useState( [] );
	const [ lastManualProductIds, setLastManualProductIds ] = useState( [] );

	const categories = useSelect(
		( select ) =>
			select( 'core' ).getEntityRecords( 'taxonomy', 'product_cat', {
				per_page: 100,
				search: categorySearch,
			} ) || [],
		[ categorySearch ]
	);

	const tags = useSelect(
		( select ) =>
			select( 'core' ).getEntityRecords( 'taxonomy', 'product_tag', {
				per_page: 100,
				search: tagSearch,
			} ) || [],
		[ tagSearch ]
	);

	const [ formgivareTerms, setFormgivareTerms ] = useState( [] );
	useEffect( () => {
		const nonce = window.wpApiSettings ? window.wpApiSettings.nonce : '';
		fetch(
			`/wp-json/wc/v3/products/attributes/6/terms?per_page=100&search=${ encodeURIComponent(
				formgivareSearch
			) }`,
			{ headers: { 'X-WP-Nonce': nonce } }
		)
			.then( ( res ) => res.json() )
			.then( ( data ) => {
				setFormgivareTerms(
					Array.isArray( data )
						? data.map( ( f ) => ( {
								value: f.slug,
								label: decodeEntities( f.name ),
						  } ) )
						: []
				);
			} )
			.catch( () => setFormgivareTerms( [] ) );
	}, [ formgivareSearch ] );

	useEffect( () => {
		if ( ! productSearch ) {
			setProductSuggestions( [] );
			return;
		}
		const nonce = window.wpApiSettings ? window.wpApiSettings.nonce : '';
		fetch(
			`/wp-json/wc/v3/products?per_page=20&search=${ encodeURIComponent(
				productSearch
			) }`,
			{ headers: { 'X-WP-Nonce': nonce } }
		)
			.then( ( res ) => res.json() )
			.then( ( data ) => {
				setProductSuggestions(
					Array.isArray( data )
						? data.map(
								( p ) =>
									`${ decodeEntities( p.name ) } - ${ p.id }`
						  )
						: []
				);
			} )
			.catch( () => setProductSuggestions( [] ) );
	}, [ productSearch ] );

	useEffect( () => {
		const idsChanged =
			manualProducts.length !== lastManualProductIds.length ||
			manualProducts.some(
				( id, i ) => id !== lastManualProductIds[ i ]
			);
		if ( ! manualProducts.length ) {
			setManualProductObjects( [] );
			setLastManualProductIds( [] );
			return;
		}
		if ( ! idsChanged ) {
			setManualProductObjects( ( prev ) =>
				manualProducts
					.map( ( id ) => prev.find( ( p ) => p.id === id ) )
					.filter( Boolean )
			);
			return;
		}

		setLastManualProductIds( [ ...manualProducts ] );
		const nonce = window.wpApiSettings ? window.wpApiSettings.nonce : '';
		fetch(
			`/wp-json/wc/v3/products?include=${ manualProducts.join(
				','
			) }&per_page=100`,
			{ headers: { 'X-WP-Nonce': nonce } }
		)
			.then( ( res ) => res.json() )
			.then( ( data ) => {
				const sorted = manualProducts
					.map( ( id ) => data.find( ( p ) => p.id === id ) )
					.filter( Boolean );
				setManualProductObjects( sorted );
			} )
			.catch( () => {} );
	}, [ manualProducts ] );

	function handleManualProductsChange( tokens ) {
		const ids = tokens
			.map( ( token ) => {
				const parts = token.split( ' - ' );
				return parseInt( parts[ parts.length - 1 ], 10 );
			} )
			.filter( Boolean );
		setAttributes( { manualProducts: ids } );
	}

	return (
		<div { ...useBlockProps() }>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'goodblocks' ) }>
					<TextControl
						label={ __( 'Title', 'goodblocks' ) }
						value={ rubrik }
						onChange={ ( v ) => setAttributes( { rubrik: v } ) }
					/>
					<TextControl
						label={ __( 'Description', 'goodblocks' ) }
						value={ text }
						onChange={ ( v ) => setAttributes( { text: v } ) }
					/>
					<ToggleControl
						label={ __( 'Manual product selection', 'goodblocks' ) }
						checked={ manualMode }
						onChange={ ( v ) =>
							setAttributes( { manualMode: v } )
						}
					/>
					{ ! manualMode && (
						<>
							<RangeControl
								label={ __(
									'Products to show',
									'goodblocks'
								) }
								value={ productsToShow }
								onChange={ ( v ) =>
									setAttributes( { productsToShow: v } )
								}
								min={ 1 }
								max={ 24 }
							/>
							<ComboboxControl
								label={ __( 'Category', 'goodblocks' ) }
								value={ category }
								options={ categories.map( ( cat ) => ( {
									value: cat.slug,
									label: decodeEntities( cat.name ),
								} ) ) }
								onChange={ ( v ) =>
									setAttributes( { category: v } )
								}
								onFilterValueChange={ setCategorySearch }
							/>
							<ComboboxControl
								label={ __( 'Tag', 'goodblocks' ) }
								value={ tag }
								options={ tags.map( ( t ) => ( {
									value: t.slug,
									label: decodeEntities( t.name ),
								} ) ) }
								onChange={ ( v ) =>
									setAttributes( { tag: v } )
								}
								onFilterValueChange={ setTagSearch }
							/>
							<ComboboxControl
								label={ __( 'Designer', 'goodblocks' ) }
								value={ formgivare }
								options={ formgivareTerms }
								onChange={ ( v ) =>
									setAttributes( { formgivare: v } )
								}
								onFilterValueChange={ setFormgivareSearch }
							/>
							<SelectControl
								label={ __( 'Sort by', 'goodblocks' ) }
								value={ orderBy || 'menu_order' }
								options={ [
									{ label: __( 'Default', 'goodblocks' ), value: 'menu_order' },
									{ label: __( 'Title A-Z', 'goodblocks' ), value: 'title-asc' },
									{ label: __( 'Title Z-A', 'goodblocks' ), value: 'title-desc' },
									{ label: __( 'Popularity', 'goodblocks' ), value: 'popularity' },
									{ label: __( 'Price: Low to High', 'goodblocks' ), value: 'price' },
									{ label: __( 'Price: High to Low', 'goodblocks' ), value: 'price-desc' },
								] }
								onChange={ ( v ) =>
									setAttributes( { orderBy: v } )
								}
							/>
						</>
					) }
					{ manualMode && (
						<>
							<FormTokenField
								label={ __(
									'Search and select products',
									'goodblocks'
								) }
								value={ manualProducts.map( ( id ) => {
									const prod = manualProductObjects.find(
										( p ) => p.id === id
									);
									return prod
										? `${ decodeEntities( prod.name ) } - ${ prod.id }`
										: `${ id }`;
								} ) }
								suggestions={ productSuggestions }
								onInputChange={ setProductSearch }
								onChange={ handleManualProductsChange }
								maxLength={ 24 }
							/>
							{ manualProductObjects.length > 0 && (
								<div style={ { marginTop: 12 } }>
									<strong>
										{ __(
											'Selected products (drag to reorder):',
											'goodblocks'
										) }
									</strong>
									<ul
										style={ {
											listStyle: 'none',
											padding: 0,
											minHeight: 40,
										} }
									>
										{ manualProductObjects.map( ( prod ) => (
											<li
												key={ prod.id }
												draggable
												onDragStart={ ( e ) =>
													e.dataTransfer.setData(
														'text/plain',
														prod.id
													)
												}
												onDragOver={ ( e ) =>
													e.preventDefault()
												}
												onDrop={ ( e ) => {
													const draggedId = Number(
														e.dataTransfer.getData(
															'text/plain'
														)
													);
													if (
														draggedId !== prod.id
													) {
														const oldIdx =
															manualProducts.indexOf(
																draggedId
															);
														const newIdx =
															manualProducts.indexOf(
																prod.id
															);
														if (
															oldIdx !== -1 &&
															newIdx !== -1
														) {
															const newOrder =
																Array.from(
																	manualProducts
																);
															const [ removed ] =
																newOrder.splice(
																	oldIdx,
																	1
																);
															newOrder.splice(
																newIdx,
																0,
																removed
															);
															setAttributes( {
																manualProducts:
																	newOrder,
															} );
														}
													}
												} }
												style={ {
													marginBottom: 4,
													background: '#f8f8f8',
													border: '1px solid #ddd',
													borderRadius: 3,
													padding: 6,
													cursor: 'grab',
												} }
											>
												{ decodeEntities( prod.name ) }
											</li>
										) ) }
									</ul>
								</div>
							) }
						</>
					) }
				</PanelBody>
			</InspectorControls>
			<ServerSideRender
				block="goodblocks/product-carousel"
				attributes={ attributes }
			/>
		</div>
	);
}

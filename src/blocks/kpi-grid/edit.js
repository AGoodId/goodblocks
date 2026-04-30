/**
 * KPI Grid Block — Editor
 */

import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	SelectControl,
	Button,
} from '@wordpress/components';

const MAX_TILES = 6;

const generateId = () =>
	`tile-${ Math.random().toString( 36 ).slice( 2, 10 ) }`;

export default function Edit( { attributes, setAttributes } ) {
	const { items, columns, theme } = attributes;

	const tiles = items || [];
	const columnsResolved =
		columns === 'auto' ? Math.min( tiles.length || 1, MAX_TILES ) : columns;

	const blockProps = useBlockProps( {
		className: [
			'kpi-grid',
			`kpi-grid--${ theme }`,
			`kpi-grid--cols-${ columnsResolved }`,
		].join( ' ' ),
	} );

	const updateTile = ( index, patch ) => {
		const next = [ ...tiles ];
		next[ index ] = { ...next[ index ], ...patch };
		setAttributes( { items: next } );
	};

	const addTile = () => {
		if ( tiles.length >= MAX_TILES ) {
			return;
		}
		setAttributes( {
			items: [ ...tiles, { id: generateId(), value: '', label: '' } ],
		} );
	};

	const removeTile = ( index ) => {
		setAttributes( {
			items: tiles.filter( ( _, i ) => i !== index ),
		} );
	};

	const moveTile = ( index, direction ) => {
		const target = index + direction;
		if ( target < 0 || target >= tiles.length ) {
			return;
		}
		const next = [ ...tiles ];
		[ next[ index ], next[ target ] ] = [ next[ target ], next[ index ] ];
		setAttributes( { items: next } );
	};

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Tiles', 'goodblocks' ) }>
					{ tiles.map( ( tile, index ) => (
						<div
							key={ tile.id || index }
							style={ {
								marginBottom: '16px',
								padding: '10px',
								background: '#f6f7f7',
								borderRadius: '4px',
							} }
						>
							<div
								style={ {
									display: 'flex',
									gap: '8px',
									marginBottom: '8px',
									justifyContent: 'space-between',
									alignItems: 'center',
								} }
							>
								<strong>
									{ __( 'Tile', 'goodblocks' ) } { index + 1 }
								</strong>
								<div style={ { display: 'flex', gap: '4px' } }>
									<Button
										size="small"
										variant="secondary"
										disabled={ index === 0 }
										onClick={ () => moveTile( index, -1 ) }
										aria-label={ __(
											'Move up',
											'goodblocks'
										) }
									>
										↑
									</Button>
									<Button
										size="small"
										variant="secondary"
										disabled={ index === tiles.length - 1 }
										onClick={ () => moveTile( index, 1 ) }
										aria-label={ __(
											'Move down',
											'goodblocks'
										) }
									>
										↓
									</Button>
								</div>
							</div>
							<TextControl
								label={ __( 'Value', 'goodblocks' ) }
								value={ tile.value || '' }
								onChange={ ( v ) =>
									updateTile( index, { value: v } )
								}
								help={ __(
									'Main number or text (ex: 71, 5 yrs, ↑)',
									'goodblocks'
								) }
							/>
							<TextControl
								label={ __( 'Label', 'goodblocks' ) }
								value={ tile.label || '' }
								onChange={ ( v ) =>
									updateTile( index, { label: v } )
								}
							/>
							<div style={ { display: 'flex', gap: '8px' } }>
								<TextControl
									label={ __( 'Prefix', 'goodblocks' ) }
									value={ tile.prefix || '' }
									onChange={ ( v ) =>
										updateTile( index, { prefix: v } )
									}
									help={ __( 'e.g. −, ≈, €', 'goodblocks' ) }
								/>
								<TextControl
									label={ __( 'Suffix', 'goodblocks' ) }
									value={ tile.suffix || '' }
									onChange={ ( v ) =>
										updateTile( index, { suffix: v } )
									}
									help={ __(
										'e.g. %, t, yrs',
										'goodblocks'
									) }
								/>
							</div>
							<Button
								isDestructive
								variant="link"
								onClick={ () => removeTile( index ) }
							>
								{ __( 'Remove tile', 'goodblocks' ) }
							</Button>
						</div>
					) ) }
					{ tiles.length < MAX_TILES && (
						<Button variant="secondary" onClick={ addTile }>
							{ __( '+ Add tile', 'goodblocks' ) }
						</Button>
					) }
					{ tiles.length >= MAX_TILES && (
						<p
							style={ {
								fontSize: '12px',
								color: '#757575',
								marginTop: 0,
							} }
						>
							{ __( 'Maximum 6 tiles per grid.', 'goodblocks' ) }
						</p>
					) }
				</PanelBody>

				<PanelBody
					title={ __( 'Layout', 'goodblocks' ) }
					initialOpen={ false }
				>
					<SelectControl
						label={ __( 'Columns', 'goodblocks' ) }
						value={ columns }
						options={ [
							{
								label: __(
									'Auto (match tile count)',
									'goodblocks'
								),
								value: 'auto',
							},
							{ label: '2', value: '2' },
							{ label: '3', value: '3' },
							{ label: '4', value: '4' },
							{ label: '5', value: '5' },
							{ label: '6', value: '6' },
						] }
						onChange={ ( v ) => setAttributes( { columns: v } ) }
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'Style', 'goodblocks' ) }
					initialOpen={ false }
				>
					<SelectControl
						label={ __( 'Theme', 'goodblocks' ) }
						value={ theme }
						options={ [
							{
								label: __( 'Light', 'goodblocks' ),
								value: 'light',
							},
							{
								label: __( 'Dark', 'goodblocks' ),
								value: 'dark',
							},
							{
								label: __( 'Accent', 'goodblocks' ),
								value: 'accent',
							},
						] }
						onChange={ ( v ) => setAttributes( { theme: v } ) }
					/>
				</PanelBody>
			</InspectorControls>

			<section { ...blockProps }>
				{ tiles.length === 0 ? (
					<div className="kpi-grid__empty">
						<Button variant="primary" onClick={ addTile }>
							{ __( '+ Add your first tile', 'goodblocks' ) }
						</Button>
					</div>
				) : (
					<div className="kpi-grid__inner">
						{ tiles.map( ( tile ) => (
							<div
								key={ tile.id }
								className="kpi-grid__tile"
								data-id={ tile.id }
							>
								<div className="kpi-grid__value">
									{ tile.prefix && (
										<span className="kpi-grid__prefix">
											{ tile.prefix }
										</span>
									) }
									<span className="kpi-grid__number">
										{ tile.value || '—' }
									</span>
									{ tile.suffix && (
										<span className="kpi-grid__suffix">
											{ tile.suffix }
										</span>
									) }
								</div>
								<div className="kpi-grid__label">
									{ tile.label || (
										<em
											style={ {
												opacity: 0.5,
											} }
										>
											{ __( 'Label…', 'goodblocks' ) }
										</em>
									) }
								</div>
							</div>
						) ) }
					</div>
				) }
			</section>
		</>
	);
}

/**
 * Testimonials Block — Editor
 */

import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	TextareaControl,
	RangeControl,
	ToggleControl,
	SelectControl,
	Button,
} from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const { items, autoplay, autoplayDelay, animation, showArrows, showDots } =
		attributes;

	const blockProps = useBlockProps( {
		className: 'testimonials-editor',
	} );

	const firstItem = items[ 0 ];

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Citat', 'goodblocks' ) }>
					{ items.map( ( item, index ) => (
						<div
							key={ index }
							style={ {
								marginBottom: '16px',
								padding: '10px',
								background: '#f6f7f7',
								borderRadius: '4px',
							} }
						>
							<TextareaControl
								label={ `${ __( 'Citat', 'goodblocks' ) } ${
									index + 1
								}` }
								value={ item.quote }
								onChange={ ( value ) => {
									const updated = [ ...items ];
									updated[ index ] = {
										...updated[ index ],
										quote: value,
									};
									setAttributes( { items: updated } );
								} }
								rows={ 3 }
							/>
							<TextControl
								label={ __( 'Namn', 'goodblocks' ) }
								value={ item.author }
								onChange={ ( value ) => {
									const updated = [ ...items ];
									updated[ index ] = {
										...updated[ index ],
										author: value,
									};
									setAttributes( { items: updated } );
								} }
							/>
							<TextControl
								label={ __( 'Roll / företag', 'goodblocks' ) }
								value={ item.role }
								onChange={ ( value ) => {
									const updated = [ ...items ];
									updated[ index ] = {
										...updated[ index ],
										role: value,
									};
									setAttributes( { items: updated } );
								} }
							/>
							<Button
								isDestructive
								variant="link"
								onClick={ () =>
									setAttributes( {
										items: items.filter(
											( _, i ) => i !== index
										),
									} )
								}
							>
								{ __( 'Ta bort', 'goodblocks' ) }
							</Button>
						</div>
					) ) }
					<Button
						variant="secondary"
						onClick={ () =>
							setAttributes( {
								items: [
									...items,
									{ quote: '', author: '', role: '' },
								],
							} )
						}
					>
						{ __( '+ Lägg till citat', 'goodblocks' ) }
					</Button>
				</PanelBody>

				<PanelBody
					title={ __( 'Karusell', 'goodblocks' ) }
					initialOpen={ false }
				>
					<SelectControl
						label={ __( 'Animation', 'goodblocks' ) }
						value={ animation }
						options={ [
							{
								label: __( 'Fade', 'goodblocks' ),
								value: 'fade',
							},
							{
								label: __( 'Slide', 'goodblocks' ),
								value: 'slide',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { animation: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Autoplay', 'goodblocks' ) }
						checked={ autoplay }
						onChange={ ( value ) =>
							setAttributes( { autoplay: value } )
						}
					/>
					{ autoplay && (
						<RangeControl
							label={ __(
								'Autoplay-intervall (ms)',
								'goodblocks'
							) }
							value={ autoplayDelay }
							onChange={ ( value ) =>
								setAttributes( { autoplayDelay: value } )
							}
							min={ 1000 }
							max={ 10000 }
							step={ 500 }
						/>
					) }
					<ToggleControl
						label={ __( 'Visa pilar', 'goodblocks' ) }
						checked={ showArrows }
						onChange={ ( value ) =>
							setAttributes( { showArrows: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Visa punkter', 'goodblocks' ) }
						checked={ showDots }
						onChange={ ( value ) =>
							setAttributes( { showDots: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				{ items.length === 0 ? (
					<div className="testimonials-editor__empty">
						<p>
							{ __(
								'Lägg till citat i sidopanelen.',
								'goodblocks'
							) }
						</p>
					</div>
				) : (
					<blockquote className="testimonials-editor__preview">
						{ firstItem?.quote && (
							<p className="testimonial-quote">
								{ firstItem.quote }
							</p>
						) }
						{ firstItem?.author && (
							<footer>
								<cite>
									<span className="testimonial-author">
										{ firstItem.author }
									</span>
									{ firstItem?.role && (
										<span className="testimonial-role">
											{ firstItem.role }
										</span>
									) }
								</cite>
							</footer>
						) }
						{ items.length > 1 && (
							<p className="testimonials-editor__count">
								{ items.length }{ ' ' }
								{ __( 'citat totalt', 'goodblocks' ) }
							</p>
						) }
					</blockquote>
				) }
			</div>
		</>
	);
}

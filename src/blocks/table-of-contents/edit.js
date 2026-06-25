import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	RichText,
	useBlockProps,
} from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	SelectControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';

const sampleItems = [
	{ text: __( 'Introduction', 'goodblocks' ), level: 2 },
	{ text: __( 'Background', 'goodblocks' ), level: 2 },
	{ text: __( 'Details', 'goodblocks' ), level: 3 },
	{ text: __( 'Next steps', 'goodblocks' ), level: 2 },
];

const normalizeLevels = ( attributes ) => {
	if ( Array.isArray( attributes.includeLevels ) ) {
		return attributes.includeLevels
			.map( ( level ) => parseInt( level, 10 ) )
			.filter( ( level ) => level >= 2 && level <= 6 );
	}

	return [ 2, 3, 4, 5, 6 ].filter(
		( level ) => attributes[ `includeH${ level }` ]
	);
};

export default function Edit( { attributes, setAttributes } ) {
	const {
		title,
		layout,
		sticky,
		floatingPosition,
		showNumbers,
		showActive,
		collapsible,
		startCollapsed,
		smoothScroll,
		minHeadings,
		scrollOffset,
		scopeSelector,
		headingSelector,
		excludeSelector,
	} = attributes;
	const includeLevels = normalizeLevels( attributes );

	const blockProps = useBlockProps( {
		className: [
			'goodblocks-toc',
			`goodblocks-toc--${ layout }`,
			sticky ? 'is-sticky' : '',
			showNumbers ? 'has-numbers' : '',
			collapsible ? 'is-collapsible' : '',
			startCollapsed ? 'is-collapsed' : '',
			`is-floating-${ floatingPosition }`,
		]
			.filter( Boolean )
			.join( ' ' ),
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Heading levels', 'goodblocks' ) }>
					{ [ 2, 3, 4, 5, 6 ].map( ( level ) => (
						<ToggleControl
							key={ level }
							label={ `H${ level }` }
							checked={ includeLevels.includes( level ) }
							onChange={ ( value ) => {
								const next = value
									? [ ...includeLevels, level ]
									: includeLevels.filter(
											( current ) => current !== level
									  );

								setAttributes( {
									includeLevels: next.sort(),
									[ `includeH${ level }` ]: value,
								} );
							} }
						/>
					) ) }
				</PanelBody>
				<PanelBody title={ __( 'Layout', 'goodblocks' ) }>
					<SelectControl
						label={ __( 'Presentation', 'goodblocks' ) }
						value={ layout }
						options={ [
							{
								label: __( 'Card', 'goodblocks' ),
								value: 'card',
							},
							{
								label: __( 'Minimal', 'goodblocks' ),
								value: 'minimal',
							},
							{
								label: __( 'Floating', 'goodblocks' ),
								value: 'floating',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { layout: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Sticky on desktop', 'goodblocks' ) }
						checked={ sticky }
						onChange={ ( value ) =>
							setAttributes( { sticky: value } )
						}
					/>
					{ layout === 'floating' && (
						<SelectControl
							label={ __( 'Floating position', 'goodblocks' ) }
							value={ floatingPosition }
							options={ [
								{
									label: __( 'Right', 'goodblocks' ),
									value: 'right',
								},
								{
									label: __( 'Left', 'goodblocks' ),
									value: 'left',
								},
							] }
							onChange={ ( value ) =>
								setAttributes( { floatingPosition: value } )
							}
						/>
					) }
					<ToggleControl
						label={ __( 'Show numbers', 'goodblocks' ) }
						checked={ showNumbers }
						onChange={ ( value ) =>
							setAttributes( { showNumbers: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Highlight active section', 'goodblocks' ) }
						checked={ showActive }
						onChange={ ( value ) =>
							setAttributes( { showActive: value } )
						}
					/>
				</PanelBody>
				<PanelBody
					title={ __( 'Behavior', 'goodblocks' ) }
					initialOpen={ false }
				>
					<ToggleControl
						label={ __( 'Collapsible', 'goodblocks' ) }
						checked={ collapsible }
						onChange={ ( value ) =>
							setAttributes( { collapsible: value } )
						}
					/>
					{ collapsible && (
						<ToggleControl
							label={ __( 'Start collapsed', 'goodblocks' ) }
							checked={ startCollapsed }
							onChange={ ( value ) =>
								setAttributes( { startCollapsed: value } )
							}
						/>
					) }
					<ToggleControl
						label={ __( 'Smooth scroll', 'goodblocks' ) }
						checked={ smoothScroll }
						onChange={ ( value ) =>
							setAttributes( { smoothScroll: value } )
						}
					/>
					<RangeControl
						label={ __( 'Minimum headings', 'goodblocks' ) }
						value={ minHeadings }
						min={ 1 }
						max={ 8 }
						onChange={ ( value ) =>
							setAttributes( { minHeadings: value } )
						}
					/>
					<RangeControl
						label={ __( 'Scroll offset', 'goodblocks' ) }
						value={ scrollOffset }
						min={ 0 }
						max={ 240 }
						step={ 8 }
						onChange={ ( value ) =>
							setAttributes( { scrollOffset: value } )
						}
					/>
					<TextControl
						label={ __( 'Scope selector', 'goodblocks' ) }
						value={ scopeSelector }
						placeholder=".entry-content"
						onChange={ ( value ) =>
							setAttributes( { scopeSelector: value } )
						}
					/>
					<TextControl
						label={ __( 'Heading selector', 'goodblocks' ) }
						value={ headingSelector }
						placeholder="h2, h3 or h5.styleguide"
						onChange={ ( value ) =>
							setAttributes( { headingSelector: value } )
						}
					/>
					<TextControl
						label={ __( 'Exclude selector', 'goodblocks' ) }
						value={ excludeSelector }
						placeholder=".skip-toc, .hero"
						onChange={ ( value ) =>
							setAttributes( { excludeSelector: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<nav
				{ ...blockProps }
				aria-label={ __( 'Table of contents', 'goodblocks' ) }
			>
				<div className="goodblocks-toc__header">
					<RichText
						tagName="p"
						className="goodblocks-toc__title"
						value={ title }
						allowedFormats={ [] }
						onChange={ ( value ) =>
							setAttributes( { title: value } )
						}
						placeholder={ __(
							'Table of contents title…',
							'goodblocks'
						) }
					/>
					{ collapsible && (
						<span
							className="goodblocks-toc__toggle"
							aria-hidden="true"
						/>
					) }
				</div>
				<ol className="goodblocks-toc__list">
					{ sampleItems.map( ( item, index ) => (
						<li
							className={ `goodblocks-toc__item is-level-${
								item.level
							} ${
								showActive && index === 1 ? 'is-active' : ''
							}` }
							key={ item.text }
						>
							<span className="goodblocks-toc__link">
								{ showNumbers && (
									<span className="goodblocks-toc__number">
										{ index + 1 }
									</span>
								) }
								<span>{ item.text }</span>
							</span>
						</li>
					) ) }
				</ol>
			</nav>
		</>
	);
}

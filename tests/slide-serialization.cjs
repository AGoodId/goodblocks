/* Regression: the added alt attribute must not invalidate existing saved slides. */
const assert = require( 'node:assert/strict' );
const fs = require( 'node:fs' );
const { execFileSync } = require( 'node:child_process' );
const React = require( 'react' );
const { renderToStaticMarkup } = require( 'react-dom/server' );
const babel = require( '@babel/core' );
const focalPointStyle = new Function( `${ fs.readFileSync( 'src/blocks/slide/focal-point.js', 'utf8' ).replace( 'export function', 'function' ) }; return focalPointStyle;` )();

function loadSave( source ) {
	const body = source.replace( /import[^;]+;/g, '' ).replace( 'export default function save', 'function save' );
	const compiled = babel.transformSync( body, { configFile: false, babelrc: false, plugins: [ [ '@babel/plugin-transform-react-jsx', { runtime: 'classic' } ] ] } ).code;
	const blockProps = { save: ( props ) => ( { className: `wp-block-goodblocks-slide ${ props.className }`, ...props } ) };
	const richText = { Content: ( { tagName, value, ...props } ) => value ? React.createElement( tagName, { ...props, dangerouslySetInnerHTML: { __html: value } } ) : null };
	return new Function( 'React', 'useBlockProps', 'RichText', 'focalPointStyle', `${ compiled }; return save;` )( React, blockProps, richText, focalPointStyle );
}
const oldSave = loadSave( execFileSync( 'git', [ 'show', 'a38bd2b:src/blocks/slide/save.js' ], { encoding: 'utf8' } ) );
const newSave = loadSave( fs.readFileSync( 'src/blocks/slide/save.js', 'utf8' ) );
const metadata = JSON.parse( fs.readFileSync( 'src/blocks/slide/block.json', 'utf8' ) );
const defaults = Object.fromEntries( Object.entries( metadata.attributes ).map( ( [ key, value ] ) => [ key, value.default ] ) );
const render = ( save, attributes ) => renderToStaticMarkup( React.createElement( save, { attributes } ) );
let checked = 0;
for ( const mediaType of [ 'image', 'video' ] ) {
	for ( const linkTarget of [ false, true ] ) {
		for ( const heading of [ '', 'An artwork', 'An <em>important</em> artwork' ] ) {
			const attributes = { ...defaults, mediaUrl: '/art.jpg', mediaType, heading, text: 'A <strong>description</strong>', linkUrl: '/visit/', linkTarget, showOverlay: true };
			delete attributes.mediaAlt; // Actual old content has no new attribute.
			assert.equal( render( newSave, attributes ), render( oldSave, attributes ), 'old slide markup stays byte-identical' );
			checked++;
		}
	}
}
const described = render( newSave, { ...defaults, mediaUrl: '/art.jpg', mediaAlt: 'Sculpture & light' } );
assert.match( described, /alt="Sculpture &amp; light"/ );
assert.equal( defaults.mediaAlt, '' );
for ( const mediaType of [ 'image', 'video' ] ) {
	const markup = render( newSave, { ...defaults, mediaType, mediaUrl: '/art.jpg', focalPoint: { x: 0.48, y: 0.38 } } );
	assert.match( markup, /--slide-object-position:48% 38%/ );
}
for ( const point of [ undefined, null, {}, { x: '0.5', y: 0.5 }, { x: NaN, y: 0.5 }, { x: 0.5, y: Infinity }, { x: -0.1, y: 0.5 }, { x: 0.5, y: 1.1 } ] ) {
	assert.equal( render( newSave, { ...defaults, mediaUrl: '/art.jpg', focalPoint: point } ), render( newSave, { ...defaults, mediaUrl: '/art.jpg' } ) );
}
assert.deepEqual( focalPointStyle( { x: 0, y: 1 } ), { '--slide-object-position': '0% 100%' } );
assert.deepEqual( focalPointStyle( { x: 0.5, y: 0.55 } ), { '--slide-object-position': '50% 55%' } );
console.log( `PASS: ${ checked } existing slide serializations unchanged; new alternative text is saved and escaped` );
console.log( 'PASS: explicit focal points saved for images/video; invalid/absent points preserve old markup; edge coordinates and percentage precision verified' );

/* Regression: the added alt attribute must not invalidate existing saved slides. */
const assert = require( 'node:assert/strict' );
const fs = require( 'node:fs' );
const { execFileSync } = require( 'node:child_process' );
const React = require( 'react' );
const { renderToStaticMarkup } = require( 'react-dom/server' );
const babel = require( '@babel/core' );

function loadSave( source ) {
	const body = source.replace( /import[^;]+;/g, '' ).replace( 'export default function save', 'function save' );
	const compiled = babel.transformSync( body, { configFile: false, babelrc: false, plugins: [ [ '@babel/plugin-transform-react-jsx', { runtime: 'classic' } ] ] } ).code;
	const blockProps = { save: ( props ) => ( { className: `wp-block-goodblocks-slide ${ props.className }`, ...props } ) };
	const richText = { Content: ( { tagName, value, ...props } ) => value ? React.createElement( tagName, { ...props, dangerouslySetInnerHTML: { __html: value } } ) : null };
	return new Function( 'React', 'useBlockProps', 'RichText', `${ compiled }; return save;` )( React, blockProps, richText );
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
console.log( `PASS: ${ checked } existing slide serializations unchanged; new alternative text is saved and escaped` );

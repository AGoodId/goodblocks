/* Optional height caps must never invalidate existing saved slider blocks. */
const assert = require( 'node:assert/strict' );
const fs = require( 'node:fs' );
const { execFileSync } = require( 'node:child_process' );
const React = require( 'react' );
const { renderToStaticMarkup } = require( 'react-dom/server' );
const babel = require( '@babel/core' );

function loadSave( source ) {
	const body = source.replace( /import[^;]+;/g, '' ).replace( 'export default function save', 'function save' );
	const compiled = babel.transformSync( body, { configFile: false, babelrc: false, plugins: [ [ '@babel/plugin-transform-react-jsx', { runtime: 'classic' } ] ] } ).code;
	return new Function( 'React', 'useBlockProps', 'InnerBlocks', `${ compiled }; return save;` )( React, { save: ( props ) => props }, { Content: () => 'Nested slides' } );
}
const baseline = loadSave( execFileSync( 'git', [ 'show', 'b5e7139:src/blocks/slider/save.js' ], { encoding: 'utf8' } ) );
const current = loadSave( fs.readFileSync( 'src/blocks/slider/save.js', 'utf8' ) );
const metadata = JSON.parse( fs.readFileSync( 'src/blocks/slider/block.json', 'utf8' ) );
const defaults = Object.fromEntries( Object.entries( metadata.attributes ).map( ( [ key, value ] ) => [ key, value.default ] ) );
const render = ( save, attributes ) => renderToStaticMarkup( React.createElement( save, { attributes } ) );
let checked = 0;
for ( const layout of [ 'overlay', 'below' ] ) {
	for ( const aspectRatio of [ '16-9', '4-3', '3-1', '2-1', '3-2', 'none', undefined ] ) {
		const attrs = { ...defaults, layout, aspectRatio };
		delete attrs.maxImageHeight;
		assert.equal( render( current, attrs ), render( baseline, attrs ), 'legacy HTML stays byte-identical' );
		for ( const maxImageHeight of [ 0, null, -100, 99, 1601, 600.5, '600', NaN, Infinity ] ) {
			assert.equal( render( current, { ...attrs, maxImageHeight } ), render( baseline, attrs ), 'invalid cap cannot alter HTML' );
		}
		checked++;
	}
}
for ( const maxImageHeight of [ 100, 600, 800, 1600 ] ) {
	assert.match( render( current, { ...defaults, layout: 'overlay', maxImageHeight } ), new RegExp( `--slider-max-image-height:${ maxImageHeight }px` ) );
	for ( const attrs of [ { layout: 'below' }, { layout: 'overlay', aspectRatio: 'none' } ] ) {
		assert.equal( render( current, { ...defaults, ...attrs, maxImageHeight } ), render( baseline, { ...defaults, ...attrs } ), 'cap is inactive outside ratio overlay' );
	}
}
console.log( `PASS: ${ checked } legacy slider serializations; invalid/inactive caps unchanged; valid pixel caps saved` );

/* Optional height caps must never invalidate existing saved slider blocks. */
const assert = require( 'node:assert/strict' );
const fs = require( 'node:fs' );
const { execFileSync } = require( 'node:child_process' );
const React = require( 'react' );
const { renderToStaticMarkup } = require( 'react-dom/server' );
const babel = require( '@babel/core' );
const mobileTextPaddingStyle = new Function( fs.readFileSync( 'src/blocks/slider/mobile-text-padding.js', 'utf8' ).replace( 'export function', 'function' ) + '; return mobileTextPaddingStyle;' )();

function loadSave( source ) {
	const body = source.replace( /import[^;]+;/g, '' ).replace( 'export default function save', 'function save' );
	const compiled = babel.transformSync( body, { configFile: false, babelrc: false, plugins: [ [ '@babel/plugin-transform-react-jsx', { runtime: 'classic' } ] ] } ).code;
	return new Function( 'React', 'useBlockProps', 'InnerBlocks', 'mobileTextPaddingStyle', `${ compiled }; return save;` )( React, { save: ( props ) => props }, { Content: () => 'Nested slides' }, mobileTextPaddingStyle );
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
const padding = { top: 170, right: 40, bottom: 80, left: 40 };
const padded = render( current, { ...defaults, mobileTextPadding: padding } );
for ( const [ side, value ] of Object.entries( padding ) ) assert.ok( padded.includes( `--slider-mobile-text-pad-${ side }:${ value }px` ) );
for ( const value of [ undefined, null, [], '170px', {}, { ...padding, top: -1 }, { ...padding, top: 801 }, { ...padding, left: '40' }, { ...padding, bottom: 79 }, { ...padding, bottom: Infinity }, { ...padding, top: '0; color:red' } ] ) {
	assert.equal( render( current, { ...defaults, mobileTextPadding: value } ), render( baseline, defaults ), 'invalid spacing must not alter legacy HTML' );
}
assert.equal( render( current, { ...defaults, layout: 'below', mobileTextPadding: padding } ), render( baseline, { ...defaults, layout: 'below' } ), 'spacing cannot change caption layout' );
console.log( 'PASS: explicit bounded four-side mobile padding; malformed/inactive padding keeps legacy HTML' );

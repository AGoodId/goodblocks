/* Optional height caps must never invalidate existing saved slider blocks. */
const assert = require( 'node:assert/strict' );
const fs = require( 'node:fs' );
const { execFileSync } = require( 'node:child_process' );
const React = require( 'react' );
const { renderToStaticMarkup } = require( 'react-dom/server' );
const babel = require( '@babel/core' );
const mobileTextPaddingStyle = new Function( fs.readFileSync( 'src/blocks/slider/mobile-text-padding.js', 'utf8' ).replace( 'export function', 'function' ) + '; return mobileTextPaddingStyle;' )();
const desktopTextPaddingStyle = new Function( fs.readFileSync( 'src/blocks/slider/desktop-text-padding.js', 'utf8' ).replace( 'export function', 'function' ) + '; return desktopTextPaddingStyle;' )();

function loadSave( source ) {
	const body = source.replace( /import[^;]+;/g, '' ).replace( 'export default function save', 'function save' );
	const compiled = babel.transformSync( body, { configFile: false, babelrc: false, plugins: [ [ '@babel/plugin-transform-react-jsx', { runtime: 'classic' } ] ] } ).code;
	return new Function( 'React', 'useBlockProps', 'InnerBlocks', 'mobileTextPaddingStyle', 'desktopTextPaddingStyle', `${ compiled }; return save;` )( React, { save: ( props ) => props }, { Content: () => 'Nested slides' }, mobileTextPaddingStyle, desktopTextPaddingStyle );
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
const previous = loadSave( execFileSync( 'git', [ 'show', '084f4647:src/blocks/slider/save.js' ], { encoding: 'utf8' } ) );
const desktopPadding = { top: 320, right: 20, bottom: 270, left: 20 };
for ( const attrs of [ defaults, { ...defaults, mobileTextPadding: padding }, { ...defaults, maxImageHeight: 600, mobileTextPadding: padding } ] ) {
	assert.equal( render( current, attrs ), render( previous, attrs ), 'existing mobile/capped HTML stays byte-identical' );
	for ( const value of [ undefined, null, [], '320px', {}, { ...desktopPadding, top: -1 }, { ...desktopPadding, bottom: 801 }, { ...desktopPadding, left: '20' }, { ...desktopPadding, top: 1.5 }, { ...desktopPadding, bottom: Infinity }, { ...desktopPadding, top: '0; color:red' } ] ) {
		assert.equal( render( current, { ...attrs, desktopTextPadding: value } ), render( previous, attrs ), 'invalid desktop spacing keeps old HTML' );
	}
}
const desktop = render( current, { ...defaults, desktopTextPadding: desktopPadding, mobileTextPadding: padding } );
assert.match( desktop, /data-desktop-text-padding="true"/ );
for ( const [ side, value ] of Object.entries( desktopPadding ) ) assert.ok( desktop.includes( `--slider-desktop-text-pad-${ side }:${ value }px` ) );
for ( const [ side, value ] of Object.entries( padding ) ) assert.ok( desktop.includes( `--slider-mobile-text-pad-${ side }:${ value }px` ) );
assert.equal( render( current, { ...defaults, layout: 'below', desktopTextPadding: desktopPadding } ), render( previous, { ...defaults, layout: 'below' } ), 'desktop spacing ignores caption layout' );
assert.match( render( current, { ...defaults, desktopTextPadding: { top: 0, right: 0, bottom: 0, left: 0 } } ), /data-desktop-text-padding="true"/ );
console.log( 'PASS: bounded desktop spacing, independent mobile vars, zero insets and unchanged legacy/invalid/below HTML' );
for ( const layout of [ 'overlay', 'below' ] ) {
	for ( const showThumbnails of [ undefined, false, null, 'true', 1 ] ) {
		assert.equal( render( current, { ...defaults, layout, showThumbnails } ), render( previous, { ...defaults, layout } ), 'absent/invalid thumbnails preserve saved HTML' );
	}
}
assert.equal( render( current, { ...defaults, showThumbnails: true } ), render( previous, defaults ), 'thumbnails cannot alter overlay blocks' );
assert.match( render( current, { ...defaults, layout: 'below', showThumbnails: true } ), /data-show-thumbnails="true"/ );
console.log( 'PASS: explicit thumbnail opt-in is limited to below layout; legacy/default/invalid markup is unchanged' );

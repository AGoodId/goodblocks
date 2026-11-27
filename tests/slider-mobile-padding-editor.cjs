/* Editor callbacks must preserve the other insets and reset the optional value. */
const assert = require( 'node:assert/strict' );
const fs = require( 'node:fs' );
const React = require( 'react' );
const { renderToStaticMarkup } = require( 'react-dom/server' );
const babel = require( '@babel/core' );
const mobileTextPaddingStyle = new Function( fs.readFileSync( 'src/blocks/slider/mobile-text-padding.js', 'utf8' ).replace( 'export function', 'function' ) + '; return mobileTextPaddingStyle;' )();
const desktopTextPaddingStyle = new Function( fs.readFileSync( 'src/blocks/slider/desktop-text-padding.js', 'utf8' ).replace( 'export function', 'function' ) + '; return desktopTextPaddingStyle;' )();
const source = fs.readFileSync( 'src/blocks/slider/edit.js', 'utf8' );
const compiled = babel.transformSync( source, { configFile: false, babelrc: false, plugins: [ '@babel/plugin-transform-react-jsx', '@babel/plugin-transform-modules-commonjs' ] } ).code;
const captured = {};
let captureMode = 'mobile';
const container = ( { children } ) => React.createElement( 'div', null, children );
const controls = Object.fromEntries( [ 'ToolbarGroup', 'ToolbarButton', 'PanelBody', 'ToggleControl', 'SelectControl' ].map( ( name ) => [ name, container ] ) );
controls.RangeControl = ( props ) => { captured[ props.label ] = props; return null; };
controls.PanelBody = ( props ) => {
	if ( props.title === ( captureMode === 'mobile' ? 'Desktop text padding' : 'Mobile text padding' ) ) return null;
	return React.createElement( 'div', null, props.children );
};
controls.Button = ( props ) => { if ( props.children === ( captureMode === 'mobile' ? 'Reset text padding' : 'Reset desktop text padding' ) ) captured.reset = props; return null; };
const modules = {
	'@wordpress/i18n': { __: ( text ) => text },
	'@wordpress/block-editor': { useBlockProps: ( props ) => props, useInnerBlocksProps: ( props ) => props, BlockControls: container, InspectorControls: container, MediaUpload: () => null, MediaUploadCheck: container },
	'@wordpress/components': controls,
	'@wordpress/icons': {}, '@wordpress/data': { useSelect: () => ( { innerBlocks: [], selectedBlockId: null } ), useDispatch: () => ( {} ) },
	'@wordpress/element': React, '@wordpress/blocks': {}, './editor.scss': {}, './mobile-text-padding': { mobileTextPaddingStyle },
	'./desktop-text-padding': { desktopTextPaddingStyle },
};
const moduleValue = { exports: {} };
new Function( 'require', 'module', 'exports', 'React', compiled )( ( name ) => { assert.ok( Object.hasOwn( modules, name ), name ); return modules[ name ]; }, moduleValue, moduleValue.exports, React );
const Edit = moduleValue.exports.default;
const metadata = JSON.parse( fs.readFileSync( 'src/blocks/slider/block.json', 'utf8' ) );
const defaults = Object.fromEntries( Object.entries( metadata.attributes ).map( ( [ key, value ] ) => [ key, value.default ] ) );
let change;
const render = ( attrs ) => renderToStaticMarkup( React.createElement( Edit, { clientId: 'example', attributes: { ...defaults, ...attrs }, setAttributes( value ) { change = value; } } ) );
const padding = { top: 170, right: 40, bottom: 80, left: 40 };
assert.match( render( { mobileTextPadding: padding } ), /data-mobile-text-padding="true"/ );
for ( const [ side, label ] of [ [ 'top', 'Above text (px)' ], [ 'right', 'Right of text (px)' ], [ 'bottom', 'Below text (px)' ], [ 'left', 'Left of text (px)' ] ] ) {
	assert.equal( captured[ label ].value, padding[ side ] );
	assert.equal( captured[ label ].min, side === 'bottom' ? 80 : 0 );
	captured[ label ].onChange( 190 );
	assert.deepEqual( change, { mobileTextPadding: { ...padding, [ side ]: 190 } } );
}
captured.reset.onClick();
assert.deepEqual( change, { mobileTextPadding: undefined } );
assert.doesNotMatch( render( {} ), /data-mobile-text-padding/ );
assert.doesNotMatch( render( { layout: 'below', mobileTextPadding: padding } ), /data-mobile-text-padding/ );
console.log( 'PASS: four independent editor controls preserve other sides; reserved control row; reset/unset/below leave no active spacing marker' );
captureMode = 'desktop';
const desktopPadding = { top: 320, right: 20, bottom: 270, left: 20 };
assert.match( render( { desktopTextPadding: desktopPadding, mobileTextPadding: padding } ), /data-desktop-text-padding="true"/ );
for ( const [ side, label ] of [ [ 'top', 'Above text (px)' ], [ 'right', 'Right of text (px)' ], [ 'bottom', 'Below text (px)' ], [ 'left', 'Left of text (px)' ] ] ) {
	assert.equal( captured[ label ].value, desktopPadding[ side ] );
	assert.equal( captured[ label ].min, 0 );
	assert.equal( captured[ label ].max, 800 );
	captured[ label ].onChange( 75 );
	assert.deepEqual( change, { desktopTextPadding: { ...desktopPadding, [ side ]: 75 } } );
}
captured.reset.onClick();
assert.deepEqual( change, { desktopTextPadding: undefined } );
assert.doesNotMatch( render( {} ), /data-desktop-text-padding/ );
captured[ 'Above text (px)' ].onChange( 320 );
assert.deepEqual( change, { desktopTextPadding: { top: 320, right: 32, bottom: 32, left: 32 } } );
assert.doesNotMatch( render( { layout: 'below', desktopTextPadding: desktopPadding } ), /data-desktop-text-padding/ );
console.log( 'PASS: desktop callbacks/defaults/reset stay independent of mobile spacing; overlay-only marker' );

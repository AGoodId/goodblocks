/* Component contract observed in installed WordPress: the matrix export is experimental. */
const assert = require( 'node:assert/strict' );
const fs = require( 'node:fs' );
const React = require( 'react' );
const { renderToStaticMarkup } = require( 'react-dom/server' );
const babel = require( '@babel/core' );
const source = fs.readFileSync( 'src/blocks/slide/edit.js', 'utf8' );
const container = ( { children } ) => React.createElement( 'div', null, children );
const controls = Object.fromEntries( [ 'ToolbarGroup', 'ToolbarButton', 'PanelBody', 'SelectControl', 'TextControl', 'ToggleControl', 'RangeControl', 'Popover' ].map( ( key ) => [ key, container ] ) );
let pickerProps;
let captionProps;
controls.TextareaControl = ( props ) => { captionProps = props; return React.createElement( 'textarea', { value: props.value, readOnly: true } ); };
controls.FocalPointPicker = ( props ) => { pickerProps = props; return React.createElement( 'div', { 'data-native-control': 'focal-point' } ); };
const focalPointStyle = new Function( `${ fs.readFileSync( 'src/blocks/slide/focal-point.js', 'utf8' ).replace( 'export function', 'function' ) }; return focalPointStyle;` )();
const editor = {
	useBlockProps: ( props ) => props,
	RichText: ( { tagName = 'span', placeholder } ) => React.createElement( tagName, null, placeholder ),
	BlockControls: container,
	InspectorControls: container,
	MediaUpload: ( { render } ) => render( { open() {} } ),
	MediaUploadCheck: container,
	__experimentalBlockAlignmentMatrixControl: () => React.createElement( 'div', { 'data-native-control': 'alignment-matrix' } ),
	__experimentalLinkControl: container,
};
function load( code ) {
	const compiled = babel.transformSync( code, { configFile: false, babelrc: false, plugins: [ '@babel/plugin-transform-react-jsx', '@babel/plugin-transform-modules-commonjs' ] } ).code;
	const module = { exports: {} };
	const modules = { '@wordpress/i18n': { __: ( value ) => value }, '@wordpress/block-editor': editor, '@wordpress/components': controls, '@wordpress/icons': {}, '@wordpress/element': React, './editor.scss': {}, './focal-point': { focalPointStyle } };
	new Function( 'require', 'module', 'exports', 'React', compiled )( ( name ) => {
		assert.ok( Object.hasOwn( modules, name ), name );
		return modules[ name ];
	}, module, module.exports, React );
	return module.exports.default;
}
const metadata = JSON.parse( fs.readFileSync( 'src/blocks/slide/block.json', 'utf8' ) );
const attributes = Object.fromEntries( Object.entries( metadata.attributes ).map( ( [ key, value ] ) => [ key, value.default ] ) );
attributes.mediaUrl = '/test.jpg'; attributes.mediaAlt = 'An artwork';
const render = ( Edit, layout ) => renderToStaticMarkup( React.createElement( Edit, { attributes, setAttributes() {}, context: { 'goodblocks/layout': layout } } ) );
const html = render( load( source ), 'overlay' );
assert.match( html, /data-native-control="alignment-matrix"/ );
assert.match( html, /alt="An artwork"/ );
assert.match( render( load( source ), 'below' ), /alt="An artwork"/ );
assert.match( html, /data-native-control="focal-point"/ );
assert.deepEqual( pickerProps.value, { x: 0.5, y: 0.5 } );
assert.equal( pickerProps.url, '/test.jpg' );
let changed;
renderToStaticMarkup( React.createElement( load( source ), { attributes: { ...attributes, focalPoint: { x: 0.48, y: 0.38 } }, setAttributes( value ) { changed = value; }, context: {} } ) );
assert.deepEqual( pickerProps.value, { x: 0.48, y: 0.38 } );
pickerProps.onChange( { x: 0.3, y: 0.7 } );
assert.deepEqual( changed, { focalPoint: { x: 0.3, y: 0.7 } } );
renderToStaticMarkup( React.createElement( load( source ), { attributes: { ...attributes, caption: 'A local caption' }, setAttributes( value ) { changed = value; }, context: { 'goodblocks/layout': 'below' } } ) );
assert.equal( captionProps.value, 'A local caption' );
assert.equal( captionProps.label, 'Caption' );
captionProps.onChange( 'Updated only here' );
assert.deepEqual( changed, { caption: 'Updated only here' } );
const previous = source.replace( '__experimentalBlockAlignmentMatrixControl as BlockAlignmentMatrixControl', 'BlockAlignmentMatrixControl' );
const originalError = console.error;
try {
	console.error = () => {}; // Expected React warning from the negative regression only.
	assert.throws( () => render( load( previous ), 'overlay' ), /Element type is invalid/ );
} finally {
	console.error = originalError;
}
console.log( 'PASS: selected slide renders against native experimental matrix export; former missing export fails; overlay and below preserve alternative text' );

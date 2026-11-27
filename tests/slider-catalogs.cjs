/* New shared catalogs must retain the union of all three installed catalogs. */
const assert = require( 'node:assert/strict' );
const fs = require( 'node:fs' );
const { execFileSync } = require( 'node:child_process' );
const names = [ 'slider-editor', 'slider-view', 'slide-editor' ];
const contents = [];
let checked = 0;
for ( const name of names ) {
	const path = `inc/languages/goodblocks-sv_SE-goodblocks-${ name }-script.json`;
	const previous = JSON.parse( execFileSync( 'git', [ 'show', `084f4647:${ path }` ], { encoding: 'utf8' } ) );
	const raw = fs.readFileSync( path, 'utf8' );
	const current = JSON.parse( raw ).locale_data.messages;
	contents.push( raw );
	for ( const [ key, value ] of Object.entries( previous.locale_data.messages ) ) {
		assert.deepEqual( current[ key ], value, `${ name } must retain ${ key }` );
		checked++;
	}
	assert.deepEqual( current[ 'Desktop text padding' ], [ 'Luft runt text på stor skärm' ] );
	assert.deepEqual( current[ 'Reset desktop text padding' ], [ 'Återställ luft på stor skärm' ] );
	assert.deepEqual( current[ 'Link label for screen readers' ], [ 'Länktext för skärmläsare' ] );
	assert.ok( current[ 'Sets text spacing on large screens; the slide grows to fit the text.' ] );
	assert.ok( current[ '0 means no limit. Text can expand the slide.' ] );
}
assert.equal( new Set( contents ).size, 1, 'three shared catalogs stay identical' );
console.log( `PASS ${ checked } previous messages retained, desktop/help/link labels present, three identical Swedish catalogs` );

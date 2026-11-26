/* Run after npm run build. Exercises the compiled frontend with real Swiper. */
const assert = require( 'node:assert/strict' );
const fs = require( 'node:fs' );
const path = require( 'node:path' );
const { JSDOM } = require( 'jsdom' );
const { createI18n } = require( '@wordpress/i18n' );

const bundle = fs.readFileSync( path.join( __dirname, '../build/blocks/slider/view.js' ), 'utf8' );
const slide = ( index ) => `<div class="wp-block-goodblocks-slide" data-link-url="/art-${ index }"><div class="slide-media"><img src="/image-${ index }.jpg" alt="Artwork ${ index }"></div><div class="slide-heading">Artwork ${ index }</div></div>`;
const slider = ( id, count = 3, layout = 'overlay' ) => `<div id="${ id }" class="slider-container" data-show-navigation="true" data-show-pagination="true" data-layout="${ layout }" data-autoplay="true" data-autoplay-delay="100000">${ Array.from( { length: count }, ( _, i ) => slide( i ) ).join( '' ) }</div>`;

async function fixture( reduced = false, locale = 'en_US' ) {
	const dom = new JSDOM( slider( 'first' ) + slider( 'second', 3, 'below' ) + slider( 'single', 1 ), { runScripts: 'outside-only', pretendToBeVisual: true, url: 'https://example.test/' } );
	const { window } = dom;
	await new Promise( ( resolve ) => window.document.addEventListener( 'DOMContentLoaded', resolve, { once: true } ) );
	const media = { matches: reduced, listeners: [], addEventListener( _, callback ) { this.listeners.push( callback ); }, removeEventListener( _, callback ) { this.listeners = this.listeners.filter( ( listener ) => listener !== callback ); } };
	window.matchMedia = () => media;
	const i18n = createI18n();
	if ( locale === 'sv_SE' ) {
		const catalog = JSON.parse( fs.readFileSync( path.join( __dirname, '../inc/languages/goodblocks-sv_SE-goodblocks-slider-view-script.json' ), 'utf8' ) );
		i18n.setLocaleData( catalog.locale_data.messages, 'goodblocks' );
	}
	window.wp = { i18n };
	window.eval( bundle );
	window.document.dispatchEvent( new window.Event( 'DOMContentLoaded' ) );
	return { dom, window, media, get: ( id ) => window.document.getElementById( id ) };
}

( async () => {
	const { dom, window, media, get } = await fixture();
	const first = get( 'first' );
	const second = get( 'second' );
	const single = get( 'single' );
	assert.ok( first.swiper, 'compiled bundle initializes real Swiper' );
	assert.equal( first.tabIndex, 0, 'carousel can receive keyboard focus even with navigation hidden' );
	assert.equal( first.swiper.navigation.nextEl, first.querySelector( '.swiper-button-next' ), 'navigation scoped to first instance' );
	assert.equal( second.swiper.pagination.el, second.querySelector( '.swiper-pagination' ), 'pagination scoped to second instance' );
	assert.equal( first.querySelector( '.swiper-button-next' ).tagName, 'BUTTON' );
	assert.equal( first.querySelector( 'img' ).alt, 'Artwork 0' );
	assert.equal( second.querySelector( '.slide-link' ).getAttribute( 'aria-label' ), 'Artwork 0', 'below layout retains an accessible link name' );
	assert.equal( single.swiper.params.loop, false );
	assert.equal( single.swiper.autoplay.running, false );
	assert.equal( single.querySelector( '.goodblocks-slider-rotation, .swiper-button-next, .swiper-pagination' ), null );
	assert.equal( first.swiper.autoplay.running, true );
	const rotation = first.querySelector( '.goodblocks-slider-rotation' );
	rotation.click();
	assert.equal( first.swiper.autoplay.running, false, 'pause stops actual autoplay' );
	rotation.click();
	assert.equal( first.swiper.autoplay.running, true, 'play resumes actual autoplay' );
	rotation.focus();
	assert.equal( first.swiper.autoplay.running, false, 'rotation button focus also stops autoplay' );
	rotation.click();
	assert.equal( first.swiper.autoplay.running, true, 'explicit Play while focused resumes autoplay' );
	const pointerDown = new window.Event( 'pointerdown', { bubbles: true } );
	rotation.dispatchEvent( pointerDown );
	rotation.dispatchEvent( new window.FocusEvent( 'focusin', { bubbles: true } ) );
	rotation.click();
	assert.equal( first.swiper.autoplay.running, false, 'pointer Pause intent survives focus stopping rotation before click' );
	rotation.click();
	first.querySelector( '.swiper-button-next' ).focus();
	assert.equal( first.swiper.autoplay.running, false, 'focus pauses autoplay' );
	first.swiper.params.speed = 0;
	const secondIndex = second.swiper.realIndex;
	first.querySelector( '.swiper-button-next' ).dispatchEvent( new window.KeyboardEvent( 'keydown', { key: 'ArrowRight', bubbles: true, cancelable: true } ) );
	assert.equal( first.swiper.realIndex, 1, 'focused carousel responds to arrow key' );
	assert.equal( second.swiper.realIndex, secondIndex, 'other carousel does not move' );
	first.swiper.slides.forEach( ( element, index ) => {
		assert.equal( element.inert, index !== first.swiper.activeIndex );
		assert.equal( element.getAttribute( 'aria-hidden' ), String( index !== first.swiper.activeIndex ) );
	} );
	rotation.click();
	media.listeners.forEach( ( callback ) => callback( { matches: true } ) );
	assert.equal( first.swiper.autoplay.running, false, 'enabling reduced motion stops autoplay' );
	assert.equal( first.swiper.params.speed, 0 );
	for ( let i = 0; i < 8; i++ ) {
		first.swiper.slideNext( 0 );
		await new Promise( ( resolve ) => window.requestAnimationFrame( resolve ) );
		first.swiper.slides.forEach( ( element, index ) => {
			assert.equal( element.inert, index !== first.swiper.activeIndex, 'inactive state survives loop reordering' );
		} );
	}
	[ first, second, single ].forEach( ( element ) => element.swiper.destroy() );
	dom.window.close();
	const reducedFixture = await fixture( true );
	const reducedSlider = reducedFixture.get( 'first' );
	assert.equal( reducedSlider.swiper.autoplay.running, false, 'reduced motion never auto-starts' );
	assert.equal( reducedSlider.swiper.params.speed, 0 );
	const reducedPlay = reducedSlider.querySelector( '.goodblocks-slider-rotation' );
	reducedPlay.click();
	assert.equal( reducedSlider.swiper.autoplay.running, true, 'explicit Play works after initial reduced motion' );
	assert.equal( reducedSlider.swiper.params.autoplay.waitForTransition, true, 'native defaults retained after Play' );
	const hover = new reducedFixture.window.Event( 'pointerenter' );
	Object.defineProperty( hover, 'pointerType', { value: 'mouse' } );
	reducedSlider.dispatchEvent( hover );
	assert.equal( reducedSlider.swiper.autoplay.running, false, 'hover stops rotation after initial reduced motion and Play' );
	reducedPlay.click();
	Object.defineProperty( reducedFixture.window.document, 'hidden', { value: true, configurable: true } );
	reducedFixture.window.document.dispatchEvent( new reducedFixture.window.Event( 'visibilitychange' ) );
	assert.equal( reducedSlider.swiper.autoplay.running, false, 'hidden document stops manually started rotation' );
	reducedFixture.window.document.querySelectorAll( '.slider-container' ).forEach( ( element ) => element.swiper.destroy() );
	reducedFixture.dom.window.close();
	const swedishFixture = await fixture( false, 'sv_SE' );
	const swedishSlider = swedishFixture.get( 'first' );
	assert.equal( swedishSlider.getAttribute( 'aria-label' ), 'Bildspel' );
	assert.equal( swedishSlider.querySelector( '.swiper-button-prev' ).getAttribute( 'aria-label' ), 'Föregående bild' );
	assert.equal( swedishSlider.querySelector( '.swiper-button-next' ).getAttribute( 'aria-label' ), 'Nästa bild' );
	assert.equal( swedishSlider.querySelector( '.swiper-pagination-bullet' ).getAttribute( 'aria-label' ), 'Visa bild 1' );
	assert.equal( swedishSlider.querySelector( '.goodblocks-slider-rotation' ).textContent, 'Pausa bildspel' );
	swedishSlider.querySelector( '.goodblocks-slider-rotation' ).click();
	assert.equal( swedishSlider.querySelector( '.goodblocks-slider-rotation' ).textContent, 'Starta bildspel' );
	assert.equal( swedishFixture.window.wp.i18n.__( 'Alternative text', 'goodblocks' ), 'Alternativtext' );
	swedishFixture.window.document.querySelectorAll( '.slider-container' ).forEach( ( element ) => element.swiper.destroy() );
	swedishFixture.dom.window.close();
	console.log( 'PASS: compiled Swiper controls, separate instances, alt/link names, single slide, focus, keyboard, pause/play, inactive slides, reduced motion and Swedish catalog' );
} )().catch( ( error ) => { console.error( error ); process.exitCode = 1; } );

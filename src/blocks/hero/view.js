/**
 * Hero Block — Frontend animations + scroll-arrow.
 *
 * - IntersectionObserver triggers `.is-in-view` class for fade-up / split-words animations
 * - split-words wraps each word in `<span.hero-block__word>` with `--word-index` for stagger
 * - scroll-arrow scrolls smoothly to next sibling element
 * - Respects prefers-reduced-motion
 * - Adds `.goodblocks-js` to <html> so CSS can gate animation initial states
 *   on JS-availability (prevents invisible hero text when JS is disabled)
 */

// Mark JS as available — runs synchronously at script load so CSS gating works
// from the first opportunity (defer means this fires before DOMContentLoaded).
document.documentElement.classList.add( 'goodblocks-js' );

const prefersReducedMotion = window.matchMedia(
	'(prefers-reduced-motion: reduce)'
).matches;

function splitWords( el ) {
	const headings = el.querySelectorAll( '.hero-block__text h2' );
	headings.forEach( ( h2 ) => {
		// textContent only — preserves rubrik fallback for any rich-text content
		const text = h2.textContent || '';
		const words = text.split( /\s+/ ).filter( Boolean );
		if ( words.length === 0 ) {
			return;
		}
		h2.innerHTML = words
			.map(
				( word, i ) =>
					`<span class="hero-block__word" style="--word-index:${ i }">${ word }</span>`
			)
			.join( ' ' );
	} );
}

function setupScrollArrow( el ) {
	const arrow = el.querySelector( '.hero-block__scroll-arrow' );
	if ( ! arrow ) {
		return;
	}
	arrow.addEventListener( 'click', () => {
		// Find next sibling element after the hero block
		let next = el.nextElementSibling;
		while ( next && ! ( next instanceof Element ) ) {
			next = next.nextSibling;
		}
		if ( next ) {
			next.scrollIntoView( {
				behavior: prefersReducedMotion ? 'auto' : 'smooth',
				block: 'start',
			} );
		} else {
			window.scrollBy( {
				top: window.innerHeight,
				behavior: prefersReducedMotion ? 'auto' : 'smooth',
			} );
		}
	} );
}

function initHero( el ) {
	const isFadeUp = el.classList.contains( 'hero-block--fade-up' );
	const isSplitWords = el.classList.contains( 'hero-block--split-words' );

	// Skip word-splitting if user prefers reduced motion (no animation = no need)
	if ( isSplitWords && ! prefersReducedMotion ) {
		splitWords( el );
	}

	// IntersectionObserver toggles `.is-in-view` when block enters viewport.
	// CSS-keyframes are gated on this class.
	if ( ( isFadeUp || isSplitWords ) && 'IntersectionObserver' in window ) {
		const observer = new IntersectionObserver(
			( entries ) => {
				entries.forEach( ( entry ) => {
					if ( entry.isIntersecting ) {
						el.classList.add( 'is-in-view' );
						observer.disconnect();
					}
				} );
			},
			{ threshold: 0.2 }
		);
		observer.observe( el );
	} else if ( isFadeUp || isSplitWords ) {
		// Fallback for browsers without IntersectionObserver: animate immediately
		el.classList.add( 'is-in-view' );
	}

	setupScrollArrow( el );
}

function initAllHeroes() {
	document
		.querySelectorAll( '.wp-block-goodblocks-hero' )
		.forEach( initHero );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', initAllHeroes );
} else {
	initAllHeroes();
}

/**
 * Campaign popup frontend behaviour.
 *
 * The campaign is selected server-side. This file handles its device rule,
 * frequency cap and accessible modal behaviour without exposing private data.
 */

const focusableSelector =
	'a[href], area[href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled]), [tabindex]:not([tabindex="-1"])';

function getCookieValue( name ) {
	const prefix = `${ name }=`;
	const item = document.cookie
		.split( ';' )
		.map( ( cookie ) => cookie.trim() )
		.find( ( cookie ) => cookie.startsWith( prefix ) );

	return item ? decodeURIComponent( item.slice( prefix.length ) ) : '';
}

function setCookie( name, value, days ) {
	const expires = new Date( Date.now() + days * 864e5 ).toUTCString();
	document.cookie = `${ name }=${ encodeURIComponent(
		value
	) }; expires=${ expires }; path=/; SameSite=Lax`;
}

function appliesToDevice( device ) {
	if ( device === 'desktop' ) {
		return window.matchMedia( '(min-width: 768px)' ).matches;
	}
	if ( device === 'mobile' ) {
		return window.matchMedia( '(max-width: 767px)' ).matches;
	}

	return true;
}

function initPopup( block ) {
	const device = block.dataset.device || 'all';
	if ( ! appliesToDevice( device ) ) {
		return;
	}

	const cookieName = block.dataset.cookieName || 'gb_popup_1';
	const maxImpressions = parseInt( block.dataset.maxImpressions || '1', 10 );
	const impressions = parseInt( getCookieValue( cookieName ) || '0', 10 );
	if ( impressions >= maxImpressions ) {
		return;
	}

	const cookieDays = parseInt( block.dataset.cookieDays || '7', 10 );
	const trigger = block.dataset.trigger || 'time';
	const delay = parseInt( block.dataset.delay || '3', 10 ) * 1000;
	const scrollPercent = parseInt( block.dataset.scrollPercent || '50', 10 );
	const backdrop = block.querySelector( '.popup-backdrop' );
	const closeButton = block.querySelector( '.popup-close' );
	const modal = block.querySelector( '.popup-modal' );
	let lastFocusedElement;
	let visible = false;

	function getFocusableElements() {
		return [ ...modal.querySelectorAll( focusableSelector ) ].filter(
			( element ) => element.offsetParent !== null
		);
	}

	function close() {
		if ( ! visible ) {
			return;
		}

		visible = false;
		block.classList.remove( 'is-visible' );
		block.setAttribute( 'aria-hidden', 'true' );
		document.body.classList.remove( 'goodblocks-popup-open' );
		block.addEventListener(
			'transitionend',
			() => {
				block.style.display = 'none';
			},
			{ once: true }
		);
		if ( lastFocusedElement instanceof HTMLElement ) {
			lastFocusedElement.focus();
		}
	}

	function handleKeydown( event ) {
		if ( ! visible ) {
			return;
		}
		if ( event.key === 'Escape' ) {
			event.preventDefault();
			close();
			return;
		}
		if ( event.key !== 'Tab' ) {
			return;
		}

		const focusable = getFocusableElements();
		if ( ! focusable.length ) {
			event.preventDefault();
			modal.focus();
			return;
		}

		const first = focusable[ 0 ];
		const last = focusable[ focusable.length - 1 ];
		if ( event.shiftKey && block.ownerDocument.activeElement === first ) {
			event.preventDefault();
			last.focus();
		} else if (
			! event.shiftKey &&
			block.ownerDocument.activeElement === last
		) {
			event.preventDefault();
			first.focus();
		}
	}

	function show() {
		if ( visible ) {
			return;
		}

		visible = true;
		lastFocusedElement = block.ownerDocument.activeElement;
		setCookie( cookieName, String( impressions + 1 ), cookieDays );
		block.style.display = '';
		block.setAttribute( 'aria-hidden', 'false' );
		document.body.classList.add( 'goodblocks-popup-open' );
		requestAnimationFrame( () => {
			block.classList.add( 'is-visible' );
			const focusable = getFocusableElements();
			( focusable[ 0 ] || modal ).focus();
		} );
	}

	closeButton?.addEventListener( 'click', close );
	backdrop?.addEventListener( 'click', close );
	document.addEventListener( 'keydown', handleKeydown );

	if ( trigger === 'time' ) {
		window.setTimeout( show, delay );
	} else if ( trigger === 'scroll' ) {
		const onScroll = () => {
			const scrollable = document.body.scrollHeight - window.innerHeight;
			if (
				scrollable > 0 &&
				( window.scrollY / scrollable ) * 100 >= scrollPercent
			) {
				show();
				window.removeEventListener( 'scroll', onScroll );
			}
		};
		window.addEventListener( 'scroll', onScroll, { passive: true } );
	} else if (
		trigger === 'exit' &&
		window.matchMedia( '(hover: hover) and (pointer: fine)' ).matches
	) {
		const onMouseMove = ( event ) => {
			if ( event.clientY < window.innerHeight * 0.05 ) {
				show();
				document.removeEventListener( 'mousemove', onMouseMove );
			}
		};
		document.addEventListener( 'mousemove', onMouseMove );
	}
}

function initPopups() {
	document
		.querySelectorAll( '.wp-block-goodblocks-popup' )
		.forEach( initPopup );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', initPopups );
} else {
	initPopups();
}

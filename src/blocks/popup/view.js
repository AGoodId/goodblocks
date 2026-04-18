/**
 * Popup block — frontend trigger logic.
 *
 * Supports: time delay, scroll %, exit intent.
 * Cookie-based "already seen" — popup is always hidden in HTML (cache-safe).
 */

function getCookie( name ) {
	return document.cookie.split( ';' ).some( ( c ) => c.trim().startsWith( name + '=' ) );
}

function setCookie( name, days ) {
	const expires = new Date( Date.now() + days * 864e5 ).toUTCString();
	document.cookie = name + '=1; expires=' + expires + '; path=/; SameSite=Lax';
}

function initPopups() {
	document.querySelectorAll( '.wp-block-goodblocks-popup' ).forEach( ( block ) => {
		const trigger        = block.dataset.trigger || 'time';
		const delay          = parseInt( block.dataset.delay || '3', 10 ) * 1000;
		const scrollPercent  = parseInt( block.dataset.scrollPercent || '50', 10 );
		const cookieName     = block.dataset.cookieName || 'gb_popup_1';
		const cookieDays     = parseInt( block.dataset.cookieDays || '7', 10 );

		if ( getCookie( cookieName ) ) {
			return;
		}

		const backdrop = block.querySelector( '.popup-backdrop' );
		const closeBtn = block.querySelector( '.popup-close' );

		function show() {
			block.style.display = '';
			// Trigger CSS transition on next frame.
			requestAnimationFrame( () => block.classList.add( 'is-visible' ) );
		}

		function close() {
			block.classList.remove( 'is-visible' );
			block.addEventListener( 'transitionend', () => {
				block.style.display = 'none';
			}, { once: true } );
			setCookie( cookieName, cookieDays );
		}

		closeBtn?.addEventListener( 'click', close );
		backdrop?.addEventListener( 'click', close );

		// Close on Escape key.
		document.addEventListener( 'keydown', ( e ) => {
			if ( e.key === 'Escape' && block.classList.contains( 'is-visible' ) ) {
				close();
			}
		} );

		if ( trigger === 'time' ) {
			setTimeout( show, delay );

		} else if ( trigger === 'scroll' ) {
			function onScroll() {
				const scrollable = document.body.scrollHeight - window.innerHeight;
				if ( scrollable <= 0 ) {
					return;
				}
				const pct = ( window.scrollY / scrollable ) * 100;
				if ( pct >= scrollPercent ) {
					show();
					window.removeEventListener( 'scroll', onScroll );
				}
			}
			window.addEventListener( 'scroll', onScroll, { passive: true } );

		} else if ( trigger === 'exit' ) {
			// Desktop only — devices with a real pointer/hover.
			if ( ! window.matchMedia( '(hover: hover) and (pointer: fine)' ).matches ) {
				return;
			}
			function onMouseMove( e ) {
				if ( e.clientY < window.innerHeight * 0.05 ) {
					show();
					document.removeEventListener( 'mousemove', onMouseMove );
				}
			}
			document.addEventListener( 'mousemove', onMouseMove );
		}
	} );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', initPopups );
} else {
	initPopups();
}

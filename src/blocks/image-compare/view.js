/**
 * Image Compare Block - Frontend interaction.
 *
 * Handles mouse, touch, and keyboard input for the comparison handle.
 */

function init() {
	document
		.querySelectorAll( '.wp-block-goodblocks-image-compare' )
		.forEach( setup );
}

function setup( container ) {
	const handle = container.querySelector( '.image-compare__handle' );
	const before = container.querySelector( '.image-compare__before' );
	if ( ! handle || ! before ) {
		return;
	}

	const startPos = parseFloat( container.dataset.start ) || 50;
	let isDragging = false;
	let containerRect;

	// Set initial position.
	setPosition( startPos );

	// --- Mouse ---
	handle.addEventListener( 'mousedown', ( e ) => {
		e.preventDefault();
		startDrag();
	} );

	container.addEventListener( 'mousedown', ( e ) => {
		if ( e.target === handle || handle.contains( e.target ) ) {
			return;
		}
		startDrag();
		updateFromEvent( e );
	} );

	const onMouseMove = ( e ) => {
		if ( ! isDragging ) {
			return;
		}
		updateFromEvent( e );
	};

	const onMouseUp = () => {
		if ( isDragging ) {
			stopDrag();
		}
	};

	document.addEventListener( 'mousemove', onMouseMove );
	document.addEventListener( 'mouseup', onMouseUp );

	// --- Touch ---
	handle.addEventListener(
		'touchstart',
		( e ) => {
			e.preventDefault();
			startDrag();
		},
		{ passive: false }
	);

	container.addEventListener(
		'touchstart',
		( e ) => {
			if ( e.target === handle || handle.contains( e.target ) ) {
				return;
			}
			startDrag();
			updateFromEvent( e.touches[ 0 ] );
		},
		{ passive: false }
	);

	const onTouchMove = ( e ) => {
		if ( ! isDragging ) {
			return;
		}
		e.preventDefault();
		updateFromEvent( e.touches[ 0 ] );
	};

	const onTouchEnd = () => {
		if ( isDragging ) {
			stopDrag();
		}
	};

	document.addEventListener( 'touchmove', onTouchMove, { passive: false } );
	document.addEventListener( 'touchend', onTouchEnd );

	// --- Keyboard ---
	handle.addEventListener( 'keydown', ( e ) => {
		const step = e.shiftKey ? 10 : 1;
		const current =
			parseFloat( handle.getAttribute( 'aria-valuenow' ) ) || 50;
		let next;

		switch ( e.key ) {
			case 'ArrowLeft':
			case 'ArrowDown':
				e.preventDefault();
				next = Math.max( 0, current - step );
				break;
			case 'ArrowRight':
			case 'ArrowUp':
				e.preventDefault();
				next = Math.min( 100, current + step );
				break;
			case 'Home':
				e.preventDefault();
				next = 0;
				break;
			case 'End':
				e.preventDefault();
				next = 100;
				break;
			default:
				return;
		}
		setPosition( next );
	} );

	function startDrag() {
		isDragging = true;
		containerRect = container.getBoundingClientRect();
		container.classList.add( 'is-dragging' );
	}

	function stopDrag() {
		isDragging = false;
		container.classList.remove( 'is-dragging' );
	}

	function updateFromEvent( e ) {
		const x = e.clientX - containerRect.left;
		const pct = ( x / containerRect.width ) * 100;
		setPosition( pct );
	}

	function setPosition( pct ) {
		pct = Math.max( 0, Math.min( 100, pct ) );
		before.style.clipPath = 'inset(0 ' + ( 100 - pct ) + '% 0 0)';
		handle.style.left = pct + '%';
		handle.setAttribute( 'aria-valuenow', Math.round( pct ) );
	}

	// Recalculate on resize.
	if ( window.ResizeObserver ) {
		new ResizeObserver( () => {
			if ( isDragging ) {
				containerRect = container.getBoundingClientRect();
			}
		} ).observe( container );
	}
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}

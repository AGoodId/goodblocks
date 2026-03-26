/**
 * Image Compare Block — Frontend interaction.
 *
 * Features:
 *   - Mouse, touch, and keyboard input for the comparison handle
 *   - Auto-tease animation that attracts attention
 *   - IntersectionObserver triggers animation when block enters viewport
 *   - User interaction immediately takes over from animation
 *   - Supports horizontal and vertical orientation
 *   - Respects prefers-reduced-motion
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

	const isVertical = container.classList.contains( 'is-vertical' );
	const startPos = parseFloat( container.dataset.start ) || 50;
	const enableTease = container.dataset.tease === '1';
	const teaseSpeed = parseFloat( container.dataset.teaseSpeed ) || 3;
	const teaseOnce = container.dataset.teaseOnce === '1';
	const prefersReducedMotion = window.matchMedia(
		'(prefers-reduced-motion: reduce)'
	).matches;

	let isDragging = false;
	let containerRect;
	let userTookOver = false;
	let teaseAnimId = null;
	let teaseRunning = false;

	// Set initial position.
	setPosition( startPos );

	// --- Auto-tease animation ---

	function easeInOutCubic( t ) {
		return t < 0.5
			? 4 * t * t * t
			: 1 - Math.pow( -2 * t + 2, 3 ) / 2;
	}

	function startTease() {
		if ( teaseRunning || userTookOver || prefersReducedMotion ) {
			return;
		}
		teaseRunning = true;
		container.classList.add( 'is-teasing' );

		const cycleDuration = teaseSpeed * 1000;
		const rangeMin = Math.max( 5, startPos - 35 );
		const rangeMax = Math.min( 95, startPos + 35 );
		let teaseStart = null;
		let cycleCount = 0;

		function tick( timestamp ) {
			if ( ! teaseRunning ) {
				return;
			}
			if ( ! teaseStart ) {
				teaseStart = timestamp;
			}

			const elapsed = timestamp - teaseStart;
			const cycleProgress = ( elapsed % cycleDuration ) / cycleDuration;
			const currentCycle = Math.floor( elapsed / cycleDuration );

			if ( teaseOnce && currentCycle >= 2 ) {
				stopTease();
				setPosition( startPos );
				userTookOver = true;
				return;
			}

			if ( currentCycle !== cycleCount ) {
				cycleCount = currentCycle;
			}

			// Ping-pong: 0→1 on even cycles, 1→0 on odd cycles.
			const isReverse = currentCycle % 2 === 1;
			const t = isReverse
				? 1 - easeInOutCubic( cycleProgress )
				: easeInOutCubic( cycleProgress );

			const pos = rangeMin + ( rangeMax - rangeMin ) * t;
			setPosition( pos );

			teaseAnimId = requestAnimationFrame( tick );
		}

		teaseAnimId = requestAnimationFrame( tick );
	}

	function stopTease() {
		teaseRunning = false;
		container.classList.remove( 'is-teasing' );
		if ( teaseAnimId ) {
			cancelAnimationFrame( teaseAnimId );
			teaseAnimId = null;
		}
	}

	function onUserInteraction() {
		if ( teaseRunning ) {
			stopTease();
		}
		userTookOver = true;
	}

	// Trigger tease when block enters viewport.
	if ( enableTease && ! prefersReducedMotion ) {
		if ( 'IntersectionObserver' in window ) {
			const observer = new IntersectionObserver(
				( entries ) => {
					entries.forEach( ( entry ) => {
						if ( entry.isIntersecting && ! userTookOver ) {
							startTease();
						} else if ( ! entry.isIntersecting && teaseRunning ) {
							stopTease();
						}
					} );
				},
				{ threshold: 0.3 }
			);
			observer.observe( container );
		} else {
			// Fallback: start immediately.
			startTease();
		}
	}

	// --- Mouse ---
	handle.addEventListener( 'mousedown', ( e ) => {
		e.preventDefault();
		onUserInteraction();
		startDrag();
	} );

	container.addEventListener( 'mousedown', ( e ) => {
		if ( e.target === handle || handle.contains( e.target ) ) {
			return;
		}
		onUserInteraction();
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
			onUserInteraction();
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
			onUserInteraction();
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
		onUserInteraction();
		const step = e.shiftKey ? 10 : 1;
		const current =
			parseFloat( handle.getAttribute( 'aria-valuenow' ) ) || 50;
		let next;

		if ( isVertical ) {
			switch ( e.key ) {
				case 'ArrowUp':
				case 'ArrowLeft':
					e.preventDefault();
					next = Math.max( 0, current - step );
					break;
				case 'ArrowDown':
				case 'ArrowRight':
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
		} else {
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
		if ( isVertical ) {
			const y = e.clientY - containerRect.top;
			const pct = ( y / containerRect.height ) * 100;
			setPosition( pct );
		} else {
			const x = e.clientX - containerRect.left;
			const pct = ( x / containerRect.width ) * 100;
			setPosition( pct );
		}
	}

	function setPosition( pct ) {
		pct = Math.max( 0, Math.min( 100, pct ) );
		if ( isVertical ) {
			before.style.clipPath =
				'inset(0 0 ' + ( 100 - pct ) + '% 0)';
			handle.style.top = pct + '%';
			handle.style.left = '';
		} else {
			before.style.clipPath =
				'inset(0 ' + ( 100 - pct ) + '% 0 0)';
			handle.style.left = pct + '%';
			handle.style.top = '';
		}
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

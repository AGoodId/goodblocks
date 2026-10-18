const preference = window.matchMedia(
	'(min-width: 960px) and (prefers-reduced-motion: no-preference)'
);
document.querySelectorAll( '.gb-sticky-cards' ).forEach( ( stack ) => {
	const cards = Array.from( stack.children ).filter( ( card ) =>
		card.classList.contains( 'wp-block-group' )
	);
	let frame;
	const update = () => {
		cancelAnimationFrame( frame );
		frame = requestAnimationFrame( () => {
			const styles = getComputedStyle( stack );
			const offset =
				parseFloat( styles.getPropertyValue( '--gb-card-offset' ) ) ||
				0;
			const gap =
				parseFloat( styles.getPropertyValue( '--gb-card-gap' ) ) || 0;
			const fits = cards.every(
				( card, index ) =>
					card.offsetHeight + offset + index * gap + 32 <
					window.innerHeight
			);
			stack.classList.toggle(
				'gb-stack-ready',
				preference.matches &&
					cards.length > 1 &&
					fits &&
					! stack.contains( stack.ownerDocument.activeElement )
			);
		} );
	};
	cards.forEach( ( card, index ) =>
		card.style.setProperty( '--gb-card-index', index )
	);
	// Keyboard users receive the ordinary document flow; no focused link is covered.
	stack.addEventListener( 'focusin', () =>
		stack.classList.remove( 'gb-stack-ready' )
	);
	stack.addEventListener( 'focusout', update );
	preference.addEventListener( 'change', update );
	window.addEventListener( 'resize', update, { passive: true } );
	if ( window.ResizeObserver ) {
		const observer = new ResizeObserver( update );
		cards.forEach( ( card ) => observer.observe( card ) );
	}
	update();
} );

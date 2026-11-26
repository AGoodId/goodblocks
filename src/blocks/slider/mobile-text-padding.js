/**
 * Optional pixel padding. Missing or malformed values preserve existing HTML.
 * @param {string} layout  Slider layout.
 * @param {Object} padding Four pixel insets.
 */
export function mobileTextPaddingStyle( layout, padding ) {
	if (
		layout !== 'overlay' ||
		! padding ||
		typeof padding !== 'object' ||
		Array.isArray( padding )
	) {
		return {};
	}
	const sides = [ 'top', 'right', 'bottom', 'left' ];
	if (
		! sides.every(
			( side ) =>
				Number.isInteger( padding[ side ] ) &&
				padding[ side ] >= ( side === 'bottom' ? 80 : 0 ) &&
				padding[ side ] <= 800
		)
	) {
		return {};
	}
	return Object.fromEntries(
		sides.map( ( side ) => [
			`--slider-mobile-text-pad-${ side }`,
			`${ padding[ side ] }px`,
		] )
	);
}

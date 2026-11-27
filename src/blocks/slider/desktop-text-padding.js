/**
 * Optional large-screen text flow; invalid values keep legacy HTML.
 * @param {string} layout  Slider layout.
 * @param {Object} padding Four pixel insets.
 */
export function desktopTextPaddingStyle( layout, padding ) {
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
				padding[ side ] >= 0 &&
				padding[ side ] <= 800
		)
	) {
		return {};
	}
	return Object.fromEntries(
		sides.map( ( side ) => [
			`--slider-desktop-text-pad-${ side }`,
			`${ padding[ side ] }px`,
		] )
	);
}

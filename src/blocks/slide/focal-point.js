/**
 * Preserve legacy markup when no valid, explicit focal point was saved.
 *
 * @param {Object|undefined} point Saved x/y coordinates between zero and one.
 */
export function focalPointStyle( point ) {
	if (
		! point ||
		! [ point.x, point.y ].every(
			( value ) => Number.isFinite( value ) && value >= 0 && value <= 1
		)
	) {
		return {};
	}
	const percent = ( value ) => Number( ( value * 100 ).toFixed( 6 ) );
	return {
		'--slide-object-position': `${ percent( point.x ) }% ${ percent(
			point.y
		) }%`,
	};
}

/**
 * Frontend JavaScript for the Countdown block.
 */

function initCountdown() {
	const countdownBlocks = document.querySelectorAll(
		'.wp-block-goodblocks-countdown'
	);

	countdownBlocks.forEach( ( block ) => {
		const targetDateElement = block.querySelector( '[data-target-date]' );
		if ( ! targetDateElement ) {
			return;
		}

		const targetDate = targetDateElement.getAttribute( 'data-target-date' );
		const showSeconds =
			targetDateElement.getAttribute( 'data-show-seconds' ) === 'true';

		if ( ! targetDate ) {
			return;
		}

		const previousValues = {};
		let isFirstLoad = true;

		function animateNumberChange( element, newValue, oldValue ) {
			if ( ! element ) {
				return;
			}

			element.classList.add( 'spinning' );

			setTimeout( () => {
				element.textContent = newValue.toString().padStart( 2, '0' );
				element.classList.remove( 'spinning' );

				if ( oldValue !== newValue && ! isFirstLoad ) {
					element.classList.add( 'bounce' );
					setTimeout( () => {
						element.classList.remove( 'bounce' );
					}, 300 );
				}
			}, 200 );
		}

		function updateCountdown() {
			const now = new Date().getTime();
			const target = new Date( targetDate ).getTime();
			const difference = target - now;

			if ( difference > 0 ) {
				const days = Math.floor( difference / ( 1000 * 60 * 60 * 24 ) );
				const hours = Math.floor(
					( difference % ( 1000 * 60 * 60 * 24 ) ) /
						( 1000 * 60 * 60 )
				);
				const minutes = Math.floor(
					( difference % ( 1000 * 60 * 60 ) ) / ( 1000 * 60 )
				);
				const seconds = Math.floor(
					( difference % ( 1000 * 60 ) ) / 1000
				);

				const daysElement = block.querySelector(
					'.countdown-days .countdown-number'
				);
				const hoursElement = block.querySelector(
					'.countdown-hours .countdown-number'
				);
				const minutesElement = block.querySelector(
					'.countdown-minutes .countdown-number'
				);
				const secondsElement = block.querySelector(
					'.countdown-seconds .countdown-number'
				);

				if ( daysElement ) {
					animateNumberChange(
						daysElement,
						days,
						previousValues.days
					);
					previousValues.days = days;
				}

				if ( hoursElement ) {
					animateNumberChange(
						hoursElement,
						hours,
						previousValues.hours
					);
					previousValues.hours = hours;
				}

				if ( minutesElement ) {
					animateNumberChange(
						minutesElement,
						minutes,
						previousValues.minutes
					);
					previousValues.minutes = minutes;
				}

				if ( secondsElement && showSeconds ) {
					animateNumberChange(
						secondsElement,
						seconds,
						previousValues.seconds
					);
					previousValues.seconds = seconds;
				}

				isFirstLoad = false;
			} else {
				block.innerHTML =
					'<div class="countdown-finished"><p>Time\'s up!</p></div>';
			}
		}

		updateCountdown();
		setInterval( updateCountdown, 1000 );
	} );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', initCountdown );
} else {
	initCountdown();
}

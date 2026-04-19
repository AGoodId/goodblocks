/**
 * Testimonials Block — Frontend (Swiper).
 */

import Swiper from 'swiper';
import { Navigation, Pagination, Autoplay, EffectFade } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';
import 'swiper/css/effect-fade';

function initTestimonials() {
	const prefersReducedMotion = window.matchMedia(
		'(prefers-reduced-motion: reduce)'
	).matches;

	document
		.querySelectorAll( '.wp-block-goodblocks-testimonials' )
		.forEach( ( el ) => {
			const slides = el.querySelectorAll( '.swiper-slide' );
			if ( ! slides.length ) {
				return;
			}

			const autoplay = el.dataset.autoplay === 'true';
			const autoplayDelay = parseInt( el.dataset.autoplayDelay ) || 5000;
			const animation = el.dataset.animation || 'fade';
			const showArrows = el.dataset.showArrows === 'true';
			const showDots = el.dataset.showDots === 'true';
			const prevLabel = el.dataset.prevLabel || 'Previous testimonial';
			const nextLabel = el.dataset.nextLabel || 'Next testimonial';
			const effectiveAutoplay = autoplay && ! prefersReducedMotion;

			const config = {
				modules: [ Navigation, Pagination, Autoplay, EffectFade ],
				// Only loop when there are multiple slides — Swiper clones
				// slides for loop mode and breaks with a single slide.
				loop: slides.length > 1,
				speed: 600,
				a11y: {
					prevSlideMessage: prevLabel,
					nextSlideMessage: nextLabel,
				},
			};

			if ( animation === 'fade' ) {
				config.effect = 'fade';
				config.fadeEffect = { crossFade: true };
			}

			if ( showArrows ) {
				config.navigation = {
					prevEl: el.querySelector( '.swiper-button-prev' ),
					nextEl: el.querySelector( '.swiper-button-next' ),
				};
			}

			if ( showDots ) {
				config.pagination = {
					el: el.querySelector( '.swiper-pagination' ),
					clickable: true,
				};
			}

			if ( effectiveAutoplay ) {
				config.autoplay = {
					delay: autoplayDelay,
					disableOnInteraction: false,
					pauseOnMouseEnter: true,
				};
			}

			const swiper = new Swiper( el, config );

			const toggleBtn = el.querySelector(
				'.testimonials-autoplay-toggle'
			);
			if ( toggleBtn && effectiveAutoplay ) {
				toggleBtn.addEventListener( 'click', () => {
					if ( toggleBtn.dataset.state === 'playing' ) {
						swiper.autoplay.stop();
						toggleBtn.dataset.state = 'paused';
						toggleBtn.setAttribute(
							'aria-label',
							toggleBtn.dataset.labelPlay
						);
					} else {
						swiper.autoplay.start();
						toggleBtn.dataset.state = 'playing';
						toggleBtn.setAttribute(
							'aria-label',
							toggleBtn.dataset.labelPause
						);
					}
				} );
			} else if ( toggleBtn ) {
				// prefers-reduced-motion: autoplay never started, hide the button.
				toggleBtn.hidden = true;
			}
		} );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', initTestimonials );
} else {
	initTestimonials();
}

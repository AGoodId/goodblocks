/**
 * Product Carousel Block — Frontend (Swiper).
 */

import Swiper from 'swiper';
import { Navigation, Pagination } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/navigation';

document.addEventListener( 'DOMContentLoaded', function () {
	document.querySelectorAll( '.product-carousel-swiper' ).forEach( ( el ) => {
		const prev = el.querySelector( '.swiper-button-prev' );
		const next = el.querySelector( '.swiper-button-next' );

		new Swiper( el, {
			modules: [ Navigation, Pagination ],
			loop: false,
			spaceBetween: 10,
			freeMode: true,
			watchSlidesProgress: true,
			centeredSlides: false,
			slidesPerGroupAuto: true,
			slidesPerView: 'auto',
			navigation: {
				nextEl: next,
				prevEl: prev,
			},
			breakpoints: {
				320: { slidesPerView: 1.5 },
				768: { slidesPerView: 2.5 },
				1024: { slidesPerView: 3 },
				1200: { slidesPerView: 4 },
			},
		} );
	} );
} );

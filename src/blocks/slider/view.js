/**
 * Slider Block — Frontend (Swiper).
 */

import Swiper from 'swiper';
import {
	Navigation,
	Pagination,
	Autoplay,
	EffectFade,
	EffectCube,
	EffectFlip,
	EffectCoverflow,
} from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';
import 'swiper/css/effect-fade';
import 'swiper/css/effect-cube';
import 'swiper/css/effect-flip';
import 'swiper/css/effect-coverflow';

document.addEventListener( 'DOMContentLoaded', function () {
	document
		.querySelectorAll( '.slider-container' )
		.forEach( ( sliderContainer ) => {
			const slides = sliderContainer.querySelectorAll(
				'.wp-block-goodblocks-slide'
			);
			if ( ! slides.length ) return;

			const showNavigation =
				sliderContainer.dataset.showNavigation === 'true';
			const showPagination =
				sliderContainer.dataset.showPagination === 'true';
			const effect = sliderContainer.dataset.effect || 'fade';
			const autoplay = sliderContainer.dataset.autoplay === 'true';
			const autoplayDelay =
				parseInt( sliderContainer.dataset.autoplayDelay ) || 5000;
			const layout = sliderContainer.dataset.layout || 'overlay';
			const sliderHeight =
				parseInt( sliderContainer.dataset.sliderHeight ) || 100;

			const swiperWrapper = document.createElement( 'div' );
			swiperWrapper.className = 'swiper-wrapper';

			slides.forEach( ( slide ) => {
				const objectFit = slide.dataset.objectFit || 'cover';
				const linkUrl = slide.dataset.linkUrl || '';
				const linkTarget = slide.dataset.linkTarget || '_self';

				if ( layout === 'below' ) {
					const figure = document.createElement( 'figure' );
					figure.className = 'swiper-slide slide-item layout-below';
					figure.style.setProperty( '--object-fit', objectFit );

					const mediaDiv = slide.querySelector( '.slide-media' );
					if ( mediaDiv ) {
						figure.appendChild( mediaDiv.cloneNode( true ) );
					}

					const caption = slide.dataset.caption;
					if ( caption && caption.trim() !== '' ) {
						const figcaption = document.createElement( 'figcaption' );
						figcaption.className = 'slide-caption-content';
						const captionP = document.createElement( 'p' );
						captionP.className = 'slide-caption';
						captionP.textContent = caption;
						figcaption.appendChild( captionP );
						figure.appendChild( figcaption );
					}

					if ( linkUrl && linkUrl.trim() !== '' ) {
						const link = document.createElement( 'a' );
						link.href = linkUrl;
						link.target = linkTarget;
						if ( linkTarget === '_blank' ) {
							link.rel = 'noopener noreferrer';
						}
						link.className = 'slide-link';
						link.style.cssText =
							'position:absolute;top:0;left:0;width:100%;height:100%;z-index:10;text-decoration:none;color:inherit;';
						figure.appendChild( link );
					}

					swiperWrapper.appendChild( figure );
				} else {
					slide.classList.add( 'swiper-slide' );
					slide.classList.remove( 'is-active' );
					slide.style.setProperty( '--object-fit', objectFit );

					if (
						linkUrl &&
						linkUrl.trim() !== '' &&
						! slide.querySelector( '.slide-link' )
					) {
						const link = document.createElement( 'a' );
						link.href = linkUrl;
						link.target = linkTarget;
						if ( linkTarget === '_blank' ) {
							link.rel = 'noopener noreferrer';
						}
						link.className = 'slide-link';
						link.style.cssText =
							'position:absolute;top:0;left:0;width:100%;height:100%;z-index:10;text-decoration:none;color:inherit;';
						slide.appendChild( link );
					}

					swiperWrapper.appendChild( slide );
				}
			} );

			sliderContainer.innerHTML = '';
			sliderContainer.appendChild( swiperWrapper );

			if ( showNavigation ) {
				const prevBtn = document.createElement( 'div' );
				prevBtn.className = 'swiper-button-prev';
				sliderContainer.appendChild( prevBtn );
				const nextBtn = document.createElement( 'div' );
				nextBtn.className = 'swiper-button-next';
				sliderContainer.appendChild( nextBtn );
			}

			if ( showPagination ) {
				const pagination = document.createElement( 'div' );
				pagination.className = 'swiper-pagination';
				sliderContainer.appendChild( pagination );
			}

			sliderContainer.classList.add( 'swiper' );
			sliderContainer.classList.add( `layout-${ layout }` );
			sliderContainer.style.setProperty(
				'--slider-height',
				`${ sliderHeight }vh`
			);

			const swiperConfig = {
				modules: [
					Navigation,
					Pagination,
					Autoplay,
					EffectFade,
					EffectCube,
					EffectFlip,
					EffectCoverflow,
				],
				speed: 600,
				loop: true,
				keyboard: { enabled: true },
				a11y: {
					prevSlideMessage: 'Previous slide',
					nextSlideMessage: 'Next slide',
				},
			};

			if ( effect === 'fade' ) {
				swiperConfig.effect = 'fade';
				swiperConfig.fadeEffect = { crossFade: true };
			} else if ( effect === 'cube' ) {
				swiperConfig.effect = 'cube';
			} else if ( effect === 'flip' ) {
				swiperConfig.effect = 'flip';
			} else if ( effect === 'coverflow' ) {
				swiperConfig.effect = 'coverflow';
			}

			if ( showNavigation ) {
				swiperConfig.navigation = {
					nextEl: '.swiper-button-next',
					prevEl: '.swiper-button-prev',
				};
			}

			if ( showPagination ) {
				swiperConfig.pagination = {
					el: '.swiper-pagination',
					clickable: true,
					dynamicBullets: slides.length > 5,
				};
			}

			if ( autoplay ) {
				swiperConfig.autoplay = {
					delay: autoplayDelay,
					disableOnInteraction: false,
					pauseOnMouseEnter: true,
				};
			}

			new Swiper( sliderContainer, swiperConfig );
		} );
} );

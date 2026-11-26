/**
 * Slider Block — Frontend (Swiper).
 */

import Swiper from 'swiper';
import { __ } from '@wordpress/i18n';
import {
	A11y,
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
			if ( ! slides.length ) {
				return;
			}

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
				const linkLabel =
					slide
						.querySelector( '.slide-heading' )
						?.textContent?.trim() ||
					slide.querySelector( 'img' )?.alt ||
					slide.dataset.caption ||
					__( 'View slide', 'goodblocks' );
				const objectFit = slide.dataset.objectFit || 'cover';
				const linkUrl = slide.dataset.linkUrl || '';
				const linkTarget = slide.dataset.linkTarget || '_self';

				if ( layout === 'below' ) {
					const figure = document.createElement( 'figure' );
					figure.className = 'swiper-slide slide-item layout-below';
					figure.style.setProperty( '--object-fit', objectFit );
					const focalPoint = slide.style.getPropertyValue(
						'--slide-object-position'
					);
					if ( focalPoint ) {
						figure.style.setProperty(
							'--slide-object-position',
							focalPoint
						);
					}

					const mediaDiv = slide.querySelector( '.slide-media' );
					if ( mediaDiv ) {
						figure.appendChild( mediaDiv.cloneNode( true ) );
					}

					const caption = slide.dataset.caption;
					if ( caption && caption.trim() !== '' ) {
						const figcaption =
							document.createElement( 'figcaption' );
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
						link.setAttribute( 'aria-label', linkLabel );
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
						link.setAttribute( 'aria-label', linkLabel );
						link.style.cssText =
							'position:absolute;top:0;left:0;width:100%;height:100%;z-index:10;text-decoration:none;color:inherit;';
						slide.appendChild( link );
					}

					swiperWrapper.appendChild( slide );
				}
			} );

			sliderContainer.innerHTML = '';
			sliderContainer.appendChild( swiperWrapper );

			let prevBtn;
			let nextBtn;
			let pagination;
			if ( showNavigation && slides.length > 1 ) {
				prevBtn = document.createElement( 'button' );
				prevBtn.type = 'button';
				prevBtn.setAttribute(
					'aria-label',
					__( 'Previous slide', 'goodblocks' )
				);
				prevBtn.className = 'swiper-button-prev';
				sliderContainer.appendChild( prevBtn );
				nextBtn = document.createElement( 'button' );
				nextBtn.type = 'button';
				nextBtn.setAttribute(
					'aria-label',
					__( 'Next slide', 'goodblocks' )
				);
				nextBtn.className = 'swiper-button-next';
				sliderContainer.appendChild( nextBtn );
			}

			if ( showPagination && slides.length > 1 ) {
				pagination = document.createElement( 'div' );
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
					A11y,
					Navigation,
					Pagination,
					Autoplay,
					EffectFade,
					EffectCube,
					EffectFlip,
					EffectCoverflow,
				],
				speed: 600,
				loop: slides.length > 1,
				a11y: {
					containerRole: 'region',
					containerMessage: __( 'Slideshow', 'goodblocks' ),
					prevSlideMessage: __( 'Previous slide', 'goodblocks' ),
					nextSlideMessage: __( 'Next slide', 'goodblocks' ),
					paginationBulletMessage: __(
						'Go to slide {{index}}',
						'goodblocks'
					),
					containerRoleDescriptionMessage: __(
						'Carousel',
						'goodblocks'
					),
					itemRoleDescriptionMessage: __( 'Slide', 'goodblocks' ),
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

			if ( prevBtn && nextBtn ) {
				swiperConfig.navigation = {
					nextEl: nextBtn,
					prevEl: prevBtn,
				};
			}

			if ( pagination ) {
				swiperConfig.pagination = {
					el: pagination,
					clickable: true,
					dynamicBullets: slides.length > 5,
				};
			}

			if ( autoplay && slides.length > 1 ) {
				swiperConfig.autoplay = {
					enabled: false,
					delay: autoplayDelay,
					disableOnInteraction: false,
					pauseOnMouseEnter: false,
				};
			}

			const reducedMotion = window.matchMedia(
				'(prefers-reduced-motion: reduce)'
			);
			const shouldRotate = autoplay && slides.length > 1;
			if ( reducedMotion.matches ) {
				swiperConfig.speed = 0;
			}
			const swiper = new Swiper( sliderContainer, swiperConfig );
			sliderContainer.tabIndex = slides.length > 1 ? 0 : -1;
			const updateSlides = () => {
				swiper.slides.forEach( ( slide, index ) => {
					const inactive = index !== swiper.activeIndex;
					if (
						inactive &&
						slide.contains( slide.ownerDocument.activeElement )
					) {
						sliderContainer.focus( { preventScroll: true } );
					}
					slide.inert = inactive;
					slide.setAttribute( 'aria-hidden', String( inactive ) );
				} );
			};
			swiper.on( 'slideChange', updateSlides );
			updateSlides();
			let rotationButton;
			let hoverPaused = false;
			const updateRotationButton = () => {
				swiperWrapper.setAttribute(
					'aria-live',
					swiper.autoplay.running ? 'off' : 'polite'
				);
				if ( rotationButton ) {
					rotationButton.textContent =
						swiper.autoplay.running || hoverPaused
							? __( 'Pause slideshow', 'goodblocks' )
							: __( 'Play slideshow', 'goodblocks' );
				}
			};
			const stopRotation = () => {
				hoverPaused = false;
				swiper.autoplay.stop();
				updateRotationButton();
			};
			if ( shouldRotate ) {
				let pointerWasRunning = null;
				rotationButton = document.createElement( 'button' );
				rotationButton.type = 'button';
				rotationButton.className = 'goodblocks-slider-rotation';
				// Pointerdown precedes focusin. Preserve an intended Pause click
				// when focus itself stops rotation before the click arrives.
				rotationButton.addEventListener( 'pointerdown', () => {
					pointerWasRunning = swiper.autoplay.running || hoverPaused;
				} );
				rotationButton.addEventListener( 'pointercancel', () => {
					pointerWasRunning = null;
				} );
				rotationButton.addEventListener( 'keydown', () => {
					pointerWasRunning = null;
				} );
				rotationButton.addEventListener( 'click', () => {
					if (
						pointerWasRunning === true ||
						swiper.autoplay.running ||
						hoverPaused
					) {
						stopRotation();
					} else {
						swiper.autoplay.start();
						updateRotationButton();
					}
					pointerWasRunning = null;
				} );
				sliderContainer.insertBefore( rotationButton, swiperWrapper );
				if ( ! reducedMotion.matches ) {
					swiper.autoplay.start();
				}
				updateRotationButton();
			}
			sliderContainer.addEventListener( 'focusin', stopRotation );
			// Hover temporarily suspends the user's playing state. Keeping the
			// Pause label prevents pointerenter -> focus -> click from becoming Play.
			// Focus, visibility and reduced-motion stops still require explicit Play.
			sliderContainer.addEventListener( 'pointerenter', ( event ) => {
				if (
					event.pointerType === 'mouse' &&
					swiper.autoplay.running
				) {
					hoverPaused = true;
					swiper.autoplay.stop();
					updateRotationButton();
				}
			} );
			sliderContainer.addEventListener( 'pointerleave', ( event ) => {
				if ( event.pointerType === 'mouse' && hoverPaused ) {
					hoverPaused = false;
					swiper.autoplay.start();
					updateRotationButton();
				}
			} );
			sliderContainer.addEventListener( 'keydown', ( event ) => {
				if (
					event.target.closest(
						'input, textarea, select, [contenteditable="true"]'
					)
				) {
					return;
				}
				if ( event.key === 'ArrowLeft' || event.key === 'ArrowRight' ) {
					event.preventDefault();
					stopRotation();
					if ( event.key === 'ArrowLeft' ) {
						swiper.slidePrev();
					} else {
						swiper.slideNext();
					}
				}
			} );
			const onMotionChange = ( event ) => {
				if ( event.matches ) {
					stopRotation();
				}
				swiper.params.speed = event.matches ? 0 : 600;
			};
			const onVisibilityChange = () => {
				if ( sliderContainer.ownerDocument.hidden ) {
					stopRotation();
				}
			};
			reducedMotion.addEventListener( 'change', onMotionChange );
			sliderContainer.ownerDocument.addEventListener(
				'visibilitychange',
				onVisibilityChange
			);
			swiper.on( 'destroy', () => {
				reducedMotion.removeEventListener( 'change', onMotionChange );
				sliderContainer.ownerDocument.removeEventListener(
					'visibilitychange',
					onVisibilityChange
				);
			} );
		} );
} );

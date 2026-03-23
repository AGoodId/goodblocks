/**
 * Masonry Query Block - Frontend JavaScript
 *
 * Side-by-side lightbox: 3/4 image + 1/4 text sidebar
 * - Cycles through project gallery before next project
 * - Shows title, excerpt, tags, caption, read more
 * - Keyboard + swipe navigation
 * - Deep-linking
 */

import { gsap } from 'gsap';

/* translatable strings passed via wp_localize_script */
const i18n = window.goodblocksMasonry?.i18n || {};

class MasonryQueryBlock {
	constructor( container ) {
		this.container = container;
		this.grid = container.querySelector( '.masonry-query__grid' );
		this.items = Array.from(
			container.querySelectorAll( '.masonry-query__item' )
		);
		this.filters = container.querySelectorAll( '.masonry-query__filter' );
		this.loadMoreBtn = container.querySelector(
			'.masonry-query__load-more'
		);
		this.pagination = container.querySelector(
			'.masonry-query__pagination'
		);

		this.settings = {
			blockId: container.dataset.blockId,
			clickAction: container.dataset.clickAction || 'lightbox',
			lightboxAnimation: container.dataset.lightboxAnimation || 'zoom',
			lightboxInfo: container.dataset.lightboxInfo === 'true',
			lightboxLink: container.dataset.lightboxLink === 'true',
			enableAnimation: container.dataset.enableAnimation === 'true',
			animationType: container.dataset.animationType || 'fade-up',
			animationStagger:
				parseInt( container.dataset.animationStagger ) || 50,
		};

		this.currentPage = 1;
		this.maxPages = this.pagination
			? parseInt( this.pagination.dataset.maxPages )
			: 1;
		this.isLoading = false;
		this.modal = null;
		this.currentProjectIndex = 0; // Index in visibleItems
		this.currentSlideIndex = 0; // Index within project gallery
		this.currentGallery = []; // Current project's gallery array
		this.visibleItems = this.items;
		this.originalUrl = null;

		this.init();
	}

	init() {
		this.setupMasonry();
		this.setupLightbox();
		this.setupFilters();
		this.setupPagination();
		this.setupDeepLinks();
		this.animateEntrance();
	}

	setupMasonry() {
		if ( ! this.container.classList.contains( 'masonry-query--masonry' ) ) {
			return;
		}

		this.isScrolling = false;
		this.layoutPending = false;

		// Track active touch/scroll to avoid layout during scroll (iOS fix).
		let scrollTimer;
		window.addEventListener( 'scroll', () => {
			this.isScrolling = true;
			clearTimeout( scrollTimer );
			scrollTimer = setTimeout( () => {
				this.isScrolling = false;
				if ( this.layoutPending ) {
					this.layoutPending = false;
					this.layoutMasonryGrid();
				}
			}, 150 );
		}, { passive: true } );

		const layout = () => {
			if ( this.isScrolling ) {
				this.layoutPending = true;
				return;
			}
			this.layoutMasonryGrid();
		};

		const images = this.grid.querySelectorAll( 'img' );
		let loadedCount = 0;
		const totalImages = images.length;

		const onImageLoad = () => {
			loadedCount++;
			layout();
			if ( loadedCount >= totalImages ) {
				this.container.classList.add( 'masonry-query--loaded' );
			}
		};

		images.forEach( ( img ) => {
			if ( img.complete ) {
				onImageLoad();
			} else {
				img.addEventListener( 'load', onImageLoad );
				img.addEventListener( 'error', onImageLoad );
			}
		} );

		if ( totalImages === 0 ) {
			this.container.classList.add( 'masonry-query--loaded' );
			layout();
		}

		// Recalculate on resize (debounced, skip during scroll)
		let resizeTimer;
		window.addEventListener(
			'resize',
			() => {
				clearTimeout( resizeTimer );
				resizeTimer = setTimeout( layout, 150 );
			},
			{ passive: true }
		);
	}

	layoutMasonryGrid() {
		if ( ! this.grid ) {
			return;
		}

		// Preserve scroll position to prevent iOS scroll jumps during re-layout.
		const scrollY = window.scrollY;

		const gap =
			parseFloat(
				getComputedStyle( this.container ).getPropertyValue(
					'--masonry-gap'
				)
			) || 0;

		// Temporarily use auto rows to measure natural item heights.
		this.grid.style.gridAutoRows = 'auto';
		this.items.forEach( ( item ) => {
			// Reset span to measure natural height
			item.style.gridRowEnd = '';
		} );

		// Force layout recalc
		this.grid.offsetHeight; // eslint-disable-line no-unused-expressions

		// Read natural heights while grid-auto-rows is auto.
		const heights = this.items.map(
			( item ) => item.getBoundingClientRect().height
		);

		// Switch to 1px rows for tight masonry packing.
		this.grid.style.gridAutoRows = '1px';

		// grid-auto-rows is 1px, so row N occupies 1px + gap between rows.
		// Total height for span S = S * 1 + (S - 1) * gap = S * (1 + gap) - gap.
		// Solving for S: S = ceil( (height + gap) / (1 + gap) ).
		const rowHeight = 1 + gap;

		// Write all spans (heights already measured above with auto rows).
		this.items.forEach( ( item, idx ) => {
			const span = Math.ceil( ( heights[ idx ] + gap ) / rowHeight );
			item.style.gridRowEnd = `span ${ span }`;
		} );

		// Restore scroll position after layout change (prevents iOS scroll jump).
		if ( Math.abs( window.scrollY - scrollY ) > 1 ) {
			window.scrollTo( 0, scrollY );
		}
	}

	// ========================================
	// LIGHTBOX
	// ========================================

	setupLightbox() {
		if ( this.settings.clickAction !== 'lightbox' ) {
			return;
		}

		this.createModal();

		this.items.forEach( ( item, index ) => {
			item.addEventListener( 'click', ( e ) => {
				e.preventDefault();
				this.openModal( index );
			} );
		} );

		document.addEventListener( 'keydown', ( e ) => {
			if (
				! this.modal ||
				! this.modal.classList.contains( 'is-open' )
			) {
				return;
			}
			switch ( e.key ) {
				case 'Escape':
					this.closeModal();
					break;
				case 'ArrowLeft':
					this.navigatePrev();
					break;
				case 'ArrowRight':
					this.navigateNext();
					break;
			}
		} );

		window.addEventListener( 'popstate', () => {
			this.closeModalDirect();
		} );
	}

	createModal() {
		this.modal = document.createElement( 'div' );
		this.modal.className = 'masonry-modal';
		this.modal.setAttribute( 'role', 'dialog' );
		this.modal.setAttribute( 'aria-modal', 'true' );
		this.modal.setAttribute( 'aria-label', i18n.close || 'Close' );
		this.modal.innerHTML = `
			<div class="masonry-modal__backdrop"></div>
			<div class="masonry-modal__container">
				<div class="masonry-modal__media">
					<img class="masonry-modal__image" src="" alt="" />
					<video class="masonry-modal__video" controls loop playsinline style="display:none"></video>
					<span class="masonry-modal__caption"></span>
					<span class="masonry-modal__meta"></span>
					<span class="masonry-modal__counter"></span>
				</div>
				<div class="masonry-modal__sidebar">
					<div class="masonry-modal__sidebar-inner">
						<h2 class="masonry-modal__title"></h2>
						<p class="masonry-modal__excerpt"></p>
						<div class="masonry-modal__tags portfolio-meta__tags"></div>
					</div>
					<a class="masonry-modal__read-more" href="#">
						${ i18n.readMore || 'Read more' }
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<line x1="5" y1="12" x2="19" y2="12"></line>
							<polyline points="12 5 19 12 12 19"></polyline>
						</svg>
					</a>
				</div>
				<button class="masonry-modal__nav masonry-modal__nav--prev" aria-label="${
					i18n.prev || 'Previous'
				}">
					<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<polyline points="15 18 9 12 15 6"></polyline>
					</svg>
				</button>
				<button class="masonry-modal__nav masonry-modal__nav--next" aria-label="${
					i18n.next || 'Next'
				}">
					<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<polyline points="9 18 15 12 9 6"></polyline>
					</svg>
				</button>
				<button class="masonry-modal__close" aria-label="${ i18n.close || 'Close' }">
					<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<line x1="18" y1="6" x2="6" y2="18"></line>
						<line x1="6" y1="6" x2="18" y2="18"></line>
					</svg>
				</button>
			</div>
		`;

		document.body.appendChild( this.modal );

		this.modal
			.querySelector( '.masonry-modal__backdrop' )
			.addEventListener( 'click', () => this.closeModal() );
		this.modal
			.querySelector( '.masonry-modal__close' )
			.addEventListener( 'click', () => this.closeModal() );
		this.modal
			.querySelector( '.masonry-modal__nav--prev' )
			.addEventListener( 'click', () => this.navigatePrev() );
		this.modal
			.querySelector( '.masonry-modal__nav--next' )
			.addEventListener( 'click', () => this.navigateNext() );

		// Focus trap inside modal.
		this.modal.addEventListener( 'keydown', ( e ) => {
			if (
				e.key !== 'Tab' ||
				! this.modal.classList.contains( 'is-open' )
			) {
				return;
			}
			const focusable = this.modal.querySelectorAll(
				'button, a[href], [tabindex]:not([tabindex="-1"])'
			);
			const first = focusable[ 0 ];
			const last = focusable[ focusable.length - 1 ];

			if ( e.shiftKey && document.activeElement === first ) {
				e.preventDefault();
				last.focus();
			} else if ( ! e.shiftKey && document.activeElement === last ) {
				e.preventDefault();
				first.focus();
			}
		} );

		// Touch/swipe navigation.
		this.setupModalSwipe();
	}

	setupModalSwipe() {
		const container = this.modal.querySelector(
			'.masonry-modal__container'
		);
		let touchStartX = 0;
		let touchStartY = 0;

		container.addEventListener(
			'touchstart',
			( e ) => {
				touchStartX = e.touches[ 0 ].clientX;
				touchStartY = e.touches[ 0 ].clientY;
			},
			{ passive: true }
		);

		container.addEventListener(
			'touchend',
			( e ) => {
				const dx = e.changedTouches[ 0 ].clientX - touchStartX;
				const dy = e.changedTouches[ 0 ].clientY - touchStartY;

				// Only count horizontal swipes (ignore vertical scrolling).
				if ( Math.abs( dx ) < 50 || Math.abs( dx ) < Math.abs( dy ) ) {
					return;
				}

				if ( dx > 0 ) {
					this.navigatePrev();
				} else {
					this.navigateNext();
				}
			},
			{ passive: true }
		);
	}

	getProjectGallery( item ) {
		try {
			const raw = item.dataset.gallery;
			return raw ? JSON.parse( raw ) : [];
		} catch {
			return [];
		}
	}

	openModal( index ) {
		const visibleIndices = this.visibleItems.map( ( item ) =>
			this.items.indexOf( item )
		);
		this.currentProjectIndex = visibleIndices.indexOf( index );
		if ( this.currentProjectIndex === -1 ) {
			this.currentProjectIndex = 0;
		}

		const item = this.visibleItems[ this.currentProjectIndex ];
		this.currentGallery = this.getProjectGallery( item );
		this.currentSlideIndex = 0;
		this.originalUrl = window.location.pathname + window.location.search;
		this.lastFocusedElement = document.activeElement;

		this.updateModalContent( true );
		this.modal.classList.add( 'is-open' );
		document.body.style.overflow = 'hidden';

		gsap.fromTo(
			this.modal.querySelector( '.masonry-modal__container' ),
			{ opacity: 0, scale: 0.95 },
			{
				opacity: 1,
				scale: 1,
				duration: 0.3,
				ease: 'power2.out',
				onComplete: () => {
					this.modal
						.querySelector( '.masonry-modal__close' )
						?.focus();
				},
			}
		);
	}

	closeModal() {
		if ( ! this.modal || ! this.modal.classList.contains( 'is-open' ) ) {
			return;
		}
		history.back();
	}

	closeModalDirect() {
		if ( ! this.modal || ! this.modal.classList.contains( 'is-open' ) ) {
			return;
		}
		// Stop any playing video
		const video = this.modal.querySelector( '.masonry-modal__video' );
		if ( video ) {
			video.pause();
		}

		gsap.to( this.modal.querySelector( '.masonry-modal__container' ), {
			opacity: 0,
			scale: 0.95,
			duration: 0.2,
			ease: 'power2.in',
			onComplete: () => {
				this.modal.classList.remove( 'is-open' );
				document.body.style.overflow = '';
				this.lastFocusedElement?.focus();
			},
		} );
	}

	navigateNext() {
		if (
			this.currentGallery.length > 1 &&
			this.currentSlideIndex < this.currentGallery.length - 1
		) {
			// Next slide within same project
			this.currentSlideIndex++;
		} else {
			// Next project
			this.currentProjectIndex =
				( this.currentProjectIndex + 1 ) % this.visibleItems.length;
			const item = this.visibleItems[ this.currentProjectIndex ];
			this.currentGallery = this.getProjectGallery( item );
			this.currentSlideIndex = 0;
		}
		this.animateSlide( 1 );
	}

	navigatePrev() {
		if ( this.currentGallery.length > 1 && this.currentSlideIndex > 0 ) {
			// Previous slide within same project
			this.currentSlideIndex--;
		} else {
			// Previous project (go to its last slide)
			this.currentProjectIndex =
				( this.currentProjectIndex - 1 + this.visibleItems.length ) %
				this.visibleItems.length;
			const item = this.visibleItems[ this.currentProjectIndex ];
			this.currentGallery = this.getProjectGallery( item );
			this.currentSlideIndex = Math.max(
				0,
				this.currentGallery.length - 1
			);
		}
		this.animateSlide( -1 );
	}

	animateSlide( direction ) {
		const media = this.modal.querySelector( '.masonry-modal__media' );
		gsap.to( media, {
			opacity: 0,
			x: direction * -30,
			duration: 0.15,
			ease: 'power2.in',
			onComplete: () => {
				this.updateModalContent();
				gsap.fromTo(
					media,
					{ opacity: 0, x: direction * 30 },
					{ opacity: 1, x: 0, duration: 0.2, ease: 'power2.out' }
				);
			},
		} );
	}

	updateModalContent( isFirstOpen = false ) {
		const item = this.visibleItems[ this.currentProjectIndex ];
		if ( ! item ) {
			return;
		}

		const slide = this.currentGallery[ this.currentSlideIndex ];
		const image = this.modal.querySelector( '.masonry-modal__image' );
		const video = this.modal.querySelector( '.masonry-modal__video' );
		const caption = this.modal.querySelector( '.masonry-modal__caption' );
		const counter = this.modal.querySelector( '.masonry-modal__counter' );

		const meta = this.modal.querySelector( '.masonry-modal__meta' );

		// Show image or video
		if ( slide && slide.type === 'video' ) {
			image.style.display = 'none';
			video.style.display = '';
			video.src = slide.src;
			video.play();
			caption.textContent = '';
			meta.textContent = '';
		} else {
			video.style.display = 'none';
			video.pause();
			image.style.display = '';
			image.src = slide
				? slide.src
				: item.dataset.pswpSrc ||
				  item.querySelector( 'img' )?.src ||
				  '';
			image.alt =
				item.querySelector( '.masonry-query__title' )?.textContent ||
				'';
			caption.textContent = slide?.cap || '';

			// EXIF metadata
			if ( slide?.meta ) {
				const parts = [];
				if ( slide.meta.camera ) {
					parts.push( slide.meta.camera );
				}
				if ( slide.meta.focal ) {
					parts.push( slide.meta.focal );
				}
				if ( slide.meta.aperture ) {
					parts.push( slide.meta.aperture );
				}
				if ( slide.meta.shutter ) {
					parts.push( slide.meta.shutter );
				}
				if ( slide.meta.iso ) {
					parts.push( slide.meta.iso );
				}
				if ( slide.meta.dimensions ) {
					parts.push( slide.meta.dimensions );
				}
				if ( slide.meta.date ) {
					parts.push( slide.meta.date );
				}
				meta.textContent = parts.join( ' · ' );
			} else {
				meta.textContent = '';
			}
		}

		// Counter (e.g. "2 / 8")
		if ( this.currentGallery.length > 1 ) {
			counter.textContent = `${ this.currentSlideIndex + 1 } / ${
				this.currentGallery.length
			}`;
			counter.style.display = '';
		} else {
			counter.style.display = 'none';
		}

		// Sidebar content — from data attributes + hidden lightbox data
		const lightboxData = item.querySelector(
			'.masonry-query__lightbox-data'
		);
		const title =
			item.querySelector(
				'.masonry-query__overlay .masonry-query__title'
			)?.textContent ||
			lightboxData?.querySelector( '.masonry-query__title' )
				?.textContent ||
			'';
		const excerpt = item.dataset.excerpt || '';
		const permalink = item.dataset.permalink || '#';

		this.modal.querySelector( '.masonry-modal__title' ).textContent = title;
		this.modal.querySelector( '.masonry-modal__excerpt' ).textContent =
			excerpt;

		// Tags
		const tagsContainer = this.modal.querySelector(
			'.masonry-modal__tags'
		);
		tagsContainer.innerHTML = '';
		try {
			const tagLinks = item.dataset.tagLinks
				? JSON.parse( item.dataset.tagLinks )
				: {};
			Object.entries( tagLinks ).forEach( ( [ name, url ] ) => {
				const pill = document.createElement( 'a' );
				pill.className = 'portfolio-meta__pill';
				pill.href = url;
				pill.textContent = name;
				tagsContainer.appendChild( pill );
			} );
		} catch {
			/* ignore */
		}

		// Read more link
		const fullUrl = new URL( permalink, window.location.origin );
		this.modal.querySelector( '.masonry-modal__read-more' ).href =
			fullUrl.toString();

		// Browser URL
		if ( isFirstOpen ) {
			history.pushState(
				{ masonryLightbox: true },
				'',
				fullUrl.pathname
			);
		} else {
			history.replaceState(
				{ masonryLightbox: true },
				'',
				fullUrl.pathname
			);
		}

		// Nav visibility
		const showNav =
			this.visibleItems.length > 1 || this.currentGallery.length > 1;
		this.modal.querySelector( '.masonry-modal__nav--prev' ).style.display =
			showNav ? '' : 'none';
		this.modal.querySelector( '.masonry-modal__nav--next' ).style.display =
			showNav ? '' : 'none';
	}

	// ========================================
	// FILTERING
	// ========================================

	setupFilters() {
		if ( this.filters.length === 0 ) {
			return;
		}

		this.filters.forEach( ( btn ) => {
			btn.addEventListener( 'click', () => {
				const filter = btn.dataset.filter;
				this.filters.forEach( ( f ) => {
					f.classList.remove( 'is-active' );
					f.setAttribute( 'aria-pressed', 'false' );
				} );
				btn.classList.add( 'is-active' );
				btn.setAttribute( 'aria-pressed', 'true' );
				this.filterItems( filter );
			} );
		} );
	}

	filterItems( filter ) {
		this.items.forEach( ( item ) => {
			gsap.killTweensOf( item );

			const categories = ( item.dataset.category || '' ).split( ',' );
			const shouldShow = filter === '*' || categories.includes( filter );

			if ( shouldShow ) {
				item.style.display = '';
				gsap.to( item, {
					opacity: 1,
					scale: 1,
					duration: 0.3,
					ease: 'power2.out',
				} );
			} else {
				gsap.to( item, {
					opacity: 0,
					scale: 0.9,
					duration: 0.3,
					ease: 'power2.out',
					onComplete: () => {
						item.style.display = 'none';
					},
				} );
			}
		} );

		setTimeout( () => {
			this.visibleItems = this.items.filter(
				( item ) => item.style.display !== 'none'
			);
		}, 350 );
	}

	// ========================================
	// PAGINATION
	// ========================================

	setupPagination() {
		if ( ! this.loadMoreBtn || ! this.pagination ) {
			return;
		}

		const paginationType = this.pagination.dataset.type;
		if ( paginationType === 'load-more' ) {
			this.loadMoreBtn.addEventListener( 'click', () => this.loadMore() );
		} else if ( paginationType === 'infinite' ) {
			this.setupInfiniteScroll();
		}
	}

	async loadMore() {
		if ( this.isLoading || this.currentPage >= this.maxPages ) {
			return;
		}

		this.isLoading = true;
		this.loadMoreBtn.classList.add( 'is-loading' );
		this.loadMoreBtn.disabled = true;

		try {
			const response = await fetch(
				'/wp-json/goodblocks/v1/masonry-query',
				{
					method: 'POST',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify( {
						page: this.currentPage + 1,
						blockId: this.settings.blockId,
						attributes: this.getBlockAttributes(),
					} ),
				}
			);

			if ( ! response.ok ) {
				throw new Error( 'Network error' );
			}

			const data = await response.json();

			if ( data.items && data.items.length > 0 ) {
				const tempDiv = document.createElement( 'div' );
				tempDiv.innerHTML = data.items.join( '' );
				const newItems = Array.from(
					tempDiv.querySelectorAll( '.masonry-query__item' )
				);

				newItems.forEach( ( item ) => {
					item.style.opacity = '0';
					this.grid.appendChild( item );
					this.items.push( item );
					this.visibleItems.push( item );

					item.addEventListener( 'click', ( e ) => {
						e.preventDefault();
						this.openModal( this.items.indexOf( item ) );
					} );
				} );

				gsap.to( newItems, {
					opacity: 1,
					y: 0,
					duration: 0.5,
					stagger: this.settings.animationStagger / 1000,
					ease: 'power2.out',
				} );

				this.currentPage++;
			}

			if ( ! data.hasMore || this.currentPage >= this.maxPages ) {
				this.loadMoreBtn.style.display = 'none';
			}
		} catch ( error ) {
			console.error( 'Error loading more items:', error );
			this.showLoadError();
		} finally {
			this.isLoading = false;
			this.loadMoreBtn.classList.remove( 'is-loading' );
			this.loadMoreBtn.disabled = false;
		}
	}

	showLoadError() {
		// Remove any existing error message.
		this.container.querySelector( '.masonry-query__error' )?.remove();

		const errorEl = document.createElement( 'div' );
		errorEl.className = 'masonry-query__error';
		errorEl.innerHTML = `
			<p>${ i18n.loadError || 'Could not load more items. Try again.' }</p>
			<button class="masonry-query__retry" type="button">${
				i18n.retry || 'Try again'
			}</button>
		`;
		errorEl
			.querySelector( '.masonry-query__retry' )
			.addEventListener( 'click', () => {
				errorEl.remove();
				this.loadMore();
			} );
		this.pagination.parentNode.insertBefore( errorEl, this.pagination );
	}

	setupInfiniteScroll() {
		const observer = new IntersectionObserver(
			( entries ) => {
				if ( entries[ 0 ].isIntersecting && ! this.isLoading ) {
					this.loadMore();
				}
			},
			{ rootMargin: '200px' }
		);
		observer.observe( this.pagination );
	}

	getBlockAttributes() {
		try {
			return JSON.parse( this.container.dataset.queryAttrs || '{}' );
		} catch {
			return {};
		}
	}

	// ========================================
	// DEEP LINKING
	// ========================================

	setupDeepLinks() {
		const hash = window.location.hash;
		if ( hash.startsWith( '#item-' ) ) {
			const postId = hash.replace( '#item-', '' );
			const itemIndex = this.items.findIndex(
				( item ) => item.dataset.postId === postId
			);
			if ( itemIndex !== -1 ) {
				setTimeout( () => this.openModal( itemIndex ), 500 );
			}
		}
	}

	// ========================================
	// ENTRANCE ANIMATIONS
	// ========================================

	animateEntrance() {
		if ( ! this.settings.enableAnimation ) {
			this.container.classList.add( 'masonry-query--loaded' );
			return;
		}

		const items = this.container.querySelectorAll( '.masonry-query__item' );

		// Safety: if gsap failed to load, show items immediately.
		if ( typeof gsap === 'undefined' || typeof gsap.set !== 'function' ) {
			this.container.classList.add( 'masonry-query--loaded' );
			items.forEach( ( item ) => {
				item.style.opacity = '1';
			} );
			return;
		}

		const initialState = { opacity: 0 };

		switch ( this.settings.animationType ) {
			case 'fade-up':
				initialState.y = 30;
				break;
			case 'slide-up':
				initialState.y = 50;
				break;
			case 'scale':
				initialState.scale = 0.9;
				break;
		}

		gsap.set( items, initialState );

		const observer = new IntersectionObserver(
			( entries ) => {
				entries.forEach( ( entry ) => {
					if ( entry.isIntersecting ) {
						const item = entry.target;
						const idx = Array.from( items ).indexOf( item );
						gsap.to( item, {
							opacity: 1,
							y: 0,
							scale: 1,
							duration: 0.6,
							delay:
								( idx % 6 ) *
								( this.settings.animationStagger / 1000 ),
							ease: 'power2.out',
						} );
						observer.unobserve( item );
					}
				} );
			},
			{ threshold: 0.1, rootMargin: '50px' }
		);

		items.forEach( ( item ) => observer.observe( item ) );

		// Failsafe: if items haven't animated in after 5s, force them visible.
		// Handles edge cases where IntersectionObserver doesn't fire (iOS bugs).
		setTimeout( () => {
			items.forEach( ( item ) => {
				if ( getComputedStyle( item ).opacity === '0' ) {
					item.style.opacity = '1';
					item.style.transform = 'none';
				}
			} );
		}, 5000 );

		setTimeout(
			() => this.container.classList.add( 'masonry-query--loaded' ),
			100
		);
	}

	destroy() {
		if ( this.modal ) {
			this.modal.remove();
		}
	}
}

// Initialize
document.addEventListener( 'DOMContentLoaded', () => {
	document
		.querySelectorAll( '.wp-block-goodblocks-masonry-query' )
		.forEach( ( block ) => {
			new MasonryQueryBlock( block );
		} );
} );

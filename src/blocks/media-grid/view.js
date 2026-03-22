/**
 * Frontend script for Media Grid block
 */

document.addEventListener( 'DOMContentLoaded', function () {
	const mediaGrids = document.querySelectorAll(
		'.wp-block-goodblocks-media-grid'
	);

	mediaGrids.forEach( ( grid ) => {
		initializeMediaGrid( grid );
	} );
} );

function initializeMediaGrid( gridElement ) {
	const gridItems = gridElement.querySelectorAll(
		'.wp-block-goodblocks-media-grid-item'
	);

	gridItems.forEach( ( item ) => {
		initializeGridItem( item );
	} );
}

function initializeGridItem( item ) {
	const video = item.querySelector( 'video' );

	if ( video ) {
		setupVideoInteraction( item, video );
	}

	setupClickInteraction( item );
	setupIntersectionObserver( item );
}

function setupVideoInteraction( item, video ) {
	video.addEventListener( 'loadeddata', function () {
		item.addEventListener( 'mouseenter', function () {
			if ( window.innerWidth > 768 ) {
				video.play().catch( () => {} );
			}
		} );

		item.addEventListener( 'mouseleave', function () {
			if ( window.innerWidth > 768 ) {
				video.pause();
				video.currentTime = 0;
			}
		} );
	} );

	item.addEventListener( 'click', function ( e ) {
		if ( window.innerWidth <= 768 ) {
			e.preventDefault();

			if ( video.paused ) {
				pauseAllVideos(
					item.closest( '.wp-block-goodblocks-media-grid' )
				);
				video.play().catch( () => {} );
			} else {
				video.pause();
			}
		}
	} );
}

function setupClickInteraction( item ) {
	const title = item.querySelector( '.item-title' );

	item.setAttribute( 'tabindex', '0' );
	item.setAttribute( 'role', 'button' );

	if ( title ) {
		item.setAttribute( 'aria-label', title.textContent );
	}

	item.addEventListener( 'keydown', function ( e ) {
		if ( e.key === 'Enter' || e.key === ' ' ) {
			e.preventDefault();
			item.click();
		}
	} );
}

function setupIntersectionObserver( item ) {
	const observer = new IntersectionObserver(
		( entries ) => {
			entries.forEach( ( entry ) => {
				const video = entry.target.querySelector( 'video' );

				if ( video ) {
					if ( entry.isIntersecting ) {
						video.load();
					} else {
						video.pause();
						video.currentTime = 0;
					}
				}
			} );
		},
		{
			threshold: 0.1,
			rootMargin: '50px',
		}
	);

	observer.observe( item );
}

function pauseAllVideos( gridContainer ) {
	if ( ! gridContainer ) {
		return;
	}
	const videos = gridContainer.querySelectorAll( 'video' );
	videos.forEach( ( video ) => {
		video.pause();
		video.currentTime = 0;
	} );
}

function handleResponsiveLayout() {
	const mediaGrids = document.querySelectorAll(
		'.wp-block-goodblocks-media-grid'
	);

	mediaGrids.forEach( ( grid ) => {
		const items = grid.querySelectorAll(
			'.wp-block-goodblocks-media-grid-item'
		);

		items.forEach( ( item ) => {
			const video = item.querySelector( 'video' );
			if ( video ) {
				if ( window.innerWidth <= 768 ) {
					video.removeAttribute( 'autoplay' );
				}
			}
		} );
	} );
}

window.addEventListener( 'resize', debounce( handleResponsiveLayout, 250 ) );

function debounce( func, wait ) {
	let timeout;
	return function executedFunction( ...args ) {
		const later = () => {
			clearTimeout( timeout );
			func( ...args );
		};
		clearTimeout( timeout );
		timeout = setTimeout( later, wait );
	};
}

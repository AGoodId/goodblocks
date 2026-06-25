const usedIds = new Set();

const slugify = ( text ) =>
	text
		.toString()
		.trim()
		.toLowerCase()
		.normalize( 'NFD' )
		.replace( /[\u0300-\u036f]/g, '' )
		.replace( /å/g, 'a' )
		.replace( /ä/g, 'a' )
		.replace( /ö/g, 'o' )
		.replace( /[^a-z0-9\s-]/g, '' )
		.replace( /\s+/g, '-' )
		.replace( /-+/g, '-' )
		.replace( /^-|-$/g, '' );

const uniqueId = ( base ) => {
	let candidate = base || 'section';
	let index = 2;

	while ( usedIds.has( candidate ) || document.getElementById( candidate ) ) {
		candidate = `${ base || 'section' }-${ index }`;
		index += 1;
	}

	usedIds.add( candidate );
	return candidate;
};

const getContentRoots = ( toc ) => {
	const scopeSelector = toc.dataset.scopeSelector || '';

	if ( scopeSelector ) {
		try {
			const scopedRoots = Array.from(
				document.querySelectorAll( scopeSelector )
			);

			if ( scopedRoots.length ) {
				return scopedRoots;
			}
		} catch ( error ) {}
	}

	return [
		toc.closest( 'main, article, .entry-content, .wp-site-blocks' ) ||
			document.querySelector(
				'main, article, .entry-content, .wp-site-blocks'
			) ||
			document.body,
	];
};

const isExcluded = ( heading, toc, excludeSelector ) => {
	if (
		heading.closest(
			'nav, header, footer, aside, [data-goodblocks-toc], [data-goodblocks-toc-ignore], .goodblocks-toc-ignore'
		)
	) {
		return true;
	}

	if ( excludeSelector ) {
		try {
			return !! heading.closest( excludeSelector );
		} catch ( error ) {
			return false;
		}
	}

	return heading === toc || toc.contains( heading );
};

const collectHeadings = ( toc ) => {
	const levels = ( toc.dataset.levels || '2,3' )
		.split( ',' )
		.map( ( level ) => parseInt( level, 10 ) )
		.filter( Boolean );
	const selector =
		toc.dataset.headingSelector ||
		levels.map( ( level ) => `h${ level }` ).join( ',' );
	const excludeSelector = toc.dataset.excludeSelector || '';

	if ( ! selector ) {
		return [];
	}

	const roots = getContentRoots( toc );
	let headings = [];

	try {
		headings = roots.flatMap( ( root ) =>
			Array.from( root.querySelectorAll( selector ) )
		);
	} catch ( error ) {
		return [];
	}

	return headings
		.filter( ( heading ) => ! isExcluded( heading, toc, excludeSelector ) )
		.map( ( heading ) => ( {
			heading,
			level: parseInt( heading.tagName.slice( 1 ), 10 ),
			text: heading.textContent.trim(),
		} ) )
		.filter( ( item ) => item.text );
};

const buildList = ( toc, items ) => {
	const list = toc.querySelector( '[data-goodblocks-toc-list]' );
	const showNumbers = toc.dataset.showNumbers === 'true';
	const baseLevel = Math.min( ...items.map( ( item ) => item.level ) );

	list.innerHTML = '';

	const counters = [];

	items.forEach( ( item ) => {
		if ( ! item.heading.id ) {
			item.heading.id = uniqueId( slugify( item.text ) );
		} else {
			usedIds.add( item.heading.id );
		}

		item.heading.style.scrollMarginTop = `${
			toc.dataset.scrollOffset || 96
		}px`;

		const depth = Math.max( 0, item.level - baseLevel );
		counters[ depth ] = ( counters[ depth ] || 0 ) + 1;
		counters.length = depth + 1;

		const li = document.createElement( 'li' );
		li.className = `goodblocks-toc__item is-level-${ item.level }`;
		li.style.setProperty( '--toc-depth', depth );

		const link = document.createElement( 'a' );
		link.className = 'goodblocks-toc__link';
		link.href = `#${ item.heading.id }`;
		link.dataset.target = item.heading.id;

		if ( showNumbers ) {
			const number = document.createElement( 'span' );
			number.className = 'goodblocks-toc__number';
			number.textContent = counters.join( '.' );
			link.appendChild( number );
		}

		const label = document.createElement( 'span' );
		label.className = 'goodblocks-toc__label';
		label.textContent = item.text;
		link.appendChild( label );

		li.appendChild( link );
		list.appendChild( li );
	} );
};

const bindToggle = ( toc ) => {
	const button = toc.querySelector( '.goodblocks-toc__toggle' );

	if ( ! button ) {
		return;
	}

	button.addEventListener( 'click', () => {
		const collapsed = toc.classList.toggle( 'is-collapsed' );
		button.setAttribute( 'aria-expanded', collapsed ? 'false' : 'true' );
	} );
};

const bindSmoothScroll = ( toc ) => {
	if ( toc.dataset.smoothScroll !== 'true' ) {
		return;
	}

	toc.addEventListener( 'click', ( event ) => {
		const link = event.target.closest( 'a[href^="#"]' );

		if ( ! link ) {
			return;
		}

		const target = document.getElementById( link.dataset.target );

		if ( ! target ) {
			return;
		}

		event.preventDefault();
		target.scrollIntoView( {
			behavior: 'smooth',
			block: 'start',
		} );
		window.history.pushState( null, '', link.hash );
	} );
};

const observeActiveHeading = ( toc, items ) => {
	if ( toc.dataset.showActive !== 'true' || ! items.length ) {
		return;
	}

	const links = new Map(
		Array.from( toc.querySelectorAll( '.goodblocks-toc__link' ) ).map(
			( link ) => [ link.dataset.target, link ]
		)
	);

	const setActive = ( id ) => {
		links.forEach( ( link, linkId ) => {
			const active = linkId === id;
			link.classList.toggle( 'is-active', active );
			link.parentElement?.classList.toggle( 'is-active', active );

			if ( active ) {
				link.setAttribute( 'aria-current', 'true' );
			} else {
				link.removeAttribute( 'aria-current' );
			}
		} );
	};

	const observer = new IntersectionObserver(
		( entries ) => {
			const visible = entries
				.filter( ( entry ) => entry.isIntersecting )
				.sort(
					( a, b ) =>
						a.boundingClientRect.top - b.boundingClientRect.top
				);

			if ( visible[ 0 ]?.target?.id ) {
				setActive( visible[ 0 ].target.id );
			}
		},
		{
			rootMargin: `-${ toc.dataset.scrollOffset || 96 }px 0px -65% 0px`,
			threshold: [ 0, 1 ],
		}
	);

	items.forEach( ( item ) => observer.observe( item.heading ) );
};

const initToc = ( toc ) => {
	const items = collectHeadings( toc );
	const minHeadings = parseInt( toc.dataset.minHeadings || '2', 10 );

	if ( items.length < minHeadings ) {
		toc.hidden = true;
		return;
	}

	buildList( toc, items );
	bindToggle( toc );
	bindSmoothScroll( toc );
	observeActiveHeading( toc, items );
	toc.classList.add( 'is-ready' );
};

document.addEventListener( 'DOMContentLoaded', () => {
	document.querySelectorAll( '[data-goodblocks-toc]' ).forEach( initToc );
} );

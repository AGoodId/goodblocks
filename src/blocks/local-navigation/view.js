function setItemOpen( item, open ) {
	item.classList.toggle( 'goodblocks-local-navigation__item--open', open );
	item.classList.toggle( 'bellows-active', open );

	const toggle = item.querySelector(
		':scope > .goodblocks-local-navigation__item-row > .goodblocks-local-navigation__toggle, :scope > .goodblocks-local-navigation__toggle, :scope > .bellows-subtoggle'
	);
	const submenu = item.querySelector(
		':scope > .children, :scope > .bellows-submenu'
	);

	if ( toggle ) {
		toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
	}

	if ( submenu ) {
		submenu.hidden = ! open;
	}
}

function handleToggleClick( event ) {
	const toggle = event.target.closest(
		'.goodblocks-local-navigation__toggle, .goodblocks-local-navigation.bellows .bellows-subtoggle'
	);

	if ( ! toggle ) {
		return;
	}

	const nav = toggle.closest( '.goodblocks-local-navigation' );

	if ( ! nav ) {
		return;
	}

	const item = toggle.closest(
		'.page_item_has_children, .bellows-menu-item-has-children'
	);

	if ( ! item ) {
		return;
	}

	event.preventDefault();
	event.stopPropagation();
	event.stopImmediatePropagation();
	setItemOpen( item, toggle.getAttribute( 'aria-expanded' ) !== 'true' );
}

document.addEventListener( 'click', handleToggleClick, true );

function initializeLocalNavigation() {
	document
		.querySelectorAll( '.goodblocks-local-navigation' )
		.forEach( ( nav ) => {
			nav.querySelectorAll(
				'.page_item_has_children, .bellows-menu-item-has-children'
			).forEach( ( item ) => {
				const shouldOpen =
					item.classList.contains(
						'goodblocks-local-navigation__item--open'
					) || item.classList.contains( 'bellows-active' );

				setItemOpen( item, shouldOpen );
			} );
		} );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', initializeLocalNavigation );
} else {
	initializeLocalNavigation();
}

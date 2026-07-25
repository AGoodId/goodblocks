document.addEventListener( 'DOMContentLoaded', () => {
	document
		.querySelectorAll( '[data-goodblocks-event-calendar]' )
		.forEach( ( root ) => {
			const panels = root.querySelectorAll( '[data-calendar-panel]' );
			const viewButtons = root.querySelectorAll( '[data-calendar-view]' );
			const search = root.querySelector( '[data-calendar-search]' );
			const type = root.querySelector( '[data-calendar-type]' );
			const items = root.querySelectorAll( '[data-calendar-item]' );
			const groups = root.querySelectorAll( '[data-calendar-group]' );
			const empty = root.querySelector( '[data-calendar-empty]' );
			let activeView = root.dataset.defaultView || 'month';

			const setView = ( view ) => {
				activeView = view;
				panels.forEach( ( panel ) => {
					panel.hidden = panel.dataset.calendarPanel !== activeView;
				} );
				viewButtons.forEach( ( button ) => {
					button.classList.toggle(
						'is-active',
						button.dataset.calendarView === activeView
					);
				} );
			};

			const applyFilters = () => {
				const query = ( search?.value || '' ).trim().toLowerCase();
				const activeType = type?.value || '';
				let visible = 0;

				items.forEach( ( item ) => {
					const haystack = `${ item.dataset.title || '' } ${
						item.dataset.meta || ''
					}`.toLowerCase();
					const matchesSearch = ! query || haystack.includes( query );
					const matchesType =
						! activeType || item.dataset.type === activeType;
					const shouldShow = matchesSearch && matchesType;

					item.hidden = ! shouldShow;
					if ( shouldShow ) {
						visible += 1;
					}
				} );

				if ( empty ) {
					empty.hidden = visible !== 0;
				}

				groups.forEach( ( group ) => {
					const groupItems = group.querySelectorAll(
						'[data-calendar-item]'
					);
					const hasVisibleItem = Array.from( groupItems ).some(
						( item ) => ! item.hidden
					);

					group.hidden = ! hasVisibleItem;
				} );
			};

			viewButtons.forEach( ( button ) => {
				button.addEventListener( 'click', () => {
					setView( button.dataset.calendarView || 'month' );
				} );
			} );

			search?.addEventListener( 'input', applyFilters );
			type?.addEventListener( 'change', applyFilters );

			setView( activeView );
			applyFilters();
		} );
} );

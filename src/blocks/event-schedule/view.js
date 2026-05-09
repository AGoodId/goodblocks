document.addEventListener( 'DOMContentLoaded', () => {
	document
		.querySelectorAll( '.goodblocks-event-schedule' )
		.forEach( ( root ) => {
			const buttons = root.querySelectorAll( '[data-day]' );
			const search = root.querySelector( '[data-schedule-search]' );
			const type = root.querySelector( '[data-schedule-type]' );
			const items = root.querySelectorAll( '[data-schedule-item]' );
			const list = root.querySelector(
				'.goodblocks-event-schedule__list'
			);
			const empty = root.querySelector( '[data-schedule-empty]' );
			let activeDay = '';

			const applyFilters = () => {
				const query = ( search?.value || '' ).trim().toLowerCase();
				const activeType = type?.value || '';
				let visible = 0;

				items.forEach( ( item ) => {
					const matchesDay =
						! activeDay || item.dataset.day === activeDay;
					const matchesType =
						! activeType || item.dataset.type === activeType;
					const haystack = `${ item.dataset.class || '' } ${
						item.dataset.title || ''
					}`.toLowerCase();
					const matchesSearch = ! query || haystack.includes( query );
					const shouldShow =
						matchesDay && matchesType && matchesSearch;

					item.hidden = ! shouldShow;
					if ( shouldShow ) {
						visible += 1;
					}
				} );

				if ( list ) {
					list.classList.toggle( 'is-empty', visible === 0 );
				}

				if ( empty ) {
					empty.hidden = visible !== 0;
				}
			};

			buttons.forEach( ( button ) => {
				button.addEventListener( 'click', () => {
					activeDay = button.dataset.day || '';
					buttons.forEach( ( current ) =>
						current.classList.toggle(
							'is-active',
							current === button
						)
					);
					applyFilters();
				} );
			} );

			search?.addEventListener( 'input', applyFilters );
			type?.addEventListener( 'change', applyFilters );
		} );
} );

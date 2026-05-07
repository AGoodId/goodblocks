document.addEventListener( 'DOMContentLoaded', () => {
	document
		.querySelectorAll( '.goodblocks-event-class-schedule' )
		.forEach( ( root ) => {
			const search = root.querySelector( '[data-class-schedule-search]' );
			const items = root.querySelectorAll( '[data-class-schedule-item]' );
			const results = root.querySelector(
				'.goodblocks-event-class-schedule__results'
			);
			const message = root.querySelector(
				'[data-class-schedule-message]'
			);

			const filter = () => {
				const query = ( search?.value || '' ).trim().toLowerCase();
				let visible = 0;

				items.forEach( ( item ) => {
					const haystack = `${ item.dataset.class || '' } ${
						item.dataset.title || ''
					}`.toLowerCase();
					const shouldShow =
						query.length > 1 && haystack.includes( query );

					item.hidden = ! shouldShow;
					if ( shouldShow ) {
						visible += 1;
					}
				} );

				if ( ! message || ! results ) {
					return;
				}

				if ( ! query ) {
					message.hidden = false;
					message.textContent = results.dataset.emptyText || '';
					return;
				}

				message.hidden = visible > 0;
				message.textContent = results.dataset.noResultsText || '';
			};

			search?.addEventListener( 'input', filter );
		} );
} );

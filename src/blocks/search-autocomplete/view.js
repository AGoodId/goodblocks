/**
 * Search Autocomplete Block - Frontend
 * Handles live search with REST API integration
 */

/* translatable strings passed via wp_localize_script */
const searchI18n = window.goodblocksSearch?.i18n || {};

class SearchAutocompleteBlock {
	constructor( container ) {
		this.container = container;
		this.settings = this.getSettings();
		this.cache = new Map();
		this.abortController = null;
		this.debounceTimer = null;
		this.selectedIndex = -1;

		this.init();
	}

	getSettings() {
		return {
			minChars: parseInt( this.container.dataset.minChars, 10 ) || 2,
			maxResults: parseInt( this.container.dataset.maxResults, 10 ) || 5,
			postTypes: this.container.dataset.postTypes || 'post,page',
			showThumbnail: this.container.dataset.showThumbnail === 'true',
			showExcerpt: this.container.dataset.showExcerpt === 'true',
			showType: this.container.dataset.showType === 'true',
			expandable: this.container.dataset.expandable === 'true',
			apiUrl: this.container.dataset.apiUrl,
			suggestionsUrl: this.container.dataset.suggestionsUrl,
			suggestionsType: 'popular',
			debounceMs: 300,
		};
	}

	init() {
		this.cacheElements();
		this.bindEvents();

		if ( this.settings.expandable ) {
			this.setupExpandable();
		}
	}

	cacheElements() {
		this.trigger = this.container.querySelector(
			'.search-autocomplete__trigger'
		);
		this.form = this.container.querySelector(
			'.search-autocomplete__form'
		);
		this.input = this.container.querySelector(
			'.search-autocomplete__input'
		);
		this.results = this.container.querySelector(
			'.search-autocomplete__results'
		);
		this.clearBtn = this.container.querySelector(
			'.search-autocomplete__clear'
		);
		this.closeBtn = this.container.querySelector(
			'.search-autocomplete__close'
		);
		this.backdrop = this.container.querySelector(
			'.search-autocomplete__backdrop'
		);
	}

	bindEvents() {
		this.input.addEventListener( 'input', this.handleInput.bind( this ) );
		this.input.addEventListener(
			'keydown',
			this.handleKeydown.bind( this )
		);
		this.input.addEventListener( 'focus', this.handleFocus.bind( this ) );

		if ( this.clearBtn ) {
			this.clearBtn.addEventListener(
				'click',
				this.handleClear.bind( this )
			);
		}

		this.form.addEventListener( 'submit', this.handleSubmit.bind( this ) );
		document.addEventListener(
			'click',
			this.handleClickOutside.bind( this )
		);

		document.addEventListener( 'keydown', ( event ) => {
			if ( event.key !== 'Escape' ) {
				return;
			}
			this.closeResults();
			if (
				this.settings.expandable &&
				this.container.classList.contains( 'is-expanded' )
			) {
				this.collapse();
			}
		} );
	}

	setupExpandable() {
		if ( this.trigger ) {
			this.trigger.addEventListener( 'click', this.expand.bind( this ) );
		}

		if ( this.closeBtn ) {
			this.closeBtn.addEventListener(
				'click',
				this.collapse.bind( this )
			);
		}

		if ( this.backdrop ) {
			this.backdrop.addEventListener(
				'click',
				this.collapse.bind( this )
			);
		}
	}

	expand() {
		if ( this.container.classList.contains( 'is-expanded' ) ) {
			return;
		}

		this.container.classList.add( 'is-expanded' );
		this.form.setAttribute( 'aria-hidden', 'false' );
		this.trigger?.setAttribute( 'aria-expanded', 'true' );
		document.body.style.overflow = 'hidden';

		requestAnimationFrame( () => {
			this.input.focus();
			if ( ! this.input.value.trim() ) {
				this.loadSuggestions();
			}
		} );
	}

	collapse() {
		this.container.classList.remove( 'is-expanded' );
		this.form.setAttribute( 'aria-hidden', 'true' );
		this.trigger?.setAttribute( 'aria-expanded', 'false' );

		if ( this.abortController ) {
			this.abortController.abort();
		}
		if ( this.debounceTimer ) {
			clearTimeout( this.debounceTimer );
		}

		this.closeResults();
		this.input.value = '';
		this.updateClearButton();
		document.body.style.overflow = '';
	}

	handleInput( event ) {
		const query = event.target.value.trim();
		this.updateClearButton();

		if ( this.debounceTimer ) {
			clearTimeout( this.debounceTimer );
		}
		if ( this.abortController ) {
			this.abortController.abort();
		}

		if ( query.length < this.settings.minChars ) {
			if ( query.length === 0 ) {
				this.loadSuggestions( '' );
			} else {
				this.loadSuggestions( query );
			}
			return;
		}

		this.debounceTimer = setTimeout( () => {
			this.search( query );
		}, this.settings.debounceMs );
	}

	handleKeydown( event ) {
		const items = this.results.querySelectorAll(
			'.search-autocomplete__result'
		);

		switch ( event.key ) {
			case 'ArrowDown':
				event.preventDefault();
				this.selectedIndex = Math.min(
					this.selectedIndex + 1,
					items.length - 1
				);
				this.updateSelection( items );
				break;
			case 'ArrowUp':
				event.preventDefault();
				this.selectedIndex = Math.max( this.selectedIndex - 1, -1 );
				this.updateSelection( items );
				break;
			case 'Enter':
				if ( this.selectedIndex >= 0 && items[ this.selectedIndex ] ) {
					event.preventDefault();
					const link = items[ this.selectedIndex ].querySelector(
						'.search-autocomplete__result-link'
					);
					if ( link ) {
						window.location.href = link.href;
					}
				}
				break;
			case 'Escape':
				this.closeResults();
				this.input.blur();
				break;
		}
	}

	handleFocus() {
		const query = this.input.value.trim();

		if ( ! query ) {
			this.loadSuggestions( '' );
			return;
		}

		if ( query.length < this.settings.minChars ) {
			this.loadSuggestions( query );
			return;
		}

		if ( this.results.children.length > 0 ) {
			this.openResults();
		} else {
			this.search( query );
		}
	}

	handleClear() {
		this.input.value = '';
		this.input.focus();
		this.updateClearButton();
		this.loadSuggestions( '' );
	}

	handleSubmit( event ) {
		const query = this.input.value.trim();
		if ( ! query ) {
			event.preventDefault();
		}
	}

	handleClickOutside( event ) {
		if ( this.container.contains( event.target ) ) {
			return;
		}

		if (
			this.settings.expandable &&
			this.container.classList.contains( 'is-expanded' )
		) {
			this.collapse();
			return;
		}

		this.closeResults();
	}

	updateClearButton() {
		if ( this.clearBtn ) {
			this.clearBtn.hidden = ! this.input.value;
		}
	}

	updateSelection( items ) {
		items.forEach( ( item, index ) => {
			if ( index === this.selectedIndex ) {
				item.classList.add( 'is-selected' );
				item.scrollIntoView( { block: 'nearest' } );
			} else {
				item.classList.remove( 'is-selected' );
			}
		} );
	}

	async loadSuggestions( query = '' ) {
		if ( ! this.settings.suggestionsUrl ) {
			this.closeResults();
			return;
		}

		const normalizedQuery = String( query || '' ).trim();
		const cacheKey = `__suggestions_${ this.settings.suggestionsType }_${
			this.settings.maxResults
		}_${ normalizedQuery.toLowerCase() }`;
		if ( this.cache.has( cacheKey ) ) {
			this.renderSuggestions(
				this.cache.get( cacheKey ),
				normalizedQuery
			);
			return;
		}

		this.container.classList.add( 'is-loading' );

		try {
			const params = new URLSearchParams( {
				type: this.settings.suggestionsType,
				count: this.settings.maxResults,
			} );
			if ( normalizedQuery ) {
				params.set( 's', normalizedQuery );
			}

			const response = await fetch(
				`${ this.settings.suggestionsUrl }?${ params }`
			);
			if ( ! response.ok ) {
				throw new Error( 'Suggestions failed' );
			}

			const data = await response.json();
			this.cache.set( cacheKey, data );
			this.renderSuggestions( data, normalizedQuery );
		} catch ( error ) {
			// eslint-disable-next-line no-console
			console.error( 'Suggestions error:', error );
			this.closeResults();
		} finally {
			this.container.classList.remove( 'is-loading' );
		}
	}

	async search( query ) {
		const cacheKey = `${ query }-${ this.settings.postTypes }`;
		if ( this.cache.has( cacheKey ) ) {
			this.renderResults( this.cache.get( cacheKey ), query );
			return;
		}

		this.container.classList.add( 'is-loading' );

		try {
			this.abortController = new AbortController();
			const params = new URLSearchParams( {
				s: query,
				post_types: this.settings.postTypes,
				per_page: this.settings.maxResults,
			} );

			const response = await fetch(
				`${ this.settings.apiUrl }?${ params }`,
				{
					signal: this.abortController.signal,
					headers: {
						'Content-Type': 'application/json',
					},
				}
			);

			if ( ! response.ok ) {
				throw new Error( 'Search failed' );
			}

			const data = await response.json();
			this.cache.set( cacheKey, data );
			if ( this.cache.size > 60 ) {
				const firstKey = this.cache.keys().next().value;
				this.cache.delete( firstKey );
			}

			this.renderResults( data, query );
		} catch ( error ) {
			if ( error.name !== 'AbortError' ) {
				// eslint-disable-next-line no-console
				console.error( 'Search error:', error );
				this.renderError();
			}
		} finally {
			this.container.classList.remove( 'is-loading' );
		}
	}

	renderSuggestions( results, query = '' ) {
		this.selectedIndex = -1;

		if ( ! Array.isArray( results ) || results.length === 0 ) {
			this.closeResults();
			return;
		}

		this.results.innerHTML = results
			.map( ( item, index ) =>
				this.renderResultItem( item, index, query )
			)
			.join( '' );

		if ( query ) {
			this.results.innerHTML += this.renderViewAllLink( query );
		}

		this.openResults();
	}

	renderResults( results, query ) {
		this.selectedIndex = -1;

		if ( ! Array.isArray( results ) || results.length === 0 ) {
			const noResultsText = (
				searchI18n.noResults || 'No results for "%s"'
			).replace( '%s', this.escapeHtml( query ) );
			this.results.innerHTML = `
				<div class="search-autocomplete__no-results">
					<p>${ noResultsText }</p>
				</div>
			`;
		} else {
			this.results.innerHTML = results
				.map( ( item, index ) =>
					this.renderResultItem( item, index, query )
				)
				.join( '' );
		}

		if ( Array.isArray( results ) && results.length > 0 ) {
			this.results.innerHTML += this.renderViewAllLink( query );
		}

		this.openResults();
	}

	renderResultItem( item, index, query = '' ) {
		const thumbnail =
			this.settings.showThumbnail && item.thumbnail
				? `<img src="${ this.escapeAttribute(
						item.thumbnail
				  ) }" alt="" class="search-autocomplete__thumb" loading="lazy" />`
				: '';

		const type =
			this.settings.showType && item.type
				? `<span class="search-autocomplete__type">${ this.escapeHtml(
						item.type
				  ) }</span>`
				: '';

		const title = this.highlightText( item.title || '', query );
		const excerpt =
			this.settings.showExcerpt && item.excerpt
				? `<p class="search-autocomplete__excerpt">${ this.highlightText(
						item.excerpt,
						query
				  ) }</p>`
				: '';

		const terms = this.renderTermPills( item.terms );

		return `
			<div class="search-autocomplete__result" role="option" data-index="${ index }">
				<a href="${ this.escapeAttribute(
					item.url || '#'
				) }" class="search-autocomplete__result-link">
					${ thumbnail }
					<div class="search-autocomplete__content">
						<div class="search-autocomplete__header">
							<span class="search-autocomplete__title">${ title }</span>
							${ type }
						</div>
						${ excerpt }
					</div>
				</a>
				${ terms }
			</div>
		`;
	}

	renderTermPills( terms ) {
		if ( ! Array.isArray( terms ) || terms.length === 0 ) {
			return '';
		}

		const items = terms
			.slice( 0, 4 )
			.map( ( term ) => {
				const termName = this.escapeHtml( term.name || '' );
				const termUrl = this.escapeAttribute( term.url || '#' );
				const taxonomyLabel = this.escapeAttribute(
					term.taxonomy_label || term.taxonomy || 'Kategori'
				);

				return `
					<a href="${ termUrl }" class="search-autocomplete__term" aria-label="${ taxonomyLabel }: ${ termName }">
						#${ termName }
					</a>
				`;
			} )
			.join( '' );

		return `<div class="search-autocomplete__terms">${ items }</div>`;
	}

	highlightText( text, query ) {
		const source = String( text || '' );
		const terms = this.getHighlightTerms( query );

		if ( ! terms.length ) {
			return this.escapeHtml( source );
		}

		const pattern = terms
			.map( ( term ) => this.escapeRegExp( term ) )
			.join( '|' );
		const regex = new RegExp( `(${ pattern })`, 'gi' );
		const parts = source.split( regex );

		return parts
			.map( ( part, index ) => {
				if ( index % 2 === 1 ) {
					return `<mark class="search-autocomplete__highlight">${ this.escapeHtml(
						part
					) }</mark>`;
				}

				return this.escapeHtml( part );
			} )
			.join( '' );
	}

	getHighlightTerms( query ) {
		return String( query || '' )
			.trim()
			.split( /\s+/ )
			.filter( ( term ) => term.length >= this.settings.minChars )
			.slice( 0, 6 );
	}

	renderViewAllLink( query ) {
		const viewAllUrl = `${
			window.location.origin
		}/?s=${ encodeURIComponent( query ) }`;
		const viewAllText = (
			searchI18n.viewAll || 'View all results for "%s"'
		).replace( '%s', this.escapeHtml( query ) );
		return `
			<a href="${ this.escapeAttribute(
				viewAllUrl
			) }" class="search-autocomplete__view-all">
				${ viewAllText }
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
					<path d="M5 12h14M12 5l7 7-7 7"/>
				</svg>
			</a>
		`;
	}

	renderError() {
		this.results.innerHTML = `
			<div class="search-autocomplete__error">
				<p>${ searchI18n.error || 'An error occurred. Please try again.' }</p>
			</div>
		`;
		this.openResults();
	}

	openResults() {
		this.results.hidden = false;
		this.input.setAttribute( 'aria-expanded', 'true' );
		this.container.classList.add( 'has-results' );
	}

	closeResults() {
		this.results.hidden = true;
		this.input.setAttribute( 'aria-expanded', 'false' );
		this.container.classList.remove( 'has-results' );
		this.selectedIndex = -1;
	}

	escapeHtml( text ) {
		const div = document.createElement( 'div' );
		div.textContent = text || '';
		return div.innerHTML;
	}

	escapeRegExp( text ) {
		return String( text || '' ).replace( /[.*+?^${}()|[\]\\]/g, '\\$&' );
	}

	escapeAttribute( text ) {
		return this.escapeHtml( String( text || '' ) );
	}
}

function initSearchAutocomplete() {
	document
		.querySelectorAll( '.search-autocomplete' )
		.forEach( ( container ) => {
			if ( ! container._searchInit ) {
				container._searchInit = true;
				new SearchAutocompleteBlock( container );
			}
		} );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', initSearchAutocomplete );
} else {
	initSearchAutocomplete();
}

window.SearchAutocompleteBlock = SearchAutocompleteBlock;

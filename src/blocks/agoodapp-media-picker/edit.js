import { __ } from '@wordpress/i18n';
import { useBlockProps, BlockControls } from '@wordpress/block-editor';
import {
	ToolbarButton,
	ToolbarGroup,
	Modal,
	Spinner,
	Notice,
	SearchControl,
} from '@wordpress/components';
import { useState, useEffect, useRef, useCallback } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import apiFetch from '@wordpress/api-fetch';
import { image as imageIcon } from '@wordpress/icons';
import './editor.scss';

const LIMIT = 24;

export default function Edit( { attributes, setAttributes } ) {
	const { attachmentId, mediaType, title: mediaTitle } = attributes;

	const [ isModalOpen, setIsModalOpen ]       = useState( false );
	const [ items, setItems ]                   = useState( [] );
	const [ page, setPage ]                     = useState( 1 );
	const [ hasMore, setHasMore ]               = useState( true );
	const [ isLoading, setIsLoading ]           = useState( false );
	const [ isSideloading, setIsSideloading ]   = useState( false );
	const [ error, setError ]                   = useState( null );
	const [ search, setSearch ]                 = useState( '' );

	const isLoadingRef = useRef( false );
	const sentinelRef  = useRef( null );
	const searchTimer  = useRef( null );
	const blockProps   = useBlockProps();

	const mediaItem = useSelect(
		( select ) => attachmentId ? select( coreStore ).getMedia( attachmentId ) : null,
		[ attachmentId ]
	);

	const previewUrl = mediaType === 'video'
		? mediaItem?.source_url
		: ( mediaItem?.media_details?.sizes?.medium?.source_url ?? mediaItem?.source_url );

	// Fetch one page whenever page, search, or modal visibility changes.
	useEffect( () => {
		if ( ! isModalOpen ) return;

		let cancelled = false;
		isLoadingRef.current = true;
		setIsLoading( true );
		setError( null );

		const searchParam = search ? `&search=${ encodeURIComponent( search ) }` : '';
		apiFetch( { path: `/goodblocks/v1/agoodapp/media?page=${ page }&limit=${ LIMIT }${ searchParam }` } )
			.then( ( data ) => {
				if ( cancelled ) return;
				setItems( ( prev ) => page === 1 ? data.items : [ ...prev, ...data.items ] );
				setHasMore( data.hasMore );
			} )
			.catch( ( err ) => {
				if ( cancelled ) return;
				setError( err.message || __( 'Could not load media.', 'goodblocks' ) );
			} )
			.finally( () => {
				if ( cancelled ) return;
				isLoadingRef.current = false;
				setIsLoading( false );
			} );

		return () => {
			cancelled = true;
		};
	}, [ page, search, isModalOpen ] );

	// Sentinel-based infinite scroll — reconnect when hasMore changes.
	useEffect( () => {
		if ( ! isModalOpen || ! sentinelRef.current ) return;

		const observer = new IntersectionObserver(
			( entries ) => {
				if ( entries[ 0 ].isIntersecting && ! isLoadingRef.current && hasMore ) {
					setPage( ( p ) => p + 1 );
				}
			},
			{ threshold: 0.1 }
		);

		observer.observe( sentinelRef.current );
		return () => observer.disconnect();
	}, [ isModalOpen, hasMore ] );

	function openModal() {
		setItems( [] );
		setPage( 1 );
		setHasMore( true );
		setError( null );
		setSearch( '' );
		setIsModalOpen( true );
	}

	const handleSearch = useCallback( ( value ) => {
		clearTimeout( searchTimer.current );
		searchTimer.current = setTimeout( () => {
			setItems( [] );
			setPage( 1 );
			setHasMore( true );
			setSearch( value );
		}, 400 );
	}, [] );

	async function pickItem( item ) {
		setIsSideloading( true );
		setError( null );
		try {
			const data = await apiFetch( {
				path:   '/goodblocks/v1/agoodapp/sideload',
				method: 'POST',
				data:   {
					source_id:  String( item.id ),
					url:        item.web_path,
					title:      item.title,
					filename:   item.filename,
					media_type: item.file_type ?? 'image',
				},
			} );
			setAttributes( {
				attachmentId:     data.attachment_id,
				mediaType:        data.media_type,
				agoodappSourceId: String( item.id ),
				title:            item.title,
			} );
			setIsModalOpen( false );
		} catch ( err ) {
			setError( err.message || __( 'Upload failed.', 'goodblocks' ) );
		} finally {
			setIsSideloading( false );
		}
	}

	return (
		<>
			<BlockControls>
				<ToolbarGroup>
					<ToolbarButton
						icon={ imageIcon }
						label={
							attachmentId
								? __( 'Replace media', 'goodblocks' )
								: __( 'Choose from AGoodApp', 'goodblocks' )
						}
						onClick={ openModal }
					/>
				</ToolbarGroup>
			</BlockControls>

			<div { ...blockProps }>
				{ ! attachmentId ? (
					<div className="agoodapp-media-picker__placeholder">
						<button
							className="agoodapp-media-picker__placeholder-btn"
							onClick={ openModal }
							type="button"
						>
							{ __( 'Choose media from AGoodApp', 'goodblocks' ) }
						</button>
					</div>
				) : (
					<div className="agoodapp-media-picker__preview">
						{ mediaType === 'video' ? (
							<video src={ previewUrl } controls />
						) : (
							<img src={ previewUrl } alt={ mediaTitle } />
						) }
						{ mediaTitle && (
							<p className="agoodapp-media-picker__title">
								{ mediaTitle }
							</p>
						) }
					</div>
				) }
			</div>

			{ isModalOpen && (
				<Modal
					title={ __( 'AGoodApp Media', 'goodblocks' ) }
					onRequestClose={ () => setIsModalOpen( false ) }
					className="agoodapp-modal"
					size="large"
				>
					{ error && (
						<Notice status="error" isDismissible={ false }>
							{ error }
						</Notice>
					) }
					<SearchControl
						value={ search }
						onChange={ handleSearch }
						placeholder={ __( 'Search media…', 'goodblocks' ) }
						className="agoodapp-modal__search"
					/>
					{ isSideloading && (
						<div className="agoodapp-modal__sideloading">
							<Spinner />
							{ __( 'Uploading to media library…', 'goodblocks' ) }
						</div>
					) }
					<div className="agoodapp-modal__grid">
						{ items.map( ( item ) => (
							<button
								key={ item.id }
								className="agoodapp-modal__item"
								onClick={ () => pickItem( item ) }
								type="button"
								disabled={ isSideloading }
								title={ item.title }
							>
								{ item.file_type === 'video' ? (
									<div className="agoodapp-modal__video-thumb">
										<img
											src={ item.thumbnail_path }
											alt={ item.title }
											className="agoodapp-modal__thumbnail"
											loading="lazy"
										/>
										<span
											className="agoodapp-modal__video-badge"
											aria-hidden="true"
										>
											▶
										</span>
									</div>
								) : (
									<img
										src={ item.thumbnail_path }
										alt={ item.title }
										className="agoodapp-modal__thumbnail"
										loading="lazy"
									/>
								) }
							</button>
						) ) }
					</div>
					{ isLoading && (
						<div className="agoodapp-modal__loading">
							<Spinner />
						</div>
					) }
					<div ref={ sentinelRef } className="agoodapp-modal__sentinel" />
				</Modal>
			) }
		</>
	);
}

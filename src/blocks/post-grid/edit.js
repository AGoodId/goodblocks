import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	ToggleControl,
	TextControl,
	SelectControl,
	RangeControl,
	ComboboxControl,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreDataStore } from '@wordpress/core-data';
import './editor.scss';
import ServerSideRender from '@wordpress/server-side-render';

const Edit = ({ attributes, setAttributes }) => {
	const {
		showTitle,
		showFeaturedImage,
		showExcerpt,
		excerptLength,
		showDate,
		showAuthor,
		aspectRatio,
		postsToShow,
		postType,
		taxonomyTerms,
		sortOrder,
		metaKey,
		gridType,
		showChildren,
		parentPost,
		showMoreLink,
		moreLinkText,
		moreLinkUrl,
		selectedTaxonomy,
		noPostsText,
	} = attributes;

	const postTypes = useSelect((select) => {
		const types = select(coreDataStore).getPostTypes({ per_page: -1 }) || [];
		return [...types, { name: __('Alla', 'goodblocks'), slug: 'any' }];
	});

	const taxonomies = useSelect((select) => {
		return select(coreDataStore).getTaxonomies() || [];
	});

	const terms = useSelect(
		(select) => {
			if (!selectedTaxonomy) return [];
			const fetchedTerms =
				select(coreDataStore).getEntityRecords('taxonomy', selectedTaxonomy, {
					per_page: -1,
				}) || [];
			return [{ name: __('Ingen', 'goodblocks'), slug: '' }, ...fetchedTerms];
		},
		[selectedTaxonomy]
	);

	const parentPosts = useSelect(
		(select) => {
			return (
				select('core').getEntityRecords('postType', postType || 'post', {
					per_page: 20,
				}) || []
			);
		},
		[postType]
	);

	return (
		<div {...useBlockProps()}>
			<InspectorControls>
				<PanelBody title={__('Inställningar', 'goodblocks')}>
					<ToggleControl
						label={__('Visa rubrik', 'goodblocks')}
						checked={showTitle}
						onChange={(value) => setAttributes({ showTitle: value })}
					/>
					<ToggleControl
						label={__('Visa utvald bild', 'goodblocks')}
						checked={showFeaturedImage}
						onChange={(value) => setAttributes({ showFeaturedImage: value })}
					/>
					<SelectControl
						label={__('Aspect ratio', 'goodblocks')}
						value={aspectRatio}
						options={[
							{ label: '16:9', value: '16/9' },
							{ label: '4:3', value: '4/3' },
							{ label: '2:3', value: '2/3' },
						]}
						onChange={(value) => setAttributes({ aspectRatio: value })}
					/>
					<ToggleControl
						label={__('Visa utdrag', 'goodblocks')}
						checked={showExcerpt}
						onChange={(value) => setAttributes({ showExcerpt: value })}
					/>
					{showExcerpt && (
						<RangeControl
							label={__('Utdrag längd', 'goodblocks')}
							value={excerptLength}
							onChange={(value) => setAttributes({ excerptLength: value })}
							min={20}
							max={200}
						/>
					)}
					<ToggleControl
						label={__('Visa datum', 'goodblocks')}
						checked={showDate}
						onChange={(value) => setAttributes({ showDate: value })}
					/>
					<ToggleControl
						label={__('Visa författare', 'goodblocks')}
						checked={showAuthor}
						onChange={(value) => setAttributes({ showAuthor: value })}
					/>
					<TextControl
						label={__('Meta fält', 'goodblocks')}
						value={metaKey}
						onChange={(value) => setAttributes({ metaKey: value })}
					/>
					<RangeControl
						label={__('Antal inlägg att visa', 'goodblocks')}
						value={postsToShow}
						onChange={(value) => setAttributes({ postsToShow: value })}
						min={-1}
						max={100}
						help={__('Använd -1 för att visa alla inlägg', 'goodblocks')}
					/>
					<RangeControl
						label={__('Inlägg per rad', 'goodblocks')}
						value={attributes.postsPerRow}
						onChange={(value) => setAttributes({ postsPerRow: value })}
						min={1}
						max={4}
					/>
					<SelectControl
						label={__('Post type', 'goodblocks')}
						value={postType}
						options={
							postTypes &&
							postTypes.map((type) => ({
								label: type.name,
								value: type.slug,
							}))
						}
						onChange={(value) => setAttributes({ postType: value })}
					/>
					<SelectControl
						label={__('Sorteringsordning', 'goodblocks')}
						value={sortOrder}
						options={[
							{ label: __('Publiceringsdatum, fallande', 'goodblocks'), value: 'desc' },
							{ label: __('Publiceringsdatum, stigande', 'goodblocks'), value: 'asc' },
							{ label: __('Senast uppdaterad, fallande', 'goodblocks'), value: 'modified_desc' },
							{ label: __('Sorteringsordning (drag & drop)', 'goodblocks'), value: 'menu' },
							{ label: __('Evenemangsdatum, kommande', 'goodblocks'), value: 'date_upcoming' },
							{ label: __('Evenemangsdatum, tidigare', 'goodblocks'), value: 'date_past' },
							{ label: __('Metavärde, fallande', 'goodblocks'), value: 'meta_desc' },
							{ label: __('Metavärde, stigande', 'goodblocks'), value: 'meta_asc' },
						]}
						onChange={(value) => setAttributes({ sortOrder: value })}
					/>
					{(sortOrder === 'meta_asc' || sortOrder === 'meta_desc') && (
						<TextControl
							label={__('Metanamn', 'goodblocks')}
							value={attributes.metaKey}
							onChange={(value) => setAttributes({ metaKey: value })}
						/>
					)}
					<ToggleControl
						label={__('Visa undersidor', 'goodblocks')}
						checked={showChildren}
						onChange={(value) => setAttributes({ showChildren: value })}
					/>
					{showChildren && (
						<SelectControl
							label={__('Välj förälder', 'goodblocks')}
							value={parentPost}
							options={[
								{ label: __('Ingen', 'goodblocks'), value: 0 },
								...parentPosts.map((post) => ({
									label: post.title.rendered,
									value: post.id,
								})),
							]}
							onChange={(value) =>
								setAttributes({ parentPost: parseInt(value, 10) })
							}
						/>
					)}
					<SelectControl
						label={__('Taxonomi', 'goodblocks')}
						value={selectedTaxonomy || ''}
						options={[
							{ label: __('Välj taxonomi', 'goodblocks'), value: '' },
							...taxonomies.map((taxonomy) => ({
								label: taxonomy.name,
								value: taxonomy.slug,
							})),
						]}
						onChange={(value) =>
							setAttributes({ selectedTaxonomy: value, taxonomyTerms: '' })
						}
					/>
					{selectedTaxonomy && (
						<ComboboxControl
							label={__('Termer', 'goodblocks')}
							value={taxonomyTerms}
							options={
								terms &&
								terms.map((term) => ({
									label: term.name,
									value: term.slug,
								}))
							}
							onChange={(value) => setAttributes({ taxonomyTerms: value })}
						/>
					)}
					<SelectControl
						label={__('Typ av post grid', 'goodblocks')}
						value={gridType}
						options={[
							{ label: 'Grid', value: 'grid' },
							{ label: __('Lista', 'goodblocks'), value: 'list' },
							{ label: 'People', value: 'people' },
							{ label: 'Timeline', value: 'timeline' },
						]}
						onChange={(value) => setAttributes({ gridType: value })}
					/>
					<ToggleControl
						label={__('Visa "Läs mer"-länk', 'goodblocks')}
						checked={showMoreLink}
						onChange={(value) => setAttributes({ showMoreLink: value })}
					/>
					{showMoreLink && (
						<>
							<TextControl
								label={__('Länktext', 'goodblocks')}
								value={moreLinkText}
								onChange={(value) => setAttributes({ moreLinkText: value })}
								placeholder="Fler nyheter"
							/>
							<TextControl
								label={__('Länk-URL', 'goodblocks')}
								value={moreLinkUrl}
								onChange={(value) => setAttributes({ moreLinkUrl: value })}
								placeholder="https://example.com/news"
							/>
						</>
					)}
					<TextControl
						label={__('Text när inga inlägg hittades', 'goodblocks')}
						value={noPostsText}
						onChange={(value) => setAttributes({ noPostsText: value })}
						placeholder={__('Inga inlägg hittades.', 'goodblocks')}
					/>
				</PanelBody>
			</InspectorControls>
			<ServerSideRender block="goodblocks/post-grid" attributes={attributes} />
		</div>
	);
};

export default Edit;

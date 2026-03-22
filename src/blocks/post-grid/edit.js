import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	ToggleControl,
	TextControl,
	SelectControl,
	RangeControl,
	CheckboxControl,
	FormTokenField,
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
		postTypes: selectedPostTypes,
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

	// Migrate legacy single postType to postTypes array.
	const activePostTypes = selectedPostTypes && selectedPostTypes.length > 0
		? selectedPostTypes
		: (attributes.postType ? [attributes.postType] : ['post']);

	const allPostTypes = useSelect((select) => {
		return (select(coreDataStore).getPostTypes({ per_page: -1 }) || [])
			.filter((t) => t.viewable && t.slug !== 'attachment');
	});

	const taxonomies = useSelect((select) => {
		return select(coreDataStore).getTaxonomies() || [];
	});

	// Filter taxonomies to those that apply to selected post types.
	const relevantTaxonomies = taxonomies.filter((tax) =>
		tax.types?.some((type) => activePostTypes.includes(type))
	);

	const terms = useSelect(
		(select) => {
			if (!selectedTaxonomy) return [];
			return (
				select(coreDataStore).getEntityRecords('taxonomy', selectedTaxonomy, {
					per_page: -1,
				}) || []
			);
		},
		[selectedTaxonomy]
	);

	// For parent post picker, use first selected post type.
	const primaryPostType = activePostTypes[0] || 'post';
	const parentPosts = useSelect(
		(select) => {
			return (
				select('core').getEntityRecords('postType', primaryPostType, {
					per_page: 20,
				}) || []
			);
		},
		[primaryPostType]
	);

	const handlePostTypeToggle = (slug, checked) => {
		const updated = checked
			? [...activePostTypes, slug]
			: activePostTypes.filter((s) => s !== slug);
		setAttributes({
			postTypes: updated.length > 0 ? updated : ['post'],
			// Also keep legacy attribute in sync for ServerSideRender.
			postType: updated.length > 0 ? updated.join(',') : 'post',
		});
	};

	// Term multi-select helpers.
	const termNames = terms.map((t) => t.name);
	const selectedTermNames = (taxonomyTerms || [])
		.map((slug) => {
			const found = terms.find((t) => t.slug === slug);
			return found ? found.name : null;
		})
		.filter(Boolean);

	const handleTermChange = (names) => {
		const slugs = names
			.map((name) => {
				const found = terms.find((t) => t.name === name);
				return found ? found.slug : null;
			})
			.filter(Boolean);
		setAttributes({ taxonomyTerms: slugs });
	};

	// Build SSR-compatible attributes (postType as comma-separated, taxonomyTerms as comma-separated).
	const ssrAttributes = {
		...attributes,
		postType: activePostTypes.join(','),
		taxonomyTerms: Array.isArray(taxonomyTerms) ? taxonomyTerms.join(',') : taxonomyTerms || '',
	};

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
				</PanelBody>

				<PanelBody title={__('Innehållskällor', 'goodblocks')} initialOpen={true}>
					<p className="components-base-control__label">
						{__('Post types', 'goodblocks')}
					</p>
					{allPostTypes &&
						allPostTypes.map((type) => (
							<CheckboxControl
								key={type.slug}
								label={type.labels?.singular_name || type.name}
								checked={activePostTypes.includes(type.slug)}
								onChange={(checked) => handlePostTypeToggle(type.slug, checked)}
							/>
						))}

					<SelectControl
						label={__('Taxonomi', 'goodblocks')}
						value={selectedTaxonomy || ''}
						options={[
							{ label: __('Välj taxonomi', 'goodblocks'), value: '' },
							...relevantTaxonomies.map((taxonomy) => ({
								label: taxonomy.name,
								value: taxonomy.slug,
							})),
						]}
						onChange={(value) =>
							setAttributes({ selectedTaxonomy: value, taxonomyTerms: [] })
						}
					/>
					{selectedTaxonomy && terms.length > 0 && (
						<FormTokenField
							label={__('Termer', 'goodblocks')}
							value={selectedTermNames}
							suggestions={termNames}
							onChange={handleTermChange}
							__experimentalExpandOnFocus
						/>
					)}
				</PanelBody>

				<PanelBody title={__('Sortering & filter', 'goodblocks')} initialOpen={false}>
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
				</PanelBody>

				<PanelBody title={__('Layout', 'goodblocks')} initialOpen={false}>
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
			<ServerSideRender block="goodblocks/post-grid" attributes={ssrAttributes} />
		</div>
	);
};

export default Edit;

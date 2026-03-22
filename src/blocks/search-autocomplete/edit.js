/**
 * Search Autocomplete Block - Editor
 */
import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	InspectorControls,
} from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	RangeControl,
	ToggleControl,
	SelectControl,
} from '@wordpress/components';
import { useEffect } from '@wordpress/element';

export default function Edit({ attributes, setAttributes, clientId }) {
	const {
		blockId,
		placeholder,
		minChars,
		maxResults,
		postTypes,
		showThumbnail,
		showExcerpt,
		showType,
		expandable,
		buttonStyle,
	} = attributes;

	// Set unique block ID
	useEffect(() => {
		if (!blockId) {
			setAttributes({ blockId: `search-${clientId.slice(0, 8)}` });
		}
	}, [blockId, clientId, setAttributes]);

	const blockProps = useBlockProps({
		className: 'search-autocomplete-editor',
	});

	return (
		<>
			<InspectorControls>
				<PanelBody title={__('Sökinställningar', 'goodblocks')}>
					<TextControl
						label={__('Placeholder-text', 'goodblocks')}
						value={placeholder}
						onChange={(value) => setAttributes({ placeholder: value })}
					/>
					<RangeControl
						label={__('Minsta antal tecken', 'goodblocks')}
						value={minChars}
						onChange={(value) => setAttributes({ minChars: value })}
						min={1}
						max={5}
						help={__('Antal tecken innan sökning startar', 'goodblocks')}
					/>
					<RangeControl
						label={__('Max antal resultat', 'goodblocks')}
						value={maxResults}
						onChange={(value) => setAttributes({ maxResults: value })}
						min={3}
						max={10}
					/>
					<TextControl
						label={__('Posttyper', 'goodblocks')}
						value={postTypes}
						onChange={(value) => setAttributes({ postTypes: value })}
						help={__('Kommaseparerade posttyper: post,page,projekt', 'goodblocks')}
					/>
				</PanelBody>

				<PanelBody title={__('Visning', 'goodblocks')} initialOpen={false}>
					<ToggleControl
						label={__('Visa miniatyrbilder', 'goodblocks')}
						checked={showThumbnail}
						onChange={(value) => setAttributes({ showThumbnail: value })}
					/>
					<ToggleControl
						label={__('Visa utdrag', 'goodblocks')}
						checked={showExcerpt}
						onChange={(value) => setAttributes({ showExcerpt: value })}
					/>
					<ToggleControl
						label={__('Visa posttyp', 'goodblocks')}
						checked={showType}
						onChange={(value) => setAttributes({ showType: value })}
					/>
				</PanelBody>

				<PanelBody title={__('Beteende', 'goodblocks')} initialOpen={false}>
					<ToggleControl
						label={__('Expanderbart sökfält', 'goodblocks')}
						checked={expandable}
						onChange={(value) => setAttributes({ expandable: value })}
						help={__('Sökfältet expanderar från ikon vid klick', 'goodblocks')}
					/>
					<SelectControl
						label={__('Knappstil', 'goodblocks')}
						value={buttonStyle}
						options={[
							{ label: __('Endast ikon', 'goodblocks'), value: 'icon' },
							{ label: __('Endast text', 'goodblocks'), value: 'text' },
							{ label: __('Ikon + text', 'goodblocks'), value: 'both' },
						]}
						onChange={(value) => setAttributes({ buttonStyle: value })}
					/>
				</PanelBody>

			</InspectorControls>

			<div {...blockProps}>
				<div className="search-autocomplete-editor__preview">
					<div className="search-autocomplete-editor__field">
						<svg
							className="search-autocomplete-editor__icon"
							width="20"
							height="20"
							viewBox="0 0 24 24"
							fill="none"
							stroke="currentColor"
							strokeWidth="2"
						>
							<circle cx="11" cy="11" r="8" />
							<path d="M21 21l-4.35-4.35" />
						</svg>
						<input
							type="text"
							placeholder={placeholder}
							disabled
							className="search-autocomplete-editor__input"
						/>
					</div>
					<div className="search-autocomplete-editor__info">
						{__('Autocomplete-resultat visas här', 'goodblocks')}
					</div>
				</div>
			</div>
		</>
	);
}

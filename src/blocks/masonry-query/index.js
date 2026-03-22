/**
 * Masonry Query Block
 *
 * Dynamic masonry grid that fetches posts, pages, or custom post types
 */

import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import metadata from './block.json';
import './style.scss';
import './editor.scss';

// Migrate old preset gap strings to pixel numbers
const presetToPx = { xs: 4, sm: 8, md: 16, lg: 24, xl: 40 };

registerBlockType( metadata.name, {
	...metadata,
	edit: Edit,
	save: () => null, // Dynamic block, rendered on server
	deprecated: [
		{
			attributes: {
				...metadata.attributes,
				gap: { type: 'string', default: 'md' },
			},
			save: () => null,
			migrate( attributes ) {
				return {
					...attributes,
					gap: presetToPx[ attributes.gap ] ?? 16,
				};
			},
			isEligible( attributes ) {
				return typeof attributes.gap === 'string';
			},
		},
	],
} );

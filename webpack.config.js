/**
 * GoodBlocks webpack config
 */

const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
	...defaultConfig,
	entry: {
		// Existing blocks
		'blocks/masonry-query/index': path.resolve(__dirname, 'src/blocks/masonry-query/index.js'),
		'blocks/masonry-query/view': path.resolve(__dirname, 'src/blocks/masonry-query/view.js'),
		'blocks/card-feature/index': path.resolve(__dirname, 'src/blocks/card-feature/index.js'),
		'blocks/search-autocomplete/index': path.resolve(__dirname, 'src/blocks/search-autocomplete/index.js'),
		'blocks/search-autocomplete/view': path.resolve(__dirname, 'src/blocks/search-autocomplete/view.js'),
		'blocks/image-compare/index': path.resolve(__dirname, 'src/blocks/image-compare/index.js'),
		'blocks/image-compare/view': path.resolve(__dirname, 'src/blocks/image-compare/view.js'),
		// Imported blocks
		'blocks/countdown/index': path.resolve(__dirname, 'src/blocks/countdown/index.js'),
		'blocks/countdown/view': path.resolve(__dirname, 'src/blocks/countdown/view.js'),
		'blocks/quiz/index': path.resolve(__dirname, 'src/blocks/quiz/index.js'),
		'blocks/quiz/view': path.resolve(__dirname, 'src/blocks/quiz/view.js'),
		'blocks/page-list/index': path.resolve(__dirname, 'src/blocks/page-list/index.js'),
		'blocks/double-container-text/index': path.resolve(__dirname, 'src/blocks/double-container-text/index.js'),
		'blocks/media-grid/index': path.resolve(__dirname, 'src/blocks/media-grid/index.js'),
		'blocks/media-grid/view': path.resolve(__dirname, 'src/blocks/media-grid/view.js'),
		'blocks/media-grid-item/index': path.resolve(__dirname, 'src/blocks/media-grid-item/index.js'),
		'blocks/mailchimp-signup/index': path.resolve(__dirname, 'src/blocks/mailchimp-signup/index.js'),
	},
	output: {
		...defaultConfig.output,
		path: path.resolve(__dirname, 'build'),
	},
};

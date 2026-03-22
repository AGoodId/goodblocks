/**
 * GoodBlocks webpack config
 */

const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
	...defaultConfig,
	entry: {
		'blocks/masonry-query/index': path.resolve(__dirname, 'src/blocks/masonry-query/index.js'),
		'blocks/masonry-query/view': path.resolve(__dirname, 'src/blocks/masonry-query/view.js'),
		'blocks/card-feature/index': path.resolve(__dirname, 'src/blocks/card-feature/index.js'),
		'blocks/search-autocomplete/index': path.resolve(__dirname, 'src/blocks/search-autocomplete/index.js'),
		'blocks/search-autocomplete/view': path.resolve(__dirname, 'src/blocks/search-autocomplete/view.js'),
		'blocks/image-compare/index': path.resolve(__dirname, 'src/blocks/image-compare/index.js'),
		'blocks/image-compare/view': path.resolve(__dirname, 'src/blocks/image-compare/view.js'),
	},
	output: {
		...defaultConfig.output,
		path: path.resolve(__dirname, 'build'),
	},
};

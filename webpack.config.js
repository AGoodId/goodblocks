/**
 * GoodBlocks webpack config
 */

const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');
const CopyWebpackPlugin = require('copy-webpack-plugin');

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
		'blocks/post-grid/index': path.resolve(__dirname, 'src/blocks/post-grid/index.js'),
		'blocks/post-grid/view': path.resolve(__dirname, 'src/blocks/post-grid/view.js'),
		// Imported from agoodblocks
		'blocks/hero/index': path.resolve(__dirname, 'src/blocks/hero/index.js'),
		'blocks/slider/index': path.resolve(__dirname, 'src/blocks/slider/index.js'),
		'blocks/slider/view': path.resolve(__dirname, 'src/blocks/slider/view.js'),
		'blocks/slide/index': path.resolve(__dirname, 'src/blocks/slide/index.js'),
		'blocks/product-carousel/index': path.resolve(__dirname, 'src/blocks/product-carousel/index.js'),
		'blocks/product-carousel/view': path.resolve(__dirname, 'src/blocks/product-carousel/view.js'),
		// AGoodApp Media Picker
		'blocks/agoodapp-media-picker/index': path.resolve(__dirname, 'src/blocks/agoodapp-media-picker/index.js'),
	},
	output: {
		...defaultConfig.output,
		path: path.resolve(__dirname, 'build'),
	},
	plugins: [
		...(defaultConfig.plugins || []),
		new CopyWebpackPlugin({
			patterns: [
				{
					from: 'src/blocks/*/templates/**/*.php',
					to({ absoluteFilename }) {
						const relative = path.relative(path.resolve(__dirname, 'src'), absoluteFilename);
						return path.join('blocks', relative.replace(/^blocks[/\\]/, ''));
					},
				},
			],
		}),
	],
};

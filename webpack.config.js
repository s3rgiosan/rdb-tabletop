/**
 * WordPress dependencies.
 */
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaultConfig,
	entry: {
		'block-editor': './src/css/block-editor.css',
	},
	output: {
		...defaultConfig.output,
		path: require( 'path' ).resolve( __dirname, 'build' ),
	},
};

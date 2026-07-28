const path = require('path');
const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const WooCommerceDependencyExtractionWebpackPlugin = require('@woocommerce/dependency-extraction-webpack-plugin');

// Remove SASS rule from the default config so we can define our own.
const defaultRules = defaultConfig.module.rules.filter((rule) => {
	return String(rule.test) !== String(/\.(sc|sa)ss$/);
});

module.exports = {
	...defaultConfig,
	entry: {
		'captcha-block-frontend': path.resolve(process.cwd(), 'src', 'frontend.js'),
	},
	module: {
		...defaultConfig.module,
		rules: [
			...defaultRules,
			{
				test: /\.(sc|sa)ss$/,
				exclude: /node_modules/
			},
		],
	},
	plugins: [
		...defaultConfig.plugins.filter(
			(plugin) => plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
		),
		new WooCommerceDependencyExtractionWebpackPlugin(),
	],
	resolve: {
		// Alias to avoid directly referencing node_modules
		alias: {
			'@components': path.resolve(__dirname, 'src/components/'),
			'@blocks': path.resolve(__dirname, 'src/blocks/'),
			// Add other aliases as needed
		},
		modules: [
			path.resolve(__dirname, 'src'), // Allow imports from src
			'node_modules' // Keep this for fallback
		]
	},
	output: {
		filename: 'captcha-block-frontend.js',
		path: path.resolve(__dirname, 'build'), // Adjust as needed
		library: {
			name: 'MyLibrary',
			type: 'umd', // Universal Module Definition
		},
	},
	externals: {
        grecaptcha: 'grecaptcha', // Expose grecaptcha as an external dependency
    },
};

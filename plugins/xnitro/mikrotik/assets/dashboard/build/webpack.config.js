const path = require('path');
const webpack = require('webpack');

module.exports = {
  entry: './js/main.js',
  watch: true,
  output: {
    filename: 'bundle.js',
    path: path.resolve(__dirname, 'dist')
  },
  plugins: [
  	new webpack.ProvidePlugin({
		Backbone: 'backbone'
	})
  ]
};
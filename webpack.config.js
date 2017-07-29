// webpack.config.js
var path = require('path')
var webpack = require('webpack')
var ExtractTextPlugin = require("extract-text-webpack-plugin");
const UglifyJSPlugin = require('uglifyjs-webpack-plugin');

module.exports = {
    entry: ['./src/index.js'],
    output: {
        path: __dirname+"/dist",
        filename: 'bundle.js'
    },
    module: {
        rules: [{
            // 전 시간 babel-loader
        }, {
            test: /\.css$/,
            use: ExtractTextPlugin.extract({
                fallback: 'style-loader',
                use: 'css-loader'
            }),
        }],
    },
    plugins: [
        new UglifyJSPlugin(),
        new ExtractTextPlugin({
            filename: 'app.css',
        })
    ]

};
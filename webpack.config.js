// webpack.config.js
var path = require('path')
var webpack = require('webpack')
const UglifyJSPlugin = require('uglifyjs-webpack-plugin');

module.exports = {
    entry: ['./src/index.js'],
    output: {
        path: __dirname+"/dist",
        filename: 'bundle.js'
    },
    module: {
        rules: [
            {
                test: /\.css$/,
                use: [
                    'style-loader',
                    'css-loader'
                ]
            },
            {
                test: /\.js$/,
                exclude: /node_modules/,
                loader: 'babel-loader',
                options: {
                    presets: [[
                        'env', {
                            targets: {
                                browsers: ['last 2 versions']
                            }
                        }
                    ]]
                }
            }
        ]
    },
    plugins: [
        new UglifyJSPlugin()
    ]

};
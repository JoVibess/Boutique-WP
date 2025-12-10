const webpack = require("webpack");
const path = require("path");
const ExtractTextWebpackPlugin = require("extract-text-webpack-plugin");
const CopyWebpackPlugin = require('copy-webpack-plugin');

let config = {
  entry: "./app.js",
  output: {
    path: path.resolve(__dirname, "../build"),
    filename: "./js/index.js"
  },
  module: {
    rules: [
      {
        test: /\.js$/,
        exclude: /node_modules/,
        loader: "babel-loader"
      },
      {
        test: /\.scss$/,
        use: ExtractTextWebpackPlugin.extract({
          fallback: 'style-loader',
          use: ['css-loader', 'sass-loader', 'postcss-loader'],
        }),
      },
      {
        test: /\.woff(2)?(\?v=[0-9]\.[0-9]\.[0-9])?$/,
        loader: "url-loader?limit=10000&mimetype=application/font-woff",
        options: {
          outputPath: 'fonts',
        },
      },
      {
        test: /\.(ttf|eot|svg)(\?v=[0-9]\.[0-9]\.[0-9])?$/,
        loader: "file-loader",
        options: {
          outputPath: 'fonts',
        },
      },
      {
        test: /\.(png|gif|webp|jpeg|jpg)(\?v=[0-9]\.[0-9]\.[0-9])?$/,
        loader: "file-loader",
        options: {
            outputPath: 'img',
            publicPath: '../img',
            useRelativePaths: true
        },
      },
      {
        test: /\.(mp3|mp4|webm)(\?v=[0-9]\.[0-9]\.[0-9])?$/,
        loader: "file-loader",
        options: {
            outputPath: 'media',
        },
      },
      {
        test: /\.less$/,
        loader: 'less-loader' // compiles Less to CSS
      },
      { test: /\.css$/, loader: "style-loader!css-loader" }
    ]
  },
  plugins: [
    new ExtractTextWebpackPlugin({ filename: "css/index.css"}),
    new CopyWebpackPlugin([
        {from:'./img',to:'img'}
    ]),
    new webpack.ProvidePlugin({
        $: 'jquery',
        jQuery: 'jquery',
        'window.jQuery': 'jquery'
    }),
  ]
}

module.exports = config;

module.exports = {
    slug: 'test',
    jsMinSuffix: '.min',
    browserify : {
        src: 'js/main.js',
        dest: 'js/',
        fileName: 'build.js',
        watchFiles: [
            'js/**'
        ]
    }
};
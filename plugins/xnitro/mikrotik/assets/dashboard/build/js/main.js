String.prototype.panelsProcessTemplate = function() {
    var s = this;
    s = s.replace(/{{%/g, '<%');
    s = s.replace(/%}}/g, '%>');
    s = s.trim();
    return s;
};

jQuery(function($) {
    var main = {}
    window.main = main
    main.router = require('./route')
    
    // Backbone.history.start()
});
var main = window.main, $ = jQuery;

module.exports = Backbone.Router.extend( {
	routes: {
		"": "default",
		"test": "test"
	},

	default: function(){
		console.log('ini default')
	},

	test: function(){
		console.log('ini test')
	}
});
$(document).ready(function(){
	$(document).on('change', '[data-control="selectServer"]', function(e){
		var url = window.location.origin+window.location.pathname
		if($(this).val()==''){
			window.location = url
		}else{
			window.location = url+'?server='+$(this).val()
		}
	})
})
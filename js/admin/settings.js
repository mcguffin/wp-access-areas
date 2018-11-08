(function($){
	// init tabs
	$(document).on('click','.tab-links a',function(e){
		e.preventDefault();
		var $group = $(this).closest('.tab-group');
		$group.find('.active').removeClass('active');
		$group.find( $(this).attr('href') ).addClass('active');
		$(this).closest('li').addClass('active');
	});
})(jQuery)

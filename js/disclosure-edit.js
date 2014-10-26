(function($){
	$(document).on('change','#wpaa_enable_custom_behaviour',function(e){
		var $this = $(this), $parent = $this.closest('.wpaa-select-behaviour');
		if ( $this.prop('checked') ) {
			$parent.addClass('custom');
		} else {
			$parent.removeClass('custom');
		}
	});
})(jQuery);


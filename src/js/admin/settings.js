(function($){

	function init(){
		$('.tab-group').each(function(i,el){
			var key = $(this).closest('tr').attr('class'),
				tab = window.localStorage.getItem( key + '-active-tab' );
				console.log(tab)
			if ( tab ) {
				$(this).find('[href="'+tab+'"]').trigger('click');
			}

		});
	}
	// init tabs
	$(document)
		.on('click','.tab-links a',function(e){
			e.preventDefault();
			var $group = $(this).closest('.tab-group'),
				key = $group.closest('tr').attr('class'),
				href = $(this).attr('href');
			$group.find('.active').removeClass('active');
			$group.find( href ).addClass('active');
			$(this).closest('li').addClass('active');

			window.localStorage.setItem( key + '-active-tab', href );
		})
		.on('click','.wpaa-behavior [type="radio"]',function(e){
			$(this).closest('.wpaa-behavior').attr('data-value',$(this).val());
		})
		.ready(init);
})(jQuery)

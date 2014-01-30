jQuery(document).ready(function($){
	$('.bulkactions').on( 'click' , '.button.action'  , function( event ) {
		var props = ['post_view_cap','post_edit_cap','post_comment_cap'];
		for ( var s in props )
			$('select#'+props[s]+'-select:visible').val( '' );
	});
	$('#the-list').on( 'click' , 'a.editinline'  , function( event ) {
		var data = { 'action' : 'get_accessarea_values'};
		data.post_ID = inlineEditPost.getId(this);
		$.post( ajaxurl, data,
			function( data, textStatus, jqXHR ) {
				console.log($('.inline-edit-col-access-areas').closest('.inline-edit-row:visible').hasClass('quick-edit-row'));
				console.log($('.inline-edit-col-access-areas').closest('.inline-edit-row:visible').hasClass('bulk-edit-row'));
				
				var fallback = $('.inline-edit-col-access-areas').closest('.inline-edit-row:visible').hasClass('quick-edit-row')?'exist':''
				var props = ['post_view_cap','post_edit_cap','post_comment_cap'];
				for ( var s in props )
					$('select#'+props[s]+'-select:visible').val( data[props[s]] || fallback );
			}			
		);
	})
});
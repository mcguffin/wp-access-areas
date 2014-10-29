(function($){
	// on open bulk action panel ...
	$(document).on( 'click' , '.bulkactions .button.action' , function( e ) {
		var props = ['post_view_cap','post_edit_cap','post_comment_cap'];
		// ... set access selects to neutral values.
		for ( var s in props )
			$('select#'+props[s]+'-select:visible').val( '' );
	} );
	$(document).ready(function(){
		// on open quick edit...
		$('#the-list').on( 'click' , 'a.editinline' , function( e ) {
			// ... set access select values to post settings
			var props = ['post_view_cap','post_edit_cap','post_comment_cap'], s, 
				data = { 
					'action'     : 'get_accessarea_values',
					'ajax_nonce' : wpaa_postedit.ajax_nonce,
					'post_ID'    : inlineEditPost.getId(this)
				};
		
			for ( s in props )
				$('select#'+props[s]+'-select:visible').attr( 'disabled' , 'disabled' );
		
			$.post( ajaxurl, data,
				function( response_data, textStatus, jqXHR ) {
					if ( !!response_data ) {
						var fallback = $('.inline-edit-col-access-areas').closest('.inline-edit-row:visible').hasClass('quick-edit-row')?'exist':''
						for ( s in props )
							$('select#'+props[s]+'-select:visible')
								.removeAttr( 'disabled' )
								.val( response_data[props[s]] || fallback );
					}			
				}			
			);
		});
	});
})(jQuery);

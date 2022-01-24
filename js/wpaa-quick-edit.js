(function($){
	var busy = false;
	// on open bulk action panel ...
	$( document ).on(
        'click',
        '.bulkactions .button.action',
        function( e ) {
		var props = ['post_view_cap','post_edit_cap','post_comment_cap'];
		// ... set access selects to neutral values.
		for ( var s in props ) {
			$( 'select#' + props[s] + '-select:visible' ).val( '' );
		}
	});

	$(document).on('click','#the-list .editinline', function(e){
		console.log( ! busy , $(e.target).is( '#the-list .editinline' ) )
		if ( ! busy ) {
			busy = true
			// ... set access select values to post settings
			var props = ['post_view_cap','post_edit_cap','post_comment_cap'], s,
				data  = {
					'action'     : 'get_accessarea_values',
					'_ajax_nonce' : wpaa_postedit.ajax_nonce,
					'post_ID'    : inlineEditPost.getId( this )
				};

			for ( s in props ) {
				$( 'select#' + props[s] + '-select:visible' ).attr( 'disabled' , 'disabled' );
			}
			$.post(
				ajaxurl,
				data,
				function( response_data, textStatus, jqXHR ) {
					busy = false
					if ( !! response_data ) {
						var fallback = $( '.inline-edit-col-access-areas' ).closest( '.inline-edit-row:visible' ).hasClass( 'quick-edit-row' ) ? 'exist' : ''
						for ( s in props )
							$( 'select#' + props[s] + '-select:visible' )
								.removeAttr( 'disabled' )
								.val( response_data[props[s]] || fallback );
					}
				}
			);
		}
	})

	document.addEventListener( 'DOMContentLoaded', function() {

		$('a.editinline').on(
				'click',
				function( e ) {
				}
			);
	})

})( jQuery );

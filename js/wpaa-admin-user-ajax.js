(function($){
	// $(document).ready(function(){
	$( document )
		.on(
            'click' ,
            '.cap-add-submit' ,
            function(e){
				// send ajax.
				var data    = {
					'action' : 'add_accessarea',
					'_ajax_nonce' : $( e.target ).attr( 'data-nonce' )
				};
				var $parent = $( this ).closest( '.ajax-add-item' );
				$( this ).closest( '.ajax-add-item' ).find( 'input' ).each(
	                function(){
					data[$( this ).attr( 'name' )] = $( this ).val();
				}
	                ); // nonce,
				$.post(
					ajaxurl,
	                data,
					function( data, textStatus, jqXHR ) {
						$( data ).insertBefore( $parent );
						$parent.find( "input[name='cap_title']" ).val( '' );
						$( '.wpaa-label-item.error' ).fadeOut( 5000,function(){$( this ).remove()} );
					}
				);
				return false;
			}
		)
		.on(
            'keypress' ,
            '.cap-add:focus' ,
            function(event){
				if ( event.keyCode == 13 ) {
						event.preventDefault();
						event.stopPropagation();
						$( this ).next( '.cap-add-submit' ).click();
						return false;
				} else {
						if ( '' == $( this ).val().replace( /^\s+|\s+$/g,'' ) )
						$( this ).next( '.cap-add-submit' ).attr( 'disabled','disabled' );
						else
						$( this ).next( '.cap-add-submit' ).removeAttr( 'disabled' );
				}
			}
		)
		.on(
            'keyup' ,
            '.cap-add:focus' ,
            function(event){
				if ( '' == $( this ).val().replace( /^\s+|\s+$/g,'' ) ) {
					$( this ).next( '.cap-add-submit' ).attr( 'disabled','disabled' );
				} else {
					$( this ).next( '.cap-add-submit' ).removeAttr( 'disabled' );
				}
			}
		);
})( jQuery );

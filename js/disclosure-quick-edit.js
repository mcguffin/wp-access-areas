jQuery(document).ready(function($){
	$('#the-list').on( 'click' , 'a.editinline'  , function( event ) {
		var data = { 'action' : 'get_accessarea_values'};
		data.post_ID = 	inlineEditPost.getId(this);
		$.post( ajaxurl, data,
			function( data, textStatus, jqXHR ) {
				var props=['post_view_cap','post_edit_cap','post_comment_cap']
				for ( var s in props ) {
					var val = data[props[s]];
					console.log(props[s],val);
					
					$('select#'+props[s]+'-select').val( val );
				}
			}			
		);
	})
});
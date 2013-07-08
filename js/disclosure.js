(function($){$(document).ready(function(){
	
	$('#add-disclosure-group').keyup(function(){
		if (sanitize($(this).val()) != '')
			$('#do-add-disclosure-group').removeAttr('disabled');
		else 
			$('#do-add-disclosure-group').attr('disabled','disabled');
	});
	
	$('#do-add-disclosure-group').click(function(){
		var title = $('#add-disclosure-group').val();
		$('#add-disclosure-group').val('');
		var i = $('.disclosure-group-item').length;
		var html = '<span class="disclosure-group-item">\
			<input type="hidden" name="capabilities['+i+'][title]" value="'+title+'" />\
			<input type="hidden" name="capabilities['+i+'][has_cap]" value="0" />\
			<input id="cap-'+i+'" type="checkbox" name="capabilities['+i+'][has_cap]" value="1" checked="checked" />\
			<label for="cap-'+i+'">'+title+'</label></span>';
		$(html).appendTo('#disclosure-group-items');
		return false;
	});

	function sanitize(v) {
		return v.replace(/(\s+)/ , '' );
	}

});})(jQuery);
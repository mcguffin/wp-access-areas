(function($,exports){

	var wpaa = exports.accessAreas;


	$(document).ready(function(){
		var $el = $('.access-areas-list-table');
		if ( $el.length ) {
			new wpaa.view.TableViewManage({
				el: $el.get(0)
			});
		}
	});

})(jQuery,wp);

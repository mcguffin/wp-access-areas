(function($,exports){

	var wpaa = exports.accessAreas,
		model = new wpaa.model.Assign(),
		modal = new wpaa.view.ModalAssign({model:model,propagate:false});



	$(document)
		.on('click','body.users-php .button.action',function(e){
			var action = $(this).prev('select').val(),
				users = [];
			e.preventDefault();
			if ( ['wpaa-grant','wpaa-revoke'].indexOf(action) !== -1 ) {
				$('[name="users[]"]:checked').each(function(i,el){
					users.push($(this).val());
				});

				model.set('action', action.replace('wpaa-',''));
				model.set('user_id', users );
				modal.open();
			}
		})
		.on('click','[data-wpaa-action]',function(e){

			e.preventDefault();

			var action = $(this).attr('data-wpaa-action'),
				self = this;

			model.set('action', action );
			model.set('user_id', $(this).attr('data-wpaa-user') );

			if ( action === 'revoke' ) {
				model.set( 'id', $(this).attr('data-wpaa-access-area') );
				model.save(null,{
					success:function(){
						$(self).closest('.wpaa-access-area').remove();
					},
					error:function(e,response) {
						var model = new Backbone.Model(response.responseJSON),
							notice = new wp.accessAreas.view.Notice({
								model: model
							});
						model.set('dismissible',true);
						model.set('type','error');
						// print message
						//this.$('.notice').remove();
						notice.render().$el.insertfter($('.wp-header-end'));
					}
				});
			} else if ( action === 'grant' ) {
				modal.open();
			}
		})
		// .on('click','[data-wpaa-action]',function(e){
		// 	e.preventDefault();
        //
		// })
		;


})(jQuery,wp);

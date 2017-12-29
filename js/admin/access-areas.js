(function($,exports){
	// wpApiSettings.mapping = {
	// 	models: {
	// 		'AccessAreas' : 'AccessArea'
	// 	},
	// 	collections:{}
	// };
	// wp.api.init({
	// 	versionString : 'wpaa/v1/',
	// 	apiRoot: wpApiSettings.root,
	// 	schema: null,
	// 	})
	// 	.done( function() {
	// 		console.log(this, wp.api.endpoints.find({versionString:'wpaa/v1/'}) );
	// 	});

	var l10n = access_areas_admin.l10n,
		wpaa = {
			l10n: l10n,
			view: {},
			model: {}
		};

	wpaa.model.Assign = Backbone.Model.extend({
		defaults:{
			id: null,
			action:'grant',
			users:[]
		},
		save:function() {

			this.url = wpApiSettings.root + wpApiSettings.versionString + 'access-area/' + this.get('id') + '/' + this.get('action');
			Backbone.Model.prototype.save.apply(this,arguments);

		}
	});

	wpaa.view.Notice = wp.media.View.extend({
		template: wp.template('access-area-notice'),
		events:{
			'click .notice-dismiss' : function(){
				this.$el.remove();
			}
		}
	});

	wpaa.view.RowViewManage = wp.media.View.extend({
		template:wp.template('access-area-row'),
		tagName:'tr',
		events: {
			'click a.delete'		: 'delete',
			'click a.edit'			: 'edit',
		},
		initialize: function() {
			wp.media.View.prototype.initialize.apply(this,arguments);
			this.listenTo( this.model, 'before_sync', this.disable );
			this.listenTo( this.model, 'before_destroy', this.disable );
			this.listenTo( this.model, 'sync', this.render );
			this.listenTo( this.model, 'destroy', this.remove );
		},
		render: function() {
			var self = this;
			wp.media.View.prototype.render.apply(this,arguments);
			this.$el.attr('data-id',this.model.get('id'));
			return this;
		},
		disable:function(e){
			this.$el.addClass('disabled');
			return this;
		},
		enable:function(e){
			this.$el.removeClass('disabled');
			return this;
		},
		delete:function(e){
			e.preventDefault();
			this.model.destroy();
			return this;
		},
		edit:function(e){
			this.controller.editEntry(this.model);
			return this;
		},
		delete:function(e){
			this.model.destroy();
			return this;
		}
	});
	wpaa.view.TableViewManage = wp.media.View.extend({
		events: {
			'click .add' : 'newEntry',
		},
		initialize: function() {
			var self = this;
			wp.media.View.prototype.initialize.apply(this,arguments);
			this.collection = new wp.api.collections.AccessArea();
			this.collection.fetch();
			this.collection.once('sync',this.render,this);
			this.listenTo(this.collection,'add',function(model){
				self.addRow(model);
				console.log(arguments);
			});
			this.listenTo(this.collection,'remove',function(model){
				self.setPlaceholder();
			});
			this.render();
		},
		newEntry:function(e) {
			var model = new wp.api.models.AccessArea({
					ID: null,
					title: '',
				});
			this.editEntry(model);
			return this;
		},
		editEntry:function(model) {
			var self = this;
			// open modal
			this.modal.model = model;
			this.modal.render().open({});
			this.listenTo(model,'sync',function(e){
				self.stopListening(model,'sync');
				if ( ! self.collection.where( { id: model.get('id') } ).length ) {
					self.collection.add(model);
//						self.addRow(model);

				}
			});
			return this;
		},
		addRow:function( model ){
			var self = this,
				view = new wpaa.view.RowViewManage({
					model:model,
					controller:this,
				});
			this.$el.append( view.render().el );
			this.listenTo( model,'destroy',function(e){
				view.$el.remove();
				self.setPlaceholder();
			});
			this.setPlaceholder();
			return this;
		},
		hasRow:function(model){
			return this.$('[data-id="'+model.get('id')+'"]').length > 0;
		},
		render: function() {

			var self = this;

			if ( ! this.modal ) {
				this.modal = new wpaa.view.ModalManage({
					controller:	this,
				} );
			}

			wp.media.View.prototype.render.apply(this,arguments);

			this.$placeholder = this.$('.placeholder-row');

			this.setPlaceholder();
			return this;
		},
		setPlaceholder:function(){
			if ( this.collection.length ) {
				this.$placeholder.remove();
			} else if ( ! this.$placeholder.parent().length ) {
				this.$placeholder.appendTo( this.el );
			}
		}
	});


	wpaa.view.ModalManage = wp.media.view.Modal.extend({
		template:wp.template('access-area-modal'),
		events:		{
			'click .modal-close'	: 'close',
			'click #btn-ok'			: 'save',
			'keyup #title-input'	: 'setUIState',
			'change #title-input'	: 'setUIState',
			'blur #title-input'		: 'setUIState',
			'keyup'					: 'onkeyup',
		},
		setUIState:function(e){
			this.$okay.prop('disabled',!this.$title.val());
			return this;
		},
		render: function() {
			wp.media.view.Modal.prototype.render.apply( this, arguments );
			this.$okay = this.$('#btn-ok');
			this.$title = this.$('#title-input');
			this.$title.val( this.model.get( 'title' ) );
			this.setUIState();
			return this;
		},
		onkeyup:function(e) {
			if ( e.originalEvent.keyCode === 27 ) {
				this.close();
			}
		},
		open:function(){
			wp.media.view.Modal.prototype.open.apply( this, arguments );
			this.$('.title').text( !! this.model.get('id') ? l10n.editAccessArea : l10n.createAccessArea );
			this.setUIState();
		},
		save:function() {
			var self = this;

			this.model.set('title', this.$title.val() );
			this.model.save( null, {
				success: function(e) {
					self.close();
					// add message
				},
				error: function(e,response) {
					var model = new Backbone.Model(response.responseJSON),
						notice = new wp.accessAreas.view.Notice({
							model: model
						});
					model.set('dismissible',true);
					model.set('type','error');
					// print message
					//this.$('.notice').remove();
					notice.render().$el.prependTo(this.$('.modal-content'));
				}
			});
		},
	});

	wpaa.view.ModalAssign = wp.media.view.Modal.extend({
		template:wp.template('access-area-assign-modal'),
		events:		{
			'click .modal-close'	: 'close',
			'click #btn-ok'			: 'save',
			'change select'			: 'setUIState',
			'keyup'					: 'onkeyup',
		},
		onkeyup:function(e) {
			if ( e.originalEvent.keyCode === 27 ) {
				this.close();
			}
			return this;
		},
		render: function() {
			wp.media.view.Modal.prototype.render.apply( this, arguments );
			this.$okay = this.$('#btn-ok');
			this.setUIState();
			return this;
		},
		open:function(){
			wp.media.view.Modal.prototype.open.apply( this, arguments );
			this.$('.title').text( this.model.get('action') === 'grant' ? l10n.grantAccess : l10n.revokeAccess );
			this.$('select').val('')
			this.setUIState();
			return this;
		},
		setUIState:function(e){
			console.log(this.$('select').val());
			this.$okay.prop('disabled',this.$('select').val()==='');
			return this;
		},
		save:function() {
			var self = this;

			this.model.set('id',this.$('select').val());
			this.model.save( null, {
				success: function(e,response) {
					var action = self.model.get('action');
					_.each(response.user_id,function(user_id,i){
						if ( 'grant' === action ) {
							var $box = $('.assign-access-areas[data-wpaa-user="' + user_id + '"]'),
								html = wp.template('access-area-assigned-user')($.extend(response.access_area,{
									user_id:response.user_id,
								}));
							$box.prepend(html);
						} else if ( 'revoke' === action ) {
							$('[data-wpaa-access-area="'+response.access_area.id+'"][data-wpaa-user="'+user_id+'"]')
								.closest('.wpaa-access-area')
								.remove();
						}
					});

					self.close();
					// add message
				},
				error: function(e,response) {
					var model = new Backbone.Model(response.responseJSON),
						notice = new wp.accessAreas.view.Notice({
							model: model
						});
					model.set('dismissible',true);
					model.set('type','error');
					// print message
					//this.$('.notice').remove();
					notice.render().$el.prependTo(this.$('.modal-content'));
				}
			});
			return this;
		}
	});




	exports.accessAreas = wpaa;
})(jQuery,wp);

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

//# sourceMappingURL=data:application/json;charset=utf8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbImJhc2UuanMiLCJtYW5hZ2UuanMiLCJ1c2Vycy5qcyJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiQUFBQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FDblRBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FDZkE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EiLCJmaWxlIjoiYWNjZXNzLWFyZWFzLmpzIiwic291cmNlc0NvbnRlbnQiOlsiKGZ1bmN0aW9uKCQsZXhwb3J0cyl7XG5cdC8vIHdwQXBpU2V0dGluZ3MubWFwcGluZyA9IHtcblx0Ly8gXHRtb2RlbHM6IHtcblx0Ly8gXHRcdCdBY2Nlc3NBcmVhcycgOiAnQWNjZXNzQXJlYSdcblx0Ly8gXHR9LFxuXHQvLyBcdGNvbGxlY3Rpb25zOnt9XG5cdC8vIH07XG5cdC8vIHdwLmFwaS5pbml0KHtcblx0Ly8gXHR2ZXJzaW9uU3RyaW5nIDogJ3dwYWEvdjEvJyxcblx0Ly8gXHRhcGlSb290OiB3cEFwaVNldHRpbmdzLnJvb3QsXG5cdC8vIFx0c2NoZW1hOiBudWxsLFxuXHQvLyBcdH0pXG5cdC8vIFx0LmRvbmUoIGZ1bmN0aW9uKCkge1xuXHQvLyBcdFx0Y29uc29sZS5sb2codGhpcywgd3AuYXBpLmVuZHBvaW50cy5maW5kKHt2ZXJzaW9uU3RyaW5nOid3cGFhL3YxLyd9KSApO1xuXHQvLyBcdH0pO1xuXG5cdHZhciBsMTBuID0gYWNjZXNzX2FyZWFzX2FkbWluLmwxMG4sXG5cdFx0d3BhYSA9IHtcblx0XHRcdGwxMG46IGwxMG4sXG5cdFx0XHR2aWV3OiB7fSxcblx0XHRcdG1vZGVsOiB7fVxuXHRcdH07XG5cblx0d3BhYS5tb2RlbC5Bc3NpZ24gPSBCYWNrYm9uZS5Nb2RlbC5leHRlbmQoe1xuXHRcdGRlZmF1bHRzOntcblx0XHRcdGlkOiBudWxsLFxuXHRcdFx0YWN0aW9uOidncmFudCcsXG5cdFx0XHR1c2VyczpbXVxuXHRcdH0sXG5cdFx0c2F2ZTpmdW5jdGlvbigpIHtcblxuXHRcdFx0dGhpcy51cmwgPSB3cEFwaVNldHRpbmdzLnJvb3QgKyB3cEFwaVNldHRpbmdzLnZlcnNpb25TdHJpbmcgKyAnYWNjZXNzLWFyZWEvJyArIHRoaXMuZ2V0KCdpZCcpICsgJy8nICsgdGhpcy5nZXQoJ2FjdGlvbicpO1xuXHRcdFx0QmFja2JvbmUuTW9kZWwucHJvdG90eXBlLnNhdmUuYXBwbHkodGhpcyxhcmd1bWVudHMpO1xuXG5cdFx0fVxuXHR9KTtcblxuXHR3cGFhLnZpZXcuTm90aWNlID0gd3AubWVkaWEuVmlldy5leHRlbmQoe1xuXHRcdHRlbXBsYXRlOiB3cC50ZW1wbGF0ZSgnYWNjZXNzLWFyZWEtbm90aWNlJyksXG5cdFx0ZXZlbnRzOntcblx0XHRcdCdjbGljayAubm90aWNlLWRpc21pc3MnIDogZnVuY3Rpb24oKXtcblx0XHRcdFx0dGhpcy4kZWwucmVtb3ZlKCk7XG5cdFx0XHR9XG5cdFx0fVxuXHR9KTtcblxuXHR3cGFhLnZpZXcuUm93Vmlld01hbmFnZSA9IHdwLm1lZGlhLlZpZXcuZXh0ZW5kKHtcblx0XHR0ZW1wbGF0ZTp3cC50ZW1wbGF0ZSgnYWNjZXNzLWFyZWEtcm93JyksXG5cdFx0dGFnTmFtZTondHInLFxuXHRcdGV2ZW50czoge1xuXHRcdFx0J2NsaWNrIGEuZGVsZXRlJ1x0XHQ6ICdkZWxldGUnLFxuXHRcdFx0J2NsaWNrIGEuZWRpdCdcdFx0XHQ6ICdlZGl0Jyxcblx0XHR9LFxuXHRcdGluaXRpYWxpemU6IGZ1bmN0aW9uKCkge1xuXHRcdFx0d3AubWVkaWEuVmlldy5wcm90b3R5cGUuaW5pdGlhbGl6ZS5hcHBseSh0aGlzLGFyZ3VtZW50cyk7XG5cdFx0XHR0aGlzLmxpc3RlblRvKCB0aGlzLm1vZGVsLCAnYmVmb3JlX3N5bmMnLCB0aGlzLmRpc2FibGUgKTtcblx0XHRcdHRoaXMubGlzdGVuVG8oIHRoaXMubW9kZWwsICdiZWZvcmVfZGVzdHJveScsIHRoaXMuZGlzYWJsZSApO1xuXHRcdFx0dGhpcy5saXN0ZW5UbyggdGhpcy5tb2RlbCwgJ3N5bmMnLCB0aGlzLnJlbmRlciApO1xuXHRcdFx0dGhpcy5saXN0ZW5UbyggdGhpcy5tb2RlbCwgJ2Rlc3Ryb3knLCB0aGlzLnJlbW92ZSApO1xuXHRcdH0sXG5cdFx0cmVuZGVyOiBmdW5jdGlvbigpIHtcblx0XHRcdHZhciBzZWxmID0gdGhpcztcblx0XHRcdHdwLm1lZGlhLlZpZXcucHJvdG90eXBlLnJlbmRlci5hcHBseSh0aGlzLGFyZ3VtZW50cyk7XG5cdFx0XHR0aGlzLiRlbC5hdHRyKCdkYXRhLWlkJyx0aGlzLm1vZGVsLmdldCgnaWQnKSk7XG5cdFx0XHRyZXR1cm4gdGhpcztcblx0XHR9LFxuXHRcdGRpc2FibGU6ZnVuY3Rpb24oZSl7XG5cdFx0XHR0aGlzLiRlbC5hZGRDbGFzcygnZGlzYWJsZWQnKTtcblx0XHRcdHJldHVybiB0aGlzO1xuXHRcdH0sXG5cdFx0ZW5hYmxlOmZ1bmN0aW9uKGUpe1xuXHRcdFx0dGhpcy4kZWwucmVtb3ZlQ2xhc3MoJ2Rpc2FibGVkJyk7XG5cdFx0XHRyZXR1cm4gdGhpcztcblx0XHR9LFxuXHRcdGRlbGV0ZTpmdW5jdGlvbihlKXtcblx0XHRcdGUucHJldmVudERlZmF1bHQoKTtcblx0XHRcdHRoaXMubW9kZWwuZGVzdHJveSgpO1xuXHRcdFx0cmV0dXJuIHRoaXM7XG5cdFx0fSxcblx0XHRlZGl0OmZ1bmN0aW9uKGUpe1xuXHRcdFx0dGhpcy5jb250cm9sbGVyLmVkaXRFbnRyeSh0aGlzLm1vZGVsKTtcblx0XHRcdHJldHVybiB0aGlzO1xuXHRcdH0sXG5cdFx0ZGVsZXRlOmZ1bmN0aW9uKGUpe1xuXHRcdFx0dGhpcy5tb2RlbC5kZXN0cm95KCk7XG5cdFx0XHRyZXR1cm4gdGhpcztcblx0XHR9XG5cdH0pO1xuXHR3cGFhLnZpZXcuVGFibGVWaWV3TWFuYWdlID0gd3AubWVkaWEuVmlldy5leHRlbmQoe1xuXHRcdGV2ZW50czoge1xuXHRcdFx0J2NsaWNrIC5hZGQnIDogJ25ld0VudHJ5Jyxcblx0XHR9LFxuXHRcdGluaXRpYWxpemU6IGZ1bmN0aW9uKCkge1xuXHRcdFx0dmFyIHNlbGYgPSB0aGlzO1xuXHRcdFx0d3AubWVkaWEuVmlldy5wcm90b3R5cGUuaW5pdGlhbGl6ZS5hcHBseSh0aGlzLGFyZ3VtZW50cyk7XG5cdFx0XHR0aGlzLmNvbGxlY3Rpb24gPSBuZXcgd3AuYXBpLmNvbGxlY3Rpb25zLkFjY2Vzc0FyZWEoKTtcblx0XHRcdHRoaXMuY29sbGVjdGlvbi5mZXRjaCgpO1xuXHRcdFx0dGhpcy5jb2xsZWN0aW9uLm9uY2UoJ3N5bmMnLHRoaXMucmVuZGVyLHRoaXMpO1xuXHRcdFx0dGhpcy5saXN0ZW5Ubyh0aGlzLmNvbGxlY3Rpb24sJ2FkZCcsZnVuY3Rpb24obW9kZWwpe1xuXHRcdFx0XHRzZWxmLmFkZFJvdyhtb2RlbCk7XG5cdFx0XHRcdGNvbnNvbGUubG9nKGFyZ3VtZW50cyk7XG5cdFx0XHR9KTtcblx0XHRcdHRoaXMubGlzdGVuVG8odGhpcy5jb2xsZWN0aW9uLCdyZW1vdmUnLGZ1bmN0aW9uKG1vZGVsKXtcblx0XHRcdFx0c2VsZi5zZXRQbGFjZWhvbGRlcigpO1xuXHRcdFx0fSk7XG5cdFx0XHR0aGlzLnJlbmRlcigpO1xuXHRcdH0sXG5cdFx0bmV3RW50cnk6ZnVuY3Rpb24oZSkge1xuXHRcdFx0dmFyIG1vZGVsID0gbmV3IHdwLmFwaS5tb2RlbHMuQWNjZXNzQXJlYSh7XG5cdFx0XHRcdFx0SUQ6IG51bGwsXG5cdFx0XHRcdFx0dGl0bGU6ICcnLFxuXHRcdFx0XHR9KTtcblx0XHRcdHRoaXMuZWRpdEVudHJ5KG1vZGVsKTtcblx0XHRcdHJldHVybiB0aGlzO1xuXHRcdH0sXG5cdFx0ZWRpdEVudHJ5OmZ1bmN0aW9uKG1vZGVsKSB7XG5cdFx0XHR2YXIgc2VsZiA9IHRoaXM7XG5cdFx0XHQvLyBvcGVuIG1vZGFsXG5cdFx0XHR0aGlzLm1vZGFsLm1vZGVsID0gbW9kZWw7XG5cdFx0XHR0aGlzLm1vZGFsLnJlbmRlcigpLm9wZW4oe30pO1xuXHRcdFx0dGhpcy5saXN0ZW5Ubyhtb2RlbCwnc3luYycsZnVuY3Rpb24oZSl7XG5cdFx0XHRcdHNlbGYuc3RvcExpc3RlbmluZyhtb2RlbCwnc3luYycpO1xuXHRcdFx0XHRpZiAoICEgc2VsZi5jb2xsZWN0aW9uLndoZXJlKCB7IGlkOiBtb2RlbC5nZXQoJ2lkJykgfSApLmxlbmd0aCApIHtcblx0XHRcdFx0XHRzZWxmLmNvbGxlY3Rpb24uYWRkKG1vZGVsKTtcbi8vXHRcdFx0XHRcdFx0c2VsZi5hZGRSb3cobW9kZWwpO1xuXG5cdFx0XHRcdH1cblx0XHRcdH0pO1xuXHRcdFx0cmV0dXJuIHRoaXM7XG5cdFx0fSxcblx0XHRhZGRSb3c6ZnVuY3Rpb24oIG1vZGVsICl7XG5cdFx0XHR2YXIgc2VsZiA9IHRoaXMsXG5cdFx0XHRcdHZpZXcgPSBuZXcgd3BhYS52aWV3LlJvd1ZpZXdNYW5hZ2Uoe1xuXHRcdFx0XHRcdG1vZGVsOm1vZGVsLFxuXHRcdFx0XHRcdGNvbnRyb2xsZXI6dGhpcyxcblx0XHRcdFx0fSk7XG5cdFx0XHR0aGlzLiRlbC5hcHBlbmQoIHZpZXcucmVuZGVyKCkuZWwgKTtcblx0XHRcdHRoaXMubGlzdGVuVG8oIG1vZGVsLCdkZXN0cm95JyxmdW5jdGlvbihlKXtcblx0XHRcdFx0dmlldy4kZWwucmVtb3ZlKCk7XG5cdFx0XHRcdHNlbGYuc2V0UGxhY2Vob2xkZXIoKTtcblx0XHRcdH0pO1xuXHRcdFx0dGhpcy5zZXRQbGFjZWhvbGRlcigpO1xuXHRcdFx0cmV0dXJuIHRoaXM7XG5cdFx0fSxcblx0XHRoYXNSb3c6ZnVuY3Rpb24obW9kZWwpe1xuXHRcdFx0cmV0dXJuIHRoaXMuJCgnW2RhdGEtaWQ9XCInK21vZGVsLmdldCgnaWQnKSsnXCJdJykubGVuZ3RoID4gMDtcblx0XHR9LFxuXHRcdHJlbmRlcjogZnVuY3Rpb24oKSB7XG5cblx0XHRcdHZhciBzZWxmID0gdGhpcztcblxuXHRcdFx0aWYgKCAhIHRoaXMubW9kYWwgKSB7XG5cdFx0XHRcdHRoaXMubW9kYWwgPSBuZXcgd3BhYS52aWV3Lk1vZGFsTWFuYWdlKHtcblx0XHRcdFx0XHRjb250cm9sbGVyOlx0dGhpcyxcblx0XHRcdFx0fSApO1xuXHRcdFx0fVxuXG5cdFx0XHR3cC5tZWRpYS5WaWV3LnByb3RvdHlwZS5yZW5kZXIuYXBwbHkodGhpcyxhcmd1bWVudHMpO1xuXG5cdFx0XHR0aGlzLiRwbGFjZWhvbGRlciA9IHRoaXMuJCgnLnBsYWNlaG9sZGVyLXJvdycpO1xuXG5cdFx0XHR0aGlzLnNldFBsYWNlaG9sZGVyKCk7XG5cdFx0XHRyZXR1cm4gdGhpcztcblx0XHR9LFxuXHRcdHNldFBsYWNlaG9sZGVyOmZ1bmN0aW9uKCl7XG5cdFx0XHRpZiAoIHRoaXMuY29sbGVjdGlvbi5sZW5ndGggKSB7XG5cdFx0XHRcdHRoaXMuJHBsYWNlaG9sZGVyLnJlbW92ZSgpO1xuXHRcdFx0fSBlbHNlIGlmICggISB0aGlzLiRwbGFjZWhvbGRlci5wYXJlbnQoKS5sZW5ndGggKSB7XG5cdFx0XHRcdHRoaXMuJHBsYWNlaG9sZGVyLmFwcGVuZFRvKCB0aGlzLmVsICk7XG5cdFx0XHR9XG5cdFx0fVxuXHR9KTtcblxuXG5cdHdwYWEudmlldy5Nb2RhbE1hbmFnZSA9IHdwLm1lZGlhLnZpZXcuTW9kYWwuZXh0ZW5kKHtcblx0XHR0ZW1wbGF0ZTp3cC50ZW1wbGF0ZSgnYWNjZXNzLWFyZWEtbW9kYWwnKSxcblx0XHRldmVudHM6XHRcdHtcblx0XHRcdCdjbGljayAubW9kYWwtY2xvc2UnXHQ6ICdjbG9zZScsXG5cdFx0XHQnY2xpY2sgI2J0bi1vaydcdFx0XHQ6ICdzYXZlJyxcblx0XHRcdCdrZXl1cCAjdGl0bGUtaW5wdXQnXHQ6ICdzZXRVSVN0YXRlJyxcblx0XHRcdCdjaGFuZ2UgI3RpdGxlLWlucHV0J1x0OiAnc2V0VUlTdGF0ZScsXG5cdFx0XHQnYmx1ciAjdGl0bGUtaW5wdXQnXHRcdDogJ3NldFVJU3RhdGUnLFxuXHRcdFx0J2tleXVwJ1x0XHRcdFx0XHQ6ICdvbmtleXVwJyxcblx0XHR9LFxuXHRcdHNldFVJU3RhdGU6ZnVuY3Rpb24oZSl7XG5cdFx0XHR0aGlzLiRva2F5LnByb3AoJ2Rpc2FibGVkJywhdGhpcy4kdGl0bGUudmFsKCkpO1xuXHRcdFx0cmV0dXJuIHRoaXM7XG5cdFx0fSxcblx0XHRyZW5kZXI6IGZ1bmN0aW9uKCkge1xuXHRcdFx0d3AubWVkaWEudmlldy5Nb2RhbC5wcm90b3R5cGUucmVuZGVyLmFwcGx5KCB0aGlzLCBhcmd1bWVudHMgKTtcblx0XHRcdHRoaXMuJG9rYXkgPSB0aGlzLiQoJyNidG4tb2snKTtcblx0XHRcdHRoaXMuJHRpdGxlID0gdGhpcy4kKCcjdGl0bGUtaW5wdXQnKTtcblx0XHRcdHRoaXMuJHRpdGxlLnZhbCggdGhpcy5tb2RlbC5nZXQoICd0aXRsZScgKSApO1xuXHRcdFx0dGhpcy5zZXRVSVN0YXRlKCk7XG5cdFx0XHRyZXR1cm4gdGhpcztcblx0XHR9LFxuXHRcdG9ua2V5dXA6ZnVuY3Rpb24oZSkge1xuXHRcdFx0aWYgKCBlLm9yaWdpbmFsRXZlbnQua2V5Q29kZSA9PT0gMjcgKSB7XG5cdFx0XHRcdHRoaXMuY2xvc2UoKTtcblx0XHRcdH1cblx0XHR9LFxuXHRcdG9wZW46ZnVuY3Rpb24oKXtcblx0XHRcdHdwLm1lZGlhLnZpZXcuTW9kYWwucHJvdG90eXBlLm9wZW4uYXBwbHkoIHRoaXMsIGFyZ3VtZW50cyApO1xuXHRcdFx0dGhpcy4kKCcudGl0bGUnKS50ZXh0KCAhISB0aGlzLm1vZGVsLmdldCgnaWQnKSA/IGwxMG4uZWRpdEFjY2Vzc0FyZWEgOiBsMTBuLmNyZWF0ZUFjY2Vzc0FyZWEgKTtcblx0XHRcdHRoaXMuc2V0VUlTdGF0ZSgpO1xuXHRcdH0sXG5cdFx0c2F2ZTpmdW5jdGlvbigpIHtcblx0XHRcdHZhciBzZWxmID0gdGhpcztcblxuXHRcdFx0dGhpcy5tb2RlbC5zZXQoJ3RpdGxlJywgdGhpcy4kdGl0bGUudmFsKCkgKTtcblx0XHRcdHRoaXMubW9kZWwuc2F2ZSggbnVsbCwge1xuXHRcdFx0XHRzdWNjZXNzOiBmdW5jdGlvbihlKSB7XG5cdFx0XHRcdFx0c2VsZi5jbG9zZSgpO1xuXHRcdFx0XHRcdC8vIGFkZCBtZXNzYWdlXG5cdFx0XHRcdH0sXG5cdFx0XHRcdGVycm9yOiBmdW5jdGlvbihlLHJlc3BvbnNlKSB7XG5cdFx0XHRcdFx0dmFyIG1vZGVsID0gbmV3IEJhY2tib25lLk1vZGVsKHJlc3BvbnNlLnJlc3BvbnNlSlNPTiksXG5cdFx0XHRcdFx0XHRub3RpY2UgPSBuZXcgd3AuYWNjZXNzQXJlYXMudmlldy5Ob3RpY2Uoe1xuXHRcdFx0XHRcdFx0XHRtb2RlbDogbW9kZWxcblx0XHRcdFx0XHRcdH0pO1xuXHRcdFx0XHRcdG1vZGVsLnNldCgnZGlzbWlzc2libGUnLHRydWUpO1xuXHRcdFx0XHRcdG1vZGVsLnNldCgndHlwZScsJ2Vycm9yJyk7XG5cdFx0XHRcdFx0Ly8gcHJpbnQgbWVzc2FnZVxuXHRcdFx0XHRcdC8vdGhpcy4kKCcubm90aWNlJykucmVtb3ZlKCk7XG5cdFx0XHRcdFx0bm90aWNlLnJlbmRlcigpLiRlbC5wcmVwZW5kVG8odGhpcy4kKCcubW9kYWwtY29udGVudCcpKTtcblx0XHRcdFx0fVxuXHRcdFx0fSk7XG5cdFx0fSxcblx0fSk7XG5cblx0d3BhYS52aWV3Lk1vZGFsQXNzaWduID0gd3AubWVkaWEudmlldy5Nb2RhbC5leHRlbmQoe1xuXHRcdHRlbXBsYXRlOndwLnRlbXBsYXRlKCdhY2Nlc3MtYXJlYS1hc3NpZ24tbW9kYWwnKSxcblx0XHRldmVudHM6XHRcdHtcblx0XHRcdCdjbGljayAubW9kYWwtY2xvc2UnXHQ6ICdjbG9zZScsXG5cdFx0XHQnY2xpY2sgI2J0bi1vaydcdFx0XHQ6ICdzYXZlJyxcblx0XHRcdCdjaGFuZ2Ugc2VsZWN0J1x0XHRcdDogJ3NldFVJU3RhdGUnLFxuXHRcdFx0J2tleXVwJ1x0XHRcdFx0XHQ6ICdvbmtleXVwJyxcblx0XHR9LFxuXHRcdG9ua2V5dXA6ZnVuY3Rpb24oZSkge1xuXHRcdFx0aWYgKCBlLm9yaWdpbmFsRXZlbnQua2V5Q29kZSA9PT0gMjcgKSB7XG5cdFx0XHRcdHRoaXMuY2xvc2UoKTtcblx0XHRcdH1cblx0XHRcdHJldHVybiB0aGlzO1xuXHRcdH0sXG5cdFx0cmVuZGVyOiBmdW5jdGlvbigpIHtcblx0XHRcdHdwLm1lZGlhLnZpZXcuTW9kYWwucHJvdG90eXBlLnJlbmRlci5hcHBseSggdGhpcywgYXJndW1lbnRzICk7XG5cdFx0XHR0aGlzLiRva2F5ID0gdGhpcy4kKCcjYnRuLW9rJyk7XG5cdFx0XHR0aGlzLnNldFVJU3RhdGUoKTtcblx0XHRcdHJldHVybiB0aGlzO1xuXHRcdH0sXG5cdFx0b3BlbjpmdW5jdGlvbigpe1xuXHRcdFx0d3AubWVkaWEudmlldy5Nb2RhbC5wcm90b3R5cGUub3Blbi5hcHBseSggdGhpcywgYXJndW1lbnRzICk7XG5cdFx0XHR0aGlzLiQoJy50aXRsZScpLnRleHQoIHRoaXMubW9kZWwuZ2V0KCdhY3Rpb24nKSA9PT0gJ2dyYW50JyA/IGwxMG4uZ3JhbnRBY2Nlc3MgOiBsMTBuLnJldm9rZUFjY2VzcyApO1xuXHRcdFx0dGhpcy4kKCdzZWxlY3QnKS52YWwoJycpXG5cdFx0XHR0aGlzLnNldFVJU3RhdGUoKTtcblx0XHRcdHJldHVybiB0aGlzO1xuXHRcdH0sXG5cdFx0c2V0VUlTdGF0ZTpmdW5jdGlvbihlKXtcblx0XHRcdGNvbnNvbGUubG9nKHRoaXMuJCgnc2VsZWN0JykudmFsKCkpO1xuXHRcdFx0dGhpcy4kb2theS5wcm9wKCdkaXNhYmxlZCcsdGhpcy4kKCdzZWxlY3QnKS52YWwoKT09PScnKTtcblx0XHRcdHJldHVybiB0aGlzO1xuXHRcdH0sXG5cdFx0c2F2ZTpmdW5jdGlvbigpIHtcblx0XHRcdHZhciBzZWxmID0gdGhpcztcblxuXHRcdFx0dGhpcy5tb2RlbC5zZXQoJ2lkJyx0aGlzLiQoJ3NlbGVjdCcpLnZhbCgpKTtcblx0XHRcdHRoaXMubW9kZWwuc2F2ZSggbnVsbCwge1xuXHRcdFx0XHRzdWNjZXNzOiBmdW5jdGlvbihlLHJlc3BvbnNlKSB7XG5cdFx0XHRcdFx0dmFyIGFjdGlvbiA9IHNlbGYubW9kZWwuZ2V0KCdhY3Rpb24nKTtcblx0XHRcdFx0XHRfLmVhY2gocmVzcG9uc2UudXNlcl9pZCxmdW5jdGlvbih1c2VyX2lkLGkpe1xuXHRcdFx0XHRcdFx0aWYgKCAnZ3JhbnQnID09PSBhY3Rpb24gKSB7XG5cdFx0XHRcdFx0XHRcdHZhciAkYm94ID0gJCgnLmFzc2lnbi1hY2Nlc3MtYXJlYXNbZGF0YS13cGFhLXVzZXI9XCInICsgdXNlcl9pZCArICdcIl0nKSxcblx0XHRcdFx0XHRcdFx0XHRodG1sID0gd3AudGVtcGxhdGUoJ2FjY2Vzcy1hcmVhLWFzc2lnbmVkLXVzZXInKSgkLmV4dGVuZChyZXNwb25zZS5hY2Nlc3NfYXJlYSx7XG5cdFx0XHRcdFx0XHRcdFx0XHR1c2VyX2lkOnJlc3BvbnNlLnVzZXJfaWQsXG5cdFx0XHRcdFx0XHRcdFx0fSkpO1xuXHRcdFx0XHRcdFx0XHQkYm94LnByZXBlbmQoaHRtbCk7XG5cdFx0XHRcdFx0XHR9IGVsc2UgaWYgKCAncmV2b2tlJyA9PT0gYWN0aW9uICkge1xuXHRcdFx0XHRcdFx0XHQkKCdbZGF0YS13cGFhLWFjY2Vzcy1hcmVhPVwiJytyZXNwb25zZS5hY2Nlc3NfYXJlYS5pZCsnXCJdW2RhdGEtd3BhYS11c2VyPVwiJyt1c2VyX2lkKydcIl0nKVxuXHRcdFx0XHRcdFx0XHRcdC5jbG9zZXN0KCcud3BhYS1hY2Nlc3MtYXJlYScpXG5cdFx0XHRcdFx0XHRcdFx0LnJlbW92ZSgpO1xuXHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdH0pO1xuXG5cdFx0XHRcdFx0c2VsZi5jbG9zZSgpO1xuXHRcdFx0XHRcdC8vIGFkZCBtZXNzYWdlXG5cdFx0XHRcdH0sXG5cdFx0XHRcdGVycm9yOiBmdW5jdGlvbihlLHJlc3BvbnNlKSB7XG5cdFx0XHRcdFx0dmFyIG1vZGVsID0gbmV3IEJhY2tib25lLk1vZGVsKHJlc3BvbnNlLnJlc3BvbnNlSlNPTiksXG5cdFx0XHRcdFx0XHRub3RpY2UgPSBuZXcgd3AuYWNjZXNzQXJlYXMudmlldy5Ob3RpY2Uoe1xuXHRcdFx0XHRcdFx0XHRtb2RlbDogbW9kZWxcblx0XHRcdFx0XHRcdH0pO1xuXHRcdFx0XHRcdG1vZGVsLnNldCgnZGlzbWlzc2libGUnLHRydWUpO1xuXHRcdFx0XHRcdG1vZGVsLnNldCgndHlwZScsJ2Vycm9yJyk7XG5cdFx0XHRcdFx0Ly8gcHJpbnQgbWVzc2FnZVxuXHRcdFx0XHRcdC8vdGhpcy4kKCcubm90aWNlJykucmVtb3ZlKCk7XG5cdFx0XHRcdFx0bm90aWNlLnJlbmRlcigpLiRlbC5wcmVwZW5kVG8odGhpcy4kKCcubW9kYWwtY29udGVudCcpKTtcblx0XHRcdFx0fVxuXHRcdFx0fSk7XG5cdFx0XHRyZXR1cm4gdGhpcztcblx0XHR9XG5cdH0pO1xuXG5cblxuXG5cdGV4cG9ydHMuYWNjZXNzQXJlYXMgPSB3cGFhO1xufSkoalF1ZXJ5LHdwKTtcbiIsIihmdW5jdGlvbigkLGV4cG9ydHMpe1xuXG5cdHZhciB3cGFhID0gZXhwb3J0cy5hY2Nlc3NBcmVhcztcblxuXG5cdCQoZG9jdW1lbnQpLnJlYWR5KGZ1bmN0aW9uKCl7XG5cdFx0dmFyICRlbCA9ICQoJy5hY2Nlc3MtYXJlYXMtbGlzdC10YWJsZScpO1xuXHRcdGlmICggJGVsLmxlbmd0aCApIHtcblx0XHRcdG5ldyB3cGFhLnZpZXcuVGFibGVWaWV3TWFuYWdlKHtcblx0XHRcdFx0ZWw6ICRlbC5nZXQoMClcblx0XHRcdH0pO1xuXHRcdH1cblx0fSk7XG5cbn0pKGpRdWVyeSx3cCk7XG4iLCIoZnVuY3Rpb24oJCxleHBvcnRzKXtcblxuXHR2YXIgd3BhYSA9IGV4cG9ydHMuYWNjZXNzQXJlYXMsXG5cdFx0bW9kZWwgPSBuZXcgd3BhYS5tb2RlbC5Bc3NpZ24oKSxcblx0XHRtb2RhbCA9IG5ldyB3cGFhLnZpZXcuTW9kYWxBc3NpZ24oe21vZGVsOm1vZGVsLHByb3BhZ2F0ZTpmYWxzZX0pO1xuXG5cblxuXHQkKGRvY3VtZW50KVxuXHRcdC5vbignY2xpY2snLCdib2R5LnVzZXJzLXBocCAuYnV0dG9uLmFjdGlvbicsZnVuY3Rpb24oZSl7XG5cdFx0XHR2YXIgYWN0aW9uID0gJCh0aGlzKS5wcmV2KCdzZWxlY3QnKS52YWwoKSxcblx0XHRcdFx0dXNlcnMgPSBbXTtcblx0XHRcdGUucHJldmVudERlZmF1bHQoKTtcblx0XHRcdGlmICggWyd3cGFhLWdyYW50Jywnd3BhYS1yZXZva2UnXS5pbmRleE9mKGFjdGlvbikgIT09IC0xICkge1xuXHRcdFx0XHQkKCdbbmFtZT1cInVzZXJzW11cIl06Y2hlY2tlZCcpLmVhY2goZnVuY3Rpb24oaSxlbCl7XG5cdFx0XHRcdFx0dXNlcnMucHVzaCgkKHRoaXMpLnZhbCgpKTtcblx0XHRcdFx0fSk7XG5cblx0XHRcdFx0bW9kZWwuc2V0KCdhY3Rpb24nLCBhY3Rpb24ucmVwbGFjZSgnd3BhYS0nLCcnKSk7XG5cdFx0XHRcdG1vZGVsLnNldCgndXNlcl9pZCcsIHVzZXJzICk7XG5cdFx0XHRcdG1vZGFsLm9wZW4oKTtcblx0XHRcdH1cblx0XHR9KVxuXHRcdC5vbignY2xpY2snLCdbZGF0YS13cGFhLWFjdGlvbl0nLGZ1bmN0aW9uKGUpe1xuXG5cdFx0XHRlLnByZXZlbnREZWZhdWx0KCk7XG5cblx0XHRcdHZhciBhY3Rpb24gPSAkKHRoaXMpLmF0dHIoJ2RhdGEtd3BhYS1hY3Rpb24nKSxcblx0XHRcdFx0c2VsZiA9IHRoaXM7XG5cblx0XHRcdG1vZGVsLnNldCgnYWN0aW9uJywgYWN0aW9uICk7XG5cdFx0XHRtb2RlbC5zZXQoJ3VzZXJfaWQnLCAkKHRoaXMpLmF0dHIoJ2RhdGEtd3BhYS11c2VyJykgKTtcblxuXHRcdFx0aWYgKCBhY3Rpb24gPT09ICdyZXZva2UnICkge1xuXHRcdFx0XHRtb2RlbC5zZXQoICdpZCcsICQodGhpcykuYXR0cignZGF0YS13cGFhLWFjY2Vzcy1hcmVhJykgKTtcblx0XHRcdFx0bW9kZWwuc2F2ZShudWxsLHtcblx0XHRcdFx0XHRzdWNjZXNzOmZ1bmN0aW9uKCl7XG5cdFx0XHRcdFx0XHQkKHNlbGYpLmNsb3Nlc3QoJy53cGFhLWFjY2Vzcy1hcmVhJykucmVtb3ZlKCk7XG5cdFx0XHRcdFx0fSxcblx0XHRcdFx0XHRlcnJvcjpmdW5jdGlvbihlLHJlc3BvbnNlKSB7XG5cdFx0XHRcdFx0XHR2YXIgbW9kZWwgPSBuZXcgQmFja2JvbmUuTW9kZWwocmVzcG9uc2UucmVzcG9uc2VKU09OKSxcblx0XHRcdFx0XHRcdFx0bm90aWNlID0gbmV3IHdwLmFjY2Vzc0FyZWFzLnZpZXcuTm90aWNlKHtcblx0XHRcdFx0XHRcdFx0XHRtb2RlbDogbW9kZWxcblx0XHRcdFx0XHRcdFx0fSk7XG5cdFx0XHRcdFx0XHRtb2RlbC5zZXQoJ2Rpc21pc3NpYmxlJyx0cnVlKTtcblx0XHRcdFx0XHRcdG1vZGVsLnNldCgndHlwZScsJ2Vycm9yJyk7XG5cdFx0XHRcdFx0XHQvLyBwcmludCBtZXNzYWdlXG5cdFx0XHRcdFx0XHQvL3RoaXMuJCgnLm5vdGljZScpLnJlbW92ZSgpO1xuXHRcdFx0XHRcdFx0bm90aWNlLnJlbmRlcigpLiRlbC5pbnNlcnRmdGVyKCQoJy53cC1oZWFkZXItZW5kJykpO1xuXHRcdFx0XHRcdH1cblx0XHRcdFx0fSk7XG5cdFx0XHR9IGVsc2UgaWYgKCBhY3Rpb24gPT09ICdncmFudCcgKSB7XG5cdFx0XHRcdG1vZGFsLm9wZW4oKTtcblx0XHRcdH1cblx0XHR9KVxuXHRcdC8vIC5vbignY2xpY2snLCdbZGF0YS13cGFhLWFjdGlvbl0nLGZ1bmN0aW9uKGUpe1xuXHRcdC8vIFx0ZS5wcmV2ZW50RGVmYXVsdCgpO1xuICAgICAgICAvL1xuXHRcdC8vIH0pXG5cdFx0O1xuXG5cbn0pKGpRdWVyeSx3cCk7XG4iXX0=

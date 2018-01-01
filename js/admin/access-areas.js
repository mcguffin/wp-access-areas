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
			this.$blog_id = this.$('[name="blog_id"]');
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
			this.model.set('blog_id', this.$blog_id.val() );
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
				$(this).closest('.wpaa-access-area').addClass('idle');
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

//# sourceMappingURL=data:application/json;charset=utf8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbImJhc2UuanMiLCJtYW5hZ2UuanMiLCJ1c2Vycy5qcyJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiQUFBQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQ3JUQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQ2ZBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EiLCJmaWxlIjoiYWNjZXNzLWFyZWFzLmpzIiwic291cmNlc0NvbnRlbnQiOlsiKGZ1bmN0aW9uKCQsZXhwb3J0cyl7XG5cdC8vIHdwQXBpU2V0dGluZ3MubWFwcGluZyA9IHtcblx0Ly8gXHRtb2RlbHM6IHtcblx0Ly8gXHRcdCdBY2Nlc3NBcmVhcycgOiAnQWNjZXNzQXJlYSdcblx0Ly8gXHR9LFxuXHQvLyBcdGNvbGxlY3Rpb25zOnt9XG5cdC8vIH07XG5cdC8vIHdwLmFwaS5pbml0KHtcblx0Ly8gXHR2ZXJzaW9uU3RyaW5nIDogJ3dwYWEvdjEvJyxcblx0Ly8gXHRhcGlSb290OiB3cEFwaVNldHRpbmdzLnJvb3QsXG5cdC8vIFx0c2NoZW1hOiBudWxsLFxuXHQvLyBcdH0pXG5cdC8vIFx0LmRvbmUoIGZ1bmN0aW9uKCkge1xuXHQvLyBcdFx0Y29uc29sZS5sb2codGhpcywgd3AuYXBpLmVuZHBvaW50cy5maW5kKHt2ZXJzaW9uU3RyaW5nOid3cGFhL3YxLyd9KSApO1xuXHQvLyBcdH0pO1xuXG5cdHZhciBsMTBuID0gYWNjZXNzX2FyZWFzX2FkbWluLmwxMG4sXG5cdFx0d3BhYSA9IHtcblx0XHRcdGwxMG46IGwxMG4sXG5cdFx0XHR2aWV3OiB7fSxcblx0XHRcdG1vZGVsOiB7fVxuXHRcdH07XG5cblx0d3BhYS5tb2RlbC5Bc3NpZ24gPSBCYWNrYm9uZS5Nb2RlbC5leHRlbmQoe1xuXHRcdGRlZmF1bHRzOntcblx0XHRcdGlkOiBudWxsLFxuXHRcdFx0YWN0aW9uOidncmFudCcsXG5cdFx0XHR1c2VyczpbXVxuXHRcdH0sXG5cdFx0c2F2ZTpmdW5jdGlvbigpIHtcblxuXHRcdFx0dGhpcy51cmwgPSB3cEFwaVNldHRpbmdzLnJvb3QgKyB3cEFwaVNldHRpbmdzLnZlcnNpb25TdHJpbmcgKyAnYWNjZXNzLWFyZWEvJyArIHRoaXMuZ2V0KCdpZCcpICsgJy8nICsgdGhpcy5nZXQoJ2FjdGlvbicpO1xuXHRcdFx0QmFja2JvbmUuTW9kZWwucHJvdG90eXBlLnNhdmUuYXBwbHkodGhpcyxhcmd1bWVudHMpO1xuXG5cdFx0fVxuXHR9KTtcblxuXHR3cGFhLnZpZXcuTm90aWNlID0gd3AubWVkaWEuVmlldy5leHRlbmQoe1xuXHRcdHRlbXBsYXRlOiB3cC50ZW1wbGF0ZSgnYWNjZXNzLWFyZWEtbm90aWNlJyksXG5cdFx0ZXZlbnRzOntcblx0XHRcdCdjbGljayAubm90aWNlLWRpc21pc3MnIDogZnVuY3Rpb24oKXtcblx0XHRcdFx0dGhpcy4kZWwucmVtb3ZlKCk7XG5cdFx0XHR9XG5cdFx0fVxuXHR9KTtcblxuXHR3cGFhLnZpZXcuUm93Vmlld01hbmFnZSA9IHdwLm1lZGlhLlZpZXcuZXh0ZW5kKHtcblx0XHR0ZW1wbGF0ZTp3cC50ZW1wbGF0ZSgnYWNjZXNzLWFyZWEtcm93JyksXG5cdFx0dGFnTmFtZTondHInLFxuXHRcdGV2ZW50czoge1xuXHRcdFx0J2NsaWNrIGEuZGVsZXRlJ1x0XHQ6ICdkZWxldGUnLFxuXHRcdFx0J2NsaWNrIGEuZWRpdCdcdFx0XHQ6ICdlZGl0Jyxcblx0XHR9LFxuXHRcdGluaXRpYWxpemU6IGZ1bmN0aW9uKCkge1xuXHRcdFx0d3AubWVkaWEuVmlldy5wcm90b3R5cGUuaW5pdGlhbGl6ZS5hcHBseSh0aGlzLGFyZ3VtZW50cyk7XG5cdFx0XHR0aGlzLmxpc3RlblRvKCB0aGlzLm1vZGVsLCAnYmVmb3JlX3N5bmMnLCB0aGlzLmRpc2FibGUgKTtcblx0XHRcdHRoaXMubGlzdGVuVG8oIHRoaXMubW9kZWwsICdiZWZvcmVfZGVzdHJveScsIHRoaXMuZGlzYWJsZSApO1xuXHRcdFx0dGhpcy5saXN0ZW5UbyggdGhpcy5tb2RlbCwgJ3N5bmMnLCB0aGlzLnJlbmRlciApO1xuXHRcdFx0dGhpcy5saXN0ZW5UbyggdGhpcy5tb2RlbCwgJ2Rlc3Ryb3knLCB0aGlzLnJlbW92ZSApO1xuXHRcdH0sXG5cdFx0cmVuZGVyOiBmdW5jdGlvbigpIHtcblx0XHRcdHZhciBzZWxmID0gdGhpcztcblx0XHRcdHdwLm1lZGlhLlZpZXcucHJvdG90eXBlLnJlbmRlci5hcHBseSh0aGlzLGFyZ3VtZW50cyk7XG5cdFx0XHR0aGlzLiRlbC5hdHRyKCdkYXRhLWlkJyx0aGlzLm1vZGVsLmdldCgnaWQnKSk7XG5cdFx0XHRyZXR1cm4gdGhpcztcblx0XHR9LFxuXHRcdGRpc2FibGU6ZnVuY3Rpb24oZSl7XG5cdFx0XHR0aGlzLiRlbC5hZGRDbGFzcygnZGlzYWJsZWQnKTtcblx0XHRcdHJldHVybiB0aGlzO1xuXHRcdH0sXG5cdFx0ZW5hYmxlOmZ1bmN0aW9uKGUpe1xuXHRcdFx0dGhpcy4kZWwucmVtb3ZlQ2xhc3MoJ2Rpc2FibGVkJyk7XG5cdFx0XHRyZXR1cm4gdGhpcztcblx0XHR9LFxuXHRcdGRlbGV0ZTpmdW5jdGlvbihlKXtcblx0XHRcdGUucHJldmVudERlZmF1bHQoKTtcblx0XHRcdHRoaXMubW9kZWwuZGVzdHJveSgpO1xuXHRcdFx0cmV0dXJuIHRoaXM7XG5cdFx0fSxcblx0XHRlZGl0OmZ1bmN0aW9uKGUpe1xuXHRcdFx0dGhpcy5jb250cm9sbGVyLmVkaXRFbnRyeSh0aGlzLm1vZGVsKTtcblx0XHRcdHJldHVybiB0aGlzO1xuXHRcdH0sXG5cdFx0ZGVsZXRlOmZ1bmN0aW9uKGUpe1xuXHRcdFx0dGhpcy5tb2RlbC5kZXN0cm95KCk7XG5cdFx0XHRyZXR1cm4gdGhpcztcblx0XHR9XG5cdH0pO1xuXHR3cGFhLnZpZXcuVGFibGVWaWV3TWFuYWdlID0gd3AubWVkaWEuVmlldy5leHRlbmQoe1xuXHRcdGV2ZW50czoge1xuXHRcdFx0J2NsaWNrIC5hZGQnIDogJ25ld0VudHJ5Jyxcblx0XHR9LFxuXHRcdGluaXRpYWxpemU6IGZ1bmN0aW9uKCkge1xuXHRcdFx0dmFyIHNlbGYgPSB0aGlzO1xuXHRcdFx0d3AubWVkaWEuVmlldy5wcm90b3R5cGUuaW5pdGlhbGl6ZS5hcHBseSh0aGlzLGFyZ3VtZW50cyk7XG5cdFx0XHR0aGlzLmNvbGxlY3Rpb24gPSBuZXcgd3AuYXBpLmNvbGxlY3Rpb25zLkFjY2Vzc0FyZWEoKTtcblx0XHRcdHRoaXMuY29sbGVjdGlvbi5mZXRjaCgpO1xuXHRcdFx0dGhpcy5jb2xsZWN0aW9uLm9uY2UoJ3N5bmMnLHRoaXMucmVuZGVyLHRoaXMpO1xuXHRcdFx0dGhpcy5saXN0ZW5Ubyh0aGlzLmNvbGxlY3Rpb24sJ2FkZCcsZnVuY3Rpb24obW9kZWwpe1xuXHRcdFx0XHRzZWxmLmFkZFJvdyhtb2RlbCk7XG5cdFx0XHRcdGNvbnNvbGUubG9nKGFyZ3VtZW50cyk7XG5cdFx0XHR9KTtcblx0XHRcdHRoaXMubGlzdGVuVG8odGhpcy5jb2xsZWN0aW9uLCdyZW1vdmUnLGZ1bmN0aW9uKG1vZGVsKXtcblx0XHRcdFx0c2VsZi5zZXRQbGFjZWhvbGRlcigpO1xuXHRcdFx0fSk7XG5cdFx0XHR0aGlzLnJlbmRlcigpO1xuXHRcdH0sXG5cdFx0bmV3RW50cnk6ZnVuY3Rpb24oZSkge1xuXHRcdFx0dmFyIG1vZGVsID0gbmV3IHdwLmFwaS5tb2RlbHMuQWNjZXNzQXJlYSh7XG5cdFx0XHRcdFx0SUQ6IG51bGwsXG5cdFx0XHRcdFx0dGl0bGU6ICcnLFxuXHRcdFx0XHR9KTtcblx0XHRcdHRoaXMuZWRpdEVudHJ5KG1vZGVsKTtcblx0XHRcdHJldHVybiB0aGlzO1xuXHRcdH0sXG5cdFx0ZWRpdEVudHJ5OmZ1bmN0aW9uKG1vZGVsKSB7XG5cdFx0XHR2YXIgc2VsZiA9IHRoaXM7XG5cdFx0XHQvLyBvcGVuIG1vZGFsXG5cdFx0XHR0aGlzLm1vZGFsLm1vZGVsID0gbW9kZWw7XG5cdFx0XHR0aGlzLm1vZGFsLnJlbmRlcigpLm9wZW4oe30pO1xuXHRcdFx0dGhpcy5saXN0ZW5Ubyhtb2RlbCwnc3luYycsZnVuY3Rpb24oZSl7XG5cdFx0XHRcdHNlbGYuc3RvcExpc3RlbmluZyhtb2RlbCwnc3luYycpO1xuXHRcdFx0XHRpZiAoICEgc2VsZi5jb2xsZWN0aW9uLndoZXJlKCB7IGlkOiBtb2RlbC5nZXQoJ2lkJykgfSApLmxlbmd0aCApIHtcblx0XHRcdFx0XHRzZWxmLmNvbGxlY3Rpb24uYWRkKG1vZGVsKTtcbi8vXHRcdFx0XHRcdFx0c2VsZi5hZGRSb3cobW9kZWwpO1xuXG5cdFx0XHRcdH1cblx0XHRcdH0pO1xuXHRcdFx0cmV0dXJuIHRoaXM7XG5cdFx0fSxcblx0XHRhZGRSb3c6ZnVuY3Rpb24oIG1vZGVsICl7XG5cdFx0XHR2YXIgc2VsZiA9IHRoaXMsXG5cdFx0XHRcdHZpZXcgPSBuZXcgd3BhYS52aWV3LlJvd1ZpZXdNYW5hZ2Uoe1xuXHRcdFx0XHRcdG1vZGVsOm1vZGVsLFxuXHRcdFx0XHRcdGNvbnRyb2xsZXI6dGhpcyxcblx0XHRcdFx0fSk7XG5cdFx0XHR0aGlzLiRlbC5hcHBlbmQoIHZpZXcucmVuZGVyKCkuZWwgKTtcblx0XHRcdHRoaXMubGlzdGVuVG8oIG1vZGVsLCdkZXN0cm95JyxmdW5jdGlvbihlKXtcblx0XHRcdFx0dmlldy4kZWwucmVtb3ZlKCk7XG5cdFx0XHRcdHNlbGYuc2V0UGxhY2Vob2xkZXIoKTtcblx0XHRcdH0pO1xuXHRcdFx0dGhpcy5zZXRQbGFjZWhvbGRlcigpO1xuXHRcdFx0cmV0dXJuIHRoaXM7XG5cdFx0fSxcblx0XHRoYXNSb3c6ZnVuY3Rpb24obW9kZWwpe1xuXHRcdFx0cmV0dXJuIHRoaXMuJCgnW2RhdGEtaWQ9XCInK21vZGVsLmdldCgnaWQnKSsnXCJdJykubGVuZ3RoID4gMDtcblx0XHR9LFxuXHRcdHJlbmRlcjogZnVuY3Rpb24oKSB7XG5cblx0XHRcdHZhciBzZWxmID0gdGhpcztcblxuXHRcdFx0aWYgKCAhIHRoaXMubW9kYWwgKSB7XG5cdFx0XHRcdHRoaXMubW9kYWwgPSBuZXcgd3BhYS52aWV3Lk1vZGFsTWFuYWdlKHtcblx0XHRcdFx0XHRjb250cm9sbGVyOlx0dGhpcyxcblx0XHRcdFx0fSApO1xuXHRcdFx0fVxuXG5cdFx0XHR3cC5tZWRpYS5WaWV3LnByb3RvdHlwZS5yZW5kZXIuYXBwbHkodGhpcyxhcmd1bWVudHMpO1xuXG5cdFx0XHR0aGlzLiRwbGFjZWhvbGRlciA9IHRoaXMuJCgnLnBsYWNlaG9sZGVyLXJvdycpO1xuXG5cdFx0XHR0aGlzLnNldFBsYWNlaG9sZGVyKCk7XG5cdFx0XHRyZXR1cm4gdGhpcztcblx0XHR9LFxuXHRcdHNldFBsYWNlaG9sZGVyOmZ1bmN0aW9uKCl7XG5cdFx0XHRpZiAoIHRoaXMuY29sbGVjdGlvbi5sZW5ndGggKSB7XG5cdFx0XHRcdHRoaXMuJHBsYWNlaG9sZGVyLnJlbW92ZSgpO1xuXHRcdFx0fSBlbHNlIGlmICggISB0aGlzLiRwbGFjZWhvbGRlci5wYXJlbnQoKS5sZW5ndGggKSB7XG5cdFx0XHRcdHRoaXMuJHBsYWNlaG9sZGVyLmFwcGVuZFRvKCB0aGlzLmVsICk7XG5cdFx0XHR9XG5cdFx0fVxuXHR9KTtcblxuXG5cdHdwYWEudmlldy5Nb2RhbE1hbmFnZSA9IHdwLm1lZGlhLnZpZXcuTW9kYWwuZXh0ZW5kKHtcblx0XHR0ZW1wbGF0ZTp3cC50ZW1wbGF0ZSgnYWNjZXNzLWFyZWEtbW9kYWwnKSxcblx0XHRldmVudHM6XHRcdHtcblx0XHRcdCdjbGljayAubW9kYWwtY2xvc2UnXHQ6ICdjbG9zZScsXG5cdFx0XHQnY2xpY2sgI2J0bi1vaydcdFx0XHQ6ICdzYXZlJyxcblx0XHRcdCdrZXl1cCAjdGl0bGUtaW5wdXQnXHQ6ICdzZXRVSVN0YXRlJyxcblx0XHRcdCdjaGFuZ2UgI3RpdGxlLWlucHV0J1x0OiAnc2V0VUlTdGF0ZScsXG5cdFx0XHQnYmx1ciAjdGl0bGUtaW5wdXQnXHRcdDogJ3NldFVJU3RhdGUnLFxuXHRcdFx0J2tleXVwJ1x0XHRcdFx0XHQ6ICdvbmtleXVwJyxcblx0XHR9LFxuXHRcdHNldFVJU3RhdGU6ZnVuY3Rpb24oZSl7XG5cdFx0XHR0aGlzLiRva2F5LnByb3AoJ2Rpc2FibGVkJywhdGhpcy4kdGl0bGUudmFsKCkpO1xuXHRcdFx0cmV0dXJuIHRoaXM7XG5cdFx0fSxcblx0XHRyZW5kZXI6IGZ1bmN0aW9uKCkge1xuXHRcdFx0d3AubWVkaWEudmlldy5Nb2RhbC5wcm90b3R5cGUucmVuZGVyLmFwcGx5KCB0aGlzLCBhcmd1bWVudHMgKTtcblx0XHRcdHRoaXMuJG9rYXkgPSB0aGlzLiQoJyNidG4tb2snKTtcblx0XHRcdHRoaXMuJHRpdGxlID0gdGhpcy4kKCcjdGl0bGUtaW5wdXQnKTtcblx0XHRcdHRoaXMuJGJsb2dfaWQgPSB0aGlzLiQoJ1tuYW1lPVwiYmxvZ19pZFwiXScpO1xuXHRcdFx0dGhpcy4kdGl0bGUudmFsKCB0aGlzLm1vZGVsLmdldCggJ3RpdGxlJyApICk7XG5cdFx0XHR0aGlzLnNldFVJU3RhdGUoKTtcblx0XHRcdHJldHVybiB0aGlzO1xuXHRcdH0sXG5cdFx0b25rZXl1cDpmdW5jdGlvbihlKSB7XG5cdFx0XHRpZiAoIGUub3JpZ2luYWxFdmVudC5rZXlDb2RlID09PSAyNyApIHtcblx0XHRcdFx0dGhpcy5jbG9zZSgpO1xuXHRcdFx0fVxuXHRcdH0sXG5cdFx0b3BlbjpmdW5jdGlvbigpe1xuXHRcdFx0d3AubWVkaWEudmlldy5Nb2RhbC5wcm90b3R5cGUub3Blbi5hcHBseSggdGhpcywgYXJndW1lbnRzICk7XG5cdFx0XHR0aGlzLiQoJy50aXRsZScpLnRleHQoICEhIHRoaXMubW9kZWwuZ2V0KCdpZCcpID8gbDEwbi5lZGl0QWNjZXNzQXJlYSA6IGwxMG4uY3JlYXRlQWNjZXNzQXJlYSApO1xuXHRcdFx0dGhpcy5zZXRVSVN0YXRlKCk7XG5cdFx0fSxcblx0XHRzYXZlOmZ1bmN0aW9uKCkge1xuXHRcdFx0dmFyIHNlbGYgPSB0aGlzO1xuXG5cdFx0XHR0aGlzLm1vZGVsLnNldCgndGl0bGUnLCB0aGlzLiR0aXRsZS52YWwoKSApO1xuXHRcdFx0dGhpcy5tb2RlbC5zZXQoJ2Jsb2dfaWQnLCB0aGlzLiRibG9nX2lkLnZhbCgpICk7XG5cdFx0XHR0aGlzLm1vZGVsLnNhdmUoIG51bGwsIHtcblx0XHRcdFx0c3VjY2VzczogZnVuY3Rpb24oZSkge1xuXHRcdFx0XHRcdHNlbGYuY2xvc2UoKTtcblx0XHRcdFx0XHQvLyBhZGQgbWVzc2FnZVxuXHRcdFx0XHR9LFxuXHRcdFx0XHRlcnJvcjogZnVuY3Rpb24oZSxyZXNwb25zZSkge1xuXHRcdFx0XHRcdHZhciBtb2RlbCA9IG5ldyBCYWNrYm9uZS5Nb2RlbChyZXNwb25zZS5yZXNwb25zZUpTT04pLFxuXHRcdFx0XHRcdFx0bm90aWNlID0gbmV3IHdwLmFjY2Vzc0FyZWFzLnZpZXcuTm90aWNlKHtcblx0XHRcdFx0XHRcdFx0bW9kZWw6IG1vZGVsXG5cdFx0XHRcdFx0XHR9KTtcblx0XHRcdFx0XHRtb2RlbC5zZXQoJ2Rpc21pc3NpYmxlJyx0cnVlKTtcblx0XHRcdFx0XHRtb2RlbC5zZXQoJ3R5cGUnLCdlcnJvcicpO1xuXHRcdFx0XHRcdC8vIHByaW50IG1lc3NhZ2Vcblx0XHRcdFx0XHQvL3RoaXMuJCgnLm5vdGljZScpLnJlbW92ZSgpO1xuXHRcdFx0XHRcdG5vdGljZS5yZW5kZXIoKS4kZWwucHJlcGVuZFRvKHRoaXMuJCgnLm1vZGFsLWNvbnRlbnQnKSk7XG5cdFx0XHRcdH1cblx0XHRcdH0pO1xuXHRcdH0sXG5cdH0pO1xuXG5cdHdwYWEudmlldy5Nb2RhbEFzc2lnbiA9IHdwLm1lZGlhLnZpZXcuTW9kYWwuZXh0ZW5kKHtcblx0XHR0ZW1wbGF0ZTp3cC50ZW1wbGF0ZSgnYWNjZXNzLWFyZWEtYXNzaWduLW1vZGFsJyksXG5cdFx0ZXZlbnRzOlx0XHR7XG5cdFx0XHQnY2xpY2sgLm1vZGFsLWNsb3NlJ1x0OiAnY2xvc2UnLFxuXHRcdFx0J2NsaWNrICNidG4tb2snXHRcdFx0OiAnc2F2ZScsXG5cdFx0XHQnY2hhbmdlIHNlbGVjdCdcdFx0XHQ6ICdzZXRVSVN0YXRlJyxcblx0XHRcdCdrZXl1cCdcdFx0XHRcdFx0OiAnb25rZXl1cCcsXG5cdFx0fSxcblx0XHRvbmtleXVwOmZ1bmN0aW9uKGUpIHtcblx0XHRcdGlmICggZS5vcmlnaW5hbEV2ZW50LmtleUNvZGUgPT09IDI3ICkge1xuXHRcdFx0XHR0aGlzLmNsb3NlKCk7XG5cdFx0XHR9XG5cdFx0XHRyZXR1cm4gdGhpcztcblx0XHR9LFxuXHRcdHJlbmRlcjogZnVuY3Rpb24oKSB7XG5cdFx0XHR3cC5tZWRpYS52aWV3Lk1vZGFsLnByb3RvdHlwZS5yZW5kZXIuYXBwbHkoIHRoaXMsIGFyZ3VtZW50cyApO1xuXHRcdFx0dGhpcy4kb2theSA9IHRoaXMuJCgnI2J0bi1vaycpO1xuXHRcdFx0dGhpcy5zZXRVSVN0YXRlKCk7XG5cdFx0XHRyZXR1cm4gdGhpcztcblx0XHR9LFxuXHRcdG9wZW46ZnVuY3Rpb24oKXtcblx0XHRcdHdwLm1lZGlhLnZpZXcuTW9kYWwucHJvdG90eXBlLm9wZW4uYXBwbHkoIHRoaXMsIGFyZ3VtZW50cyApO1xuXHRcdFx0dGhpcy4kKCcudGl0bGUnKS50ZXh0KCB0aGlzLm1vZGVsLmdldCgnYWN0aW9uJykgPT09ICdncmFudCcgPyBsMTBuLmdyYW50QWNjZXNzIDogbDEwbi5yZXZva2VBY2Nlc3MgKTtcblx0XHRcdHRoaXMuJCgnc2VsZWN0JykudmFsKCcnKVxuXHRcdFx0dGhpcy5zZXRVSVN0YXRlKCk7XG5cdFx0XHRyZXR1cm4gdGhpcztcblx0XHR9LFxuXHRcdHNldFVJU3RhdGU6ZnVuY3Rpb24oZSl7XG5cdFx0XHRjb25zb2xlLmxvZyh0aGlzLiQoJ3NlbGVjdCcpLnZhbCgpKTtcblx0XHRcdHRoaXMuJG9rYXkucHJvcCgnZGlzYWJsZWQnLHRoaXMuJCgnc2VsZWN0JykudmFsKCk9PT0nJyk7XG5cdFx0XHRyZXR1cm4gdGhpcztcblx0XHR9LFxuXHRcdHNhdmU6ZnVuY3Rpb24oKSB7XG5cdFx0XHR2YXIgc2VsZiA9IHRoaXM7XG5cblx0XHRcdHRoaXMubW9kZWwuc2V0KCdpZCcsdGhpcy4kKCdzZWxlY3QnKS52YWwoKSk7XG5cdFx0XHR0aGlzLm1vZGVsLnNhdmUoIG51bGwsIHtcblx0XHRcdFx0c3VjY2VzczogZnVuY3Rpb24oZSxyZXNwb25zZSkge1xuXHRcdFx0XHRcdHZhciBhY3Rpb24gPSBzZWxmLm1vZGVsLmdldCgnYWN0aW9uJyk7XG5cdFx0XHRcdFx0Xy5lYWNoKHJlc3BvbnNlLnVzZXJfaWQsZnVuY3Rpb24odXNlcl9pZCxpKXtcblx0XHRcdFx0XHRcdGlmICggJ2dyYW50JyA9PT0gYWN0aW9uICkge1xuXHRcdFx0XHRcdFx0XHR2YXIgJGJveCA9ICQoJy5hc3NpZ24tYWNjZXNzLWFyZWFzW2RhdGEtd3BhYS11c2VyPVwiJyArIHVzZXJfaWQgKyAnXCJdJyksXG5cdFx0XHRcdFx0XHRcdFx0aHRtbCA9IHdwLnRlbXBsYXRlKCdhY2Nlc3MtYXJlYS1hc3NpZ25lZC11c2VyJykoJC5leHRlbmQocmVzcG9uc2UuYWNjZXNzX2FyZWEse1xuXHRcdFx0XHRcdFx0XHRcdFx0dXNlcl9pZDpyZXNwb25zZS51c2VyX2lkLFxuXHRcdFx0XHRcdFx0XHRcdH0pKTtcblx0XHRcdFx0XHRcdFx0JGJveC5wcmVwZW5kKGh0bWwpO1xuXHRcdFx0XHRcdFx0fSBlbHNlIGlmICggJ3Jldm9rZScgPT09IGFjdGlvbiApIHtcblx0XHRcdFx0XHRcdFx0JCgnW2RhdGEtd3BhYS1hY2Nlc3MtYXJlYT1cIicrcmVzcG9uc2UuYWNjZXNzX2FyZWEuaWQrJ1wiXVtkYXRhLXdwYWEtdXNlcj1cIicrdXNlcl9pZCsnXCJdJylcblx0XHRcdFx0XHRcdFx0XHQuY2xvc2VzdCgnLndwYWEtYWNjZXNzLWFyZWEnKVxuXHRcdFx0XHRcdFx0XHRcdC5yZW1vdmUoKTtcblx0XHRcdFx0XHRcdH1cblx0XHRcdFx0XHR9KTtcblxuXHRcdFx0XHRcdHNlbGYuY2xvc2UoKTtcblx0XHRcdFx0XHQvLyBhZGQgbWVzc2FnZVxuXHRcdFx0XHR9LFxuXHRcdFx0XHRlcnJvcjogZnVuY3Rpb24oZSxyZXNwb25zZSkge1xuXHRcdFx0XHRcdHZhciBtb2RlbCA9IG5ldyBCYWNrYm9uZS5Nb2RlbChyZXNwb25zZS5yZXNwb25zZUpTT04pLFxuXHRcdFx0XHRcdFx0bm90aWNlID0gbmV3IHdwLmFjY2Vzc0FyZWFzLnZpZXcuTm90aWNlKHtcblx0XHRcdFx0XHRcdFx0bW9kZWw6IG1vZGVsXG5cdFx0XHRcdFx0XHR9KTtcblx0XHRcdFx0XHRtb2RlbC5zZXQoJ2Rpc21pc3NpYmxlJyx0cnVlKTtcblx0XHRcdFx0XHRtb2RlbC5zZXQoJ3R5cGUnLCdlcnJvcicpO1xuXHRcdFx0XHRcdC8vIHByaW50IG1lc3NhZ2Vcblx0XHRcdFx0XHQvL3RoaXMuJCgnLm5vdGljZScpLnJlbW92ZSgpO1xuXHRcdFx0XHRcdG5vdGljZS5yZW5kZXIoKS4kZWwucHJlcGVuZFRvKHRoaXMuJCgnLm1vZGFsLWNvbnRlbnQnKSk7XG5cdFx0XHRcdH1cblx0XHRcdH0pO1xuXHRcdFx0cmV0dXJuIHRoaXM7XG5cdFx0fVxuXHR9KTtcblxuXG5cblxuXHRleHBvcnRzLmFjY2Vzc0FyZWFzID0gd3BhYTtcbn0pKGpRdWVyeSx3cCk7XG4iLCIoZnVuY3Rpb24oJCxleHBvcnRzKXtcblxuXHR2YXIgd3BhYSA9IGV4cG9ydHMuYWNjZXNzQXJlYXM7XG5cblxuXHQkKGRvY3VtZW50KS5yZWFkeShmdW5jdGlvbigpe1xuXHRcdHZhciAkZWwgPSAkKCcuYWNjZXNzLWFyZWFzLWxpc3QtdGFibGUnKTtcblx0XHRpZiAoICRlbC5sZW5ndGggKSB7XG5cdFx0XHRuZXcgd3BhYS52aWV3LlRhYmxlVmlld01hbmFnZSh7XG5cdFx0XHRcdGVsOiAkZWwuZ2V0KDApXG5cdFx0XHR9KTtcblx0XHR9XG5cdH0pO1xuXG59KShqUXVlcnksd3ApO1xuIiwiKGZ1bmN0aW9uKCQsZXhwb3J0cyl7XG5cblx0dmFyIHdwYWEgPSBleHBvcnRzLmFjY2Vzc0FyZWFzLFxuXHRcdG1vZGVsID0gbmV3IHdwYWEubW9kZWwuQXNzaWduKCksXG5cdFx0bW9kYWwgPSBuZXcgd3BhYS52aWV3Lk1vZGFsQXNzaWduKHttb2RlbDptb2RlbCxwcm9wYWdhdGU6ZmFsc2V9KTtcblxuXG5cblx0JChkb2N1bWVudClcblx0XHQub24oJ2NsaWNrJywnYm9keS51c2Vycy1waHAgLmJ1dHRvbi5hY3Rpb24nLGZ1bmN0aW9uKGUpe1xuXHRcdFx0dmFyIGFjdGlvbiA9ICQodGhpcykucHJldignc2VsZWN0JykudmFsKCksXG5cdFx0XHRcdHVzZXJzID0gW107XG5cdFx0XHRlLnByZXZlbnREZWZhdWx0KCk7XG5cdFx0XHRpZiAoIFsnd3BhYS1ncmFudCcsJ3dwYWEtcmV2b2tlJ10uaW5kZXhPZihhY3Rpb24pICE9PSAtMSApIHtcblx0XHRcdFx0JCgnW25hbWU9XCJ1c2Vyc1tdXCJdOmNoZWNrZWQnKS5lYWNoKGZ1bmN0aW9uKGksZWwpe1xuXHRcdFx0XHRcdHVzZXJzLnB1c2goJCh0aGlzKS52YWwoKSk7XG5cdFx0XHRcdH0pO1xuXG5cdFx0XHRcdG1vZGVsLnNldCgnYWN0aW9uJywgYWN0aW9uLnJlcGxhY2UoJ3dwYWEtJywnJykpO1xuXHRcdFx0XHRtb2RlbC5zZXQoJ3VzZXJfaWQnLCB1c2VycyApO1xuXHRcdFx0XHRtb2RhbC5vcGVuKCk7XG5cdFx0XHR9XG5cdFx0fSlcblx0XHQub24oJ2NsaWNrJywnW2RhdGEtd3BhYS1hY3Rpb25dJyxmdW5jdGlvbihlKXtcblxuXHRcdFx0ZS5wcmV2ZW50RGVmYXVsdCgpO1xuXG5cdFx0XHR2YXIgYWN0aW9uID0gJCh0aGlzKS5hdHRyKCdkYXRhLXdwYWEtYWN0aW9uJyksXG5cdFx0XHRcdHNlbGYgPSB0aGlzO1xuXG5cdFx0XHRtb2RlbC5zZXQoJ2FjdGlvbicsIGFjdGlvbiApO1xuXHRcdFx0bW9kZWwuc2V0KCd1c2VyX2lkJywgJCh0aGlzKS5hdHRyKCdkYXRhLXdwYWEtdXNlcicpICk7XG5cblx0XHRcdGlmICggYWN0aW9uID09PSAncmV2b2tlJyApIHtcblx0XHRcdFx0JCh0aGlzKS5jbG9zZXN0KCcud3BhYS1hY2Nlc3MtYXJlYScpLmFkZENsYXNzKCdpZGxlJyk7XG5cdFx0XHRcdG1vZGVsLnNldCggJ2lkJywgJCh0aGlzKS5hdHRyKCdkYXRhLXdwYWEtYWNjZXNzLWFyZWEnKSApO1xuXHRcdFx0XHRtb2RlbC5zYXZlKG51bGwse1xuXHRcdFx0XHRcdHN1Y2Nlc3M6ZnVuY3Rpb24oKXtcblx0XHRcdFx0XHRcdCQoc2VsZikuY2xvc2VzdCgnLndwYWEtYWNjZXNzLWFyZWEnKS5yZW1vdmUoKTtcblx0XHRcdFx0XHR9LFxuXHRcdFx0XHRcdGVycm9yOmZ1bmN0aW9uKGUscmVzcG9uc2UpIHtcblx0XHRcdFx0XHRcdHZhciBtb2RlbCA9IG5ldyBCYWNrYm9uZS5Nb2RlbChyZXNwb25zZS5yZXNwb25zZUpTT04pLFxuXHRcdFx0XHRcdFx0XHRub3RpY2UgPSBuZXcgd3AuYWNjZXNzQXJlYXMudmlldy5Ob3RpY2Uoe1xuXHRcdFx0XHRcdFx0XHRcdG1vZGVsOiBtb2RlbFxuXHRcdFx0XHRcdFx0XHR9KTtcblx0XHRcdFx0XHRcdG1vZGVsLnNldCgnZGlzbWlzc2libGUnLHRydWUpO1xuXHRcdFx0XHRcdFx0bW9kZWwuc2V0KCd0eXBlJywnZXJyb3InKTtcblx0XHRcdFx0XHRcdC8vIHByaW50IG1lc3NhZ2Vcblx0XHRcdFx0XHRcdC8vdGhpcy4kKCcubm90aWNlJykucmVtb3ZlKCk7XG5cdFx0XHRcdFx0XHRub3RpY2UucmVuZGVyKCkuJGVsLmluc2VydGZ0ZXIoJCgnLndwLWhlYWRlci1lbmQnKSk7XG5cdFx0XHRcdFx0fVxuXHRcdFx0XHR9KTtcblx0XHRcdH0gZWxzZSBpZiAoIGFjdGlvbiA9PT0gJ2dyYW50JyApIHtcblx0XHRcdFx0bW9kYWwub3BlbigpO1xuXHRcdFx0fVxuXHRcdH0pXG5cdFx0Ly8gLm9uKCdjbGljaycsJ1tkYXRhLXdwYWEtYWN0aW9uXScsZnVuY3Rpb24oZSl7XG5cdFx0Ly8gXHRlLnByZXZlbnREZWZhdWx0KCk7XG4gICAgICAgIC8vXG5cdFx0Ly8gfSlcblx0XHQ7XG5cblxufSkoalF1ZXJ5LHdwKTtcbiJdfQ==

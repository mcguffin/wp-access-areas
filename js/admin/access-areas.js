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
console.log("base!")
	var l10n = access_areas_admin.l10n,
		options = access_areas_admin.options,
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
			this.collection.fetch( { data: { blog_id: options.current_blog_id } } );
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

//# sourceMappingURL=data:application/json;charset=utf8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbImJhc2UuanMiLCJtYW5hZ2UuanMiLCJ1c2Vycy5qcyJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiQUFBQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FDdFRBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FDZkE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSIsImZpbGUiOiJhY2Nlc3MtYXJlYXMuanMiLCJzb3VyY2VzQ29udGVudCI6WyIoZnVuY3Rpb24oJCxleHBvcnRzKXtcblx0Ly8gd3BBcGlTZXR0aW5ncy5tYXBwaW5nID0ge1xuXHQvLyBcdG1vZGVsczoge1xuXHQvLyBcdFx0J0FjY2Vzc0FyZWFzJyA6ICdBY2Nlc3NBcmVhJ1xuXHQvLyBcdH0sXG5cdC8vIFx0Y29sbGVjdGlvbnM6e31cblx0Ly8gfTtcblx0Ly8gd3AuYXBpLmluaXQoe1xuXHQvLyBcdHZlcnNpb25TdHJpbmcgOiAnd3BhYS92MS8nLFxuXHQvLyBcdGFwaVJvb3Q6IHdwQXBpU2V0dGluZ3Mucm9vdCxcblx0Ly8gXHRzY2hlbWE6IG51bGwsXG5cdC8vIFx0fSlcblx0Ly8gXHQuZG9uZSggZnVuY3Rpb24oKSB7XG5cdC8vIFx0XHRjb25zb2xlLmxvZyh0aGlzLCB3cC5hcGkuZW5kcG9pbnRzLmZpbmQoe3ZlcnNpb25TdHJpbmc6J3dwYWEvdjEvJ30pICk7XG5cdC8vIFx0fSk7XG5jb25zb2xlLmxvZyhcImJhc2UhXCIpXG5cdHZhciBsMTBuID0gYWNjZXNzX2FyZWFzX2FkbWluLmwxMG4sXG5cdFx0b3B0aW9ucyA9IGFjY2Vzc19hcmVhc19hZG1pbi5vcHRpb25zLFxuXHRcdHdwYWEgPSB7XG5cdFx0XHRsMTBuOiBsMTBuLFxuXHRcdFx0dmlldzoge30sXG5cdFx0XHRtb2RlbDoge31cblx0XHR9O1xuXG5cdHdwYWEubW9kZWwuQXNzaWduID0gQmFja2JvbmUuTW9kZWwuZXh0ZW5kKHtcblx0XHRkZWZhdWx0czp7XG5cdFx0XHRpZDogbnVsbCxcblx0XHRcdGFjdGlvbjonZ3JhbnQnLFxuXHRcdFx0dXNlcnM6W11cblx0XHR9LFxuXHRcdHNhdmU6ZnVuY3Rpb24oKSB7XG5cblx0XHRcdHRoaXMudXJsID0gd3BBcGlTZXR0aW5ncy5yb290ICsgd3BBcGlTZXR0aW5ncy52ZXJzaW9uU3RyaW5nICsgJ2FjY2Vzcy1hcmVhLycgKyB0aGlzLmdldCgnaWQnKSArICcvJyArIHRoaXMuZ2V0KCdhY3Rpb24nKTtcblx0XHRcdEJhY2tib25lLk1vZGVsLnByb3RvdHlwZS5zYXZlLmFwcGx5KHRoaXMsYXJndW1lbnRzKTtcblxuXHRcdH1cblx0fSk7XG5cblx0d3BhYS52aWV3Lk5vdGljZSA9IHdwLm1lZGlhLlZpZXcuZXh0ZW5kKHtcblx0XHR0ZW1wbGF0ZTogd3AudGVtcGxhdGUoJ2FjY2Vzcy1hcmVhLW5vdGljZScpLFxuXHRcdGV2ZW50czp7XG5cdFx0XHQnY2xpY2sgLm5vdGljZS1kaXNtaXNzJyA6IGZ1bmN0aW9uKCl7XG5cdFx0XHRcdHRoaXMuJGVsLnJlbW92ZSgpO1xuXHRcdFx0fVxuXHRcdH1cblx0fSk7XG5cblx0d3BhYS52aWV3LlJvd1ZpZXdNYW5hZ2UgPSB3cC5tZWRpYS5WaWV3LmV4dGVuZCh7XG5cdFx0dGVtcGxhdGU6d3AudGVtcGxhdGUoJ2FjY2Vzcy1hcmVhLXJvdycpLFxuXHRcdHRhZ05hbWU6J3RyJyxcblx0XHRldmVudHM6IHtcblx0XHRcdCdjbGljayBhLmRlbGV0ZSdcdFx0OiAnZGVsZXRlJyxcblx0XHRcdCdjbGljayBhLmVkaXQnXHRcdFx0OiAnZWRpdCcsXG5cdFx0fSxcblx0XHRpbml0aWFsaXplOiBmdW5jdGlvbigpIHtcblx0XHRcdHdwLm1lZGlhLlZpZXcucHJvdG90eXBlLmluaXRpYWxpemUuYXBwbHkodGhpcyxhcmd1bWVudHMpO1xuXHRcdFx0dGhpcy5saXN0ZW5UbyggdGhpcy5tb2RlbCwgJ2JlZm9yZV9zeW5jJywgdGhpcy5kaXNhYmxlICk7XG5cdFx0XHR0aGlzLmxpc3RlblRvKCB0aGlzLm1vZGVsLCAnYmVmb3JlX2Rlc3Ryb3knLCB0aGlzLmRpc2FibGUgKTtcblx0XHRcdHRoaXMubGlzdGVuVG8oIHRoaXMubW9kZWwsICdzeW5jJywgdGhpcy5yZW5kZXIgKTtcblx0XHRcdHRoaXMubGlzdGVuVG8oIHRoaXMubW9kZWwsICdkZXN0cm95JywgdGhpcy5yZW1vdmUgKTtcblx0XHR9LFxuXHRcdHJlbmRlcjogZnVuY3Rpb24oKSB7XG5cdFx0XHR2YXIgc2VsZiA9IHRoaXM7XG5cdFx0XHR3cC5tZWRpYS5WaWV3LnByb3RvdHlwZS5yZW5kZXIuYXBwbHkodGhpcyxhcmd1bWVudHMpO1xuXHRcdFx0dGhpcy4kZWwuYXR0cignZGF0YS1pZCcsdGhpcy5tb2RlbC5nZXQoJ2lkJykpO1xuXHRcdFx0cmV0dXJuIHRoaXM7XG5cdFx0fSxcblx0XHRkaXNhYmxlOmZ1bmN0aW9uKGUpe1xuXHRcdFx0dGhpcy4kZWwuYWRkQ2xhc3MoJ2Rpc2FibGVkJyk7XG5cdFx0XHRyZXR1cm4gdGhpcztcblx0XHR9LFxuXHRcdGVuYWJsZTpmdW5jdGlvbihlKXtcblx0XHRcdHRoaXMuJGVsLnJlbW92ZUNsYXNzKCdkaXNhYmxlZCcpO1xuXHRcdFx0cmV0dXJuIHRoaXM7XG5cdFx0fSxcblx0XHRkZWxldGU6ZnVuY3Rpb24oZSl7XG5cdFx0XHRlLnByZXZlbnREZWZhdWx0KCk7XG5cdFx0XHR0aGlzLm1vZGVsLmRlc3Ryb3koKTtcblx0XHRcdHJldHVybiB0aGlzO1xuXHRcdH0sXG5cdFx0ZWRpdDpmdW5jdGlvbihlKXtcblx0XHRcdHRoaXMuY29udHJvbGxlci5lZGl0RW50cnkodGhpcy5tb2RlbCk7XG5cdFx0XHRyZXR1cm4gdGhpcztcblx0XHR9LFxuXHRcdGRlbGV0ZTpmdW5jdGlvbihlKXtcblx0XHRcdHRoaXMubW9kZWwuZGVzdHJveSgpO1xuXHRcdFx0cmV0dXJuIHRoaXM7XG5cdFx0fVxuXHR9KTtcblx0d3BhYS52aWV3LlRhYmxlVmlld01hbmFnZSA9IHdwLm1lZGlhLlZpZXcuZXh0ZW5kKHtcblx0XHRldmVudHM6IHtcblx0XHRcdCdjbGljayAuYWRkJyA6ICduZXdFbnRyeScsXG5cdFx0fSxcblx0XHRpbml0aWFsaXplOiBmdW5jdGlvbigpIHtcblx0XHRcdHZhciBzZWxmID0gdGhpcztcblx0XHRcdHdwLm1lZGlhLlZpZXcucHJvdG90eXBlLmluaXRpYWxpemUuYXBwbHkodGhpcyxhcmd1bWVudHMpO1xuXHRcdFx0dGhpcy5jb2xsZWN0aW9uID0gbmV3IHdwLmFwaS5jb2xsZWN0aW9ucy5BY2Nlc3NBcmVhKCk7XG5cdFx0XHR0aGlzLmNvbGxlY3Rpb24uZmV0Y2goIHsgZGF0YTogeyBibG9nX2lkOiBvcHRpb25zLmN1cnJlbnRfYmxvZ19pZCB9IH0gKTtcblx0XHRcdHRoaXMuY29sbGVjdGlvbi5vbmNlKCdzeW5jJyx0aGlzLnJlbmRlcix0aGlzKTtcblx0XHRcdHRoaXMubGlzdGVuVG8odGhpcy5jb2xsZWN0aW9uLCdhZGQnLGZ1bmN0aW9uKG1vZGVsKXtcblx0XHRcdFx0c2VsZi5hZGRSb3cobW9kZWwpO1xuXHRcdFx0XHRjb25zb2xlLmxvZyhhcmd1bWVudHMpO1xuXHRcdFx0fSk7XG5cdFx0XHR0aGlzLmxpc3RlblRvKHRoaXMuY29sbGVjdGlvbiwncmVtb3ZlJyxmdW5jdGlvbihtb2RlbCl7XG5cdFx0XHRcdHNlbGYuc2V0UGxhY2Vob2xkZXIoKTtcblx0XHRcdH0pO1xuXHRcdFx0dGhpcy5yZW5kZXIoKTtcblx0XHR9LFxuXHRcdG5ld0VudHJ5OmZ1bmN0aW9uKGUpIHtcblx0XHRcdHZhciBtb2RlbCA9IG5ldyB3cC5hcGkubW9kZWxzLkFjY2Vzc0FyZWEoe1xuXHRcdFx0XHRcdElEOiBudWxsLFxuXHRcdFx0XHRcdHRpdGxlOiAnJyxcblx0XHRcdFx0fSk7XG5cdFx0XHR0aGlzLmVkaXRFbnRyeShtb2RlbCk7XG5cdFx0XHRyZXR1cm4gdGhpcztcblx0XHR9LFxuXHRcdGVkaXRFbnRyeTpmdW5jdGlvbihtb2RlbCkge1xuXHRcdFx0dmFyIHNlbGYgPSB0aGlzO1xuXHRcdFx0Ly8gb3BlbiBtb2RhbFxuXHRcdFx0dGhpcy5tb2RhbC5tb2RlbCA9IG1vZGVsO1xuXHRcdFx0dGhpcy5tb2RhbC5yZW5kZXIoKS5vcGVuKHt9KTtcblx0XHRcdHRoaXMubGlzdGVuVG8obW9kZWwsJ3N5bmMnLGZ1bmN0aW9uKGUpe1xuXHRcdFx0XHRzZWxmLnN0b3BMaXN0ZW5pbmcobW9kZWwsJ3N5bmMnKTtcblx0XHRcdFx0aWYgKCAhIHNlbGYuY29sbGVjdGlvbi53aGVyZSggeyBpZDogbW9kZWwuZ2V0KCdpZCcpIH0gKS5sZW5ndGggKSB7XG5cdFx0XHRcdFx0c2VsZi5jb2xsZWN0aW9uLmFkZChtb2RlbCk7XG4vL1x0XHRcdFx0XHRcdHNlbGYuYWRkUm93KG1vZGVsKTtcblxuXHRcdFx0XHR9XG5cdFx0XHR9KTtcblx0XHRcdHJldHVybiB0aGlzO1xuXHRcdH0sXG5cdFx0YWRkUm93OmZ1bmN0aW9uKCBtb2RlbCApe1xuXHRcdFx0dmFyIHNlbGYgPSB0aGlzLFxuXHRcdFx0XHR2aWV3ID0gbmV3IHdwYWEudmlldy5Sb3dWaWV3TWFuYWdlKHtcblx0XHRcdFx0XHRtb2RlbDptb2RlbCxcblx0XHRcdFx0XHRjb250cm9sbGVyOnRoaXMsXG5cdFx0XHRcdH0pO1xuXHRcdFx0dGhpcy4kZWwuYXBwZW5kKCB2aWV3LnJlbmRlcigpLmVsICk7XG5cdFx0XHR0aGlzLmxpc3RlblRvKCBtb2RlbCwnZGVzdHJveScsZnVuY3Rpb24oZSl7XG5cdFx0XHRcdHZpZXcuJGVsLnJlbW92ZSgpO1xuXHRcdFx0XHRzZWxmLnNldFBsYWNlaG9sZGVyKCk7XG5cdFx0XHR9KTtcblx0XHRcdHRoaXMuc2V0UGxhY2Vob2xkZXIoKTtcblx0XHRcdHJldHVybiB0aGlzO1xuXHRcdH0sXG5cdFx0aGFzUm93OmZ1bmN0aW9uKG1vZGVsKXtcblx0XHRcdHJldHVybiB0aGlzLiQoJ1tkYXRhLWlkPVwiJyttb2RlbC5nZXQoJ2lkJykrJ1wiXScpLmxlbmd0aCA+IDA7XG5cdFx0fSxcblx0XHRyZW5kZXI6IGZ1bmN0aW9uKCkge1xuXG5cdFx0XHR2YXIgc2VsZiA9IHRoaXM7XG5cblx0XHRcdGlmICggISB0aGlzLm1vZGFsICkge1xuXHRcdFx0XHR0aGlzLm1vZGFsID0gbmV3IHdwYWEudmlldy5Nb2RhbE1hbmFnZSh7XG5cdFx0XHRcdFx0Y29udHJvbGxlcjpcdHRoaXMsXG5cdFx0XHRcdH0gKTtcblx0XHRcdH1cblxuXHRcdFx0d3AubWVkaWEuVmlldy5wcm90b3R5cGUucmVuZGVyLmFwcGx5KHRoaXMsYXJndW1lbnRzKTtcblxuXHRcdFx0dGhpcy4kcGxhY2Vob2xkZXIgPSB0aGlzLiQoJy5wbGFjZWhvbGRlci1yb3cnKTtcblxuXHRcdFx0dGhpcy5zZXRQbGFjZWhvbGRlcigpO1xuXHRcdFx0cmV0dXJuIHRoaXM7XG5cdFx0fSxcblx0XHRzZXRQbGFjZWhvbGRlcjpmdW5jdGlvbigpe1xuXHRcdFx0aWYgKCB0aGlzLmNvbGxlY3Rpb24ubGVuZ3RoICkge1xuXHRcdFx0XHR0aGlzLiRwbGFjZWhvbGRlci5yZW1vdmUoKTtcblx0XHRcdH0gZWxzZSBpZiAoICEgdGhpcy4kcGxhY2Vob2xkZXIucGFyZW50KCkubGVuZ3RoICkge1xuXHRcdFx0XHR0aGlzLiRwbGFjZWhvbGRlci5hcHBlbmRUbyggdGhpcy5lbCApO1xuXHRcdFx0fVxuXHRcdH1cblx0fSk7XG5cblxuXHR3cGFhLnZpZXcuTW9kYWxNYW5hZ2UgPSB3cC5tZWRpYS52aWV3Lk1vZGFsLmV4dGVuZCh7XG5cdFx0dGVtcGxhdGU6d3AudGVtcGxhdGUoJ2FjY2Vzcy1hcmVhLW1vZGFsJyksXG5cdFx0ZXZlbnRzOlx0XHR7XG5cdFx0XHQnY2xpY2sgLm1vZGFsLWNsb3NlJ1x0OiAnY2xvc2UnLFxuXHRcdFx0J2NsaWNrICNidG4tb2snXHRcdFx0OiAnc2F2ZScsXG5cdFx0XHQna2V5dXAgI3RpdGxlLWlucHV0J1x0OiAnc2V0VUlTdGF0ZScsXG5cdFx0XHQnY2hhbmdlICN0aXRsZS1pbnB1dCdcdDogJ3NldFVJU3RhdGUnLFxuXHRcdFx0J2JsdXIgI3RpdGxlLWlucHV0J1x0XHQ6ICdzZXRVSVN0YXRlJyxcblx0XHRcdCdrZXl1cCdcdFx0XHRcdFx0OiAnb25rZXl1cCcsXG5cdFx0fSxcblx0XHRzZXRVSVN0YXRlOmZ1bmN0aW9uKGUpe1xuXHRcdFx0dGhpcy4kb2theS5wcm9wKCdkaXNhYmxlZCcsIXRoaXMuJHRpdGxlLnZhbCgpKTtcblx0XHRcdHJldHVybiB0aGlzO1xuXHRcdH0sXG5cdFx0cmVuZGVyOiBmdW5jdGlvbigpIHtcblx0XHRcdHdwLm1lZGlhLnZpZXcuTW9kYWwucHJvdG90eXBlLnJlbmRlci5hcHBseSggdGhpcywgYXJndW1lbnRzICk7XG5cdFx0XHR0aGlzLiRva2F5ID0gdGhpcy4kKCcjYnRuLW9rJyk7XG5cdFx0XHR0aGlzLiR0aXRsZSA9IHRoaXMuJCgnI3RpdGxlLWlucHV0Jyk7XG5cdFx0XHR0aGlzLiRibG9nX2lkID0gdGhpcy4kKCdbbmFtZT1cImJsb2dfaWRcIl0nKTtcblx0XHRcdHRoaXMuJHRpdGxlLnZhbCggdGhpcy5tb2RlbC5nZXQoICd0aXRsZScgKSApO1xuXHRcdFx0dGhpcy5zZXRVSVN0YXRlKCk7XG5cdFx0XHRyZXR1cm4gdGhpcztcblx0XHR9LFxuXHRcdG9ua2V5dXA6ZnVuY3Rpb24oZSkge1xuXHRcdFx0aWYgKCBlLm9yaWdpbmFsRXZlbnQua2V5Q29kZSA9PT0gMjcgKSB7XG5cdFx0XHRcdHRoaXMuY2xvc2UoKTtcblx0XHRcdH1cblx0XHR9LFxuXHRcdG9wZW46ZnVuY3Rpb24oKXtcblx0XHRcdHdwLm1lZGlhLnZpZXcuTW9kYWwucHJvdG90eXBlLm9wZW4uYXBwbHkoIHRoaXMsIGFyZ3VtZW50cyApO1xuXHRcdFx0dGhpcy4kKCcudGl0bGUnKS50ZXh0KCAhISB0aGlzLm1vZGVsLmdldCgnaWQnKSA/IGwxMG4uZWRpdEFjY2Vzc0FyZWEgOiBsMTBuLmNyZWF0ZUFjY2Vzc0FyZWEgKTtcblx0XHRcdHRoaXMuc2V0VUlTdGF0ZSgpO1xuXHRcdH0sXG5cdFx0c2F2ZTpmdW5jdGlvbigpIHtcblx0XHRcdHZhciBzZWxmID0gdGhpcztcblxuXHRcdFx0dGhpcy5tb2RlbC5zZXQoJ3RpdGxlJywgdGhpcy4kdGl0bGUudmFsKCkgKTtcblx0XHRcdHRoaXMubW9kZWwuc2V0KCdibG9nX2lkJywgdGhpcy4kYmxvZ19pZC52YWwoKSApO1xuXHRcdFx0dGhpcy5tb2RlbC5zYXZlKCBudWxsLCB7XG5cdFx0XHRcdHN1Y2Nlc3M6IGZ1bmN0aW9uKGUpIHtcblx0XHRcdFx0XHRzZWxmLmNsb3NlKCk7XG5cdFx0XHRcdFx0Ly8gYWRkIG1lc3NhZ2Vcblx0XHRcdFx0fSxcblx0XHRcdFx0ZXJyb3I6IGZ1bmN0aW9uKGUscmVzcG9uc2UpIHtcblx0XHRcdFx0XHR2YXIgbW9kZWwgPSBuZXcgQmFja2JvbmUuTW9kZWwocmVzcG9uc2UucmVzcG9uc2VKU09OKSxcblx0XHRcdFx0XHRcdG5vdGljZSA9IG5ldyB3cC5hY2Nlc3NBcmVhcy52aWV3Lk5vdGljZSh7XG5cdFx0XHRcdFx0XHRcdG1vZGVsOiBtb2RlbFxuXHRcdFx0XHRcdFx0fSk7XG5cdFx0XHRcdFx0bW9kZWwuc2V0KCdkaXNtaXNzaWJsZScsdHJ1ZSk7XG5cdFx0XHRcdFx0bW9kZWwuc2V0KCd0eXBlJywnZXJyb3InKTtcblx0XHRcdFx0XHQvLyBwcmludCBtZXNzYWdlXG5cdFx0XHRcdFx0Ly90aGlzLiQoJy5ub3RpY2UnKS5yZW1vdmUoKTtcblx0XHRcdFx0XHRub3RpY2UucmVuZGVyKCkuJGVsLnByZXBlbmRUbyh0aGlzLiQoJy5tb2RhbC1jb250ZW50JykpO1xuXHRcdFx0XHR9XG5cdFx0XHR9KTtcblx0XHR9LFxuXHR9KTtcblxuXHR3cGFhLnZpZXcuTW9kYWxBc3NpZ24gPSB3cC5tZWRpYS52aWV3Lk1vZGFsLmV4dGVuZCh7XG5cdFx0dGVtcGxhdGU6d3AudGVtcGxhdGUoJ2FjY2Vzcy1hcmVhLWFzc2lnbi1tb2RhbCcpLFxuXHRcdGV2ZW50czpcdFx0e1xuXHRcdFx0J2NsaWNrIC5tb2RhbC1jbG9zZSdcdDogJ2Nsb3NlJyxcblx0XHRcdCdjbGljayAjYnRuLW9rJ1x0XHRcdDogJ3NhdmUnLFxuXHRcdFx0J2NoYW5nZSBzZWxlY3QnXHRcdFx0OiAnc2V0VUlTdGF0ZScsXG5cdFx0XHQna2V5dXAnXHRcdFx0XHRcdDogJ29ua2V5dXAnLFxuXHRcdH0sXG5cdFx0b25rZXl1cDpmdW5jdGlvbihlKSB7XG5cdFx0XHRpZiAoIGUub3JpZ2luYWxFdmVudC5rZXlDb2RlID09PSAyNyApIHtcblx0XHRcdFx0dGhpcy5jbG9zZSgpO1xuXHRcdFx0fVxuXHRcdFx0cmV0dXJuIHRoaXM7XG5cdFx0fSxcblx0XHRyZW5kZXI6IGZ1bmN0aW9uKCkge1xuXHRcdFx0d3AubWVkaWEudmlldy5Nb2RhbC5wcm90b3R5cGUucmVuZGVyLmFwcGx5KCB0aGlzLCBhcmd1bWVudHMgKTtcblx0XHRcdHRoaXMuJG9rYXkgPSB0aGlzLiQoJyNidG4tb2snKTtcblx0XHRcdHRoaXMuc2V0VUlTdGF0ZSgpO1xuXHRcdFx0cmV0dXJuIHRoaXM7XG5cdFx0fSxcblx0XHRvcGVuOmZ1bmN0aW9uKCl7XG5cdFx0XHR3cC5tZWRpYS52aWV3Lk1vZGFsLnByb3RvdHlwZS5vcGVuLmFwcGx5KCB0aGlzLCBhcmd1bWVudHMgKTtcblx0XHRcdHRoaXMuJCgnLnRpdGxlJykudGV4dCggdGhpcy5tb2RlbC5nZXQoJ2FjdGlvbicpID09PSAnZ3JhbnQnID8gbDEwbi5ncmFudEFjY2VzcyA6IGwxMG4ucmV2b2tlQWNjZXNzICk7XG5cdFx0XHR0aGlzLiQoJ3NlbGVjdCcpLnZhbCgnJylcblx0XHRcdHRoaXMuc2V0VUlTdGF0ZSgpO1xuXHRcdFx0cmV0dXJuIHRoaXM7XG5cdFx0fSxcblx0XHRzZXRVSVN0YXRlOmZ1bmN0aW9uKGUpe1xuXHRcdFx0Y29uc29sZS5sb2codGhpcy4kKCdzZWxlY3QnKS52YWwoKSk7XG5cdFx0XHR0aGlzLiRva2F5LnByb3AoJ2Rpc2FibGVkJyx0aGlzLiQoJ3NlbGVjdCcpLnZhbCgpPT09JycpO1xuXHRcdFx0cmV0dXJuIHRoaXM7XG5cdFx0fSxcblx0XHRzYXZlOmZ1bmN0aW9uKCkge1xuXHRcdFx0dmFyIHNlbGYgPSB0aGlzO1xuXG5cdFx0XHR0aGlzLm1vZGVsLnNldCgnaWQnLHRoaXMuJCgnc2VsZWN0JykudmFsKCkpO1xuXHRcdFx0dGhpcy5tb2RlbC5zYXZlKCBudWxsLCB7XG5cdFx0XHRcdHN1Y2Nlc3M6IGZ1bmN0aW9uKGUscmVzcG9uc2UpIHtcblx0XHRcdFx0XHR2YXIgYWN0aW9uID0gc2VsZi5tb2RlbC5nZXQoJ2FjdGlvbicpO1xuXHRcdFx0XHRcdF8uZWFjaChyZXNwb25zZS51c2VyX2lkLGZ1bmN0aW9uKHVzZXJfaWQsaSl7XG5cdFx0XHRcdFx0XHRpZiAoICdncmFudCcgPT09IGFjdGlvbiApIHtcblx0XHRcdFx0XHRcdFx0dmFyICRib3ggPSAkKCcuYXNzaWduLWFjY2Vzcy1hcmVhc1tkYXRhLXdwYWEtdXNlcj1cIicgKyB1c2VyX2lkICsgJ1wiXScpLFxuXHRcdFx0XHRcdFx0XHRcdGh0bWwgPSB3cC50ZW1wbGF0ZSgnYWNjZXNzLWFyZWEtYXNzaWduZWQtdXNlcicpKCQuZXh0ZW5kKHJlc3BvbnNlLmFjY2Vzc19hcmVhLHtcblx0XHRcdFx0XHRcdFx0XHRcdHVzZXJfaWQ6cmVzcG9uc2UudXNlcl9pZCxcblx0XHRcdFx0XHRcdFx0XHR9KSk7XG5cdFx0XHRcdFx0XHRcdCRib3gucHJlcGVuZChodG1sKTtcblx0XHRcdFx0XHRcdH0gZWxzZSBpZiAoICdyZXZva2UnID09PSBhY3Rpb24gKSB7XG5cdFx0XHRcdFx0XHRcdCQoJ1tkYXRhLXdwYWEtYWNjZXNzLWFyZWE9XCInK3Jlc3BvbnNlLmFjY2Vzc19hcmVhLmlkKydcIl1bZGF0YS13cGFhLXVzZXI9XCInK3VzZXJfaWQrJ1wiXScpXG5cdFx0XHRcdFx0XHRcdFx0LmNsb3Nlc3QoJy53cGFhLWFjY2Vzcy1hcmVhJylcblx0XHRcdFx0XHRcdFx0XHQucmVtb3ZlKCk7XG5cdFx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0fSk7XG5cblx0XHRcdFx0XHRzZWxmLmNsb3NlKCk7XG5cdFx0XHRcdFx0Ly8gYWRkIG1lc3NhZ2Vcblx0XHRcdFx0fSxcblx0XHRcdFx0ZXJyb3I6IGZ1bmN0aW9uKGUscmVzcG9uc2UpIHtcblx0XHRcdFx0XHR2YXIgbW9kZWwgPSBuZXcgQmFja2JvbmUuTW9kZWwocmVzcG9uc2UucmVzcG9uc2VKU09OKSxcblx0XHRcdFx0XHRcdG5vdGljZSA9IG5ldyB3cC5hY2Nlc3NBcmVhcy52aWV3Lk5vdGljZSh7XG5cdFx0XHRcdFx0XHRcdG1vZGVsOiBtb2RlbFxuXHRcdFx0XHRcdFx0fSk7XG5cdFx0XHRcdFx0bW9kZWwuc2V0KCdkaXNtaXNzaWJsZScsdHJ1ZSk7XG5cdFx0XHRcdFx0bW9kZWwuc2V0KCd0eXBlJywnZXJyb3InKTtcblx0XHRcdFx0XHQvLyBwcmludCBtZXNzYWdlXG5cdFx0XHRcdFx0Ly90aGlzLiQoJy5ub3RpY2UnKS5yZW1vdmUoKTtcblx0XHRcdFx0XHRub3RpY2UucmVuZGVyKCkuJGVsLnByZXBlbmRUbyh0aGlzLiQoJy5tb2RhbC1jb250ZW50JykpO1xuXHRcdFx0XHR9XG5cdFx0XHR9KTtcblx0XHRcdHJldHVybiB0aGlzO1xuXHRcdH1cblx0fSk7XG5cblxuXG5cblx0ZXhwb3J0cy5hY2Nlc3NBcmVhcyA9IHdwYWE7XG59KShqUXVlcnksd3ApO1xuIiwiKGZ1bmN0aW9uKCQsZXhwb3J0cyl7XG5cblx0dmFyIHdwYWEgPSBleHBvcnRzLmFjY2Vzc0FyZWFzO1xuXG5cblx0JChkb2N1bWVudCkucmVhZHkoZnVuY3Rpb24oKXtcblx0XHR2YXIgJGVsID0gJCgnLmFjY2Vzcy1hcmVhcy1saXN0LXRhYmxlJyk7XG5cdFx0aWYgKCAkZWwubGVuZ3RoICkge1xuXHRcdFx0bmV3IHdwYWEudmlldy5UYWJsZVZpZXdNYW5hZ2Uoe1xuXHRcdFx0XHRlbDogJGVsLmdldCgwKVxuXHRcdFx0fSk7XG5cdFx0fVxuXHR9KTtcblxufSkoalF1ZXJ5LHdwKTtcbiIsIihmdW5jdGlvbigkLGV4cG9ydHMpe1xuXG5cdHZhciB3cGFhID0gZXhwb3J0cy5hY2Nlc3NBcmVhcyxcblx0XHRtb2RlbCA9IG5ldyB3cGFhLm1vZGVsLkFzc2lnbigpLFxuXHRcdG1vZGFsID0gbmV3IHdwYWEudmlldy5Nb2RhbEFzc2lnbih7bW9kZWw6bW9kZWwscHJvcGFnYXRlOmZhbHNlfSk7XG5cblxuXG5cdCQoZG9jdW1lbnQpXG5cdFx0Lm9uKCdjbGljaycsJ2JvZHkudXNlcnMtcGhwIC5idXR0b24uYWN0aW9uJyxmdW5jdGlvbihlKXtcblx0XHRcdHZhciBhY3Rpb24gPSAkKHRoaXMpLnByZXYoJ3NlbGVjdCcpLnZhbCgpLFxuXHRcdFx0XHR1c2VycyA9IFtdO1xuXHRcdFx0ZS5wcmV2ZW50RGVmYXVsdCgpO1xuXHRcdFx0aWYgKCBbJ3dwYWEtZ3JhbnQnLCd3cGFhLXJldm9rZSddLmluZGV4T2YoYWN0aW9uKSAhPT0gLTEgKSB7XG5cdFx0XHRcdCQoJ1tuYW1lPVwidXNlcnNbXVwiXTpjaGVja2VkJykuZWFjaChmdW5jdGlvbihpLGVsKXtcblx0XHRcdFx0XHR1c2Vycy5wdXNoKCQodGhpcykudmFsKCkpO1xuXHRcdFx0XHR9KTtcblxuXHRcdFx0XHRtb2RlbC5zZXQoJ2FjdGlvbicsIGFjdGlvbi5yZXBsYWNlKCd3cGFhLScsJycpKTtcblx0XHRcdFx0bW9kZWwuc2V0KCd1c2VyX2lkJywgdXNlcnMgKTtcblx0XHRcdFx0bW9kYWwub3BlbigpO1xuXHRcdFx0fVxuXHRcdH0pXG5cdFx0Lm9uKCdjbGljaycsJ1tkYXRhLXdwYWEtYWN0aW9uXScsZnVuY3Rpb24oZSl7XG5cblx0XHRcdGUucHJldmVudERlZmF1bHQoKTtcblxuXHRcdFx0dmFyIGFjdGlvbiA9ICQodGhpcykuYXR0cignZGF0YS13cGFhLWFjdGlvbicpLFxuXHRcdFx0XHRzZWxmID0gdGhpcztcblxuXHRcdFx0bW9kZWwuc2V0KCdhY3Rpb24nLCBhY3Rpb24gKTtcblx0XHRcdG1vZGVsLnNldCgndXNlcl9pZCcsICQodGhpcykuYXR0cignZGF0YS13cGFhLXVzZXInKSApO1xuXG5cdFx0XHRpZiAoIGFjdGlvbiA9PT0gJ3Jldm9rZScgKSB7XG5cdFx0XHRcdCQodGhpcykuY2xvc2VzdCgnLndwYWEtYWNjZXNzLWFyZWEnKS5hZGRDbGFzcygnaWRsZScpO1xuXHRcdFx0XHRtb2RlbC5zZXQoICdpZCcsICQodGhpcykuYXR0cignZGF0YS13cGFhLWFjY2Vzcy1hcmVhJykgKTtcblx0XHRcdFx0bW9kZWwuc2F2ZShudWxsLHtcblx0XHRcdFx0XHRzdWNjZXNzOmZ1bmN0aW9uKCl7XG5cdFx0XHRcdFx0XHQkKHNlbGYpLmNsb3Nlc3QoJy53cGFhLWFjY2Vzcy1hcmVhJykucmVtb3ZlKCk7XG5cdFx0XHRcdFx0fSxcblx0XHRcdFx0XHRlcnJvcjpmdW5jdGlvbihlLHJlc3BvbnNlKSB7XG5cdFx0XHRcdFx0XHR2YXIgbW9kZWwgPSBuZXcgQmFja2JvbmUuTW9kZWwocmVzcG9uc2UucmVzcG9uc2VKU09OKSxcblx0XHRcdFx0XHRcdFx0bm90aWNlID0gbmV3IHdwLmFjY2Vzc0FyZWFzLnZpZXcuTm90aWNlKHtcblx0XHRcdFx0XHRcdFx0XHRtb2RlbDogbW9kZWxcblx0XHRcdFx0XHRcdFx0fSk7XG5cdFx0XHRcdFx0XHRtb2RlbC5zZXQoJ2Rpc21pc3NpYmxlJyx0cnVlKTtcblx0XHRcdFx0XHRcdG1vZGVsLnNldCgndHlwZScsJ2Vycm9yJyk7XG5cdFx0XHRcdFx0XHQvLyBwcmludCBtZXNzYWdlXG5cdFx0XHRcdFx0XHQvL3RoaXMuJCgnLm5vdGljZScpLnJlbW92ZSgpO1xuXHRcdFx0XHRcdFx0bm90aWNlLnJlbmRlcigpLiRlbC5pbnNlcnRmdGVyKCQoJy53cC1oZWFkZXItZW5kJykpO1xuXHRcdFx0XHRcdH1cblx0XHRcdFx0fSk7XG5cdFx0XHR9IGVsc2UgaWYgKCBhY3Rpb24gPT09ICdncmFudCcgKSB7XG5cdFx0XHRcdG1vZGFsLm9wZW4oKTtcblx0XHRcdH1cblx0XHR9KVxuXHRcdC8vIC5vbignY2xpY2snLCdbZGF0YS13cGFhLWFjdGlvbl0nLGZ1bmN0aW9uKGUpe1xuXHRcdC8vIFx0ZS5wcmV2ZW50RGVmYXVsdCgpO1xuICAgICAgICAvL1xuXHRcdC8vIH0pXG5cdFx0O1xuXG5cbn0pKGpRdWVyeSx3cCk7XG4iXX0=

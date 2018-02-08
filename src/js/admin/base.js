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

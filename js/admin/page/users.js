(function($){
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

	var l10n = access_areas_admin_page.l10n,
		AARowView = wp.media.View.extend({
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
		}),
		AATableView = wp.media.View.extend({
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
					view = new AARowView({
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
					this.modal = new AAModal({
						controller:	this,
					} );
				}

				wp.media.View.prototype.render.apply(this,arguments);

				this.$placeholder = this.$('.placeholder-row');

				this.collection.each(function(model){
//					self.addRow(model);
				});
				this.setPlaceholder();
				return this;
			},
			setPlaceholder:function(){
				console.log(this.$placeholder,this.$placeholder.parent().length)
				if ( this.collection.length ) {
					this.$placeholder.remove();
				} else if ( ! this.$placeholder.parent().length ) {
					this.$placeholder.appendTo( this.el );
				}
			}
		}),
		AAModal = wp.media.view.Modal.extend({
			template:wp.template('access-area-modal'),
			events:		{
				'click .modal-close'	: 'close',
				'click #btn-ok'			: 'save',
				'keyup #title-input'	: 'setUIState',
				'changed #title-input'	: 'setUIState',
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
							notice = new AANotice({
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
		}),
		AANotice = wp.media.View.extend({
			template: wp.template('access-area-notice'),
			events:{
				'click .notice-dismiss' : function(){
					this.$el.remove();
				}
			}
		}),
		tableView, aaModal;


	$(document).ready(function(){
		tableView = new AATableView({
			el:$('.access-areas-list-table').get(0)
		});
	})
})(jQuery);

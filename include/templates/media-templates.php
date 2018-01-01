<?php

$core = AccessAreas\Core\Core::instance();
$template = AccessAreas\Core\Template::instance();
$model = AccessAreas\Model\ModelAccessAreas::instance();

?>
<!-- List Table Row -->
<script type="text/html" id="tmpl-access-area-row">
	<td class="access-areas-name">
		<a href="#" class="edit"><strong>{{ data.model.get('title') }}</strong></a>
		<div class="row-actions">
			<a href="#" class="edit"><?php _e('Edit','wp-access-areas'); ?></a>
			<a href="#" class="delete"><?php _e('Delete','wp-access-areas'); ?></a>
		</div>
	</td>
	<td class="access-areas-scope">{{ data.model.get('scope') }}</td>
	<td class="access-areas-cap"><code>{{ data.model.get('capability') }}</code></td>
</script>

<!-- Create Acces Area Modal -->
<script type="text/html" id="tmpl-access-area-modal">
	<div class="media-modal access-area-modal">
		<div class="media-modal-content">
			<div class="media-modal-main" role="main">
				<header class="media-modal-header">
					<h1 class="title"><?php _e( 'Create Access Area', 'wp-access-areas' ); ?></h1>
					<button class="media-modal-close modal-close dashicons dashicons-no-alt">
						<span class="screen-reader-text"><?php _e( 'Close', 'wp-access-areas' ); ?></span>
					</button>
				</header>
				<article class="modal-content">
					<div class="form-control">
						<label for="title-input"><?php _e( 'Title', 'wp-access-areas' ) ?></label>
						<input class="widefat input-title" id="title-input" type="text" name="title" placeholder="<?php esc_attr_e( 'Title', 'wp-access-areas' ); ?>" />
					</div>
					<input type="hidden" name="blog_id" value="<?php echo apply_filters( 'access_areas_current_blog_id', get_current_blog_id() ); ?>" />
				</article>
				<footer class="modal-toolbar">
					<div class="inner">
						<button id="btn-ok" class="button button-primary button-large"><?php _e( 'Save', 'wp-access-areas' ); ?></button>
					</div>
				</footer>
			</div>
		</div>
	</div>
	<div class="media-modal-backdrop modal-close"></div>
</script>

<!-- Motice -->
<script type="text/html" id="tmpl-access-area-notice">
	<div id="message" class="notice notice-{{ data.model.get('type')}} {{ data.model.get('dismissible') ? 'is-dismissible' : '' }}">
		<p>{{ data.model.get('message') }}</p>
		{{{ data.model.get('dismissible') ? '<button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php _e( 'Dismiss this notice.', 'wp-access-areas' ); ?></span></button>' : '' }}}
	</div>
</script>

<!-- User Assigned Access Area -->
<script type="text/html" id="tmpl-access-area-assigned-user">
	<?php
		echo $template->user_access_area( (object) array(
			'capability'	=> '{{{ data.capability }}}',
			'blog_id'		=> '{{{ data.blog_id }}}',
			'id'			=> '{{{ data.id }}}',
			'title'			=> '{{{ data.title }}}',
		), '{{{ data.user_id }}}');
	?>
</script>


<!-- Assign to User Dialog modal -->
<script type="text/html" id="tmpl-access-area-assign-modal">
	<div class="media-modal access-area-modal">
		<div class="media-modal-content">
			<div class="media-modal-main" role="main">
				<header class="media-modal-header">
					<h1 class="title"><?php _e( 'Grant Access', 'wp-access-areas' ); ?></h1>
					<button class="media-modal-close modal-close dashicons dashicons-no-alt">
						<span class="screen-reader-text"><?php _e( 'Close', 'wp-access-areas' ); ?></span>
					</button>
				</header>
				<article class="modal-content">
					<div class="form-control">
						<?php

							$access_areas = $model->fetch_available( 'user' );

							$access_areas = apply_filters( "wpaa_access_areas_dropdown_user", $access_areas );

							// select access area
							echo $template->access_areas_dropdown( $access_areas, 'user' );
						?>
					</div>
				</article>
				<footer class="modal-toolbar">
					<div class="inner">
						<button id="btn-ok" class="button button-primary button-large"><?php _e( 'Okay', 'wp-access-areas' ); ?></button>
					</div>
				</footer>
			</div>
		</div>
	</div>
	<div class="media-modal-backdrop modal-close"></div>
</script>

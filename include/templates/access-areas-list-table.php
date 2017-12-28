<table class="widefat access-areas-list-table striped">
	<thead>
		<tr>
			<th class="access-areas-name"><?php _e( 'Name', 'wp-access-areas' ); ?></th>
			<th class="access-areas-scope"><?php _e( 'Scope', 'wp-access-areas' ); ?></th>
			<th class="access-areas-cap"><?php _e( 'WP Capability', 'wp-access-areas' ); ?></th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td colspan="3">
				<a class="button button-secondary add" href="#"><?php _e( 'Add Access Area', 'wp-access-areas' ); ?></a>
			</td>
		</tr>
	</tfoot>
	<tbody class="rows access-areas-entries">
		<tr class="placeholder-row">
			<td class="access-areas-blank-state" colspan="3"><p><?php _e( 'No Access Areas yet...', 'wp-access-areas' ) ?></p></td>
		</tr>
	</tbody>
</table>

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

<script type="text/html" id="tmpl-access-area-notice">
	<div id="message" class="notice notice-{{ data.model.get('type')}} {{ data.model.get('dismissible') ? 'is-dismissible' : '' }}">
		<p>{{ data.model.get('message') }}</p>
		{{{ data.model.get('dismissible') ? '<button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php _e( 'Dismiss this notice.', 'wp-access-areas' ); ?></span></button>' : '' }}}
	</div>
</script>

<?php

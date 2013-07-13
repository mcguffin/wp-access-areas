<?php


/**
* @package WPUndisclosed
* @version 1.0
*/

// ----------------------------------------
//	This class provides an UI for assining 
//	WP-Roles and user-labels to posts.
// ----------------------------------------

if ( ! class_exists('UndisclosedEditPost') ) :
class UndisclosedEditPost {

	static function init() {
		if ( is_admin() ) {
			// edit post
			add_filter('wp_insert_post_data', array(__CLASS__ , 'edit_post') , 10 ,2 );
			add_action('add_meta_boxes' , array( __CLASS__ , 'add_meta_boxes' ) );

			// list views
			add_filter('manage_posts_columns' , array(__CLASS__ , 'add_disclosure_column'));
			add_filter('manage_posts_custom_column' , array(__CLASS__ , 'manage_disclosure_column') , 10 ,2 );

			add_filter('manage_pages_columns' , array(__CLASS__ , 'add_disclosure_column'));
			add_filter('manage_pages_custom_column' , array(__CLASS__ , 'manage_disclosure_column') , 10 ,2 );
		}
	}
	
	// --------------------------------------------------
	// add meta boxes to all post content
	// --------------------------------------------------
	static function add_meta_boxes() {
		global $wp_post_types;
		foreach ( array_keys($wp_post_types) as $post_type ) {
			add_meta_box( 'post-disclosure' , __('Restrictions','wpundisclose') , array(__CLASS__,'disclosure_box_info') , $post_type , 'side' , 'high' );
		}
	}
	// --------------------------------------------------
	// saving post
	// --------------------------------------------------
	static function edit_post( $data, $postarr) {
		$data['post_view_cap']		= isset($postarr['post_view_cap']) ? $postarr['post_view_cap'] : 'exist';
		/* // future use
		$data['post_edit_cap']		= isset($postarr['post_edit_cap']) ? $postarr['post_edit_cap'] : 'exist';
		*/
		$data['post_comment_cap']	= isset($postarr['post_comment_cap']) ? $postarr['post_comment_cap'] : 'exist';
		if ( $data['post_comment_cap'] == 'exist' )
			$data['comment_status'] = 'open';
		else 
			$data['comment_status'] = 'closed';
		return $data;
		
	}
	
	// --------------------------------------------------
	// edit post - the meta box
	// --------------------------------------------------
	static function disclosure_box_info() {
		$post = get_post(get_the_ID());

		// <select> with - Evereybody, Logged-in only, list WP-Roles, list discosure-groups
		$current_user = wp_get_current_user();
		$roles = new WP_Roles();
		$rolenames = $roles->get_names();
		$groups = UndisclosedUserlabel::get_label_array( );

		$user_role_caps = $roles->get_role(wp_get_current_user()->roles[0])->capabilities;
		$is_admin = current_user_can( 'administrator' );
		// 
		/*
		(1) inject css: 
		#post-disclosure .inside{
			margin:0;
			padding:0
		}
		(2) don't show optgrous without options
		*/
		?><div class="disclosure-view-select misc-pub-section">
			<label for="select-disclosure"><?php _e( 'Visible to:' , 'wpundisclose') ?></label>
			<select id="select-disclosure" name="post_view_cap">
				<option value="exist" <?php selected($post->post_view_cap , 'exist') ?>><?php _e( 'WordPress default' , 'wpundisclose' ) ?></option>
				<option value="read" <?php selected($post->post_view_cap , 'read') ?>><?php _e( 'Blog users' , 'wpundisclose' ) ?></option>
			
				<optgroup label="<?php _e( 'WordPress roles' , 'wpundisclose') ?>">
				<?php foreach ($rolenames as $role=>$rolename) {
					if ( !current_user_can( $role ) )
						continue;
					?>
					<option value="<?php echo $role ?>" <?php selected($post->post_view_cap , $role) ?>><?php _ex( $rolename, 'User role' ) ?></option>
				<?php } ?>
				</optgroup>

				<optgroup label="<?php _e( 'Users with label' , 'wpundisclose') ?>">
				<?php foreach ($groups as $group=>$groupname) { 
					if ( !current_user_can($group) && !$is_admin )
						continue;
					?>
					<option value="<?php echo $group ?>" <?php selected($post->post_view_cap , $group) ?>><?php _e( $groupname , 'wpundisclose' ) ?></option>
				<?php } ?>
				</optgroup>
			</select>
		</div><?php
/* // for future use
		?><div class="disclosure-edit-select misc-pub-section">
			<label for="select-disclosure"><?php _e( 'Editable for:' , 'wpundisclose') ?></label>
			<select id="select-disclosure" name="post_edit_cap">
				<option value="exist" <?php selected($post->post_edit_cap , 'exist') ?>><?php _e( 'WordPress default' , 'wpundisclose' ) ?></option>
				<option value="read" <?php selected($post->post_edit_cap , 'read') ?>><?php _e( 'Blog users' , 'wpundisclose' ) ?></option>
			
				<optgroup label="<?php _e( 'WordPress roles' , 'wpundisclose') ?>">
				<?php foreach ($rolenames as $role=>$rolename) {
					if ( !current_user_can( $role ) )
						continue;
					?>
					<option value="<?php echo $role ?>" <?php selected($post->post_edit_cap , $role) ?>><?php _ex( $rolename, 'User role' ) ?></option>
				<?php } ?>
				</optgroup>

				<optgroup label="<?php _e( 'Users with label' , 'wpundisclose') ?>">
				<?php foreach ($groups as $group=>$groupname) { 
					if ( !current_user_can($group) && !$is_admin )
						continue;
					?>
					<option value="<?php echo $group ?>" <?php selected($post->post_edit_cap , $group) ?>><?php _e( $groupname , 'wpundisclose' ) ?></option>
				<?php } ?>
				</optgroup>
			</select>
		</div><?php
*/

//* // for future use
		?><div class="disclosure-comment-select misc-pub-section">
			<label for="select-disclosure"><?php _e( 'Who can comment:' , 'wpundisclose') ?></label>
			<select id="select-disclosure" name="post_comment_cap">
				<option value="exist" <?php selected($post->post_comment_cap , 'exist') ?>><?php _e( 'WordPress default' , 'wpundisclose' ) ?></option>
				<option value="read" <?php selected($post->post_comment_cap , 'read') ?>><?php _e( 'Blog users' , 'wpundisclose' ) ?></option>
			
				<optgroup label="<?php _e( 'WordPress roles' , 'wpundisclose') ?>">
				<?php foreach ($rolenames as $role=>$rolename) {
					var_dump($role);
					if ( !current_user_can( $role ) )
						continue;
					?>
					<option value="<?php echo $role ?>" <?php selected($post->post_comment_cap , $role) ?>><?php _ex( $rolename, 'User role' ) ?></option>
				<?php } ?>
				</optgroup>

				<optgroup label="<?php _e( 'Users with label' , 'wpundisclose') ?>">
				<?php foreach ($groups as $group=>$groupname) { 
					if ( !current_user_can($group) && !$is_admin )
						continue;
					?>
					<option value="<?php echo $group ?>" <?php selected($post->post_comment_cap , $group) ?>><?php _e( $groupname , 'wpundisclose' ) ?></option>
				<?php } ?>
				</optgroup>
			</select>
		</div><?php
//*/
	}
	
	// --------------------------------------------------
	// admin list views
	// --------------------------------------------------
	static function add_disclosure_column($columns) {
		$cols = array();
		foreach ($columns as $k=>$v) {
			$cols[$k] = $v;
			if ($k=='author') 
				$cols['disclosure'] = __('Visible to','wpundisclose');
		}
		return $cols;
	}
	static function manage_disclosure_column($column, $post_ID) {
		if ( $column != 'disclosure')
			return;
		$roles = new WP_Roles();
		$names = array_merge(array('exist' => __( 'Everybody' , 'wpundisclose' ), 'read' => __( 'Blog users' , 'wpundisclose' )) , UndisclosedUserlabel::get_label_array( ), $roles->get_names());
		$names[''] = $names['exist'];
		$val = get_post($post_ID)->post_view_cap;
		_e($names[$val]);
	}

}
UndisclosedEditPost::init();
endif;




?>
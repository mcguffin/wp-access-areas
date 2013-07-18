<?php
/**
* @package WP_AccessAreas
* @version 1.0.0
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
		add_action( 'load-post.php' , array( __CLASS__ , 'load_style' ) );
		add_action( 'load-post-new.php' , array( __CLASS__ , 'load_style' ) );
	}
	static function load_style() {
		wp_enqueue_style( 'disclosure-admin' );
	}
	
	// --------------------------------------------------
	// add meta boxes to all post content
	// --------------------------------------------------
	static function add_meta_boxes() {
		global $wp_post_types;
		foreach ( array_keys($wp_post_types) as $post_type )
			add_meta_box( 'post-disclosure' , __('Access','wpundisclosed') , array(__CLASS__,'disclosure_box_info') , $post_type , 'side' , 'high' );
	}
	// --------------------------------------------------
	// saving post
	// --------------------------------------------------
	static function edit_post( $data, $postarr ) {
		if ( $data['post_status'] == 'auto-draft' )
			return $data;

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
			<label for="select-disclosure"><strong><?php _e( 'Who can read:' , 'wpundisclosed') ?></strong></label><br />
			<select id="select-disclosure" name="post_view_cap">
				<option value="exist" <?php selected($post->post_view_cap , 'exist') ?>><?php _e( 'WordPress default' , 'wpundisclosed' ) ?></option>
				<option value="read" <?php selected($post->post_view_cap , 'read') ?>><?php _e( 'Blog users' , 'wpundisclosed' ) ?></option>
			
				<optgroup label="<?php _e( 'WordPress roles' , 'wpundisclosed') ?>">
				<?php foreach ($rolenames as $role=>$rolename) {
					global $_asssas;
					if ( !self::_user_can_role( $role , $user_role_caps ) )
						continue;
					?>
					<option value="<?php echo $role ?>" <?php selected($post->post_view_cap , $role) ?>><?php _ex( $rolename, 'User role' ) ?></option>
				<?php } ?>
				</optgroup>

				<optgroup label="<?php _e( 'Users with Access to' , 'wpundisclosed') ?>">
				<?php foreach ($groups as $group=>$groupname) { 
					if ( ! current_user_can($group) && ! $is_admin )
						continue;
					?>
					<option value="<?php echo $group ?>" <?php selected($post->post_view_cap , $group) ?>><?php _e( $groupname , 'wpundisclosed' ) ?></option>
				<?php } ?>
				</optgroup>
			</select>
		</div><?php
/* // for future use
		?><div class="disclosure-edit-select misc-pub-section">
			<label for="select-disclosure"><?php _e( 'Editable for:' , 'wpundisclosed') ?></label>
			<select id="select-disclosure" name="post_edit_cap">
				<option value="exist" <?php selected($post->post_edit_cap , 'exist') ?>><?php _e( 'WordPress default' , 'wpundisclosed' ) ?></option>
				<option value="read" <?php selected($post->post_edit_cap , 'read') ?>><?php _e( 'Blog users' , 'wpundisclosed' ) ?></option>
			
				<optgroup label="<?php _e( 'WordPress roles' , 'wpundisclosed') ?>">
				<?php foreach ($rolenames as $role=>$rolename) {
					if ( !current_user_can( $role ) )
						continue;
					?>
					<option value="<?php echo $role ?>" <?php selected($post->post_edit_cap , $role) ?>><?php _ex( $rolename, 'User role' ) ?></option>
				<?php } ?>
				</optgroup>

				<optgroup label="<?php _e( 'Users with Access to' , 'wpundisclosed') ?>">
				<?php foreach ($groups as $group=>$groupname) { 
					if ( !current_user_can($group) && !$is_admin )
						continue;
					?>
					<option value="<?php echo $group ?>" <?php selected($post->post_edit_cap , $group) ?>><?php _e( $groupname , 'wpundisclosed' ) ?></option>
				<?php } ?>
				</optgroup>
			</select>
		</div><?php
*/

//* // for future use
		?><div class="disclosure-comment-select misc-pub-section">
			<label for="select-disclosure"><strong><?php _e( 'Who can comment:' , 'wpundisclosed') ?></strong></label><br />
			<select id="select-disclosure" name="post_comment_cap">
				<option value="exist" <?php selected($post->post_comment_cap , 'exist') ?>><?php _e( 'WordPress default' , 'wpundisclosed' ) ?></option>
				<option value="read" <?php selected($post->post_comment_cap , 'read') ?>><?php _e( 'Blog users' , 'wpundisclosed' ) ?></option>
			
				<optgroup label="<?php _e( 'WordPress roles' , 'wpundisclosed') ?>">
				<?php foreach ($rolenames as $role=>$rolename) {
					if ( !self::_user_can_role( $role , $user_role_caps ) )
						continue;
					?>
					<option value="<?php echo $role ?>" <?php selected($post->post_comment_cap , $role) ?>><?php _ex( $rolename, 'User role' ) ?></option>
				<?php } ?>
				</optgroup>

				<optgroup label="<?php _e( 'Users with Access to' , 'wpundisclosed') ?>">
				<?php foreach ($groups as $group=>$groupname) { 
					if ( !current_user_can($group) && !$is_admin )
						continue;
					?>
					<option value="<?php echo $group ?>" <?php selected($post->post_comment_cap , $group) ?>><?php _e( $groupname , 'wpundisclosed' ) ?></option>
				<?php } ?>
				</optgroup>
			</select>
		</div><?php
//*/
	}
	static function _user_can_role( $role , $user_role_caps ) {
		$roles = new WP_Roles();
		if ($roles->is_role($role))
			return 0 == count(array_diff_assoc(  $roles->get_role( $role )->capabilities , $user_role_caps));
		return false;
	}
	
	// --------------------------------------------------
	// admin list views
	// --------------------------------------------------
	static function add_disclosure_column($columns) {
		$cols = array();
		foreach ($columns as $k=>$v) {
			$cols[$k] = $v;
			if ($k=='author') 
				$cols['disclosure'] = __('Visible to','wpundisclosed');
		}
		return $cols;
	}
	static function manage_disclosure_column($column, $post_ID) {
		if ( $column != 'disclosure')
			return;
		$roles = new WP_Roles();
		$names = array_merge(array('exist' => __( 'Everybody' , 'wpundisclosed' ), 'read' => __( 'Blog users' , 'wpundisclosed' )) , UndisclosedUserlabel::get_label_array( ), $roles->get_names());
		$names[''] = $names['exist'];
		$val = get_post($post_ID)->post_view_cap;
		_e($names[$val]);
	}

}
UndisclosedEditPost::init();
endif;




?>
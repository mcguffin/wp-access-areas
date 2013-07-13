<?php

// ----------------------------------------
//	This class provides an UI to assign Userlabels to Users.
// ----------------------------------------

if ( ! class_exists( 'UndisclosedUsers' ) ) :
class UndisclosedUsers {

	static function init( ) {
		if ( is_admin() ) {
			add_action( 'admin_init' , array( __CLASS__ , 'admin_init' ) );
			add_filter('manage_users_columns' , array(__CLASS__ , 'add_userlabels_column'));
			add_filter('manage_users_custom_column' , array(__CLASS__ , 'manage_userlabels_column') , 10 ,3 );
		}
		add_action('add_user_to_blog',array(__CLASS__,'add_user_to_blog'),10,3);
	}
	
	// --------------------------------------------------
	// user editing
	// --------------------------------------------------
	static function load_user_editor(){
		wp_register_script( 'disclosure' , plugins_url('js/disclosure.js', __FILE__), array('jquery') );
		wp_enqueue_script( 'disclosure' );
	}
	static function profile_update( $user_id, $old_user_data ) {
		if ( ! current_user_can( 'promote_users' ) || ! isset( $_POST['userlabels'] ) ) 
			return;
		
		// sanitize
		$label_data = array();
		foreach ($_POST['userlabels'] as $label_id => $add ) {
			$label_data[ intval($label_id) ] = (bool) $add;
		}
		$user = new WP_User( $user_id );
		$global_label_data = array();
		foreach ($label_data as $label_id => $add) {
			$label = UndisclosedUserLabel::get_userlabel( $label_id );
			if ( ! $label->blog_id && $add )
				$global_label_data[] = intval($label->ID);
			// network or 
			if ( is_multisite() && ! $label->blog_id ) { // network
				global $wpdb;
				$blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
				foreach ( $blogids as $blog_id ) {
					switch_to_blog( $blog_id );
					$user->for_blog( $blog_id );
					self::_set_cap_for_user( $label->capability , $user , $add );
				}
				restore_current_blog();
			} else { // blog only
				self::_set_cap_for_user( $label->capability , $user , $add );
			}
		}
		update_user_meta($user_id, 'undisclosed_global_capabilities' , $global_label_data );
	}
	
	static function add_user_to_blog( $user_id , $role , $blog_id ) {
		switch_to_blog( $label->blog_id );
		$label_IDs = get_user_meta($user_id, 'undisclosed_global_capabilities' , true );
		$user = new WP_User( $user_id );
		foreach ( $label_IDs as $label_id ) {
			$label = UndisclosedUserLabel::get_userlabel( $label_id );
			self::_set_cap_for_user( $label->capability , &$user , true );
		}
		restore_current_blog();
	}
	

	private static function _set_cap_for_user( $capability , &$user , $add ) {
		if ( $add ) 
			$user->add_cap( $capability , true );
		else 
			$user->remove_cap( $capability );
	}
	static function personal_options( $profileuser ) {
		// IS_PROFILE_PAGE : self or other
		if ( ! current_user_can( 'promote_users' ) ) 
			return;
		$labels = UndisclosedUserLabel::get_available_userlabels( );
		?><h3><?php _e( 'User-Labels' , 'disclosure' ) ?></h3><?php
		?><table class="form-table"><?php

		?><tr><th><label for="set-disclosure-groups"><?php _e( 'Set User-Labels' , 'disclosure' ) ?></label></th><?php
		?><td id="disclosure-group-items"><?php
			foreach ( $labels as $label ) {
				?><span class="disclosure-group-item"><?php
				?><input type="hidden" name="userlabels[<?php echo $label->ID ?>]" value="0" /><?php
				
				?><input id="cap-<?php echo $label->capability ?>" type="checkbox" name="userlabels[<?php echo $label->ID ?>]" value="1" <?php checked( $profileuser->has_cap( $label->capability ) , true ) ?> /><?php
				?><label for="cap-<?php echo $label->capability ?>">  <?php echo $label->cap_title ?> <?php echo !$label->blog_id ? __('(Network)','wpundisclosed'):''; ?></label></span><?php
			}
		?></td></tr><?php

		?></table><?php
	}
	
	

	// --------------------------------------------------
	// general actions
	// --------------------------------------------------
	static function admin_init() {
		if ( current_user_can( 'promote_users' ) ) {
			add_action( 'profile_update' , array(__CLASS__ , 'profile_update') , 10, 2 );
			add_action( 'edit_user_profile' , array( __CLASS__ , 'personal_options' ) );
			add_action( 'show_user_profile' , array( __CLASS__ , 'personal_options' ) );
			add_action( 'load-user-edit.php' , array( __CLASS__ , 'load_user_editor' ) );
		}
		add_filter( 'additional_capabilities_display' , '__return_false' );
	}


	
	// --------------------------------------------------
	// user admin list view
	// --------------------------------------------------
	static function add_userlabels_column($columns) {
		$cols = array();
		foreach ($columns as $k=>$v) {
			$cols[$k] = $v;
			if ($k=='role') 
				$cols['labels'] = __('Labels','wpundisclosed');
		}
		return $cols;
	}
	static function manage_userlabels_column($wtf, $column, $user_ID) {
		if ( $column != 'labels')
			return;
				
		$ugroups = array();
		
		$labels = UndisclosedUserLabel::get_available_userlabels( );
		
		$user = new WP_User( $user_ID );
		foreach ($labels as $label)
			if ( $user->has_cap( $label->capability ) )
				$ugroups[] = $label->cap_title . (!$label->blog_id ? ' '. __('(*)','wpundisclosed'):'');
		return implode("<br />", $ugroups);
		// echo all groups by user.
	}
}
UndisclosedUsers::init();
endif;

?>
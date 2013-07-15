<?php

// ----------------------------------------
//	This class provides an UI to assign Userlabels to Users.
// ----------------------------------------

if ( ! class_exists( 'UndisclosedUsers' ) ) :
class UndisclosedUsers {

	static function init( ) {
		if ( is_admin() ) {
			add_action( 'admin_init' , array( __CLASS__ , 'admin_init' ) );
			add_filter('wpmu_users_columns' , array(__CLASS__ , 'add_userlabels_column'));
			add_filter('manage_users_columns' , array(__CLASS__ , 'add_userlabels_column'));
			add_filter('manage_users_custom_column' , array(__CLASS__ , 'manage_userlabels_column') , 10 ,3 );
		}
		add_action('add_user_to_blog',array(__CLASS__,'add_user_to_blog'),10,3);
	}
	
	// --------------------------------------------------
	// general actions
	// --------------------------------------------------
	static function admin_init() {
		if ( current_user_can( 'promote_users' ) ) {
			add_action( 'profile_update' , array(__CLASS__ , 'profile_update') , 10, 2 );
			add_action( 'edit_user_profile' , array( __CLASS__ , 'personal_options' ) );
			add_action( 'show_user_profile' , array( __CLASS__ , 'personal_options' ) );
			add_action( 'load-profile.php' , array( __CLASS__ , 'load_user_editor' ) );
			add_action( 'load-user-edit.php' , array( __CLASS__ , 'load_user_editor' ) );

			add_action( 'load-users.php' , array( __CLASS__ , 'load_user_editor' ) );
			add_action( 'load-user-edit.php' , array( __CLASS__ , 'load_user_editor' ) );
			
			
			add_filter('views_users' , array( __CLASS__ , 'table_views' ) );
		//	add_filter('views_users-network' , array( __CLASS__ , 'table_views' ) );
		}
		add_filter( 'additional_capabilities_display' , '__return_false' );
	}

	
	// --------------------------------------------------
	// user editing
	// --------------------------------------------------
	static function load_user_editor() {
		wp_enqueue_style( 'disclosure-admin' );
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
				switch_to_blog( $label->blog_id );
				$user->for_blog( $label->blog_id );
				self::_set_cap_for_user( $label->capability , $user , $add );
			}
		}
		update_user_meta($user_id, 'undisclosed_global_capabilities' , $global_label_data );
		restore_current_blog();
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
		?><h3><?php _e( 'User-Labels' , 'wpundisclosed' ) ?></h3><?php
		?><table class="form-table" id="disclosure-group-items"><?php
		
		$labelrows = array( 
								__( 'Network User-Labels' , 'wpundisclosed' )	=> array( 'network' => true ,	'labels' => UndisclosedUserLabel::get_network_userlabels()  , ), 
			 );
		if ( ! is_network_admin() )
			$labelrows[ __( 'Local User-Labels' , 'wpundisclosed' ) ] = array( 'network' => false ,		'labels' => UndisclosedUserLabel::get_blog_userlabels() , );
		
		foreach ( $labelrows as $row_title => $value ) {
			extract( $value );
			
			if ( empty($labels) ) 
				continue;
			
			?><tr class="<?php echo $network ? 'undisclosed-network' : 'undisclosed-local' ?>">
				<th><?php

				echo $row_title;

				if ($network) {
					?><span class="icon-undisclosed-network icon16"></span><?php
				} else {
					?><span class="icon-undisclosed-local icon16"></span><?php
				}

				?></th>
				<td><?php
				foreach ( $labels as $label ) {
					?><span class="disclosure-label-item"><?php
						?><input type="hidden" name="userlabels[<?php echo $label->ID ?>]" value="0" /><?php
				
						?><input id="cap-<?php echo $label->capability ?>" type="checkbox" name="userlabels[<?php echo $label->ID ?>]" value="1" <?php checked( $profileuser->has_cap( $label->capability ) , true ) ?> /><?php
						?><label for="cap-<?php echo $label->capability ?>">  <?php echo $label->cap_title ?></label><?php
					?></span><?php
				}
			?></td></tr><?php
		
		}
		?></table><?php
	}
	
	


	
	// --------------------------------------------------
	// user admin list view
	// --------------------------------------------------
	
	static function table_views( $views ) {
		$ret = '';
		$ret .= self::_listtable_label_select( UndisclosedUserLabel::get_blog_userlabels() , 'local' );
		$ret .= self::_listtable_label_select( UndisclosedUserLabel::get_network_userlabels() , 'network');

		if ( $ret )
			$views['labels'] = '<strong>'.__('User-Label:').' </strong>' . $ret;
		return $views;
	}
	private static function _listtable_label_select( $labels , $slug ) {
		if (! count( $labels ) )
			return '';
		$ret = '';
		$current_label = isset($_GET['role']) ? $_GET['role'] : '';
		$ret .= '<form class="select-user-label-form" method="get"><label for="select-user-label"><span class="icon-undisclosed-'.$slug.' icon16"></span>';
		$ret .= '<select id="select-user-label" onchange="this.form.submit()" name="role">';
		$ret .= sprintf('<option value="%s">%s</option>' , '' , __('(None)'));
		foreach ( $labels as $label ) {
			$ret .= sprintf('<option ' . selected($current_label,$label->capability,false) . ' value="%s">%s</option>' , $label->capability , $label->cap_title);
		}
		$ret .= '</select>';
		$ret .= '</form>';
		return $ret;
	}
	
	static function add_userlabels_column($columns) {

		$columns['labels'] = __('Labels','wpundisclosed');
		return $columns;
	}
	static function manage_userlabels_column($wtf, $column, $user_ID) {
		if ( $column != 'labels')
			return;
				
		$ugroups = array();
		
		$labels = UndisclosedUserLabel::get_available_userlabels( );
		
		$user = new WP_User( $user_ID );
			foreach ($labels as $label) {
				if ( $user->has_cap( $label->capability ) ) {
					$icon =  $label->blog_id ? '<span class="icon-undisclosed-local icon16"></span>' : '<span class="icon-undisclosed-network icon16"></span>';
					$ugroups[] = '<span class="disclosure-label-item">' . $icon . $label->cap_title . '</span>';
				}
			}
		if ( count( $ugroups ) ) {
		}
		return '<div class="disclosure-labels">'.implode("", $ugroups) . '</div>';
		// echo all groups by user.
	}
}
UndisclosedUsers::init();
endif;

?>
<?php
/**
* @package WP_AccessAreas
* @version 1.0.0
*/ 

// ----------------------------------------
//	This class provides an UI to assign Userlabels to Users.
// ----------------------------------------

if ( ! class_exists( 'UndisclosedUsers' ) ) :
class UndisclosedUsers {

	static function init( ) {
		if ( is_admin() ) {
			add_action( 'admin_init' , array( __CLASS__ , 'admin_init' ) );
			if ( is_accessareas_active_for_network() )
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
			
			// css
			add_action( 'load-users.php' , array( __CLASS__ , 'enqueue_style' ) );
			
			// css + js
			add_action( 'load-profile.php' , array( __CLASS__ , 'enqueue_script_style' ) );
			add_action( 'load-user-edit.php' , array( __CLASS__ , 'enqueue_script_style' ) );

//			add_action( 'admin_enqueue_scripts', array( __CLASS__ , 'admin_enqueue_user_scripts' ) );
			// ajax
			add_action( 'wp_ajax_add_accessarea', array( __CLASS__ , 'ajax_add_access_area' ) );
			
			add_filter('views_users' , array( __CLASS__ , 'table_views' ) );
		}
		add_filter( 'additional_capabilities_display' , '__return_false' );
	}
	
	static function enqueue_script_style() {
		self::enqueue_style();
		self::enqueue_script();
	}
	static function enqueue_style() {
		add_action('admin_enqueue_scripts' , array(__CLASS__,'load_style'));
	}
	static function enqueue_script() {
		add_action('admin_enqueue_scripts' , array(__CLASS__,'load_edit_script'));
	}
	static function load_edit_script() {
		wp_enqueue_script( 'disclosure-admin-user-ajax');
	} 
	static function load_style() {
		wp_enqueue_style( 'disclosure-admin' );
	}
	
	
	/*
	static function admin_enqueue_user_scripts() {
	}
	*/
	
	// --------------------------------------------------
	// ajax adding access areas
	// --------------------------------------------------
	static function ajax_add_access_area() {
		if ( wp_verify_nonce(@$_POST['_wp_ajax_nonce'] , 'userlabel-new' ) && current_user_can( 'promote_users' ) ) {
			if ( ( !$_POST['blog_id'] && !is_super_admin() ) || ( $_POST['blog_id'] && $_POST['blog_id'] != get_current_blog_id() ) ) {
				?><span class="disclosure-label-item error"><?php _e('Insufficient privileges.','wpundisclosed'); ?></span><?php  // throw_error: insufficient privileges
			} else {
				$create_id = UndisclosedUserlabel::create_userlabel( array('cap_title' => $_POST['cap_title'], 'blog_id' => $_POST['blog_id'] ) );
			
				if ( $create_id ) {
					$label = UndisclosedUserlabel::get_userlabel( $create_id );
					self::_select_label_formitem( $label , true );
				} else {
					switch (UndisclosedUserlabel::what_went_wrong()) {
						case 4: // Error: area exists
							?><span class="disclosure-label-item error"><?php _e('Access Area exists.','wpundisclosed'); ?></span><?php  // throw_error: insufficient privileges
							break;
					};
				}
			}
		} else {
			?><span class="disclosure-label-item error"><?php _e('Insufficient privileges.','wpundisclosed'); ?></span><?php  // throw_error: insufficient privileges
			// throw_error: insufficient privileges
		}
		die();
	}
	

	// --------------------------------------------------
	// user editing
	// --------------------------------------------------
	static function profile_update( $user_id, $old_user_data ) {
		if ( ! current_user_can( 'promote_users' ) || ! isset( $_POST['userlabels'] ) ) 
			return;
		
		// sanitize
		global $wpdb;
		
		if ( is_multisite() ) {
			$blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
			$current_blog_id = get_current_blog_id();
		}
		$label_data = array();
		foreach ($_POST['userlabels'] as $label_id => $add ) {
			$label_data[ intval($label_id) ] = (bool) $add;
		}
		$user = new WP_User( $user_id );
		$global_label_data = array();
		foreach ($label_data as $label_id => $add) {
			$label = UndisclosedUserLabel::get_userlabel( $label_id );
			if ( is_multisite() && ! $label->blog_id ) { // global
				if ( $add )
					$global_label_data[] = $label->capability;
				foreach ( $blogids as $blog_id ) {
					if ( is_user_member_of_blog( $user_id , $blog_id ) ) {
						switch_to_blog( $blog_id );
						$user->for_blog( $blog_id );
						self::_set_cap_for_user( $label->capability , $user , $add );
					}
				}
				restore_current_blog();
			} else { // local or single page 
				if ( is_multisite() ) {
					switch_to_blog( $current_blog_id );
					$user->for_blog( $current_blog_id );
				}
				self::_set_cap_for_user( $label->capability , $user , $add );
			}
		}
		if ( is_multisite() ) {
			update_user_meta($user_id, WPUND_GLOBAL_USERMETA_KEY , $global_label_data );
			switch_to_blog( $current_blog_id );
			$user->for_blog( $current_blog_id );
		}
	}
	
	static function add_user_to_blog( $user_id , $role , $blog_id ) {
		switch_to_blog( $blog_id );
		$label_caps = get_user_meta($user_id, WPUND_GLOBAL_USERMETA_KEY , true );
		if ( ! $label_caps )
			return;
		$user = new WP_User( $user_id );
		foreach ( $label_caps as $cap ) {
			self::_set_cap_for_user( $cap , $user , true );
		}
		restore_current_blog();
	}
	

	private static function _set_cap_for_user( $capability , &$user , $add ) {
		// prevent blogadmin from granting network permissions he does not own himself.
		$network = ! wpaa_is_local_cap( $capability );
		$can_grant = current_user_can( $capability ) || ! $network;
		$is_change = ($add && ! $user->has_cap( $capability )) || (!$add && $user->has_cap( $capability ));
		if ( ! $can_grant && $is_change )
			wp_die( __('You do not have permission to do this.' , 'wpundisclosed' ) );
		if ( $add && $is_change ) 
			$user->add_cap( $capability , true );
		else if ( ! $add && $is_change ) 
			$user->remove_cap( $capability );
	}
	static function personal_options( $profileuser ) {
		// IS_PROFILE_PAGE : self or other
		if ( ! current_user_can( 'promote_users' ) || (is_network_admin() && ! is_accessareas_active_for_network() ) ) 
			return;
		$labels = UndisclosedUserLabel::get_available_userlabels();
		
		?><h3><?php _e( 'Access Areas' , 'wpundisclosed' ) ?></h3><?php
		?><table class="form-table" id="disclosure-group-items"><?php
		
		$labelrows = array();
		// wtf happens on single install?
		if ( ! is_network_admin() ) {
			$labelrows[ __( 'Grant Access' , 'wpundisclosed' ) ] = array( 
				'network' => false ,
				'labels' => UndisclosedUserLabel::get_blog_userlabels() ,
				'can_ajax_add' => current_user_can( 'promote_users' ),
			);
		}
		if ( ( is_network_admin() || is_super_admin() ) && is_accessareas_active_for_network() ) {
			$labelrows[ __( 'Grant Network-Wide Access' , 'wpundisclosed' )] = array( 
				'network' => true ,	
				'labels' => UndisclosedUserLabel::get_network_userlabels()  , 
				'can_ajax_add' => is_network_admin() || is_super_admin(),
			);
		}
		foreach ( $labelrows as $row_title => $value ) {
			extract( $value );
			
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
					$can_grant = current_user_can( $label->capability ) || ! $network;
					$user_has_cap = $profileuser->has_cap( $label->capability );
					self::_select_label_formitem( $label , $user_has_cap , $can_grant );
				}
				
				if ( $can_ajax_add )
					self::_ajax_add_area_formitem( $network ? 0 : get_current_blog_id() );
				
			?></td></tr><?php
		
		}
		?></table><?php
	}
	private static function _select_label_formitem( $label , $checked , $enabled = true ) {
		$attr_disabled = $enabled ? '' : ' disabled="disabled" ';
		$item_class = array('disclosure-label-item');
		if (!$enabled)
			$item_class[] = 'disabled';
		?><span class="<?php echo  implode(' ',$item_class)?>"><?php
			?><input type="hidden" name="userlabels[<?php echo $label->ID ?>]" value="0" /><?php
			?><input <?php echo $attr_disabled ?> id="cap-<?php echo $label->capability ?>" type="checkbox" name="userlabels[<?php echo $label->ID ?>]" value="1" <?php checked( $checked , true ) ?> /><?php
			?><label for="cap-<?php echo $label->capability ?>">  <?php echo $label->cap_title ?></label><?php
			if ( ! $enabled ) {
				?><input type="hidden" name="userlabels[<?php echo $label->ID ?>]" value="<?php echo (int) $checked  ?>" /><?php
			}
		?></span><?php
	}
	
	private static function _ajax_add_area_formitem( $blog_id ) {
		?><span class="disclosure-label-item ajax-add-item"><?php
			wp_nonce_field( 'userlabel-new' , '_wp_ajax_nonce' );
			?><input type="hidden" name="blog_id" value="<?php echo $blog_id; ?>" /><?php
			?><input class="cap-add" type="text" name="cap_title" placeholder="<?php _ex('Add New','access area','wpundisclosed') ?>" /><?php
			
			?><a href="#" class="cap-add-submit button"><?php _e('+') ?></a><?php
		?></span><?php
	}
	
	
	
	
	// --------------------------------------------------
	// user admin list view
	// --------------------------------------------------
	
	static function table_views( $views ) {
		$ret = '';
		$ret .= self::_listtable_label_select( UndisclosedUserLabel::get_blog_userlabels() , 'local' );
		if ( is_accessareas_active_for_network() )
			$ret .= self::_listtable_label_select( UndisclosedUserLabel::get_network_userlabels() , 'network');
		if ( $ret )
			$views['labels'] = '<strong>'.__('Access Areas:','wpundisclosed').' </strong>' . $ret;
		return $views;
	}
	private static function _listtable_label_select( $labels , $slug ) {
		if (! count( $labels ) )
			return '';
		$ret = '';
		$current_label = isset($_GET['role']) ? $_GET['role'] : '';
		$ret .= '<form class="select-user-label-form" method="get">';
		$ret .= '<label for="select-user-label-'.$slug.'">';
		$ret .= '<span class="icon-undisclosed-'.$slug.' icon16"></span>';
		$ret .= '<select id="select-user-label-'.$slug.'" onchange="this.form.submit()" name="role">';
		$ret .= sprintf('<option value="%s">%s</option>' , '' , __('(None)'));
		foreach ( $labels as $label ) {
			$ret .= sprintf('<option %s value="%s">%s</option>' , selected($current_label,$label->capability,false) , $label->capability , $label->cap_title);
		}
		$ret .= '</select>';
		$ret .= '</label>';
		$ret .= '</form>';
		return $ret;
	}
	
	static function add_userlabels_column($columns) {

		$columns['labels'] = __('Access Areas','wpundisclosed');
		return $columns;
	}
	static function manage_userlabels_column($column_content, $column, $user_ID) {
		if ( $column != 'labels')
			return $column_content;
				
		$ugroups = array();
		
		$labels = UndisclosedUserLabel::get_available_userlabels( );
		$user = new WP_User( $user_ID );
		if ( ( is_multisite() && is_super_admin( $user_ID ) ) || ( ! is_multisite() && $user->has_cap( 'administrator' )) )
			return '<div class="disclosure-labels"><span class="disclosure-label-item access-all-areas"><span class="icon-undisclosed-network icon16"></span>' . __('Everywhere') . '</span></div>';
		
		
		foreach ($labels as $label) {
			if ( $user->has_cap( $label->capability ) ) {
				$icon =  $label->blog_id ? '<span class="icon-undisclosed-local icon16"></span>' : '<span class="icon-undisclosed-network icon16"></span>';
				$ugroups[] = '<span class="disclosure-label-item">' . $icon . $label->cap_title . '</span>';
			}
		}
		if ( count( $ugroups ) )
			return '<div class="disclosure-labels">' . implode("", $ugroups) . '</div>';

		return '';
	}
}
UndisclosedUsers::init();
endif;


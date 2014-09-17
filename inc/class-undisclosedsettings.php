<?php
/**
* @package WP_AccessAreas
* @version 1.0.0
*/ 

// ----------------------------------------
//	This class provides an interface for editing access areas
// ----------------------------------------

if ( ! class_exists('UndisclosedSettings' ) ) :

class UndisclosedSettings {
	private static $post_stati;
	private static $role_caps;

	static function init( ) {
		self::$post_stati = array(
			'' => __('Don‘t change','wpundisclosed'),
			'publish' => __('Public'),
			'private' => __('Private'),
			'draft' => __('Draft'),
			'pending' => __('Pending Review'),
		);
		self::$role_caps = array( 
			'wpaa_set_view_cap'		=> __( 'Change View Access' , 'wpundisclosed'),
			'wpaa_set_edit_cap'		=> __( 'Change Edit Access' , 'wpundisclosed'),
			'wpaa_set_comment_cap'	=> __( 'Change Comment Access' , 'wpundisclosed'),
		);
		add_option( 'wpaa_default_behavior' , '404' );
		add_option( 'wpaa_fallback_page' , 0 );
		add_option( 'wpaa_default_caps' , array( ) );
		add_option( 'wpaa_default_post_status' , 'publish' );
		add_option( 'wpaa_enable_assign_cap' , 0 );
		
		add_action( 'update_option_wpaa_enable_assign_cap' , array( __CLASS__ , 'enable_assign_cap' ) , 10 , 2 );
		add_filter( 'pre_update_option_wpaa_enable_assign_cap' , array( __CLASS__ , 'assign_role_cap' ) , 10 );

		add_action( 'admin_menu', array( __CLASS__ , 'create_menu' ));
		add_action( 'admin_init', array( __CLASS__ , 'register_settings' ) );

		add_action( 'load-settings_page_wpaa_settings' , array( __CLASS__ , 'load_style' ) );
	}
	static function load_style() {
		wp_enqueue_style( 'disclosure-admin' );
	}
	static function enable_assign_cap( $old_value , $new_value ) {
		if ( $new_value && ! $old_value  ) {
			// check if admin/editor/author
			$admin_role = get_role( 'administrator' );
			if ( ! $admin_role->has_cap('wpaa_set_view_cap') ||  
				! $admin_role->has_cap('wpaa_set_edit_cap')  ||
				! $admin_role->has_cap('wpaa_set_comment_cap') ) {

				UndisclosedInstall::install_role_caps();
			}
		}
	}
	static function assign_role_cap( $value ) {
		if ( current_user_can( 'promote_users' ) ) {
			if ( isset( $_POST['grant_cap'] ) && is_array( $_POST['grant_cap'] ) ) {
				foreach( $_POST['grant_cap'] as $role_slug => $cap ) {
					if ( 'administrator' != $role_slug && array_key_exists( $cap , self::$role_caps ) && ($role = get_role($role_slug)) && ! $role->has_cap( $cap ) )
						$role->add_cap( $cap );
				}
			} else if ( isset( $_POST['revoke_cap'] ) && is_array( $_POST['revoke_cap'] ) ) {
				foreach( $_POST['revoke_cap'] as $role_slug => $cap ) {
					if ( 'administrator' != $role_slug && array_key_exists( $cap , self::$role_caps ) && ($role = get_role($role_slug)) && $role->has_cap( $cap ) )
						$role->remove_cap( $cap );
				}
			}
		}
		return $value;
	}
	static function get_post_stati() {
		return array_filter( array_keys( self::$post_stati ) );
	}
	static function create_menu() { // @ admin_menu
		add_options_page(__('Access Settings','wpundisclosed'), __('Access Settings','wpundisclosed'), 'promote_users', 'wpaa_settings', array(__CLASS__,'settings_page'));
	}
	static function register_settings() { // @ admin_init
		
		register_setting( 'wpaa_settings' , 'wpaa_default_behavior', array(__CLASS__,'sanitize_behavior') );
		register_setting( 'wpaa_settings' , 'wpaa_fallback_page' , array(__CLASS__,'sanitize_fallbackpage') );
		register_setting( 'wpaa_settings' , 'wpaa_default_post_status' , array(__CLASS__,'sanitize_poststatus') );
		register_setting( 'wpaa_settings' , 'wpaa_default_caps' , array(__CLASS__,'sanitize_access_caps') );
		register_setting( 'wpaa_settings' , 'wpaa_enable_assign_cap' , 'intval' );

		add_settings_section('wpaa_main_section', __('Restricted Access Behavior','wpundisclosed'), array(__CLASS__,'main_section_intro'), 'wpaa');
		
		add_settings_field('wpaa_default_behavior', __('Default Behaviour','wpundisclosed'), array( __CLASS__ , 'select_behavior'), 'wpaa', 'wpaa_main_section');
		add_settings_field('wpaa_fallback_page', __('Default Fallback Page','wpundisclosed'), array( __CLASS__ , 'select_fallback_page'), 'wpaa', 'wpaa_main_section');

		add_settings_section('wpaa_post_access_section', __('Access Defaults for new Posts','wpundisclosed'), array( __CLASS__ , 'post_access_section_intro' ), 'wpaa');
		add_settings_field( 'wpaa_default_caps', __('Default Access:','wpundisclosed'), array( __CLASS__ , 'select_default_caps'), 'wpaa', 'wpaa_post_access_section');

		add_settings_section('wpaa_posts_section', __('Posts defaults','wpundisclosed'), '__return_false', 'wpaa');
		add_settings_field('wpaa_default_post_status', __('Default Post Status','wpundisclosed'), array( __CLASS__ , 'select_post_status'), 'wpaa', 'wpaa_posts_section');
		add_settings_field('wpaa_enable_assign_cap', __('Role Capabilities','wpundisclosed'), array( __CLASS__ , 'set_enable_capability'), 'wpaa', 'wpaa_posts_section');
	}
	static function main_section_intro() {
		?><p class="small description"><?php _e('You can also set these Options for each post individually.' , 'wpundisclosed' ); ?></p><?php
	}
	static function post_access_section_intro() {
		?><p class="small description"><?php _e('Default settings for newly created posts.' , 'wpundisclosed' ); ?></p><?php
	}
	static function settings_page() {
		/*
		if ( isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'],'wpaa_settings-options') ) {
			if ( isset( $_POST['wpaa_default_behavior'] ) ) {
				update_option( 'wpaa_default_behavior', $_POST['wpaa_default_behavior'] );
			}
		}*/
		?>
		<div class="wrap">
			<h2><?php _e('Access Areas Settings','wpundisclosed') ?></h2>
			
			<form id="wpaa-options" method="post" action="options.php">
				<?php 
					settings_fields( 'wpaa_settings' );
				?>
				<?php do_settings_sections( 'wpaa' );  
				?><input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" /><?php
				
			?></form>
		</div>
		<?php
	}
	static function select_default_caps( ) {
		$option_values = get_option('wpaa_default_caps');
		$post_types = get_post_types(array(
			'show_ui' => true,
		));

		global $wp_roles;
		$roles = $wp_roles->get_names();
		$user_role_caps = wpaa_get_user_role_caps();
		$rolenames = array();
		$edit_rolenames = array();
		foreach ( $roles as $role => $rolename ) {
			$rolenames[$role] = $rolename;
		}
		
		$groups = UndisclosedUserlabel::get_label_array( );
		?><table class="wp-list-table widefat set-default-caps"><?php
			?><thead><?php
				?><tr><?php
				
					?><th class="manage-column"><?php
						_e('Post Type' , 'wpundisclosed' );
					?></th><?php
					?><th class="manage-column"><?php
						_e('Reading');
					?></th><?php
					?><th class="manage-column"><?php
						_e('Edit');
					?></th><?php
					?><th class="manage-column"><?php
						_e('Post Comment');
					?></th><?php
				?></tr><?php
			?></thead><?php
			?><tfoot><?php
				?><tr><?php
				
					?><th class="manage-column"><?php
						_e('Post Type', 'wpundisclosed');
					?></th><?php
					?><th class="manage-column"><?php
						_e('Reading');
					?></th><?php
					?><th class="manage-column"><?php
						_e('Edit');
					?></th><?php
					?><th class="manage-column"><?php
						_e('Post Comment');
					?></th><?php
				?></tr><?php
			?></tfoot><?php
			?><tbody><?php
		$alternate = false;
		foreach ( $post_types as $post_type ) {
			$post_type_object = get_post_type_object( $post_type );
			$editing_cap = $post_type_object->cap->edit_posts;
			
			$alternate = !$alternate;
			$edit_rolenames = array();
			foreach ( $roles as $role => $rolename ) {
				if ( get_role( $role )->has_cap( $editing_cap ) ) {
					$edit_rolenames[$role] = $rolename;
				}
			}
			
			?><tr class="post-select <?php if ( $alternate ) echo "alternate" ?>"><?php
				?><th><?php
					echo $post_type_object->labels->name;
				?></th><?php
				?><td><?php
					$action = 'post_view_cap';
					$cap = isset($option_values[$post_type][$action] )?$option_values[$post_type][$action] : 'exist';
					if ( $post_type != 'attachment' && ( $post_type_object->public || $post_type_object->show_ui)  )
						UndisclosedEditPost::access_area_dropdown(  $roles , $groups , 
							wpaa_sanitize_access_cap( $cap ) , 
							"wpaa_default_caps[$post_type][$action]"  );
				?></td><?php
				?><td><?php
					$action = 'post_edit_cap';
					$cap = isset($option_values[$post_type][$action] )?$option_values[$post_type][$action] : 'exist';
					UndisclosedEditPost::access_area_dropdown(  $edit_rolenames , $groups , 
						wpaa_sanitize_access_cap( $cap ) , 
						"wpaa_default_caps[$post_type][$action]"  );
				?></td><?php
				?><td><?php
					$action = 'post_comment_cap';
					$cap = isset($option_values[$post_type][$action] )?$option_values[$post_type][$action] : 'exist';
					if ( post_type_supports( $post_type , 'comments' ) )
						UndisclosedEditPost::access_area_dropdown(  $roles , $groups , 
							wpaa_sanitize_access_cap( $cap ) , 
							"wpaa_default_caps[$post_type][$action]"  );
				?></td><?php
			?></tr><?php
		}
			?></tbody><?php
		?></table><?php
		
	}
	static function set_enable_capability(  ) {
		$enabled = get_option( 'wpaa_enable_assign_cap' );

		?><input type="hidden" name="wpaa_enable_assign_cap" value="<?php echo $enabled ?>" /><?php
		if ( $enabled ) {
			$roles = get_editable_roles();
			?><p class="description"><?php 
				_e('This table shows which Roles are allowed to set the ‘Who can view’, ‘Who can edit’ and ‘Who can comment’ properties.','wpundisclosed');
			?></p><?php
			?><table class="wp-list-table widefat set-default-caps"><?php
				?><thead><?php
					?><tr><?php
				
						?><th class="manage-column"><?php
							_e( 'Role' , 'wpundisclosed' );
						?></th><?php
						foreach ( self::$role_caps as $cap => $label ) {
							?><th class="manage-column"><?php
								echo $label;
								?><br /><code><small><?php echo $cap; ?></small></code><?php
							?></th><?php
						}
					?></tr><?php
				?></thead><?php
				?><tbody><?php

				$alternate = false;
				foreach ( $roles as $role_slug => $role_details ) {
					$role = get_role( $role_slug );
					$alternate = !$alternate;
					?><tr class="role-select <?php if ( $alternate ) echo "alternate" ?>"><?php
					?><th><?php
						echo translate_user_role( $role_details['name'] );
					?></th><?php
					foreach ( array_keys( self::$role_caps ) as $cap ) {
						?><td><?php
						if ( $role->has_cap( 'edit_posts' ) || $role->has_cap( 'edit_pages' ) ) {
							$attr = $role_slug == 'administrator'?'disabled':'';
							if ( $role->has_cap( $cap ) ) {
								?><button <?php echo $attr ?> name="revoke_cap[<?php echo $role_slug ?>]" value="<?php echo $cap ?>" type="submit" class="button-secondary" /><?php _e('Forbid' , 'wpundisclosed') ?></button><?php
							} else {
								?><button name="grant_cap[<?php echo $role_slug ?>]" value="<?php echo $cap ?>" type="submit" class="button-primary" /><?php _e('Allow'  , 'wpundisclosed') ?></button><?php
							}
					
						} else {
						}
						?></td><?php
					}
					?><tr><?php
					}
				?></tbody><?php
			?></table><?php
			?><p class="description"><?php 
				_e('If you are running a role editor plugin such as <a href="https://wordpress.org/plugins/user-role-editor/">User Role editor by Vladimir Garagulya</a> or <a href="https://wordpress.org/plugins/wpfront-user-role-editor/">WPFront User Role Editor by Syam Mohan</a> you can do the same as here by assigning the custom capabilites <code>wpaa_set_view_cap</code>, <code>wpaa_set_edit_cap</code> and <code>wpaa_set_comment_cap</code>.','wpundisclosed');
			?></p><?php
			?><p class="description"><?php 
				_e('By disabling the role capabilities feature you will allow everybody who can at least publish a post to edit the access properties as well.','wpundisclosed');
			?></p><?php
			?><button name="wpaa_enable_assign_cap" value="0" type="submit" class="button-secondary" /><?php _e('Disable Role Capabilities' , 'wpundisclosed'); ?></button><?php
		} else {
			?><p class="description"><?php 
				_e('By default everybody who can publish an entry can also edit the access properties such as ‘Who can view’ or ‘Who can edit’.<br /> If this is too generous for you then click on the button below.','wpundisclosed');
			?></p><?php
			?><button name="wpaa_enable_assign_cap" value="1" type="submit" class="button-secondary" /><?php _e('Enable Role Capabilities' , 'wpundisclosed'); ?></button><?php
		}
	}
	static function select_behavior() {
		$behavior = get_option('wpaa_default_behavior');
		?><p><?php _e('If somebody tries to view a restricted post directly:' , 'wpundisclosed' ); ?></p><?php
		UndisclosedEditPost::behavior_select( $behavior , 'wpaa_default_behavior' );
	}
	static function sanitize_behavior( $behavior ) {
		if ( ! preg_match('/^(404|page|login)$/',$behavior) )
			$behavior = '404';
		return $behavior;
	}
	static function select_fallback_page(){
		$post_fallback_page = get_option('wpaa_fallback_page');
		UndisclosedEditPost::fallback_page_dropdown( $post_fallback_page , 'wpaa_fallback_page' );
	}
	static function sanitize_fallbackpage($fallback_page_id) {
		$page = get_post( $fallback_page_id );
		if ( !$page || $page->post_status != 'publish' || $page->post_type != 'page' || $page->post_view_cap != 'exist' )
			$fallback_page_id = 0;
		return $fallback_page_id;
	}
	static function sanitize_access_caps( $caps ) {
		$return_caps = array();
		foreach ( $caps as $post_type => $post_type_caps ) {
			/// check is_post_type()
			if ( ! isset( $return_caps[$post_type] ) && post_type_exists( $post_type ) ) {
				$return_caps[$post_type] = array();
			}
			foreach ($post_type_caps as $action => $cap ) {
				$return_caps[$post_type][$action] = wpaa_sanitize_access_cap( $cap );
			}
		}
		return $return_caps;
	}
	static function select_post_status() {
		$default_post_status = get_option('wpaa_default_post_status');
		// stati: none, publish, private, pending, draft
		?><select id="default-post-status-select" name="wpaa_default_post_status"><?php
		foreach ( self::$post_stati as $post_status => $label ) {
			?><option value="<?php echo $post_status; ?>" <?php selected($default_post_status,$post_status,true) ?>><?php echo $label ?></option><?php
		}
		?></select><?php
		?><p class="description"><?php
			_e('Set post status of assigned posts after an Access Area has been deleted.','wpundisclosed');
		?></p><?php
	}
	static function sanitize_poststatus( $post_status ) {
		if ( array_key_exists( $post_status , self::$post_stati ) )
			return $post_status;
		return false;
	}
}
endif;

<?php
/**
* @package WPUndisclosed
* @version 0.9b
*/ 

/*
Plugin Name: WordPress Undisclosed
Plugin URI: https://github.com/mcguffin/wp-undisclosed
Description: Adds the ability to make WPs posts and pages only visible to logged in users. For all the others these items simply do not appear. Supports custom post types.
Author: Joern Lund
Version: 0.9b
Author URI: https://github.com/mcguffin/
*/

/*
Changelog:
0.2.2
	- rewritten get_posts
	- added option allowing you to show a login-message instead of 404 error on restreicted items.

ToDo:
	- store groupnames in table
	- Permissions are: read, edit, comment
	- store content-permissions in extra columns

	- register_activation_hook -> creates table
	- register_deactivation_hook -> drops table, extra-columns, updates user capabilities
	- predefine network-wide groups
	- add group editing interface
	- split functionalities
		Parts:
			√ core: load textdomain
			√ install: create / drop tables, columns
			- cap editing.
			- user editing
			- post editing
			- post viewing and retrieving
			
			- backend options, edit screens, ...
			

*/

// table name for userlabels
define( 'WPUND_USERLABEL_TABLE' , "disclosure_userlabels");
define( 'WPUND_USERLABEL_PREFIX' , "userlabel_");


require_once( dirname(__FILE__). '/inc/class-undisclosedcore.php' );
require_once( dirname(__FILE__). '/inc/class-undisclosedinstall.php' );
require_once( dirname(__FILE__). '/inc/class-undisclosedcaps.php' );
require_once( dirname(__FILE__). '/inc/class-undiscloseduserlabel.php' );
require_once( dirname(__FILE__). '/inc/class-userlabel_list_table.php' );
require_once( dirname(__FILE__). '/inc/class-undisclosedusers.php' );
require_once( dirname(__FILE__). '/inc/class-undisclosededitpost.php' );
require_once( dirname(__FILE__). '/inc/class-undisclosedposts.php' );

register_activation_hook( __FILE__ , array( 'UndisclosedInstall' , 'activate' ) );
register_deactivation_hook( __FILE__ , array( 'UndisclosedInstall' , 'deactivate' ) );
register_uninstall_hook( __FILE__ , array( 'UndisclosedInstall' , 'uninstall' ) );

// include the other stuff.


/*
if ( !class_exists( 'WPUndisclosed' ) ) :
class WPUndisclosed {

	// --------------------------------------------------
	// adding hooks
	// --------------------------------------------------
	static function init() {

		add_option( 'disclosure_groups' , array() , '' , 'yes' );
		add_option( 'disclosure_settings' , array('show_404'=>true) , '' , 'yes' );

		$opts = get_option( 'disclosure_settings' );

		if ( is_admin() ) {
			// general
			add_action( 'admin_init' , array( __CLASS__ , 'admin_init' ) );
			
			add_action( 'admin_menu', array( __CLASS__ , 'create_menu' ));
			
			add_action( 'admin_init' , array( __CLASS__ , 'register_settings' ) );

			// deny on edit post
			add_action( 'edit_post_ID' , array( __CLASS__, 'edit_post_ID' ));

			// edit post
			add_action( 'edit_post' , array( __CLASS__ , 'edit_post' ) );
			add_action( 'add_meta_boxes' , array( __CLASS__ , 'add_meta_boxes' ) );
			
			// list views
			add_filter('manage_posts_columns' , array(__CLASS__ , 'add_disclosure_column'));
			add_filter('manage_posts_custom_column' , array(__CLASS__ , 'manage_disclosure_column') , 10 ,2 );

			add_filter('manage_pages_columns' , array(__CLASS__ , 'add_disclosure_column'));
			add_filter('manage_pages_custom_column' , array(__CLASS__ , 'manage_disclosure_column') , 10 ,2 );

			add_filter('manage_users_columns' , array(__CLASS__ , 'add_groups_column'));
			add_filter('manage_users_custom_column' , array(__CLASS__ , 'manage_groups_column') , 10 ,3 );
		}
		
		add_action( 'get_pages' , array( __CLASS__ , 'skip_undisclosed_items' ) , 10 , 1 );
		add_filter( "posts_join" , array( __CLASS__ , "get_posts_join" ) , 10, 2 );
		add_filter( "posts_where" , array( __CLASS__ , "get_posts_where" ) , 10, 2 );

		if (!(boolean) $opts['show_404'])
			add_filter('the_content' ,  array( __CLASS__ , "undisclosed_content" ) );


		add_filter( "posts_join" , array( __CLASS__ , "get_posts_join" ) , 10, 2 );
//		add_filter( "posts_where" , array( __CLASS__ , "get_posts_where" ) , 10, 2 );
			
		// used in previous_post_link() / next_post_link()
		add_filter( "get_next_post_join" , array( __CLASS__ , "get_adjacent_post_join" ) , 10, 3 );
		add_filter( "get_previous_post_join" , array( __CLASS__ , "get_adjacent_post_join" ) , 10, 3 );
		add_filter( "get_next_post_where" , array( __CLASS__ , "get_adjacent_post_where" ) , 10, 3 );
		add_filter( "get_previous_post_where" , array( __CLASS__ , "get_adjacent_post_where" ) , 10, 3 );
		
		// more post links to filter:
		
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

	static function edit_post_ID($post_ID) {
		$roles = new WP_Roles();
		$disclosure = get_post_meta($post_ID,'_disclosure',true);
		if ( $disclosure && !(self::_user_can_role( $disclosure , $roles->get_role(wp_get_current_user()->roles[0])->capabilities) || current_user_can( $disclosure ) || current_user_can( 'administrator' )) )
			wp_die( __('You are not allowed to edit this item.') );
		return $post_ID;
	}
	
	// --------------------------------------------------
	// Options page
	// --------------------------------------------------
	static function create_menu() { // @ admin_menu
		add_options_page(__('Disclosed Content','disclosure'), __('Disclosure Setting','disclosure'), 'manage_options', 'disclosure_settings', array(__CLASS__,'settings_page'));
		add_action( 'admin_init', array( __CLASS__ , 'register_settings' ) );
	}
	static function register_settings() { // @ admin_init
		register_setting( 'disclosure_settings', 'disclosure_settings' );
		add_settings_section('disclosedcontent_main', __('Main Settings','disclosure'), '__return_false', 'disclosure_settings');
		add_settings_field('plugin_text_string', __('Show login message instead of 404','disclosure'), array( __CLASS__ , 'setting_hide_undisclosed'), 'disclosure_settings', 'disclosedcontent_main');
	}
	static function settings_page() {
		?>
		<div class="wrap">
			<h2><?php _e('Disclosed Content') ?></h2>
			
			<form method="post" action="options.php">
				<?php settings_fields( 'disclosure_settings' ); ?>
				<?php do_settings_sections( 'disclosure_settings' ); ?>
				
				<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
				
			</form>
		</div>
		<?php
	}
	static function setting_hide_undisclosed() {
		$opts = get_option( 'disclosure_settings' );
		?>
		<input type="hidden" name="disclosure_settings[show_404]" value="1" />
		<input type="checkbox" name="disclosure_settings[show_404]" id="hide_undisclosed" value="0" <?php checked((boolean) $opts['show_404'], false) ?> />
		<label for="hide_undisclosed">
			<?php _e('When somebody without authorization tries to navigate a protected URL directly, WordPress will show a 404 message.<br />Check this, if you want it to show a please-login-message instead.','disclosure'); ?>
		</label><?php
	}
	
	// --------------------------------------------------
	// admin list views
	// --------------------------------------------------
	static function manage_disclosure_column($column, $post_ID) {
		if ( $column != 'disclosure')
			return;
		$roles = new WP_Roles();
		$names = array_merge(array('exist' => __( 'Everybody' , 'disclosure' ), 'undisclosed' => __( 'Logged in Users' , 'disclosure' )) , get_option( 'disclosure_groups' ), $roles->get_names());
		$names[''] = $names['exist'];
		$val = get_post_meta($post_ID , '_disclosure' , true);
		_e($names[$val]);
	}
	static function add_disclosure_column($columns) {
		$cols = array();
		foreach ($columns as $k=>$v) {
			$cols[$k] = $v;
			if ($k=='author') 
				$cols['disclosure'] = __('Visible to','disclosure');
		}
		return $cols;
	}
	static function add_groups_column($columns) {
		$cols = array();
		foreach ($columns as $k=>$v) {
			$cols[$k] = $v;
			if ($k=='role') 
				$cols['groups'] = __('Groups','disclosure');
		}
		return $cols;
	}
	static function manage_groups_column($wtf, $column, $user_ID) {
		if ( $column != 'groups')
			return;
		
		global $table_prefix;
		
		$ugroups = array();
		$groups = get_option( 'disclosure_groups' );
		$ucaps = get_user_meta($user_ID, $table_prefix.'capabilities',true);
		foreach ($groups as $gid=>$group)
			if (isset($ucaps[$gid]))
				$ugroups[] = $group;
		return implode(", ", $ugroups);
		// echo all groups by user.
	}
	
	
	
	
	// --------------------------------------------------
	// user editing
	// --------------------------------------------------
	static function load_user_editor(){
		wp_register_script( 'disclosure' , plugins_url('js/disclosure.js', __FILE__), array('jquery') );
		wp_enqueue_script( 'disclosure' );
	}
	static function profile_update( $user_id, $old_user_data ) {
		if ( ! current_user_can( 'promote_users' ) || ! isset( $_POST['capabilities'] ) ) 
			return;
		$user = new WP_User( $user_id );
		// get group names
		$groups = get_option( 'disclosure_groups' );
		foreach ($_POST['capabilities'] as $i=>$cap) {
			if ( !isset( $cap["name"] ) )
				$cap["name"] = str_replace('-','_','disclosure_'.sanitize_title( $cap["title"] ));
			$groups[$cap["name"]] = $cap["title"];
			if ( $cap["has_cap"] ) 
				$user->add_cap( $cap["name"] , true );
			else 
				$user->remove_cap( $cap["name"] );
		}
		// update group names
		update_option( 'disclosure_groups' , $groups );
	}
	static function personal_options( $profileuser ) {
		// IS_PROFILE_PAGE : self or other
		if ( ! current_user_can( 'promote_users' ) ) 
			return;
		$groups = get_option( 'disclosure_groups' );
		?><h3><?php _e( 'Groups for Disclosure' , 'disclosure' ) ?></h3><?php
		?><table class="form-table"><?php

		?><tr><th><label for="add-disclosure-group"><?php _e( 'Add group' , 'disclosure' ) ?></label></th><?php
		?><td><input class="regular-text" type="text" id="add-disclosure-group" /><button class="button-secondary" disabled="disabled" id="do-add-disclosure-group"><?php _e( 'Add' , 'disclosure' ) ?></button></td></tr><?php

		?><tr><th><label for="set-disclosure-groups"><?php _e( 'Enable groups' , 'disclosure' ) ?></label></th><?php
		?><td id="disclosure-group-items"><?php
			foreach ( $groups as $name => $title ) {
				?><span class="disclosure-group-item"><input type="hidden" name="capabilities[0][name]" value="<?php echo $name ?>" /><?php
				?><input type="hidden" name="capabilities[0][title]" value="<?php echo $title ?>" /><?php
				?><input type="hidden" name="capabilities[0][has_cap]" value="0" /><?php
				?><input id="cap-<?php echo $name ?>" type="checkbox" name="capabilities[0][has_cap]" value="1" <?php checked($profileuser->has_cap( $name ),true) ?> /><?php
				?><label for="cap-<?php echo $name ?>">  <?php echo $title ?></label></span><?php
			}
		?></td></tr><?php

		?></table><?php
	}
	
	//	manage_users_columns
	//	manage_users_custom_column
	
	
	// --------------------------------------------------
	// edit post - add meta boxes to all post content
	// --------------------------------------------------
	static function add_meta_boxes() {
		global $wp_post_types;
		foreach ( array_keys($wp_post_types) as $post_type ) {
			add_meta_box( 'post-disclosure' , __('Disclosure','disclosure') , array(__CLASS__,'disclosure_box_info') , $post_type , 'side' , 'high' );
		}
	}
	
	// --------------------------------------------------
	// edit post - the meta box
	// --------------------------------------------------
	static function disclosure_box_info() {
		$disclosure = get_post_meta(get_the_ID() , '_disclosure' , true );
		// <select> with - Evereybody, Logged-in only, list WP-Roles, list discosure-groups
		$current_user = wp_get_current_user();
		$roles = new WP_Roles();
		$rolenames = $roles->get_names();
		$groups = get_option( 'disclosure_groups' );

		$user_role_caps = $roles->get_role(wp_get_current_user()->roles[0])->capabilities;
		$is_admin = current_user_can( 'administrator' );
		
		?><div id="disclosure-select">
			<label for="select-disclosure"><?php _e( 'Visible to:' , 'disclosure') ?></label>
			<select id="select-disclosure" name="_disclosure">
				<option value="exist" <?php selected($disclosure , 'exist') ?>><?php _e( 'Everybody' , 'disclosure' ) ?></option>
				<option value="read" <?php selected($disclosure , 'read') ?>><?php _e( 'Logged in Users' , 'disclosure' ) ?></option>
				
				<optgroup label="<?php _e( 'WordPress roles' , 'disclosure') ?>">
				<?php foreach ($rolenames as $role=>$rolename) {
					if ( !self::_user_can_role( $role , $user_role_caps ) )
						continue;
					?>
					<option value="<?php echo $role ?>" <?php selected($disclosure , $role) ?>><?php _ex( $rolename, 'User role' ) ?></option>
				<?php } ?>
				</optgroup>

				<optgroup label="<?php _e( 'User Groups' , 'disclosure') ?>">
				<?php foreach ($groups as $group=>$groupname) { 
					if ( !current_user_can($group) && !$is_admin )
						continue;
					?>
					<option value="<?php echo $group ?>" <?php selected($disclosure , $group) ?>><?php _e( $groupname , 'disclosure' ) ?></option>
				<?php } ?>
				</optgroup>
			</select>
		</div><?php
	}
	
	// --------------------------------------------------
	// saving post meta
	// --------------------------------------------------
	static function edit_post( $post_ID ) {
		// no request, no change
		if ( !isset( $_POST['_disclosure'] ) )
			return;
			
		// check if user can set this
		$roles = new WP_Roles();
		
		if ( !(self::_user_can_role( $_POST['_disclosure'] , $roles->get_role(wp_get_current_user()->roles[0])->capabilities) 
				|| current_user_can( $_POST['_disclosure'] )) )
			
			wp_die( __('You don‘t have permission to apply this type of content disclosure.' , 'disclosure') ); // deny!
		update_post_meta($post_ID, '_disclosure', $_POST['_disclosure'] );
	}
	
	
	// --------------------------------------------------
	// frontend - called before the_loop, filtering out undisclosed posts
	// --------------------------------------------------
	
	static function undisclosed_content( $content ) {
		if ( current_user_can( 'administrator' ) )
			return $content;
		$disclosure = get_post_meta( get_the_ID() , '_disclosure' , true);
		if ( self::_user_can( $disclosure ) )
			return $content;
		return sprintf(__('Please <a href="%s">log in</a> to see this content!' , 'disclosure'),wp_login_url( get_permalink() ));
	}
	
	static function skip_undisclosed_items( $items ) {
		// everything's fine - return.
		if ( current_user_can( 'administrator' ) )
			return $items;
		
		
		
		// remove undisclosed posts
		$ret = array();
		foreach ( $items as $i => $item ) {
			$disclosure = get_post_meta($item->ID , '_disclosure' , true);
			if ( self::_user_can( $disclosure ) )
					$ret[] = $item;
		}
		return $ret;
	}
	
	static function get_posts_join( $join , &$wp_query ) {
		global $wpdb;
		$opts = get_option( 'disclosure_settings' );
		if ( $wp_query->is_single() && !(boolean) $opts['show_404'] )
			return $join;
			
		return self::_get_join($join,$wpdb->posts);
	}
	static function get_posts_where( $where , &$wp_query ) {
		$opts = get_option( 'disclosure_settings' );
		if ( $wp_query->is_single() && !(boolean) $opts['show_404'] )
			return $where;
			
		return self::_get_where($where);
	}
	
	static function get_adjacent_post_join( $join , $in_same_cat, $excluded_categories ) {
		return self::_get_join($join,'p');
	}
	static function get_adjacent_post_where( $where , $in_same_cat, $excluded_categories ) {
		return self::_get_where($where);
	}


	private static function _get_join($join, $posts_table) {
		if ( current_user_can( 'administrator' ) )
			return $join;
		global $wpdb;
		$join .= " LEFT JOIN $wpdb->postmeta AS m ON {$posts_table}.id=m.post_id AND (m.meta_key='_disclosure' OR ISNULL(m.meta_key)) ";
		return $join;
	}
	private static function _get_where($where) {
		if ( current_user_can( 'administrator' ) )
			return $where;
		if ( is_user_logged_in() ) {
			// get current user's groups
			$roles = new WP_Roles();
			$cond = array( "ISNULL(m.meta_value)" , "m.meta_value = 'exist'");
			foreach( array_keys( array_merge( get_option( 'disclosure_groups' ) , $roles->get_names() )) as $cap)
				if ( current_user_can($cap) )
					$cond[] = "m.meta_value = '$cap'";
			
			return $where . " AND (".implode( ' OR ' , $cond ) . ")";
		}
		$where .= " AND (m.meta_value = 'exist' OR ISNULL(m.meta_value)) ";
		return $where;
	}
	
	// --------------------------------------------------
	// private - retrieving user capabilities
	// --------------------------------------------------
	static function _user_can_role( $role , $user_role_caps ) {
		$roles = new WP_Roles();
		if ($roles->is_role($role))
			return 0 == count(array_diff_assoc(  $roles->get_role( $role )->capabilities , $user_role_caps));
		return false;
	}
	static function _user_can($disclosure) {
		if ( !$disclosure || 'exist' == $disclosure || 'undisclosed' == $disclosure && is_user_logged_in() )
			return true;
		return current_user_can( $disclosure );
	}
}

WPUndisclosed::init();
endif;
*/


?>
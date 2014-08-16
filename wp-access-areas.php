<?php
/**
* @package WP_AccessAreas
* @version 1.1.0
*/ 

/*
Plugin Name: WordPress Access Areas
Plugin URI: http://wordpress.org/plugins/wp-access-areas/
Description: Lets you define Access Areas and assign them to Posts, Pages and Custom Post types. Through Access Areas you can fine-tune who can view, edit or comment on your posts.
Author: Jörn Lund
Version: 1.2.7
Author URI: https://github.com/mcguffin/

Text Domain: wpundisclosed
Domain Path: /lang/
*/

/*
Next:
- put note when fallback page is access restricted | private | draft
- remove view access control + fallback behavior from fallback page edit
- Pages list: show fallback page note
- set fallback page per local access area (manage page deletions!)
*/

// table name for userlabels
define( 'WPUND_VERSION' , "1.2.7"); // edit-col came with 1.1.0
define( 'WPUND_USERLABEL_TABLE' , "disclosure_userlabels");
define( 'WPUND_USERLABEL_PREFIX' , "userlabel_");
define( 'WPUND_GLOBAL_USERMETA_KEY' , "undisclosed_global_capabilities");

function is_accessareas_active_for_network( ) {
	if ( ! is_multisite() )
		return false;
	if ( ! function_exists( 'is_plugin_active_for_network' ) )
		require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

	return is_plugin_active_for_network( basename(dirname(__FILE__)).'/'.basename(__FILE__) );
}

// common plugin functions
require_once( dirname(__FILE__). '/inc/class-undisclosedcore.php' );

// function library
require_once( dirname(__FILE__). '/inc/wpaa_roles.php' );

// installation hooks
function accessareas_activate(){
	require_once( dirname(__FILE__). '/inc/class-undisclosedinstall.php' );
	UndisclosedInstall::activate();
}
function accessareas_deactivate(){
	require_once( dirname(__FILE__). '/inc/class-undisclosedinstall.php' );
	UndisclosedInstall::deactivate();
}
function accessareas_uninstall(){
	require_once( dirname(__FILE__). '/inc/class-undisclosedinstall.php' );
	UndisclosedInstall::uninstall();
}

// access area data model 
require_once( dirname(__FILE__). '/inc/class-undiscloseduserlabel.php' );
if ( is_admin() ) {
	require_once( dirname(__FILE__). '/inc/class-undisclosedcaps.php' );
	require_once( dirname(__FILE__). '/inc/class-userlabel_list_table.php' );
	require_once( dirname(__FILE__). '/inc/class-undisclosedusers.php' );
	require_once( dirname(__FILE__). '/inc/class-undisclosededitpost.php' );
	require_once( dirname(__FILE__). '/inc/class-undisclosedsettings.php' );

	register_activation_hook( __FILE__ , 'accessareas_activate' );
	register_deactivation_hook( __FILE__ , 'accessareas_deactivate' );
	register_uninstall_hook( __FILE__ , 'accessareas_uninstall' );
	
}

// frontend output
require_once( dirname(__FILE__). '/inc/class-undisclosedposts.php' );

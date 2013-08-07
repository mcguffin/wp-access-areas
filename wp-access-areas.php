<?php
/**
* @package WP_AccessAreas
* @version 1.1.0
*/ 

/*
Plugin Name: WordPress Access Areas
Plugin URI: http://wordpress.org/plugins/wp-access-areas/
Description: Lets you define Access Areas and assign them to Posts, Pages and Custom Post types. Through Access Areas you can fine-tune who can view, edit or comment on your posts.
Author: Joern Lund
Version: 1.1.5
Author URI: https://github.com/mcguffin/

Text Domain: wpundisclosed
Domain Path: /lang/
*/


// table name for userlabels
define( 'WPUND_VERSION' , "1.1.5"); // edit-col came with 1.1.0
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
require_once( dirname(__FILE__). '/inc/class-undisclosedcore.php' );

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

require_once( dirname(__FILE__). '/inc/class-undiscloseduserlabel.php' );
if ( is_admin() ) {
	require_once( dirname(__FILE__). '/inc/class-undisclosedcaps.php' );
	require_once( dirname(__FILE__). '/inc/class-userlabel_list_table.php' );
	require_once( dirname(__FILE__). '/inc/class-undisclosedusers.php' );
	require_once( dirname(__FILE__). '/inc/class-undisclosededitpost.php' );

	register_activation_hook( __FILE__ , 'accessareas_activate' );
	register_deactivation_hook( __FILE__ , 'accessareas_deactivate' );
	register_uninstall_hook( __FILE__ , 'accessareas_uninstall' );
	
}
require_once( dirname(__FILE__). '/inc/class-undisclosedposts.php' );



?>
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
define( 'WPUND_GLOBAL_USERMETA_KEY' , "undisclosed_global_capabilities");

function is_accessareas_active_for_network( ) {
	if ( ! function_exists( 'is_plugin_active_for_network' ) )
		require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

	return is_plugin_active_for_network( basename(dirname(__FILE__)).'/'.basename(__FILE__) );
}
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



?>
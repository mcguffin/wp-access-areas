<?php
/**
* @package WP_AccessAreas
* @version 1.0.0
*/ 

/*
Plugin Name: WordPress Access Areas
Plugin URI: https://github.com/mcguffin/wp-access-areas
Description: Lets you define Access Areas and assign them to Posts, Pages and Custom Post types. Through Access Areas you can fine-tune who can view or comment your posts.
Author: Joern Lund
Version: 1.0.0
Author URI: https://github.com/mcguffin/
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


?>
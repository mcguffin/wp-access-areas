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
Version: 1.3.0
Author URI: https://github.com/mcguffin/

Text Domain: wpundisclosed
Domain Path: /lang/
*/

/*
Next:
UX:
- put note when fallback page is access restricted | private | draft
- remove view access control + fallback behavior from fallback page edit
- Pages list: show fallback page note
FEATURE:
- set fallback page per local access area (manage page deletions!)
- add cap wpaa_restrict_access to Administrator + Editor role (or any other role with editing capabilities)
- UI: add / revoke access restricting cap for role
*/

// table name for userlabels
define( 'WPUND_VERSION' , "1.3.0"); // edit-col came with 1.1.0
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

/**
 * Autoload WPAA Classes
 *
 * @param string $classname
 */
function wpaa_autoload( $classname ) {
	$class_path = dirname(__FILE__). sprintf('/inc/class-%s.php' , strtolower( $classname ) ) ; 
	if ( file_exists($class_path) )
		require_once $class_path;
}
spl_autoload_register( 'wpaa_autoload' );

// function library
require_once( dirname(__FILE__). '/inc/wpaa_roles.php' );

// common plugin functions
UndisclosedCore::init();
UndisclosedPosts::init();


/**
 * Activation hook
 */
function accessareas_activate() {
	UndisclosedInstall::activate();
}

/**
 * Deactivation hook
 */
function accessareas_deactivate() {
	UndisclosedInstall::deactivate();
}

/**
 * Uninstall hook
 */
function accessareas_uninstall() {
	UndisclosedInstall::uninstall();
}

// access area data model 
if ( is_admin() ) {
	UndisclosedCaps::init();
	UndisclosedUsers::init();
	UndisclosedEditPost::init();
	UndisclosedSettings::init();
}
register_activation_hook( __FILE__ , 'accessareas_activate' );
register_deactivation_hook( __FILE__ , 'accessareas_deactivate' );
register_uninstall_hook( __FILE__ , 'accessareas_uninstall' );

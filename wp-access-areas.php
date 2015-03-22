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
Version: 1.4.3
Author URI: https://github.com/mcguffin/
*/

/*
Next:
UX:
- put note when fallback page is access restricted | private | draft
- remove view access control + fallback behavior from fallback page edit
- Pages list: show fallback page note
FEATURE:
- set fallback page per local access area (manage page deletions!)
*/

// table name for userlabels
define( 'WPUND_VERSION' , "1.4.3"); // edit-col came with 1.1.0
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
WPAA_Core::init();
WPAA_Posts::init();


/**
 * Activation hook
 */
function accessareas_activate() {
	WPAA_Install::activate();
}

/**
 * Deactivation hook
 */
function accessareas_deactivate() {
	WPAA_Install::deactivate();
}

/**
 * Uninstall hook
 */
function accessareas_uninstall() {
	WPAA_Install::uninstall();
}

if ( is_admin() ) {
	// access area data model 
	WPAA_Caps::init();
	// access user profiles
	WPAA_Users::init();
	// access posts editing
	WPAA_EditPost::init();
	// access options
	WPAA_Settings::init();
}
register_activation_hook( __FILE__ , 'accessareas_activate' );
register_deactivation_hook( __FILE__ , 'accessareas_deactivate' );
register_uninstall_hook( __FILE__ , 'accessareas_uninstall' );

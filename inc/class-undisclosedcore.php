<?php
/**
* @package WP_AccessAreas
* @version 1.0.0
*/ 



// ----------------------------------------
//	This class initializes the WordPress Undisclosed plugin.
//	(As of version 1.0 it only loads an apropriate plugin textdomain for translation readyness.)
// ----------------------------------------


if ( ! class_exists('UndisclosedCore') ) :
class UndisclosedCore {
	static function init() {
		add_action( 'plugins_loaded' , array( __CLASS__, 'plugin_loaded' ) );
		add_action( 'admin_init' , array(__CLASS__,'admin_enqueue_scripts') );
	}

	static function admin_enqueue_scripts() {
		wp_register_style( 'disclosure-admin' , plugins_url('css/disclosure-admin.css', dirname(__FILE__)) );
	}
	// translation ready.
	static function plugin_loaded() {
		load_plugin_textdomain( 'wpundisclosed' , false, dirname(dirname( plugin_basename( __FILE__ ))) . '/lang');
	}
}
UndisclosedCore::init();
endif;

?>
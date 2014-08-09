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
		if ( is_multisite() ) {
			add_action('wpmu_new_blog' , array( __CLASS__ , 'set_network_roles_for_blog' ) , 10 , 1 );
			add_action('wpmu_upgrade_site' , array( __CLASS__ , 'set_network_roles_for_blog' ) , 10 ,1 );
		}
		add_action('init',array(__CLASS__,'admin_register_scripts'));
		add_filter( 'admin_body_class' , array( __CLASS__ , 'admin_body_class_gte_38' ) );
		
		add_option('wpaa_default_behavior' , '404' );
		add_option('wpaa_fallback_page' , 0 );
		add_option('wpaa_default_post_status' , 'private' );
	}
	static function admin_register_scripts() {
		wp_register_script( 'disclosure-admin-user-ajax' , plugins_url('js/disclosure-admin-user-ajax.js', dirname(__FILE__)) );
		wp_register_script( 'disclosure-quick-edit' , plugins_url('js/disclosure-quick-edit.js', dirname(__FILE__)) );
		wp_register_style( 'disclosure-admin' , plugins_url('css/disclosure-admin.css', dirname(__FILE__)) );
	}
	static function admin_body_class_gte_38( $admin_body_class ) {
		if ( version_compare(get_bloginfo('version'),'3.8.0','>=') )
			$admin_body_class .= ' wp-gte-38';
		else
			$admin_body_class .= ' wp-lt-38';
		return $admin_body_class;
	}
	// translation ready.
	static function plugin_loaded() {
		self::check_version();
		load_plugin_textdomain( 'wpundisclosed' , false, dirname(dirname( plugin_basename( __FILE__ ))) . '/lang');
	}
	
	static function set_network_roles_for_blog( $blog_id /*, $user_id, $domain, $path, $site_id, $meta */ ) {
		require_once( dirname(__FILE__). '/class-undisclosedinstall.php' );
		UndisclosedInstall::activate_for_blog( $blog_id );
	}
	static function check_version( ) {
		if ( is_multisite( ) ) {
			$installed_version = get_site_option('accessareas_version');
			update_site_option( 'accessareas_version' , WPUND_VERSION );
		} else {
			$installed_version = get_option('accessareas_version');
			update_option( 'accessareas_version' , WPUND_VERSION );
		}
		if ( ! $installed_version || version_compare( WPUND_VERSION , $installed_version ) )
			accessareas_activate();
	}

}
UndisclosedCore::init();
endif;

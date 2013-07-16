<?php
/**
* @package WPUndisclosed
* @version 1.0
*/

// ----------------------------------------
//	This class provides install and uninstall 
//	routines for the WP undisclosed plugin.
// ----------------------------------------

if ( ! class_exists('UndisclosedInstall') ) :
class UndisclosedInstall {
	
	// --------------------------------------------------
	// de-/activation/uninstall hooks
	// --------------------------------------------------
	static function activate( ) {
		if ( ! current_user_can( 'activate_plugins' ) )
			return;
			
		global $wpdb;

		self::_install_capabilities_table( );
		
		if ( is_multisite() && is_network_admin() ) {
			$blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
			foreach ( $blogids as $blog_id) {
				switch_to_blog($blog_id);
				self::_install_posts_table( );
			}
		} else {
			self::_install_posts_table( );
		}
		
	}
	static function deactivate( $networkwide ) {
		if ( ! current_user_can( 'activate_plugins' ) )
			return;

		
	}
	static function uninstall() {
		if ( ! current_user_can( 'activate_plugins' ) )
			return;
		
		self::_remove_custom_caps();
		
		self::_uninstall_capabilities_table();
		
		if (function_exists('is_multisite') && is_multisite() ) {
			$blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
			foreach ( $blogids as $blog_id) {
				switch_to_blog($blog_id);
				self::_uninstall_posts_table( );
			}
		} else {
			self::_uninstall_posts_table( );
		}
		restore_currnet_blog();
	}
	

	// --------------------------------------------------
	// posts table
	// --------------------------------------------------
	private static function _install_posts_table( ) {
		global $wpdb;
		$wpdb->query("ALTER IGNORE TABLE $wpdb->posts ADD COLUMN post_comment_cap varchar(128) NOT NULL DEFAULT 'exist' AFTER `post_status`;");
		$wpdb->query("ALTER IGNORE TABLE $wpdb->posts ADD COLUMN post_edit_cap varchar(128) NOT NULL DEFAULT 'exist' AFTER `post_status`;");
		$wpdb->query("ALTER IGNORE TABLE $wpdb->posts ADD COLUMN post_view_cap varchar(128) NOT NULL DEFAULT 'exist' AFTER `post_status`;");
		$wpdb->query("ALTER IGNORE TABLE $wpdb->posts ADD INDEX `view_cap` (`post_view_cap`);");
		$wpdb->query("ALTER IGNORE TABLE $wpdb->posts ADD INDEX `edit_cap` (`post_edit_cap`);");
		$wpdb->query("ALTER IGNORE TABLE $wpdb->posts ADD INDEX `comment_cap` (`post_comment_cap`);");
	}
	private static function _uninstall_posts_table( ) {
		global $wpdb;
		$wpdb->query("ALTER IGNORE TABLE $wpdb->posts DROP COLUMN post_comment_cap;");
		$wpdb->query("ALTER IGNORE TABLE $wpdb->posts DROP COLUMN post_edit_cap;");
		$wpdb->query("ALTER IGNORE TABLE $wpdb->posts DROP COLUMN post_view_cap;");
		$wpdb->query("ALTER IGNORE TABLE $wpdb->posts DROP INDEX (`view_cap`);");
		$wpdb->query("ALTER IGNORE TABLE $wpdb->posts DROP INDEX (`edit_cap`);");
		$wpdb->query("ALTER IGNORE TABLE $wpdb->posts DROP INDEX (`comment_cap`);");
	}
	
	
	// --------------------------------------------------
	// capabilities table
	// --------------------------------------------------
	private static function _install_capabilities_table( ) {
		global $wpdb;
		$table_name = $wpdb->base_prefix . WPUND_USERLABEL_TABLE;
		if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
			$sql = "CREATE TABLE " . $table_name . " (
				ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				cap_title varchar(64) NOT NULL,
				capability varchar(128) NOT NULL,
				blog_id bigint(20) NOT NULL,
				PRIMARY KEY id (`id`),
				UNIQUE KEY capability (`capability`),
				KEY blog_id (`blog_id`)
				);";

			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);
		}
	}
	
	
	private static function _uninstall_capabilities_table( ) {
		global $wpdb;
		$table_name = $wpdb->base_prefix . WPUND_USERLABEL_TABLE;
		$wpdb->query("DROP TABLE IF EXISTS $table_name");
	}
	// --------------------------------------------
	// remove Caps from User
	private function _uninstall_custom_caps( ) {
		global $wpdb;
		$table_name = $wpdb->base_prefix . WPUND_USERLABEL_TABLE;
		$uids = $wpdb->get_col( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key LIKE '{$wpdb->base_prefix}%capabilities' AND meta_value LIKE '%\"".WPUND_USERLABEL_PREFIX."%'" );
		$caps = $wpdb->get_col( "SELECT capability FROM $table_name" );
		foreach ( $uids as $uid) {
			$user = new WP_User( $uid );
			foreach ( $caps as $capability )
				if ( $user->has_cap() )
					$user->remove_cap( $capability );
		}
		
	}
	
}
endif;




?>
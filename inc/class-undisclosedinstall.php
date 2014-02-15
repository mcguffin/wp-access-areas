<?php
/**
* @package WP_AccessAreas
* @version 1.0.0
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
		global $wpdb;

		if ( ! current_user_can( 'activate_plugins' ) )
			return;

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
//		self::uninstall();
	}
	static function uninstall() {
		global $wpdb;
		if ( ! current_user_can( 'activate_plugins' ) )
			return;
		
		self::_uninstall_custom_caps();
		self::_uninstall_capabilities_table();
		
		if (function_exists('is_multisite') && is_multisite() && is_network_admin() ) {
			$blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
			foreach ( $blogids as $blog_id) {
				switch_to_blog($blog_id);
				self::_uninstall_posts_table( );
				restore_current_blog();
			}
		} else {
			self::_uninstall_posts_table( );
		}
	}
	
	function activate_for_blog( $blog_id ) {
		switch_to_blog( $blog_id );
		self::_install_posts_table( );
		restore_current_blog();
	}
	

	// --------------------------------------------------
	// posts table
	// --------------------------------------------------
	private static function _install_posts_table( ) {
		global $wpdb;
		// , 'edit_cap'=>'post_edit_cap' will be used later.
		$cols = array( 'comment_cap'=>'post_comment_cap' , 'edit_cap'=>'post_edit_cap' , 'view_cap'=>'post_view_cap' );
		foreach ( $cols as $idx => $col ) {
			$c = $wpdb->get_results("SHOW COLUMNS FROM $wpdb->posts LIKE '$col'");
			if (empty($c))
				$wpdb->query("ALTER TABLE $wpdb->posts ADD COLUMN $col varchar(128) NOT NULL DEFAULT 'exist' AFTER `post_status`;");
				
			$i = $wpdb->query("SHOW INDEX FROM $wpdb->posts WHERE Key_name = '$idx'");
			if (empty($i))
				$wpdb->query("ALTER TABLE $wpdb->posts ADD INDEX `$idx` (`$col`);");
		}
	}
	private static function _uninstall_posts_table( ) {
		global $wpdb;
		// , 'edit_cap'=>'post_edit_cap' will be used later.
		$cols = array( 'comment_cap'=>'post_comment_cap' , 'edit_cap'=>'post_edit_cap' , 'view_cap'=>'post_view_cap' );
		foreach ( $cols as $idx => $col ) {
			$c = $wpdb->get_results("SHOW COLUMNS FROM $wpdb->posts LIKE '$col'");
			if (!empty($c))
				$wpdb->query("ALTER TABLE $wpdb->posts DROP COLUMN $col;");
				
			$i = $wpdb->query("SHOW INDEX FROM $wpdb->posts WHERE Key_name = '$idx'");
			if (!empty($i))
				$wpdb->query("ALTER TABLE $wpdb->posts DROP INDEX ('$idx');");
		}
	}
	
	
	// --------------------------------------------------
	// capabilities table
	// --------------------------------------------------
	private static function _install_capabilities_table( ) {
		global $wpdb;
		$table_name = $wpdb->base_prefix . WPUND_USERLABEL_TABLE;
		if ($wpdb->get_var("show tables like '$table_name'") != $table_name) {
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
		if ($wpdb->get_var("show tables like '$table_name'") == $table_name)
			$wpdb->query("DROP TABLE IF EXISTS $table_name");
	}
	// --------------------------------------------
	// remove Caps from User
	private function _uninstall_custom_caps( ) {
		global $wpdb;

		$query =  "SELECT * FROM $wpdb->usermeta WHERE meta_key LIKE '{$wpdb->base_prefix}%capabilities' AND meta_value LIKE '%\"".WPUND_USERLABEL_PREFIX."%'" ;
		$usermeta = $wpdb->get_results($query);
		foreach ( $usermeta as $meta) {
			$caps = maybe_unserialize($meta->meta_value);
			foreach ( array_keys($caps) as $key ) 
				if ( strpos( $key , WPUND_USERLABEL_PREFIX ) === 0 )
					unset($caps[$key]);
			$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->usermeta SET meta_value=%s WHERE umeta_id=%d",
				serialize( $caps ), 
				$meta->umeta_id
			) );
		}
		$wpdb->query( "DELETE FROM $wpdb->usermeta WHERE meta_key = '".WPUND_GLOBAL_USERMETA_KEY."'" );

	}
	
}
endif;

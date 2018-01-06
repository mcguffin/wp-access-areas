<?php


namespace AccessAreas\Model;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}

use AccessAreas\Core;

class ModelPost extends Core\PluginComponent {


	/**
	 *	@inheritdoc
	 */
	public function activate() {
		$this->install_posts_table();
	}

	/**
	 *	...
	 */
	private function install_posts_table( ) {

		global $wpdb;

		$cols = array( 'comment_cap'=>'post_comment_cap' , 'edit_cap'=>'post_edit_cap' , 'view_cap'=>'post_view_cap' );

		foreach ( $cols as $idx => $col ) {

			$c = $wpdb->get_results( $wpdb->prepare( "SHOW COLUMNS FROM $wpdb->posts LIKE %s", $col ) );

			if ( empty( $c ) ) {
				$wpdb->query("ALTER TABLE $wpdb->posts ADD COLUMN $col varchar(128) NOT NULL DEFAULT 'exist' AFTER `post_status`;");
			}

			$i = $wpdb->get_results( $wpdb->prepare( "SHOW INDEX FROM $wpdb->posts WHERE Key_name = %s", $idx ) );

			if ( empty( $i ) ) {
				$wpdb->query("ALTER TABLE $wpdb->posts ADD INDEX `$idx` (`$col`);");
			}
		}
	}

	/**
	 *	...
	 */
	private function uninstall_posts_table( ) {
		global $wpdb;
		// , 'edit_cap'=>'post_edit_cap' will be used later.
		$cols = array( 'comment_cap'=>'post_comment_cap' , 'edit_cap'=>'post_edit_cap' , 'view_cap'=>'post_view_cap' );
		foreach ( $cols as $idx => $col ) {

			$c = $wpdb->get_results( $wpdb->prepare( "SHOW COLUMNS FROM $wpdb->posts LIKE %s", $col ) );

			if ( ! empty( $c ) ) {
				$wpdb->query("ALTER TABLE $wpdb->posts DROP COLUMN $col;");
			}

			$i = $wpdb->get_results( $wpdb->prepare( "SHOW INDEX FROM $wpdb->posts WHERE Key_name = %s", $idx ) );

			if ( ! empty( $i ) ) {
				$wpdb->query("ALTER TABLE $wpdb->posts DROP INDEX ('$idx');");
			}
		}
	}

	/**
	 *	@inheritdoc
	 */
	public function deactivate() {
	}

	/**
	 *	@inheritdoc
	 */
	public function uninstall() {
		// drop table
		$this->uninstall_posts_table();

		global $wpdb;
		$wpdb->query($wpdb->prepare("DELETE FROM $wpdb->postmeta WHERE meta_key LIKE %s", $wpdb->esc_like('_wpaa_').'%') );
	}

	/**
	 *	@inheritdoc
	 */
	public function upgrade( $new_version, $old_version ) {
		if ( version_compare( $old_version, '2.0.0', '<' ) ) {
			$this->upgrade_1x();
		}

	}

	/**
	 *	Upgrade from version 1.x
	 */
	private function upgrade_1x() {
		global $wpdb;
		$posts = $wpdb->get_results($wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key=%s AND meta_value=%s" , '_wpaa_post_behavior', 'login' ));
		// post behavior
		foreach ( $posts as $p ) {
			update_post_meta( $p->post_id, '_wpaa_post_behavior', 'page' );
			update_post_meta( $p->post_id, '_wpaa_post_behavior_login_redirect', true );
		}

	}

}

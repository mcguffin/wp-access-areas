<?php

namespace AccessAreas\Compat\WPMU;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}


use AccessAreas\Core;
use AccessAreas\Admin;
use AccessAreas\Settings;

class AdminUsers extends Users {

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {
		add_action( 'wpaa_grant_access', array( $this, 'grant_access', 10, 3 ) );
		add_action( 'wpaa_revoke_access', array( $this, 'revoke_access', 10, 3 ) );
		add_filter( 'wpaa_user_is_admin', array( $this, 'user_is_admin' ), 10, 2 );

		add_filter('map_meta_cap',array( $this, 'map_meta_cap'), 20, 4 );

		//add_filter( 'pre_update_option_wpaa_enable_assign_cap', array( $this, 'process_admin_role_cap' ), 9, 2 );
		add_filter( 'wpaa_settings_editable_role_caps', array( $this, 'editable_role_caps' ) );
	}

	/**
	 *	@filter wpaa_settings_editable_role_caps
	 */
	public function editable_role_caps( $caps ) {
		$add_caps = array();
		if ( current_user_can('manage_network_users' ) ) {
			$add_caps = array(
				'wpaa_manage_access_areas'	=> __( 'Manage Access Areas', 'wp-access-areas' ),
				'wpaa_edit_role_caps'		=> __( 'Edit Role Capabilities', 'wp-access-areas' ),
				'wpaa_grant_access'			=> __( 'Grant Access', 'wp-access-areas' ),
				'wpaa_revoke_access'		=> __( 'Revoke Access', 'wp-access-areas' ),
			);
		}
		return $add_caps + $caps;
	}

	/**
	 *	@filter pre_update_option_wpaa_enable_assign_cap
	 */
	public function process_admin_role_cap( $value ) {
		if ( current_user_can('manage_network_users') ) {
			// grant or revoke wpaa_edit_role_caps to administrator role
		}
	}

	/**
	 *	@filter map_meta_cap
	 */
	public function map_meta_cap( $caps, $cap, $user_id, $args ) {
		if ( $cap === 'wpaa_edit_role_caps' ) {
			if ( is_super_admin() ) { // super admins can edit
				$idx = array_search( 'do_not_allow', $caps );
				if ( false !== $idx ) {
					unset( $caps[$idx] );
				}
			}
		}
		return $caps;
	}

	/**
	 *	@filter wpaa_user_is_admin
	 */
	public function user_is_admin( $is_admin, $wp_user ) {
		return is_super_admin( $wp_user->ID );
	}

	/**
	 *	@action wpaa_grant_access
	 */
	public function grant_access( $user, $capability, $access_area ) {
		if ( intval( $access_area->blog_id ) === 0 ) {
			//
			$global_caps = $this->get_global_caps( $user );
			$global_caps[ $access_area->capability ] = true;

			update_user_option( $user->ID, $this->option_name, $global_caps, true );

			// sync along other blogs
			$blog_ids = $this->get_blog_ids_of_user( $user->ID );
			foreach ( $blog_ids as $blog_id  ) {
				switch_to_blog( $blog_id );
				$user->remove_cap( $capability );
				restore_current_blog();
			}

		}
	}

	/**
	 *	@action wpaa_revoke_access
	 */
	public function revoke_access( $user, $capability, $access_area ) {
		if ( intval( $access_area->blog_id ) === 0 ) {

			$global_caps = $this->get_global_caps( $user );

			if ( isset( $global_caps[ $access_area->capability ] ) ) {
				unset( $global_caps[ $access_area->capability ] );
			}

			update_user_option( $user->ID, $this->option_name, $global_caps, true );

			// sync along other blogs
			$blog_ids = $this->get_blog_ids_of_user( $user->ID );
			foreach ( $blog_ids as $blog_id  ) {
				switch_to_blog( $blog_id );
				$user->remove_cap( $capability );
				restore_current_blog();
			}
		}
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

		$usermetas = $wpdb->get_results("SELECT * FROM $wpdb->usermeta WHERE meta_key = 'undisclosed_global_capabilities'");
		foreach ( $usermetas as $usermeta ) {
			$caps = maybe_unserialize( $usermeta->mata_value );
			$new_caps = array();
			foreach ( $caps as $cap ) {
				$new_caps[$cap] = true;
			}
			update_user_option( $user->ID, $this->option_name, $new_caps, true );
			delete_user_meta( $usermeta->user_id, $usermeta->meta_key );
		}
	}

}

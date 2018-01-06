<?php


namespace AccessAreas\Model;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}

use AccessAreas\Core;

class ModelUser extends Core\PluginComponent {

	protected function __construct() {
		add_action('wpaa_before_delete',array($this,'revoke_from_all'));
	}


	public function revoke_from_all( $access_area_id ) {
		$model = ModelAccessAreas::instance()
		$access_area = $model->fetch_one_by( 'id', $access_area_id );
		$users = get_users();
		foreach ( $users as $user ) {
			$this->revoke_user_access( $user, $access_area );
		}
	}

	/**
	 *	Grant Access
	 *	@param WP_User $user
	 *	@param object $access_area
	 *	@return bool
	 */
	public function grant_user_access( $user, $access_area ) {
		if ( ! $user->has_cap( $access_area->capability ) ) {
			$user->add_cap( $access_area->capability , true );
			do_action( 'wpaa_grant_access', $user, $access_area->capability, $access_area );
			do_action( "wpaa_grant_{$access_area->capability}", $user );
			return true;
		}
		return false;
	}

	/**
	 *	Revoke Access
	 *	@param WP_User $user
	 *	@param object $access_area
	 *	@return bool
	 */
	public function revoke_user_access( $user, $access_area ) {
		if ( $user->has_cap( $access_area->capability ) ) {
			$user->remove_cap( $access_area->capability, true );
			do_action( 'wpaa_grant_access', $user, $access_area->capability, $access_area );
			do_action( "wpaa_grant_{$access_area->capability}", $user );
			return true;
		}
		return false;
	}


	/**
	 *	@inheritdoc
	 */
	public function activate() {
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
		$model = Model\ModelAccessAreas::instance();
		$access_areas = $model->fetch_available();
		$users = get_users();
		foreach ( $users as $user ) {
			foreach ( $access_areas as $access_area ) {
				// much faster than $user->remove_cap( $access_area->capability )
				if ( isset( $user->caps[ $access_area->capability ] ) ) {
					unset( $user->caps[ $access_area->capability ] );
				}
			}
			update_user_meta( $user->ID, $user->cap_key, $user->caps );
		}
	}

	/**
	*	@inheritdoc
	 */
	public function upgrade( $new_version, $old_version ) {
	}

}

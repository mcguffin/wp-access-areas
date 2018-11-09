<?php

namespace AccessAreas\Admin;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}

use AccessAreas\Core;


class Admin extends Core\Singleton {

	private $core;

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {

		$this->core = Core\Core::instance();

		add_action( 'admin_init', array( $this , 'register_assets' ) );

		add_action( 'print_media_templates', array( $this, 'print_media_templates' ) );

		add_filter('map_meta_cap',array( $this, 'map_meta_cap'),10,4);
	}

	public function map_meta_cap( $caps, $cap, $user_id, $args ) {
		if ( $cap === 'edit_wpaa_role_caps' ) {
			if ( count( $args ) && in_array( $args[0], get_userdata( $user_id )->roles ) ) { // don't change own role!
				$caps[] = 'do_not_allow';
			}
		}
		return $caps;
	}

	/**
	 *	@action print_media_templates
	 */
	public function print_media_templates() {
		include implode( DIRECTORY_SEPARATOR, array( ACCESS_AREAS_DIRECTORY, 'include','templates','media-templates.php' ) );
	}

	/**
	 *	Admin init
	 *	@action admin_init
	 */
	function admin_init() {
		// check for upgradeability ...
	}



	/**
	 *	Register admin Assets
	 *
	 *	@action admin_init
	 */
	function register_assets() {

		// post editor and settings page
		wp_register_style( 'access-areas-settings', $this->core->get_asset_url( '/css/admin/settings.css' ) );

		wp_register_script( 'access-areas-settings', $this->core->get_asset_url( '/js/admin/settings.js' ) );


		// post editor and settings page
		wp_register_style( 'access-areas-posts', $this->core->get_asset_url( '/css/admin/posts.css' ) );

		// admin access areas
		wp_register_style( 'access-areas-admin' , $this->core->get_asset_url( '/css/admin/access-areas.css' ) );

		wp_register_script( 'access-areas-admin' , $this->core->get_asset_url( 'js/admin/access-areas.js' ), array( 'jquery', 'wp-api' ), null, true );
		wp_localize_script( 'access-areas-admin' , 'access_areas_admin' , array(
			'l10n'	=> array(
				'createAccessArea'	=> __('Create Access Area','wp-access-areas'),
				'editAccessArea'	=> __('Edit Access Area','wp-access-areas'),
				'grantAccess'		=> __('Grant Access','wp-access-areas'),
				'revokeAccess'		=> __('Revoke Access','wp-access-areas'),
			),
			'options'	=> array(
				'current_blog_id'	=> apply_filters( 'access_areas_current_blog_id', get_current_blog_id() ),
			),
		) );
	}

}

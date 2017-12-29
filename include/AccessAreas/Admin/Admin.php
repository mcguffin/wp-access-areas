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
		// check for upgradeability

	}

	/**
	 *	Enqueue options Assets
	 *	@action admin_print_scripts
	 */
	function register_assets() {

		wp_register_style( 'access-areas-admin' , $this->core->get_asset_url( '/css/admin/access-areas.css' ) );

		wp_register_script( 'access-areas-admin' , $this->core->get_asset_url( 'js/admin/access-areas.js' ), array( 'jquery', 'wp-api' ), null, true );
		wp_localize_script( 'access-areas-admin' , 'access_areas_admin' , array(
			'l10n'	=> array(
				'createAccessArea'	=> __('Create Access Area','wp-access-areas'),
				'editAccessArea'	=> __('Edit Access Area','wp-access-areas'),
				'grantAccess'		=> __('Grant Access','wp-access-areas'),
				'revokeAccess'		=> __('Revoke Access','wp-access-areas'),
			)
		) );
	}

}

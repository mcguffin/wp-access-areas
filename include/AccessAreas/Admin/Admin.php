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

		add_action( 'admin_init', array( $this , 'admin_init' ) );
		add_action( 'admin_print_scripts', array( $this , 'enqueue_assets' ) );
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
	function enqueue_assets() {
		wp_enqueue_style( 'access_areas-admin' , $this->core->get_asset_url( '/css/admin/admin.css' ) );

		wp_enqueue_script( 'access_areas-admin' , $this->core->get_asset_url( 'js/admin/admin.js' ) );
		wp_localize_script('access_areas-admin' , 'access_areas_admin' , array(
		) );
	}

}

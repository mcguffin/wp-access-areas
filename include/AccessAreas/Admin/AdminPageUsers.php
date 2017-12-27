<?php

namespace AccessAreas\Admin;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}

use AccessAreas\Core;


class AdminPageUsers extends AdminPage {

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {
		parent::__construct();

		$this->core = Core\Core::instance();

		add_action( 'admin_init' , array( $this, 'admin_init' ) );
		add_action( "admin_print_scripts" , array( $this, 'enqueue_assets' ) );
		add_action( 'admin_menu' , array( $this, 'add_admin_page' ) );
	}
	/**
	 * 	Add Admin page to menu
	 *
	 *	@action	admin_menu
	 */
	function add_admin_page() {
		$page_hook = add_users_page( __( 'WP Access Areas (users)' , 'wp-access-areas' ), __( 'WP Access Areas' , 'wp-access-areas' ), 'manage_options', 'access_areas-users', array( $this, 'render_page' ) );
		add_action( "load-{$page_hook}" , array( $this, 'enqueue_assets' ) );
	}

	/**
	 * 	Add Admin page to menu
	 */
	function render_page() {
		?><div class="wrap"><?php
			?><h2><?php _e( 'WP Access Areas (users)' , 'wp-access-areas' ); ?></h2><?php
			?><p><?php _e( 'Content for users' , 'wp-access-areas' ); ?></p><?php
		?></div><?php
	}


	function enqueue_assets() {
		wp_enqueue_style( 'access_areas-admin-page-users' , $this->core->get_asset_url( '/css/admin/page/users.css' ) );
		wp_enqueue_script( 'access_areas-admin-page-users' , $this->core->get_asset_url( 'js/admin/page/users.js' ) );
		wp_localize_script('access_areas-admin-page-users' , 'access_areas_admin_page' , array(
		) );
	}

	/**
	 * Admin init
	 */
	function admin_init() {
	}

}

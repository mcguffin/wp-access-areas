<?php

namespace AccessAreas\Compat\WPMU;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}


use AccessAreas\Core;
use AccessAreas\Admin;


class NetworkAdmin extends Core\Singleton {

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {
		// is_network_admin() => true
		if ( current_user_can( 'manage_network_users' ) ) {
			$admin = \AccessAreas\Admin\AdminPageAccessAreas::instance();
			add_action( 'network_admin_menu', array( $admin, 'add_admin_page' ));
			add_filter( 'access_areas_current_blog_id',array($this,'return_zero_string'));

		}
	}
	/**
	 *	@filter access_areas_current_blog_id
	 */
	public function return_zero_string() {
		return '0';
	}

}

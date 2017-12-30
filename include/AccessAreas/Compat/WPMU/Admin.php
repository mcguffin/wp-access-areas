<?php

namespace AccessAreas\Compat\WPMU;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}


use AccessAreas\Core;
use AccessAreas\Admin;


class Admin extends Core\PluginComponent {

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {
		$admin = AccessAreas\Admin\AdminPageAccessAreas::instance();
		if ( current_user_can( 'manage_network_users' ) ) {
			add_action( 'network_admin_menu', array( $admin, 'add_admin_page' ));
		}
	}

}

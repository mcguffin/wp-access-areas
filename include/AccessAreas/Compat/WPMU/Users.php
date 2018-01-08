<?php

namespace AccessAreas\Compat\WPMU;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}


use AccessAreas\Core;
use AccessAreas\Admin;


class Users extends Core\Singleton {

	protected $option_name = 'wpaa_gloabl_access_areas';

	/**
	 *	@param WP_User	$user
	 *	@return array
	 */
	public function get_global_caps($user) {
		get_user_option( $user->id, $this->option_name );
	}

}

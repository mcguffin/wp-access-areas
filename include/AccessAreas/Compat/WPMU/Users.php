<?php

namespace AccessAreas\Compat\WPMU;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}


use AccessAreas\Admin;
use AccessAreas\Core;
use AccessAreas\Model;


class Users extends Core\Singleton {

	protected $option_name = 'wpaa_gloabl_access_areas';

	protected function __construct() {
	//	add_filter( 'wpaa_user_can_access_area', array( $this, 'can_access_area' ), 10, 3 );
	}


	/**
	 *	@filter wpaa_user_can_access_area
	 *	@return boolean
	 */
	public function can_access_area( $can, $capability, $args ) {
		$user = Model\ModelUser::instance();
		$model = Model\ModelAccessAreas::instance();
		$access_area = $model->fetch_one_by( 'capability', $capability );
		// not super admin but blog admin and aa is in current blog scope
		if ( ! $user->current_user_is_admin() && ( current_user_can('administrator') && $access_area->blog_id === get_current_blog_id() ) ) {
			$can = true;
		}
		return $can;
	}

	/**
	 *	@param $user_id int|WP_User
	 *	@return array
	 */
	public function get_blog_ids_of_user( $user_id ) {
		global $wpdb;
		$blog_ids = array();
		$meta = get_user_meta( $user_id );

		foreach ( $meta as $key => $value ) {
			// Like in https://developer.wordpress.org/reference/functions/get_blogs_of_user/
			if ( 'capabilities' !== substr( $key, -12 ) ||
				$wpdb->base_prefix && 0 !== strpos( $key, $wpdb->base_prefix )
			) {
				continue;
			}

	        $blog_id = str_replace( array( $wpdb->base_prefix, '_capabilities' ), '', $key );

	        if ( ! is_numeric( $blog_id ) )
	            continue;

	        $blog_ids[] = (int) $blog_id;
		}
		return $blog_ids;
	}

	/**
	 *	@param WP_User	$user
	 *	@return array
	 */
	public function get_global_caps( $user ) {
		get_user_option( $user->ID, $this->option_name );
	}

}

<?php

namespace AccessAreas\Compat;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}


use AccessAreas\Core;
use AccessAreas\Model;
use AccessAreas\Settings;


class ACF extends Core\Singleton {

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {
		// bind late
		add_filter( 'map_meta_cap' , array( $this, 'map_meta_cap' ) , 20 , 4 );
	}

	/**
	 *	Deny edit access caps for field groups
	 *
	 *	@filter map_meta_cap
	 */
	public function map_meta_cap( $caps, $cap, $user_id, $args ) {

		switch ( $cap ) {
			case 'wpaa_set_view_cap': // belongs to post!
			case 'wpaa_set_edit_cap':
			case 'wpaa_set_comment_cap':

				if ( 'acf-field-group' === get_post_type( isset($args[0]) ? $args[0] : null ) ) {
					return array( 'do_not_allow' );
				}

				break;
		}
		return $caps;
	}
}

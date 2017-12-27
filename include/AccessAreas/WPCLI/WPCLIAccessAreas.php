<?php

namespace AccessAreas\WPCLI;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}

use AccessAreas\Core;

class WPCLIAccessAreas extends Core\Singleton {

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {
		\WP_CLI::add_command( 'access-areas', 'AccessAreas\WPCLI\Commands\AccessAreas', array(
//			'before_invoke'	=> 'a_callable',
//			'after_invoke'	=> 'another_callable',
			'shortdesc'		=> 'WP Access Areas commands',
//			'synopsis'		=> 'wp access-areas <command> <args>',
//			'when'			=> 'before_wp_load',
			'is_deferred'	=> false,
		) );
	}

}

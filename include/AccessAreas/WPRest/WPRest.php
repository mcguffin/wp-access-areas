<?php

namespace AccessAreas\WPRest;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}

use AccessAreas\Core;

class WPRest extends Core\Singleton {

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {
		parent::__construct();

		add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );
	}

	/**
	 *	@return string
	 */
	public function get_namespace() {
		return 'wp/v2';
	}

	/**
	 *	@action widgets_init
	 */
	public function rest_api_init(){
		WPRestAccessAreas::instance();
	}

}
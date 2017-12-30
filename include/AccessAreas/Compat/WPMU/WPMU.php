<?php

namespace AccessAreas\Compat\WPMU;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}


use AccessAreas\Core;


class WPMU extends Core\PluginComponent {

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {
		// add_action('wpmu_new_blog' , array( $this, 'set_network_roles_for_blog' ) , 10 , 1 );
		// add_action('wpmu_upgrade_site' , array( $this, 'set_network_roles_for_blog' ) , 10 ,1 );
		if ( is_network_admin() ) {
			Admin::instance();
		}
	}

	/**
	 *	@inheritdoc
	 */
	 public function activate() {

	 }

	 /**
	  *	@inheritdoc
	  */
	 public function deactivate() {

	 }

	 /**
	  *	@inheritdoc
	  */
	 public function uninstall() {
		 // iterate blogs, alter posts table
	 }

	/**
 	 *	@inheritdoc
	 */
	public function upgrade( $new_version, $old_version ) {
	}

}

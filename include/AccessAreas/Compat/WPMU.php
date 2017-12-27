<?php

namespace AccessAreas\Compat;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}


use AccessAreas\Core;


class WPMU extends Core\PluginComponent {

	protected function __construct() {
		var_dump($this);exit();
	}

	/**
	 *	@inheritdoc
	 */
	 public function activate(){

	 }

	 /**
	  *	@inheritdoc
	  */
	 public function deactivate(){

	 }

	 /**
	  *	@inheritdoc
	  */
	 public function uninstall() {
		 // remove content and settings
	 }

	/**
 	 *	@inheritdoc
	 */
	public function upgrade( $new_version, $old_version ) {
	}

}
